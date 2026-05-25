<?php
// semestral/logica/solicitar_revision.php
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

function sm_json_error($code, $msg)
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

function sm_json_ok($extra = array())
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

function sm_item_completo($row, $tipo)
{
    switch ((string)$tipo) {
        case 'varchar':
            return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'longtext':
        case 'longtext_parrafo':
            return isset($row['val_longtext']) && trim((string)$row['val_longtext']) !== '';
        case 'tinyint':
            return array_key_exists('val_tinyint', $row) && $row['val_tinyint'] !== null;
        case 'int':
            return array_key_exists('val_int', $row) && $row['val_int'] !== null;
        case 'boolean':
            return array_key_exists('val_boolean', $row) && $row['val_boolean'] !== null;
        case 'datetime':
            return !empty($row['val_datetime']);
        case 'date':
            return !empty($row['val_date']);
        case 'decimal':
            return array_key_exists('val_decimal', $row) && $row['val_decimal'] !== null;
        case 'programa_ods':
        case 'ods':
            return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'pdf':
        case 'excel':
        case 'word':
            return isset($row['archivo_url']) && trim((string)$row['archivo_url']) !== '';
        default:
            return false;
    }
}

function sm_enviar_correo_confirmacion(mysqli $conexion, $destino, $asunto, $textoPlano)
{
    $destino = trim((string)$destino);
    if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        return array('ok' => false, 'msg' => 'Correo de destino inválido.');
    }


    $htmlBody = nl2br(htmlspecialchars((string)$textoPlano, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    $html = '<div style="margin:0;padding:0;background:#f5f7fb;">'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dde5ef;font-family:Segoe UI,Arial,sans-serif;">'
        . '<tr><td style="padding:22px 24px;color:#1f2d3d;font-size:14px;line-height:1.56;">'
        . '<p style="margin:0;">' . $htmlBody . '</p>'
        . '</td></tr></table></div>';

    $errorDetail = '';
    $ok = cor_mail_send_using_active_config($conexion, array($destino), (string)$asunto, $html, (string)$textoPlano, $errorDetail);
    if ($ok) {
        return array('ok' => true, 'msg' => 'Correo enviado.');
    }
    return array('ok' => false, 'msg' => 'No se pudo enviar correo: ' . ($errorDetail !== '' ? $errorDetail : 'Fallo desconocido.'));
}

require_once __DIR__ . '/../../componentes/db.php';
require_once __DIR__ . '/../../includes/evaluacion_v1/messaging_helpers.php';
require_once __DIR__ . '/../../includes/correo_config_service.php';

$id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
$usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
$proyTituloPost = isset($_POST['proy_titulo']) ? trim((string)$_POST['proy_titulo']) : '';
$formNombrePost = isset($_POST['form_nombre']) ? trim((string)$_POST['form_nombre']) : '';

if ($id_respuesta <= 0) {
    sm_json_error(400, 'ID de respuesta inválido.');
}
if ($usuario === '') {
    sm_json_error(401, 'Sesión inválida.');
}

// Validar propiedad de la respuesta por coordinador activo.
$sqlResp = "
    SELECT r.id, r.id_py, r.id_formulario, r.estado
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
    sm_json_error(500, 'No se pudo preparar la validación de la respuesta.');
}
$stResp->bind_param('is', $id_respuesta, $usuario);
if (!$stResp->execute()) {
    $stResp->close();
    sm_json_error(500, 'No se pudo validar la respuesta.');
}
$resp = $stResp->get_result()->fetch_assoc();
$stResp->close();

if (!$resp) {
    sm_json_error(404, 'No se encontró la respuesta o no pertenece al usuario activo.');
}

$idProyecto = (int)$resp['id_py'];

