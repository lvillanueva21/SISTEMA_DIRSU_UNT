// /sistema_web/informe_semestral/js/observaciones_ui.js
(function () {
  const BTN_ID = 'btnObservaciones';
  const MODAL_ID = 'obsModalDirsu';
  // Ruta relativa para soportar despliegues en /sistema_web o /algo/sistema_web.
  const API = (id_py) => `../informe_semestral/api/observaciones_estado.php?id_py=${encodeURIComponent(id_py)}`;

  function ensureStyles() {
  if (document.getElementById('obs-ui-style')) return;
  const css = document.createElement('style');
  css.id = 'obs-ui-style';
  css.textContent = `
    /* Punto rojo + vibración del botón */
    #btnObservaciones.has-obs { position: relative; }
    #btnObservaciones.has-obs .obs-dot{
      position:absolute; top:-4px; right:-4px; width:10px; height:10px;
      background:#dc3545; border-radius:50%; box-shadow:0 0 0 2px #ffc107;
    }
    @keyframes obs-shake {
      0%{transform:translate(0,0)} 25%{transform:translate(1px,0)}
      50%{transform:translate(0,0)} 75%{transform:translate(-1px,0)} 100%{transform:translate(0,0)}
    }
    #btnObservaciones.has-obs.animate{ animation: obs-shake .8s ease-in-out infinite; }

    /* SOLO el header en warning */
    .obs-warning-head { background:#fff3cd; border-bottom:1px solid #ffe69c; }

    /* Cuerpo blanco por defecto; la tabla con cabecera en warning suave */
    .table-obs thead tr { background:#fff3cd; }
    .table-obs th, .table-obs td { border:1px solid #dee2e6 !important; }

    /* Botón de cierre compatible BS4/BS5 */
    .obs-close{
      position:absolute; right:.75rem; top:.5rem; font-size:1.25rem; line-height:1;
      background:transparent; border:0; opacity:.6; cursor:pointer;
    }
    .obs-close:hover{ opacity:1; }
  `;
  document.head.appendChild(css);
}


  function fmtDT(s) {
    if (!s) return '—';
    // s viene como "YYYY-mm-dd HH:ii:ss" Lima
    try {
      // Mostrar dd/mm/YYYY HH:ii
      const [d, t] = s.split(' ');
      if (!d) return s;
      const [Y, M, D] = d.split('-');
      const hm = (t || '').slice(0,5);
      return `${D}/${M}/${Y}${hm ? ' ' + hm : ''}`;
    } catch (_) { return s; }
  }

  function buildRubricaTable(det) {
    if (!det || !Array.isArray(det.aspectos) || det.aspectos.length === 0) {
      return '<div class="text-muted">Sin detalles de aspectos.</div>';
    }
    let html = '';
    html += '<div class="d-flex justify-content-between align-items-center mb-1">';
    html += '  <div class="fw-semibold">Calificación total</div>';
    html += `  <div class="badge bg-warning text-dark">${det.total || 0} / 20</div>`;
    html += '</div>';

    html += '<div class="table-responsive"><table class="table table-sm table-obs">';
    html += '<thead><tr><th>Aspecto</th><th class="text-center" style="width:140px;">Nota</th><th>Observación</th></tr></thead><tbody>';
    det.aspectos.forEach(a => {
      html += '<tr>';
      html += `<td>${escapeHtml(a.aspecto)}</td>`;
      html += `<td class="text-center"><strong>(${a.nota})</strong> ${escapeHtml(a.notaTx)}</td>`;
      html += `<td>${nl2br(escapeHtml(a.obs))}</td>`;
      html += '</tr>';
    });
    html += '</tbody></table></div>';
    return html;
  }

  function escapeHtml(s){ return (s==null?'':String(s))
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;'); }
  function nl2br(s){ return String(s).replace(/\n/g,'<br>'); }

  function ensureModal() {
  let modal = document.getElementById('obsModalDirsu');
  if (modal) return modal;

  modal = document.createElement('div');
  modal.id = 'obsModalDirsu';
  modal.className = 'modal fade';
  modal.tabIndex = -1;

  modal.innerHTML = `
    <div class="modal-dialog modal-lg modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header obs-warning-head" style="position:relative;">
          <h5 class="modal-title"><i class="fas fa-exclamation-triangle"></i> Observaciones</h5>

          <!-- Cierre compatible con BS4 y BS5 -->
          <button type="button" class="obs-close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>

        <div class="modal-body" id="obsModalDirsu-body">
          <div class="text-center text-muted">Cargando…</div>
        </div>

        <div class="modal-footer bg-white">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  `;
  document.body.appendChild(modal);
  return modal;
}


  function showModal() {
    const el = ensureModal();
    if (window.jQuery && typeof jQuery.fn.modal === 'function') jQuery(el).modal('show');
    else if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(el).show();
    else el.style.display = 'block';
  }

  async function fetchEstado(id_py){
    const resp = await fetch(API(id_py), { cache: 'no-store' });
    if (!resp.ok) throw new Error(`HTTP ${resp.status}`);
    const data = await resp.json();
    if (!data || data.ok !== true) throw new Error(data && data.error ? data.error : 'Respuesta inválida');
    return data;
  }

  function paintButton(btn, hasObs){
    btn.classList.toggle('has-obs', !!hasObs);
    btn.classList.toggle('animate', !!hasObs);
    // dot
    let dot = btn.querySelector('.obs-dot');
    if (hasObs) {
      if (!dot) {
        dot = document.createElement('span');
        dot.className = 'obs-dot';
        btn.appendChild(dot);
      }
    } else if (dot) {
      dot.remove();
    }
  }

  function renderBody(data){
  const body = document.getElementById('obsModalDirsu-body');
  if (!body) return;

  const bloques = [];

  const mkHeader = (tipoTxt, det) => {
    const oficina = det?.oficina_nom
      ? `${escapeHtml(det.oficina_nom)}${det.oficina_cod ? ' ('+escapeHtml(det.oficina_cod)+')' : ''}`
      : '—';
    const obsAt = fmtDT(det?.obs_at || '');
    const lim   = fmtDT(det?.limite || '');
    return `
      <div class="mb-2"><strong>Tipo:</strong> ${tipoTxt}</div>
      <div class="mb-2"><strong>Oficina:</strong> ${oficina}</div>
      <div class="mb-2"><strong>Fecha/Hora de observación:</strong> ${obsAt}</div>
      <div class="mb-2"><strong>Fecha máxima de subsanación:</strong> ${lim}</div>
    `;
  };

  if (data.cotejo) {
    const det = data.cotejo;
    let html = `<h6 class="mb-2">Observación — Lista de Cotejo</h6>${mkHeader('Lista de Cotejo', det)}`;
    const obs = (det.obs_text || '').trim();
    html += `<hr class="my-2"><div class="fw-semibold mb-1">Observación</div>`;
    html += `<div class="border rounded p-2 bg-light">${nl2br(escapeHtml(obs || 'Sin Observación'))}</div>`;
    bloques.push(`<div class="mb-3">${html}</div>`);
  }

  if (data.rubrica) {
    const det = data.rubrica;
    let html = `<h6 class="mb-2">Observación — Rúbrica</h6>${mkHeader('Rúbrica', det)}`;
    html += `<hr class="my-2">` + buildRubricaTable(det);
    bloques.push(`<div class="mb-1">${html}</div>`);
  }

  if (bloques.length === 0) {
    body.innerHTML = `<div class="alert alert-warning mb-0">No has recibido observaciones por el momento.</div>`;
  } else {
    // CUERPO EN BLANCO (solo agregamos contenedor blanco)
    body.innerHTML = `<div class="bg-white">${bloques.join('<hr class="my-3">')}</div>`;
  }
}


  async function openModalFlow(btn){
    const id_py = parseInt(btn.getAttribute('data-id-py') || '0', 10) || 0;
    const body = document.getElementById(`${MODAL_ID}-body`);
    if (body) body.innerHTML = `<div class="text-center text-muted">Cargando…</div>`;
    showModal();
    try {
      const data = await fetchEstado(id_py);
      renderBody(data);
    } catch (e) {
      if (body) body.innerHTML = `<div class="alert alert-danger mb-0">No se pudo cargar observaciones.</div>`;
      console.error('obs error:', e);
    }
  }

  async function init(){
    ensureStyles();
    const btn = document.getElementById(BTN_ID);
    if (!btn) return;
    const id_py = parseInt(btn.getAttribute('data-id-py') || '0', 10) || 0;
    // pintar puntito/animación al cargar
    try {
      const data = await fetchEstado(id_py);
      paintButton(btn, !!data.has_obs);
    } catch (e) {
      // si falla el ping inicial, no rompemos nada
      console.warn('observaciones ping falló:', e);
    }
    // click -> abrir modal
    btn.addEventListener('click', function (ev) {
      ev.preventDefault();
      openModalFlow(btn);
    });
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();

