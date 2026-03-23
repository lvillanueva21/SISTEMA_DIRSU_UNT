<?php
include_once __DIR__ . '/guard_api.php';
include_once __DIR__ . '/json_response.php';
include_once __DIR__ . '/user_service.php';

rsu_api_dirsu_guard_api(array(
    'allowed_roles' => array(0, 1, 2, 3, 4, 5)
));

$method = isset($_SERVER['REQUEST_METHOD']) ? strtoupper((string)$_SERVER['REQUEST_METHOD']) : 'GET';
if ($method !== 'GET') {
    rsu_api_json_error(405, 'method_not_allowed', 'Solo se permite el metodo GET en este endpoint.', array());
}

$action = isset($_GET['action']) ? trim((string)$_GET['action']) : '';
if ($action === '') {
    rsu_api_json_error(400, 'missing_action', 'Falta el parametro action.', array());
}

if ($action === 'user.get') {
    $id = 0;
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
    }

    $usuario = '';
    if (isset($_GET['usuario'])) {
        $usuario = trim((string)$_GET['usuario']);
    }

    if ($id <= 0 && $usuario === '') {
        rsu_api_json_error(422, 'missing_filter', 'Debes enviar id o usuario.', array());
    }

    if ($usuario !== '' && strlen($usuario) > 120) {
        rsu_api_json_error(422, 'invalid_usuario', 'El valor de usuario excede el maximo permitido.', array());
    }

    $result = rsu_api_user_get($id, $usuario);
    if (!is_array($result) || !isset($result['ok']) || !$result['ok']) {
        $error_code = isset($result['error_code']) ? (string)$result['error_code'] : 'internal_error';
        $error_message = isset($result['error_message']) ? (string)$result['error_message'] : 'No se pudo completar la consulta.';

        if ($error_code === 'not_found') {
            rsu_api_json_error(404, $error_code, $error_message, array());
        }

        if ($error_code === 'missing_filter' || $error_code === 'invalid_filter' || $error_code === 'invalid_usuario') {
            rsu_api_json_error(422, $error_code, $error_message, array());
        }

        if ($error_code === 'db_connection_error' || $error_code === 'db_prepare_error') {
            rsu_api_json_error(500, $error_code, $error_message, array());
        }

        rsu_api_json_error(400, $error_code, $error_message, array());
    }

    $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : array();
    $meta['requested_at'] = date('Y-m-d H:i:s');
    $meta['requested_by'] = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;

    rsu_api_json_ok($result['data'], 'Consulta de usuario completada.', $meta);
}

rsu_api_json_error(400, 'unknown_action', 'Accion no soportada para api_dirsu.', array());
