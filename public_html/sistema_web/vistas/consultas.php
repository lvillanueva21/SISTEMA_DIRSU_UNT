<?php
// consultas.php
include('../componentes/configSesion.php'); // Valida sesion activa
include('../componentes/db.php'); // Incluye la conexion a la base de datos
require_once('../includes/evt_mantenimiento.php');

if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

function consultas_exit_403($message)
{
  if (!headers_sent()) {
    http_response_code(403);
    header('Content-Type: text/html; charset=UTF-8');
  }
  echo '<!doctype html><html lang=\"es\"><head><meta charset=\"utf-8\"><meta name=\"viewport\" content=\"width=device-width, initial-scale=1\">';
  echo '<title>Acceso restringido</title>';
  echo '<style>body{font-family:Arial,sans-serif;background:#f4f6f9;padding:32px;} .card{max-width:780px;margin:0 auto;background:#fff;border-radius:8px;padding:22px;border:1px solid #ddd;} h2{margin-top:0;color:#b22;} p{line-height:1.5;}</style>';
  echo '</head><body><div class=\"card\"><h2>Acceso restringido</h2><p>' . htmlspecialchars((string)$message, ENT_QUOTES, 'UTF-8') . '</p></div></body></html>';
  exit;
}

if (!isset($_SESSION['usuario']) || !isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
  consultas_exit_403('Solo administradores autorizados pueden ingresar al Gestor DB.');
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
  consultas_exit_403('No se pudo conectar a la base de datos.');
}
@mysqli_set_charset($conexion, 'utf8mb4');

$evtDbAccessEnabled = false;
$evtDbAccessResult = mysqli_query($conexion, "SELECT estado FROM evt_eventos WHERE codigo='acceso_gestor_db' LIMIT 1");
if ($evtDbAccessResult && $evtDbAccessRow = mysqli_fetch_assoc($evtDbAccessResult)) {
  $evtDbAccessEnabled = ((int)$evtDbAccessRow['estado'] === 1);
}
if (!$evtDbAccessEnabled) {
  consultas_exit_403('El acceso al Gestor DB esta deshabilitado desde Control de eventos.');
}

$consultasCsrf = evt_mto_get_csrf_token('consultas_csrf');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $postedCsrf = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
  if (!evt_mto_validate_csrf_token($postedCsrf, 'consultas_csrf')) {
    consultas_exit_403('Token CSRF invalido. Recarga la pagina e intenta nuevamente.');
  }
}

function toSqlValue($conexion, $value) {
  if (is_null($value)) {
    return "NULL";
  }
  return "'" . mysqli_real_escape_string($conexion, (string)$value) . "'";
}

function downloadDatabaseBackup($conexion, $databaseName, $backupType = 'full') {
  $validTypes = ['structure', 'data', 'full'];
  if (!in_array($backupType, $validTypes, true)) {
    $backupType = 'full';
  }

  $timestamp = date('Ymd_His');
  $safeDb = preg_replace('/[^a-zA-Z0-9_]/', '_', $databaseName);
  $fileName = 'backup_' . $safeDb . '_' . $timestamp . '.sql';

  header('Content-Type: application/sql; charset=utf-8');
  header('Content-Disposition: attachment; filename="' . $fileName . '"');
  header('Pragma: no-cache');
  header('Expires: 0');

  echo "-- Backup SQL generado desde consultas.php\n";
  echo "-- Base de datos: " . $databaseName . "\n";
  echo "-- Fecha: " . date('Y-m-d H:i:s') . "\n";
  echo "-- Tipo: " . strtoupper($backupType) . "\n\n";
  echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
  echo "START TRANSACTION;\n";
  echo "SET time_zone = \"+00:00\";\n";
  echo "SET FOREIGN_KEY_CHECKS=0;\n\n";

  $resultTables = mysqli_query($conexion, "SHOW FULL TABLES");
  if (!$resultTables) {
    echo "-- Error al obtener tablas: " . mysqli_error($conexion) . "\n";
    echo "SET FOREIGN_KEY_CHECKS=1;\nCOMMIT;\n";
    exit;
  }

  $tables = [];
  while ($row = mysqli_fetch_row($resultTables)) {
    $tables[] = [
      'name' => $row[0],
      'type' => strtoupper((string)($row[1] ?? 'BASE TABLE')),
    ];
  }

  foreach ($tables as $tableItem) {
    $table = $tableItem['name'];
    $isView = ($tableItem['type'] === 'VIEW');

    if ($backupType === 'structure' || $backupType === 'full') {
      if ($isView) {
        $resultCreate = mysqli_query($conexion, "SHOW CREATE VIEW `$table`");
        if ($resultCreate && $rowCreate = mysqli_fetch_assoc($resultCreate)) {
          $createStatement = isset($rowCreate['Create View']) ? (string)$rowCreate['Create View'] : '';
          // Eliminar DEFINER mejora portabilidad entre servidores.
          $createStatement = preg_replace('/DEFINER=`[^`]+`@`[^`]+`\s*/i', '', $createStatement);

          echo "-- --------------------------------------------------------\n";
          echo "-- Estructura de vista `$table`\n";
          echo "-- --------------------------------------------------------\n";
          echo "DROP VIEW IF EXISTS `$table`;\n";
          echo "DROP TABLE IF EXISTS `$table`;\n";
          echo $createStatement . ";\n\n";
        } else {
          echo "-- Error al obtener CREATE VIEW de `$table`: " . mysqli_error($conexion) . "\n\n";
        }
      } else {
        $resultCreate = mysqli_query($conexion, "SHOW CREATE TABLE `$table`");
        if ($resultCreate && $rowCreate = mysqli_fetch_assoc($resultCreate)) {
          $createStatement = $rowCreate['Create Table'];
          echo "-- --------------------------------------------------------\n";
          echo "-- Estructura de tabla `$table`\n";
          echo "-- --------------------------------------------------------\n";
          echo "DROP VIEW IF EXISTS `$table`;\n";
          echo "DROP TABLE IF EXISTS `$table`;\n";
          echo $createStatement . ";\n\n";
        } else {
          echo "-- Error al obtener CREATE TABLE de `$table`: " . mysqli_error($conexion) . "\n\n";
        }
      }
    }

    if ($backupType === 'data' || $backupType === 'full') {
      if ($isView) {
        echo "-- Vista `$table` omitida en seccion de datos para compatibilidad.\n\n";
        continue;
      }

      $resultData = mysqli_query($conexion, "SELECT * FROM `$table`");
      if ($resultData && mysqli_num_rows($resultData) > 0) {
        echo "-- Datos de tabla `$table`\n";
        while ($rowData = mysqli_fetch_assoc($resultData)) {
          $columns = array_map(function ($col) {
            return "`" . $col . "`";
          }, array_keys($rowData));

          $values = array_map(function ($val) use ($conexion) {
            return toSqlValue($conexion, $val);
          }, array_values($rowData));

          echo "INSERT INTO `$table` (" . implode(', ', $columns) . ") VALUES (" . implode(', ', $values) . ");\n";
        }
        echo "\n";
      } else {
        echo "-- Tabla `$table` sin registros o no accesible.\n\n";
      }
    }
  }

  echo "SET FOREIGN_KEY_CHECKS=1;\n";
  echo "COMMIT;\n";
  exit;
}

