<?php $U = usuario_actual(); ?>
<style>
.pila-fab{position:fixed;right:16px;bottom:16px;z-index:1050;display:flex;flex-direction:column;gap:.5rem;align-items:flex-end}
.btn-fab{display:inline-flex;align-items:center;gap:.6rem;border-radius:999px;padding:.6rem 1rem;font-weight:600;box-shadow:0 6px 16px rgba(0,0,0,.18)}
.fab-login{background:#189c2f;color:#fff}
.fab-panel{background:#28a5ea;color:#fff}
.fab-salir{background:#000;color:#fff}
.fab-etiqueta{background:#5bb3a7;color:#fff;font-size:.95rem}
.btn-fab img{width:22px;height:22px;border-radius:50%}
</style>

<div class="pila-fab">
  <?php if(!$U): ?>
    <button class="btn-fab fab-login" data-bs-toggle="modal" data-bs-target="#dlgLogin">Iniciar Sesión</button>
  <?php else: ?>
    <div class="btn-fab fab-etiqueta">Bienvenid@: <?= htmlspecialchars($U['nombres']) ?> - Rol: <?= htmlspecialchars(ucfirst($U['rol'])) ?></div>
    <a class="btn-fab fab-panel" href="<?= url('pagina_web/panel.php') ?>">Panel de Control</a>
    <a class="btn-fab fab-salir" href="<?= url('cerrar_sesion.php') ?>">Cerrar Sesión</a>
  <?php endif; ?>
</div>

<!-- Modal de login -->
<div class="modal fade" id="dlgLogin" tabindex="-1" aria-hidden="true"><div class="modal-dialog">
  <div class="modal-content">
    <form id="formLogin">
      <div class="modal-header">
        <h5 class="modal-title">Iniciar sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input name="usuario" class="form-control" required>
        </div>
        <div class="mb-2">
          <label class="form-label">Contraseña</label>
          <input type="password" name="clave" class="form-control" required>
        </div>
        <div id="loginMsg" class="text-danger small"></div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-primary" type="submit">Entrar</button>
      </div>
    </form>
  </div>
</div></div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  var f = document.getElementById('formLogin');
  if (!f) return;
  f.addEventListener('submit', function(e){
    e.preventDefault();
    var datos = new FormData(f);
    fetch('<?= url("autenticacion_login.php") ?>', {method:'POST', body:datos})
      .then(r => r.json())
      .then(j => { if(j.status==='ok'){ location.reload(); } else { document.getElementById('loginMsg').textContent = j.msg || 'Error'; } })
      .catch(()=>{ document.getElementById('loginMsg').textContent = 'Error de red'; });
  });
});
</script>
