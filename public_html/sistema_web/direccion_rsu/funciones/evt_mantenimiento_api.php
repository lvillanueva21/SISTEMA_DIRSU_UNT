<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';
require_once __DIR__ . '/../../includes/correo_config_service.php';

function evt_mto_api_exit($success, $msg, $data = null, $httpCode = 200)
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
    echo json_encode($out);
    exit;
}

function evt_mto_api_ensure_db_manager_event(mysqli $conexion, $userId = null)
{
    $uid = ($userId === null || (int)$userId <= 0) ? null : (int)$userId;

    $sql = "INSERT INTO evt_eventos (codigo, nombre, estado, actualizado_por)
            VALUES ('acceso_gestor_db', 'Acceso a Gestor DB', 0, ?)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'i', $uid);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function evt_mto_api_ensure_inicio_deadline_event(mysqli $conexion, $userId = null)
{
    $uid = ($userId === null || (int)$userId <= 0) ? null : (int)$userId;

    $sql = "INSERT INTO evt_eventos (codigo, nombre, estado, actualizado_por)
            VALUES ('inicio_fecha_limite_visible', 'Mostrar fecha límite en inicio', 0, ?)
            ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'i', $uid);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function evt_mto_api_get_db_manager_state(mysqli $conexion)
{
    $state = array(
        'codigo' => 'acceso_gestor_db',
        'nombre' => 'Acceso a Gestor DB',
        'estado' => 0,
        'actualizado_en' => null,
        'actualizado_por' => null
    );

    $sql = "SELECT codigo, nombre, estado, actualizado_en, actualizado_por
              FROM evt_eventos
             WHERE codigo = 'acceso_gestor_db'
             LIMIT 1";
    $res = mysqli_query($conexion, $sql);
    if (!$res) {
        return $state;
    }

    $row = mysqli_fetch_assoc($res);
    if (!$row) {
        return $state;
    }

    $state['codigo'] = isset($row['codigo']) ? (string)$row['codigo'] : 'acceso_gestor_db';
    $state['nombre'] = isset($row['nombre']) ? (string)$row['nombre'] : 'Acceso a Gestor DB';
    $state['estado'] = (isset($row['estado']) && (int)$row['estado'] === 1) ? 1 : 0;
    $state['actualizado_en'] = isset($row['actualizado_en']) ? $row['actualizado_en'] : null;
    $state['actualizado_por'] = isset($row['actualizado_por']) ? $row['actualizado_por'] : null;

    return $state;
}

function evt_mto_api_default_inicio_deadline_title()
{
    return 'Fecha límite';
}

function evt_mto_api_default_inicio_deadline_message()
{
    return 'Sin fechas límites por el momento.';
}

function evt_mto_api_default_inicio_deadline_datetime()
{
    $tz = new DateTimeZone('America/Lima');
    $dt = new DateTime('now', $tz);
    $dt->modify('+7 days');
    $dt->setTime(23, 59, 0);
    return $dt->format('Y-m-d H:i:s');
}

function evt_mto_api_parse_deadline_datetime($input, &$errorMessage = null)
{
    $value = trim((string)$input);
    if ($value === '') {
        $errorMessage = 'Debe indicar la fecha y hora límite.';
        return false;
    }

    $value = str_replace('T', ' ', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $value)) {
        $value .= ':00';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $value)) {
        $errorMessage = 'Formato de fecha límite inválido.';
        return false;
    }

    $tz = new DateTimeZone('America/Lima');
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value, $tz);
    $errors = DateTime::getLastErrors();
    $hasParseErrors = (is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count'])));
    if (!$dt || $hasParseErrors) {
        $errorMessage = 'Fecha límite inválida.';
        return false;
    }

    return $dt->format('Y-m-d H:i:s');
}

