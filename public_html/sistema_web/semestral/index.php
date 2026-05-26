<?php
/*
 * Bootstrap seguro para Informe Semestral/Final.
 * Objetivo: evitar HTTP 500 sin detalle y capturar fallos fatales/parsing
 * incluso cuando ocurran dentro de includes del flujo principal.
 */

$RSU_SEM_DEBUG = isset($_GET['debug']) || isset($_GET['DEBUG']);

ini_set('log_errors', '1');
if ($RSU_SEM_DEBUG) {
    ini_set('display_errors', '1');
    ini_set('display_startup_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    ini_set('display_startup_errors', '0');
    error_reporting(E_ALL);
}

if (!defined('RSU_SEM_BOOT_LOG_FILE')) {
    define('RSU_SEM_BOOT_LOG_FILE', __DIR__ . '/../includes/logs/rsu_semestral_bootstrap.log');
}

if (!function_exists('rsu_sem_boot_log')) {
    function rsu_sem_boot_log($label, $payload)
    {
        $line = date('Y-m-d H:i:s') . ' [' . (string)$label . '] ';
        if (is_scalar($payload) || $payload === null) {
            $line .= (string)$payload;
        } else {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $line .= ($json === false) ? '[json_encode_error]' : $json;
        }

        $dir = dirname(RSU_SEM_BOOT_LOG_FILE);
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents(RSU_SEM_BOOT_LOG_FILE, $line . PHP_EOL, FILE_APPEND);
    }
}

if (!function_exists('rsu_sem_boot_render_fatal_modal')) {
    function rsu_sem_boot_render_fatal_modal($title, $detail, $statusCode)
    {
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            http_response_code((int)$statusCode);
            header('Content-Type: text/html; charset=UTF-8');
        }

        $safeTitle = htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');
        $json = json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"error":"No se pudo serializar el detalle"}';
        }
        $safeJson = htmlspecialchars($json, ENT_QUOTES, 'UTF-8');

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Error del sistema - Informe</title>';
        echo '<style>
          body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;color:#1f2937;}
          .overlay{position:fixed;inset:0;background:rgba(0,0,0,.42);display:flex;align-items:center;justify-content:center;padding:16px;}
          .modal{width:min(980px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:10px;box-shadow:0 16px 42px rgba(0,0,0,.26);}
          .head{padding:14px 16px;background:#b91c1c;color:#fff;font-weight:700;font-size:18px;}
          .body{padding:16px;}
          .hint{margin:0 0 10px 0;color:#4b5563;}
          textarea{width:100%;min-height:420px;border:1px solid #d1d5db;border-radius:8px;padding:10px;box-sizing:border-box;font-family:Consolas,monospace;font-size:13px;line-height:1.42;}
          .actions{margin-top:10px;display:flex;gap:8px;flex-wrap:wrap;}
          .btn{border:0;border-radius:6px;padding:8px 12px;background:#0d6efd;color:#fff;font-weight:600;cursor:pointer;}
          .btn.secondary{background:#6b7280;}
        </style></head><body>';
        echo '<div class="overlay"><div class="modal" role="dialog" aria-modal="true">';
        echo '<div class="head">' . $safeTitle . '</div><div class="body">';
        echo '<p class="hint">Copia este detalle completo y compártelo para soporte técnico.</p>';
        echo '<textarea id="rsuSemBootDiag" readonly>' . $safeJson . '</textarea>';
        echo '<div class="actions">';
        echo '<button class="btn" type="button" id="rsuSemBootCopy">Copiar detalle</button>';
        echo '<button class="btn secondary" type="button" onclick="location.reload()">Recargar</button>';
        echo '</div></div></div></div>';
        echo '<script>(function(){var b=document.getElementById("rsuSemBootCopy"),t=document.getElementById("rsuSemBootDiag");if(!b||!t){return;}b.addEventListener("click",function(){t.focus();t.select();t.setSelectionRange(0,t.value.length);var ok=false;try{ok=document.execCommand("copy");}catch(e){ok=false;}b.textContent=ok?"Copiado":"No se pudo copiar";});})();</script>';
        echo '</body></html>';
        exit;
    }
}

if (!function_exists('rsu_sem_boot_shutdown_handler')) {
    function rsu_sem_boot_shutdown_handler()
    {
        $e = error_get_last();
        if (!$e || !isset($e['type'])) {
            return;
        }

        $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
        if (!in_array((int)$e['type'], $fatalTypes, true)) {
            return;
        }

        $detail = array(
            'kind' => 'shutdown_fatal',
            'type' => (int)$e['type'],
            'message' => isset($e['message']) ? (string)$e['message'] : '',
            'file' => isset($e['file']) ? (string)$e['file'] : '',
            'line' => isset($e['line']) ? (int)$e['line'] : 0,
            'php_version' => PHP_VERSION,
            'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
            'script_name' => isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '',
            'script_filename' => isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : ''
        );

        rsu_sem_boot_log('fatal', $detail);
        rsu_sem_boot_render_fatal_modal('Error interno detallado (Bootstrap Informe)', $detail, 500);
    }
}

register_shutdown_function('rsu_sem_boot_shutdown_handler');

rsu_sem_boot_log('start', array(
    'php_version' => PHP_VERSION,
    'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
    'script_filename' => isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : ''
));

$appFile = __DIR__ . '/index_app.php';
if (!file_exists($appFile)) {
    $detail = array(
        'kind' => 'missing_app_file',
        'file' => $appFile,
        'php_version' => PHP_VERSION,
        'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : ''
    );
    rsu_sem_boot_log('missing', $detail);
    rsu_sem_boot_render_fatal_modal('Archivo principal no encontrado', $detail, 500);
}

include $appFile;