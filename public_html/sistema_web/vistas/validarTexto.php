<?php
// Conexión a la base de datos
$servername = "localhost"; // Cambia esto por tu servidor
$username = "gla_livp_2024_dirsu"; // Cambia esto por tu usuario de base de datos
$password = "passLIVP24@"; // Cambia esto por tu contraseña de base de datos
$dbname = "gla_dirsu_bd_2024"; // Cambia esto por el nombre de tu base de datos

// Crear conexión
$conn = new mysqli($servername, $username, $password, $dbname);

// Verificar conexión
if ($conn->connect_error) {
    die("La conexión falló: " . $conn->connect_error);
}

// Verificar si se ha enviado el formulario
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Obtener el contenido del formulario
    $contenido = $_POST['texto'];

    // Preparar la sentencia SQL para evitar inyecciones SQL
    $stmt = $conn->prepare("INSERT INTO texto (contenido) VALUES (?)");
    $stmt->bind_param("s", $contenido);

    // Ejecutar la consulta
    if ($stmt->execute()) {
        echo "El texto ha sido guardado exitosamente.";
    } else {
        echo "Error al guardar el texto: " . $stmt->error;
    }

    // Cerrar la sentencia y la conexión
    $stmt->close();
}

$conn->close();
?>
