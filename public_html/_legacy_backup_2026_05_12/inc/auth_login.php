<?php
require_once __DIR__.'/../inc/app_boot.php';
header('Content-Type: application/json; charset=UTF-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  echo json_encode(['status'=>'error','msg'=>'Método no permitido']); exit;
}

$usuario = trim($_POST['usuario'] ?? '');
$clave   = (string)($_POST['clave'] ?? '');

if ($usuario === '' || $clave === '') {
  echo json_encode(['status'=>'error','msg'=>'Usuario y contraseña son obligatorios']); exit;
}

$stmt = $mysqli->prepare("SELECT id, usuario, clave_hash, nombres, apellidos, correo, rol, foto_perfil, activo FROM pc_usuarios WHERE usuario = ? LIMIT 1");
$stmt->bind_param('s', $usuario);
$stmt->execute();
$res = $stmt->get_result();
$row = $res->fetch_assoc();
$stmt->close();

if (!$row || (int)$row['activo'] !== 1) {
  echo json_encode(['status'=>'error','msg'=>'Usuario no encontrado o inactivo']); exit;
}

if (!password_verify($clave, $row['clave_hash'])) {
  echo json_encode(['status'=>'error','msg'=>'Contraseña incorrecta']); exit;
}

// Guarda sesión mínima
$_SESSION['pc_usuario'] = [
  'id'          => (int)$row['id'],
  'usuario'     => $row['usuario'],
  'nombres'     => $row['nombres'],
  'apellidos'   => $row['apellidos'],
  'rol'         => $row['rol'],
  'foto_perfil' => $row['foto_perfil'] ?: null
];

echo json_encode(['status'=>'ok']);
