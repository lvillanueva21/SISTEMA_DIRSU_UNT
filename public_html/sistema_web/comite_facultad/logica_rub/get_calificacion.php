<?php
include("../../componentes/db.php");

if (isset($_GET['id'])) {
    $id_proyecto = (int)$_GET['id'];  // Obtener el ID del proyecto

    // Consulta para obtener los valores actuales de rub_cf y obs_rubrica_cf
    $query = "SELECT rub_cf, obs_rubrica_cf FROM proyectos_finales WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_proyecto);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rub_cf, $obs_rubrica_cf);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Si los valores son null, asignamos valores por defecto
    if (is_null($rub_cf)) {
        $rub_cf = '["0","0","0","0","0"]';  // Valor por defecto para rub_cf
    }

    if (is_null($obs_rubrica_cf)) {
        $obs_rubrica_cf = '["","","","",""]';  // Valor por defecto para obs_rubrica_cf
    }

    // Enviar los resultados como una respuesta JSON
    echo json_encode([
        'rub_cf' => $rub_cf,
        'obs_rubrica_cf' => $obs_rubrica_cf
    ]);
}
?>
