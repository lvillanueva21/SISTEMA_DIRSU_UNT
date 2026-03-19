<?php
include("../../componentes/db.php");
session_start();

$id_py = (int) $_GET['id_py'];
$id_periodo = (int) $_GET['id_periodo'];
$oficina = 'rsu'; // Cambiado de 'pcf' a 'rsu'
$tipo = 'cotejo';

$respuesta = ['success' => false];

$busca = mysqli_query($conexion, "
  SELECT id, estado FROM evaluaciones
  WHERE id_py = $id_py AND id_periodo = $id_periodo
    AND oficina = '$oficina' AND tipo = '$tipo'
  LIMIT 1
");

if ($e = mysqli_fetch_assoc($busca)) {
  $respuesta['success'] = true;
  $respuesta['estado'] = $e['estado'];
  $respuesta['observacion'] = ''; 
  $respuesta['dias'] = '';

  // Si fue observado, traer observación
  if ($e['estado'] === 'observado') {
    $obs = mysqli_query($conexion, "
      SELECT observacion, dias_subsanacion
      FROM observaciones_cotejo
      WHERE id_evaluacion = {$e['id']} LIMIT 1
    ");
    if ($o = mysqli_fetch_assoc($obs)) {
      $respuesta['observacion'] = $o['observacion'];
      $respuesta['dias'] = $o['dias_subsanacion'];
    }
  }
}

echo json_encode($respuesta);
