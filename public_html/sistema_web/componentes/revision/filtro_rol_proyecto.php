<?php
require_once("../componentes/db.php");
session_start();

/**
 * Obtiene el ID de la facultad que puede ver el usuario logueado.
 * Devuelve NULL si tiene acceso global o si no aplica.
 */
function getFacultadUsuario($conexion) {
    $rol = $_SESSION['id_rol'];
    $id_depa = $_SESSION['id_depa'];
    $id_escuela = $_SESSION['id_escuela'];

    if ($rol == 1) return null; // RSU ve todo
    if (in_array($rol, [3, 5])) return $id_escuela;

    if (in_array($rol, [2, 4])) {
        $query = mysqli_query($conexion, "SELECT id_facultad FROM departamentos WHERE id = $id_depa LIMIT 1");
        if ($row = mysqli_fetch_assoc($query)) {
            return $row['id_facultad'];
        }
    }

    return null;
}

/**
 * Devuelve el ID del departamento que puede ver el usuario.
 * Aplica para Dirección de Departamento y Coordinadores.
 */
function getDepartamentoUsuario() {
    $rol = $_SESSION['id_rol'];
    $id_depa = $_SESSION['id_depa'];

    return in_array($rol, [2, 4]) ? $id_depa : null;
}

/**
 * Devuelve los IDs de proyectos visibles según el rol y período activo.
 */
function getProyectosVisibles($conexion, $id_periodo = null) {
    $rol = (int)$_SESSION['id_rol'];
    $id_usuario = (int)$_SESSION['id'];
    $id_depa = (int)$_SESSION['id_depa'];
    $id_escuela = (int)$_SESSION['id_escuela'];

    // Obtener ID del período activo si no se pasa uno
    if (is_null($id_periodo)) {
        $periodo_activo = mysqli_query($conexion, "SELECT id FROM periodos WHERE activo = 1 LIMIT 1");
        if ($row = mysqli_fetch_assoc($periodo_activo)) {
            $id_periodo = (int)$row['id'];
        } else {
            return [];
        }
    }

    // Construcción de query
    $where = "pp.id_periodo = $id_periodo";

    if ($rol == 1) { 
        // RSU - ve TODO por período
        $sql = "
            SELECT p.id
            FROM proyectos p
            INNER JOIN proyectos_periodo pp ON pp.id_py = p.id
            WHERE $where
        ";
    } elseif (in_array($rol, [3, 5])) { 
        // Decanato / Comité - filtra por facultad
        $sql = "
            SELECT p.id
            FROM proyectos p
            INNER JOIN usuarios_proyectos up ON up.id_proyecto = p.id
            INNER JOIN usuarios u ON u.id = up.id_usuario
            INNER JOIN departamentos d ON u.id_depa = d.id
            INNER JOIN proyectos_periodo pp ON pp.id_py = p.id
            WHERE d.id_facultad = $id_escuela
              AND up.activo = 1
              AND $where
        ";
    } elseif ($rol == 4) {
        // Dirección de Departamento - filtra por departamento
        $sql = "
            SELECT p.id
            FROM proyectos p
            INNER JOIN usuarios_proyectos up ON up.id_proyecto = p.id
            INNER JOIN usuarios u ON u.id = up.id_usuario
            INNER JOIN proyectos_periodo pp ON pp.id_py = p.id
            WHERE u.id_depa = $id_depa
              AND up.activo = 1
              AND $where
        ";
    } elseif ($rol == 2) { 
        // Coordinador de Proyecto - ve solo sus proyectos
        $sql = "
            SELECT p.id
            FROM proyectos p
            INNER JOIN usuarios_proyectos up ON up.id_proyecto = p.id
            INNER JOIN proyectos_periodo pp ON pp.id_py = p.id
            WHERE up.id_usuario = $id_usuario
              AND up.activo = 1
              AND $where
        ";
    } else {
        return [];
    }

    $result = mysqli_query($conexion, $sql);
    $proyectos = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $proyectos[] = $row['id'];
    }
    return $proyectos;
}

function getFiltrosPermitidos() {
    $rol = $_SESSION['id_rol'];

    switch ($rol) {
        case 5: // Comité de Facultad
            return ['periodo', 'estado', 'departamento', 'texto'];
        case 4: // Dirección de Departamento
            return ['periodo', 'estado', 'texto'];
        case 3: // Decanato de Facultad
            return ['periodo', 'estado', 'departamento', 'texto'];
        case 1: // RSU
            return ['periodo', 'estado', 'facultad', 'departamento', 'texto'];
        default:
            return ['periodo']; // Rol desconocido o coordinador
    }
}
