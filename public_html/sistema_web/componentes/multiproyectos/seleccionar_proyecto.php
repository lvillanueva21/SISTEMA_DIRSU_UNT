<?php
session_start();
header('Content-Type: application/json; charset=UTF-8');

include_once __DIR__ . '/../../includes/db_connection.php';

if (!function_exists('rsu_multiproyecto_response')) {
    function rsu_multiproyecto_response($ok, $message, $extra = array())
    {
        $payload = array_merge(
            array(
                'ok' => (bool)$ok,
                'message' => (string)$message
            ),
            is_array($extra) ? $extra : array()
        );

        echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        exit;
    }
}

if (!isset($_SESSION['usuario'])) {
    rsu_multiproyecto_response(false, 'Sesión no válida. Vuelve a iniciar sesión.');
}

if (!isset($_POST['id_proyecto'])) {
    rsu_multiproyecto_response(false, 'Debes seleccionar un proyecto para continuar.');
}

$id_nuevo_proyecto = (int)$_POST['id_proyecto'];
if ($id_nuevo_proyecto <= 0) {
    rsu_multiproyecto_response(false, 'El proyecto seleccionado no es válido.');
}

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    rsu_multiproyecto_response(false, 'No se pudo conectar con la base de datos.');
}

$usuario = trim((string)$_SESSION['usuario']);
$id_usuario = 0;
$id_actual_py = 0;

$stmt_user = $conexion->prepare('SELECT id, id_py FROM usuarios WHERE usuario = ? LIMIT 1');
if (!$stmt_user) {
    rsu_multiproyecto_response(false, 'No se pudo validar el usuario en sesión.');
}

$stmt_user->bind_param('s', $usuario);
$stmt_user->execute();
$stmt_user->bind_result($id_usuario, $id_actual_py);
$stmt_user->fetch();
$stmt_user->close();

if ($id_usuario <= 0) {
    rsu_multiproyecto_response(false, 'No se encontró el usuario en sesión.');
}

$stmt_permiso = $conexion->prepare('SELECT id_proyecto FROM usuarios_proyectos WHERE id_usuario = ? AND id_proyecto = ? AND activo = 1 LIMIT 1');
if (!$stmt_permiso) {
    rsu_multiproyecto_response(false, 'No se pudo validar la relación entre usuario y proyecto.');
}

$stmt_permiso->bind_param('ii', $id_usuario, $id_nuevo_proyecto);
$stmt_permiso->execute();
$stmt_permiso->store_result();
$proyecto_valido = $stmt_permiso->num_rows > 0;
$stmt_permiso->close();

if (!$proyecto_valido) {
    rsu_multiproyecto_response(false, 'El proyecto seleccionado no está vinculado a tu usuario.');
}

if ($id_actual_py === $id_nuevo_proyecto) {
    $_SESSION['id_py'] = $id_nuevo_proyecto;
    rsu_multiproyecto_response(true, 'Proyecto activo confirmado.', array('id_proyecto' => $id_nuevo_proyecto));
}

$periodo_anterior = 'No registrado';
if ($id_actual_py > 0) {
    $stmt_periodo_anterior = $conexion->prepare(
        'SELECT per.nombre
         FROM proyectos_periodo pp
         INNER JOIN periodos per ON per.id = pp.id_periodo
         WHERE pp.id_py = ?
         ORDER BY per.fecha_inicio DESC, per.id DESC
         LIMIT 1'
    );

    if ($stmt_periodo_anterior) {
        $stmt_periodo_anterior->bind_param('i', $id_actual_py);
        $stmt_periodo_anterior->execute();
        $stmt_periodo_anterior->bind_result($periodo_anterior);
        $stmt_periodo_anterior->fetch();
        $stmt_periodo_anterior->close();
    }
}

$periodo_nuevo = 'No registrado';
$stmt_periodo_nuevo = $conexion->prepare(
    'SELECT per.nombre
     FROM proyectos_periodo pp
     INNER JOIN periodos per ON per.id = pp.id_periodo
     WHERE pp.id_py = ?
     ORDER BY per.fecha_inicio DESC, per.id DESC
     LIMIT 1'
);

if ($stmt_periodo_nuevo) {
    $stmt_periodo_nuevo->bind_param('i', $id_nuevo_proyecto);
    $stmt_periodo_nuevo->execute();
    $stmt_periodo_nuevo->bind_result($periodo_nuevo);
    $stmt_periodo_nuevo->fetch();
    $stmt_periodo_nuevo->close();
}

$stmt_update = $conexion->prepare('UPDATE usuarios SET id_py = ? WHERE id = ?');
if (!$stmt_update) {
    rsu_multiproyecto_response(false, 'No se pudo actualizar el proyecto activo del usuario.');
}

$stmt_update->bind_param('ii', $id_nuevo_proyecto, $id_usuario);
$stmt_update->execute();
$filas_afectadas = $stmt_update->affected_rows;
$stmt_update->close();

if ($filas_afectadas < 0) {
    rsu_multiproyecto_response(false, 'No fue posible registrar el proyecto activo seleccionado.');
}

$_SESSION['id_py'] = $id_nuevo_proyecto;

date_default_timezone_set('America/Lima');
$fecha = date('Y-m-d H:i:s');
$descripcion = 'Se cambió el proyecto activo al ID: ' . $id_nuevo_proyecto
    . ' (período: ' . $periodo_nuevo . '). '
    . 'Proyecto anterior ID: ' . $id_actual_py
    . ' (período: ' . $periodo_anterior . ').';

$stmt_historial = $conexion->prepare('INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, ?, ?)');
if ($stmt_historial) {
    $stmt_historial->bind_param('ssi', $descripcion, $fecha, $id_nuevo_proyecto);
    $stmt_historial->execute();
    $stmt_historial->close();
}

rsu_multiproyecto_response(true, 'Proyecto seleccionado correctamente.', array('id_proyecto' => $id_nuevo_proyecto));
