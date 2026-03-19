<?php 
require_once("../componentes/db.php");

/* ═════════════ 1.  PARÁMETROS DE FILTRO ═════════════ */
$f_per = isset($_GET['filtro_periodo'])      ? trim($_GET['filtro_periodo'])      : '';
$f_fac = isset($_GET['filtro_facultad'])     ? trim($_GET['filtro_facultad'])     : '';
$f_dep = isset($_GET['filtro_departamento']) ? trim($_GET['filtro_departamento']) : '';   // ⬅️ nuevo
$f_ofi = isset($_GET['filtro_oficina'])      ? trim($_GET['filtro_oficina'])      : '';

$whereParts = [];

/* alias periodo */
if ($f_per !== '') {
    $v = mysqli_real_escape_string($conexion,$f_per);
    $whereParts[] = "periodo = '$v'";
}

/* alias departamento  – debe ir antes que facultad para poder
   autocompletar la facultad si el usuario no la envió */
if ($f_dep !== '') {
    $v = mysqli_real_escape_string($conexion,$f_dep);
    $whereParts[] = "departamento = '$v'";

    /* Si no llegó facultad, averiguamos la correspondiente        */
    if ($f_fac === '') {
        $sqlFac = "SELECT f.nombre
                     FROM departamentos d
                     JOIN facultades f ON f.id = d.id_facultad
                    WHERE d.nombre = '$v'
                    LIMIT 1";
        if ($rowFac = mysqli_fetch_assoc(mysqli_query($conexion,$sqlFac))) {
            $f_fac = $rowFac['nombre'];   // se usará más abajo
        }
    }
}

/* alias facultad */
if ($f_fac !== '') {
    $v = mysqli_real_escape_string($conexion,$f_fac);
    $whereParts[] = "facultad = '$v'";
}

/* alias oficina_real  +  alias estado_global */
if ($f_ofi !== '') {
    if ($f_ofi === 'sin') {
        $whereParts[] = "(oficina_real IS NULL OR oficina_real = '')";
    } elseif ($f_ofi === 'aprobado') {
        $whereParts[] = "estado_global = 2";
    } else {
        $v = mysqli_real_escape_string($conexion,$f_ofi);
        $whereParts[] = "oficina_real = '$v'";
    }
}

$where_sql = $whereParts ? 'WHERE '.implode(' AND ',$whereParts) : '';

/* ═════════════ 2.  Paginación ═════════════ */
$limite = 20;
$pagina = isset($_GET['pag']) ? max(1,(int)$_GET['pag']) : 1;

/* ═════════════ 3.  Núcleo (SELECT con todos los alias) ═════════════ */
$core = "
SELECT
  p.id                                            AS id_py,
  u.usuario                                       AS codigo_docente,
  CONCAT(u.nombres,' ',u.apellidos)               AS coordinador,
  per.nombre                                      AS periodo,
  IFNULL(NULLIF(p.p2,''),'No se ha registrado en presentación de proyecto') AS titulo_pres,
  IFNULL(NULLIF(pf.titulo,''),'No se ha registrado en informe semestral')   AS titulo_inf,
  f.nombre                                        AS facultad,
  d.nombre                                        AS departamento,

  CASE
      WHEN rp.oficina_actual = 'pcf' AND p.estado = 1
           THEN 'pcf'
      WHEN rp.oficina_actual = 'pcf' AND p.estado = 0
           AND (ev_pcf_cot.estado = 'observado'
             OR ev_pcf_rub.estado = 'observado')
           THEN 'pcf'
      WHEN rp.oficina_actual = 'pcf'
           THEN NULL
      ELSE rp.oficina_actual
  END                                             AS oficina_real,

  p.estado                                        AS estado_global,

  ev_pcf_cot.estado AS pcf_cot,
  ev_pcf_rub.estado AS pcf_rub,
  ev_dd_vb.estado   AS dd_vb,
  ev_df_vb.estado   AS df_vb,
  ev_rsu_cot.estado AS rsu_cot,
  ev_rsu_rub.estado AS rsu_rub,

  obs_pcf_cot.cotejo_pcf  AS pcf_cot_obs,
  obs_pcf_rub.rubrica_pcf AS obs_rubrica_pcf,
  obs_rsu_cot.cotejo_rsu  AS rsu_cot_obs,
  obs_rsu_rub.rubrica_rsu AS rsu_rubrica_rsu

