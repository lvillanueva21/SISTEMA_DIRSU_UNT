<?php
// sistema_web/inicio/guardar_contacto.php

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}
date_default_timezone_set('America/Lima');

require_once __DIR__ . '/../includes/db_connection.php';

$defaultRedirect = '../direccion_rsu/inicio.php';

function go_contacto($msg, $type, $redirect, $defaultRedirect)
{
    $_SESSION['contact_msg'] = $msg;
    $_SESSION['contact_msg_type'] = $type;

    $target = $redirect;
    if (!is_string($target) || !preg_match('#^(/|\.{1,2}/)#', $target)) {
        $target = $defaultRedirect;
    }

    header('Location: ' . $target);
    exit;
}

$usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
$idRol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;

$nombres = isset($_POST['nombres']) ? trim((string)$_POST['nombres']) : '';
$apellidos = isset($_POST['apellidos']) ? trim((string)$_POST['apellidos']) : '';
$email = isset($_POST['email']) ? trim((string)$_POST['email']) : '';
$telefono = isset($_POST['telefono']) ? trim((string)$_POST['telefono']) : '';
$telefonoAsistente = isset($_POST['telefono_asistente']) ? trim((string)$_POST['telefono_asistente']) : '';
$correoAsistente = isset($_POST['correo_asistente']) ? trim((string)$_POST['correo_asistente']) : '';
$redirect = isset($_POST['redirect']) ? (string)$_POST['redirect'] : $defaultRedirect;

if ($usuario === '' || $idRol <= 0) {
    go_contacto('Sesion invalida. Vuelve a iniciar sesion.', 'danger', $redirect, $defaultRedirect);
}

$conexion = rsu_db_connect();
if (!($conexion instanceof mysqli)) {
    error_log('guardar_contacto: conexion no disponible');
    go_contacto('No se pudo conectar con la base de datos.', 'danger', $redirect, $defaultRedirect);
}
@mysqli_set_charset($conexion, 'utf8mb4');

if (function_exists('mb_strtoupper')) {
    $nombres = mb_strtoupper($nombres, 'UTF-8');
    $apellidos = mb_strtoupper($apellidos, 'UTF-8');
} else {
    $nombres = strtoupper($nombres);
    $apellidos = strtoupper($apellidos);
}

$email = strtolower($email);

if (!preg_match('/^[A-Z\x{00C1}\x{00C9}\x{00CD}\x{00D3}\x{00DA}\x{00DC}\x{00D1}\'\.\-\s]{2,100}$/u', $nombres)) {
    go_contacto('Nombres invalidos.', 'danger', $redirect, $defaultRedirect);
}
if (!preg_match('/^[A-Z\x{00C1}\x{00C9}\x{00CD}\x{00D3}\x{00DA}\x{00DC}\x{00D1}\'\.\-\s]{2,100}$/u', $apellidos)) {
    go_contacto('Apellidos invalidos.', 'danger', $redirect, $defaultRedirect);
}
if (!preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i', $email)) {
    go_contacto('El correo debe ser @unitru.edu.pe.', 'danger', $redirect, $defaultRedirect);
}
if (!preg_match('/^9\d{8}$/', $telefono)) {
    go_contacto('El telefono debe tener 9 digitos y empezar con 9.', 'danger', $redirect, $defaultRedirect);
}
if ($telefonoAsistente !== '' && !preg_match('/^9\d{8}$/', $telefonoAsistente)) {
    go_contacto('Telefono de asistente invalido.', 'danger', $redirect, $defaultRedirect);
}
if ($correoAsistente !== '' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $correoAsistente)) {
    go_contacto('Correo de asistente invalido.', 'danger', $redirect, $defaultRedirect);
}

$chk = mysqli_query($conexion, "SHOW TABLES LIKE 'directorio'");
if (!($chk instanceof mysqli_result) || $chk->num_rows === 0) {
    go_contacto('No existe la tabla directorio.', 'danger', $redirect, $defaultRedirect);
}
mysqli_free_result($chk);

$rolOk = false;
$stRol = mysqli_prepare($conexion, 'SELECT 1 FROM rol WHERE id = ? LIMIT 1');
if ($stRol) {
    mysqli_stmt_bind_param($stRol, 'i', $idRol);
    if (mysqli_stmt_execute($stRol)) {
        mysqli_stmt_bind_result($stRol, $dummy);
        if (mysqli_stmt_fetch($stRol)) {
            $rolOk = true;
        }
    }
    mysqli_stmt_close($stRol);
}
if (!$rolOk) {
    go_contacto('Rol de sesion invalido.', 'danger', $redirect, $defaultRedirect);
}

$sql = "
  INSERT INTO directorio
    (usuario, id_rol, email, telefono, nombres, apellidos, telefono_asistente, correo_asistente, created_at, updated_at)
  VALUES
    (?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
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
$st = mysqli_prepare($conexion, $sql);
if (!$st) {
    error_log('guardar_contacto: prepare upsert fallo: ' . mysqli_error($conexion));
    go_contacto('Error interno al preparar guardado.', 'danger', $redirect, $defaultRedirect);
}

$telAsistDb = ($telefonoAsistente !== '') ? $telefonoAsistente : null;
$mailAsistDb = ($correoAsistente !== '') ? $correoAsistente : null;

mysqli_stmt_bind_param(
    $st,
    'sissssss',
    $usuario,
    $idRol,
    $email,
    $telefono,
    $nombres,
    $apellidos,
    $telAsistDb,
    $mailAsistDb
);

if (!mysqli_stmt_execute($st)) {
    $err = mysqli_stmt_error($st);
    mysqli_stmt_close($st);
    error_log('guardar_contacto: execute upsert fallo: ' . $err);
    go_contacto('Error al guardar contacto. Intentalo otra vez.', 'danger', $redirect, $defaultRedirect);
}
mysqli_stmt_close($st);

go_contacto('Contacto registrado/actualizado correctamente.', 'success', $redirect, $defaultRedirect);