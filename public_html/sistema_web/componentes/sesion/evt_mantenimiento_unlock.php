<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';

function evt_mto_unlock_exit($success, $msg, $data = null, $httpCode = 200)
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    evt_mto_unlock_exit(false, 'Método no permitido.', null, 405);
}

$csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!evt_mto_validate_csrf_token($csrfToken, 'evt_mantenimiento_unlock_csrf')) {
    evt_mto_unlock_exit(false, 'Token CSRF inválido.', null, 403);
}

$state = evt_mto_fetch_state();
$maintenanceOn = ((int)$state['sistema_activo'] === 0);

if (!$maintenanceOn) {
    evt_mto_set_bypass_session(false);
    evt_mto_unlock_exit(true, 'Sistema activo.', array('unlocked' => true));
}

$conexion = evt_mto_db_connect();
if (!($conexion instanceof mysqli)) {
    error_log('evt_mantenimiento_unlock: conexión BD no disponible');
    evt_mto_unlock_exit(false, 'No se pudo validar la clave en este momento.', null, 500);
}

$sql = "SELECT m.clave_hash
          FROM evt_mantenimiento_cfg m
          JOIN evt_eventos e ON e.id = m.evento_id
         WHERE e.codigo = 'mantenimiento_sistema'
         LIMIT 1";
$res = @mysqli_query($conexion, $sql);
if ($res === false) {
    error_log('evt_mantenimiento_unlock: consulta de clave_hash falló');
    evt_mto_unlock_exit(false, 'No se pudo validar la clave en este momento.', null, 500);
}

$row = mysqli_fetch_assoc($res);
$hash = ($row && isset($row['clave_hash'])) ? trim((string)$row['clave_hash']) : '';
if ($hash === '') {
    evt_mto_unlock_exit(false, 'No hay clave secreta configurada.', null, 422);
}

$secret = isset($_POST['clave']) ? trim((string)$_POST['clave']) : '';
if ($secret === '') {
    evt_mto_unlock_exit(false, 'Debe ingresar la clave secreta.', null, 422);
}

if (!password_verify($secret, $hash)) {
    evt_mto_unlock_exit(false, 'Clave secreta incorrecta.', null, 401);
}

evt_mto_set_bypass_session(true);
evt_mto_unlock_exit(true, 'Acceso habilitado para esta sesión.', array('unlocked' => true));
