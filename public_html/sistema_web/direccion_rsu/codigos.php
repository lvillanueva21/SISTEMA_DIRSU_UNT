<?php
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
// Incluir configSesion.php para verificar la sesión
include "../componentes/configSesion.php";
// Incluir la conexión a la base de datos
include('../componentes/db.php');
include_once __DIR__ . '/../evaluacion/funciones.php';
?>
<?php
/* ==== ENDPOINTS AJAX (para DIGITAR desde codigo_pool y GENERAR secuencial) ==== */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
  ini_set('display_errors', 0);
  ini_set('display_startup_errors', 0);
  if (function_exists('ob_get_level')) {
    while (ob_get_level() > 0) { @ob_end_clean(); }
  }
  header('Content-Type: application/json; charset=utf-8');

  if (!isset($conexion) || !$conexion) {
    echo '{"ok":false,"msg":"Sin conexión a BD"}'; exit;
  }
  if ($conexion instanceof mysqli) {
    @mysqli_set_charset($conexion, 'utf8mb4');
  }

  $emitJson = function(array $payload) {
    $flags = 0;
    if (defined('JSON_UNESCAPED_UNICODE')) $flags |= JSON_UNESCAPED_UNICODE;
    if (defined('JSON_UNESCAPED_SLASHES')) $flags |= JSON_UNESCAPED_SLASHES;
    if (defined('JSON_INVALID_UTF8_SUBSTITUTE')) $flags |= JSON_INVALID_UTF8_SUBSTITUTE;
    $json = json_encode($payload, $flags);
    if ($json === false) {
      $fallback = [
        'ok' => false,
        'msg' => 'json_encode_failed',
        'json_error' => function_exists('json_last_error_msg') ? json_last_error_msg() : 'unknown',
      ];
      $json = json_encode($fallback);
      if ($json === false) $json = '{"ok":false,"msg":"json_encode_failed_hard"}';
    }
    echo $json;
    exit;
  };
  register_shutdown_function(function() {
    $e = error_get_last();
    if (!$e) return;
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if (!in_array($e['type'], $fatalTypes, true)) return;
    if (headers_sent()) return;
    header('Content-Type: application/json; charset=utf-8');
    $msg = 'fatal_error: ' . ($e['message'] ?? 'unknown') . ' @ ' . ($e['file'] ?? '') . ':' . ($e['line'] ?? '');
    $json = json_encode(['ok'=>false,'msg'=>$msg]);
    if ($json === false) $json = '{"ok":false,"msg":"fatal_error"}';
    echo $json;
  });

  $ok  = function($data=[]) use ($emitJson) { $emitJson(array_merge(['ok'=>true],  $data)); };
  $err = function($msg, $extra=[]) use ($emitJson) { $emitJson(array_merge(['ok'=>false,'msg'=>$msg], $extra)); };
  $id_rol_sesion = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
  $extraerCorrel = function(string $codigo): int {
    if (preg_match('/^[^-]+-([0-9]+)-[0-9]{4}$/', $codigo, $m)) return (int)$m[1];
    return 0;
  };

  $action = $_POST['action'];

  // 1) Listar códigos disponibles del pool por periodo y facultad
  if ($action === 'listar_codigos_disponibles') {
    $periodo_id  = isset($_POST['periodo_id'])  ? (int)$_POST['periodo_id']  : 0;
    $facultad_id = isset($_POST['facultad_id']) ? (int)$_POST['facultad_id'] : 0;
    if ($periodo_id <= 0 || $facultad_id <= 0) $err('Seleccione Período y Facultad.');

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

  // 2) Asignar código desde el pool al proyecto
  if ($action === 'asignar_codigo_pool') {
    $pool_id = isset($_POST['pool_id']) ? (int)$_POST['pool_id'] : 0;
    $id_py   = isset($_POST['id_py'])   ? (int)$_POST['id_py']   : 0;
    if ($pool_id <= 0 || $id_py <= 0) $err('Parámetros incompletos.');

    mysqli_begin_transaction($conexion);

    // (a) Bloqueo del código
    $sql = "SELECT periodo_id, codigo, disponible
              FROM codigo_pool
             WHERE id=? FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (pool)'); }
    mysqli_stmt_bind_param($stmt, 'i', $pool_id);
    mysqli_stmt_execute($stmt);
    $rs  = mysqli_stmt_get_result($stmt);
    $row = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);

    if (!$row) { mysqli_rollback($conexion); $err('Código no encontrado.'); }
    if ((int)$row['disponible'] !== 1) { mysqli_rollback($conexion); $err('El código ya no está disponible.'); }

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
    if ($ya) { mysqli_rollback($conexion); $err('Este proyecto ya tiene código en ese período.'); }

    // (c) Consumir del pool
    $sql = "UPDATE codigo_pool
               SET disponible=0
             WHERE id=? AND disponible=1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (upd pool)'); }
    mysqli_stmt_bind_param($stmt, 'i', $pool_id);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) <= 0) { mysqli_stmt_close($stmt); mysqli_rollback($conexion); $err('No se pudo reservar el código.'); }
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
    $ok(['codigo'=>$codigo, 'pool_id'=>$pool_id]);
  }

  // 3) Previsualizar código automático (sin reservar)
  if ($action === 'preview_codigo_auto') {
    $id_py       = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
    $facultad_id = isset($_POST['facultad_id']) ? (int)$_POST['facultad_id'] : 0;
    $periodo_id  = isset($_POST['periodo_id']) ? (int)$_POST['periodo_id'] : 0;
    if ($id_py<=0 || $facultad_id<=0 || $periodo_id<=0) $err('Parámetros incompletos.');

    $sql = "SELECT alias, correlativo_width FROM codigo_alias_facultad WHERE facultad_id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) $err('Error SQL (alias preview)');
    mysqli_stmt_bind_param($stmt, 'i', $facultad_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $al = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$al) $err('No hay alias definido para la facultad.');
    $alias = (string)$al['alias'];
    $width = (int)$al['correlativo_width'];
    if ($width <= 0) $width = 3;

    $sql = "SELECT nombre FROM periodos WHERE id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) $err('Error SQL (periodo preview)');
    mysqli_stmt_bind_param($stmt, 'i', $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $pr = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$pr) $err('Período inválido.');
    $yyyy = (int)substr($pr['nombre'], 0, 4);
    if ($yyyy <= 0) $yyyy = (int)date('Y');

    $sql = "SELECT id FROM proyecto_codigos WHERE id_py=? AND periodo_id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) $err('Error SQL (chk preview)');
    mysqli_stmt_bind_param($stmt, 'ii', $id_py, $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $ya = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if ($ya) $err('El proyecto ya tiene código en este periodo.');

    $pool_id = 0;
    $codigo = '';
    $fuente = 'nuevo';
    $sql = "SELECT cp.id, cp.codigo
              FROM codigo_pool cp
             WHERE cp.periodo_id=? AND cp.facultad_id=? AND cp.disponible=1
               AND NOT EXISTS (
                 SELECT 1 FROM proyecto_codigos pc
                  WHERE pc.periodo_id = cp.periodo_id
                    AND pc.codigo = cp.codigo
               )
             ORDER BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cp.codigo,'-',2),'-',-1) AS UNSIGNED) ASC, cp.id ASC
             LIMIT 1";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
      mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      if ($rw = mysqli_fetch_assoc($rs)) {
        $pool_id = (int)$rw['id'];
        $codigo  = (string)$rw['codigo'];
        $fuente  = 'pool';
      }
      mysqli_stmt_close($stmt);
    }

    if ($codigo === '') {
      $max_ultimo = 0;
      $sql = "SELECT ultimo FROM codigo_secuencias_periodo WHERE periodo_id=? AND facultad_id=? LIMIT 1";
      if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        if ($r = mysqli_fetch_assoc($rs)) $max_ultimo = (int)$r['ultimo'];
        mysqli_stmt_close($stmt);
      }

      $max_asignados = 0;
      $sql = "SELECT codigo FROM proyecto_codigos WHERE periodo_id=? AND codigo LIKE CONCAT(?, '-%')";
      if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'is', $periodo_id, $alias);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) {
          $n = $extraerCorrel((string)$r['codigo']);
          if ($n > $max_asignados) $max_asignados = $n;
        }
        mysqli_stmt_close($stmt);
      }

      $max_pool = 0;
      $sql = "SELECT codigo FROM codigo_pool WHERE periodo_id=? AND facultad_id=?";
      if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) {
          $n = $extraerCorrel((string)$r['codigo']);
          if ($n > $max_pool) $max_pool = $n;
        }
        mysqli_stmt_close($stmt);
      }

      $next = max($max_ultimo, $max_asignados, $max_pool) + 1;
      $correl = str_pad((string)$next, $width, '0', STR_PAD_LEFT);
      $codigo = $alias . '-' . $correl . '-' . $yyyy;
    }

    $ok(['codigo'=>$codigo, 'pool_id'=>$pool_id, 'fuente'=>$fuente]);
  }

  // 4) Generar código automático (reutiliza pool libre y, si no hay, genera siguiente)
  if ($action === 'generar_codigo_auto') {
    $id_py       = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
    $facultad_id = isset($_POST['facultad_id']) ? (int)$_POST['facultad_id'] : 0;
    $periodo_id  = isset($_POST['periodo_id']) ? (int)$_POST['periodo_id'] : 0;
    if ($id_py<=0 || $facultad_id<=0 || $periodo_id<=0) $err('Parámetros incompletos.');

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

    // (b) Año desde periodos.nombre (p.ej. '2025-I' -> 2025)
    $sql = "SELECT nombre FROM periodos WHERE id=? FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (periodo)'); }
    mysqli_stmt_bind_param($stmt, 'i', $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $pr = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$pr) { mysqli_rollback($conexion); $err('Período inválido.'); }
    $yyyy = (int)substr($pr['nombre'], 0, 4);
    if ($yyyy <= 0) $yyyy = (int)date('Y');

    // (c) ¿El proyecto ya tiene código en ese periodo?
    $sql = "SELECT id FROM proyecto_codigos WHERE id_py=? AND periodo_id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (chk existe)'); }
    mysqli_stmt_bind_param($stmt, 'ii', $id_py, $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $ya = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if ($ya) { mysqli_rollback($conexion); $err('El proyecto ya tiene código en este periodo.'); }

    // (d) Reutilizar primero el menor código disponible del pool
    $pool_id = 0;
    $codigo = '';
    $sql = "SELECT cp.id, cp.codigo
              FROM codigo_pool cp
             WHERE cp.periodo_id=? AND cp.facultad_id=? AND cp.disponible=1
               AND NOT EXISTS (
                 SELECT 1 FROM proyecto_codigos pc
                  WHERE pc.periodo_id = cp.periodo_id
                    AND pc.codigo = cp.codigo
               )
             ORDER BY CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(cp.codigo,'-',2),'-',-1) AS UNSIGNED) ASC, cp.id ASC
             LIMIT 1
             FOR UPDATE";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
      mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
      mysqli_stmt_execute($stmt);
      $rs = mysqli_stmt_get_result($stmt);
      if ($rw = mysqli_fetch_assoc($rs)) {
        $pool_id = (int)$rw['id'];
        $codigo  = (string)$rw['codigo'];
      }
      mysqli_stmt_close($stmt);
    }

    if ($pool_id > 0) {
      $sql = "UPDATE codigo_pool SET disponible=0 WHERE id=? AND disponible=1";
      if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (consumir pool auto)'); }
      mysqli_stmt_bind_param($stmt, 'i', $pool_id);
      mysqli_stmt_execute($stmt);
      if (mysqli_stmt_affected_rows($stmt) <= 0) { mysqli_stmt_close($stmt); mysqli_rollback($conexion); $err('El código del pool ya no está disponible.'); }
      mysqli_stmt_close($stmt);
    } else {
      // (e) Si no hay pool libre, generar siguiente correlativo
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
      $sql = "SELECT codigo FROM proyecto_codigos WHERE periodo_id=? AND codigo LIKE CONCAT(?, '-%')";
      if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'is', $periodo_id, $alias);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) {
          $n = $extraerCorrel((string)$r['codigo']);
          if ($n > $max_asignados) $max_asignados = $n;
        }
        mysqli_stmt_close($stmt);
      }

      $max_pool = 0;
      $sql = "SELECT codigo FROM codigo_pool WHERE periodo_id=? AND facultad_id=?";
      if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $periodo_id, $facultad_id);
        mysqli_stmt_execute($stmt);
        $rs = mysqli_stmt_get_result($stmt);
        while ($r = mysqli_fetch_assoc($rs)) {
          $n = $extraerCorrel((string)$r['codigo']);
          if ($n > $max_pool) $max_pool = $n;
        }
        mysqli_stmt_close($stmt);
      }

      $next = max($max_ultimo, $max_asignados, $max_pool) + 1;
      $correl = str_pad((string)$next, $width, '0', STR_PAD_LEFT);
      $codigo = $alias . '-' . $correl . '-' . $yyyy;

      $sql = "INSERT INTO codigo_secuencias_periodo (periodo_id, facultad_id, ultimo)
              VALUES (?, ?, ?)
              ON DUPLICATE KEY UPDATE ultimo=VALUES(ultimo)";
      if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (upsert secuencia)'); }
      mysqli_stmt_bind_param($stmt, 'iii', $periodo_id, $facultad_id, $next);
      $okUp = mysqli_stmt_execute($stmt);
      $eUp  = mysqli_error($conexion);
      mysqli_stmt_close($stmt);
      if (!$okUp) { mysqli_rollback($conexion); $err('No se pudo actualizar secuencia: '.$eUp); }
    }

    // (f) Insertar en proyecto_codigos (origen=auto)
    $sql = "INSERT INTO proyecto_codigos (id_py, periodo_id, codigo, origen)
            VALUES (?, ?, ?, ?)";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (ins generar)'); }
    $origen = 'auto';
    mysqli_stmt_bind_param($stmt, 'iiss', $id_py, $periodo_id, $codigo, $origen);
    $okIns = mysqli_stmt_execute($stmt);
    $eIns  = mysqli_error($conexion);
    mysqli_stmt_close($stmt);
    if (!$okIns) { mysqli_rollback($conexion); $err('No se pudo registrar: '.$eIns); }

    mysqli_commit($conexion);
    $ok(['codigo'=>$codigo, 'pool_id'=>$pool_id]);
  }

  // 5) Quitar código (solo RSU id_rol=1) y devolverlo al pool
  if ($action === 'quitar_codigo') {
    if ($id_rol_sesion !== 1) $err('No tienes permiso para quitar códigos.');

    $id_py       = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
    $facultad_id = isset($_POST['facultad_id']) ? (int)$_POST['facultad_id'] : 0;
    $periodo_id  = isset($_POST['periodo_id']) ? (int)$_POST['periodo_id'] : 0;
    if ($id_py<=0 || $periodo_id<=0 || $facultad_id<=0) $err('Parámetros incompletos.');

    mysqli_begin_transaction($conexion);

    $sql = "SELECT id, codigo
              FROM proyecto_codigos
             WHERE id_py=? AND periodo_id=?
             LIMIT 1
             FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (get quitar)'); }
    mysqli_stmt_bind_param($stmt, 'ii', $id_py, $periodo_id);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $rw = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);
    if (!$rw) { mysqli_rollback($conexion); $err('El proyecto no tiene código para ese periodo.'); }

    $codigo = (string)$rw['codigo'];
    $id_reg = (int)$rw['id'];

    $sql = "DELETE FROM proyecto_codigos WHERE id=? LIMIT 1";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (del código)'); }
    mysqli_stmt_bind_param($stmt, 'i', $id_reg);
    mysqli_stmt_execute($stmt);
    if (mysqli_stmt_affected_rows($stmt) <= 0) { mysqli_stmt_close($stmt); mysqli_rollback($conexion); $err('No se pudo quitar el código.'); }
    mysqli_stmt_close($stmt);

    $sql = "SELECT id
              FROM codigo_pool
             WHERE periodo_id=? AND facultad_id=? AND codigo=?
             LIMIT 1
             FOR UPDATE";
    if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (pool buscar)'); }
    mysqli_stmt_bind_param($stmt, 'iis', $periodo_id, $facultad_id, $codigo);
    mysqli_stmt_execute($stmt);
    $rs = mysqli_stmt_get_result($stmt);
    $pool = mysqli_fetch_assoc($rs);
    mysqli_stmt_close($stmt);

    if ($pool) {
      $pool_id = (int)$pool['id'];
      $sql = "UPDATE codigo_pool SET disponible=1 WHERE id=?";
      if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (pool liberar)'); }
      mysqli_stmt_bind_param($stmt, 'i', $pool_id);
      $okUpd = mysqli_stmt_execute($stmt);
      $eUpd  = mysqli_error($conexion);
      mysqli_stmt_close($stmt);
      if (!$okUpd) { mysqli_rollback($conexion); $err('No se pudo liberar en pool: '.$eUpd); }
    } else {
      $coment = 'Liberado desde quitar codigo';
      $sql = "INSERT INTO codigo_pool (periodo_id, facultad_id, codigo, disponible, comentario)
              VALUES (?, ?, ?, 1, ?)";
      if (!$stmt = mysqli_prepare($conexion, $sql)) { mysqli_rollback($conexion); $err('Error SQL (pool insertar)'); }
      mysqli_stmt_bind_param($stmt, 'iiss', $periodo_id, $facultad_id, $codigo, $coment);
      $okIns = mysqli_stmt_execute($stmt);
      $eIns  = mysqli_error($conexion);
      mysqli_stmt_close($stmt);
      if (!$okIns) { mysqli_rollback($conexion); $err('No se pudo insertar en pool: '.$eIns); }
      $pool_id = (int)mysqli_insert_id($conexion);
    }

    mysqli_commit($conexion);
    $ok(['codigo'=>$codigo, 'pool_id'=>$pool_id]);
  }

  $err('Acción inválida.');
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
                        <p style="color: white;">Ir a página DIRSU</p>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
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
$creacion     = isset($_GET['creacion']) ? (int)$_GET['creacion'] : (isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0);
$oficina      = isset($_GET['oficina']) ? (string)$_GET['oficina'] : ''; // NUEVO
$q            = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Catálogos
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
  'creacion'     => $creacion,
  'periodo'      => $creacion,
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
    return ($total > 0) ? range(1, $total) : [1];
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
      'creacion'    => (int)($f['creacion'] ?? ($f['periodo'] ?? 0)),
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
$puede_quitar_codigo = ($id_rol === 1);

