<?php
/**
 * Helpers de respuesta JSON para API Dirsu.
 */

if (!function_exists('rsu_api_json_send')) {
    function rsu_api_json_send($status_code, $payload)
    {
        if (!headers_sent()) {
            http_response_code((int)$status_code);
            header('Content-Type: application/json; charset=UTF-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
            header('Pragma: no-cache');
        }

        $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($json === false) {
            $json = '{"ok":false,"message":"Error al serializar JSON","data":null}';
        }

        echo $json;
        exit;
    }
}

if (!function_exists('rsu_api_json_ok')) {
    function rsu_api_json_ok($data, $message, $meta)
    {
        if (!is_array($meta)) {
            $meta = array();
        }

        rsu_api_json_send(200, array(
            'ok' => true,
            'message' => (string)$message,
            'data' => $data,
            'meta' => $meta
        ));
    }
}

if (!function_exists('rsu_api_json_error')) {
    function rsu_api_json_error($status_code, $code, $message, $errors)
    {
        if (!is_array($errors)) {
            $errors = array();
        }

        rsu_api_json_send((int)$status_code, array(
            'ok' => false,
            'code' => (string)$code,
            'message' => (string)$message,
            'errors' => $errors,
            'data' => null
        ));
    }
}

