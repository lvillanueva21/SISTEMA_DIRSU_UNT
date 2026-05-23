<?php
include_once __DIR__ . '/../../componentes/configSesion.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo "Sesión no válida.";
    exit;
}

$baseDir = realpath(__DIR__ . '/../../comite_facultad/calificacion');
if ($baseDir === false) {
    http_response_code(500);
    echo "No se encontró el origen de descarga.";
    exit;
}

if (isset($_GET['id_py'])) {
    $_GET['id_py'] = (int)$_GET['id_py'];
}
if (isset($_GET['categoria'])) {
    $_GET['categoria'] = trim((string)$_GET['categoria']);
}
if (isset($_GET['archivo'])) {
    $_GET['archivo'] = trim((string)$_GET['archivo']);
}
if (isset($_GET['ver'])) {
    $_GET['ver'] = (int)$_GET['ver'];
}

$prevCwd = getcwd();
chdir($baseDir);
require $baseDir . DIRECTORY_SEPARATOR . 'descarga_archivos.php';
if ($prevCwd !== false) {
    chdir($prevCwd);
}

