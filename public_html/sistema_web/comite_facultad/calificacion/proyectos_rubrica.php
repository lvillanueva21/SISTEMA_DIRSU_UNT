<?php
require_once("../componentes/db.php");                 // misma ruta que la versión Cotejo
require_once("../componentes/revision/filtro_rol_proyecto.php");

$oficina_actual = isset($oficina_actual) ? $oficina_actual : null;   // viene desde rubrica.php

/* ──────────────────────────────
   1.  Catálogos y filtros base
   ────────────────────────────── */

// Períodos
$periodos_q = mysqli_query($conexion,"SELECT id,nombre FROM periodos ORDER BY fecha_inicio DESC");
$periodos = [];
$periodo_activo_id = null;
$nombre_periodo_activo = "-- Período activo --";

// Facultades
$facultades_q = mysqli_query($conexion,"SELECT id,nombre FROM facultades ORDER BY id ASC");
$facultades=[];
while($f = mysqli_fetch_assoc($facultades_q)){ $facultades[]=$f; }

// Departamentos (según rol)
$id_rol = $_SESSION['id_rol'];
$id_facultad_usuario = getFacultadUsuario($conexion);
$departamentos=[];
if(in_array($id_rol,[3,5]) && $id_facultad_usuario!==null){
   $departamentos_q = mysqli_query($conexion,"SELECT id,nombre,id_facultad FROM departamentos WHERE id_facultad=$id_facultad_usuario ORDER BY nombre");
}else{
   $departamentos_q = mysqli_query($conexion,"SELECT id,nombre,id_facultad FROM departamentos ORDER BY nombre");
}
while($d=mysqli_fetch_assoc($departamentos_q)){ $departamentos[]=$d; }

// ODS
$ods_q = mysqli_query($conexion,"SELECT id,nombre FROM ods ORDER BY id");
$ods_list=[];
while($o=mysqli_fetch_assoc($ods_q)){ $ods_list[]=$o; }

/* ──────────────────────────────
   2.  Parámetros recibidos
   ────────────────────────────── */
$filtro_facultad     = isset($_GET['facultad_id'])     ? (int)$_GET['facultad_id']     : 0;
$filtro_departamento = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : 0;
$estado              = (isset($_GET['estado']) && $_GET['estado']!=='') ? (int)$_GET['estado'] : null;
$filtro_ods          = isset($_GET['ods_id']) ? (int)$_GET['ods_id'] : 0;
$filtros_visibles    = getFiltrosPermitidos();

function mostrarFiltro($n){ global $filtros_visibles; return in_array($n,$filtros_visibles); }

/* Si solo llega departamento, calculamos facultad */
if($filtro_facultad===0 && $filtro_departamento!==0){
   $q = mysqli_query($conexion,"SELECT id_facultad FROM departamentos WHERE id=$filtro_departamento LIMIT 1");
   if($row=mysqli_fetch_assoc($q)){ $filtro_facultad=(int)$row['id_facultad']; }
}

/* ──────────────────────────────
   3.  Período activo / seleccionado
   ────────────────────────────── */
while($p=mysqli_fetch_assoc($periodos_q)){ $periodos[]=$p; }
$activo_q = mysqli_query($conexion,"SELECT id,nombre FROM periodos WHERE activo=1 LIMIT 1");
if($a=mysqli_fetch_assoc($activo_q)){ $periodo_activo_id=$a['id']; $nombre_periodo_activo=$a['nombre']; }

$periodo_seleccionado = (isset($_GET['periodo_id']) && $_GET['periodo_id']!=='') ? (int)$_GET['periodo_id'] : $periodo_activo_id;

/* ──────────────────────────────
   4.  Proyectos visibles
   ────────────────────────────── */
$proyectos_permitidos = getProyectosVisibles($conexion,$periodo_seleccionado);
$filtro_texto = isset($_GET['buscar_texto']) ? trim($_GET['buscar_texto']) : '';
$ids_permitidos = empty($proyectos_permitidos) ? '0' : implode(',',$proyectos_permitidos);

