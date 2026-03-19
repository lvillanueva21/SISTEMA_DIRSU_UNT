<?php
// Incluir la conexión a la base de datos
include('../db.php');

// Verificar si se recibió un id de departamento
if (isset($_POST['departamento_id'])) {
    $departamento_id = $_POST['departamento_id'];

    // Consultar las provincias del departamento seleccionado
    $query_provincias = "SELECT id, name FROM ubigeo_peru_provinces WHERE department_id = $departamento_id";
    $result_provincias = mysqli_query($conexion, $query_provincias);

    if (mysqli_num_rows($result_provincias) > 0) {
        echo '<option value="">Selecciona una provincia</option>';
        while ($row = mysqli_fetch_assoc($result_provincias)) {
            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
    } else {
        echo '<option value="">No hay provincias disponibles</option>';
    }
}
?>
