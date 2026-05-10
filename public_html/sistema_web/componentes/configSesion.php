<?php
require_once __DIR__ . '/../includes/rsu_diag.php';
rsu_diag_context('entry_point', 'componentes/configSesion.php');
rsu_diag_context('evt_file_exists', file_exists(__DIR__ . '/../includes/evt_mantenimiento.php') ? 'yes' : 'no');
rsu_diag_context('db_connection_file_exists', file_exists(__DIR__ . '/../includes/db_connection.php') ? 'yes' : 'no');

if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}
ini_set('default_charset', 'UTF-8');
if (function_exists('mb_internal_encoding')) {
    mb_internal_encoding('UTF-8');
}

error_reporting(0);
session_start();

require_once __DIR__ . '/../includes/evt_mantenimiento.php';

function rsu_redirect_login_rel()
{
    $loginRel = evt_mto_get_login_relative_path();
    if (!headers_sent()) {
        header('Location: ' . $loginRel);
    } else {
        echo "<script>location.assign(" . json_encode($loginRel) . ");</script>";
    }
    exit();
}

$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$usuario = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : null;

$nombres = isset($_SESSION['nombres']) ? $_SESSION['nombres'] : '';
$apellidos = isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '';
$id_escuela = isset($_SESSION['id_escuela']) ? (int)$_SESSION['id_escuela'] : 0;
$id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
$id_sede = isset($_SESSION['id_sede']) ? (int)$_SESSION['id_sede'] : 0;
$id_depa = isset($_SESSION['id_depa']) ? (int)$_SESSION['id_depa'] : 0;

$_SESSION['facultad_de_depa'] = '';
$_SESSION['nombre_depa'] = '';
$_SESSION['facultad_aut'] = '';

if (!isset($_SESSION['usuario']) || $usuario === null || $usuario === '') {
    rsu_redirect_login_rel();
}

// Validacion global de mantenimiento para sesiones internas.
$evtMtoState = evt_mto_fetch_state();
if ((int)$evtMtoState['sistema_activo'] === 0) {
    $isDireccionRsu = ($id_rol === 1);
    $hasBypass = evt_mto_has_bypass_session();

    if (!$isDireccionRsu && !$hasBypass) {
        // Se corta la sesion ya iniciada para roles no permitidos.
        session_unset();
        session_destroy();
        rsu_redirect_login_rel();
    }
}

if (function_exists('evt_mto_db_connect')) {
    $conexion = evt_mto_db_connect();
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    echo "No se pudo conectar con el sistema en este momento.";
    exit();
}

$facultad_de_depa = '';
$nombre_depa = '';
$facultad_aut = '';

$query = "SELECT id_facultad, nombre FROM departamentos WHERE id = ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "i", $id_depa);
mysqli_stmt_execute($stmt);
mysqli_stmt_store_result($stmt);
mysqli_stmt_bind_result($stmt, $id_facultad, $nombre_departamento);

if (mysqli_stmt_fetch($stmt)) {
    $nombre_depa = $nombre_departamento;

    $query_facultad = "SELECT nombre FROM facultades WHERE id = ? ORDER BY nombre ASC";
    $stmt_facultad = mysqli_prepare($conexion, $query_facultad);
    mysqli_stmt_bind_param($stmt_facultad, "i", $id_facultad);
    mysqli_stmt_execute($stmt_facultad);
    mysqli_stmt_store_result($stmt_facultad);
    mysqli_stmt_bind_result($stmt_facultad, $nombre_facultad);

    if (mysqli_stmt_fetch($stmt_facultad)) {
        $facultad_de_depa = $nombre_facultad;
    }
}

$query_facultad_aut = "SELECT nombre FROM facultades WHERE id = ? ORDER BY nombre ASC";
$stmt_facultad_aut = mysqli_prepare($conexion, $query_facultad_aut);
mysqli_stmt_bind_param($stmt_facultad_aut, "i", $id_escuela);
mysqli_stmt_execute($stmt_facultad_aut);
mysqli_stmt_store_result($stmt_facultad_aut);
mysqli_stmt_bind_result($stmt_facultad_aut, $nombre_facultad_aut);

if (mysqli_stmt_fetch($stmt_facultad_aut)) {
    $_SESSION['facultad_aut'] = $nombre_facultad_aut;
}

mysqli_stmt_close($stmt);
if (isset($stmt_facultad) && $stmt_facultad instanceof mysqli_stmt) {
    mysqli_stmt_close($stmt_facultad);
}
mysqli_stmt_close($stmt_facultad_aut);

$_SESSION['facultad_de_depa'] = $facultad_de_depa;
$_SESSION['nombre_depa'] = $nombre_depa;
?>
