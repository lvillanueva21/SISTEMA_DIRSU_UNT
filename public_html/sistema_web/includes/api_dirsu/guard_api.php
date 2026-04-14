<?php
/**
 * Guard de acceso para endpoints API Dirsu.
 * - Exige sesion activa.
 * - Permite configurar roles autorizados.
 * - No depende de session_mode (la API se usa en todo el sistema).
 */

include_once __DIR__ . '/json_response.php';
include_once __DIR__ . '/../config.php';

if (!function_exists('rsu_api_dirsu_guard_api_start_session')) {
    function rsu_api_dirsu_guard_api_start_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}

if (!function_exists('rsu_api_dirsu_guard_api_is_logged')) {
    function rsu_api_dirsu_guard_api_is_logged()
    {
        return isset($_SESSION['usuario']) && trim((string)$_SESSION['usuario']) !== '';
    }
}

if (!function_exists('rsu_api_dirsu_guard_api')) {
    function rsu_api_dirsu_guard_api($options)
    {
        if (!is_array($options)) {
            $options = array();
        }

        rsu_api_dirsu_guard_api_start_session();

        if (!rsu_api_dirsu_guard_api_is_logged()) {
            rsu_api_json_error(401, 'auth_required', 'Sesión no válida o expirada.', array());
        }

        if (isset($options['allowed_roles']) && is_array($options['allowed_roles']) && count($options['allowed_roles']) > 0) {
            $role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
            if (!in_array($role_id, $options['allowed_roles'], true)) {
                rsu_api_json_error(403, 'forbidden_role', 'No tienes permisos para usar este endpoint.', array());
            }
        }
    }
}
