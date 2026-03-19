<?php
require_once("../componentes/db.php");
require_once("../componentes/revision/filtro_rol_proyecto.php");

$oficina_actual  = 'df';        // Decanato de Facultad
$tipo_evaluacion = 'vb';        // Tipo de evaluación: visto bueno

/* ──────────────────────────────
   1.  Catálogos y filtros base
   ────────────────────────────── */

// Períodos
$periodos_q = mysqli_query(
    $conexion,
    "SELECT id, nombre FROM periodos ORDER BY fecha_inicio DESC"
);
$periodos = [];
$periodo_activo_id = null;
$nombre_periodo_activo = "-- Período activo --";

// Facultades  (incluye id 0 = “Sin facultad”)
$facultades_q = mysqli_query(
    $conexion,
    "SELECT id, nombre FROM facultades ORDER BY id ASC"
);
$facultades = [];
while ($f = mysqli_fetch_assoc($facultades_q)) { $facultades[] = $f; }

// Departamentos (incluye id 0 = “Sin departamento académico”)
$id_rol = $_SESSION['id_rol'];
$id_facultad_usuario = getFacultadUsuario($conexion); // Ya tienes esta función

$departamentos = [];

if (in_array($id_rol, [3, 5]) && $id_facultad_usuario !== null) {
    // Filtrar solo los departamentos de su facultad
    $departamentos_q = mysqli_query(
        $conexion,
        "SELECT id, nombre, id_facultad FROM departamentos 
         WHERE id_facultad = $id_facultad_usuario 
         ORDER BY nombre ASC"
    );
} else {
    // Para RSU o DD, mostrar todos
    $departamentos_q = mysqli_query(
        $conexion,
        "SELECT id, nombre, id_facultad FROM departamentos ORDER BY nombre ASC"
    );
}

while ($d = mysqli_fetch_assoc($departamentos_q)) {
    $departamentos[] = $d;
}

// ODS
$ods_q = mysqli_query($conexion, "SELECT id, nombre FROM ods ORDER BY id ASC");
$ods_list = [];
while ($o = mysqli_fetch_assoc($ods_q)) { $ods_list[] = $o; }

/* ──────────────────────────────
   2.  Parámetros recibidos
   ────────────────────────────── */

$filtro_facultad     = isset($_GET['facultad_id'])     ? (int)$_GET['facultad_id']     : 0;
$filtro_departamento = isset($_GET['departamento_id']) ? (int)$_GET['departamento_id'] : 0;
$estado              = isset($_GET['estado']) && $_GET['estado'] !== '' ? (int)$_GET['estado'] : null;
$filtro_ods          = isset($_GET['ods_id']) ? (int)$_GET['ods_id'] : 0;

// 🔽 Aquí agregas este nuevo bloque
$filtros_visibles = getFiltrosPermitidos();

function mostrarFiltro($nombre) {
    global $filtros_visibles;
    return in_array($nombre, $filtros_visibles);
}

// Si solo llega el departamento, averiguamos a qué facultad pertenece
if ($filtro_facultad === 0 && $filtro_departamento !== 0) {
    $q = mysqli_query(
        $conexion,
        "SELECT id_facultad FROM departamentos WHERE id = $filtro_departamento LIMIT 1"
    );
    if ($row = mysqli_fetch_assoc($q)) {
        $filtro_facultad = (int)$row['id_facultad'];
    }
}

/* ──────────────────────────────
   3.  Período activo / seleccionado
   ────────────────────────────── */

while ($p = mysqli_fetch_assoc($periodos_q)) {
    $periodos[] = $p;

    if (is_null($periodo_activo_id)) {
        $activo_q = mysqli_query(
            $conexion,
            "SELECT id, nombre FROM periodos WHERE activo = 1 LIMIT 1"
        );
        if ($a = mysqli_fetch_assoc($activo_q)) {
            $periodo_activo_id = $a['id'];
            $nombre_periodo_activo = $a['nombre'];
        }
    }
}
$periodo_seleccionado = (isset($_GET['periodo_id']) && $_GET['periodo_id'] !== '')
    ? (int)$_GET['periodo_id']
    : $periodo_activo_id;

/* ──────────────────────────────
   4.  Proyectos visibles
   ────────────────────────────── */

