<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Incluir configSesion.php para verificar la sesiÃ³n
include "../componentes/configSesion.php";
// Incluir la conexiÃ³n a la base de datos
include('../componentes/db.php');
include_once __DIR__ . '/../evaluacion/funciones.php';
?>
<?php
/* ==== ENDPOINTS AJAX (para DIGITAR desde codigo_pool y GENERAR secuencial) ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  header('Content-Type: application/json; charset=utf-8');

  if (!isset($conexion) || !$conexion) {
    echo json_encode(['ok'=>false,'msg'=>'Sin conexiÃ³n a BD']); exit;
  }

  $ok  = function($data=[]) { echo json_encode(array_merge(['ok'=>true],  $data)); exit; };
  $err = function($msg, $extra=[]) { echo json_encode(array_merge(['ok'=>false,'msg'=>$msg], $extra)); exit; };

  $action = $_POST['action'];

  // 1) Listar cÃ³digos disponibles del pool por periodo y facultad
  if ($action === 'listar_codigos_disponibles') {
    $periodo_id  = isset($_POST['periodo_id'])  ? (int)$_POST['periodo_id']  : 0;
    $facultad_id = isset($_POST['facultad_id']) ? (int)$_POST['facultad_id'] : 0;
    if ($periodo_id <= 0 || $facultad_id <= 0) $err('Seleccione PerÃ­odo y Facultad.');

    $sql = "SELECT id, codigo
              FROM codigo_pool
             WHERE periodo_id=? AND facultad_id=? AND disponible=1
             ORDER BY codigo ASC
             LIMIT 500";
    if (!$stmt = mysqli_prepare($conexion, $sql)) $err('Error SQL (prep listar)');
    mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);

    $items = [];
    while ($r = mysqli_fetch_assoc($rs)) $items[] = ['id'=>(int)$r['id'], 'codigo'=>$r['codigo']];
    mysqli_stmt_close($stmt);

    $ok(['items'=>$items]);
  }

  // 2) Asignar cÃ³digo desde el pool al proyecto
  if ($action === 'asignar_codigo_pool') {
    $pool_id = isset($_POST['pool_id']) ? (int)$_POST['pool_id'] : 0;
    $id_py   = isset($_POST['id_py'])   ? (int)$_POST['id_py']   : 0;
    if ($pool_id <= 0 || $id_py <= 0) $err('ParÃ¡metros incompletos.');

    mysqli_begin_transaction($conexion);

    // (a) Bloqueo del cÃ³digo
    $sql = "SELECT periodo_id, codigo, disponible
              FROM codigo_pool
             WHERE id=? FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (pool)'); }
    mysqli_stmt_bind_param($stmt, 'i', $pool_id);
    mysqli_stmt_execute($stmt);
    $rs  = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);

    if (!$row) { mysqli_rollback($conexion); $err('CÃ³digo no encontrado.'); }
    if ((int)$row['disponible'] !== 1) { mysqli_rollback($conexion); $err('El cÃ³digo ya no estÃ¡ disponible.'); }

    $periodo_id = (int)$row['periodo_id'];
    $codigo     = (string)$row['codigo'];

    // (b) Evitar duplicado para el mismo periodo
    $sql = "SELECT id
              FROM proyecto_codigos
             WHERE id_py=? AND periodo_id=?
             LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (chk)'); }
    mysqli_stmt_bind_param($stmt, 'ii', $id_py, $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $ya = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if ($ya) { mysqli_rollback($conexion); $err('Este proyecto ya tiene cÃ³digo en ese perÃ­odo.'); }

    // (c) Consumir del pool
    $sql = "UPDATE codigo_pool
               SET disponible=0
             WHERE id=? AND disponible=1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (upd pool)'); }
    mysqli_stmt_bind_param($stmt, 'i', $pool_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) <= 0) { mysqli_stmt_close($stmt); mysqli_rollback($conexion); $err('No se pudo reservar el cÃ³digo.'); }
    mysqli_stmt_close($stmt);

    // (d) Registrar en proyecto_codigos (origen=manual)
    $sql = "INSERT INTO proyecto_codigos (id_py, periodo_id, codigo, origen)
            VALUES (?, ?, ?, 'manual')";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (ins)'); }
    mysqli_stmt_bind_param($stmt, 'iis', $id_py, $periodo_id, $codigo);
    $okIns = mysqli_stmt_execute($stmt);
    $eIns  = mysqli_error($conexion);
    mysqli_stmt_close($stmt);
    if (!$okIns) { mysqli_rollback($conexion); $err('No se pudo registrar: '.$eIns); }

    mysqli_commit($conexion);
    $ok(['codigo'=>$codigo]);
  }

  // 3) Generar cÃ³digo secuencial (alias y ancho desde codigo_alias_facultad; control con codigo_secuencias_periodo)
  if ($action === 'generar_codigo_auto') {
    $id_py      = isset($_POST['id_py'])      ? (int)$_POST['id_py']      : 0;
    $facultad_id= isset($_POST['facultad_id'])? (int)$_POST['facultad_id']: 0;
    $periodo_id = isset($_POST['periodo_id']) ? (int)$_POST['periodo_id'] : 0;
    if ($id_py<=0 || $facultad_id<=0 || $periodo_id<=0) $err('ParÃ¡metros incompletos.');

    mysqli_begin_transaction($conexion);

    // (a) Alias y ancho
    $sql = "SELECT alias, correlativo_width FROM codigo_alias_facultad WHERE facultad_id=? FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (alias)'); }
    mysqli_stmt_bind_param($stmt, 'i', $facultad_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $al = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$al) { mysqli_rollback($conexion); $err('No hay alias definido para la facultad.'); }
    $alias = (string)$al['alias'];
    $width = (int)$al['correlativo_width'];
    if ($width <= 0) $width = 3;

    // (b) AÃ±o desde periodos.nombre (p.ej. '2025-I' -> 2025)
    $sql = "SELECT nombre FROM periodos WHERE id=? FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (periodo)'); }
    mysqli_stmt_bind_param($stmt, 'i', $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $pr = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$pr) { mysqli_rollback($conexion); $err('PerÃ­odo invÃ¡lido.'); }
    $yyyy = (int)substr($pr['nombre'], 0, 4);
    if ($yyyy <= 0) $yyyy = (int)date('Y');

    // (c) Â¿El proyecto ya tiene cÃ³digo en ese periodo?
    $sql = "SELECT id FROM proyecto_codigos WHERE id_py=? AND periodo_id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (chk existe)'); }
    mysqli_stmt_bind_param($stmt, 'ii', $id_py, $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $ya = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if ($ya) { mysqli_rollback($conexion); $err('El proyecto ya tiene cÃ³digo en este periodo.'); }

    // (d) Calcular siguiente correlativo con seguridad (max entre secuencia guardada, asignados y pool)
    $max_ultimo = 0;
    $sql = "SELECT ultimo FROM codigo_secuencias_periodo WHERE periodo_id=? AND facultad_id=? FOR UPDATE";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
      mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      if ($r = mysqli_fetch_assoc($rs)) $max_ultimo = (int)$r['ultimo'];
      mysqli_stmt_close($stmt);
    }

    $max_asignados = 0;
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo,'-',2),'-',-1) AS UNSIGNED)) AS mx
            FROM proyecto_codigos
            WHERE periodo_id=? AND codigo LIKE CONCAT(?, '-%')";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
      mysqli_stmt_bind_param($stmt, 'is', $periodo_id, $alias);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      if ($r = mysqli_fetch_assoc($rs)) $max_asignados = (int)$r['mx'];
      mysqli_stmt_close($stmt);
    }

    $max_pool = 0;
    $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo,'-',2),'-',-1) AS UNSIGNED)) AS mx
            FROM codigo_pool
            WHERE periodo_id=? AND facultad_id=?";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
      mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      if ($r = mysqli_fetch_assoc($rs)) $max_pool = (int)$r['mx'];
      mysqli_stmt_close($stmt);
    }

    $next = max($max_ultimo, $max_asignados, $max_pool) + 1;
    $correl = str_pad((string)$next, $width, '0', STR_PAD_LEFT);
    $codigo = $alias . '-' . $correl . '-' . $yyyy;

    // (e) Upsert a codigo_secuencias_periodo con el nuevo "ultimo"
    $sql = "INSERT INTO codigo_secuencias_periodo (periodo_id, facultad_id, ultimo)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE ultimo=VALUES(ultimo)";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (upsert secuencia)'); }
    mysqli_stmt_bind_param($stmt, 'iii', $periodo_id, $facultad_id, $next);
    $okUp = mysqli_stmt_execute($stmt);
    $eUp  = mysqli_error($conexion);
    mysqli_stmt_close($stmt);
    if (!$okUp) { mysqli_rollback($conexion); $err('No se pudo actualizar secuencia: '.$eUp); }

    // (f) Insertar en proyecto_codigos (origen=auto)
    $sql = "INSERT INTO proyecto_codigos (id_py, periodo_id, codigo, origen)
            VALUES (?, ?, ?, 'auto')";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (ins generar)'); }
    mysqli_stmt_bind_param($stmt, 'iis', $id_py, $periodo_id, $codigo);
    $okIns = mysqli_stmt_execute($stmt);
    $eIns  = mysqli_error($conexion);
    mysqli_stmt_close($stmt);
    if (!$okIns) { mysqli_rollback($conexion); $err('No se pudo registrar: '.$eIns); }

    mysqli_commit($conexion);
    $ok(['codigo'=>$codigo]);
  }

  $err('AcciÃ³n invÃ¡lida.');
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Proyectos</title>
    <!-- Favicon -->
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                    <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank">
                        <p style="color: white;">Ir a pÃ¡gina DIRSU</p>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesiÃ³n</a>
                </li>
            </ul>
        </nav>
        <!-- Sidebar -->
                <?php include_once "../includes/sidebar.php"; ?>
        <div class="content-wrapper">
<section class="content p-3">
<?php
/* ===== Controlador local ===== */
$por_pagina = 20;
$pagina     = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;

