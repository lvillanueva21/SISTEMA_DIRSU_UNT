<?php
require_once __DIR__ . '/sistema_web/componentes/db.php';

/* Activa excepciones automáticas en errores SQL */
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = $conexion;
$mysqli->set_charset('utf8mb4');
?>
