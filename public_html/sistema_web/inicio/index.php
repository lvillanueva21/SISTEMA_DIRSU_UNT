<?php
// sistema_web/inicio/index.php
// Este archivo es incluido dentro de .../direccion_rsu/inicio.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

$scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
$appBasePath = rtrim(dirname($scriptName), '/');
if ($appBasePath === '' || $appBasePath === '.') {
  $appBasePath = '/sistema_web';
}

$idRol   = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null;
$usuario = $_SESSION['usuario'] ?? '';
$rolNombre = '—';
$rolIcon   = '👤';

if (!isset($conexion)) { include_once __DIR__ . '/../componentes/db.php'; }

// Rol: nombre + icono elegante por tipo
if ($idRol !== null) {
  if ($st = $conexion->prepare("SELECT nombre FROM rol WHERE id=? LIMIT 1")) {
    $st->bind_param("i", $idRol);
    $st->execute();
    $st->bind_result($rolNombreDb);
    if ($st->fetch() && $rolNombreDb) { $rolNombre = $rolNombreDb; }
    $st->close();
  }
  // Mapeo sencillo de iconos
  $rolIcons = [
    0 => '🛠️', // Administrador
    1 => '🧭', // Dirección RSU y Extensión Cultural
    2 => '🗂️', // Coordinador de Proyecto
    3 => '🏛️', // Decanato
    4 => '🏫', // Dirección de Departamento
    5 => '🤝', // Comité RS de Facultad
  ];
  if (isset($rolIcons[$idRol])) { $rolIcon = $rolIcons[$idRol]; }
}

// Prefill desde DIRECTORIO
$contact = [
  'email' => '', 'telefono' => '',
  'nombres' => '', 'apellidos' => '',
  'telefono_asistente' => '', 'correo_asistente' => '',
  'updated_at' => null
];
$hasRecord = false;

if ($usuario !== '') {
  $chk = $conexion->query("SHOW TABLES LIKE 'directorio'");
  if ($chk && $chk->num_rows > 0) {
    if ($st = $conexion->prepare("
      SELECT email, telefono, nombres, apellidos, telefono_asistente, correo_asistente, updated_at
      FROM directorio WHERE usuario=? LIMIT 1
    ")) {
      $st->bind_param("s", $usuario);
      $st->execute();
      $res = $st->get_result()->fetch_assoc();
      if ($res) {
        foreach ($contact as $k => $_) { $contact[$k] = (string)($res[$k] ?? ''); }
        $hasRecord = true;
      }
      $st->close();
    }
  }
}

// Mensajes flash y bandera de “recién guardado”
$flashMsg = $_SESSION['contact_msg']      ?? null;
$flashTyp = $_SESSION['contact_msg_type'] ?? null;
$justSaved = ($flashTyp === 'success');
unset($_SESSION['contact_msg'], $_SESSION['contact_msg_type']);

// Helper para fecha bonita
function fecha_bonita($dt) {
  if (!$dt) return '';
  return date('d/m/Y H:i', strtotime($dt));
}
?>
<style>
  /* Imágenes de cards (recorte uniforme y cursor de zoom) */
  .image-wrapper{ height:300px; overflow:hidden; }
  @media (max-width: 767.98px){ .image-wrapper{ height:220px; } }
  .img-thumb{
    width:100%; height:100%;
    object-fit:cover; object-position: top;
    cursor:zoom-in;
  }

  /* Modal visor con zoom/arrastre */
  .viewer-container{
    position:relative; background:#000; height:75vh; overflow:hidden; cursor:grab;
  }
  .viewer-container:active{ cursor:grabbing; }
  #modalImage{
    position:absolute; top:50%; left:50%;
    transform:translate(-50%, -50%) scale(1);
    max-width:none; user-select:none; pointer-events:none;
  }
