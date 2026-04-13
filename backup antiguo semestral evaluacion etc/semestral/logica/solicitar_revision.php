<?php
// semestral/logica/solicitar_revision.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('America/Lima');

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

register_shutdown_function(function () {
  $e = error_get_last();
  if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
    if (!headers_sent()) {
      http_response_code(500);
      header('Content-Type: application/json; charset=UTF-8');
    }
    while (ob_get_level() > 0) { ob_end_clean(); }
    echo json_encode(['status'=>'error','msg'=>'Error interno del servidor.']);
  }
});
function jfail(int $code, string $msg){ if(!headers_sent()){http_response_code($code);header('Content-Type: application/json; charset=UTF-8');} while (ob_get_level()>0){ob_end_clean();} echo json_encode(['status'=>'error','msg'=>$msg]); exit; }
function jok(array $extra=[]){ if(!headers_sent()){http_response_code(200);header('Content-Type: application/json; charset=UTF-8');} while (ob_get_level()>0){ob_end_clean();} echo json_encode(array_merge(['status'=>'ok'],$extra)); exit; }

require_once __DIR__ . '/../../componentes/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
$base = realpath(__DIR__ . '/../../recursos/src') ?: (__DIR__ . '/../../recursos/src');
foreach ([$base.'/PHPMailer.php',$base.'/SMTP.php',$base.'/Exception.php'] as $p){ if(!file_exists($p)) jfail(500,'PHPMailer no encontrado: '.$p); }
require_once $base.'/Exception.php';
require_once $base.'/PHPMailer.php';
require_once $base.'/SMTP.php';

// -------- Entrada --------
$id_respuesta = (int)($_POST['id_respuesta'] ?? 0);
$proy_titulo  = trim((string)($_POST['proy_titulo'] ?? ''));
$form_nombre  = trim((string)($_POST['form_nombre'] ?? ''));
if ($id_respuesta<=0) jfail(400,'ID de respuesta inválido');
if (empty($_SESSION['usuario'])) jfail(401,'Sesión inválida');

$usuarioLogin = $_SESSION['usuario'];

// Email del usuario
$email = null;
$stE = $conexion->prepare("SELECT email FROM usuario_contactos WHERE usuario=? LIMIT 1");
$stE->bind_param("s",$usuarioLogin); $stE->execute();
if ($r=$stE->get_result()->fetch_assoc()) $email=trim((string)$r['email']);
$stE->close();
if(!$email || !filter_var($email,FILTER_VALIDATE_EMAIL)) jfail(400,'No se encontró un email válido para el usuario actual');

// Respuesta
$stR = $conexion->prepare("SELECT id, id_py, id_formulario, id_cronograma, estado FROM sm_respuestas WHERE id=? LIMIT 1");
$stR->bind_param("i",$id_respuesta); $stR->execute();
$resp = $stR->get_result()->fetch_assoc();
$stR->close();
if(!$resp) jfail(404,'Respuesta no encontrada');

// Helpers locales
function table_exists(mysqli $cx, string $t): bool {
  $q="SELECT 1 FROM information_schema.tables WHERE table_schema=DATABASE() AND table_name=? LIMIT 1";
  $st=$cx->prepare($q); $st->bind_param('s',$t); $st->execute();
  $ok=(bool)$st->get_result()->fetch_row(); $st->close(); return $ok;
}
function upsert_revisiones_no_forzar_oficina(mysqli $cx, int $idRespuesta): void {
  if (!table_exists($cx,'ev_revisiones')) throw new RuntimeException("Tabla requerida no existe: ev_revisiones");
  // Si no existe, crea en PCF; si existe, CONSERVA oficina_actual y solo fija fecha_solicitud si era NULL
  $sql="
    INSERT INTO ev_revisiones (id_respuesta, oficina_actual, estado, fecha_solicitud)
    VALUES (?, 'pcf', 'editable', NOW())
    ON DUPLICATE KEY UPDATE
      oficina_actual = ev_revisiones.oficina_actual,
      estado         = 'editable',
      fecha_solicitud= COALESCE(ev_revisiones.fecha_solicitud, VALUES(fecha_solicitud))
  ";
  $st=$cx->prepare($sql);
  $st->bind_param('i',$idRespuesta);
  $st->execute();
  $st->close();
}
function ensure_evaluaciones_para_oficina(mysqli $cx, int $idRespuesta, string $oficina): void {
  if (!table_exists($cx,'ev_evaluaciones')) throw new RuntimeException("Tabla requerida no existe: ev_evaluaciones");

  // Qué tipos se requieren por oficina
  $tipos = ($oficina==='pcf' || $oficina==='rsu') ? ['cotejo','rubrica'] : ['vb'];

  foreach($tipos as $t){
    $existe=0;
    $chk=$cx->prepare("SELECT 1 FROM ev_evaluaciones WHERE id_respuesta=? AND oficina=? AND tipo=? LIMIT 1");
    $chk->bind_param('iss',$idRespuesta,$oficina,$t); $chk->execute();
    $existe=(int)(bool)$chk->get_result()->fetch_row(); $chk->close();

    if(!$existe){
      $ins=$cx->prepare("INSERT INTO ev_evaluaciones (id_respuesta, oficina, tipo, estado, fecha_inicio) VALUES (?,?,?,?,NOW())");
      $estado='en_espera';
      $ins->bind_param('isss',$idRespuesta,$oficina,$t,$estado);
      $ins->execute(); $ins->close();
    }
  }
}
function reactivar_si_venia_observado(mysqli $cx, int $idRespuesta, string $oficina): void {
  // Si la respuesta venía de observado (estado=3), re-habilitamos las evaluaciones en esa oficina:
  // estado -> en_espera, limpiamos fecha_evaluacion; conservas el histórico en ev_historial.
  $tipos = ($oficina==='pcf' || $oficina==='rsu') ? ['cotejo','rubrica'] : ['vb'];
  $in = implode("','",$tipos);
  $sql="UPDATE ev_evaluaciones
           SET estado='en_espera', fecha_evaluacion=NULL, fecha_inicio=COALESCE(fecha_inicio,NOW())
         WHERE id_respuesta=? AND oficina=? AND tipo IN ('{$in}') AND estado='observado'";
  $st=$cx->prepare($sql);
  $st->bind_param('is',$idRespuesta,$oficina);
  $st->execute(); $st->close();
}

