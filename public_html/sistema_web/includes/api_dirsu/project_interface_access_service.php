<?php
/**
 * Wrapper API para evaluación de acceso por interfaz.
 */

include_once __DIR__ . '/../db_connection.php';
include_once __DIR__ . '/../access/project_interface_access_service.php';

if (!function_exists('rsu_api_project_interface_access_get')) {
    function rsu_api_project_interface_access_get($interface_code, $id_py, $timezone_name)
    {
        $interface_code = trim((string)$interface_code);
        $id_py = (int)$id_py;
        $timezone_name = trim((string)$timezone_name);
        if ($timezone_name === '') {
            $timezone_name = 'America/Lima';
        }

        if ($interface_code === '') {
            return array(
                'ok' => false,
                'error_code' => 'missing_interface_code',
                'error_message' => 'Debes enviar interface_code.'
            );
        }

        $conexion = rsu_db_connect();
        if (!($conexion instanceof mysqli)) {
            return array(
                'ok' => false,
                'error_code' => 'db_connection_error',
                'error_message' => 'No fue posible conectar con la base de datos.'
            );
        }

        $evaluation = rsu_project_interface_access_evaluate($conexion, $interface_code, array(
            'id_py' => $id_py,
            'timezone' => $timezone_name
        ));

        if (!is_array($evaluation)) {
            return array(
                'ok' => false,
                'error_code' => 'internal_error',
                'error_message' => 'No se pudo evaluar el acceso.'
            );
        }

        if (!empty($evaluation['ok']) || isset($evaluation['allow'])) {
            return array(
                'ok' => true,
                'data' => $evaluation,
                'meta' => array(
                    'timezone' => $timezone_name,
                    'requested_interface' => $interface_code,
                    'requested_id_py' => $id_py > 0 ? $id_py : null
                )
            );
        }

        return array(
            'ok' => false,
            'error_code' => isset($evaluation['reason_code']) ? (string)$evaluation['reason_code'] : 'internal_error',
            'error_message' => isset($evaluation['reason_message']) ? (string)$evaluation['reason_message'] : 'No se pudo evaluar el acceso.'
        );
    }
}