</style>
<style>
  /* Tarjeta de presentación (business card) */
  .bc-card{
    border:0; border-radius:1rem;
    box-shadow:0 12px 30px rgba(0,0,0,.08);
    overflow:hidden; background:linear-gradient(135deg,#f8fafc,#ffffff);
  }
  .bc-head{
    background:linear-gradient(135deg,#2563eb,#1e40af);
    color:#fff; padding:1rem 1.25rem;
    display:flex; align-items:center; gap:.75rem;
  }
  .bc-head .bc-icon{
    width:48px; height:48px; border-radius:12px;
    background:rgba(255,255,255,.15); display:flex; align-items:center; justify-content:center;
    font-size:1.5rem;
  }
  .bc-body{ padding:1rem 1.25rem; }
  .bc-row{ display:flex; flex-wrap:wrap; margin:-.25rem; }
  .bc-col{ padding:.25rem; width:50%; }
  .bc-item{
    background:#fff; border:1px solid #eef2f7; border-radius:.75rem;
    padding:.75rem; height:100%;
  }
  .bc-item .label{ color:#64748b; font-size:.8rem; margin-bottom:.25rem; }
  .bc-item .value{ font-weight:600; }
  @media (max-width: 767.98px){ .bc-col{ width:100%; } }

  /* Modal “bonito” de info */
  .nice-modal .modal-header{
    background:linear-gradient(135deg,#16a34a,#15803d); color:#fff;
    border-bottom:0; padding:.8rem 1rem;
  }
  .nice-modal .modal-content{ border-radius:1rem; overflow:hidden; }
  .nice-modal .success-icon{
    width:52px;height:52px;border-radius:50%;
    background:#dcfce7; color:#15803d; display:flex; align-items:center; justify-content:center;
    font-size:1.4rem; margin-right:.65rem;
  }

  /* Form 2 columnas responsivo */
  .form-grid{ display:flex; flex-wrap:wrap; margin:-.5rem; }
  .form-col{ padding:.5rem; width:50%; }
  @media (max-width: 767.98px){ .form-col{ width:100%; } }
</style>
<style>
  /* === Compactar el card de bienvenida sin tocar la estructura === */
  .bc-card.bc-compact{
    border-radius:.75rem;
    box-shadow:0 8px 18px rgba(0,0,0,.06);
  }
  .bc-compact .bc-head{padding:.5rem .75rem;}
  .bc-compact .bc-icon{
    width:36px;height:36px;border-radius:8px;
    font-size:1.1rem;
  }
  .bc-compact .bc-title{font-size:.95rem;font-weight:700;margin:0;}
  .bc-compact .bc-sub{font-size:.82rem;opacity:.9;margin:0;}
  .bc-compact .bc-body{padding:.55rem .75rem;}
  .bc-compact .text-muted{font-size:.8rem;}
  .bc-compact .mt-3{margin-top:.5rem !important;}

  /* Si muestras la “tarjeta de presentación” dentro del mismo card, también la comprimimos */
  .bc-compact .bc-row{margin:-.2rem;}
  .bc-compact .bc-col{padding:.2rem;}
  .bc-compact .bc-item{
    padding:.5rem;border-radius:.5rem;
    border:1px solid #eef2f7;
  }
  .bc-compact .bc-item .label{font-size:.72rem;margin-bottom:.15rem;color:#64748b;}
  .bc-compact .bc-item .value{font-size:.9rem;font-weight:600;}

  @media (max-width: 767.98px){
    .bc-compact .bc-head{padding:.45rem .65rem;}
    .bc-compact .bc-body{padding:.45rem .65rem;}
  }
</style>
<style>
  /* Cards iguales y compactos */
  .home-row { margin-top: .75rem; }
  .home-col { display:flex; }
  .home-card {
    width:100%;
    display:flex; flex-direction:column;
    box-shadow:0 8px 24px rgba(0,0,0,.06);
    border-radius:.75rem;
    min-height: 260px;   /* alto mínimo igual para todos */
  }
  .home-card .card-header{ padding:.5rem .75rem; }
  .home-card .card-body{ padding:.75rem; font-size:.92rem; }
  @media (max-width: 767.98px){
    .home-card{ min-height: auto; }
  }
</style>
<style>
  :root{
    --btn-comite: #0a72cc;       /* azul */
    --btn-depto:  #f4ca66;       /* amarillo */
    --btn-decan:  #61b1c7;       /* cian */
    --btn-dirsu:  #57a868;       /* verde */
    --ink-dark:   #111827;       /* gris muy oscuro para texto */
  }

  /* Estilo base de la barra (el tuyo) ... */
  .home-toolbar{
    background: linear-gradient(180deg,#ffffff,#f8fafc);
    border: 1px solid #e5e7eb;
    border-radius: .5rem;
    padding: .35rem .5rem;
    margin-bottom: .75rem;
    box-shadow: 0 6px 14px rgba(0,0,0,.04);
  }
  .home-toolbar .btn{ margin-right:.35rem; font-weight:600; }
  .home-toolbar .btn:last-child{ margin-right:0; }
  .home-toolbar .label{ font-size:.82rem; color:#64748b; margin-right:.5rem; white-space:nowrap; }
  @media (max-width: 767.98px){
    .home-toolbar{ padding:.3rem .4rem; }
    .home-toolbar .btn{ margin:.2rem .3rem .2rem 0; }
  }

  /* === Botones con color de la imagen === */
  .btn-comite{
    background: var(--btn-comite); border-color: var(--btn-comite); color:#fff;
  }
  .btn-comite:hover{ background:#0966b7; border-color:#0966b7; }
  .btn-comite:active{ background:#085ba3 !important; border-color:#085ba3 !important; }
  .btn-comite:focus{ box-shadow:0 0 0 .2rem rgba(10,114,204,.35); }

  .btn-depto{
    background: var(--btn-depto); border-color: var(--btn-depto); color:var(--ink-dark);
  }
  .btn-depto:hover{ background:#dbb55b; border-color:#dbb55b; color:var(--ink-dark); }
  .btn-depto:active{ background:#c3a151 !important; border-color:#c3a151 !important; color:var(--ink-dark); }
  .btn-depto:focus{ box-shadow:0 0 0 .2rem rgba(244,202,102,.45); }

  .btn-decan{
    background: var(--btn-decan); border-color: var(--btn-decan); color:var(--ink-dark);
  }
  .btn-decan:hover{ background:#579fb3; border-color:#579fb3; color:var(--ink-dark); }
  .btn-decan:active{ background:#4d8d9f !important; border-color:#4d8d9f !important; color:var(--ink-dark); }
  .btn-decan:focus{ box-shadow:0 0 0 .2rem rgba(97,177,199,.4); }

  .btn-dirsu{
    background: var(--btn-dirsu); border-color: var(--btn-dirsu); color:var(--ink-dark);
  }
  .btn-dirsu:hover{ background:#4e975d; border-color:#4e975d; color:var(--ink-dark); }
  .btn-dirsu:active{ background:#458653 !important; border-color:#458653 !important; color:var(--ink-dark); }
  .btn-dirsu:focus{ box-shadow:0 0 0 .2rem rgba(87,168,104,.4); }
  /* Botón de video: añade icono play, pulso y brillo al hover */
.home-toolbar .video-btn{
  position: relative;
  padding-left: 2rem;                 /* espacio para el play */
  border-width: 2px;
  transition: transform .06s ease, box-shadow .2s ease;
}
.home-toolbar .video-btn:active{ transform: translateY(1px); }

/* Círculo con triángulo (play) */
.home-toolbar .video-btn::before{
  content: "";
  position: absolute; left: .5rem; top: 50%; transform: translateY(-50%);
  width: 1.05rem; height: 1.05rem; border-radius: 999px;
  background: rgba(255,255,255,.9);
  box-shadow: 0 0 0 3px rgba(255,255,255,.35) inset, 0 0 0 0 rgba(255,255,255,.0);
}

/* Triángulo dentro del círculo */
.home-toolbar .video-btn::after{
  content: "";
  position: absolute; left: .82rem; top: 50%; transform: translateY(-50%);
  width: 0; height: 0;
  border-left: .42rem solid #111;           /* color del triángulo */
  border-top:  .26rem solid transparent;
  border-bottom: .26rem solid transparent;
  filter: drop-shadow(0 1px 0 rgba(0,0,0,.08));
}

/* Halo animado al pasar el mouse (llama la atención = es video) */
.home-toolbar .video-btn:hover{
  box-shadow: 0 0 0 .15rem rgba(0,0,0,.05), 0 8px 20px rgba(0,0,0,.12);
}
.home-toolbar .video-btn:hover::before{
  animation: videoPulse 1.2s ease-out infinite;
}

@keyframes videoPulse{
  0%   { box-shadow: 0 0 0 0 rgba(255,255,255,.0), 0 0 0 3px rgba(255,255,255,.35) inset; }
  70%  { box-shadow: 0 0 0 8px rgba(255,255,255,.15), 0 0 0 3px rgba(255,255,255,.35) inset; }
  100% { box-shadow: 0 0 0 0 rgba(255,255,255,.0), 0 0 0 3px rgba(255,255,255,.35) inset; }
}

/* Accesibilidad: foco claro al tabular */
.home-toolbar .video-btn:focus{
  outline: none;
  box-shadow: 0 0 0 .2rem rgba(17,24,39,.12), 0 0 0 .35rem rgba(255,255,255,.45);
}

/* En botones claros (amarillo/cian/verde) el triángulo más oscuro para contraste */
.btn-depto.video-btn::after,
.btn-decan.video-btn::after,
.btn-dirsu.video-btn::after{
  border-left-color: var(--ink-dark);
}

</style>
<!-- Toolbar superior de acciones rápidas -->
<div class="home-toolbar d-flex align-items-center flex-wrap">
  <span class="label"><i class="fas fa-video mr-1"></i> Tutoriales para Revisión de Informe Semestral 2025 - I:</span>

  <a class="btn btn-sm btn-comite video-btn"
     href="https://drive.google.com/file/d/1jBXfcNGzRUfhDQ-QPuiH13ZvGGzng9Q5/view?usp=sharing"
     target="_blank" rel="noopener noreferrer"
     data-toggle="tooltip" title="Ver tutorial (se abrirá en otra pestaña)">
    Comité de Facultad
  </a>

  <a class="btn btn-sm btn-depto video-btn"
     href="https://drive.google.com/file/d/1Q2DEyhG_eFQ3TXIpEuB3BmpBcxAhWf2A/view?usp=sharing"
     target="_blank" rel="noopener noreferrer"
     data-toggle="tooltip" title="Ver tutorial (se abrirá en otra pestaña)">
    Dirección de Departamento
  </a>

  <a class="btn btn-sm btn-decan video-btn"
     href="https://drive.google.com/file/d/1jROsasdErplDsT2pPiKqwDFmmlgr_242/view?usp=sharing"
     target="_blank" rel="noopener noreferrer"
     data-toggle="tooltip" title="Ver tutorial (se abrirá en otra pestaña)">
    Decanato de Facultad
  </a>

  <a class="btn btn-sm btn-dirsu video-btn"
     href="https://drive.google.com/file/d/1zB0nJ-TcGaYefYNK2Mpj-bNghW_4S6rV/view?usp=sharing"
     target="_blank" rel="noopener noreferrer"
     data-toggle="tooltip" title="Ver tutorial (se abrirá en otra pestaña)">
    Dirección de RSU
  </a>
</div>


<!-- Row: Bienvenida (columna 1) + columna 2 vacía -->
<div class="row home-row">
  <!-- Columna 1: Card Bienvenido/a -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header d-flex align-items-center" style="background:linear-gradient(135deg,#2563eb,#1e40af); color:#fff;">
        <div class="bc-icon mr-2" style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,.15);display:flex;align-items:center;justify-content:center;font-size:1.1rem;">
          <?= htmlspecialchars($rolIcon) ?>
        </div>
        <div>
          <div style="font-weight:700;line-height:1;">Bienvenido/a</div>
          <small class="text-white-75">Tu rol: <b><?= htmlspecialchars($rolNombre) ?></b></small>
        </div>
      </div>

      <div class="card-body">
        <?php if ($flashMsg && $flashTyp!=='success'): ?>
          <div class="alert alert-danger mb-3"><?= htmlspecialchars($flashMsg) ?></div>
        <?php endif; ?>

        <?php if ($hasRecord): ?>
          <!-- Tarjeta de presentación con la info registrada (reutiliza tus clases .bc-*) -->
          <div class="bc-row">
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-id-badge"></i> Nombre</div><div class="value"><?= htmlspecialchars($contact['nombres'].' '.$contact['apellidos']) ?></div></div></div>
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-user-tag"></i> Rol</div><div class="value"><?= htmlspecialchars($rolNombre) ?></div></div></div>
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-envelope"></i> Email</div><div class="value"><?= htmlspecialchars($contact['email']) ?></div></div></div>
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-phone"></i> Teléfono</div><div class="value"><?= htmlspecialchars($contact['telefono']) ?></div></div></div>
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-user-friends"></i> Tel. asistente</div><div class="value"><?= htmlspecialchars($contact['telefono_asistente'] ?: '—') ?></div></div></div>
            <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-at"></i> Correo asistente</div><div class="value"><?= htmlspecialchars($contact['correo_asistente'] ?: '—') ?></div></div></div>
          </div>

          <div class="text-muted mt-2" style="font-size:.85rem;">
            <i class="far fa-clock"></i> Última actualización: <?= htmlspecialchars(fecha_bonita($contact['updated_at'])) ?>
          </div>

          <div class="mt-3">
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#registroContactoModal">
              <i class="fas fa-edit"></i> Editar mis datos
            </button>
            <a class="btn btn-outline-secondary btn-sm" href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/componentes/sesion/cerrarSesion.php">
              <i class="fas fa-sign-out-alt"></i> Salir y cerrar sesión
            </a>
          </div>

        <?php else: ?>
          <div class="alert alert-warning mb-2">
            <i class="fas fa-exclamation-circle"></i> Para continuar, registra tus datos en la ventana que se ha abierto.
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Columna 2: Fecha Límite -->
<div class="col-md-6 home-col">
  <?php include __DIR__ . '/card_fecha_limite.php'; ?>
</div>
</div>

<!-- Modal SIEMPRE visible: si no hay registro -> formulario; si hay registro -> modal info elegante -->
<?php if (!$hasRecord): ?>
<!-- ===== Modal de REGISTRO (2 columnas, responsive) ===== -->
<div class="modal fade" id="registroContactoModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header" style="background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff;">
      <h6 class="modal-title mb-0"><i class="fas fa-address-card mr-1"></i> Registrar información de contacto</h6>
    </div>
    <form id="formContacto" method="post" action="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/inicio/guardar_contacto.php" novalidate>
      <div class="modal-body">
        <div class="alert alert-info mb-3">
          <b>Obligatorio:</b> Registra <b>Nombres</b>, <b>Apellidos</b>, <b>Correo institucional</b> y <b>Teléfono</b>.
          Los datos de <i>asistente</i> son <u>opcionales</u>. <br>
          Nota: <b>Nombres</b> y <b>Apellidos</b> se guardarán en <u>MAYÚSCULA</u>.
        </div>

        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ($appBasePath . '/direccion_rsu/inicio.php')) ?>">

        <div class="form-grid">
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_nombres">Nombres</label>
              <input type="text" class="form-control" id="contact_nombres" name="nombres"
                     value="<?= htmlspecialchars(mb_strtoupper($contact['nombres'] ?: '', 'UTF-8')) ?>"
                     placeholder="TUS NOMBRES" required minlength="2" maxlength="100">
              <div class="invalid-feedback">Ingresa tus nombres.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_apellidos">Apellidos</label>
              <input type="text" class="form-control" id="contact_apellidos" name="apellidos"
                     value="<?= htmlspecialchars(mb_strtoupper($contact['apellidos'] ?: '', 'UTF-8')) ?>"
                     placeholder="TUS APELLIDOS" required minlength="2" maxlength="100">
              <div class="invalid-feedback">Ingresa tus apellidos.</div>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_email">Correo institucional (@unitru.edu.pe)</label>
              <input type="email" class="form-control" id="contact_email" name="email"
                     value="<?= htmlspecialchars($contact['email']) ?>"
                     placeholder="tucuenta@unitru.edu.pe" required autocomplete="email" inputmode="email">
              <div class="invalid-feedback">Ingresa un correo válido @unitru.edu.pe.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_tel">Teléfono (9 dígitos, inicia con 9)</label>
              <input type="tel" class="form-control" id="contact_tel" name="telefono"
                     value="<?= htmlspecialchars($contact['telefono']) ?>"
                     placeholder="9XXXXXXXX" required minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
              <div class="invalid-feedback">Debe tener 9 dígitos y empezar con 9.</div>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_tel_asist">Teléfono asistente <small class="text-muted">(opcional)</small></label>
              <input type="tel" class="form-control" id="contact_tel_asist" name="telefono_asistente"
                     value="<?= htmlspecialchars($contact['telefono_asistente']) ?>"
                     placeholder="9XXXXXXXX (si aplica)" minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
              <div class="invalid-feedback">Si lo indicas, debe tener 9 dígitos y empezar con 9.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-0">
              <label class="mb-0" for="contact_email_asist">Correo asistente <small class="text-muted">(opcional)</small></label>
              <input type="email" class="form-control" id="contact_email_asist" name="correo_asistente"
                     value="<?= htmlspecialchars($contact['correo_asistente']) ?>"
                     placeholder="correo@dominio.com (si aplica)" autocomplete="email" inputmode="email">
              <div class="invalid-feedback">Correo inválido (si decides completarlo).</div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer d-flex justify-content-between">
        <a href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-sign-out-alt"></i> Salir y cerrar sesión
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </form>
  </div></div>
</div>
<?php else: ?>
<!-- ===== Modal de INFO ELEGANTE (si ya hay registro) ===== -->
<div class="modal fade nice-modal" id="infoContactoModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header">
      <div class="d-flex align-items-center">
        <div class="success-icon mr-2"><i class="fas fa-check"></i></div>
        <div>
          <div style="font-weight:700; line-height:1;">
            <?= $justSaved ? '¡Datos guardados con éxito!' : 'Tu información de contacto' ?>
          </div>
          <small class="text-white-50">Rol: <?= htmlspecialchars($rolNombre) ?></small>
        </div>
      </div>
    </div>
    <div class="modal-body">
      <div class="bc-row">
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-id-badge"></i> Nombre</div><div class="value"><?= htmlspecialchars($contact['nombres'].' '.$contact['apellidos']) ?></div></div></div>
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-envelope"></i> Email</div><div class="value"><?= htmlspecialchars($contact['email']) ?></div></div></div>
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-phone"></i> Teléfono</div><div class="value"><?= htmlspecialchars($contact['telefono']) ?></div></div></div>
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-user-friends"></i> Tel. asistente</div><div class="value"><?= htmlspecialchars($contact['telefono_asistente'] ?: '—') ?></div></div></div>
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="fas fa-at"></i> Correo asistente</div><div class="value"><?= htmlspecialchars($contact['correo_asistente'] ?: '—') ?></div></div></div>
        <div class="bc-col"><div class="bc-item"><div class="label"><i class="far fa-clock"></i> Actualizado</div><div class="value"><?= htmlspecialchars(fecha_bonita($contact['updated_at'])) ?></div></div></div>
      </div>
    </div>
    <div class="modal-footer d-flex justify-content-between">
      <a href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
        <i class="fas fa-sign-out-alt"></i> Salir y cerrar sesión
      </a>
      <div>
        <button class="btn btn-outline-primary btn-sm" data-dismiss="modal">
          <i class="fas fa-thumbs-up"></i> Entendido
        </button>
        <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#registroContactoModal" data-dismiss="modal">
          <i class="fas fa-edit"></i> Editar
        </button>
      </div>
    </div>
  </div></div>
</div>

<!-- Reutilizamos el mismo formulario de edición (oculto hasta que se use "Editar") -->
<div class="modal fade" id="registroContactoModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header" style="background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff;">
      <h6 class="modal-title mb-0"><i class="fas fa-address-card mr-1"></i> Editar información de contacto</h6>
    </div>
    <form id="formContacto" method="post" action="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/inicio/guardar_contacto.php" novalidate>
      <div class="modal-body">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? ($appBasePath . '/direccion_rsu/inicio.php')) ?>">
        <div class="form-grid">
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_nombres">Nombres (MAYÚSCULA)</label>
              <input type="text" class="form-control" id="contact_nombres" name="nombres"
                     value="<?= htmlspecialchars(mb_strtoupper($contact['nombres'], 'UTF-8')) ?>"
                     required minlength="2" maxlength="100">
              <div class="invalid-feedback">Ingresa tus nombres.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_apellidos">Apellidos (MAYÚSCULA)</label>
              <input type="text" class="form-control" id="contact_apellidos" name="apellidos"
                     value="<?= htmlspecialchars(mb_strtoupper($contact['apellidos'], 'UTF-8')) ?>"
                     required minlength="2" maxlength="100">
              <div class="invalid-feedback">Ingresa tus apellidos.</div>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_email">Correo institucional (@unitru.edu.pe)</label>
              <input type="email" class="form-control" id="contact_email" name="email"
                     value="<?= htmlspecialchars($contact['email']) ?>"
                     required autocomplete="email" inputmode="email">
              <div class="invalid-feedback">Ingresa un correo válido @unitru.edu.pe.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_tel">Teléfono</label>
              <input type="tel" class="form-control" id="contact_tel" name="telefono"
                     value="<?= htmlspecialchars($contact['telefono']) ?>"
                     required minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
              <div class="invalid-feedback">Debe tener 9 dígitos y empezar con 9.</div>
            </div>
          </div>

          <div class="form-col">
            <div class="form-group mb-2">
              <label class="mb-0" for="contact_tel_asist">Teléfono asistente <small class="text-muted">(opcional)</small></label>
              <input type="tel" class="form-control" id="contact_tel_asist" name="telefono_asistente"
                     value="<?= htmlspecialchars($contact['telefono_asistente']) ?>"
                     minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
              <div class="invalid-feedback">Si lo indicas, debe tener 9 dígitos y empezar con 9.</div>
            </div>
          </div>
          <div class="form-col">
            <div class="form-group mb-0">
              <label class="mb-0" for="contact_email_asist">Correo asistente <small class="text-muted">(opcional)</small></label>
              <input type="email" class="form-control" id="contact_email_asist" name="correo_asistente"
                     value="<?= htmlspecialchars($contact['correo_asistente']) ?>"
                     autocomplete="email" inputmode="email">
              <div class="invalid-feedback">Correo inválido (si decides completarlo).</div>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <a href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
          <i class="fas fa-sign-out-alt"></i> Salir y cerrar sesión
        </a>
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>
<?php
// Asegura tener $idRol disponible (ya lo tienes arriba en este mismo archivo)
$soyAdminDirsu = (int)($idRol ?? -1) === 1;
?>
<!-- ===== Row 1 ===== -->
<div class="row home-row">
  <!-- Card: Ruta de evaluación del informe semestral -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
        <strong><i class="fas fa-route"></i> Ruta de evaluación del informe semestral</strong>
        <div class="btn-group">
          <a class="btn btn-sm btn-light"
             href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/ruta_informe_semestral2025.jpg"
             download="ruta_informe_semestral2025.jpg"
             title="Descargar imagen" aria-label="Descargar imagen">
            <i class="fas fa-download"></i>
          </a>
          <button type="button" class="btn btn-sm btn-light open-image"
                  data-src="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/ruta_informe_semestral2025.jpg"
                  title="Expandir imagen" aria-label="Expandir imagen">
            <i class="fas fa-expand-arrows-alt"></i>
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <?php include __DIR__ . '/card_1_ruta_evaluacion.php'; ?>
      </div>
    </div>
  </div>

  <!-- Card: Comunicado de vencimiento -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-2">
        <strong><i class="fas fa-bullhorn"></i> Comunicado por el Vencimiento del Plazo de Informe Semestrales</strong>
        <div class="btn-group">
          <a class="btn btn-sm btn-light"
             href="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/comunicado_vencimiento.jpeg"
             download="comunicado_vencimiento.jpeg"
             title="Descargar imagen" aria-label="Descargar imagen">
            <i class="fas fa-download"></i>
          </a>
          <button type="button" class="btn btn-sm btn-light open-image"
                  data-src="<?= htmlspecialchars($appBasePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/comunicado_vencimiento.jpeg"
                  title="Expandir imagen" aria-label="Expandir imagen">
            <i class="fas fa-expand-arrows-alt"></i>
          </button>
        </div>
      </div>
      <div class="card-body p-0">
        <?php include __DIR__ . '/card_2_comunicado_vencimiento.php'; ?>
      </div>
    </div>
  </div>
</div>
<?php if ($soyAdminDirsu): ?>
<!-- ===== Row 2 (solo rol = 1) ===== -->
<div class="row home-row">
  <!-- Card 3 -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-warning text-white">
        <strong><i class="fas fa-bell"></i> Alertas & vencimientos</strong>
      </div>
      <div class="card-body">
        <div class="text-muted small">Contenido próximo…</div>
      </div>
    </div>
  </div>

  <!-- Card 4 -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-danger text-white">
        <strong><i class="fas fa-database"></i> Reboot de directorio</strong>
      </div>
      <div class="card-body">
        <?php include __DIR__ . '/card_4_control_directorio.php'; ?>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>


<script>
document.addEventListener('DOMContentLoaded', function () {
  // Campos
  var form  = document.getElementById('formContacto');
  var email = document.getElementById('contact_email');
  var tel   = document.getElementById('contact_tel');
  var nom   = document.getElementById('contact_nombres');
  var ape   = document.getElementById('contact_apellidos');
  var telA  = document.getElementById('contact_tel_asist');
  var mailA = document.getElementById('contact_email_asist');

  function sanitizeTel(el){ if(!el) return; el.value = (el.value||'').replace(/\D/g,'').slice(0,9); }
  function upper(el){ if(!el) return; el.value = (el.value||'').toUpperCase(); }

  if (tel)  tel.addEventListener('input',  function(){ sanitizeTel(tel); });
  if (telA) telA.addEventListener('input', function(){ sanitizeTel(telA); });
  if (nom)  nom.addEventListener('input',  function(){ upper(nom); });
  if (ape)  ape.addEventListener('input',  function(){ upper(ape); });

  function validateEmailDomain() {
    if (!email) return;
    var v = (email.value || '').trim().toLowerCase(); email.value = v;
    email.setCustomValidity(/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i.test(v) ? '' : 'Dominio no permitido');
  }
  if (email) email.addEventListener('input', validateEmailDomain);

  function validateOptionalEmail(el){
    if (!el) return;
    var v = (el.value || '').trim(); el.value = v;
    if (v === '') { el.setCustomValidity(''); return; }
    el.setCustomValidity(/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v) ? '' : 'Correo inválido');
  }
  if (mailA) mailA.addEventListener('input', function(){ validateOptionalEmail(mailA); });

  if (form) {
    form.addEventListener('submit', function (e) {
      sanitizeTel(tel); sanitizeTel(telA); upper(nom); upper(ape);
      validateEmailDomain(); validateOptionalEmail(mailA);

      if (tel && !/^9\d{8}$/.test(tel.value||''))    tel.setCustomValidity('Teléfono inválido'); else if (tel)  tel.setCustomValidity('');
      if (telA){
        var vA = (telA.value||'').trim();
        telA.setCustomValidity( vA==='' || /^9\d{8}$/.test(vA) ? '' : 'Teléfono inválido' );
      }
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    });
  }
// Abrir modal SOLO si NO hay registro; si se acaba de guardar, mostrar info
(function showModalWhenReady(){
  if (!(window.$ && $.fn.modal)) { return setTimeout(showModalWhenReady, 150); }

  <?php if (!$hasRecord): ?>
    // Usuario sin datos de contacto: abrir el formulario de registro
    if ($('#registroContactoModal').length) {
      $('#registroContactoModal').modal('show');
    }
  <?php elseif ($justSaved): ?>
    // Opcional: tras guardar y volver por redirect, mostrar confirmación
    if ($('#infoContactoModal').length) {
      $('#infoContactoModal').modal('show');
    }
  <?php endif; ?>
})();
});
</script>
<!-- Modal reutilizable para imágenes -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog" aria-labelledby="imgModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0" id="imgModalLabel">Vista de imagen</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body p-0">
        <div id="viewer" class="viewer-container">
          <img id="modalImage" alt="Imagen ampliada" />
        </div>
      </div>
      <div class="modal-footer py-2">
        <div class="btn-group mr-auto" role="group" aria-label="Zoom">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomOut"><i class="fas fa-minus"></i></button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomReset"><i class="fas fa-compress"></i></button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomIn"><i class="fas fa-plus"></i></button>
        </div>
        <a id="downloadImage" class="btn btn-primary btn-sm" href="#" download><i class="fas fa-download mr-1"></i> Descargar</a>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
(function(){
  var modalEl = document.getElementById('imgModal');
  var imgEl   = document.getElementById('modalImage');
  var viewer  = document.getElementById('viewer');
  var dlModal = document.getElementById('downloadImage');
  if (!modalEl || !imgEl || !viewer) return;

  var scale = 1, minScale = 0.5, maxScale = 6;
  var posX = 0, posY = 0, isDown = false, startX = 0, startY = 0;

  function applyTransform(){
    imgEl.style.transform = 'translate(calc(-50% + '+posX+'px), calc(-50% + '+posY+'px)) scale('+scale+')';
  }
  function resetTransform(){ scale = 1; posX = 0; posY = 0; applyTransform(); }
  function filenameFromPath(path){ try { return path.split('/').pop() || 'imagen.jpg'; } catch(e){ return 'imagen.jpg'; } }

  // --- Bootstrap-aware show/hide + fallback ---
  function hasBootstrapModal(){ return !!(window.$ && window.$.fn && typeof window.$.fn.modal === 'function'); }

  function showModal(){
    if (hasBootstrapModal()) { window.$('#imgModal').modal('show'); return; }
    // Fallback simple
    modalEl.style.display = 'block';
    modalEl.classList.add('show');
    document.body.classList.add('modal-open');
    if (!document.getElementById('__imgBackdrop')){
      var bd = document.createElement('div');
      bd.id = '__imgBackdrop';
      bd.className = 'modal-backdrop fade show';
      document.body.appendChild(bd);
    }
  }
  function hideModal(){
    if (hasBootstrapModal()) { window.$('#imgModal').modal('hide'); return; }
    modalEl.classList.remove('show');
    modalEl.style.display = 'none';
    document.body.classList.remove('modal-open');
    var bd = document.getElementById('__imgBackdrop'); if (bd) bd.remove();
    imgEl.src = '';
  }

  // Cerrar (fallback) con los botones que tienen data-dismiss="modal"
  modalEl.querySelectorAll('[data-dismiss="modal"]').forEach(function(btn){
    btn.addEventListener('click', hideModal);
  });
  // Limpiar al cerrar (si Bootstrap está disponible)
  if (hasBootstrapModal()){
    window.$('#imgModal').on('hidden.bs.modal', function(){ imgEl.src = ''; });
  }

  function openModal(src, alt){
    imgEl.src = src;
    imgEl.alt = alt || 'Imagen ampliada';
    if (dlModal){ dlModal.href = src; dlModal.download = filenameFromPath(src); }
    imgEl.onload = resetTransform;
    showModal();
  }

  // Botones de header (.open-image)
  document.querySelectorAll('.open-image').forEach(function(btn){
    btn.addEventListener('click', function(){
      openModal(btn.getAttribute('data-src'), btn.getAttribute('title') || btn.getAttribute('aria-label'));
    });
  });

  // Click en la miniatura (.img-thumb)
  document.querySelectorAll('.img-thumb').forEach(function(el){
    el.addEventListener('click', function(){
      openModal(el.getAttribute('data-full-src') || el.getAttribute('src'), el.getAttribute('alt'));
    });
  });

  // Zoom con rueda
  viewer.addEventListener('wheel', function(e){
    e.preventDefault();
    var delta = e.deltaY < 0 ? 0.1 : -0.1;
    var newScale = Math.min(maxScale, Math.max(minScale, scale + delta));
    if (newScale !== scale){
      var rect = viewer.getBoundingClientRect();
      var cx = e.clientX - rect.left - rect.width/2 - posX;
      var cy = e.clientY - rect.top  - rect.height/2 - posY;
      posX -= cx * (newScale/scale - 1);
      posY -= cy * (newScale/scale - 1);
      scale = newScale;
      applyTransform();
    }
  }, { passive:false });

  // Arrastre
  viewer.addEventListener('mousedown', function(e){ isDown = true; startX = e.clientX - posX; startY = e.clientY - posY; });
  viewer.addEventListener('mousemove', function(e){ if(!isDown) return; posX = e.clientX - startX; posY = e.clientY - startY; applyTransform(); });
  ['mouseup','mouseleave'].forEach(function(evt){ viewer.addEventListener(evt, function(){ isDown=false; }); });

  // Botones zoom
  var zi = document.getElementById('zoomIn'), zo = document.getElementById('zoomOut'), zr = document.getElementById('zoomReset');
  if (zi) zi.addEventListener('click', function(){ scale = Math.min(maxScale, scale + 0.2); applyTransform(); });
  if (zo) zo.addEventListener('click', function(){ scale = Math.max(minScale, scale - 0.2); applyTransform(); });
  if (zr) zr.addEventListener('click', resetTransform);

  // Doble clic: zoom/normal
  viewer.addEventListener('dblclick', function(){ if (scale === 1) { scale = 2; applyTransform(); } else { resetTransform(); } });

  // Escape para cerrar (fallback)
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') hideModal(); });
})();
</script>
