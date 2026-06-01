<?php if (!isset($mysqli)) require_once __DIR__.'/app_boot.php'; ?>
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

<?php include APP_ROOT.'/inc/float_sesion.php'; ?>

<a href="#" class="btn btn-lg btn-primary btn-lg-square rounded-circle back-to-top"><i class="bi bi-arrow-up"></i></a>