$usr = testeo(); // rol, usuario, ids

// Filtros desde GET
$facultad     = isset($_GET['facultad']) ? (int)$_GET['facultad'] : 0;
$departamento = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
$revision     = isset($_GET['revision']) ? (string)$_GET['revision'] : '';
$periodo      = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0;
$oficina      = isset($_GET['oficina']) ? (string)$_GET['oficina'] : ''; // NUEVO
$q            = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// CatÃ¡logos
$facultades = obtenerFacultades();
$fac_by_name_to_id = array_flip($facultades); // nombre => id

// Facultad base para cascada
$fac_for_deps = $facultad;
if ($fac_for_deps <= 0) {
  if (in_array((int)$usr['id_rol'], [3,5], true)) $fac_for_deps = (int)$usr['id_escuela'];
}
$departamentos_cat = obtenerDepartamentos((int)$fac_for_deps);
$periodos          = obtenerPeriodos();
$per_by_name_to_id = array_flip($periodos); // nombre => id

// Filtros agrupados
$filtros = [
  'facultad'     => $facultad,
  'departamento' => $departamento,
  'revision'     => $revision,
  'periodo'      => $periodo,
  'oficina'      => $oficina, // NUEVO
  'q'            => $q,
];

// Totales + items
$total_items = totalProyectos($usr, $filtros);
$total_pages = max(1, (int) ceil($total_items / $por_pagina));
$items       = proyectosListado($pagina, $por_pagina, $usr, $filtros);

