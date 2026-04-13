<?php
if (!isset($rsu_card_auditoria_api_url)) {
    $rsu_card_auditoria_api_url = 'includes/api_dirsu/api.php';
}

$rsu_card_auditoria_usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
?>
<script>
(function () {
  var apiUrl = <?php echo json_encode($rsu_card_auditoria_api_url); ?>;
  var usuario = <?php echo json_encode($rsu_card_auditoria_usuario); ?>;
  var cardId = 'cardAuditoriaSemestresTodos';

  function escapeHtml(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function ensureCardContainer() {
    var modalBody = document.querySelector('#modalMultiproyectos .modal-body');
    if (!modalBody) {
      return null;
    }

    var existing = document.getElementById(cardId);
    if (existing) {
      return existing;
    }

    var card = document.createElement('div');
    card.id = cardId;
    card.className = 'card card-outline card-primary mt-3';
    card.innerHTML = ''
      + '<div class="card-header py-2"><h6 class="m-0">Auditoria de semestres de todos tus proyectos</h6></div>'
      + '<div class="card-body p-2" id="' + cardId + 'Body">'
      + '<div class="text-muted">Cargando informacion...</div>'
      + '</div>';
    modalBody.appendChild(card);
    return card;
  }

  function renderMessage(html) {
    var body = document.getElementById(cardId + 'Body');
    if (!body) {
      return;
    }
    body.innerHTML = html;
  }

  function estadoSemestreTexto(value) {
    var estado = parseInt(value, 10);
    if (estado === 2) {
      return 'Aprobado';
    }
    if (estado === 1) {
      return 'Observado';
    }
    return 'En espera';
  }

  function buildExpectedTable(rows) {
    if (!rows || !rows.length) {
      return '<div class="text-muted small">Sin semestres esperados.</div>';
    }

    var i;
    var htmlRows = '';
    for (i = 0; i < rows.length; i++) {
      var r = rows[i] || {};
      htmlRows += '<tr>'
        + '<td>' + escapeHtml(r.anio || '-') + '</td>'
        + '<td>' + escapeHtml(r.periodo || '-') + '</td>'
        + '<td>' + escapeHtml(r.tipo || '-') + '</td>'
        + '<td>' + escapeHtml(r.numero === null || r.numero === undefined ? '-' : r.numero) + '</td>'
        + '<td>' + escapeHtml(r.fecha_inicio || '-') + '</td>'
        + '<td>' + escapeHtml(r.fecha_fin || '-') + '</td>'
        + '</tr>';
    }

    return ''
      + '<div class="table-responsive">'
      + '<table class="table table-bordered table-sm mb-2">'
      + '<thead><tr><th>Año</th><th>Periodo</th><th>Tipo</th><th>#</th><th>Inicio</th><th>Fin</th></tr></thead>'
      + '<tbody>' + htmlRows + '</tbody></table></div>';
  }

  function buildActualTable(rows) {
    if (!rows || !rows.length) {
      return '<div class="text-muted small">No hay semestres vigentes en BD.</div>';
    }

    var i;
    var htmlRows = '';
    for (i = 0; i < rows.length; i++) {
      var r = rows[i] || {};
      var respuesta = r.respuesta || null;
      var infoRespuesta = respuesta
        ? ('SI (id ' + escapeHtml(respuesta.id) + ', estado ' + escapeHtml(respuesta.estado) + ')')
        : 'No';

      htmlRows += '<tr>'
        + '<td>' + escapeHtml(r.id || '-') + '</td>'
        + '<td>' + escapeHtml(r.anio || '-') + '</td>'
        + '<td>' + escapeHtml(r.periodo || '-') + '</td>'
        + '<td>' + escapeHtml(r.tipo || '-') + '</td>'
        + '<td>' + escapeHtml(r.numero === null || r.numero === undefined ? '-' : r.numero) + '</td>'
        + '<td>' + escapeHtml(r.fecha_inicio || '-') + '</td>'
        + '<td>' + escapeHtml(r.fecha_fin || '-') + '</td>'
        + '<td>' + escapeHtml(estadoSemestreTexto(r.estado)) + '</td>'
        + '<td>' + escapeHtml(infoRespuesta) + '</td>'
        + '<td>' + escapeHtml(r.titulo || '-') + '</td>'
        + '</tr>';
    }

    return ''
      + '<div class="table-responsive">'
      + '<table class="table table-bordered table-sm mb-2">'
      + '<thead><tr><th>ID</th><th>Año</th><th>Periodo</th><th>Tipo</th><th>#</th><th>Inicio</th><th>Fin</th><th>Estado</th><th>Informe</th><th>Titulo</th></tr></thead>'
      + '<tbody>' + htmlRows + '</tbody></table></div>';
  }

  function buildDiffList(difs) {
    if (!difs || !difs.length) {
      return '<div class="text-success small">Sin diferencias.</div>';
    }

    var i;
    var html = '<ul class="mb-1 pl-3">';
    for (i = 0; i < difs.length; i++) {
      var d = difs[i] || {};
      html += '<li class="small">' + escapeHtml(d.tipo || 'diferencia') + ' - ' + escapeHtml(d.clave || '-') + '</li>';
    }
    html += '</ul>';
    return html;
  }

  function renderRows(projects, resumen) {
    var i;
    var rows = '';
    for (i = 0; i < projects.length; i++) {
      var p = projects[i];
      var dataResumen = p && p.resumen ? p.resumen : {};
      var estado = (dataResumen.desactualizado === 1) ? 'Desactualizado' : 'Alineado';
      var badge = (dataResumen.desactualizado === 1) ? 'badge badge-warning' : 'badge badge-success';

      rows += '<tr>'
        + '<td>' + escapeHtml(p.id) + '</td>'
        + '<td>' + escapeHtml(p.titulo || 'Sin titulo') + '</td>'
        + '<td>' + escapeHtml(p.fecha_inicio || '-') + '</td>'
        + '<td>' + escapeHtml(p.fecha_fin || '-') + '</td>'
        + '<td>' + escapeHtml(dataResumen.total_esperado || 0) + '</td>'
        + '<td>' + escapeHtml(dataResumen.total_bd_vigente || 0) + '</td>'
        + '<td><span class="' + badge + '">' + estado + '</span></td>'
        + '<td>' + escapeHtml(p.mensaje || '-') + '</td>'
        + '</tr>';

      rows += '<tr class="table-light">'
        + '<td colspan="8">'
        + '<div class="mb-2"><strong>Semestres esperados</strong></div>'
        + buildExpectedTable(p.semestres_esperados || [])
        + '<div class="mb-2 mt-2"><strong>Semestres vigentes en BD</strong></div>'
        + buildActualTable(p.semestres_bd || [])
        + '<div class="mb-1 mt-2"><strong>Diferencias detectadas</strong></div>'
        + buildDiffList(p.diferencias || [])
        + '</td>'
        + '</tr>';
    }

    var info = '';
    if (resumen) {
      info = '<div class="mb-2 text-muted small">'
        + 'Total proyectos: <strong>' + escapeHtml(resumen.total_proyectos || 0) + '</strong>'
        + ' | Desactualizados: <strong>' + escapeHtml(resumen.total_desactualizados || 0) + '</strong>'
        + '</div>';
    }

    var table = ''
      + info
      + '<div class="table-responsive">'
      + '<table class="table table-sm table-bordered mb-0">'
      + '<thead><tr>'
      + '<th>ID</th><th>Proyecto</th><th>Inicio</th><th>Fin</th><th>Esperados</th><th>BD vigente</th><th>Estado</th><th>Detalle</th>'
      + '</tr></thead>'
      + '<tbody>' + rows + '</tbody>'
      + '</table>'
      + '</div>';
    renderMessage(table);
  }

  function loadData() {
    if (!usuario) {
      renderMessage('<div class="alert alert-info mb-0">No se pudo identificar el usuario de sesion para consultar proyectos.</div>');
      return;
    }

    var requestUrl = apiUrl + '?action=project.semesters.audit&usuario=' + encodeURIComponent(usuario);
    var xhr = new XMLHttpRequest();
    xhr.open('GET', requestUrl, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) {
        return;
      }

      var payload = null;
      try {
        payload = JSON.parse(xhr.responseText || '{}');
      } catch (e) {
        payload = null;
      }

      if (!payload || !payload.ok || !payload.data || !payload.data.proyectos) {
        renderMessage('<div class="alert alert-warning mb-0">No se pudo obtener la auditoria de proyectos.</div>');
        return;
      }

      if (!payload.data.proyectos.length) {
        renderMessage('<div class="alert alert-info mb-0">No hay proyectos aun definidos para este usuario o aun no se definieron fechas.</div>');
        return;
      }

      renderRows(payload.data.proyectos, payload.data.resumen || null);
    };
    xhr.send(null);
  }

  function init() {
    if (!ensureCardContainer()) {
      return;
    }
    loadData();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
</script>
