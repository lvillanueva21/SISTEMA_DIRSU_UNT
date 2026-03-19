<?php
/*-------------------------------------------------------
|   Calculadora de semestres  –  salida: texto plano
|   Formato devuelto:
|   #PROY          (marca de bloque)
|   2024-II|Presentación de proyecto
|   2025-I|Informe semestral 01
|   ...
|   #FINAL
|   2026-I|Informe semestral 04
|   2026-II|Informe Final
--------------------------------------------------------*/
header('Content-Type: text/plain; charset=utf-8');
require_once '../../componentes/db.php';

$id = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
if (!$id) { exit('ERROR: ID inválido'); }

/* ---------- helpers ---------- */
function toDate($str){                      // 29/07/2024 → DateTime
    return DateTime::createFromFormat('d/m/Y', $str) ?: false;
}
function semestre(DateTime $d){
    return $d->format('Y') . ($d->format('n')<=6 ? '-I' : '-II');
}
function rangoSemestres(DateTime $ini, DateTime $fin){
    $out=[]; $c=clone $ini;
    while($c<=$fin){ $out[]=semestre($c); $c->modify('+6 months'); }
    return $out;
}
function etiqueta($i,$n){
    if ($i==0)       return 'Presentación de proyecto';
    if ($i+1==$n)    return 'Informe Final';
    return 'Informe semestral '.str_pad($i,2,'0',STR_PAD_LEFT);
}
function buildLines($ini,$fin){
    if(!$ini||!$fin) return [];
    $labs=rangoSemestres($ini,$fin); $out=[];
    foreach($labs as $k=>$s) $out[]=$s.'|'.etiqueta($k,count($labs));
    return $out;
}

/* ---------- datos tabla proyectos ---------- */
$row = mysqli_fetch_assoc(
        mysqli_query($conexion,"SELECT fecha_inicio,fecha_fin FROM proyectos WHERE id=$id"));
$fiP = toDate($row['fecha_inicio']??'');
$ffP = toDate($row['fecha_fin']??'');

/* ---------- datos tabla proyectos_finales ---------- */
$rowF = mysqli_fetch_assoc(
        mysqli_query($conexion,"SELECT fecha_inicio,fecha_fin FROM proyectos_finales WHERE id_py=$id LIMIT 1"));
$fiF = toDate($rowF['fecha_inicio']??'');
$ffF = toDate($rowF['fecha_fin']??'');

/* ---------- salida ---------- */
echo "#PROY\n";
echo join("\n", buildLines($fiP,$ffP));
echo "\n#FINAL\n";
echo join("\n", buildLines($fiF,$ffF));
