<?php
// /sistema_web/evaluacion/modales/detalle_observacion.php
header('Content-Type: text/html; charset=utf-8');

include_once __DIR__ . '/../funciones.php'; // $conexion

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;
$tipo  = isset($_GET['tipo']) ? trim((string)$_GET['tipo']) : '';
$tipo  = in_array($tipo, ['cotejo','rubrica'], true) ? $tipo : '';

if ($id_py <= 0 || $tipo === '') {
  echo '<div class="alert alert-danger mb-0">Parámetros inválidos.</div>';
  exit;
}

/*
  Reglas de fecha:
  - Fecha de observación: preferimos eva_calificaciones (calificación > instancia).
  - Fecha límite: DÍAS HÁBILES (sin sábados ni domingos), sumando N días a la fecha de observación.
*/

// Plazos por defecto si no se registró dias_subsanacion en la calificación
$DEFAULT_DIAS = [
  'cotejo'  => 2, // días hábiles
  'rubrica' => 1, // días hábiles
];

$det = [
  'oficina_nom' => null,
  'oficina_cod' => null,
  'obs_at'      => null,  // fecha/hora de la observación (preferencia: calificación)
  'dias'        => null,  // dias_subsanacion si existe; si no, fallback de $DEFAULT_DIAS
  'limite'      => null,  // obs_at + dias (hábiles)
  'obs_text'    => null,  // solo cotejo
  'total'       => null,  // solo rubrica
  'aspectos'    => [],    // solo rubrica (SIEMPRE listar todos)
];

try {
  // 1) Calificación observada más reciente (de este proyecto y tipo)
  $sql = "SELECT
            c.id, c.id_evaluacion, c.id_oficina,
            o.nombre AS oficina_nom, o.codigo AS oficina_cod,
            c.estado,
            COALESCE(c.ultimo_observado_at, c.actualizado_at) AS obs_at,
            c.total, c.obs_general,
            c.dias_subsanacion
          FROM sm_respuestas r
          JOIN eva_evaluaciones e ON e.id_respuesta = r.id
          JOIN eva_calificaciones c ON c.id_evaluacion = e.id AND c.tipo = ?
          JOIN eva_oficinas o ON o.id = c.id_oficina
          WHERE r.id_py = ? AND c.estado = 'observado'
          ORDER BY COALESCE(c.ultimo_observado_at, c.actualizado_at) DESC, c.id DESC
          LIMIT 1";

  if ($st = $conexion->prepare($sql)) {
    $st->bind_param('si', $tipo, $id_py);
    if ($st->execute()) {
      $res = $st->get_result();
      if ($res && $res->num_rows) {
        $row = $res->fetch_assoc();
        $det['oficina_nom'] = $row['oficina_nom'] ?? null;
        $det['oficina_cod'] = $row['oficina_cod'] ?? null;
        $det['obs_at']      = $row['obs_at'] ?? null;
        $det['dias']        = isset($row['dias_subsanacion']) ? (int)$row['dias_subsanacion'] : null;

        if ($tipo === 'cotejo') {
          $det['obs_text'] = $row['obs_general'] ?? null;
        } else {
          $det['total'] = isset($row['total']) ? (int)$row['total'] : null;

          // 2) Aspectos de rúbrica — SIEMPRE traer todos los aspectos, en orden fijo
          $sqlA = "SELECT aspecto, nota, observacion
                   FROM eva_rubrica_aspectos
                   WHERE id_calificacion = ?
                   ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora')";
          if ($st2 = $conexion->prepare($sqlA)) {
            $idc = (int)$row['id'];
            $st2->bind_param('i', $idc);
            if ($st2->execute()) {
              $res2 = $st2->get_result();
              while ($a = $res2->fetch_assoc()) {
                $det['aspectos'][] = [
                  'aspecto' => (string)$a['aspecto'],
                  'nota'    => (int)$a['nota'],
                  'obs'     => (string)($a['observacion'] ?? ''),
                ];
              }
            }
            $st2->close();
          }

          // Si no vino total, lo calculamos sumando notas
          if ($det['total'] === null && !empty($det['aspectos'])) {
            $suma = 0; foreach ($det['aspectos'] as $ax) $suma += (int)$ax['nota'];
            $det['total'] = $suma;
          }
        }
      }
    }
    $st->close();
  }

  // 1.b) Si no obtuvimos fecha de observación por calificación, intentamos la instancia (fallback)
  if (empty($det['obs_at'])) {
    $sqlI = "SELECT oi.ultima_observacion_at, oi.salida, oi.llegada
             FROM sm_respuestas r
             JOIN eva_evaluaciones e ON e.id_respuesta = r.id
             JOIN eva_oficina_instancias oi
                  ON oi.id_evaluacion = e.id
             WHERE r.id_py = ?
             ORDER BY oi.ultima_observacion_at DESC, oi.salida DESC, oi.llegada DESC
             LIMIT 1";
    if ($stI = $conexion->prepare($sqlI)) {
      $stI->bind_param('i', $id_py);
      if ($stI->execute()) {
        $rI = $stI->get_result();
        if ($rI && $rI->num_rows) {
          $ri = $rI->fetch_assoc();
          $det['obs_at'] = $ri['ultima_observacion_at'] ?: ($ri['salida'] ?: $ri['llegada']);
        }
      }
      $stI->close();
    }
  }

  // 3) Calcular fecha límite (días hábiles)
  if (!empty($det['obs_at'])) {
    $dias = (!empty($det['dias']) && (int)$det['dias'] > 0)
              ? (int)$det['dias']
              : (int)($DEFAULT_DIAS[$tipo] ?? 0);

    if ($dias > 0) {
      $tsObs = strtotime($det['obs_at']);
      if ($tsObs) {
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

        $lim_ts = $addBusinessDays($tsObs, $dias);
        $det['limite'] = date('Y-m-d H:i:s', $lim_ts);
      }
    }
  }

} catch (\Throwable $e) {
  echo '<div class="alert alert-danger">Excepción: '.htmlspecialchars($e->getMessage()).'</div>';
  exit;
}

