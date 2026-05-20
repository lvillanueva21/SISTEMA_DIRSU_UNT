<?php
/**
 * Buffer global de warnings de notificación para la respuesta HTTP actual.
 */

if (!function_exists('rsu_eval_v1_notification_add_warning')) {
    function rsu_eval_v1_notification_add_warning($message)
    {
        $message = trim((string)$message);
        if ($message === '') {
            return;
        }
        if (!isset($GLOBALS['rsu_eval_v1_notification_warnings']) || !is_array($GLOBALS['rsu_eval_v1_notification_warnings'])) {
            $GLOBALS['rsu_eval_v1_notification_warnings'] = array();
        }
        $GLOBALS['rsu_eval_v1_notification_warnings'][] = $message;
    }
}

if (!function_exists('rsu_eval_v1_notification_get_warnings')) {
    function rsu_eval_v1_notification_get_warnings($clear = false)
    {
        $arr = array();
        if (isset($GLOBALS['rsu_eval_v1_notification_warnings']) && is_array($GLOBALS['rsu_eval_v1_notification_warnings'])) {
            $arr = $GLOBALS['rsu_eval_v1_notification_warnings'];
        }
        if ($clear) {
            $GLOBALS['rsu_eval_v1_notification_warnings'] = array();
        }
        return $arr;
    }
}
