<?php
// presentacion/logica/formulario.php — versión con video en archivo aparte (logica/video_embed.php)

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

// --------- Datos base del flujo ----------
$respuestaId = (int)($sm_info['respuesta_id'] ?? 0);
$formulario  = $sm_info['form_activo'] ?? null;
$periodoNom  = $sm_info['periodo_activo']['nombre'] ?? '-';

if ($respuestaId <= 0 || !$formulario) {
    echo "<div class='alert alert-danger'>No se pudo cargar el formulario actual.</div>";
    return;
}

// --------- Cargar ítems activos y ordenados ----------
$st = $conexion->prepare("
  SELECT fi.id_item,
         fi.orden,
         i.nombre,
         i.tipo,
         i.ejemplo,
         i.img_ruta,
         i.pdf_ruta,
         i.link,
         i.formato,
         i.video
  FROM sm_formulario_items fi
  JOIN sm_items i ON i.id = fi.id_item
  WHERE fi.id_formulario=? AND fi.activo=1
  ORDER BY fi.orden ASC
");
$st->bind_param("i", $formulario['id']);
$st->execute();
$rs = $st->get_result();
$items = $rs->fetch_all(MYSQLI_ASSOC);
$st->close();

$totalItems = count($items);
if ($totalItems === 0) {
    echo "<div class='alert alert-warning'>Este formulario no tiene ítems activos.</div>";
    return;
}

// --------- Cargar respuestas existentes de esta cabecera ----------
$st2 = $conexion->prepare("
  SELECT id_item, tipo,
         val_varchar, val_longtext, val_tinyint, val_int, val_boolean,
         val_datetime, val_date, val_decimal, archivo_url
  FROM sm_respuesta_items
  WHERE id_respuesta=?
");
$st2->bind_param("i", $respuestaId);
$st2->execute();
$rs2 = $st2->get_result();

$respuestas = []; // id_item => row
while ($row = $rs2->fetch_assoc()) {
    $respuestas[(int)$row['id_item']] = $row;
}
$st2->close();

// --------- Helpers ----------
function ri_val_esta_lleno(array $row, string $tipo): bool {
    switch ($tipo) {
        case 'varchar':       return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'longtext':      return isset($row['val_longtext']) && trim((string)$row['val_longtext']) !== '';
        case 'tinyint':       return $row['val_tinyint'] !== null;
        case 'int':           return $row['val_int'] !== null;
        case 'boolean':       return $row['val_boolean'] !== null;
        case 'datetime':      return !empty($row['val_datetime']);
        case 'date':          return !empty($row['val_date']);
        case 'decimal':       return $row['val_decimal'] !== null;
        // nuevos:
        case 'programa_ods':  return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'ods':           return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'pdf':
        case 'excel':
        case 'word':          return isset($row['archivo_url']) && trim((string)$row['archivo_url']) !== '';
        default:              return false;
    }
}
function pillClass($estado) {
    return match ($estado) {
        'done'   => 'btn-success',
        'active' => 'btn-primary',
        'next'   => 'btn-outline-primary',
        default  => 'btn-secondary'
    };
}
function val($arr, $key) { return isset($arr[$key]) ? $arr[$key] : ''; }

/** Normaliza rutas internas para que apunten a /sistema_web */
function public_url(string $p): string {
    $p = trim($p);
    if ($p === '') return '';
    if (preg_match('~^https?://~i', $p)) return $p;
    if ($p[0] === '/') {
        if (strpos($p, '/sistema_web/') === 0) return $p;
        return '/sistema_web' . $p;
    }
    return '/sistema_web/' . ltrim($p, '/');
}

// --------- Índice del primer incompleto y progreso ----------
$completados = 0;
$primerIncompletoIdx = 1; // 1-based
foreach ($items as $idx0 => $it) {
    $idItem = (int)$it['id_item'];
    $tipo   = $it['tipo'];
    $tiene  = isset($respuestas[$idItem]) ? ri_val_esta_lleno($respuestas[$idItem], $tipo) : false;
    if ($tiene) $completados++;
    if (!$tiene && $primerIncompletoIdx === 1) {
        $primerIncompletoIdx = $idx0 + 1;
        break;
    }
}
if ($completados === $totalItems) $primerIncompletoIdx = $totalItems;

// --------- Elegir el ítem actual (1-based), con bloqueo de salto ----------
$requested = isset($_GET['item']) ? (int)$_GET['item'] : $primerIncompletoIdx;
if ($requested < 1) $requested = 1;
if ($requested > $totalItems) $requested = $totalItems;

$maxPermitido = max(1, $primerIncompletoIdx);
if ($requested > $maxPermitido) {
    $qs = $_GET;
    $qs['item'] = $maxPermitido;
    $url = strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query($qs);
    header("Location: ".$url);
    exit;
}

$itemActualIdx = $requested;
$itemActual    = $items[$itemActualIdx - 1];

// --------- Cálculo de progreso ----------
$porcentaje = ($totalItems > 0) ? round(($completados / $totalItems) * 100) : 0;

// --------- Valores actuales del ítem ----------
$valorExistente = $respuestas[(int)$itemActual['id_item']] ?? null;

// --------- Catálogos nuevos ----------
$odsLibres = [];
$stO = $conexion->prepare("
  SELECT o.id, o.nombre
  FROM ods o
  LEFT JOIN programa_ods po ON po.ods_id = o.id
  WHERE po.ods_id IS NULL
  ORDER BY o.id
");
$stO->execute();
$resO = $stO->get_result();
while ($row = $resO->fetch_assoc()) {
    $odsLibres[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre']];
}
$stO->close();

$programas = [];
$stP = $conexion->prepare("SELECT id, nombre FROM programas WHERE activo=1 ORDER BY nombre");
$stP->execute();
$resP = $stP->get_result();
while ($row = $resP->fetch_assoc()) {
    $programas[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre']];
}
$stP->close();

$progOdsMap = []; // programa_id => "ODS 1, ODS 2"
$stM = $conexion->prepare("
  SELECT po.programa_id, GROUP_CONCAT(o.nombre ORDER BY o.id SEPARATOR ', ') AS ods
  FROM programa_ods po
  JOIN ods o ON o.id = po.ods_id
  GROUP BY po.programa_id
");
$stM->execute();
$resM = $stM->get_result();
while ($row = $resM->fetch_assoc()) {
    $progOdsMap[(int)$row['programa_id']] = $row['ods'];
}
$stM->close();
?>
<div class="container-fluid d-flex flex-column p-0" style="height: calc(100vh - 150px);">

    <!-- Div Superior -->
    <div class="bg-white shadow-sm p-3">
        <div class="row">
            <div class="col-md-7 d-flex flex-column justify-content-center align-items-center text-center">
                <h5 class="mb-2">Progreso de proyecto en período <?= htmlspecialchars($periodoNom) ?></h5>
                <div class="progress w-100">
                    <div class="progress-bar bg-success" role="progressbar"
                         style="width: <?= $porcentaje ?>%;"
                         aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100">
                    </div>
                </div>
            </div>
            <div class="col-md-5 d-flex flex-column justify-content-center align-items-center text-center">
                <span class="fw-bold mb-2"><?= $porcentaje ?>% para el objetivo</span>
                <small class="text-muted"><?= $completados ?> de <?= $totalItems ?> ítems completados</small>
            </div>
        </div>
    </div>

    <!-- Contenido central -->
    <div class="row g-0 flex-grow-1" style="overflow: hidden;">
        <!-- Panel izquierdo -->
        <div class="col-md-8 border-end p-3" style="height: 100%; overflow-y: auto;">

            <!-- Encabezado informativo -->
            <div style="display: flex; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; max-width: 800px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <div style="flex: 1; padding: 20px; background-color: #f8f9fa; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; text-align: center;">
                    Te encuentras en el período de presentación de informes semestrales <?= htmlspecialchars($periodoNom) ?>.
                    <br>
                    <?php 
                    echo "Apertura: " . htmlspecialchars($sm_info['apertura'] ?? '-') . "<br>";
                    echo "Cierre: "   . htmlspecialchars($sm_info['cierre']   ?? '-') . "<br>";
                    ?>
                </div>
                <div style="flex: 1; padding: 20px; background-color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; text-align: center;">
                    Completa los ítems en orden. Puedes editar anteriores, pero no saltarte los siguientes sin completar.
                </div>
            </div>

            <!-- Bloque del Ítem Actual -->
            <div style="margin-top: 15px; border: 1px solid #ccc; border-radius: 8px; background-color: #ffffff; padding: 20px; max-width: 800px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
                <h5 class="mb-2">Ítem <?= $itemActualIdx ?> de <?= $totalItems ?> — <?= htmlspecialchars($itemActual['nombre']) ?></h5>

                <!-- BOTONES DE AYUDA -->
                <div class="mb-3">
                    <?php
                    $img = trim((string)($itemActual['img_ruta'] ?? ''));
                    $pdf = trim((string)($itemActual['pdf_ruta'] ?? ''));
                    $lnk = trim((string)($itemActual['link'] ?? ''));
                    $fmt = trim((string)($itemActual['formato'] ?? ''));
                    $vid = trim((string)($itemActual['video'] ?? ''));

                    $imgUrl = $img !== '' ? public_url($img) : '';
                    $pdfUrl = $pdf !== '' ? public_url($pdf) : '';
                    $fmtUrl = $fmt !== '' ? public_url($fmt) : '';

                    if ($imgUrl !== '') {
                        echo '<button class="btn btn-outline-secondary btn-sm mr-1" type="button"
                              onclick="showImg(\''.htmlspecialchars($imgUrl, ENT_QUOTES).'\')">Imagen</button>';
                    }
                    if ($pdfUrl !== '') {
                        echo '<a class="btn btn-outline-secondary btn-sm mr-1" target="_blank" href="'.htmlspecialchars($pdfUrl).'">PDF</a>';
                    }
                    if ($lnk !== '') {
                        $href = (preg_match('~^https?://~i', $lnk)) ? $lnk : ('https://'.$lnk);
                        echo '<a class="btn btn-outline-secondary btn-sm mr-1" target="_blank" href="'.htmlspecialchars($href).'">Enlace</a>';
                    }
                    if ($fmtUrl !== '') {
                        echo '<a class="btn btn-outline-secondary btn-sm mr-1" download target="_blank" href="'.htmlspecialchars($fmtUrl).'">Plantilla</a>';
                    }
                    if ($vid !== '') {
                        // Sin inline JS con la URL; usamos data-video y lo lee JS (evita problemas con &amp;)
                        $data = htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
                        echo '<button class="btn btn-outline-secondary btn-sm mr-1 video-btn" type="button" data-video="'.$data.'">Video</button>';
                    }
                    ?>
                </div>

                <!-- Párrafo de ejemplo -->
                <div class="text-muted mb-3" style="white-space:pre-wrap; border:1px dashed #ddd; padding:8px; border-radius:6px; background:#fafafa">
                    <?php
                    $ej = (string)($itemActual['ejemplo'] ?? '');
                    echo ($ej !== '') ? nl2br(htmlspecialchars($ej)) : '<em>Sin ejemplo disponible para este ítem.</em>';
                    ?>
                </div>

                <?php
                // Límites de archivo (informativo)
                $uploadMax = ini_get('upload_max_filesize') ?: '2M';
                $postMax   = ini_get('post_max_size') ?: '8M';

                $tipo = $itemActual['tipo'];
                $v    = $valorExistente;
                $isFileType = in_array($tipo, ['pdf','excel','word'], true);
                ?>
                <form method="post" action="guardar_item.php" class="mt-2" <?= $isFileType ? 'enctype="multipart/form-data"' : '' ?>>
                    <input type="hidden" name="id_respuesta" value="<?= $respuestaId ?>">
                    <input type="hidden" name="id_item" value="<?= (int)$itemActual['id_item'] ?>">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                    <input type="hidden" name="next" value="<?= min($itemActualIdx+1, $totalItems) ?>">

                    <?php if ($tipo === 'varchar'): ?>
                        <input class="form-control" type="text" name="val_varchar" maxlength="1000"
                               value="<?= htmlspecialchars(val($v,'val_varchar')) ?>"
                               placeholder="Escribe tu respuesta (máx 1000 caracteres)">

                    <?php elseif ($tipo === 'longtext'): ?>
                        <textarea class="form-control" name="val_longtext" rows="6"
                                  placeholder="Escribe tu respuesta"><?= htmlspecialchars(val($v,'val_longtext')) ?></textarea>

                    <?php elseif ($tipo === 'tinyint'): ?>
                        <input class="form-control" type="number" name="val_tinyint" min="0" max="9"
                               value="<?= htmlspecialchars(val($v,'val_tinyint')) ?>" placeholder="0-9">

                    <?php elseif ($tipo === 'int'): ?>
                        <input class="form-control" type="number" name="val_int" min="0"
                               value="<?= htmlspecialchars(val($v,'val_int')) ?>" placeholder="Número entero positivo">

                    <?php elseif ($tipo === 'boolean'): ?>
                        <?php $checked = (isset($v['val_boolean']) && (int)$v['val_boolean'] === 1) ? 'checked' : ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chk_bool" name="val_boolean" value="1" <?= $checked ?>>
                            <label class="form-check-label" for="chk_bool">Marcar si aplica</label>
                        </div>

                    <?php elseif ($tipo === 'datetime'): ?>
                        <input class="form-control" type="datetime-local" name="val_datetime"
                               value="<?= htmlspecialchars(val($v,'val_datetime')) ?>">

                    <?php elseif ($tipo === 'date'): ?>
                        <input class="form-control" type="date" name="val_date"
                               value="<?= htmlspecialchars(val($v,'val_date')) ?>">

                    <?php elseif ($tipo === 'decimal'): ?>
                        <input class="form-control" type="text" name="val_decimal"
                               value="<?= htmlspecialchars(val($v,'val_decimal')) ?>"
                               placeholder="Ej. 1234.56 o 1,234.56 (se normalizará a 2 decimales)">

                    <?php elseif ($tipo === 'programa_ods'): ?>
                        <?php $selProg = trim((string)val($v,'val_varchar')); ?>
                        <label class="form-label">Programa priorizado</label>
                        <select class="form-control" name="val_varchar" id="programa_select">
                            <option value="">-- Selecciona un programa --</option>
                            <?php foreach ($programas as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($selProg !== '' && (int)$selProg === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2 p-2" style="background:#f8f9fa; border:1px solid #e3e3e3; border-radius:6px">
                            <strong>ODS de este programa:</strong>
                            <div id="prog_ods_view" class="mt-1"></div>
                        </div>

                    <?php elseif ($tipo === 'ods'): ?>
                        <?php
                        $csvExist = trim((string)val($v, 'val_varchar'));
                        $idsExist = array_filter($csvExist !== '' ? array_map('intval', explode(',', $csvExist)) : []);
                        ?>
                        <label class="form-label">Selecciona ODS (no asociados a programas)</label>
                        <select class="form-control" id="ods_select" multiple size="6">
                            <?php foreach ($odsLibres as $o): ?>
                                <option value="<?= (int)$o['id'] ?>" <?= in_array((int)$o['id'], $idsExist, true) ? 'selected' : '' ?>>
                                    <?= (int)$o['id'] ?> — <?= htmlspecialchars($o['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <input type="hidden" name="val_varchar" id="ods_hidden_csv" value="<?= htmlspecialchars($csvExist) ?>">
                        <small class="text-muted">Se guardará como CSV de IDs (ej. 3,5,12).</small>

                    <?php elseif (in_array($tipo, ['pdf','excel','word'], true)): ?>
                        <?php
                        $exist_url = trim((string)val($v, 'archivo_url'));
                        $openUrl   = $exist_url !== '' ? public_url($exist_url) : '';
                        $accept    = ($tipo === 'pdf')
                            ? '.pdf,application/pdf'
                            : (($tipo === 'excel')
                                ? '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                : '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                        ?>
                        <div class="mb-2">
                            <label class="form-label">Subir archivo <?= strtoupper($tipo) ?></label>
                            <input class="form-control" type="file" name="upload_file" accept="<?= htmlspecialchars($accept) ?>">
                            <small class="text-muted d-block mt-1">Tamaño máx (referencial): upload_max_filesize=<?= htmlspecialchars($uploadMax) ?>, post_max_size=<?= htmlspecialchars($postMax) ?>.</small>
                        </div>

                        <div class="mt-3 p-2" style="background:#f8f9fa; border:1px solid #e3e3e3; border-radius:6px">
                            <strong>Archivo actual:</strong>
                            <?php if ($exist_url !== ''): ?>
                                <div class="d-flex align-items-center mt-1">
                                    <code class="mr-2" style="word-break:break-all"><?= htmlspecialchars($exist_url) ?></code>
                                    <a class="btn btn-outline-primary btn-xs mr-2" target="_blank" href="<?= htmlspecialchars($openUrl) ?>">Abrir</a>
                                    <form method="post" action="borrar_archivo.php" onsubmit="return confirm('¿Eliminar el archivo actual?');" class="m-0 p-0">
                                        <input type="hidden" name="id_respuesta" value="<?= $respuestaId ?>">
                                        <input type="hidden" name="id_item" value="<?= (int)$itemActual['id_item'] ?>">
                                        <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                                        <input type="hidden" name="return_item" value="<?= (int)$itemActualIdx ?>">
                                        <button type="submit" class="btn btn-outline-danger btn-xs">Borrar</button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <div class="text-muted mt-1"><em>No hay archivo subido aún.</em></div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-warning">Tipo no soportado: <?= htmlspecialchars($tipo) ?></div>
                    <?php endif; ?>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success">Guardar e ir al siguiente</button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Panel derecho -->
        <div class="col-md-4 p-3" style="height: 100%; overflow-y: auto;">
            <h5>¿Cómo va tu avance?</h5>
            <ul class="list-unstyled">
                <li><strong>Completados:</strong> <?= $completados ?> / <?= $totalItems ?></li>
                <li><strong>Actual:</strong> Ítem <?= $itemActualIdx ?></li>
            </ul>
        </div>
    </div>

    <!-- Div Inferior -->
    <div class="bg-white shadow-sm p-3">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="btn-group mb-2" role="group" aria-label="Navegación por ítems">
                <?php
                for ($i=1; $i <= $totalItems; $i++) {
                    $it = $items[$i-1];
                    $idIt = (int)$it['id_item'];
                    $tp = $it['tipo'];
                    $filled = isset($respuestas[$idIt]) && ri_val_esta_lleno($respuestas[$idIt], $tp);

                    $estado = 'lock';
                    if ($i < $primerIncompletoIdx)      $estado = 'done';
                    elseif ($i === $itemActualIdx)      $estado = 'active';
                    elseif ($i === $primerIncompletoIdx)$estado = 'next';

                    $cls = pillClass($estado);
                    $disabled = ($estado === 'lock') ? 'disabled' : '';
                    $url = strtok($_SERVER["REQUEST_URI"], '?') . '?item=' . $i;

                    echo "<a href='{$url}' class='btn {$cls} {$disabled}' style='min-width:42px'>{$i}</a>";
                }
                ?>
            </div>

            <div class="mb-2">
                <button class="btn btn-primary" type="button">Solicitar Revisión de Informe</button>
            </div>
        </div>
    </div>

</div>

<!-- Modales -->
<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="imgModalSrc" src="" alt="Imagen" style="width:100%; height:auto; display:block;">
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// ========= Programa → vista ODS relacionados =========
const PROG_ODS_MAP = <?= json_encode($progOdsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
function renderProgOdsView() {
  const sel = document.getElementById('programa_select');
  if (!sel) return;
  const val = sel.value.trim();
  const box = document.getElementById('prog_ods_view');
  if (!box) return;
  if (val === '') { box.innerHTML = '<em>Selecciona un programa para ver sus ODS.</em>'; return; }
  const txt = PROG_ODS_MAP[val] || '';
  box.innerHTML = txt ? txt : '<em>Este programa no tiene ODS asociados.</em>';
}
document.addEventListener('change', e => { if (e.target && e.target.id === 'programa_select') renderProgOdsView(); });

document.addEventListener('DOMContentLoaded', () => {
  renderProgOdsView();

  // Sincroniza ODS múltiple → hidden CSV
  const sel = document.getElementById('ods_select');
  const hid = document.getElementById('ods_hidden_csv');
  if (sel && hid) {
    const sync = () => {
      const vals = Array.from(sel.selectedOptions).map(o => o.value.trim()).filter(Boolean);
      hid.value = vals.join(',');
    };
    sel.addEventListener('change', sync);
    sync();
  }
});

// ========= Imagen =========
function showImg(url) {
  const img = document.getElementById('imgModalSrc');
  img.src = url;
  if (window.jQuery && typeof jQuery.fn.modal === 'function') {
    jQuery('#imgModal').modal('show');
  } else if (window.bootstrap && window.bootstrap.Modal) {
    new bootstrap.Modal(document.getElementById('imgModal')).show();
  }
}

// ========= Utilidad para mostrar modal (BS4 o BS5) =========
function showBsModal(element) {
  if (window.jQuery && typeof jQuery.fn.modal === 'function') {
    jQuery(element).modal('show');
  } else if (window.bootstrap && window.bootstrap.Modal) {
    const m = new bootstrap.Modal(element);
    m.show();
  } else {
    element.style.display = 'block';
  }
}

// ========= Modal de VIDEO dinámico (crea y destruye) =========
function openVideoModal(videoUrl) {
  // Elimina cualquier modal dinámico previo
  const old = document.getElementById('ytModalDyn');
  if (old && old.parentNode) old.parentNode.removeChild(old);

  // Construye el modal (compatible BS4/BS5)
  const modal = document.createElement('div');
  modal.className = 'modal fade';
  modal.id = 'ytModalDyn';
  modal.setAttribute('tabindex', '-1');
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML = `
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-0">
          <div style="position:relative; width:100%; height:0; padding-bottom:56.25%; background:#000;">
            <iframe id="ytFrameDyn"
              src="logica/video_embed.php?u=${encodeURIComponent(videoUrl)}"
              style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen>
            </iframe>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  // Al ocultarse, destruir COMPLETAMENTE el modal (mata el iframe y el audio)
  const teardown = () => {
    try {
      const f = modal.querySelector('#ytFrameDyn');
      if (f && f.contentWindow) {
        // Por si tu video_embed.php escucha un STOP
        f.contentWindow.postMessage('STOP', '*');
      }
    } catch (e) {}
    if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
  };

  if (window.jQuery) {
    jQuery(modal).on('hidden.bs.modal', teardown);
  }
  modal.addEventListener('hidden.bs.modal', teardown);

  showBsModal(modal);
}

// Delegación: click en botón de video
document.addEventListener('click', function (ev) {
  const btn = ev.target.closest('.video-btn');
  if (!btn) return;
  const url = btn.getAttribute('data-video') || '';
  if (!url) return;
  openVideoModal(url);
});
</script>
