<?php
// semestral/logica/enviar_subsanacion.php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
date_default_timezone_set('America/Lima');

ob_start();
error_reporting(E_ALL);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=UTF-8');

function jfail(int $code, string $msg){
  if(!headers_sent()){
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
  }
  while (ob_get_level()>0){ob_end_clean();}
  echo json_encode(['status'=>'error','msg'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}
function jok(array $extra=[]){
  if(!headers_sent()){
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
  }
  while (ob_get_level()>0){ob_end_clean();}
  echo json_encode(array_merge(['status'=>'ok'],$extra), JSON_UNESCAPED_UNICODE);
  exit;
}

require_once __DIR__ . '/../../componentes/db.php';

$id_respuesta = (int)($_POST['id_respuesta'] ?? 0);
if ($id_respuesta<=0) jfail(400,'ID de respuesta inválido');
if (empty($_SESSION['usuario'])) jfail(401,'Sesión inválida');

// 1) Cargar evaluación + oficina actual + estados
$sql = "
  SELECT e.id                 AS id_eval,
         e.situacion          AS situacion,
         e.id_oficina_actual  AS id_ofi,
         o.nombre             AS ofi_nom,
         o.codigo             AS ofi_cod,
         cj.estado            AS cj_estado,
         rb.estado            AS rb_estado
  FROM eva_evaluaciones e
  LEFT JOIN eva_oficinas o ON o.id = e.id_oficina_actual
  LEFT JOIN eva_calificaciones cj
    ON cj.id_evaluacion=e.id AND cj.id_oficina=e.id_oficina_actual AND cj.tipo='cotejo'
  LEFT JOIN eva_calificaciones rb
    ON rb.id_evaluacion=e.id AND rb.id_oficina=e.id_oficina_actual AND rb.tipo='rubrica'
  WHERE e.id_respuesta = ?
  LIMIT 1
";
$st = $conexion->prepare($sql);
$st->bind_param("i",$id_respuesta);
$st->execute();
$eval = $st->get_result()->fetch_assoc();
$st->close();

if (!$eval) jfail(404,'No existe ruta de evaluación para esta respuesta.');
if (empty($eval['id_ofi'])) jfail(409,'La respuesta no está en ninguna oficina actualmente.');
if ($eval['situacion'] === 'aprobado') jfail(409,'La evaluación ya está aprobada totalmente.');

$obsCj = ($eval['cj_estado'] ?? '') === 'observado';
$obsRb = ($eval['rb_estado'] ?? '') === 'observado';
if (!$obsCj && !$obsRb) jfail(409,'No hay observaciones activas en la oficina actual.');

// 2) Última instancia de ESA oficina
$st = $conexion->prepare("
  SELECT id
  FROM eva_oficina_instancias
  WHERE id_evaluacion=? AND id_oficina=?
  ORDER BY id DESC
  LIMIT 1
");
$st->bind_param("ii", $eval['id_eval'], $eval['id_ofi']);
$st->execute();
$rowInst = $st->get_result()->fetch_assoc();
$st->close();
$instId = $rowInst ? (int)$rowInst['id'] : 0;
if ($instId<=0) jfail(404,'No se encontró la instancia de oficina para esta evaluación.');

// 3) Transacción: pasar a EN ESPERA lo observado + marcar timestamp de reenvío
$conexion->begin_transaction();
try {
  // a) Reabrir calificaciones observadas → en_espera
  $tipos = [];
  if ($obsCj) $tipos[] = 'cotejo';
  if ($obsRb) $tipos[] = 'rubrica';

  if (!empty($tipos)) {
    $in = implode("','",$tipos);
    $qCal = "
      UPDATE eva_calificaciones
         SET estado='en_espera',
             ultima_revision_solicitada_at = NOW(),
             actualizado_at = NOW()
       WHERE id_evaluacion=? AND id_oficina=? AND tipo IN ('{$in}') AND estado='observado'
    ";
    $stUp = $conexion->prepare($qCal);
    $stUp->bind_param("ii", $eval['id_eval'], $eval['id_ofi']);
    $stUp->execute();
    $stUp->close();
  }

  // b) Instancia de oficina → marcar reenvío y dejar en_espera
  $stI = $conexion->prepare("
    UPDATE eva_oficina_instancias
       SET estado='en_espera',
           ultima_revision_solicitada_at = NOW()
     WHERE id = ?
  ");
  $stI->bind_param("i", $instId);
  $stI->execute();
  $stI->close();

  // Nota: sm_respuestas se mantiene en 1 (en revisión)
  $conexion->commit();

  // === Notificar a autoridades que observaron (según oficina/rol) ===
  try {
    require_once __DIR__ . '/notificaciones_subsanacion_autoridades.php';
    $resNotif = notif_subsanacion_autoridades($conexion, [
      'id_respuesta' => $id_respuesta,
      'eval_id'      => (int)$eval['id_eval'],
      'oficina_id'   => (int)$eval['id_ofi'],
      'oficina_cod'  => $eval['ofi_cod'] ?? null,
      'oficina_nom'  => (string)($eval['ofi_nom'] ?? ''),
    ]);
    if (!$resNotif || empty($resNotif['ok'])) {
      $msg  = $resNotif['error'] ?? 'Fallo desconocido al enviar la notificación de subsanación.';
      $diag = !empty($resNotif['diag']) ? (' | '.implode(' | ', (array)$resNotif['diag'])) : '';
      jfail(500, 'Subsanación registrada, pero falló el envío de correo: '.$msg.$diag);
    }
  } catch (Throwable $e) {
    jfail(500, 'Subsanación registrada, pero falló el envío de correo: '.$e->getMessage());
  }

  jok(['oficina'=>$eval['ofi_nom'] ?? 'Oficina']);

} catch (Throwable $e) {
  $conexion->rollback();
  jfail(500,'No se pudo enviar la subsanación: '.$e->getMessage());
}
