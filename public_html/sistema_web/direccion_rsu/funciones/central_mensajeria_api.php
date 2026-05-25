<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';
require_once __DIR__ . '/../../includes/correo_config_service.php';

function cm_exit($success, $msg, $data = null, $httpCode = 200)
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($httpCode);
    }
    $out = array('success' => (bool)$success, 'msg' => (string)$msg);
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE);
    exit;
}

function cm_bind_and_execute(mysqli_stmt $stmt, $types, array $params)
{
    if ($types !== '' && !empty($params)) {
        $refs = array();
        $refs[] = $types;
        foreach ($params as $k => $v) {
            $refs[] = &$params[$k];
        }
        call_user_func_array(array($stmt, 'bind_param'), $refs);
    }
    return $stmt->execute();
}

function cm_tipo_informe_label($semTipo, $semFinal)
{
    $semTipo = trim((string)$semTipo);
    $semFinal = (int)$semFinal;
    if ($semTipo === 'semestral') {
        return $semFinal === 1 ? 'Informe final' : 'Informe semestral';
    }
    return 'Sin determinar';
}

function cm_estado_detalle_from_detalle($detalleRaw)
{
    $txt = trim((string)$detalleRaw);
    if ($txt === '') {
        return '';
    }
    $arr = json_decode($txt, true);
    if (!is_array($arr)) {
        return mb_substr($txt, 0, 280, 'UTF-8');
    }
    $parts = array();
    if (isset($arr['status'])) $parts[] = 'status=' . (string)$arr['status'];
    if (isset($arr['status_reason'])) $parts[] = 'reason=' . (string)$arr['status_reason'];
    if (isset($arr['event_code'])) $parts[] = 'event=' . (string)$arr['event_code'];
    if (isset($arr['subject'])) $parts[] = 'subject=' . (string)$arr['subject'];
    if (empty($parts)) {
        return mb_substr($txt, 0, 280, 'UTF-8');
    }
    return implode(' | ', $parts);
}

function cm_outbox_filters()
{
    $f = array();
    $f['desde'] = isset($_POST['desde']) ? trim((string)$_POST['desde']) : '';
    $f['hasta'] = isset($_POST['hasta']) ? trim((string)$_POST['hasta']) : '';
    $f['estado'] = isset($_POST['estado']) ? trim((string)$_POST['estado']) : '';
    $f['event_code'] = isset($_POST['event_code']) ? strtoupper(trim((string)$_POST['event_code'])) : '';
    $f['tipo_informe'] = isset($_POST['tipo_informe']) ? trim((string)$_POST['tipo_informe']) : '';
    $f['id_respuesta'] = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
    $f['office'] = isset($_POST['office']) ? (int)$_POST['office'] : 0;
    $f['motivo'] = isset($_POST['motivo']) ? trim((string)$_POST['motivo']) : '';
    $f['q'] = isset($_POST['q']) ? trim((string)$_POST['q']) : '';
    return $f;
}

function cm_build_outbox_where(array $f, &$types, &$params)
{
    $types = '';
    $params = array();
    $where = array();

    if ($f['desde'] !== '') {
        $where[] = "m.created_at >= ?";
        $types .= 's';
        $params[] = $f['desde'] . ' 00:00:00';
    }
    if ($f['hasta'] !== '') {
        $where[] = "m.created_at <= ?";
        $types .= 's';
        $params[] = $f['hasta'] . ' 23:59:59';
    }
    if (in_array($f['estado'], array('enviado', 'no_enviado', 'error'), true)) {
        $where[] = "m.estado = ?";
        $types .= 's';
        $params[] = $f['estado'];
    }
    if ($f['event_code'] !== '') {
        $where[] = "m.event_code = ?";
        $types .= 's';
        $params[] = $f['event_code'];
    }
    if ($f['id_respuesta'] > 0) {
        $where[] = "m.id_respuesta = ?";
        $types .= 'i';
        $params[] = $f['id_respuesta'];
    }
    if ($f['office'] > 0) {
        $where[] = "m.office = ?";
        $types .= 'i';
        $params[] = $f['office'];
    }
    if ($f['motivo'] !== '') {
        $where[] = "(m.motivo LIKE ? OR m.no_enviado_motivo LIKE ?)";
        $types .= 'ss';
        $like = '%' . $f['motivo'] . '%';
        $params[] = $like;
        $params[] = $like;
    }
    if ($f['q'] !== '') {
        $where[] = "(m.asunto LIKE ? OR m.destinatarios LIKE ? OR m.error_detalle LIKE ? OR p.p2 LIKE ?)";
        $types .= 'ssss';
        $likeQ = '%' . $f['q'] . '%';
        $params[] = $likeQ;
        $params[] = $likeQ;
        $params[] = $likeQ;
        $params[] = $likeQ;
    }
    if ($f['tipo_informe'] === 'semestral') {
        $where[] = "(s.tipo = 'semestral' AND COALESCE(s.final,0) = 0)";
    } elseif ($f['tipo_informe'] === 'final') {
        $where[] = "(s.tipo = 'semestral' AND COALESCE(s.final,0) = 1)";
    }

    return empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));
}

