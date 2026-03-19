<?php
// Incluye conexión y sesión
include('../componentes/configSesion.php');
include('../componentes/db.php');

// Consulta roles
$roles = mysqli_query($conexion, "SELECT * FROM roles");

// Consulta deadlines
$deadlines = mysqli_query($conexion, "SELECT d.*, r.nombre_rol FROM deadlines d JOIN roles r ON d.id_rol = r.id ORDER BY d.fecha_limite DESC");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Gestión de Deadlines</title>
    <link rel="stylesheet" href="../plogins/bootstrap/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="p-4">
    <div class="container">
        <h3 class="mb-4">Gestión de Deadlines</h3>

        <!-- Formulario -->
        <form id="form-deadline" class="border p-3 mb-4">
            <input type="hidden" name="id" id="id">
            <div class="mb-2">
                <label for="titulo" class="form-label">Título:</label>
                <input type="text" name="titulo" id="titulo" class="form-control" required>
            </div>
            <div class="mb-2">
                <label for="mensaje" class="form-label">Mensaje:</label>
                <textarea name="mensaje" id="mensaje" class="form-control" required></textarea>
            </div>
            <div class="mb-2">
                <label for="fecha_limite" class="form-label">Fecha límite:</label>
                <input type="date" name="fecha_limite" id="fecha_limite" class="form-control" required>
            </div>
            <div class="mb-2">
                <label for="id_rol" class="form-label">Rol:</label>
                <select name="id_rol" id="id_rol" class="form-control" required>
                    <option value="">Seleccione un rol</option>
                    <?php while ($rol = mysqli_fetch_assoc($roles)) : ?>
                        <option value="<?= $rol['id'] ?>"><?= $rol['nombre_rol'] ?></option>
                    <?php endwhile; ?>
                </select>
            </div>
            <div class="form-check mb-2">
                <input type="checkbox" name="activo" id="activo" class="form-check-input">
                <label for="activo" class="form-check-label">Activo</label>
            </div>
            <button type="submit" class="btn btn-primary">Guardar Deadline</button>
        </form>

        <!-- Tabla -->
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Título</th>
                    <th>Mensaje</th>
                    <th>Fecha Límite</th>
                    <th>Rol</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody id="tabla-deadlines">
                <?php while ($d = mysqli_fetch_assoc($deadlines)) : ?>
                <tr>
                    <td><?= $d['id'] ?></td>
                    <td><?= $d['titulo'] ?></td>
                    <td><?= $d['mensaje'] ?></td>
                    <td><?= $d['fecha_limite'] ?></td>
                    <td><?= $d['nombre_rol'] ?></td>
                    <td><?= $d['activo'] ? 'Sí' : 'No' ?></td>
                    <td>
                        <button class="btn btn-sm btn-warning" onclick='editarDeadline(<?= json_encode($d) ?>)'>Editar</button>
                        <form class="d-inline eliminar-form" method="POST">
                            <input type="hidden" name="eliminar" value="<?= $d['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <script>
    function editarDeadline(data) {
        $('#id').val(data.id);
        $('#titulo').val(data.titulo);
        $('#mensaje').val(data.mensaje);
        $('#fecha_limite').val(data.fecha_limite);
        $('#id_rol').val(data.id_rol);
        $('#activo').prop('checked', data.activo == 1);
    }

    $('#form-deadline').on('submit', function (e) {
        e.preventDefault();
        let formData = new FormData(this);
        fetch('procesar_deadline.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });

    $('.eliminar-form').on('submit', function (e) {
        e.preventDefault();
        if (!confirm('¿Eliminar este deadline?')) return;
        let formData = new FormData(this);
        fetch('procesar_deadline.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            alert(data.message);
            if (data.success) location.reload();
        });
    });
    </script>
</body>
</html>
