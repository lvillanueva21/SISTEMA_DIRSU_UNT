<?php
require_once("../componentes/db.php");

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;

$proyecto = null;

if ($id_py > 0) {
  $query = mysqli_query($conexion, "
    SELECT p.p2 AS titulo, p.p1 AS programa, u.nombres, u.apellidos, u.usuario,
           d.nombre AS departamento, f.nombre AS facultad
    FROM proyectos p
    JOIN usuarios u ON u.id_py = p.id
    LEFT JOIN departamentos d ON d.id = u.id_depa
    LEFT JOIN facultades f ON f.id = d.id_facultad
    WHERE p.id = $id_py
    LIMIT 1
  ");
  $proyecto = mysqli_fetch_assoc($query);
}
?>

<?php if ($proyecto): ?>
<div class="modal-header">
  <h5 class="modal-title">Proyecto #<?= $id_py ?></h5>
  <button type="button" class="close" data-dismiss="modal">&times;</button>
</div>
<div class="modal-body">
  <p><strong>Título:</strong> <?= htmlspecialchars($proyecto['titulo'] ?: 'No registrado') ?></p>
  <p><strong>Programa:</strong> <?= htmlspecialchars($proyecto['programa'] ?: 'No registrado') ?></p>
  <p><strong>Coordinador:</strong> <?= htmlspecialchars($proyecto['nombres'].' '.$proyecto['apellidos']) ?></p>
  <p><strong>Código Docente:</strong> <?= htmlspecialchars($proyecto['usuario']) ?></p>
  <p><strong>Departamento:</strong> <?= htmlspecialchars($proyecto['departamento'] ?: 'No definido') ?></p>
  <p><strong>Facultad:</strong> <?= htmlspecialchars($proyecto['facultad'] ?: 'No definida') ?></p>
</div>
<div class="modal-footer">
  <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
</div>
<?php else: ?>
<div class="modal-body">
  <div class="alert alert-danger">No se encontró el proyecto.</div>
</div>
<?php endif; ?>
