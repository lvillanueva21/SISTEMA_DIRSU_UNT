<?php
include("../../componentes/db.php");

if (isset($_GET['id'])) {
    $id_proyecto = (int)$_GET['id'];  // Obtener el ID del proyecto

    // Consulta para obtener los valores actuales de cot_cf y obs_cotejo_cf
    $query = "SELECT vb_df FROM proyectos_finales WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_proyecto);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $vb_df);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Enviar los resultados como una respuesta JSON
    echo json_encode([
        'vb_df' => $vb_df
    ]);
}
?>