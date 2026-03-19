<?php
require __DIR__.'/inc/app_boot.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit; }

$usuario = trim(isset($_POST['usuario']) ? $_POST['usuario'] : '');
$clave   = (string)(isset($_POST['clave']) ? $_POST['clave'] : '');

$stmt = $mysqli->prepare("SELECT id, usuario, clave_hash, nombres, apellidos, rol, activo, foto_perfil
                          FROM pc_usuarios WHERE usuario=? LIMIT 1");
$stmt->bind_param('s', $usuario);
$stmt->execute();
$u = $stmt->get_result()->fetch_assoc();

if (!$u || !$u['activo'] || !password_verify($clave, $u['clave_hash'])) {
  http_response_code(401);
  header('Content-Type: application/json');
  echo json_encode(array('status'=>'error','msg'=>'Credenciales inválidas'));
  exit;
}

session_regenerate_id(true);
$_SESSION['pc_usuario'] = array(
  'id'        => (int)$u['id'],
  'usuario'   => $u['usuario'],
  'nombres'   => $u['nombres'],
  'apellidos' => $u['apellidos'],
  'rol'       => $u['rol'],
  'foto'      => $u['foto_perfil']
);

header('Content-Type: application/json');
echo json_encode(array('status'=>'ok'));
