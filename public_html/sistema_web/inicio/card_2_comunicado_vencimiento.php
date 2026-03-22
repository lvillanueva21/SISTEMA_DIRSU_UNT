<?php
// sistema_web/inicio/card_2_comunicado_vencimiento.php
// Solo el contenido del cuerpo del card (la imagen). El header lo arma index.php.
$basePath = isset($appBasePath) && $appBasePath !== '' ? $appBasePath : '/sistema_web';
?>
<div class="image-wrapper">
  <img
    src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/comunicado_vencimiento.jpeg"
    alt="Comunicado por el Vencimiento del Plazo de Informe Semestrales"
    class="img-thumb"
    loading="lazy"
    data-full-src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/comunicado_vencimiento.jpeg"
  />
</div>
