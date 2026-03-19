<?php
// /sistema_web/evaluacion/notificaciones_observacion.php
// Envia un correo personalizado cuando un proyecto queda "observado".
// Mensaje base + datos del proyecto, oficina y tipo (cotejo/rúbrica).
// Reutiliza PHPMailer con la misma configuración SMTP de tu sistema.

date_default_timezone_set('America/Lima');

/**
 * Envía correo de observación a coordinadores activos (id_rol=2) del proyecto.
 *
 * @param \mysqli $conexion
 * @param array   $ctx [
 *   'id_py'       => int,             // ID proyecto
 *   'eval_id'     => int,             // ID evaluación actual
 *   'oficina_id'  => int,             // ID oficina actual
 *   'tipo'        => 'cotejo'|'rubrica',
 *   'obs_text'    => string|null      // opcional: texto de observación (cotejo)
 * ]
 * @return bool
 */
function notif_observacion_personalizada(\mysqli $conexion, array $ctx): bool {
    $id_py      = (int)($ctx['id_py'] ?? 0);
    $eval_id    = (int)($ctx['eval_id'] ?? 0);
    $oficina_id = (int)($ctx['oficina_id'] ?? 0);
    $tipo       = strtolower((string)($ctx['tipo'] ?? ''));
    $obs_text   = isset($ctx['obs_text']) ? (string)$ctx['obs_text'] : null;

    if ($id_py <= 0 || $eval_id <= 0 || $oficina_id <= 0 || !in_array($tipo, ['cotejo','rubrica'], true)) {
        error_log('notif_obs: parámetros inválidos');
        return false;
    }

    /* ========== 1) Destinatarios (coordinadores activos con email) ========== */
    // Evita "Illegal mix of collations" forzando COLLATE en el JOIN por 'usuario'
    $sqlTo = "SELECT DISTINCT uc.email
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
    $stmt = $conexion->prepare($sqlTo);
    if (!$stmt) { error_log('notif_obs: prepare destinatarios falló: '.$conexion->error); return false; }
    $stmt->bind_param("i", $id_py);
    if (!$stmt->execute()) { error_log('notif_obs: execute destinatarios falló: '.$conexion->error); $stmt->close(); return false; }
    $rs   = $stmt->get_result();
    $dest = [];
    while ($r = $rs->fetch_assoc()) {
        $e = trim((string)$r['email']);
        if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $dest[$e] = true;
    }
    $stmt->close();
    if (empty($dest)) { error_log('notif_obs: sin destinatarios'); return false; }

    /* ========== 2) Datos del proyecto y periodo ========== */
    $titulo  = 'Proyecto';
    $periodo = '';
    $stmt = $conexion->prepare("
        SELECT p.p2 AS titulo, COALESCE(pr.nombre,'') AS periodo
          FROM proyectos p
          LEFT JOIN proyectos_periodo pp ON pp.id_py = p.id
          LEFT JOIN periodos pr          ON pr.id    = pp.id_periodo
         WHERE p.id = ? LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("i", $id_py);
        if ($stmt->execute()) {
            if ($row = $stmt->get_result()->fetch_assoc()) {
                $titulo  = (string)($row['titulo'] ?? $titulo);
                $periodo = (string)($row['periodo'] ?? $periodo);
            }
        }
        $stmt->close();
    }

    /* ========== 3) Oficina (código y nombre) ========== */
    $of_cod = ''; $of_nom = '';
    $stmt = $conexion->prepare("SELECT codigo, nombre FROM eva_oficinas WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $oficina_id);
        if ($stmt->execute() && ($row = $stmt->get_result()->fetch_assoc())) {
            $of_cod = (string)($row['codigo'] ?? '');
            $of_nom = (string)($row['nombre'] ?? '');
        }
        $stmt->close();
    }

    /* ========== 4) Fecha/hora de la observación (última calificación observada) ========== */
    $obs_at = '';
    $stmt = $conexion->prepare("
        SELECT actualizado_at
          FROM eva_calificaciones
         WHERE id_evaluacion = ?
           AND id_oficina    = ?
           AND tipo          = ?
           AND estado        = 'observado'
         ORDER BY id DESC
         LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("iis", $eval_id, $oficina_id, $tipo);
        if ($stmt->execute() && ($row = $stmt->get_result()->fetch_assoc())) {
            $ts = strtotime((string)$row['actualizado_at']);
            $obs_at = $ts ? date('d/m/Y H:i', $ts) : '';
        }
        $stmt->close();
    }

    /* ========== 5) Contenido del correo ========== */
    $subject = "[Proyecto #{$id_py}] Observación en {$of_nom}";
    $url_det = "/sistema_web/evaluacion/modales/detalle_observacion.php?id_py={$id_py}&tipo={$tipo}";
    $tipo_txt = ($tipo === 'cotejo') ? 'Cotejo' : 'Rúbrica';
    $lineaObs = ($obs_text !== null && $obs_text !== '')
        ? "<p><strong>Detalle:</strong><br>".nl2br(htmlspecialchars($obs_text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))."</p>"
        : "";

    $html = "
      <p><strong>Recibiste una observación.</strong></p>
      <p><strong>Proyecto:</strong> ".htmlspecialchars($titulo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')." (ID {$id_py}) ".($periodo ? "— ".htmlspecialchars($periodo, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') : "")."</p>
      <p><strong>Oficina:</strong> ".htmlspecialchars($of_nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')." ({$of_cod})
         &nbsp;|&nbsp; <strong>Tipo:</strong> {$tipo_txt}".
        ($obs_at ? " &nbsp;|&nbsp; <strong>Fecha:</strong> {$obs_at}" : "")
      ."</p>
      {$lineaObs}
      <p><a href=\"{$url_det}\" target=\"_blank\">Revisar y subsanar ahora</a></p>
      <hr>
      <p style=\"font-size:12px;color:#666\">Si tienes dudas, contacta a ".htmlspecialchars($of_nom ?: 'la oficina correspondiente', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')." o responde este correo.</p>
    ";

    $text = "Recibiste una observación.\n"
          . "Proyecto: {$titulo} (ID {$id_py})".($periodo ? " — {$periodo}" : "")."\n"
          . "Oficina: {$of_nom} ({$of_cod}) | Tipo: {$tipo_txt}".($obs_at ? " | Fecha: {$obs_at}" : "")."\n"
          . ($obs_text ? "Detalle:\n{$obs_text}\n" : "")
          . "Revisar y subsanar: {$url_det}\n";

    /* ========== 6) PHPMailer (misma config de tu sistema) ========== */
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
        foreach(array_keys($dest) as $to){ $mail->addAddress($to); }

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $html;
        $mail->AltBody = $text;

        return $mail->send();
    }catch(\Throwable $e){
        error_log('Mailer obs error: '.$e->getMessage());
        return false;
    }
}