function evt_mto_api_get_inicio_deadline_state(mysqli $conexion)
{
    $state = array(
        'codigo' => 'inicio_fecha_limite_visible',
        'visible' => 0,
        'titulo' => evt_mto_api_default_inicio_deadline_title(),
        'mensaje' => evt_mto_api_default_inicio_deadline_message(),
        'deadline' => evt_mto_api_default_inicio_deadline_datetime(),
        'updated_by' => null,
        'updated_at' => null,
        'event_updated_by' => null,
        'event_updated_at' => null
    );

    $sqlEvt = "SELECT codigo, estado, actualizado_por, actualizado_en
                 FROM evt_eventos
                WHERE codigo = 'inicio_fecha_limite_visible'
                LIMIT 1";
    $resEvt = mysqli_query($conexion, $sqlEvt);
    if ($resEvt && ($rowEvt = mysqli_fetch_assoc($resEvt))) {
        $state['codigo'] = isset($rowEvt['codigo']) ? (string)$rowEvt['codigo'] : 'inicio_fecha_limite_visible';
        $state['visible'] = (isset($rowEvt['estado']) && (int)$rowEvt['estado'] === 1) ? 1 : 0;
        $state['event_updated_by'] = isset($rowEvt['actualizado_por']) ? $rowEvt['actualizado_por'] : null;
        $state['event_updated_at'] = isset($rowEvt['actualizado_en']) ? $rowEvt['actualizado_en'] : null;
    }

    $sqlCfg = "SELECT titulo, mensaje, deadline, updated_by, updated_at
                 FROM inicio_fecha_limite
                WHERE id = 1
                LIMIT 1";
    $resCfg = mysqli_query($conexion, $sqlCfg);
    if ($resCfg && ($rowCfg = mysqli_fetch_assoc($resCfg))) {
        if (isset($rowCfg['titulo']) && trim((string)$rowCfg['titulo']) !== '') {
            $state['titulo'] = (string)$rowCfg['titulo'];
        }
        if (isset($rowCfg['mensaje']) && trim((string)$rowCfg['mensaje']) !== '') {
            $state['mensaje'] = (string)$rowCfg['mensaje'];
        }
        if (isset($rowCfg['deadline']) && trim((string)$rowCfg['deadline']) !== '') {
            $state['deadline'] = (string)$rowCfg['deadline'];
        }
        $state['updated_by'] = isset($rowCfg['updated_by']) ? $rowCfg['updated_by'] : null;
        $state['updated_at'] = isset($rowCfg['updated_at']) ? $rowCfg['updated_at'] : null;
    }

    return $state;
}

function evt_mto_api_messaging_events_catalog()
{
    return array(
        'evaluacion_mensajeria' => 'Mensajería global de evaluación',
        'evaluacion_mail_derivacion' => 'Correo: derivación entre oficinas',
        'evaluacion_mail_observacion' => 'Correo: observación (cotejo/rúbrica)',
        'evaluacion_mail_aprob_total' => 'Correo: aprobación total',
        'evaluacion_mail_solicitud_revision' => 'Correo: solicitud de revisión',
        'evaluacion_mail_subsanacion' => 'Correo: subsanación',
    );
}

function evt_mto_api_ensure_messaging_events(mysqli $conexion, $userId = null)
{
    $uid = ($userId === null || (int)$userId <= 0) ? null : (int)$userId;
    $catalog = evt_mto_api_messaging_events_catalog();
    foreach ($catalog as $code => $name) {
        $sql = "INSERT INTO evt_eventos (codigo, nombre, estado, actualizado_por)
                VALUES (?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE nombre = VALUES(nombre)";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            return false;
        }
        mysqli_stmt_bind_param($st, 'ssi', $code, $name, $uid);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        if (!$ok) {
            return false;
        }
    }
    return true;
}

function evt_mto_api_get_messaging_state(mysqli $conexion)
{
    $catalog = evt_mto_api_messaging_events_catalog();
    $state = array();
    foreach ($catalog as $code => $name) {
        $state[$code] = array(
            'codigo' => $code,
            'nombre' => $name,
            'estado' => 1,
            'actualizado_en' => null,
            'actualizado_por' => null
        );
    }

    $sql = "SELECT codigo, nombre, estado, actualizado_en, actualizado_por
              FROM evt_eventos
             WHERE codigo IN ('evaluacion_mensajeria','evaluacion_mail_derivacion','evaluacion_mail_observacion','evaluacion_mail_aprob_total','evaluacion_mail_solicitud_revision','evaluacion_mail_subsanacion')";
    $rs = mysqli_query($conexion, $sql);
    if (!($rs instanceof mysqli_result)) {
        return $state;
    }
    while ($row = mysqli_fetch_assoc($rs)) {
        $code = isset($row['codigo']) ? (string)$row['codigo'] : '';
        if ($code === '' || !isset($state[$code])) {
            continue;
        }
        $state[$code]['nombre'] = isset($row['nombre']) && trim((string)$row['nombre']) !== '' ? (string)$row['nombre'] : $state[$code]['nombre'];
        $state[$code]['estado'] = (isset($row['estado']) && (int)$row['estado'] === 1) ? 1 : 0;
        $state[$code]['actualizado_en'] = isset($row['actualizado_en']) ? $row['actualizado_en'] : null;
        $state[$code]['actualizado_por'] = isset($row['actualizado_por']) ? $row['actualizado_por'] : null;
    }
    mysqli_free_result($rs);

    return $state;
}