$condiciones = "p.id IN ($ids_permitidos)";
if($filtro_facultad!==0)         $condiciones .= " AND f.id=$filtro_facultad";
if($filtro_departamento!==0)     $condiciones .= " AND d.id=$filtro_departamento";
if($filtro_texto!==''){
  $esc = mysqli_real_escape_string($conexion,$filtro_texto);
  if(is_numeric($filtro_texto)){
     $condiciones .= " AND (p.id=$filtro_texto OR u.usuario LIKE '%$esc%')";
  }else{
     $condiciones .= " AND (u.usuario LIKE '%$esc%' OR u.nombres LIKE '%$esc%' OR u.apellidos LIKE '%$esc%' OR p.p2 LIKE '%$esc%')";
  }
}
if(!is_null($estado))            $condiciones .= " AND p.estado=$estado";
if($filtro_ods!==0)              $condiciones .= " AND FIND_IN_SET($filtro_ods,p.p3)";

/* Total */
$total_q = mysqli_query($conexion,"
   SELECT COUNT(*) AS total
   FROM proyectos p
   JOIN usuarios_proyectos up ON up.id_proyecto = p.id
   JOIN usuarios u ON u.id = up.id_usuario
   JOIN proyectos_periodo pp ON pp.id_py = p.id
   JOIN revisiones_proyectos rp ON rp.id_py = p.id AND rp.id_periodo = pp.id_periodo
   LEFT JOIN departamentos d ON d.id = u.id_depa
   LEFT JOIN facultades f ON f.id = d.id_facultad
   WHERE $condiciones
     AND up.activo = 1
");

$total = (int)mysqli_fetch_assoc($total_q)['total'];
?>
<!-- ========== FORMULARIO DE FILTROS ========== -->
<form method="GET" class="mb-3">
 <div class="row">

  <!-- zona de filtros -->
  <div class="col-md-10"><div class="row">
   <?php if(mostrarFiltro('periodo')): ?>
   <div class="col-md-4 mb-2">
     <label>Período:</label>
     <select name="periodo_id" class="form-control">
       <?php foreach($periodos as $p): ?>
         <option value="<?= $p['id'] ?>" <?= $periodo_seleccionado==$p['id']?'selected':'' ?>><?= htmlspecialchars($p['nombre']) ?></option>
       <?php endforeach; ?>
     </select>
   </div><?php endif; ?>

   <?php if(mostrarFiltro('facultad')): ?>
   <div class="col-md-4 mb-2">
     <label>Facultad:</label>
     <select name="facultad_id" id="facultad_id" class="form-control">
       <?php foreach($facultades as $fac): ?>
         <option value="<?= $fac['id'] ?>" <?= $filtro_facultad==$fac['id']?'selected':'' ?>><?= htmlspecialchars($fac['nombre']) ?></option>
       <?php endforeach; ?>
     </select>
   </div><?php endif; ?>

   <?php if(mostrarFiltro('departamento')): ?>
   <div class="col-md-4 mb-2">
     <label>Departamento:</label>
     <select name="departamento_id" id="departamento_id" class="form-control">
       <?php if(in_array($_SESSION['id_rol'],[3,5])): ?>
         <option value="0" <?= $filtro_departamento===0?'selected':'' ?>>Todos</option>
       <?php endif; ?>
       <?php foreach($departamentos as $dep): ?>
         <option value="<?= $dep['id'] ?>" data-id_facultad="<?= $dep['id_facultad'] ?>"
                 <?= $filtro_departamento==$dep['id']?'selected':'' ?>
                 <?= ($filtro_facultad && $dep['id_facultad']!=$filtro_facultad)?'style=display:none;':'' ?>>
           <?= htmlspecialchars($dep['nombre']) ?>
         </option>
       <?php endforeach; ?>
     </select>
   </div><?php endif; ?>

   <?php if(mostrarFiltro('estado')): ?>
   <div class="col-md-4 mb-2">
     <label>Estado:</label>
     <select name="estado" class="form-control">
       <option value="">Todos</option>
       <option value="0" <?= isset($_GET['estado'])&&$_GET['estado']==='0'?'selected':'' ?>>En Espera</option>
       <option value="1" <?= isset($_GET['estado'])&&$_GET['estado']==='1'?'selected':'' ?>>En Revisión</option>
       <option value="2" <?= isset($_GET['estado'])&&$_GET['estado']==='2'?'selected':'' ?>>Aprobado</option>
     </select>
   </div><?php endif; ?>

   <?php if(mostrarFiltro('texto')): ?>
   <div class="col-md-4 mb-2">
   <label for="buscar_texto">Texto o código:</label>
     <input type="text" name="buscar_texto" class="form-control" placeholder="ID, usuario, nombre o título"
            value="<?= htmlspecialchars($filtro_texto) ?>">
   </div><?php endif; ?>
   <!-- 🔵 SECCIÓN LISTAS DE RÚBRICA -->
<div class="row justify-content-center align-items-center">
  <div class="col-12 mb-1 text-center">
    <label><b>Formatos de Rúbrica:</b></label>
  </div>

  <div class="col-auto mb-1 pr-1">
    <a href="https://docs.google.com/document/d/1OMSd0CKJsNAOMo3q3UPyDLVve2aAHBQP/edit?tab=t.0"
       target="_blank"
       class="btn btn-info btn-sm text-white"
       style="font-size: 0.7rem; padding: 0.25rem 0.5rem; white-space: nowrap;"
       title="Lista de Cotejo para Presentación de Proyecto 2025-I">
      <i class="fas fa-external-link-alt mr-1"></i> Presentación<br>Proyecto 2025-I
    </a>
  </div>

  <div class="col-auto mb-1">
    <a href="https://docs.google.com/document/d/1Mbee2jAqKR_rApJrfkANbt5bPn2ljuUr/edit?tab=t.0"
       target="_blank"
       class="btn btn-success btn-sm text-white"
       style="font-size: 0.7rem; padding: 0.25rem 0.5rem; white-space: nowrap;"
       title="Lista de Cotejo para Informe Semestral 2024-II">
      <i class="fas fa-external-link-alt mr-1"></i> Informe<br>Semestral 2024-II
    </a>
  </div>
</div>
<!-- 🔵 FIN SECCIÓN LISTAS DE RÚBRICA -->
  </div></div>

  <!-- total y botones -->
  <div class="col-md-2 d-flex flex-column align-items-center">
    <div class="row w-100">
      <div class="col-12 text-center mb-1">
        <label class="d-block font-digital bg-white p-1" style="border-radius:2px;color:#0d6efd;">Resultados:<br><?= $total ?></label>
      </div>
      <div class="col-6 mb-2"><button class="btn btn-primary btn-sm w-100"><i class="fas fa-filter"></i></button></div>
      <div class="col-6 mb-2"><a href="?" class="btn btn-danger btn-sm w-100"><i class="fas fa-broom"></i></a></div>
      <div class="col-6 text-center">
  <button type="button" class="btn btn-secondary btn-sm w-100 h-100 text-white" disabled title="Imprimir">
    <i class="fas fa-print"></i>
  </button>
</div>
<div class="col-6 text-center">
  <button type="button" class="btn btn-success btn-sm w-100 h-100 text-white" disabled title="Exportar a Excel">
    <i class="fas fa-file-excel"></i>
  </button>
</div>
    </div>
  </div>
 </div>
</form>

<?php
/* ──────────────────────────────
   5.  Consulta paginada
   ────────────────────────────── */
$limite = 10;
$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$inicio = ($pagina-1)*$limite;
$contador = $inicio+1;
$paginas = max(1,ceil($total/$limite));

$query = mysqli_query($conexion,"
 SELECT p.id,p.p2,p.p1,p.coordinador,p.fecha_inicio,p.fecha_fin,
        p.estado,
        u.id_depa,u.nombres,u.apellidos,u.usuario,
        d.nombre AS departamento, f.nombre AS facultad,
        ev.estado AS estado_rubrica, ev.fecha_limite,
        rp.oficina_actual,rp.fecha_solicitud
 FROM proyectos p
 JOIN usuarios_proyectos up ON up.id_proyecto = p.id
 JOIN usuarios u ON u.id = up.id_usuario
 JOIN proyectos_periodo pp ON pp.id_py = p.id
 JOIN revisiones_proyectos rp ON rp.id_py=p.id AND rp.id_periodo=pp.id_periodo
 LEFT JOIN departamentos d ON d.id = u.id_depa
 LEFT JOIN facultades f ON f.id = d.id_facultad
 LEFT JOIN evaluaciones ev ON ev.id_py = p.id
                           AND ev.id_periodo = $periodo_seleccionado
                           AND ev.oficina = 'pcf' AND ev.tipo = 'rubrica'
 WHERE $condiciones
   AND up.activo = 1
 ORDER BY p.id ASC
 LIMIT $inicio,$limite");

?>
<!-- ========== TABLA ========== -->
<div id="tablaProyectos">
 <div class="table-responsive">
  <table class="table table-bordered table-hover table-sm">
   <thead class="thead-dark">
    <tr>
      <th>#</th><th>ID PY</th><th>Título Proyecto</th><th>Estado</th>
      <th>Rúbrica</th><th>Coordinador</th><th>Acciones</th>
    </tr>
   </thead>
   <tbody>
<?php if(mysqli_num_rows($query)): while($r=mysqli_fetch_assoc($query)): ?>
   <tr data-toggle="collapse" data-target="#det<?= $r['id'] ?>" class="accordion-toggle" style="cursor:pointer;">
     <td><?= $contador++ ?></td>
     <td><?= $r['id'] ?></td>
     <td><?= $r['p2']!==''?htmlspecialchars($r['p2']):'<b style="color:#8B0000;">No registrado</b>' ?></td>

     <!-- Estado general -->
     <td>
       <?php
         $estado_int = (int)$r['estado'];
         $ofi_act    = $r['oficina_actual']??'';
         $ofiMap = ['pcf'=>'#0275D8','dd'=>'#F0AD4E','df'=>'#5BC0DE','rsu'=>'#5CB85C'];
         $oficinas = ['pcf' => ['nombre' => 'Oficina del Comité de Facultad', 'bg' => '#0275D8', 'color' => 'white'],
             'dd'  => ['nombre' => 'Oficina de la Dirección de Departamento', 'bg' => '#F0AD4E', 'color' => 'black'],
             'df'  => ['nombre' => 'Oficina del Decanato de Facultad', 'bg' => '#5BC0DE', 'color' => 'black'],
             'rsu' => ['nombre' => 'Oficina de la Dirección de RSU - UNT', 'bg' => '#5CB85C', 'color' => 'white']];

if ($estado_int === 0) {
  echo '<span class="badge badge-secondary">En Proceso</span>';
} elseif ($estado_int === 2) {
  echo '<span class="badge badge-success">Aprobado</span>';
} elseif ($estado_int === 1 && isset($oficinas[$ofi_act])) {
  $info = $oficinas[$ofi_act];
  echo '<span class="badge" style="background-color: ' . $info['bg'] . '; color: ' . $info['color'] . ';">' . $info['nombre'] . '</span>';
  if ($r['fecha_solicitud']) {
    $fecha_obj = new DateTime($r['fecha_solicitud']);
    echo '<br><small>Desde: <b>' . $fecha_obj->format('d/m/Y H:i') . '</b></small>';
  }
}else echo '<span class="badge badge-secondary">Desconocido</span>';
       ?>
     </td>

     <!-- Estado rúbrica -->
     <td>
       <?php
         $er = $r['estado_rubrica']??'en_espera';
         if ($er === 'aprobado') {
          echo '<span class="badge badge-success">Aprobado</span>';
        } elseif ($er === 'observado') {
          echo '<span class="badge badge-danger">Observado</span>';
          if (!empty($r['fecha_limite'])) {
            $fecha = new DateTime($r['fecha_limite']);
            $meses = [
              'January' => 'enero', 'February' => 'febrero', 'March' => 'marzo',
              'April' => 'abril', 'May' => 'mayo', 'June' => 'junio',
              'July' => 'julio', 'August' => 'agosto', 'September' => 'septiembre',
              'October' => 'octubre', 'November' => 'noviembre', 'December' => 'diciembre'
            ];
            $fecha_str = $fecha->format('d F Y H:i:s');
            foreach ($meses as $en => $es) {
              $fecha_str = str_replace($en, $es, $fecha_str);
            }
            echo '<br><small><i class="fas fa-clock"></i> Límite:<br><b>' . $fecha_str . '</b></small>';
          }
        } else {
          echo '<span class="badge badge-primary">En Espera</span>';
        }        
       ?>
     </td>

     <td><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>

     <!-- Acciones -->
     <?php
$estado_proyecto = (int)$r['estado'];
$oficina_proyecto = $r['oficina_actual'] ?? null;
$en_misma_oficina = ($oficina_actual === $oficina_proyecto);

// Condición para deshabilitar
$deshabilitado = ($estado_proyecto === 0 || $estado_proyecto === 2 || !$en_misma_oficina);

// Tooltip personalizado
if ($estado_proyecto === 0) {
  $tooltip = 'title="El coordinador no ha solicitado CALIFICACIÓN."';
} elseif ($estado_proyecto === 2) {
  $tooltip = 'title="El proyecto ya fue APROBADO totalmente."';
} elseif (!$en_misma_oficina) {
  $oficinas = [
    'pcf' => 'Comité de Facultad',
    'dd' => 'Dirección de Departamento',
    'df' => 'Decanato de Facultad',
    'rsu' => 'Dirección RSU'
  ];
  $nombre_oficina = $oficinas[$oficina_proyecto] ?? $oficina_proyecto;
  $tooltip = 'title="No puedes calificar este proyecto porque está en la oficina de ' . $nombre_oficina . '."';
} else {
  $tooltip = '';
}

$disabled_attr = $deshabilitado ? 'disabled' : '';
?>
     <td class="text-center">
     <div class="btn-group-vertical">
  <!-- botón CALIFICAR (LO DEJAS TAL CUAL) -->
  <button class="btn btn-warning btn-sm btn-calificar-rubrica"
          data-id="<?= $r['id'] ?>"
          data-periodo="<?= $periodo_seleccionado ?>"
          <?= $disabled_attr ?> <?= $tooltip ?>>
      <i class="fas fa-star"></i> Calificar
  </button>

  <!-- NUEVO botón PROYECTO -->
  <button type="button"
          class="btn btn-primary btn-sm btn-modal-proyecto"
          data-id="<?= $r['id'] ?>"
          data-titulo="<?= htmlspecialchars($r['p2']) ?>">
      <i class="fas fa-info-circle"></i> Proyecto
  </button>

  <!-- NUEVO botón SEMESTRAL -->
  <button type="button"
          class="btn btn-sm btn-modal-semestral"
          style="background-color:#3d9970;border-color:#3d9970;color:white;"
          data-id="<?= $r['id'] ?>"
          data-titulo="<?= htmlspecialchars($r['p2']) ?>">
      <i class="fas fa-calendar-alt"></i> Semestral
  </button>
</div>

</td>
   </tr>
   <!-- detalle oculto -->
   <tr class="collapse bg-light" id="det<?= $r['id'] ?>">
  <td colspan="7" class="p-3">
    <p><i class="fas fa-university"></i> <strong>Facultad:</strong> <?= htmlspecialchars($r['facultad'] ?? 'No definido') ?></p>
    <p><i class="fas fa-building"></i> <strong>Departamento:</strong> <?= htmlspecialchars($r['departamento'] ?? 'No definido') ?></p>
    <p><i class="fas fa-book"></i> <strong>Programa:</strong> <?= htmlspecialchars($r['p1'] ?: 'No definido') ?></p>
    <p><i class="fas fa-id-badge"></i> <strong>Código Docente:</strong> <?= htmlspecialchars($r['usuario'] ?? 'No definido') ?></p>
  </td>
</tr>
<?php endwhile; else: ?>
   <tr><td colspan="7" class="text-center text-muted">No se encontraron proyectos.</td></tr>
<?php endif; ?>
   </tbody>
  </table>
 </div>
</div>

<!-- ========== PAGINACIÓN ========== -->
<nav><ul class="pagination pagination-sm justify-content-center">
<?php for($i=1;$i<=$paginas;$i++): ?>
  <li class="page-item <?= $i==$pagina?'active':'' ?>">
    <a class="page-link" href="?<?= http_build_query(array_merge($_GET,['pagina'=>$i])) ?>"><?= $i ?></a>
  </li>
<?php endfor; ?>
</ul></nav>

<!-- ========== MODAL RÚBRICA ========== -->
<div class="modal fade" id="modalRubrica" tabindex="-1">
 <div class="modal-dialog modal-lg"><div class="modal-content">
  <form id="formRubrica">
   <div class="modal-header">
     <h5 class="modal-title">Evaluar por Rúbrica</h5>
     <button type="button" class="close" data-dismiss="modal">&times;</button>
   </div>
   <div class="modal-body">
     <input type="hidden" name="id_py" id="rubrica_id_py">
     <input type="hidden" name="id_periodo" id="rubrica_id_periodo">

     <div class="alert alert-secondary d-flex justify-content-between">
       <span>Puntaje total: <strong id="rubrica_total">0</strong> / 20</span>
       <span>Estado: <strong id="rubrica_estado" class="text-primary">En espera</strong></span>
     </div>
     <div class="form-group" id="grupo_dias_limite" style="display:none;">
  <label for="dias_subsanacion"><i class="fas fa-clock"></i> Días para subsanar observaciones:</label>
  <select name="dias_subsanacion" id="dias_subsanacion" class="form-control">
    <option value="">-- Seleccionar --</option>
    <option value="1">1 día</option>
    <option value="2">2 días</option>
  </select>
</div>

     <div class="row">
<?php
$aspectos=[
 'estructura'=>'Estructura','contenido'=>'Contenido',
 'redaccion'=>'Redacción','calidad_info'=>'Calidad de Información',
 'mejora'=>'Propuesta de Mejora'
];
foreach($aspectos as $k=>$lbl): ?>
  <div class="col-md-6 mb-3">
   <label><strong><?= $lbl ?></strong></label>
   <select class="form-control sel-aspecto" name="puntaje[<?= $k ?>]" data-aspecto="<?= $k ?>">
     <option value="0">0 – En espera</option>
     <option value="1">1 – Insuficiente</option>
     <option value="2">2 – Mejorable</option>
     <option value="3">3 – Satisfactorio</option>
     <option value="4">4 – Excelente</option>
   </select>
   <textarea class="form-control mt-2 obs-aspecto"
             name="observacion[<?= $k ?>]"
             maxlength="3000" placeholder="Observación (máx 3000 car.)"
             style="display:none;"></textarea>
  </div>
<?php endforeach; ?>
     </div>
   </div>
   <div class="modal-footer">
     <button class="btn btn-success" type="submit">Guardar</button>
     <button class="btn btn-secondary" data-dismiss="modal" type="button">Cancelar</button>
   </div>
  </form>
 </div></div>
</div>

<!-- Modal de éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">¡Éxito!</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body text-center">
        <i class="fas fa-check-circle" style="font-size: 50px; color: green;"></i>
        <p class="mt-3">La calificación se guardó correctamente.</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal PROYECTO -->
<div class="modal fade" id="modalProyecto" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content border-info">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title">
          <i class="fas fa-info-circle"></i> Formulación y presentación de Proyecto
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="contenidoProyecto">
        <p class="text-center text-muted">Cargando datos del proyecto...</p>
      </div>
    </div>
  </div>
</div>

<!-- Modal SEMESTRAL -->
<div class="modal fade" id="modalSemestral" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content border-success">
      <div class="modal-header text-white" style="background-color:#3d9970;">
        <h5 class="modal-title">
          <i class="fas fa-calendar-alt"></i> Evaluación e informe Semestral
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="contenidoSemestral">
        <p class="text-center text-muted">Cargando datos del proyecto...</p>
      </div>
    </div>
  </div>
</div>

<!-- ========== JS RUBRICA ========== -->
<script>
$(document).ready(function () {
  // Mostrar modal RÚBRICA
  $(document).on("click", ".btn-calificar-rubrica", function () {
    const id_py = $(this).data("id");
    const id_periodo = $(this).data("periodo");

    // Resetear modal
    $("#rubrica_id_py").val(id_py);
    $("#rubrica_id_periodo").val(id_periodo);
    $("#formRubrica")[0].reset();
    $(".obs-aspecto").hide().val("");
    $("#rubrica_total").text("0");
    $("#rubrica_estado").text("En espera").removeClass().addClass("text-primary");

    // Cargar datos previos si existen
    $.get("calificacion/cargar_rubrica.php", { id_py, id_periodo }, function (data) {
  if (data.success) {
    let total = 0;
    for (const key in data.aspectos) {
      const val = parseInt(data.aspectos[key].puntaje);
      const obs = data.aspectos[key].observacion;
      $(`select[name="puntaje[${key}]"]`).val(val);
      if (val !== 4 && val !== 3 && val !== 0) {
        $(`textarea[name="observacion[${key}]"]`).val(obs || "").show();
      }
      total += val;
    }

    // 👇 Nuevo: setear fecha límite si existe
    if (data.dias_subsanacion) {
  $("#dias_subsanacion").val(data.dias_subsanacion);
  $("#grupo_dias_limite").show();
}

    actualizarEstado(total);
  }
}, "json");

    $("#modalRubrica").modal("show");
  });

  // Mostrar/ocultar textareas y recalcular puntaje total
  $(".sel-aspecto").change(function () {
    const aspecto = $(this).data("aspecto");
    const valor = parseInt($(this).val());
    const textarea = $(`textarea[name="observacion[${aspecto}]"]`);

    // Mostrar textarea si calificación < 3 y != 0
    if (valor !== 4 && valor !== 3 && valor !== 0) {
      textarea.show();
    } else {
      textarea.hide().val("");
    }

    // Si se selecciona En Espera (0), resetear todos los selects
    if (valor === 0) {
      $(".sel-aspecto").val(0);
      $(".obs-aspecto").hide().val("");
      actualizarEstado(0);
      return;
    }

    // Calcular puntaje total
    let total = 0;
    $(".sel-aspecto").each(function () {
      total += parseInt($(this).val());
    });

    actualizarEstado(total);
  });

  // Función para actualizar puntaje y estado dinámicamente
  function actualizarEstado(total) {
  $("#rubrica_total").text(total);
  const estadoSpan = $("#rubrica_estado");

  if (total === 0) {
  estadoSpan.text("En espera").removeClass().addClass("text-primary");
  $("#grupo_dias_limite").hide();
} else if (total > 13) {
  estadoSpan.text("Aprobado").removeClass().addClass("text-success");
  $("#grupo_dias_limite").hide();
} else {
  estadoSpan.text("Observado").removeClass().addClass("text-danger");
  $("#grupo_dias_limite").show();
}
}


  // Envío del formulario
  $("#formRubrica").submit(function (e) {
    e.preventDefault();
    $.post("calificacion/guardar_rubrica.php", $(this).serialize(), function (resp) {
      const r = JSON.parse(resp);
      if (r.success) {
        $("#modalRubrica").modal("hide");
        $("#successModal").modal("show");

        // Recargar tabla
        $.get(window.location.href, function (data) {
          const nuevaTabla = $(data).find("#tablaProyectos").html();
          $("#tablaProyectos").html(nuevaTabla);
        });
      } else {
        alert(r.msg || "Ocurrió un error al guardar");
      }
    });
  });
});
</script>
<script>
  /* ---------- Modal PROYECTO ---------- */
$(document).on('click','.btn-modal-proyecto',function(){
  const id = $(this).data('id');
  const titulo = $(this).data('titulo');

  $('#contenidoProyecto').html('<p class="text-center text-muted">Cargando datos del proyecto...</p>');
  $('#modalProyecto').modal('show');

  $.get('calificacion/presentacion.php',{id_py:id},function(html){
      $('#contenidoProyecto').html(html);

      /* Cargar y mostrar archivos adjuntos */
      const cont = document.getElementById('contenedor-archivos');
      if(!cont) return;
      fetch('calificacion/gestion_archivos.php?id_py='+id)
        .then(r=>r.json())
        .then(data=>{
          const nombres = {
            lista_docentes:'1. Lista de Docentes',
            lista_alumnos:'2. Lista de Alumnos',
            diagrama:'3. Diagrama',
            compromiso:'4. Compromiso Ético',
            carta:'5. Carta de Intención'
          };
          const idsNav = {
            lista_docentes:'anitem1',
            lista_alumnos:'anitem2',
            diagrama:'anitem3',
            compromiso:'anitem4',
            carta:'anitem5'
          };
          for(const [clave,archs] of Object.entries(data)){
            const seccion = document.createElement('div');
            seccion.className='mb-3';
            seccion.id = idsNav[clave]||'';
            const titulo = `<strong>${nombres[clave]}</strong><br>`;
            if(!archs || archs.length===0){
               seccion.innerHTML = `${titulo}<span class="text-danger">No hay archivo</span>`;
            }else{
               const lista = archs.map(n=>{
                 const ext   = n.split('.').pop().toLowerCase();
                 const isPDF = ext==='pdf';
                 const isXLS = ['xls','xlsx'].includes(ext);
                 const icon  = isPDF? 'file-pdf text-danger' :
                               isXLS? 'file-excel text-success' : 'file-alt text-secondary';
                 const btn   = isPDF? 'btn-outline-danger' :
                               isXLS? 'btn-outline-success' : 'btn-outline-secondary';
                 const url   = `calificacion/descarga_archivos.php?categoria=${clave}&id_py=${id}&archivo=${encodeURIComponent(n)}${isPDF?'&ver=1':''}`;
                 return `
                   <div class="archivo-card d-flex align-items-center justify-content-between p-3 mb-2 border rounded shadow-sm bg-white">
                     <div class="d-flex align-items-center" style="gap:10px;">
                       <i class="fas fa-${icon}" style="font-size:1.5rem;"></i>
                       <div title="${n}" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                         ${n}
                       </div>
                     </div>
                     <a href="${url}" target="_blank" class="btn ${btn} btn-sm">Descargar</a>
                   </div>`;
               }).join('');
               seccion.innerHTML = titulo+lista;
            }
            cont.appendChild(seccion);
          }
        })
        .catch(()=>cont.innerHTML='<div class="text-danger">Error al cargar archivos.</div>');
  });
});

/* ---------- Modal SEMESTRAL ---------- */
$(document).on('click','.btn-modal-semestral',function(){
  const id = $(this).data('id');
  const titulo = $(this).data('titulo');

  $('#contenidoSemestral').html('<p class="text-center text-muted">Cargando informe semestral...</p>');
  $('#modalSemestral').modal('show');

  $.get('calificacion/semestral.php',{id_py:id},function(html){
      $('#contenidoSemestral').html(html);
  });
});

</script>

