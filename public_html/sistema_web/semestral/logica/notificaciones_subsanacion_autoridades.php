<?php
// semestral/logica/notificaciones_subsanacion_autoridades.php
// Notifica por correo a la(s) autoridad(es) de la oficina que observó el proyecto
// cuando el coordinador envía una subsanación.
// Versión: sin inclusión de observaciones en el correo.

declare(strict_types=1);
date_default_timezone_set('America/Lima');
require_once __DIR__ . '/../../includes/evaluacion_v1/messaging_helpers.php';

/** Obtiene URL base de sistema_web segun host/ruta actual. */
function sistema_web_base_url(): string {
  $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
  $host = isset($_SERVER['HTTP_HOST']) ? trim((string)$_SERVER['HTTP_HOST']) : (isset($_SERVER['SERVER_NAME']) ? trim((string)$_SERVER['SERVER_NAME']) : '');
  $scriptName = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', (string)$_SERVER['SCRIPT_NAME']) : (isset($_SERVER['PHP_SELF']) ? str_replace('\\', '/', (string)$_SERVER['PHP_SELF']) : '');
  if ($host === '' || $scriptName === '') return '';

  if (preg_match('~^(.*?/sistema_web)(?:/|$)~', $scriptName, $m)) {
    return $scheme . '://' . $host . $m[1];
  }
  return '';
}

/** Mapea código/nombre de oficina a id_rol esperado. */
function rolIdDesdeOficina(?string $cod, ?string $nom): ?int {
  $c = strtoupper(trim((string)$cod));
  if ($c === 'RSU') return 1;  // Dirección de RSU
  if ($c === 'PCF') return 5;  // Comité RSU de Facultad
  if ($c === 'DF')  return 3;  // Decanato de Facultad (código real en tu tabla)
  if ($c === 'DD')  return 4;  // Dirección de Departamento

  // Fallback por nombre
  $n = mb_strtolower((string)$nom, 'UTF-8');
  if (strpos($n,'responsabilidad social') !== false || strpos($n,'rsu') !== false) return 1;
  if (strpos($n,'comité') !== false) return 5;
  if (strpos($n,'decan') !== false) return 3;
  if (strpos($n,'departament') !== false) return 4;
  return null;
}

/** Contexto del proyecto/coordinador/facultad/departamento a partir de id_respuesta. */
function obtenerContextoProyecto(mysqli $cx, int $id_respuesta): array {
  $out = [
    'id_py'=>0,'titulo'=>'Proyecto','coord_usuario'=>null,'coord_nom'=>null,'coord_ape'=>null,
    'depa_id'=>null,'depa_nom'=>null,'fac_id'=>null,'fac_nom'=>null
  ];
  $sql = "
    SELECT r.id_py,
           COALESCE(p.p2,'Proyecto') AS titulo,
           u.usuario    AS coord_usuario,
           u.nombres    AS coord_nom,
           u.apellidos  AS coord_ape,
           u.id_depa    AS depa_id,
           d.nombre     AS depa_nom,
           d.id_facultad AS fac_id,
           f.nombre     AS fac_nom
      FROM sm_respuestas r
      JOIN proyectos p            ON p.id = r.id_py
      JOIN usuarios_proyectos up  ON up.id_proyecto = r.id_py AND up.activo = 1
      JOIN usuarios u             ON u.id = up.id_usuario AND u.id_rol = 2
 LEFT JOIN departamentos d        ON d.id = u.id_depa
 LEFT JOIN facultades f           ON f.id = d.id_facultad
     WHERE r.id = ?
     LIMIT 1
  ";
  if ($st = $cx->prepare($sql)) {
    $st->bind_param("i", $id_respuesta);
    if ($st->execute() && ($row = $st->get_result()->fetch_assoc())) {
      foreach ($out as $k=>$_) { if (array_key_exists($k,$row)) $out[$k] = $row[$k]; }
      $out['id_py'] = (int)($row['id_py'] ?? 0);
    }
    $st->close();
  }
  return $out;
}