// ¿Departamento deshabilitado?
$dep_disabled = $mostrarDep && $fac_for_deps <= 0;

// Metadatos por proyecto: fuente oficial de periodo + fechas + facultad_id
$meta_py = [];
if (!empty($items) && isset($conexion) && $conexion instanceof mysqli) {
  $ids = [];
  foreach ($items as $it) {
    $pid = isset($it['id_py']) ? (int)$it['id_py'] : 0;
    if ($pid > 0) $ids[] = $pid;
  }
  $ids = array_values(array_unique($ids));
  if (!empty($ids)) {
    $id_list = implode(',', $ids);
    $sqlMeta = "SELECT
                  p.id AS id_py,
                  p.fecha_inicio,
                  p.fecha_fin,
                  COALESCE(pp.id_periodo, 0) AS periodo_id,
                  COALESCE(pr.nombre, '') AS periodo_nombre,
                  COALESCE((
                    SELECT d2.id_facultad
                    FROM usuarios_proyectos up2
                    INNER JOIN usuarios u2 ON u2.id = up2.id_usuario
                    LEFT JOIN departamentos d2 ON d2.id = u2.id_depa
                    WHERE up2.id_proyecto = p.id AND up2.activo = 1
                    ORDER BY up2.id DESC
                    LIMIT 1
                  ), 0) AS facultad_id
                FROM proyectos p
                LEFT JOIN proyectos_periodo pp ON pp.id = (
                  SELECT ppx.id
                  FROM proyectos_periodo ppx
                  WHERE ppx.id_py = p.id
                  ORDER BY ppx.id DESC
                  LIMIT 1
                )
                LEFT JOIN periodos pr ON pr.id = pp.id_periodo
                WHERE p.id IN ($id_list)";
    if ($rsMeta = mysqli_query($conexion, $sqlMeta)) {
      while ($rm = mysqli_fetch_assoc($rsMeta)) {
        $meta_py[(int)$rm['id_py']] = [
          'periodo_id' => (int)$rm['periodo_id'],
          'periodo_nombre' => (string)$rm['periodo_nombre'],
          'facultad_id' => (int)$rm['facultad_id'],
          'fecha_inicio' => (string)($rm['fecha_inicio'] ?? ''),
          'fecha_fin' => (string)($rm['fecha_fin'] ?? ''),
        ];
      }
      mysqli_free_result($rsMeta);
    }
  }
}
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
.badge-code-pending { background:#fff; color:#c82333; border:1px solid #c82333; }
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
                <option value="0" selected>Sin Departamento Académico</option>
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
            <label class="form-label" for="selRevision">Revisión:</label>
            <select name="revision" id="selRevision" class="form-control">
              <option value=""   <?= $revision===''?'selected':''; ?>>Todos</option>
              <option value="0"  <?= $revision==='0'?'selected':''; ?>>No solicitó</option>
              <option value="1"  <?= $revision==='1'?'selected':''; ?>>Sí solicitó</option>
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
    <option value="PCF"  <?= $oficina==='PCF'?'selected':''; ?>>Comité de Facultad</option>
    <option value="DD"   <?= $oficina==='DD'?'selected':''; ?>>Dirección de Departamento</option>
    <option value="DF"   <?= $oficina==='DF'?'selected':''; ?>>Decanato de Facultad</option>
    <option value="RSU"  <?= $oficina==='RSU'?'selected':''; ?>>Dirección RSU</option>
    <option value="APROB"<?= $oficina==='APROB'?'selected':''; ?>>Aprobación Total</option>
    <option value="SIN"  <?= $oficina==='SIN'?'selected':''; ?>>sin Estado / Oficina</option>
  </select>
</div>

        <?php if ($mostrarPer): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selCreacion">Creación:</label>
            <select name="creacion" id="selCreacion" class="form-control">
              <option value="0" <?= $creacion===0?'selected':''; ?>>Todos</option>
              <?php foreach ($periodos as $id=>$nom): ?>
                <option value="<?= (int)$id ?>" <?= ($creacion===(int)$id)?'selected':''; ?>>
                  <?= htmlspecialchars($nom) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarBusq): ?>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="txtQ">Búsqueda:</label>
            <input type="text" name="q" id="txtQ" value="<?= htmlspecialchars($q) ?>" class="form-control"
                   placeholder="Coordinador, código, id, título">
          </div>
        <?php endif; ?>

        <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end justify-content-end">
          <div class="d-flex w-100" style="gap:6px;">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i>
            </button>
            <a class="btn btn-danger" title="Limpiar filtros"
               href="<?= htmlspecialchars(link_con_filtros(1, ['facultad'=>0,'departamento'=>0,'revision'=>'','creacion'=>0,'oficina'=>'','q'=>''])) ?>">
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
    Mostrando <strong><?= ($total_items > 0) ? $desde . '–' . $hasta : 0 ?></strong>
    de <strong><?= number_format($total_items) ?></strong> resultado<?= ($total_items === 1) ? '' : 's' ?>.
  </div>
  <div class="text-muted small">
    Página <?= (int)$pagina ?> de <?= (int)$total_pages ?>
  </div>
