<?php
$targetDir = __DIR__ . "/carrusel/";
if (!file_exists($targetDir)) mkdir($targetDir, 0755, true);

if (!empty($_FILES['file']['name'])) {
    $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'gif'])) {
        http_response_code(400);
        echo json_encode(["error" => "Formato no permitido"]);
        exit;
    }

    $fileName = uniqid("slide_") . "." . $ext;
    $targetFile = $targetDir . $fileName;

    if (move_uploaded_file($_FILES['file']['tmp_name'], $targetFile)) {
        echo json_encode(["file" => "pagina_web/carrusel/" . $fileName]);
    } else {
        http_response_code(500);
        echo json_encode(["error" => "Error al subir"]);
    }
}
?>
