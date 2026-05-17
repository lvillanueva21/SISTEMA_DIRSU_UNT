<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';

function evt_mto_api_exit($success, $msg, $data = null, $httpCode = 200)
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($httpCode);
    }
    $out = array('success' => (bool)$success, 'msg' => (string)$msg);
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out);
    exit;
}

function evt_mto_api_ensure_db_manager_event(mysqli $conexion, $userId = null)
{
    $uid = ($userId === null || (int)$userId <= 0) ? null : (int)$userId;

    $sql = "INSERT INTO evt_eventos (codigo, nombre, estado, actualizado_por)
            VALUES ('acceso_gestor_db', 'Acceso a Gestor DB', 0, ?)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'i', $uid);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function evt_mto_api_get_db_manager_state(mysqli $conexion)
{
    $state = array(
        'codigo' => 'acceso_gestor_db',
        'nombre' => 'Acceso a Gestor DB',
        'estado' => 0,
        'actualizado_en' => null,
        'actualizado_por' => null
    );

    $sql = "SELECT codigo, nombre, estado, actualizado_en, actualizado_por
              FROM evt_eventos
             WHERE codigo = 'acceso_gestor_db'
             LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        return $state;
    }

    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return $state;
    }

    $state['codigo'] = isset($row['codigo']) ? (string)$row['codigo'] : 'acceso_gestor_db';
    $state['nombre'] = isset($row['nombre']) ? (string)$row['nombre'] : 'Acceso a Gestor DB';
    $state['estado'] = (isset($row['estado']) && (int)$row['estado'] === 1) ? 1 : 0;
    $state['actualizado_en'] = isset($row['actualizado_en']) ? $row['actualizado_en'] : null;
    $state['actualizado_por'] = isset($row['actualizado_por']) ? $row['actualizado_por'] : null;

    return $state;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    evt_mto_api_exit(false, 'Metodo no permitido.', null, 405);
}

if (!isset($_SESSION['usuario'])) {
    evt_mto_api_exit(false, 'Sesion no valida.', null, 401);
}

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
    evt_mto_api_exit(false, 'No autorizado.', null, 403);
}

$csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!evt_mto_validate_csrf_token($csrfToken, 'evt_mantenimiento_admin_csrf')) {
    evt_mto_api_exit(false, 'Token CSRF invalido.', null, 403);
}

$conexion = evt_mto_db_connect();
if (!($conexion instanceof mysqli)) {
    error_log('evt_mantenimiento_api: conexion BD no disponible');
    evt_mto_api_exit(false, 'No se pudo procesar la solicitud en este momento.', null, 500);
}
@mysqli_set_charset($conexion, 'utf8mb4');

$userId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
if (!evt_mto_ensure_seed($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar seed de evt_');
    evt_mto_api_exit(false, 'No se pudo inicializar la configuracion de mantenimiento.', null, 500);
}
if (!evt_mto_api_ensure_db_manager_event($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar evento acceso_gestor_db');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de acceso del gestor DB.', null, 500);
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if ($action === 'get_state') {
    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    evt_mto_api_exit(true, 'Estado cargado.', $state);
}

if ($action === 'save_db_manager_access') {
    $dbManagerEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : -1;
    if ($dbManagerEstado !== 0 && $dbManagerEstado !== 1) {
        evt_mto_api_exit(false, 'Estado de acceso al gestor DB invalido.', null, 422);
    }

    $sql = "UPDATE evt_eventos
               SET estado = ?,
                   actualizado_por = ?,
                   actualizado_en = NOW()
             WHERE codigo = 'acceso_gestor_db'
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        evt_mto_api_exit(false, 'No se pudo preparar la actualizacion del gestor DB.', null, 500);
    }
    mysqli_stmt_bind_param($st, 'ii', $dbManagerEstado, $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if (!$ok) {
        evt_mto_api_exit(false, 'No se pudo guardar la configuracion del gestor DB.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    evt_mto_api_exit(true, 'Acceso a gestor DB actualizado correctamente.', $state);
}

if ($action !== 'save_config') {
    evt_mto_api_exit(false, 'Accion no permitida.', null, 400);
}

$sistemaActivo = isset($_POST['sistema_activo']) ? (int)$_POST['sistema_activo'] : -1;
if ($sistemaActivo !== 0 && $sistemaActivo !== 1) {
    evt_mto_api_exit(false, 'Estado de sistema invalido.', null, 422);
}

$titulo = evt_mto_trim_limit(isset($_POST['titulo']) ? $_POST['titulo'] : '', 180);
$mensaje = evt_mto_trim_limit(isset($_POST['mensaje']) ? $_POST['mensaje'] : '', 5000);
$claveNueva = isset($_POST['clave_nueva']) ? trim((string)$_POST['clave_nueva']) : '';

if ($titulo === '') {
    $titulo = evt_mto_default_title();
}
if ($mensaje === '') {
    $mensaje = evt_mto_default_message();
}

if ($claveNueva !== '' && strlen($claveNueva) < 8) {
    evt_mto_api_exit(false, 'La clave secreta debe tener al menos 8 caracteres.', null, 422);
}

$current = evt_mto_fetch_state();
$eventoId = isset($current['evento_id']) ? (int)$current['evento_id'] : 0;
if ($eventoId <= 0) {
    error_log('evt_mantenimiento_api: evento mantenimiento_sistema no encontrado');
    evt_mto_api_exit(false, 'No se encontro la configuracion de mantenimiento.', null, 500);
}

$hasSecret = !empty($current['has_secret']);
if ($sistemaActivo === 0 && !$hasSecret && $claveNueva === '') {
    evt_mto_api_exit(false, 'No se puede apagar el sistema sin clave secreta configurada.', null, 422);
}

$useHash = false;
$hash = null;
if ($claveNueva !== '') {
    $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        evt_mto_api_exit(false, 'No se pudo proteger la clave secreta.', null, 500);
    }
    $useHash = true;
}

mysqli_begin_transaction($conexion);
try {
    if ($useHash) {
        $sql = "UPDATE evt_mantenimiento_cfg
                   SET sistema_activo = ?,
                       titulo = ?,
                       mensaje = ?,
                       clave_hash = ?,
                       clave_actualizada_en = NOW(),
                       actualizado_por = ?
                 WHERE evento_id = ?";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la actualizacion.');
        }
        mysqli_stmt_bind_param($st, 'isssii', $sistemaActivo, $titulo, $mensaje, $hash, $userId, $eventoId);
    } else {
        $sql = "UPDATE evt_mantenimiento_cfg
                   SET sistema_activo = ?,
                       titulo = ?,
                       mensaje = ?,
                       actualizado_por = ?
                 WHERE evento_id = ?";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la actualizacion.');
        }
        mysqli_stmt_bind_param($st, 'issii', $sistemaActivo, $titulo, $mensaje, $userId, $eventoId);
    }

    if (!mysqli_stmt_execute($st)) {
        mysqli_stmt_close($st);
        throw new Exception('No se pudo guardar la configuracion.');
    }
    mysqli_stmt_close($st);

    mysqli_commit($conexion);

    if ($sistemaActivo === 1) {
        evt_mto_set_bypass_session(false);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    evt_mto_api_exit(true, 'Configuracion guardada correctamente.', $state);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log('evt_mantenimiento_api save_config: ' . $e->getMessage());
    evt_mto_api_exit(false, 'No se pudo guardar la configuracion de mantenimiento.', null, 500);
}
