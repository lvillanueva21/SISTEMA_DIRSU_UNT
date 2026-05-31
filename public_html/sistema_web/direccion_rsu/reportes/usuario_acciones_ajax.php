<?php
include "../../componentes/configSesion.php";
include "../../componentes/db.php";

header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');
mysqli_set_charset($conexion, 'utf8mb4');

function ua_json_exit($payload)
{
    echo json_encode($payload);
    exit;
}

function ua_qid($name)
{
    return '`' . str_replace('`', '``', (string)$name) . '`';
}

function ua_table_exists($conexion, $table)
{
    $table = trim((string)$table);
    if ($table === '') {
        return false;
    }

    $sql = "SELECT 1
              FROM INFORMATION_SCHEMA.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = ?
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return false;
    }
    mysqli_stmt_bind_param($st, 's', $table);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $ok = ($res instanceof mysqli_result) && (mysqli_num_rows($res) > 0);
    if ($res instanceof mysqli_result) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($st);

    return $ok;
}

function ua_user_get($conexion, $id)
{
    $sql = "SELECT id, usuario, nombres, apellidos, id_rol, id_py
              FROM usuarios
             WHERE id = ?
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return null;
    }
    mysqli_stmt_bind_param($st, 'i', $id);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
    if ($res instanceof mysqli_result) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($st);
    return $row ?: null;
}

function ua_user_current_state($conexion, $idUsuario)
{
    $sql = "SELECT descripcion
              FROM historial_usuarios
             WHERE id_usuario = ?
               AND descripcion LIKE 'Estado de usuario:%'
             ORDER BY id DESC
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return true;
    }
    mysqli_stmt_bind_param($st, 'i', $idUsuario);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
    if ($res instanceof mysqli_result) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($st);

    if (!$row || !isset($row['descripcion'])) {
        return true;
    }
    return stripos((string)$row['descripcion'], 'desactivado') === false;
}

function ua_historial_insert_estado($conexion, $idUsuario, $nuevoActivo)
{
    $estadoTxt = $nuevoActivo ? 'ACTIVADO' : 'DESACTIVADO';
    $descripcion = 'Estado de usuario: ' . $estadoTxt . ' por DIRSU';
    $adicional = '';

    $sql = "INSERT INTO historial_usuarios (descripcion, fecha, id_usuario, adicional)
            VALUES (?, NOW(), ?, ?)";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return false;
    }
    mysqli_stmt_bind_param($st, 'sis', $descripcion, $idUsuario, $adicional);
    $ok = mysqli_stmt_execute($st);
    mysqli_stmt_close($st);
    return $ok;
}

function ua_is_evaluator_role($idRol)
{
    $idRol = (int)$idRol;
    return in_array($idRol, array(1, 3, 4, 5), true);
}

function ua_upper_name($txt)
{
    $txt = trim((string)$txt);
    if (function_exists('mb_strtoupper')) {
        return mb_strtoupper($txt, 'UTF-8');
    }
    return strtoupper($txt);
}

function ua_valid_email_unitru($email)
{
    return (bool)preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i', (string)$email);
}

function ua_valid_phone($phone)
{
    return (bool)preg_match('/^9\d{8}$/', (string)$phone);
}

function ua_valid_name($name)
{
    return (bool)preg_match('/^[\p{L}\s\.\'\-]{2,50}$/u', (string)$name);
}

function ua_valid_email_simple($email)
{
    $email = trim((string)$email);
    if ($email === '') {
        return true;
    }
    return (bool)preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/', $email);
}

