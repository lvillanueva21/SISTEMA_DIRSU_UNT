<?php
// sistema_web/inicio/card_fecha_limite.php (versión PRG)
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
date_default_timezone_set('America/Lima');

$idRol   = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null;
$usuario = $_SESSION['usuario'] ?? '';

if (!isset($conexion)) { include_once __DIR__ . '/../componentes/db.php'; }

// CSRF mínimo
if (empty($_SESSION['csrf_deadline'])) {
  $_SESSION['csrf_deadline'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf_deadline'];

// Mensaje flash desde el endpoint
$alert = null;
if (isset($_SESSION['dl_msg'])) {
  $alert = ['type' => ($_SESSION['dl_msg_type'] ?? 'success'), 'msg' => $_SESSION['dl_msg']];
  unset($_SESSION['dl_msg'], $_SESSION['dl_msg_type']);
}

// Leer registro actual (y detectar si está configurado)
$tzLima = new DateTimeZone('America/Lima');
$hadRow = false;
$row    = null;

$res = $conexion->query("SELECT titulo, mensaje, deadline, updated_by, updated_at FROM inicio_fecha_limite WHERE id=1 LIMIT 1");
if ($res && $res->num_rows) {
  $row = $res->fetch_assoc();
  $hadRow = true;
}
if (!$hadRow) {
  // Defaults: mensaje por defecto SOLO para admin (rol=1)
  $defaultMsg = ((int)$idRol === 1) ? 'Configura aquí tu primera fecha límite.' : '';
  $dtDef = new DateTime('+7 days', $tzLima);
  $row = [
    'titulo'     => 'Fecha Límite',
    'mensaje'    => $defaultMsg,
    'deadline'   => $dtDef->format('Y-m-d H:i:s'),
    'updated_by' => null,
    'updated_at' => null,
  ];
}

// Formateos
$dl        = new DateTime($row['deadline'], $tzLima);
$dlISO     = $dl->format('Y-m-d\TH:i:sP');   // ISO con offset -05:00
$dlText    = $dl->format('d/m/Y H:i');       // para mostrar
$dlDateVal = $dl->format('Y-m-d');           // para <input type="date">
$dlTimeVal = $dl->format('H:i:s');           // para <input type="time">
$nowLima   = new DateTime('now', $tzLima);
$expired   = ($dl <= $nowLima);

// Estado visual
$statusClass = $expired ? 'badge-dark' : 'badge-light text-danger';
$statusText  = $expired ? 'Cerrado'    : 'Activo';

// Si NO está configurado en BD y no es admin: no mostrar contador, y estado “No configurada”
$notConfigured = !$hadRow;
$hideCountdown = false;
if ($notConfigured && (int)$idRol !== 1) {
  $statusClass = 'badge-secondary';
  $statusText  = 'No configurada';
  $hideCountdown = true;
  $dlText = '—';
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
      <h6 class="m-0">Fecha Límite</h6>
    </div>
    <span id="dl-status" class="badge <?= htmlspecialchars($statusClass) ?>"><?= htmlspecialchars($statusText) ?></span>
  </div>

  <div class="card-body" style="padding:12px;">
    <?php if ($alert): ?>
      <div class="alert alert-<?= $alert['type']==='danger'?'danger':'success' ?> py-2 mb-2"><?= htmlspecialchars($alert['msg']) ?></div>
    <?php endif; ?>

    <h6 class="mb-1"><?= htmlspecialchars($row['titulo'] ?: 'Fecha Límite') ?></h6>
    <?php if (trim($row['mensaje']) !== ''): ?>
      <p class="text-muted mb-2" style="font-size:.9rem;"><?= nl2br(htmlspecialchars($row['mensaje'])) ?></p>
    <?php endif; ?>

    <?php if (!$hideCountdown || (int)$idRol === 1): ?>
      <div class="meta">
        <span class="chip">
          <i class="far fa-calendar-alt mr-2"></i>
          <strong class="mr-1">Termina:</strong>
          <span id="dl-end-text"><?= htmlspecialchars($dlText) ?></span>
        </span>
        <span class="text-muted small">(Hora de Lima)</span>
      </div>
    <?php endif; ?>

    <div id="dl-countdown" class="counter-grid" <?= ($expired || $hideCountdown) ? 'style="display:none;"' : '' ?>>
      <div class="tile"><div id="dl-days" class="num">0</div><div class="lab">Días</div></div>
      <div class="tile"><div id="dl-hours" class="num">0</div><div class="lab">Horas</div></div>
      <div class="tile"><div id="dl-minutes" class="num">0</div><div class="lab">Minutos</div></div>
      <div class="tile"><div id="dl-seconds" class="num">0</div><div class="lab">Segundos</div></div>
    </div>

    <div id="dl-ended" class="alert alert-danger mb-0" <?= ($expired && !$hideCountdown) ? '' : 'style="display:none;"' ?>>
      <i class="fas fa-exclamation-triangle mr-1"></i> Fecha vencida
    </div>

    <?php if ($hideCountdown && (int)$idRol !== 1): ?>
      <div class="alert alert-light border mb-0">Aún no hay una fecha límite configurada.</div>
    <?php endif; ?>

    <?php if ((int)$idRol === 1): ?>
      <hr>
      <form method="post" action="/sistema_web/inicio/guardar_fecha_limite.php" class="mt-2" autocomplete="off" novalidate>
        <input type="hidden" name="dl_action" value="save_deadline">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/sistema_web/direccion_rsu/inicio.php') ?>">

        <div class="form-group mb-2">
          <label class="mb-0">Título</label>
          <input type="text" name="titulo" class="form-control form-control-sm" required maxlength="120"
                 value="<?= htmlspecialchars($row['titulo'] ?? '') ?>">
        </div>

        <div class="form-group mb-2">
          <label class="mb-0">Mensaje</label>
          <textarea name="mensaje" class="form-control form-control-sm" rows="2" maxlength="300"><?= htmlspecialchars($row['mensaje'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
          <div class="form-group col-6">
            <label class="mb-0">Fecha (AAAA-MM-DD)</label>
            <input type="date" name="fecha" class="form-control form-control-sm" required
                   value="<?= htmlspecialchars($dlDateVal) ?>">
          </div>
          <div class="form-group col-6">
            <label class="mb-0">Hora (HH:MM:SS)</label>
            <input type="time" name="hora" step="1" class="form-control form-control-sm" required
                   value="<?= htmlspecialchars($dlTimeVal) ?>">
          </div>
        </div>

        <button type="submit" class="btn btn-danger btn-sm">
          <i class="fas fa-save mr-1"></i> Guardar
        </button>
      </form>
      <?php if ($row['updated_at']): ?>
        <div class="text-muted small mt-2">
          Última edición: <?= htmlspecialchars($row['updated_at']) ?><?= $row['updated_by'] ? ' por '.htmlspecialchars($row['updated_by']) : '' ?>
        </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<script>
(function(){
  var configured = <?= $hadRow ? 'true' : 'false' ?>;
  var isAdmin    = <?= ((int)$idRol === 1) ? 'true' : 'false' ?>;
  if (!configured && !isAdmin) { return; }

  var iso = '<?= htmlspecialchars($dlISO, ENT_QUOTES) ?>';
  var deadline = new Date(iso).getTime();

  var statusEl   = document.getElementById('dl-status');
  var cdEl       = document.getElementById('dl-countdown');
  var endedEl    = document.getElementById('dl-ended');
  var daysEl     = document.getElementById('dl-days');
  var hoursEl    = document.getElementById('dl-hours');
  var minutesEl  = document.getElementById('dl-minutes');
  var secondsEl  = document.getElementById('dl-seconds');

  function setText(el, v){ if(el) el.textContent = v; }

  function tick(){
    var now = Date.now();
    var diff = deadline - now;

    if (diff <= 0){
      if (statusEl){ statusEl.className = 'badge badge-dark'; statusEl.textContent = 'Cerrado'; }
      if (cdEl)     cdEl.style.display = 'none';
      if (endedEl)  endedEl.style.display = '';
      return;
    }

    var d = Math.floor(diff / (1000 * 60 * 60 * 24));
    var h = Math.floor((diff % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    var m = Math.floor((diff % (1000 * 60 * 60)) / (1000 * 60));
    var s = Math.floor((diff % (1000 * 60)) / 1000);

    if (statusEl){ statusEl.className = 'badge badge-light text-danger'; statusEl.textContent = 'Activo'; }
    if (cdEl)     cdEl.style.display = '';
    if (endedEl)  endedEl.style.display = 'none';

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
