<?php
include "../componentes/configSesion.php";
include "../componentes/db.php";

$edit_mode = false;
$edit_id = 0;
$titulo = $mensaje = $id_rol = $fecha_inicio = $fecha_fin = '';

if (isset($_GET['edit'])) {
    $edit_mode = true;
    $edit_id = intval($_GET['edit']);
    $res = mysqli_query($conexion, "SELECT * FROM deadlines WHERE id=$edit_id");
    if ($row = mysqli_fetch_assoc($res)) {
        $id_rol = $row['id_rol'];
        $titulo = htmlspecialchars($row['titulo']);
        $mensaje = htmlspecialchars($row['mensaje']);
        $fecha_inicio = substr($row['fecha_inicio'], 0, 16);
        $fecha_fin = substr($row['fecha_fin'], 0, 16);
    }
}

$roles_res = mysqli_query($conexion, "SELECT id, nombre FROM rol");
$dl_res = mysqli_query($conexion, "
    SELECT d.*, r.nombre AS rol_nombre
    FROM deadlines d
    LEFT JOIN rol r ON r.id = d.id_rol
    ORDER BY d.fecha_inicio DESC
");
?>

<div class="container-fluid mt-3">
    <!-- Formulario -->
    <div class="card">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><?= $edit_mode ? "Editar Deadline" : "Nuevo Deadline" ?></h5>
        </div>
        <div class="card-body">
            <form method="post" action="funciones/procesar_deadline.php">
                <input type="hidden" name="action" value="<?= $edit_mode ? 'update' : 'create' ?>">
                <?php if ($edit_mode): ?>
                    <input type="hidden" name="id" value="<?= $edit_id ?>">
                <?php endif; ?>

                <div class="form-group">
                    <label for="id_rol">Rol</label>
                    <select name="id_rol" id="id_rol" class="form-control" required>
                        <option value="">-- Seleccione un rol --</option>
                        <?php while ($r = mysqli_fetch_assoc($roles_res)): ?>
                            <option value="<?= $r['id'] ?>" <?= $r['id'] == $id_rol ? 'selected' : '' ?>>
                                <?= htmlspecialchars($r['nombre']) ?>
                            </option>
                        <?php endwhile; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label for="titulo">Título</label>
                    <input type="text" name="titulo" id="titulo" class="form-control" value="<?= $titulo ?>" required>
                </div>

                <div class="form-group">
                    <label for="mensaje">Mensaje</label>
                    <textarea name="mensaje" id="mensaje" class="form-control" rows="3" required><?= $mensaje ?></textarea>
                </div>

                <div class="form-row">
                    <div class="form-group col-md-6">
                        <label for="fecha_inicio">Fecha Inicio</label>
                        <input type="datetime-local" name="fecha_inicio" id="fecha_inicio" class="form-control" value="<?= $fecha_inicio ?>" required>
                    </div>
                    <div class="form-group col-md-6">
                        <label for="fecha_fin">Fecha Fin</label>
                        <input type="datetime-local" name="fecha_fin" id="fecha_fin" class="form-control" value="<?= $fecha_fin ?>" required>
                    </div>
                </div>

                <button type="submit" class="btn btn-<?= $edit_mode ? 'warning' : 'success' ?>">
                    <?= $edit_mode ? 'Actualizar' : 'Guardar' ?>
                </button>
                <?php if ($edit_mode): ?>
                    <a href="control_eventos.php" class="btn btn-secondary">Cancelar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de Deadlines -->
    <div class="card mt-4">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0">Deadlines Registrados</h5>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead class="thead-dark">
                    <tr>
                        <th>ID</th><th>Rol</th><th>Título</th><th>Inicio</th><th>Fin</th>
                        <th>Activo</th><th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($d = mysqli_fetch_assoc($dl_res)): ?>
                        <tr>
                            <td><?= $d['id'] ?></td>
                            <td><?= htmlspecialchars($d['rol_nombre']) ?></td>
                            <td><?= htmlspecialchars($d['titulo']) ?></td>
                            <td><?= $d['fecha_inicio'] ?></td>
                            <td><?= $d['fecha_fin'] ?></td>
                            <td><?= $d['activo'] ? '✔️' : '❌' ?></td>
                            <td>
                                <a href="control_eventos.php?edit=<?= $d['id'] ?>" class="btn btn-sm btn-info" title="Editar"><i class="fas fa-edit"></i></a>
                                <form method="post" action="funciones/procesar_deadline.php" class="d-inline">
                                    <input type="hidden" name="action" value="toggle">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-secondary" title="Activar/Desactivar">
                                        <i class="fas fa-power-off"></i>
                                    </button>
                                </form>
                                <form method="post" action="funciones/procesar_deadline.php" class="d-inline" onsubmit="return confirm('¿Eliminar este deadline?');">
                                    <input type="hidden" name="action" value="delete">
                                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
