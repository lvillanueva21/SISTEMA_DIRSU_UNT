<?php
$activePage = $activePage ?? '';

$isActive = function (string $key) use ($activePage): string {
    return $activePage === $key ? ' active' : '';
};

$brandLogoUrl = '';
$brandLogoDir = dirname(__DIR__) . '/img/logo_sin_texto';
if (is_dir($brandLogoDir)) {
    $logos = glob($brandLogoDir . '/*.png');
    if (is_array($logos) && count($logos) > 0) {
        usort($logos, static function (string $a, string $b): int {
            return filemtime($b) <=> filemtime($a);
        });
        $brandLogoUrl = 'img/logo_sin_texto/' . basename($logos[0]);
    }
}
?>

<!-- Topbar Start -->
<div class="container-fluid topbar-dirsu text-white px-0 py-2">
  <div class="row gx-0 d-none d-lg-flex">
    <div class="col-lg-7 px-5 text-start">
      <div class="h-100 d-inline-flex align-items-center">
        <span class="far fa-envelope me-2"></span>
        <span>dirsu@unitru.edu.pe</span>
      </div>
    </div>
    <div class="col-lg-5 px-5 text-end">
      <div class="h-100 d-inline-flex align-items-center mx-n2">
        <span>Síguenos en redes sociales:</span>
        <a class="btn btn-link text-white" href="https://www.facebook.com/RSUUNT?locale=es_LA" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i></a>
        <a class="btn btn-link text-white" href="https://www.instagram.com/dirsuunt/" target="_blank" rel="noopener"><i class="fab fa-instagram"></i></a>
      </div>
    </div>
  </div>
</div>
<!-- Topbar End -->

<!-- Navbar Start -->
<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
    <div class="dirsu-brand-wrap ps-3 ps-lg-4 pe-2">
      <a href="index.php" class="navbar-brand d-flex align-items-center dirsu-brand">
          <?php if ($brandLogoUrl !== ''): ?>
              <span class="dirsu-brand-logo-wrap">
                  <img src="<?= htmlspecialchars($brandLogoUrl, ENT_QUOTES, 'UTF-8') ?>" alt="Logo DIRSU" class="dirsu-brand-logo">
              </span>
          <?php endif; ?>
          <span class="dirsu-brand-title">DIRSU</span>
      </a>
      <span class="dirsu-brand-sep" aria-hidden="true"></span>
      <span class="dirsu-brand-subtext" title="Dirección de Responsabilidad Social y Extensión Cultural Universitaria de la Universidad Nacional de Trujillo">
          Dirección de Responsabilidad Social y Extensión Cultural Universitaria de la Universidad Nacional de Trujillo
      </span>
    </div>

    <button type="button" class="navbar-toggler me-4" data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
        <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarCollapse">
        <div class="navbar-nav ms-auto p-4 p-lg-0">

<a href="index.php?p=inicio" class="nav-item nav-link<?= $isActive('home') ?>">Inicio</a>

<div class="nav-item dropdown">
  <a href="#" class="nav-link dropdown-toggle<?= $isActive('areas') ?>" data-bs-toggle="dropdown">Áreas</a>
  <div class="dropdown-menu bg-light m-0">
    <a href="index.php?p=areas_proyectos" class="dropdown-item">Proyectos</a>
    <a href="index.php?p=areas_ambiental" class="dropdown-item">Ambiental</a>
  </div>
</div>

<div class="nav-item dropdown">
  <a href="#" class="nav-link dropdown-toggle<?= $isActive('voluntariado') ?>" data-bs-toggle="dropdown">Voluntariado UNT</a>
  <div class="dropdown-menu bg-light m-0">
    <a href="index.php?p=vol_cdn" class="dropdown-item">CDN</a>
    <a href="index.php?p=vol_cvgen" class="dropdown-item">CVGEN</a>
    <a href="index.php?p=vol_grd" class="dropdown-item">GRD</a>
    <a href="index.php?p=vol_promam" class="dropdown-item">PROMAM</a>
    <a href="index.php?p=vol_sbc" class="dropdown-item">SBC</a>
    <a href="index.php?p=vol_unippets" class="dropdown-item">UNIPPETS</a>
  </div>
</div>

<div class="nav-item dropdown">
  <a href="#" class="nav-link dropdown-toggle<?= $isActive('cecunt') ?>" data-bs-toggle="dropdown">CECUNT</a>
  <div class="dropdown-menu bg-light m-0">
    <a href="index.php?p=cec_teatro" class="dropdown-item">Teatro Universitario</a>
    <a href="index.php?p=cec_orfeon" class="dropdown-item">Orfeón Universitario</a>
    <a href="index.php?p=cec_danza" class="dropdown-item">Grupo de Danza</a>
    <a href="index.php?p=cec_banda" class="dropdown-item">Banda de Música</a>
    <a href="index.php?p=cec_musica" class="dropdown-item">Grupo de Música</a>
  </div>
</div>

        </div>

        <a href="https://rsu.unitru.edu.pe/sistema_web/login.php"
           class="btn btn-primary py-4 px-lg-4 rounded-0 d-none d-lg-block"
           target="_blank" rel="noopener">
            Ir a Sistema DIRSU<i class="fa fa-arrow-right ms-3"></i>
        </a>
    </div>
</nav>
<!-- Navbar End -->
