<?php
// semestral/logica/crear_respuesta.php  — REEMPLAZO COMPLETO (con contacto + transacción)

// === Config básica para POST lógicos (sin HTML) ===
declare(strict_types=1);
ob_start();                                     // evita cualquier salida antes de header()
ini_set('display_errors', '1');                 // quita en producción
ini_set('display_startup_errors', '1');         // quita en producción
error_reporting(E_ALL);                         // quita en producción
date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// === Cargas mínimas ===
require_once __DIR__ . '/../../componentes/db.php';
require_once __DIR__ . '/funciones.php';

// === Lee y valida POST base ===
$id_formulario = isset($_POST['id_formulario']) ? (int)$_POST['id_formulario'] : 0;
$id_semestre   = isset($_POST['id_semestre'])   ? (int)$_POST['id_semestre']   : 0;
$id_cronograma = isset($_POST['id_cronograma']) ? (int)$_POST['id_cronograma'] : 0;

$email   = isset($_POST['email'])   ? trim((string)$_POST['email'])   : '';
$telefono= isset($_POST['telefono'])? trim((string)$_POST['telefono']): '';
$usuario = $_SESSION['usuario'] ?? '';

function fail($msgKey = 'error_general', $flashMsg = null) {
    if ($flashMsg) {
        $_SESSION['form_msg'] = $flashMsg;
        $_SESSION['form_msg_type'] = 'danger';
    }
    $indexUrl = defined('SM_SEMESTRAL_INDEX_REL') ? SM_SEMESTRAL_INDEX_REL : '../index.php';
    ob_end_clean();
    header("Location: " . $indexUrl . "?err=" . urlencode($msgKey));
    exit;
}

// Validaciones de campos obligatorios
if ($id_formulario <= 0 || $id_semestre <= 0 || $id_cronograma <= 0) {
    fail('param_vacios', 'Parámetros incompletos.');
}
if ($usuario === '') {
    fail('no_usuario', 'Sesión inválida (usuario no disponible).');
}

// Validación de contacto (server-side)
$email = strtolower($email);
if (!preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i', $email)) {
    fail('email_invalido', 'El correo debe ser @unitru.edu.pe y el teléfono debe tener 9 dígitos y empezar en 9.');
}
if (!preg_match('/^9\d{8}$/', $telefono)) {
    fail('tel_invalido', 'El correo debe ser @unitru.edu.pe y el teléfono debe tener 9 dígitos y empezar en 9.');
}

// === Derivar id_py desde el semestre (no dependemos de $_SESSION['id_py']) ===
$qh = $conexion->prepare("SELECT id_py, vigente FROM sm_proyecto_semestres WHERE id=?");
if (!$qh) fail('prep_sem', 'Error interno (prep_sem).');
$qh->bind_param("i", $id_semestre);
$qh->execute();
$qh->bind_result($id_py_derivado, $vigente_sem);
$found = $qh->fetch();
$qh->close();

if (!$found)                          fail('sem_not_found', 'No se encontró el semestre.');
if ((int)$vigente_sem !== 1)          fail('sem_not_vigente', 'El semestre no está vigente.');

// Si la sesión trae id_py, validamos consistencia; si no, usamos el derivado.
if (!empty($_SESSION['id_py']) && (int)$_SESSION['id_py'] !== (int)$id_py_derivado) {
    fail('py_mismatch', 'Proyecto de sesión no coincide.');
}
$id_py = (int)$id_py_derivado;

// === Seguridad de ventana: cronograma activo tipo=2 y en rango ===
$cron = obtenerCronogramaActivoTipo2($conexion);
if (!$cron || (int)$cron['id'] !== $id_cronograma) {
    fail('no_crono', 'Cronograma no activo.');
}
$now = new DateTime('now', new DateTimeZone('America/Lima'));
$ap  = new DateTime($cron['apertura'], new DateTimeZone('America/Lima'));
$ci  = new DateTime($cron['cierre'],   new DateTimeZone('America/Lima'));
if (!($now >= $ap && $now <= $ci)) {
    fail('fuera_rango', 'Fuera del rango de fechas.');
}

// === El formulario existe y pertenece a ese cronograma ===
$chk1 = $conexion->prepare("SELECT 1 FROM sm_formularios WHERE id=? AND id_cronograma=? AND activo=1");
if (!$chk1) fail('prep_form', 'Error interno (prep_form).');
$chk1->bind_param("ii", $id_formulario, $id_cronograma);
$chk1->execute();
$ok1 = (bool)$chk1->get_result()->fetch_row();
$chk1->close();
if (!$ok1) fail('form_no_match', 'Formulario no válido para este cronograma.');
// Tomar nombre legible del formulario para mensajes
$form_nombre = 'formulario';
if ($stF = $conexion->prepare("SELECT nombre FROM sm_formularios WHERE id=? LIMIT 1")) {
    $stF->bind_param("i", $id_formulario);
    $stF->execute();
    $stF->bind_result($form_nombre_db);
    if ($stF->fetch() && $form_nombre_db) {
        $form_nombre = $form_nombre_db;
    }
    $stF->close();
}


// === Transacción: contacto + creación de respuesta ===
$conexion->begin_transaction();
try {
    // 1) UPSERT contacto por usuario (único)
    $sqlC = "
      INSERT INTO usuario_contactos (usuario, email, telefono, created_at, updated_at)
      VALUES (?, ?, ?, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        email=VALUES(email),
        telefono=VALUES(telefono),
        updated_at=NOW()
    ";
    if (!($stc = $conexion->prepare($sqlC))) {
        throw new Exception('prep_contacto');
    }
    $stc->bind_param("sss", $usuario, $email, $telefono);
    if (!$stc->execute()) {
        throw new Exception('exec_contacto');
    }
    $stc->close();

    // 2) UPSERT idempotente en sm_respuestas (lógica existente)
    $st = $conexion->prepare("
        INSERT INTO sm_respuestas (id_py, id_formulario, id_cronograma, id_semestre, estado)
        VALUES (?,?,?,?,0)
        ON DUPLICATE KEY UPDATE id = id
    ");
    if (!$st) {
        throw new Exception('prep_insert_respuesta');
    }
    $st->bind_param("iiii", $id_py, $id_formulario, $id_cronograma, $id_semestre);
    if (!$st->execute()) {
        $st->close();
        throw new Exception('exec_insert_respuesta');
    }
    $st->close();

    if (!$conexion->commit()) {
        throw new Exception('commit_respuesta');
    }

    // Mantén id_py en sesión
    if (empty($_SESSION['id_py'])) { $_SESSION['id_py'] = $id_py; }

    $_SESSION['form_msg'] = "Se creó el formulario {$form_nombre} y se registró/actualizó tu información de contacto.";
    $_SESSION['form_msg_type'] = 'success';

    ob_end_clean();
    $indexUrl = defined('SM_SEMESTRAL_INDEX_REL') ? SM_SEMESTRAL_INDEX_REL : '../index.php';
    header("Location: " . $indexUrl);
    exit;

} catch (Throwable $e) {
    $conexion->rollback();
    // Log opcional:
    error_log("[crear_respuesta] TX error: ".$e->getMessage());

    $_SESSION['form_msg'] = "No se pudo crear el formulario porque falló el registro de contacto. Inténtalo de nuevo.";
    $_SESSION['form_msg_type'] = 'danger';

    ob_end_clean();
    $indexUrl = defined('SM_SEMESTRAL_INDEX_REL') ? SM_SEMESTRAL_INDEX_REL : '../index.php';
    header("Location: " . $indexUrl . "?err=tx_fail");
    exit;
}
