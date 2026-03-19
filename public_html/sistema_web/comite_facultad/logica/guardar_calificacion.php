<?php
include("../../componentes/db.php");

if (isset($_POST['id']) && isset($_POST['estado'])) {
    $idProyecto = (int)$_POST['id'];
    $estado = (int)$_POST['estado'];
    $observacion = null;
    $tiempoDias = null;

    // Si Observado, obtener observación y tiempo de subsanación
    if ($estado === 2) {
        $observacion = isset($_POST['observacion']) ? $_POST['observacion'] : null;
        $tiempoDias = isset($_POST['tiempo_subsanacion']) ? (int)$_POST['tiempo_subsanacion'] : null;

        if (!$tiempoDias) {
            echo "error: tiempo no seleccionado";
            exit;
        }
    }

    // Si En espera o Aprobado, observación será NULL
    if ($estado === 0 || $estado === 1) {
        $observacion = null;
    }

    // Actualizar calificación en proyectos_finales
    $query = "UPDATE proyectos_finales SET cot_cf = ?, obs_cotejo_cf = ? WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "isi", $estado, $observacion, $idProyecto);

    if (mysqli_stmt_execute($stmt)) {
        echo "success";

        date_default_timezone_set('America/Lima');
        $fechaHoraActual = date('Y-m-d H:i:s');

        // Estado Aprobado
        if ($estado === 1) {
            $queryRuta = "UPDATE rutas_semestrales SET pcf_cot = 1 WHERE id_py = ?";
            $stmtRuta = mysqli_prepare($conexion, $queryRuta);
            mysqli_stmt_bind_param($stmtRuta, "i", $idProyecto);
            mysqli_stmt_execute($stmtRuta);
            mysqli_stmt_close($stmtRuta);

            $queryCheck = "SELECT pcf_rub FROM rutas_semestrales WHERE id_py = ?";
            $stmtCheck = mysqli_prepare($conexion, $queryCheck);
            mysqli_stmt_bind_param($stmtCheck, "i", $idProyecto);
            mysqli_stmt_execute($stmtCheck);
            mysqli_stmt_bind_result($stmtCheck, $pcf_rub);
            mysqli_stmt_fetch($stmtCheck);
            mysqli_stmt_close($stmtCheck);

            if ($pcf_rub == 1) {
                $queryCrono = "UPDATE cronogramas_semestrales SET pcf_fin = ?, dd_inicio = ? WHERE id_py = ?";
                $stmtCrono = mysqli_prepare($conexion, $queryCrono);
                mysqli_stmt_bind_param($stmtCrono, "ssi", $fechaHoraActual, $fechaHoraActual, $idProyecto);
                mysqli_stmt_execute($stmtCrono);
                mysqli_stmt_close($stmtCrono);
            }

        } elseif ($estado === 2 && $tiempoDias) {
            // Estado Observado
            $fechaLimite = date('Y-m-d H:i:s', strtotime("+$tiempoDias days"));

            $queryRuta = "UPDATE rutas_semestrales SET pcf_cot = 2 WHERE id_py = ?";
            $stmtRuta = mysqli_prepare($conexion, $queryRuta);
            mysqli_stmt_bind_param($stmtRuta, "i", $idProyecto);
            mysqli_stmt_execute($stmtRuta);
            mysqli_stmt_close($stmtRuta);

            $queryCrono = "UPDATE cronogramas_semestrales SET pcf_limite = ? WHERE id_py = ?";
            $stmtCrono = mysqli_prepare($conexion, $queryCrono);
            mysqli_stmt_bind_param($stmtCrono, "si", $fechaLimite, $idProyecto);
            mysqli_stmt_execute($stmtCrono);
            mysqli_stmt_close($stmtCrono);

            // 🔁 NUEVA MEJORA: Cambiar estado = 0 en tabla proyectos
            $queryProyecto = "UPDATE proyectos SET estado = 0 WHERE id = ?";
            $stmtProyecto = mysqli_prepare($conexion, $queryProyecto);
            mysqli_stmt_bind_param($stmtProyecto, "i", $idProyecto);
            mysqli_stmt_execute($stmtProyecto);
            mysqli_stmt_close($stmtProyecto);

        } elseif ($estado === 0) {
            // Estado En espera
            $queryInicio = "SELECT pcf_inicio FROM cronogramas_semestrales WHERE id_py = ?";
            $stmtInicio = mysqli_prepare($conexion, $queryInicio);
            mysqli_stmt_bind_param($stmtInicio, "i", $idProyecto);
            mysqli_stmt_execute($stmtInicio);
            mysqli_stmt_bind_result($stmtInicio, $pcf_inicio);
            mysqli_stmt_fetch($stmtInicio);
            mysqli_stmt_close($stmtInicio);

            $queryReset = "UPDATE cronogramas_semestrales SET pcf_limite = ? WHERE id_py = ?";
            $stmtReset = mysqli_prepare($conexion, $queryReset);
            mysqli_stmt_bind_param($stmtReset, "si", $pcf_inicio, $idProyecto);
            mysqli_stmt_execute($stmtReset);
            mysqli_stmt_close($stmtReset);

            $queryRuta = "UPDATE rutas_semestrales SET pcf_cot = 0 WHERE id_py = ?";
            $stmtRuta = mysqli_prepare($conexion, $queryRuta);
            mysqli_stmt_bind_param($stmtRuta, "i", $idProyecto);
            mysqli_stmt_execute($stmtRuta);
            mysqli_stmt_close($stmtRuta);
        }

    } else {
        echo "error";
    }

    mysqli_stmt_close($stmt);
}
?>