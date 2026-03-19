<?php
include("../db.php");
session_start();

$id_py        = (int) $_POST['id_py'];
$id_periodo   = (int) $_POST['id_periodo'];
$estado       = $_POST['estado']; // en_espera, aprobado, observado
$observacion  = trim($_POST['observacion'] ?? '');
$dias         = isset($_POST['dias']) ? (int) $_POST['dias'] : null;
$evaluador_id = $_SESSION['usuario'];
$oficina      = 'pcf'; // Comité Facultad
$tipo         = 'cotejo';

date_default_timezone_set('America/Lima');
$now = date('Y-m-d H:i:s');

// 1. Verificar si ya existe evaluación
$busca = mysqli_query($conexion, "
  SELECT id FROM evaluaciones 
  WHERE id_py = $id_py AND id_periodo = $id_periodo 
    AND oficina = '$oficina' AND tipo = '$tipo' 
  LIMIT 1
");

if ($row = mysqli_fetch_assoc($busca)) {
  $id_eval = $row['id'];
  mysqli_query($conexion, "
    UPDATE evaluaciones SET 
      estado = '$estado',
      evaluador_id = $evaluador_id,
      fecha_evaluacion = '$now',
      fecha_limite = " . ($estado == 'observado' ? "'".date('Y-m-d H:i:s', strtotime("+$dias days"))."'" : "NULL") . "
    WHERE id = $id_eval
  ");
} else {
  // Insertar nueva evaluación
  mysqli_query($conexion, "
    INSERT INTO evaluaciones (id_py, id_periodo, oficina, tipo, estado, evaluador_id, fecha_inicio, fecha_evaluacion, fecha_limite)
    VALUES ($id_py, $id_periodo, '$oficina', '$tipo', '$estado', $evaluador_id, '$now', '$now', " . 
      ($estado == 'observado' ? "'".date('Y-m-d H:i:s', strtotime("+$dias days"))."'" : "NULL") . "
    )
  ");
  $id_eval = mysqli_insert_id($conexion);
}

// 2. Si es observado, insertar o actualizar observación
if ($estado == 'observado') {
  $busca_obs = mysqli_query($conexion, "SELECT id FROM observaciones_cotejo WHERE id_evaluacion = $id_eval LIMIT 1");
  if ($row = mysqli_fetch_assoc($busca_obs)) {
    mysqli_query($conexion, "
      UPDATE observaciones_cotejo SET 
        observacion = '".mysqli_real_escape_string($conexion, $observacion)."',
        dias_subsanacion = $dias
      WHERE id = {$row['id']}
    ");
  } else {
    mysqli_query($conexion, "
      INSERT INTO observaciones_cotejo (id_evaluacion, observacion, dias_subsanacion)
      VALUES ($id_eval, '".mysqli_real_escape_string($conexion, $observacion)."', $dias)
    ");
  }

  // 3. Cambiar estado del proyecto a 0 (en espera)
  mysqli_query($conexion, "UPDATE proyectos SET estado = 0 WHERE id = $id_py");
}

// 4. (Opcional) registrar en historial_estados
mysqli_query($conexion, "
  INSERT INTO historial_estados (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
  VALUES (
    $id_py, $id_periodo, '$now',
    'Evaluación Cotejo PCF',
    'Resultado: $estado',
    $evaluador_id
  )
");

echo json_encode(['success' => true]);
?>