function ua_upsert_directorio($conexion, $usuario, $idRol, $email, $telefono, $nombres, $apellidos, $telAsis, $correoAsis, &$errorMsg = '')
{
    $sql = "INSERT INTO directorio
                (usuario, id_rol, email, telefono, nombres, apellidos, telefono_asistente, correo_asistente, created_at, updated_at)
            VALUES
                (?, ?, ?, ?, ?, ?, NULLIF(?, ''), NULLIF(?, ''), NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                id_rol = VALUES(id_rol),
                email = VALUES(email),
                telefono = VALUES(telefono),
                nombres = VALUES(nombres),
                apellidos = VALUES(apellidos),
                telefono_asistente = VALUES(telefono_asistente),
                correo_asistente = VALUES(correo_asistente),
                updated_at = NOW()";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        $errorMsg = 'Error al preparar directorio: ' . mysqli_error($conexion);
        return false;
    }
    mysqli_stmt_bind_param($st, 'sissssss', $usuario, $idRol, $email, $telefono, $nombres, $apellidos, $telAsis, $correoAsis);
    $ok = mysqli_stmt_execute($st);
    if (!$ok) {
        $errorMsg = mysqli_stmt_error($st);
    }
    mysqli_stmt_close($st);
    return $ok;
}

function ua_upsert_usuario_contactos($conexion, $usuario, $email, $telefono, &$errorMsg = '')
{
    $sql = "INSERT INTO usuario_contactos (usuario, email, telefono, created_at, updated_at)
            VALUES (?, ?, ?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                telefono = VALUES(telefono),
                updated_at = NOW()";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        $errorMsg = 'Error al preparar usuario_contactos: ' . mysqli_error($conexion);
        return false;
    }
    mysqli_stmt_bind_param($st, 'sss', $usuario, $email, $telefono);
    $ok = mysqli_stmt_execute($st);
    if (!$ok) {
        $errorMsg = mysqli_stmt_error($st);
    }
    mysqli_stmt_close($st);
    return $ok;
}

function ua_update_directorio_contact_only($conexion, $usuario, $email, $telefono, &$errorMsg = '')
{
    $sql = "UPDATE directorio
               SET email = ?, telefono = ?, updated_at = NOW()
             WHERE usuario = ?
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        $errorMsg = 'Error al preparar sincronización en directorio: ' . mysqli_error($conexion);
        return false;
    }
    mysqli_stmt_bind_param($st, 'sss', $email, $telefono, $usuario);
    $ok = mysqli_stmt_execute($st);
    if (!$ok) {
        $errorMsg = mysqli_stmt_error($st);
    }
    mysqli_stmt_close($st);
    return $ok;
}

function ua_first_link_in_table($conexion, $table, $column, $idUsuario)
{
    if (!ua_table_exists($conexion, $table)) {
        return null;
    }

    $sql = "SELECT *
              FROM " . ua_qid($table) . "
             WHERE " . ua_qid($column) . " = ?
             LIMIT 1";
    $st = mysqli_prepare($conexion, $sql);
    if (!$st) {
        return null;
    }
    mysqli_stmt_bind_param($st, 'i', $idUsuario);
    mysqli_stmt_execute($st);
    $res = mysqli_stmt_get_result($st);
    $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
    if ($res instanceof mysqli_result) {
        mysqli_free_result($res);
    }
    mysqli_stmt_close($st);

    if (!$row) {
        return null;
    }

    $registro = '';
    if (isset($row['id'])) {
        $registro = 'id=' . $row['id'];
    } else {
        $keys = array_keys($row);
        if (!empty($keys)) {
            $k = $keys[0];
            $registro = $k . '=' . $row[$k];
        } else {
            $registro = 'sin identificador';
        }
    }

    return array(
        'tabla' => $table,
        'columna' => $column,
        'registro' => $registro
    );
}

function ua_find_user_links($conexion, $idUsuario, $idProyecto)
{
    if ((int)$idProyecto > 0) {
        return array(
            'tabla' => 'usuarios',
            'columna' => 'id_py',
            'registro' => 'id_py=' . (int)$idProyecto
        );
    }

    $known = array(
        array('tabla' => 'usuarios_proyectos', 'columna' => 'id_usuario'),
        array('tabla' => 'evaluaciones', 'columna' => 'evaluador_id'),
        array('tabla' => 'historial_estados', 'columna' => 'usuario_id')
    );

    foreach ($known as $item) {
        $link = ua_first_link_in_table($conexion, $item['tabla'], $item['columna'], $idUsuario);
        if ($link) {
            return $link;
        }
    }

    $sql = "SELECT TABLE_NAME, COLUMN_NAME
              FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND COLUMN_NAME IN ('id_usuario', 'usuario_id', 'evaluador_id')
               AND TABLE_NAME NOT IN ('usuarios', 'historial_usuarios', 'usuarios_proyectos', 'evaluaciones', 'historial_estados')
             ORDER BY TABLE_NAME ASC";
    $res = mysqli_query($conexion, $sql);
    if ($res instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($res)) {
            $table = isset($row['TABLE_NAME']) ? $row['TABLE_NAME'] : '';
            $column = isset($row['COLUMN_NAME']) ? $row['COLUMN_NAME'] : '';
            if ($table === '' || $column === '') {
                continue;
            }
            $link = ua_first_link_in_table($conexion, $table, $column, $idUsuario);
            if ($link) {
                mysqli_free_result($res);
                return $link;
            }
        }
        mysqli_free_result($res);
    }

    return null;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ua_json_exit(array('ok' => false, 'msg' => 'Metodo no permitido.'));
}

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

