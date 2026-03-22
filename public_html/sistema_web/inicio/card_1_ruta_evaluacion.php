<?php
// sistema_web/inicio/card_1_ruta_evaluacion.php
// Solo el contenido del cuerpo del card (la imagen). El header lo arma index.php.
$basePath = isset($appBasePath) && $appBasePath !== '' ? $appBasePath : '/sistema_web';
?>
<div class="image-wrapper">
  <img
    src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/ruta_informe_semestral2025.jpg"
    alt="Ruta de evaluación del informe semestral 2025-I"
    class="img-thumb"
    loading="lazy"
    data-full-src="<?= htmlspecialchars($basePath, ENT_QUOTES, 'UTF-8') ?>/imagenes/temporal/ruta_informe_semestral2025.jpg"
  />
</div>
