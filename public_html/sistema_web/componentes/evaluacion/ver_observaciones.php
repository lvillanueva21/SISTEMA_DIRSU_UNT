<?php
require_once __DIR__.'/../db.php';          // ← misma ruta que en monitoreo
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$id_py = (int)($_GET['id_py'] ?? 0);
if(!$id_py){ echo '<div class="alert alert-warning">ID inválido</div>'; exit; }

// Traer las evaluaciones del proyecto (última por oficina/tipo)
$sql = "SELECT e.id, e.oficina, e.tipo, e.estado,
               DATE_FORMAT(e.fecha_evaluacion,'%d/%m/%Y %H:%i') AS fecha,
               DATE_FORMAT(e.fecha_limite,'%d/%m/%Y %H:%i') AS fecha_limite
        FROM evaluaciones e
        WHERE e.id_py=$id_py
        ORDER BY e.fecha_evaluacion DESC";
$evals = mysqli_query($conexion,$sql);

// Observaciones de lista de cotejo
$obsCot = [];
$resCot = mysqli_query($conexion,"SELECT oc.id_evaluacion,oc.observacion,
        oc.dias_subsanacion FROM observaciones_cotejo oc
        JOIN evaluaciones e ON e.id=oc.id_evaluacion
        WHERE e.id_py=$id_py");
while($o=mysqli_fetch_assoc($resCot)) $obsCot[$o['id_evaluacion']]=$o;

// Observaciones de rúbrica (por aspecto)
$obsRub = [];
$resRub = mysqli_query($conexion,"SELECT ra.id_evaluacion,ra.aspecto,
        ra.puntaje,ra.observacion FROM rubrica_aspectos ra
        JOIN evaluaciones e ON e.id=ra.id_evaluacion
        WHERE e.id_py=$id_py
        ORDER BY ra.id_evaluacion,ra.aspecto");
while($r=mysqli_fetch_assoc($resRub))
    $obsRub[$r['id_evaluacion']][]=$r;
?>
<div class="table-responsive">
<table class="table table-sm table-bordered">
<thead class="thead-light"><tr>
 <th>Oficina</th><th>Tipo</th><th>Estado</th><th>Fecha</th><th>Detalle / Observación</th></tr></thead><tbody>
<?php while($e=mysqli_fetch_assoc($evals)): ?>
<tr>
 <td><?=strtoupper($e['oficina'])?></td>
 <td><?=$e['tipo']?></td>
 <td><?=$e['estado']?></td>
 <td><?=$e['fecha']?></td>
 <td>
   <?php
     if($e['tipo']=='cotejo' && isset($obsCot[$e['id']])){
         $o=$obsCot[$e['id']];
         echo '<b>Obs. ('.$o['dias_subsanacion'].' días)</b><br>';
echo '<div>'.htmlspecialchars($o['observacion']).'</div>';
echo '<div class="text-muted small">Fecha límite: '.($e['fecha_limite'] ?? '—').'</div>';
     }elseif($e['tipo']=='rubrica' && isset($obsRub[$e['id']])){
         echo '<ul class=\"mb-0 pl-3\">';
         foreach($obsRub[$e['id']] as $r){
           if(trim($r['observacion'])!==''){
             echo '<li><b>'.ucfirst($r['aspecto']).'</b> ('.$r['puntaje'].'): '.htmlspecialchars($r['observacion']).'</li>';
           }
         }
         echo '</ul>';
     }else{
         echo '—';
     }
   ?>
 </td>
</tr>
<?php endwhile; ?>
</tbody></table>
</div>
