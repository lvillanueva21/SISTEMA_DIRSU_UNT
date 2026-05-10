<?php
session_start();

require_once "../../includes/evt_mantenimiento.php";

$conexion = evt_mto_db_connect();
if (!($conexion instanceof mysqli)) {
    header("Location: ../../login.php?error=2");
    exit();
}

$usuario = isset($_POST['usuario']) ? trim((string)$_POST['usuario']) : '';
$clave = isset($_POST['clave']) ? (string)$_POST['clave'] : '';

// Bloqueo real del login para evitar bypass por POST directo.
$evtMtoState = evt_mto_fetch_state();
if ((int)$evtMtoState['sistema_activo'] === 0 && !evt_mto_has_bypass_session()) {
    header("Location: ../../login.php?error=6");
    exit();
}

if ($usuario === '' || $clave === '') {
    header("Location: ../../login.php?error=1");
    exit();
}

$stmt = null;

try {
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

    if ($stmt === false) {
        throw new Exception("Error en la preparacion de la consulta");
    }

    $stmt->bind_param("s", $usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows > 0) {
        $row = $result->fetch_assoc();
        $hashClave = isset($row['clave']) ? (string)$row['clave'] : '';

        if (password_verify($clave, $hashClave)) {
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

            $_SESSION['id_usuario'] = $idUsuario;
            $_SESSION['usuario'] = $usuario;
            $_SESSION['id_rol'] = (int)$row['id_rol'];
            $_SESSION['nombres'] = $row['nombres'];
            $_SESSION['apellidos'] = $row['apellidos'];
            $_SESSION['id_escuela'] = $row['id_escuela'];
            $_SESSION['id_py'] = $row['id_py'];
            $_SESSION['id_sede'] = $row['id_sede'];
            $_SESSION['id_depa'] = $row['id_depa'];

            if ((int)$row['id_rol'] === 1) {
                header("Location: ../../direccion_rsu/inicio.php");
                exit();
            } elseif ((int)$row['id_rol'] === 2) {
                header("Location: ../../inicio.php");
                exit();
            } elseif ((int)$row['id_rol'] === 3) {
                header("Location: ../../decanato_facultad/inicio.php");
                exit();
            } elseif ((int)$row['id_rol'] === 4) {
                header("Location: ../../director_departamento/inicio.php");
                exit();
            } elseif ((int)$row['id_rol'] === 5) {
                header("Location: ../../comite_facultad/inicio.php");
                exit();
            } else {
                header("Location: ../../login.php?error=2");
                exit();
            }
        } else {
            header("Location: ../../login.php?error=1");
            exit();
        }
    } else {
        header("Location: ../../login.php?error=1");
        exit();
    }
} catch (Exception $e) {
    error_log($e->getMessage());
    header("Location: ../../login.php?error=2");
    exit();
} finally {
    if ($stmt instanceof mysqli_stmt) {
        $stmt->close();
    }
    if (isset($conexion) && ($conexion instanceof mysqli)) {
        $conexion->close();
    }
}
?>
