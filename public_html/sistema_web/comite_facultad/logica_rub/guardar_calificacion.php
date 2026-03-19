<?php
include("../../componentes/db.php");
date_default_timezone_set('America/Lima');

if (isset($_POST['id']) && isset($_POST['rub_cf'])) {
    $idProyecto = (int)$_POST['id'];
    $rubCf = $_POST['rub_cf'];  // JSON con calificaciones
    $obsRubricaCf = $_POST['obs_rubrica_cf'];  // JSON con observaciones

    // Decodificar JSON para evaluar calificación total
    $calificaciones = json_decode($rubCf, true);
    $total = array_sum(array_map('intval', $calificaciones));

    // Determinar estado según la nota total
    if (in_array("0", $calificaciones)) {
        $estado = 0; // En espera
    } elseif ($total > 13) {
        $estado = 1; // Aprobado
    } else {
        $estado = 2; // Observado
    }

    // Guardar en proyectos_finales
    $query = "UPDATE proyectos_finales SET rub_cf = ?, obs_rubrica_cf = ? WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "ssi", $rubCf, $obsRubricaCf, $idProyecto);
    $success = mysqli_stmt_execute($stmt);
    mysqli_stmt_close($stmt);

    if (!$success) {
        echo "error";
        exit;
    }

    // Acciones según estado
    if ($estado === 0) {
        // EN ESPERA
        $stmt = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET pcf_rub = 0 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        // Resetear límite = inicio
        $inicio = null;
        $stmt = mysqli_prepare($conexion, "SELECT pcf_inicio FROM cronogramas_semestrales WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_bind_result($stmt, $inicio);
        mysqli_stmt_fetch($stmt);
        mysqli_stmt_close($stmt);

        if ($inicio) {
            $stmt = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET pcf_limite = ? WHERE id_py = ?");
            mysqli_stmt_bind_param($stmt, "si", $inicio, $idProyecto);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }

    } elseif ($estado === 1) {
        // APROBADO
        $now = date('Y-m-d H:i:s');

        $stmt = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET pcf_rub = 1 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET pcf_fin = ?, dd_inicio = ? WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "ssi", $now, $now, $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

    } elseif ($estado === 2) {
        // OBSERVADO
        $diasSubsanacion = isset($_POST['tiempo_subsanacion']) ? (int)$_POST['tiempo_subsanacion'] : 3;

        $fechaLimite = new DateTime('now', new DateTimeZone('America/Lima'));
        $fechaLimite->modify("+{$diasSubsanacion} days");
        $limiteStr = $fechaLimite->format('Y-m-d H:i:s');

        $stmt = mysqli_prepare($conexion, "UPDATE rutas_semestrales SET pcf_rub = 2 WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conexion, "UPDATE cronogramas_semestrales SET pcf_limite = ? WHERE id_py = ?");
        mysqli_stmt_bind_param($stmt, "si", $limiteStr, $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);

        $stmt = mysqli_prepare($conexion, "UPDATE proyectos SET estado = 0 WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $idProyecto);
        mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
    }

    echo "success";
}
?>