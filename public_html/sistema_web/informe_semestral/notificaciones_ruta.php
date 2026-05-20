<?php
declare(strict_types=1);

// /sistema_web/informe_semestral/notificaciones_ruta.php
// Funciones en *namespace global* para poder llamarlas desde handlers con \notif_...

namespace {

date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../includes/evaluacion_v1/messaging_helpers.php';

/**
 * Obtiene emails de coordinadores (id_rol=2) del proyecto (con fix de collation).
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
    if (!$stmt) { error_log('notif_ruta: prepare destinatarios: '.$db->error); return []; }
    $stmt->bind_param("i", $id_py);
    if (!$stmt->execute()) { error_log('notif_ruta: execute destinatarios: '.$db->error); $stmt->close(); return []; }
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
 * Info básica del proyecto: título y período (si existe).
 */
function _notif_info_proyecto(\mysqli $db, int $id_py): array {
    $titulo='Proyecto'; $periodo='';
    $stmt = $db->prepare("
        SELECT p.p2 AS titulo, COALESCE(pr.nombre,'') AS periodo
          FROM proyectos p
          LEFT JOIN proyectos_periodo pp ON pp.id_py = p.id
          LEFT JOIN periodos pr          ON pr.id    = pp.id_periodo
         WHERE p.id = ? LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $id_py);
        if ($stmt->execute() && ($row=$stmt->get_result()->fetch_assoc())) {
            $titulo  = (string)($row['titulo'] ?? $titulo);
            $periodo = (string)($row['periodo'] ?? $periodo);
        }
        $stmt->close();
    }
    return [$titulo, $periodo];
}

/**
 * Nombre/código de oficina por ID.
 */
function _notif_oficina(\mysqli $db, int $oficina_id): array {
    $cod=''; $nom='';
    $st = $db->prepare("SELECT codigo, nombre FROM eva_oficinas WHERE id=? LIMIT 1");
    if ($st) {
        $st->bind_param("i", $oficina_id);
        if ($st->execute() && ($r=$st->get_result()->fetch_assoc())) {
            $cod = (string)($r['codigo'] ?? '');
            $nom = (string)($r['nombre'] ?? '');
        }
        $st->close();
    }
    return [$cod,$nom];
}

/**
 * Timestamps de la instancia (preferimos salida; si no hay, llegada).
 */
function _notif_ts_instancia(\mysqli $db, int $instancia_id): ?int {
    $ts = null;
    $st = $db->prepare("SELECT salida, llegada FROM eva_oficina_instancias WHERE id=? LIMIT 1");
    if ($st) {
        $st->bind_param("i", $instancia_id);
        if ($st->execute() && ($r=$st->get_result()->fetch_assoc())) {
            $ts = $r['salida'] ? strtotime((string)$r['salida']) : ($r['llegada'] ? strtotime((string)$r['llegada']) : null);
        }
        $st->close();
    }
    return $ts;
}

/**
 * Envío (PHPMailer) — igual a tus otros envíos.
 */
function _notif_mail_send(array $to, string $subject, string $html, string $text): bool {
    if (empty($to)) return false;

    $base = realpath(__DIR__ . '/../recursos/src') ?: (__DIR__ . '/../recursos/src');
    foreach ([$base.'/PHPMailer.php',$base.'/SMTP.php',$base.'/Exception.php'] as $p){
        if(!file_exists($p)){ error_log('PHPMailer no encontrado: '.$p); return false; }
    }
    require_once $base.'/Exception.php';
    require_once $base.'/PHPMailer.php';
    require_once $base.'/SMTP.php';

    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
    try{
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'proyectosdirsu@unitru.edu.pe';
        $mail->Password   = 'owmjcvzzurfnocgq';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        $mail->setFrom('proyectosdirsu@unitru.edu.pe','Sistema DIRSU');
        $mail->addReplyTo('proyectosdirsu@unitru.edu.pe','Sistema DIRSU');
        foreach($to as $addr){ $mail->addAddress($addr); }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;

        return $mail->send();
    }catch(\Throwable $e){
        error_log('Mailer ruta error: '.$e->getMessage());
        return false;
    }
}

/**
 * Wrapper centralizado: respeta modo de mensajería y audita en ev_eventos.
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
    string $text
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
        ),
        function (array $mailPayload) use ($to, $subject, $html, $text) {
            return _notif_mail_send($to, $subject, $html, $text);
        }
    );
}

/**
 * Notifica DERIVACIÓN: aprobado en origen y derivado a destino.
 * ctx: ['id_py', 'eval_id', 'of_origen_id', 'of_destino_id', 'instancia_id']
 */