if ($id <= 0) {
    ua_json_exit(array('ok' => false, 'msg' => 'ID de usuario invalido.'));
}

$user = ua_user_get($conexion, $id);
if (!$user) {
    ua_json_exit(array('ok' => false, 'msg' => 'Usuario no encontrado.'));
}

if ($action === 'toggle_state') {
    $activoActual = ua_user_current_state($conexion, $id);
    $activoNuevo = !$activoActual;

    if (!ua_historial_insert_estado($conexion, $id, $activoNuevo)) {
        ua_json_exit(array('ok' => false, 'msg' => 'No se pudo actualizar el estado del usuario.'));
    }

    ua_json_exit(array(
        'ok' => true,
        'msg' => $activoNuevo ? 'Usuario activado correctamente.' : 'Usuario desactivado correctamente.',
        'data' => array(
            'id' => $id,
            'activo' => $activoNuevo ? 1 : 0
        )
    ));
}

if ($action === 'update_contact') {
    $idRolUsuario = (int)(isset($user['id_rol']) ? $user['id_rol'] : 0);
    $esEvaluador = ua_is_evaluator_role($idRolUsuario);
    $esCoordinador = ($idRolUsuario === 2);

    if (!$esEvaluador && !$esCoordinador) {
        ua_json_exit(array('ok' => false, 'msg' => 'Solo se permite editar contacto para evaluadores o coordinadores.'));
    }

    $email = strtolower(trim((string)(isset($_POST['email']) ? $_POST['email'] : '')));
    $telefono = trim((string)(isset($_POST['telefono']) ? $_POST['telefono'] : ''));
    $nombres = ua_upper_name(isset($_POST['nombres']) ? $_POST['nombres'] : '');
    $apellidos = ua_upper_name(isset($_POST['apellidos']) ? $_POST['apellidos'] : '');
    $telefonoAsistente = trim((string)(isset($_POST['telefono_asistente']) ? $_POST['telefono_asistente'] : ''));
    $correoAsistente = strtolower(trim((string)(isset($_POST['correo_asistente']) ? $_POST['correo_asistente'] : '')));

    if (!ua_valid_email_unitru($email)) {
        ua_json_exit(array('ok' => false, 'msg' => 'El correo debe ser institucional (@unitru.edu.pe).'));
    }
    if (!ua_valid_phone($telefono)) {
        ua_json_exit(array('ok' => false, 'msg' => 'El telefono debe iniciar con 9 y tener 9 digitos.'));
    }

    if (mb_strlen($email, 'UTF-8') > 150) {
        ua_json_exit(array('ok' => false, 'msg' => 'El correo principal supera el máximo permitido (150 caracteres).'));
    }

    if ($esEvaluador) {
        if (!ua_valid_name($nombres) || !ua_valid_name($apellidos)) {
            ua_json_exit(array('ok' => false, 'msg' => 'Nombres o apellidos invalidos para este usuario (2 a 50 caracteres).'));
        }
        if ($telefonoAsistente !== '' && !ua_valid_phone($telefonoAsistente)) {
            ua_json_exit(array('ok' => false, 'msg' => 'El telefono de asistente es invalido.'));
        }
        if (!ua_valid_email_simple($correoAsistente)) {
            ua_json_exit(array('ok' => false, 'msg' => 'El correo de asistente es invalido.'));
        }
        if ($correoAsistente !== '' && mb_strlen($correoAsistente, 'UTF-8') > 150) {
            ua_json_exit(array('ok' => false, 'msg' => 'El correo de asistente supera el máximo permitido (150 caracteres).'));
        }
    } else {
        $telefonoAsistente = '';
        $correoAsistente = '';
        $nombres = (string)(isset($user['nombres']) ? $user['nombres'] : '');
        $apellidos = (string)(isset($user['apellidos']) ? $user['apellidos'] : '');
    }

    mysqli_begin_transaction($conexion);
    try {
        if ($esEvaluador) {
            $errDetalle = '';
            if (!ua_table_exists($conexion, 'directorio')) {
                throw new Exception('No existe la tabla directorio para registrar el contacto del evaluador.');
            }

            $sqlUser = "UPDATE usuarios
                           SET nombres = ?, apellidos = ?
                         WHERE id = ?
                         LIMIT 1";
            $stUser = mysqli_prepare($conexion, $sqlUser);
            if (!$stUser) {
                throw new Exception('No se pudo preparar la actualizacion del usuario.');
            }
            mysqli_stmt_bind_param($stUser, 'ssi', $nombres, $apellidos, $id);
            if (!mysqli_stmt_execute($stUser)) {
                mysqli_stmt_close($stUser);
                throw new Exception('No se pudo actualizar nombres y apellidos del usuario.');
            }
            mysqli_stmt_close($stUser);

            if (!ua_upsert_directorio(
                $conexion,
                (string)$user['usuario'],
                $idRolUsuario,
                $email,
                $telefono,
                $nombres,
                $apellidos,
                $telefonoAsistente,
                $correoAsistente,
                $errDetalle
            )) {
                throw new Exception('No se pudo guardar el contacto en directorio.' . ($errDetalle !== '' ? ' Detalle: ' . $errDetalle : ''));
            }
        } else {
            $errDetalle = '';
            if (!ua_table_exists($conexion, 'usuario_contactos')) {
                throw new Exception('No existe la tabla usuario_contactos para registrar el contacto del coordinador.');
            }
            if (!ua_upsert_usuario_contactos($conexion, (string)$user['usuario'], $email, $telefono, $errDetalle)) {
                throw new Exception('No se pudo guardar el contacto del coordinador.' . ($errDetalle !== '' ? ' Detalle: ' . $errDetalle : ''));
            }

            if (ua_table_exists($conexion, 'directorio')) {
                if (!ua_update_directorio_contact_only($conexion, (string)$user['usuario'], $email, $telefono, $errDetalle)) {
                    throw new Exception('No se pudo sincronizar el contacto en directorio.' . ($errDetalle !== '' ? ' Detalle: ' . $errDetalle : ''));
                }
            }
        }

        mysqli_commit($conexion);
        ua_json_exit(array(
            'ok' => true,
            'msg' => 'Informacion de contacto actualizada correctamente.',
            'data' => array(
                'id' => $id,
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'id_rol' => $idRolUsuario
            )
        ));
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        ua_json_exit(array('ok' => false, 'msg' => $e->getMessage()));
    }
}

