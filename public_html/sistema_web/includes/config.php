<?php
/**
 * Configuracion central del sistema.
 * Nota: este archivo debe ser incluido desde codigo PHP, no accedido via navegador.
 */

if (!defined('RSU_CONFIG_LOADED')) {
    define('RSU_CONFIG_LOADED', 1);

    // Zona horaria oficial del sistema (Lima, Peru).
    date_default_timezone_set('America/Lima');

    $RSU_CONFIG = array(
        // Modo de aplicacion.
        'app_env' => 'production',
        // 'app_env' => 'development',

        // Modo de sesion (base para futuras reglas).
        'session_mode' => 'production',
        // 'session_mode' => 'development',

        // Se mantiene configurable para uso futuro.
        // Recomendacion: priorizar rutas relativas en vistas/modulos.
        'base_url' => 'https://rsu.unitru.edu.pe',

        'timezone' => 'America/Lima',

        'db' => array(
            'host' => 'localhost',
            'name' => 'rsudb',
            'user' => 'au_rsu',
            'pass' => '_BrHJMGO3U3(9v.c',
            'charset' => 'utf8mb4',
            'sql_time_zone' => '-05:00'
        )
    );
}

