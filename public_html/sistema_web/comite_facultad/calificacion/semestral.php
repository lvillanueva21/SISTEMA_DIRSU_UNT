<!-- Antes de malograr -->
<?php
require_once("../../componentes/db.php");

if (!isset($_GET['id_py']) || !is_numeric($_GET['id_py'])) {
    echo "<div class='alert alert-danger'>ID de proyecto inválido.</div>";
    exit;
}

$id_py = (int)$_GET['id_py'];
$q = mysqli_query($conexion, "SELECT * FROM proyectos_finales WHERE id_py = $id_py");

if (!$q || mysqli_num_rows($q) === 0) {
    echo "<div class='alert alert-warning'>Informe semestral no encontrado.</div>";
    exit;
}

$informe = mysqli_fetch_assoc($q);

// Funciones de presentación
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
.navegacion-lateral {
  max-width: 230px; min-width: 180px; flex-shrink: 0;
  padding: 1rem 0.5rem; border-right: 1px solid #dee2e6;
  background-color: #f8f9fa;
  height: calc(100vh - 180px); overflow-y: auto; position: sticky; top: 0;
}
.navegacion-lateral a {
  display: block; margin-bottom: 0.75rem;
  color: #007bff; font-weight: 500; text-decoration: none;
}
.navegacion-lateral a:hover { text-decoration: underline; }
.item-proyecto {
  border: 1px solid #dee2e6;
  padding: 1rem;
  margin-bottom: 1.5rem;
  background-color: #fff;
}
.modal-body { max-height: 80vh; overflow-y: auto; position: relative; }
</style>

<div class="contenedor-scroll p-2">
<!-- Navegación lateral -->
<div class="navegacion-lateral">
  <label style="font-weight: bold;">I. GENERALIDADES</label>
  <a href="#isitem1"><i class="fas fa-graduation-cap"></i> 1.1 Programa</a>
  <a href="#isitem2"><i class="fas fa-book"></i> 1.2 Proyecto</a>
  <a href="#isitem3"><i class="fas fa-leaf"></i> 1.3 ODS</a>
  <a href="#isitem4"><i class="fas fa-user-friends"></i> 1.4 Integrantes</a>
  <a href="#isitem5"><i class="fas fa-bullseye"></i> 1.5 OMI</a>
  <a href="#isitem6"><i class="fas fa-map-marker-alt"></i> 1.6 Lugar</a>
  <a href="#isitem7"><i class="fas fa-building"></i> 1.7 Beneficiarios</a>
  <a href="#isitem8"><i class="fas fa-clock"></i> 1.8 Duración</a>

  <label style="font-weight: bold; margin-top: 1rem;">II. RESULTADOS</label>
  <a href="#isitem9"><i class="fas fa-align-left"></i> 2.1 Resumen</a>
  <a href="#isitem10"><i class="fas fa-tasks"></i> 2.2 Actividades</a>
  <a href="#isitem11"><i class="fas fa-poll"></i> 2.3 Resultados</a>
  <a href="#isitem12"><i class="fas fa-comments"></i> 2.4 Comentarios</a>
  <a href="#isitem13"><i class="fas fa-check"></i> 2.5 Conclusiones</a>
  <a href="#isitem14"><i class="fas fa-chart-pie"></i> 2.6 Análisis</a>
  <a href="#isitem15"><i class="fas fa-lightbulb"></i> 2.7 Recomendaciones</a>
  <a href="#isitem16"><i class="fas fa-book"></i> 2.8 Fuentes</a>
  <a href="#isitem17"><i class="fas fa-folder-open"></i> 2.9 Anexos</a>

  <label style="font-weight: bold; margin-top: 1rem;">III. CARGA HORARIA</label>
  <a href="#isitem18"><i class="fas fa-clock"></i> 3.1 Horas de Proyección</a>
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
echo '<td colspan="3" style="border: none; font-style: italic; word-break: break-word; padding: 4px;">' . htmlspecialchars($titulo_proyecto) . '</td>';
echo '</tr>';
echo '<tr>';
echo '<td style="border: none; padding: 4px;">
  <span style="display: block; background-color: #6A00FF; color: white; padding: 6px; border-radius: 5px; text-align: center;">' . htmlspecialchars($nombre_facultad) . '</span>
</td>';
echo '<td style="border: none; padding: 4px;">
  <span style="display: block; background-color: #FFFF88; color: black; padding: 6px; border-radius: 5px; text-align: center;">' . htmlspecialchars($nombre_departamento) . '</span>