function cm_list_outbox(mysqli $conexion)
{
    $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $perPage = isset($_POST['per_page']) ? max(1, min(100, (int)$_POST['per_page'])) : 10;
    $offset = ($page - 1) * $perPage;
    $f = cm_outbox_filters();

    $types = '';
    $params = array();
    $where = cm_build_outbox_where($f, $types, $params);

    $from = " FROM msj_correos_outbox m
              LEFT JOIN sm_respuestas r ON r.id = m.id_respuesta
              LEFT JOIN sm_proyecto_semestres s ON s.id = r.id_semestre
              LEFT JOIN eva_oficinas o ON o.id = m.office
              LEFT JOIN proyectos p ON p.id = r.id_py ";

    $sqlCount = "SELECT COUNT(*) AS total " . $from . $where;
    $stCount = $conexion->prepare($sqlCount);
    if (!$stCount) {
        cm_exit(false, 'No se pudo preparar el conteo de outbox.', null, 500);
    }
    if (!cm_bind_and_execute($stCount, $types, $params)) {
        $stCount->close();
        cm_exit(false, 'No se pudo ejecutar el conteo de outbox.', null, 500);
    }
    $rowTotal = $stCount->get_result()->fetch_assoc();
    $total = isset($rowTotal['total']) ? (int)$rowTotal['total'] : 0;
    $stCount->close();

    $sql = "SELECT
                m.id, m.id_respuesta, m.event_code, m.office, m.tipo, m.destinatarios, m.asunto,
                m.estado, m.motivo, m.no_enviado_motivo, m.error_detalle, m.intentos, m.enviado_en,
                m.created_by, m.ip, m.origen, m.created_at, m.updated_at,
                o.codigo AS oficina_codigo, o.nombre AS oficina_nombre,
                r.id_py,
                p.p2 AS proyecto_titulo,
                s.tipo AS semestre_tipo,
                COALESCE(s.final,0) AS semestre_final,
                s.anio, s.periodo
            " . $from . $where . " ORDER BY m.id DESC LIMIT ? OFFSET ?";

    $st = $conexion->prepare($sql);
    if (!$st) {
        cm_exit(false, 'No se pudo preparar la lista de outbox.', null, 500);
    }

    $typesRows = $types . 'ii';
    $paramsRows = $params;
    $paramsRows[] = $perPage;
    $paramsRows[] = $offset;

    if (!cm_bind_and_execute($st, $typesRows, $paramsRows)) {
        $st->close();
        cm_exit(false, 'No se pudo ejecutar la lista de outbox.', null, 500);
    }

    $rs = $st->get_result();
    $rows = array();
    while ($r = $rs->fetch_assoc()) {
        $r['tipo_informe_label'] = cm_tipo_informe_label(
            isset($r['semestre_tipo']) ? $r['semestre_tipo'] : '',
            isset($r['semestre_final']) ? $r['semestre_final'] : 0
        );
        $officeCode = trim((string)($r['oficina_codigo'] ?? ''));
        $officeName = trim((string)($r['oficina_nombre'] ?? ''));
        $r['oficina_label'] = ($officeCode !== '' || $officeName !== '') ? ($officeName . ($officeCode !== '' ? ' (' . $officeCode . ')' : '')) : '';
        $rows[] = $r;
    }
    $st->close();

    $totalPages = (int)max(1, ceil($total / $perPage));
    return array(
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages
    );
}