$metaTipoInforme = rsu_eval_v1_report_type($conexion, $id_respuesta);
if (empty($metaTipoInforme['ok'])) {
    $msgTipo = isset($metaTipoInforme['message']) ? (string)$metaTipoInforme['message'] : 'No se pudo determinar el tipo de informe.';
    sm_json_error(409, $msgTipo);
}
$tipoInformeTitle = (string)$metaTipoInforme['label_title'];
$semestreInformeLabel = 'No determinado';
$stSem = $conexion->prepare("
    SELECT s.anio, s.periodo
      FROM sm_respuestas r
      LEFT JOIN sm_proyecto_semestres s ON s.id = r.id_semestre
     WHERE r.id = ?
     LIMIT 1
");
if ($stSem) {
    $stSem->bind_param('i', $id_respuesta);
    if ($stSem->execute() && ($rowSem = $stSem->get_result()->fetch_assoc())) {
        $anioSem = isset($rowSem['anio']) ? (int)$rowSem['anio'] : 0;
        $periodoSem = trim((string)($rowSem['periodo'] ?? ''));
        if ($anioSem > 0 && $periodoSem !== '') {
            $semestreInformeLabel = $anioSem . '-' . $periodoSem;
        }
    }
    $stSem->close();
}

$estadoActual = (int)$resp['estado'];
if (!in_array($estadoActual, array(0, 1, 3), true)) {
    sm_json_error(409, 'La respuesta no se puede enviar a revisión en su estado actual.');
}

// Si ya existe ruta aprobada, no se debe reabrir desde aquí.
$evalPrevia = null;
$stEvalPrev = $conexion->prepare("SELECT id, situacion, id_oficina_actual FROM eva_evaluaciones WHERE id_respuesta=? LIMIT 1");
if ($stEvalPrev) {
    $stEvalPrev->bind_param('i', $id_respuesta);
    if ($stEvalPrev->execute()) {
        $evalPrevia = $stEvalPrev->get_result()->fetch_assoc();
    }
    $stEvalPrev->close();
}

if (!empty($evalPrevia) && isset($evalPrevia['situacion']) && $evalPrevia['situacion'] === 'aprobado') {
    sm_json_error(409, 'El informe ya fue aprobado y no puede volver a revisión.');
}

if ($estadoActual === 1 && !empty($evalPrevia) && !empty($evalPrevia['id_oficina_actual'])) {
    // Doble clic o solicitud repetida: no romper UX.
    sm_json_ok(array('msg' => 'La solicitud ya estaba en revisión.'));
}

// Validar que todos los ítems activos estén completos.
$id_formulario = (int)$resp['id_formulario'];
$items = array();
$stItems = $conexion->prepare("
    SELECT fi.id_item, i.tipo
    FROM sm_formulario_items fi
    INNER JOIN sm_items i ON i.id = fi.id_item
    WHERE fi.id_formulario = ?
      AND fi.activo = 1
    ORDER BY fi.orden ASC
");
if (!$stItems) {
    sm_json_error(500, 'No se pudo preparar la validación de ítems.');
}
$stItems->bind_param('i', $id_formulario);
if (!$stItems->execute()) {
    $stItems->close();
    sm_json_error(500, 'No se pudo validar los ítems del formulario.');
}
$rsItems = $stItems->get_result();
while ($row = $rsItems->fetch_assoc()) {
    $items[] = $row;
}
$stItems->close();

$totalItems = count($items);
if ($totalItems <= 0) {
    sm_json_error(409, 'El formulario activo no tiene ítems configurados.');
}

$respuestas = array();
$stVals = $conexion->prepare("
    SELECT id_item, tipo, val_varchar, val_longtext, val_tinyint, val_int, val_boolean, val_datetime, val_date, val_decimal, archivo_url
    FROM sm_respuesta_items
    WHERE id_respuesta = ?
");
if (!$stVals) {
    sm_json_error(500, 'No se pudo preparar la lectura de respuestas.');
}
$stVals->bind_param('i', $id_respuesta);
if (!$stVals->execute()) {
    $stVals->close();
    sm_json_error(500, 'No se pudo validar las respuestas del formulario.');
}
$rsVals = $stVals->get_result();
while ($row = $rsVals->fetch_assoc()) {
    $respuestas[(int)$row['id_item']] = $row;
}
$stVals->close();

$completados = 0;
foreach ($items as $it) {
    $idItem = (int)$it['id_item'];
    $tipoItem = (string)$it['tipo'];
    $lleno = isset($respuestas[$idItem]) ? sm_item_completo($respuestas[$idItem], $tipoItem) : false;
    if ($lleno) {
        $completados++;
    }
}

if ($completados < $totalItems) {
    $faltantes = $totalItems - $completados;
    sm_json_error(409, 'Completa todos los ítems antes de solicitar revisión. Faltan ' . $faltantes . '.');
}

// Oficina de entrada de ruta: PCF si está activa, si no la primera oficina activa por orden.
$oficina = null;
$stOf1 = $conexion->prepare("
    SELECT id, codigo
    FROM eva_oficinas
    WHERE activo = 1 AND codigo = 'PCF'
    ORDER BY id ASC
    LIMIT 1
");
if ($stOf1 && $stOf1->execute()) {
    $oficina = $stOf1->get_result()->fetch_assoc();
}
if ($stOf1) {
    $stOf1->close();
}
if (!$oficina) {
    $stOf2 = $conexion->prepare("
        SELECT id, codigo
        FROM eva_oficinas
        WHERE activo = 1
        ORDER BY orden ASC, id ASC
        LIMIT 1
    ");
    if ($stOf2 && $stOf2->execute()) {
        $oficina = $stOf2->get_result()->fetch_assoc();
    }
    if ($stOf2) {
        $stOf2->close();
    }
}
if (!$oficina || empty($oficina['id'])) {
    sm_json_error(500, 'No hay oficinas activas para iniciar la revisión.');
}

$oficinaId = (int)$oficina['id'];
$oficinaCod = isset($oficina['codigo']) ? (string)$oficina['codigo'] : '';
$tiposCalif = ($oficinaCod === 'PCF' || $oficinaCod === 'RSU') ? array('cotejo', 'rubrica') : array('vistobueno');

$conexion->begin_transaction();
try {
    $stEstado = $conexion->prepare("
        UPDATE sm_respuestas
        SET estado = 1, actualizado_at = NOW()
        WHERE id = ?
          AND estado IN (0,1,3)
    ");
    if (!$stEstado) {
        throw new RuntimeException('No se pudo preparar la actualización de estado.');
    }
    $stEstado->bind_param('i', $id_respuesta);
    if (!$stEstado->execute()) {
        $err = $stEstado->error;
        $stEstado->close();
        throw new RuntimeException('No se pudo actualizar el estado de la respuesta: ' . $err);
    }
    $stEstado->close();

    $stEval = $conexion->prepare("
        INSERT INTO eva_evaluaciones (id_respuesta, situacion, id_oficina_actual, creado_at, actualizado_at)
        VALUES (?, 'en_oficina', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            situacion = 'en_oficina',
            id_oficina_actual = VALUES(id_oficina_actual),
            actualizado_at = NOW()
    ");
    if (!$stEval) {
        throw new RuntimeException('No se pudo preparar la ruta de evaluación.');
    }
    $stEval->bind_param('ii', $id_respuesta, $oficinaId);
    if (!$stEval->execute()) {
        $err = $stEval->error;
        $stEval->close();
        throw new RuntimeException('No se pudo inicializar la ruta de evaluación: ' . $err);
    }
    $stEval->close();

    $evalId = 0;
    $stEvalId = $conexion->prepare("SELECT id FROM eva_evaluaciones WHERE id_respuesta = ? LIMIT 1");
    if (!$stEvalId) {
        throw new RuntimeException('No se pudo obtener la evaluación creada.');
    }
    $stEvalId->bind_param('i', $id_respuesta);
    if (!$stEvalId->execute()) {
        $err = $stEvalId->error;
        $stEvalId->close();
        throw new RuntimeException('No se pudo obtener la evaluación creada: ' . $err);
    }
    $rowEvalId = $stEvalId->get_result()->fetch_assoc();
    $stEvalId->close();
    if (!$rowEvalId || empty($rowEvalId['id'])) {
        throw new RuntimeException('No se encontró la evaluación de la respuesta.');
    }
    $evalId = (int)$rowEvalId['id'];

    $stInst = $conexion->prepare("
        INSERT INTO eva_oficina_instancias
            (id_evaluacion, id_oficina, llegada, salida, estado, reintentos, anulaciones, ultima_observacion_at, ultima_revision_solicitada_at)
        VALUES
            (?, ?, NOW(), NULL, 'en_espera', 0, 0, NULL, NOW())
        ON DUPLICATE KEY UPDATE
            llegada = VALUES(llegada),
            salida = NULL,
            estado = 'en_espera',
            ultima_revision_solicitada_at = NOW()
    ");
    if (!$stInst) {
        throw new RuntimeException('No se pudo preparar la instancia de oficina.');
    }
    $stInst->bind_param('ii', $evalId, $oficinaId);
    if (!$stInst->execute()) {
        $err = $stInst->error;
        $stInst->close();
        throw new RuntimeException('No se pudo crear/actualizar la instancia de oficina: ' . $err);
    }
    $stInst->close();

    $stCal = $conexion->prepare("
        INSERT INTO eva_calificaciones
            (id_evaluacion, id_oficina, tipo, estado, dias_subsanacion, total, obs_general, reintentos, anulaciones, ultimo_observado_at, ultima_revision_solicitada_at, creado_at, actualizado_at)
        VALUES
            (?, ?, ?, 'en_espera', NULL, NULL, NULL, 0, 0, NULL, NOW(), NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            estado = 'en_espera',
            dias_subsanacion = NULL,
            total = NULL,
            obs_general = NULL,
            ultima_revision_solicitada_at = NOW(),
            actualizado_at = NOW()
    ");
    if (!$stCal) {
        throw new RuntimeException('No se pudo preparar las calificaciones de entrada.');
    }
    foreach ($tiposCalif as $tipoCalif) {
        $stCal->bind_param('iis', $evalId, $oficinaId, $tipoCalif);
        if (!$stCal->execute()) {
            $err = $stCal->error;
            $stCal->close();
            throw new RuntimeException('No se pudo crear/actualizar la calificación inicial (' . $tipoCalif . '): ' . $err);
        }
    }
    $stCal->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    sm_json_error(500, 'No se pudo solicitar revisión: ' . $e->getMessage());
}

// Correo de confirmación (best effort, nunca bloquea la operación principal).
$mailInfo = array('ok' => false, 'msg' => 'Correo no intentado.');
$correoDestino = '';
$stEmail = $conexion->prepare("SELECT email FROM usuario_contactos WHERE usuario = ? LIMIT 1");
if ($stEmail) {
    $stEmail->bind_param('s', $usuario);
    if ($stEmail->execute()) {
        $rowEmail = $stEmail->get_result()->fetch_assoc();
        if ($rowEmail && !empty($rowEmail['email'])) {
            $correoDestino = trim((string)$rowEmail['email']);
        }
    }
    $stEmail->close();
}

$tituloProyecto = $proyTituloPost;
if ($tituloProyecto === '') {
    $stProy = $conexion->prepare("SELECT p2 FROM proyectos WHERE id = ? LIMIT 1");
    if ($stProy) {
        $stProy->bind_param('i', $idProyecto);
        if ($stProy->execute()) {
            $rowProy = $stProy->get_result()->fetch_assoc();
            if ($rowProy && !empty($rowProy['p2'])) {
                $tituloProyecto = trim((string)$rowProy['p2']);
            }
        }
        $stProy->close();
    }
}

$nombreFormulario = $formNombrePost;
if ($nombreFormulario === '') {
    $stForm = $conexion->prepare("SELECT nombre FROM sm_formularios WHERE id = ? LIMIT 1");
    if ($stForm) {
        $stForm->bind_param('i', $id_formulario);
        if ($stForm->execute()) {
            $rowForm = $stForm->get_result()->fetch_assoc();
            if ($rowForm && !empty($rowForm['nombre'])) {
                $nombreFormulario = trim((string)$rowForm['nombre']);
            }
        }
        $stForm->close();
    }
}

$fecha = date('d/m/Y');
$hora = date('H:i');
$asunto = 'Solicitud de Revisión de ' . $tipoInformeTitle . ' — ' . ($nombreFormulario !== '' ? $nombreFormulario : 'Formulario');
$mensaje = 'Se solicitó la revisión del proyecto "' . ($tituloProyecto !== '' ? $tituloProyecto : 'Proyecto') . '"'
    . ' para el formulario "' . ($nombreFormulario !== '' ? $nombreFormulario : 'Formulario') . '"'
    . ' el día ' . $fecha . ' a las ' . $hora . ' (Lima-Perú).';
$mensaje .= "\nSemestre del informe: " . $semestreInformeLabel;
$mensaje .= "\n\nIngresar al Sistema DIRSU: https://rsu.unitru.edu.pe/sistema_web/login.php";
$toList = ($correoDestino !== '') ? array($correoDestino) : array();

$mailNotifyOk = rsu_eval_v1_notify_mail(
    $conexion,
    array(
        'id_respuesta' => $id_respuesta,
        'event_code' => 'MAIL_SOLICITUD_REVISION',
        'office' => $oficinaId,
        'tipo' => 3,
        'to' => $toList,
        'subject' => $asunto,
        'message' => nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        'html' => nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')),
        'text' => $mensaje,
        'created_by' => $usuario,
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
        'skip_reason' => empty($toList) ? 'sin_destinatarios' : '',
    ),
    function (array $mailPayload) use ($conexion, $correoDestino) {
        $res = sm_enviar_correo_confirmacion(
            $conexion,
            $correoDestino,
            isset($mailPayload['subject']) ? (string)$mailPayload['subject'] : '',
            isset($mailPayload['text']) ? (string)$mailPayload['text'] : ''
        );
        return !empty($res['ok']);
    }
);

if ($mailNotifyOk && !empty($toList)) {
    $mailInfo = array('ok' => true, 'msg' => 'Notificación registrada correctamente.');
} elseif (empty($toList)) {
    $mailInfo = array('ok' => false, 'msg' => 'La solicitud se registró. No se encontró destinatario y el evento quedó auditado como no enviado.');
} else {
    $mailInfo = array('ok' => false, 'msg' => 'La solicitud se registró, pero no se pudo enviar/notificar el correo.');
}
sm_json_ok(array(
    'msg' => 'Solicitud enviada correctamente.',
    'completados' => $completados,
    'total' => $totalItems,
    'mail_ok' => !empty($mailInfo['ok']),
    'mail_msg' => isset($mailInfo['msg']) ? (string)$mailInfo['msg'] : ''
));
