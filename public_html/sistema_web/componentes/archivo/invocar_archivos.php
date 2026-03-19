<?php
session_start();

// Asegúrate de que $_SESSION['id_py'] esté definido
if (!isset($_SESSION['id_py'])) {
    die('ID de docente no definido.');
}

// Rutas de los directorios donde se buscarán los archivos
$directorios = [
    '../componentes/archivo/lista_docentes/' . $_SESSION['id_py'] . '/',
    '../componentes/archivo/lista_alumnos/' . $_SESSION['id_py'] . '/',
    '../componentes/archivo/visto_bueno/' . $_SESSION['id_py'] . '/',
    '../componentes/archivo/diagrama/' . $_SESSION['id_py'] . '/',
    '../componentes/archivo/compromiso/' . $_SESSION['id_py'] . '/',
    '../componentes/archivo/carta/' . $_SESSION['id_py'] . '/'
];

// Inicializa un array para almacenar todos los archivos encontrados
$archivosEncontrados = [];

foreach ($directorios as $directorio) {
    // Verifica si el directorio existe
    if (is_dir($directorio)) {
        // Obtener los archivos en el directorio
        $archivos = array_diff(scandir($directorio), array('..', '.')); // Excluir los directorios '.' y '..'
        foreach ($archivos as $archivo) {
            $archivosEncontrados[] = [
                'nombre' => $archivo,
                'ruta' => $directorio . $archivo
            ];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Archivos Subidos</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
            <?php if (count($archivosEncontrados) > 0): ?>
                <?php foreach ($archivosEncontrados as $archivo): ?>
                    <a href="<?php echo $archivo['ruta']; ?>" class="btn-link text-secondary" download><i class="far fa-fw fa-file"></i>
                        <?php echo htmlspecialchars($archivo['nombre']); ?>
                    </a><br>
                <?php endforeach; ?>
            <?php else: ?>
                <p>Aún no has subido ningún archivo.</p>
            <?php endif; ?>
</body>
</html>