function evt_mto_api_get_correo_config_state(mysqli $conexion)
{
    return cor_mail_get_ui_state($conexion);
}

function evt_mto_api_preview_templates_catalog()
{
    return array(
        'PREVIEW_DERIVACION' => 'Derivación entre oficinas',
        'PREVIEW_DERIVACION_OFICINA' => 'Derivación a oficina destino',
        'PREVIEW_OBS_COTEJO' => 'Observación por Cotejo',
        'PREVIEW_OBS_RUBRICA' => 'Observación por Rúbrica',
        'PREVIEW_APROB_TOTAL' => 'Aprobación total',
        'PREVIEW_SOLICITUD_REVISION' => 'Solicitud de revisión',
        'PREVIEW_SUBSANACION' => 'Subsanación',
    );
}

function evt_mto_api_mail_esc($value)
{
    return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function evt_mto_api_mail_format_line($line)
{
    $line = trim((string)$line);
    if ($line === '') {
        return '';
    }
    if (preg_match('/^presiona\s+para\s+ir\s+al\s+sistema\s+dirsu\s+y\s+subsanar\.$/iu', $line)) {
        return '<a href="https://rsu.unitru.edu.pe/sistema_web/login.php" target="_blank" style="color:#0a58ca;text-decoration:underline;">Presiona para ir al Sistema DIRSU y subsanar.</a>';
    }
    if (preg_match('/^([A-Za-zÁÉÍÓÚáéíóúÑñüÜ0-9()\/\-\s]+):(.*)$/u', $line, $m)) {
        $label = evt_mto_api_mail_esc(trim((string)$m[1]));
        $value = trim((string)$m[2]);
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return '<strong>' . $label . ':</strong> <a href="' . evt_mto_api_mail_esc($value) . '" target="_blank" style="color:#0a58ca;text-decoration:underline;">' . evt_mto_api_mail_esc($value) . '</a>';
        }
        return '<strong>' . $label . ':</strong>' . ($value !== '' ? ' ' . evt_mto_api_mail_esc($value) : '');
    }
    return evt_mto_api_mail_esc($line);
}

function evt_mto_api_mail_table_from_pipe_lines(array $lines)
{
    if (empty($lines)) {
        return '';
    }
    $rows = array();
    foreach ($lines as $line) {
        $parts = array_map('trim', explode('|', (string)$line));
        if (count($parts) < 3) {
            return '';
        }
        $rows[] = $parts;
    }
    if (count($rows) < 2) {
        return '';
    }

    $html = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #d7dee7;margin:8px 0 14px 0;">';
    $html .= '<thead><tr style="background:#f3f7fb;">';
    foreach ($rows[0] as $cell) {
        $html .= '<th align="left" style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;font-weight:700;color:#1f2d3d;">' . evt_mto_api_mail_esc($cell) . '</th>';
    }
    $html .= '</tr></thead><tbody>';

    for ($i = 1; $i < count($rows); $i++) {
        $html .= '<tr>';
        foreach ($rows[$i] as $idx => $cell) {
            $align = ($idx === 1) ? 'center' : 'left';
            $html .= '<td align="' . $align . '" style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;line-height:1.45;color:#1f2d3d;">' . evt_mto_api_mail_esc($cell) . '</td>';
        }
        $html .= '</tr>';
    }
    $html .= '</tbody></table>';
    return $html;
}