FROM proyectos p
JOIN usuarios_proyectos   up ON up.id_proyecto = p.id
JOIN usuarios             u  ON u.id          = up.id_usuario
JOIN departamentos        d  ON d.id          = u.id_depa
JOIN facultades           f  ON f.id          = d.id_facultad
JOIN proyectos_periodo    pp ON pp.id_py      = p.id
JOIN periodos             per ON per.id       = pp.id_periodo
LEFT JOIN proyectos_finales     pf ON pf.id_py = p.id
LEFT JOIN revisiones_proyectos  rp ON rp.id_py = p.id AND rp.id_periodo = pp.id_periodo

LEFT JOIN evaluaciones ev_pcf_cot
       ON ev_pcf_cot.id_py = p.id AND ev_pcf_cot.id_periodo = pp.id_periodo
       AND ev_pcf_cot.oficina='pcf' AND ev_pcf_cot.tipo='cotejo'
LEFT JOIN evaluaciones ev_pcf_rub
       ON ev_pcf_rub.id_py = p.id AND ev_pcf_rub.id_periodo = pp.id_periodo
       AND ev_pcf_rub.oficina='pcf' AND ev_pcf_rub.tipo='rubrica'
LEFT JOIN evaluaciones ev_dd_vb
       ON ev_dd_vb.id_py = p.id AND ev_dd_vb.id_periodo = pp.id_periodo
       AND ev_dd_vb.oficina='dd' AND ev_dd_vb.tipo='vb'
LEFT JOIN evaluaciones ev_df_vb
       ON ev_df_vb.id_py = p.id AND ev_df_vb.id_periodo = pp.id_periodo
       AND ev_df_vb.oficina='df' AND ev_df_vb.tipo='vb'
LEFT JOIN evaluaciones ev_rsu_cot
       ON ev_rsu_cot.id_py = p.id AND ev_rsu_cot.id_periodo = pp.id_periodo
       AND ev_rsu_cot.oficina='rsu' AND ev_rsu_cot.tipo='cotejo'
LEFT JOIN evaluaciones ev_rsu_rub
       ON ev_rsu_rub.id_py = p.id AND ev_rsu_rub.id_periodo = pp.id_periodo
       AND ev_rsu_rub.oficina='rsu' AND ev_rsu_rub.tipo='rubrica'

LEFT JOIN (
  SELECT e.id_py,e.id_periodo,
         GROUP_CONCAT(o.observacion SEPARATOR ' | ') AS cotejo_pcf
  FROM evaluaciones e
  JOIN observaciones_cotejo o ON o.id_evaluacion=e.id
  WHERE e.oficina='pcf' AND e.tipo='cotejo'
  GROUP BY e.id_py,e.id_periodo
) obs_pcf_cot
ON obs_pcf_cot.id_py = p.id AND obs_pcf_cot.id_periodo = pp.id_periodo

LEFT JOIN (
  SELECT e.id_py,e.id_periodo,
         GROUP_CONCAT(o.observacion SEPARATOR ' | ') AS cotejo_rsu
  FROM evaluaciones e
  JOIN observaciones_cotejo o ON o.id_evaluacion=e.id
  WHERE e.oficina='rsu' AND e.tipo='cotejo'
  GROUP BY e.id_py,e.id_periodo
) obs_rsu_cot
ON obs_rsu_cot.id_py = p.id AND obs_rsu_cot.id_periodo = pp.id_periodo

LEFT JOIN (
  SELECT e.id_py,e.id_periodo,
         GROUP_CONCAT(ra.observacion SEPARATOR ' | ') AS rubrica_pcf
  FROM evaluaciones e
  JOIN rubrica_aspectos ra ON ra.id_evaluacion=e.id
  WHERE e.oficina='pcf' AND e.tipo='rubrica' AND ra.observacion<>'' 
  GROUP BY e.id_py,e.id_periodo
) obs_pcf_rub
ON obs_pcf_rub.id_py = p.id AND obs_pcf_rub.id_periodo = pp.id_periodo

