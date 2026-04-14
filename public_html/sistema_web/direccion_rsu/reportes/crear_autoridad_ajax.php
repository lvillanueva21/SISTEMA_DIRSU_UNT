<?php
include "../../componentes/configSesion.php";
include "../../componentes/db.php";

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');
mysqli_set_charset($conexion, 'utf8mb4');

function json_exit($data)
{
    echo json_encode($data);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_exit(array('ok' => false, 'msg' => 'Metodo no permitido.'));
}

$nombres = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
$apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
$usuario = isset($_POST['usuario']) ? trim($_POST['usuario']) : '';
$id_sede = isset($_POST['id_sede']) ? (int)$_POST['id_sede'] : 0;
$id_rol = isset($_POST['id_rol']) ? (int)$_POST['id_rol'] : 0;
$id_escuela = isset($_POST['id_escuela']) ? (int)$_POST['id_escuela'] : 0;
$id_depa = isset($_POST['id_depa']) ? (int)$_POST['id_depa'] : 0;
$clave = isset($_POST['clave']) ? trim($_POST['clave']) : '';
$clave2 = isset($_POST['clave2']) ? trim($_POST['clave2']) : '';

if ($nombres === '' || $apellidos === '' || $usuario === '' || $clave === '' || $clave2 === '') {
    json_exit(array('ok' => false, 'msg' => 'Complete todos los campos obligatorios.'));
}

if (!in_array($id_sede, array(1, 2, 3, 4), true)) {
    json_exit(array('ok' => false, 'msg' => 'Sede invalida.'));
}

if (!in_array($id_rol, array(1, 2, 3, 4, 5), true)) {
    json_exit(array('ok' => false, 'msg' => 'Tipo de usuario invalido.'));
}

if ($id_rol === 2) {
    if (!preg_match('/^\d{4}$/', $usuario)) {
        json_exit(array('ok' => false, 'msg' => 'Para coordinador, el usuario debe tener exactamente 4 digitos numericos.'));
    }
} elseif ($id_rol === 1) {
    if (!preg_match('/^\d{8}$/', $usuario)) {
        json_exit(array('ok' => false, 'msg' => 'Para Direccion RSU, el usuario debe tener exactamente 8 digitos numericos.'));
    }
} elseif (in_array($id_rol, array(3, 4, 5), true)) {
    if (!preg_match('/^\d{5}$/', $usuario)) {
        json_exit(array('ok' => false, 'msg' => 'Para este rol, el usuario debe tener exactamente 5 digitos numericos.'));
    }
}

if ($clave !== $clave2) {
    json_exit(array('ok' => false, 'msg' => 'Las contrasenas ingresadas no coinciden.'));
}

if ($id_rol === 3 || $id_rol === 5) {
    if ($id_escuela <= 0) {
        json_exit(array('ok' => false, 'msg' => 'Debe seleccionar una facultad para el rol elegido.'));
    }
    $id_depa = 0;
} elseif ($id_rol === 4 || $id_rol === 2) {
    if ($id_depa <= 0) {
        if ($id_rol === 2) {
            json_exit(array('ok' => false, 'msg' => 'Debe seleccionar un departamento para el coordinador.'));
        }
        json_exit(array('ok' => false, 'msg' => 'Debe seleccionar un departamento para el rol elegido.'));
    }
    $id_escuela = 0;
} else {
    $id_escuela = 0;
    $id_depa = 0;
}

$sqlExiste = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
$stExiste = mysqli_prepare($conexion, $sqlExiste);
mysqli_stmt_bind_param($stExiste, 's', $usuario);
mysqli_stmt_execute($stExiste);
$rsExiste = mysqli_stmt_get_result($stExiste);
if ($rsExiste && mysqli_fetch_assoc($rsExiste)) {
    json_exit(array('ok' => false, 'msg' => 'El usuario ingresado ya existe.'));
}

$claveHash = password_hash($clave, PASSWORD_DEFAULT);
$fechaActual = date('Y-m-d H:i:s');

$descripcion = 'Creacion de autoridad';
if ($id_rol === 1) {
    $descripcion = 'Creacion de autoridad tipo Director de DIRSU';
} elseif ($id_rol === 3) {
    $descripcion = 'Creacion de autoridad tipo Decano de la Facultad';
} elseif ($id_rol === 4) {
    $descripcion = 'Creacion de autoridad tipo Director de Departamento';
} elseif ($id_rol === 5) {
    $descripcion = 'Creacion de autoridad tipo Presidente de Comit? de RS de Facultad';
} elseif ($id_rol === 2) {
    $descripcion = 'Creacion de usuario tipo Coordinador de Proyecto';
}

mysqli_begin_transaction($conexion);

try {
    $sqlInsert = "INSERT INTO usuarios (usuario, clave, id_rol, nombres, apellidos, id_py, id_sede, id_escuela, id_depa)
                  VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stInsert = mysqli_prepare($conexion, $sqlInsert);
    $id_py = 0;
    mysqli_stmt_bind_param(
        $stInsert,
        'ssissiiii',
        $usuario,
        $claveHash,
        $id_rol,
        $nombres,
        $apellidos,
        $id_py,
        $id_sede,
        $id_escuela,
        $id_depa
    );

    if (!mysqli_stmt_execute($stInsert)) {
        throw new Exception('No se pudo registrar el usuario.');
    }

    $nuevoId = mysqli_insert_id($conexion);

    $sqlHist = "INSERT INTO historial_usuarios (descripcion, fecha, id_usuario, adicional) VALUES (?, ?, ?, ?)";
    $stHist = mysqli_prepare($conexion, $sqlHist);
    mysqli_stmt_bind_param($stHist, 'ssis', $descripcion, $fechaActual, $nuevoId, $clave2);
    if (!mysqli_stmt_execute($stHist)) {
        throw new Exception('No se pudo registrar el historial del usuario.');
    }

    $sqlData = "SELECT u.id, u.usuario, u.nombres, u.apellidos, u.id_rol, u.id_depa, u.id_py,
                       d.nombre AS dep,
                       f.nombre AS fac
                FROM usuarios u
                LEFT JOIN departamentos d ON d.id = u.id_depa
                LEFT JOIN facultades f ON (
                     (u.id_rol IN (3,5) AND f.id=u.id_escuela)
                  OR (u.id_rol IN (2,4) AND f.id=d.id_facultad)
                )
                WHERE u.id = ?
                LIMIT 1";
    $stData = mysqli_prepare($conexion, $sqlData);
    mysqli_stmt_bind_param($stData, 'i', $nuevoId);
    mysqli_stmt_execute($stData);
    $rsData = mysqli_stmt_get_result($stData);
    $data = $rsData ? mysqli_fetch_assoc($rsData) : null;

    if (!$data) {
        $data = array(
            'id' => $nuevoId,
            'usuario' => $usuario,
            'nombres' => $nombres,
            'apellidos' => $apellidos,
            'id_rol' => $id_rol,
            'id_py' => 0,
            'id_depa' => $id_depa,
            'dep' => '',
            'fac' => ''
        );
    }

    $data['clave_visible'] = $clave2;

    mysqli_commit($conexion);

    json_exit(array(
        'ok' => true,
        'msg' => 'Usuario creado correctamente.',
        'data' => $data
    ));
} catch (Exception $e) {
    mysqli_rollback($conexion);
    json_exit(array('ok' => false, 'msg' => $e->getMessage()));
}

