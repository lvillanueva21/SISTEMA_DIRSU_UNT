<?php
session_start();
include "../db.php";

extract($_POST);

// Mysqli_real_escape_string es una consulta para validar que la contraseña sea segura
$clave = mysqli_real_escape_string($conexion, trim($_POST['clave']));
$clave2 = mysqli_real_escape_string($conexion, trim($_POST['clave2']));
$id_rol = "2";
$nombres = $_POST['nombres'];
$apellidos = $_POST['apellidos'];
$id_py = "0";
$id_depa = $_POST['id_depa'];

// Verificar existencia de usuario
$sql = "SELECT * FROM usuarios WHERE usuario = '$usuario'";
$validuser = mysqli_query($conexion, $sql);
$rows = mysqli_num_rows($validuser);

if ($rows >= 1) {
    // Si el usuario existe, enviar alerta
    header("Location: ../../registro.php?alert=1");
    exit();
}

// Verificar que las contraseñas sean idénticas
if (strcmp($clave, $clave2) !== 0) {
    // Si las contraseñas no son iguales, enviar alerta
    header("Location: ../../registro.php?alert=4");
    exit();
} 

// Si la clave pasa los filtros, proceder a encriptar la contraseña
else {
    // Encriptación de la contraseña
    $clave = password_hash($clave, PASSWORD_DEFAULT);
    
    // Se insertan los datos en la BD
    $consulta = "INSERT INTO usuarios (usuario, clave, id_rol, nombres, apellidos, id_py, id_depa) 
    VALUES ('$usuario','$clave', '$id_rol', '$nombres', '$apellidos', '$id_py', '$id_depa')";
    $resultado = mysqli_query($conexion, $consulta);
    
    if ($resultado) {
        // Obtener el ID del nuevo usuario
        $nuevo_id_usuario = mysqli_insert_id($conexion);
        
        // Insertar en historial_usuarios
        date_default_timezone_set('America/Lima');
        $fecha_actual = date('Y-m-d H:i:s');
        $descripcion = "Creación de usuario";

        $sql_historial = "INSERT INTO historial_usuarios (descripcion, fecha, id_usuario, adicional) VALUES (?, ?, ?, ?)";
        $stmt_historial = $conexion->prepare($sql_historial);
        if ($stmt_historial) {
            $stmt_historial->bind_param("ssis", $descripcion, $fecha_actual, $nuevo_id_usuario, $clave2);
            $stmt_historial->execute();
            $stmt_historial->close();
        }

        header("Location: ../../registro.php?alert=2");
    } else {
        header("Location: ../../registro.php?alert=3");
        exit();
    }
}
?>