$proyectos_permitidos = getProyectosVisibles($conexion, $periodo_seleccionado);
$filtro_texto = isset($_GET['buscar_texto']) ? trim($_GET['buscar_texto']) : '';
if (empty($proyectos_permitidos)) {
  $ids_permitidos = '0';
} else {
  $ids_permitidos = implode(',', $proyectos_permitidos);
}

$condiciones = "p.id IN ($ids_permitidos)";

if ($filtro_facultad !== 0) {
    $condiciones .= " AND f.id = $filtro_facultad";
}
if ($filtro_departamento !== 0) {
    $condiciones .= " AND d.id = $filtro_departamento";
}
if ($filtro_texto !== '') {
  $texto_esc = mysqli_real_escape_string($conexion, $filtro_texto);
  if (is_numeric($filtro_texto)) {
      $condiciones .= " AND (p.id = $filtro_texto OR u.usuario LIKE '%$texto_esc%')";
  } else {
      $condiciones .= " AND (
          u.usuario LIKE '%$texto_esc%' OR
          u.nombres LIKE '%$texto_esc%' OR
          u.apellidos LIKE '%$texto_esc%' OR
          p.p2 LIKE '%$texto_esc%'
      )";
  }
}
if (!is_null($estado)) {
  $condiciones .= " AND p.estado = $estado";
}
if ($filtro_ods !== 0) {
  $condiciones .= " AND FIND_IN_SET($filtro_ods, p.p3)";
}

