<?php
// registro.php (solo prueba) - UI sin recarga, usa AJAX a acciones/registro_usuario.php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/conexion.php';
require_once __DIR__ . '/includes/csrf.php';

$pageTitle  = 'Registro (prueba)';
$activePage = '';

// Cargar roles para el select
$roles = array();
$rs = db()->query("SELECT id, codigo, nombre FROM l2601_roles ORDER BY id ASC");
while ($r = $rs->fetch_assoc()) { $roles[] = $r; }
$rs->free();

require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/navbar.php';
?>

<div class="container-xxl py-5">
  <div class="container" style="max-width: 760px;">
    <h1 class="mb-3">Registro (prueba)</h1>
    <p class="text-muted mb-4">Crea usuarios iniciales. Luego borra este archivo.</p>

    <div id="alertBox" class="alert" style="display:none;"></div>

    <form id="registroForm" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?php echo htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8'); ?>">

      <div class="row g-3">
        <div class="col-md-6">
          <label class="form-label">Nombres</label>
          <input class="form-control" name="nombres" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Apellidos</label>
          <input class="form-control" name="apellidos" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">DNI (usuario)</label>
          <input class="form-control" name="dni" inputmode="numeric" pattern="\d{8}" maxlength="8" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Contraseña</label>
          <input class="form-control" type="password" name="password" required>
        </div>

        <div class="col-md-6">
          <label class="form-label">Rol</label>
          <select class="form-select" name="rol_id" required>
            <option value="">-- Selecciona --</option>
            <?php foreach ($roles as $rol): ?>
              <option value="<?php echo (int)$rol['id']; ?>">
                <?php echo htmlspecialchars($rol['nombre'], ENT_QUOTES, 'UTF-8'); ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="col-md-6">
          <label class="form-label">Foto de perfil (opcional)</label>
          <input class="form-control" type="file" name="foto_perfil" id="foto_perfil" accept="image/*">
          <div class="mt-2">
            <img id="foto_preview" alt="Vista previa" style="display:none;max-width:180px;border-radius:12px;">
          </div>
        </div>

        <div class="col-12 d-flex gap-2">
          <button id="btnGuardar" class="btn btn-primary" type="submit">Crear usuario</button>
          <a class="btn btn-outline-secondary" href="index.php?p=inicio">Volver</a>
        </div>
      </div>
    </form>
  </div>
</div>

<script>
// Vista previa de foto (sin recargar)
(function () {
  var input = document.getElementById('foto_perfil');
  var img = document.getElementById('foto_preview');
  if (!input || !img) return;

  input.addEventListener('change', function () {
    var file = (this.files && this.files[0]) ? this.files[0] : null;
    if (!file) { img.style.display = 'none'; img.src = ''; return; }
    if (!file.type || file.type.indexOf('image/') !== 0) { img.style.display = 'none'; img.src = ''; return; }

    var reader = new FileReader();
    reader.onload = function (e) {
      img.src = e.target.result;
      img.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });
})();

// Registro por AJAX (sin recargar)
(function () {
  var form = document.getElementById('registroForm');
  var alertBox = document.getElementById('alertBox');
  var btn = document.getElementById('btnGuardar');

  function showAlert(type, msg) {
    alertBox.className = 'alert alert-' + type;
    alertBox.textContent = msg;
    alertBox.style.display = 'block';
  }

  function hideAlert() {
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  if (!form) return;

  form.addEventListener('submit', function (ev) {
    ev.preventDefault();
    hideAlert();

    btn.disabled = true;
    btn.textContent = 'Guardando...';

    var fd = new FormData(form);

fetch('acciones/registro_usuario.php', {
  method: 'POST',
  body: fd
})
.then(function (r) {
  // Leer texto primero para poder mostrar errores aunque no sea JSON
  return r.text().then(function (t) {
    return { ok: r.ok, status: r.status, text: t };
  });
})
.then(function (pack) {
  var data = null;
  try { data = JSON.parse(pack.text); } catch (e) { data = null; }

  if (!pack.ok) {
    // Si el server devolvió JSON con msg, úsalo; si no, muestra fragmento
    var msg = (data && data.msg) ? data.msg : ('HTTP ' + pack.status + ' - ' + pack.text.substring(0, 200));
    showAlert('danger', msg);
    return;
  }

  if (data && data.ok) {
    showAlert('success', data.msg || 'Usuario creado.');
    form.reset();
    var img = document.getElementById('foto_preview');
    if (img) { img.style.display = 'none'; img.src = ''; }
  } else {
    showAlert('danger', (data && data.msg) ? data.msg : 'Error al registrar.');
  }
})
.catch(function () {
  showAlert('danger', 'No se pudo conectar con acciones/registro_usuario.php');
})
.finally(function () {
  btn.disabled = false;
  btn.textContent = 'Crear usuario';
});

  });
})();
</script>

<?php require __DIR__ . '/includes/footer.php'; ?>
