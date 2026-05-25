<?php
include_once __DIR__ . '/guard_api.php';
include_once __DIR__ . '/json_response.php';
include_once __DIR__ . '/user_service.php';
include_once __DIR__ . '/project_service.php';
include_once __DIR__ . '/semester_audit_service.php';
include_once __DIR__ . '/active_periods_service.php';
include_once __DIR__ . '/project_interface_access_service.php';

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

if ($action === 'user.projects.get') {
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

    $result = rsu_api_user_projects_get($id, $usuario);
    if (!is_array($result) || !isset($result['ok']) || !$result['ok']) {
        $error_code = isset($result['error_code']) ? (string)$result['error_code'] : 'internal_error';
        $error_message = isset($result['error_message']) ? (string)$result['error_message'] : 'No se pudo completar la consulta.';

        if ($error_code === 'not_found') {
            rsu_api_json_error(404, $error_code, $error_message, array());
        }

        if ($error_code === 'missing_filter' || $error_code === 'invalid_filter' || $error_code === 'invalid_usuario' || $error_code === 'not_coordinator') {
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

    rsu_api_json_ok($result['data'], 'Consulta de proyectos del coordinador completada.', $meta);
}

if ($action === 'project.semesters.audit') {
    $id_py = 0;
    if (isset($_GET['id_py'])) {
        $id_py = (int)$_GET['id_py'];
    }

    $id = 0;
    if (isset($_GET['id'])) {
        $id = (int)$_GET['id'];
    }

    $usuario = '';
    if (isset($_GET['usuario'])) {
        $usuario = trim((string)$_GET['usuario']);
    }

    if ($id_py <= 0 && $id <= 0 && $usuario === '') {
        rsu_api_json_error(422, 'missing_filter', 'Debes enviar id_py o id/usuario.', array());
    }

    if ($usuario !== '' && strlen($usuario) > 120) {
        rsu_api_json_error(422, 'invalid_usuario', 'El valor de usuario excede el maximo permitido.', array());
    }

    $result = rsu_api_project_semesters_audit_get($id_py, $id, $usuario);
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

    rsu_api_json_ok($result['data'], 'Auditoria de semestres completada.', $meta);
}

if ($action === 'project.semesters.preview') {
    $fecha_inicio = isset($_GET['fecha_inicio']) ? trim((string)$_GET['fecha_inicio']) : '';
    $fecha_fin = isset($_GET['fecha_fin']) ? trim((string)$_GET['fecha_fin']) : '';

    $result = rsu_api_project_semesters_preview_get($fecha_inicio, $fecha_fin);
    if (!is_array($result) || !isset($result['ok']) || !$result['ok']) {
        $error_code = isset($result['error_code']) ? (string)$result['error_code'] : 'internal_error';
        $error_message = isset($result['error_message']) ? (string)$result['error_message'] : 'No se pudo calcular la vista previa.';

        if ($error_code === 'missing_dates' || $error_code === 'invalid_dates' || $error_code === 'inverted_dates') {
            rsu_api_json_error(422, $error_code, $error_message, array());
        }

        rsu_api_json_error(400, $error_code, $error_message, array());
    }

    $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : array();
    $meta['requested_at'] = date('Y-m-d H:i:s');
    $meta['requested_by'] = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;

    rsu_api_json_ok($result['data'], 'Vista previa de semestres calculada.', $meta);
}

if ($action === 'periods.active.snapshot.get') {
    $role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
    if ($role_id !== 1) {
        rsu_api_json_error(403, 'forbidden_role', 'No tienes permisos para usar este endpoint.', array());
    }

    $id_periodo = 0;
    if (isset($_GET['id_periodo'])) {
        $id_periodo = (int)$_GET['id_periodo'];
    }
    if ($id_periodo < 0) {
        rsu_api_json_error(422, 'invalid_id_periodo', 'El parametro id_periodo es invalido.', array());
    }

    $include_empty = 1;
    if (isset($_GET['include_empty'])) {
        $raw_include_empty = trim((string)$_GET['include_empty']);
        if ($raw_include_empty !== '0' && $raw_include_empty !== '1' && $raw_include_empty !== '') {
            rsu_api_json_error(422, 'invalid_include_empty', 'El parametro include_empty debe ser 0 o 1.', array());
        }
        if ($raw_include_empty === '0') {
            $include_empty = 0;
        }
    }

    $timezone_name = 'America/Lima';
    if (isset($_GET['tz'])) {
        $tz_input = trim((string)$_GET['tz']);
        if ($tz_input !== '') {
            try {
                new DateTimeZone($tz_input);
                $timezone_name = $tz_input;
            } catch (Throwable $e) {
                rsu_api_json_error(422, 'invalid_timezone', 'El parametro tz no es una zona horaria valida.', array());
            }
        }
    }

    $result = rsu_api_periods_active_snapshot_get($id_periodo, $include_empty, $timezone_name);
    if (!is_array($result) || !isset($result['ok']) || !$result['ok']) {
        $error_code = isset($result['error_code']) ? (string)$result['error_code'] : 'internal_error';
        $error_message = isset($result['error_message']) ? (string)$result['error_message'] : 'No se pudo completar la consulta.';

        if ($error_code === 'invalid_id_periodo') {
            rsu_api_json_error(422, $error_code, $error_message, array());
        }

        if ($error_code === 'db_connection_error' || $error_code === 'db_prepare_error' || $error_code === 'db_query_error') {
            rsu_api_json_error(500, $error_code, $error_message, array());
        }

        rsu_api_json_error(400, $error_code, $error_message, array());
    }

    $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : array();
    $meta['requested_at'] = date('Y-m-d H:i:s');
    $meta['requested_by'] = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;

    rsu_api_json_ok($result['data'], 'Snapshot de periodos activos completado.', $meta);
}

if ($action === 'project.interface.access.evaluate') {
    $interface_code = isset($_GET['interface_code']) ? trim((string)$_GET['interface_code']) : '';
    if ($interface_code === '') {
        rsu_api_json_error(422, 'missing_interface_code', 'Debes enviar el parametro interface_code.', array());
    }

    $id_py = 0;
    if (isset($_GET['id_py'])) {
        $id_py = (int)$_GET['id_py'];
    }
    if ($id_py < 0) {
        rsu_api_json_error(422, 'invalid_id_py', 'El parametro id_py es invalido.', array());
    }

    $timezone_name = 'America/Lima';
    if (isset($_GET['tz'])) {
        $tz_input = trim((string)$_GET['tz']);
        if ($tz_input !== '') {
            try {
                new DateTimeZone($tz_input);
                $timezone_name = $tz_input;
            } catch (Throwable $e) {
                rsu_api_json_error(422, 'invalid_timezone', 'El parametro tz no es una zona horaria valida.', array());
            }
        }
    }

    $result = rsu_api_project_interface_access_get($interface_code, $id_py, $timezone_name);
    if (!is_array($result) || !isset($result['ok']) || !$result['ok']) {
        $error_code = isset($result['error_code']) ? (string)$result['error_code'] : 'internal_error';
        $error_message = isset($result['error_message']) ? (string)$result['error_message'] : 'No se pudo completar la consulta.';

        if ($error_code === 'missing_interface_code' || $error_code === 'invalid_id_py' || $error_code === 'invalid_interface_code') {
            rsu_api_json_error(422, $error_code, $error_message, array());
        }
        if ($error_code === 'db_connection_error') {
            rsu_api_json_error(500, $error_code, $error_message, array());
        }

        rsu_api_json_error(400, $error_code, $error_message, array());
    }

    $meta = isset($result['meta']) && is_array($result['meta']) ? $result['meta'] : array();
    $meta['requested_at'] = date('Y-m-d H:i:s');
    $meta['requested_by'] = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;

    rsu_api_json_ok($result['data'], 'Evaluacion de acceso por interfaz completada.', $meta);
}

rsu_api_json_error(400, 'unknown_action', 'Accion no soportada para api_dirsu.', array());