// TOTAL de resultados antes de cargar el formulario
$total_q = mysqli_query($conexion, "
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
$total = mysqli_fetch_assoc($total_q)['total'];


?>
<!-- ========== FILTROS REORGANIZADOS ========== -->
<form method="GET" class="mb-3">
  <div class="row">

    <!-- Filtros (ocupando 9 columnas en total) -->
    <div class="col-md-10">
      <div class="row">

        <!-- Primera fila -->
        <?php if (mostrarFiltro('periodo')): ?>
        <div class="col-md-4 mb-2">
          <label for="periodo_id">Período:</label>
          <select name="periodo_id" class="form-control">
            <?php foreach ($periodos as $p): ?>
              <option value="<?= $p['id'] ?>" <?= ($periodo_seleccionado == $p['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($p['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <?php if (mostrarFiltro('facultad')): ?>
        <div class="col-md-4 mb-2">
          <label for="facultad_id">Facultad:</label>
          <select name="facultad_id" id="facultad_id" class="form-control">
            <?php foreach ($facultades as $fac): ?>
              <option value="<?= $fac['id'] ?>" <?= ($filtro_facultad == $fac['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($fac['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <?php endif; ?>

        <?php if (mostrarFiltro('departamento')): ?>
        <div class="col-md-4 mb-2">
          <label for="departamento_id">Departamento:</label>
          <select name="departamento_id" id="departamento_id" class="form-control">
          <?php if (in_array($_SESSION['id_rol'], [3, 5])): ?>
  <option value="0" <?= $filtro_departamento === 0 ? 'selected' : '' ?>>Todos</option>
<?php endif; ?>
  <?php foreach ($departamentos as $dep): ?>
    <option value="<?= $dep['id'] ?>" data-id_facultad="<?= $dep['id_facultad'] ?>"
      <?= ($filtro_departamento == $dep['id']) ? 'selected' : '' ?>
      <?= ($filtro_facultad && $dep['id_facultad'] != $filtro_facultad) ? 'style=display:none;' : '' ?>>
      <?= htmlspecialchars($dep['nombre']) ?>
    </option>
  <?php endforeach; ?>
</select>
        </div>
        <?php endif; ?>

        <!-- Segunda fila -->
        <?php if (mostrarFiltro('estado')): ?>
        <div class="col-md-4 mb-2">
          <label for="estado">Estado:</label>
          <select name="estado" class="form-control">
  <option value="">Todos</option>
  <option value="0" <?= (isset($_GET['estado']) && $_GET['estado'] === '0') ? 'selected' : '' ?>>En Espera</option>
  <option value="1" <?= (isset($_GET['estado']) && $_GET['estado'] === '1') ? 'selected' : '' ?>>En Revisión</option>
  <option value="2" <?= (isset($_GET['estado']) && $_GET['estado'] === '2') ? 'selected' : '' ?>>Aprobado</option>
</select>
        </div>
        <?php endif; ?>

        <?php if (mostrarFiltro('texto')): ?>
        <div class="col-md-4 mb-2">
          <label for="buscar_texto">Texto o código:</label>
          <input type="text" class="form-control" name="buscar_texto"
                 placeholder="ID, usuario, nombre o título"
                 value="<?= htmlspecialchars($filtro_texto ?? '') ?>">
        </div>
        <?php endif; ?>

      </div>
    </div>

    <!-- Botonera lateral -->
    <div class="col-md-2 d-flex flex-column justify-content-center align-items-center" style="min-height: 100%;">
      <div class="row w-100">

        <!-- Total -->
        <div class="col-12 mb-1 text-center">
          <label class="d-block font-digital p-1 bg-white" style="font-size: 1rem; border-radius: 2px; color: #0d6efd;">
            Resultados:<br>
            <?= $total ?>
          </label>
        </div>

        <!-- Botones -->
        <div class="col-6 mb-2 text-center">
          <button type="submit" class="btn btn-primary btn-sm w-100 h-100 text-white" title="Filtrar">
            <i class="fas fa-filter"></i>
          </button>
        </div>

        <div class="col-6 mb-2 text-center">
          <a href="?" class="btn btn-danger btn-sm w-100 h-100 text-white" title="Limpiar filtros">
            <i class="fas fa-broom"></i>
          </a>
        </div>

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
   5.  Condiciones y consulta
   ────────────────────────────── */

   if (empty($proyectos_permitidos)) { $ids_permitidos = '0'; }
   else { $ids_permitidos = implode(',', $proyectos_permitidos); }
   
   $condiciones = "p.id IN ($ids_permitidos)";

if ($filtro_departamento !== 0) {
    $condiciones .= " AND d.id = $filtro_departamento";
}

if ($filtro_facultad !== 0) {          // <-- ahora se aplica para cualquier rol
    $condiciones .= " AND f.id = $filtro_facultad";
}
   
   if ($filtro_texto !== '') {
    $texto_esc = mysqli_real_escape_string($conexion, $filtro_texto);
  
    if (is_numeric($filtro_texto)) {
      // Coincidencia exacta en ID de proyecto o parcial en usuario
      $condiciones .= " AND (p.id = $filtro_texto OR u.usuario LIKE '%$texto_esc%')";
    } else {
      // Coincidencia parcial en texto
      $condiciones .= " AND (
        u.usuario LIKE '%$texto_esc%' OR
        u.nombres LIKE '%$texto_esc%' OR
        u.apellidos LIKE '%$texto_esc%' OR
        p.p2 LIKE '%$texto_esc%'
      )";
    }
  }  
   if (!is_null($estado))     $condiciones .= " AND p.estado = $estado";
   if ($filtro_ods !== 0)     $condiciones .= " AND FIND_IN_SET($filtro_ods, p.p3)";
   
   /* ------------- paginación y consulta ------------- */
   
   $limite   = 10;
   $pagina   = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
   $inicio   = ($pagina - 1) * $limite;
   $contador = $inicio + 1;

   $paginas = ceil($total / $limite);
   
$query = mysqli_query($conexion,
 "SELECT p.id, p.p2, p.p1, p.coordinador, p.fecha_inicio, p.fecha_fin,
         p.estado,
         u.id_depa, u.nombres, u.apellidos, u.usuario,
         d.nombre AS departamento, f.nombre AS facultad,
         ev.estado AS estado_cotejo, ev.fecha_limite,
         rp.oficina_actual, rp.fecha_solicitud
  FROM proyectos p
  JOIN usuarios_proyectos up ON up.id_proyecto = p.id
  JOIN usuarios u ON u.id = up.id_usuario
  JOIN proyectos_periodo pp ON pp.id_py = p.id
  JOIN revisiones_proyectos rp ON rp.id_py = p.id AND rp.id_periodo = pp.id_periodo
  LEFT JOIN departamentos d ON d.id = u.id_depa
  LEFT JOIN facultades f ON f.id = d.id_facultad
  LEFT JOIN evaluaciones ev 
  ON ev.id_py = p.id AND ev.id_periodo = $periodo_seleccionado 
  AND ev.oficina = 'df' AND ev.tipo = 'vb'
  WHERE $condiciones
    AND up.activo = 1
  ORDER BY p.id ASC
  LIMIT $inicio, $limite");
   
   /* ──────────────────────────────
      6.  Tabla de resultados
      ────────────────────────────── */
   ?>
   <div id="tablaProyectos">
   <div class="table-responsive">
     <table class="table table-bordered table-hover table-sm">
     <thead class="thead-dark">
  <tr>
    <th>#</th>
    <th>ID PY</th>
    <th>Título Proyecto</th>
    <th>Estado</th>
    <th>Visto Bueno</th>
    <th>Coordinador</th>
    <th>Acciones</th>
  </tr>
</thead>
       <tbody>
   <?php if (mysqli_num_rows($query)): ?>
   <?php while ($r = mysqli_fetch_assoc($query)): ?>
     <tr data-toggle="collapse" data-target="#det<?= $r['id'] ?>"
         class="accordion-toggle" style="cursor:pointer;">
       <td><?= $contador++ ?></td>
       <td><?= $r['id'] ?></td>
       <td>
  <?php if (trim($r['p2']) !== ''): ?>
    <?= htmlspecialchars($r['p2']) ?>
  <?php else: ?>
    <b style="color: #8B0000;">No registrado en Presentación de Proyectos</b>
  <?php endif; ?>
</td>
<!-- Columna Estado -->
<td>
  <?php
    $estado          = (int)$r['estado'];
    $ofi_act         = $r['oficina_actual'] ?? '';   // ← SOLO para mostrar la etiqueta
    $fecha_solicitud = $r['fecha_solicitud'] ?? null;

    $oficinas = ['pcf' => ['nombre' => 'Oficina del Comité de Facultad', 'bg' => '#0275D8', 'color' => 'white'], 'dd' => ['nombre' => 'Oficina de la Dirección de Departamento', 'bg' => '#F0AD4E', 'color' => 'black'], 'df' => ['nombre' => 'Oficina del Decanato de Facultad', 'bg' => '#5BC0DE', 'color' => 'black'], 'rsu' => ['nombre' => 'Oficina de la Dirección de RSU - UNT', 'bg' => '#5CB85C', 'color' => 'white']];

    if ($estado === 0) {
      echo '<span class="badge badge-secondary">En Proceso</span>';
    } elseif ($estado === 2) {
      echo '<span class="badge badge-success">Aprobación 2024-II</span>';
    } elseif ($estado === 1 && isset($oficinas[$ofi_act])) {
      $info = $oficinas[$ofi_act];
      echo '<span class="badge" style="background-color: '.$info['bg'].'; color: '.$info['color'].';">'.$info['nombre'].'</span>';  
      if ($fecha_solicitud) {
        $fecha_obj = new DateTime($fecha_solicitud);
        echo '<br><small>Desde: <b>' . $fecha_obj->format('d/m/Y H:i') . '</b></small>';
      }
    } else {
      echo '<span class="badge badge-secondary">Desconocido</span>';
    }
  ?>
</td>

<!-- Columna Cotejo -->
<td>
  <?php
    $estado_cotejo = $r['estado_cotejo'];
    $fecha_limite = $r['fecha_limite'];

    if ($estado_cotejo === 'aprobado') {
      echo '<span class="badge badge-success">Aprobado</span>';
    } elseif ($estado_cotejo === 'en_espera') {
      echo '<span class="badge badge-primary">En Espera</span>';
    } elseif ($estado_cotejo === 'observado') {
      echo '<span class="badge badge-danger">Observado</span>';
      if (!empty($fecha_limite)) {
        // Formatear fecha en español: 21 abril 2025 20:19:22
        $fecha = new DateTime($fecha_limite);
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
       <td class="text-center">
  <div class="btn-group-vertical">
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
    // Este es el nuevo mensaje claro como en proyectos_rubrica
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

<button 
  class="btn btn-warning btn-sm btn-calificar"
  data-id="<?= $r['id'] ?>" 
  data-periodo="<?= $periodo_seleccionado ?>"
  data-oficina="<?= $oficina_actual ?>"
  <?= $disabled_attr ?> <?= $tooltip ?>>
  <i class="fas fa-star"></i> Calificar
</button>
<!-- NUEVOS BOTONES -->
<button type="button" 
          class="btn btn-primary btn-sm btn-modal-proyecto" 
          data-id="<?= $r['id'] ?>" 
          data-titulo="<?= htmlspecialchars($r['p2']) ?>">
    <i class="fas fa-info-circle"></i> Proyecto
  </button>
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
     <tr class="collapse bg-light" id="det<?= $r['id'] ?>">
     <td colspan="7" class="p-3">
  <p><i class="fas fa-university"></i> <strong>Facultad:</strong> <?= htmlspecialchars($r['facultad'] ?? 'No definido') ?></p>
  <p><i class="fas fa-building"></i> <strong>Departamento:</strong> <?= htmlspecialchars($r['departamento'] ?? 'No definido') ?></p>
  <p><i class="fas fa-book"></i> <strong>Programa:</strong> <?= htmlspecialchars($r['p1'] ?: 'No definido') ?></p>
  <p><i class="fas fa-id-badge"></i> <strong>Código Docente:</strong> <?= htmlspecialchars($r['usuario'] ?? 'No definido') ?></p>
</td>
     </tr>
   <?php endwhile; else: ?>
     <tr><td colspan="5" class="text-center text-muted">No se encontraron proyectos.</td></tr>
   <?php endif; ?>
       </tbody>
     </table>
   </div>
   </div> 
   
   <!-- Paginación -->
   <nav><ul class="pagination pagination-sm justify-content-center">
   <?php for ($i = 1; $i <= $paginas; $i++): ?>
     <li class="page-item <?= $i==$pagina?'active':'' ?>">
       <a class="page-link"
          href="?pagina=<?= $i ?>&periodo_id=<?= $periodo_seleccionado ?>
   &facultad_id=<?= $filtro_facultad ?>&departamento_id=<?= $filtro_departamento ?>
   &estado=<?= urlencode($estado) ?>&ods_id=<?= $filtro_ods ?>
   &buscar_id=<?= urlencode($filtro_id) ?>&buscar_usuario=<?= urlencode($filtro_usuario) ?>">
         <?= $i ?>
       </a>
     </li>
   <?php endfor; ?>
   </ul></nav>
   
   <!-- ───────────── JS: interacción facultad‑departamento ───────────── -->
   <script>
   document.addEventListener('DOMContentLoaded', () => {
     const selFac = document.getElementById('facultad_id');
     const selDep = document.getElementById('departamento_id');
   
     const filtraDepartamentos = () => {
       const fac = parseInt(selFac.value, 10);
       [...selDep.options].forEach(opt => {
         const facDep = parseInt(opt.dataset.id_facultad || 0, 10);
         const visible = fac === 0 || facDep === fac || opt.value === "0";
         opt.style.display = visible ? '' : 'none';
         if (!visible && opt.selected) selDep.value = "0";
       });
     };
   
     selFac.addEventListener('change', filtraDepartamentos);
   
     selDep.addEventListener('change', () => {
       const opt = selDep.selectedOptions[0];
       const facDep = parseInt(opt.dataset.id_facultad || 0, 10);
       if (selDep.value !== "0" && selFac.value != facDep) {
         selFac.value = facDep;
         filtraDepartamentos();
       }
     });
   
     filtraDepartamentos();   // llamada inicial
   });
   </script>
   <div class="modal fade" id="modalVisto" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-md" role="document">
    <div class="modal-content">
    <form id="formVisto">
        <div class="modal-header">
        <h5 class="modal-title">Evaluar por Visto Bueno</h5>
          <button type="button" class="close" data-dismiss="modal">&times;</button>
        </div>
        <div class="modal-body">
        <input type="hidden" name="id_py" id="visto_id_py">
        <input type="hidden" name="id_periodo" id="visto_id_periodo">
          <label>Resultado:</label>
          <select name="estado_vb" class="form-control" id="cotejo_estado" required>
  <option value="">-- Seleccionar --</option>
  <option value="aprobado">✅ Aprobado</option>
  <option value="en_espera">⏳ En Espera</option>
</select>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-success">Guardar</button>
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        </div>
      </form>
    </div>
  </div>
</div>
<!-- Modal de éxito -->
<div class="modal fade" id="successModal" tabindex="-1" aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="successModalLabel">¡Éxito!</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">×</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="text-center">
          <i class="fas fa-check-circle" style="font-size: 50px; color: green;"></i>
          <p class="mt-3">La calificación se ha guardado correctamente.</p>
        </div>
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

<script>
$(document).ready(function() {
  // Mostrar modal con datos
  $(document).on('click', '.btn-calificar', function() {
    const id_py = $(this).data('id');
    const id_periodo = $(this).data('periodo');

    $('#visto_id_py').val(id_py);
    $('#visto_id_periodo').val(id_periodo);
    $('#formVisto')[0].reset();

    $.get('calificacion/cargar_visto.php', { id_py, id_periodo }, function(data) {
      if (data.success) {
        $('#cotejo_estado').val(data.estado).trigger('change');
      }
    }, 'json');

    $('#modalVisto').modal('show');
  });

  // Guardar evaluación
  $('#formVisto').submit(function(e) {
    e.preventDefault();
    $.post('calificacion/guardar_visto.php', $(this).serialize(), function(resp) {
      const r = JSON.parse(resp);
      if (r.success) {
        $('#modalVisto').modal('hide');
        $('#successModal').modal('show');

        $.get(window.location.href, function (data) {
          const nuevaTabla = $(data).find('#tablaProyectos').html();
          $('#tablaProyectos').html(nuevaTabla);
        });
      } else {
        alert('Ocurrió un error al guardar');
      }
    });
  });
});
</script>
<script>
  /* ——— Modal PROYECTO ——— */
$(document).on('click','.btn-modal-proyecto',function(){
  const id = $(this).data('id');
  $('#contenidoProyecto')
      .html('<p class="text-center text-muted">Cargando datos del proyecto...</p>');
  $('#modalProyecto').modal('show');

  $.get('../comite_facultad/calificacion/presentacion.php',{id_py:id},function(html){
      $('#contenidoProyecto').html(html);

      /* Cargar archivos adjuntos */
      const cont = document.getElementById('contenedor-archivos');
      if(!cont) return;

      fetch('../comite_facultad/calificacion/gestion_archivos.php?id_py='+id)
        .then(r=>r.json())
        .then(data=>{
          const label = {
            lista_docentes:'1. Lista de Docentes',
            lista_alumnos:'2. Lista de Alumnos',
            diagrama:'3. Diagrama',
            compromiso:'4. Compromiso Ético',
            carta:'5. Carta de Intención'
          };
          const navId = {
            lista_docentes:'anitem1', lista_alumnos:'anitem2',
            diagrama:'anitem3', compromiso:'anitem4', carta:'anitem5'
          };
          for(const [cat,files] of Object.entries(data)){
            const sec = document.createElement('div');
            sec.className='mb-3';  sec.id = navId[cat]||'';
            const titulo = `<strong>${label[cat]}</strong><br>`;
            if(!files || !files.length){
              sec.innerHTML = `${titulo}<span class="text-danger">No hay archivo</span>`;
            }else{
              sec.innerHTML = titulo + files.map(f=>{
                const ext = f.split('.').pop().toLowerCase();
                const pdf = ext==='pdf', xls = ['xls','xlsx'].includes(ext);
                const icon = pdf?'file-pdf text-danger': xls?'file-excel text-success':'file-alt text-secondary';
                const btn  = pdf?'btn-outline-danger': xls?'btn-outline-success':'btn-outline-secondary';
                const url  = `../comite_facultad/calificacion/descarga_archivos.php?categoria=${cat}&id_py=${id}&archivo=${encodeURIComponent(f)}${pdf?'&ver=1':''}`;
                return `
                  <div class="archivo-card d-flex align-items-center justify-content-between p-3 mb-2 border rounded shadow-sm bg-white">
                    <div class="d-flex align-items-center" style="gap:10px;">
                      <i class="fas fa-${icon}" style="font-size:1.5rem;"></i>
                      <div title="${f}" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">
                        ${f}
                      </div>
                    </div>
                    <a href="${url}" target="_blank" class="btn ${btn} btn-sm">Descargar</a>
                  </div>`;
              }).join('');
            }
            cont.appendChild(sec);
          }
        })
        .catch(()=>cont.innerHTML='<div class="text-danger">Error al cargar archivos.</div>');
  });
});
/* ——— Modal SEMESTRAL ——— */
$(document).on('click','.btn-modal-semestral',function(){
  const id = $(this).data('id');
  $('#contenidoSemestral')
      .html('<p class="text-center text-muted">Cargando informe semestral...</p>');
  $('#modalSemestral').modal('show');

  $.get('../comite_facultad/calificacion/semestral.php',{id_py:id},function(html){
      $('#contenidoSemestral').html(html);
  });
});
</script>