<div class="container-fluid quote my-5 py-5" data-parallax="scroll" data-image-src="img/Pampa-Galeras-Barbara-DAchille_webdirsu.png">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <?php $convocatoria_activa = false; $disabled_attr = $convocatoria_activa ? '' : 'disabled'; ?>
        <div class="bg-white rounded p-4 p-sm-5 wow fadeIn convocatoria-card position-relative" data-wow-delay="0.5s">
          <?php if (!$convocatoria_activa): ?>
            <div class="convocatoria-overlay"><i class="bi bi-exclamation-circle"></i><span>Por el momento no tenemos convocatoria activas.</span></div>
          <?php endif; ?>

          <h1 class="display-5 text-center mb-5">Registrate en la convocatoria</h1>
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?= $disabled_attr ?> type="text" class="form-control bg-light border-0" id="gname" placeholder="Tu nombre" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>">
                <label for="gname">Tu nombre</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?= $disabled_attr ?> type="email" class="form-control bg-light border-0" id="gmail" placeholder="Tu correo" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>">
                <label for="gmail">Tu correo</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?= $disabled_attr ?> type="text" class="form-control bg-light border-0" id="cname" placeholder="Tu celular" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>">
                <label for="cname">Tu celular</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?= $disabled_attr ?> type="text" class="form-control bg-light border-0" id="cage" placeholder="Tu escuela" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>">
                <label for="cage">Tu escuela</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <textarea <?= $disabled_attr ?> class="form-control bg-light border-0" placeholder="Tu ciclo" id="message" style="height: 100px" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>"></textarea>
                <label for="message">Tu ciclo</label>
              </div>
            </div>
            <div class="col-12 text-center">
              <button <?= $disabled_attr ?> class="btn btn-primary py-3 px-4" type="submit" aria-disabled="<?= $convocatoria_activa?'false':'true' ?>">Quiero unirme</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
