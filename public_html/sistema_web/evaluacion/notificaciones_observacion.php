<?php
declare(strict_types=1);

// /sistema_web/informe_semestral/notificaciones_observacion.php
// Define la función en el namespace GLOBAL para evitar conflictos con includes desde archivos con namespace.
namespace {

date_default_timezone_set('America/Lima');
require_once __DIR__ . '/notificaciones_ruta.php';
require_once __DIR__ . '/../includes/evaluacion_v1/messaging_helpers.php';

function notif_obs_wrap_html(string $innerHtml): string {
    return '<div style="margin:0;padding:0;background:#f5f7fb;">'
        . '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;max-width:760px;margin:0 auto;background:#ffffff;border:1px solid #dde5ef;font-family:Segoe UI,Arial,sans-serif;">'
        . '<tr><td style="padding:22px 24px;color:#1f2d3d;font-size:14px;line-height:1.56;">'
        . $innerHtml
        . '</td></tr></table></div>';
}

/**
 * Envía correo de observación a coordinadores activos (id_rol=2) del proyecto,
 * con fecha/hora límite (DÍAS HÁBILES) y, si es RÚBRICA, una tabla de aspectos con (n) + significado.
 *
 * @param \mysqli $conexion
 * @param array   $ctx [
 *   'id_py'       => int,
 *   'eval_id'     => int,
 *   'oficina_id'  => int,
 *   'tipo'        => 'cotejo'|'rubrica',
 *   'obs_text'    => string|null  // texto general solo para Cotejo (opcional)
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

    $metaTipoInforme = _notif_resolver_tipo_informe($conexion, $eval_id);
    if (empty($metaTipoInforme['ok'])) {
        return false;
    }
    $tipoInformeTitle = (string)$metaTipoInforme['label_title'];
    $semMeta = _notif_info_semestre_informe($conexion, $eval_id);
    $semLabel = (string)$semMeta['label'];

    /* ===== 1) Destinatarios (coordinadores activos con email) ===== */
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
    if (empty($dest)) { error_log('notif_obs: sin destinatarios'); }

    /* ===== 2) Proyecto & período ===== */
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

    /* ===== 3) Oficina ===== */
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

    /* ===== 4) Calificación OBSERVADA más reciente ===== */
    $cal_id = null;
    $obs_ts = null;
    $obs_at_txt = '';
    $total_rb = null;
    $obs_general_cotejo = null;

