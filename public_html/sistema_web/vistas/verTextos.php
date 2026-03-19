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

// Consulta SQL para obtener todos los registros de la tabla texto
$sql = "SELECT id, contenido FROM texto";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ver Textos</title>
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h2 class="mb-4">Lista de Textos Guardados</h2>
        <table class="table table-bordered">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Texto</th>
                </tr>
            </thead>
            <tbody>
                <?php
                if ($result->num_rows > 0) {
                    // Recorrer y mostrar los resultados
                    while($row = $result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . htmlspecialchars($row["id"]) . "</td>";
                        echo "<td>" . $row["contenido"] . "</td>";
                        echo "</tr>";
                    }
                } else {
                    echo "<tr><td colspan='2'>No hay textos guardados.</td></tr>";
                }
                ?>
            </tbody>
        </table>
    </div>
</body>
</html>

<?php
// Cerrar la conexión
$conn->close();
?>