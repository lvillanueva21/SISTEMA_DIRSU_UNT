<?php 
// /sistema_web/evaluacion/ver_informe.php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../componentes/db.php';

$id_py = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id_py <= 0) { echo "<div class='alert alert-danger m-3'>ID de proyecto inválido.</div>"; exit; }

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
if ($rs = mysqli_query($conexion, $sqlProy)) { if ($r = mysqli_fetch_assoc($rs)) { $proy['titulo']=(string)$r['titulo']; $proy['coordinador']=trim(($r['nombres']??'').' '.($r['apellidos']??'')); $proy['cod_docente']=(string)($r['cod_docente']??''); } mysqli_free_result($rs); }

// === Catálogos para ODS y Programa-ODS ===
$ODS = []; $PROGS = []; $PROG_ODS = [];
if ($q1 = mysqli_query($conexion, "SELECT id, nombre FROM ods ORDER BY id")) { while ($r = mysqli_fetch_assoc($q1)) { $ODS[(int)$r['id']] = $r['nombre']; } mysqli_free_result($q1); }
if ($q2 = mysqli_query($conexion, "SELECT id, nombre FROM programas WHERE activo=1 ORDER BY nombre")) { while ($r = mysqli_fetch_assoc($q2)) { $PROGS[(int)$r['id']] = $r['nombre']; } mysqli_free_result($q2); }
if ($q3 = mysqli_query($conexion, "SELECT po.programa_id, o.id AS ods_id, o.nombre AS ods_nombre FROM programa_ods po JOIN ods o ON o.id = po.ods_id ORDER BY po.programa_id, o.id")) {
  while ($r = mysqli_fetch_assoc($q3)) { $pid=(int)$r['programa_id']; if (!isset($PROG_ODS[$pid])) $PROG_ODS[$pid]=[]; $PROG_ODS[$pid][]=['id'=>(int)$r['ods_id'], 'nombre'=>$r['ods_nombre']]; }
  mysqli_free_result($q3);
}

// === Colores oficiales por ODS (ONU) ===  [bg, fg]
$ODS_STYLES = [ 1=>['#E5243B','#ffffff'], 2=>['#DDA63A','#111111'], 3=>['#4C9F38','#ffffff'], 4=>['#C5192D','#ffffff'], 5=>['#FF3A21','#ffffff'], 6=>['#26BDE2','#ffffff'], 7=>['#FCC30B','#111111'], 8=>['#A21942','#ffffff'], 9=>['#FD6925','#ffffff'], 10=>['#DD1367','#ffffff'], 11=>['#FD9D24','#111111'], 12=>['#BF8B2E','#111111'], 13=>['#3F7E44','#ffffff'], 14=>['#0A97D9','#ffffff'], 15=>['#56C02B','#ffffff'], 16=>['#00689D','#ffffff'], 17=>['#19486A','#ffffff'] ];

// === Ruta web base para servir archivos subidos ===
$WEB_FILES_BASE = '/sistema_web/files_answer/';

// === Cabeceras de respuestas de este proyecto ===
$cabs = [];
$sqlCab = "
  SELECT r.id, r.id_formulario, r.estado, r.creado_at, r.actualizado_at, f.nombre AS formulario
  FROM sm_respuestas r
  JOIN sm_formularios f ON f.id = r.id_formulario
  WHERE r.id_py = $id_py
  ORDER BY f.nombre ASC, r.id ASC
";
if ($rs = mysqli_query($conexion, $sqlCab)) { while ($r = mysqli_fetch_assoc($rs)) $cabs[] = $r; mysqli_free_result($rs); }

// === Helpers de presentación ===

