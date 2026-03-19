<?php
// Incluir la rutina para obtener los datos del proyecto
$proyecto = include '../componentes/proyecto/ver_proyecto.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detalles del Proyecto</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
</head>
<body>
    <div class="container">
        <h1>Detalles del Proyecto</h1>

        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID Proyecto</th>
                    <th>Título del Programa</th>
                    <th>Título del Proyecto</th>
                    <th>ODS</th>
                    <th>Grupo de Interés</th>
                    <!-- Agrega más columnas según tus necesidades -->
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td><?php echo htmlspecialchars($proyecto['id']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['p1']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['p2']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['p3']); ?></td>
                    <td><?php echo htmlspecialchars($proyecto['p5']); ?></td>
                    <!-- Agrega más campos según tus necesidades -->
                </tr>
            </tbody>
        </table>

        <a href="editar_proyecto.php?id=<?php echo htmlspecialchars($proyecto['id']); ?>" class="btn btn-primary">Editar Proyecto</a>
    </div>
</body>
</html>
