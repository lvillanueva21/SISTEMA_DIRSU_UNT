<?php
require_once("../componentes/db.php");

/* 1. Facultades sin id 0 */
$facultades = mysqli_query(
    $conexion,
    "SELECT id, nombre FROM facultades WHERE id <> 0 ORDER BY id"
);

$estados = [0=>'En proceso',1=>'En revisión',2=>'Aprobado'];

/* 2. Contenedores */
$datos     = [];              // para doughnuts
$labels    = [];              // para barra
$enProc    = $enRev = $apr = [];  // datasets barra

/* 3. Loop de facultades */
while ($f = mysqli_fetch_assoc($facultades)) {
    $id_fac = (int)$f['id'];

    foreach ($estados as $val => $lab) {
        $q = mysqli_query($conexion,"
            SELECT COUNT(*) total
            FROM proyectos p
            JOIN usuarios_proyectos up ON up.id_proyecto = p.id
            JOIN usuarios u ON u.id = up.id_usuario
            JOIN departamentos d ON d.id = u.id_depa
            WHERE p.estado = $val
              AND d.id_facultad = $id_fac
        ");
        $total = mysqli_fetch_assoc($q)['total'] ?? 0;
        $datos[$id_fac]['label'] = $f['nombre'];
        $datos[$id_fac][$lab]    = $total;
    }

    $labels[]  = $f['nombre'];
    $enProc[]  = $datos[$id_fac]['En proceso']  ?? 0;
    $enRev[]   = $datos[$id_fac]['En revisión'] ?? 0;
    $apr[]     = $datos[$id_fac]['Aprobado']    ?? 0;
}
?>

<!-- === BAR CHART (estados apilados) === -->
<div class="mb-4">
  <canvas id="barEstado" height="220"></canvas>
</div>

<!-- === DOUGHNUTS POR FACULTAD === -->
<div class="row">
<?php foreach ($datos as $id_fac => $info): ?>
  <div class="col-md-6 mb-4">
    <h6 class="text-center"><?= htmlspecialchars($info['label']) ?></h6>
    <canvas id="chartEstado<?= $id_fac ?>" height="220"></canvas>
  </div>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ███ BAR ███ */
  const facLbl = <?= json_encode($labels,JSON_UNESCAPED_UNICODE) ?>;
  const proc   = <?= json_encode($enProc) ?>;
  const revis  = <?= json_encode($enRev) ?>;
  const apr    = <?= json_encode($apr) ?>;

  new Chart(document.getElementById('barEstado'),{
    type:'bar',
    data:{
      labels:facLbl,
      datasets:[
        { label:'En proceso',  data:proc,  backgroundColor:'#6c757d', stack:'s' },
        { label:'En revisión', data:revis, backgroundColor:'#17a2b8', stack:'s' },
        { label:'Aprobado',    data:apr,   backgroundColor:'#28a745', stack:'s' }
      ]
    },
    options:{
      plugins:{ legend:{ position:'bottom' } },
      scales:{ y:{ stacked:true, beginAtZero:true }, x:{ stacked:true } }
    }
  });

  /* ███ DOUGHNUTS ███ */
  <?php foreach ($datos as $id_fac => $info): ?>
  new Chart(document.getElementById('chartEstado<?= $id_fac ?>'),{
    type:'doughnut',
    data:{
      labels:['En proceso','En revisión','Aprobado'],
      datasets:[{
        data:[
          <?= $info['En proceso']  ?? 0 ?>,
          <?= $info['En revisión'] ?? 0 ?>,
          <?= $info['Aprobado']    ?? 0 ?>
        ]
      }]
    },
    options:{ plugins:{ legend:{ position:'bottom' } } }
  });
  <?php endforeach; ?>

});
</script>
