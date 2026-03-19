<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de diagrama no definido.');
}

// Directorio donde se guardan los archivos
$directorio2 = '../componentes/archivo/diagrama/' . $_SESSION['id_py'] . '/';

// Inicializa la variable para el nombre del archivo
$archivo_diagrama = 'Aún no has subido un archivo';

//Linea agregada
$archivos2 = [];

// Verifica si el directorio existe
if (is_dir($directorio2)) {
    // Obtener los archivos en el directorio
    $archivos2 = array_diff(scandir($directorio2), array('..', '.')); // Excluir los directorios '.' y '..'

    // Verifica si hay archivos en el directorio
    if (count($archivos2) > 0) {
        // Solo toma el primer archivo encontrado (puedes modificar esto si es necesario)
        $archivo_diagrama = $archivos2[0]; // Cambia esto según tu lógica
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
            <?php if (count($archivos2) > 0): ?>
                <?php foreach ($archivos2 as $archivo2): ?>
                    <?php
                    // Obtener información del archivo
                    $rutaArchivo2 = $directorio2 . $archivo2;
                    $extension2 = pathinfo($archivo2, PATHINFO_EXTENSION);
                    $tamaño2 = filesize($rutaArchivo2); // Tamaño del archivo en bytes
                    $fechaSubida2 = date("F d Y H:i:s.", filemtime($rutaArchivo2)); // Fecha de modificación (o subida)
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($archivo2); ?></td>
                        <!-- <td><?php echo strtoupper($extension2); ?></td> -->
                        <td><a href="<?php echo $rutaArchivo2; ?>" target="_blank"><button class="btn btn-secondary">Descargar</button></a></td>
                        <!-- <td>
                            <button onclick="mostrarInfo('<?php echo htmlspecialchars($archivo2); ?>', '<?php echo $tamaño2; ?>', '<?php echo $fechaSubida2; ?>')">Info</button>
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
