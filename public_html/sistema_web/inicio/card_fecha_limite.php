<?php
// sistema_web/inicio/card_fecha_limite.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Lima');

if (!isset($conexion)) { include_once __DIR__ . '/../componentes/db.php'; }

$tzLima = new DateTimeZone('America/Lima');
$defaultTitle = 'Fecha limite';
$defaultFriendlyMessage = 'Sin fechas limites por el momento.';
$defaultDeadline = (new DateTime('+7 days', $tzLima))->setTime(23, 59, 0)->format('Y-m-d H:i:s');

$deadlineVisible = false;
$cfg = array(
  'titulo' => $defaultTitle,
  'mensaje' => $defaultFriendlyMessage,
  'deadline' => $defaultDeadline,
  'updated_by' => null,
  'updated_at' => null
);

if (isset($conexion) && ($conexion instanceof mysqli)) {
  $resEvt = $conexion->query("SELECT estado FROM evt_eventos WHERE codigo='inicio_fecha_limite_visible' LIMIT 1");
  if ($resEvt && $resEvt->num_rows > 0) {
    $evtRow = $resEvt->fetch_assoc();
    $deadlineVisible = (isset($evtRow['estado']) && (int)$evtRow['estado'] === 1);
  }

  $resCfg = $conexion->query("SELECT titulo, mensaje, deadline, updated_by, updated_at FROM inicio_fecha_limite WHERE id=1 LIMIT 1");
  if ($resCfg && $resCfg->num_rows > 0) {
    $row = $resCfg->fetch_assoc();
    if (isset($row['titulo']) && trim((string)$row['titulo']) !== '') {
      $cfg['titulo'] = (string)$row['titulo'];
    }
    if (isset($row['mensaje']) && trim((string)$row['mensaje']) !== '') {
      $cfg['mensaje'] = (string)$row['mensaje'];
    }
    if (isset($row['deadline']) && trim((string)$row['deadline']) !== '') {
      $cfg['deadline'] = (string)$row['deadline'];
    }
    $cfg['updated_by'] = isset($row['updated_by']) ? $row['updated_by'] : null;
    $cfg['updated_at'] = isset($row['updated_at']) ? $row['updated_at'] : null;
  }
}

$deadlineDate = DateTime::createFromFormat('Y-m-d H:i:s', $cfg['deadline'], $tzLima);
if (!$deadlineDate) {
  $deadlineDate = DateTime::createFromFormat('Y-m-d H:i', $cfg['deadline'], $tzLima);
}
if (!$deadlineDate) {
  $deadlineVisible = false;
}

$nowLima = new DateTime('now', $tzLima);
$expired = false;
$dlISO = '';
$dlText = '-';
if ($deadlineDate) {
  $expired = ($deadlineDate <= $nowLima);
  $dlISO = $deadlineDate->format('Y-m-d\TH:i:sP');
  $dlText = $deadlineDate->format('d/m/Y H:i');
}

$titleToShow = trim((string)$cfg['titulo']) !== '' ? (string)$cfg['titulo'] : $defaultTitle;
$messageToShow = trim((string)$cfg['mensaje']) !== '' ? (string)$cfg['mensaje'] : $defaultFriendlyMessage;
$friendlyOffMessage = 'Sin fechas limites por el momento.';

