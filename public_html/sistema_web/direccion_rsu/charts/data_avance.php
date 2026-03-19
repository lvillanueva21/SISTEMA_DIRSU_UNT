<?php
require_once("../componentes/db.php");

/* ────────────────────────────────────────────────
   1.  Armamos una vista rápida de cada proyecto
       • obs_cot = 1  si tiene observación Cotejo en PCF
       • obs_rub = 1  si tiene observación Rúbrica en PCF
       • Si el proyecto YA NO está en PCF, se considera SIN observación
   ──────────────────────────────────────────────── */

$sql = "
SELECT
    p.id                                AS id_py,
    f.id                                AS id_fac,
    f.nombre                            AS fac,
    rp.oficina_actual,
    MAX(CASE WHEN ev.tipo='cotejo'  AND ev.estado='observado' THEN 1 ELSE 0 END) AS obs_cot,
    MAX(CASE WHEN ev.tipo='rubrica' AND ev.estado='observado' THEN 1 ELSE 0 END) AS obs_rub
FROM         proyectos p
JOIN         proyectos_periodo    pp ON pp.id_py       = p.id
JOIN         revisiones_proyectos rp ON rp.id_py       = p.id
                                     AND rp.id_periodo = pp.id_periodo
JOIN         usuarios_proyectos   up ON up.id_proyecto = p.id
JOIN         usuarios             u  ON u.id           = up.id_usuario
JOIN         departamentos        d  ON d.id           = u.id_depa
JOIN         facultades           f  ON f.id           = d.id_facultad
LEFT JOIN    evaluaciones         ev ON ev.id_py       = p.id
                                     AND ev.id_periodo = pp.id_periodo
                                     AND ev.oficina    = 'pcf'
WHERE        f.id <> 0
GROUP BY     p.id, f.id, f.nombre, rp.oficina_actual
";

$res = mysqli_query($conexion, $sql);

/* ────────────────────────────────────────────────
   2.  Contadores globales y por facultad
   ──────────────────────────────────────────────── */

$labels  = [];        // nombres de facultad
$sinObs  = $obsCot = $obsRub = [];   // datasets para la barra

$donut   = [];        // $donut[id_fac] = [ 'Sin observación'=>x, 'Obs Cotejo'=>y, 'Obs Rúbrica'=>z ]

while ($row = mysqli_fetch_assoc($res)) {

    $idF  = (int)$row['id_fac'];
    $fac  = $row['fac'];

    /* clasificamos el proyecto */
    $cat = 'Sin observación';
    if ($row['oficina_actual'] === 'pcf') {
        if ($row['obs_cot'])       $cat = 'Obs Cotejo';
        elseif ($row['obs_rub'])   $cat = 'Obs Rúbrica';
    }

    /* inicializa contadores */
    foreach (['Sin observación','Obs Cotejo','Obs Rúbrica'] as $c) {
        $donut[$idF]['label'] = $fac;
        $donut[$idF][$c]   = $donut[$idF][$c] ?? 0;
    }

    $donut[$idF][$cat]++;

}

/* construimos arrays para la barra */
foreach ($donut as $idF => $info) {
    $labels[]  = $info['label'];
    $sinObs[]  = $info['Sin observación'] ?? 0;
    $obsCot[]  = $info['Obs Cotejo']      ?? 0;
    $obsRub[]  = $info['Obs Rúbrica']     ?? 0;
}

?>

<!-- === BAR CHART: visión general de TODAS las facultades === -->
<div class="mb-4">
  <canvas id="barAvance" height="220"></canvas>
</div>

<!-- === DOUGHNUTS individuales por facultad === -->
<div class="row">
<?php foreach ($donut as $idF => $info): ?>
  <div class="col-md-6 mb-4">
    <h6 class="text-center"><?= htmlspecialchars($info['label']) ?></h6>
    <canvas id="chartAvance<?= $idF ?>" height="220"></canvas>
  </div>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ███ Barra global ███ */
  const lblAv   = <?= json_encode($labels,JSON_UNESCAPED_UNICODE) ?>;
  const sinObs  = <?= json_encode($sinObs) ?>;
  const cotObs  = <?= json_encode($obsCot) ?>;
  const rubObs  = <?= json_encode($obsRub) ?>;

  new Chart(document.getElementById('barAvance'),{
    type:'bar',
    data:{
      labels:lblAv,
      datasets:[
        { label:'Sin observación', data:sinObs, backgroundColor:'#28a745', stack:'g' },
        { label:'Obs Cotejo',      data:cotObs, backgroundColor:'#dc3545', stack:'g' },
        { label:'Obs Rúbrica',     data:rubObs, backgroundColor:'#ffc107', stack:'g' }
      ]
    },
    options:{
      plugins:{ legend:{ position:'bottom' } },
      scales:{ y:{ beginAtZero:true, stacked:true }, x:{ stacked:true } }
    }
  });

  /* ███ Doughnuts por facultad ███ */
  <?php foreach ($donut as $idF => $info): ?>
  new Chart(document.getElementById('chartAvance<?= $idF ?>'),{
    type:'doughnut',
    data:{
      labels:['Sin observación','Obs Cotejo','Obs Rúbrica'],
      datasets:[{
        data:[
          <?= $info['Sin observación'] ?>,
          <?= $info['Obs Cotejo']      ?>,
          <?= $info['Obs Rúbrica']     ?>
        ]
      }]
    },
    options:{ plugins:{ legend:{ position:'bottom' } } }
  });
  <?php endforeach; ?>

});
</script>
