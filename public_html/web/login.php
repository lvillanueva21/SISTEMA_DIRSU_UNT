<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/csrf.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  http_response_code(405);
  exit('Método no permitido.');
}

$redirect = $_POST['redirect'] ?? 'index.php?p=inicio';
if (!is_string($redirect) || $redirect === '') $redirect = 'index.php?p=inicio';

// Validar CSRF
if (!csrf_validate($_POST['csrf'] ?? null)) {
  flash_set('login_error', 'Sesión inválida. Recarga la página e intenta otra vez.');
  header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'login=1');
  exit;
}

$dni = (string)($_POST['dni'] ?? '');
$pass = (string)($_POST['password'] ?? '');

if (auth_login($dni, $pass)) {
  header('Location: ' . $redirect);
  exit;
}

flash_set('login_error', 'Usuario o contraseña incorrectos.');
header('Location: ' . $redirect . (str_contains($redirect, '?') ? '&' : '?') . 'login=1');
exit;
