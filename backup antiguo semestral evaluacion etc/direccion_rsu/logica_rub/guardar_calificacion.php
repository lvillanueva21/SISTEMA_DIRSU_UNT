<?php
include("../../componentes/db.php");

if (isset($_POST['id']) && isset($_POST['rub_dr'])) {
    $idProyecto = $_POST['id'];
    $rubDr = $_POST['rub_dr']; // JSON de calificaciones
    $obsRubricaDr = $_POST['obs_rubrica_dr']; // JSON de observaciones
    $tiempoSubsanacion = isset($_POST['tiempo_subsanacion']) ? $_POST['tiempo_subsanacion'] : null;

    // 1. Guardar calificaciones en proyectos_finales
    $query = "UPDATE proyectos_finales SET rub_dr = ?, obs_rubrica_dr = ? WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $rubDr, $obsRubricaDr, $idProyecto);
    $resultado = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$resultado) {
        echo "error";
        exit;
    }

    // 2. Calcular suma total de la rúbrica
    $calificaciones = json_decode($rubDr, true);
    $total = array_sum(array_map('intval', $calificaciones));

    // 3. Obtener IDs relacionados
    $queryRel = "SELECT id_ruta, id_cronograma, id_proyecto FROM proyectos WHERE id_py = ?";
    $stmtRel = mysqli_prepare($conexion, $queryRel);
    mysqli_stmt_bind_param($stmtRel, "i", $idProyecto);
    mysqli_stmt_execute($stmtRel);
    mysqli_stmt_bind_result($stmtRel, $idRuta, $idCronograma, $idProyectoBase);
    mysqli_stmt_fetch($stmtRel);
    mysqli_stmt_close($stmtRel);

    if ($total <= 13 && $total > 0 && $tiempoSubsanacion !== null) {
        // 4. Si el proyecto está observado, guardar tiempo y estado

        // 4.1 Actualizar rutas_semestrales (rsu_rub = 2)
        $sqlRuta = "UPDATE rutas_semestrales SET rsu_rub = 2 WHERE id_ruta = ?";
        $stmtRuta = mysqli_prepare($conexion, $sqlRuta);
        mysqli_stmt_bind_param($stmtRuta, "i", $idRuta);
        mysqli_stmt_execute($stmtRuta);
        mysqli_stmt_close($stmtRuta);

        // 4.2 Calcular fecha límite en cronogramas_semestrales
        $dias = (int)$tiempoSubsanacion;
        $fechaLimite = date('Y-m-d', strtotime("+$dias days"));

        $sqlCronograma = "UPDATE cronogramas_semestrales SET rsu_limite = ? WHERE id_cronograma = ?";
        $stmtCrono = mysqli_prepare($conexion, $sqlCronograma);
        mysqli_stmt_bind_param($stmtCrono, "si", $fechaLimite, $idCronograma);
        mysqli_stmt_execute($stmtCrono);
        mysqli_stmt_close($stmtCrono);

        // 4.3 Actualizar estado del proyecto a "En espera"
        $sqlProyecto = "UPDATE proyectos SET estado = 0 WHERE id_proyecto = ?";
        $stmtProyecto = mysqli_prepare($conexion, $sqlProyecto);
        mysqli_stmt_bind_param($stmtProyecto, "i", $idProyectoBase);
        mysqli_stmt_execute($stmtProyecto);
        mysqli_stmt_close($stmtProyecto);
    }

    // 5. Si aprobado (total > 13), marcar como aprobado
    if ($total > 13) {
        $sqlAprobado = "UPDATE rutas_semestrales SET rsu_rub = 1 WHERE id_ruta = ?";
        $stmtAprobado = mysqli_prepare($conexion, $sqlAprobado);
        mysqli_stmt_bind_param($stmtAprobado, "i", $idRuta);
        mysqli_stmt_execute($stmtAprobado);
        mysqli_stmt_close($stmtAprobado);

        // Verificar si puede cerrarse RSU
        $sqlVerificacion = "SELECT pcf_cot, pcf_rub, dd_vb, df_vb, rsu_cot, rsu_rub FROM rutas_semestrales WHERE id_ruta = ?";
        $stmtCheck = mysqli_prepare($conexion, $sqlVerificacion);
        mysqli_stmt_bind_param($stmtCheck, "i", $idRuta);
        mysqli_stmt_execute($stmtCheck);
        mysqli_stmt_bind_result($stmtCheck, $pcf_cot, $pcf_rub, $dd_vb, $df_vb, $rsu_cot, $rsu_rub);
        mysqli_stmt_fetch($stmtCheck);
        mysqli_stmt_close($stmtCheck);

        if ($pcf_cot == 1 && $pcf_rub == 1 && $dd_vb == 1 && $df_vb == 1 && $rsu_cot == 1 && $rsu_rub == 1) {
            // Actualizar estado del proyecto a aprobado (ej. 1 o cualquier lógica que tengas)
            $sqlEstado = "UPDATE proyectos SET estado = 1 WHERE id_proyecto = ?";
            $stmtEstado = mysqli_prepare($conexion, $sqlEstado);
            mysqli_stmt_bind_param($stmtEstado, "i", $idProyectoBase);
            mysqli_stmt_execute($stmtEstado);
            mysqli_stmt_close($stmtEstado);
        }
    }

    echo "success";
}
?>