function fetchSingleValue($conexion, $sql, $defaultValue = 'N/A') {
  $result = mysqli_query($conexion, $sql);
  if (!$result) {
    return $defaultValue;
  }
  $row = mysqli_fetch_row($result);
  mysqli_free_result($result);
  return isset($row[0]) ? (string)$row[0] : $defaultValue;
}

function fetchRegexLikeCapability($conexion) {
  $version = fetchSingleValue($conexion, "SELECT VERSION()", '');
  if ($version !== '' && stripos($version, 'mariadb') !== false) {
    return 'No';
  }

  $result = mysqli_query($conexion, "SELECT REGEXP_LIKE('123', '^[0-9]+$') AS supports_regex_like");
  if (!$result) {
    return 'No disponible';
  }

  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);

  if (!isset($row['supports_regex_like'])) {
    return 'No disponible';
  }

  return ((string)$row['supports_regex_like'] === '1') ? 'Si' : 'No';
}

function fetchVariableValue($conexion, $variableName, $defaultValue = 'N/A') {
  $safeName = mysqli_real_escape_string($conexion, $variableName);
  $result = mysqli_query($conexion, "SHOW VARIABLES LIKE '$safeName'");
  if (!$result) {
    return $defaultValue;
  }
  $row = mysqli_fetch_assoc($result);
  mysqli_free_result($result);
  return (isset($row['Value']) && $row['Value'] !== '') ? (string)$row['Value'] : $defaultValue;
}

function formatBytesHuman($bytes) {
  if (!is_numeric($bytes) || (float)$bytes <= 0) {
    return "0 B";
  }
  $units = ['B', 'KB', 'MB', 'GB', 'TB'];
  $size = (float)$bytes;
  $pow = (int)floor(log($size, 1024));
  $pow = min($pow, count($units) - 1);
  $size /= pow(1024, $pow);
  return number_format($size, 2) . " " . $units[$pow];
}

if (
  $_SERVER['REQUEST_METHOD'] === 'POST' &&
  isset($_POST['backup_action']) &&
  $_POST['backup_action'] === 'download_backup'
) {
  $backupType = isset($_POST['backup_type']) ? trim($_POST['backup_type']) : 'full';
  downloadDatabaseBackup($conexion, $baseDatos, $backupType);
}

$consulta_all = '';
$resultado_query = null;
$error_query = '';
$mensaje_query = '';
$tablaSeleccionada = '';
$estructura = null;
$datosTabla = null;
$errorTabla = '';

// Si se envía una consulta desde el textarea, se ignora cualquier tabla seleccionada.
if (isset($_POST['consulta_all'])) {
  $consulta_all = trim($_POST['consulta_all']);
  $tablaSeleccionada = '';
}

// Obtener la lista de tablas de la base de datos 'rsudb'
$tablas = [];
$query_tablas = "SHOW TABLES";
$result_tablas = mysqli_query($conexion, $query_tablas);
if ($result_tablas) {
  while ($row = mysqli_fetch_array($result_tablas)) {
    $tablas[] = $row[0];
  }
} else {
  $error_tablas = "Error al obtener las tablas: " . mysqli_error($conexion);
}

// Preparamos un arreglo para almacenar:
// - El código de creación (SHOW CREATE TABLE)
// - La lista de columnas (DESCRIBE) para cada tabla
$tableInfo = [];