</div>

<!-- ======= TABLA ======= -->
<div style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
  <table class="table table-bordered table-hover" width="100%">
    <thead>
      <tr>
        <th style="width:4%;">#</th>
        <th style="width:34%;">Título del proyecto</th>
        <th style="width:18%;">Coordinador</th>
        <th style="width:12%;">Próximo paso</th>
        <th style="width:14%;">Estado / Oficina</th>
        <th style="width:18%;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (empty($items)): ?>
        <tr><td colspan="6" class="text-center">Sin registros</td></tr>
      <?php else: foreach ($items as $i => $it): ?>
        <?php
          $pid = (int)$it['id_py'];
          $meta = $meta_py[$pid] ?? [
            'periodo_id' => 0,
            'periodo_nombre' => '',
            'facultad_id' => 0,
            'fecha_inicio' => '',
            'fecha_fin' => '',
          ];

          $periodo_id = (int)$meta['periodo_id'];
          $periodo_nombre = trim((string)$meta['periodo_nombre']);
          if ($periodo_nombre === '') $periodo_nombre = 'No definido';

          $facultad_id = (int)$meta['facultad_id'];
          if ($facultad_id <= 0 && !empty($it['facultad']) && isset($fac_by_name_to_id[$it['facultad']])) {
            $facultad_id = (int)$fac_by_name_to_id[$it['facultad']];
          }

          $fecha_inicio_py = trim((string)$meta['fecha_inicio']);
          $fecha_fin_py = trim((string)$meta['fecha_fin']);

          // Consultar si ya tiene código para este periodo oficial
          $codigo_asignado = '';
          if ($periodo_id > 0 && isset($conexion) && $conexion instanceof mysqli) {
            $sqlC = "SELECT codigo FROM proyecto_codigos WHERE id_py=? AND periodo_id=? LIMIT 1";
            if ($stmtC = mysqli_prepare($conexion, $sqlC)) {
              mysqli_stmt_bind_param($stmtC, 'ii', $pid, $periodo_id);
              mysqli_stmt_execute($stmtC);
              $rsC = mysqli_stmt_get_result($stmtC);
              if ($rC = mysqli_fetch_assoc($rsC)) $codigo_asignado = (string)$rC['codigo'];
              mysqli_stmt_close($stmtC);
            }
          }

          $tiene_codigo = ($codigo_asignado !== '');
          $can_identify = ($facultad_id > 0 && $periodo_id > 0);
          $btn_digitar_disabled = ($tiene_codigo || !$can_identify) ? 'disabled' : '';
          $btn_digitar_title    = $tiene_codigo ? 'Ya tiene código en este periodo'
                                 : (!$can_identify ? 'Falta identificar Periodo/Facultad' : 'Digitar/seleccionar un código pre-generado');
          if ($tiene_codigo && $puede_quitar_codigo) {
            $btn_gestion_disabled = '';
            $btn_gestion_title    = '';
            $accion_gestion       = 'quitar';
            $texto_gestion        = 'Quitar código';
            $icono_gestion        = 'fas fa-trash-alt';
            $clase_gestion        = 'btn btn-sm btn-danger w-100 btn-gestionar-codigo';
          } elseif ($tiene_codigo && !$puede_quitar_codigo) {
            $btn_gestion_disabled = 'disabled';
            $btn_gestion_title    = 'Ya tiene código en este periodo';
            $accion_gestion       = 'none';
            $texto_gestion        = 'Código asignado';
            $icono_gestion        = 'fas fa-lock';
            $clase_gestion        = 'btn btn-sm btn-dark w-100 btn-gestionar-codigo';
          } else {
            $btn_gestion_disabled = $can_identify ? '' : 'disabled';
            $btn_gestion_title    = $can_identify ? '' : 'Falta identificar Periodo/Facultad';
            $accion_gestion       = 'generar';
            $texto_gestion        = 'Generar código';
            $icono_gestion        = 'fas fa-hashtag';
            $clase_gestion        = 'btn btn-sm btn-secondary w-100 btn-gestionar-codigo';
          }
        ?>
        <tr class="fila-toggle" data-id="<?= $i ?>">
          <td><?= ($pagina - 1) * $por_pagina + $i + 1 ?></td>
          <td>
            <?= htmlspecialchars($it['titulo']) ?> <span class="badge badge-secondary bg-secondary"><?= htmlspecialchars($periodo_nombre) ?></span>
            <div class="codigo-badge-slot mt-1">
              <?php if ($tiene_codigo): ?>
                <span class="badge badge-code">CÓDIGO: <?= htmlspecialchars($codigo_asignado) ?></span>
              <?php else: ?>
                <span class="badge badge-code-pending">Código pendiente</span>
              <?php endif; ?>
            </div>
          </td>
          <td><?= htmlspecialchars($it['coordinador']) ?></td>

          <!-- Próximo paso -->
          <td>
            <?php
              $estPrin = (string)($it['estado_oficina'] ?? '—');
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
                echo 'El coordinador debe completar su informe y solicitar Revisión.';
              } elseif (mb_strtolower($estPrin,'UTF-8') === 'aprobación total') {
                echo 'Sin acciones requeridas.';
              } elseif ($cj === 'observado' || $rb === 'observado') {
                echo 'El coordinador debe subsanar informe.<br>';
                if ($cj === 'observado') {
                  echo '<button type="button" class="btn btn-sm btn-outline-danger mt-1 btn-detalle-obs" data-id_py="'.(int)$it['id_py'].'" data-tipo="cotejo"><i class="fas fa-exclamation-triangle"></i> Detalle Observación Cotejo</button><br>';
                }
                if ($rb === 'observado') {
                  echo '<button type="button" class="btn btn-sm btn-outline-danger mt-1 btn-detalle-obs" data-id_py="'.(int)$it['id_py'].'" data-tipo="rubrica"><i class="fas fa-exclamation-triangle"></i> Detalle Observación Rúbrica</button>';
                }
              } elseif ($instSt === 'en_espera' || $instSt === 'aprobado' || $instSt === null) {
                $rol = rolCalificadorPorCodigo($ofCod);
                echo 'El ' . htmlspecialchars($rol) . ' debe Calificar el proyecto para continuar.';

                $chips = [];
                if (in_array($ofCod, ['PCF','RSU'], true)) {
                  if ($cj) $chips[] = ['Cotejo', $cj];
                  if ($rb) $chips[] = ['Rúbrica', $rb];
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
              $main = (string)($it['estado_oficina'] ?? '—');
              $sub  = (string)($it['estado_sub']     ?? '');
              $dt   = (string)($it['estado_dt']      ?? '');

              $dtTxt = '';
              if ($dt !== '') { $ts = strtotime($dt); $dtTxt = $ts ? date('d/m/Y H:i', $ts) : $dt; }

              if ($main === 'Sin Informe Semestral' || $main === 'No solicitó Revisión' || $main === '—') {
                echo '--';
              } else {
                $clsMain = badgeClaseEstadoOficina($main);
                echo '<span class="'. $clsMain .'">'. htmlspecialchars($main) .'</span>';
                if ($main === 'Aprobación Total') {
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
                title="<?= htmlspecialchars($btn_digitar_title) ?>"
                data-id_py="<?= (int)$pid ?>"
                data-facultad_id="<?= (int)$facultad_id ?>"
                data-periodo_id="<?= (int)$periodo_id ?>"
                <?= $btn_digitar_disabled ?>>
                <i class="fas fa-keyboard"></i> Digitar código
              </button>
              <button type="button"
                class="<?= $clase_gestion ?>" data-no-toggle="1"
                title="<?= htmlspecialchars($btn_gestion_title) ?>"
                data-action="<?= htmlspecialchars($accion_gestion) ?>"
                data-id_py="<?= (int)$pid ?>"
                data-facultad_id="<?= (int)$facultad_id ?>"
                data-periodo_id="<?= (int)$periodo_id ?>"
                data-titulo="<?= htmlspecialchars($it['titulo']) ?>"
                data-coordinador="<?= htmlspecialchars($it['coordinador']) ?>"
                data-facultad="<?= htmlspecialchars($it['facultad']) ?>"
                data-periodo="<?= htmlspecialchars($periodo_nombre) ?>"
                data-fecha_inicio="<?= htmlspecialchars($fecha_inicio_py) ?>"
                data-fecha_fin="<?= htmlspecialchars($fecha_fin_py) ?>"
                <?= $btn_gestion_disabled ?>>
                <i class="<?= htmlspecialchars($icono_gestion) ?>"></i> <?= htmlspecialchars($texto_gestion) ?>
              </button>
              <?php if ($btn_gestion_disabled && !$tiene_codigo): ?>
                <small class="small-muted mt-1">Seleccione una <strong>Creación</strong> en filtros y verifique la <strong>Facultad</strong>.</small>
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
              <strong>Código Docente:</strong> <?= htmlspecialchars($it['cod_docente']) ?> |
              <strong>id_py:</strong> <?= htmlspecialchars($it['id_py']) ?>
            </p>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Paginación -->
<?php if ($total_pages > 1): ?>
  <nav aria-label="Paginación" class="mt-2">
    <ul class="pagination justify-content-center">
      <?php foreach ($pages as $p): ?>
        <?php if ($p === '...'): ?>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
          <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
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
<!-- ===== Modal: Detalle Observación ===== -->
<div class="modal fade" id="modalDetalleObs" tabindex="-1" role="dialog" aria-labelledby="tituloDetObs" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white py-2">
        <h5 class="modal-title" id="tituloDetObs"><i class="fas fa-exclamation-triangle"></i> Detalle de Observación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="contenidoDetObs" class="modal-body">
        <p class="text-center text-muted my-4">Cargando…</p>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Digitar código (seleccionar del pool) ===== -->
<div class="modal fade" id="modalDigitar" tabindex="-1" role="dialog" aria-labelledby="tituloDigitar" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form id="frmDigitar" class="modal-content">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title" id="tituloDigitar"><i class="fas fa-keyboard"></i> Asignar código (pool)</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="id_py" id="dg_id_py">
        <input type="hidden" name="facultad_id" id="dg_facultad_id">
        <input type="hidden" name="periodo_id" id="dg_periodo_id">
        <div class="form-group">
          <label for="dg_codigo">Seleccione un código disponible</label>
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

<!-- ===== Modal: Confirmar generación automática ===== -->
<div class="modal fade" id="modalConfirmarCodigo" tabindex="-1" role="dialog" aria-labelledby="tituloConfirmarCodigo" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <form id="frmConfirmarCodigo" class="modal-content">
      <div class="modal-header bg-secondary text-white py-2">
        <h5 class="modal-title" id="tituloConfirmarCodigo"><i class="fas fa-hashtag"></i> Confirmar generación de código</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <input type="hidden" id="cf_id_py">
        <input type="hidden" id="cf_facultad_id">
        <input type="hidden" id="cf_periodo_id">
        <div class="mb-2"><strong>Proyecto:</strong> <span id="cf_titulo"></span></div>
        <div class="mb-1"><strong>ID proyecto:</strong> <span id="cf_id_txt"></span></div>
        <div class="mb-1"><strong>Coordinador:</strong> <span id="cf_coord"></span></div>
        <div class="mb-1"><strong>Facultad:</strong> <span id="cf_fac"></span></div>
        <div class="mb-1"><strong>Periodo de creación:</strong> <span id="cf_periodo"></span></div>
        <div class="mb-1"><strong>Fecha inicio:</strong> <span id="cf_fi"></span></div>
        <div class="mb-2"><strong>Fecha fin:</strong> <span id="cf_ff"></span></div>
        <hr>
        <div><strong>Código propuesto:</strong> <span id="cf_codigo" class="badge badge-dark"></span></div>
        <div class="small text-muted mt-1" id="cf_fuente"></div>
        <div id="cf_msg" class="text-danger small mt-2" style="display:none;"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-secondary"><i class="fas fa-check"></i> Confirmar y generar</button>
      </div>
    </form>
  </div>
</div>

<!-- ===== Modal: Mensajes amigables ===== -->
<div class="modal fade" id="modalMensajeApp" tabindex="-1" role="dialog" aria-labelledby="tituloMensajeApp" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2" id="mensajeHeaderApp">
        <h5 class="modal-title" id="tituloMensajeApp"><i class="fas fa-info-circle"></i> Mensaje</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="contenidoMensajeApp"></div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- ===== Modal: Confirmación ===== -->
<div class="modal fade" id="modalConfirmarAccion" tabindex="-1" role="dialog" aria-labelledby="tituloConfirmarAccion" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark py-2">
        <h5 class="modal-title" id="tituloConfirmarAccion"><i class="fas fa-exclamation-triangle"></i> Confirmar acción</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="contenidoConfirmarAccion"></div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-light" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-warning" id="btnAceptarConfirmarAccion">Confirmar</button>
      </div>
    </div>
  </div>
</div>

<script>
/* ===== Utilidad global de modales amigables ===== */
(function(){
  if (!window.jQuery) return;

  function setHeader(type){
    var $h = jQuery('#mensajeHeaderApp');
    var $t = jQuery('#tituloMensajeApp');
    if (type === 'error') {
      $h.attr('class', 'modal-header bg-danger text-white py-2');
      $t.html('<i class="fas fa-times-circle"></i> Error');
    } else if (type === 'ok') {
      $h.attr('class', 'modal-header bg-success text-white py-2');
      $t.html('<i class="fas fa-check-circle"></i> Correcto');
    } else {
      $h.attr('class', 'modal-header bg-primary text-white py-2');
      $t.html('<i class="fas fa-info-circle"></i> Mensaje');
    }
  }

  window.AppUI = {
    showMessage: function(message, type){
      setHeader(type || 'info');
      jQuery('#contenidoMensajeApp').text(message || 'Operación completada.');
      jQuery('#modalMensajeApp').modal('show');
    },
    confirm: function(message, onConfirm){
      jQuery('#contenidoConfirmarAccion').text(message || '¿Deseas continuar?');
      jQuery('#btnAceptarConfirmarAccion').off('click').on('click', function(){
        jQuery('#modalConfirmarAccion').modal('hide');
        if (typeof onConfirm === 'function') onConfirm();
      });
      jQuery('#modalConfirmarAccion').modal('show');
    }
  };
})();
</script>

<script>
/* Auto-submit + cascada filtros + debounce búsqueda (ahora incluye Estado/Oficina) */
(function(){
  const form = document.getElementById('frmFiltros');
  if (!form) return;
  const fac = document.getElementById('selFacultad');
  const dep = document.getElementById('selDepartamento');
  const rev = document.getElementById('selRevision');
  const ofi = document.getElementById('selOficina'); // NUEVO
  const cre = document.getElementById('selCreacion');
  const q   = document.getElementById('txtQ');

  function submit(){ form.requestSubmit ? form.requestSubmit() : form.submit(); }

  [fac, dep, rev, ofi, cre].forEach(el => { if (!el) return;
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
      // Si el click vino desde un botón, link, input, select, etc., NO togglear la fila
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
/* Modal Detalle Observación (igual que antes) */
(function () {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-detalle-obs');
    if (!btn) return;
    e.preventDefault(); e.stopPropagation();
    const idpy = btn.getAttribute('data-id_py');
    const tipo = btn.getAttribute('data-tipo');
    if (!idpy || !tipo) return;

    const $contenedor = window.jQuery ? jQuery('#contenidoDetObs') : null;
    if ($contenedor) $contenedor.html('<p class="text-center text-muted my-4">Cargando…</p>');

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
/* ===== DIGITAR CÓDIGO (pool) — compatible con codigo_pool.disponible ===== */
(function(){
  const $ = window.jQuery;
  const PUEDE_QUITAR = <?= $puede_quitar_codigo ? 'true' : 'false' ?>;
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
      if (window.AppUI) AppUI.showMessage('No se pudo determinar período o facultad. Revisa los filtros y vuelve a intentar.', 'error');
      return;
    }
    $('#dg_id_py').val(id_py);
    $('#dg_facultad_id').val(facultadId);
    $('#dg_periodo_id').val(periodoId);
    $('#dg_msg').hide().text('');
    $('#dg_codigo').empty().append($('<option>',{value:'',text:'Cargando...'}));

    var payloadListar = {action:'listar_codigos_disponibles', periodo_id:periodoId, facultad_id:facultadId};
    postJSON(payloadListar)
      .done(function(r){
        $('#dg_codigo').empty();
        if (!r.ok) { $('#dg_codigo').append($('<option>',{value:'',text:r.msg||'Error'})); return; }
        if (!r.items || !r.items.length){
          $('#dg_codigo').append($('<option>',{value:'',text:'No hay códigos disponibles en el pool'}));
        } else {
          $('#dg_codigo').append($('<option>',{value:'',text:'-- Seleccione --'}));
          r.items.forEach(function(it){
            $('#dg_codigo').append($('<option>',{ value: it.id, text: it.codigo }));
          });
        }
      })
      .fail(function(jqXHR, textStatus, errorThrown){
        $('#dg_codigo').empty().append($('<option>',{value:'',text:'No se pudo cargar'}));
        if (window.AppUI) AppUI.showMessage('No se pudo cargar la lista de códigos disponibles. Intenta nuevamente.', 'error');
      });

    $('#modalDigitar').modal('show');
  });

  $('#frmDigitar').on('submit', function(e){
    e.preventDefault();
    const pool_id = $('#dg_codigo').val();
    const id_py   = $('#dg_id_py').val();
    if (!pool_id) { $('#dg_msg').text('Seleccione un código.').show(); return; }
    var payloadAsignar = {action:'asignar_codigo_pool', pool_id:pool_id, id_py:id_py};
    postJSON(payloadAsignar)
      .done(function(r){
        if (!r.ok){
          $('#dg_msg').text(r.msg||'No se pudo asignar.').show();
          if (window.AppUI) AppUI.showMessage(r.msg||'No se pudo asignar el código seleccionado.', 'error');
          return;
        }
        if (rowCtx) {
          rowCtx.find('.btn-digitar-codigo').prop('disabled', true).attr('title','Ya tiene código en este periodo');
          const titleCell = rowCtx.find('td').eq(1);
          const slot = titleCell.find('.codigo-badge-slot');
          slot.html('<span class="badge badge-code">CÓDIGO: '+$('<div>').text(r.codigo||'').html()+'</span>');
          const $gest = rowCtx.find('.btn-gestionar-codigo');
          if (PUEDE_QUITAR) {
            $gest.removeClass('btn-secondary').addClass('btn-danger')
              .attr('data-action', 'quitar')
              .attr('title', '')
              .prop('disabled', false)
              .html('<i class="fas fa-trash-alt"></i> Quitar código');
          } else {
            $gest.removeClass('btn-secondary btn-danger').addClass('btn-dark')
              .attr('data-action', 'none')
              .attr('title', 'Ya tiene código en este periodo')
              .prop('disabled', true)
              .html('<i class="fas fa-lock"></i> Código asignado');
          }
        }
        $('#modalDigitar').modal('hide');
        if (window.AppUI) AppUI.showMessage('Código asignado correctamente.', 'ok');
      })
      .fail(function(jqXHR, textStatus, errorThrown){
        $('#dg_msg').text('Error de conexión o respuesta inválida.').show();
        if (window.AppUI) AppUI.showMessage('No se pudo asignar el código por un problema de conexión. Intenta nuevamente.', 'error');
      });
  });
})();
</script>

<script>
/* ===== GENERAR / QUITAR CÓDIGO ===== */
(function(){
  const $ = window.jQuery;
  const PUEDE_QUITAR = <?= $puede_quitar_codigo ? 'true' : 'false' ?>;
  function postJSON(data){
    return $.ajax({url: window.location.href, method:'POST', data: data, dataType:'json'});
  }
  function escHtml(v){ return $('<div>').text(v == null ? '' : String(v)).html(); }
  function texto(v){ return (v == null || String(v).trim()==='') ? 'No definido' : String(v); }

  let filaCtx = null;

  function actualizarUIConCodigo($row, codigo){
    $row.find('.btn-digitar-codigo').prop('disabled', true).attr('title','Ya tiene código en este periodo');
    const $gest = $row.find('.btn-gestionar-codigo');
    const titleCell = $row.find('td').eq(1);
    const slot = titleCell.find('.codigo-badge-slot');
    slot.html('<span class="badge badge-code">CÓDIGO: '+escHtml(codigo||'')+'</span>');
    if (PUEDE_QUITAR) {
      $gest.removeClass('btn-secondary btn-dark').addClass('btn-danger')
        .attr('data-action','quitar')
        .attr('title','')
        .prop('disabled', false)
        .html('<i class="fas fa-trash-alt"></i> Quitar código');
    } else {
      $gest.removeClass('btn-secondary btn-danger').addClass('btn-dark')
        .attr('data-action','none')
        .attr('title','Ya tiene código en este periodo')
        .prop('disabled', true)
        .html('<i class="fas fa-lock"></i> Código asignado');
    }
  }

  function actualizarUISinCodigo($row){
    $row.find('.btn-digitar-codigo').prop('disabled', false).attr('title','Digitar/seleccionar un código pre-generado');
    const $gest = $row.find('.btn-gestionar-codigo');
    const titleCell = $row.find('td').eq(1);
    const slot = titleCell.find('.codigo-badge-slot');
    slot.html('<span class="badge badge-code-pending">Código pendiente</span>');
    $gest.removeClass('btn-danger btn-dark').addClass('btn-secondary')
      .attr('data-action','generar')
      .attr('title','')
      .prop('disabled', false)
      .html('<i class="fas fa-hashtag"></i> Generar código');
  }

  $(document).on('click', '.btn-gestionar-codigo', function(e){
    e.preventDefault(); e.stopPropagation();
    const $btn = $(this);
    const action = String($btn.attr('data-action') || 'none');
    if (action === 'none') return;
    const id_py      = $btn.data('id_py');
    const facultadId = $btn.data('facultad_id');
    const periodoId  = $btn.data('periodo_id');
    if (!id_py || !facultadId || !periodoId) {
      if (window.AppUI) AppUI.showMessage('Faltan datos de período o facultad para continuar.', 'error');
      return;
    }

    if (action === 'quitar') {
      if (!PUEDE_QUITAR) return;
      if (!window.AppUI) return;
      AppUI.confirm('¿Seguro que deseas quitar el código de este proyecto?', function(){
        $btn.prop('disabled', true);
        var payloadQuitar = {action:'quitar_codigo', id_py:id_py, facultad_id:facultadId, periodo_id:periodoId};
        postJSON(payloadQuitar)
          .done(function(r){
            if (!r.ok){
              AppUI.showMessage(r.msg||'No se pudo quitar el código.', 'error');
              $btn.prop('disabled', false);
              return;
            }
            actualizarUISinCodigo($btn.closest('tr'));
            AppUI.showMessage('Código retirado correctamente. Quedó disponible para reutilización.', 'ok');
          })
          .fail(function(jqXHR, textStatus, errorThrown){
            AppUI.showMessage('No se pudo quitar el código por un problema de conexión. Intenta nuevamente.', 'error');
            $btn.prop('disabled', false);
          });
      });
      return;
    }

    filaCtx = $btn.closest('tr');
    $('#cf_id_py').val(id_py);
    $('#cf_facultad_id').val(facultadId);
    $('#cf_periodo_id').val(periodoId);
    $('#cf_titulo').text(texto($btn.data('titulo')));
    $('#cf_id_txt').text(id_py);
    $('#cf_coord').text(texto($btn.data('coordinador')));
    $('#cf_fac').text(texto($btn.data('facultad')));
    $('#cf_periodo').text(texto($btn.data('periodo')));
    $('#cf_fi').text(texto($btn.data('fecha_inicio')));
    $('#cf_ff').text(texto($btn.data('fecha_fin')));
    $('#cf_codigo').text('Calculando...');
    $('#cf_fuente').text('');
    $('#cf_msg').hide().text('');

    var payloadPreview = {action:'preview_codigo_auto', id_py:id_py, facultad_id:facultadId, periodo_id:periodoId};
    postJSON(payloadPreview)
      .done(function(r){
        if (!r.ok){
          if (window.AppUI) AppUI.showMessage(r.msg||'No se pudo previsualizar el código propuesto.', 'error');
          return;
        }
        $('#cf_codigo').text(r.codigo || 'No disponible');
        $('#cf_fuente').text((r.fuente === 'pool') ? 'Fuente: código liberado (pool disponible).' : 'Fuente: nuevo correlativo.');
        $('#modalConfirmarCodigo').modal('show');
      })
      .fail(function(jqXHR, textStatus, errorThrown){
        if (window.AppUI) AppUI.showMessage('No se pudo previsualizar el código por un problema de conexión. Intenta nuevamente.', 'error');
      });
  });

  $('#frmConfirmarCodigo').on('submit', function(e){
    e.preventDefault();
    const id_py = $('#cf_id_py').val();
    const facultadId = $('#cf_facultad_id').val();
    const periodoId = $('#cf_periodo_id').val();
    const $submit = $(this).find('button[type="submit"]');
    $submit.prop('disabled', true);
    $('#cf_msg').hide().text('');

    var payloadGenerar = {action:'generar_codigo_auto', id_py:id_py, facultad_id:facultadId, periodo_id:periodoId};
    postJSON(payloadGenerar)
      .done(function(r){
        if (!r.ok){
          $('#cf_msg').text(r.msg||'No se pudo generar.').show();
          if (window.AppUI) AppUI.showMessage(r.msg||'No se pudo generar el código.', 'error');
          $submit.prop('disabled', false);
          return;
        }
        if (filaCtx && filaCtx.length) actualizarUIConCodigo(filaCtx, r.codigo || '');
        $('#modalConfirmarCodigo').modal('hide');
        if (window.AppUI) AppUI.showMessage('Código generado y asignado correctamente.', 'ok');
        $submit.prop('disabled', false);
      })
      .fail(function(jqXHR, textStatus, errorThrown){
        $('#cf_msg').text('Error de conexión o respuesta inválida.').show();
        if (window.AppUI) AppUI.showMessage('No se pudo generar el código por un problema de conexión. Intenta nuevamente.', 'error');
        $submit.prop('disabled', false);
      });
  });

})();
</script>
</section>
        </div>
        <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://adminlte.io"> Área  informática - DIRSU</a></p>
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

