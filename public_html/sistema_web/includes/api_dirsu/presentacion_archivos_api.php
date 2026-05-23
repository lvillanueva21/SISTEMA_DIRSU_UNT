<?php
include_once __DIR__ . '/../../componentes/configSesion.php';

if (!isset($_SESSION['usuario'])) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'Sesión no válida.'));
    exit;
}

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;
if ($id_py <= 0) {
    http_response_code(422);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'ID de proyecto inválido.'));
    exit;
}

$baseDir = realpath(__DIR__ . '/../../comite_facultad/calificacion');
if ($baseDir === false) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(array('error' => 'No se encontró el origen de archivos.'));
    exit;
}

$prevCwd = getcwd();
chdir($baseDir);
$_GET['id_py'] = $id_py;
require $baseDir . DIRECTORY_SEPARATOR . 'gestion_archivos.php';
if ($prevCwd !== false) {
    chdir($prevCwd);
}