// Para cada tabla, obtenemos su CREATE y sus columnas (para la inserción)
foreach ($tablas as $t) {
  // SHOW CREATE TABLE
  $resCreate = mysqli_query($conexion, "SHOW CREATE TABLE `$t`");
  if ($resCreate && $rowCreate = mysqli_fetch_assoc($resCreate)) {
    $tableInfo[$t]['create'] = $rowCreate['Create Table'];
  } else {
    $tableInfo[$t]['create'] = "-- Error al obtener CREATE TABLE de `$t` --";
  }

  // DESCRIBE para obtener las columnas
  $resDesc = mysqli_query($conexion, "DESCRIBE `$t`");
  $cols = [];
  if ($resDesc) {
    while ($drow = mysqli_fetch_assoc($resDesc)) {
      $cols[] = $drow['Field'];
    }
  }
  $tableInfo[$t]['columns'] = $cols;
}

// Definir opciones para cantidad de registros a mostrar
$limit_options = ['5','10','25','50','100','all'];
$limit = (isset($_GET['limit']) && in_array($_GET['limit'], $limit_options)) ? $_GET['limit'] : '5';

// Si no se envió consulta general, se revisa si se seleccionó una tabla
if (!$consulta_all && isset($_GET['tabla'])) {
  $tablaSeleccionada = trim($_GET['tabla']);
  if (in_array($tablaSeleccionada, $tablas)) {
    $query_describe = "DESCRIBE `$tablaSeleccionada`";
    $estructura = mysqli_query($conexion, $query_describe);
    if (!$estructura) {
      $errorTabla = "Error al obtener la estructura: " . mysqli_error($conexion);
    }
    $query_select = ($limit !== 'all')
      ? "SELECT * FROM `$tablaSeleccionada` LIMIT 0, " . intval($limit)
      : "SELECT * FROM `$tablaSeleccionada`";
    $datosTabla = mysqli_query($conexion, $query_select);
    if (!$datosTabla) {
      $errorTabla = "Error al obtener los registros: " . mysqli_error($conexion);
    }
  } else {
    $errorTabla = "La tabla seleccionada no existe.";
  }
}

// Si se envió una consulta general desde el textarea
if ($consulta_all) {
  $resultado_query = mysqli_query($conexion, $consulta_all);
  if (!$resultado_query) {
    $error_query = "<strong>Error en la consulta:</strong> " . mysqli_error($conexion);
  } else {
    if (is_object($resultado_query) && mysqli_num_rows($resultado_query) > 0) {
      // Se mostrarán resultados en tabla
    } else {
      $mensaje_query = "<strong>Éxito:</strong> Consulta ejecutada correctamente. ";
      $filas_afectadas = mysqli_affected_rows($conexion);
      $mensaje_query .= ($filas_afectadas >= 0) ? "Filas afectadas: " . $filas_afectadas . "." : "";
    }
  }
}

// Para el dashboard, se obtiene la cantidad de registros por tabla
$tableNames = [];
$tableCounts = [];
if (!$tablaSeleccionada && !$consulta_all && count($tablas) > 0) {
  foreach ($tablas as $t) {
    $tableNames[] = $t;
    $queryCount = "SELECT COUNT(*) as cnt FROM `$t`";
    $resultCount = mysqli_query($conexion, $queryCount);
    if ($resultCount && $row = mysqli_fetch_assoc($resultCount)) {
      $tableCounts[] = (int)$row['cnt'];
    } else {
      $tableCounts[] = 0;
    }
  }
}

$diagnosticTables = [];
$totalRowsEstimate = 0;
$totalSizeBytes = 0;

$diagnosticMeta = [
  'Fecha diagnostico' => date('Y-m-d H:i:s'),
  'Base de datos activa' => $baseDatos,
  'Host DB (mysqli)' => mysqli_get_host_info($conexion),
  'Version servidor DB' => mysqli_get_server_info($conexion),
  'Version cliente mysqli' => mysqli_get_client_info(),
  'Protocolo mysqli' => (string)mysqli_get_proto_info($conexion),
  'Charset conexion' => mysqli_character_set_name($conexion),
  'Version PHP' => PHP_VERSION,
  'SAPI PHP' => php_sapi_name(),
];

$engineCounts = [];
$resultDiagTables = mysqli_query(
  $conexion,
  "SELECT TABLE_NAME, ENGINE, TABLE_COLLATION, TABLE_ROWS, DATA_LENGTH, INDEX_LENGTH, CREATE_TIME, UPDATE_TIME
   FROM information_schema.TABLES
   WHERE TABLE_SCHEMA = '" . mysqli_real_escape_string($conexion, $baseDatos) . "'
   ORDER BY TABLE_NAME"
);

