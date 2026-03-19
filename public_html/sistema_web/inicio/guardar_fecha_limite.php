<?php
// sistema_web/inicio/guardar_fecha_limite.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../componentes/db.php';

$idRol   = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null;
$usuario = $_SESSION['usuario'] ?? '';

function back_to($redirect = '/sistema_web/direccion_rsu/inicio.php', $msg = null, $type = 'success') {
  if ($msg !== null) {
    $_SESSION['dl_msg'] = $msg;
    $_SESSION['dl_msg_type'] = $type;
  }
  // Sanitizar redirect: solo rutas internas
  if (!is_string($redirect) || !preg_match('#^(/|\.{1,2}/)#', $redirect)) {
    $redirect = '/sistema_web/direccion_rsu/inicio.php';
  }
  header("Location: {$redirect}");
  exit;
}

if ($idRol !== 1) {
  back_to($_POST['redirect'] ?? null, 'No autorizado.', 'danger');
}

if (($_POST['dl_action'] ?? '') !== 'save_deadline') {
  back_to($_POST['redirect'] ?? null, 'Acción inválida.', 'danger');
}

// CSRF
$csrf = (string)($_POST['csrf'] ?? '');
if (!isset($_SESSION['csrf_deadline']) || !hash_equals($_SESSION['csrf_deadline'], $csrf)) {
  back_to($_POST['redirect'] ?? null, 'Token inválido. Recarga la página e inténtalo otra vez.', 'danger');
}

// Campos
$titulo  = trim((string)($_POST['titulo']  ?? ''));
$mensaje = trim((string)($_POST['mensaje'] ?? ''));
$fecha   = trim((string)($_POST['fecha']   ?? '')); // YYYY-MM-DD
$hora    = trim((string)($_POST['hora']    ?? '')); // HH:MM[:SS]
$redirect= (string)($_POST['redirect']     ?? '/sistema_web/direccion_rsu/inicio.php');

// Validaciones
if ($titulo === '')                                  back_to($redirect, 'El título es obligatorio.', 'danger');
if ($fecha === '' || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha))
                                                     back_to($redirect, 'Fecha inválida.', 'danger');
if ($hora  === '' || !preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora))
                                                     back_to($redirect, 'Hora inválida.', 'danger');

if (strlen($hora) === 5) { $hora .= ':00'; }
$deadline = $fecha.' '.$hora; // hora local Lima

$sql = "INSERT INTO inicio_fecha_limite (id, titulo, mensaje, deadline, updated_by, updated_at)
        VALUES (1, ?, ?, ?, ?, NOW())
        ON DUPLICATE KEY UPDATE
          titulo=VALUES(titulo),
          mensaje=VALUES(mensaje),
          deadline=VALUES(deadline),
          updated_by=VALUES(updated_by),
          updated_at=NOW()";

if (!($st = $conexion->prepare($sql))) {
  back_to($redirect, 'Error interno (prepare).', 'danger');
}
$st->bind_param('ssss', $titulo, $mensaje, $deadline, $usuario);
if (!$st->execute()) {
  $st->close();
  back_to($redirect, 'No se pudo guardar (DB).', 'danger');
}
$st->close();

back_to($redirect, 'Fecha límite guardada correctamente.', 'success');