// Convierte ruta cruda a URL pública
function rsu_public_url(string $raw): ?string {
  global $WEB_FILES_BASE;
  $raw = trim($raw); if ($raw==='') return null;
  if (preg_match('~^https?://~i', $raw)) return $raw;
  $norm = str_replace(['\\'], ['/'], $raw);
  $pos = stripos($norm, 'files_answer');
  if ($pos !== false) { $rel = ltrim(substr($norm, $pos + strlen('files_answer')), '/'); $segments = array_map('rawurlencode', array_filter(explode('/', $rel), 'strlen')); return rtrim($WEB_FILES_BASE,'/').'/'.implode('/',$segments); }
  $base = basename($norm); if ($base === '' || $base === '.' || $base === '..') return null;
  return rtrim($WEB_FILES_BASE,'/').'/'.rawurlencode($base);
}
function rsu_file_name(string $raw): string { $p = preg_split('/[?#]/',$raw,2); $name = basename($p[0]??$raw); return $name!=='' ? $name : $raw; }

// Ícono + estilo por extensión (th color + botón sólido)
function rsu_file_style_by_ext(string $ext): array {
  $ext = strtolower($ext);
  // [th_bg, th_fg, btn_class, btn_icon_html, btn_label, force_target_blank]
  if ($ext==='pdf')  return ['#E53935','#ffffff','btn-file-pdf','<i class="fas fa-file-pdf"></i>','Ver PDF', true];
  if (in_array($ext,['xls','xlsx','csv'])) return ['#1E7E34','#ffffff','btn-file-excel','<i class="fas fa-file-excel"></i>','Descargar', false];
  if (in_array($ext,['doc','docx'])) return ['#185ABD','#ffffff','btn-file-word','<i class="fas fa-file-word"></i>','Descargar', false];
  return ['#6c757d','#ffffff','btn-file-generic','<i class="fas fa-file"></i>','Descargar', false];
}

// Tabla de archivos (ancho de acción más cómodo)
function rsu_render_archivos_table(string $rawList): string {
  $rawList = trim($rawList); if ($rawList==='') return '<em class="text-muted">No hay archivo</em>';
  $parts = preg_split('/[\r\n,;]+/', $rawList, -1, PREG_SPLIT_NO_EMPTY); if (!$parts) return '<em class="text-muted">No hay archivo</em>';
  $rows = []; $headerStyle = null;
  foreach ($parts as $raw) {
    $raw = trim($raw); if ($raw==='') continue;
    $url = rsu_public_url($raw); if (!$url) continue;
    $path = parse_url($url, PHP_URL_PATH) ?? ''; $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    [$th_bg,$th_fg,$btn_class,$btn_icon,$btn_label,$blank] = rsu_file_style_by_ext($ext);
    if ($headerStyle===null) $headerStyle = "background:$th_bg;color:$th_fg;";
    $name = rsu_file_name($raw);
    $attr = $blank ? 'target="_blank" rel="noopener"' : 'download';
    $btn = '<a href="'.htmlspecialchars($url,ENT_QUOTES,'UTF-8').'" '.$attr.' class="btn '.$btn_class.'">'.$btn_icon.' '.$btn_label.'</a>';
    $rows[] = '<tr><td style="word-break:break-all;">'.htmlspecialchars($name,ENT_QUOTES,'UTF-8').'</td><td style="text-align:right;white-space:nowrap;min-width:200px;">'.$btn.'</td></tr>';
  }
  if (empty($rows)) return '<em class="text-muted">No hay archivo</em>';
  return '<div class="table-responsive"><table class="table table-sm table-bordered mb-2" style="width:auto;min-width:380px;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);"><thead><tr><th style="'.$headerStyle.'">Archivo</th><th style="'.$headerStyle.';min-width:200px;white-space:nowrap;text-align:right;">Acción</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table></div>';
}

