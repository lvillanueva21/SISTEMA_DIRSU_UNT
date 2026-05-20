<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';

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
            VALUES ('inicio_fecha_limite_visible', 'Mostrar fecha limite en inicio', 0, ?)
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
    return 'Fecha limite';
}

function evt_mto_api_default_inicio_deadline_message()
{
    return 'Sin fechas limites por el momento.';
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
        $errorMessage = 'Debe indicar la fecha y hora limite.';
        return false;
    }

    $value = str_replace('T', ' ', $value);
    if (preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}$/', $value)) {
        $value .= ':00';
    }
    if (!preg_match('/^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}:\d{2}$/', $value)) {
        $errorMessage = 'Formato de fecha limite invalido.';
        return false;
    }

    $tz = new DateTimeZone('America/Lima');
    $dt = DateTime::createFromFormat('Y-m-d H:i:s', $value, $tz);
    $errors = DateTime::getLastErrors();
    $hasParseErrors = (is_array($errors) && (!empty($errors['warning_count']) || !empty($errors['error_count'])));
    if (!$dt || $hasParseErrors) {
        $errorMessage = 'Fecha limite invalida.';
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
        'evaluacion_mensajeria' => 'Mensajeria global de evaluacion',
        'evaluacion_mail_derivacion' => 'Correo: derivacion entre oficinas',
        'evaluacion_mail_observacion' => 'Correo: observacion (cotejo/rubrica)',
        'evaluacion_mail_aprob_total' => 'Correo: aprobacion total',
        'evaluacion_mail_solicitud_revision' => 'Correo: solicitud de revision',
        'evaluacion_mail_subsanacion' => 'Correo: subsanacion',
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    evt_mto_api_exit(false, 'Metodo no permitido.', null, 405);
}

if (!isset($_SESSION['usuario'])) {
    evt_mto_api_exit(false, 'Sesion no valida.', null, 401);
}

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
    evt_mto_api_exit(false, 'No autorizado.', null, 403);
}

$csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!evt_mto_validate_csrf_token($csrfToken, 'evt_mantenimiento_admin_csrf')) {
    evt_mto_api_exit(false, 'Token CSRF invalido.', null, 403);
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
    evt_mto_api_exit(false, 'No se pudo inicializar la configuracion de mantenimiento.', null, 500);
}
if (!evt_mto_api_ensure_db_manager_event($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar evento acceso_gestor_db');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de acceso del gestor DB.', null, 500);
}
if (!evt_mto_api_ensure_inicio_deadline_event($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar evento inicio_fecha_limite_visible');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de fecha limite.', null, 500);
}
if (!evt_mto_api_ensure_messaging_events($conexion, $userId > 0 ? $userId : null)) {
    error_log('evt_mantenimiento_api: fallo al inicializar eventos de mensajeria');
    evt_mto_api_exit(false, 'No se pudo inicializar el control de mensajeria.', null, 500);
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
if ($action === 'get_state') {
    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    evt_mto_api_exit(true, 'Estado cargado.', $state);
}

if ($action === 'save_messaging') {
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
        evt_mto_api_exit(false, 'No se recibieron cambios de mensajeria.', null, 422);
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
                throw new Exception('No se pudo preparar actualizacion de mensajeria.');
            }
            mysqli_stmt_bind_param($st, 'iis', $status, $userId, $code);
            if (!mysqli_stmt_execute($st)) {
                mysqli_stmt_close($st);
                throw new Exception('No se pudo guardar estado de mensajeria.');
            }
            mysqli_stmt_close($st);
        }
        mysqli_commit($conexion);
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        error_log('evt_mantenimiento_api save_messaging: ' . $e->getMessage());
        evt_mto_api_exit(false, 'No se pudo guardar la mensajeria.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    evt_mto_api_exit(true, 'Mensajeria actualizada correctamente.', $state);
}

if ($action === 'save_db_manager_access') {
    $dbManagerEstado = isset($_POST['estado']) ? (int)$_POST['estado'] : -1;
    if ($dbManagerEstado !== 0 && $dbManagerEstado !== 1) {
        evt_mto_api_exit(false, 'Estado de acceso al gestor DB invalido.', null, 422);
    }

    $sql = "UPDATE evt_eventos
               SET estado = ?,
                   actualizado_por = ?,
                   actualizado_en = NOW()
             WHERE codigo = 'acceso_gestor_db'
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        evt_mto_api_exit(false, 'No se pudo preparar la actualizacion del gestor DB.', null, 500);
    }
    mysqli_stmt_bind_param($st, 'ii', $dbManagerEstado, $userId);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    if (!$ok) {
        evt_mto_api_exit(false, 'No se pudo guardar la configuracion del gestor DB.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    evt_mto_api_exit(true, 'Acceso a gestor DB actualizado correctamente.', $state);
}

if ($action === 'save_inicio_deadline') {
    $visible = isset($_POST['visible']) ? (int)$_POST['visible'] : -1;
    if ($visible !== 0 && $visible !== 1) {
        evt_mto_api_exit(false, 'Estado de visibilidad invalido.', null, 422);
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
            evt_mto_api_exit(false, $parseError !== null ? $parseError : 'Fecha limite invalida.', null, 422);
        }
        $deadline = $parsed;
    } elseif ($visible === 1) {
        evt_mto_api_exit(false, 'Debe indicar la fecha y hora limite para habilitar el contador.', null, 422);
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
            throw new Exception('No se pudo preparar la actualizacion de visibilidad.');
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
            throw new Exception('No se pudo preparar la actualizacion del contenido.');
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
        evt_mto_api_exit(false, 'No se pudo guardar la configuracion de fecha limite.', null, 500);
    }

    $state = evt_mto_fetch_state();
    $state['db_manager_access'] = evt_mto_api_get_db_manager_state($conexion);
    $state['inicio_deadline'] = evt_mto_api_get_inicio_deadline_state($conexion);
    $state['messaging'] = evt_mto_api_get_messaging_state($conexion);
    evt_mto_api_exit(true, 'Configuracion de fecha limite guardada correctamente.', $state);
}

if ($action !== 'save_config') {
    evt_mto_api_exit(false, 'Accion no permitida.', null, 400);
}

$sistemaActivo = isset($_POST['sistema_activo']) ? (int)$_POST['sistema_activo'] : -1;
if ($sistemaActivo !== 0 && $sistemaActivo !== 1) {
    evt_mto_api_exit(false, 'Estado de sistema invalido.', null, 422);
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
    evt_mto_api_exit(false, 'No se encontro la configuracion de mantenimiento.', null, 500);
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
            throw new Exception('No se pudo preparar la actualizacion.');
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
            throw new Exception('No se pudo preparar la actualizacion.');
        }
        mysqli_stmt_bind_param($st, 'issii', $sistemaActivo, $titulo, $mensaje, $userId, $eventoId);
    }

    if (!mysqli_stmt_execute($st)) {
        mysqli_stmt_close($st);
        throw new Exception('No se pudo guardar la configuracion.');
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
    evt_mto_api_exit(true, 'Configuracion guardada correctamente.', $state);
} catch (Exception $e) {
    mysqli_rollback($conexion);
    error_log('evt_mantenimiento_api save_config: ' . $e->getMessage());
    evt_mto_api_exit(false, 'No se pudo guardar la configuracion de mantenimiento.', null, 500);
}
