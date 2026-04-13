<?php
include_once __DIR__ . '/../../componentes/configSesion.php';
include_once __DIR__ . '/guard.php';
include_once __DIR__ . '/url_helper.php';

rsu_api_dirsu_guard(array());

if (!function_exists('rsu_api_dirsu_escape')) {
    function rsu_api_dirsu_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$rsu_api_dirsu_assets_prefix = '../../';
$rsu_api_dirsu_page_title = 'Api Dirsu - Sistema DIRSU';

$rsu_api_dirsu_favicon = $rsu_api_dirsu_assets_prefix . 'imagenes/dirsu_128_128.ico';
$rsu_api_dirsu_logo = $rsu_api_dirsu_assets_prefix . 'dust/img/dirsu_logo_128_128.png';
$rsu_api_dirsu_logout_href = $rsu_api_dirsu_assets_prefix . 'componentes/sesion/cerrarSesion.php';
$rsu_api_dirsu_api_url = rsu_api_dirsu_api_url();
if (trim((string)$rsu_api_dirsu_api_url) === '' || strpos((string)$rsu_api_dirsu_api_url, 'includes/includes/') !== false) {
    $rsu_api_dirsu_api_url = 'api.php';
}

$rsu_api_dirsu_data = array(
    array(
        'id' => 1,
        'nombre' => 'Consulta de usuario',
        'modulo' => 'usuarios',
        'metodo' => 'GET',
        'endpoint' => 'api.php?action=user.get&usuario={usuario}|id={id}',
        'estado' => 'activo',
        'actualizado_en' => '2026-03-22 09:00:00',
        'responsable' => 'api_dirsu',
        'action' => 'user.get',
        'soporte_live' => 1
    ),
    array(
        'id' => 2,
        'nombre' => 'Proyectos de coordinador',
        'modulo' => 'proyectos',
        'metodo' => 'GET',
        'endpoint' => 'api.php?action=user.projects.get&usuario={usuario}|id={id}',
        'estado' => 'activo',
        'actualizado_en' => '2026-03-25 12:00:00',
        'responsable' => 'api_dirsu',
        'action' => 'user.projects.get',
        'soporte_live' => 1
    ),
    array(
        'id' => 3,
        'nombre' => 'Auditoria de semestres por proyecto',
        'modulo' => 'semestral',
        'metodo' => 'GET',
        'endpoint' => 'api.php?action=project.semesters.audit&id_py={id_py}|id={id}|usuario={usuario}',
        'estado' => 'activo',
        'actualizado_en' => '2026-03-26 09:00:00',
        'responsable' => 'api_dirsu',
        'action' => 'project.semesters.audit',
        'soporte_live' => 1
    ),
    array(
        'id' => 4,
        'nombre' => 'Snapshot de periodos activos',
        'modulo' => 'periodos',
        'metodo' => 'GET',
        'endpoint' => 'api.php?action=periods.active.snapshot.get&id_periodo={id_periodo}&include_empty={0|1}&tz={America/Lima}',
        'estado' => 'activo',
        'actualizado_en' => '2026-04-13 10:00:00',
        'responsable' => 'api_dirsu',
        'action' => 'periods.active.snapshot.get',
        'soporte_live' => 1
    )
);

$rsu_api_dirsu_json = json_encode($rsu_api_dirsu_data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
if ($rsu_api_dirsu_json === false) {
    $rsu_api_dirsu_json = '[]';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?php echo rsu_api_dirsu_escape($rsu_api_dirsu_page_title); ?></title>

  <link href="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_favicon); ?>" rel="icon">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>plogins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>dust/css/adminlte.min.css">

  <style>
    .api-dirsu-json {
      min-height: 220px;
      max-height: 320px;
      overflow: auto;
      margin: 0;
      padding: 12px;
      border: 1px solid #dee2e6;
      border-radius: 4px;
      background: #f8f9fa;
      font-size: 13px;
    }
    .api-dirsu-table-wrap {
      max-height: 300px;
      overflow: auto;
    }
    .api-dirsu-filter .form-control {
      height: 38px;
    }
    .api-dirsu-empty {
      color: #6c757d;
      text-align: center;
    }
    .api-dirsu-params {
      border: 1px solid #dee2e6;
      border-radius: 4px;
      padding: 10px;
      margin-bottom: 12px;
      background: #fdfdfd;
    }
    .api-dirsu-note {
      font-size: 12px;
      color: #6c757d;
      margin-top: 6px;
    }
  </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_logo); ?>" alt="DIRSU" height="60" width="60">
  </div>

  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="background-image: url('<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
        <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank">
          <p style="color: white; margin: 0;">Ir a pagina DIRSU</p>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_logout_href); ?>" class="nav-link">Cerrar sesion</a>
      </li>
    </ul>
  </nav>

  <?php include_once __DIR__ . '/../sidebar.php'; ?>

  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-8">
            <h1 class="m-0">Api Dirsu</h1>
          </div>
          <div class="col-sm-4 text-sm-right">
            <span class="badge badge-warning">Modo Development</span>
          </div>
        </div>
      </div>
    </div>

    <section class="content">
      <div class="container-fluid">
        <div class="row">
          <div class="col-lg-5">
            <div class="card card-outline card-primary">
              <div class="card-header">
                <h3 class="card-title">Tabla de pruebas</h3>
              </div>
              <div class="card-body">
                <div class="row api-dirsu-filter mb-3">
                  <div class="col-md-7 mb-2 mb-md-0">
                    <input type="text" id="apiFilterText" class="form-control" placeholder="Filtrar por nombre, modulo o metodo">
                  </div>
                  <div class="col-md-5">
                    <select id="apiFilterStatus" class="form-control">
                      <option value="">Todos los estados</option>
                      <option value="activo">Activo</option>
                      <option value="borrador">Borrador</option>
                      <option value="deshabilitado">Deshabilitado</option>
                    </select>
                  </div>
                </div>

                <div class="api-dirsu-params">
                  <div class="form-group mb-2" id="apiParamsUsuarioWrap">
                    <label for="apiUsuarioInput" class="mb-1">Usuario (codigo o DNI)</label>
                    <input type="text" id="apiUsuarioInput" class="form-control" placeholder="Ejemplo: 67676767 o 6407">
                  </div>
                  <div class="form-group mb-2" id="apiParamsIdWrap">
                    <label for="apiIdInput" class="mb-1">ID interno</label>
                    <input type="number" id="apiIdInput" class="form-control" min="1" step="1" placeholder="Ejemplo: 37">
                  </div>
                  <div class="form-group mb-2" id="apiParamsPeriodoWrap" style="display:none;">
                    <label for="apiPeriodoInput" class="mb-1">ID de periodo (opcional)</label>
                    <input type="number" id="apiPeriodoInput" class="form-control" min="1" step="1" placeholder="Ejemplo: 12">
                  </div>
                  <div class="form-group mb-2" id="apiParamsIncludeEmptyWrap" style="display:none;">
                    <label for="apiIncludeEmptyInput" class="mb-1">Incluir periodos sin cronogramas activos</label>
                    <select id="apiIncludeEmptyInput" class="form-control">
                      <option value="1" selected>Si (include_empty=1)</option>
                      <option value="0">No (include_empty=0)</option>
                    </select>
                  </div>
                  <div class="form-group mb-2" id="apiParamsTzWrap" style="display:none;">
                    <label for="apiTimezoneInput" class="mb-1">Zona horaria</label>
                    <input type="text" id="apiTimezoneInput" class="form-control" value="America/Lima">
                  </div>
                  <button type="button" class="btn btn-primary btn-sm" id="apiRunBtn">
                    <i class="fas fa-play"></i> Consultar API seleccionada
                  </button>
                  <div class="api-dirsu-note" id="apiActionNote">Selecciona una fila y luego consulta por usuario o id.</div>
                </div>

                <div class="table-responsive api-dirsu-table-wrap">
                  <table class="table table-sm table-hover" id="apiTableLeft">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Nombre</th>
                        <th>Estado</th>
                        <th>Accion</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>

          <div class="col-lg-7">
            <div class="card card-outline card-info">
              <div class="card-header">
                <h3 class="card-title">JSON crudo</h3>
              </div>
              <div class="card-body">
                <pre id="apiJsonOutput" class="api-dirsu-json"></pre>
              </div>
            </div>
          </div>
        </div>

        <div class="row">
          <div class="col-12">
            <div class="card card-outline card-secondary">
              <div class="card-header">
                <h3 class="card-title">Vista tabular</h3>
              </div>
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table table-bordered table-sm" id="apiTableRight">
                    <thead>
                      <tr>
                        <th>Campo</th>
                        <th>Alias</th>
                        <th>Valor</th>
                      </tr>
                    </thead>
                    <tbody></tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>
  </div>

  <footer class="main-footer">
    <strong>(c) 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
    <div class="float-right d-none d-sm-inline-block">
      <p style="margin: 0;">Desarrollado por el Area informatica - DIRSU</p>
    </div>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<script src="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>plogins/jquery/jquery.min.js"></script>
<script src="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="<?php echo rsu_api_dirsu_escape($rsu_api_dirsu_assets_prefix); ?>dust/js/adminlte.min.js"></script>
<script>
(function () {
  var apiData = <?php echo $rsu_api_dirsu_json; ?>;
  var apiBaseUrl = <?php echo json_encode($rsu_api_dirsu_api_url, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES); ?>;
  var selectedId = null;

  var inputText = document.getElementById('apiFilterText');
  var selectStatus = document.getElementById('apiFilterStatus');
  var leftBody = document.querySelector('#apiTableLeft tbody');
  var rightBody = document.querySelector('#apiTableRight tbody');
  var jsonOutput = document.getElementById('apiJsonOutput');
  var inputUsuario = document.getElementById('apiUsuarioInput');
  var inputId = document.getElementById('apiIdInput');
  var inputPeriodo = document.getElementById('apiPeriodoInput');
  var inputIncludeEmpty = document.getElementById('apiIncludeEmptyInput');
  var inputTimezone = document.getElementById('apiTimezoneInput');
  var wrapUsuario = document.getElementById('apiParamsUsuarioWrap');
  var wrapId = document.getElementById('apiParamsIdWrap');
  var wrapPeriodo = document.getElementById('apiParamsPeriodoWrap');
  var wrapIncludeEmpty = document.getElementById('apiParamsIncludeEmptyWrap');
  var wrapTimezone = document.getElementById('apiParamsTzWrap');
  var runBtn = document.getElementById('apiRunBtn');
  var actionNote = document.getElementById('apiActionNote');

  function toLower(value) {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value).toLowerCase();
  }

  function trimValue(value) {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value).replace(/^\s+|\s+$/g, '');
  }

  function clearNode(node) {
    while (node.firstChild) {
      node.removeChild(node.firstChild);
    }
  }

  function getFilteredRows() {
    var text = toLower(inputText.value);
    var status = toLower(selectStatus.value);
    var filtered = [];
    var i;

    for (i = 0; i < apiData.length; i++) {
      var item = apiData[i];
      var itemStatus = toLower(item.estado);
      var hayEstado = !status || itemStatus === status;

      var fullText = toLower(item.nombre) + ' ' + toLower(item.modulo) + ' ' + toLower(item.metodo);
      var hayTexto = !text || fullText.indexOf(text) !== -1;

      if (hayEstado && hayTexto) {
        filtered.push(item);
      }
    }

    return filtered;
  }

  function makeBadge(status) {
    var className = 'badge badge-secondary';

    if (status === 'activo') {
      className = 'badge badge-success';
    } else if (status === 'borrador') {
      className = 'badge badge-warning';
    } else if (status === 'deshabilitado') {
      className = 'badge badge-danger';
    }

    var span = document.createElement('span');
    span.className = className;
    span.appendChild(document.createTextNode(status || '-'));
    return span;
  }

  function renderLeftTable(rows) {
    clearNode(leftBody);

    if (!rows.length) {
      var trEmpty = document.createElement('tr');
      var tdEmpty = document.createElement('td');
      tdEmpty.colSpan = 4;
      tdEmpty.className = 'api-dirsu-empty';
      tdEmpty.appendChild(document.createTextNode('No hay resultados para los filtros actuales.'));
      trEmpty.appendChild(tdEmpty);
      leftBody.appendChild(trEmpty);
      return;
    }

    var i;
    for (i = 0; i < rows.length; i++) {
      var item = rows[i];

      var tr = document.createElement('tr');
      if (selectedId === item.id) {
        tr.className = 'table-active';
      }

      var tdId = document.createElement('td');
      tdId.appendChild(document.createTextNode(item.id));
      tr.appendChild(tdId);

      var tdNombre = document.createElement('td');
      tdNombre.appendChild(document.createTextNode(item.nombre));
      tr.appendChild(tdNombre);

      var tdEstado = document.createElement('td');
      tdEstado.appendChild(makeBadge(item.estado));
      tr.appendChild(tdEstado);

      var tdAction = document.createElement('td');
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'btn btn-xs btn-outline-primary btn-api-view';
      btn.setAttribute('data-api-id', item.id);
      btn.appendChild(document.createTextNode(item.soporte_live ? 'Probar' : 'Ver'));
      tdAction.appendChild(btn);
      tr.appendChild(tdAction);

      leftBody.appendChild(tr);
    }
  }

  function findRowById(id) {
    var i;
    for (i = 0; i < apiData.length; i++) {
      if (apiData[i].id === id) {
        return apiData[i];
      }
    }

    return null;
  }

  function getAliasForField(field) {
    var aliasMap = {
      'id': 'ID interno',
      'usuario': 'Codigo de usuario',
      'codigo_usuario': 'Codigo de usuario',
      'nombres': 'Nombres',
      'apellidos': 'Apellidos',
      'nombres_completos': 'Nombre completo',
      'rol.id': 'ID de rol',
      'rol.nombre': 'Nombre del rol',
      'sede.id': 'ID de sede',
      'sede.nombre': 'Nombre de sede',
      'facultad.id': 'ID de facultad',
      'facultad.nombre': 'Nombre de facultad',
      'facultad.origen': 'Origen de facultad',
      'escuela.id': 'ID de escuela',
      'escuela.nombre': 'Nombre de escuela',
      'departamento_academico.id': 'ID de departamento academico',
      'departamento_academico.nombre': 'Nombre de Departamento Academico',
      'contacto.email': 'Correo principal',
      'contacto.telefono': 'Telefono principal',
      'contacto.telefono_asistente': 'Telefono de asistente',
      'contacto.correo_asistente': 'Correo de asistente',
      'contacto.origen': 'Origen de contacto',
      'proyecto.id': 'ID de proyecto principal',
      'usuario.id': 'ID interno del usuario',
      'usuario.usuario': 'Codigo de usuario',
      'usuario.nombres': 'Nombres',
      'usuario.apellidos': 'Apellidos',
      'usuario.rol.id': 'ID de rol',
      'usuario.rol.nombre': 'Rol del usuario',
      'usuario.id_py_actual': 'Proyecto activo en sesion',
      'proyectos': 'Listado de proyectos',
      'resumen.total_proyectos': 'Total de proyectos',
      'resumen.total_periodos_activos': 'Total de periodos activos',
      'resumen.total_cronogramas_activos': 'Total de cronogramas activos',
      'resumen.total_formularios_activos_vinculados': 'Total de formularios activos vinculados',
      'periodos': 'Listado de periodos activos',
      'periodos.id': 'ID de periodo',
      'periodos.nombre': 'Nombre del periodo',
      'periodos.fecha_inicio': 'Fecha de inicio del periodo',
      'periodos.fecha_fin': 'Fecha de fin del periodo',
      'periodos.estado_periodo': 'Estado del periodo',
      'cronogramas_activos': 'Cronogramas activos del periodo',
      'tipo_id': 'ID tipo de cronograma',
      'tipo_nombre': 'Tipo de cronograma',
      'ventana_estado': 'Estado de ventana',
      'formulario.estado': 'Estado de formulario',
      'formulario.existe': 'Existe formulario',
      'formulario.items_activos': 'Items activos del formulario',
      'meta.search_mode': 'Modo de busqueda',
      'meta.search_value': 'Valor de busqueda',
      'meta.requested_at': 'Fecha de consulta',
      'meta.requested_by': 'Usuario que consulta',
      'ok': 'Estado de respuesta',
      'message': 'Mensaje de respuesta',
      'code': 'Codigo de error'
    };

    if (aliasMap.hasOwnProperty(field)) {
      return aliasMap[field];
    }

    var pretty = String(field).replace(/\./g, ' > ').replace(/_/g, ' ');
    return pretty.charAt(0).toUpperCase() + pretty.slice(1);
  }

  function renderKeyValueRows(payload) {
    clearNode(rightBody);

    if (!payload) {
      var trEmpty = document.createElement('tr');
      var tdEmpty = document.createElement('td');
      tdEmpty.colSpan = 3;
      tdEmpty.className = 'api-dirsu-empty';
      tdEmpty.appendChild(document.createTextNode('Selecciona una fila para ver su detalle.'));
      trEmpty.appendChild(tdEmpty);
      rightBody.appendChild(trEmpty);
      return;
    }

    var rows = [];

    function isArray(value) {
      return Object.prototype.toString.call(value) === '[object Array]';
    }

    function pushRow(key, value) {
      var printable = value;
      if (printable === null || printable === undefined) {
        printable = '';
      } else if (typeof printable === 'object') {
        printable = JSON.stringify(printable);
      } else {
        printable = String(printable);
      }

      rows.push({
        key: key,
        alias: getAliasForField(key),
        value: printable
      });
    }

    function flattenObject(obj, prefix) {
      var key;
      for (key in obj) {
        if (!obj.hasOwnProperty(key)) {
          continue;
        }

        var fullKey = prefix ? (prefix + '.' + key) : key;
        var value = obj[key];

        if (isArray(value)) {
          flattenArray(value, fullKey);
        } else if (value !== null && typeof value === 'object') {
          flattenObject(value, fullKey);
        } else {
          pushRow(fullKey, value);
        }
      }
    }

    function flattenArray(list, prefix) {
      var idx;
      for (idx = 0; idx < list.length; idx++) {
        var item = list[idx];
        var fullKey = prefix + '[' + idx + ']';

        if (isArray(item)) {
          flattenArray(item, fullKey);
        } else if (item !== null && typeof item === 'object') {
          flattenObject(item, fullKey);
        } else {
          pushRow(fullKey, item);
        }
      }
    }

    if (payload !== null && typeof payload === 'object') {
      flattenObject(payload, '');
    } else {
      pushRow('resultado', payload);
    }

    if (!rows.length) {
      pushRow('resultado', 'Sin datos.');
    }

    var i;
    for (i = 0; i < rows.length; i++) {
      var tr = document.createElement('tr');
      var tdKey = document.createElement('td');
      var tdAlias = document.createElement('td');
      var tdValue = document.createElement('td');

      tdKey.appendChild(document.createTextNode(rows[i].key));
      tdAlias.appendChild(document.createTextNode(rows[i].alias));
      tdValue.appendChild(document.createTextNode(rows[i].value));

      tr.appendChild(tdKey);
      tr.appendChild(tdAlias);
      tr.appendChild(tdValue);
      rightBody.appendChild(tr);
    }
  }

  function renderJson(payload) {
    if (payload === null || payload === undefined) {
      jsonOutput.textContent = '{}';
      return;
    }

    try {
      jsonOutput.textContent = JSON.stringify(payload, null, 2);
    } catch (error) {
      jsonOutput.textContent = String(payload);
    }
  }

  function showCatalogPreview(item) {
    if (!item) {
      actionNote.textContent = 'Selecciona una fila para consultar su API.';
      renderJson({});
      renderKeyValueRows(null);
      setParamsVisibility(null);
      return;
    }

    setParamsVisibility(item);

    if (item.soporte_live) {
      if (item.action === 'periods.active.snapshot.get') {
        actionNote.textContent = 'Endpoint activo. Puedes filtrar por id_periodo y include_empty en tiempo real.';
      } else {
        actionNote.textContent = 'Endpoint activo. Puedes consultar por usuario o por id sin recargar la pagina.';
      }
    } else {
      actionNote.textContent = 'Endpoint de muestra. Aun no tiene backend activo.';
    }

    renderJson(item);
    renderKeyValueRows(item);
  }

  function ensureSelectedFromRows(rows) {
    if (!rows.length) {
      selectedId = null;
      return;
    }

    var i;
    for (i = 0; i < rows.length; i++) {
      if (rows[i].id === selectedId) {
        return;
      }
    }

    selectedId = rows[0].id;
  }

  function refreshView() {
    var filtered = getFilteredRows();
    ensureSelectedFromRows(filtered);
    renderLeftTable(filtered);
    showCatalogPreview(findRowById(selectedId));
  }

  function requestJson(url, callback) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', url, true);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    xhr.onreadystatechange = function () {
      if (xhr.readyState !== 4) {
        return;
      }

      var payload = null;
      if (xhr.responseText) {
        try {
          payload = JSON.parse(xhr.responseText);
        } catch (e) {
          payload = null;
        }
      }

      callback(xhr.status, payload, xhr.responseText || '');
    };

    xhr.send(null);
  }

  function setParamsVisibility(item) {
    var action = item && item.action ? item.action : '';
    var isPeriodSnapshot = action === 'periods.active.snapshot.get';
    var isUserBased = action === 'user.get' || action === 'user.projects.get' || action === 'project.semesters.audit';

    wrapUsuario.style.display = isUserBased ? '' : 'none';
    wrapId.style.display = isUserBased ? '' : 'none';
    wrapPeriodo.style.display = isPeriodSnapshot ? '' : 'none';
    wrapIncludeEmpty.style.display = isPeriodSnapshot ? '' : 'none';
    wrapTimezone.style.display = isPeriodSnapshot ? '' : 'none';
  }

  function actionIsSupported(item) {
    if (!item || !item.soporte_live) {
      return false;
    }

    var action = item.action;
    return (
      action === 'user.get' ||
      action === 'user.projects.get' ||
      action === 'project.semesters.audit' ||
      action === 'periods.active.snapshot.get'
    );
  }

  function buildRequestUrlForUserBased(item) {
    var usuario = trimValue(inputUsuario.value);
    var id = trimValue(inputId.value);
    var requestUrl = apiBaseUrl + '?action=' + encodeURIComponent(item.action);

    if (id !== '' && !/^\d+$/.test(id)) {
      actionNote.textContent = 'El id debe ser numerico.';
      return null;
    }

    if (id === '' && usuario === '') {
      actionNote.textContent = 'Ingresa un usuario o un id para consultar.';
      return null;
    }

    if (id !== '') {
      requestUrl += '&id=' + encodeURIComponent(id);
    } else {
      requestUrl += '&usuario=' + encodeURIComponent(usuario);
    }

    return requestUrl;
  }

  function buildRequestUrlForPeriodSnapshot(item) {
    var idPeriodo = trimValue(inputPeriodo.value);
    var includeEmpty = trimValue(inputIncludeEmpty.value);
    var timezone = trimValue(inputTimezone.value);
    var requestUrl = apiBaseUrl + '?action=' + encodeURIComponent(item.action);

    if (idPeriodo !== '' && !/^\d+$/.test(idPeriodo)) {
      actionNote.textContent = 'El id_periodo debe ser numerico.';
      return null;
    }

    if (idPeriodo !== '') {
      requestUrl += '&id_periodo=' + encodeURIComponent(idPeriodo);
    }

    if (includeEmpty !== '0' && includeEmpty !== '1') {
      actionNote.textContent = 'include_empty debe ser 0 o 1.';
      return null;
    }
    requestUrl += '&include_empty=' + encodeURIComponent(includeEmpty);

    if (timezone === '') {
      timezone = 'America/Lima';
      inputTimezone.value = timezone;
    }
    requestUrl += '&tz=' + encodeURIComponent(timezone);

    return requestUrl;
  }

  function runSelectedApi() {
    var item = findRowById(selectedId);

    if (!item) {
      actionNote.textContent = 'Selecciona una API en la tabla izquierda antes de ejecutar.';
      return;
    }

    if (!actionIsSupported(item)) {
      actionNote.textContent = 'Esta API aun no tiene implementacion activa. Solo muestra datos de prueba.';
      showCatalogPreview(item);
      return;
    }

    var requestUrl = null;
    if (item.action === 'periods.active.snapshot.get') {
      requestUrl = buildRequestUrlForPeriodSnapshot(item);
    } else {
      requestUrl = buildRequestUrlForUserBased(item);
    }

    if (requestUrl === null) {
      return;
    }

    actionNote.textContent = 'Consultando API...';
    renderJson({ status: 'loading', url: requestUrl });

    requestJson(requestUrl, function (status, payload, rawText) {
      if (payload !== null) {
        renderJson(payload);
        if (payload.ok && payload.data) {
          renderKeyValueRows(payload.data);
          actionNote.textContent = 'Consulta completada sin recarga de pagina.';
        } else {
          renderKeyValueRows(payload);
          actionNote.textContent = 'La API respondio con un mensaje de error (HTTP ' + status + ').';
        }
        return;
      }

      renderJson({ ok: false, status: status, raw: rawText });
      renderKeyValueRows({ status: status, mensaje: 'No se pudo parsear el JSON de respuesta.' });
      actionNote.textContent = 'Respuesta no valida del endpoint (HTTP ' + status + ').';
    });
  }

  inputText.addEventListener('input', refreshView);
  selectStatus.addEventListener('change', refreshView);

  leftBody.addEventListener('click', function (event) {
    var target = event.target;

    if (!target || target.getAttribute('data-api-id') === null) {
      return;
    }

    selectedId = parseInt(target.getAttribute('data-api-id'), 10);
    refreshView();
  });

  runBtn.addEventListener('click', runSelectedApi);

  inputUsuario.addEventListener('keypress', function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      runSelectedApi();
    }
  });

  inputId.addEventListener('keypress', function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      runSelectedApi();
    }
  });

  inputPeriodo.addEventListener('keypress', function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      runSelectedApi();
    }
  });

  inputTimezone.addEventListener('keypress', function (event) {
    if (event.keyCode === 13) {
      event.preventDefault();
      runSelectedApi();
    }
  });

  refreshView();
})();
</script>
</body>
</html>
