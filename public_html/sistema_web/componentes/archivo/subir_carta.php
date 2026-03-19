<?php
// Iniciar sesión
session_start();

// Suponemos que ya tienes la variable $id_py definida con el id del usuario actual
$id_py = $_SESSION['id_py']; // Cambia esto según cómo obtienes la variable

// Definir la ruta de la carpeta
$targetDir = "carta/";
$folderPath = $targetDir . $id_py . "/";

// Verificar si la carpeta ya existe
if (!file_exists($folderPath)) {
    // Crear la carpeta si no existe
    if (!mkdir($folderPath, 0777, true)) {
        echo json_encode(["error" => "No se pudo crear la carpeta."]);
        exit;
    }
} else {
    // Vaciar la carpeta si ya existe
    $files = glob($folderPath . '*'); // Obtener todos los archivos de la carpeta
    foreach ($files as $file) {
        if (is_file($file)) {
            unlink($file); // Eliminar el archivo
        }
    }
}

// Verificar si se ha subido un archivo
if (!empty($_FILES)) {
    $file = $_FILES['file']; // 'file' debe coincidir con el nombre del input

    // Verificar errores
    if ($file['error'] !== UPLOAD_ERR_OK) {
        echo json_encode(["error" => "Error al subir el archivo. Código de error: " . $file['error']]);
        exit;
    }

    // Sanitizar el nombre del archivo
    $sanitizedFileName = preg_replace('/[^a-zA-Z0-9_. -]/', '', basename($file['name']));

    // Mover el archivo a la carpeta correspondiente
    $filePath = $folderPath . $sanitizedFileName;
    
    if (move_uploaded_file($file['tmp_name'], $filePath)) {
        echo json_encode([
            "success" => "Archivo subido correctamente.",
            "fileName" => $sanitizedFileName, // Usar el nombre sanitizado
            "fileSize" => $file['size'] // Tamaño del archivo
        ]);
    } else {
        echo json_encode(["error" => "Error al mover el archivo a la carpeta."]);
    }
} else {
    echo json_encode(["error" => "No se recibió ningún archivo."]);
}
?>
