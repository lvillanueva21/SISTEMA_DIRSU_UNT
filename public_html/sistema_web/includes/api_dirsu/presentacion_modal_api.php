<?php
include_once __DIR__ . '/../../componentes/configSesion.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    echo "<div class='alert alert-danger'>Sesión no válida.</div>";
    exit;
}

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;
if ($id_py <= 0) {
    http_response_code(422);
    echo "<div class='alert alert-danger'>ID de proyecto inválido.</div>";
    exit;
}

$baseDir = realpath(__DIR__ . '/../../comite_facultad/calificacion');
if ($baseDir === false) {
    http_response_code(500);
    echo "<div class='alert alert-danger'>No se encontró el origen de presentación.</div>";
    exit;
}

$prevCwd = getcwd();
chdir($baseDir);
$_GET['id_py'] = $id_py;
require $baseDir . DIRECTORY_SEPARATOR . 'presentacion.php';
if ($prevCwd !== false) {
    chdir($prevCwd);
}

