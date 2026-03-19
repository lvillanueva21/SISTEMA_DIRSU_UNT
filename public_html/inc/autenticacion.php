<?php
// ===============================
//  Autenticación y permisos (pc_)
// ===============================
if (session_status() === PHP_SESSION_NONE) session_start();

/* ---------- Sesión ---------- */
function usuario_actual(){
  return isset($_SESSION['pc_usuario']) ? $_SESSION['pc_usuario'] : null;
}
function esta_logueado(){ return usuario_actual() !== null; }
function es_administrador(){
  $u = usuario_actual();
  return $u && isset($u['rol']) && $u['rol']==='administrador';
}
function exigir_login(){
  if (!esta_logueado()){
    $ret = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : url('index.php');
    header('Location: '.url('pagina_web/login.php?r=').rawurlencode($ret));
    exit;
  }
}

/* ---------- Permisos ---------- */
function id_menu_por_url($mysqli, $url){
  $stmt = $mysqli->prepare("SELECT id FROM cw_opciones_menu WHERE url=? LIMIT 1");
  $stmt->bind_param('s', $url);
  $stmt->execute();
  $stmt->bind_result($id);
  if ($stmt->fetch()) return (int)$id;
  return null;
}

function puede_editar_menu($mysqli, $id_menu){
  if (es_administrador()) return true;
  $u = usuario_actual();
  if (!$u) return false;

  $stmt = $mysqli->prepare("SELECT 1 FROM pc_permisos_paginas WHERE id_usuario=? AND id_menu=? AND puede_editar=1 LIMIT 1");
  $stmt->bind_param('ii', $u['id'], $id_menu);
  $stmt->execute();
  $stmt->store_result();
  return $stmt->num_rows > 0;
}

/* ---------- Login / Logout ---------- */
function iniciar_sesion($mysqli, $usuario, $clave){
  $stmt = $mysqli->prepare("
    SELECT id, usuario, clave_hash, nombres, apellidos, correo, rol, foto_perfil, activo
    FROM pc_usuarios
    WHERE usuario = ? LIMIT 1
  ");
  $stmt->bind_param('s',$usuario);
  $stmt->execute();
  $res = $stmt->get_result();
  if(!$row = $res->fetch_assoc()) return false;
  if((int)$row['activo'] !== 1) return false;

  // Soporta password_hash(); si tuvieras contraseñas antiguas md5: añade || md5($clave)===$row['clave_hash']
  if (!password_verify($clave, $row['clave_hash'])) return false;

  $_SESSION['pc_usuario'] = [
    'id'        => (int)$row['id'],
    'usuario'   => $row['usuario'],
    'nombres'   => $row['nombres'],
    'apellidos' => $row['apellidos'],
    'correo'    => $row['correo'],
    'rol'       => $row['rol'], // administrador | editor
    'foto'      => $row['foto_perfil']
  ];
  return true;
}

function cerrar_sesion(){
  $_SESSION = [];
  if (ini_get('session.use_cookies')) {
    $p = session_get_cookie_params();
    setcookie(session_name(), '', time()-42000, $p['path'],$p['domain'],$p['secure'],$p['httponly']);
  }
  session_destroy();
}