// Helpers
if (!function_exists('compact_pages')) {
  function compact_pages($current, $total){
    if ($total <= 7) return range(1, $total);
    $first = [1, 2, 3];
    $last  = [$total - 2, $total - 1, $total];
    $pages = $first;
    if ($first[2] + 1 < $last[0]) $pages[] = '...';
    foreach ($last as $p) if (!in_array($p, $pages, true)) $pages[] = $p;
    return $pages;
  }
}
$pages = compact_pages($pagina, $total_pages);

if (!function_exists('link_con_filtros')) {
  function link_con_filtros($p, $f){
    $qs = [
      'pagina'      => (int)$p,
      'facultad'    => (int)$f['facultad'],
      'departamento'=> (int)$f['departamento'],
      'revision'    => (string)$f['revision'],
      'periodo'     => (int)$f['periodo'],
      'oficina'     => (string)($f['oficina'] ?? ''), // NUEVO
      'q'           => (string)$f['q'],
    ];
    return '?' . http_build_query($qs);
  }
}

// Rango mostrado
$desde = ($total_items > 0) ? (($pagina - 1) * $por_pagina + 1) : 0;
$hasta = ($total_items > 0) ? (($pagina - 1) * $por_pagina + count($items)) : 0;

// Visibilidad de controles por rol
$id_rol = (int)$usr['id_rol'];
$mostrarFac   = in_array($id_rol, [0,1], true);
$mostrarDep   = in_array($id_rol, [0,1,3,5], true);
$mostrarRev   = true;
$mostrarPer   = true;
$mostrarBusq  = true;

// Â¿Departamento deshabilitado?
$dep_disabled = $mostrarDep && $fac_for_deps <= 0;
?>