    $stmt = $conexion->prepare("
        SELECT id, COALESCE(ultimo_observado_at, actualizado_at) AS obs_at,
               total, obs_general
          FROM eva_calificaciones
         WHERE id_evaluacion = ?
           AND id_oficina    = ?
           AND tipo          = ?
           AND estado        = 'observado'
         ORDER BY COALESCE(ultimo_observado_at, actualizado_at) DESC, id DESC
         LIMIT 1
    ");
    if ($stmt) {
        $stmt->bind_param("iis", $eval_id, $oficina_id, $tipo);
        if ($stmt->execute() && ($row = $stmt->get_result()->fetch_assoc())) {
            $cal_id = (int)$row['id'];
            $obs_ts = strtotime((string)$row['obs_at']);
            $obs_at_txt = $obs_ts ? date('d/m/Y H:i', $obs_ts) : '';
            if ($tipo === 'rubrica') {
                $total_rb = isset($row['total']) ? (int)$row['total'] : null;
            } else {
                $obs_general_cotejo = (string)($row['obs_general'] ?? '');
            }
        }
        $stmt->close();
    }
    if (!$cal_id) { error_log('notif_obs: no hay calificación observada'); return false; }

    /* ===== 4.1) días de subsanación + fecha límite (DÍAS HÁBILES) ===== */
    $dias_plazo = null;
    $stmt = $conexion->prepare("SELECT dias_subsanacion FROM eva_calificaciones WHERE id = ? LIMIT 1");
    if ($stmt) {
        $stmt->bind_param("i", $cal_id);
        if ($stmt->execute() && ($row = $stmt->get_result()->fetch_assoc())) {
            if (isset($row['dias_subsanacion'])) $dias_plazo = (int)$row['dias_subsanacion'];
        }
        $stmt->close();
    }
    if (!$dias_plazo || $dias_plazo <= 0) {
        $dias_plazo = ($tipo === 'cotejo') ? 2 : 1; // fallback
    }

    $fecha_limite_txt = '';
    $dias_laborables_restantes = null;
    if ($obs_ts) {
        // utilidades de días hábiles
        $addBusinessDays = function(int $tsBase, int $n): int {
            if ($n <= 0) return $tsBase;
            $y = (int)date('Y', $tsBase);
            $m = (int)date('n', $tsBase);
            $d = (int)date('j', $tsBase);
            $h = (int)date('G', $tsBase);
            $i = (int)date('i', $tsBase);

            $daysInMonth = function(int $yy,int $mm){ return (int)date('t', strtotime(sprintf('%04d-%02d-01',$yy,$mm))); };
            $dow = function(int $yy,int $mm,int $dd){ return (int)date('w', strtotime(sprintf('%04d-%02d-%02d',$yy,$mm,$dd))); }; // 0=Dom..6=Sáb
            $isWeekend = function(int $yy,int $mm,int $dd) use ($dow){ $w=$dow($yy,$mm,$dd); return ($w===0 || $w===6); };

            $rest = (int)$n;
            while ($rest > 0) {
                $dim = $daysInMonth($y,$m);
                if ($d < $dim) $d++; else { $d=1; $m++; if($m>12){$m=1;$y++;} }
                if (!$isWeekend($y,$m,$d)) $rest--;
            }
            return strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $y,$m,$d,$h,$i));
        };

        $countBusinessDaysFromNow = function(int $tsLimite): int {
            $now = time();
            if ($tsLimite <= $now) return 0;
            $start = strtotime(date('Y-m-d', $now));
            $end = strtotime(date('Y-m-d', $tsLimite));
            $count = 0;
            for ($ts = $start + 86400; $ts <= $end; $ts += 86400) {
                $w = (int)date('w', $ts); // 0=Dom, 6=Sáb
                if ($w >= 1 && $w <= 5) {
                    $count++;
                }
            }
            return $count;
        };

