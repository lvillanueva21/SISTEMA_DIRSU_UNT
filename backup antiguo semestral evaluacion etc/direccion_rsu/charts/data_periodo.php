<?php
require_once("../componentes/db.php");

/* ─── 1. Catálogo de facultades (sin id 0) ─── */
$facultades = mysqli_query(
    $conexion,
    "SELECT id, nombre FROM facultades WHERE id <> 0 ORDER BY id"
);

/* ─── 2. IDs de los períodos “2024-II” y “2025-I” ─── */
$periodos = [];   // [nombre] => id
$res = mysqli_query(
    $conexion,
    "SELECT id, nombre FROM periodos WHERE nombre IN ('2024-II','2025-I')"
);
while ($p = mysqli_fetch_assoc($res)) {
    $periodos[$p['nombre']] = (int)$p['id'];
}

/* ─── 3. Contenedores de datos ─── */
$datos   = []; // para los doughnuts
$labels  = []; // nombres de facultad para la barra
$data24  = []; // totales período 2024-II
$data25  = []; // totales período 2025-I

/* ─── 4. Recorremos cada facultad ─── */
while ($f = mysqli_fetch_assoc($facultades)) {
    $id_fac = (int)$f['id'];

    /* Conteos por período */
    foreach ($periodos as $nomPer => $idPer) {
        $q = mysqli_query($conexion,"
            SELECT COUNT(*) AS total
            FROM proyectos p
            JOIN proyectos_periodo pp ON pp.id_py = p.id
            JOIN usuarios_proyectos up ON up.id_proyecto = p.id
            JOIN usuarios u ON u.id = up.id_usuario
            JOIN departamentos d ON d.id = u.id_depa
            WHERE pp.id_periodo = $idPer
              AND d.id_facultad = $id_fac
        ");
        $total = mysqli_fetch_assoc($q)['total'] ?? 0;
        $datos[$id_fac]['label']  = $f['nombre'];
        $datos[$id_fac][$nomPer]  = $total;
    }

    /* Rellenamos para la barra */
    $labels[] = $f['nombre'];
    $data24[] = $datos[$id_fac]['2024-II'] ?? 0;
    $data25[] = $datos[$id_fac]['2025-I']  ?? 0;
}
?>

<!-- === BAR CHART (comparativo por período) === -->
<div class="mb-4">
  <canvas id="barPeriodo" height="220"></canvas>
</div>

<!-- === DOUGHNUTS POR FACULTAD === -->
<div class="row">
<?php foreach ($datos as $id_fac => $info): ?>
  <div class="col-md-6 mb-4">
    <h6 class="text-center"><?= htmlspecialchars($info['label']) ?></h6>
    <canvas id="chartPeriodo<?= $id_fac ?>" height="220"></canvas>
  </div>
<?php endforeach; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  /* ███  BAR  ███ */
  const labelsPeriodo = <?= json_encode($labels,JSON_UNESCAPED_UNICODE) ?>;
  const data2024      = <?= json_encode($data24) ?>;
  const data2025      = <?= json_encode($data25) ?>;

  new Chart(document.getElementById('barPeriodo'),{
    type:'bar',
    data:{
      labels:labelsPeriodo,
      datasets:[
        { label:'2024-II', data:data2024, stack:'p' },
        { label:'2025-I',  data:data2025, stack:'p' }
      ]
    },
    options:{
      plugins:{ legend:{ position:'bottom' } },
      scales:{ y:{ beginAtZero:true, stacked:true } }
    }
  });

  /* ███  DOUGHNUTS  ███ */
  <?php foreach ($datos as $id_fac => $info): ?>
  new Chart(document.getElementById('chartPeriodo<?= $id_fac ?>'),{
    type:'doughnut',
    data:{
      labels:['2024-II','2025-I'],
      datasets:[{ data:[<?= $info['2024-II'] ?? 0 ?>,<?= $info['2025-I'] ?? 0 ?>] }]
    },
    options:{ plugins:{ legend:{ position:'bottom' } } }
  });
  <?php endforeach; ?>

});
</script>