// Tabla para enlaces de Google Drive (solo tabla; no duplicar en cuerpo)
function rsu_render_drive_table(array $urls): string {
  if (!$urls) return '';
  $rows = [];
  foreach ($urls as $u) { $u = trim($u); if ($u==='') continue; $safe = htmlspecialchars($u,ENT_QUOTES,'UTF-8'); $btn = '<a href="'.$safe.'" target="_blank" rel="noopener" class="btn btn-drive"><i class="fab fa-google-drive"></i> Abrir drive</a>'; $rows[] = '<tr><td style="word-break:break-all;"><a href="'.$safe.'" target="_blank" rel="noopener">'.$safe.'</a></td><td style="text-align:right;white-space:nowrap;min-width:200px;">'.$btn.'</td></tr>'; }
  if (!$rows) return '';
  return '<div class="table-responsive"><table class="table table-sm table-bordered mb-2 drive-table"><thead><tr><th>Enlace</th><th style="min-width:200px;white-space:nowrap;text-align:right;">Acción</th></tr></thead><tbody>'.implode('', $rows).'</tbody></table></div>';
}

// Extrae y elimina enlaces de Drive del cuerpo
function rsu_extract_drive_links(string $htmlOrText): array { $found=[]; if (preg_match_all('~https?://drive\.google\.com[^\s"<>\']+~i',$htmlOrText,$m)) { $found=array_merge($found,$m[0]); } if (preg_match_all('~href=[\'"]([^\'"]*drive\.google\.com[^\'"]*)[\'"]~i',$htmlOrText,$m2)) { $found=array_merge($found,$m2[1]); } return array_values(array_unique(array_map('trim',$found))); }
function rsu_strip_drive_links(string $html): string { $html = preg_replace('~<a[^>]+href=[\'"]https?://drive\.google\.com[^\'"]*[\'"][^>]*>.*?</a>~is','',$html); $html = preg_replace('~https?://drive\.google\.com[^\s"<>\']+~i','',$html); return preg_replace("/[ \t]{2,}/",' ',$html); }

// Badge/Chip ODS
function chip($txt, $bg = null, $fg = null) { if (!$bg && !$fg) { $bg = '#6c757d'; $fg = '#ffffff'; } $style = 'font-weight:600;'; if ($bg) $style .= 'background:'.htmlspecialchars($bg,ENT_QUOTES,'UTF-8').';'; if ($fg) $style .= 'color:'.htmlspecialchars($fg,ENT_QUOTES,'UTF-8').';'; return '<span class="badge mr-1 mb-1" style="'.$style.'">'.htmlspecialchars($txt,ENT_QUOTES,'UTF-8').'</span>'; }