function cm_get_outbox_detail(mysqli $conexion, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        cm_exit(false, 'ID inválido.', null, 422);
    }
    $sql = "SELECT * FROM msj_correos_outbox WHERE id = ? LIMIT 1";
    $st = $conexion->prepare($sql);
    if (!$st) {
        cm_exit(false, 'No se pudo preparar detalle.', null, 500);
    }
    $st->bind_param('i', $id);
    if (!$st->execute()) {
        $st->close();
        cm_exit(false, 'No se pudo ejecutar detalle.', null, 500);
    }
    $row = $st->get_result()->fetch_assoc();
    $st->close();
    if (!$row) {
        cm_exit(false, 'Registro no encontrado.', null, 404);
    }
    return $row;
}

function cm_list_eventos(mysqli $conexion)
{
    $page = isset($_POST['page']) ? max(1, (int)$_POST['page']) : 1;
    $perPage = isset($_POST['per_page']) ? max(1, min(100, (int)$_POST['per_page'])) : 10;
    $offset = ($page - 1) * $perPage;

    $desde = isset($_POST['desde']) ? trim((string)$_POST['desde']) : '';
    $hasta = isset($_POST['hasta']) ? trim((string)$_POST['hasta']) : '';
    $eventCode = isset($_POST['event_code']) ? strtoupper(trim((string)$_POST['event_code'])) : '';
    $idRespuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;

    $where = array();
    $types = '';
    $params = array();

    if ($desde !== '') {
        $where[] = "created_at >= ?";
        $types .= 's';
        $params[] = $desde . ' 00:00:00';
    }
    if ($hasta !== '') {
        $where[] = "created_at <= ?";
        $types .= 's';
        $params[] = $hasta . ' 23:59:59';
    }
    if ($eventCode !== '') {
        $where[] = "event_code = ?";
        $types .= 's';
        $params[] = $eventCode;
    }
    if ($idRespuesta > 0) {
        $where[] = "id_respuesta = ?";
        $types .= 'i';
        $params[] = $idRespuesta;
    }

    $whereSql = empty($where) ? '' : (' WHERE ' . implode(' AND ', $where));

    $sqlCount = "SELECT COUNT(*) AS total FROM ev_eventos" . $whereSql;
    $stCount = $conexion->prepare($sqlCount);
    if (!$stCount) {
        cm_exit(false, 'No se pudo preparar conteo de eventos.', null, 500);
    }
    if (!cm_bind_and_execute($stCount, $types, $params)) {
        $stCount->close();
        cm_exit(false, 'No se pudo ejecutar conteo de eventos.', null, 500);
    }
    $rowTotal = $stCount->get_result()->fetch_assoc();
    $total = isset($rowTotal['total']) ? (int)$rowTotal['total'] : 0;
    $stCount->close();

    $sql = "SELECT id, id_respuesta, event_code, office, tipo, detalle, created_at, created_by, ip
            FROM ev_eventos" . $whereSql . " ORDER BY id DESC LIMIT ? OFFSET ?";

    $st = $conexion->prepare($sql);
    if (!$st) {
        cm_exit(false, 'No se pudo preparar lista de eventos.', null, 500);
    }
    $typesRows = $types . 'ii';
    $paramsRows = $params;
    $paramsRows[] = $perPage;
    $paramsRows[] = $offset;
    if (!cm_bind_and_execute($st, $typesRows, $paramsRows)) {
        $st->close();
        cm_exit(false, 'No se pudo ejecutar lista de eventos.', null, 500);
    }

    $rs = $st->get_result();
    $rows = array();
    while ($r = $rs->fetch_assoc()) {
        $rows[] = $r;
    }
    $st->close();

    $totalPages = (int)max(1, ceil($total / $perPage));
    return array(
        'rows' => $rows,
        'total' => $total,
        'page' => $page,
        'per_page' => $perPage,
        'total_pages' => $totalPages
    );
}

