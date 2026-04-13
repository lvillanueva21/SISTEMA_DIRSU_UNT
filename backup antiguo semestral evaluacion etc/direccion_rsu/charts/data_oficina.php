<?php
require_once(__DIR__.'/../../componentes/db.php');   // ← ruta absoluta segura

/* ───────────────────────── 1.  Constantes ───────────────────────── */
$mapOfi = [                         // orden fijo para los gráficos
  'sin' => 'Sin oficina',
  'pcf' => 'Comité de Facultad',
  'dd'  => 'Dirección de Departamento',
  'df'  => 'Decanato de Facultad',
  'rsu' => 'Dirección de RSU'
];

/* ───────────────────────── 2.  Consulta única ─────────────────────────
   • Calcula la oficina “real” con la misma lógica corregida
   • Cuenta proyectos por facultad y oficina_real                        */
$sql = "
SELECT
  f.id                           AS id_fac,
  f.nombre                       AS facultad,
  ofi.oficina_real,
  COUNT(*)                       AS total
FROM (

  /* —— sub-select: proyecto + oficina_real —— */
  SELECT
    p.id,
    d.id_facultad,
    CASE
      /* ①  Sigue en PCF */
      WHEN rp.oficina_actual = 'pcf' AND p.estado = 1
           THEN 'pcf'

      /* ②  Volvió a borrador pero tiene observaciones PCF */
      WHEN rp.oficina_actual = 'pcf' AND p.estado = 0
           AND ( ev_pcf_cot.estado = 'observado'
              OR ev_pcf_rub.estado = 'observado')
           THEN 'pcf'

      /* ③  Era “pcf” por defecto  →  se trata como SIN oficina */
      WHEN rp.oficina_actual = 'pcf'
           THEN 'sin'

      /* ④  Cualquier otra oficina (incluye NULL) */
      ELSE IFNULL(rp.oficina_actual,'sin')
    END AS oficina_real
  FROM proyectos p
  JOIN proyectos_periodo   pp  ON pp.id_py = p.id
  LEFT JOIN revisiones_proyectos rp
       ON rp.id_py = p.id AND rp.id_periodo = pp.id_periodo
  /* unimos solo lo necesario para la lógica */
  LEFT JOIN evaluaciones ev_pcf_cot
       ON ev_pcf_cot.id_py = p.id AND ev_pcf_cot.id_periodo = pp.id_periodo
       AND ev_pcf_cot.oficina='pcf' AND ev_pcf_cot.tipo='cotejo'
  LEFT JOIN evaluaciones ev_pcf_rub
       ON ev_pcf_rub.id_py = p.id AND ev_pcf_rub.id_periodo = pp.id_periodo
       AND ev_pcf_rub.oficina='pcf' AND ev_pcf_rub.tipo='rubrica'
  JOIN usuarios_proyectos up ON up.id_proyecto = p.id
  JOIN usuarios           u  ON u.id = up.id_usuario
  JOIN departamentos      d  ON d.id = u.id_depa
) ofi
JOIN facultades f ON f.id = ofi.id_facultad
GROUP BY f.id, ofi.oficina_real
ORDER BY f.id;
";

$res = mysqli_query($conexion,$sql) or die('Error SQL: '.mysqli_error($conexion));

/* ───────────────────────── 3.  Arreglo de datos ───────────────────────── */
$labels = [];               // nombres de facultades (eje Y)
$series = [                 // acumuladores por tipo de oficina
  'sin'=>[], 'pcf'=>[], 'dd'=>[], 'df'=>[], 'rsu'=>[]
];

$rows = [];                 // para doughnuts
while($r = mysqli_fetch_assoc($res)){
  $fid  = (int)$r['id_fac'];
  $ofiC = $r['oficina_real'];          // sin | pcf | dd | ...
  $tot  = (int)$r['total'];

  if(!isset($rows[$fid])){
      $rows[$fid] = ['label'=>$r['facultad']] + array_fill_keys(array_keys($mapOfi),0);
  }
  $rows[$fid][$ofiC] = $tot;
}

/* llenar vacíos y construir arrays finales */
foreach($rows as $fid=>$info){
    $labels[] = $info['label'];
    foreach($mapOfi as $cod=>$txt){
        $series[$cod][] = $info[$cod] ?? 0;
    }
}
?>
<!-- =========== BARRA APILADA HORIZONTAL =========== -->
<div class="mb-4">
  <canvas id="barOficina" height="260"></canvas>
</div>

<!-- =========== DOUGHNUT POR FACULTAD =========== -->
<div class="row">
<?php foreach($rows as $fid=>$info): ?>
  <div class="col-md-6 mb-4">
    <h6 class="text-center"><?= htmlspecialchars($info['label']) ?></h6>
    <canvas id="chartOficina<?= $fid ?>" height="220"></canvas>
  </div>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ───── datos PHP  →  JS ───── */
  const lbl  = <?= json_encode($labels,JSON_UNESCAPED_UNICODE) ?>;
  const sin  = <?= json_encode($series['sin']) ?>;
  const pcf  = <?= json_encode($series['pcf']) ?>;
  const dd   = <?= json_encode($series['dd']) ?>;
  const df   = <?= json_encode($series['df']) ?>;
  const rsu  = <?= json_encode($series['rsu']) ?>;

  /* ███  BARRA  ███ */
  new Chart(document.getElementById('barOficina'),{
    type:'bar',
    indexAxis:'y',
    data:{
      labels:lbl,
      datasets:[
        {label:'Sin oficina',              data:sin, backgroundColor:'#6c757d', stack:'s'},
        {label:'Comité de Facultad',       data:pcf, backgroundColor:'#007bff', stack:'s'},
        {label:'Dirección de Departamento',data:dd,  backgroundColor:'#ffc107', stack:'s'},
        {label:'Decanato de Facultad',     data:df,  backgroundColor:'#17a2b8', stack:'s'},
        {label:'Dirección de RSU',         data:rsu, backgroundColor:'#28a745', stack:'s'}
      ]
    },
    options:{
      plugins:{legend:{position:'bottom'}},
      scales:{x:{stacked:true,beginAtZero:true},y:{stacked:true}}
    }
  });

  /* ███  DOUGHNUTS  ███ */
<?php foreach($rows as $fid=>$info): ?>
  new Chart(document.getElementById('chartOficina<?= $fid ?>'),{
    type:'doughnut',
    data:{
      labels:[<?= '"'.implode('","',$mapOfi).'"' ?>],
      datasets:[{data:[
        <?= $info['sin'] ?>,
        <?= $info['pcf'] ?>,
        <?= $info['dd']  ?>,
        <?= $info['df']  ?>,
        <?= $info['rsu'] ?>
      ]}]
    },
    options:{plugins:{legend:{position:'bottom'}}}
  });
<?php endforeach; ?>

});
</script>
