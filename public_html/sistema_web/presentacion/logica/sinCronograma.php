<?php
// 1) Traer filas desde sm_proyecto_semestres ordenadas cronológicamente
$q = $conexion->prepare("
  SELECT id, anio, periodo, tipo, numero, final, estado, titulo, fecha_inicio, fecha_fin
  FROM sm_proyecto_semestres
  WHERE id_py = ?
  ORDER BY anio, FIELD(periodo,'I','II'),
           CASE tipo WHEN 'presentacion' THEN 0 ELSE 1 END,
           COALESCE(numero,0)
");
$q->bind_param("i", $id_py);
$q->execute();
$res = $q->get_result();
$rows = $res->fetch_all(MYSQLI_ASSOC);
$q->close();

// 2) Agrupar por semestre (YYYY-I / YYYY-II)
$bySem = [];
foreach ($rows as $r) {
  $key = "{$r['anio']}-{$r['periodo']}";
  if (!isset($bySem[$key])) {
    $bySem[$key] = [
      'anio' => $r['anio'],
      'periodo' => $r['periodo'],
      'items' => []
    ];
  }
  $bySem[$key]['items'][] = $r;
}

// 3) Helpers visuales
function estadoLabel($e) {
  return match((int)$e) {
    2 => 'Aprobado',
    1 => 'Observado',
    default => 'En espera',
  };
}
function estadoClass($e) {
  return match((int)$e) {
    2 => 'st-ok',
    1 => 'st-warn',
    default => 'st-wait',
  };
}
?>
<style>
  .panel-flex{display:flex;border:1px solid #ccc;border-radius:8px;overflow:hidden;max-width:1000px;box-shadow:0 2px 8px rgba(0,0,0,.1)}
  .panel-left{flex:1;padding:20px;background:#f8f9fa;display:flex;align-items:center;justify-content:center;font-weight:bold;font-size:16px;text-align:center}
  .panel-right{flex:2;padding:16px;background:#fff}
  .sem-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(280px,1fr));gap:16px;margin-top:10px}
  .sem-card{border:1px solid #ddd;border-radius:10px;overflow:hidden}
  .sem-head{background:#f1f3f5;padding:8px 12px;font-weight:700;border-bottom:1px solid #ddd;text-align:center}
  .sem-table{width:100%;border-collapse:collapse}
  .sem-table th,.sem-table td{padding:8px 10px;border-bottom:1px solid #eee;font-size:14px}
  .sem-table th{background:#fafafa;text-align:left}
  .pill{display:inline-block;padding:2px 8px;border-radius:999px;font-size:12px;border:1px solid #ddd;background:#fff}
  .st{display:inline-block;padding:2px 8px;border-radius:6px;font-size:12px;border:1px solid transparent}
  .st-ok{background:#e6f7e9;border-color:#b7e3bf;color:#0b6b1c}
  .st-warn{background:#fff6d6;border-color:#f1e19b;color:#7a6500}
  .st-wait{background:#f1f5f9;border-color:#d5dde6;color:#334155}
  .flag-final{font-size:12px;padding:2px 6px;border-radius:6px;border:1px solid #111;background:#111;color:#fff}
  .legend{margin-top:8px;font-size:12px;color:#555}
  .legend span{margin-right:8px}
</style>

<div class="panel-flex">
  <!-- Lado izquierdo -->
  <div class="panel-left">
    <?php if (($sm_info['interfaz'] ?? 0) !== 2): ?>
      Actualmente no tienes un cronograma activo
    <?php else: ?>
      Cronograma activo del <?= htmlspecialchars($sm_info['apertura'] ?? '-') ?>
      al <?= htmlspecialchars($sm_info['cierre'] ?? '-') ?>
    <?php endif; ?>
  </div>

  <!-- Lado derecho -->
  <div class="panel-right">
    <div style="font-size:14px;margin-bottom:8px">
      Revisa la información de tus proyectos.
    </div>

    <?php if (!empty($bySem)): ?>
      <div class="legend">
        <span class="st st-wait">En espera</span>
        <span class="st st-warn">Observado</span>
        <span class="st st-ok">Aprobado</span>
        <span class="flag-final">Informe Final</span>
      </div>

      <div class="sem-grid">
        <?php foreach ($bySem as $semLabel => $sem): ?>
          <div class="sem-card">
            <div class="sem-head">
              <span class="pill"><?= htmlspecialchars($semLabel) ?></span>
            </div>
            <table class="sem-table">
              <thead>
                <tr>
                  <th>Pendiente</th>
                  <th>#</th>
                  <th>Estado</th>
                  <th>Final</th>
                </tr>
              </thead>
              <tbody>
              <?php foreach ($sem['items'] as $it): ?>
                <tr>
                  <td>
                    <?php
                      // Mostrar el título guardado (ya viene "Presentación..." o "Informe Semestral NN")
                      echo htmlspecialchars($it['titulo']);
                    ?>
                  </td>
                  <td style="width:50px;text-align:center">
                    <?= $it['tipo']==='semestral' ? (int)$it['numero'] : '-' ?>
                  </td>
                  <td style="width:120px">
                    <span class="st <?= estadoClass($it['estado']) ?>">
                      <?= estadoLabel($it['estado']) ?>
                    </span>
                  </td>
                  <td style="width:90px;text-align:center">
                    <?php if ((int)$it['final'] === 1): ?>
                      <span class="flag-final">Final</span>
                    <?php else: ?>
                      -
                    <?php endif; ?>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <p>No hay semestres disponibles.</p>
    <?php endif; ?>
  </div>
</div>
