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
        
        $planificacion = isset($_POST['planificacion']) ? $_POST['planificacion'] : null;
        $ejecucion = isset($_POST['ejecucion']) ? $_POST['ejecucion'] : null;
        $monitoreo = isset($_POST['monitoreo']) ? $_POST['monitoreo'] : null;
        $p10_1s = isset($_POST['p10_1s']) ? $_POST['p10_1s'] : null;
        $p10_2s = isset($_POST['p10_2s']) ? $_POST['p10_2s'] : null;
        
        $p10_3s = isset($_POST['p10_3s']) ? $_POST['p10_3s'] : null;
        $p10_1h = isset($_POST['p10_1h']) ? $_POST['p10_1h'] : null;
        $p10_2h = isset($_POST['p10_2h']) ? $_POST['p10_2h'] : null;
        $p10_3h = isset($_POST['p10_3h']) ? $_POST['p10_3h'] : null;

        // Preparar la consulta para actualizar los datos en la tabla proyectos
        $sql_update_proyecto = "UPDATE proyectos 
                                SET planificacion = ?, ejecucion = ?, monitoreo = ?, p10_1s = ?, p10_2s = ?, p10_3s = ?, p10_1h = ?, p10_2h = ?, p10_3h = ?
                                WHERE id = ?";
        $stmt = $conexion->prepare($sql_update_proyecto);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // Vincular los parámetros a la consulta
        $stmt->bind_param('sssiiiiiii', $planificacion, $ejecucion, $monitoreo, $p10_1s, $p10_2s, $p10_3s, $p10_1h, $p10_2h, $p10_3h, $id_proyecto);

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
