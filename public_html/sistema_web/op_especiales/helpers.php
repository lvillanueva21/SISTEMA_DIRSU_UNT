<?php

if (!function_exists('opesp_h')) {
    function opesp_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('opesp_fallback')) {
    function opesp_fallback($value, $fallback)
    {
        $text = trim((string)$value);
        return $text === '' ? (string)$fallback : $text;
    }
}

if (!function_exists('opesp_is_truthy')) {
    function opesp_is_truthy($value)
    {
        return (int)$value === 1;
    }
}
