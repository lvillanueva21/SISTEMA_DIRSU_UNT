<?php
// semestral/logica/anular_revision.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

ob_start();
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json; charset=UTF-8');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR), true)) {
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=UTF-8');
        }
        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        echo json_encode(array('status' => 'error', 'msg' => 'Error interno del servidor.'), JSON_UNESCAPED_UNICODE);
    }
});

function sm_anular_error($code, $msg)
{
    if (!headers_sent()) {
        http_response_code((int)$code);
        header('Content-Type: application/json; charset=UTF-8');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    echo json_encode(array('status' => 'error', 'msg' => (string)$msg), JSON_UNESCAPED_UNICODE);
    exit;
}

function sm_anular_ok($extra = array())
{
    if (!headers_sent()) {
        http_response_code(200);
        header('Content-Type: application/json; charset=UTF-8');
    }
    while (ob_get_level() > 0) {
        ob_end_clean();
    }
    $payload = array_merge(array('status' => 'ok'), is_array($extra) ? $extra : array());
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../componentes/db.php';

$id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
$usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';

if ($id_respuesta <= 0) {
    sm_anular_error(400, 'ID de respuesta inválido.');
}
if ($usuario === '') {
    sm_anular_error(401, 'Sesión inválida.');
}

$sqlResp = "
    SELECT r.id, r.estado
    FROM sm_respuestas r
    INNER JOIN usuarios_proyectos up
            ON up.id_proyecto = r.id_py
           AND up.activo = 1
    INNER JOIN usuarios u
            ON u.id = up.id_usuario
           AND u.id_rol = 2
    WHERE r.id = ?
      AND u.usuario = ?
    LIMIT 1
";
$stResp = $conexion->prepare($sqlResp);
if (!$stResp) {
    sm_anular_error(500, 'No se pudo preparar la validación de la respuesta.');
}
$stResp->bind_param('is', $id_respuesta, $usuario);
if (!$stResp->execute()) {
    $stResp->close();
    sm_anular_error(500, 'No se pudo validar la respuesta.');
}
$resp = $stResp->get_result()->fetch_assoc();
$stResp->close();

if (!$resp) {
    sm_anular_error(404, 'No se encontró la respuesta o no pertenece al usuario activo.');
}

$estadoActual = (int)$resp['estado'];
if ($estadoActual === 0) {
    sm_anular_ok(array('msg' => 'La respuesta ya estaba en borrador.'));
}
if ($estadoActual === 2) {
    sm_anular_error(409, 'No se puede anular una respuesta aprobada.');
}
if ($estadoActual !== 1) {
    sm_anular_error(409, 'Solo se puede anular una respuesta en estado de revisión.');
}

$eval = null;
$stEval = $conexion->prepare("SELECT id, situacion, id_oficina_actual FROM eva_evaluaciones WHERE id_respuesta=? LIMIT 1");
if ($stEval) {
    $stEval->bind_param('i', $id_respuesta);
    if ($stEval->execute()) {
        $eval = $stEval->get_result()->fetch_assoc();
    }
    $stEval->close();
}

if (!empty($eval) && isset($eval['situacion']) && $eval['situacion'] === 'aprobado') {
    sm_anular_error(409, 'No se puede anular: la evaluación ya está aprobada.');
}

if (!empty($eval) && !empty($eval['id_oficina_actual'])) {
    sm_anular_error(409, 'No se puede anular: el informe ya está en revisión de una oficina.');
}

$conexion->begin_transaction();
try {
    $stUp = $conexion->prepare("
        UPDATE sm_respuestas
        SET estado = 0, actualizado_at = NOW()
        WHERE id = ?
          AND estado = 1
    ");
    if (!$stUp) {
        throw new RuntimeException('No se pudo preparar la anulación de estado.');
    }
    $stUp->bind_param('i', $id_respuesta);
    if (!$stUp->execute()) {
        $err = $stUp->error;
        $stUp->close();
        throw new RuntimeException('No se pudo anular la solicitud: ' . $err);
    }
    $stUp->close();

    if (!empty($eval) && !empty($eval['id'])) {
        $evalId = (int)$eval['id'];
        $stFix = $conexion->prepare("
            UPDATE eva_evaluaciones
            SET id_oficina_actual = NULL,
                situacion = 'en_oficina',
                actualizado_at = NOW()
            WHERE id = ?
            LIMIT 1
        ");
        if (!$stFix) {
            throw new RuntimeException('No se pudo preparar el ajuste de la ruta.');
        }
        $stFix->bind_param('i', $evalId);
        if (!$stFix->execute()) {
            $err = $stFix->error;
            $stFix->close();
            throw new RuntimeException('No se pudo ajustar la ruta de evaluación: ' . $err);
        }
        $stFix->close();
    }

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    sm_anular_error(500, 'No se pudo anular la solicitud: ' . $e->getMessage());
}

sm_anular_ok(array('msg' => 'Solicitud anulada. La respuesta volvió a borrador.'));
