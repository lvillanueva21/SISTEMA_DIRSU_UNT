<?php
// sistema_web/inicio/guardar_contacto.php
declare(strict_types=1);

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../componentes/db.php';

$usuario            = $_SESSION['usuario'] ?? '';
$idRol              = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null;

$nombres            = isset($_POST['nombres']) ? trim((string)$_POST['nombres']) : '';
$apellidos          = isset($_POST['apellidos']) ? trim((string)$_POST['apellidos']) : '';
$email              = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$telefono           = isset($_POST['telefono']) ? trim((string)$_POST['telefono']) : '';
$telefono_asistente = isset($_POST['telefono_asistente']) ? trim((string)$_POST['telefono_asistente']) : '';
$correo_asistente   = isset($_POST['correo_asistente']) ? trim((string)$_POST['correo_asistente']) : '';
$redirect           = $_POST['redirect'] ?? '/sistema_web/direccion_rsu/inicio.php';

function go($msg, $type='danger', $redirect='/sistema_web/direccion_rsu/inicio.php') {
  $_SESSION['contact_msg'] = $msg;
  $_SESSION['contact_msg_type'] = $type;
  if (!is_string($redirect) || !preg_match('#^(/|\.{1,2}/)#', $redirect)) {
    $redirect = '/sistema_web/direccion_rsu/inicio.php';
  }
  header("Location: {$redirect}");
  exit;
}

if ($usuario === '' || $idRol === null) { go('Sesión inválida. Vuelve a iniciar sesión.'); }

// MAYÚSCULA servidor (Unicode)
if (function_exists('mb_strtoupper')) {
  $nombres   = mb_strtoupper($nombres,   'UTF-8');
  $apellidos = mb_strtoupper($apellidos, 'UTF-8');
} else {
  $nombres   = strtoupper($nombres);
  $apellidos = strtoupper($apellidos);
}

// Validaciones
$email = strtolower($email);
if (!preg_match('/^[A-ZÁÉÍÓÚÜÑ\'\.\-\s]{2,100}$/u', $nombres))   { go('Nombres inválidos.'); }
if (!preg_match('/^[A-ZÁÉÍÓÚÜÑ\'\.\-\s]{2,100}$/u', $apellidos)) { go('Apellidos inválidos.'); }
if (!preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i', $email)) { go('El correo debe ser @unitru.edu.pe.'); }
if (!preg_match('/^9\d{8}$/', $telefono))                        { go('El teléfono debe tener 9 dígitos y empezar con 9.'); }
if ($telefono_asistente !== '' && !preg_match('/^9\d{8}$/', $telefono_asistente)) { go('Teléfono de asistente inválido.'); }
if ($correo_asistente !== '' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $correo_asistente)) { go('Correo de asistente inválido.'); }

// Tabla y rol válidos
$chk = $conexion->query("SHOW TABLES LIKE 'directorio'");
if (!$chk || $chk->num_rows === 0) { go('No existe la tabla directorio.'); }

$rolOk = false;
if ($stR = $conexion->prepare("SELECT 1 FROM rol WHERE id=? LIMIT 1")) {
  $stR->bind_param("i", $idRol);
  $stR->execute();
  $rolOk = (bool)$stR->get_result()->fetch_row();
  $stR->close();
}
if (!$rolOk) { go('Rol de sesión inválido.'); }

// UPSERT
$sql = "
  INSERT INTO directorio
    (usuario, id_rol, email, telefono, nombres, apellidos, telefono_asistente, correo_asistente, created_at, updated_at)
  VALUES
    (?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NOW(), NOW())
  ON DUPLICATE KEY UPDATE
    id_rol = VALUES(id_rol),
    email = VALUES(email),
    telefono = VALUES(telefono),
    nombres = VALUES(nombres),
    apellidos = VALUES(apellidos),
    telefono_asistente = VALUES(telefono_asistente),
    correo_asistente = VALUES(correo_asistente),
    updated_at = NOW()
";
if (!($st = $conexion->prepare($sql))) { go('Error interno (prep).'); }

$st->bind_param(
  "sissssss",
  $usuario,            // s
  $idRol,              // i
  $email,              // s
  $telefono,           // s
  $nombres,            // s (en MAYÚSCULA)
  $apellidos,          // s (en MAYÚSCULA)
  $telefono_asistente, // s
  $correo_asistente    // s
);
if (!$st->execute()) { $st->close(); go('Error al guardar contacto. Inténtalo otra vez.'); }
$st->close();

go('Contacto registrado/actualizado correctamente.', 'success', $redirect);
