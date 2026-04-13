<?php
session_start();
include "../../componentes/db.php";

extract($_POST);

// Mysqli_real_escape_string es una consulta para validar que los campos sean seguros
$clave = mysqli_real_escape_string($conexion, trim($_POST['clave']));
$clave2 = mysqli_real_escape_string($conexion, trim($_POST['clave2']));
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$usuario = $_POST['usuario'];  // Este es el campo "Código o DNI"
$id_sede = $_POST['id_sede'];
$id_rol = $_POST['id_rol'];

$id_escuela = $_POST['id_escuela'];
$id_depa = $_POST['id_depa'];

// Verificar existencia de usuario (código o DNI)
$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
$validuser = mysqli_query($conexion, $sql);
$rows = mysqli_num_rows($validuser);

if ($rows >= 1) {
    // Si el usuario existe, enviar alerta
    header("Location: ../panel.php?alert=1");
    exit();
}

// Verificar que las contraseñas sean idénticas
if (strcmp($clave, $clave2) !== 0) {
    // Si las contraseñas no son iguales, enviar alerta
    header("Location: ../panel.php?alert=4");
    exit();
} 

// Si las contraseñas son iguales, proceder a encriptar la contraseña
else {
    // Encriptación de la contraseña
    $clave = password_hash($clave, PASSWORD_DEFAULT);
    
    // Se insertan los datos en la BD
    $consulta = "INSERT INTO usuarios (usuario, clave, id_rol, nombres, apellidos, id_sede, id_escuela, id_depa) 
    VALUES ('$usuario', '$clave', '$id_rol', '$nombres', '$apellidos', '$id_sede', '$id_escuela', '$id_depa')";
    $resultado = mysqli_query($conexion, $consulta);
    
    if ($resultado) {
        // Obtener el ID del nuevo usuario
        $nuevo_id_usuario = mysqli_insert_id($conexion);
        
        // Insertar en historial_usuarios
        date_default_timezone_set('America/Lima');
        $fecha_actual = date('Y-m-d H:i:s');
        
        // Asignar un mensaje dependiendo del valor de id_rol
        switch ($id_rol) {
            case 1:
                $descripcion = "Creación de autoridad tipo Director de DIRSU";
                break;
            case 3:
                $descripcion = "Creación de autoridad tipo Decano de la Facultad";
                break;
            case 4:
                $descripcion = "Creación de autoridad tipo Director de Departamento";
                break;
            case 5:
                $descripcion = "Creación de autoridad tipo Presidente de Comité de RS de Facultad";
                break;
            default:
                $descripcion = "Creación de autoridad - Rol ID desconocido";
                break;
        }

        // Insertar en historial_usuarios con la descripción generada
        $sql_historial = "INSERT INTO historial_usuarios (descripcion, fecha, id_usuario, adicional) VALUES (?, ?, ?, ?)";
        $stmt_historial = $conexion->prepare($sql_historial);
        if ($stmt_historial) {
            $stmt_historial->bind_param("ssis", $descripcion, $fecha_actual, $nuevo_id_usuario, $clave2);
            $stmt_historial->execute();
            $stmt_historial->close();
        }

        // Redirigir con mensaje de éxito
        header("Location: ../panel.php?alert=2");
    } else {
        // En caso de error al insertar en la BD
        header("Location: ../panel.php?alert=3");
        exit();
    }
}
?>