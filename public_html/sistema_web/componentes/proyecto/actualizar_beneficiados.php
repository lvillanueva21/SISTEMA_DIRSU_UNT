<?php
// Iniciar la sesión
session_start();

// Incluir el archivo de conexión a la base de datos
include('../db.php');

// Validar que la sesión esté activa y que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Debe iniciar sesión para continuar.');</script>";
    echo "<script>location.assign('https://rsu.unitru.edu.pe/sistema_web/login.php');</script>";
    exit();
}

// Obtener el usuario de la sesión y el id_py del proyecto
$usuario = $_SESSION['usuario'];
$id_proyecto = $_SESSION['id_py']; // Se asume que el id_py está almacenado en la sesión

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Iniciar la transacción
    $conexion->begin_transaction();

    try {
        // Recuperar los valores del formulario
        $p7_1 = isset($_POST['p7_1']) ? $_POST['p7_1'] : null;
        $p7_2 = isset($_POST['p7_2']) ? $_POST['p7_2'] : null;
        
        $infantes = isset($_POST['infantes']) ? $_POST['infantes'] : null;
        $ninos = isset($_POST['ninos']) ? $_POST['ninos'] : null;
        $adolescentes = isset($_POST['adolescentes']) ? $_POST['adolescentes'] : null;
        $jovenes = isset($_POST['jovenes']) ? $_POST['jovenes'] : null;
        $adultos = isset($_POST['adultos']) ? $_POST['adultos'] : null;
        $adultos_mayores = isset($_POST['adultos_mayores']) ? $_POST['adultos_mayores'] : null;

        // Preparar la consulta para actualizar los datos en la tabla proyectos
        $sql_update_proyecto = "UPDATE proyectos 
                                SET p7_1 = ?, p7_2 = ?, infantes = ?, ninos = ?, adolescentes = ?, jovenes = ?, adultos = ?, adultos_mayores = ?
                                WHERE id = ?";
        $stmt = $conexion->prepare($sql_update_proyecto);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // Vincular los parámetros a la consulta
        $stmt->bind_param('ssiiiiiii', $p7_1, $p7_2, $infantes, $ninos, $adolescentes, $jovenes, $adultos, $adultos_mayores, $id_proyecto);

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el proyecto: " . $stmt->error);
        }

        // Confirmar la transacción
        $conexion->commit();

        echo "<script>alert('Proyecto actualizado exitosamente.');</script>";
        echo "<script>location.assign('../../vistas/datos_principales.php');</script>";
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        echo "<script>location.assign('../../vistas/datos_principales.php');</script>";
    }

    // Cerrar la declaración y la conexión
    if (isset($stmt)) $stmt->close();
    $conexion->close();
}
?>
