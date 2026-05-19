<?php
include_once "../componentes/configSesion.php";
include_once "../includes/db_connection.php";

header('Content-Type: application/json; charset=UTF-8');

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    echo json_encode(array(
        'ok' => false,
        'message' => 'Conexion a base de datos no disponible.',
    ));
    exit;
}

$id_py = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
$id_legacy = isset($_POST['id_legacy']) ? (int)$_POST['id_legacy'] : 0;

if ($id_py <= 0 || $id_legacy <= 0) {
    echo json_encode(array(
        'ok' => false,
        'message' => 'Parametros invalidos.',
    ));
    exit;
}

mysqli_begin_transaction($conexion);

try {
    $check = mysqli_prepare(
        $conexion,
        "SELECT id FROM proyectos_finales WHERE id = ? AND id_py = ? LIMIT 1"
    );

    if (!$check) {
        throw new Exception('No se pudo preparar la verificacion del registro legacy.');
    }

    mysqli_stmt_bind_param($check, 'ii', $id_legacy, $id_py);
    if (!mysqli_stmt_execute($check)) {
        mysqli_stmt_close($check);
        throw new Exception('No se pudo verificar el registro legacy.');
    }

    $rs = mysqli_stmt_get_result($check);
    $exists = ($rs && mysqli_num_rows($rs) > 0);
    if ($rs) {
        mysqli_free_result($rs);
    }
    mysqli_stmt_close($check);

    if (!$exists) {
        throw new Exception('No existe el registro legacy indicado para este proyecto.');
    }

    $delete = mysqli_prepare(
        $conexion,
        "DELETE FROM proyectos_finales WHERE id = ? AND id_py = ? LIMIT 1"
    );

    if (!$delete) {
        throw new Exception('No se pudo preparar la eliminacion del registro legacy.');
    }

    mysqli_stmt_bind_param($delete, 'ii', $id_legacy, $id_py);
    if (!mysqli_stmt_execute($delete)) {
        mysqli_stmt_close($delete);
        throw new Exception('No se pudo eliminar el registro legacy.');
    }

    $afectadas = mysqli_stmt_affected_rows($delete);
    mysqli_stmt_close($delete);

    if ((int)$afectadas !== 1) {
        throw new Exception('No se elimino ningun registro legacy.');
    }

    mysqli_commit($conexion);

    echo json_encode(array(
        'ok' => true,
        'message' => 'Registro legacy eliminado correctamente.',
        'id_py' => $id_py,
        'id_legacy' => $id_legacy,
    ));
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(array(
        'ok' => false,
        'message' => $e->getMessage(),
    ));
}