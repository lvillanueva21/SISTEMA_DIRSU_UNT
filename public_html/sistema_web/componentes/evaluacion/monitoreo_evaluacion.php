<?php
/*  componentes/evaluacion/monitoreo_evaluacion.php
-------------------------------------------------------------------
  MONITOREO GLOBAL DE EVALUACIÓN  –  DIRSU v2.2
  Esta versión es **100 % abierta**: no usa sesiones ni filtros por perfil.
  Lista todos los proyectos del período activo y muestra:
    • Oficina en la que se encuentran
    • Fechas de llegada / salida del flujo
    • Estado + fecha de la última evaluación en cada oficina (PCF, DD, DF, RSU)
    • Acceso a las observaciones registradas (cotejo / rúbrica / vb)

  Requisitos front‑end: Bootstrap 4+, jQuery 3+, DataTables 1.13+
  Acceso directo a la BD mediante  componentes/db.php
-------------------------------------------------------------------*/

require_once __DIR__ . '/../db.php';   // conexión bruta sin seguridad
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// ────────── parámetros GET ──────────
$limite = isset($_GET['l']) && in_array($_GET['l'],[5,10,100,0]) ? (int)$_GET['l'] : 10; // 0 = todas
$pagina = isset($_GET['p']) ? max((int)$_GET['p'],1) : 1;
$id_bus = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$inicio = $limite? ($pagina-1)*$limite : 0;

// ────────── período activo ──────────
$periodo = (int)mysqli_fetch_row(mysqli_query($conexion,"SELECT id FROM periodos WHERE activo=1 LIMIT 1"))[0] ?? 0;

// ────────── filtros opcionales ──────────
$where = $id_bus ? "WHERE p.id = $id_bus" : '';

// Helper para última evaluación
$lastEval = function($oficina,$tipo=null) use ($periodo){
  $tipoCond = $tipo? " AND e.tipo='$tipo'" : '';
  return "(SELECT e.estado FROM evaluaciones e WHERE e.id_py=p.id AND e.id_periodo=$periodo AND e.oficina='$oficina'$tipoCond ORDER BY e.fecha_evaluacion DESC LIMIT 1) AS {$oficina}_estado,
          (SELECT DATE_FORMAT(e.fecha_evaluacion,'%d/%m/%Y %H:%i') FROM evaluaciones e WHERE e.id_py=p.id AND e.id_periodo=$periodo AND e.oficina='$oficina'$tipoCond ORDER BY e.fecha_evaluacion DESC LIMIT 1) AS {$oficina}_fecha";
};

// ────────── consulta principal ──────────
$sql = "SELECT p.id,
               COALESCE(p.p2,'— sin título —') AS titulo,
               rp.oficina_actual,
               DATE_FORMAT(rp.fecha_solicitud,'%d/%m/%Y %H:%i') AS inicio_flujo,
               DATE_FORMAT(rp.fecha_cierre,  '%d/%m/%Y %H:%i') AS fin_flujo,
               {$lastEval('pcf')},
               {$lastEval('dd','vb')},
               {$lastEval('df','vb')},
               {$lastEval('rsu')}
        FROM proyectos p
        JOIN proyectos_periodo pp ON pp.id_py=p.id AND pp.id_periodo=$periodo
        JOIN revisiones_proyectos rp ON rp.id_py=p.id AND rp.id_periodo=$periodo
        $where
        ORDER BY p.id ASC".
        ($limite? " LIMIT $inicio,$limite" : '');

$result = mysqli_query($conexion,$sql);
$total  = (int)mysqli_fetch_row(mysqli_query($conexion,
          "SELECT COUNT(*) FROM proyectos p JOIN proyectos_periodo pp ON pp.id_py=p.id AND pp.id_periodo=$periodo $where"))[0];
