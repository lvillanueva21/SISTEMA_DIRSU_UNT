<?php 
error_reporting(0);
session_start();

// Declaración de las variables de sesión
$id_rol = $_SESSION['id_rol'];
$usuario = $_SESSION['usuario'];

// Obligatorio para mostrar información de usuarios, si no, no aparecen
$nombres = $_SESSION['nombres'];
$apellidos = $_SESSION['apellidos'];
$id_escuela = $_SESSION['id_escuela'];
$id_py = $_SESSION['id_py'];
$id_sede = $_SESSION['id_sede'];
$id_depa = $_SESSION['id_depa'];

// Declarar las nuevas variables de sesión
$_SESSION['facultad_de_depa'] = ''; // Inicializamos la variable de sesión para evitar errores
$_SESSION['nombre_depa'] = ''; // Inicializamos la nueva variable de sesión

// Variable que sirve para obtener facultad desde una id_escuela
$_SESSION['facultad_aut'] = ''; // Facultdad a partir de id_escuela de formulario crear_autoridad

if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Primero debes iniciar sesión y validar tus credenciales ...');</script>";
    echo "<script>location.assign('https://rsu.unitru.edu.pe/sistema_web/login.php');</script>";
    exit();
}

// Conexión a la base de datos
$direccionservidor = "localhost";
$baseDatos = "rsudb";
$usuarioBD = "au_rsu";
$contraseniaBD = "_BrHJMGO3U3(9v.c";

$conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
if (!$conexion) {
    echo "No se realizó la conexión a la base de datos, el error fue: " . mysqli_connect_error();
    exit();
}

// Obtener el nombre de la facultad del departamento
$facultad_de_depa = '';  // Inicializamos la variable para evitar errores si no se encuentra nada
$nombre_depa = '';  // Inicializamos la variable para evitar errores si no se encuentra nada

$facultad_aut = '';  // Inicializamos la variable para evitar errores si no se encuentra nada

// 1. Consultar la tabla 'departamentos' con el 'id_depa' de la sesión
$query = "SELECT id_facultad, nombre FROM departamentos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_depa);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
mysqli_stmt_bind_result($stmt, $id_facultad, $nombre_departamento);

// Si existe el departamento, obtener el 'id_facultad' y el 'nombre' del departamento
if (mysqli_stmt_fetch($stmt)) {
    // Asignar el nombre del departamento a la variable de sesión
    $nombre_depa = $nombre_departamento;

    // 2. Consultar la tabla 'facultades' con el 'id_facultad'
    $query_facultad = "SELECT nombre FROM facultades WHERE id = ? ORDER BY nombre ASC";
    $stmt_facultad = mysqli_prepare($conexion, $query_facultad);
    mysqli_stmt_bind_param($stmt_facultad, "i", $id_facultad);
    mysqli_stmt_execute($stmt_facultad);
    mysqli_stmt_store_result($stmt_facultad);
    mysqli_stmt_bind_result($stmt_facultad, $nombre_facultad);

    // Si existe la facultad, asignar el nombre a la variable
    if (mysqli_stmt_fetch($stmt_facultad)) {
        $facultad_de_depa = $nombre_facultad;
    }
}

// 3. Consultar la tabla 'facultades' con el 'id_escuela' de la sesión
$query_facultad_aut = "SELECT nombre FROM facultades WHERE id = ? ORDER BY nombre ASC";
$stmt_facultad_aut = mysqli_prepare($conexion, $query_facultad_aut);
mysqli_stmt_bind_param($stmt_facultad_aut, "i", $id_escuela);
mysqli_stmt_execute($stmt_facultad_aut);
mysqli_stmt_store_result($stmt_facultad_aut);
mysqli_stmt_bind_result($stmt_facultad_aut, $nombre_facultad_aut);

// Si existe la facultad, asignar el nombre a la variable de sesión 'facultad_aut'
if (mysqli_stmt_fetch($stmt_facultad_aut)) {
    $_SESSION['facultad_aut'] = $nombre_facultad_aut;
}

// Cerrar las consultas preparadas
mysqli_stmt_close($stmt);
mysqli_stmt_close($stmt_facultad);
mysqli_stmt_close($stmt_facultad_aut);

// Cerrar la conexión a la base de datos
mysqli_close($conexion);

// Asignar los valores a las variables de sesión
$_SESSION['facultad_de_depa'] = $facultad_de_depa;
$_SESSION['nombre_depa'] = $nombre_depa;

// Ahora puedes usar $_SESSION['facultad_de_depa'], $_SESSION['nombre_depa'], y $_SESSION['facultad_aut'] en otras partes del código
?>
