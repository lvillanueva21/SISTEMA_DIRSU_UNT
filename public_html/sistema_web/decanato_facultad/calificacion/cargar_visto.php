<?php
include("../../componentes/db.php");
session_start();

$id_py = (int) $_GET['id_py'];
$id_periodo = (int) $_GET['id_periodo'];
$oficina = 'df';     // Decanato de Facultad
$tipo = 'vb';        // Visto Bueno

$respuesta = ['success' => false];

$busca = mysqli_query($conexion, "
  SELECT estado FROM evaluaciones
  WHERE id_py = $id_py AND id_periodo = $id_periodo
    AND oficina = '$oficina' AND tipo = '$tipo'
  LIMIT 1
");

if ($e = mysqli_fetch_assoc($busca)) {
  $respuesta['success'] = true;
  $respuesta['estado'] = $e['estado'];
}

echo json_encode($respuesta);