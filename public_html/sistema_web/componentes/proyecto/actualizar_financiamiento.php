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
        $resumen = isset($_POST['resumen']) ? $_POST['resumen'] : null;
        
        // Obtener los montos y reemplazar la coma por un punto
        $monto_uni = isset($_POST['monto_uni']) ? str_replace(',', '.', $_POST['monto_uni']) : null;
        $monto_auto = isset($_POST['monto_auto']) ? str_replace(',', '.', $_POST['monto_auto']) : null;
        $monto_otro = isset($_POST['monto_otro']) ? str_replace(',', '.', $_POST['monto_otro']) : null;

        // Convertir los montos a float
        $monto_uni = floatval($monto_uni);
        $monto_auto = floatval($monto_auto);
        $monto_otro = floatval($monto_otro);

        // Preparar la consulta para actualizar los datos en la tabla proyectos
        $sql_update_proyecto = "UPDATE proyectos 
                                SET resumen = ?, monto_uni = ?, monto_auto = ?, monto_otro = ?
                                WHERE id = ?";
        $stmt = $conexion->prepare($sql_update_proyecto);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // Vincular los parámetros a la consulta
        $stmt->bind_param('sdddi', $resumen, $monto_uni, $monto_auto, $monto_otro, $id_proyecto);

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el proyecto: " . $stmt->error);
        }

        // Confirmar la transacción
        $conexion->commit();

        echo "<script>alert('Proyecto actualizado exitosamente.');</script>";
        echo "<script>location.assign('../../vistas/desarrollo_informe.php');</script>";
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        echo "<script>location.assign('../../vistas/desarrollo_informe.php');</script>";
    }

    // Cerrar la declaración y la conexión
    if (isset($stmt)) $stmt->close();
    $conexion->close();
}
?>