if (empty($det['oficina_nom'])) {
  echo '<div class="alert alert-warning mb-0">No se encontró una observación activa para este proyecto en “'.htmlspecialchars($tipo).'”.</div>';
  exit;
}

// Utilidades de render
function fmtDT($s){ if(!$s) return '—'; $ts=strtotime($s); return $ts? date('d/m/Y H:i', $ts): htmlspecialchars($s); }

// Mapeo de nombres amigables para aspectos
$names = [
  'estructura'       => 'Estructura',
  'contenido'        => 'Contenido',
  'redaccion'        => 'Redacción',
  'calidad_info'     => 'Calidad de información',
  'propuesta_mejora' => 'Propuesta de Mejora',
];
?>
<div class="container-fluid">
  <div class="row g-3">
    <div class="col-12 col-md-6">
      <div class="mb-2"><strong>Oficina:</strong> <?= htmlspecialchars($det['oficina_nom']) ?></div>
      <div class="mb-2"><strong>Tipo:</strong> <?= ($tipo==='cotejo'?'Lista de Cotejo':'Rúbrica') ?></div>
      <div class="mb-2"><strong>Fecha/Hora de observación:</strong> <?= fmtDT($det['obs_at']) ?></div>
      <div class="mb-2"><strong>Fecha máxima de subsanación:</strong> <?= fmtDT($det['limite']) ?></div>
    </div>

    <?php if ($tipo === 'cotejo'): ?>
      <div class="col-12">
        <hr>
        <div class="mb-2 fw-semibold">Observación</div>
        <div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars((string)$det['obs_text'])) ?></div>
      </div>
    <?php else: ?>
      <div class="col-12">
        <hr>
        <div class="d-flex justify-content-between align-items-center">
          <div class="fw-semibold">Calificación total</div>
          <div class="badge badge-secondary bg-secondary"><?= (int)($det['total'] ?? 0) ?> / 20</div>
        </div>

        <div class="table-responsive mt-2">
          <table class="table table-sm table-bordered">
            <thead class="thead-light">
              <tr>
                <th>Aspecto</th>
                <th style="width:90px;" class="text-center">Nota</th>
                <th>Observación</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($det['aspectos'])): ?>
                <tr><td colspan="3" class="text-muted text-center">Sin detalles de aspectos.</td></tr>
              <?php else: foreach ($det['aspectos'] as $ax): 
                $nom    = $names[$ax['aspecto']] ?? $ax['aspecto'];
                $nota   = (int)$ax['nota'];
                $obsRaw = trim((string)($ax['obs'] ?? ''));
                $obsTxt = ($obsRaw === '') ? 'Sin observación' : $obsRaw;
              ?>
                <tr>
                  <td><?= htmlspecialchars($nom) ?></td>
                  <td class="text-center"><?= $nota ?></td>
                  <td><?= nl2br(htmlspecialchars($obsTxt)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
