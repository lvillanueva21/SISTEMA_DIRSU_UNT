<?php
declare(strict_types=1);

// /sistema_web/informe_semestral/notificaciones_ruta.php
// Funciones en namespace global para invocacion desde handlers.

namespace {

date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../includes/evaluacion_v1/messaging_helpers.php';
require_once __DIR__ . '/../includes/correo_config_service.php';

function _notif_mail_wrap_html(string $innerHtml): string {
    return '<div style="margin:0;padding:0;background:#f5f7fb;">'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dde5ef;font-family:Segoe UI,Arial,sans-serif;">'
        . '<tr><td style="padding:22px 24px;color:#1f2d3d;font-size:14px;line-height:1.56;">'
        . $innerHtml
        . '</td></tr></table></div>';
}

/**
 * Obtiene emails de coordinadores (id_rol=2) del proyecto.
 */
function _notif_destinatarios(\mysqli $db, int $id_py): array {
    $sql = "SELECT DISTINCT uc.email
              FROM usuarios_proyectos up
              INNER JOIN usuarios u
                      ON u.id = up.id_usuario
              INNER JOIN usuario_contactos uc
                      ON uc.usuario COLLATE utf8mb4_unicode_ci
                       = u.usuario   COLLATE utf8mb4_unicode_ci
             WHERE up.id_proyecto = ?
               AND up.activo = 1
               AND u.id_rol = 2
               AND uc.email <> ''";
    $stmt = $db->prepare($sql);
    if (!$stmt) { error_log('notif_ruta: prepare destinatarios: ' . $db->error); return []; }
    $stmt->bind_param('i', $id_py);
    if (!$stmt->execute()) { error_log('notif_ruta: execute destinatarios: ' . $db->error); $stmt->close(); return []; }
    $rs = $stmt->get_result();
    $dest = [];
    while ($r = $rs->fetch_assoc()) {
        $e = trim((string)$r['email']);
        if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $dest[$e] = true;
    }
    $stmt->close();
    return array_keys($dest);
}

/**
 * Obtiene contexto (facultad/departamento) del proyecto para resolver evaluadores destino.
 */
function _notif_contexto_proyecto(\mysqli $db, int $id_py): array {
    $ctx = array(
        'id_py' => $id_py,
        'fac_id' => 0,
        'dep_id' => 0,
    );
    if ($id_py <= 0) {
        return $ctx;
    }

    $sql = "SELECT u.id_depa AS dep_id, d.id_facultad AS fac_id
              FROM usuarios_proyectos up
              INNER JOIN usuarios u ON u.id = up.id_usuario
              LEFT JOIN departamentos d ON d.id = u.id_depa
             WHERE up.id_proyecto = ?
               AND up.activo = 1
               AND u.id_rol = 2
             ORDER BY up.id ASC
             LIMIT 1";
    $st = $db->prepare($sql);
    if (!$st) {
        return $ctx;
    }
    $st->bind_param('i', $id_py);
    if ($st->execute() && ($row = $st->get_result()->fetch_assoc())) {
        $ctx['dep_id'] = isset($row['dep_id']) ? (int)$row['dep_id'] : 0;
        $ctx['fac_id'] = isset($row['fac_id']) ? (int)$row['fac_id'] : 0;
    }
    $st->close();
    return $ctx;
}

/**
 * Detalle de contactos por usuario desde directorio (email + correo_asistente).
 */
function _notif_contactos_directorio_por_usuario(\mysqli $db, array $usuarios): array {
    $out = array();
    $usuarios = array_values(array_unique(array_filter(array_map('strval', $usuarios))));
    if (empty($usuarios)) {
        return $out;
    }

    $place = implode(',', array_fill(0, count($usuarios), '?'));
    $types = str_repeat('s', count($usuarios));
    $sql = "SELECT usuario, email, correo_asistente FROM directorio WHERE usuario IN ($place)";
    $st = $db->prepare($sql);
    if (!$st) {
        return $out;
    }
    $st->bind_param($types, ...$usuarios);
    if ($st->execute()) {
        $rs = $st->get_result();
        while ($row = $rs->fetch_assoc()) {
            $u = trim((string)($row['usuario'] ?? ''));
            if ($u === '') {
                continue;
            }
            $out[$u] = array(
                'email' => trim((string)($row['email'] ?? '')),
                'correo_asistente' => trim((string)($row['correo_asistente'] ?? '')),
            );
        }
    }
    $st->close();
    return $out;
}

/**
 * Destinatarios evaluadores por oficina destino (solo DD y DF).
 * Devuelve:
 * - emails: correos validados (email + asistente)
 * - roles: logins de usuarios evaluadores en alcance
 * - diag: razones de faltantes para auditoria
 */
function _notif_destinatarios_oficina_destino(\mysqli $db, int $id_py, int $of_destino, string $codDestino): array {
    $result = array(
        'emails' => array(),
        'roles' => array(),
        'diag' => array(),
    );
    $codigo = strtoupper(trim($codDestino));
    if ($codigo !== 'DD' && $codigo !== 'DF') {
        $result['diag'][] = 'oficina_destino_sin_notificacion=' . ($codigo !== '' ? $codigo : (string)$of_destino);
        return $result;
    }

    $ctx = _notif_contexto_proyecto($db, $id_py);
    $depId = (int)($ctx['dep_id'] ?? 0);
    $facId = (int)($ctx['fac_id'] ?? 0);
    $rolId = ($codigo === 'DD') ? 4 : 3;

    if ($codigo === 'DD' && $depId <= 0) {
        $result['diag'][] = 'sin_departamento_proyecto';
        return $result;
    }
    if ($codigo === 'DF' && $facId <= 0) {
        $result['diag'][] = 'sin_facultad_proyecto';
        return $result;
    }

    if ($codigo === 'DD') {
        $sql = "SELECT usuario FROM usuarios WHERE id_rol = 4 AND id_depa = ?";
        $st = $db->prepare($sql);
        if (!$st) {
            $result['diag'][] = 'prepare_destinatarios_dd_error';
            return $result;
        }
        $st->bind_param('i', $depId);
    } else {
        $sql = "SELECT usuario FROM usuarios WHERE id_rol = 3 AND id_escuela = ?";
        $st = $db->prepare($sql);
        if (!$st) {
            $result['diag'][] = 'prepare_destinatarios_df_error';
            return $result;
        }
        $st->bind_param('i', $facId);
    }

    $roles = array();
    if ($st->execute()) {
        $rs = $st->get_result();
        while ($row = $rs->fetch_assoc()) {
            $u = trim((string)($row['usuario'] ?? ''));
            if ($u !== '') {
                $roles[$u] = true;
            }
        }
    } else {
        $result['diag'][] = 'execute_destinatarios_error=' . $st->error;
    }
    $st->close();

    $usuarios = array_keys($roles);
    $result['roles'] = $usuarios;
    if (empty($usuarios)) {
        $result['diag'][] = ($codigo === 'DD') ? 'sin_director_departamento_asignado' : 'sin_decano_facultad_asignado';
        return $result;
    }

    $contactos = _notif_contactos_directorio_por_usuario($db, $usuarios);
    $emails = array();
    $faltantes = array();
    foreach ($usuarios as $usuario) {
        $email = '';
        $asistente = '';
        if (isset($contactos[$usuario])) {
            $email = trim((string)($contactos[$usuario]['email'] ?? ''));
            $asistente = trim((string)($contactos[$usuario]['correo_asistente'] ?? ''));
        }

        $has = false;
        if ($email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emails[$email] = true;
            $has = true;
        }
        if ($asistente !== '' && filter_var($asistente, FILTER_VALIDATE_EMAIL)) {
            $emails[$asistente] = true;
            $has = true;
        }
        if (!$has) {
            $faltantes[] = $usuario;
        }
    }

    if (!empty($faltantes)) {
        $result['diag'][] = 'sin_correos_directorio=' . implode(',', $faltantes);
    }
    if (empty($emails)) {
        $result['diag'][] = ($codigo === 'DD') ? 'sin_destinatarios_dd' : 'sin_destinatarios_df';
    }

    $result['emails'] = array_keys($emails);
    return $result;
}

/**
 * Info basica del proyecto.
 */
function _notif_info_proyecto(\mysqli $db, int $id_py): array {
    $titulo = 'Proyecto';
    $periodo = '';
    $stmt = $db->prepare(
        "SELECT p.p2 AS titulo, COALESCE(pr.nombre,'') AS periodo
           FROM proyectos p
           LEFT JOIN proyectos_periodo pp ON pp.id_py = p.id
           LEFT JOIN periodos pr          ON pr.id    = pp.id_periodo
          WHERE p.id = ?
          LIMIT 1"
    );
    if ($stmt) {
        $stmt->bind_param('i', $id_py);
        if ($stmt->execute() && ($row = $stmt->get_result()->fetch_assoc())) {
            $titulo = (string)($row['titulo'] ?? $titulo);
            $periodo = (string)($row['periodo'] ?? $periodo);
        }
        $stmt->close();
    }
    return [$titulo, $periodo];
}

/**
 * Obtiene el código del proyecto (último registrado).
 */
function _notif_codigo_proyecto(\mysqli $db, int $id_py): string {
    if ($id_py <= 0) return '';
    $codigo = '';
    $st = $db->prepare("SELECT codigo FROM proyecto_codigos WHERE id_py = ? ORDER BY id DESC LIMIT 1");
    if ($st) {
        $st->bind_param('i', $id_py);
        if ($st->execute() && ($row = $st->get_result()->fetch_assoc())) {
            $codigo = trim((string)($row['codigo'] ?? ''));
        }
        $st->close();
    }
    return $codigo;
}

function _notif_proyecto_line_html(string $titulo, string $periodo, string $codigo): string {
    $line = '<strong>Proyecto:</strong> ' . htmlspecialchars($titulo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    if ($periodo !== '') {
        $line .= ' — ' . htmlspecialchars($periodo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
    if ($codigo !== '') {
        $line .= ' <span style="color:#4a5568;">(Código: ' . htmlspecialchars($codigo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . ')</span>';
    }
    return $line;
}

function _notif_proyecto_line_text(string $titulo, string $periodo, string $codigo): string {
    $line = 'Proyecto: ' . $titulo;
    if ($periodo !== '') {
        $line .= ' — ' . $periodo;
    }
    if ($codigo !== '') {
        $line .= ' (Código: ' . $codigo . ')';
    }
    return $line;
}

/**
 * Resuelve el semestre real del informe vinculado a la evaluación.
 * Fuente: eva_evaluaciones -> sm_respuestas.id_semestre -> sm_proyecto_semestres.
 */
function _notif_info_semestre_informe(\mysqli $db, int $eval_id): array {
    $meta = array(
        'ok' => false,
        'id_semestre' => 0,
        'anio' => null,
        'periodo' => '',
        'numero' => null,
        'final' => 0,
        'titulo' => '',
        'label' => 'No determinado',
    );

    $sql = "SELECT s.id, s.anio, s.periodo, s.numero, COALESCE(s.final,0) AS final, s.titulo
              FROM eva_evaluaciones e
              INNER JOIN sm_respuestas r ON r.id = e.id_respuesta
              LEFT JOIN sm_proyecto_semestres s ON s.id = r.id_semestre
             WHERE e.id = ?
             LIMIT 1";
    $st = $db->prepare($sql);
    if (!$st) {
        return $meta;
    }
    $st->bind_param('i', $eval_id);
    if ($st->execute() && ($row = $st->get_result()->fetch_assoc())) {
        $meta['ok'] = true;
        $meta['id_semestre'] = isset($row['id']) ? (int)$row['id'] : 0;
        $meta['anio'] = isset($row['anio']) ? (int)$row['anio'] : null;
        $meta['periodo'] = (string)($row['periodo'] ?? '');
        $meta['numero'] = isset($row['numero']) ? (int)$row['numero'] : null;
        $meta['final'] = isset($row['final']) ? (int)$row['final'] : 0;
        $meta['titulo'] = trim((string)($row['titulo'] ?? ''));

        $base = '';
        if (!empty($meta['anio']) && $meta['periodo'] !== '') {
            $base = $meta['anio'] . '-' . $meta['periodo'];
        } elseif ($meta['titulo'] !== '') {
            $base = $meta['titulo'];
        }
        $meta['label'] = $base !== '' ? $base : 'No determinado';
    }
    $st->close();
    return $meta;
}

/**
 * Nombre/codigo de oficina por ID.
 */
function _notif_oficina(\mysqli $db, int $oficina_id): array {
    $cod = '';
    $nom = '';
    $st = $db->prepare('SELECT codigo, nombre FROM eva_oficinas WHERE id=? LIMIT 1');
    if ($st) {
        $st->bind_param('i', $oficina_id);
        if ($st->execute() && ($r = $st->get_result()->fetch_assoc())) {
            $cod = (string)($r['codigo'] ?? '');
            $nom = (string)($r['nombre'] ?? '');
        }
        $st->close();
    }
    return [$cod, $nom];
}

/**
 * Timestamps de la instancia (salida preferente; si no hay, llegada).
 */
function _notif_ts_instancia(\mysqli $db, int $instancia_id): ?int {
    $ts = null;
    $st = $db->prepare('SELECT salida, llegada FROM eva_oficina_instancias WHERE id=? LIMIT 1');
    if ($st) {
        $st->bind_param('i', $instancia_id);
        if ($st->execute() && ($r = $st->get_result()->fetch_assoc())) {
            $ts = $r['salida'] ? strtotime((string)$r['salida']) : ($r['llegada'] ? strtotime((string)$r['llegada']) : null);
        }
        $st->close();
    }
    return $ts;
}

/**
 * Resuelve tipo de informe (semestral/final) desde eval_id.
 */
function _notif_resolver_tipo_informe(\mysqli $db, int $eval_id): array {
    $meta = rsu_eval_v1_report_type_from_eval($db, $eval_id);
    if (!isset($meta['ok']) || !$meta['ok']) {
        $msg = isset($meta['message']) ? (string)$meta['message'] : 'No se pudo determinar el tipo de informe.';
        rsu_eval_v1_notification_add_warning($msg);
        error_log('notif_ruta: tipo informe no resuelto: ' . $msg);
    }
    return $meta;
}

/**
 * Envio via PHPMailer.
 */
function _notif_mail_send(\mysqli $db, array $to, string $subject, string $html, string $text): bool {
    if (empty($to)) return false;
    $errorDetail = '';
    $ok = cor_mail_send_using_active_config($db, $to, $subject, $html, $text, $errorDetail);
    if (!$ok) {
        error_log('Mailer ruta error: ' . $errorDetail);
    }
    return $ok;
}

/**
 * Wrapper centralizado: respeta modo de mensajeria y audita en ev_eventos.
 */
function _notif_mail_controlado(
    \mysqli $db,
    int $eval_id,
    int $office_id,
    ?int $tipo,
    string $event_code,
    array $to,
    string $subject,
    string $html,
    string $text,
    string $skipReason = ''
): bool {
    $id_respuesta = rsu_eval_v1_eval_to_respuesta($db, $eval_id);
    if ($id_respuesta <= 0) {
        rsu_eval_v1_notification_add_warning('La evaluación se guardó, pero no se pudo vincular la notificación al expediente.');
        return false;
    }

    return rsu_eval_v1_notify_mail(
        $db,
        array(
            'id_respuesta' => $id_respuesta,
            'event_code' => $event_code,
            'office' => $office_id,
            'tipo' => $tipo,
            'to' => $to,
            'subject' => $subject,
            'message' => $html,
            'html' => $html,
            'text' => $text,
            'created_by' => isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
            'ip' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
            'skip_reason' => $skipReason,
        ),
        function (array $mailPayload) use ($db, $to, $subject, $html, $text) {
            return _notif_mail_send($db, $to, $subject, $html, $text);
        }
    );
}

/**
 * Notifica derivación: aprobado en origen y derivado a destino.
 * ctx: ['id_py','eval_id','of_origen_id','of_destino_id','instancia_id']
 */
function notif_derivacion(\mysqli $db, array $ctx): bool {
    $id_py       = (int)($ctx['id_py'] ?? 0);
    $eval_id     = (int)($ctx['eval_id'] ?? 0);
    $of_origen   = (int)($ctx['of_origen_id'] ?? 0);
    $of_destino  = (int)($ctx['of_destino_id'] ?? 0);
    $inst_id     = (int)($ctx['instancia_id'] ?? 0);
    if ($id_py <= 0 || $eval_id <= 0 || $of_origen <= 0 || $of_destino <= 0 || $inst_id <= 0) return false;

    $to = _notif_destinatarios($db, $id_py);
    $metaTipo = _notif_resolver_tipo_informe($db, $eval_id);
    if (empty($metaTipo['ok'])) {
        return false;
    }
    $tipoLower = (string)$metaTipo['label_lower'];
    $semMeta = _notif_info_semestre_informe($db, $eval_id);
    $semLabel = (string)$semMeta['label'];

    [$titulo, $periodo] = _notif_info_proyecto($db, $id_py);
    $codigoProyecto = _notif_codigo_proyecto($db, $id_py);
    [$codOri, $nomOri]  = _notif_oficina($db, $of_origen);
    [$codDes, $nomDes]  = _notif_oficina($db, $of_destino);
    $ts = _notif_ts_instancia($db, $inst_id);
    $when = $ts ? date('d/m/Y H:i', $ts) : '';

    $subject = "Tu {$tipoLower} fue derivado a {$nomDes} - Sistema DIRSU";
    $url     = "https://rsu.unitru.edu.pe/sistema_web/login.php";

    $htmlBody = "
      <p><strong>Tu {$tipoLower} fue aprobado en la Oficina {$nomOri}</strong> y ha sido <strong>derivado</strong> a la Oficina {$nomDes}.</p>
      <p>" . ($when ? "<strong>Fecha y hora:</strong> {$when}<br>" : "") . "</p>
      <p>" . _notif_proyecto_line_html($titulo, $periodo, $codigoProyecto) . "</p>
      <p><strong>Semestre del informe:</strong> " . htmlspecialchars($semLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><a href=\"{$url}\" target=\"_blank\" style=\"color:#0a58ca;text-decoration:underline;\">Ingresar al Sistema DIRSU</a></p>
      <hr style=\"border:none;border-top:1px solid #d8dde5;margin:14px 0;\">
      <p style=\"font-size:12px;color:#666\">Este es un correo automático de notificación de derivación.</p>
    ";
    $html = _notif_mail_wrap_html($htmlBody);

    $text  = "Tu {$tipoLower} fue aprobado en la Oficina {$nomOri} y ha sido derivado a la Oficina {$nomDes}.\n";
    if ($when) $text .= "Fecha y hora: {$when}\n";
    $text .= _notif_proyecto_line_text($titulo, $periodo, $codigoProyecto) . "\n";
    $text .= "Semestre del informe: {$semLabel}\n";
    $text .= "Ingresar: {$url}\n";

    $okCoord = _notif_mail_controlado(
        $db,
        $eval_id,
        $of_destino,
        3,
        'MAIL_DERIVACION',
        $to,
        $subject,
        $html,
        $text
    );

    $codigoDestino = strtoupper(trim((string)$codDes));
    if ($codigoDestino !== 'DD' && $codigoDestino !== 'DF') {
        return $okCoord;
    }

    // Notificación adicional a la oficina destino solo cuando es DD/DF.
    $destInfo = _notif_destinatarios_oficina_destino($db, $id_py, $of_destino, $codDes);
    $toOffice = isset($destInfo['emails']) && is_array($destInfo['emails']) ? $destInfo['emails'] : array();
    $diag = isset($destInfo['diag']) && is_array($destInfo['diag']) ? $destInfo['diag'] : array();
    $skipReason = '';
    if (empty($toOffice)) {
        if (!empty($diag)) {
            $skipReason = implode(' | ', $diag);
        } else {
            $skipReason = 'sin_destinatarios_oficina_destino';
        }
    }

    $subjectOffice = "Tienes un {$tipoLower} derivado para visto bueno ({$nomDes}) - Sistema DIRSU";
    $htmlBodyOffice = "
      <p>Hola,</p>
      <p>Ha llegado un <strong>{$tipoLower}</strong> a tu oficina (<strong>{$nomDes}</strong>) para revisión y visto bueno.</p>
      <p>" . ($when ? "<strong>Fecha y hora de derivación:</strong> {$when}<br>" : "") . "</p>
      <p><strong>Oficina origen:</strong> " . htmlspecialchars($nomOri, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p>" . _notif_proyecto_line_html($titulo, $periodo, $codigoProyecto) . "</p>
      <p><strong>Semestre del informe:</strong> " . htmlspecialchars($semLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><a href=\"{$url}\" target=\"_blank\" style=\"color:#0a58ca;text-decoration:underline;\">Ingresar al Sistema DIRSU</a></p>
      <hr style=\"border:none;border-top:1px solid #d8dde5;margin:14px 0;\">
      <p style=\"font-size:12px;color:#666\">Este es un correo automático de derivación a oficina destino.</p>
    ";
    $htmlOffice = _notif_mail_wrap_html($htmlBodyOffice);
    $textOffice = "Hola,\n";
    $textOffice .= "Ha llegado un {$tipoLower} a tu oficina ({$nomDes}) para revisión y visto bueno.\n";
    if ($when) {
        $textOffice .= "Fecha y hora de derivación: {$when}\n";
    }
    $textOffice .= "Oficina origen: {$nomOri}\n";
    $textOffice .= _notif_proyecto_line_text($titulo, $periodo, $codigoProyecto) . "\n";
    $textOffice .= "Semestre del informe: {$semLabel}\n";
    $textOffice .= "Ingresar: {$url}\n";

    $okOffice = _notif_mail_controlado(
        $db,
        $eval_id,
        $of_destino,
        3,
        'MAIL_DERIVACION_OFICINA',
        $toOffice,
        $subjectOffice,
        $htmlOffice,
        $textOffice,
        $skipReason
    );

    return ($okCoord || $okOffice);
}

/**
 * Notifica aprobacion total: proceso culminado exitosamente.
 * ctx: ['id_py','eval_id','of_ultima_id','instancia_id']
 */
function notif_aprobacion_total(\mysqli $db, array $ctx): bool {
    $id_py     = (int)($ctx['id_py'] ?? 0);
    $eval_id   = (int)($ctx['eval_id'] ?? 0);
    $of_ult    = (int)($ctx['of_ultima_id'] ?? 0);
    $inst_id   = (int)($ctx['instancia_id'] ?? 0);
    if ($id_py <= 0 || $eval_id <= 0 || $of_ult <= 0 || $inst_id <= 0) return false;

    $to = _notif_destinatarios($db, $id_py);
    $metaTipo = _notif_resolver_tipo_informe($db, $eval_id);
    if (empty($metaTipo['ok'])) {
        return false;
    }
    $tipoTitle = (string)$metaTipo['label_title'];
    $semMeta = _notif_info_semestre_informe($db, $eval_id);
    $semLabel = (string)$semMeta['label'];

    [$titulo, $periodo] = _notif_info_proyecto($db, $id_py);
    $codigoProyecto = _notif_codigo_proyecto($db, $id_py);
    [$codUlt, $nomUlt]  = _notif_oficina($db, $of_ult);
    $ts = _notif_ts_instancia($db, $inst_id);
    $when = $ts ? date('d/m/Y H:i', $ts) : '';

    $subject = "Aprobación Total ({$tipoTitle}) - Sistema DIRSU";
    $url     = "https://rsu.unitru.edu.pe/sistema_web/login.php";

    $htmlBody = "
      <p><strong>¡Aprobación Total!</strong></p>
      <p>Tu {$tipoTitle} fue <strong>aprobado</strong> en la Oficina {$nomUlt} el " . ($when ?: '—') . ".</p>
      <p>Con esta aprobación, el proceso de revisión ha culminado exitosamente. <strong>No quedan tareas pendientes por realizar.</strong></p>
      <p>" . _notif_proyecto_line_html($titulo, $periodo, $codigoProyecto) . "</p>
      <p><strong>Semestre del informe:</strong> " . htmlspecialchars($semLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . "</p>
      <p><a href=\"{$url}\" target=\"_blank\" style=\"color:#0a58ca;text-decoration:underline;\">Ingresar al Sistema DIRSU</a></p>
      <hr style=\"border:none;border-top:1px solid #d8dde5;margin:14px 0;\">
      <p style=\"font-size:12px;color:#666\">Este es un correo automático de notificación de Aprobación Total.</p>
    ";
    $html = _notif_mail_wrap_html($htmlBody);

    $text  = "¡Aprobación Total!\n";
    $text .= "Tu {$tipoTitle} fue aprobado en la Oficina {$nomUlt}" . ($when ? " el {$when}" : "") . ".\n";
    $text .= "El proceso de revisión ha culminado exitosamente; no quedan tareas pendientes.\n";
    $text .= _notif_proyecto_line_text($titulo, $periodo, $codigoProyecto) . "\n";
    $text .= "Semestre del informe: {$semLabel}\n";
    $text .= "Ingresar: {$url}\n";

    return _notif_mail_controlado(
        $db,
        $eval_id,
        $of_ult,
        3,
        'MAIL_APROB_TOTAL',
        $to,
        $subject,
        $html,
        $text
    );
}

} // fin namespace
