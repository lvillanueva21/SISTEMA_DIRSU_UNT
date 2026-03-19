<?php 
// /sistema_web/evaluacion/ver_informe.php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../componentes/db.php';

$id_py = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_py <= 0) {
    echo "<div class='alert alert-danger m-3'>ID de proyecto inválido.</div>";
    exit;
}

// === Encabezado del proyecto ===
$proy = ['titulo' => '', 'coordinador' => '', 'cod_docente' => ''];
$sqlProy = "
  SELECT p.p2 AS titulo, u.nombres, u.apellidos, u.usuario AS cod_docente
  FROM usuarios_proyectos up
  JOIN proyectos p ON p.id = up.id_proyecto
  JOIN usuarios u   ON u.id = up.id_usuario
  WHERE up.id_proyecto = $id_py AND up.activo = 1
  LIMIT 1
";
if ($rs = mysqli_query($conexion, $sqlProy)) {
    if ($r = mysqli_fetch_assoc($rs)) {
        $proy['titulo']      = (string)$r['titulo'];
        $proy['coordinador'] = trim(($r['nombres'] ?? '').' '.($r['apellidos'] ?? ''));
        $proy['cod_docente'] = (string)($r['cod_docente'] ?? '');
    }
    mysqli_free_result($rs);
}

// === Catálogos para ODS y Programa-ODS ===
$ODS = [];      // id => nombre
$PROGS = [];    // id => nombre
$PROG_ODS = []; // programa_id => [ ['id'=>n,'nombre'=>'..'], ... ]

