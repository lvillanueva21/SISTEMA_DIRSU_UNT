<?php
/**
 * Guard reusable para páginas del sistema.
 */

include_once __DIR__ . '/project_interface_access_service.php';

if (!function_exists('rsu_project_interface_guard')) {
    function rsu_project_interface_guard($conexion, $interface_code, $options = array())
    {
        return rsu_project_interface_access_evaluate($conexion, $interface_code, $options);
    }
}
