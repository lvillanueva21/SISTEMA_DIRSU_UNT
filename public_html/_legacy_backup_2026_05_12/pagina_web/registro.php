<?php
require_once __DIR__.'/../inc/app_boot.php';

// Procesar POST
$msg = '';
$ok  = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $usuario   = trim($_POST['usuario'] ?? '');
  $clave     = (string)($_POST['clave'] ?? '');
  $nombres   = trim($_POST['nombres'] ?? '');
  $apellidos = trim($_POST['apellidos'] ?? '');
  $correo    = trim($_POST['correo'] ?? '');
  $rol       = $_POST['rol'] ?? 'editor';

  if ($usuario==='' || $clave==='' || $nombres==='' || $apellidos==='' || !in_array($rol, ['administrador','editor'], true)) {
    $msg = 'Todos los campos (excepto correo y foto) son obligatorios.';
  } else {
    // ¿usuario existe?
    $st = $mysqli->prepare("SELECT id FROM pc_usuarios WHERE usuario=? LIMIT 1");
    $st->bind_param('s',$usuario);
    $st->execute();
    $st->store_result();
    if ($st->num_rows > 0) {
      $msg = 'El usuario ya existe.';
    } else {
      $fotoRel = guardar_subida('perfil', 'foto_perfil'); // puede ser null
      $hash = password_hash($clave, PASSWORD_DEFAULT);

      $st2 = $mysqli->prepare("INSERT INTO pc_usuarios (usuario, clave_hash, nombres, apellidos, correo, rol, foto_perfil, activo) VALUES (?,?,?,?,?,?,?,1)");
      $st2->bind_param('sssssss', $usuario, $hash, $nombres, $apellidos, $correo, $rol, $fotoRel);
      if ($st2->execute()) {
        $ok = true;
        $msg = 'Usuario creado correctamente.';
      } else {
        $msg = 'Error al crear usuario: '.$mysqli->error;
      }
      $st2->close();
    }
    $st->close();
  }
}

$page_title = 'Registro temporal de usuarios';
include APP_ROOT.'/inc/head.php';
include APP_ROOT.'/inc/topbar.php';
include APP_ROOT.'/inc/navbar.php';
?>
<div class="container-xxl py-5">
  <div class="container">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">Registro de usuarios (temporal)</div>
          <div class="card-body">
            <?php if ($msg): ?>
              <div class="alert <?= $ok?'alert-success':'alert-danger' ?>"><?= htmlspecialchars($msg) ?></div>
            <?php endif; ?>

            <form method="post" enctype="multipart/form-data" autocomplete="off">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label">Usuario</label>
                  <input type="text" name="usuario" class="form-control" maxlength="60" required>
                </div>
                <div class="col-md-6 position-relative">
                  <label class="form-label">Contraseña</label>
                  <input type="password" name="clave" id="regClave" class="form-control" required>
                  <i class="bi bi-eye-slash toggle-pass" id="regToggle" title="Mostrar/Ocultar" style="position:absolute;right:10px;top:48px;cursor:pointer;"></i>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Nombres</label>
                  <input type="text" name="nombres" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Apellidos</label>
                  <input type="text" name="apellidos" class="form-control" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label">Correo (opcional)</label>
                  <input type="email" name="correo" class="form-control">
                </div>
                <div class="col-md-6">
                  <label class="form-label">Rol</label>
                  <select name="rol" class="form-select" required>
                    <option value="editor">editor</option>
                    <option value="administrador">administrador</option>
                  </select>
                </div>
                <div class="col-md-12">
                  <label class="form-label">Foto de perfil (opcional)</label>
                  <input type="file" name="foto_perfil" class="form-control" accept="image/*">
                </div>
              </div>
              <div class="mt-3">
                <button class="btn btn-primary">Crear usuario</button>
              </div>
            </form>

                    </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const t = document.getElementById('regToggle');
  const c = document.getElementById('regClave');
  if (t && c) {
    t.addEventListener('click', function(){
      const pass = c.type === 'password';
      c.type = pass ? 'text' : 'password';
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });
  }
})();
</script>

<?php
include APP_ROOT.'/inc/footer.php';
include APP_ROOT.'/inc/scripts.php';
  