LEFT JOIN (
  SELECT e.id_py,e.id_periodo,
         GROUP_CONCAT(ra.observacion SEPARATOR ' | ') AS rubrica_rsu
  FROM evaluaciones e
  JOIN rubrica_aspectos ra ON ra.id_evaluacion=e.id
  WHERE e.oficina='rsu' AND e.tipo='rubrica' AND ra.observacion<>'' 
  GROUP BY e.id_py,e.id_periodo
) obs_rsu_rub
ON obs_rsu_rub.id_py = p.id AND obs_rsu_rub.id_periodo = pp.id_periodo
";

/* ═════════════ 4-A.  Conteo total ═════════════ */
$sqlTotal = "SELECT COUNT(*) tot FROM ( $core ) base $where_sql";
$total    = (int)mysqli_fetch_assoc(mysqli_query($conexion,$sqlTotal))['tot'];
$paginas  = $total ? (int)ceil($total/$limite) : 1;
if ($pagina > $paginas) $pagina = $paginas;
$inicio   = ($pagina-1)*$limite;

/* ═════════════ 4-B.  Consulta paginada ═════════════ */
$sql = "
SELECT * FROM ( $core ) base
$where_sql
ORDER BY id_py
LIMIT $inicio,$limite
";
$result = mysqli_query($conexion,$sql) or die('Error SQL: '.mysqli_error($conexion));

/* ═════════════ 5.  Helpers visuales ═════════════ */
function badge($s){
    if ($s==='aprobado')  return '<span class=\"badge badge-success\">aprobado</span>';
    if ($s==='observado') return '<span class=\"badge badge-danger\">observado</span>';
    return '<span class=\"badge badge-primary\">en espera</span>';
}