$paginas = $limite? ceil(max($total,1)/$limite) : 1;
?>
<!DOCTYPE html><html lang="es"><head>
<meta charset="utf-8"><title>Monitoreo Evaluación – DIRSU</title>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/css/bootstrap.min.css"/>
<link rel="stylesheet" href="https://cdn.datatables.net/1.13.8/css/dataTables.bootstrap4.min.css"/>
</head><body class="p-3">
<h4 class="mb-3">Monitoreo de Evaluación de Proyectos (Período activo)</h4>

<form class="form-inline mb-2" method="get">
  <label class="mr-2">ID Proyecto:</label>
  <input type="number" name="id" value="<?=htmlspecialchars($id_bus)?>" class="form-control mr-2"/>
  <label class="mr-2">Mostrar:</label>
  <select name="l" class="form-control mr-2">
    <?php foreach([5,10,100,0=>'Todas'] as $v=>$txt){ $sel=($limite==$v||($v===0&&$limite==0))?'selected':''; echo "<option value='$v' $sel>$txt</option>";?>
  </select>
  <button class="btn btn-primary">Aplicar</button>
</form>

<table id="tbl" class="table table-sm table-bordered table-hover">
<thead class="thead-dark"><tr>
 <th>ID</th><th>Título</th><th>Oficina&nbsp;actual</th><th>Inicio flujo</th><th>Fin flujo</th>
 <th>PCF&nbsp;estado</th><th>PCF&nbsp;fecha</th>
 <th>DD&nbsp;estado</th><th>DD&nbsp;fecha</th>
 <th>DF&nbsp;estado</th><th>DF&nbsp;fecha</th>
 <th>RSU&nbsp;estado</th><th>RSU&nbsp;fecha</th>
 <th>Obs.</th></tr></thead><tbody>
<?php while($r=mysqli_fetch_assoc($result)): ?>
<tr>
 <td><?=$r['id']?></td>
 <td><?=htmlspecialchars($r['titulo'])?></td>
 <td><?=$r['oficina_actual']?></td>
 <td><?=$r['inicio_flujo']?></td>
 <td><?=$r['fin_flujo']?></td>
 <td><?=$r['pcf_estado']??'‑'?></td><td><?=$r['pcf_fecha']??'‑'?></td>
 <td><?=$r['dd_estado']??'‑'?></td><td><?=$r['dd_fecha']??'‑'?></td>
 <td><?=$r['df_estado']??'‑'?></td><td><?=$r['df_fecha']??'‑'?></td>
 <td><?=$r['rsu_estado']??'‑'?></td><td><?=$r['rsu_fecha']??'‑'?></td>
 <td class="text-center"><button class="btn btn-info btn-sm verObs" data-id="<?=$r['id']?>">Obs.</button></td>
</tr>
<?php endwhile; ?>
</tbody></table>

<nav><ul class="pagination">
<?php if($limite) for($i=1;$i<=$paginas;$i++): $act=$i==$pagina?'active':''; ?>
  <li class="page-item <?=$act?>"><a class="page-link" href="?p=<?=$i?>&l=<?=$limite?>&id=<?=$id_bus?>"><?=$i?></a></li>
<?php endfor; ?>
</ul></nav>

<!-- Modal observaciones -->
<div class="modal fade" id="modalObs"><div class="modal-dialog modal-lg"><div class="modal-content">
<div class="modal-header"><h5 class="modal-title">Observaciones del proyecto <span id="obsId"></span></h5>
<button class="close" data-dismiss="modal">&times;</button></div>
<div class="modal-body" id="obsBody">Cargando...</div>
</div></div></div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.6.2/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.13.8/js/dataTables.bootstrap4.min.js"></script>
<script>
// DataTable solo para scroll horizontal; paginación la hacemos en servidor
$('#tbl').DataTable({paging:false,info:false,searching:false,scrollX:true});
$('.verObs').click(function(){
   const id=$(this).data('id'); $('#obsId').text(id);
   $('#obsBody').html('Cargando...');
   $.get('ver_observaciones.php',{id_py:id},function(html){ $('#obsBody').html(html); $('#modalObs').modal('show'); });
});
</script>
</body></html>
