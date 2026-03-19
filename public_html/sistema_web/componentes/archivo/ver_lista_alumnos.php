<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de alumno no definido.');
}

// Directorio donde se guardan los archivos
$directorio1 = '../componentes/archivo/lista_alumnos/' . $_SESSION['id_py'] . '/';

// Inicializa la variable para el nombre del archivo
$archivo_lista_alumno = 'Aún no has subido un archivo';

//Linea agregada
$archivos1 = [];

// Verifica si el directorio1 existe
if (is_dir($directorio1)) {
    // Obtener los archivos en el directorio1
    $archivos1 = array_diff(scandir($directorio1), array('..', '.')); // Excluir los directorios '.' y '..'

    // Verifica si hay archivos en el directorio1
    if (count($archivos1) > 0) {
        // Solo toma el primer archivo encontrado (puedes modificar esto si es necesario)
        $archivo_lista_alumno = $archivos1[0]; // Cambia esto según tu lógica
    }
}

?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos Subidos</title>
</head>
<body>
    <br>
    <table class="table table-striped" style="border-radius: 0.5rem; overflow: hidden;">
        <thead style="background-color: #28a745; color: white;">
        <tr>
            <th>Archivo subido al sistema</th>
            <!-- <th>Extensión</th> -->
            <th>Opciones</th>
            <!--  <th>Info</th> -->
        </tr>
    </thead>
        <tbody>
            <?php if (count($archivos1) > 0): ?>
                <?php foreach ($archivos1 as $archivo1): ?>
                    <?php
                    // Obtener información del archivo
                    $rutaArchivo1 = $directorio1 . $archivo1;
                    $extension1 = pathinfo($archivo1, PATHINFO_EXTENSION);
                    $tamaño1 = filesize($rutaArchivo1); // Tamaño del archivo en bytes
                    $fechaSubida1 = date("F d Y H:i:s.", filemtime($rutaArchivo1)); // Fecha de modificación (o subida)
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($archivo1); ?></td>
                        <!-- <td><?php echo strtoupper($extension1); ?></td> -->
                        <td><a href="<?php echo $rutaArchivo1; ?>"><button class="btn btn-secondary">Descargar</button></a></td>
                        <!-- <td>
                            <button onclick="mostrarInfo('<?php echo htmlspecialchars($archivo1); ?>', '<?php echo $tamaño1; ?>', '<?php echo $fechaSubida1; ?>')">Info</button>
                        </td> -->
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="4">Aun no has subido el archivo.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
