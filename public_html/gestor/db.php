
<?php
//gestor/db.php

$direccionservidor = "localhost";
$baseDatos = "rsudb";
$usuarioBD = "au_rsu";
$contraseniaBD = '_BrHJMGO3U3(9v.c';

// Crear la conexion
$conexion = new mysqli($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);

// Verificar la conexion
if ($conexion->connect_error) {
    die("Conexion fallida: " . $conexion->connect_error);
}
?>
