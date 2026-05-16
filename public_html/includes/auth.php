<?php
// includes/auth.php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/conexion.php';

function auth_user() {
  return isset($_SESSION['auth_user']) ? $_SESSION['auth_user'] : null;
}

function auth_check() {
  return !empty($_SESSION['auth_user']);
}

function auth_login($dni, $password) {
  $dni = trim((string)$dni);
  $password = (string)$password;

  if (!preg_match('/^\d{8}$/', $dni)) return false;

  $mysqli = db();

  $sql = "SELECT u.id, u.nombres, u.apellidos, u.dni, u.clave_hash, u.foto_perfil, u.estado,
                 u.rol_id, r.codigo AS rol_codigo, r.nombre AS rol_nombre
          FROM l2601_usuarios u
          LEFT JOIN l2601_roles r ON r.id = u.rol_id
          WHERE u.dni = ?
          LIMIT 1";

  $st = $mysqli->prepare($sql);
  $st->bind_param('s', $dni);
  $st->execute();
  $res = $st->get_result();
  $row = $res->fetch_assoc();
  $st->close();

  if (!$row) return false;
  if ((int)$row['estado'] !== 1) return false;

  if (!password_verify($password, (string)$row['clave_hash'])) return false;

  if (function_exists('session_regenerate_id')) {
    session_regenerate_id(true);
  }

  $_SESSION['auth_user'] = array(
    'id' => (int)$row['id'],
    'dni' => (string)$row['dni'],
    'nombres' => (string)$row['nombres'],
    'apellidos' => (string)$row['apellidos'],
    'foto_perfil' => $row['foto_perfil'] ? (string)$row['foto_perfil'] : null,
    'rol' => array(
      'id' => (int)$row['rol_id'],
      'codigo' => $row['rol_codigo'] ? (string)$row['rol_codigo'] : '',
      'nombre' => $row['rol_nombre'] ? (string)$row['rol_nombre'] : '',
    ),
  );

  return true;
}

function auth_logout() {
  unset($_SESSION['auth_user']);
}

function flash_set($key, $msg) {
  if (!isset($_SESSION['flash'])) $_SESSION['flash'] = array();
  $_SESSION['flash'][$key] = (string)$msg;
}

function flash_get($key) {
  if (!isset($_SESSION['flash'][$key])) return null;
  $msg = $_SESSION['flash'][$key];
  unset($_SESSION['flash'][$key]);
  return is_string($msg) ? $msg : null;
}