if ($action === 'delete_contact') {
    $idRolUsuario = (int)(isset($user['id_rol']) ? $user['id_rol'] : 0);
    $esEvaluador = ua_is_evaluator_role($idRolUsuario);
    $esCoordinador = ($idRolUsuario === 2);
    $usuarioSistema = (string)(isset($user['usuario']) ? $user['usuario'] : '');
    $tocoAlgo = false;

    if (!$esEvaluador && !$esCoordinador) {
        ua_json_exit(array('ok' => false, 'msg' => 'Solo se permite eliminar contacto para evaluadores o coordinadores.'));
    }

    mysqli_begin_transaction($conexion);
    try {
        if (ua_table_exists($conexion, 'directorio')) {
            $sqlDir = "DELETE FROM directorio WHERE usuario = ? LIMIT 1";
            $stDir = mysqli_prepare($conexion, $sqlDir);
            if (!$stDir) {
                throw new Exception('No se pudo preparar la eliminacion de directorio.');
            }
            mysqli_stmt_bind_param($stDir, 's', $usuarioSistema);
            if (!mysqli_stmt_execute($stDir)) {
                mysqli_stmt_close($stDir);
                throw new Exception('No se pudo eliminar el contacto de directorio.');
            }
            $tocoAlgo = $tocoAlgo || (mysqli_stmt_affected_rows($stDir) > 0);
            mysqli_stmt_close($stDir);
        }

        if (ua_table_exists($conexion, 'usuario_contactos')) {
            $sqlUc = "DELETE FROM usuario_contactos WHERE usuario = ? LIMIT 1";
            $stUc = mysqli_prepare($conexion, $sqlUc);
            if (!$stUc) {
                throw new Exception('No se pudo preparar la eliminacion de usuario_contactos.');
            }
            mysqli_stmt_bind_param($stUc, 's', $usuarioSistema);
            if (!mysqli_stmt_execute($stUc)) {
                mysqli_stmt_close($stUc);
                throw new Exception('No se pudo eliminar el contacto de usuario_contactos.');
            }
            $tocoAlgo = $tocoAlgo || (mysqli_stmt_affected_rows($stUc) > 0);
            mysqli_stmt_close($stUc);
        }

        if (!$tocoAlgo) {
            throw new Exception('No se encontro informacion de contacto para eliminar.');
        }

        mysqli_commit($conexion);
        ua_json_exit(array(
            'ok' => true,
            'msg' => $esEvaluador
                ? 'Se elimino el contacto del evaluador.'
                : 'Se elimino el contacto del coordinador.'
        ));
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        ua_json_exit(array('ok' => false, 'msg' => $e->getMessage()));
    }
}

