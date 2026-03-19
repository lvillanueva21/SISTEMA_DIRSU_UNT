<?php
// Incluir la conexión a la base de datos
include('../db.php');

// Consultar los departamentos
$query_departamentos = "SELECT id, name FROM ubigeo_peru_departments";
$result_departamentos = mysqli_query($conexion, $query_departamentos);

if (mysqli_num_rows($result_departamentos) > 0) {
    echo '<option value="">Selecciona un departamento</option>';
    while ($row = mysqli_fetch_assoc($result_departamentos)) {
        echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
    }
} else {
    echo '<option value="">No hay departamentos disponibles</option>';
}
?>
