<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// 1. Conexión DB
require_once __DIR__ . '/../componentes/db.php';

// 2. Autocarga sencilla (PSR-4 no estricta)
spl_autoload_register(function ($class) {
    $base = __DIR__ . '/../modulos/';
    $file = $base . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) require $file;
});

// 3. Instanciar el motor
use Merp\MerpEngine;

$merp = new MerpEngine($conexion);      // $conexion viene de db.php
$tablaHtml = $merp->render('proyectos'); // nombre lógico del reporte

?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Progreso de proyectos</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="assets/merp.css"><!-- colores extra -->
</head>
<body class="p-4">
    <h1 class="mb-4">Progreso de proyectos</h1>
    <?= $tablaHtml ?>
</body>
</html>
