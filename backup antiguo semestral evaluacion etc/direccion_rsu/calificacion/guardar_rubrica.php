<?php
include("../../componentes/db.php");
session_start();

$id_py        = (int) $_POST['id_py'];
$id_periodo   = (int) $_POST['id_periodo'];
$puntajes     = $_POST['puntaje'] ?? [];
$obs          = $_POST['observacion'] ?? [];
$dias         = isset($_POST['dias_subsanacion']) ? (int) $_POST['dias_subsanacion'] : null;
$evaluador    = $_SESSION['usuario'];
$oficina = 'rsu';  // Dirección RSU
$tipo         = 'rubrica';

date_default_timezone_set('America/Lima');
$now = date('Y-m-d H:i:s');

// Calcular fecha límite si es observado y se seleccionó días
$fecha_limite = "NULL";
if ($dias && in_array($dias, [1, 2])) {
  $fecha_limite_calc = date('Y-m-d H:i:s', strtotime("+$dias days"));
  $fecha_limite = "'$fecha_limite_calc'";
}

/* ── 1. Verificar si el proyecto está en esta oficina ──────────────── */
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
      'msg' => "El proyecto ya no está en la oficina de <b>$oficina_actual</b>.<br>Estado actual: <b>$estado_actual</b>."
    ]);
    exit;
  }
}

/* ── 2. Calcular total y determinar estado ────────────────────────── */
$total = 0;
foreach ($puntajes as $p) {
  $total += is_numeric($p) ? intval($p) : 0;
}

$estado = 'en_espera';
if ($total > 13) {
  $estado = 'aprobado';
} elseif ($total > 0) {
  $estado = 'observado';
}

/* ── 3. Insertar o actualizar evaluación ───────────────────────────── */
$eval = mysqli_query($conexion, "
  SELECT id FROM evaluaciones 
  WHERE id_py = $id_py AND id_periodo = $id_periodo 
    AND oficina = '$oficina' AND tipo = '$tipo'
  LIMIT 1
");

if ($row = mysqli_fetch_assoc($eval)) {
  $eval_id = $row['id'];
  mysqli_query($conexion, "
    UPDATE evaluaciones SET 
      estado = '$estado',
      evaluador_id = $evaluador,
      fecha_evaluacion = '$now',
      fecha_limite = $fecha_limite
    WHERE id = $eval_id
  ");
} else {
  mysqli_query($conexion, "
    INSERT INTO evaluaciones (
      id_py, id_periodo, oficina, tipo, estado,
      evaluador_id, fecha_inicio, fecha_evaluacion, fecha_limite
    ) VALUES (
      $id_py, $id_periodo, '$oficina', '$tipo', '$estado',
      $evaluador, '$now', '$now', $fecha_limite
    )
  ");
  $eval_id = mysqli_insert_id($conexion);
}

/* ── 4. Insertar/actualizar rubrica_aspectos ──────────────────────── */
$aspectos = ['estructura', 'contenido', 'redaccion', 'calidad_info', 'mejora'];

foreach ($aspectos as $a) {
  $p  = isset($puntajes[$a]) ? (int)$puntajes[$a] : 0;
  $ob = mysqli_real_escape_string($conexion, $obs[$a] ?? '');

  $existe = mysqli_query($conexion, "
    SELECT id FROM rubrica_aspectos 
    WHERE id_evaluacion = $eval_id AND aspecto = '$a' LIMIT 1
  ");

  if ($fila = mysqli_fetch_assoc($existe)) {
    mysqli_query($conexion, "
      UPDATE rubrica_aspectos 
      SET puntaje = $p, observacion = '$ob'
      WHERE id = {$fila['id']}
    ");
  } else {
    mysqli_query($conexion, "
      INSERT INTO rubrica_aspectos (id_evaluacion, aspecto, puntaje, observacion)
      VALUES ($eval_id, '$a', $p, '$ob')
    ");
  }
}

/* ── 5. Si es observado, regresar estado del proyecto a EN ESPERA ─── */
if ($estado === 'observado') {
  mysqli_query($conexion, "
    UPDATE proyectos SET estado = 0 WHERE id = $id_py
  ");
}

/* ── 6. Historial ─────────────────────────────────────────────────── */
mysqli_query($conexion, "
  INSERT INTO historial_estados (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
  VALUES ($id_py, $id_periodo, '$now', 'Evaluación Rúbrica RSU', 'Resultado: $estado', $evaluador)
");

require_once("../../componentes/revision/verificar_avance.php");
revisarYAvanzarProyecto($id_py, $id_periodo, $oficina);
echo json_encode(['success' => true]);
?>
