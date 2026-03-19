<?php
declare(strict_types=1);

// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
  http_response_code(403);
  exit('Acceso directo no permitido.');
}

$cfg = require __DIR__ . '/config.php';

// Zona horaria fija (Perú)
date_default_timezone_set($cfg['app']['timezone'] ?? 'America/Lima');

// Errores mysqli
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

try {
  $mysqli = new mysqli(
    $cfg['db']['host'],
    $cfg['db']['user'],
    $cfg['db']['pass'],
    $cfg['db']['name'],
    (int)$cfg['db']['port']
  );

  $mysqli->set_charset($cfg['db']['charset'] ?? 'utf8mb4');

  // Zona horaria de MySQL para ESTA conexión
  try {
    $mysqli->query("SET time_zone = 'America/Lima'");
  } catch (Throwable $e1) {
    try {
      $mysqli->query("SET time_zone = '-05:00'");
    } catch (Throwable $e2) { /* continuar */ }
  }
} catch (Throwable $e) {
  http_response_code(500);
  exit('Error de conexión a la base de datos.');
}

function db(): mysqli {
  global $mysqli;
  return $mysqli;
}