</td>';
echo '<td style="border: none; padding: 4px; text-align: center;"><b>Cod. Usuario:</b><br>' . htmlspecialchars($usuario) . '</td>';
echo '<td style="border: none; padding: 4px; text-align: center;" data-noprint="1">
        <button type="button" id="btnPrintSemestral" class="btn btn-outline-secondary btn-sm" style="font-weight:600;">
          <i class="fas fa-print"></i> Imprimir
        </button>
      </td>';
echo '<td style="border: none; padding: 4px; text-align: center;"><b>Cod. Proyecto:</b><br>' . htmlspecialchars($id_py) . '</td>';
echo '</tr>';
echo '</table>';
echo '</div>';
?>

  <div class="flex-grow-1 pr-3">
<!-- I. GENERALIDADES -->

<div id="isitem1" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-graduation-cap text-primary"></i> 1.1 Título del Programa</h5>
  <?= mostrarTextoPlano($informe['programa']) ?>
</div>

<div id="isitem2" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-book text-primary"></i> 1.2 Título del Proyecto</h5>
  <?= mostrarTextoPlano($informe['titulo']) ?>
</div>

<div id="isitem3" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-leaf text-primary"></i> 1.3 Objetivos de Desarrollo Sostenible</h5>
  <?php
    $ods_ids = array_map('intval', explode(',', $informe['ods']));
    $ods_ids = array_filter($ods_ids);
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
      echo "<div class='text-danger'>Item no ingresado.</div>";
    }
  ?>
</div>