// TRANSACCIÓN
$conexion->begin_transaction();
try {
  // 1) Subir a EN REVISIÓN (1) si venía de 0 o 3
  $stU = $conexion->prepare("
    UPDATE sm_respuestas
       SET estado = 1, actualizado_at = NOW()
     WHERE id=? AND estado IN (0,3)
  ");
  $stU->bind_param("i",$id_respuesta); $stU->execute();
  $af = $stU->affected_rows; $stU->close();

  if ($af<=0 && (int)$resp['estado']!==1) {
    throw new Exception('La respuesta ya fue enviada o aprobada. (Estado actual: '.$resp['estado'].')');
  }

  // 2) Revisiones (no forzar oficina si ya existe)
  upsert_revisiones_no_forzar_oficina($conexion, $id_respuesta);

  // 3) ¿En qué oficina está ahora?
  $oficina='pcf';
  $rs=$conexion->query("SELECT oficina_actual FROM ev_revisiones WHERE id_respuesta=".(int)$id_respuesta." LIMIT 1");
  if($rs && ($row=$rs->fetch_assoc()) && !empty($row['oficina_actual'])) { $oficina=$row['oficina_actual']; }

  // 4) Asegurar evaluaciones requeridas para ESA oficina
  ensure_evaluaciones_para_oficina($conexion, $id_respuesta, $oficina);

  // 5) Si venía de OBSERVADO, re-activar esas evaluaciones (sin borrar nada)
  if ((int)$resp['estado']===3) {
    reactivar_si_venia_observado($conexion, $id_respuesta, $oficina);
  }

  $conexion->commit();

} catch (Throwable $e) {
  $conexion->rollback();
  jfail(409,$e->getMessage());
}

// Enviar correo (igual que tenías)
$fecha = date('d/m/Y'); $hora  = date('H:i');
$asunto = 'Solicitud de Revisión de Informe — '.($form_nombre ?: 'Formulario');
$cuerpo = "Se ha solicitado la Revisión a DIRSU del proyecto \"{$proy_titulo}\" ".
          "del formulario \"{$form_nombre}\" el día {$fecha} a las {$hora} (Lima-Perú).";

$mail = new PHPMailer(true);
try{
  $mail->isSMTP();
  $mail->Host='smtp.gmail.com'; $mail->SMTPAuth=true;
  $mail->Username='proyectosdirsu@unitru.edu.pe'; $mail->Password='owmjcvzzurfnocgq';
  $mail->SMTPSecure=PHPMailer::ENCRYPTION_STARTTLS; $mail->Port=587; $mail->CharSet='UTF-8';
  $mail->setFrom('proyectosdirsu@unitru.edu.pe','Sistema DIRSU');
  $mail->addReplyTo('proyectosdirsu@unitru.edu.pe','Sistema DIRSU');
  $mail->addAddress($email);
  $mail->isHTML(true); $mail->Subject=$asunto;
  $mail->Body=nl2br(htmlspecialchars($cuerpo,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'));
  $mail->AltBody=$cuerpo;
  $mail->send();
  jok();
}catch(Exception $e){
  jok(['email'=>'fail','msg'=>'Estado actualizado; andamiaje listo, pero no se pudo enviar el correo.']);
}
