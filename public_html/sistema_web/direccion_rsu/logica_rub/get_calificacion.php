<?php
include("../../componentes/db.php");

if (isset($_GET['id'])) {
    $id_proyecto = (int)$_GET['id'];

    // Consulta principal para obtener rubrica y observaciones
    $query = "SELECT rub_dr, obs_rubrica_dr FROM proyectos_finales WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_proyecto);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $rub_dr, $obs_rubrica_dr);
    mysqli_stmt_fetch($stmt);
    mysqli_stmt_close($stmt);

    // Valores por defecto si son nulos
    if (is_null($rub_dr)) $rub_dr = '["0","0","0","0","0"]';
    if (is_null($obs_rubrica_dr)) $obs_rubrica_dr = '["","","","",""]';

    // Consulta adicional para obtener tiempo de subsanación si existe
    $querySub = "SELECT tiempo_subsanacion_rub FROM rutas_semestrales WHERE id_py = ?";
    $stmtSub = mysqli_prepare($conexion, $querySub);
    mysqli_stmt_bind_param($stmtSub, "i", $id_proyecto);
    mysqli_stmt_execute($stmtSub);
    mysqli_stmt_bind_result($stmtSub, $tiempo_subsanacion_rub);
    mysqli_stmt_fetch($stmtSub);
    mysqli_stmt_close($stmtSub);

    // Respuesta JSON
    echo json_encode([
        'rub_dr' => $rub_dr,
        'obs_rubrica_dr' => $obs_rubrica_dr,
        'tiempo_subsanacion_rub' => $tiempo_subsanacion_rub ?? null
    ]);
}
?>