if ($resultDiagTables) {
  while ($rowDiag = mysqli_fetch_assoc($resultDiagTables)) {
    $rowsEstimate = isset($rowDiag['TABLE_ROWS']) ? (int)$rowDiag['TABLE_ROWS'] : 0;
    $dataLength = isset($rowDiag['DATA_LENGTH']) ? (int)$rowDiag['DATA_LENGTH'] : 0;
    $indexLength = isset($rowDiag['INDEX_LENGTH']) ? (int)$rowDiag['INDEX_LENGTH'] : 0;
    $tableSize = $dataLength + $indexLength;
    $engineName = $rowDiag['ENGINE'] ? $rowDiag['ENGINE'] : 'N/A';

    if (!isset($engineCounts[$engineName])) {
      $engineCounts[$engineName] = 0;
    }
    $engineCounts[$engineName]++;

    $totalRowsEstimate += $rowsEstimate;
    $totalSizeBytes += $tableSize;

    $diagnosticTables[] = [
      'name' => $rowDiag['TABLE_NAME'],
      'engine' => $engineName,
      'collation' => $rowDiag['TABLE_COLLATION'] ? $rowDiag['TABLE_COLLATION'] : 'N/A',
      'rows' => $rowsEstimate,
      'size_bytes' => $tableSize,
      'size_human' => formatBytesHuman($tableSize),
      'created_at' => $rowDiag['CREATE_TIME'] ? $rowDiag['CREATE_TIME'] : 'N/A',
      'updated_at' => $rowDiag['UPDATE_TIME'] ? $rowDiag['UPDATE_TIME'] : 'N/A',
    ];
  }
  mysqli_free_result($resultDiagTables);
}

$diagnosticServerVars = [
  'version_comment' => fetchVariableValue($conexion, 'version_comment'),
  'version_compile_os' => fetchVariableValue($conexion, 'version_compile_os'),
  'version_compile_machine' => fetchVariableValue($conexion, 'version_compile_machine'),
  'sql_mode' => fetchVariableValue($conexion, 'sql_mode'),
  'time_zone' => fetchVariableValue($conexion, 'time_zone'),
  'system_time_zone' => fetchVariableValue($conexion, 'system_time_zone'),
  'lower_case_table_names' => fetchVariableValue($conexion, 'lower_case_table_names'),
  'character_set_server' => fetchVariableValue($conexion, 'character_set_server'),
  'collation_server' => fetchVariableValue($conexion, 'collation_server'),
  'max_allowed_packet' => fetchVariableValue($conexion, 'max_allowed_packet'),
  'innodb_version' => fetchVariableValue($conexion, 'innodb_version'),
];

$diagnosticCounts = [
  'Numero de tablas' => (string)count($tablas),
  'Filas estimadas (information_schema)' => number_format($totalRowsEstimate),
  'Tamano total estimado' => formatBytesHuman($totalSizeBytes) . " (" . number_format($totalSizeBytes) . " bytes)",
  'Tipos de engine detectados' => implode(', ', array_map(function ($engine, $count) {
    return $engine . " (" . $count . ")";
  }, array_keys($engineCounts), array_values($engineCounts))),
  'Funciones regex DB' => "REGEXP: " . fetchSingleValue($conexion, "SELECT 'abc123' REGEXP '[0-9]'", 'N/A')
    . " | REGEXP_LIKE: " . fetchRegexLikeCapability($conexion),
];

if ($diagnosticCounts['Tipos de engine detectados'] === '') {
  $diagnosticCounts['Tipos de engine detectados'] = 'N/A';
}

$diagnosticPhpEnv = [
  'PHP_INT_SIZE' => (string)PHP_INT_SIZE,
  'memory_limit' => ini_get('memory_limit'),
  'max_execution_time' => ini_get('max_execution_time'),
  'upload_max_filesize' => ini_get('upload_max_filesize'),
  'post_max_size' => ini_get('post_max_size'),
  'mysqli extension' => extension_loaded('mysqli') ? 'si' : 'no',
  'pdo_mysql extension' => extension_loaded('pdo_mysql') ? 'si' : 'no',
  'mbstring extension' => extension_loaded('mbstring') ? 'si' : 'no',
];