function cm_timeline_respuesta(mysqli $conexion, $idRespuesta)
{
    $idRespuesta = (int)$idRespuesta;
    if ($idRespuesta <= 0) {
        cm_exit(false, 'Debes indicar un ID de respuesta válido.', null, 422);
    }

    $rows = array();

    $sqlOut = "SELECT id, created_at, event_code, estado, motivo, no_enviado_motivo
               FROM msj_correos_outbox
               WHERE id_respuesta = ?
               ORDER BY created_at DESC, id DESC";
    $stOut = $conexion->prepare($sqlOut);
    if ($stOut) {
        $stOut->bind_param('i', $idRespuesta);
        if ($stOut->execute()) {
            $rs = $stOut->get_result();
            while ($r = $rs->fetch_assoc()) {
                $rows[] = array(
                    'fecha' => $r['created_at'],
                    'origen' => 'outbox',
                    'codigo' => $r['event_code'],
                    'estado_detalle' => 'estado=' . (string)$r['estado'] . ' | motivo=' . (string)($r['motivo'] ?: $r['no_enviado_motivo']),
                    'referencia' => 'msj_correos_outbox#' . (string)$r['id']
                );
            }
        }
        $stOut->close();
    }

    $sqlEv = "SELECT id, created_at, event_code, detalle
              FROM ev_eventos
              WHERE id_respuesta = ?
              ORDER BY created_at DESC, id DESC";
    $stEv = $conexion->prepare($sqlEv);
    if ($stEv) {
        $stEv->bind_param('i', $idRespuesta);
        if ($stEv->execute()) {
            $rs = $stEv->get_result();
            while ($r = $rs->fetch_assoc()) {
                $rows[] = array(
                    'fecha' => $r['created_at'],
                    'origen' => 'ev_eventos',
                    'codigo' => $r['event_code'],
                    'estado_detalle' => cm_estado_detalle_from_detalle(isset($r['detalle']) ? $r['detalle'] : ''),
                    'referencia' => 'ev_eventos#' . (string)$r['id']
                );
            }
        }
        $stEv->close();
    }

    usort($rows, function ($a, $b) {
        $ta = strtotime((string)$a['fecha']);
        $tb = strtotime((string)$b['fecha']);
        if ($ta === $tb) return 0;
        return ($ta > $tb) ? -1 : 1;
    });

    return array('rows' => $rows, 'id_respuesta' => $idRespuesta);
}

function cm_kpis(mysqli $conexion)
{
    $sql = "SELECT
                COUNT(*) AS total,
                SUM(CASE WHEN estado='enviado' THEN 1 ELSE 0 END) AS enviado,
                SUM(CASE WHEN estado='no_enviado' THEN 1 ELSE 0 END) AS no_enviado,
                SUM(CASE WHEN estado='error' THEN 1 ELSE 0 END) AS error,
                SUM(CASE WHEN DATE(created_at)=CURDATE() THEN 1 ELSE 0 END) AS hoy
            FROM msj_correos_outbox";
    $rs = mysqli_query($conexion, $sql);
    if (!$rs) {
        return array('total' => 0, 'enviado' => 0, 'no_enviado' => 0, 'error' => 0, 'hoy' => 0, 'tasa_envio' => 0);
    }
    $r = mysqli_fetch_assoc($rs);
    $total = isset($r['total']) ? (int)$r['total'] : 0;
    $enviado = isset($r['enviado']) ? (int)$r['enviado'] : 0;
    $noEnviado = isset($r['no_enviado']) ? (int)$r['no_enviado'] : 0;
    $error = isset($r['error']) ? (int)$r['error'] : 0;
    $hoy = isset($r['hoy']) ? (int)$r['hoy'] : 0;
    $tasa = $total > 0 ? (int)round(($enviado * 100) / $total) : 0;
    return array(
        'total' => $total,
        'enviado' => $enviado,
        'no_enviado' => $noEnviado,
        'error' => $error,
        'hoy' => $hoy,
        'tasa_envio' => $tasa
    );
}

