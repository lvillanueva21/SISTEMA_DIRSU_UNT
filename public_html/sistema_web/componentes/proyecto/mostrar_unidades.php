<?php
// mostrar_lugares.php
// Incluir configSesion.php para verificar la sesión
include "configSesion.php";

// Incluir la conexión a la base de datos
include('db.php');

// Obtener el id del proyecto de la sesión
$id_proyecto = $_SESSION['id_py'];

// Consultar los registros para ese proyecto específico
$query = "SELECT * FROM unidades_multi WHERE id_py = '$id_proyecto'";
$result = mysqli_query($conexion, $query);

// Eliminar un registro si se recibe un parámetro 'eliminar'
if (isset($_GET['eliminar'])) {
    $id_unidad = $_GET['eliminar'];
    $delete_query = "DELETE FROM unidades_multi WHERE id = $id_unidad";
    if (mysqli_query($conexion, $delete_query)) {
        echo "<script>alert('Registro eliminado con éxito.'); window.location.href = 'unidades_ejecutoras.php';</script>";
    } else {
        echo "<script>alert('Error al eliminar el registro.');</script>";
    }
}
?>