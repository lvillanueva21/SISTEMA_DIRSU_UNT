<div class="container-fluid p-0 wow fadeIn" data-wow-delay="0.1s">
  <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php
      $q = $mysqli->query("SELECT * FROM cw_carrusel WHERE visible=1 ORDER BY orden");
      $first = true;
      while($r = $q->fetch_assoc()):
      ?>
        <div class="carousel-item <?= $first?'active':'' ?>">
          <div class="carousel-image-crop">
            <img src="<?= htmlspecialchars($r['imagen']) ?>" alt="Slide">
          </div>
          <div class="carousel-caption">
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-lg-12">
                  <?php if($r['mostrar_titulo']): ?>
                    <h5 class="display-1 text-white mb-3 animated slideInDown"><?= htmlspecialchars($r['titulo']) ?></h5>
                  <?php endif; ?>
                  <?php if($r['mostrar_subtitulo']): ?>
                    <p class="lead text-white mb-4 animated fadeInUp"><?= htmlspecialchars($r['subtitulo']) ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php $first=false; endwhile; ?>
    </div>
    <button class="carousel-control-prev" type="button" data-bs-target="#header-carousel" data-bs-slide="prev">
      <span class="carousel-control-prev-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Previous</span>
    </button>
    <button class="carousel-control-next" type="button" data-bs-target="#header-carousel" data-bs-slide="next">
      <span class="carousel-control-next-icon" aria-hidden="true"></span>
      <span class="visually-hidden">Next</span>
    </button>
  </div>
</div>
