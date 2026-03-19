<?php
// Incluir la conexión a la base de datos
include('../db.php');

// Verificar si se recibió un id de provincia
if (isset($_POST['provincia_id'])) {
    $provincia_id = $_POST['provincia_id'];

    // Consultar los distritos de la provincia seleccionada
    $query_distritos = "SELECT id, name FROM ubigeo_peru_districts WHERE province_id = $provincia_id";
    $result_distritos = mysqli_query($conexion, $query_distritos);

    if (mysqli_num_rows($result_distritos) > 0) {
        echo '<option value="">Selecciona un distrito</option>';
        while ($row = mysqli_fetch_assoc($result_distritos)) {
            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
    } else {
        echo '<option value="">No hay distritos disponibles</option>';
    }
}
?>
