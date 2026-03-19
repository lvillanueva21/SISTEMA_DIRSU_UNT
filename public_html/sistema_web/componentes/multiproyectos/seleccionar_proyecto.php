<?php
session_start();
include("../../componentes/db.php");

if (!isset($_SESSION["usuario"]) || !isset($_POST['id_proyecto'])) {
    exit;
}

$usuario = $_SESSION["usuario"];
$id_nuevo_proyecto = intval($_POST['id_proyecto']);

// Obtener id y id_py actual del usuario
$stmt = $conexion->prepare("SELECT id, id_py FROM usuarios WHERE usuario = ?");
$stmt->bind_param("s", $usuario);
$stmt->execute();
$stmt->bind_result($id_usuario, $id_actual_py);
$stmt->fetch();
$stmt->close();

if ($id_actual_py != $id_nuevo_proyecto) {

    // Obtener período del proyecto anterior
    $periodo_anterior = "Desconocido";
    if ($id_actual_py > 0) {
        $stmt = $conexion->prepare("
            SELECT per.nombre
            FROM proyectos_periodo pp
            JOIN periodos per ON per.id = pp.id_periodo
            WHERE pp.id_py = ?
            LIMIT 1
        ");
        $stmt->bind_param("i", $id_actual_py);
        $stmt->execute();
        $stmt->bind_result($periodo_anterior);
        $stmt->fetch();
        $stmt->close();
    }

    // Obtener período del nuevo proyecto
    $periodo_nuevo = "Desconocido";
    $stmt = $conexion->prepare("
        SELECT per.nombre
        FROM proyectos_periodo pp
        JOIN periodos per ON per.id = pp.id_periodo
        WHERE pp.id_py = ?
        LIMIT 1
    ");
    $stmt->bind_param("i", $id_nuevo_proyecto);
    $stmt->execute();
    $stmt->bind_result($periodo_nuevo);
    $stmt->fetch();
    $stmt->close();

    // Actualizar en la base de datos
    $update = $conexion->prepare("UPDATE usuarios SET id_py = ? WHERE id = ?");
    $update->bind_param("ii", $id_nuevo_proyecto, $id_usuario);
    $update->execute();
    $update->close();

    // Actualizar en la sesión
    $_SESSION["id_py"] = $id_nuevo_proyecto;

    // Guardar en historial_proyectos
    $descripcion = "Se cambió el proyecto activo al ID: $id_nuevo_proyecto (período: $periodo_nuevo). El proyecto anterior era el ID: $id_actual_py (período: $periodo_anterior)";
    date_default_timezone_set('America/Lima');
$fecha = date("Y-m-d H:i:s");

    $stmt = $conexion->prepare("INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, ?, ?)");
    $stmt->bind_param("ssi", $descripcion, $fecha, $id_nuevo_proyecto);
    $stmt->execute();
    $stmt->close();
}
?>
