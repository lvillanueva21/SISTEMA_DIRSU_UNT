<?php
include "../db.php";
$q = "SELECT d.id, d.nombre, f.nombre AS facultad
        FROM departamentos d
        JOIN facultades f ON f.id = d.id_facultad
    ORDER BY d.nombre";
echo json_encode(mysqli_fetch_all(mysqli_query($conexion, $q), MYSQLI_ASSOC));
 