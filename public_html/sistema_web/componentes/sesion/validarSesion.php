<?php
session_start();
// Conexión a la base de datos
include "../db.php";

// Se declaran las variables para almacenar usuario y clave recibidas por POST
$usuario = $_POST['usuario'];
$clave = $_POST['clave'];

if (empty($usuario) || empty($clave)) {
    // Redirige con un error si alguno de los campos está vacío
    header("Location: ../../login.php?error=1");
    exit();
}

try {
    // Consulta a la base de datos para obtener los datos del usuario
    $stmt = $conexion->prepare("SELECT 
        id,
        usuario, 
        clave, 
        id_rol, 
        nombres, 
        apellidos,
        id_escuela,
        id_py,
        id_sede,
        id_depa
    FROM usuarios
    WHERE usuario = ?");

    // Verificar si la consulta se preparó correctamente
    if ($stmt === false) {
        throw new Exception("Error en la preparación de la consulta");
    }

    // Vincula el parámetro de entrada
    $stmt->bind_param("s", $usuario);
    
    // Ejecutar la consulta
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontraron resultados
    
    if ($result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $hash_clave = $row['clave'];
    
    // Verifica la contraseña usando password_verify()
    if (password_verify($clave, $hash_clave)) {
        $usuarioActivo = true;
        $idUsuario = (int)$row['id'];

        $stEstado = $conexion->prepare("SELECT descripcion
                                          FROM historial_usuarios
                                         WHERE id_usuario = ?
                                           AND descripcion LIKE 'Estado de usuario:%'
                                         ORDER BY id DESC
                                         LIMIT 1");
        if ($stEstado) {
            $stEstado->bind_param("i", $idUsuario);
            $stEstado->execute();
            $rsEstado = $stEstado->get_result();
            if ($rsEstado && $rsEstado->num_rows > 0) {
                $estado = $rsEstado->fetch_assoc();
                if (isset($estado['descripcion']) && stripos((string)$estado['descripcion'], 'desactivado') !== false) {
                    $usuarioActivo = false;
                }
            }
            $stEstado->close();
        }

        if (!$usuarioActivo) {
            header("Location: ../../login.php?error=5");
            exit();
        }

        // Almacenar datos del usuario en la sesión
        $_SESSION['usuario'] = $usuario;
        $_SESSION['id_rol'] = $row['id_rol'];
        $_SESSION['nombres'] = $row['nombres'];
        $_SESSION['apellidos'] = $row['apellidos'];
        $_SESSION['id_escuela'] = $row['id_escuela'];
        $_SESSION['id_py'] = $row['id_py'];
        $_SESSION['id_sede'] = $row['id_sede'];
        $_SESSION['id_depa'] = $row['id_depa'];

        // Verificar el rol del usuario
        if ($row['id_rol'] == 1) {
            // Si el rol es 1, redirigir a consola.php
            header("Location: ../../direccion_rsu/inicio.php");
            exit();
        } elseif ($row['id_rol'] == 2) {
            // Si el rol es 2, redirigir a inicio.php
            header("Location: ../../inicio.php");
            exit();
        } elseif ($row['id_rol'] == 3) {
            // Si el rol es 2, redirigir a inicio.php
            header("Location: ../../decanato_facultad/inicio.php");
            exit();
        } elseif ($row['id_rol'] == 4) {
            // Si el rol es 2, redirigir a inicio.php
            header("Location: ../../director_departamento/inicio.php");
            exit();
        } elseif ($row['id_rol'] == 5) {
            // Si el rol es 2, redirigir a inicio.php
            header("Location: ../../comite_facultad/inicio.php");
            exit();
        }else {
            // Manejo de otros roles (opcional)
            header("Location: ../../login.php?error=2");
            exit();
        }
    } else {
        // Contraseña incorrecta
        header("Location: ../../login.php?error=1");
        exit();
    }
} else {
    // Usuario no encontrado
    header("Location: ../../login.php?error=1");
    exit();
}

    
} catch (Exception $e) {
    // Manejo de errores: muestra el mensaje o redirige a una página de error
    error_log($e->getMessage());
    header("Location: ../../login.php?error=2"); // Error general
    exit();
} finally {
    // Cerrar la conexión
    $stmt->close();
    $conexion->close();
}
?>
