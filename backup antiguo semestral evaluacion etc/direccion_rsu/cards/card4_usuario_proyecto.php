<?php
require_once("../componentes/db.php");

// Contar relaciones actuales
$resConteo = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM usuarios_proyectos");
$conteo = mysqli_fetch_assoc($resConteo)['total'];

// Asignar relaciones automáticamente
if (isset($_POST['asignar_relaciones'])) {
    $query = "
        INSERT IGNORE INTO usuarios_proyectos (id_usuario, id_proyecto)
        SELECT u.id, u.id_py
        FROM usuarios u
        WHERE LENGTH(u.usuario) = 4
          AND u.id_py IS NOT NULL AND u.id_py != 0 AND u.id_py != ''
    ";
    mysqli_query($conexion, $query);
    echo '<div class="alert alert-success">✅ Relaciones asignadas correctamente.</div>';
}

// Vaciar la tabla
if (isset($_POST['vaciar_tabla'])) {
    mysqli_query($conexion, "TRUNCATE TABLE usuarios_proyectos");
    echo '<div class="alert alert-danger">🧨 Todas las relaciones han sido eliminadas.</div>';
}

// Parámetros de ordenamiento
$orden_columna = $_GET['orden'] ?? 'u.usuario';
$orden_direccion = $_GET['dir'] ?? 'ASC';
$toggle_direccion = $orden_direccion === 'ASC' ? 'DESC' : 'ASC';

$relaciones = [];
$mostrar_todo = isset($_POST['mostrar_todo']) || isset($_GET['mostrar_todo']);


if ($mostrar_todo) {
    $sql = "
        SELECT 
            up.id,
            u.usuario,
            u.nombres,
            u.apellidos,
            p.p2 AS titulo_proyecto,
            pr.nombre AS periodo,
            pr.activo
        FROM usuarios_proyectos up
        JOIN usuarios u ON up.id_usuario = u.id
        JOIN proyectos p ON up.id_proyecto = p.id
        LEFT JOIN proyectos_periodo pp ON pp.id_py = p.id
        LEFT JOIN periodos pr ON pr.id = pp.id_periodo
        ORDER BY $orden_columna $orden_direccion
    ";
    $resultado = mysqli_query($conexion, $sql);
    while ($row = mysqli_fetch_assoc($resultado)) {
        $relaciones[] = $row;
    }
}
?>

<!-- Botones de acción -->
<form method="POST" class="mb-3 d-flex flex-wrap gap-2">
    <button type="submit" name="asignar_relaciones" class="btn btn-success btn-sm">
        <i class="fas fa-link"></i> Asignar relaciones
    </button>

    <button type="submit" name="vaciar_tabla" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de vaciar todas las relaciones?');">
        <i class="fas fa-trash-alt"></i> Vaciar tabla
    </button>

    <a href="?mostrar_todo=1&orden=<?= $orden_columna ?>&dir=<?= $orden_direccion ?>" class="btn btn-info btn-sm">
        <i class="fas fa-list"></i> Mostrar relaciones (<?= $conteo ?>)
    </a>
</form>


<!-- Tabla de relaciones -->
<?php if ($mostrar_todo && count($relaciones) > 0): ?>
<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="thead-dark">
            <tr>
                <th>#</th>
                <th>Usuario</th>
                <th>Nombre completo</th>
                <th>Proyecto asignado</th>
                <th>
    <a href="?mostrar_todo=1&orden=pr.nombre&dir=<?= $toggle_direccion ?>" class="text-white">
        Periodo
    </a>
</th>
<th>
    <a href="?mostrar_todo=1&orden=pr.activo&dir=<?= $toggle_direccion ?>" class="text-white">
        Activo
    </a>
</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($relaciones as $i => $rel): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= htmlspecialchars($rel['usuario']) ?></td>
                <td><?= htmlspecialchars($rel['nombres'] . ' ' . $rel['apellidos']) ?></td>
                <td><?= htmlspecialchars($rel['titulo_proyecto']) ?></td>
                <td><?= htmlspecialchars($rel['periodo'] ?? 'Sin período') ?></td>
                <td>
                    <?php if ($rel['activo'] == 1): ?>
                        <span class="badge badge-success">Sí</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">No</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php elseif ($mostrar_todo): ?>
    <div class="alert alert-warning">No hay relaciones registradas actualmente.</div>
<?php endif; ?>