$labels_ofi = [
  null => ['label'=>'Sin oficina','bg'=>'#6C757D','color'=>'white'],
  ''   => ['label'=>'Sin oficina','bg'=>'#6C757D','color'=>'white'],
  'pcf'=> ['label'=>'Comité de Facultad','bg'=>'#0275D8','color'=>'white'],
  'dd' => ['label'=>'Dirección de Departamento','bg'=>'#F0AD4E','color'=>'black'],
  'df' => ['label'=>'Decanato de Facultad','bg'=>'#5BC0DE','color'=>'black'],
  'rsu'=> ['label'=>'Dirección de RSU','bg'=>'#5CB85C','color'=>'white']
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Listado de proyectos</title>
  <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body class="p-3">

<!-- ═════════════ 6.  Formulario de filtros ═════════════ -->
<form method="get" class="mb-3">
  <div class="form-row align-items-end">
    <div class="col-md-3">
      <label for="filtro_periodo">Período</label>
      <select name="filtro_periodo" id="filtro_periodo" class="form-control">
        <option value="">Todos</option>
        <option value="2024-II" <?= $f_per==='2024-II'?'selected':'' ?>>2024-II</option>
        <option value="2025-I" <?= $f_per==='2025-I'?'selected':'' ?>>2025-I</option>
      </select>
    </div>

    <div class="col-md-4">
      <label for="filtro_facultad">Facultad</label>
      <select name="filtro_facultad" id="filtro_facultad" class="form-control">
        <option value="">Todas</option>
        <?php
          $rsFac = mysqli_query($conexion,"SELECT nombre FROM facultades ORDER BY nombre");
          while($f = mysqli_fetch_assoc($rsFac)){
            $sel = ($f_fac === $f['nombre']) ? 'selected' : '';
            echo '<option value="'.htmlspecialchars($f['nombre']).'" '.$sel.'>'
                 .htmlspecialchars($f['nombre']).'</option>';
          }
        ?>
      </select>
    </div>

    <div class="col-md-4">
  <label for="filtro_departamento">Departamento</label>
  <select name="filtro_departamento"
          id="filtro_departamento"
          class="form-control">
      <option value="">Todos</option>
      <?php
        $rsDep = mysqli_query($conexion, "
            SELECT d.nombre AS dep, f.nombre AS fac
              FROM departamentos d
              JOIN facultades f ON f.id = d.id_facultad
          ORDER BY f.nombre, d.nombre");
        while ($d = mysqli_fetch_assoc($rsDep)) {
            $sel = ($f_dep === $d['dep']) ? 'selected' : '';
            echo '<option value="'.htmlspecialchars($d['dep']).'" '
                .'data-fac="'.htmlspecialchars($d['fac']).'" '.$sel.'>'
                .htmlspecialchars($d['dep']).'</option>';
        }
      ?>
  </select>
</div>

    <div class="col-md-3">
      <label for="filtro_oficina">Oficina</label>
      <select name="filtro_oficina" id="filtro_oficina" class="form-control">
        <option value="">Todas</option>
        <option value="sin"      <?= $f_ofi==='sin'?'selected':''      ?>>Sin oficina</option>
        <option value="pcf"      <?= $f_ofi==='pcf'?'selected':''      ?>>Comité de Facultad</option>
        <option value="dd"       <?= $f_ofi==='dd'?'selected':''       ?>>Dirección de Departamento</option>
        <option value="df"       <?= $f_ofi==='df'?'selected':''       ?>>Decanato de Facultad</option>
        <option value="rsu"      <?= $f_ofi==='rsu'?'selected':''      ?>>Dirección RSU</option>
        <option value="aprobado" <?= $f_ofi==='aprobado'?'selected':'' ?>>Aprobación total</option>
      </select>
    </div>

    <div class="col-md-2">
      <button type="submit" class="btn btn-primary btn-block">
        <i class="fas fa-filter"></i> Filtrar
      </button>
      <a href="?pag=1" class="btn btn-secondary btn-block mt-1">
        <i class="fas fa-broom"></i> Limpiar
      </a>
    </div>
  </div>
</form>

<!-- ═══ Botones de exportación (JS) ═══ -->
<div class="mb-2 d-flex justify-content-end">
  <button id="btnExcel" class="btn btn-success btn-sm mr-2">
    <i class="fas fa-file-excel"></i> Excel
  </button>
  <button id="btnPDF" class="btn btn-danger btn-sm">
    <i class="fas fa-file-pdf"></i> PDF
  </button>
</div>

<!-- ═════════════ 7.  Tabla ═════════════ -->
<div class="table-responsive">
<table class="table table-bordered table-sm">
  <thead class="thead-dark">
    <tr>
      <th>#</th><th>CÓDIGO DOCENTE</th><th>COORDINADOR</th><th>PERÍODO</th>
      <th>ID&nbsp;PY</th><th>TÍTULO</th><th>FACULTAD</th><th>DEPARTAMENTO</th>
      <th>OFICINA</th><th>Estado en Oficina</th><th>OBSERVACIÓN</th>
    </tr>
  </thead>
  <tbody>
<?php
$contador = $inicio+1;
while($r = mysqli_fetch_assoc($result)):
  $ofi = $labels_ofi[$r['oficina_real']] ?? ['label'=>$r['oficina_real'],'bg'=>'#999','color'=>'white'];
?>
    <tr>
      <td><?= $contador++ ?></td>
      <td><?= htmlspecialchars($r['codigo_docente']) ?></td>
      <td><?= htmlspecialchars($r['coordinador']) ?></td>
      <td><?= htmlspecialchars($r['periodo']) ?></td>
      <td><?= $r['id_py'] ?></td>
      <td><?= htmlspecialchars($r['titulo_pres']) ?><br><em><?= htmlspecialchars($r['titulo_inf']) ?></em></td>
      <td><?= htmlspecialchars($r['facultad']) ?></td>
      <td><?= htmlspecialchars($r['departamento']) ?></td>
      <td><span class="badge" style="background:<?= $ofi['bg'] ?>;color:<?= $ofi['color'] ?>;">
            <?= $ofi['label'] ?></span></td>
      <td>
<?php
  switch($r['oficina_real']){
    case null: case '':
      echo 'No solicitó evaluación'; break;
    case 'pcf':
      echo 'Cotejo: '.badge($r['pcf_cot']).'<br>Rúbrica: '.badge($r['pcf_rub']); break;
    case 'dd':
      echo 'Visto Bueno: '.badge($r['dd_vb']); break;
    case 'df':
      echo 'Visto Bueno: '.badge($r['df_vb']); break;
    case 'rsu':
      echo 'Cotejo: '.badge($r['rsu_cot']).'<br>Rúbrica: '.badge($r['rsu_rub']); break;
  }
  if ($r['estado_global']==2){
    echo '<br><span class="badge badge-warning">Aprobación '.$r['periodo'].'</span>';
  }
?>
      </td>
      <td>
<?php
  if ($r['estado_global']==2){
      echo '—';
  }elseif(!$r['oficina_real']){
      echo 'Sin observación';
  }elseif($r['oficina_real']==='pcf'){
      echo '<strong>Cotejo:</strong> '.($r['pcf_cot_obs'] ? htmlspecialchars($r['pcf_cot_obs']) : 'Sin observación').'<br>';
      echo '<strong>Rúbrica:</strong> '.($r['obs_rubrica_pcf'] ? htmlspecialchars($r['obs_rubrica_pcf']) : 'Sin observación');
  }elseif($r['oficina_real']==='rsu'){
      echo '<strong>Cotejo:</strong> '.($r['rsu_cot_obs'] ? htmlspecialchars($r['rsu_cot_obs']) : 'Sin observación').'<br>';
      echo '<strong>Rúbrica:</strong> '.($r['rsu_rubrica_rsu'] ? htmlspecialchars($r['rsu_rubrica_rsu']) : 'Sin observación');
  }else{
      echo '—';
  }
?>
      </td>
    </tr>
<?php endwhile; ?>
  </tbody>
</table>
</div>

<!-- ═════════════ 8.  Paginación ═════════════ -->
<?php
$qBase = $_GET; unset($qBase['pag']);
?>
<nav>
  <ul class="pagination pagination-sm justify-content-center">
<?php for($i=1;$i<=$paginas;$i++):
        $qBase['pag']=$i; $link='?'.http_build_query($qBase); ?>
    <li class="page-item <?= $i==$pagina?'active':'' ?>">
      <a class="page-link" href="<?= $link ?>"><?= $i ?></a>
    </li>
<?php endfor; ?>
  </ul>
</nav>
<!-- SheetJS FULL (con utilidades) -->
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<!-- html2pdf (html2canvas + jsPDF) -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', () => {

/* === Excel === */
document.getElementById('btnExcel').addEventListener('click', () => {
    // 1) tabla HTML
    const tabla = document.querySelector('.table-responsive table');

    // 2) hoja desde la tabla
    const ws = XLSX.utils.table_to_sheet(tabla, {raw:true});

    // 3) libro nuevo + añadimos hoja
    const wb = XLSX.utils.book_new();
    XLSX.utils.book_append_sheet(wb, ws, 'Proyectos');

    // 4) descarga
    XLSX.writeFile(wb, 'proyectos.xlsx');
});


  /* === PDF === */
  document.getElementById('btnPDF').addEventListener('click', () => {
      const tabla = document.querySelector('.table-responsive');   // contenedor
      const opt = {
        margin:       5,
        filename:     'proyectos.pdf',
        html2canvas:  { scale: 2 },            // mejor nitidez
        jsPDF:        { unit: 'mm', format:'a4', orientation:'landscape' }
      };
      html2pdf().set(opt).from(tabla).save();
  });

});

/* === Sincronizar Facultad ↔ Departamento === */
const $dep = $('#filtro_departamento');
const $fac = $('#filtro_facultad');

function syncFacWithDep() {
    const fac = $dep.find('option:selected').data('fac');
    if ($dep.val() !== '' && fac) {
        $fac.val(fac);
    }
}

$dep.on('change', function () {
    if (this.value !== '') {
        syncFacWithDep();
    }
});

$fac.on('change', function () {
    /* Si había un departamento elegido y el usuario forzó otra facultad,
       reseteamos el depto a 'Todos' */
    if ($dep.val() !== '' && $fac.val() !== $dep.find('option:selected').data('fac')) {
        $dep.val('');
    }
});

/* Sincronizar al cargar la página por si la URL trae un depto */
if ($dep.val() !== '') { syncFacWithDep(); }

</script>

</body>
</html>