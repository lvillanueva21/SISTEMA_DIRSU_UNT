<?php
/**
 * Helpers compartidos para Control de eventos > Mantenimiento del sistema.
 * Requiere conexion mysqli desde includes/db_connection.php.
 */

if (!defined('EVT_MTO_HELPERS_LOADED')) {
    define('EVT_MTO_HELPERS_LOADED', 1);

    // Carga temprana en scope global para evitar problemas de alcance
    // cuando este helper se invoca desde funciones.
    if (!function_exists('rsu_db_connect')) {
        try {
            include_once __DIR__ . '/db_connection.php';
        } catch (Throwable $e) {
            if (function_exists('rsu_diag_context')) {
                rsu_diag_context('evt_db_bootstrap_exception', $e->getMessage());
            }
        }
    }

    function evt_mto_default_title()
    {
        return 'SISTEMA DIRSU EN MANTENIMIENTO';
    }

    function evt_mto_default_message()
    {
        return 'El Área Informática a cargo de la gestión del SISTEMA DIRSU se encuentra realizando labores de mantenimiento para corregir incidencias surgidas tras la modernización de los servidores de la Oficina de Tecnología e Información (OTI) de la UNT. Trabajaremos para restablecer el servicio a la brevedad. Gracias por su comprensión.';
    }

    function evt_mto_default_image()
    {
        return 'imagenes/mantenimiento2.png';
    }

    function evt_mto_db_connect()
    {
        static $initialized = false;
        static $cached = null;

        if ($initialized) {
            return $cached;
        }
        $initialized = true;

        if (!isset($GLOBALS['conexion']) || !($GLOBALS['conexion'] instanceof mysqli)) {
            if (function_exists('rsu_db_connect')) {
                try {
                    $tmp = rsu_db_connect();
                    if ($tmp instanceof mysqli) {
                        $GLOBALS['conexion'] = $tmp;
                    }
                } catch (Throwable $e) {
                    if (function_exists('rsu_diag_context')) {
                        rsu_diag_context('evt_db_connect_exception', $e->getMessage());
                    }
                    $cached = false;
                    return $cached;
                }
            }
        }

        if (isset($GLOBALS['conexion']) && ($GLOBALS['conexion'] instanceof mysqli)) {
            $cached = $GLOBALS['conexion'];
            @mysqli_set_charset($cached, 'utf8mb4');
        } else {
            $cached = false;
        }

        return $cached;
    }

    function evt_mto_make_relative_path($fromDir, $toPath)
    {
        $from = str_replace('\\', '/', (string)$fromDir);
        $to = str_replace('\\', '/', (string)$toPath);

        $from = trim($from);
        $to = trim($to);
        if ($from === '' || $to === '') {
            return 'login.php';
        }

        $fromParts = array_values(array_filter(explode('/', rtrim($from, '/')), 'strlen'));
        $toParts = array_values(array_filter(explode('/', $to), 'strlen'));

        $max = min(count($fromParts), count($toParts));
        $common = 0;
        while ($common < $max && $fromParts[$common] === $toParts[$common]) {
            $common++;
        }

        $up = array_fill(0, count($fromParts) - $common, '..');
        $down = array_slice($toParts, $common);
        $parts = array_merge($up, $down);

        if (empty($parts)) {
            return './';
        }

        $rel = implode('/', $parts);
        if (strpos($rel, '../') !== 0 && strpos($rel, './') !== 0) {
            $rel = './' . $rel;
        }
        return $rel;
    }

    function evt_mto_get_login_relative_path()
    {
        $systemRoot = realpath(__DIR__ . '/..');
        if ($systemRoot === false) {
            return 'login.php';
        }

        $target = rtrim(str_replace('\\', '/', $systemRoot), '/') . '/login.php';
        $scriptFile = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $fromDir = $scriptFile !== '' ? realpath(dirname($scriptFile)) : false;
        if ($fromDir === false) {
            $fromDir = $systemRoot;
        }

        return evt_mto_make_relative_path($fromDir, $target);
    }

    function evt_mto_fetch_state()
    {
        $state = array(
            'ok' => true,
            'evento_id' => 0,
            'sistema_activo' => 1,
            'titulo' => evt_mto_default_title(),
            'mensaje' => evt_mto_default_message(),
            'imagen' => evt_mto_default_image(),
            'has_secret' => false,
            'actualizado_en' => null,
            'actualizado_por' => null
        );

        $conexion = evt_mto_db_connect();
        if (!($conexion instanceof mysqli)) {
            return $state;
        }

        $sql = "SELECT e.id AS evento_id,
                       m.sistema_activo,
                       m.titulo,
                       m.mensaje,
                       m.imagen,
                       m.clave_hash,
                       m.actualizado_en,
                       m.actualizado_por
                  FROM evt_eventos e
             LEFT JOIN evt_mantenimiento_cfg m ON m.evento_id = e.id
                 WHERE e.codigo = 'mantenimiento_sistema'
                 LIMIT 1";

        $res = @mysqli_query($conexion, $sql);
        if ($res === false) {
            return $state;
        }

        $row = mysqli_fetch_assoc($res);
        if (!$row) {
            return $state;
        }

        $state['evento_id'] = (int)$row['evento_id'];
        if (isset($row['sistema_activo']) && $row['sistema_activo'] !== null) {
            $state['sistema_activo'] = ((int)$row['sistema_activo'] === 1) ? 1 : 0;
        }
        if (isset($row['titulo']) && trim((string)$row['titulo']) !== '') {
            $state['titulo'] = (string)$row['titulo'];
        }
        if (isset($row['mensaje']) && trim((string)$row['mensaje']) !== '') {
            $state['mensaje'] = (string)$row['mensaje'];
        }
        if (isset($row['imagen']) && trim((string)$row['imagen']) !== '') {
            $state['imagen'] = (string)$row['imagen'];
        }
        $state['has_secret'] = isset($row['clave_hash']) && trim((string)$row['clave_hash']) !== '';
        $state['actualizado_en'] = isset($row['actualizado_en']) ? $row['actualizado_en'] : null;
        $state['actualizado_por'] = isset($row['actualizado_por']) ? $row['actualizado_por'] : null;

        return $state;
    }

    function evt_mto_is_enabled()
    {
        $state = evt_mto_fetch_state();
        return ((int)$state['sistema_activo'] === 0);
    }

    function evt_mto_has_bypass_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        return isset($_SESSION['evt_mantenimiento_bypass']) && (int)$_SESSION['evt_mantenimiento_bypass'] === 1;
    }

    function evt_mto_set_bypass_session($enabled)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        if ($enabled) {
            $_SESSION['evt_mantenimiento_bypass'] = 1;
            $_SESSION['evt_mantenimiento_bypass_at'] = time();
            return;
        }

        unset($_SESSION['evt_mantenimiento_bypass'], $_SESSION['evt_mantenimiento_bypass_at']);
    }

    function evt_mto_get_csrf_token($key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key]) || strlen($_SESSION[$key]) < 16) {
            if (function_exists('random_bytes')) {
                $_SESSION[$key] = bin2hex(random_bytes(32));
            } elseif (function_exists('openssl_random_pseudo_bytes')) {
                $_SESSION[$key] = bin2hex(openssl_random_pseudo_bytes(32));
            } else {
                $_SESSION[$key] = md5(uniqid((string)mt_rand(), true)) . md5((string)microtime(true));
            }
        }

        return $_SESSION[$key];
    }

    function evt_mto_validate_csrf_token($token, $key)
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return false;
        }
        if (!isset($_SESSION[$key]) || !is_string($_SESSION[$key])) {
            return false;
        }

        $token = (string)$token;
        if ($token === '') {
            return false;
        }

        return hash_equals($_SESSION[$key], $token);
    }

    function evt_mto_trim_limit($value, $limit)
    {
        $value = trim((string)$value);
        if (function_exists('mb_substr')) {
            return mb_substr($value, 0, $limit, 'UTF-8');
        }
        return substr($value, 0, $limit);
    }

    function evt_mto_ensure_seed(mysqli $conexion, $userId = null)
    {
        $userIdSql = ($userId === null || (int)$userId <= 0) ? null : (int)$userId;

        $sqlEvt = "INSERT INTO evt_eventos (codigo, nombre, estado, actualizado_por)
                   VALUES ('mantenimiento_sistema', 'Mantenimiento del sistema', 1, ?)
                   ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";
        $stEvt = mysqli_prepare($conexion, $sqlEvt);
        if (!$stEvt) {
            return false;
        }
        mysqli_stmt_bind_param($stEvt, 'i', $userIdSql);
        if (!mysqli_stmt_execute($stEvt)) {
            mysqli_stmt_close($stEvt);
            return false;
        }
        mysqli_stmt_close($stEvt);

        $eventoId = 0;
        $resEvt = mysqli_query($conexion, "SELECT id FROM evt_eventos WHERE codigo='mantenimiento_sistema' LIMIT 1");
        if ($resEvt) {
            $rowEvt = mysqli_fetch_assoc($resEvt);
            if ($rowEvt && isset($rowEvt['id'])) {
                $eventoId = (int)$rowEvt['id'];
            }
        }
        if ($eventoId <= 0) {
            return false;
        }

        $titulo = evt_mto_default_title();
        $mensaje = evt_mto_default_message();
        $imagen = evt_mto_default_image();
        $sqlCfg = "INSERT INTO evt_mantenimiento_cfg
                      (evento_id, sistema_activo, titulo, mensaje, imagen, clave_hash, actualizado_por)
                   VALUES (?, 1, ?, ?, ?, NULL, ?)
                   ON DUPLICATE KEY UPDATE imagen = COALESCE(NULLIF(imagen, ''), VALUES(imagen))";
        $stCfg = mysqli_prepare($conexion, $sqlCfg);
        if (!$stCfg) {
            return false;
        }
        mysqli_stmt_bind_param($stCfg, 'isssi', $eventoId, $titulo, $mensaje, $imagen, $userIdSql);
        $okCfg = mysqli_stmt_execute($stCfg);
        mysqli_stmt_close($stCfg);
        if (!$okCfg) {
            return false;
        }

        return true;
    }
}
