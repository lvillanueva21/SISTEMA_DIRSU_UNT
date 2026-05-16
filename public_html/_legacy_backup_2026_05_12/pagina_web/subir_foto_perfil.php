<?php
require __DIR__.'/../inc/app_boot.php';
exigir_login();
header('Content-Type: application/json');

list($ok,$resp) = guardar_upload('archivo', 'perfil');  // guarda en almacen/perfil/YYYY/mm/dd
if (!$ok) { http_response_code(400); echo json_encode(array('status'=>'error','msg'=>$resp)); exit; }

$u = usuario_actual();
$stmt = $mysqli->prepare("UPDATE pc_usuarios SET foto_perfil=? WHERE id=?");
$stmt->bind_param('si', $resp, $u['id']);
$stmt->execute();

$_SESSION['pc_usuario']['foto'] = $resp;
echo json_encode(array('status'=>'ok','file'=>asset($resp))); // asset('almacen/…')
