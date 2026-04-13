<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

$rsu_periodos_activos_role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
if ($rsu_periodos_activos_role_id !== 1) {
    return;
}

if (!isset($rsu_card_periodos_api_url)) {
    $rsu_card_periodos_api_url = '../includes/api_dirsu/api.php';
}
?>
<style>
  .periodos-activos-card .period-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    background: #28a745;
    margin-right: 8px;
    vertical-align: middle;
    box-shadow: 0 0 0 2px rgba(40, 167, 69, 0.2);
  }
  .periodos-activos-card .period-row-btn {
    width: 100%;
    border: 0;
    background: #f8f9fa;
    text-align: left;
    padding: 10px 12px;
    border-radius: 6px;
    margin-bottom: 8px;
    cursor: pointer;
  }
  .periodos-activos-card .period-row-btn:hover {
    background: #eef4ff;
  }
  .periodos-activos-card .period-row-title {
    font-weight: 700;
    color: #1f2937;
  }
  .periodos-activos-card .period-row-meta {
    font-size: 12px;
    color: #6c757d;
    margin-top: 4px;
  }
  .periodos-activos-card .period-panel {
    display: none;
    border: 1px solid #e9ecef;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    background: #ffffff;
  }
  .periodos-activos-card .period-panel.is-open {
    display: block;
  }
  .periodos-activos-card .cron-item {
    border: 1px solid #e5e7eb;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 8px;
    background: #fcfdff;
  }
  .periodos-activos-card .cron-item:last-child {
    margin-bottom: 0;
  }
  .periodos-activos-card .badge-window {
    margin-left: 6px;
  }
  .periodos-activos-card .badge-form-google {
    background: #673ab7;
    color: #fff;
  }
  .periodos-activos-card .text-items-zero {
    color: #dc3545;
    font-weight: 700;
  }
  .periodos-activos-card .text-items-ok {
    color: #198754;
    font-weight: 700;
  }
  .periodos-activos-card .interfaces-wrap {
    border: 1px dashed #d7dbe0;
    border-radius: 6px;
    padding: 10px;
    margin-bottom: 10px;
    background: #f9fbfd;
  }
  .periodos-activos-card .interfaces-title {
    font-weight: 700;
    color: #334155;
    margin-bottom: 8px;
    font-size: 13px;
  }
  .periodos-activos-card .interface-item {
    margin-bottom: 6px;
    padding-bottom: 6px;
    border-bottom: 1px solid #eceff3;
  }
  .periodos-activos-card .interface-item:last-child {
    margin-bottom: 0;
    padding-bottom: 0;
    border-bottom: 0;
  }
  .periodos-activos-card .interface-meta {
    font-size: 12px;
    color: #6c757d;
    margin-top: 2px;
  }
</style>

<div class="card home-card periodos-activos-card">
  <div class="card-header bg-warning text-white">
    <strong><i class="fas fa-stream"></i> Per&iacute;odos activos</strong>
  </div>
  <div class="card-body">
    <div id="periodosActivosResumen" class="mb-2 text-muted small">Cargando resumen...</div>
    <div id="periodosActivosBody">
      <div class="text-muted small">Consultando API de per&iacute;odos activos...</div>
    </div>
  </div>
</div>