/** Emails desde directorio (email + correo_asistente) para usuarios (logins). */
function emailsDesdeDirectorio(mysqli $cx, array $usuarios): array {
  $dest = [];
  $usuarios = array_values(array_unique(array_filter(array_map('strval',$usuarios))));
  if (empty($usuarios)) return $dest;

  $place = implode(',', array_fill(0, count($usuarios), '?'));
  $types = str_repeat('s', count($usuarios));

  $sql = "SELECT usuario, email, correo_asistente FROM directorio WHERE usuario IN ($place)";
  if ($st = $cx->prepare($sql)) {
    $st->bind_param($types, ...$usuarios);
    if ($st->execute()) {
      $rs = $st->get_result();
      while ($r = $rs->fetch_assoc()) {
        foreach (['email','correo_asistente'] as $col) {
          $e = trim((string)($r[$col] ?? ''));
          if ($e && filter_var($e, FILTER_VALIDATE_EMAIL)) $dest[$e] = true;
        }
      }
    }
    $st->close();
  }
  return array_keys($dest);
}

/** Usuarios destinatarios según rol y alcance (facultad/departamento). */
function destinatariosPorOficina(mysqli $cx, int $rol_id, array $ctx): array {
  $usuarios = [];
  if ($rol_id === 1) { // RSU: todos los RSU
    if ($rs = $cx->query("SELECT usuario FROM usuarios WHERE id_rol = 1")) {
      while ($r = $rs->fetch_assoc()) { $u = trim((string)$r['usuario']); if ($u) $usuarios[] = $u; }
      $rs->close();
    }
  } elseif ($rol_id === 5) { // PCF por facultad (usuarios.id_escuela ≡ facultad)
    $fac = (int)($ctx['fac_id'] ?? 0);
    if ($fac > 0) {
      $st = $cx->prepare("SELECT usuario FROM usuarios WHERE id_rol=5 AND id_escuela=?");
      $st->bind_param("i",$fac);
      $st->execute();
      $rs = $st->get_result();
      while ($r = $rs->fetch_assoc()) { $u = trim((string)$r['usuario']); if ($u) $usuarios[]=$u; }
      $st->close();
    }
  } elseif ($rol_id === 3) { // Decanato por facultad
    $fac = (int)($ctx['fac_id'] ?? 0);
    if ($fac > 0) {
      $st = $cx->prepare("SELECT usuario FROM usuarios WHERE id_rol=3 AND id_escuela=?");
      $st->bind_param("i",$fac);
      $st->execute();
      $rs = $st->get_result();
      while ($r = $rs->fetch_assoc()) { $u = trim((string)$r['usuario']); if ($u) $usuarios[]=$u; }
      $st->close();
    }
  } elseif ($rol_id === 4) { // Dirección de Departamento por departamento
    $dep = (int)($ctx['depa_id'] ?? 0);
    if ($dep > 0) {
      $st = $cx->prepare("SELECT usuario FROM usuarios WHERE id_rol=4 AND id_depa=?");
      $st->bind_param("i",$dep);
      $st->execute();
      $rs = $st->get_result();
      while ($r = $rs->fetch_assoc()) { $u = trim((string)$r['usuario']); if ($u) $usuarios[]=$u; }
      $st->close();
    }
  }
  return array_values(array_unique($usuarios));
}
/** Envío real con PHPMailer. Devuelve array con ok/error/detail y diag extra. */
function obtenerResumenRubricaSubsanacion(mysqli $cx, int $eval_id, int $ofi_id): array {
  $out = ['html' => '', 'text' => ''];
  if ($eval_id <= 0 || $ofi_id <= 0) return $out;

  $sqlCal = "SELECT id, total FROM eva_calificaciones
             WHERE id_evaluacion = ? AND id_oficina = ? AND tipo = 'rubrica'
             ORDER BY id DESC
             LIMIT 1";
  $stCal = $cx->prepare($sqlCal);
  if (!$stCal) return $out;
  $stCal->bind_param('ii', $eval_id, $ofi_id);
  if (!$stCal->execute()) { $stCal->close(); return $out; }
  $rc = $stCal->get_result()->fetch_assoc();
  $stCal->close();
  if (!$rc || empty($rc['id'])) return $out;

  $id_cal = (int)$rc['id'];
  $total = isset($rc['total']) ? (int)$rc['total'] : null;

  $sqlAsp = "SELECT aspecto, nota, observacion
             FROM eva_rubrica_aspectos
             WHERE id_calificacion = ?
             ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora'), id ASC";
  $stAsp = $cx->prepare($sqlAsp);
  if (!$stAsp) return $out;
  $stAsp->bind_param('i', $id_cal);
  if (!$stAsp->execute()) { $stAsp->close(); return $out; }
  $rs = $stAsp->get_result();
  $rows = [];
  while ($r = $rs->fetch_assoc()) {
    $obs = trim((string)($r['observacion'] ?? ''));
    if ($obs === '') continue;
    $rows[] = [
      'aspecto' => (string)($r['aspecto'] ?? ''),
      'nota' => isset($r['nota']) ? (int)$r['nota'] : 0,
      'obs' => $obs,
    ];
  }
  $stAsp->close();
  if (empty($rows)) return $out;

  $alias = [
    'estructura' => 'Estructura',
    'contenido' => 'Contenido',
    'redaccion' => 'Redaccion',
    'calidad_info' => 'Calidad de informacion',
    'propuesta_mejora' => 'Propuesta de mejora',
  ];

  $html = '<div style="margin-top:10px;">'
    . '<p><strong>Resumen de observaciones de rubrica:</strong></p>'
    . '<table role="presentation" cellspacing="0" cellpadding="6" border="0" style="width:100%;border-collapse:collapse;border:1px solid #ddd;">'
    . '<thead><tr style="background:#f5f5f5;">'
    . '<th align="left" style="border:1px solid #ddd;padding:8px;">Aspecto</th>'
    . '<th align="center" style="border:1px solid #ddd;padding:8px;width:90px;">Nota</th>'
    . '<th align="left" style="border:1px solid #ddd;padding:8px;">Observacion</th>'
    . '</tr></thead><tbody>';

  $text = "Resumen de observaciones de rubrica:\n";
  foreach ($rows as $r) {
    $aspecto = $alias[$r['aspecto']] ?? $r['aspecto'];
    $nota = (int)$r['nota'];
    $obs = (string)$r['obs'];
    $html .= '<tr>'
      . '<td style="border:1px solid #ddd;padding:8px;">' . htmlspecialchars($aspecto, ENT_QUOTES, 'UTF-8') . '</td>'
      . '<td align="center" style="border:1px solid #ddd;padding:8px;">' . $nota . '</td>'
      . '<td style="border:1px solid #ddd;padding:8px;">' . htmlspecialchars($obs, ENT_QUOTES, 'UTF-8') . '</td>'
      . '</tr>';
    $text .= "- {$aspecto} (nota {$nota}): {$obs}\n";
  }

  $html .= '</tbody></table>';
  if ($total !== null) {
    $html .= '<p style="margin-top:8px;"><strong>Puntaje total:</strong> ' . $total . ' / 20</p>';
    $text .= "Puntaje total: {$total}/20\n";
  }
  $html .= '</div>';

  $out['html'] = $html;
  $out['text'] = $text . "\n";
  return $out;
}

