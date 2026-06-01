<?php
$capConfig = require __DIR__ . '/capacitaciones_videos_config.php';
$capTitulo = isset($capConfig['titulo']) ? (string)$capConfig['titulo'] : 'Clic para ver capacitaciones en video:';
$capGrupos = isset($capConfig['grupos']) && is_array($capConfig['grupos']) ? $capConfig['grupos'] : [];
?>
<style>
  .home-video-toolbar{
    background:linear-gradient(180deg,#ffffff,#f8fafc);
    border:1px solid #e5e7eb;
    border-radius:.5rem;
    padding:.5rem .65rem;
    margin-bottom:.75rem;
    box-shadow:0 6px 14px rgba(0,0,0,.04);
  }
  .home-video-title{
    display:block;
    font-size:.9rem;
    color:#334155;
    font-weight:600;
    margin-bottom:.45rem;
  }
  .home-video-groups{
    display:flex;
    flex-wrap:wrap;
    margin:0 -.4rem;
  }
  .home-video-group{
    width:50%;
    padding:0 .4rem .35rem;
  }
  .home-video-group label{
    font-size:.82rem;
    color:#64748b;
    margin:0 0 .25rem 0;
    font-weight:600;
  }
  .home-video-group .form-control{
    height:calc(1.9rem + 2px);
    font-size:.86rem;
  }
  @media (max-width: 767.98px){
    .home-video-group{ width:100%; }
  }
</style>

<div class="home-video-toolbar">
  <span class="home-video-title">
    <i class="fas fa-video mr-1"></i>
    <?= htmlspecialchars($capTitulo, ENT_QUOTES, 'UTF-8') ?>
  </span>

  <div class="home-video-groups">
    <?php foreach ($capGrupos as $idx => $grupo):
      $nombre = isset($grupo['nombre']) ? (string)$grupo['nombre'] : ('Grupo ' . ($idx + 1));
      $opciones = isset($grupo['opciones']) && is_array($grupo['opciones']) ? $grupo['opciones'] : [];
      $selectId = 'capacitacionSelect_' . $idx;
    ?>
      <div class="home-video-group">
        <label for="<?= htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></label>
        <select
          id="<?= htmlspecialchars($selectId, ENT_QUOTES, 'UTF-8') ?>"
          class="form-control form-control-sm js-capacitacion-video-select"
          data-group-name="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>"
        >
          <option value="">Selecciona una capacitación...</option>
          <?php foreach ($opciones as $opc):
            $label = isset($opc['label']) ? (string)$opc['label'] : '';
            $url = isset($opc['url']) ? (string)$opc['url'] : '';
            if ($label === '' || $url === '') {
              continue;
            }
          ?>
            <option value="<?= htmlspecialchars($url, ENT_QUOTES, 'UTF-8') ?>">
              <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<script>
(function () {
  function showBsModal(element) {
    if (window.jQuery && typeof jQuery.fn.modal === 'function') {
      jQuery(element).modal('show');
    } else if (window.bootstrap && window.bootstrap.Modal) {
      var modal = new window.bootstrap.Modal(element);
      modal.show();
    } else {
      element.style.display = 'block';
    }
  }

  function openVideoModal(videoUrl, titleText) {
    var old = document.getElementById('ytModalHomeCap');
    if (old && old.parentNode) old.parentNode.removeChild(old);

    var safeTitle = titleText || 'Capacitación en video';
    var modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.id = 'ytModalHomeCap';
    modal.setAttribute('tabindex', '-1');
    modal.setAttribute('aria-hidden', 'true');

    modal.innerHTML =
      '<div class="modal-dialog modal-xl modal-dialog-centered">' +
        '<div class="modal-content">' +
          '<div class="modal-header py-2">' +
            '<h6 class="modal-title mb-0">' + safeTitle.replace(/</g, '&lt;').replace(/>/g, '&gt;') + '</h6>' +
            '<button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">' +
              '<span aria-hidden="true">&times;</span>' +
            '</button>' +
          '</div>' +
          '<div class="modal-body p-0">' +
            '<div style="position:relative;width:100%;height:0;padding-bottom:56.25%;background:#000;">' +
              '<iframe id="ytFrameHomeCap" src="' + videoUrl + '" style="position:absolute;top:0;left:0;width:100%;height:100%;border:0;" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe>' +
            '</div>' +
          '</div>' +
          '<div class="modal-footer py-2">' +
            '<button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>' +
          '</div>' +
        '</div>' +
      '</div>';

    document.body.appendChild(modal);

    var teardown = function () {
      try {
        var frame = modal.querySelector('#ytFrameHomeCap');
        if (frame) frame.src = '';
      } catch (e) {}
      if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
    };

    if (window.jQuery) {
      jQuery(modal).on('hidden.bs.modal', teardown);
    }
    modal.addEventListener('hidden.bs.modal', teardown);

    showBsModal(modal);
  }

  document.addEventListener('change', function (ev) {
    var select = ev.target.closest('.js-capacitacion-video-select');
    if (!select) return;

    var url = (select.value || '').trim();
    if (!url) return;

    var option = select.options[select.selectedIndex];
    var titleText = option ? option.text : 'Capacitación en video';
    openVideoModal(url, titleText);

    select.selectedIndex = 0;
  });
})();
</script>
