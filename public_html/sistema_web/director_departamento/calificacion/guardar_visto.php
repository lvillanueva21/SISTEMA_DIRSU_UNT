<?php
include("../../componentes/db.php");
session_start();

$id_py        = (int) $_POST['id_py'];
$id_periodo   = (int) $_POST['id_periodo'];
$estado_vb    = $_POST['estado_vb']; // debe ser 'en_espera' o 'aprobado'
$evaluador_id = $_SESSION['usuario'];
$oficina      = 'dd';  // Dirección de Departamento
$tipo         = 'vb';  // Visto Bueno

date_default_timezone_set('America/Lima');
$now = date('Y-m-d H:i:s');

// 🔍 1. Validar si el proyecto está en esta oficina
$verifica = mysqli_query($conexion, "
  SELECT oficina_actual FROM revisiones_proyectos 
  WHERE id_py = $id_py AND id_periodo = $id_periodo
  LIMIT 1
");

if ($row = mysqli_fetch_assoc($verifica)) {
  $oficina_actual = $row['oficina_actual'];

  if ($oficina_actual !== $oficina) {
    $estado_q = mysqli_query($conexion, "
      SELECT estado FROM evaluaciones 
      WHERE id_py = $id_py AND id_periodo = $id_periodo 
        AND oficina = '$oficina_actual' AND tipo = '$tipo'
      ORDER BY fecha_evaluacion DESC LIMIT 1
    ");
    $estado_actual = 'en_espera';
    if ($r = mysqli_fetch_assoc($estado_q)) {
      $estado_actual = $r['estado'];
    }

    echo json_encode([
      'success' => false,
      'msg' => "Aún no puedes calificar este proyecto pues se encuentra en la oficina de <b>$oficina_actual</b> con estado <b>$estado_actual</b>."
    ]);
    exit;
  }
}

// ✅ 2. Guardar evaluación en tabla evaluaciones
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
      estado = '$estado_vb',
      evaluador_id = $evaluador_id,
      fecha_evaluacion = '$now',
      fecha_limite = NULL
    WHERE id = $id_eval
  ");
} else {
  mysqli_query($conexion, "
    INSERT INTO evaluaciones (id_py, id_periodo, oficina, tipo, estado, evaluador_id, fecha_inicio, fecha_evaluacion, fecha_limite)
    VALUES ($id_py, $id_periodo, '$oficina', '$tipo', '$estado_vb', $evaluador_id, '$now', '$now', NULL)
  ");
  $id_eval = mysqli_insert_id($conexion);
}

// ✅ Historial
mysqli_query($conexion, "
  INSERT INTO historial_estados (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
  VALUES (
    $id_py, $id_periodo, '$now',
    'Evaluación Visto Bueno DD',
    'Resultado: $estado_vb',
    $evaluador_id
  )
");

// ✅ Verificar avance
require_once("../../componentes/revision/verificar_avance.php");
revisarYAvanzarProyecto($id_py, $id_periodo, $oficina);

echo json_encode(['success' => true]);
?>
