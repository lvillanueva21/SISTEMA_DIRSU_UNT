<?php
// Verifica si 'id_py_temp' está definida
if (!isset($id_py_temp)) {
    die('ID de docente no definido.');
}

// Usamos 'id_py_temp' para la búsqueda de archivos
$directorios = [
    '../../componentes/archivo/lista_docentes/' . $id_py_temp . '/',
    '../../componentes/archivo/lista_alumnos/' . $id_py_temp . '/',
    '../../componentes/archivo/visto_bueno/' . $id_py_temp . '/',
    '../../componentes/archivo/diagrama/' . $id_py_temp . '/',
    '../../componentes/archivo/compromiso/' . $id_py_temp . '/',
    '../../componentes/archivo/carta/' . $id_py_temp . '/'
];

// Inicializa un array para almacenar todos los archivos encontrados
$archivosEncontrados = [];

foreach ($directorios as $directorio) {
    if (is_dir($directorio)) {
        $archivos = array_diff(scandir($directorio), array('..', '.'));
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