<div id="isitem4" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-user-friends text-primary"></i> 1.4 Integrantes del Proyecto</h5>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-user-tie text-primary"></i> Coordinador</h6>
    <?= mostrarHTML($informe['coordinador']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-chalkboard-teacher text-primary"></i> Docentes</h6>
    <?= mostrarHTML($informe['integrantes']) ?>
  </div>
  <div class="mb-2">
    <h6 class="text-primary"><i class="fas fa-user-graduate text-primary"></i> Estudiantes</h6>
    <?= mostrarHTML($informe['estudiantes']) ?>
  </div>
</div>

<div id="isitem5" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-bullseye text-primary"></i> 1.5 Objetivos, Metas e Indicadores</h5>
  <?= mostrarHTML($informe['omi']) ?>
</div>

<div id="isitem6" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-map-marker-alt text-primary"></i> 1.6 Lugar de Ejecución</h5>
  <?= mostrarHTML($informe['lugar']) ?>
</div>

<div id="isitem7" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-building text-primary"></i> 1.7 Institución y Población Beneficiada</h5>
  <?= mostrarHTML($informe['beneficiados']) ?>
</div>

<div id="isitem8" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-clock text-primary"></i> 1.8 Duración del Proyecto o Actividad</h5>
  <?php
    $inicio = trim($informe['fecha_inicio']);
    $fin = trim($informe['fecha_fin']);

    if ($inicio && $fin) {
      echo "<div class='row g-3'>
              <div class='col-md-6'>
                <div class='p-3 rounded shadow-sm' style='background-color: #f8f9fa;'>
                  <div class='text-muted' style='font-size: 0.85rem;'>Inicio</div>
                  <div class='fw-semibold text-dark'>" . htmlspecialchars($inicio) . "</div>
                </div>
              </div>
              <div class='col-md-6'>
                <div class='p-3 rounded shadow-sm' style='background-color: #f8f9fa;'>
                  <div class='text-muted' style='font-size: 0.85rem;'>Fin</div>
                  <div class='fw-semibold text-dark'>" . htmlspecialchars($fin) . "</div>
                </div>
              </div>
            </div>";
    } else {
      echo "<div class='text-danger'>Item no ingresado.</div>";
    }
  ?>
</div>
<!-- II. RESULTADOS -->

<div id="isitem9" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-align-left text-primary"></i> 2.1 Resumen</h5>
  <?= mostrarHTML($informe['resumen']) ?>
</div>

<div id="isitem10" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-tasks text-primary"></i> 2.2 Actividades Ejecutadas</h5>
  <?= mostrarHTML($informe['actividades']) ?>
</div>

<div id="isitem11" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-chart-bar text-primary"></i> 2.3 Resultados</h5>
  <?= mostrarHTML($informe['resultados']) ?>
</div>

<div id="isitem12" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-comments text-primary"></i> 2.4 Comentarios o Discusión de Resultados</h5>
  <?= mostrarHTML($informe['comentarios']) ?>
</div>

<div id="isitem13" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-check-circle text-primary"></i> 2.5 Conclusiones</h5>
  <?= mostrarHTML($informe['conclusiones']) ?>
</div>

<div id="isitem14" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-chart-pie text-primary"></i> 2.6 Análisis de Impacto del Proyecto Ejecutado</h5>
  <?= mostrarHTML($informe['analisis']) ?>
</div>

<div id="isitem15" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-lightbulb text-primary"></i> 2.7 Recomendaciones</h5>
  <?= mostrarHTML($informe['recomendaciones']) ?>
</div>

<div id="isitem16" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-book-open text-primary"></i> 2.8 Fuentes Consultadas</h5>
  <?= mostrarHTML($informe['fuentes']) ?>
</div>

<div id="isitem17" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-folder-open text-primary"></i> 2.9 Anexos (Fuentes de verificación)</h5>
  <?= mostrarHTML($informe['anexos']) ?>
</div>
<!-- III. CUMPLIMIENTO DE LA CARGA HORARIA -->

<div id="isitem18" class="item-proyecto">
  <h5 class="text-primary"><i class="fas fa-hourglass-half text-primary"></i> 3.1 Número de horas de proyección social o extensión universitaria</h5>
  <?= mostrarHTML($informe['carga']) ?>
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
        contenedor.scrollTo({ top: topOffset, behavior: 'smooth' });
      }
    });
  });
});
</script>
<script>
(function(){
  var btn = document.getElementById('btnPrintSemestral');
  if(!btn) return;

  btn.addEventListener('click', function(){
    var modal = document.getElementById('contenidoSemestral');
    var content = modal ? (modal.querySelector('.flex-grow-1.pr-3') || modal) : document.body;

    var css = '<style>'
      + '@page{size:A4 portrait;margin:10mm;}'
      + 'html,body{margin:0;padding:0;}'
      + 'body{font-family:system-ui,Segoe UI,Roboto,Helvetica,Arial,sans-serif;}'
      + '.print-root{padding:12px;}'
      + '.print-root *{box-sizing:border-box;max-width:100% !important;min-width:0 !important;}'
      + '.print-root table{width:100% !important;max-width:100% !important;table-layout:fixed !important;border-collapse:collapse !important;border-spacing:0 !important;page-break-inside:auto !important;}'
      + '.print-root col,.print-root colgroup{width:auto !important;}'
      + '.print-root th,.print-root td{width:auto !important;min-width:0 !important;padding:4px !important;border:1px solid #ddd !important;vertical-align:top !important;white-space:normal !important;word-break:break-word !important;overflow-wrap:anywhere !important;hyphens:auto !important;font-size:12px !important;line-height:1.25 !important;}'
      + '.navegacion-lateral{display:none !important;}'
      + '.contenedor-scroll{display:block !important;}'
      + '.flex-grow-1{max-width:100% !important;}'
      + '</style>';

    var html = ''
      + '<!DOCTYPE html><html lang="es"><head><meta charset="utf-8"><title>Imprimir</title>'
      + css
      + '</head><body><div class="print-root">'
      + content.innerHTML
      + '</div>'
      + '<script>(function(){'
        + 'var root=document.querySelector(".print-root"); if(!root) return;'
        + 'root.querySelectorAll("table").forEach(function(tb){'
          + 'tb.removeAttribute("width");'
          + 'tb.style.width="100%";'
          + 'tb.style.maxWidth="100%";'
          + 'tb.style.tableLayout="fixed";'
        + '});'
        + 'root.querySelectorAll("th,td").forEach(function(c){'
          + 'c.removeAttribute("width");'
          + 'c.style.width="auto";'
          + 'c.style.minWidth="0";'
          + 'c.style.whiteSpace="normal";'
          + 'c.style.wordBreak="break-word";'
        + '});'
        + 'root.querySelectorAll("[style]").forEach(function(el){'
          + 'if(el.style.width) el.style.width="auto";'
          + 'if(el.style.minWidth) el.style.minWidth="0";'
          + 'if(el.style.whiteSpace==="nowrap") el.style.whiteSpace="normal";'
          + 'if(parseInt(el.style.maxWidth)>0) el.style.maxWidth="100%";'
        + '});'
      + '})();<\/script>'
      + '</body></html>';

    var win = window.open('', '_blank');
    if(!win){ alert('Permite ventanas emergentes para imprimir.'); return; }
    win.document.open();
    win.document.write(html);
    win.document.close();
    win.focus();
    setTimeout(function(){ win.print(); }, 400);
  });
})();
</script>

