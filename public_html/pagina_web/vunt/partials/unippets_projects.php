<!-- Projects Start -->
<div class="container-xxl py-5">
  <div class="container">
    <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 500px;">
      <h2 class="display-6 mb-5">Nuestras actividades</h2>
    </div>

    <div class="row wow fadeInUp" data-wow-delay="0.3s">
      <div class="col-12 text-center">
        <ul class="list-inline rounded mb-5" id="portfolio-flters">
          <li class="mx-2 active" data-filter="*">Todas</li>
          <li class="mx-2" data-filter=".first">Últimas</li>
          <li class="mx-2" data-filter=".second">Próximas</li>
        </ul>
      </div>
    </div>

    <div class="row g-4 portfolio-container">
      <div class="col-lg-4 col-md-6 portfolio-item first wow fadeInUp" data-wow-delay="0.1s">
        <div class="portfolio-inner rounded">
          <img class="img-fluid" src="<?= asset('img/up1.png') ?>" alt="">
          <div class="portfolio-text">
            <h4 class="text-white mb-4">Actividad 1</h4>
            <div class="d-flex">
              <a class="btn btn-lg-square rounded-circle mx-2" href="<?= asset('img/up1.png') ?>" data-lightbox="portfolio"><i class="fa fa-eye"></i></a>
              <a class="btn btn-lg-square rounded-circle mx-2" href="#"><i class="fa fa-link"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6 portfolio-item second wow fadeInUp" data-wow-delay="0.3s">
        <div class="portfolio-inner rounded">
          <img class="img-fluid" src="<?= asset('img/up2.png') ?>" alt="">
          <div class="portfolio-text">
            <h4 class="text-white mb-4">Actividad 2</h4>
            <div class="d-flex">
              <a class="btn btn-lg-square rounded-circle mx-2" href="<?= asset('img/up2.png') ?>" data-lightbox="portfolio"><i class="fa fa-eye"></i></a>
              <a class="btn btn-lg-square rounded-circle mx-2" href="#"><i class="fa fa-link"></i></a>
            </div>
          </div>
        </div>
      </div>

      <div class="col-lg-4 col-md-6 portfolio-item first wow fadeInUp" data-wow-delay="0.5s">
        <div class="portfolio-inner rounded">
          <img class="img-fluid" src="<?= asset('img/up3.png') ?>" alt="">
          <div class="portfolio-text">
            <h4 class="text-white mb-4">En adopción responsable</h4>
            <div class="d-flex">
              <a class="btn btn-lg-square rounded-circle mx-2" href="<?= asset('img/up3.png') ?>" data-lightbox="portfolio"><i class="fa fa-eye"></i></a>
              <a class="btn btn-lg-square rounded-circle mx-2" href="#"><i class="fa fa-link"></i></a>
            </div>
          </div>
        </div>
      </div>

      <!-- Agrega los demás items: up4.png, up5.png, up6.png ... -->
      <div class="col-lg-4 col-md-6 portfolio-item second wow fadeInUp" data-wow-delay="0.1s">
        <div class="portfolio-inner rounded">
          <img class="img-fluid" src="<?= asset('img/up4.png') ?>" alt="">
          <div class="portfolio-text">
            <h4 class="text-white mb-4">Actividad 4</h4>
            <div class="d-flex">
              <a class="btn btn-lg-square rounded-circle mx-2" href="<?= asset('img/up4.png') ?>" data-lightbox="portfolio"><i class="fa fa-eye"></i></a>
              <a class="btn btn-lg-square rounded-circle mx-2" href="#"><i class="fa fa-link"></i></a>
            </div>
          </div>
        </div>
      </div>

      <!-- ... más cards según necesites -->
    </div>
  </div>
</div>
<!-- Projects End -->
