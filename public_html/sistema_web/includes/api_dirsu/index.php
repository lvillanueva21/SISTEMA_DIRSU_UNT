<?php
include_once __DIR__ . '/../../componentes/configSesion.php';
include_once __DIR__ . '/guard.php';
include_once __DIR__ . '/mock.php';

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

$rsu_api_dirsu_data = rsu_api_dirsu_mock_items();
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
      max-height: 360px;
      overflow: auto;
    }
    .api-dirsu-filter .form-control {
      height: 38px;
    }
    .api-dirsu-empty {
      color: #6c757d;
      text-align: center;
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
  var selectedId = null;

  var inputText = document.getElementById('apiFilterText');
  var selectStatus = document.getElementById('apiFilterStatus');
  var leftBody = document.querySelector('#apiTableLeft tbody');
  var rightBody = document.querySelector('#apiTableRight tbody');
  var jsonOutput = document.getElementById('apiJsonOutput');

  function toLower(value) {
    if (value === null || value === undefined) {
      return '';
    }

    return String(value).toLowerCase();
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
      btn.appendChild(document.createTextNode('Ver'));
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

  function renderRightPreview(item) {
    clearNode(rightBody);

    if (!item) {
      jsonOutput.textContent = '{}';
      var trEmpty = document.createElement('tr');
      var tdEmpty = document.createElement('td');
      tdEmpty.colSpan = 2;
      tdEmpty.className = 'api-dirsu-empty';
      tdEmpty.appendChild(document.createTextNode('Selecciona una fila para ver su detalle.'));
      trEmpty.appendChild(tdEmpty);
      rightBody.appendChild(trEmpty);
      return;
    }

    jsonOutput.textContent = JSON.stringify(item, null, 2);

    var keys = ['id', 'nombre', 'modulo', 'metodo', 'endpoint', 'estado', 'actualizado_en', 'responsable'];
    var i;
    for (i = 0; i < keys.length; i++) {
      var key = keys[i];
      var tr = document.createElement('tr');

      var tdKey = document.createElement('td');
      tdKey.appendChild(document.createTextNode(key));
      tr.appendChild(tdKey);

      var tdValue = document.createElement('td');
      tdValue.appendChild(document.createTextNode(item[key]));
      tr.appendChild(tdValue);

      rightBody.appendChild(tr);
    }
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
    renderRightPreview(findRowById(selectedId));
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

  refreshView();
})();
</script>
</body>
</html>