function cm_split_destinatarios($raw)
{
    $raw = trim((string)$raw);
    if ($raw === '') return array();
    $parts = preg_split('/[;,]+/', $raw);
    if (!is_array($parts)) return array();
    $out = array();
    foreach ($parts as $p) {
        $v = trim((string)$p);
        if ($v === '' || !filter_var($v, FILTER_VALIDATE_EMAIL)) continue;
        $out[$v] = true;
    }
    return array_keys($out);
}

function cm_log_evento_reenvio(mysqli $conexion, array $row, $estado, $motivo, $errorDetalle = '')
{
    $detalle = array(
        'event_code' => isset($row['event_code']) ? (string)$row['event_code'] : 'MAIL_EVENT',
        'status' => (string)$estado,
        'status_reason' => (string)$motivo,
        'to' => isset($row['destinatarios']) ? (string)$row['destinatarios'] : '',
        'subject' => isset($row['asunto']) ? (string)$row['asunto'] : '',
        'error' => (string)$errorDetalle,
        'source' => 'central_mensajeria_reenvio',
        'origen_outbox_id' => isset($row['id']) ? (int)$row['id'] : 0,
    );
    $detalleJson = json_encode($detalle, JSON_UNESCAPED_UNICODE);
    if ($detalleJson === false) {
        $detalleJson = '{"source":"central_mensajeria_reenvio","error":"json_encode_failed"}';
    }

    $sql = "INSERT INTO ev_eventos (id_respuesta, event_code, office, tipo, detalle, created_by, ip)
            VALUES (?, ?, ?, ?, ?, ?, ?)";
    $st = $conexion->prepare($sql);
    if (!$st) return;
    $idRespuesta = isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
    $eventCode = isset($row['event_code']) ? (string)$row['event_code'] : 'MAIL_EVENT';
    $office = isset($row['office']) && $row['office'] !== null ? (int)$row['office'] : null;
    $tipo = isset($row['tipo']) && $row['tipo'] !== null ? (int)$row['tipo'] : null;
    $createdBy = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
    $st->bind_param('isiisss', $idRespuesta, $eventCode, $office, $tipo, $detalleJson, $createdBy, $ip);
    $st->execute();
    $st->close();
}

function cm_registrar_outbox_reenvio(mysqli $conexion, array $row, $estado, $motivo, $errorDetalle = '')
{
    $sql = "INSERT INTO msj_correos_outbox
            (id_respuesta, event_code, office, tipo, destinatarios, asunto, cuerpo_html, cuerpo_texto,
             estado, motivo, no_enviado_motivo, error_detalle, intentos, enviado_en, created_by, ip, origen)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $st = $conexion->prepare($sql);
    if (!$st) {
        return 0;
    }

    $idRespuesta = isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
    $eventCode = isset($row['event_code']) ? (string)$row['event_code'] : '';
    $office = isset($row['office']) && $row['office'] !== null ? (int)$row['office'] : null;
    $tipo = isset($row['tipo']) && $row['tipo'] !== null ? (int)$row['tipo'] : null;
    $destinatarios = isset($row['destinatarios']) ? (string)$row['destinatarios'] : '';
    $asunto = isset($row['asunto']) ? (string)$row['asunto'] : '';
    $cuerpoHtml = isset($row['cuerpo_html']) ? (string)$row['cuerpo_html'] : '';
    $cuerpoTexto = isset($row['cuerpo_texto']) ? (string)$row['cuerpo_texto'] : '';
    $estadoIns = (string)$estado;
    $motivoIns = (string)$motivo;
    $noEnviadoMotivo = ($estadoIns === 'enviado') ? '' : $motivoIns;
    $errorIns = (string)$errorDetalle;
    $intentos = 1;
    $enviadoEn = ($estadoIns === 'enviado') ? date('Y-m-d H:i:s') : null;
    $createdBy = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null;
    $ip = isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null;
    $origen = 'central_mensajeria_reenvio';

    $st->bind_param(
        'isiissssssssissss',
        $idRespuesta,
        $eventCode,
        $office,
        $tipo,
        $destinatarios,
        $asunto,
        $cuerpoHtml,
        $cuerpoTexto,
        $estadoIns,
        $motivoIns,
        $noEnviadoMotivo,
        $errorIns,
        $intentos,
        $enviadoEn,
        $createdBy,
        $ip,
        $origen
    );
    $ok = $st->execute();
    $newId = $ok ? (int)$st->insert_id : 0;
    $st->close();
    return $newId;
}

