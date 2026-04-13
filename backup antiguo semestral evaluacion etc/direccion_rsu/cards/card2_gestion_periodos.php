<?php
require_once("../componentes/db.php");

// INSERTAR NUEVO PERÍODO
if (isset($_POST['agregar'])) {
    $nombre = trim($_POST['nombre']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];

    if (!empty($nombre)) {
        $stmt = mysqli_prepare($conexion, "INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, 1)");
        mysqli_stmt_bind_param($stmt, 'sss', $nombre, $fecha_inicio, $fecha_fin);
        mysqli_stmt_execute($stmt);
    }
}

// ACTUALIZAR PERÍODO
if (isset($_POST['editar'])) {
    $id = $_POST['id'];
    $nombre = trim($_POST['nombre']);
    $fecha_inicio = $_POST['fecha_inicio'];
    $fecha_fin = $_POST['fecha_fin'];
    $activo = isset($_POST['activo']) ? 1 : 0;

    $stmt = mysqli_prepare($conexion, "UPDATE periodos SET nombre=?, fecha_inicio=?, fecha_fin=?, activo=? WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'sssii', $nombre, $fecha_inicio, $fecha_fin, $activo, $id);
    mysqli_stmt_execute($stmt);
}

// ELIMINAR PERÍODO
if (isset($_POST['eliminar'])) {
    $id = $_POST['id'];
    $stmt = mysqli_prepare($conexion, "DELETE FROM periodos WHERE id=?");
    mysqli_stmt_bind_param($stmt, 'i', $id);
    mysqli_stmt_execute($stmt);
}

// CONSULTAR TODOS LOS PERÍODOS
$result = mysqli_query($conexion, "SELECT * FROM periodos ORDER BY fecha_inicio DESC");
?>

<!-- FORMULARIO PARA NUEVO PERÍODO -->
<form method="POST" class="mb-3">
    <div class="form-row">
        <div class="col-md-3">
            <input type="text" name="nombre" class="form-control form-control-sm" placeholder="Nombre del período" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="fecha_inicio" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <input type="date" name="fecha_fin" class="form-control form-control-sm" required>
        </div>
        <div class="col-md-3">
            <button type="submit" name="agregar" class="btn btn-success btn-sm btn-block">
                <i class="fas fa-plus"></i> Agregar período
            </button>
        </div>
    </div>
</form>

<!-- TABLA DE PERÍODOS -->
<div class="table-responsive">
<table class="table table-bordered table-sm">
    <thead class="thead-dark">
        <tr>
            <th>Nombre</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Activo</th>
            <th>Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)) : ?>
        <tr>
            <form method="POST" class="form-inline">
                <input type="hidden" name="id" value="<?= $row['id'] ?>">
                <td>
                    <input type="text" name="nombre" class="form-control form-control-sm" value="<?= htmlspecialchars($row['nombre']) ?>">
                </td>
                <td>
                    <input type="date" name="fecha_inicio" class="form-control form-control-sm" value="<?= $row['fecha_inicio'] ?>">
                </td>
                <td>
                    <input type="date" name="fecha_fin" class="form-control form-control-sm" value="<?= $row['fecha_fin'] ?>">
                </td>
                <td class="text-center">
                    <input type="checkbox" name="activo" <?= $row['activo'] ? 'checked' : '' ?>>
                </td>
                <td class="text-center">
                    <button name="editar" class="btn btn-primary btn-sm" title="Guardar cambios">
                        <i class="fas fa-save"></i>
                    </button>
                    <button name="eliminar" class="btn btn-danger btn-sm" title="Eliminar" onclick="return confirm('¿Eliminar este período?')">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </form>
        </tr>
        <?php endwhile; ?>
    </tbody>
</table>
</div>
