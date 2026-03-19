<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de visto bueno no definido.');
}

// Directorio donde se guardan los archivos
$directorio3 = '../componentes/archivo/visto_bueno/' . $_SESSION['id_py'] . '/';

// Inicializa la variable para el nombre del archivo
$archivo_visto_bueno = 'Aún no has subido un archivo';

//Linea agregada
$archivos3 = [];

// Verifica si el directorio existe
if (is_dir($directorio3)) {
    // Obtener los archivos en el directorio
    $archivos3 = array_diff(scandir($directorio3), array('..', '.')); // Excluir los directorios '.' y '..'

    // Verifica si hay archivos en el directorio
    if (count($archivos3) > 0) {
        // Solo toma el primer archivo encontrado (puedes modificar esto si es necesario)
        $archivo_visto_bueno = $archivos3[0]; // Cambia esto según tu lógica
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
            <?php if (count($archivos3) > 0): ?>
                <?php foreach ($archivos3 as $archivo3): ?>
                    <?php
                    // Obtener información del archivo
                    $rutaArchivo3 = $directorio3 . $archivo3;
                    $extension3 = pathinfo($archivo3, PATHINFO_EXTENSION);
                    $tamaño3 = filesize($rutaArchivo3); // Tamaño del archivo en bytes
                    $fechaSubida3 = date("F d Y H:i:s.", filemtime($rutaArchivo3)); // Fecha de modificación (o subida)
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($archivo3); ?></td>
                        <!-- <td><?php echo strtoupper($extension3); ?></td> -->
                        <td><a href="<?php echo $rutaArchivo3; ?>" target="_blank"><button class="btn btn-secondary">Descargar</button></a></td>
                        <!-- <td>
                            <button onclick="mostrarInfo('<?php echo htmlspecialchars($archivo3); ?>', '<?php echo $tamaño3; ?>', '<?php echo $fechaSubida3; ?>')">Info</button>
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
