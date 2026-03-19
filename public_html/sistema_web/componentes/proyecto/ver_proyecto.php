<?php
session_start();
include "../db.php"; // Asegúrate de que esta ruta es correcta y que se conecta a la base de datos adecuadamente

// Validar que la sesión esté activa y que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Debe iniciar sesión para continuar.');</script>";
    echo "<script>location.assign('https://rsu.unitru.edu.pe/sistema_web/login.php');</script>";
    exit();
}

// Obtener el usuario de la sesión
$usuario = $_SESSION['usuario'];

// Obtener el id_py del usuario logueado
$queryUsuario = "SELECT id_py FROM usuarios WHERE usuario = ?";
$stmtUsuario = $conexion->prepare($queryUsuario);
$stmtUsuario->bind_param('s', $usuario);
$stmtUsuario->execute();
$resultUsuario = $stmtUsuario->get_result();
$rowUsuario = $resultUsuario->fetch_assoc();

$id_py = $rowUsuario['id_py'];

// Verificar si el usuario tiene un id_py asignado
if (!$id_py) {
    echo "<script>alert('No se ha asignado un proyecto para este usuario.');</script>";
    echo "<script>location.assign('../../inicio.php');</script>";
    exit();
}

// Realizar el SELECT de todos los campos del proyecto correspondiente al id_py
$queryProyecto = "SELECT * FROM proyectos WHERE id = ?";
$stmtProyecto = $conexion->prepare($queryProyecto);
$stmtProyecto->bind_param('i', $id_py);
$stmtProyecto->execute();
$resultProyecto = $stmtProyecto->get_result();
$proyecto = $resultProyecto->fetch_assoc();

// Verificar si se encontró un proyecto
if (!$proyecto) {
    echo "<script>alert('No se encontró el proyecto asociado.');</script>";
    echo "<script>location.assign('../../inicio.php');</script>";
    exit();
}

// Devolver el array de datos del proyecto
return $proyecto;
?>
