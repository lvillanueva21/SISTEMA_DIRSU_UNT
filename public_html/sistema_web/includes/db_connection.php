<?php
/**
 * Conexion mysqli reutilizable para todo el sistema.
 * Mantiene compatibilidad con codigo legacy que espera $conexion.
 */

include_once __DIR__ . '/config.php';

// Si este archivo se incluye dentro de una funcion, $RSU_CONFIG puede quedar
// en scope local. Lo promovemos a $GLOBALS para rsu_db_connect().
if (!isset($GLOBALS['RSU_CONFIG']) && isset($RSU_CONFIG) && is_array($RSU_CONFIG)) {
    $GLOBALS['RSU_CONFIG'] = $RSU_CONFIG;
}

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

        try {
            $shared_connection = @mysqli_connect($host, $user, $pass, $name);
        } catch (Throwable $e) {
            error_log(
                'RSU DB connection exception: host=' . (string)$host .
                ' db=' . (string)$name .
                ' user=' . (string)$user .
                ' msg=' . $e->getMessage()
            );
            $shared_connection = false;
        }
        if (!$shared_connection) {
            error_log(
                'RSU DB connection failed: host=' . (string)$host .
                ' db=' . (string)$name .
                ' user=' . (string)$user .
                ' errno=' . (string)mysqli_connect_errno() .
                ' error=' . (string)mysqli_connect_error()
            );
            return false;
        }

        @mysqli_set_charset($shared_connection, $charset);
        @mysqli_query($shared_connection, "SET time_zone = '" . mysqli_real_escape_string($shared_connection, $sql_tz) . "'");

        return $shared_connection;
    }
}

// Reestablece siempre la conexion compartida de este include.
// Evita depender de handles previos que pudieron cerrarse en otros includes.
$rsu_connection = rsu_db_connect();
if ($rsu_connection instanceof mysqli) {
    $conexion = $rsu_connection;
} elseif (!isset($conexion) || !($conexion instanceof mysqli)) {
    $conexion = false;
}