function cm_reenviar_outbox(mysqli $conexion, $id)
{
    $id = (int)$id;
    if ($id <= 0) {
        cm_exit(false, 'ID inválido para reenvío.', null, 422);
    }

    $row = cm_get_outbox_detail($conexion, $id);
    $destinatarios = cm_split_destinatarios(isset($row['destinatarios']) ? $row['destinatarios'] : '');
    if (empty($destinatarios)) {
        cm_exit(false, 'El mensaje no tiene destinatarios válidos para reenvío.', null, 409);
    }

    $reason = '';
    $msg = '';
    if (!cor_mail_can_send_notifications($conexion, $reason, $msg)) {
        cm_exit(false, 'No se puede reenviar: ' . $msg, null, 409);
    }

    $asunto = isset($row['asunto']) ? (string)$row['asunto'] : '';
    $cuerpoHtml = isset($row['cuerpo_html']) ? (string)$row['cuerpo_html'] : '';
    $cuerpoTexto = isset($row['cuerpo_texto']) ? (string)$row['cuerpo_texto'] : '';
    $errorDetail = '';
    $ok = cor_mail_send_using_active_config($conexion, $destinatarios, $asunto, $cuerpoHtml, $cuerpoTexto, $errorDetail);

    $estado = $ok ? 'enviado' : 'error';
    $motivo = $ok ? 'reenvio_manual_exitoso' : 'reenvio_manual_fallido';
    $newId = cm_registrar_outbox_reenvio($conexion, $row, $estado, $motivo, $errorDetail);
    cm_log_evento_reenvio($conexion, $row, $estado, $motivo, $errorDetail);

    if (!$ok) {
        cm_exit(false, 'Reenvío fallido: ' . ($errorDetail !== '' ? $errorDetail : 'Fallo SMTP desconocido.'), array(
            'nuevo_outbox_id' => $newId,
            'estado' => $estado,
            'motivo' => $motivo,
            'error' => $errorDetail,
        ), 500);
    }

    cm_exit(true, 'Reenvío ejecutado correctamente.', array(
        'nuevo_outbox_id' => $newId,
        'estado' => $estado,
        'motivo' => $motivo,
        'destinatarios' => implode(';', $destinatarios),
    ));
}

try {
    if (!isset($_SESSION['usuario']) || trim((string)$_SESSION['usuario']) === '') {
        cm_exit(false, 'Sesión inválida.', null, 401);
    }
    if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
        cm_exit(false, 'No autorizado.', null, 403);
    }

    $csrf = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
    if (!evt_mto_validate_csrf_token($csrf, 'central_mensajeria_csrf')) {
        cm_exit(false, 'Token CSRF inválido.', null, 419);
    }

    $conexion = evt_mto_db_connect();
    if (!($conexion instanceof mysqli)) {
        cm_exit(false, 'No hay conexión disponible.', null, 500);
    }

    $action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
    if ($action === 'kpis') {
        cm_exit(true, 'OK', cm_kpis($conexion));
    } elseif ($action === 'list_outbox') {
        cm_exit(true, 'OK', cm_list_outbox($conexion));
    } elseif ($action === 'get_outbox_detail') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        cm_exit(true, 'OK', cm_get_outbox_detail($conexion, $id));
    } elseif ($action === 'list_eventos') {
        cm_exit(true, 'OK', cm_list_eventos($conexion));
    } elseif ($action === 'timeline_respuesta') {
        $idRespuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
        cm_exit(true, 'OK', cm_timeline_respuesta($conexion, $idRespuesta));
    } elseif ($action === 'reenviar_outbox') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        cm_reenviar_outbox($conexion, $id);
    }

    cm_exit(false, 'Acción no soportada.', null, 400);
} catch (Throwable $e) {
    cm_exit(false, 'Error interno: ' . $e->getMessage(), null, 500);
}
