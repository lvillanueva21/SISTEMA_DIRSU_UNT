<?php
// sistema_web/inicio/index.php
// Este archivo es incluido dentro de .../direccion_rsu/inicio.php
if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }

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

<div class="row">
  <div class="col-12">
    <?php if ($flashMsg && $flashTyp!=='success'): ?>
      <div class="alert alert-danger mb-3"><?= htmlspecialchars($flashMsg) ?></div>
    <?php endif; ?>

    <div class="bc-card bc-compact">
      <div class="bc-head">
        <div class="bc-icon"><?= htmlspecialchars($rolIcon) ?></div>
        <div>
          <div style="font-weight:700;">Bienvenido/a</div>
          <div style="opacity:.9">Tu rol: <b><?= htmlspecialchars($rolNombre) ?></b></div>
        </div>
      </div>

      <div class="bc-body">
        <?php if ($hasRecord): ?>
          <!-- Tarjeta de presentación con la info registrada -->
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
            <a class="btn btn-outline-secondary btn-sm" href="/sistema_web/componentes/sesion/cerrarSesion.php">
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
</div>

<!-- Modal SIEMPRE visible: si no hay registro -> formulario; si hay registro -> modal info elegante -->
<?php if (!$hasRecord): ?>
<!-- ===== Modal de REGISTRO (2 columnas, responsive) ===== -->
<div class="modal fade" id="registroContactoModal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header" style="background:linear-gradient(135deg,#0284c7,#0369a1); color:#fff;">
      <h6 class="modal-title mb-0"><i class="fas fa-address-card mr-1"></i> Registrar información de contacto</h6>
    </div>
    <form id="formContacto" method="post" action="/sistema_web/inicio/guardar_contacto.php" novalidate>
      <div class="modal-body">
        <div class="alert alert-info mb-3">
          <b>Obligatorio:</b> Registra <b>Nombres</b>, <b>Apellidos</b>, <b>Correo institucional</b> y <b>Teléfono</b>.
          Los datos de <i>asistente</i> son <u>opcionales</u>. <br>
          Nota: <b>Nombres</b> y <b>Apellidos</b> se guardarán en <u>MAYÚSCULA</u>.
        </div>

        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/sistema_web/direccion_rsu/inicio.php') ?>">

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
        <a href="/sistema_web/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
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
      <a href="/sistema_web/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
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
    <form id="formContacto" method="post" action="/sistema_web/inicio/guardar_contacto.php" novalidate>
      <div class="modal-body">
        <input type="hidden" name="redirect" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '/sistema_web/direccion_rsu/inicio.php') ?>">
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
        <a href="/sistema_web/componentes/sesion/cerrarSesion.php" class="btn btn-outline-secondary btn-sm">
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
  <!-- Card 1 -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-primary text-white">
        <strong><i class="fas fa-rocket"></i> Panel rápido</strong>
      </div>
      <div class="card-body">
        <div class="text-muted small">Contenido próximo…</div>
      </div>
    </div>
  </div>

  <!-- Card 2 -->
  <div class="col-md-6 home-col">
    <div class="card home-card">
      <div class="card-header bg-success text-white">
        <strong><i class="fas fa-clipboard-check"></i> Resumen de evaluación</strong>
      </div>
      <div class="card-body">
        <div class="text-muted small">Contenido próximo…</div>
      </div>
    </div>
  </div>
</div>

<!-- ===== Row 2 ===== -->
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
        <?php if ($soyAdminDirsu): ?>
          <?php include __DIR__ . '/card_4_control_directorio.php'; ?>
        <?php else: ?>
          <div class="alert alert-light border small mb-2">
            <i class="fas fa-info-circle"></i>
            Esta sección es visible solo para usuarios con rol <b>1</b>.
          </div>
          <div class="text-muted small">Si necesitas gestionar el directorio, contacta a la Dirección RSU.</div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


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

  // Mostrar SIEMPRE un modal al cargar (registro si no hay record, info si sí hay record)
  (function showModalWhenReady(){
    if (window.$ && $('#registroContactoModal').length && $('#registroContactoModal').modal) {
      <?php if (!$hasRecord): ?>
        $('#registroContactoModal').modal('show');
      <?php else: ?>
        $('#infoContactoModal').modal('show');
      <?php endif; ?>
    } else { setTimeout(showModalWhenReady,150); }
  })();
});
</script>
