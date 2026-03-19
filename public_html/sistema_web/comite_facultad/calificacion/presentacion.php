<?php
require_once("../../componentes/db.php");
if (!isset($_GET['id_py']) || !is_numeric($_GET['id_py'])) {
    echo "<div class='alert alert-danger'>ID de proyecto inválido.</div>";
    exit;
}
$id_py = (int)$_GET['id_py'];
$q = mysqli_query($conexion, "SELECT * FROM proyectos WHERE id = $id_py");

if (!$q || mysqli_num_rows($q) === 0) {
    echo "<div class='alert alert-warning'>Proyecto no encontrado.</div>";
    exit;
}
$proyecto = mysqli_fetch_assoc($q);
function mostrarHTML($campo) {
  return $campo && trim($campo) !== ''
      ? "<div class='border p-2 bg-white rounded' style='overflow-x:auto;'>$campo</div>"
      : "<div class='text-danger'>Item no ingresado.</div>";
}
function mostrarTextoPlano($campo) {
  return $campo && trim($campo) !== ''
      ? "<span class='text-dark'>" . htmlspecialchars($campo) . "</span>"
      : "<div class='text-danger'>Item no ingresado.</div>";
}
?>
<style>
.contenedor-scroll { display: flex; position: relative; gap: 1rem; }
.flex-grow-1 { overflow-x: auto; }
.navegacion-lateral { max-width: 220px; min-width: 180px; flex-shrink: 0; padding: 1rem 0.5rem; border-right: 1px solid #dee2e6; background-color: #f8f9fa; height: calc(100vh - 180px); overflow-y: auto; position: sticky; top: 0; }
.navegacion-lateral a { display: block; margin-bottom: 0.75rem; color: #007bff; font-weight: 500; text-decoration: none; }
.navegacion-lateral a:hover { text-decoration: underline; }
.item-proyecto { border: 1px solid #dee2e6; padding: 1rem; margin-bottom: 1.5rem; background-color: #fff; }
.modal-body { max-height: 80vh; overflow-y: auto; position: relative; }
</style>

<div class="contenedor-scroll p-2">
  <!-- Barra de navegación -->
  <div class="navegacion-lateral">
      <!-- Generalidades -->
    <label style="font-weight: bold; display: block; margin-bottom: 8px;">I. GENERALIDADES:</label>
    <a href="#item1"><i class="fas fa-graduation-cap"></i> 1. Programa</a>
    <a href="#item2"><i class="fas fa-book"></i> 2. Proyecto</a>
    <a href="#item3"><i class="fas fa-leaf"></i> 3. ODS</a>
    <a href="#item4"><i class="fas fa-folder"></i> 4. Tipo</a>
    <a href="#item5"><i class="fas fa-users"></i> 5. Grupos</a>
    <a href="#item6"><i class="fas fa-bullseye"></i> 6. Necesidades</a>
    <a href="#item7"><i class="fas fa-building"></i> 7.1 Institución</a>
    <a href="#item72"><i class="fas fa-child"></i> 7.2 Población</a>
    <a href="#item8"><i class="fas fa-map-marker-alt"></i> 8. Lugar</a>
    <a href="#item9"><i class="fas fa-clock"></i> 9. Duración</a>
    <a href="#item10"><i class="fas fa-chart-line"></i> 10. Fases</a>
    <a href="#item11"><i class="fas fa-university"></i> 11. Disciplinar</a>
    <a href="#item12"><i class="fas fa-tools"></i> 12. Unidades</a>
    <a href="#item13"><i class="fas fa-user-friends"></i> 13. Responsables</a>
      <!-- Plan de Proyecto -->
    <label style="font-weight: bold; display: block; margin-bottom: 8px;">II. PLAN DE PROYECTO:</label>
    <a href="#ppitem1"><i class="fas fa-stethoscope"></i> 1. Diagnóstico</a>
<a href="#ppitem2"><i class="fas fa-balance-scale"></i> 2. Justificación</a>
<a href="#ppitem3"><i class="fas fa-bullseye"></i> 3. Objetivos</a>
<a href="#ppitem4"><i class="fas fa-flag-checkered"></i> 4. Metas</a>
<a href="#ppitem5"><i class="fas fa-calendar-alt"></i> 5. Cronograma</a>
<a href="#ppitem6"><i class="fas fa-project-diagram"></i> 6. Metodología</a>
<a href="#ppitem7"><i class="fas fa-clipboard-check"></i> 7. Entregables</a>
<a href="#ppitem8"><i class="fas fa-chart-pie"></i> 8. Impacto</a>
<a href="#ppitem9"><i class="fas fa-th"></i> 9. Matriz</a>
<a href="#ppitem10"><i class="fas fa-coins"></i> 10. Presupuesto</a>
      <!-- Anexos -->
      <label style="font-weight: bold; display: block; margin-bottom: 8px;">III. ANEXOS:</label>
<a href="#anitem1"><i class="fas fa-user-tie"></i> 1. Lista Docentes</a>
<a href="#anitem2"><i class="fas fa-user-graduate"></i> 2. Lista Alumnos</a>
<a href="#anitem3"><i class="fas fa-sitemap"></i> 3. Diagrama</a>
<a href="#anitem4"><i class="fas fa-file-signature"></i> 4. Compromiso ético</a>
<a href="#anitem5"><i class="fas fa-handshake"></i> 5. Carta Intención</a>
  </div>

  <!-- Contenido de información -->
  <div class="flex-grow-1 pr-3">
    <?php
// --- BLOQUE A INSERTAR ---
include('../../componentes/db.php');
$id_py = isset($_GET['id_py']) ? intval($_GET['id_py']) : 0;

$info_q = mysqli_query($conexion, "
SELECT u.nombres, u.apellidos, u.usuario, u.id_depa, d.id_facultad, p.p2 AS titulo
FROM usuarios u
JOIN usuarios_proyectos up ON up.id_usuario = u.id
JOIN proyectos p ON up.id_proyecto = p.id
JOIN departamentos d ON u.id_depa = d.id
WHERE p.id = $id_py AND up.activo = 1
LIMIT 1
");

$info = mysqli_fetch_assoc($info_q);
$nombre_completo = $info['nombres'] . ' ' . $info['apellidos'];
$usuario = $info['usuario'];
$titulo_proyecto = $info['titulo'];

$fac_q = mysqli_query($conexion, "SELECT nombre FROM facultades WHERE id = {$info['id_facultad']} LIMIT 1");
$nombre_facultad = ($fac = mysqli_fetch_assoc($fac_q)) ? $fac['nombre'] : 'Facultad no definida';

$dep_q = mysqli_query($conexion, "SELECT nombre FROM departamentos WHERE id = {$info['id_depa']} LIMIT 1");
$nombre_departamento = ($dep = mysqli_fetch_assoc($dep_q)) ? $dep['nombre'] : 'Departamento no definido';

$periodo_q = mysqli_query($conexion, "SELECT pe.nombre FROM proyectos_periodo pp JOIN periodos pe ON pp.id_periodo = pe.id WHERE pp.id_py = $id_py ORDER BY pe.fecha_inicio DESC LIMIT 1");

$periodo = mysqli_fetch_assoc($periodo_q);
$periodo_nombre = $periodo['nombre'] ?? 'Periodo no definido';

echo '<div style="border: 2px solid black; border-radius: 10px; margin-bottom: 20px;">';
echo '<table style="width: 100%; table-layout: fixed; border-collapse: collapse;">';
echo '<tr>';
echo '<td rowspan="2" style="background-color: #19804F; color: white; font-weight: bold; text-align: center; padding: 10px; width: 90px; border: none; word-wrap: break-word; vertical-align: middle;">
  <i class="fas fa-calendar-alt"></i><br>' . htmlspecialchars($periodo_nombre) . '</td>';
echo '<td colspan="2" style="border: none; font-weight: bold; word-break: break-word; padding: 4px;">' . htmlspecialchars($nombre_completo) . '</td>';
echo '<td colspan="2" style="border: none; font-style: italic; word-break: break-word; padding: 4px;">' . htmlspecialchars($titulo_proyecto) . '</td>';
echo '</tr>';
echo '<tr>';
echo '<td style="border: none; padding: 4px;">
  <span style="display: block; background-color: #6A00FF; color: white; padding: 6px; border-radius: 5px; text-align: center;">' . htmlspecialchars($nombre_facultad) . '</span>
</td>';
echo '<td style="border: none; padding: 4px;">
  <span style="display: block; background-color: #FFFF88; color: black; padding: 6px; border-radius: 5px; text-align: center;">' . htmlspecialchars($nombre_departamento) . '</span>
</td>';
echo '<td style="border: none; padding: 4px; text-align: center;"><b>Cod. Usuario:</b><br>' . htmlspecialchars($usuario) . '</td>';
echo '<td style="border: none; padding: 4px; text-align: center;"><b>Cod. Proyecto:</b><br>' . htmlspecialchars($id_py) . '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';
?>
<div id="item1" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-graduation-cap text-primary"></i> 1. Título del Programa</h5>
  <?= mostrarTextoPlano($proyecto['p1']) ?>
</div>

<div id="item2" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-book text-primary"></i> 2. Título del Proyecto</h5>
  <?= mostrarTextoPlano($proyecto['p2']) ?>
</div>

<div id="item3" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-leaf text-primary"></i> 3. Objetivo de Desarrollo Sostenible</h5>
  <div class="d-flex flex-wrap">
    <?php
    $ods_ids = array_map('intval', explode(',', $proyecto['p3']));
    $ods_ids = array_filter($ods_ids); // Elimina vacíos

    $ods_colores = [1=>'#e5243b',2=>'#dda63a',3=>'#4c9f38',4=>'#c5192d',5=>'#ff3a21',6=>'#26bde2',7=>'#fcc30b',8=>'#a21942',9=>'#fd6925',10=>'#dd1367',11=>'#fd9d24',12=>'#bf8b2e',13=>'#3f7e44',14=>'#0a97d9',15=>'#56c02b',16=>'#00689d',17=>'#19486a'];

    if (!empty($ods_ids)) {
      $ods_query = mysqli_query($conexion, "SELECT id, nombre FROM ods WHERE id IN (" . implode(',', $ods_ids) . ") ORDER BY id ASC");
      while ($ods = mysqli_fetch_assoc($ods_query)) {
        $id = (int)$ods['id'];
        $nombre = htmlspecialchars($ods['nombre']);
        $color = $ods_colores[$id] ?? '#007bff';

        echo "<span class='text-white px-2 py-1 rounded d-inline-block' 
                  style='background-color: $color; margin-right: 10px; margin-bottom: 10px; min-width: 200px; font-size: 0.85rem;'>
                $id. $nombre
              </span>";
      }
    } else {
      echo "<em class='text-muted'>No registrado</em>";
    }
    ?>
  </div>
</div>

<div id="item4" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-folder text-primary"></i> 4. Tipo de Proyecto</h5>
  <div class="d-flex flex-wrap">
    <?php
    $tipo_ids = array_map('intval', explode(',', $proyecto['p4']));
    $tipo_ids = array_filter($tipo_ids);

    if (!empty($tipo_ids)) {
      $query = mysqli_query($conexion, "SELECT id, nombre FROM tipos_proyecto WHERE id IN (" . implode(',', $tipo_ids) . ") ORDER BY id ASC");
      while ($tipo = mysqli_fetch_assoc($query)) {
        echo "<span class='text-white px-2 py-1 rounded d-inline-block' 
                     style='background-color: #0d6efd; margin-right: 10px; margin-bottom: 10px; min-width: 200px; font-size: 0.85rem;'>
                  {$tipo['id']}. " . htmlspecialchars($tipo['nombre']) . "
              </span>";
      }
    } else {
      echo "<em class='text-muted'>No registrado</em>";
    }
    ?>
  </div>
</div>

<div id="item5" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-users text-primary"></i> 5. Grupo(s) de Interés</h5>
  <?= mostrarHTML($proyecto['p5']) ?>
</div>

<div id="item6" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-bullseye text-primary"></i> 6. Necesidades de los Grupos</h5>
  <?= mostrarHTML($proyecto['p6']) ?>
</div>

<div id="item7" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-building text-primary"></i> 7.1 Institución Participante</h5>
  <?= mostrarHTML($proyecto['p7_1']) ?>
</div>

<div id="item72" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-child text-primary"></i> 7.2 Población Participante</h5>
  <?= mostrarHTML($proyecto['p7_2']) ?>
</div>

<div id="item8" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-map-marker-alt text-primary"></i> 8. Lugar de Ejecución</h5>

  <?php
    $campos = [
      'Sector'       => $proyecto['sector'] ?? null,
      'Caserío'      => $proyecto['caserio'] ?? null,
      'Distrito'     => $proyecto['distrito'] ?? null,
      'Provincia'    => $proyecto['provincia'] ?? null,
      'Departamento' => $proyecto['departamento'] ?? null
    ];

    $vacios = array_filter($campos, fn($v) => empty(trim($v)));

    if (count($vacios) === count($campos)) {
      echo "<div class='text-danger'>Item no ingresado.</div>";
    } else {
      echo "<div class='row g-3'>";
      foreach ($campos as $label => $valor) {
        echo "
        <div class='col-md-4'>
          <div class='p-3 rounded shadow-sm' style='background-color: #f8f9fa;'>
            <div class='text-muted' style='font-size: 0.85rem;'>$label</div>
            <div class='fw-semibold text-dark'>" . (!empty(trim($valor)) ? htmlspecialchars($valor) : "<span class='text-danger'>No ingresado</span>") . "</div>
          </div>
        </div>";
      }
      echo "</div>";
    }
  ?>
</div>

<div id="item9" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-clock text-primary"></i> 9. Duración del Proyecto</h5>

  <?php
function formatearFechaSegura($fechaTexto) {
    $dt = DateTime::createFromFormat('d/m/Y', $fechaTexto);
    if ($dt) {
        setlocale(LC_TIME, 'es_ES.UTF-8');
        return strftime('%e %B %Y', $dt->getTimestamp());
    }
    return "<span class='text-danger'>Fecha no válida</span>";
}

$inicio = $proyecto['fecha_inicio'] ?? null;
$fin = $proyecto['fecha_fin'] ?? null;

if ($inicio && $fin) {
  $f_inicio = formatearFechaSegura($inicio);
  $f_fin = formatearFechaSegura($fin);

  ?>

  <div class="d-flex flex-column flex-sm-row align-items-start gap-3 p-3 rounded shadow-sm" style="background-color: #f8f9fa;">
    <div class="text-center flex-fill">
      <div class="text-muted" style="font-size: 0.85rem;">Inicio</div>
      <div class="fw-semibold text-dark" style="font-size: 1.1rem;"><?= ucfirst($f_inicio) ?></div>
    </div>
    <div class="text-center flex-fill">
      <div class="text-muted" style="font-size: 0.85rem;">Fin</div>
      <div class="fw-semibold text-dark" style="font-size: 1.1rem;"><?= ucfirst($f_fin) ?></div>
    </div>
  </div>

  <?php
    } else {
      echo "<div class='text-danger'>Item no ingresado.</div>";
    }
  ?>
</div>

<div id="item10" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-chart-line text-primary"></i> 10. Fases del Proyecto</h5>
  <div style="background: #fff; border-radius: 15px; box-shadow: 0 4px 12px rgba(0,0,0,0.1); padding: 20px; overflow-x: auto;">
    <table class="table mb-0" style="min-width: 700px;">
      <thead style="background-color: #f1f3f5;">
      <tr style="text-align: center; font-weight: 600;"><th>Fase</th><th>Descripción</th><th>N° Semanas</th><th>N° Horas/Semana</th><th>Total Horas</th></tr>
      </thead>
      <tbody>
        <?php
        function calcHoras($semanas, $horas) {
          return (is_numeric($semanas) && is_numeric($horas)) ? $semanas * $horas : 0;
        }

        $fases = [
          ['nombre' => 'Planificación', 'desc' => $proyecto['planificacion'], 'sem' => $proyecto['p10_1s'], 'hrs' => $proyecto['p10_1h']],
          ['nombre' => 'Ejecución', 'desc' => $proyecto['ejecucion'], 'sem' => $proyecto['p10_2s'], 'hrs' => $proyecto['p10_2h']],
          ['nombre' => 'Monitoreo y Evaluación', 'desc' => $proyecto['monitoreo'], 'sem' => $proyecto['p10_3s'], 'hrs' => $proyecto['p10_3h']],
        ];

        $total_general = 0;

        foreach ($fases as $fase) {
          $total = calcHoras($fase['sem'], $fase['hrs']);
          $total_general += $total;
          echo "<tr style='text-align: center;'>";
          echo "<td style='vertical-align: middle; font-weight: 500;'>" . htmlspecialchars($fase['nombre']) . "</td>";
          echo "<td style='text-align: left;'>" . htmlspecialchars($fase['desc']) . "</td>";
          echo "<td>" . (int)$fase['sem'] . "</td>";
          echo "<td>" . (int)$fase['hrs'] . "</td>";
          echo "<td>" . $total . "</td>";
          echo "</tr>";
        }
        ?>
      </tbody>
      <tfoot>
        <tr style="font-weight: bold; text-align: center; background-color: #f8f9fa;">
          <td colspan="4">Total general de horas</td>
          <td><?= $total_general ?></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php
// Diccionario de niveles disciplinares
$nivelesDisciplinar = ['1' => 'Disciplinar', '2' => 'Interdisciplinar', '3' => 'Interfacultativo'];

// Verificar si el valor está definido y mostrar el nombre
$nivelTexto = isset($proyecto['disciplinar'], $nivelesDisciplinar[$proyecto['disciplinar']]) 
              ? $nivelesDisciplinar[$proyecto['disciplinar']] 
              : 'No especificado';
?>

<div id="item11" class="item-proyecto"> 
  <h5 class="text-primary"><i class="fas fa-university text-primary"></i> 11. Nivel Disciplinar</h5>
  <span class="btn btn-light border border-secondary shadow-sm px-4 py-1 rounded-pill" style="font-weight: 500; font-size: 1rem; pointer-events: none;">
    <?= htmlspecialchars($nivelTexto) ?>
  </span>
</div>

<?php
include('../componentes/db.php');

$idFacultad = $proyecto['facultad'] ?? null;
$idEscuela = $proyecto['programa_estudios'] ?? null;
$idDepartamento = $proyecto['departamento_academico'] ?? null;

function obtenerNombre($conexion, $tabla, $columnaNombre, $columnaID, $id) {
    $stmt = $conexion->prepare("SELECT $columnaNombre FROM $tabla WHERE $columnaID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($nombre);
    $stmt->fetch();
    $stmt->close();
    return $nombre ?: 'No especificado';
}

$nombreFacultad = $idFacultad ? obtenerNombre($conexion, 'facultades', 'nombre', 'id', $idFacultad) : 'No especificado';
$nombreEscuela = $idEscuela ? obtenerNombre($conexion, 'escuelas', 'nombre_escuela', 'id_escuela', $idEscuela) : 'No especificado';
$nombreDepartamento = $idDepartamento ? obtenerNombre($conexion, 'departamentos', 'nombre', 'id', $idDepartamento) : 'No especificado';
?>

<div id="item12" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-tools text-primary"></i> 12. Unidades Ejecutoras</h5>

  <div class="table-responsive mt-3">
    <table class="table table-borderless" style="border-radius: 12px; overflow: hidden; background: #f8f9fa; box-shadow: 0 2px 8px rgba(0,0,0,0.05);">
      <thead class="thead-light">
        <tr style="background: #e9ecef;">
          <th style="padding: 12px;">Facultad</th>
          <th style="padding: 12px;">Programa de Estudios</th>
          <th style="padding: 12px;">Departamento Académico</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($nombreFacultad) ?></td>
          <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($nombreEscuela) ?></td>
          <td style="padding: 12px; font-weight: 500;"><?= htmlspecialchars($nombreDepartamento) ?></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<div id="item13" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-user-friends text-primary"></i> 13. Responsables del Proyecto</h5>
  <div class="mb-2">
  <h6 class="text-primary">
    <i class="fas fa-user-tie text-primary"></i> 13.1 Coordinador del Equipo
  </h6>
  <span class="d-inline-flex align-items-center px-3 py-2 rounded bg-light text-dark fw-medium shadow-sm">
    <?= mostrarTextoPlano($proyecto['coordinador']) ?>
  </span>
</div>

  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-chalkboard-teacher text-primary"></i> 13.2 Equipo de Docentes</h6>
    <?= mostrarHTML($proyecto['integrantes_docentes']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-user-graduate text-primary"></i> 13.3 Equipo de Estudiantes</h6>
    <?= mostrarHTML($proyecto['delegados_estudiantes']) ?>
  </div>
</div>
<!--PLAN DE PROYECTO -->
<div id="ppitem1" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-stethoscope"></i> 1. Diagnóstico</h5>
  <?= mostrarHTML($proyecto['diagnostico']) ?>
</div>
<div id="ppitem2" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-balance-scale"></i> 2. Justificación del proyecto</h5>
  <?= mostrarHTML($proyecto['justificacion']) ?>
</div>
<div id="ppitem3" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-bullseye"></i> 3. Objetivos del proyecto</h5>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-bullseye"></i> 3.1. Objetivo General</h6>
    <?= mostrarHTML($proyecto['general']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-bullseye"></i> 3.2. Objetivos Específicos</h6>
    <?= mostrarHTML($proyecto['especificos']) ?>
  </div>
</div>
<div id="ppitem4" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-flag-checkered"></i> 4. Metas por semestre</h5>
  <?= mostrarHTML($proyecto['metas']) ?>
</div>
<div id="ppitem5" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-flag-checkered"></i> 5. Cronograma de actividades</h5>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-flag-checkered"></i> Cronograma actual</h6>
    <?= mostrarHTML($proyecto['cronograma1']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-flag-checkered"></i> Cronogramas secundarios</h6>
    <?= mostrarHTML($proyecto['cronograma2']) ?>
  </div>
</div>
<div id="ppitem6" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-project-diagram"></i> 6. Metodología</h5>
  <?= mostrarHTML($proyecto['metodologia']) ?>
</div>
<div id="ppitem7" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-clipboard-check"></i> 7. Entregables a los beneficiarios</h5>
  <?= mostrarHTML($proyecto['entregables']) ?>
</div>
<div id="ppitem8" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-chart-pie"></i> 8. Tipo de impacto</h5>
  <?= mostrarHTML($proyecto['impacto']) ?>
</div>
<div id="ppitem9" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-th"></i> 9. Matriz de Indicadores de Impacto</h5>
  <?= mostrarHTML($proyecto['matriz']) ?>
</div>

<div id="ppitem10" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-coins"></i> 10. Presupuesto</h5>
  <h6 class="text-primary"><i class="fas fa-coins"></i> 10.1 Bienes</h6>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-coins"></i> Disponibles</h6>
    <?= mostrarHTML($proyecto['pre_dis']) ?>
  </div>
  <div class="mb-2">
  <h6 class="text-primary"><i class="fas fa-coins"></i> No Disponibles</h6>
    <?= mostrarHTML($proyecto['pre_nodis']) ?>
  </div>
  <h6 class="text-primary"><i class="fas fa-coins"></i> 10.2 Servicios</h6>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-coins"></i> Disponibles</h6>
    <?= mostrarHTML($proyecto['ser_dis']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-coins"></i> No Disponibles</h6>
    <?= mostrarHTML($proyecto['ser_nodis']) ?>
  </div>
  <h6 class="text-primary"><i class="fas fa-coins"></i> 10.3 Resumen</h6>
  <div class="mb-2">
    <?= mostrarHTML($proyecto['resumen']) ?>
  </div>
</div>
<div id="anitem6" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-folder-open text-primary"></i> III. ANEXOS</h5>
  <div id="contenedor-archivos"></div>
</div>


  </div>
</div>
<script>
  document.addEventListener("DOMContentLoaded", function () {
    const contenedor = document.querySelector('.contenedor-scroll');
    const enlaces = document.querySelectorAll('.navegacion-lateral a');

    enlaces.forEach(enlace => {
      enlace.addEventListener('click', function (e) {
        e.preventDefault();
        const destino = document.querySelector(this.getAttribute('href'));
        if (destino) {
          const topOffset = destino.offsetTop - contenedor.offsetTop;
          contenedor.scrollTo({
            top: topOffset,
            left: 0, // ✅ Fuerza la vista alineada a la izquierda
            behavior: 'smooth'
          });
        }
      });
    });
  });
</script>


