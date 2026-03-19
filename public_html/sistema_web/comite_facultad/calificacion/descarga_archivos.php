<?php
$basePath = realpath(__DIR__ . "/../../componentes/archivo");

$categoria = $_GET['categoria'] ?? '';
$id_py     = isset($_GET['id_py']) ? intval($_GET['id_py']) : 0;
$archivo   = $_GET['archivo'] ?? '';
$ver       = isset($_GET['ver']) && $_GET['ver'] === '1';

if (!$categoria || !$id_py || !$archivo) {
    http_response_code(400);
    echo "Parámetros incompletos.";
    exit;
}

$archivo = basename($archivo); // Limpieza básica
$ruta = "$basePath/$categoria/$id_py/$archivo";
$rutaReal = realpath($ruta);

if (!$rutaReal || strpos($rutaReal, $basePath) !== 0 || !is_file($rutaReal)) {
    http_response_code(404);
    echo "Archivo no encontrado.";
    exit;
}

$extension = strtolower(pathinfo($archivo, PATHINFO_EXTENSION));
$esPDF = ($extension === 'pdf');
$nombreCodificado = rawurlencode($archivo);

if ($ver && $esPDF) {
    header('Content-Type: application/pdf');
    header("Content-Disposition: inline; filename=\"$archivo\"; filename*=UTF-8''$nombreCodificado");
    header('Content-Length: ' . filesize($rutaReal));
    readfile($rutaReal);
    exit;
}

// Forzar descarga para otros casos
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header("Content-Disposition: attachment; filename=\"$archivo\"; filename*=UTF-8''$nombreCodificado");
header('Content-Length: ' . filesize($rutaReal));
readfile($rutaReal);
exit;
