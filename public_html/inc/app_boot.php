<?php
define('APP_ROOT', realpath(__DIR__ . '/..'));
define('BASE_URL', '/'); // ajusta si tu sitio vive bajo una subcarpeta

function url(string $path=''): string {
  return rtrim(BASE_URL,'/').'/'.ltrim($path,'/');
}
function asset(string $path=''): string {
  return url($path);
}

require_once APP_ROOT.'/cw_config.php'; // expone $mysqli

date_default_timezone_set('America/Lima');
if (isset($mysqli) && $mysqli instanceof mysqli) {
  $mysqli->set_charset('utf8mb4');
}

// --- SESIONES ---
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}

// --- HELPERS DE SESIÓN ---
function usuario_actual(): ?array {
  return $_SESSION['pc_usuario'] ?? null;
}
function es_admin(): bool {
  $u = usuario_actual();
  return $u && $u['rol'] === 'administrador';
}
function asegurar_login(): void {
  if (!usuario_actual()) {
    header('Location: '.url('index.php'));
    exit;
  }
}

// --- UTIL para resaltar menú activo ---
function is_active(string $itemUrl): bool {
  $curr = parse_url($_SERVER['REQUEST_URI']??'/', PHP_URL_PATH) ?? '/';
  $item = parse_url($itemUrl, PHP_URL_PATH) ?? $itemUrl;
  $norm = function($p){ $p=rtrim($p,'/'); return $p===''?'/':$p; };
  return $norm($curr) === $norm($item);
}

// --- UTILIDADES DE ARCHIVOS (almacen/...) ---
require_once APP_ROOT.'/inc/archivos.php';
