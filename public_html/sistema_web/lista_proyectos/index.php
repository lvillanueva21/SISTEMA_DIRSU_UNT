<?php
include "../componentes/configSesion.php";

$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$destino = ($id_rol === 2) ? 'coordinador.php' : 'evaluador.php';

header('Location: ' . $destino);
exit;
