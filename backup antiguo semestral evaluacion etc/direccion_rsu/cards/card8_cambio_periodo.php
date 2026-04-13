<?php
require_once("../componentes/db.php");

// Obtener todos los periodos para el select
$periodos_all = mysqli_query($conexion, "SELECT id, nombre FROM periodos ORDER BY fecha_inicio DESC");

$proyecto = null;
$periodo_actual = null;

if (isset($_POST['buscar'])) {
    $id_py = (int) $_POST['id_py'];

    // Buscar título y período actual del proyecto
    $sql = "SELECT p.id, p.p2 AS titulo, pp.id_periodo 
            FROM proyectos p
            JOIN proyectos_periodo pp ON p.id = pp.id_py
            WHERE p.id = ?";
    $stmt = mysqli_prepare($conexion, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $id_py);
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    $proyecto = mysqli_fetch_assoc($res);
    $periodo_actual = $proyecto['id_periodo'] ?? null;
}

if (isset($_POST['cambiar'])) {
    $id_py = (int) $_POST['id_py'];
    $nuevo_periodo = (int) $_POST['nuevo_periodo'];

    // Verificar periodo actual
    $res = mysqli_query($conexion, "SELECT id_periodo FROM proyectos_periodo WHERE id_py = $id_py");
    $actual = mysqli_fetch_assoc($res)['id_periodo'];

    if ($nuevo_periodo !== (int)$actual) {
        $conexion->begin_transaction();
        try {
            $tablas = ['proyectos_periodo', 'revisiones_proyectos', 'evaluaciones', 'historial_estados'];
            foreach ($tablas as $tabla) {
                $sql = "UPDATE $tabla SET id_periodo = ? WHERE id_py = ?";
                $stmt = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($stmt, 'ii', $nuevo_periodo, $id_py);
                mysqli_stmt_execute($stmt);
            }

            // Registrar en historial
            $accion = "Cambio de período";
            $descripcion = "El proyecto fue trasladado del período ID $actual al ID $nuevo_periodo.";
            $sql_historial = "INSERT INTO historial_estados (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
                              VALUES (?, ?, NOW(), ?, ?, NULL)";
            $stmt = mysqli_prepare($conexion, $sql_historial);
            mysqli_stmt_bind_param($stmt, 'iiss', $id_py, $nuevo_periodo, $accion, $descripcion);
            mysqli_stmt_execute($stmt);

            $conexion->commit();
            echo "<div class='alert alert-success'>✅ Período actualizado correctamente.</div>";
            $proyecto = null;
        } catch (Exception $e) {
            $conexion->rollback();
            echo "<div class='alert alert-danger'>❌ Error al actualizar período: {$e->getMessage()}</div>";
        }
    } else {
        echo "<div class='alert alert-info'>ℹ️ Ya está asignado a ese período.</div>";
    }
}
?>

<!-- FORMULARIO DE BÚSQUEDA -->
<form method="POST" class="mb-3">
    <div class="form-row">
        <div class="col-md-4">
            <input type="number" name="id_py" class="form-control form-control-sm" placeholder="ID del proyecto" required>
        </div>
        <div class="col-md-2">
            <button type="submit" name="buscar" class="btn btn-primary btn-sm">Buscar</button>
        </div>
    </div>
</form>

<!-- FORMULARIO DE CAMBIO DE PERIODO -->
<?php if ($proyecto): ?>
<form method="POST" class="mt-3">
    <input type="hidden" name="id_py" value="<?= $proyecto['id'] ?>">
    <div class="form-group">
        <label><strong>Título del proyecto:</strong> <?= htmlspecialchars($proyecto['titulo']) ?></label>
    </div>
    <div class="form-group">
        <label><strong>Seleccionar nuevo período:</strong></label>
        <select name="nuevo_periodo" class="form-control form-control-sm" required>
            <?php while ($p = mysqli_fetch_assoc($periodos_all)): ?>
                <option value="<?= $p['id'] ?>" <?= ($p['id'] == $periodo_actual ? 'selected' : '') ?>>
                    <?= htmlspecialchars($p['nombre']) ?>
                </option>
            <?php endwhile; ?>
        </select>
    </div>
    <button name="cambiar" class="btn btn-warning btn-sm" onclick="return confirm('¿Estás seguro de cambiar el período del proyecto?');">
        <i class="fas fa-exchange-alt"></i> Cambiar período
    </button>
</form>
<?php endif; ?>
