<?php
/**
 * Helpers de rutas relativas para API Dirsu.
 * Evita URLs absolutas a raiz para facilitar migraciones entre dominios/subrutas.
 */

if (!function_exists('rsu_api_dirsu_relative_url')) {
    function rsu_api_dirsu_relative_url($target_app_path)
    {
        $target_app_path = ltrim(str_replace('\\', '/', (string)$target_app_path), '/');
        if ($target_app_path === '') {
            return '#';
        }

        $script_file = isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : '';
        // __DIR__ = includes/api_dirsu, por lo tanto la raiz de app es sistema_web.
        $app_root = realpath(__DIR__ . '/../..');
        $script_dir = $script_file !== '' ? realpath(dirname($script_file)) : false;

        if (!$app_root || !$script_dir) {
            return $target_app_path;
        }

        $app_root_norm = str_replace('\\', '/', rtrim($app_root, '\\/'));
        $script_dir_norm = str_replace('\\', '/', rtrim($script_dir, '\\/'));

        if (stripos($script_dir_norm, $app_root_norm) !== 0) {
            return $target_app_path;
        }

        $relative_dir = ltrim(substr($script_dir_norm, strlen($app_root_norm)), '/');
        $levels = $relative_dir === '' ? 0 : count(explode('/', $relative_dir));
        $prefix = str_repeat('../', $levels);

        return $prefix . $target_app_path;
    }
}

if (!function_exists('rsu_api_dirsu_api_url')) {
    function rsu_api_dirsu_api_url()
    {
        return rsu_api_dirsu_relative_url('includes/api_dirsu/api.php');
    }
}