        $lim_ts = $addBusinessDays($obs_ts, (int)$dias_plazo);
        $fecha_limite_txt = date('d/m/Y H:i', $lim_ts);
        $dias_laborables_restantes = $countBusinessDaysFromNow($lim_ts);
    }

    /* ===== 4.2) Si es RÚBRICA, armar tabla de aspectos ===== */
    $rubrica_html = '';
    if ($tipo === 'rubrica') {
        $mapNames = [
            'estructura'       => 'Estructura',
            'contenido'        => 'Contenido',
            'redaccion'        => 'Redacción',
            'calidad_info'     => 'Calidad de información',
            'propuesta_mejora' => 'Propuesta de Mejora',
        ];
        $notaLabel = [
            0 => 'En espera',
            1 => 'Insuficiente',
            2 => 'Mejorable',
            3 => 'Satisfactorio',
            4 => 'Excelente',
        ];

        $stmt = $conexion->prepare("
            SELECT aspecto, nota, observacion
              FROM eva_rubrica_aspectos
             WHERE id_calificacion = ?
             ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora')
        ");
        $rows = [];
        if ($stmt) {
            $stmt->bind_param("i", $cal_id);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($a = $res->fetch_assoc()) {
                    $aspKey = (string)$a['aspecto'];
                    $aspNom = $mapNames[$aspKey] ?? $aspKey;
                    $nota   = (int)($a['nota'] ?? 0);
                    $notaTx = $notaLabel[$nota] ?? (string)$nota;
                    $obsA   = trim((string)($a['observacion'] ?? ''));
                    $rows[] = [
                        'aspecto' => $aspNom,
                        'nota'    => $nota,
                        'notaTx'  => $notaTx,
                        'obs'     => ($obsA === '' ? 'Sin Observación' : $obsA),
                    ];
                }
            }
            $stmt->close();
        }

        // Tabla HTML (estilos inline)
        if (!empty($rows)) {
            $rubrica_html  = '<table role="presentation" cellspacing="0" cellpadding="0" border="0" style="width:100%;border-collapse:collapse;border:1px solid #d7dee7;margin:8px 0 14px 0;">';
            $rubrica_html .=   '<thead>';
            $rubrica_html .=     '<tr style="background:#f3f7fb;">';
            $rubrica_html .=       '<th align="left"  style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;font-weight:700;color:#1f2d3d;">Aspecto</th>';
            $rubrica_html .=       '<th align="center"style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;font-weight:700;color:#1f2d3d;width:140px;">Nota</th>';
            $rubrica_html .=       '<th align="left"  style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;font-weight:700;color:#1f2d3d;">Observación</th>';
            $rubrica_html .=     '</tr>';
            $rubrica_html .=   '</thead><tbody>';
            foreach ($rows as $r) {
                $notaCell = '<span style="white-space:nowrap;"><strong>(' . (int)$r['nota'] . ')</strong> ' . htmlspecialchars($r['notaTx'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</span>';
                $obsCell  = '<em>' . nl2br(htmlspecialchars($r['obs'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')) . '</em>';
                $rubrica_html .= '<tr>';
                $rubrica_html .=   '<td style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;line-height:1.45;color:#1f2d3d;">' . htmlspecialchars($r['aspecto'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</td>';
                $rubrica_html .=   '<td align="center" style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;line-height:1.45;color:#1f2d3d;">' . $notaCell . '</td>';
                $rubrica_html .=   '<td style="border:1px solid #d7dee7;padding:9px 10px;font-size:13px;line-height:1.45;color:#1f2d3d;">' . $obsCell . '</td>';
                $rubrica_html .= '</tr>';
            }
            $rubrica_html .=   '</tbody></table>';
            if ($total_rb !== null) {
                $rubrica_html .= '<p style="margin:0 0 13px 0;"><strong>Puntaje total:</strong> ' . (int)$total_rb . ' / 20</p>';
            }
        }
    }

    /* ===== 5) Cuerpo del correo ===== */
    $subject  = "Recibiste una Observación de {$tipoInformeTitle} en {$of_nom} - Sistema DIRSU";
    $url_det  = "https://rsu.unitru.edu.pe/sistema_web/login.php";
    $tipo_txt = ($tipo === 'cotejo') ? 'Por lista de Cotejo' : 'Por evaluación de Rúbrica';
    $codigoProyecto = _notif_codigo_proyecto($conexion, $id_py);

    if ($tipo === 'cotejo' && !$obs_text && $obs_general_cotejo) {
        $obs_text = $obs_general_cotejo;
    }
    $lineaObs = ($tipo === 'cotejo' && $obs_text !== null && $obs_text !== '')
        ? "<p><strong>Observación:</strong><br><em>".nl2br(htmlspecialchars($obs_text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'))."</em></p>"
        : "";

    $lineaPlazo = ($fecha_limite_txt !== '')
        ? "<p><strong>Fecha máxima de subsanación:</strong> {$fecha_limite_txt}"
          . ($dias_laborables_restantes !== null
              ? " <em>(" . $dias_laborables_restantes . " " . ($dias_laborables_restantes === 1 ? "día laborable restante" : "días laborables restantes") . ")</em>"
              : "")
          . "</p>"
        : "";

    $htmlBody = "
      <p><strong>Recibiste una observación.</strong></p>
      <p>" . _notif_proyecto_line_html($titulo, $periodo, $codigoProyecto) . "</p>
      <p><strong>Semestre del informe:</strong> ".htmlspecialchars($semLabel, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')."</p>
      <p><strong>Oficina:</strong> ".htmlspecialchars($of_nom, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')." ({$of_cod})
         &nbsp;|&nbsp; <strong>Tipo de observación:</strong> {$tipo_txt}".
        ($obs_at_txt ? " &nbsp;|&nbsp; <strong>Fecha:</strong> {$obs_at_txt}" : "")
      ."</p>
      {$lineaObs}
      ".($rubrica_html ?: '')."
      {$lineaPlazo}
      <p><a href=\"{$url_det}\" target=\"_blank\" style=\"color:#0a58ca;text-decoration:underline;\">Presiona para ir al Sistema DIRSU y subsanar.</a></p>
      <hr style=\"border:none;border-top:1px solid #d8dde5;margin:14px 0;\">
      <p style=\"font-size:12px;color:#666\">Si tienes dudas, contacta a ".htmlspecialchars($of_nom ?: 'la oficina correspondiente', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8')." o responde este correo.</p>
    ";
    $html = notif_obs_wrap_html($htmlBody);

    /* ===== Texto plano ===== */
    $text  = "Recibiste una observación.\n";
    $text .= _notif_proyecto_line_text($titulo, $periodo, $codigoProyecto) . "\n";
    $text .= "Semestre del informe: {$semLabel}\n";
    $text .= "Oficina: {$of_nom} ({$of_cod}) | Tipo de observación: {$tipo_txt}".($obs_at_txt ? " | Fecha: {$obs_at_txt}" : "")."\n";
    if ($tipo === 'cotejo' && $obs_text) {
        $text .= "Observación:\n{$obs_text}\n";
    }
    if ($tipo === 'rubrica' && $cal_id) {
        $mapNames = ['estructura'=>'Estructura','contenido'=>'Contenido','redaccion'=>'Redacción','calidad_info'=>'Calidad de información','propuesta_mejora'=>'Propuesta de Mejora'];
        $notaLabel = [0=>'En espera',1=>'Insuficiente',2=>'Mejorable',3=>'Satisfactorio',4=>'Excelente'];
        $stmt = $conexion->prepare("
            SELECT aspecto, nota, observacion
              FROM eva_rubrica_aspectos
             WHERE id_calificacion = ?
             ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora')
        ");
        if ($stmt) {
            $stmt->bind_param("i", $cal_id);
            if ($stmt->execute()) {
                $res = $stmt->get_result();
                while ($a = $res->fetch_assoc()) {
                    $nom  = $mapNames[$a['aspecto']] ?? $a['aspecto'];
                    $nota = (int)($a['nota'] ?? 0);
                    $tx   = $notaLabel[$nota] ?? (string)$nota;
                    $obsA = trim((string)($a['observacion'] ?? ''));
                    $text .= "- {$nom}: ({$nota}) {$tx} — ".($obsA === '' ? 'Sin Observación' : $obsA)."\n";
                }
            }
            $stmt->close();
        }
        if ($total_rb !== null) $text .= "Puntaje total: {$total_rb} / 20\n";
    }
    if ($fecha_limite_txt) {
        $text .= "Fecha máxima de subsanación: {$fecha_limite_txt}";
        if ($dias_laborables_restantes !== null) {
            $text .= " ({$dias_laborables_restantes} " . ($dias_laborables_restantes === 1 ? "día laborable restante" : "días laborables restantes") . ")";
        }
        $text .= "\n";
    }
    $text .= "Revisar y subsanar: {$url_det}\n";

    $tipo_num = ($tipo === 'cotejo') ? 1 : 2;
    return _notif_mail_controlado(
        $conexion,
        $eval_id,
        $oficina_id,
        $tipo_num,
        'MAIL_OBSERVACION',
        array_keys($dest),
        $subject,
        $html,
        $text
    );
}

} // <- fin namespace global

