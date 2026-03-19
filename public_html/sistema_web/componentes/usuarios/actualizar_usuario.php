<?php
session_start();
include "../db.php";

header('Content-Type: application/json');

$id       = $_POST['id']       ?? null;
$usuario  = trim($_POST['usuario'] ?? '');
$nombres  = trim($_POST['nombres'] ?? '');
$apellidos= trim($_POST['apellidos'] ?? '');
$id_depa  = $_POST['id_depa']  ?? null;
$claveTxt = trim($_POST['clave'] ?? '');

if(!$id || !$usuario || !$nombres || !$apellidos){
    echo json_encode(['ok'=>false,'msg'=>'Datos incompletos']); exit;
}

$conexion->begin_transaction();
try{

   /* 1. verificar colisión de usuario */
   $sql="SELECT id FROM usuarios WHERE usuario=? AND id<>?";
   $st=$conexion->prepare($sql); $st->bind_param('si',$usuario,$id); $st->execute();
   if($st->get_result()->num_rows){ throw new Exception('El usuario ya existe'); }

   /* 2. construir UPDATE dinámico */
$set = "usuario=?, nombres=?, apellidos=?";
$params = [$usuario,$nombres,$apellidos];
$types  = "sss";

if($id_depa !== '') { // Solo si eligió departamento
    $set .= ", id_depa=?";
    $params[] = $id_depa;
    $types .= "i";
}

   if($claveTxt!==''){
        $hash = password_hash($claveTxt,PASSWORD_DEFAULT);
        $set .= ", clave=?";
        $params[]=$hash; $types.='s';
   }

   $sql="UPDATE usuarios SET $set WHERE id=?";  $params[]=$id; $types.='i';
   $st=$conexion->prepare($sql);
   $st->bind_param($types,...$params);
   if(!$st->execute()) throw new Exception('Error al actualizar usuario');

   /* 3. auditoría en historial_usuarios */
   $desc = [];
   if($claveTxt!==''){
        $desc[] = 'Actualización de contraseña por DIRSU';
        $sql="INSERT INTO historial_usuarios(descripcion,fecha,id_usuario,adicional)
              VALUES(?,NOW(),?,?)";
        $st=$conexion->prepare($sql);
        $st->bind_param('sis',$desc[0],$id,$claveTxt); $st->execute();
   }
   /* Cambio de usuario */
   $sql="SELECT usuario FROM usuarios WHERE id=?"; $st=$conexion->prepare($sql);
   $st->bind_param('i',$id); $st->execute(); $oldUser=$st->get_result()->fetch_assoc()['usuario'];
   if($oldUser!=$usuario){
        $msg = "Cambio de usuario: $oldUser ⇒ $usuario";
        $sql="INSERT INTO historial_usuarios(descripcion,fecha,id_usuario)
              VALUES(?,NOW(),?)";
        $st=$conexion->prepare($sql); $st->bind_param('si',$msg,$id); $st->execute();
   }

   $conexion->commit();
   echo json_encode(['ok'=>true]);

}catch(Exception $e){
   $conexion->rollback();
   echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
 