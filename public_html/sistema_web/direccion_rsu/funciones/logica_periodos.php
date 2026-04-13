<?php
/*-------------------------------------------------------------------------
 |  LOGICA AJAX: Gestion de periodos
 |  Ruta  : direccion_rsu/funciones/logica_periodos.php
 *------------------------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

require_once '../../componentes/db.php';
mysqli_set_charset($conexion, 'utf8mb4');

$action = isset($_POST['action']) ? $_POST['action'] : '';

function gp_json_exit($payload)
{
    echo json_encode($payload);
    exit;
}

function gp_clean_text($value, $maxLen)
{
    $value = trim((string)$value);
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $maxLen, 'UTF-8');
    }
    return substr($value, 0, $maxLen);
}

try {
    if ($action === 'list') {
        $sql = "SELECT id, nombre,
                       DATE_FORMAT(fecha_inicio, '%Y-%m-%d') AS fecha_inicio,
                       DATE_FORMAT(fecha_fin, '%Y-%m-%d') AS fecha_fin,
                       activo
                FROM periodos
                ORDER BY fecha_inicio DESC, id DESC";
        $res = mysqli_query($conexion, $sql);
        if ($res === false) {
            throw new Exception('No se pudo listar periodos.');
        }
        gp_json_exit(array(
            'success' => true,
            'data' => mysqli_fetch_all($res, MYSQLI_ASSOC)
        ));
    }

    if ($action === 'create') {
        $nombre = gp_clean_text(isset($_POST['nombre']) ? $_POST['nombre'] : '', 120);
        $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
        $activo = (isset($_POST['activo']) && (int)$_POST['activo'] === 1) ? 1 : 0;

        if ($nombre === '') {
            throw new Exception('El nombre del periodo es obligatorio.');
        }
        if ($fecha_inicio === '' || $fecha_fin === '') {
            throw new Exception('Debe ingresar fecha de inicio y fecha de fin.');
        }
        if ($fecha_inicio > $fecha_fin) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha de fin.');
        }

        $stmt = mysqli_prepare($conexion, "INSERT INTO periodos (nombre, fecha_inicio, fecha_fin, activo) VALUES (?, ?, ?, ?)");
        mysqli_stmt_bind_param($stmt, 'sssi', $nombre, $fecha_inicio, $fecha_fin, $activo);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('No se pudo crear el periodo.');
        }

        gp_json_exit(array(
            'success' => true,
            'id' => mysqli_insert_id($conexion)
        ));
    }

    if ($action === 'update') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        $nombre = gp_clean_text(isset($_POST['nombre']) ? $_POST['nombre'] : '', 120);
        $fecha_inicio = isset($_POST['fecha_inicio']) ? trim($_POST['fecha_inicio']) : '';
        $fecha_fin = isset($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : '';
        $activo = (isset($_POST['activo']) && (int)$_POST['activo'] === 1) ? 1 : 0;

        if ($id <= 0) {
            throw new Exception('ID de periodo invalido.');
        }
        if ($nombre === '') {
            throw new Exception('El nombre del periodo es obligatorio.');
        }
        if ($fecha_inicio === '' || $fecha_fin === '') {
            throw new Exception('Debe ingresar fecha de inicio y fecha de fin.');
        }
        if ($fecha_inicio > $fecha_fin) {
            throw new Exception('La fecha de inicio no puede ser mayor que la fecha de fin.');
        }

        $stmt = mysqli_prepare($conexion, "UPDATE periodos SET nombre = ?, fecha_inicio = ?, fecha_fin = ?, activo = ? WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'sssii', $nombre, $fecha_inicio, $fecha_fin, $activo, $id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('No se pudo actualizar el periodo.');
        }

        gp_json_exit(array('success' => true));
    }

    if ($action === 'delete') {
        $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
        if ($id <= 0) {
            throw new Exception('ID de periodo invalido.');
        }

        $stmt = mysqli_prepare($conexion, "DELETE FROM periodos WHERE id = ?");
        mysqli_stmt_bind_param($stmt, 'i', $id);
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception('No se pudo eliminar el periodo. Verifique si tiene cronogramas relacionados.');
        }

        gp_json_exit(array('success' => true));
    }

    throw new Exception('Accion no valida.');
} catch (Exception $e) {
    gp_json_exit(array(
        'success' => false,
        'msg' => $e->getMessage()
    ));
}