function evt_mto_api_render_template_test_html($templateLabel, $bodyBase)
{
    $text = trim((string)$bodyBase);
    if ($text === '') {
        return '';
    }

    $lines = preg_split('/\r\n|\r|\n/', $text);
    if (!is_array($lines)) {
        $lines = array($text);
    }

    $tableRows = array();
    $tableMode = false;
    $tableInserted = false;
    $partsHtml = array();
    $paragraph = array();

    $flushParagraph = function () use (&$paragraph, &$partsHtml) {
        if (empty($paragraph)) {
            return;
        }
        $partsHtml[] = '<p style="margin:0 0 13px 0;font-size:14px;line-height:1.55;color:#1f2d3d;">' . implode('<br>', $paragraph) . '</p>';
        $paragraph = array();
    };

    foreach ($lines as $rawLine) {
        $line = trim((string)$rawLine);
        if (!$tableMode && preg_match('/^\s*Aspecto\s*\|/iu', $line)) {
            $tableMode = true;
            $tableRows[] = $line;
            continue;
        }
        if ($tableMode && $line !== '' && substr_count($line, '|') >= 2) {
            $tableRows[] = $line;
            continue;
        }
        if ($tableMode && !empty($tableRows) && !$tableInserted) {
            $flushParagraph();
            $tableHtml = evt_mto_api_mail_table_from_pipe_lines($tableRows);
            if ($tableHtml !== '') {
                $partsHtml[] = $tableHtml;
                $tableInserted = true;
            } else {
                foreach ($tableRows as $tblLine) {
                    $paragraph[] = evt_mto_api_mail_format_line($tblLine);
                }
            }
            $tableRows = array();
            $tableMode = false;
        }
        if ($line === '') {
            $flushParagraph();
            continue;
        }
        $paragraph[] = evt_mto_api_mail_format_line($line);
    }

    if ($tableMode && !empty($tableRows) && !$tableInserted) {
        $flushParagraph();
        $tableHtml = evt_mto_api_mail_table_from_pipe_lines($tableRows);
        if ($tableHtml !== '') {
            $partsHtml[] = $tableHtml;
        } else {
            foreach ($tableRows as $tblLine) {
                $paragraph[] = evt_mto_api_mail_format_line($tblLine);
            }
        }
    }
    $flushParagraph();

    $intro = '<p style="margin:0 0 16px 0;font-size:13px;line-height:1.45;color:#4b5d6f;">Este es un envío de prueba de la plantilla: <strong>' . evt_mto_api_mail_esc($templateLabel) . '</strong>.</p>';

    return '<div style="margin:0;padding:0;background:#f5f7fb;">'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dde5ef;font-family:Segoe UI,Arial,sans-serif;">'
        . '<tr><td style="padding:22px 24px;">'
        . $intro
        . implode("\n", $partsHtml)
        . '</td></tr></table></div>';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    evt_mto_api_exit(false, 'Método no permitido.', null, 405);
}

if (!isset($_SESSION['usuario'])) {
    evt_mto_api_exit(false, 'Sesión no válida.', null, 401);
}

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
    evt_mto_api_exit(false, 'No autorizado.', null, 403);
}

$csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!evt_mto_validate_csrf_token($csrfToken, 'evt_mantenimiento_admin_csrf')) {
    evt_mto_api_exit(false, 'Token CSRF inválido.', null, 403);
}

$conexion = evt_mto_db_connect();
if (!($conexion instanceof mysqli)) {
    error_log('evt_mantenimiento_api: conexion BD no disponible');
    evt_mto_api_exit(false, 'No se pudo procesar la solicitud en este momento.', null, 500);
}
@mysqli_set_charset($conexion, 'utf8mb4');

