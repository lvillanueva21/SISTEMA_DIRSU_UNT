<?php
require __DIR__.'/../../inc/app_boot.php';
$page_title = 'DIRSU - CDN';
include APP_ROOT.'/inc/head.php';
?>

<?php include APP_ROOT.'/inc/topbar.php'; ?>
<?php include APP_ROOT.'/inc/navbar.php'; ?>

<!-- Page Header Start -->
<div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
  <div class="container text-center py-5">
    <h1 class="display-3 text-white mb-4 animated slideInDown">CDN - Programa de Ciudadanía</h1>
    <nav aria-label="breadcrumb animated slideInDown">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item active" aria-current="page">
          Conoce más sobre nuestro programa de Ciudadanía del Voluntariado de la Universidad Nacional de Trujillo.
        </li>
      </ol>
    </nav>
  </div>
</div>
<!-- Page Header End -->

<?php include APP_ROOT.'/pagina_web/vunt/partials/cdn_about.php'; ?>
<?php include APP_ROOT.'/pagina_web/vunt/partials/cdn_facts.php'; ?>
<?php include APP_ROOT.'/pagina_web/vunt/partials/cdn_team.php'; ?>

<?php include APP_ROOT.'/inc/footer.php'; ?>
<?php include APP_ROOT.'/inc/scripts.php'; ?>
