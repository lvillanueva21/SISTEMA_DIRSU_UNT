<?php
// presentacion/logica/crear_respuesta.php  (REEMPLAZO COMPLETO Y SEGURO)

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

// === Cargas mínimas (NO incluir configSesion.php aquí) ===
require_once __DIR__ . '/../../componentes/db.php';
require_once __DIR__ . '/funciones.php';

// === Lee y valida POST ===
$id_formulario = isset($_POST['id_formulario']) ? (int)$_POST['id_formulario'] : 0;
$id_semestre   = isset($_POST['id_semestre'])   ? (int)$_POST['id_semestre']   : 0;
$id_cronograma = isset($_POST['id_cronograma']) ? (int)$_POST['id_cronograma'] : 0;

if ($id_formulario <= 0 || $id_semestre <= 0 || $id_cronograma <= 0) {
    ob_end_clean();
    header("Location: ../index.php?err=param_vacios");
    exit;
}

// === Derivar id_py desde el semestre (no dependemos de $_SESSION['id_py']) ===
$qh = $conexion->prepare("SELECT id_py, vigente FROM sm_proyecto_semestres WHERE id=?");
if (!$qh) { ob_end_clean(); header("Location: ../index.php?err=prep_sem"); exit; }
$qh->bind_param("i", $id_semestre);
$qh->execute();
$qh->bind_result($id_py_derivado, $vigente_sem);
$found = $qh->fetch();
$qh->close();

if (!$found) { ob_end_clean(); header("Location: ../index.php?err=sem_not_found"); exit; }
if ((int)$vigente_sem !== 1) { ob_end_clean(); header("Location: ../index.php?err=sem_not_vigente"); exit; }

// Si la sesión trae id_py, validamos consistencia; si no, usamos el derivado.
if (!empty($_SESSION['id_py']) && (int)$_SESSION['id_py'] !== (int)$id_py_derivado) {
    ob_end_clean(); header("Location: ../index.php?err=py_mismatch"); exit;
}
$id_py = (int)$id_py_derivado;

// === Seguridad de ventana: cronograma activo tipo=2 y en rango ===
$cron = obtenerCronogramaActivoTipo2($conexion);
if (!$cron || (int)$cron['id'] !== $id_cronograma) {
    ob_end_clean(); header("Location: ../index.php?err=no_crono"); exit;
}
$now = new DateTime('now', new DateTimeZone('America/Lima'));
$ap  = new DateTime($cron['apertura'], new DateTimeZone('America/Lima'));
$ci  = new DateTime($cron['cierre'],   new DateTimeZone('America/Lima'));
if (!($now >= $ap && $now <= $ci)) {
    ob_end_clean(); header("Location: ../index.php?err=fuera_rango"); exit;
}

// === El formulario existe y pertenece a ese cronograma ===
$chk1 = $conexion->prepare("SELECT 1 FROM sm_formularios WHERE id=? AND id_cronograma=? AND activo=1");
if (!$chk1) { ob_end_clean(); header("Location: ../index.php?err=prep_form"); exit; }
$chk1->bind_param("ii", $id_formulario, $id_cronograma);
$chk1->execute();
$ok1 = (bool)$chk1->get_result()->fetch_row();
$chk1->close();
if (!$ok1) { ob_end_clean(); header("Location: ../index.php?err=form_no_match"); exit; }

// === El semestre pertenece al proyecto (ya lo derivamos) ===
// (si quisieras, revalidación: id_semestre, id_py, vigente=1)
$chk2 = $conexion->prepare("SELECT 1 FROM sm_proyecto_semestres WHERE id=? AND id_py=? AND vigente=1");
if (!$chk2) { ob_end_clean(); header("Location: ../index.php?err=prep_sem2"); exit; }
$chk2->bind_param("ii", $id_semestre, $id_py);
$chk2->execute();
$ok2 = (bool)$chk2->get_result()->fetch_row();
$chk2->close();
if (!$ok2) { ob_end_clean(); header("Location: ../index.php?err=sem_no_match"); exit; }

// === UPSERT idempotente en sm_respuestas ===
$st = $conexion->prepare("
    INSERT INTO sm_respuestas (id_py, id_formulario, id_cronograma, id_semestre, estado)
    VALUES (?,?,?,?,0)
    ON DUPLICATE KEY UPDATE id = id
");
if (!$st) { ob_end_clean(); header("Location: ../index.php?err=prep_insert"); exit; }

$st->bind_param("iiii", $id_py, $id_formulario, $id_cronograma, $id_semestre);
$ok = $st->execute();
if (!$ok) {
    // log opcional
    error_log("[crear_respuesta] Insert error: (".$conexion->errno.") ".$conexion->error);
    $st->close();
    ob_end_clean(); header("Location: ../index.php?err=insert"); exit;
}
$st->close();

// === Redirigir a la UI principal (en la MISMA carpeta /presentacion) ===
if (empty($_SESSION['id_py'])) { $_SESSION['id_py'] = $id_py; }
ob_end_clean();
header("Location: index.php"); // <- SIN ../
exit;
