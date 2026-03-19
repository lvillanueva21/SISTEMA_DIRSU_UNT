<?php
include("../../componentes/db.php");

if (isset($_POST['id']) && isset($_POST['estado'])) {
    $idProyecto = $_POST['id'];
    $estado = $_POST['estado'];

    // Preparar la consulta SQL para actualizar el proyecto
    $query = "UPDATE proyectos_finales SET vb_df = ? WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ii", $estado, $idProyecto);
    
    if (mysqli_stmt_execute($stmt)) {
        echo "success";  // Devolver una respuesta de éxito
    } else {
        echo "error";  // Si hay un error en la ejecución
    }

    mysqli_stmt_close($stmt);
}
?>
