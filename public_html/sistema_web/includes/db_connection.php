<?php
/**
 * Conexion mysqli reutilizable para todo el sistema.
 * Mantiene compatibilidad con codigo legacy que espera $conexion.
 */

include_once __DIR__ . '/config.php';

if (!function_exists('rsu_db_connect')) {
    function rsu_db_connect()
    {
        global $RSU_CONFIG;
        static $shared_connection = null;

        if ($shared_connection instanceof mysqli) {
            return $shared_connection;
        }

        $host = isset($RSU_CONFIG['db']['host']) ? $RSU_CONFIG['db']['host'] : 'localhost';
        $user = isset($RSU_CONFIG['db']['user']) ? $RSU_CONFIG['db']['user'] : '';
        $pass = isset($RSU_CONFIG['db']['pass']) ? $RSU_CONFIG['db']['pass'] : '';
        $name = isset($RSU_CONFIG['db']['name']) ? $RSU_CONFIG['db']['name'] : '';
        $charset = isset($RSU_CONFIG['db']['charset']) ? $RSU_CONFIG['db']['charset'] : 'utf8mb4';
        $sql_tz = isset($RSU_CONFIG['db']['sql_time_zone']) ? $RSU_CONFIG['db']['sql_time_zone'] : '-05:00';

        $shared_connection = @mysqli_connect($host, $user, $pass, $name);
        if (!$shared_connection) {
            return false;
        }

        @mysqli_set_charset($shared_connection, $charset);
        @mysqli_query($shared_connection, "SET time_zone = '" . mysqli_real_escape_string($shared_connection, $sql_tz) . "'");

        return $shared_connection;
    }
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    $conexion = rsu_db_connect();
}

if (!$conexion) {
    echo "No se realizo la conexion a la base de datos, el error fue: " . mysqli_connect_error();
}

