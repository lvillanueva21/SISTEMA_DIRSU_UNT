<?php
/*------------------------------------------------------------------
| Devuelve información de un proyecto en 3 “modos”                 |
|  - modo = ficha    → tabla con datos generales                    |
|  - modo = fechas  → tabla con fechas de proyectos / finales       |
|  - modo = todo    → ambas tablas juntas (para el modal unificado) |
*-----------------------------------------------------------------*/
header('Content-Type: text/html; charset=utf-8');
require_once '../../componentes/db.php';

$id   = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
$modo = $_POST['modo'] ?? 'ficha';

if (!$id){
    exit('<div class="text-danger">ID inválido</div>');
}

/* ───────── consulta única ───────── */
$sql = "
SELECT  p.id                AS id_py,
        u.usuario           AS codigo,
        CONCAT(u.nombres,' ',u.apellidos) AS coordinador,
        f.nombre            AS facultad,
        d.nombre            AS departamento,
        p.p2                AS titulo,
        p.estado,
        p.fecha_inicio  AS fi_proy,
        p.fecha_fin     AS ff_proy,
        pf.fecha_inicio AS fi_final,
        pf.fecha_fin    AS ff_final
FROM      proyectos p
LEFT JOIN proyectos_finales pf ON pf.id_py = p.id
JOIN      usuarios_proyectos up ON up.id_proyecto = p.id
JOIN      usuarios           u  ON u.id          = up.id_usuario
JOIN      departamentos      d  ON d.id          = u.id_depa
JOIN      facultades         f  ON f.id          = d.id_facultad
WHERE     p.id = ? LIMIT 1";

