<?php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

if (session_status() === PHP_SESSION_NONE) {
  // cookies de sesión más seguras (ajusta si usas http local sin https)
  session_set_cookie_params([
    'lifetime' => 0,
    'path' => '/',
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
  ]);
  session_start();
}