function enviarCorreoSubsanacion(array $payload, array &$diag): array {
  if (empty($payload['emails']) || !is_array($payload['emails'])) {
    return ['ok'=>false,'error'=>'Sin destinatarios (emails[])'];
  }

  $docroot = rtrim((string)($_SERVER['DOCUMENT_ROOT'] ?? ''), '/');
  $candidates = array_filter([
    realpath(__DIR__ . '/../recursos/src') ?: (__DIR__ . '/../recursos/src'),
    realpath(__DIR__ . '/../../evaluacion/recursos/src') ?: (__DIR__ . '/../../evaluacion/recursos/src'),
    realpath(__DIR__ . '/../../recursos/src') ?: (__DIR__ . '/../../recursos/src'),
  ]);
  $diag[] = 'phpmailer_candidatos=' . implode(' | ', $candidates);

  $base = null; $layout = null;
  foreach ($candidates as $p) {
    if (is_file($p.'/PHPMailer.php') && is_file($p.'/SMTP.php') && is_file($p.'/Exception.php')) {
      $base = $p; $layout = 'flat'; break;
    }
    if (is_file($p.'/src/PHPMailer.php') && is_file($p.'/src/SMTP.php') && is_file($p.'/src/Exception.php')) {
      $base = $p.'/src'; $layout = 'src'; break;
    }
  }

  $autoloads = [
    __DIR__ . '/../../../vendor/autoload.php',
    __DIR__ . '/../../vendor/autoload.php',
    __DIR__ . '/../../../../vendor/autoload.php',
    $docroot ? $docroot.'/vendor/autoload.php' : null,
  ];
  $autoloads = array_filter($autoloads, fn($p)=> $p && is_file($p));

  if (!$base && !empty($autoloads)) {
    $try = reset($autoloads);
    $diag[] = 'composer_autoload='.$try;
    require_once $try;
  } else {
    $diag[] = 'phpmailer_base=' . ($base ?: 'no_encontrado') . ' layout=' . ($layout ?: '-');
    if (!$base) return ['ok'=>false,'error'=>'PHPMailer no encontrado'];
    require_once $base.'/Exception.php';
    require_once $base.'/PHPMailer.php';
    require_once $base.'/SMTP.php';
  }

  try {
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
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
    foreach ($payload['emails'] as $to) { $mail->addAddress($to); }

    $mail->isHTML(true);
    $mail->Subject = (string)$payload['asunto'];
    $mail->Body    = (string)$payload['html'];
    $mail->AltBody = (string)$payload['text'];

    $ok = $mail->send();
    if (!$ok) return ['ok'=>false,'error'=>'PHPMailer->send() devolvió false','detail'=>$mail->ErrorInfo];
    return ['ok'=>true,'to'=>$payload['emails']];
  } catch (\Throwable $e) {
    return ['ok'=>false,'error'=>'Excepción al enviar','detail'=>$e->getMessage()];
  }
}
/** Flujo principal. Devuelve array con ok/error/diag para que el caller lo muestre. */
function notif_subsanacion_autoridades(mysqli $cx, array $ctx): array {
  // $ctx: ['id_respuesta'=>int,'eval_id'=>int,'oficina_id'=>int,'oficina_cod'=>?,'oficina_nom'=>string]
  $id_resp = (int)($ctx['id_respuesta'] ?? 0);
  $eval_id = (int)($ctx['eval_id'] ?? 0);
  $ofi_id  = (int)($ctx['oficina_id'] ?? 0);
  $ofi_cod = $ctx['oficina_cod'] ?? null;
  $ofi_nom = (string)($ctx['oficina_nom'] ?? '');

  $diag = [];
  if ($id_resp<=0 || $eval_id<=0 || $ofi_id<=0) {
    return ['ok'=>false,'error'=>'Parámetros incompletos (id_respuesta/eval_id/oficina_id).','diag'=>$diag];
  }

  $metaTipoInforme = rsu_eval_v1_report_type($cx, $id_resp);
  if (empty($metaTipoInforme['ok'])) {
    $msgTipo = isset($metaTipoInforme['message']) ? (string)$metaTipoInforme['message'] : 'No se pudo determinar el tipo de informe.';
    return ['ok'=>false,'error'=>$msgTipo,'diag'=>$diag];
  }
  $tipoInformeLower = (string)$metaTipoInforme['label_lower'];

  // 1) Contexto
  $PX = obtenerContextoProyecto($cx, $id_resp);
  $id_py    = (int)$PX['id_py'];
  $titulo   = (string)$PX['titulo'];
  $coordNom = trim(($PX['coord_nom'] ?? '').' '.($PX['coord_ape'] ?? ''));
  $facNom   = (string)($PX['fac_nom'] ?? '');
  $depNom   = (string)($PX['depa_nom'] ?? '');
  $diag[] = "id_py={$id_py} fac_id=".((int)($PX['fac_id'] ?? 0))." depa_id=".((int)($PX['depa_id'] ?? 0));

  // 2) Rol
  $rol_id = rolIdDesdeOficina($ofi_cod, $ofi_nom);
  $diag[] = "rol_id=".((int)$rol_id)." ofi_cod=".($ofi_cod ?? '')." ofi_nom=".$ofi_nom;
  if (!$rol_id) {
    return ['ok'=>false,'error'=>'No se pudo determinar el rol de la oficina (mapeo cod/nombre).','diag'=>$diag];
  }

  // 3) Usuarios y emails
  $usuarios = destinatariosPorOficina($cx, (int)$rol_id, ['fac_id'=>$PX['fac_id'], 'depa_id'=>$PX['depa_id']]);
  $diag[] = 'usuarios_count='.count($usuarios);
  $emails = emailsDesdeDirectorio($cx, $usuarios);
  if ($rol_id === 1 && empty($emails)) { $emails = ['lvillanueva@unitru.edu.pe']; $diag[]='rsu_fallback=1'; }
  $diag[] = 'emails_count='.count($emails);
  if (empty($emails)) {
    return ['ok'=>false,'error'=>'No se encontraron correos en el directorio para los destinatarios.','diag'=>$diag];
  }

  // 4) Contenido (link portable)
  $asunto = "Subsanación enviada de {$tipoInformeLower} — Tienes un proyecto por revisar — PROYECTOS DIRSU";
  $baseUrl = sistema_web_base_url();
  $url    = $baseUrl !== '' ? ($baseUrl . '/login.php') : '../login.php';
  $rubricaResumen = obtenerResumenRubricaSubsanacion($cx, $eval_id, $ofi_id);

  $facDepFraseHTML = '';
  $facDepFraseTXT  = '';
  if ((int)$rol_id !== 1) { // RSU no muestra fac/dep
    $facDepFraseHTML = " que pertenece a la facultad <strong>".htmlspecialchars($facNom,ENT_QUOTES,'UTF-8')."</strong> y departamento <strong>".htmlspecialchars($depNom,ENT_QUOTES,'UTF-8')."</strong>";
    $facDepFraseTXT  = " — Facultad: {$facNom} — Departamento: {$depNom}";
  }

  $html = "
    <p>Hola,</p>
    <p>El proyecto con título: <strong>".htmlspecialchars($titulo,ENT_QUOTES,'UTF-8')."</strong> del coordinador <strong>".htmlspecialchars($coordNom,ENT_QUOTES,'UTF-8')."</strong>{$facDepFraseHTML} ha registrado una <strong>subsanación</strong> de las observaciones hechas por tu oficina (<strong>".htmlspecialchars($ofi_nom,ENT_QUOTES,'UTF-8')."</strong>).</p>
    <p>El siguiente paso es ingresar a la plataforma y volver a revisar el proyecto para aprobarlo si las subsanaciones satisfacen lo requerido.</p>
    ".($rubricaResumen['html'] !== '' ? $rubricaResumen['html'] : '')."
    <p><a href=\"{$url}\" target=\"_blank\">Ingresar al Sistema DIRSU</a></p>
    <hr>
    <p style=\"font-size:12px;color:#666\">Este mensaje se envió automáticamente al/los evaluador(es) de la oficina correspondiente.</p>
  ";

  $text = "Hola,\nEl proyecto: {$titulo} (coordinador: {$coordNom}){$facDepFraseTXT} registró una subsanación a las observaciones de tu oficina ({$ofi_nom}).\n\n"
        . ($rubricaResumen['text'] !== '' ? $rubricaResumen['text'] : '')
        . "Ingresar: {$url}\n";

  // 5) Mensajería controlada + auditoría (envía o solo registra según switch).
  $notifyOk = rsu_eval_v1_notify_mail(
    $cx,
    [
      'id_respuesta' => $id_resp,
      'event_code'   => 'MAIL_SUBSANACION',
      'office'       => $ofi_id,
      'tipo'         => 3,
      'to'           => $emails,
      'subject'      => $asunto,
      'message'      => $html,
      'html'         => $html,
      'text'         => $text,
      'created_by'   => isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
      'ip'           => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null,
    ],
    function(array $mailPayload) use (&$diag, $emails, $asunto, $html, $text) {
      $send = enviarCorreoSubsanacion([
        'asunto' => $asunto,
        'html'   => $html,
        'text'   => $text,
        'emails' => $emails
      ], $diag);
      if (!$send['ok'] && !empty($send['detail'])) {
        $diag[] = 'detail=' . (string)$send['detail'];
      }
      return !empty($send['ok']);
    }
  );

  if (!$notifyOk) {
    return ['ok'=>false,'error'=>'No se pudo enviar o auditar la mensajería de subsanación.','diag'=>$diag];
  }

  return ['ok'=>true,'diag'=>$diag,'to'=>$emails];
}