<style>
.badge-ofic-pcf { background-color:#0275D8 !important; color:#fff !important; }
.badge-ofic-dd  { background-color:#F0AD4E !important; color:#111 !important; }
.badge-ofic-df  { background-color:#5BC0DE !important; color:#111 !important; }
.badge-ofic-rsu { background-color:#5CB85C !important; color:#fff !important; }

#modalDetalleObs .modal-dialog { max-width: 900px; }
.filtros-card .form-label{ font-weight:600; margin-bottom:.25rem; }
.filtros-card .form-control{ min-width: 120px; width:100%; }
.filtros-card .row > [class*="col-"]{ margin-bottom:.5rem; }

.small-muted { font-size:.82rem; color:#666; }
.badge-code { background:#222; color:#fff; }
</style>

<div class="mb-2 p-2 border rounded">
  <strong>Rol:</strong> <?= htmlspecialchars($usr['rol']) ?> &nbsp;&nbsp;
  <strong>Usuario:</strong> <?= htmlspecialchars($usr['usuario']) ?>
</div>

<!-- ======= FILTROS ======= -->
<div class="card filtros-card mb-2">
  <div class="card-body py-2">
    <form id="frmFiltros" method="get" class="mb-0">
      <input type="hidden" name="pagina" value="1">
      <div class="row align-items-end">
        <?php if ($mostrarFac): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selFacultad">Facultad:</label>
            <select name="facultad" id="selFacultad" class="form-control">
              <option value="0" <?= $facultad===0?'selected':''; ?>>Todas</option>
              <?php foreach ($facultades as $id=>$nom): if ((int)$id === 0) continue; ?>
                <option value="<?= (int)$id ?>" <?= ($facultad===(int)$id)?'selected':''; ?>>
                  <?= htmlspecialchars($nom) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarDep): ?>
          <div class="col-12 col-md-3 col-lg-3">
            <label class="form-label" for="selDepartamento">Departamento:</label>
            <select name="departamento" id="selDepartamento" class="form-control" <?= $dep_disabled?'disabled':''; ?>>
              <?php if ($dep_disabled): ?>
                <option value="0" selected>Sin Departamento AcadÃ©mico</option>
              <?php else: ?>
                <option value="0" <?= $departamento===0?'selected':''; ?>>Todos</option>
                <?php foreach ($departamentos_cat as $id=>$nom): ?>
                  <option value="<?= (int)$id ?>" <?= ($departamento===(int)$id)?'selected':''; ?>>
                    <?= htmlspecialchars($nom) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarRev): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selRevision">RevisiÃ³n:</label>
            <select name="revision" id="selRevision" class="form-control">
              <option value=""   <?= $revision===''?'selected':''; ?>>Todos</option>
              <option value="0"  <?= $revision==='0'?'selected':''; ?>>No solicitÃ³</option>
              <option value="1"  <?= $revision==='1'?'selected':''; ?>>SÃ­ solicitÃ³</option>
              <option value="2"  <?= $revision==='2'?'selected':''; ?>>Aprobado</option>
              <option value="3"  <?= $revision==='3'?'selected':''; ?>>Observado</option>
              <option value="sin"<?= $revision==='sin'?'selected':''; ?>>Sin Informe Semestral</option>
            </select>
          </div>
        <?php endif; ?>
<!-- NUEVO: Estado / Oficina -->
<div class="col-12 col-md-3 col-lg-3">
  <label class="form-label" for="selOficina">Estado / Oficina:</label>
  <select name="oficina" id="selOficina" class="form-control">
    <option value="" <?= $oficina===''?'selected':''; ?>>Todos</option>
    <option value="PCF"  <?= $oficina==='PCF'?'selected':''; ?>>ComitÃ© de Facultad</option>
    <option value="DD"   <?= $oficina==='DD'?'selected':''; ?>>DirecciÃ³n de Departamento</option>
    <option value="DF"   <?= $oficina==='DF'?'selected':''; ?>>Decanato de Facultad</option>
    <option value="RSU"  <?= $oficina==='RSU'?'selected':''; ?>>DirecciÃ³n RSU</option>
    <option value="APROB"<?= $oficina==='APROB'?'selected':''; ?>>AprobaciÃ³n Total</option>
    <option value="SIN"  <?= $oficina==='SIN'?'selected':''; ?>>sin Estado / Oficina</option>
  </select>
</div>

        <?php if ($mostrarPer): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selPeriodo">PerÃ­odo:</label>
            <select name="periodo" id="selPeriodo" class="form-control">
              <option value="0" <?= $periodo===0?'selected':''; ?>>Todos</option>
              <?php foreach ($periodos as $id=>$nom): ?>
                <option value="<?= (int)$id ?>" <?= ($periodo===(int)$id)?'selected':''; ?>>
                  <?= htmlspecialchars($nom) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarBusq): ?>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="txtQ">BÃºsqueda:</label>
            <input type="text" name="q" id="txtQ" value="<?= htmlspecialchars($q) ?>" class="form-control"
                   placeholder="Coordinador, cÃ³digo, id, tÃ­tulo">
          </div>
        <?php endif; ?>

        <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end justify-content-end">
          <div class="d-flex w-100" style="gap:6px;">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i>
            </button>
            <a class="btn btn-danger" title="Limpiar filtros"
               href="<?= htmlspecialchars(link_con_filtros(1, ['facultad'=>0,'departamento'=>0,'revision'=>'','periodo'=>0,'q'=>''])) ?>">
              <i class="fas fa-broom"></i>
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
<!-- Resumen -->
<div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-2">
  <div>
    <i class="fas fa-database"></i>
    Mostrando <strong><?= ($total_items > 0) ? $desde . 'â€“' . $hasta : 0 ?></strong>
    de <strong><?= number_format($total_items) ?></strong> resultado<?= ($total_items === 1) ? '' : 's' ?>.
  </div>
  <div class="text-muted small">
    PÃ¡gina <?= (int)$pagina ?> de <?= (int)$total_pages ?>
  </div>
</div>

<!-- ======= TABLA ======= -->
<div style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
  <table class="table table-bordered table-hover" width="100%">
    <thead>
      <tr>
        <th style="width:4%;">#</th>
        <th style="width:34%;">TÃ­tulo del proyecto</th>
        <th style="width:18%;">Coordinador</th>
        <th style="width:12%;">PrÃ³ximo paso</th>
        <th style="width:14%;">Estado / Oficina</th>
        <th style="width:18%;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="6" class="text-center">Sin registros</td></tr>
      <?php else: foreach ($items as $i => $it): ?>
        <?php
          // Resolver facultad_id y periodo_id desde los nombres
          $facultad_id = 0;
          if (!empty($it['facultad']) && isset($fac_by_name_to_id[$it['facultad']])) {
            $facultad_id = (int)$fac_by_name_to_id[$it['facultad']];
          }
          $periodo_id = 0;
          if (!empty($it['periodo']) && isset($per_by_name_to_id[$it['periodo']])) {
            $periodo_id = (int)$per_by_name_to_id[$it['periodo']];
          }

          // Consultar si ya tiene cÃ³digo para este periodo (si conocemos periodo_id)
          $codigo_asignado = '';
          if ($periodo_id > 0 && isset($conexion) && $conexion instanceof mysqli) {
            $sqlC = "SELECT codigo FROM proyecto_codigos WHERE id_py=? AND periodo_id=? LIMIT 1";
            if ($stmtC = mysqli_prepare($conexion, $sqlC)) {
              mysqli_stmt_bind_param($stmtC, 'ii', $it['id_py'], $periodo_id);
              mysqli_stmt_execute($stmtC);
              $rsC = mysqli_stmt_get_result($stmtC);
              if ($rC = mysqli_fetch_assoc($rsC)) $codigo_asignado = (string)$rC['codigo'];
              mysqli_stmt_close($stmtC);
            }
          }

          $tiene_codigo = ($codigo_asignado !== '');
          $btn_disabled = ($tiene_codigo || $facultad_id<=0 || $periodo_id<=0) ? 'disabled' : '';
          $btn_title    = $tiene_codigo ? 'Ya tiene cÃ³digo en este periodo'
                         : (($facultad_id<=0||$periodo_id<=0) ? 'Falta identificar Periodo/Facultad' : '');
        ?>
        <tr class="fila-toggle" data-id="<?= $i ?>">
          <td><?= ($pagina - 1) * $por_pagina + $i + 1 ?></td>
          <td>
            <?= htmlspecialchars($it['titulo']) ?><br>
            <span class="badge badge-secondary bg-secondary"><?= htmlspecialchars($it['periodo']) ?></span>
            <?php if ($tiene_codigo): ?>
              <br><span class="badge badge-code">CÃ“DIGO: <?= htmlspecialchars($codigo_asignado) ?></span>
            <?php endif; ?>
          </td>
          <td><?= htmlspecialchars($it['coordinador']) ?></td>

          <!-- PrÃ³ximo paso -->
          <td>
            <?php
              $estPrin = (string)($it['estado_oficina'] ?? 'â€”');
              $respId  = $it['resp_id'] ?? null;
              $respSt  = $it['resp_estado'] ?? null;
              $sitEva  = $it['situacion'] ?? null;
              $ofCod   = $it['oficina_cod'] ?? null;
              $cj = $it['cotejo_estado']  ?? null;
              $rb = $it['rubrica_estado'] ?? null;
              $vb = $it['vb_estado']      ?? null;
              $instSt  = $it['instancia_estado'] ?? null;

              if ($respId === null) {
                echo 'El coordinador debe crear su informe semestral.';
              } elseif ($sitEva === null && (int)$respSt === 0) {
                echo 'El coordinador debe completar su informe y solicitar RevisiÃ³n.';
              } elseif (mb_strtolower($estPrin,'UTF-8') === 'aprobaciÃ³n total') {
                echo 'Sin acciones requeridas.';
              } elseif ($cj === 'observado' || $rb === 'observado') {
                echo 'El coordinador debe subsanar informe.<br>';
                if ($cj === 'observado') {
                  echo '<button type="button" class="btn btn-sm btn-outline-danger mt-1 btn-detalle-obs" data-id_py="'.(int)$it['id_py'].'" data-tipo="cotejo"><i class="fas fa-exclamation-triangle"></i> Detalle ObservaciÃ³n Cotejo</button><br>';
                }
                if ($rb === 'observado') {
                  echo '<button type="button" class="btn btn-sm btn-outline-danger mt-1 btn-detalle-obs" data-id_py="'.(int)$it['id_py'].'" data-tipo="rubrica"><i class="fas fa-exclamation-triangle"></i> Detalle ObservaciÃ³n RÃºbrica</button>';
                }
              } elseif ($instSt === 'en_espera' || $instSt === 'aprobado' || $instSt === null) {
                $rol = rolCalificadorPorCodigo($ofCod);
                echo 'El ' . htmlspecialchars($rol) . ' debe Calificar el proyecto para continuar.';

                $chips = [];
                if (in_array($ofCod, ['PCF','RSU'], true)) {
                  if ($cj) $chips[] = ['Cotejo', $cj];
                  if ($rb) $chips[] = ['RÃºbrica', $rb];
                } elseif (in_array($ofCod, ['DD','DF'], true)) {
                  if ($vb) $chips[] = ['Visto Bueno', $vb];
                }
                if (!empty($chips)) {
                  echo '<div class="mt-1">';
                  foreach ($chips as [$nom, $st]) {
                    $cls = ($st==='aprobado') ? 'badge badge-success bg-success' : 'badge badge-primary bg-primary';
                    $txt = ($st==='aprobado') ? ($nom.' aprobado') : ($nom.' en Espera');
                    echo '<span class="'.$cls.'" style="margin-right:4px;">'.htmlspecialchars($txt).'</span>';
                  }
                  echo '</div>';
                }
              } else {
                echo '&mdash;';
              }
            ?>
          </td>

          <!-- Estado / Oficina -->
          <td>
            <?php
              $main = (string)($it['estado_oficina'] ?? 'â€”');
              $sub  = (string)($it['estado_sub']     ?? '');
              $dt   = (string)($it['estado_dt']      ?? '');

              $dtTxt = '';
              if ($dt !== '') { $ts = strtotime($dt); $dtTxt = $ts ? date('d/m/Y H:i', $ts) : $dt; }

              if ($main === 'Sin Informe Semestral' || $main === 'No solicitÃ³ RevisiÃ³n' || $main === 'â€”') {
                echo '--';
              } else {
                $clsMain = badgeClaseEstadoOficina($main);
                echo '<span class="'. $clsMain .'">'. htmlspecialchars($main) .'</span>';
                if ($main === 'AprobaciÃ³n Total') {
                  if ($dtTxt !== '') echo '<br><small class="text-muted">'. htmlspecialchars($dtTxt) .'</small>';
                } else {
                  if ($sub !== '') {
                    $clsSub = badgeClaseSubEstado($sub);
                    echo '<br><span class="'. $clsSub .'">'. htmlspecialchars($sub) .'</span>';
                    if ($dtTxt !== '') echo '<br><small class="text-muted">'. htmlspecialchars($dtTxt) .'</small>';
                  }
                }
              }
            ?>
          </td>

          <!-- Acciones (conectadas) -->
          <td>
            <div class="d-flex flex-column">
              <button type="button"
                class="btn btn-sm btn-primary mb-1 w-100 btn-digitar-codigo" data-no-toggle="1"
                title="<?= htmlspecialchars($btn_title ?: 'Digitar/seleccionar un cÃ³digo pre-generado') ?>"
                data-id_py="<?= (int)$it['id_py'] ?>"
                data-facultad_id="<?= (int)$facultad_id ?>"
                data-periodo_id="<?= (int)$periodo_id ?>"
                <?= $btn_disabled ?>>
                <i class="fas fa-keyboard"></i> Digitar cÃ³digo
              </button>
              <button type="button"
                class="btn btn-sm btn-secondary w-100 btn-generar-codigo" data-no-toggle="1"
                title="<?= htmlspecialchars($btn_title ?: 'Generar el siguiente cÃ³digo secuencial') ?>"
                data-id_py="<?= (int)$it['id_py'] ?>"
                data-facultad_id="<?= (int)$facultad_id ?>"
                data-periodo_id="<?= (int)$periodo_id ?>"
                <?= $btn_disabled ?>>
                <i class="fas fa-hashtag"></i> Generar cÃ³digo
              </button>
              <?php if ($btn_disabled && !$tiene_codigo): ?>
                <small class="small-muted mt-1">Seleccione un <strong>PerÃ­odo</strong> en filtros y verifique la <strong>Facultad</strong>.</small>
              <?php endif; ?>
            </div>
          </td>
        </tr>

        <!-- Fila extra -->
        <tr class="fila-extra fila-extra-<?= $i ?>" style="display:none;background:#f9f9f9;">
          <td colspan="6" class="text-center">
            <p style="margin-bottom:6px;">
              <strong>Facultad:</strong> <?= htmlspecialchars($it['facultad']) ?> |
              <strong>Departamento:</strong> <?= htmlspecialchars($it['departamento']) ?>
            </p>
            <p style="margin:0;">
              <strong>CÃ³digo Docente:</strong> <?= htmlspecialchars($it['cod_docente']) ?> |
              <strong>id_py:</strong> <?= htmlspecialchars($it['id_py']) ?>
            </p>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- PaginaciÃ³n -->
<?php if ($total_pages > 1): ?>
  <nav aria-label="PaginaciÃ³n" class="mt-2">
    <ul class="pagination justify-content-center">
      <?php foreach ($pages as $p): ?>
        <?php if ($p === '...'): ?>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">â€¢</span></li>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">â€¢</span></li>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">â€¢</span></li>
        <?php else: ?>
          <?php if ((int)$p === (int)$pagina): ?>
            <li class="page-item active" aria-current="page"><span class="page-link"><?= (int)$p ?></span></li>
          <?php else: ?>
            <li class="page-item"><a class="page-link" href="<?= htmlspecialchars(link_con_filtros((int)$p, $filtros)) ?>"><?= (int)$p ?></a></li>
          <?php endif; ?>
        <?php endif; ?>
      <?php endforeach; ?>
    </ul>
  </nav>
<?php endif; ?>
<!-- ===== Modal: Detalle ObservaciÃ³n ===== -->
<div class="modal fade" id="modalDetalleObs" tabindex="-1" role="dialog" aria-labelledby="tituloDetObs" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white py-2">
        <h5 class="modal-title" id="tituloDetObs"><i class="fas fa-exclamation-triangle"></i> Detalle de ObservaciÃ³n</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="contenidoDetObs" class="modal-body">
        <p class="text-center text-muted my-4">Cargandoâ€¦</p>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Digitar cÃ³digo (seleccionar del pool) ===== -->
<div class="modal fade" id="modalDigitar" tabindex="-1" role="dialog" aria-labelledby="tituloDigitar" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="frmDigitar" class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title" id="tituloDigitar"><i class="fas fa-keyboard"></i> Asignar cÃ³digo (pool)</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_py" id="dg_id_py">
        <input type="hidden" name="facultad_id" id="dg_facultad_id">
        <input type="hidden" name="periodo_id" id="dg_periodo_id">
        <div class="form-group">
          <label for="dg_codigo">Seleccione un cÃ³digo disponible</label>
          <select id="dg_codigo" class="form-control"></select>
          <small class="form-text text-muted">Fuente: <code>codigo_pool</code>.</small>
        </div>
        <div id="dg_msg" class="text-danger small mt-2" style="display:none;"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> Asignar</button>
      </div>
    </form>
  </div>
</div>

<script>
/* Auto-submit + cascada filtros + debounce bÃºsqueda (ahora incluye Estado/Oficina) */
(function(){
  const form = document.getElementById('frmFiltros');
  if (!form) return;
  const fac = document.getElementById('selFacultad');
  const dep = document.getElementById('selDepartamento');
  const rev = document.getElementById('selRevision');
  const ofi = document.getElementById('selOficina'); // NUEVO
  const per = document.getElementById('selPeriodo');
  const q   = document.getElementById('txtQ');

  function submit(){ form.requestSubmit ? form.requestSubmit() : form.submit(); }

  [fac, dep, rev, ofi, per].forEach(el => { if (!el) return;
    el.addEventListener('change', function(){
      if (this === fac && dep) {
        dep.value = '0';
        if (fac.value === '0') { dep.setAttribute('disabled','disabled'); }
        else { dep.removeAttribute('disabled'); }
      }
      submit();
    });
  });

  if (dep && fac) {
    if (fac.value === '0' && !dep.hasAttribute('disabled')) dep.setAttribute('disabled','disabled');
  }

  if (q) {
    let t=null;
    q.addEventListener('input', function(){ clearTimeout(t); t=setTimeout(submit,600); });
    q.addEventListener('keypress', function(e){ if (e.key==='Enter'){ e.preventDefault(); submit(); }});
  }
})();
</script>
<script>
/* Toggle fila extra (ignora clicks en controles interactivos para no interferir con los modales) */
(function(){
  const rows = document.querySelectorAll('.fila-toggle');
  rows.forEach(function(row){
    row.addEventListener('click', function(ev){
      // Si el click vino desde un botÃ³n, link, input, select, etc., NO togglear la fila
      const ignora = ev.target.closest('button, a, .btn, input, select, label, [data-no-toggle]');
      if (ignora) return;

      const id = this.dataset.id;
      const extra = document.querySelector('.fila-extra-' + id);
      if (!extra) return;
      extra.style.display = (extra.style.display === 'none' || !extra.style.display) ? 'table-row' : 'none';
    }, false);
  });
})();
</script>
<script>
/* Modal Detalle ObservaciÃ³n (igual que antes) */
(function () {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-detalle-obs');
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    const idpy = btn.getAttribute('data-id_py');
    const tipo = btn.getAttribute('data-tipo');
    if (!idpy || !tipo) return;

    const $contenedor = window.jQuery ? jQuery('#contenidoDetObs') : null;
    if ($contenedor) $contenedor.html('<p class="text-center text-muted my-4">Cargandoâ€¦</p>');

    const url = '/sistema_web/evaluacion/modales/detalle_observacion.php?id_py='
              + encodeURIComponent(idpy) + '&tipo=' + encodeURIComponent(tipo);

    if (window.jQuery) {
      jQuery.get(url, function (html) { jQuery('#contenidoDetObs').html(html); }, 'html');
      jQuery('#modalDetalleObs').modal('show'); return;
    }
    fetch(url).then(r => r.text())
      .then(html => { document.getElementById('contenidoDetObs').innerHTML = html; })
      .catch(() => { document.getElementById('contenidoDetObs').innerHTML = '<div class="text-danger p-3">No se pudo cargar el detalle.</div>'; });
    const modal = document.getElementById('modalDetalleObs');
    if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(modal).show();
    else { modal.classList.add('show'); modal.style.display='block'; modal.removeAttribute('aria-hidden'); modal.setAttribute('aria-modal','true'); }
  }, false);
})();
</script>
<script>
/* ===== DIGITAR CÃ“DIGO (pool) â€” compatible con codigo_pool.disponible ===== */
(function(){
  const $ = window.jQuery;
  function postJSON(data){
    return $.ajax({url: window.location.href, method:'POST', data: data, dataType:'json'});
  }
  let rowCtx = null;

  $(document).on('click', '.btn-digitar-codigo', function(e){
    e.preventDefault(); e.stopPropagation();
    rowCtx = $(this).closest('tr');
    const id_py      = $(this).data('id_py');
    const facultadId = $(this).data('facultad_id');
    const periodoId  = $(this).data('periodo_id');

    if (!id_py || !facultadId || !periodoId) {
      alert('No se pudo determinar Periodo/Facultad. Seleccione filtros correctamente.'); return;
    }
    $('#dg_id_py').val(id_py);
    $('#dg_facultad_id').val(facultadId);
    $('#dg_periodo_id').val(periodoId);
    $('#dg_msg').hide().text('');
    $('#dg_codigo').empty().append($('<option>',{value:'',text:'Cargando...'}));

    postJSON({action:'listar_codigos_disponibles', periodo_id:periodoId, facultad_id:facultadId})
      .done(function(r){
        $('#dg_codigo').empty();
        if (!r.ok) { $('#dg_codigo').append($('<option>',{value:'',text:r.msg||'Error'})); return; }
        if (!r.items || !r.items.length){
          $('#dg_codigo').append($('<option>',{value:'',text:'No hay cÃ³digos disponibles en el pool'}));
        } else {
          $('#dg_codigo').append($('<option>',{value:'',text:'-- Seleccione --'}));
          r.items.forEach(function(it){
            $('#dg_codigo').append($('<option>',{ value: it.id, text: it.codigo }));
          });
        }
      })
      .fail(function(){ $('#dg_codigo').empty().append($('<option>',{value:'',text:'Error de red'})); });

    $('#modalDigitar').modal('show');
  });

  $('#frmDigitar').on('submit', function(e){
    e.preventDefault();
    const pool_id = $('#dg_codigo').val();
    const id_py   = $('#dg_id_py').val();
    if (!pool_id) { $('#dg_msg').text('Seleccione un cÃ³digo.').show(); return; }
    postJSON({action:'asignar_codigo_pool', pool_id:pool_id, id_py:id_py})
      .done(function(r){
        if (!r.ok){ $('#dg_msg').text(r.msg||'No se pudo asignar.').show(); return; }
        if (rowCtx) {
          rowCtx.find('.btn-digitar-codigo, .btn-generar-codigo').prop('disabled', true)
                .attr('title','Ya tiene cÃ³digo en este periodo');
          const titleCell = rowCtx.find('td').eq(1);
          if (titleCell.find('.badge-code').length===0) {
            titleCell.append('<br><span class="badge badge-code">CÃ“DIGO: '+$('<div>').text(r.codigo).html()+'</span>');
          }
        }
        $('#modalDigitar').modal('hide');
      })
      .fail(function(){ $('#dg_msg').text('Error de red.').show(); });
  });
})();
</script>

<script>
/* ===== GENERAR CÃ“DIGO (secuencial; alias+width: codigo_alias_facultad; secuencia: codigo_secuencias_periodo) ===== */
(function(){
  const $ = window.jQuery;
  function postJSON(data){
    return $.ajax({url: window.location.href, method:'POST', data: data, dataType:'json'});
  }
  $(document).on('click', '.btn-generar-codigo', function(e){
    e.preventDefault(); e.stopPropagation();
    const $btn = $(this);
    const id_py      = $btn.data('id_py');
    const facultadId = $btn.data('facultad_id');
    const periodoId  = $btn.data('periodo_id');
    if (!id_py || !facultadId || !periodoId) { alert('Faltan datos: Periodo/Facultad.'); return; }
    $btn.prop('disabled', true);
    postJSON({action:'generar_codigo_auto', id_py:id_py, facultad_id:facultadId, periodo_id:periodoId})
      .done(function(r){
        if (!r.ok){ alert(r.msg||'No se pudo generar.'); $btn.prop('disabled', false); return; }
        // UI: deshabilitar ambos y pintar badge
        const rowCtx = $btn.closest('tr');
        rowCtx.find('.btn-digitar-codigo, .btn-generar-codigo').prop('disabled', true)
              .attr('title','Ya tiene cÃ³digo en este periodo');
        const titleCell = rowCtx.find('td').eq(1);
        if (titleCell.find('.badge-code').length===0) {
          titleCell.append('<br><span class="badge badge-code">CÃ“DIGO: '+$('<div>').text(r.codigo).html()+'</span>');
        }
      })
      .fail(function(){ alert('Error de red.'); $btn.prop('disabled', false); });
  });
})();
</script>
</section>
        </div>
        <footer class="main-footer">
            <strong>Â© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://adminlte.io"> Ãrea  informÃ¡tica - DIRSU</a></p>
            </div>
        </footer>
    </div>
      <!-- jQuery UI 1.11.4 -->
      <script src="../plogins/jquery-ui/jquery-ui.min.js"></script>
      <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
      <script>
         $.widget.bridge('uibutton', $.ui.button)
      </script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- Sparkline -->
      <script src="../plogins/sparklines/sparkline.js"></script>
      <!-- JQVMap -->
      <script src="../plogins/jqvmap/jquery.vmap.min.js"></script>
      <script src="../plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
      <!-- jQuery Knob Chart -->
      <script src="../plogins/jquery-knob/jquery.knob.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
      <script src="../dust/js/pages/dashboard.js"></script>
</body>
</html>

