<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de docente no definido.');
}

// Directorio donde se guardan los archivos
$directorio = '../componentes/archivo/lista_docentes/' . $_SESSION['id_py'] . '/';

// Inicializa la variable para el nombre del archivo
$archivo_lista_docente = 'Aún no has subido un archivo';

//Linea agregada
$archivos = [];

// Verifica si el directorio existe
if (is_dir($directorio)) {
    // Obtener los archivos en el directorio
    $archivos = array_diff(scandir($directorio), array('..', '.')); // Excluir los directorios '.' y '..'

    // Verifica si hay archivos en el directorio
    if (count($archivos) > 0) {
        // Solo toma el primer archivo encontrado (puedes modificar esto si es necesario)
        $archivo_lista_docente = $archivos[0]; // Cambia esto según tu lógica
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
            <?php if (count($archivos) > 0): ?>
                <?php foreach ($archivos as $archivo): ?>
                    <?php
                    // Obtener información del archivo
                    $rutaArchivo = $directorio . $archivo;
                    $extension = pathinfo($archivo, PATHINFO_EXTENSION);
                    $tamaño = filesize($rutaArchivo); // Tamaño del archivo en bytes
                    $fechaSubida = date("F d Y H:i:s.", filemtime($rutaArchivo)); // Fecha de modificación (o subida)
                    ?>
                    <tr>
                        <td><?php echo htmlspecialchars($archivo); ?></td>
                        <!-- <td><?php echo strtoupper($extension); ?></td> -->
                        <td><a href="<?php echo $rutaArchivo; ?>"><button class="btn btn-secondary">Descargar</button></a></td>
                        <!-- <td>
                            <button onclick="mostrarInfo('<?php echo htmlspecialchars($archivo); ?>', '<?php echo $tamaño; ?>', '<?php echo $fechaSubida; ?>')">Info</button>
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
