<?php if (!isset($mysqli)) require_once __DIR__.'/app_boot.php';
$u = usuario_actual();
?>
<style>
  /* ── BOTONES FLOTANTES DE SESIÓN ───────────────────────── */
  .flot-sesion {
    position: fixed;
    right: 18px;
    bottom: 90px;
    z-index: 1055; /* sobre el contenido */
    display: flex;
    flex-direction: column;
    gap: .5rem;
    align-items: end;
  }
  .flot-sesion .etiqueta-usuario {
    background: rgba(0,0,0,.75);
    color: #fff;
    padding: .35rem .6rem;
    border-radius: .5rem;
    font-size: .85rem;
    text-align: right;
    max-width: 260px;
  }
  .flot-sesion .avatar {
    width: 34px; height: 34px; border-radius:50%;
    object-fit: cover; margin-left:.4rem; border:2px solid #fff;
    box-shadow: 0 0 0 2px rgba(0,0,0,.15);
  }
  .flot-sesion .btn-fab {
    display:inline-flex; align-items:center; gap:.4rem;
    border-radius: 999px; padding:.6rem .9rem;
    box-shadow: 0 8px 26px rgba(0,0,0,.18);
  }
  .flot-sesion .btn-fab i { font-size: 1rem; }
  @media (max-width: 576px){
    .flot-sesion { right: 12px; bottom: 80px; }
  }
  /* ── MODAL LOGIN ───────────────────────────────────────── */
  .toggle-pass {
    cursor:pointer;
    position:absolute; right: 10px; top: 50%;
    transform: translateY(-50%);
    color:#6c757d;
  }
</style>

<div class="flot-sesion">
  <?php if (!$u): ?>
    <button class="btn btn-primary btn-fab" data-bs-toggle="modal" data-bs-target="#modalLogin">
      <i class="bi bi-box-arrow-in-right"></i> Iniciar sesión
    </button>
  <?php else: ?>
    <div class="etiqueta-usuario">
      <div>
        <strong><?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?></strong><br>
        Rol: <?= htmlspecialchars($u['rol']) ?>
        <?php if (!empty($u['foto_perfil'])): ?>
          <img class="avatar" src="<?= asset($u['foto_perfil']) ?>" alt="avatar">
        <?php endif; ?>
      </div>
    </div>
    <a class="btn btn-secondary btn-fab" href="<?= url('pagina_web/panel.php') ?>">
      <i class="bi bi-speedometer2"></i> Panel de Control
    </a>
    <a class="btn btn-danger btn-fab" href="<?= url('pagina_web/logout.php') ?>">
      <i class="bi bi-box-arrow-right"></i> Cerrar sesión
    </a>
  <?php endif; ?>
</div>

<!-- MODAL LOGIN -->
<div class="modal fade" id="modalLogin" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content" id="frmLogin" autocomplete="off">
      <div class="modal-header">
        <h5 class="modal-title">Iniciar sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="loginError" class="alert alert-danger d-none"></div>

        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" name="usuario" class="form-control" maxlength="60" required>
        </div>

        <div class="mb-3 position-relative">
          <label class="form-label">Contraseña</label>
          <input type="password" name="clave" class="form-control" required id="campoClave">
          <i class="bi bi-eye-slash toggle-pass" id="toggleClave" title="Mostrar/Ocultar contraseña"></i>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Cancelar</button>
        <button class="btn btn-primary" type="submit" id="btnAcceder">
          <span class="spinner-border spinner-border-sm me-2 d-none" id="spAcceder"></span>
          Acceder
        </button>
      </div>
    </form>
  </div>
</div>

<script>
(function(){
  const $frm = document.getElementById('frmLogin');
  const $err = document.getElementById('loginError');
  const $btn = document.getElementById('btnAcceder');
  const $spn = document.getElementById('spAcceder');
  const $toggle = document.getElementById('toggleClave');
  const $clave = document.getElementById('campoClave');

  if ($toggle && $clave) {
    $toggle.addEventListener('click', function(){
      const isPass = $clave.type === 'password';
      $clave.type = isPass ? 'text' : 'password';
      this.classList.toggle('bi-eye');
      this.classList.toggle('bi-eye-slash');
    });
  }

  if ($frm) {
    $frm.addEventListener('submit', function(ev){
      ev.preventDefault();
      $err.classList.add('d-none'); $err.textContent='';
      $btn.disabled = true; $spn.classList.remove('d-none');

      const formData = new FormData($frm);
      fetch('<?= url('pagina_web/auth_login.php') ?>', {
        method: 'POST',
        body: formData,
        headers: {'X-Requested-With':'XMLHttpRequest'}
      })
      .then(r => r.json())
      .then(data => {
        if (data && data.status === 'ok') {
          // recargar para que se muestren los botones de sesión
          location.reload();
        } else {
          $err.textContent = (data && data.msg) ? data.msg : 'Credenciales inválidas';
          $err.classList.remove('d-none');
        }
      })
      .catch(() => {
        $err.textContent = 'Error de red. Intenta de nuevo.';
        $err.classList.remove('d-none');
      })
      .finally(() => {
        $btn.disabled = false; $spn.classList.add('d-none');
      });
    });
  }
})();
</script>