$statusClass = 'badge-secondary';
$statusText = 'Sin fechas';
if ($deadlineVisible) {
  if ($expired) {
    $statusClass = 'badge-dark';
    $statusText = 'Cerrado';
  } else {
    $statusClass = 'badge-light text-danger';
    $statusText = 'Activo';
  }
}
?>
<style>
  .dl-card .counter-grid { display:grid; grid-template-columns: repeat(2, minmax(0,1fr)); gap:10px; }
  .dl-card .tile { border:1px solid #f1c1c1; border-radius:12px; padding:14px; text-align:center; box-shadow:0 1px 2px rgba(0,0,0,.04); }
  .dl-card .num { font-size:32px; font-weight:800; line-height:1; }
  .dl-card .lab { font-size:12px; color:#6c757d; }
  .dl-card .meta { display:flex; flex-wrap:wrap; align-items:center; margin-bottom:10px; }
  .dl-card .chip { display:inline-flex; align-items:center; border:1px dashed #f3c2c2; color:#dc3545; background:#fff; border-radius:999px; padding:4px 10px; margin-right:8px; margin-bottom:8px; font-size:.9rem; }
  @media (max-width: 767.98px){ .dl-card .num{ font-size:28px; } }
</style>

<div class="card home-card dl-card" style="border:1px solid #f3c2c2;border-radius:.5rem;overflow:hidden;">
  <div class="card-header py-2 d-flex align-items-center justify-content-between" style="background:#dc3545;color:#fff;">
    <div class="d-flex align-items-center">
      <i class="fas fa-hourglass-half mr-2"></i>
      <h6 class="m-0">Fecha limite</h6>
    </div>
    <span id="dl-status" class="badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusText) ?></span>
  </div>

  <div class="card-body" style="padding:12px;">
    <?php if ($deadlineVisible && $deadlineDate): ?>
      <h6 class="mb-1"><?= htmlspecialchars($titleToShow) ?></h6>
      <p class="text-muted mb-2" style="font-size:.9rem;"><?= nl2br(htmlspecialchars($messageToShow)) ?></p>
    <?php endif; ?>

    <?php if ($deadlineVisible && $deadlineDate): ?>
      <div class="meta">
        <span class="chip">
          <i class="far fa-calendar-alt mr-2"></i>
          <strong class="mr-1">Termina:</strong>
          <span id="dl-end-text"><?= htmlspecialchars($dlText) ?></span>
        </span>
        <span class="text-muted small">(Hora de Lima)</span>
      </div>

      <div id="dl-countdown" class="counter-grid" <?= $expired ? 'style="display:none;"' : '' ?>>
        <div class="tile"><div id="dl-days" class="num">0</div><div class="lab">Dias</div></div>
        <div class="tile"><div id="dl-hours" class="num">0</div><div class="lab">Horas</div></div>
        <div class="tile"><div id="dl-minutes" class="num">0</div><div class="lab">Minutos</div></div>
        <div class="tile"><div id="dl-seconds" class="num">0</div><div class="lab">Segundos</div></div>
      </div>

      <div id="dl-ended" class="alert alert-danger mb-0" <?= $expired ? '' : 'style="display:none;"' ?>>
        <i class="fas fa-exclamation-triangle mr-1"></i> Fecha vencida
      </div>
    <?php else: ?>
      <div class="alert alert-light border mb-0"><?= htmlspecialchars($friendlyOffMessage) ?></div>
    <?php endif; ?>
  </div>
</div>

<?php if ($deadlineVisible && $deadlineDate): ?>
<script>
(function(){
  var iso = '<?= htmlspecialchars($dlISO, ENT_QUOTES) ?>';
  var deadline = new Date(iso).getTime();

  var statusEl = document.getElementById('dl-status');
  var cdEl = document.getElementById('dl-countdown');
  var endedEl = document.getElementById('dl-ended');
  var daysEl = document.getElementById('dl-days');
  var hoursEl = document.getElementById('dl-hours');
  var minutesEl = document.getElementById('dl-minutes');
  var secondsEl = document.getElementById('dl-seconds');

  function setText(el, v){ if(el) el.textContent = v; }

  function tick(){
    var now = Date.now();
    var diff = deadline - now;

    if (diff <= 0){
      if (statusEl){ statusEl.className = 'badge badge-dark'; statusEl.textContent = 'Cerrado'; }
      if (cdEl) cdEl.style.display = 'none';
      if (endedEl) endedEl.style.display = '';
      return;
    }

    var d = Math.floor(diff / (1000 * 60 * 60 * 24));
    var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    var s = Math.floor((diff % (1000 * 60)) / 1000);

    if (statusEl){ statusEl.className = 'badge badge-light text-danger'; statusEl.textContent = 'Activo'; }
    if (cdEl) cdEl.style.display = '';
    if (endedEl) endedEl.style.display = 'none';

    setText(daysEl, d);
    setText(hoursEl, h);
    setText(minutesEl, m);
    setText(secondsEl, s);
  }

  tick();
  window.__dlInterval && clearInterval(window.__dlInterval);
  window.__dlInterval = setInterval(tick, 1000);
})();
</script>
<?php endif; ?>
