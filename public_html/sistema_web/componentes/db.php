<?php

    $direccionservidor="localhost";
    $baseDatos="rsudb";
    $usuarioBD="au_rsu";
    $contraseniaBD="_BrHJMGO3U3(9v.c";


$conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
if (!$conexion){
    echo "No se realizo la conexion a la base de datos el error fue:" . mysqli_connect_error();
}

?>
