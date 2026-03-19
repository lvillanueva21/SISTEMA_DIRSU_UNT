<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de carta no definido.');
}

// Directorio donde se guardan los archivos
$directorio5 = '../componentes/archivo/carta/' . $_SESSION['id_py'] . '/';

// Inicializa la variable para el nombre del archivo
$archivo_carta = 'Aún no has subido un archivo';

//Linea agregada
$archivos5 = [];

// Verifica si el directorio existe
if (is_dir($directorio5)) {
    // Obtener los archivos en el directorio
    $archivos5 = array_diff(scandir($directorio5), array('..', '.')); // Excluir los directorios '.' y '..'

    // Verifica si hay archivos en el directorio
    if (count($archivos5) > 0) {
        // Solo toma el primer archivo encontrado (puedes modificar esto si es necesario)
        $archivo_carta = $archivos5[0]; // Cambia esto según tu lógica
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
            <?php if (count($archivos5) > 0): ?>
                <?php foreach ($archivos5 as $archivo5): ?>
                    <?php
                    // Obtener información del archivo
                    $rutaArchivo5 = $directorio5 . $archivo5;
                    $extension5 = pathinfo($archivo5, PATHINFO_EXTENSION);
                    $tamaño5 = filesize($rutaArchivo5); // Tamaño del archivo en bytes
                    $fechaSubida5 = date("F d Y H:i:s.", filemtime($rutaArchivo5)); // Fecha de modificación (o subida)
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($archivo5); ?></td>
                        <!-- <td><?php echo strtoupper($extension5); ?></td> -->
                        <td><a href="<?php echo $rutaArchivo5; ?>" target="_blank"><button class="btn btn-secondary">Descargar</button></a></td>
                        <!-- <td>
                            <button onclick="mostrarInfo('<?php echo htmlspecialchars($archivo5); ?>', '<?php echo $tamaño5; ?>', '<?php echo $fechaSubida5; ?>')">Info</button>
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