$userId = isset($_SESSION['id_usuario']) ? (int)$_SESSION['id_usuario'] : 0;
if (!evt_mto_ensure_seed($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar seed de evt_');
    evt_mto_api_exit(false, 'No se pudo inicializar la configuración de mantenimiento.', null, 500);
}
if (!evt_mto_api_ensure_db_manager_event($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar evento acceso_gestor_db');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de acceso del gestor DB.', null, 500);
}
if (!evt_mto_api_ensure_inicio_deadline_event($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar evento inicio_fecha_limite_visible');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de fecha límite.', null, 500);
}
if (!evt_mto_api_ensure_messaging_events($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar eventos de mensajería');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de mensajería.', null, 500);
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if ($action === 'get_state') {
    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Estado cargado.', $state);
}

if ($action === 'save_correo_config') {
    $remitenteEmail = isset($_POST['remitente_email']) ? (string)$_POST['remitente_email'] : '';
    $remitenteNombre = isset($_POST['remitente_nombre']) ? (string)$_POST['remitente_nombre'] : 'Sistema DIRSU';
    $smtpUsuario = isset($_POST['smtp_usuario']) ? (string)$_POST['smtp_usuario'] : $remitenteEmail;
    $correoVerificador = isset($_POST['correo_verificador']) ? (string)$_POST['correo_verificador'] : '';
    $appKey = isset($_POST['app_key']) ? (string)$_POST['app_key'] : '';
    $estado = isset($_POST['estado']) ? (int)$_POST['estado'] : 1;

    $saveError = '';
    $ok = cor_mail_save_config(
        $conexion,
        array(
            'remitente_email' => $remitenteEmail,
            'remitente_nombre' => $remitenteNombre,
            'smtp_usuario' => $smtpUsuario,
            'correo_verificador' => $correoVerificador,
            'app_key' => $appKey,
            'estado' => $estado,
        ),
        isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
        $saveError
    );

    if (!$ok) {
        evt_mto_api_exit(false, $saveError !== '' ? $saveError : 'No se pudo guardar la configuración de correo.', null, 422);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Configuración de correo guardada correctamente.', $state);
}

if ($action === 'test_correo_config') {
    $test = cor_mail_run_test(
        $conexion,
        isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
        isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null
    );
    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);

    if (!empty($test['ok'])) {
        evt_mto_api_exit(true, isset($test['msg']) ? (string)$test['msg'] : 'Prueba enviada.', array(
            'test' => $test,
            'state' => $state,
        ));
    }
    evt_mto_api_exit(false, isset($test['msg']) ? (string)$test['msg'] : 'No se pudo enviar la prueba.', array(
        'test' => $test,
        'state' => $state,
    ), 422);
}

if ($action === 'send_messaging_template_test') {
    $catalog = evt_mto_api_preview_templates_catalog();
    $templateCode = isset($_POST['template_code']) ? strtoupper(trim((string)$_POST['template_code'])) : '';
    if ($templateCode === '' || !isset($catalog[$templateCode])) {
        evt_mto_api_exit(false, 'Plantilla de prueba no válida.', null, 422);
    }
    $templateLabel = (string)$catalog[$templateCode];
    $templateText = isset($_POST['template_text']) ? trim((string)$_POST['template_text']) : '';
    if ($templateText === '') {
        evt_mto_api_exit(false, 'No se recibio contenido de plantilla para la prueba.', null, 422);
    }

    $correoReason = '';
    $correoMsg = '';
    if (!cor_mail_can_send_notifications($conexion, $correoReason, $correoMsg)) {
        evt_mto_api_exit(false, 'Primero configura tu Key en Configuración de correo. Detalle: ' . $correoMsg, null, 422);
    }

    $cfgResult = cor_mail_get_active_config($conexion, false);
    if (empty($cfgResult['ok']) || empty($cfgResult['config']) || !is_array($cfgResult['config'])) {
        $msg = isset($cfgResult['message']) ? (string)$cfgResult['message'] : 'No hay configuración de correo activa.';
        evt_mto_api_exit(false, $msg, null, 422);
    }
    $cfg = $cfgResult['config'];
    $destino = isset($cfg['correo_verificador']) ? trim((string)$cfg['correo_verificador']) : '';
    if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
        evt_mto_api_exit(false, 'El correo verificador configurado no es válido.', null, 422);
    }

    $lines = preg_split('/\r\n|\r|\n/', $templateText);
    if (!is_array($lines)) {
        $lines = array($templateText);
    }
    $subject = '';
    if (!empty($lines) && preg_match('/^\s*Asunto\s*:\s*(.+)\s*$/iu', (string)$lines[0], $m)) {
        $subject = trim((string)$m[1]);
        array_shift($lines);
    }
    if ($subject === '') {
        $subject = 'Prueba de plantilla - Sistema DIRSU';
    }
    $subject .= ' [Prueba ' . date('Y-m-d H:i:s') . ']';

    $bodyBase = trim(implode("\n", $lines));
    if ($bodyBase === '') {
        $bodyBase = $templateText;
    }
    $bodyBase = str_replace('{{url_login_proyecto}}', 'https://rsu.unitru.edu.pe/sistema_web/login.php', $bodyBase);
    $intro = "Este es un envío de prueba de la plantilla: {$templateLabel}.";
    $bodyText = $intro . "\n\n" . $bodyBase;
    $bodyHtml = evt_mto_api_render_template_test_html($templateLabel, $bodyBase);
    if ($bodyHtml === '') {
        $bodyHtml = nl2br(htmlspecialchars($bodyText, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
    }

    $errorDetail = '';
    $ok = cor_mail_send_using_active_config($conexion, array($destino), $subject, $bodyHtml, $bodyText, $errorDetail);
    $estado = $ok ? 'enviado' : 'error';
    $detalle = $ok
        ? 'Prueba enviada correctamente a ' . $destino . '.'
        : (($errorDetail !== '') ? $errorDetail : 'Fallo desconocido al enviar la prueba.');

    cor_mail_update_last_test($conexion, $estado, $detalle);
    cor_mail_log_test($conexion, array(
        'config_id' => isset($cfg['id']) ? (int)$cfg['id'] : 1,
        'remitente_email' => isset($cfg['remitente_email']) ? (string)$cfg['remitente_email'] : '',
        'destino_email' => $destino,
        'asunto' => $subject,
        'estado' => $estado,
        'detalle' => '[Plantilla: ' . $templateCode . '] ' . $detalle,
        'created_by' => isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
        'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
    ));

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    $test = array(
        'template_code' => $templateCode,
        'template_label' => $templateLabel,
        'estado' => $estado,
        'destino' => $destino,
        'asunto' => $subject,
        'detalle' => $detalle,
    );

    if ($ok) {
        evt_mto_api_exit(true, 'Prueba enviada: ' . $templateLabel . '.', array(
            'test' => $test,
            'state' => $state,
        ));
    }
    evt_mto_api_exit(false, 'No se pudo enviar la prueba de plantilla.', array(
        'test' => $test,
        'state' => $state,
    ), 422);
}

if ($action === 'save_messaging') {
    $correoReason = '';
    $correoMsg = '';
    if (!cor_mail_can_send_notifications($conexion, $correoReason, $correoMsg)) {
        evt_mto_api_exit(false, 'Primero configura tu Key en Configuración de correo. Detalle: ' . $correoMsg, null, 422);
    }

    $catalog = evt_mto_api_messaging_events_catalog();
    $updates = array();
    foreach ($catalog as $code => $name) {
        $raw = isset($_POST[$code]) ? (int)$_POST[$code] : null;
        if ($raw === null || ($raw !== 0 && $raw !== 1)) {
            continue;
        }
        $updates[$code] = $raw;
    }
    if (empty($updates)) {
        evt_mto_api_exit(false, 'No se recibieron cambios de mensajería.', null, 422);
    }

    mysqli_begin_transaction($conexion);
    try {
        foreach ($updates as $code => $status) {
            $sql = "UPDATE evt_eventos
                       SET estado = ?,
                           actualizado_por = ?,
                           actualizado_en = NOW()
                     WHERE codigo = ?
                     LIMIT 1";
            $st = mysqli_prepare($conexion, $sql);
            if (!$st) {
                throw new Exception('No se pudo preparar actualización de mensajería.');
            }
            mysqli_stmt_bind_param($st, 'iis', $status, $userId, $code);
            if (!mysqli_stmt_execute($st)) {
                mysqli_stmt_close($st);
                throw new Exception('No se pudo guardar estado de mensajería.');
            }
            mysqli_stmt_close($st);
        }
        mysqli_commit($conexion);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        error_log('evt_mantenimiento_api save_messaging: ' . $e->getMessage());
        evt_mto_api_exit(false, 'No se pudo guardar la mensajería.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Mensajería actualizada correctamente.', $state);
}

if ($action === 'save_db_manager_access') {
    $dbManagerEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : -1;
    if ($dbManagerEstado !== 0 && $dbManagerEstado !== 1) {
        evt_mto_api_exit(false, 'Estado de acceso al gestor DB inválido.', null, 422);
    }

    $sql = "UPDATE evt_eventos
               SET estado = ?,
                   actualizado_por = ?,
                   actualizado_en = NOW()
             WHERE codigo = 'acceso_gestor_db'
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        evt_mto_api_exit(false, 'No se pudo preparar la actualización del gestor DB.', null, 500);
    }
    mysqli_stmt_bind_param($st, 'ii', $dbManagerEstado, $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if (!$ok) {
        evt_mto_api_exit(false, 'No se pudo guardar la configuración del gestor DB.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Acceso a gestor DB actualizado correctamente.', $state);
}

if ($action === 'save_inicio_deadline') {
    $visible = isset($_POST['visible']) ? (int)$_POST['visible'] : -1;
    if ($visible !== 0 && $visible !== 1) {
        evt_mto_api_exit(false, 'Estado de visibilidad inválido.', null, 422);
    }

    $titulo = evt_mto_trim_limit(isset($_POST['titulo']) ? $_POST['titulo'] : '', 120);
    $mensaje = evt_mto_trim_limit(isset($_POST['mensaje']) ? $_POST['mensaje'] : '', 300);
    $deadlineInput = isset($_POST['deadline']) ? $_POST['deadline'] : '';
    $username = isset($_SESSION['usuario']) ? evt_mto_trim_limit($_SESSION['usuario'], 64) : '';

    if ($titulo === '') {
        $titulo = evt_mto_api_default_inicio_deadline_title();
    }
    if ($mensaje === '') {
        $mensaje = evt_mto_api_default_inicio_deadline_message();
    }

    $deadline = evt_mto_api_default_inicio_deadline_datetime();
    if (trim((string)$deadlineInput) !== '') {
        $parseError = null;
        $parsed = evt_mto_api_parse_deadline_datetime($deadlineInput, $parseError);
        if ($parsed === false) {
            evt_mto_api_exit(false, $parseError !== null ? $parseError : 'Fecha límite inválida.', null, 422);
        }
        $deadline = $parsed;
    } elseif ($visible === 1) {
        evt_mto_api_exit(false, 'Debe indicar la fecha y hora límite para habilitar el contador.', null, 422);
    } else {
        $currentDeadline = evt_mto_api_get_inicio_deadline_state($conexion);
        if (!empty($currentDeadline['deadline'])) {
            $deadline = (string)$currentDeadline['deadline'];
        }
    }

    mysqli_begin_transaction($conexion);
    try {
        $sqlEvt = "UPDATE evt_eventos
                      SET estado = ?,
                          actualizado_por = ?,
                          actualizado_en = NOW()
                    WHERE codigo = 'inicio_fecha_limite_visible'
                    LIMIT 1";
        $stEvt = mysqli_prepare($conexion, $sqlEvt);
        if (!$stEvt) {
            throw new Exception('No se pudo preparar la actualización de visibilidad.');
        }
        mysqli_stmt_bind_param($stEvt, 'ii', $visible, $userId);
        if (!mysqli_stmt_execute($stEvt)) {
            mysqli_stmt_close($stEvt);
            throw new Exception('No se pudo guardar la visibilidad.');
        }
        mysqli_stmt_close($stEvt);

        $sqlCfg = "INSERT INTO inicio_fecha_limite (id, titulo, mensaje, deadline, updated_by, updated_at)
                   VALUES (1, ?, ?, ?, ?, NOW())
                   ON DUPLICATE KEY UPDATE
                     titulo = VALUES(titulo),
                     mensaje = VALUES(mensaje),
                     deadline = VALUES(deadline),
                     updated_by = VALUES(updated_by),
                     updated_at = NOW()";
        $stCfg = mysqli_prepare($conexion, $sqlCfg);
        if (!$stCfg) {
            throw new Exception('No se pudo preparar la actualización del contenido.');
        }
        mysqli_stmt_bind_param($stCfg, 'ssss', $titulo, $mensaje, $deadline, $username);
        if (!mysqli_stmt_execute($stCfg)) {
            mysqli_stmt_close($stCfg);
            throw new Exception('No se pudo guardar el contenido.');
        }
        mysqli_stmt_close($stCfg);

        mysqli_commit($conexion);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        error_log('evt_mantenimiento_api save_inicio_deadline: ' . $e->getMessage());
        evt_mto_api_exit(false, 'No se pudo guardar la configuración de fecha límite.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Configuración de fecha límite guardada correctamente.', $state);
}

if ($action !== 'save_config') {
    evt_mto_api_exit(false, 'Accion no permitida.', null, 400);
}

$sistemaActivo = isset($_POST['sistema_activo']) ? (int)$_POST['sistema_activo'] : -1;
if ($sistemaActivo !== 0 && $sistemaActivo !== 1) {
        evt_mto_api_exit(false, 'Estado de sistema inválido.', null, 422);
}

$titulo = evt_mto_trim_limit(isset($_POST['titulo']) ? $_POST['titulo'] : '', 180);
$mensaje = evt_mto_trim_limit(isset($_POST['mensaje']) ? $_POST['mensaje'] : '', 5000);
$claveNueva = isset($_POST['clave_nueva']) ? trim((string)$_POST['clave_nueva']) : '';

if ($titulo === '') {
    $titulo = evt_mto_default_title();
}
if ($mensaje === '') {
    $mensaje = evt_mto_default_message();
}

if ($claveNueva !== '' && strlen($claveNueva) < 8) {
    evt_mto_api_exit(false, 'La clave secreta debe tener al menos 8 caracteres.', null, 422);
}

$current = evt_mto_fetch_state();
$eventoId = isset($current['evento_id']) ? (int)$current['evento_id'] : 0;
if ($eventoId <= 0) {
    error_log('evt_mantenimiento_api: evento mantenimiento_sistema no encontrado');
    evt_mto_api_exit(false, 'No se encontró la configuración de mantenimiento.', null, 500);
}

$hasSecret = !empty($current['has_secret']);
if ($sistemaActivo === 0 && !$hasSecret && $claveNueva === '') {
    evt_mto_api_exit(false, 'No se puede apagar el sistema sin clave secreta configurada.', null, 422);
}

$useHash = false;
$hash = null;
if ($claveNueva !== '') {
    $hash = password_hash($claveNueva, PASSWORD_DEFAULT);
    if (!is_string($hash) || $hash === '') {
        evt_mto_api_exit(false, 'No se pudo proteger la clave secreta.', null, 500);
    }
    $useHash = true;
}

mysqli_begin_transaction($conexion);
try {
    if ($useHash) {
        $sql = "UPDATE evt_mantenimiento_cfg
                   SET sistema_activo = ?,
                       titulo = ?,
                       mensaje = ?,
                       clave_hash = ?,
                       clave_actualizada_en = NOW(),
                       actualizado_por = ?
                 WHERE evento_id = ?";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la actualización.');
        }
        mysqli_stmt_bind_param($st, 'isssii', $sistemaActivo, $titulo, $mensaje, $hash, $userId, $eventoId);
    } else {
        $sql = "UPDATE evt_mantenimiento_cfg
                   SET sistema_activo = ?,
                       titulo = ?,
                       mensaje = ?,
                       actualizado_por = ?
                 WHERE evento_id = ?";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la actualización.');
        }
        mysqli_stmt_bind_param($st, 'issii', $sistemaActivo, $titulo, $mensaje, $userId, $eventoId);
    }

    if (!mysqli_stmt_execute($st)) {
        mysqli_stmt_close($st);
        throw new Exception('No se pudo guardar la configuración.');
    }
    mysqli_stmt_close($st);

    mysqli_commit($conexion);

    if ($sistemaActivo === 1) {
        evt_mto_set_bypass_session(false);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    $state['correo_config'] = evt_mto_api_get_correo_config_state($conexion);
    evt_mto_api_exit(true, 'Configuración guardada correctamente.', $state);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log('evt_mantenimiento_api save_config: ' . $e->getMessage());
    evt_mto_api_exit(false, 'No se pudo guardar la configuración de mantenimiento.', null, 500);
}
