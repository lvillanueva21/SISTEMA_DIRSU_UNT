<?php
include('../../componentes/db.php');
$id_py = isset($_GET['id_py']) ? intval($_GET['id_py']) : 0;

if ($id_py === 0) {
  echo 'Proyecto no válido.'; exit;
}

// Obtener información del proyecto
$info_q = mysqli_query($conexion, "
  SELECT u.nombres, u.apellidos, u.usuario, u.id_depa, d.id_facultad,
         p.p2 AS titulo, p.estado AS estado_general
  FROM usuarios u
  JOIN proyectos p ON u.id_py = p.id
  JOIN departamentos d ON u.id_depa = d.id
  WHERE p.id = $id_py
  LIMIT 1
");
$info = mysqli_fetch_assoc($info_q);
if (!$info) { echo 'No se encontró el proyecto.'; exit; }

// Obtener período actual del proyecto
$periodo_q = mysqli_query($conexion, "
  SELECT pp.id_periodo, pe.nombre
  FROM proyectos_periodo pp
  JOIN periodos pe ON pp.id_periodo = pe.id
  WHERE pp.id_py = $id_py
  ORDER BY pe.fecha_inicio DESC LIMIT 1
");
$periodo = mysqli_fetch_assoc($periodo_q);
$id_periodo = $periodo['id_periodo'] ?? 0;

// Cabecera
// Mostrar resumen en bloque estilizado con tabla 5x2 (solo borde externo visible)
$nombre_completo = $info['nombres'] . ' ' . $info['apellidos'];
$usuario = $info['usuario'];
$titulo_proyecto = $info['titulo'];
$facultad_id = $info['id_facultad'];
$depa_id = $info['id_depa'];

// Consultar nombre de facultad
$fac_q = mysqli_query($conexion, "SELECT nombre FROM facultades WHERE id = $facultad_id LIMIT 1");
$nombre_facultad = ($fac = mysqli_fetch_assoc($fac_q)) ? $fac['nombre'] : 'Facultad no definida';

// Consultar nombre de departamento
$dep_q = mysqli_query($conexion, "SELECT nombre FROM departamentos WHERE id = $depa_id LIMIT 1");
$nombre_departamento = ($dep = mysqli_fetch_assoc($dep_q)) ? $dep['nombre'] : 'Departamento no definido';

echo '<div style="border: 2px solid black; border-radius: 10px; margin-bottom: 20px;">';
echo '<table style="width: 100%; table-layout: fixed; border-collapse: collapse;">';

// Fila 1
echo '<tr>';

// Celda de Período (ocupa dos filas)
echo '<td rowspan="2" style="background-color: #19804F; color: white; font-weight: bold; text-align: center; padding: 10px; width: 90px; border: none; word-wrap: break-word; vertical-align: middle; white-space: normal;">
  <i class="fas fa-calendar-alt"></i><br>' . htmlspecialchars($periodo['nombre']) . '
</td>';

// Nombre completo (colspan=2)
echo '<td colspan="2" style="border: none; font-weight: bold; word-break: break-word; padding: 4px; white-space: normal;">'
  . htmlspecialchars($nombre_completo) .
'</td>';

// Título (colspan=2)
echo '<td colspan="2" style="border: none; font-style: italic; word-break: break-word; padding: 4px; white-space: normal;">'
  . htmlspecialchars($titulo_proyecto) .
'</td>';

echo '</tr>';

// Fila 2
echo '<tr>';

// Facultad
echo '<td style="border: none; padding: 4px;">
  <span style="
    display: inline-block;
    width: 100%;
    height: 50px;
    background-color: #6A00FF;
    color: white;
    padding: 4px 8px;
    border-radius: 5px;
    text-align: center;
    word-wrap: break-word;
    white-space: normal;
    font-size: 0.85rem;
    line-height: 1.1rem;
    overflow-wrap: break-word;
    display: flex;
    align-items: center;
    justify-content: center;
  ">' . htmlspecialchars($nombre_facultad) . '</span>
</td>';

// Departamento
echo '<td style="border: none; padding: 4px;">
  <span style="
    display: inline-block;
    width: 100%;
    height: 50px;
    background-color: #FFFF88;
    color: black;
    padding: 4px 8px;
    border-radius: 5px;
    text-align: center;
    word-wrap: break-word;
    white-space: normal;
    font-size: 0.85rem;
    line-height: 1.1rem;
    overflow-wrap: break-word;
    display: flex;
    align-items: center;
    justify-content: center;
  ">' . htmlspecialchars($nombre_departamento) . '</span>
</td>';

// Código usuario
echo '<td style="border: none; padding: 4px; word-wrap: break-word; text-align: center;">
  <b>Código usuario:</b><br>' . htmlspecialchars($usuario) . '
</td>';

// Código proyecto
echo '<td style="border: none; padding: 4px; word-wrap: break-word; text-align: center;">
  <b>Código proyecto:</b><br>' . htmlspecialchars($id_py) . '
</td>';

echo '</tr>';
echo '</table>';
echo '</div>';
echo '</table>';
echo '</div>';

// Estado general (tabla proyectos.estado)
$estado_labels = [
  0 => ['label' => 'En Proceso', 'badge' => 'primary'],
  1 => ['label' => 'En Revisión', 'badge' => 'warning'],
  2 => ['label' => 'Aprobación 2024-II', 'badge' => 'success']
];
$estado_g = $info['estado_general'];
$estado_g_label = $estado_labels[$estado_g]['label'];
$estado_g_class = $estado_labels[$estado_g]['badge'];

// Revisión actual (tabla revisiones_proyectos)
$rev_q = mysqli_query($conexion, "
  SELECT oficina_actual, estado, fecha_solicitud, fecha_cierre
  FROM revisiones_proyectos
  WHERE id_py = $id_py AND id_periodo = $id_periodo
  LIMIT 1
");
$rev = mysqli_fetch_assoc($rev_q);

// Mostrar estado actual
echo "<hr><h5>📍 Estado Actual</h5>";
if ($rev) {
  $ofi_label = [
    'pcf' => 'Comité de Facultad',
    'dd'  => 'Dirección de Departamento',
    'df'  => 'Decanato de Facultad',
    'rsu' => 'Dirección RSU'
  ];
  echo "<p><b>Oficina actual:</b> {$ofi_label[$rev['oficina_actual']]}</p>";
  echo "<p><b>Estado general:</b> <span class='badge badge-{$estado_g_class}'>{$estado_g_label}</span></p>";
  echo "<p><b>Fecha de solicitud:</b> {$rev['fecha_solicitud']}</p>";
  if ($rev['fecha_cierre']) {
    echo "<p><b>Fecha de cierre:</b> {$rev['fecha_cierre']}</p>";
  }
} else {
  echo "<p>No hay información de revisión registrada.</p>";
}

// Evaluaciones por oficina
echo "<hr><h5>📋 Evaluaciones</h5>";
$eval_q = mysqli_query($conexion, "
  SELECT * FROM evaluaciones
  WHERE id_py = $id_py AND id_periodo = $id_periodo
  ORDER BY oficina, tipo
");

$tipos = ['cotejo' => 'Lista de Cotejo', 'rubrica' => 'Rúbrica', 'vb' => 'Visto Bueno'];
$oficinas = ['pcf' => 'Comité de Facultad', 'dd' => 'Dirección de Departamento', 'df' => 'Decanato', 'rsu' => 'Dirección RSU'];

while ($e = mysqli_fetch_assoc($eval_q)) {
  $estado_eval = $e['estado'];
  $isAprobado = ($estado_eval === 'aprobado');
  $badge_color = $isAprobado ? 'success' : ($estado_eval === 'observado' ? 'danger' : 'primary');

  echo "<div style='border: 1px solid #ccc; padding: 10px; margin-bottom: 15px;'>";
  echo "<b>{$oficinas[$e['oficina']]} - {$tipos[$e['tipo']]}</b><br>";
  echo "Estado: <span class='badge badge-$badge_color'>" . ucfirst($estado_eval) . "</span><br>";
  echo "Fecha evaluación: " . ($e['fecha_evaluacion'] ?? '---') . "<br>";

  if (!$isAprobado && $e['fecha_limite']) {
    echo "Fecha límite: {$e['fecha_limite']}<br>";
  }

  if (!$isAprobado) {
    // Observaciones (Cotejo)
    if ($e['tipo'] === 'cotejo') {
      $obs_q = mysqli_query($conexion, "
        SELECT observacion, dias_subsanacion 
        FROM observaciones_cotejo WHERE id_evaluacion = {$e['id']}
      ");
      if ($obs = mysqli_fetch_assoc($obs_q)) {
        echo "<b>Observación:</b><br>" . nl2br(htmlspecialchars($obs['observacion'])) . "<br>";
        echo "Días para subsanar: {$obs['dias_subsanacion']}<br>";
      }
    }

    // Observaciones (Rúbrica)
    if ($e['tipo'] === 'rubrica') {
      $asp_q = mysqli_query($conexion, "
        SELECT aspecto, puntaje, observacion 
        FROM rubrica_aspectos 
        WHERE id_evaluacion = {$e['id']}
      ");
      echo "<b>Aspectos:</b><br>";
      while ($asp = mysqli_fetch_assoc($asp_q)) {
        echo "- <i>{$asp['aspecto']}</i>: {$asp['puntaje']}";
        if ($asp['puntaje'] < 3) {
          echo " <small>(obs: " . htmlspecialchars($asp['observacion']) . ")</small>";
        }
        echo "<br>";
      }
    }
  }

  echo "</div>";
}

// Historial de acciones
echo "<hr><h5>📜 Historial de Acciones</h5>";
$hist_q = mysqli_query($conexion, "
  SELECT fecha, accion, descripcion 
  FROM historial_estados 
  WHERE id_py = $id_py AND id_periodo = $id_periodo
  ORDER BY fecha DESC
");

if (mysqli_num_rows($hist_q)) {
  echo "<ul>";
  while ($h = mysqli_fetch_assoc($hist_q)) {
    echo "<li><b>[{$h['fecha']}]</b> {$h['accion']}: " . htmlspecialchars($h['descripcion']) . "</li>";
  }
  echo "</ul>";
} else {
  echo "<p>No hay historial aún.</p>";
}
?>
