<?php
/**
 * Diagnostico de errores fatales para soporte.
 * Muestra una pantalla HTML con modal copiable cuando ocurre un fatal.
 */

if (!defined('RSU_DIAG_LOADED')) {
    define('RSU_DIAG_LOADED', 1);

    if (!isset($GLOBALS['RSU_DIAG_CONTEXT']) || !is_array($GLOBALS['RSU_DIAG_CONTEXT'])) {
        $GLOBALS['RSU_DIAG_CONTEXT'] = array();
    }

    function rsu_diag_context($key, $value)
    {
        $GLOBALS['RSU_DIAG_CONTEXT'][(string)$key] = $value;
    }

    function rsu_diag_is_enabled()
    {
        return false;
    }

    function rsu_diag_build_text($error)
    {
        $lines = array();
        $lines[] = 'RSU DIAG';
        $lines[] = 'Fecha: ' . date('Y-m-d H:i:s');
        $lines[] = 'PHP_VERSION: ' . PHP_VERSION;
        $lines[] = 'SCRIPT_NAME: ' . (isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '');
        $lines[] = 'SCRIPT_FILENAME: ' . (isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '');
        $lines[] = 'REQUEST_URI: ' . (isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : '');
        $lines[] = 'METHOD: ' . (isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : '');
        $lines[] = '';
        $lines[] = 'ERROR_TYPE: ' . (isset($error['type']) ? $error['type'] : '');
        $lines[] = 'ERROR_MESSAGE: ' . (isset($error['message']) ? $error['message'] : '');
        $lines[] = 'ERROR_FILE: ' . (isset($error['file']) ? $error['file'] : '');
        $lines[] = 'ERROR_LINE: ' . (isset($error['line']) ? $error['line'] : '');
        $lines[] = '';
        $lines[] = 'CONTEXT:';

        foreach ($GLOBALS['RSU_DIAG_CONTEXT'] as $k => $v) {
            if (is_scalar($v) || $v === null) {
                $lines[] = '- ' . $k . ': ' . (string)$v;
            } else {
                $lines[] = '- ' . $k . ': ' . json_encode($v);
            }
        }

        return implode("\n", $lines);
    }

    function rsu_diag_render_modal($diagText)
    {
        $safe = htmlspecialchars($diagText, ENT_QUOTES, 'UTF-8');
        while (ob_get_level() > 0) {
            @ob_end_clean();
        }

        if (!headers_sent()) {
            // Forzar salida visible en lugar de pagina 500 generica del servidor.
            http_response_code(200);
            header('Content-Type: text/html; charset=UTF-8');
        }

        echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
        echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
        echo '<title>Diagnóstico del sistema</title>';
        echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">';
        echo '</head><body style="background:#f4f6f9;">';
        echo '<div class="container py-5">';
        echo '  <div class="alert alert-danger"><strong>Error interno detectado.</strong> Copia el detalle y compártelo con soporte.</div>';
        echo '  <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#diagModal">Ver detalle técnico</button>';
        echo '</div>';

        echo '<div class="modal fade show" id="diagModal" tabindex="-1" role="dialog" aria-modal="true" style="display:block; background:rgba(0,0,0,.45);">';
        echo '  <div class="modal-dialog modal-lg" role="document">';
        echo '    <div class="modal-content">';
        echo '      <div class="modal-header bg-danger text-white">';
        echo '        <h5 class="modal-title">Detalle técnico del error</h5>';
        echo '      </div>';
        echo '      <div class="modal-body">';
        echo '        <textarea id="diagText" class="form-control" rows="16" readonly>' . $safe . '</textarea>';
        echo '        <div class="mt-2"><small>Use el botón copiar y pégame el contenido completo.</small></div>';
        echo '      </div>';
        echo '      <div class="modal-footer">';
        echo '        <button type="button" class="btn btn-success" id="btnCopyDiag">Copiar detalle</button>';
        echo '      </div>';
        echo '    </div>';
        echo '  </div>';
        echo '</div>';

        echo '<script>';
        echo 'document.getElementById("btnCopyDiag").addEventListener("click", function(){';
        echo ' var t=document.getElementById("diagText"); t.select(); t.setSelectionRange(0,999999);';
        echo ' try{document.execCommand("copy"); this.textContent="Copiado";}catch(e){this.textContent="No se pudo copiar";}';
        echo '});';
        echo '</script>';
        echo '</body></html>';
    }

    function rsu_diag_log_text($text)
    {
        $dir = __DIR__ . '/logs';
        $file = $dir . '/rsu_diag_runtime.log';
        if (!is_dir($dir)) {
            @mkdir($dir, 0755, true);
        }
        @file_put_contents($file, $text . "\n\n-----------------------\n\n", FILE_APPEND);
        @error_log($text);
    }

    function rsu_diag_shutdown_handler()
    {
        if (!rsu_diag_is_enabled()) {
            return;
        }

        $error = error_get_last();
        if (!$error || !isset($error['type'])) {
            return;
        }

        $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
        if (!in_array((int)$error['type'], $fatalTypes, true)) {
            return;
        }

        $diagText = rsu_diag_build_text($error);
        rsu_diag_log_text($diagText);
        rsu_diag_render_modal($diagText);
    }

    if (rsu_diag_is_enabled()) {
        register_shutdown_function('rsu_diag_shutdown_handler');
    }
}
