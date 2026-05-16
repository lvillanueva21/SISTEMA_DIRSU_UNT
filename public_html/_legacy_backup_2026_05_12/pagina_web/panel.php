<?php
require_once __DIR__.'/../inc/app_boot.php';
asegurar_login();

$page_title = 'Panel de Control';
include APP_ROOT.'/inc/head.php';
include APP_ROOT.'/inc/topbar.php';
include APP_ROOT.'/inc/navbar.php';

$u = usuario_actual();
?>
<!-- Header -->
<div class="container-fluid page-header py-5 mb-5 wow fadeIn" data-wow-delay="0.1s">
  <div class="container text-center py-5">
    <h1 class="display-3 text-white mb-4 animated slideInDown">Panel de Control</h1>
    <nav aria-label="breadcrumb animated slideInDown">
      <ol class="breadcrumb justify-content-center mb-0">
        <li class="breadcrumb-item active" aria-current="page">
          Bienvenido, <?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?> (<?= htmlspecialchars($u['rol']) ?>)
        </li>
      </ol>
    </nav>
  </div>
</div>

<!-- Cuerpo (vacío por ahora) -->
<div class="container-xxl py-5">
  <div class="container">
    <div class="alert alert-info">
      Aquí irá tu interfaz del panel (cards, accesos, etc.).
    </div>
  </div>
</div>

<?php
include APP_ROOT.'/inc/footer.php';
include APP_ROOT.'/inc/scripts.php';
