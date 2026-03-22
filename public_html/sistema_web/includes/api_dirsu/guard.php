<?php
/**
 * Guard de acceso para Api Dirsu.
 * - Exige sesion activa.
 * - Solo habilita en session_mode=development.
 * - Restringe a roles migrados en menu_matrix (1..5).
 */

include_once __DIR__ . '/../config.php';

if (!function_exists('rsu_api_dirsu_abort_not_found')) {
    function rsu_api_dirsu_abort_not_found()
    {
        if (!headers_sent()) {
            $protocol = isset($_SERVER['SERVER_PROTOCOL']) ? $_SERVER['SERVER_PROTOCOL'] : 'HTTP/1.1';
            header($protocol . ' 404 Not Found');
            header('Content-Type: text/plain; charset=UTF-8');
        }

        echo '404 Not Found';
        exit;
    }
}

if (!function_exists('rsu_api_dirsu_guard')) {
    function rsu_api_dirsu_guard($options = array())
    {
        global $RSU_CONFIG;

        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }

        $allowed_roles = array(1, 2, 3, 4, 5);
        if (is_array($options) && isset($options['allowed_roles']) && is_array($options['allowed_roles'])) {
            $allowed_roles = $options['allowed_roles'];
        }

        $session_mode = isset($RSU_CONFIG['session_mode']) ? strtolower(trim((string)$RSU_CONFIG['session_mode'])) : 'production';
        if ($session_mode !== 'development') {
            rsu_api_dirsu_abort_not_found();
        }

        if (!isset($_SESSION['usuario']) || trim((string)$_SESSION['usuario']) === '') {
            rsu_api_dirsu_abort_not_found();
        }

        $role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
        if (!in_array($role_id, $allowed_roles, true)) {
            rsu_api_dirsu_abort_not_found();
        }
    }
}