<script>
(function () {
  var apiUrl = <?php echo json_encode($rsu_card_periodos_api_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  var bodyNode = document.getElementById('periodosActivosBody');
  var resumenNode = document.getElementById('periodosActivosResumen');

  function esc(value) {
    return String(value === null || value === undefined ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function windowBadge(status) {
    if (status === 'abierto') {
      return '<span class="badge badge-success badge-window">Abierto</span>';
    }
    if (status === 'proximo') {
      return '<span class="badge badge-warning badge-window">Pr&oacute;ximo</span>';
    }
    if (status === 'cerrado') {
      return '<span class="badge badge-secondary badge-window">Cerrado</span>';
    }
    return '<span class="badge badge-dark badge-window">Sin fechas</span>';
  }

  function renderFormulario(formulario) {
    if (!formulario || !formulario.existe) {
      return '<div><span class="badge bg-white text-danger border border-danger">Sin formulario activo</span></div>';
    }

    var html = '';
    html += '<div><span class="badge badge-form-google">Formulario activo: ' + esc(formulario.nombre || 'Sin nombre') + '</span></div>';

    var items = parseInt(formulario.items_activos, 10);
    if (isNaN(items) || items <= 0) {
      html += '<div class="mt-1"><span class="text-items-zero">Items activos: 0</span></div>';
    } else {
      html += '<div class="mt-1"><span class="text-items-ok">Items activos: ' + esc(items) + '</span></div>';
    }

    if (parseInt(formulario.total_formularios_activos_en_cronograma, 10) > 1) {
      html += '<div class="mt-1"><span class="badge badge-danger">Atencion: multiples formularios activos en el mismo cronograma</span></div>';
    }

    return html;
  }

  function renderCronogramas(periodo) {
    var cronogramas = (periodo && periodo.cronogramas_activos) ? periodo.cronogramas_activos : [];
    if (!cronogramas.length) {
      return '<div class="interfaces-title">Cronogramas activos</div><div class="text-danger small"><strong>Sin cronogramas activos en este periodo.</strong></div>';
    }

    var html = '<div class="interfaces-title">Cronogramas activos</div>';
    var i;
    for (i = 0; i < cronogramas.length; i++) {
      var cron = cronogramas[i] || {};
      html += '<div class="cron-item">';
      html += '<div><strong>' + esc(cron.tipo_nombre || 'Cronograma') + '</strong>' + windowBadge(cron.ventana_estado || '') + '</div>';
      html += '<div class="small text-muted mt-1">Apertura: ' + esc(cron.apertura || '-') + ' | Cierre: ' + esc(cron.cierre || '-') + '</div>';
      html += '<div class="mt-2">' + renderFormulario(cron.formulario || null) + '</div>';
      html += '</div>';
    }
    return html;
  }

  function interfazBadge(interfaz) {
    var estado = interfaz && interfaz.estado_visualizacion ? String(interfaz.estado_visualizacion) : '';
    if (estado === 'visible_ahora') {
      return '<span class="badge badge-success">Visible ahora</span>';
    }
    if (estado === 'activa_fuera_ventana') {
      return '<span class="badge badge-warning">Activa fuera de ventana</span>';
    }
    if (estado === 'inactiva') {
      return '<span class="badge badge-secondary">Inactiva</span>';
    }
    return '<span class="badge bg-white text-danger border border-danger">Sin regla</span>';
  }

  function renderInterfaces(periodo) {
    var interfaces = (periodo && periodo.visibilidad_interfaces) ? periodo.visibilidad_interfaces : [];
    if (!interfaces.length) {
      return '<div class="interfaces-wrap"><div class="interfaces-title">Visibilidad de p&aacute;ginas</div><div class="small text-muted">Sin datos de visibilidad.</div></div>';
    }

    var html = '<div class="interfaces-wrap"><div class="interfaces-title">Visibilidad de p&aacute;ginas</div>';
    var i;
    for (i = 0; i < interfaces.length; i++) {
      var it = interfaces[i] || {};
      html += '<div class="interface-item">';
      html += '<div><strong>' + esc(it.nombre || it.codigo || 'Interfaz') + '</strong> ' + interfazBadge(it) + '</div>';
      html += '<div class="interface-meta">Inicio: ' + esc(it.inicio || '-') + ' | Fin: ' + esc(it.fin || '-') + '</div>';
      html += '</div>';
    }
    html += '</div>';
    return html;
  }

  function bindToggleEvents() {
    if (!bodyNode) {
      return;
    }

    bodyNode.addEventListener('click', function (event) {
      var target = event.target;
      if (!target) {
        return;
      }

      while (target && target !== bodyNode && target.getAttribute('data-period-toggle') !== '1') {
        target = target.parentNode;
      }

      if (!target || target === bodyNode) {
        return;
      }

      var panelId = target.getAttribute('data-panel-id');
      if (!panelId) {
        return;
      }

      var panel = document.getElementById(panelId);
      if (!panel) {
        return;
      }

      if (panel.classList.contains('is-open')) {
        panel.classList.remove('is-open');
      } else {
        panel.classList.add('is-open');
      }
    });
  }

  function renderData(payload) {
    if (!payload || !payload.ok || !payload.data) {
      resumenNode.textContent = 'No se pudo cargar el resumen.';
      bodyNode.innerHTML = '<div class="alert alert-warning mb-0">No se pudo obtener datos de periodos activos.</div>';
      return;
    }

    var data = payload.data;
    var resumen = data.resumen || {};
    var periodos = data.periodos || [];

    resumenNode.innerHTML =
      'Per&iacute;odos activos: <strong>' + esc(resumen.total_periodos_activos || 0) + '</strong> | ' +
      'Cronogramas activos: <strong>' + esc(resumen.total_cronogramas_activos || 0) + '</strong> | ' +
      'Cronogramas sin formulario: <strong>' + esc(resumen.total_cronogramas_sin_formulario || 0) + '</strong> | ' +
      'Interfaces visibles ahora: <strong>' + esc(resumen.total_interfaces_visibles_ahora || 0) + '</strong>';

    if (!periodos.length) {
      bodyNode.innerHTML = '<div class="alert alert-info mb-0">No hay periodos activos para mostrar.</div>';
      return;
    }

    var html = '';
    var i;
    for (i = 0; i < periodos.length; i++) {
      var periodo = periodos[i] || {};
      var panelId = 'periodoActivoPanel_' + i;
      html += '<button type="button" class="period-row-btn" data-period-toggle="1" data-panel-id="' + panelId + '">';
      html += '<span class="period-dot"></span><span class="period-row-title">' + esc(periodo.nombre || ('Periodo #' + (i + 1))) + '</span>';
      html += '<div class="period-row-meta">Inicio: ' + esc(periodo.fecha_inicio || '-') + ' | Fin: ' + esc(periodo.fecha_fin || '-') + '</div>';
      html += '</button>';
      html += '<div id="' + panelId + '" class="period-panel">';
      html += renderInterfaces(periodo);
      html += renderCronogramas(periodo);
      html += '</div>';
    }

    bodyNode.innerHTML = html;
  }

  function fetchPeriodosActivos() {
    if (!apiUrl || !bodyNode || !resumenNode) {
      return;
    }

    var requestUrl = apiUrl + '?action=periods.active.snapshot.get&include_empty=1&tz=America/Lima';
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
      } catch (error) {
        payload = null;
      }

      renderData(payload);
    };
    xhr.send(null);
  }

  bindToggleEvents();
  fetchPeriodosActivos();
})();
</script>
