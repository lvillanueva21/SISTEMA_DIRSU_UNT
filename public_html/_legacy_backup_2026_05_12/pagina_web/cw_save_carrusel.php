<?php
require_once __DIR__ . '/../cw_config.php';

$data = json_decode($_POST['data'] ?? '[]', true);

// Borra todo para reinsertar ordenado
$mysqli->query("DELETE FROM cw_carrusel");

$stmt = $mysqli->prepare(
    "INSERT INTO cw_carrusel
     (imagen, orden, visible, titulo, mostrar_titulo, subtitulo, mostrar_subtitulo)
     VALUES (?, ?, ?, ?, ?, ?, ?)"
);

if (!$stmt) {
    http_response_code(500);
    echo json_encode(["status"=>"error","msg"=>"Prepare failed: ".$mysqli->error]);
    exit;
}

/* 7 parámetros:
   imagen→s | orden→i | visible→i | titulo→s | mostrar_titulo→i | subtitulo→s | mostrar_subtitulo→i
            si  i   s    i   s    i
            s  i  i  s  i  s  i   →  "siisisi"
*/
foreach ($data as $d) {
    $stmt->bind_param(
        "siisisi",
        $d['imagen'],
        $d['orden'],
        $d['visible'],
        $d['titulo'],
        $d['mostrar_titulo'],
        $d['subtitulo'],
        $d['mostrar_subtitulo']
    );
    if (!$stmt->execute()) {
        http_response_code(500);
        echo json_encode(["status"=>"error","msg"=>"Execute failed: ".$stmt->error]);
        exit;
    }
}

echo json_encode(["status"=>"ok","msg"=>"Carrusel actualizado correctamente"]);
?>