$stmt = mysqli_prepare($conexion,$sql);
mysqli_stmt_bind_param($stmt,'i',$id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (!$row = mysqli_fetch_assoc($res)){
    exit('<div class="text-danger">Proyecto no encontrado</div>');
}

/* ========= helpers ========= */
function estadoTxt($e){
    return match((int)$e){
        2 => 'Aprobado',
        1 => 'En Revisión',
        default => 'Sin Revisión'
    };
}
function h($t){ return htmlspecialchars($t,ENT_QUOTES,'UTF-8'); }

function generarSemestres($inicio, $fin) {
    $semestres = [];
    $start = DateTime::createFromFormat('d/m/Y', $inicio);
    $end   = DateTime::createFromFormat('d/m/Y', $fin);

    if (!$start || !$end || $start > $end) return $semestres;

    while ($start <= $end) {
        $anio = $start->format('Y');
        $mes  = (int)$start->format('m');
        $tipo = $mes <= 6 ? 'I' : 'II';
        $clave = "$anio-$tipo";
        if (!in_array($clave, $semestres)) {
            $semestres[] = $clave;
        }
        // Avanza al siguiente semestre
        if ($mes <= 6) {
            $start->setDate((int)$anio, 7, 1);
        } else {
            $start->modify('+7 months')->setDate((int)$anio + 1, 1, 1);
        }
    }
    return $semestres;
}

function construirTablaSemestres($semestres, $origen = '') {
    $total = count($semestres);
    $mensaje = '';
    if ($total < 4) {
        $mensaje = "<div class='text-danger font-weight-bold'>⚠️ El proyecto cuenta con <b>$total</b> semestres, esto es menor al número mínimo permitido por DIRSU.</div>";
    } elseif ($total > 10) {
        $mensaje = "<div class='text-danger font-weight-bold'>⚠️ El proyecto cuenta con <b>$total</b> semestres, esto excede el máximo permitido por DIRSU.</div>";
    }

    $etiquetas = [];
    foreach ($semestres as $i => $sem) {
        if ($i === 0) {
            $etiquetas[] = ['sem' => $sem, 'desc' => 'Informe de Presentación de Proyecto', 'color' => 'blue'];
            $etiquetas[] = ['sem' => $sem, 'desc' => 'Informe Semestral 01', 'color' => 'green'];
        } elseif ($i === $total - 1) {
            $etiquetas[] = ['sem' => $sem, 'desc' => 'Informe Semestral / Final', 'color' => 'black'];
        } else {
            $n = str_pad(count(array_filter($etiquetas, fn($e) => str_starts_with($e['desc'], 'Informe Semestral')))+1, 2, '0', STR_PAD_LEFT);
            $etiquetas[] = ['sem' => $sem, 'desc' => "Informe Semestral $n", 'color' => 'green'];
        }
    }

    ob_start();
    ?>
    <div class="semestre-box p-2">
      <h6 class="text-center font-weight-bold">📋 Detalle de Informes por Semestre <?= $origen ? "($origen)" : '' ?></h6>
      <?= $mensaje ?>
      <div class="row mt-3">
        <?php foreach (array_chunk($etiquetas, 3) as $fila): ?>
          <div class="col-sm-12 d-flex justify-content-center mb-2">
            <table class="table table-bordered text-center w-auto m-0" style="table-layout: fixed;">
              <tr>
                <?php foreach ($fila as $s): ?>
                  <td style="background-color: <?= $s['color'] ?>; color: white; width: 120px;">
                    <?= $s['sem'] ?>
                  </td>
                <?php endforeach; ?>
              </tr>
              <tr>
                <?php foreach ($fila as $s): ?>
                  <td style="width: 120px;">
                    <?= nl2br($s['desc']) ?>
                  </td>
                <?php endforeach; ?>
              </tr>
            </table>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
    <?php
    return ob_get_clean();
}

/* ========= plantilla ficha ========= */
ob_start();
?>
<table class="table table-bordered table-sm mb-3">
  <thead class="thead-dark">
    <tr>
      <th>ID<br>proyecto</th>
      <th>Código<br>docente</th>
      <th>Coordinador</th>
      <th>Facultad&nbsp;/&nbsp;Dep.</th>
      <th>Título</th>
      <th>Estado</th>
    </tr>
  </thead>
  <tr>
    <td><?= h($row['id_py']) ?></td>
    <td><?= h($row['codigo']) ?></td>
    <td><?= h($row['coordinador']) ?></td>
    <td><?= h($row['facultad'].' / '.$row['departamento']) ?></td>
    <td><?= h($row['titulo']) ?></td>
    <td><?= h(estadoTxt($row['estado'])) ?></td>
  </tr>
</table>
<?php
$fichaHTML = ob_get_clean();

/* ========= plantilla fechas ========= */
ob_start();
?>
<div class="row">
  <div class="col-sm-6 mb-3">
    <h6 class="text-center text-primary font-weight-bold mb-1">Según proyectos</h6>
    <table class="table table-bordered table-sm text-center">
      <tr class="bg-primary text-white">
        <th>Inicio</th><th>Fin</th>
      </tr>
      <tr>
        <td><?= $row['fi_proy'] ?: '—' ?></td>
        <td><?= $row['ff_proy'] ?: '—' ?></td>
      </tr>
    </table>
  </div>

  <div class="col-sm-6 mb-3">
    <h6 class="text-center text-success font-weight-bold mb-1">Según proyectos finales</h6>
    <table class="table table-bordered table-sm text-center">
      <tr class="bg-success text-white">
        <th>Inicio</th><th>Fin</th>
      </tr>
      <tr>
        <td><?= $row['fi_final'] ?: '—' ?></td>
        <td><?= $row['ff_final'] ?: '—' ?></td>
      </tr>
    </table>
  </div>
</div>
<?php
$fechasHTML = ob_get_clean();

$semestresProy = generarSemestres($row['fi_proy'], $row['ff_proy']);
$semestresFinal = generarSemestres($row['fi_final'], $row['ff_final']);
$semestresHTML1 = construirTablaSemestres($semestresProy, 'según proyectos');
$semestresHTML2 = construirTablaSemestres($semestresFinal, 'según proyectos finales');

$semestresFlexLayout = <<<HTML
<div class="d-flex flex-wrap justify-content-between gap-3">
  <div class="flex-fill me-2">$semestresHTML1</div>
  <div class="flex-fill">$semestresHTML2</div>
</div>
HTML;

switch($modo){
    case 'ficha':   echo $fichaHTML; break;
    case 'fechas':  echo $fechasHTML; break;
    case 'todo':    echo $fichaHTML . $fechasHTML . $semestresFlexLayout; break;
    default:        echo '<div class="text-danger">Modo no soportado</div>';
}
