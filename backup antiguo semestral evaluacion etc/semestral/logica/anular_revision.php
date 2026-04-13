<?php
// semestral/logica/anular_revision.php
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

function jfail(int $code, string $msg) {
  if (!headers_sent()) {
    http_response_code($code);
    header('Content-Type: application/json; charset=UTF-8');
  }
  while (ob_get_level() > 0) { ob_end_clean(); }
  echo json_encode(['status'=>'error','msg'=>$msg]);
  exit;
}
function jok(array $extra = []) {
  if (!headers_sent()) {
    http_response_code(200);
    header('Content-Type: application/json; charset=UTF-8');
  }
  while (ob_get_level() > 0) { ob_end_clean(); }
  echo json_encode(array_merge(['status'=>'ok'], $extra));
  exit;
}

require_once __DIR__ . '/../../componentes/db.php';

// Entrada
$id_respuesta = (int)($_POST['id_respuesta'] ?? 0);
if ($id_respuesta <= 0) jfail(400, 'ID de respuesta inválido');
if (empty($_SESSION['usuario'])) jfail(401, 'Sesión inválida');

// Validar respuesta
$stR = $conexion->prepare("SELECT id, estado FROM sm_respuestas WHERE id=? LIMIT 1");
$stR->bind_param("i", $id_respuesta);
$stR->execute();
$resp = $stR->get_result()->fetch_assoc();
$stR->close();
if (!$resp) jfail(404, 'Respuesta no encontrada');

// Transacción
$conexion->begin_transaction();
try {
  // 1) Verificar que está en revisión (1)
  if ((int)$resp['estado'] !== 1) {
    throw new Exception('No es posible anular: la respuesta no está en revisión (estado actual: '.$resp['estado'].').');
  }

  // 2) ¿Hubo avance de evaluaciones? Si hay cualquier eval con estado != en_espera o con fecha_evaluacion, no permitimos anular
  $stChk = $conexion->prepare("
    SELECT COUNT(*) AS c
    FROM ev_evaluaciones
    WHERE id_respuesta=?
      AND (estado <> 'en_espera' OR fecha_evaluacion IS NOT NULL)
  ");
  $stChk->bind_param('i', $id_respuesta);
  $stChk->execute();
  $c = (int)$stChk->get_result()->fetch_assoc()['c'];
  $stChk->close();

  if ($c > 0) {
    throw new Exception('No es posible anular: ya existen acciones de evaluación registradas.');
  }

  // 3) Eliminar andamiaje V3 (opcional: para dejar limpio al volver a solicitar)
  //    - ev_evaluaciones (cascadeará a cotejo_observaciones / rubrica_calificaciones si existen FKs con CASCADE)
  if ($conexion->query("DELETE FROM ev_evaluaciones WHERE id_respuesta=".(int)$id_respuesta) === false) {
    throw new Exception('No se pudo limpiar evaluaciones previas.');
  }
  //    - ev_revisiones
  if ($conexion->query("DELETE FROM ev_revisiones WHERE id_respuesta=".(int)$id_respuesta) === false) {
    throw new Exception('No se pudo limpiar la revisión previa.');
  }

  // 4) Bajar estado a borrador (0)
  $stU = $conexion->prepare("
    UPDATE sm_respuestas
       SET estado = 0, actualizado_at = NOW()
     WHERE id=? AND estado = 1
  ");
  $stU->bind_param("i", $id_respuesta);
  $stU->execute();
  $af = $stU->affected_rows;
  $stU->close();

  if ($af <= 0) {
    throw new Exception('No se pudo anular: la respuesta ya no está en revisión.');
  }

  $conexion->commit();
  jok();

} catch (Throwable $e) {
  $conexion->rollback();
  jfail(409, $e->getMessage());
}