if ($action === 'delete_physical') {
    $usuarioSesion = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
    if ($usuarioSesion !== '' && $usuarioSesion === (string)$user['usuario']) {
        ua_json_exit(array('ok' => false, 'msg' => 'No puedes eliminar tu propio usuario mientras estas en sesion.'));
    }

    $link = ua_find_user_links($conexion, $id, isset($user['id_py']) ? (int)$user['id_py'] : 0);
    if ($link) {
        ua_json_exit(array(
            'ok' => false,
            'msg' => 'No se puede eliminar: el usuario esta vinculado en la tabla ' . $link['tabla'] . ' (' . $link['registro'] . ').'
        ));
    }

    mysqli_begin_transaction($conexion);
    try {
        if (ua_table_exists($conexion, 'historial_usuarios')) {
            $sqlHist = "DELETE FROM historial_usuarios WHERE id_usuario = ?";
            $stHist = mysqli_prepare($conexion, $sqlHist);
            if ($stHist) {
                mysqli_stmt_bind_param($stHist, 'i', $id);
                if (!mysqli_stmt_execute($stHist)) {
                    throw new Exception('No se pudo limpiar el historial del usuario.');
                }
                mysqli_stmt_close($stHist);
            }
        }

        $sqlDelete = "DELETE FROM usuarios WHERE id = ?";
        $stDelete = mysqli_prepare($conexion, $sqlDelete);
        if (!$stDelete) {
            throw new Exception('No se pudo preparar la eliminacion de usuario.');
        }
        mysqli_stmt_bind_param($stDelete, 'i', $id);
        if (!mysqli_stmt_execute($stDelete)) {
            $errNo = mysqli_errno($conexion);
            mysqli_stmt_close($stDelete);
            if ($errNo === 1451) {
                throw new Exception('No se puede eliminar porque existen registros relacionados en otras tablas.');
            }
            throw new Exception('No se pudo eliminar el usuario.');
        }
        mysqli_stmt_close($stDelete);

        mysqli_commit($conexion);

        ua_json_exit(array(
            'ok' => true,
            'msg' => 'Usuario eliminado correctamente.'
        ));
    } catch (Exception $e) {
        mysqli_rollback($conexion);
        ua_json_exit(array('ok' => false, 'msg' => $e->getMessage()));
    }
}

ua_json_exit(array('ok' => false, 'msg' => 'Accion no valida.'));
