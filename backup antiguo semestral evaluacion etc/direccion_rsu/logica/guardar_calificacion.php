<?php
include("../../componentes/db.php");
date_default_timezone_set('America/Lima');

if (isset($_POST['id']) && isset($_POST['estado'])) {
    $idProyecto = (int)$_POST['id'];
    $estado = (int)$_POST['estado'];
    $observacion = null;
    $tiempoDias = isset($_POST['tiempo_subsanacion']) ? (int)$_POST['tiempo_subsanacion'] : null;

    if ($estado === 2) {
        $observacion = isset($_POST['observacion']) ? $_POST['observacion'] : null;

        if (!$tiempoDias) {
            echo "error: tiempo no seleccionado";
            exit;
        }
    }

    if ($estado === 0 || $estado === 1) {
        $observacion = null;
    }

    // Guardar cot_dr y obs_cotejo_dr
    $query = "UPDATE proyectos_finales SET cot_dr = ?, obs_cotejo_dr = ? WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "isi", $estado, $observacion, $idProyecto);

    if (!mysqli_stmt_execute($stmt)) {
        echo "error"; 
        exit;
    }
    mysqli_stmt_close($stmt);

    $fechaActual = date('Y-m-d H:i:s');

    // 🟡 ESTADO: EN ESPERA
    if ($estado === 0) {
        $stmtR = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET rsu_cot = 0 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmtR, "i", $idProyecto);
        mysqli_stmt_execute($stmtR);
        mysqli_stmt_close($stmtR);

        // Copiar rsu_inicio a rsu_limite
        $rsu_inicio = null;
        $stmtInicio = mysqli_prepare($conexion, "SELECT rsu_inicio FROM cronogramas_semestrales WHERE id_py = ?");
        mysqli_stmt_bind_param($stmtInicio, "i", $idProyecto);
        mysqli_stmt_execute($stmtInicio);
        mysqli_stmt_bind_result($stmtInicio, $rsu_inicio);
        mysqli_stmt_fetch($stmtInicio);
        mysqli_stmt_close($stmtInicio);

        if ($rsu_inicio) {
            $stmtL = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET rsu_limite = ? WHERE id_py = ?");
            mysqli_stmt_bind_param($stmtL, "si", $rsu_inicio, $idProyecto);
            mysqli_stmt_execute($stmtL);
            mysqli_stmt_close($stmtL);
        }

    // 🟢 ESTADO: APROBADO
    } elseif ($estado === 1) {
        $stmtR = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET rsu_cot = 1 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmtR, "i", $idProyecto);
        mysqli_stmt_execute($stmtR);
        mysqli_stmt_close($stmtR);

        // Verificar condiciones para activar el estado
        $sql = "SELECT pcf_cot, pcf_rub, dd_vb, df_vb, rsu_cot, rsu_rub FROM rutas_semestrales WHERE id_py = ?";
        $stmt = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $pcf_cot, $pcf_rub, $dd_vb, $df_vb, $rsu_cot, $rsu_rub);

        if (mysqli_stmt_fetch($stmt)) {
            if ($pcf_cot == 1 && $pcf_rub == 1 && $dd_vb == 1 && $df_vb == 1 && $rsu_cot == 1 && $rsu_rub == 1) {
                mysqli_stmt_close($stmt);

                $stmtU = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET estado = 1 WHERE id_py = ?");
                mysqli_stmt_bind_param($stmtU, "i", $idProyecto);
                mysqli_stmt_execute($stmtU);
                mysqli_stmt_close($stmtU);

                $stmtF = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET rsu_fin = ? WHERE id_py = ?");
                mysqli_stmt_bind_param($stmtF, "si", $fechaActual, $idProyecto);
                mysqli_stmt_execute($stmtF);
                mysqli_stmt_close($stmtF);
            } else {
                mysqli_stmt_close($stmt);
            }
        }

    // 🔴 ESTADO: OBSERVADO
    } elseif ($estado === 2) {
        $stmtR = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET rsu_cot = 2 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmtR, "i", $idProyecto);
        mysqli_stmt_execute($stmtR);
        mysqli_stmt_close($stmtR);

        // Calcular límite de subsanación
        $limite = date('Y-m-d H:i:s', strtotime("+$tiempoDias days"));
        $stmtLimite = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET rsu_limite = ? WHERE id_py = ?");
        mysqli_stmt_bind_param($stmtLimite, "si", $limite, $idProyecto);
        mysqli_stmt_execute($stmtLimite);
        mysqli_stmt_close($stmtLimite);
    }

    echo "success";
}
?>
