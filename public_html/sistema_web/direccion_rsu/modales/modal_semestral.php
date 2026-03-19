<?php
require_once("../componentes/db.php");

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;

// Aquí ejemplo de datos de avances semestrales
$avances = [];

if ($id_py > 0) {
  $query = mysqli_query($conexion, "
    SELECT avance, fecha
    FROM avances_semestrales
    WHERE id_py = $id_py
    ORDER BY fecha DESC
  ");
  while ($r = mysqli_fetch_assoc($query)) {
    $avances[] = $r;
  }
}
?>

<div class="modal-header">
  <h5 class="modal-title">Avances Semestrales - Proyecto #<?= $id_py ?></h5>
  <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
  <?php if ($avances): ?>
    <ul class="list-group">
      <?php foreach ($avances as $a): ?>
        <li class="list-group-item">
          <strong><?= (new DateTime($a['fecha']))->format('d/m/Y') ?>:</strong>
          <?= htmlspecialchars($a['avance']) ?>
        </li>
      <?php endforeach; ?>
    </ul>
  <?php else: ?>
    <div class="alert alert-info">No hay avances registrados para este proyecto.</div>
  <?php endif; ?>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>