$diagnosticTextLines = [];
$diagnosticTextLines[] = "===== RESUMEN DE COMPATIBILIDAD MIGRACION =====";
$diagnosticTextLines[] = "Fecha: " . $diagnosticMeta['Fecha diagnostico'];
$diagnosticTextLines[] = "Base de datos activa: " . $diagnosticMeta['Base de datos activa'];
$diagnosticTextLines[] = "Version servidor DB: " . $diagnosticMeta['Version servidor DB'];
$diagnosticTextLines[] = "Version PHP: " . $diagnosticMeta['Version PHP'];
$diagnosticTextLines[] = "Numero de tablas: " . $diagnosticCounts['Numero de tablas'];
$diagnosticTextLines[] = "Filas estimadas: " . $diagnosticCounts['Filas estimadas (information_schema)'];
$diagnosticTextLines[] = "Tamano total estimado: " . $diagnosticCounts['Tamano total estimado'];
$diagnosticTextLines[] = "Engines detectados: " . $diagnosticCounts['Tipos de engine detectados'];
$diagnosticTextLines[] = "Capacidades regex DB: " . $diagnosticCounts['Funciones regex DB'];
$diagnosticTextLines[] = "";
$diagnosticTextLines[] = "--- VARIABLES DEL SERVIDOR DB ---";
foreach ($diagnosticServerVars as $key => $value) {
  $diagnosticTextLines[] = $key . ": " . $value;
}
$diagnosticTextLines[] = "";
$diagnosticTextLines[] = "--- ENTORNO PHP ---";
foreach ($diagnosticPhpEnv as $key => $value) {
  $diagnosticTextLines[] = $key . ": " . $value;
}
$diagnosticTextLines[] = "";
$diagnosticTextLines[] = "--- TABLAS ---";
foreach ($diagnosticTables as $tbl) {
  $diagnosticTextLines[] = $tbl['name']
    . " | engine=" . $tbl['engine']
    . " | collation=" . $tbl['collation']
    . " | rows=" . number_format($tbl['rows'])
    . " | size=" . $tbl['size_human']
    . " | created=" . $tbl['created_at']
    . " | updated=" . $tbl['updated_at'];
}
$diagnosticText = implode("\n", $diagnosticTextLines);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Interfaz de Consultas MySQL</title>
  <!-- Tema Minty de Bootswatch -->
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.0/dist/minty/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.3/font/bootstrap-icons.css">
  <!-- jQuery (para DataTables y Chart.js) -->
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <!-- DataTables CSS -->
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/1.13.4/css/jquery.dataTables.min.css"/>
  <!-- DataTables JS -->
  <script type="text/javascript" src="https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js"></script>
  <!-- Chart.js -->
  <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
  <style>
    .records-height { height: 70vh; overflow-y: auto; }
    .structure-height { height: 30vh; overflow-y: auto; }
    .dashboard-panel { height: 80vh; }
    .sidebar-consulta { padding: 1rem; border-bottom: 1px solid #ddd; }
    .default-btn { width: 40px; height: 40px; border-radius: 50%; padding: 0; margin-right: 5px; margin-bottom: 5px; }
    .list-group-item.sidebar-table { background-color: #78c2ad !important; color: #ffffff !important; cursor: pointer; }
    .list-group-item.sidebar-table a { color: #ffffff !important; text-decoration: none; display: block; width: 100%; height: 100%; }
    .list-group-item.sidebar-table.active-table { background-color: #4da699 !important; }
    table.dataTable thead th { background-color: #78c2ad !important; color: #ffffff !important; font-weight: bold; }
    tr.selected { background-color: #b3e5fc !important; }
    .sidebar-tables { max-height: calc(100vh - 150px); overflow-y: auto; }
    .btn-minty { background-color: #78c2ad; border-color: #78c2ad; color: #ffffff; }
    .btn-minty:hover, .btn-minty:focus, .btn-minty:active { background-color: #4da699; border-color: #4da699; color: #ffffff; }
    .dataTables_filter label { font-weight: bold; color: #333; }
    .dataTables_filter input { border: 1px solid #78c2ad; border-radius: 4px; padding: 4px; }
    .btn-home { background-color: #78c2ad; border-color: #78c2ad; color: #ffffff; }
    .btn-home:hover, .btn-home:focus, .btn-home:active { background-color: #4da699; border-color: #4da699; color: #ffffff; }
    #chartRegistro { width: 100% !important; height: 400px !important; }
    .dropdown-toggle::after { margin-left: 0.3rem; }
    .list-group-item.sidebar-table .dropdown-menu .dropdown-item {
  color: #000 !important;
}
    .backup-fab {
      position: fixed;
      right: 20px;
      bottom: 20px;
      z-index: 1080;
      border-radius: 999px;
      box-shadow: 0 0.35rem 0.9rem rgba(0, 0, 0, 0.2);
      padding: 0.65rem 1rem;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }
    .info-fab {
      position: fixed;
      right: 20px;
      bottom: 76px;
      z-index: 1080;
      border-radius: 999px;
      box-shadow: 0 0.35rem 0.9rem rgba(0, 0, 0, 0.2);
      padding: 0.65rem 1rem;
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
    }
    #diagnosticText {
      font-family: "Courier New", Courier, monospace;
      font-size: 0.82rem;
      white-space: pre;
    }
  </style>
</head>
<body>
<div class="container-fluid">
  <div class="row">
    <!-- Barra lateral -->
    <div class="col-md-3 bg-light border-end" style="min-height:100vh;">
      <div class="sidebar-consulta">
        <!-- Botones de consultas rápidas -->
        <div class="mb-2">
          <button type="button" class="btn btn-outline-primary default-btn" onclick="setQuery('SELECT * FROM tabla_ejemplo WHERE condicion;')">
            <i class="bi bi-search"></i>
          </button>
          <button type="button" class="btn btn-outline-primary default-btn" onclick="setQuery('UPDATE tabla_ejemplo SET columna=valor WHERE condicion;')">
            <i class="bi bi-pencil"></i>
          </button>
          <button type="button" class="btn btn-outline-primary default-btn" onclick="setQuery('INSERT INTO tabla_ejemplo (columna1, columna2) VALUES (valor1, valor2);')">
            <i class="bi bi-plus-lg"></i>
          </button>
          <button type="button" class="btn btn-outline-primary default-btn" onclick="setQuery('DELETE FROM tabla_ejemplo WHERE condicion;')">
            <i class="bi bi-trash"></i>
          </button>
        </div>
        <!-- Formulario para la consulta general -->
        <form action="consultas.php" method="POST" id="form-consulta">
          <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($consultasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
          <div class="mb-2">
            <label for="consulta_all" class="form-label">Consulta SQL:</label>
            <textarea name="consulta_all" id="consulta_all" class="form-control" rows="3" placeholder="Escribe tu consulta SQL..."><?php echo htmlspecialchars($consulta_all); ?></textarea>
          </div>
          <button type="submit" class="btn btn-minty w-100">
            <i class="bi bi-play-circle"></i> Ejecutar Consulta
          </button>
        </form>
      </div>
      <!-- Listado de tablas -->
      <div class="p-3">
        <div class="d-flex align-items-center mb-2">
          <h4 class="mb-0">Tablas</h4>
          <form method="post" id="form-refresh" class="ms-2">
            <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($consultasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
            <button type="submit" class="btn btn-outline-secondary btn-sm" title="Actualizar">
              <i class="bi bi-arrow-clockwise"></i>
            </button>
          </form>
        </div>
        <ul class="list-group list-group-flush sidebar-tables">
          <?php if(isset($error_tablas)): ?>
            <li class="list-group-item text-danger"><?php echo $error_tablas; ?></li>
          <?php else: ?>
            <?php foreach($tablas as $tabla): ?>
              <li class="list-group-item sidebar-table <?php echo ($tablaSeleccionada == $tabla) ? 'active-table' : ''; ?>">
                <!-- Enlace a la tabla y menú de 3 puntos -->
                <div class="d-flex justify-content-between align-items-center">
                  <!-- Enlace que lleva a ver la tabla -->
                  <a class="flex-grow-1" style="text-decoration:none;color:#fff;" href="consultas.php?tabla=<?php echo urlencode($tabla); ?>">
                    <?php echo htmlspecialchars($tabla); ?>
                  </a>
                  <!-- Botón con 3 puntos + Menú desplegable para consultas -->
                  <div class="btn-group dropstart">
                    <button class="btn btn-link p-0" data-bs-toggle="dropdown" aria-expanded="false" style="color:#fff;">
                      <i class="bi bi-three-dots"></i>
                    </button>
                    <ul class="dropdown-menu">
                      <!-- Texto negro para las opciones -->
                      <li>
                        <a class="dropdown-item text-dark" href="#"
                           onclick="setQuery('SELECT * FROM <?php echo $tabla; ?> WHERE condicion;')">
                           Consulta SELECT
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item text-dark" href="#"
                           onclick="setQuery('UPDATE <?php echo $tabla; ?> SET columna=valor WHERE condicion;')">
                           Consulta UPDATE
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item text-dark" href="#"
                           onclick="setInsertQuery('<?php echo $tabla; ?>')">
                           Consulta INSERT
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item text-dark" href="#"
                           onclick="setQuery('DELETE FROM <?php echo $tabla; ?> WHERE condicion;')">
                           Consulta DELETE
                        </a>
                      </li>
                      <li>
                        <a class="dropdown-item text-dark" href="#"
                           onclick="verCodigoTabla('<?php echo $tabla; ?>')">
                           Ver Tabla
                        </a>
                      </li>
                    </ul>
                  </div>
                </div>
              </li>
            <?php endforeach; ?>
          <?php endif; ?>
        </ul>
      </div>
    </div>
    <!-- Área principal -->
    <div class="col-md-9 p-4">
      <?php if($tablaSeleccionada || $consulta_all): ?>
        <!-- Botón para volver al Dashboard -->
        <div class="mb-3">
          <a href="consultas.php" class="btn btn-home">
            <i class="bi bi-house-door-fill"></i>
          </a>
        </div>
      <?php endif; ?>
      
      <!-- Si se ha seleccionado una tabla -->
      <?php if ($tablaSeleccionada): ?>
        <h3>Tabla: <?php echo htmlspecialchars($tablaSeleccionada); ?></h3>
        <?php if ($errorTabla): ?>
          <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?php echo $errorTabla; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
          </div>
        <?php else: ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h5 class="mb-0">Registros de la tabla: <?php echo htmlspecialchars($tablaSeleccionada); ?></h5>
            <div>
              <span>Mostrar registros:</span>
              <?php foreach ($limit_options as $opcion):
                $url = "consultas.php?tabla=" . urlencode($tablaSeleccionada) . "&limit=" . urlencode($opcion);
                $label = ($opcion === 'all') ? 'Todas' : $opcion; ?>
                <a href="<?php echo $url; ?>" 
                   class="btn btn-outline-primary btn-sm <?php echo ($limit === $opcion) ? 'active' : ''; ?>">
                  <?php echo $label; ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
          <!-- Mostrar registros -->
          <?php if (is_object($datosTabla) && mysqli_num_rows($datosTabla) > 0): ?>
            <div class="table-responsive records-height" id="resultados">
              <table id="datatable1" class="table table-bordered table-striped datatable">
                <thead>
                  <tr>
                    <?php 
                    $campos = mysqli_fetch_fields($datosTabla);
                    foreach ($campos as $campo) {
                      echo "<th>" . htmlspecialchars($campo->name) . "</th>";
                    }
                    mysqli_data_seek($datosTabla, 0);
                    ?>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($fila = mysqli_fetch_assoc($datosTabla)): ?>
                    <tr>
                      <?php foreach ($fila as $valor): ?>
                        <td><?php echo htmlspecialchars($valor); ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
              <strong>Info:</strong> La tabla no tiene registros.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          <!-- Estructura de la tabla -->
          <h5>Estructura de la tabla</h5>
          <div class="table-responsive structure-height mb-4">
            <table class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th>
                </tr>
              </thead>
              <tbody>
                <?php while($row = mysqli_fetch_assoc($estructura)): ?>
                  <tr>
                    <td><?php echo htmlspecialchars($row['Field']); ?></td>
                    <td><?php echo htmlspecialchars($row['Type']); ?></td>
                    <td><?php echo htmlspecialchars($row['Null']); ?></td>
                    <td><?php echo htmlspecialchars($row['Key']); ?></td>
                    <td><?php echo htmlspecialchars($row['Default']); ?></td>
                    <td><?php echo htmlspecialchars($row['Extra']); ?></td>
                  </tr>
                <?php endwhile; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      
      <!-- Si es una consulta general desde el textarea -->
      <?php elseif ($consulta_all): ?>
        <div class="mb-4"><h3>Resultado de la Consulta</h3></div>
        <div id="resultados">
          <?php if ($error_query): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
              <?php echo $error_query; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php elseif ($mensaje_query): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
              <?php echo $mensaje_query; ?>
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
          <?php if (is_object($resultado_query) && mysqli_num_rows($resultado_query) > 0): ?>
            <div class="table-responsive">
              <table id="datatable2" class="table table-bordered table-striped datatable">
                <thead>
                  <tr>
                    <?php
                    $campos = mysqli_fetch_fields($resultado_query);
                    foreach ($campos as $campo) {
                      echo "<th>" . htmlspecialchars($campo->name) . "</th>";
                    }
                    mysqli_data_seek($resultado_query, 0);
                    ?>
                  </tr>
                </thead>
                <tbody>
                  <?php while ($fila = mysqli_fetch_assoc($resultado_query)): ?>
                    <tr>
                      <?php foreach ($fila as $valor): ?>
                        <td><?php echo htmlspecialchars($valor); ?></td>
                      <?php endforeach; ?>
                    </tr>
                  <?php endwhile; ?>
                </tbody>
              </table>
            </div>
          <?php elseif (is_object($resultado_query) && mysqli_num_rows($resultado_query) == 0): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
              <strong>Info:</strong> La consulta no devolvió resultados.
              <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
          <?php endif; ?>
        </div>
      
      <!-- Si no se ha seleccionado tabla ni enviado consulta => Dashboard -->
      <?php else: ?>
        <div class="dashboard-panel text-center">
          <h2>Dashboard</h2>
          <p>Resumen de la base de datos</p>
          <canvas id="chartRegistro"></canvas>
        </div>
        <div class="text-center mt-3">
          <a href="consultas.php" class="btn btn-home">
            <i class="bi bi-house-door-fill"></i>
          </a>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>

<button
  type="button"
  class="btn btn-info info-fab"
  data-bs-toggle="modal"
  data-bs-target="#infoModal"
  title="Ver diagnostico de entorno"
>
  <i class="bi bi-info-circle"></i> Info
</button>

<button
  type="button"
  class="btn btn-warning backup-fab"
  data-bs-toggle="modal"
  data-bs-target="#backupModal"
  title="Generar backup SQL"
>
  <i class="bi bi-download"></i> Backup
</button>

<div class="modal fade" id="backupModal" tabindex="-1" aria-labelledby="backupModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" action="consultas.php">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($consultasCsrf, ENT_QUOTES, 'UTF-8'); ?>">
        <input type="hidden" name="backup_action" value="download_backup">
        <div class="modal-header">
          <h5 class="modal-title" id="backupModalLabel">Generar copia de seguridad</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2">Selecciona el tipo de backup SQL para la base de datos actual:</p>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="backup_type" id="backupFull" value="full" checked>
            <label class="form-check-label" for="backupFull">
              General (estructura + registros)
            </label>
          </div>

          <div class="form-check mb-2">
            <input class="form-check-input" type="radio" name="backup_type" id="backupStructure" value="structure">
            <label class="form-check-label" for="backupStructure">
              Solo tablas (estructura)
            </label>
          </div>

          <div class="form-check">
            <input class="form-check-input" type="radio" name="backup_type" id="backupData" value="data">
            <label class="form-check-label" for="backupData">
              Solo registros (datos)
            </label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-minty">
            <i class="bi bi-download"></i> Descargar .sql
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="infoModalLabel">Diagnostico de compatibilidad (DB + PHP)</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="alert alert-info py-2 mb-3">
          Este resumen ayuda a comparar este servidor contra el hosting de migracion para detectar incompatibilidades.
        </div>

        <div class="row g-3 mb-3">
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">Datos base del entorno</div>
              <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                  <tbody>
                    <?php foreach ($diagnosticMeta as $label => $value): ?>
                      <tr>
                        <th style="width:45%;"><?php echo htmlspecialchars($label); ?></th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                      </tr>
                    <?php endforeach; ?>
                    <?php foreach ($diagnosticCounts as $label => $value): ?>
                      <tr>
                        <th><?php echo htmlspecialchars($label); ?></th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          <div class="col-md-6">
            <div class="card h-100">
              <div class="card-header">Variables del servidor DB</div>
              <div class="card-body p-0">
                <table class="table table-sm table-striped mb-0">
                  <tbody>
                    <?php foreach ($diagnosticServerVars as $key => $value): ?>
                      <tr>
                        <th style="width:45%;"><?php echo htmlspecialchars($key); ?></th>
                        <td><?php echo htmlspecialchars($value); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">Entorno PHP</div>
          <div class="card-body p-0">
            <table class="table table-sm table-striped mb-0">
              <tbody>
                <?php foreach ($diagnosticPhpEnv as $key => $value): ?>
                  <tr>
                    <th style="width:30%;"><?php echo htmlspecialchars($key); ?></th>
                    <td><?php echo htmlspecialchars($value); ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>

        <div class="card mb-3">
          <div class="card-header">Tablas y motores</div>
          <div class="card-body p-0">
            <div class="table-responsive" style="max-height: 280px;">
              <table class="table table-sm table-bordered table-striped mb-0">
                <thead>
                  <tr>
                    <th>Tabla</th>
                    <th>Engine</th>
                    <th>Collation</th>
                    <th>Rows (est.)</th>
                    <th>Tamano</th>
                    <th>Creada</th>
                    <th>Actualizada</th>
                  </tr>
                </thead>
                <tbody>
                  <?php if (count($diagnosticTables) > 0): ?>
                    <?php foreach ($diagnosticTables as $tbl): ?>
                      <tr>
                        <td><?php echo htmlspecialchars($tbl['name']); ?></td>
                        <td><?php echo htmlspecialchars($tbl['engine']); ?></td>
                        <td><?php echo htmlspecialchars($tbl['collation']); ?></td>
                        <td><?php echo number_format($tbl['rows']); ?></td>
                        <td><?php echo htmlspecialchars($tbl['size_human']); ?></td>
                        <td><?php echo htmlspecialchars($tbl['created_at']); ?></td>
                        <td><?php echo htmlspecialchars($tbl['updated_at']); ?></td>
                      </tr>
                    <?php endforeach; ?>
                  <?php else: ?>
                    <tr>
                      <td colspan="7" class="text-center text-muted">No se pudo leer metadata de tablas.</td>
                    </tr>
                  <?php endif; ?>
                </tbody>
              </table>
            </div>
          </div>
        </div>

        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <span>Resumen completo para copiar y comparar</span>
            <span id="copyFeedback" class="small text-success"></span>
          </div>
          <div class="card-body">
            <textarea id="diagnosticText" class="form-control" rows="16" readonly><?php echo htmlspecialchars($diagnosticText); ?></textarea>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="copyDiagnosticBtn">
          <i class="bi bi-clipboard-check"></i> Copiar informacion
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Bootstrap Bundle con Popper (necesario para Dropdowns) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Convertimos en objeto JS el array con: CREATE TABLE y columnas para cada tabla
var tableInfo = <?php echo json_encode($tableInfo, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG); ?>;

$(document).ready(function(){
  $('.datatable').DataTable({
    paging: false,
    info: false,
    lengthChange: false,
    language: {
      search: "Buscar:",
      zeroRecords: "No se encontraron resultados"
    }
  });
});

// Enviar el formulario con Ctrl+Enter
$('#consulta_all').keydown(function(e){
  if(e.ctrlKey && e.key === 'Enter'){
    e.preventDefault();
    $('#form-consulta').submit();
  }
});

// Resaltar fila seleccionada
$(document).on('click','table.dataTable tbody tr',function(){
  $('table.dataTable tbody tr').removeClass('selected');
  $(this).addClass('selected');
});

// Función para asignar cualquier consulta al textarea
function setQuery(query) {
  document.getElementById('consulta_all').value = query;
}

// Genera un INSERT real con las columnas de la tabla
function setInsertQuery(tabla) {
  var columns = tableInfo[tabla].columns;
  // columns = ["id", "nombre", "apellido", ...]
  var columnList = columns.join(", ");
  // Asignamos un valor genérico a cada columna
  // (puedes modificarlo según tus necesidades)
  var valuesList = columns.map(function(){return "valor";}).join(", ");
  
  var query = "INSERT INTO " + tabla + " (" + columnList + ") VALUES (" + valuesList + ");";
  setQuery(query);
}

// Muestra el código CREATE TABLE en el textarea
function verCodigoTabla(tabla) {
  var createSQL = tableInfo[tabla].create;
  setQuery(createSQL);
}

function copyDiagnosticInfo() {
  var diagnosticText = document.getElementById('diagnosticText');
  var feedback = document.getElementById('copyFeedback');
  if (!diagnosticText || !feedback) {
    return;
  }

  var textToCopy = diagnosticText.value;
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(textToCopy).then(function() {
      feedback.textContent = 'Copiado al portapapeles';
      setTimeout(function(){ feedback.textContent = ''; }, 2000);
    }).catch(function() {
      diagnosticText.select();
      document.execCommand('copy');
      feedback.textContent = 'Copiado al portapapeles';
      setTimeout(function(){ feedback.textContent = ''; }, 2000);
    });
    return;
  }

  diagnosticText.select();
  document.execCommand('copy');
  feedback.textContent = 'Copiado al portapapeles';
  setTimeout(function(){ feedback.textContent = ''; }, 2000);
}

var copyDiagnosticBtn = document.getElementById('copyDiagnosticBtn');
if (copyDiagnosticBtn) {
  copyDiagnosticBtn.addEventListener('click', copyDiagnosticInfo);
}

<?php if (!$tablaSeleccionada && !$consulta_all && count($tableNames) > 0): ?>
// Generar gráfico en el Dashboard
var ctx = document.getElementById('chartRegistro').getContext('2d');
var chartRegistro = new Chart(ctx, {
  type: 'bar',
  data: {
    labels: <?php echo json_encode($tableNames); ?>,
    datasets: [{
      label: 'Cantidad de registros',
      data: <?php echo json_encode($tableCounts); ?>,
      backgroundColor: 'rgba(120, 194, 173, 0.6)',
      borderColor: 'rgba(120, 194, 173, 1)',
      borderWidth: 1
    }]
  },
  options: {
    responsive: true,
    maintainAspectRatio: false,
    scales: { y: { beginAtZero: true } },
    plugins: { legend: { display: false } }
  }
});
<?php endif; ?>
</script>
</body>
</html>

