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

// Obtener el usuario de la sesión
$usuario = $_SESSION['usuario'];

// Verificar si el formulario ha sido enviado
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Iniciar la transacción
    $conexion->begin_transaction();

    try {
        // Recuperar los valores del formulario
        $id_sede = isset($_POST['id_sede']) ? $_POST['id_sede'] : null;
        $id_depa = isset($_POST['id_depa']) ? $_POST['id_depa'] : null;

        // Preparar la consulta para actualizar los datos en la tabla usuarios
        $sql_update_usuario = "UPDATE usuarios 
                               SET id_sede = ?, id_depa = ?
                               WHERE usuario = ?";
        $stmt = $conexion->prepare($sql_update_usuario);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }

        // Vincular los parámetros a la consulta
        $stmt->bind_param('sss', $id_sede,$id_depa, $usuario);

        // Ejecutar la consulta
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el usuario: " . $stmt->error);
        }

        // Confirmar la transacción
        $conexion->commit();

        // Actualizar las variables de sesión con los nuevos datos
        $_SESSION['id_sede'] = $id_sede;
        $_SESSION['id_depa'] = $id_depa;

        // Mostrar mensaje de éxito y redirigir
        echo "<script>alert('Datos actualizados exitosamente.');</script>";
        echo "<script>location.assign('../../vistas/perfil.php');</script>"; // Redirigir a perfil.php o a la página deseada
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        echo "<script>location.assign('../../vistas/perfil.php');</script>"; // Redirigir a perfil.php o la página de perfil
    }

    // Cerrar la declaración y la conexión
    if (isset($stmt)) $stmt->close();
    $conexion->close();
}
?>
