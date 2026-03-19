// /sistema_web/evaluacion/evaluacion.js
(function () {
  const api = (window && window.EV_API) || '/sistema_web/evaluacion/api_eval.php';

  // Utilidades
  function qs(sel, root) { return (root || document).querySelector(sel); }
  function qsa(sel, root) { return Array.from((root || document).querySelectorAll(sel)); }
  function showModal() {
    const m = document.getElementById('modalEval');
    if (window.jQuery && jQuery(m).modal) { jQuery(m).modal('show'); }
    else if (window.bootstrap && window.bootstrap.Modal) { new bootstrap.Modal(m).show(); }
    else { m.style.display = 'block'; }
  }
  function hideModal() {
    const m = document.getElementById('modalEval');
    if (window.jQuery && jQuery(m).modal) { jQuery(m).modal('hide'); }
    else { m.style.display = 'none'; }
  }
  function toast(msg, ok) {
    alert((ok ? '✅ ' : '⚠️ ') + msg);
  }
  async function callAPI(params) {
    const body = new URLSearchParams(params);
    const r = await fetch(api, { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body });
    const txt = await r.text();
    let json;
    try { json = JSON.parse(txt); } catch (e) {
      throw new Error('Respuesta no válida del servidor: ' + txt);
    }
    if (!json.ok) throw new Error(json.msg || 'Error desconocido');
    return json;
  }

  // Estado actual del modal
  let CURRENT = { accion: null, id_py: 0 };

  // Abrir desde botones
  document.addEventListener('click', async (e) => {
    const btn = e.target.closest('.btn-evaluar');
    if (!btn) return;

    const accion = btn.getAttribute('data-accion'); // cotejo|rubrica|vb
    const id_py  = parseInt(btn.getAttribute('data-id_py') || '0', 10);
    if (!accion || !id_py) return;

    CURRENT = { accion, id_py };

    try {
      const res = await callAPI({ do:'load', accion, id_py });
      // Cabecera
      qs('#evHeader').innerHTML = res.header_html || '';
      // Título
      const title = accion === 'cotejo' ? 'Calificar — Lista de Cotejo'
                  : accion === 'rubrica' ? 'Calificar — Rúbrica'
                  : 'Calificar — Visto Bueno';
      qs('#evTitle').textContent = title;

      // Cargar template
      const tplId = accion === 'cotejo' ? '#tpl-cotejo' : (accion === 'rubrica' ? '#tpl-rubrica' : '#tpl-vb');
      const tpl = qs(tplId);
      const node = document.importNode(tpl.content, true);
      const cont = qs('#evFormContainer');
      cont.innerHTML = '';
      cont.appendChild(node);

      // Prefill
      if (accion === 'cotejo') {
        const est = (res.prefill && typeof res.prefill.estado !== 'undefined') ? String(res.prefill.estado) : '0';
        const obs = (res.prefill && res.prefill.obs_general) ? res.prefill.obs_general : '';
        const form = qs('#formCotejo');
        const radios = qsa('input[name="estado"]', form);
        const radio = radios.find(r => r.value === est) || radios[0];
        radio.checked = true;
        qs('textarea[name="obs_general"]', form).value = obs;
        refreshCotejoUI();
      }
      if (accion === 'vb') {
        const est = (res.prefill && typeof res.prefill.estado !== 'undefined') ? String(res.prefill.estado) : '0';
        const form = qs('#formVB');
        const radios = qsa('input[name="estado"]', form);
        (radios.find(r => r.value === est) || radios[0]).checked = true;
      }
      if (accion === 'rubrica') {
        const notas = (res.prefill && res.prefill.notas) ? res.prefill.notas : {};
        const obs   = (res.prefill && res.prefill.obs)   ? res.prefill.obs   : {};
        qsa('.asp-nota').forEach(sel => {
          const a = sel.getAttribute('data-asp');
          const v = notas[a] != null ? String(notas[a]) : '0';
          sel.value = v;
        });
        qsa('.asp-obs').forEach(tx => {
          const a = tx.getAttribute('data-asp');
          tx.value = obs[a] || '';
        });
        refreshRubricaUI();
      }

      showModal();
    } catch (err) {
      toast(err.message || err, false);
    }
  });

  // Guardar
  qs('#evBtnGuardar').addEventListener('click', onGuardar);

  async function onGuardar() {
    const { accion, id_py } = CURRENT;
    if (!accion || !id_py) return;

    try {
      if (accion === 'cotejo') {
        const form = qs('#formCotejo');
        const est = (qs('input[name="estado"]:checked', form) || {}).value || '0';
        const obs = (qs('textarea[name="obs_general"]', form) || {}).value || '';
        let due = (qs('input[name="due_days"]:checked', form) || {}).value || '0';
        if (est === '2') { // Observado
          if (!obs.trim()) throw new Error('La observación es obligatoria cuando está Observado.');
          if (due !== '1' && due !== '2') throw new Error('Debes elegir 1 o 2 días de subsanación.');
        }
        await callAPI({ do:'guardar_cotejo', id_py, estado:est, obs_general:obs, due_days:due });
        toast('Cotejo guardado correctamente.', true);
      } else if (accion === 'vb') {
        const form = qs('#formVB');
        const est = (qs('input[name="estado"]:checked', form) || {}).value || '0';
        await callAPI({ do:'guardar_vb', id_py, estado:est });
        toast('Visto Bueno guardado.', true);
      } else {
        // rúbrica
        const form = qs('#formRubrica');
        const notas = {};
        const obs = {};
        let anyZero = false, total = 0;

        qsa('.asp-nota', form).forEach(sel => {
          const a = sel.getAttribute('data-asp');
          const v = parseInt(sel.value, 10);
          notas[a] = v; total += v; if (v === 0) anyZero = true;
        });
        qsa('.asp-obs', form).forEach(tx => {
          const a = tx.getAttribute('data-asp');
          obs[a] = tx.value || '';
        });

        // Validaciones
        for (let a=1; a<=5; a++) {
          const v = +notas[a];
          if (v < 0 || v > 4 || isNaN(v)) throw new Error('Nota inválida en aspecto '+a);
        }
        // Estado calculado
        let estado = 2; // observado
        if (anyZero) estado = 0;
        else if (total >= 14) estado = 1;

        // Obs obligatorias si estado observado y algún aspecto en 1/2
        if (estado === 2) {
          for (let a=1; a<=5; a++) {
            if (notas[a] === 1 || notas[a] === 2) {
              if (!obs[a] || !obs[a].trim()) {
                throw new Error('Falta observación en aspecto '+a);
              }
            }
          }
        }

        // due_days si observado
        let due = (qs('input[name="due_days"]:checked', form) || {}).value || '0';
        if (estado === 2 && (due !== '1' && due !== '2')) {
          throw new Error('Debes elegir 1 o 2 días de subsanación.');
        }

        // Enviar
        const payload = new URLSearchParams();
        payload.set('do','guardar_rubrica');
        payload.set('id_py', String(id_py));
        for (let a=1; a<=5; a++) payload.append('rubrica['+a+']', String(notas[a]));
        for (let a=1; a<=5; a++) payload.append('obs['+a+']', obs[a] || '');
        payload.set('due_days', due);

        const r = await fetch(api, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:payload });
        const txt = await r.text();
        let json; try { json = JSON.parse(txt); } catch(e) { throw new Error('Respuesta no válida: '+txt); }
        if (!json.ok) throw new Error(json.msg || 'Error al guardar rúbrica.');

        toast('Rúbrica guardada. Total: '+(json.total??total), true);
      }

      hideModal();
      // Refresca para reflejar cambios (estado/oficina/observaciones)
      location.reload();
    } catch (err) {
      toast(err.message || err, false);
    }
  }

  // === Cotejo: mostrar/ocultar controles ===
  document.addEventListener('change', (e) => {
    if (e.target && e.target.name === 'estado' && qs('#formCotejo')) refreshCotejoUI();
  });
  function refreshCotejoUI() {
    const form = qs('#formCotejo');
    if (!form) return;
    const est = (qs('input[name="estado"]:checked', form) || {}).value || '0';
    const box = qs('#cotejoObsBox', form);
    box.style.display = (est === '2') ? 'block' : 'none';
  }

  // === Rúbrica: cálculo en tiempo real ===
  document.addEventListener('change', (e) => {
    if (e.target && e.target.classList && e.target.classList.contains('asp-nota')) refreshRubricaUI();
  });
  function refreshRubricaUI() {
    const form = qs('#formRubrica'); if (!form) return;

    let total = 0, anyZero = false;
    qsa('.asp-nota', form).forEach(sel => {
      const v = parseInt(sel.value,10) || 0;
      total += v; if (v === 0) anyZero = true;
      const asp = sel.getAttribute('data-asp');
      const box = qs('.ev-aspecto[data-asp="'+asp+'"] .asp-obs-box', form);
      const label = qs('.ev-aspecto[data-asp="'+asp+'"] [data-role="label"]', form);
      // Mostrar textarea solo en 1 o 2
      if (v === 1 || v === 2) {
        box.style.display = 'block';
      } else {
        box.style.display = 'none';
      }
      // El texto (Observación vs Recomendación) lo seteo luego según estado global
      label.classList.remove('red','green');
    });

    qs('#rbTotal', form).textContent = String(total);
    const badge = qs('#rbEstado', form);
    let estado = 'wait', txt = 'En espera';
    if (anyZero) { estado = 'wait'; txt = 'En espera'; }
    else if (total >= 14) { estado = 'ok'; txt = 'Aprobado'; }
    else { estado = 'obs'; txt = 'Observado'; }
    badge.className = 'ev-badge '+estado;
    badge.textContent = txt;

    // Due days visible sólo si Observado
    const due = qs('#rbDueBox', form);
    due.style.display = (estado === 'obs') ? 'block' : 'none';

    // Si Aprobado → cambiar etiquetas a "Recomendación" (verde). Si Observado → "Observación" (roja).
    qsa('.ev-aspecto', form).forEach(div => {
      const asp = div.getAttribute('data-asp');
      const v = parseInt(qs('.asp-nota[data-asp="'+asp+'"]', form).value,10) || 0;
      const label = qs('[data-role="label"]', div);
      if (v === 1 || v === 2) {
        if (estado === 'ok') { label.textContent = 'Recomendación (opcional)'; label.classList.add('green'); label.classList.remove('red'); }
        else { label.textContent = 'Observación (obligatoria)'; label.classList.add('red'); label.classList.remove('green'); }
      }
    });
  }
})();
