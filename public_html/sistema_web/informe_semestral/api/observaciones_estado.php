<?php
// /sistema_web/informe_semestral/api/observaciones_estado.php
declare(strict_types=1);
header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../funciones.php'; // debe exponer $conexion (mysqli)
date_default_timezone_set('America/Lima');

function fail($msg, $code = 400){
  http_response_code($code);
  echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;
if ($id_py <= 0) fail('Parámetro id_py inválido');

try {
  // Defaults si no hay días guardados (>0)
  $DEFAULT_DIAS = ['cotejo'=>2, 'rubrica'=>1];

  // ---------- helper: última calificación OBSERVADA por tipo ----------
  $fetchObs = function(string $tipo) use ($conexion, $id_py, $DEFAULT_DIAS){
    if (!in_array($tipo, ['cotejo','rubrica'], true)) return null;

    $sql = "
      SELECT
        c.id, c.id_evaluacion, c.id_oficina,
        o.nombre AS oficina_nom, o.codigo AS oficina_cod,
        c.estado,
        COALESCE(c.ultimo_observado_at, c.actualizado_at) AS obs_at,
        c.dias_subsanacion,
        c.total, c.obs_general
      FROM sm_respuestas r
      JOIN eva_evaluaciones e ON e.id_respuesta = r.id
      JOIN eva_calificaciones c ON c.id_evaluacion = e.id AND c.tipo = ?
      JOIN eva_oficinas o ON o.id = c.id_oficina
      WHERE r.id_py = ? AND c.estado = 'observado'
      ORDER BY COALESCE(c.ultimo_observado_at, c.actualizado_at) DESC, c.id DESC
      LIMIT 1
    ";
    if (!($st = $conexion->prepare($sql))) return null;
    $st->bind_param('si', $tipo, $id_py);
    if (!$st->execute()) { $st->close(); return null; }
    $res = $st->get_result();
    if (!$res || !$res->num_rows) { $st->close(); return null; }
    $row = $res->fetch_assoc();
    $st->close();

    $obs_at = $row['obs_at'] ?? null;
    $dias   = isset($row['dias_subsanacion']) ? (int)$row['dias_subsanacion'] : 0;
    if ($dias <= 0) $dias = (int)($DEFAULT_DIAS[$tipo] ?? 0);

    // ---- utilidades de días hábiles (sábado/domingo no cuentan)
    $addBusinessDays = function(int $tsBase, int $n): int {
      if ($n <= 0) return $tsBase;
      $y = (int)date('Y', $tsBase);
      $m = (int)date('n', $tsBase);
      $d = (int)date('j', $tsBase);
      $h = (int)date('G', $tsBase);
      $i = (int)date('i', $tsBase);

      $daysInMonth = function(int $yy,int $mm){ return (int)date('t', strtotime(sprintf('%04d-%02d-01',$yy,$mm))); };
      $dow = function(int $yy,int $mm,int $dd){ return (int)date('w', strtotime(sprintf('%04d-%02d-%02d',$yy,$mm,$dd))); }; // 0=Dom .. 6=Sáb
      $isWeekend = function(int $yy,int $mm,int $dd) use ($dow){ $w=$dow($yy,$mm,$dd); return ($w===0 || $w===6); };

      $rest = (int)$n;
      while ($rest > 0) {
        // avanza 1 día calendario
        $dim = $daysInMonth($y,$m);
        if ($d < $dim) $d++; else { $d=1; $m++; if($m>12){$m=1;$y++;} }
        if (!$isWeekend($y,$m,$d)) $rest--;
      }
      // reconstruye timestamp manteniendo hora:minuto
      return strtotime(sprintf('%04d-%02d-%02d %02d:%02d:00', $y,$m,$d,$h,$i));
    };

    $limite = null;
    if (!empty($obs_at)) {
      $ts = strtotime($obs_at);
      if ($ts) {
        $limite_ts = $addBusinessDays($ts, (int)$dias);
        $limite    = date('Y-m-d H:i:s', $limite_ts);
      }
    }

    $out = [
      'oficina_nom' => (string)($row['oficina_nom'] ?? ''),
      'oficina_cod' => (string)($row['oficina_cod'] ?? ''),
      'obs_at'      => $obs_at,                // datetime
      'limite'      => $limite,                // datetime
      'dias'        => $dias,
    ];

    if ($tipo === 'cotejo') {
      $out['obs_text'] = (string)($row['obs_general'] ?? '');
    } else {
      $out['total'] = isset($row['total']) ? (int)$row['total'] : null;

      // aspectos
      $mapNames = [
        'estructura'       => 'Estructura',
        'contenido'        => 'Contenido',
        'redaccion'        => 'Redacción',
        'calidad_info'     => 'Calidad de información',
        'propuesta_mejora' => 'Propuesta de Mejora',
      ];
      $notaLabel = [0=>'En espera',1=>'Insuficiente',2=>'Mejorable',3=>'Satisfactorio',4=>'Excelente'];

      $st2 = $conexion->prepare("
        SELECT aspecto, nota, observacion
        FROM eva_rubrica_aspectos
        WHERE id_calificacion=?
        ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora')
      ");
      $det = [];
      if ($st2) {
        $idc = (int)$row['id'];
        $st2->bind_param('i', $idc);
        if ($st2->execute()) {
          $r2 = $st2->get_result();
          while ($a = $r2->fetch_assoc()) {
            $nota = (int)($a['nota'] ?? 0);
            $det[] = [
              'aspecto' => $mapNames[$a['aspecto']] ?? (string)$a['aspecto'],
              'nota'    => $nota,
              'notaTx'  => $notaLabel[$nota] ?? (string)$nota,
              'obs'     => trim((string)($a['observacion'] ?? '')) ?: 'Sin Observación',
            ];
          }
        }
        $st2->close();
      }
      $out['aspectos'] = $det;
      if ($out['total'] === null && $det) {
        $sum = 0; foreach ($det as $ax) $sum += (int)$ax['nota'];
        $out['total'] = $sum;
      }
    }

    return $out;
  };

  $cotejo  = $fetchObs('cotejo');
  $rubrica = $fetchObs('rubrica');

  echo json_encode([
    'ok'        => true,
    'id_py'     => $id_py,
    'has_obs'   => (bool)($cotejo || $rubrica),
    'cotejo'    => $cotejo,
    'rubrica'   => $rubrica,
  ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

} catch (\Throwable $e){
  fail($e->getMessage(), 500);
}

