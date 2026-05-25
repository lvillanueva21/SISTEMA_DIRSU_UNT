<?php
include_once __DIR__ . '/../configSesion.php';
include_once __DIR__ . '/../../includes/api_dirsu/semester_audit_service.php';

if (!function_exists('rsu_initial_data_redirect')) {
    function rsu_initial_data_redirect($target)
    {
        $target = trim((string)$target);
        if ($target === '') {
            $target = '/sistema_web/inicio.php';
        }

        if (!headers_sent()) {
            header('Location: ' . $target);
        } else {
            echo "<script>location.assign(" . json_encode($target) . ");</script>";
        }
        exit();
    }
}

if (!function_exists('rsu_initial_data_flash_and_redirect')) {
    function rsu_initial_data_flash_and_redirect($target, $msg, $type)
    {
        $_SESSION['rsu_initial_data_msg'] = (string)$msg;
        $_SESSION['rsu_initial_data_msg_type'] = (string)$type;
        rsu_initial_data_redirect($target);
    }
}

if (!function_exists('rsu_initial_data_parse_return_to')) {
    function rsu_initial_data_parse_return_to($raw, $fallback)
    {
        $fallback = trim((string)$fallback);
        if ($fallback === '') {
            $fallback = '/sistema_web/inicio.php';
        }

        $raw = trim((string)$raw);
        if ($raw === '') {
            return $fallback;
        }
        if (strpos($raw, '://') !== false || strpos($raw, '..') !== false) {
            return $fallback;
        }
        if ($raw[0] !== '/') {
            return $fallback;
        }
        return $raw;
    }
}

if (!function_exists('rsu_initial_data_valid_ymd')) {
    function rsu_initial_data_valid_ymd($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $dt = DateTime::createFromFormat('Y-m-d', $value);
        if (!($dt instanceof DateTime)) {
            return null;
        }
        if ($dt->format('Y-m-d') !== $value) {
            return null;
        }
        $dt->setTime(0, 0, 0);
        return $dt;
    }
}

$fallback_return = '/sistema_web/inicio.php';
$return_to = rsu_initial_data_parse_return_to(isset($_POST['return_to']) ? $_POST['return_to'] : '', $fallback_return);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    rsu_initial_data_flash_and_redirect($return_to, 'Solicitud invalida para guardar datos iniciales.', 'danger');
}

if (!($conexion instanceof mysqli)) {
    rsu_initial_data_flash_and_redirect($return_to, 'No se pudo conectar con la base de datos.', 'danger');
}

$id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
if ($id_py <= 0) {
    rsu_initial_data_flash_and_redirect($return_to, 'No se encontro el proyecto activo en tu sesion.', 'danger');
}

$titulo = isset($_POST['p2']) ? trim((string)$_POST['p2']) : '';
$fecha_inicio_raw = isset($_POST['fecha_inicio']) ? trim((string)$_POST['fecha_inicio']) : '';
$fecha_fin_raw = isset($_POST['fecha_fin']) ? trim((string)$_POST['fecha_fin']) : '';

if ($titulo === '') {
    rsu_initial_data_flash_and_redirect($return_to, 'Debes completar el campo 2. Titulo del Proyecto.', 'warning');
}
$titulo_len = function_exists('mb_strlen') ? mb_strlen($titulo, 'UTF-8') : strlen($titulo);
if ($titulo_len > 300) {
    rsu_initial_data_flash_and_redirect($return_to, 'El titulo del proyecto supera el maximo permitido de 300 caracteres.', 'warning');
}

$fecha_inicio_dt = rsu_initial_data_valid_ymd($fecha_inicio_raw);
$fecha_fin_dt = rsu_initial_data_valid_ymd($fecha_fin_raw);
if (!($fecha_inicio_dt instanceof DateTime) || !($fecha_fin_dt instanceof DateTime)) {
    rsu_initial_data_flash_and_redirect($return_to, 'Debes registrar fechas validas de inicio y fin.', 'warning');
}
if ($fecha_inicio_raw === $fecha_fin_raw) {
    rsu_initial_data_flash_and_redirect($return_to, 'Las fechas no pueden ser iguales.', 'warning');
}
if ($fecha_fin_dt <= $fecha_inicio_dt) {
    rsu_initial_data_flash_and_redirect($return_to, 'La fecha de fin debe ser posterior a la fecha de inicio.', 'warning');
}

$preview = rsu_api_semester_audit_build_expected_rows($fecha_inicio_raw, $fecha_fin_raw);
if (!is_array($preview) || empty($preview['ok']) || !isset($preview['rows']) || !is_array($preview['rows'])) {
    rsu_initial_data_flash_and_redirect($return_to, 'No fue posible calcular los semestres del proyecto para ese rango.', 'danger');
}

$total_semestres = 0;
foreach ($preview['rows'] as $row) {
    if (isset($row['tipo']) && (string)$row['tipo'] === 'semestral') {
        $total_semestres++;
    }
}
if ($total_semestres < 4 || $total_semestres > 10) {
    rsu_initial_data_flash_and_redirect($return_to, 'La duracion del proyecto debe estar entre 4 y 10 semestres (de 2 a 5 anos).', 'warning');
}

$sql = "UPDATE proyectos SET p2 = ?, fecha_inicio = ?, fecha_fin = ? WHERE id = ? LIMIT 1";
$stmt = mysqli_prepare($conexion, $sql);
if (!$stmt) {
    rsu_initial_data_flash_and_redirect($return_to, 'No se pudo preparar la actualizacion del proyecto.', 'danger');
}

mysqli_stmt_bind_param($stmt, 'sssi', $titulo, $fecha_inicio_raw, $fecha_fin_raw, $id_py);
$ok = mysqli_stmt_execute($stmt);
$affected = mysqli_stmt_affected_rows($stmt);
mysqli_stmt_close($stmt);

if (!$ok) {
    rsu_initial_data_flash_and_redirect($return_to, 'No se pudo guardar los datos iniciales del proyecto.', 'danger');
}

if ($affected <= 0) {
    rsu_initial_data_flash_and_redirect($return_to, 'No hubo cambios en los datos iniciales del proyecto.', 'info');
}

rsu_initial_data_flash_and_redirect($return_to, 'Datos iniciales registrados correctamente. Ya puedes continuar.', 'success');
