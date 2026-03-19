<?php
require_once("../componentes/db.php");
require_once("../componentes/revision/filtro_rol_proyecto.php");

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
$departamentos_q = mysqli_query(
    $conexion,
    "SELECT id, nombre, id_facultad FROM departamentos ORDER BY id ASC"
);
$departamentos = [];
while ($d = mysqli_fetch_assoc($departamentos_q)) { $departamentos[] = $d; }

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
  JOIN usuarios u ON u.id_py = p.id
  LEFT JOIN departamentos d ON d.id = u.id_depa
  LEFT JOIN facultades f ON f.id = d.id_facultad
  WHERE $condiciones
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

        <div class="col-md-4 mb-2">
          <label for="departamento_id">Departamento:</label>
          <select name="departamento_id" id="departamento_id" class="form-control">
            <?php foreach ($departamentos as $dep): ?>
              <option value="<?= $dep['id'] ?>" data-id_facultad="<?= $dep['id_facultad'] ?>"
                <?= ($filtro_departamento == $dep['id']) ? 'selected' : '' ?>
                <?= ($filtro_facultad && $dep['id_facultad'] != $filtro_facultad) ? 'style=display:none;' : '' ?>>
                <?= htmlspecialchars($dep['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <!-- Segunda fila -->
        <div class="col-md-4 mb-2">
          <label for="estado">Estado:</label>
          <select name="estado" class="form-control">
            <option value="">Todos</option>
            <option value="0" <?= (isset($_GET['estado']) && $_GET['estado'] === '0') ? 'selected' : '' ?>>En Espera</option>
            <option value="1" <?= (isset($_GET['estado']) && $_GET['estado'] === '1') ? 'selected' : '' ?>>En Revisión</option>
            <option value="2" <?= (isset($_GET['estado']) && $_GET['estado'] === '2') ? 'selected' : '' ?>>Aprobado</option>
          </select>
        </div>

        <div class="col-md-4 mb-2">
          <label for="ods_id">ODS:</label>
          <select name="ods_id" class="form-control">
            <option value="0">Todos</option>
            <?php foreach ($ods_list as $ods): ?>
              <option value="<?= $ods['id'] ?>" <?= ($filtro_ods === (int)$ods['id']) ? 'selected' : '' ?>>
                <?= htmlspecialchars($ods['id'] . '. ' . $ods['nombre']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-4 mb-2">
          <label for="buscar_texto">Texto o código:</label>
          <input type="text" class="form-control" name="buscar_texto"
                 placeholder="ID, usuario, nombre o título"
                 value="<?= htmlspecialchars($filtro_texto ?? '') ?>">
        </div>
      </div>
    </div>
<!-- Botonera lateral en cuadrícula 2x2, centrada verticalmente -->
<div class="col-md-2 d-flex flex-column justify-content-center align-items-center" style="min-height: 100%;">
  <div class="row w-100">

<!-- TOTAL REGISTROS -->
<div class="col-12 mb-1 text-center">
  <label class="d-block font-digital p-1 bg-white" style="font-size: 1rem; border-radius: 2px; color: #0d6efd;">
    Resultados:<br>
    <?= $total ?>
  </label>
</div>

    <!-- FILA 1 -->
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

    <!-- FILA 2 -->
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
   if ($filtro_facultad     !== 0) $condiciones .= " AND f.id = $filtro_facultad";
   if ($filtro_departamento !== 0) $condiciones .= " AND d.id = $filtro_departamento";
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
             u.id_depa, u.nombres, u.apellidos,
             d.nombre AS departamento, f.nombre AS facultad
      FROM proyectos p
      JOIN usuarios u       ON u.id_py   = p.id
      LEFT JOIN departamentos d ON d.id = u.id_depa
      LEFT JOIN facultades   f ON f.id   = d.id_facultad
      WHERE $condiciones
      ORDER BY p.id ASC
      LIMIT $inicio, $limite");
   
   /* ──────────────────────────────
      6.  Tabla de resultados
      ────────────────────────────── */
   ?>
   <div class="table-responsive">
     <table class="table table-bordered table-hover table-sm">
       <thead class="thead-dark">
         <tr>
           <th>#</th><th>ID PY</th><th>Título Proyecto</th>
           <th>Coordinador</th><th>Acciones</th>
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
       <td><?= htmlspecialchars($r['nombres'].' '.$r['apellidos']) ?></td>
       <td class="text-center">
  <div class="btn-group-vertical">
    <button type="button" class="btn btn-primary btn-sm mb-1">Proyecto</button>
    <button type="button" class="btn btn-success btn-sm mb-1">Semestral</button>
    <button type="button" class="btn btn-warning btn-sm">Evaluación</button>
  </div>
</td>
     </tr>
     <tr class="collapse bg-light" id="det<?= $r['id'] ?>">
       <td colspan="5" class="p-3">
         <strong>Facultad:</strong> <?= htmlspecialchars($r['facultad']??'No definido') ?><br>
         <strong>Departamento:</strong> <?= htmlspecialchars($r['departamento']??'No definido') ?><br>
         <strong>Programa:</strong> <?= htmlspecialchars($r['p1']?:'No definido') ?><br>
         <!-- resto de campos -->
       </td>
     </tr>
   <?php endwhile; else: ?>
     <tr><td colspan="5" class="text-center text-muted">No se encontraron proyectos.</td></tr>
   <?php endif; ?>
       </tbody>
     </table>
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
   