<?php
// Incluir el archivo de conexión a la base de datos
include('db.php');

// Verificar si se ha enviado el formulario con el botón 'Crear nuevo proyecto'
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    
    // Preparar la consulta para insertar un registro vacío
    $sql = "INSERT INTO proyectos (id) VALUES (NULL)";

    // Ejecutar la consulta
    if (mysqli_query($conexion, $sql)) {
        echo "Nuevo proyecto creado exitosamente.";
    } else {
        echo "Error al crear el proyecto: " . mysqli_error($conexion);
    }
    
    // Cerrar la conexión
    mysqli_close($conexion);
}
?>

<!-- HTML con el botón para crear un nuevo proyecto -->
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear nuevo proyecto</title>
</head>
<body>
    <h1>Crear nuevo proyecto</h1>
    <form method="POST">
        <button type="submit">Crear nuevo proyecto</button>
    </form>
</body>
</html>