function render_valor(array $row, array $ODS, array $PROGS, array $PROG_ODS, array $ODS_STYLES): string {
  $tipo = $row['tipo'] ?? '';
  switch ($tipo) {
    case 'varchar':
      return htmlspecialchars((string)($row['val_varchar'] ?? ''), ENT_QUOTES, 'UTF-8');

    case 'longtext':
      $txt = trim((string)($row['val_longtext'] ?? '')); if ($txt === '') return '<em class="text-muted">Sin respuesta</em>';
      $links = rsu_extract_drive_links($txt);
      if (!empty($links)) { $clean = rsu_strip_drive_links($txt); return $clean . rsu_render_drive_table($links); }
      return $txt;

    case 'tinyint':
      return ($row['val_tinyint'] !== null) ? htmlspecialchars((string)$row['val_tinyint']) : '<em class="text-muted">—</em>';

    case 'int':
      return ($row['val_int'] !== null) ? htmlspecialchars((string)$row['val_int']) : '<em class="text-muted">—</em>';

    case 'boolean':
      if ($row['val_boolean'] === null) return '<em class="text-muted">—</em>'; return ((int)$row['val_boolean'] === 1) ? 'Sí' : 'No';

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
      $u = (string)($row['archivo_url'] ?? ''); return rsu_render_archivos_table($u);

    case 'ods':
      $csv = trim((string)($row['val_varchar'] ?? '')); if ($csv==='') return '<em class="text-muted">—</em>';
      $ids = array_filter(array_map('intval', explode(',', $csv))); if (!$ids) return '<em class="text-muted">—</em>';
      $out = ''; foreach ($ids as $id) { $nom = $ODS[$id] ?? 'ODS '.$id; [$bg,$fg] = $ODS_STYLES[$id] ?? [null,null]; $out .= chip("ODS $id — $nom",$bg,$fg); } return $out ?: '<em class="text-muted">—</em>';

    case 'programa_ods':
      $pid = (int)trim((string)($row['val_varchar'] ?? '0')); if ($pid<=0) return '<em class="text-muted">—</em>';
      $pnom = $PROGS[$pid] ?? ('Programa #'.$pid); $odsArr = $PROG_ODS[$pid] ?? [];
      if (!$odsArr) return '<div class="text-muted"><em>Sin ODS asociados</em></div>';
      $rows = ''; foreach ($odsArr as $o) { $id = (int)$o['id']; $odsNom = $o['nombre']; [$bg,$fg] = $ODS_STYLES[$id] ?? [null,null]; $badge = chip('ODS '.$id.' — '.$odsNom, $bg, $fg); $rows .= '<tr><td>'.$badge.'</td><td>'.htmlspecialchars($pnom,ENT_QUOTES,'UTF-8').'</td></tr>'; }
      return '<div class="table-responsive"><table class="table table-sm mb-2 apple-table"><thead><tr><th>OBJETIVO DE DESARROLLO SOSTENIBLE</th><th>PROGRAMA PRIORIZADO</th></tr></thead><tbody>'.$rows.'</tbody></table></div>';

    default:
      foreach (['val_varchar','val_longtext','val_int','val_tinyint','val_boolean','val_datetime','val_date','val_decimal','archivo_url'] as $k) { if (isset($row[$k]) && $row[$k]!==null && $row[$k]!=='') return htmlspecialchars((string)$row[$k], ENT_QUOTES, 'UTF-8'); }
      return '<em class="text-muted">Sin respuesta</em>';
  }
}
?>
<style>
/* Encabezado y tarjetas */ .rsu-info-head{background:#f8f9fa;border:1px solid #e5e5e5;border-radius:.5rem;padding:.6rem .8rem;margin:.6rem;display:flex;justify-content:space-between;align-items:center;gap:.5rem;flex-wrap:wrap;} .form-card{border:1px solid #e5e5e5;border-radius:.5rem;margin:.6rem;overflow:hidden;} .form-card .form-head{background:#e9f4ff;border-bottom:1px solid #d6eaff;padding:.5rem .8rem;font-weight:600;}
/* Layout */ .form-body{padding:.6rem .4rem;height:72vh;overflow:hidden;} .rsu-split-row{display:flex;height:100%;overflow:hidden;} .rsu-left{height:100%;overflow-y:auto;overflow-x:hidden;border-right:1px solid #eee;padding-right:.5rem;} .rsu-right{height:100%;overflow:auto;padding-left:.5rem;}
/* Sidebar (azul primary) */ .item-link{display:block;padding:.35rem .5rem;border-radius:.35rem;margin-bottom:.25rem;text-decoration:none;background:#fff;border:1px solid #e9ecef;color:#0d6efd;} .item-link:hover{background:#f8f9fa;color:#0a58ca;}
/* Títulos (azul primary) */ .item-section{padding:.5rem .75rem .9rem .75rem;border-bottom:1px dashed #e9ecef;} .item-section:last-child{border-bottom:0;} .item-title{font-weight:700;margin-bottom:.35rem;color:#0d6efd;} .badge-type{font-size:.65rem;}
/* Tablas "apple-like" */ .apple-table{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);} .apple-table thead th{background:#111;color:#fff;border-color:#111;} .apple-table tbody tr:nth-child(odd){background:#fafafa;} .apple-table tbody tr:nth-child(even){background:#ffffff;} .apple-table th,.apple-table td{vertical-align:middle;border-color:#ececec;}
/* Tabla Drive */ .drive-table{width:auto;min-width:380px;border-radius:12px;overflow:hidden;box-shadow:0 1px 3px rgba(0,0,0,.06);} .drive-table thead th{background:#FFEB3B;color:#111;border-color:#F1D334;}
/* Botones sólidos por tipo */ .btn-file-pdf{background:#E53935;color:#fff;border:1px solid #C62828;} .btn-file-pdf:hover{background:#D32F2F;color:#fff;border-color:#B71C1C;} .btn-file-excel{background:#1E7E34;color:#fff;border:1px solid #19692c;} .btn-file-excel:hover{background:#1a6f2f;color:#fff;border-color:#155d25;} .btn-file-word{background:#185ABD;color:#fff;border:1px solid #0f4bb0;} .btn-file-word:hover{background:#134fa7;color:#fff;border-color:#0c3e86;} .btn-file-generic{background:#6c757d;color:#fff;border:1px solid #5a6268;} .btn-file-generic:hover{background:#616971;color:#fff;border-color:#545b62;}
/* Botón Drive (amarillo/negro) */ .btn-drive{background:#FFEB3B;color:#111;border:1px solid #F1D334;} .btn-drive:hover{background:#F7E369;color:#111;border-color:#E8C92A;}
/* Botón PDF reporte */ .btn-report{background:#E53935;color:#fff;border:1px solid #C62828;} .btn-report:hover{background:#D32F2F;color:#fff;border-color:#B71C1C;}
/* Responsive */ @media (max-width:991.98px){ .rsu-split-row{display:block;} .rsu-left{max-height:180px;margin-bottom:.5rem;} .rsu-right{height:calc(72vh - 200px);} }
</style>

<!-- ROOT del informe: lo usamos para imprimir solo esto -->
<div id="rsu-informe-root">
  <div class="rsu-info-head">
    <div>
      <div><strong>Proyecto:</strong> <?= htmlspecialchars($proy['titulo'] ?: '—') ?></div>
      <div><strong>Coordinador:</strong> <?= htmlspecialchars($proy['coordinador'] ?: '—') ?> <?php if ($proy['cod_docente']!==''): ?><span class="text-muted"> (<?= htmlspecialchars($proy['cod_docente']) ?>)</span><?php endif; ?></div>
    </div>
    <div>
      <button type="button" class="btn btn-report" onclick="rsu_imprimir_informe()"><i class="fas fa-file-pdf"></i> Generar reporte en PDF</button>
    </div>
  </div>

  <?php if (empty($cabs)): ?>
    <div class="alert alert-warning m-2">Este proyecto aún no tiene respuestas registradas.</div>
  <?php else: ?>
    <?php foreach ($cabs as $cab): ?>
      <?php
        $rid = (int)$cab['id']; $fid = (int)$cab['id_formulario']; $items = [];
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
        if ($rsi = mysqli_query($conexion, $sqlItems)) { while ($ri = mysqli_fetch_assoc($rsi)) $items[] = $ri; mysqli_free_result($rsi); }
      ?>
      <div class="form-card">
        <div class="form-head">Formulario: <?= htmlspecialchars($cab['formulario']) ?> <span class="text-muted" style="font-weight:400;">&nbsp;(Resp. #<?= (int)$cab['id'] ?>)</span></div>
        <?php if (empty($items)): ?>
          <div class="p-3"><em class="text-muted">Este formulario no tiene ítems activos.</em></div>
        <?php else: ?>
          <div class="form-body">
            <div class="rsu-split-row">
              <!-- Barra lateral -->
              <div class="col-md-3 pr-2 rsu-left">
                <div class="sticky-side">
                  <?php foreach ($items as $it): $anchor = '#F'.$rid.'I'.$it['orden']; ?>
                    <a class="item-link" href="<?= $anchor ?>">
                      <div class="small text-muted mb-1">Ítem <?= (int)$it['orden'] ?></div>
                      <div style="font-weight:700; line-height:1.1;"><?= htmlspecialchars($it['item_nombre']) ?></div>
                    </a>
                  <?php endforeach; ?>
                </div>
              </div>
              <!-- Contenido -->
              <div class="col-md-9 pl-2 rsu-right">
                <?php foreach ($items as $it): $anchorId = 'F'.$rid.'I'.$it['orden']; ?>
                  <section id="<?= $anchorId ?>" class="item-section">
                    <div class="item-title"><?= (int)$it['orden'] ?>. <?= htmlspecialchars($it['item_nombre']) ?> <span class="badge badge-light badge-type"><?= htmlspecialchars($it['tipo']) ?></span></div>
                    <div class="item-content"><?= render_valor($it, $ODS, $PROGS, $PROG_ODS, $ODS_STYLES) ?></div>
                  </section>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>
</div>
<script>
function rsu_imprimir_informe(){
  try{
    var root = document.getElementById('rsu-informe-root');
    if(!root){ window.print(); return; }

    // 👉 Todo lo imprimimos en un iframe oculto (sin ventanas nuevas ni about:blank)
    var iframe = document.getElementById('rsu-print-iframe');
    if (!iframe) {
      iframe = document.createElement('iframe');
      iframe.id = 'rsu-print-iframe';
      iframe.style.position = 'fixed';
      iframe.style.right = '0';
      iframe.style.bottom = '0';
      iframe.style.width = '0';
      iframe.style.height = '0';
      iframe.style.border = '0';
      iframe.style.opacity = '0';
      document.body.appendChild(iframe);
    }

    var styles = `
      <style>
        html,body{margin:0;padding:0;}
        *{-webkit-print-color-adjust:exact; print-color-adjust:exact;}
        .rsu-info-head{background:#f8f9fa;border:1px solid #e5e5e5;border-radius:8px;padding:10px 12px;margin:10px 10px 0 10px;display:flex;justify-content:space-between;align-items:center;gap:8px;flex-wrap:wrap;}
        .form-card{border:1px solid #e5e5e5;border-radius:10px;margin:10px;overflow:hidden;page-break-inside:avoid;}
        .form-head{background:#e9f4ff;border-bottom:1px solid #d6eaff;padding:8px 12px;font-weight:600;}
        .item-section{padding:8px 12px;border-bottom:1px dashed #e9ecef;}
        .item-section:last-child{border-bottom:0;}
        .item-title{font-weight:700;margin:6px 0;color:#0d6efd;}
        .badge{display:inline-block;padding:.35em .6em;border-radius:.35rem;}
        .table{border-collapse:collapse;width:100%;}
        .table th,.table td{border:1px solid #e5e7eb;padding:6px;vertical-align:middle;}
        .apple-table{border:1px solid #e5e7eb;border-radius:12px;overflow:hidden;box-shadow:none;}
        .apple-table thead th{background:#111;color:#fff;border-color:#111;}
        .apple-table tbody tr:nth-child(odd){background:#fafafa;}
        .drive-table thead th{background:#FFEB3B;color:#111;border-color:#F1D334;}
        a[href]:after{content:'' !important;}
        .btn{display:inline-block;padding:6px 10px;border-radius:6px;text-decoration:none;}
        .btn-drive{background:#FFEB3B;color:#111;border:1px solid #F1D334;}
        .btn-file-pdf{background:#E53935;color:#fff;border:1px solid #C62828;}
        .btn-file-excel{background:#1E7E34;color:#fff;border:1px solid #19692c;}
        .btn-file-word{background:#185ABD;color:#fff;border:1px solid #0f4bb0;}
        .btn-file-generic{background:#6c757d;color:#fff;border:1px solid #5a6268;}
        @page{size:A4; margin:12mm;}
        @media print{ .btn-report{display:none !important;} .rsu-left{display:none !important;} .rsu-right{width:100% !important; padding-left:0 !important;} }
      </style>
    `;

    var doc = iframe.contentWindow || iframe.contentDocument;
    if (doc.document) doc = doc.document;
    doc.open();
    doc.write('<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reporte</title>'+styles+'</head><body>'+document.getElementById('rsu-informe-root').innerHTML+'</body></html>');
    doc.close();

    setTimeout(function(){
      (iframe.contentWindow || iframe).focus();
      (iframe.contentWindow || iframe).print();
    }, 300);
  }catch(e){
    console.error(e);
    window.print();
  }
}
</script>


