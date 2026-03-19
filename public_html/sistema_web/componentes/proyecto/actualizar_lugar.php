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
        $sector = isset($_POST['sector']) ? $_POST['sector'] : null;
        $caserio = isset($_POST['caserio']) ? $_POST['caserio'] : null;
        $distritoId = isset($_POST['distrito']) ? $_POST['distrito'] : null;
        $provinciaId = isset($_POST['provincia']) ? $_POST['provincia'] : null;
        $departamentoId = isset($_POST['departamento']) ? $_POST['departamento'] : null;
        
        $fecha_inicio = isset($_POST['fecha_inicio']) ? $_POST['fecha_inicio'] : null;
        $fecha_fin = isset($_POST['fecha_fin']) ? $_POST['fecha_fin'] : null;

        // Obtener los nombres de los distrito, provincia y departamento por sus IDs
        $distrito = '';
        $provincia = '';
        $departamento = '';

        if ($distritoId) {
            $query_distrito = "SELECT name FROM ubigeo_peru_districts WHERE id = ?";
            $stmt_distrito = $conexion->prepare($query_distrito);
            $stmt_distrito->bind_param('i', $distritoId);
            $stmt_distrito->execute();
            $stmt_distrito->bind_result($distrito);
            $stmt_distrito->fetch();
            $stmt_distrito->close();
        }

        if ($provinciaId) {
            $query_provincia = "SELECT name FROM ubigeo_peru_provinces WHERE id = ?";
            $stmt_provincia = $conexion->prepare($query_provincia);
            $stmt_provincia->bind_param('i', $provinciaId);
            $stmt_provincia->execute();
            $stmt_provincia->bind_result($provincia);
            $stmt_provincia->fetch();
            $stmt_provincia->close();
        }

        if ($departamentoId) {
            $query_departamento = "SELECT name FROM ubigeo_peru_departments WHERE id = ?";
            $stmt_departamento = $conexion->prepare($query_departamento);
            $stmt_departamento->bind_param('i', $departamentoId);
            $stmt_departamento->execute();
            $stmt_departamento->bind_result($departamento);
            $stmt_departamento->fetch();
            $stmt_departamento->close();
        }

        // Preparar la consulta para actualizar los datos en la tabla proyectos
        $sql_update_proyecto = "UPDATE proyectos 
                                SET sector = ?, caserio = ?, distrito = ?, provincia = ?, departamento = ?, fecha_inicio = ?, fecha_fin = ? 
                                WHERE id = ?";
        $stmt = $conexion->prepare($sql_update_proyecto);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // Vincular los parámetros a la consulta
        $stmt->bind_param('sssssssi', $sector, $caserio, $distrito, $provincia, $departamento, $fecha_inicio, $fecha_fin, $id_proyecto);

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