$q1 = mysqli_query($conexion, "SELECT id, nombre FROM ods ORDER BY id");
if ($q1) { while ($r = mysqli_fetch_assoc($q1)) { $ODS[(int)$r['id']] = $r['nombre']; } mysqli_free_result($q1); }
$q2 = mysqli_query($conexion, "SELECT id, nombre FROM programas WHERE activo=1 ORDER BY nombre");
if ($q2) { while ($r = mysqli_fetch_assoc($q2)) { $PROGS[(int)$r['id']] = $r['nombre']; } mysqli_free_result($q2); }
$q3 = mysqli_query($conexion, "
  SELECT po.programa_id, o.id AS ods_id, o.nombre AS ods_nombre
  FROM programa_ods po
  JOIN ods o ON o.id = po.ods_id
  ORDER BY po.programa_id, o.id
");
if ($q3) {
  while ($r = mysqli_fetch_assoc($q3)) {
    $pid = (int)$r['programa_id'];
    if (!isset($PROG_ODS[$pid])) $PROG_ODS[$pid] = [];
    $PROG_ODS[$pid][] = ['id' => (int)$r['ods_id'], 'nombre' => $r['ods_nombre']];
  }
  mysqli_free_result($q3);
}

// === Cabeceras de respuestas de este proyecto ===
$cabs = [];
$sqlCab = "
  SELECT r.id, r.id_formulario, r.estado, r.creado_at, r.actualizado_at, f.nombre AS formulario
  FROM sm_respuestas r
  JOIN sm_formularios f ON f.id = r.id_formulario
  WHERE r.id_py = $id_py
  ORDER BY f.nombre ASC, r.id ASC
";
if ($rs = mysqli_query($conexion, $sqlCab)) {
    while ($r = mysqli_fetch_assoc($rs)) $cabs[] = $r;
    mysqli_free_result($rs);
}

// === Helpers de presentación ===
function chip($txt) {
  return '<span class="badge badge-info mr-1 mb-1" style="font-weight:500;">'.htmlspecialchars($txt, ENT_QUOTES, 'UTF-8').'</span>';
}
function render_valor(array $row, array $ODS, array $PROGS, array $PROG_ODS): string {
    $tipo = $row['tipo'] ?? '';
    switch ($tipo) {
        case 'varchar':
            return htmlspecialchars((string)($row['val_varchar'] ?? ''), ENT_QUOTES, 'UTF-8');

        case 'longtext':
            $txt = (string)($row['val_longtext'] ?? '');
            $txt = trim($txt);
            return $txt !== '' ? $txt : '<em class="text-muted">Sin respuesta</em>'; // HTML sin escapar

        case 'tinyint':
            return ($row['val_tinyint'] !== null) ? htmlspecialchars((string)$row['val_tinyint']) : '<em class="text-muted">—</em>';

        case 'int':
            return ($row['val_int'] !== null) ? htmlspecialchars((string)$row['val_int']) : '<em class="text-muted">—</em>';

        case 'boolean':
            if ($row['val_boolean'] === null) return '<em class="text-muted">—</em>';
            return ((int)$row['val_boolean'] === 1) ? 'Sí' : 'No';

        case 'datetime':
            return !empty($row['val_datetime']) ? htmlspecialchars($row['val_datetime']) : '<em class="text-muted">—</em>';

        case 'date':
            return !empty($row['val_date']) ? htmlspecialchars($row['val_date']) : '<em class="text-muted">—</em>';

        case 'decimal':
            return ($row['val_decimal'] !== null) ? htmlspecialchars((string)$row['val_decimal']) : '<em class="text-muted">—</em>';

        case 'ubicacion':
            if (!empty($row['val_longtext'])) return nl2br(htmlspecialchars($row['val_longtext'], ENT_QUOTES, 'UTF-8'));
            if (!empty($row['val_varchar']))  return htmlspecialchars($row['val_varchar'], ENT_QUOTES, 'UTF-8');
            return '<em class="text-muted">—</em>';

        case 'pdf':
        case 'excel':
        case 'word':
            $u = trim((string)($row['archivo_url'] ?? ''));
            return $u !== '' ? '<code style="word-break:break-all;">'.htmlspecialchars($u, ENT_QUOTES, 'UTF-8').'</code>'
                             : '<em class="text-muted">No hay archivo</em>';

        case 'ods':
            $csv = trim((string)($row['val_varchar'] ?? ''));
            if ($csv === '') return '<em class="text-muted">—</em>';
            $ids = array_filter(array_map('intval', explode(',', $csv)));
            if (!$ids) return '<em class="text-muted">—</em>';
            $out = '';
            foreach ($ids as $id) {
              $nom = $ODS[$id] ?? 'ODS '.$id;
              $out .= chip("ODS $id — $nom");
            }
            return $out ?: '<em class="text-muted">—</em>';

        case 'programa_ods':
            $pid = (int)trim((string)($row['val_varchar'] ?? '0'));
            if ($pid <= 0) return '<em class="text-muted">—</em>';
            $pnom = $PROGS[$pid] ?? ('Programa #'.$pid);
            $out = '<div><strong>'.htmlspecialchars($pnom, ENT_QUOTES, 'UTF-8').'</strong></div>';
            $odsArr = $PROG_ODS[$pid] ?? [];
            if ($odsArr) {
              $out .= '<div class="mt-1">';
              foreach ($odsArr as $o) { $out .= chip('ODS '.$o['id'].' — '.$o['nombre']); }
              $out .= '</div>';
            } else {
              $out .= '<div class="text-muted"><em>Sin ODS asociados</em></div>';
            }
            return $out;

        default:
            foreach (['val_varchar','val_longtext','val_int','val_tinyint','val_boolean','val_datetime','val_date','val_decimal','archivo_url'] as $k) {
                if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                    return htmlspecialchars((string)$row[$k], ENT_QUOTES, 'UTF-8');
                }
            }
            return '<em class="text-muted">Sin respuesta</em>';
    }
}
?>
<style>
/* Encabezado y tarjetas */
.rsu-info-head{
  background:#f8f9fa;border:1px solid #e5e5e5;border-radius:.5rem;padding:.6rem .8rem;margin:.6rem;
}
.form-card{
  border:1px solid #e5e5e5;border-radius:.5rem;margin:.6rem;overflow:hidden;
}
.form-card .form-head{
  background:#e9f4ff;border-bottom:1px solid #d6eaff;padding:.5rem .8rem;font-weight:600;
}
/* ALTURA FIJA INTERNA + PANELES CON SCROLL INDEPENDIENTE */
.form-body{ padding:.6rem .4rem; height:72vh; overflow:hidden; } /* Ajusta 72vh si lo necesitas */
.rsu-split-row{ display:flex; height:100%; overflow:hidden; }
.rsu-left{ height:100%; overflow-y:auto; overflow-x:hidden; border-right:1px solid #eee; padding-right:.5rem; }
.rsu-right{ height:100%; overflow:auto; padding-left:.5rem; } /* vertical y horizontal dentro del contenido */

/* Ajuste del antiguo sidebar: dejamos de usar sticky y max-height */
.sticky-side{ position:relative; top:auto; height:100%; max-height:none; overflow:visible; }

.item-link{
  display:block;padding:.35rem .5rem;border-radius:.35rem;
  margin-bottom:.25rem;text-decoration:none;
  background:#fff;border:1px solid #e9ecef;
}
.item-link:hover{ background:#f8f9fa; }
.item-section{ padding:.5rem .75rem .9rem .75rem; border-bottom:1px dashed #e9ecef;}
.item-section:last-child{ border-bottom:0; }
.item-title{ font-weight:600; margin-bottom:.35rem; }
.badge-type{ font-size:.65rem; }

/* Responsive: en pantallas chicas apilamos y damos scroll al índice */
@media (max-width: 991.98px){
  .rsu-split-row{ display:block; }
  .rsu-left{ max-height:180px; margin-bottom:.5rem; }
  .rsu-right{ height:calc(72vh - 200px); }
}
</style>

<div class="rsu-info-head">
  <div><strong>Proyecto:</strong> <?= htmlspecialchars($proy['titulo'] ?: '—') ?></div>
  <div>
    <strong>Coordinador:</strong> <?= htmlspecialchars($proy['coordinador'] ?: '—') ?>
    <?php if ($proy['cod_docente'] !== ''): ?>
      <span class="text-muted"> (<?= htmlspecialchars($proy['cod_docente']) ?>)</span>
    <?php endif; ?>
  </div>
</div>

<?php if (empty($cabs)): ?>
  <div class="alert alert-warning m-2">Este proyecto aún no tiene respuestas registradas.</div>
<?php else: ?>
  <?php foreach ($cabs as $cab): ?>
    <?php
      $rid = (int)$cab['id'];
      $fid = (int)$cab['id_formulario'];
      $items = [];

      $sqlItems = "
        SELECT 
          fi.orden,
          i.id AS id_item,
          i.nombre AS item_nombre,
          i.tipo,
          ri.val_varchar, ri.val_longtext, ri.val_tinyint, ri.val_int, ri.val_boolean,
          ri.val_datetime, ri.val_date, ri.val_decimal, ri.archivo_url
        FROM sm_formulario_items fi
        JOIN sm_items i ON i.id = fi.id_item
        LEFT JOIN sm_respuesta_items ri
          ON ri.id_respuesta = $rid AND ri.id_item = i.id
        WHERE fi.id_formulario = $fid AND fi.activo = 1
        ORDER BY fi.orden ASC
      ";
      if ($rsi = mysqli_query($conexion, $sqlItems)) {
          while ($ri = mysqli_fetch_assoc($rsi)) $items[] = $ri;
          mysqli_free_result($rsi);
      }
    ?>
    <div class="form-card">
      <div class="form-head">
        Formulario: <?= htmlspecialchars($cab['formulario']) ?>
        <span class="text-muted" style="font-weight:400;">&nbsp; (Resp. #<?= (int)$cab['id'] ?>)</span>
      </div>

      <?php if (empty($items)): ?>
        <div class="p-3"><em class="text-muted">Este formulario no tiene ítems activos.</em></div>
      <?php else: ?>
        <div class="form-body">
          <div class="rsu-split-row">
            <!-- Barra lateral: índice de ítems (scroll propio) -->
            <div class="col-md-3 pr-2 rsu-left">
              <div class="sticky-side">
                <?php foreach ($items as $it): 
                      $anchor = '#F'.$rid.'I'.$it['orden']; ?>
                  <a class="item-link" href="<?= $anchor ?>">
                    <div class="small text-muted mb-1">Ítem <?= (int)$it['orden'] ?></div>
                    <div style="font-weight:600; line-height:1.1;"><?= htmlspecialchars($it['item_nombre']) ?></div>
                  </a>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Contenido (scroll propio) -->
            <div class="col-md-9 pl-2 rsu-right">
              <?php foreach ($items as $it): 
                    $anchorId = 'F'.$rid.'I'.$it['orden']; ?>
                <section id="<?= $anchorId ?>" class="item-section">
                  <div class="item-title">
                    <?= (int)$it['orden'] ?>. <?= htmlspecialchars($it['item_nombre']) ?>
                    <span class="badge badge-light badge-type"><?= htmlspecialchars($it['tipo']) ?></span>
                  </div>
                  <div class="item-content">
                    <?= render_valor($it, $ODS, $PROGS, $PROG_ODS) ?>
                  </div>
                </section>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  <?php endforeach; ?>
<?php endif; ?>
