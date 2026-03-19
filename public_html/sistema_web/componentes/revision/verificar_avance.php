<?php
include_once("../../componentes/db.php");

function revisarYAvanzarProyecto($id_py, $id_periodo, $oficina_actual) {
  global $conexion;

  $orden = ['pcf' => 'dd', 'dd' => 'df', 'df' => 'rsu', 'rsu' => 'fin'];
  $siguiente = $orden[$oficina_actual] ?? null;

  if (!$siguiente) return;

  // Evaluaciones requeridas por oficina
  if (in_array($oficina_actual, ['pcf', 'rsu'])) {
    $rubrica = obtenerEstado($id_py, $id_periodo, $oficina_actual, 'rubrica');
    $cotejo  = obtenerEstado($id_py, $id_periodo, $oficina_actual, 'cotejo');
    if ($rubrica !== 'aprobado' || $cotejo !== 'aprobado') return;
  } elseif (in_array($oficina_actual, ['dd', 'df'])) {
    $vb = obtenerEstado($id_py, $id_periodo, $oficina_actual, 'vb');
    if ($vb !== 'aprobado') return;
  }

  // Avanzar proyecto
  if ($siguiente === 'fin') {
    mysqli_query($conexion, "UPDATE proyectos SET estado = 2 WHERE id = $id_py");
  } else {
    mysqli_query($conexion, "
      UPDATE revisiones_proyectos
      SET oficina_actual = '$siguiente', fecha_solicitud = NOW()
      WHERE id_py = $id_py AND id_periodo = $id_periodo
    ");
    mysqli_query($conexion, "UPDATE proyectos SET estado = 1 WHERE id = $id_py");
  }
}

function obtenerEstado($id_py, $id_periodo, $oficina, $tipo) {
  global $conexion;
  $q = mysqli_query($conexion, "
    SELECT estado FROM evaluaciones
    WHERE id_py = $id_py AND id_periodo = $id_periodo
      AND oficina = '$oficina' AND tipo = '$tipo'
    LIMIT 1
  ");
  return mysqli_fetch_assoc($q)['estado'] ?? 'en_espera';
}
