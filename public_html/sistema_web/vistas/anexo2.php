<?php
// consultas.php

include('../componentes/db.php'); // Incluye la conexión a la base de datos

$error = '';
$resultado = null;

if(isset($_POST['consulta'])) {
    $consulta = trim($_POST['consulta']);
    
    // Validación simple para permitir solo consultas SELECT
    if (stripos($consulta, 'select') === 0) {
        $resultado = mysqli_query($conexion, $consulta);
        if(!$resultado){
            $error = "Error en la consulta: " . mysqli_error($conexion);
        }
    } else {
        $error = "Solo se permiten consultas SELECT.";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Interfaz de Consultas MySQL</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<div class="container">
    <h1 class="mt-4">Consulta a la Base de Datos</h1>
    <form action="consultas.php" method="POST" class="mb-4">
        <div class="mb-3">
            <label for="consulta" class="form-label">Ingresa tu consulta SQL:</label>
            <textarea class="form-control" id="consulta" name="consulta" rows="3"><?php if(isset($consulta)) echo htmlspecialchars($consulta); ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Ejecutar Consulta</button>
    </form>

    <?php if($error): ?>
        <div class="alert alert-danger">
            <?php echo $error; ?>
        </div>
    <?php endif; ?>

    <?php if($resultado && mysqli_num_rows($resultado) > 0): ?>
        <div class="table-responsive">
            <table class="table table-bordered table-striped">
                <thead>
                    <tr>
                        <?php
                        // Mostrar encabezados de la tabla
                        $campos = mysqli_fetch_fields($resultado);
                        foreach($campos as $campo) {
                            echo "<th>" . htmlspecialchars($campo->name) . "</th>";
                        }
                        // Reiniciar el puntero del resultado para imprimir todas las filas
                        mysqli_data_seek($resultado, 0);
                        ?>
                    </tr>
                </thead>
                <tbody>
                    <?php while($fila = mysqli_fetch_assoc($resultado)): ?>
                        <tr>
                            <?php foreach($fila as $valor): ?>
                                <td><?php echo htmlspecialchars($valor); ?></td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    <?php elseif($resultado && mysqli_num_rows($resultado) == 0): ?>
        <div class="alert alert-info">La consulta no devolvió resultados.</div>
    <?php endif; ?>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
