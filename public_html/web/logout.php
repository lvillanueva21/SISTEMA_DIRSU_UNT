<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/auth.php';

$redirect = $_GET['redirect'] ?? 'index.php?p=inicio';
if (!is_string($redirect) || $redirect === '') $redirect = 'index.php?p=inicio';

auth_logout();

header('Location: ' . $redirect);
exit;