function notif_derivacion(\mysqli $db, array $ctx): bool {
    $id_py       = (int)($ctx['id_py'] ?? 0);
    $of_origen   = (int)($ctx['of_origen_id'] ?? 0);
    $of_destino  = (int)($ctx['of_destino_id'] ?? 0);
    $inst_id     = (int)($ctx['instancia_id'] ?? 0);
    if ($id_py<=0 || $of_origen<=0 || $of_destino<=0 || $inst_id<=0) return false;

    $to = _notif_destinatarios($db, $id_py);
    if (empty($to)) return false;

    [$titulo,$periodo] = _notif_info_proyecto($db, $id_py);
    [$codOri,$nomOri]  = _notif_oficina($db, $of_origen);
    [$codDes,$nomDes]  = _notif_oficina($db, $of_destino);
    $ts = _notif_ts_instancia($db, $inst_id);
    $when = $ts ? date('d/m/Y H:i', $ts) : '';

    $subject = 'Tu informe fue derivado - Sistema DIRSU';
    $url     = "https://rsu.unitru.edu.pe/sistema_web/login.php?id_py={$id_py}";

    $html = "
      <p><strong>Tu proyecto fue aprobado en la Oficina {$nomOri}</strong> y ha sido <strong>derivado</strong> a la Oficina {$nomDes}.</p>
      <p>".($when ? "<strong>Fecha y hora:</strong> {$when}<br>" : "")."</p>
      <p><strong>Proyecto:</strong> ".htmlspecialchars($titulo, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')." (ID {$id_py}) ".($periodo? "— ".htmlspecialchars($periodo, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'):'')."</p>
      <p><a href=\"{$url}\" target=\"_blank\">Ingresar al Sistema DIRSU</a></p>
      <hr>
      <p style=\"font-size:12px;color:#666\">Este es un correo automático de notificación de derivación.</p>
    ";

    $text  = "Tu proyecto fue aprobado en la Oficina {$nomOri} y ha sido derivado a la Oficina {$nomDes}.\n";
    if ($when) $text .= "Fecha y hora: {$when}\n";
    $text .= "Proyecto: {$titulo} (ID {$id_py})".($periodo? " — {$periodo}":"")."\n";
    $text .= "Ingresar: {$url}\n";

    return _notif_mail_controlado(
        $db,
        (int)($ctx['eval_id'] ?? 0),
        $of_destino,
        3,
        'MAIL_DERIVACION',
        $to,
        $subject,
        $html,
        $text
    );
}

/**
 * Notifica APROBACIÓN TOTAL: proceso culminado exitosamente.
 * ctx: ['id_py', 'eval_id', 'of_ultima_id', 'instancia_id']
 */
function notif_aprobacion_total(\mysqli $db, array $ctx): bool {
    $id_py     = (int)($ctx['id_py'] ?? 0);
    $of_ult    = (int)($ctx['of_ultima_id'] ?? 0);
    $inst_id   = (int)($ctx['instancia_id'] ?? 0);
    if ($id_py<=0 || $of_ult<=0 || $inst_id<=0) return false;

    $to = _notif_destinatarios($db, $id_py);
    if (empty($to)) return false;

    [$titulo,$periodo] = _notif_info_proyecto($db, $id_py);
    [$codUlt,$nomUlt]  = _notif_oficina($db, $of_ult);
    $ts = _notif_ts_instancia($db, $inst_id);
    $when = $ts ? date('d/m/Y H:i', $ts) : '';

    $subject = 'Aprobación Total — Sistema DIRSU';
    $url     = "https://rsu.unitru.edu.pe/sistema_web/login.php?id_py={$id_py}";

    $html = "
      <p><strong>¡Aprobación Total!</strong></p>
      <p>Tu proyecto fue <strong>aprobado</strong> en la Oficina {$nomUlt} el ".($when ?: '—').".</p>
      <p>Con esta aprobación, el proceso de revisión ha culminado exitosamente. <strong>No quedan tareas pendientes por realizar.</strong></p>
      <p><strong>Proyecto:</strong> ".htmlspecialchars($titulo, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8')." (ID {$id_py}) ".($periodo? "— ".htmlspecialchars($periodo, ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'):'')."</p>
      <p><a href=\"{$url}\" target=\"_blank\">Ingresar al Sistema DIRSU</a></p>
      <hr>
      <p style=\"font-size:12px;color:#666\">Este es un correo automático de notificación de Aprobación Total.</p>
    ";

    $text  = "¡Aprobación Total!\n";
    $text .= "Tu proyecto fue aprobado en la Oficina {$nomUlt}".($when? " el {$when}":"").".\n";
    $text .= "El proceso de revisión ha culminado exitosamente; no quedan tareas pendientes.\n";
    $text .= "Proyecto: {$titulo} (ID {$id_py})".($periodo? " — {$periodo}":"")."\n";
    $text .= "Ingresar: {$url}\n";

    return _notif_mail_controlado(
        $db,
        (int)($ctx['eval_id'] ?? 0),
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
