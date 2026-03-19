<?php
// Indicar que la respuesta será JSON
header('Content-Type: application/json');

// Obtener el ID del proyecto
$id_py = isset($_GET['id_py']) ? intval($_GET['id_py']) : 0;

// Validación básica
if ($id_py <= 0) {
    echo json_encode(['error' => 'ID de proyecto inválido']);
    exit;
}

// Definir categorías y ruta base corregida
$categorias = ['lista_docentes', 'lista_alumnos', 'diagrama', 'compromiso', 'carta'];
$basePath = "../../componentes/archivo"; // ✅ Ruta corregida
$resultado = [];

foreach ($categorias as $categoria) {
    $carpeta = "$basePath/$categoria/$id_py";
    if (is_dir($carpeta)) {
        // Filtrar archivos válidos, excluir . y ..
        $archivos = array_values(array_filter(scandir($carpeta), function ($f) {
            return !in_array($f, ['.', '..']);
        }));
        $resultado[$categoria] = !empty($archivos) ? $archivos : null;
    } else {
        $resultado[$categoria] = null;
    }
}

// Enviar respuesta JSON
echo json_encode($resultado);
