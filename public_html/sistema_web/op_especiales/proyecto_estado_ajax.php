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
$activo = isset($_POST['activo']) ? (int)$_POST['activo'] : -1;

if ($id_py <= 0 || ($activo !== 0 && $activo !== 1)) {
    echo json_encode(array(
        'ok' => false,
        'message' => 'Parametros invalidos.',
    ));
    exit;
}

mysqli_begin_transaction($conexion);

try {
    $stmt = mysqli_prepare(
        $conexion,
        "UPDATE usuarios_proyectos up
         INNER JOIN usuarios u
             ON u.id = up.id_usuario
            AND u.id_rol = 2
         SET up.activo = ?
         WHERE up.id_proyecto = ?"
    );

    if (!$stmt) {
        throw new Exception('No se pudo preparar la actualizacion de estado.');
    }

    mysqli_stmt_bind_param($stmt, "ii", $activo, $id_py);
    if (!mysqli_stmt_execute($stmt)) {
        mysqli_stmt_close($stmt);
        throw new Exception('No se pudo actualizar la relacion del proyecto.');
    }
    $afectadas = mysqli_stmt_affected_rows($stmt);
    mysqli_stmt_close($stmt);

    $descripcion = ($activo === 1)
        ? 'Estado de relacion proyecto-coordinador: ACTIVADO desde Operaciones Especiales'
        : 'Estado de relacion proyecto-coordinador: DESACTIVADO desde Operaciones Especiales';

    $hist = mysqli_prepare(
        $conexion,
        "INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, NOW(), ?)"
    );
    if ($hist) {
        mysqli_stmt_bind_param($hist, "si", $descripcion, $id_py);
        mysqli_stmt_execute($hist);
        mysqli_stmt_close($hist);
    }

    mysqli_commit($conexion);

    echo json_encode(array(
        'ok' => true,
        'message' => ($activo === 1) ? 'Proyecto activado correctamente.' : 'Proyecto desactivado correctamente.',
        'id_py' => $id_py,
        'activo' => $activo,
        'afectadas' => (int)$afectadas,
    ));
} catch (Exception $e) {
    mysqli_rollback($conexion);
    echo json_encode(array(
        'ok' => false,
        'message' => $e->getMessage(),
    ));
}

