<?php
include("../../componentes/db.php");
session_start();

$id_py      = (int) $_GET['id_py'];
$id_periodo = (int) $_GET['id_periodo'];
$oficina    = 'rsu'; // ← CAMBIO HECHO AQUÍ
$tipo       = 'rubrica';

$out = [
  'success'         => false,
  'estado'          => 'en_espera',
  'puntaje_total'   => 0,
  'aspectos'        => [],
  'dias_subsanacion'=> null // 👈 nuevo
];

$eval = mysqli_query($conexion, "
  SELECT id, estado, fecha_limite, fecha_evaluacion FROM evaluaciones
  WHERE id_py = $id_py AND id_periodo = $id_periodo
    AND oficina = '$oficina' AND tipo = '$tipo'
  LIMIT 1
");

if ($e = mysqli_fetch_assoc($eval)) {
  $out['success'] = true;
  $out['estado']  = $e['estado'];
  $eval_id        = $e['id'];
  $total          = 0;

  // Calcular días entre evaluación y límite
  if (!empty($e['fecha_limite']) && !empty($e['fecha_evaluacion'])) {
    $limite = new DateTime($e['fecha_limite']);
    $evaluacion = new DateTime($e['fecha_evaluacion']);
    $diff = $evaluacion->diff($limite)->days;

    if ($diff >= 1 && $diff <= 2) {
      $out['dias_subsanacion'] = $diff;
    }
  }

  $asp_q = mysqli_query($conexion, "
    SELECT aspecto, puntaje, IFNULL(observacion,'') AS observacion
    FROM rubrica_aspectos 
    WHERE id_evaluacion = $eval_id
  ");

  while ($a = mysqli_fetch_assoc($asp_q)) {
    $total += (int)$a['puntaje'];
    $out['aspectos'][$a['aspecto']] = [
      'puntaje'     => (int)$a['puntaje'],
      'observacion' => $a['observacion']
    ];
  }

  $out['puntaje_total'] = $total;
}

echo json_encode($out);
?>
