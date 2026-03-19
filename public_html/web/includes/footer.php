<!-- Footer Start -->
<div class="container-fluid bg-dark text-light footer mt-5 py-5 wow fadeIn" data-wow-delay="0.1s">
  <div class="container py-5">
    <div class="row g-5">
      <div class="col-lg-4 col-md-6">
        <h4 class="text-white mb-4">Nuestra oficina</h4>
        <p class="mb-2"><i class="fa fa-map-marker-alt me-3"></i>344 Jirón Diego De Almagro, Trujillo, La Libertad</p>
        <br>
        <h4 class="text-white mb-4">Nuestro horario de atención</h4>
        <p class="mb-2"><i class="fa fa-clock me-3"></i>Lunes a viernes de 8:00 am a 2:45 pm</p>
        <br>
        <h4 class="text-white mb-4">Nuestro correo institucional</h4>
        <p class="mb-2"><i class="fa fa-envelope me-3"></i>dirsu@unitru.edu.pe</p>
        <h4 class="text-white mb-4">Nuestro correo del área de proyectos</h4>
        <p class="mb-2"><i class="fa fa-envelope me-3"></i>proyectosdirsu@unitru.edu.pe</p>
        <div class="d-flex pt-2">
          <a class="btn btn-square btn-outline-light rounded-circle me-2" href="https://www.facebook.com/RSUUNT?locale=es_LA" target="_blank"><i class="fab fa-facebook-f"></i></a>
          <a class="btn btn-square btn-outline-light rounded-circle me-2" href="https://www.instagram.com/dirsuunt/" target="_blank"><i class="fab fa-instagram"></i></a>
          <a class="btn btn-square btn-outline-light rounded-circle me-2" href="#"><i class="fab fa-youtube"></i></a>
          <a class="btn btn-square btn-outline-light rounded-circle me-2" href="#"><i class="fab fa-linkedin-in"></i></a>
        </div>
      </div>
      <div class="col-lg-3 col-md-6 wow fadeInUp" data-wow-delay="0.5s">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d987.4755489319359!2d-79.03057314451524!3d-8.111440667282164!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x91ad3d16fb65b62d%3A0x8ad9b3e4d0b5c897!2sLocal%20Central%20Universidad%20Nacional%20de%20Trujillo!5e0!3m2!1ses!2spe!4v1720397323401!5m2!1ses!2spe" width="700" height="360" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
      </div>
    </div>
  </div>
</div>
<!-- Footer End -->

<!-- Copyright Start -->
<div class="container-fluid copyright py-4">
  <div class="container">
    <div class="row">
      <div class="col-md-6 text-center text-md-start mb-3 mb-md-0">
        &copy; <a class="border-bottom" href="#">2025 DIRSU – Universidad Nacional de Trujillo. Todos los derechos reservados.</a>
      </div>
      <div class="col-md-6 text-center text-md-end">Diseñado por el <a class="border-bottom" href="#">Área Informática - DIRSU</a></div>
    </div>
  </div>
</div>
<!-- Copyright End -->
<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';

$user = auth_user();
$openLogin = (isset($_GET['login']) && $_GET['login'] === '1');
$loginErr = flash_get('login_error');
$currentUrl = $_SERVER['REQUEST_URI'] ?? 'index.php?p=inicio';
?>

<style>
.floating-auth-wrap{
  position: fixed;
  right: 18px;
  bottom: 18px;
  z-index: 9999;
  display: flex;
  flex-direction: column;
  gap: 10px;
}
.floating-auth-wrap .btn{
  border-radius: 999px;
  padding: 10px 14px;
  box-shadow: 0 6px 18px rgba(0,0,0,.15);
}
</style>

<div class="floating-auth-wrap">
  <?php if (!$user): ?>
    <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#authLoginModal">
      Iniciar sesión
    </button>
  <?php else: ?>
    <div class="btn btn-light text-dark">
      Bienvenido <?= htmlspecialchars($user['nombres'], ENT_QUOTES, 'UTF-8') ?>
    </div>
    <a class="btn btn-danger"
       href="logout.php?redirect=<?= urlencode($currentUrl) ?>">
      Cerrar sesión
    </a>
  <?php endif; ?>
</div>

<!-- Modal Login -->
<div class="modal fade" id="authLoginModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Iniciar sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <?php if ($loginErr): ?>
          <div class="alert alert-danger"><?= htmlspecialchars($loginErr, ENT_QUOTES, 'UTF-8') ?></div>
        <?php endif; ?>

        <form method="post" action="login.php" autocomplete="off">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="redirect" value="<?= htmlspecialchars($currentUrl, ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">DNI</label>
            <input class="form-control" name="dni" inputmode="numeric" pattern="\d{8}" maxlength="8" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Contraseña</label>
            <input class="form-control" type="password" name="password" required>
          </div>

          <button class="btn btn-primary w-100" type="submit">Entrar</button>
        </form>
      </div>
    </div>
  </div>
</div>

<!-- Back to Top -->
<a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>

<!-- JavaScript Libraries -->
<script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="lib/wow/wow.min.js"></script>
<script src="lib/easing/easing.min.js"></script>
<script src="lib/waypoints/waypoints.min.js"></script>
<script src="lib/owlcarousel/owl.carousel.min.js"></script>
<script src="lib/counterup/counterup.min.js"></script>
<script src="lib/parallax/parallax.min.js"></script>
<script src="lib/isotope/isotope.pkgd.min.js"></script>
<script src="lib/lightbox/js/lightbox.min.js"></script>

<!-- Template Javascript -->
<script src="js/main.js"></script>
</body>
</html>
<script>
document.addEventListener('DOMContentLoaded', function () {
  var shouldOpen = <?= ($openLogin || !empty($loginErr)) ? 'true' : 'false' ?>;
  if (shouldOpen) {
    var el = document.getElementById('authLoginModal');
    if (el && window.bootstrap) {
      new bootstrap.Modal(el).show();
    }
  }
});
</script>
