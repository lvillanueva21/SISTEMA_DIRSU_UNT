<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/conexion.php';

if (!function_exists('home_resolve_media_path')) {
  function home_resolve_media_path(string $path): string {
    $path = trim($path);
    if ($path === '') return '';

    // Absolute URL or scheme-based URL.
    if (preg_match('~^(?:https?:)?//~i', $path) === 1) return $path;

    // Keep root-relative and data/blob URLs untouched.
    if (str_starts_with($path, '/') || str_starts_with($path, 'data:') || str_starts_with($path, 'blob:')) {
      return $path;
    }

    $normalized = ltrim(str_replace('\\', '/', $path), '/');

    // 1) Exists inside /web
    $webPath = dirname(__DIR__) . '/' . $normalized;
    if (is_file($webPath)) {
      return $normalized;
    }

    // 2) Exists at project root (/public_html)
    $rootPath = dirname(__DIR__, 2) . '/' . $normalized;
    if (is_file($rootPath)) {
      return '../' . $normalized;
    }

    return $normalized;
  }
}

$novedades = array();

if (!function_exists('home_page_label')) {
  function home_page_label(string $pageKey): string {
    $map = array(
      'areas_proyectos' => 'Áreas - Proyectos',
      'areas_ambiental' => 'Áreas - Ambiental',
      'vol_cdn' => 'Voluntariado - CDN',
      'vol_cvgen' => 'Voluntariado - CVGEN',
      'vol_grd' => 'Voluntariado - GRD',
      'vol_promam' => 'Voluntariado - PROMAM',
      'vol_sbc' => 'Voluntariado - SBC',
      'vol_unippets' => 'Voluntariado - UNIPPETS',
      'cec_teatro' => 'CECUNT - Teatro',
      'cec_orfeon' => 'CECUNT - Orfeón',
      'cec_danza' => 'CECUNT - Danza',
      'cec_banda' => 'CECUNT - Banda',
      'cec_musica' => 'CECUNT - Música',
    );
    return $map[$pageKey] ?? strtoupper(str_replace('_', ' ', $pageKey));
  }
}

if (!function_exists('home_fmt_date')) {
  function home_fmt_date(?string $date): string {
    if (!is_string($date) || trim($date) === '') return 'Fecha por definir';
    $ts = strtotime($date);
    if ($ts === false) return 'Fecha por definir';
    return date('d M, Y', $ts);
  }
}

try {
  $sql = "
    SELECT *
    FROM (
      SELECT
        'noticia' AS tipo,
        n.id,
        n.page_key,
        n.titulo,
        COALESCE(NULLIF(n.resumen, ''), NULLIF(n.cuerpo, '')) AS texto,
        n.imagen_portada AS imagen,
        n.creado_en AS fecha_orden,
        n.publicada_en AS fecha_mostrar
      FROM l2601_noticias n
      WHERE n.estado <> 'oculta'

      UNION ALL

      SELECT
        'evento' AS tipo,
        e.id,
        e.page_key,
        e.titulo,
        NULLIF(e.parrafo, '') AS texto,
        e.foto_evento AS imagen,
        e.creado_en AS fecha_orden,
        CASE
          WHEN e.inicio_fecha IS NOT NULL
          THEN TIMESTAMP(e.inicio_fecha, COALESCE(e.inicio_hora, '00:00:00'))
          ELSE NULL
        END AS fecha_mostrar
      FROM l2601_eventos e
      WHERE e.estado <> 'inactivo'
    ) z
    ORDER BY z.fecha_orden DESC
    LIMIT 7
  ";

  $rs = db()->query($sql);
  while ($row = $rs->fetch_assoc()) {
    $tipo = (string)($row['tipo'] ?? '');
    $id = (int)($row['id'] ?? 0);
    $pageKey = (string)($row['page_key'] ?? '');
    if ($id <= 0 || $pageKey === '') continue;

    $img = home_resolve_media_path((string)($row['imagen'] ?? ''));
    $titulo = trim((string)($row['titulo'] ?? ''));
    if ($titulo === '') $titulo = 'Novedad';

    $texto = trim((string)($row['texto'] ?? ''));
    if ($texto === '') $texto = 'Sin descripción por ahora.';
    if (mb_strlen($texto) > 180) $texto = mb_substr($texto, 0, 180) . '...';

    $fecha = home_fmt_date(isset($row['fecha_mostrar']) ? (string)$row['fecha_mostrar'] : null);
    $badge = ($tipo === 'evento') ? 'Evento' : 'Noticia';
    $badgeClass = ($tipo === 'evento') ? 'nov-badge-evento' : 'nov-badge-comunicado';
    $link = ($tipo === 'evento')
      ? ('index.php?p=' . rawurlencode($pageKey) . '&evt_id=' . $id . '#evtSection')
      : ('index.php?p=' . rawurlencode($pageKey) . '&news_id=' . $id . '#newsSection');

    $novedades[] = array(
      'tipo' => $tipo,
      'id' => $id,
      'page_key' => $pageKey,
      'page_label' => home_page_label($pageKey),
      'titulo' => $titulo,
      'texto' => $texto,
      'fecha' => $fecha,
      'badge' => $badge,
      'badge_class' => $badgeClass,
      'imagen' => $img,
      'link' => $link,
    );
  }
  $rs->free();
} catch (Throwable $e) {
  // Keep section fallback below.
}

$imgDirectora = home_resolve_media_path('img/directora_vertical.png');
$imgFactsBg = home_resolve_media_path('img/reserva-paracas_webdirsu.png');
$imgConvocatoriaBg = home_resolve_media_path('img/Pampa-Galeras-Barbara-DAchille_webdirsu.png');
$imgLourdes = home_resolve_media_path('img/lurdes.jpeg');
$imgYsmael = home_resolve_media_path('img/ysmael.jpeg');
$imgDefaultNovedad = home_resolve_media_path('img/carousel-1.jpg');
?>

<!-- Novedades Start -->
<div class="container-fluid p-0 wow fadeIn" data-wow-delay="0.1s">
  <div class="home-novedades-wrap" id="homeNovedadesWrap"
       data-api="modules/home_novedades_api.php"
       data-default-img="<?php echo htmlspecialchars($imgDefaultNovedad, ENT_QUOTES, 'UTF-8'); ?>">
    <?php if (count($novedades) > 0): ?>
      <?php $main = $novedades[0]; ?>
      <div class="home-novedades-grid">
        <a class="nov-main nov-card" href="<?php echo htmlspecialchars($main['link'], ENT_QUOTES, 'UTF-8'); ?>">
          <?php if ($main['imagen'] !== ''): ?>
            <img src="<?php echo htmlspecialchars($main['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="Novedad principal" loading="eager" fetchpriority="high">
          <?php else: ?>
            <div class="nov-ph"><i class="bi bi-newspaper"></i></div>
          <?php endif; ?>
          <div class="nov-overlay"></div>
          <div class="nov-body">
            <div class="nov-meta">
              <span class="nov-badge <?php echo htmlspecialchars((string)$main['badge_class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($main['badge'], ENT_QUOTES, 'UTF-8'); ?></span>
              <span><?php echo htmlspecialchars($main['fecha'], ENT_QUOTES, 'UTF-8'); ?></span>
            </div>
            <div class="nov-source"><?php echo htmlspecialchars($main['page_label'], ENT_QUOTES, 'UTF-8'); ?></div>
            <h3 class="nov-title"><?php echo htmlspecialchars($main['titulo'], ENT_QUOTES, 'UTF-8'); ?></h3>
            <p class="nov-text"><?php echo htmlspecialchars($main['texto'], ENT_QUOTES, 'UTF-8'); ?></p>
          </div>
        </a>

        <div class="nov-side">
          <?php for ($i = 1; $i <= 6; $i++): ?>
            <?php if (isset($novedades[$i])): $it = $novedades[$i]; ?>
              <a class="nov-mini nov-card" href="<?php echo htmlspecialchars($it['link'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php if ($it['imagen'] !== ''): ?>
                  <img src="<?php echo htmlspecialchars($it['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="Novedad" loading="lazy">
                <?php else: ?>
                  <div class="nov-ph"><i class="bi bi-megaphone"></i></div>
                <?php endif; ?>
                <div class="nov-overlay"></div>
                <div class="nov-body">
                  <div class="nov-meta">
                    <span class="nov-badge <?php echo htmlspecialchars((string)$it['badge_class'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($it['badge'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <span><?php echo htmlspecialchars($it['fecha'], ENT_QUOTES, 'UTF-8'); ?></span>
                  </div>
                  <div class="nov-source"><?php echo htmlspecialchars($it['page_label'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <h4 class="nov-title"><?php echo htmlspecialchars($it['titulo'], ENT_QUOTES, 'UTF-8'); ?></h4>
                </div>
              </a>
            <?php else: ?>
              <div class="nov-mini nov-empty">
                <div class="nov-empty-inner">
                  <i class="bi bi-hourglass-split"></i>
                  <span>Próximamente...</span>
                </div>
              </div>
            <?php endif; ?>
          <?php endfor; ?>
        </div>
      </div>
    <?php else: ?>
      <div class="nov-empty-all">
        <div class="nov-empty-all-inner">
          <i class="bi bi-newspaper"></i>
          <h3>Próximamente...</h3>
          <p>Próximamente publicaremos nuevas noticias y eventos.</p>
        </div>
      </div>
    <?php endif; ?>
  </div>
</div>
<!-- Novedades End -->
<script>
(function () {
  var wrap = document.getElementById('homeNovedadesWrap');
  if (!wrap) return;

  var api = wrap.getAttribute('data-api') || '';
  var fallbackImg = wrap.getAttribute('data-default-img') || 'img/carousel-1.jpg';
  if (!api) return;

  function esc(s) {
    return String(s || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/\"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function safeImg(src) {
    var v = String(src || '').trim();
    return v ? v : fallbackImg;
  }

  function render(items) {
    if (!Array.isArray(items) || items.length === 0) return;

    var main = items[0];
    var html = '';
    html += '<div class="home-novedades-grid">';
    html +=   '<a class="nov-main nov-card" href="' + esc(main.link || '#') + '">';
    html +=     '<img src="' + esc(safeImg(main.imagen)) + '" alt="Novedad principal" loading="eager" fetchpriority="high">';
    html +=     '<div class="nov-overlay"></div>';
    html +=     '<div class="nov-body">';
    html +=       '<div class="nov-meta"><span class="nov-badge ' + esc(main.badge_class || '') + '">' + esc(main.badge || '') + '</span><span>' + esc(main.fecha || 'Fecha por definir') + '</span></div>';
    html +=       '<div class="nov-source">' + esc(main.page_label || '') + '</div>';
    html +=       '<h3 class="nov-title">' + esc(main.titulo || 'Novedad') + '</h3>';
    html +=       '<p class="nov-text">' + esc(main.texto || 'Sin descripción por ahora.') + '</p>';
    html +=     '</div>';
    html +=   '</a>';
    html +=   '<div class="nov-side">';

    for (var i = 1; i <= 6; i++) {
      if (items[i]) {
        var it = items[i];
        html += '<a class="nov-mini nov-card" href="' + esc(it.link || '#') + '">';
        html +=   '<img src="' + esc(safeImg(it.imagen)) + '" alt="Novedad" loading="lazy">';
        html +=   '<div class="nov-overlay"></div>';
        html +=   '<div class="nov-body">';
        html +=     '<div class="nov-meta"><span class="nov-badge ' + esc(it.badge_class || '') + '">' + esc(it.badge || '') + '</span><span>' + esc(it.fecha || 'Fecha por definir') + '</span></div>';
        html +=     '<div class="nov-source">' + esc(it.page_label || '') + '</div>';
        html +=     '<h4 class="nov-title">' + esc(it.titulo || 'Novedad') + '</h4>';
        html +=   '</div>';
        html += '</a>';
      } else {
        html += '<div class="nov-mini nov-empty"><div class="nov-empty-inner"><i class="bi bi-hourglass-split"></i><span>Próximamente...</span></div></div>';
      }
    }

    html +=   '</div>';
    html += '</div>';
    wrap.innerHTML = html;
  }

  var ctrl = window.AbortController ? new AbortController() : null;
  var timer = setTimeout(function () {
    if (ctrl) ctrl.abort();
  }, 4500);

  fetch(api, { cache: 'no-store', signal: ctrl ? ctrl.signal : undefined })
    .then(function (res) { return res.json(); })
    .then(function (data) {
      clearTimeout(timer);
      if (!data || data.ok !== true) return;
      render(data.items || []);
    })
    .catch(function () {
      clearTimeout(timer);
      // Fallback: conservar render server-side actual.
    });
})();
</script>

<!-- Top Feature Start -->
<div class="container-fluid top-feature py-5 pt-lg-0">
  <div class="container py-5 pt-lg-0">
    <div class="row gx-4 gy-4">
      <div class="col-lg-4 wow fadeIn" data-wow-delay="0.1s">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa fa-leaf text-primary"></i></div>
          <div class="feature-body">
            <h4 class="feature-title">Desarrollo Sostenible</h4>
            <p class="feature-desc">Impulsamos educación ambiental y gestión de residuos con campañas UNT libre de plástico y RAEEcicla.</p>
          </div>
        </div>
      </div>
      <div class="col-lg-4 wow fadeIn" data-wow-delay="0.3s">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa fa-clipboard-check text-primary"></i></div>
          <div class="feature-body">
            <h4 class="feature-title">Proyectos de Responsabilidad Social</h4>
            <p class="feature-desc">Asesoramos y monitoreamos iniciativas de RSU alineadas a la directiva institucional en el Sistema DIRSU.</p>
          </div>
        </div>
      </div>
      <div class="col-lg-4 wow fadeIn" data-wow-delay="0.5s">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa fa-hands-helping text-primary"></i></div>
          <div class="feature-body">
            <h4 class="feature-title">Voluntariado Universitario</h4>
            <p class="feature-desc">Formamos estudiantes voluntarios para acciones en salud, ciudadania y ambiente en sede central y filiales.</p>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Top Feature End -->

<!-- About Start -->
<div class="container-xxl py-5">
  <div class="container">
    <div class="row g-5 align-items-end">
      <div class="col-lg-3 col-md-5 wow fadeInUp" data-wow-delay="0.1s">
        <img class="img-fluid rounded" data-wow-delay="0.1s" src="<?php echo htmlspecialchars($imgDirectora, ENT_QUOTES, 'UTF-8'); ?>" alt="Directora DIRSU">
      </div>
      <div class="col-lg-6 col-md-7 wow fadeInUp" data-wow-delay="0.3s">
        <h1 class="display-1 text-primary mb-0">+15</h1>
        <p class="text-primary mb-4">Años de compromiso con la comunidad</p>
        <h1 class="display-5 mb-4">
          La Dirección de Responsabilidad Social Universitaria impulsa el impacto positivo de la UNT a nivel local, regional y global
        </h1>
        <p class="mb-4">
          La RSU es un eje transversal en nuestra universidad. A través de proyectos sostenibles, extensión universitaria, voluntariado y alianzas estratégicas, promovemos una formación integral con sentido ético, inclusivo y solidario, alineada con los Objetivos de Desarrollo Sostenible (ODS).
        </p>
        <a class="btn btn-primary py-3 px-4" href="index.php?p=areas_proyectos">Conoce nuestra labor</a>
      </div>
      <div class="col-lg-3 col-md-12 wow fadeInUp" data-wow-delay="0.5s">
        <div class="row g-5">
          <div class="col-12 col-sm-6 col-lg-12">
            <div class="border-start ps-4">
              <i class="fa fa-award fa-3x text-primary mb-3"></i>
              <h4 class="mb-3">Estandares internacionales</h4>
              <span>Seguimos los lineamientos de la Ley Universitaria N.° 30220 y nos alineamos con los ODS de la Agenda 2030.</span>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-12">
            <div class="border-start ps-4">
              <i class="fa fa-users fa-3x text-primary mb-3"></i>
              <h4 class="mb-3">Equipo dedicado</h4>
              <span>Contamos con docentes y profesionales comprometidos con la sostenibilidad, la inclusión y la transformación social.</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- About End -->

<!-- Facts Start -->
<div class="container-fluid facts my-5 py-5" data-parallax="scroll" data-image-src="<?php echo htmlspecialchars($imgFactsBg, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="container py-5">
    <div class="row g-5">
      <div class="col-sm-6 col-lg-3 text-center wow fadeIn" data-wow-delay="0.1s">
        <h1 class="display-4 text-white" data-toggle="counter-up">1234</h1>
        <span class="fs-5 fw-semi-bold text-light">Estudiantes beneficiados</span>
      </div>
      <div class="col-sm-6 col-lg-3 text-center wow fadeIn" data-wow-delay="0.3s">
        <h1 class="display-4 text-white" data-toggle="counter-up">1234</h1>
        <span class="fs-5 fw-semi-bold text-light">Pobladores alcanzados</span>
      </div>
      <div class="col-sm-6 col-lg-3 text-center wow fadeIn" data-wow-delay="0.5s">
        <h1 class="display-4 text-white" data-toggle="counter-up">1234</h1>
        <span class="fs-5 fw-semi-bold text-light">Voluntarios dedicados</span>
      </div>
      <div class="col-sm-6 col-lg-3 text-center wow fadeIn" data-wow-delay="0.7s">
        <h1 class="display-4 text-white" data-toggle="counter-up">1234</h1>
        <span class="fs-5 fw-semi-bold text-light">Proyectos desarrollados</span>
      </div>
    </div>
  </div>
</div>
<!-- Facts End -->

<!-- Features Start -->
<div class="container-xxl py-5">
  <div class="container">
    <div class="row g-5 align-items-center">
      <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
        <p class="fs-5 fw-bold text-primary">¿Qué impulsa a la Dirección de Responsabilidad Social Universitaria?</p>
        <h1 class="display-5 mb-4">Compromiso institucional con el desarrollo sostenible</h1>
        <p class="mb-4">
          La DIRSU impulsa una universidad comprometida con su entorno. Promovemos el trabajo articulado entre academia y sociedad, contribuyendo al bienestar humano, al cuidado ambiental y a la transformación social desde un enfoque inclusivo y participativo.
        </p>
        <a class="btn btn-primary py-3 px-4" href="index.php?p=areas_proyectos">Ver mas</a>
      </div>
      <div class="col-lg-6">
        <div class="row g-4 align-items-center">
          <div class="col-md-6">
            <div class="row g-4">
              <div class="col-12 wow fadeIn" data-wow-delay="0.3s">
                <div class="text-center rounded py-5 px-4" style="box-shadow: 0 0 45px rgba(0,0,0,.08);">
                  <div class="btn-square bg-light rounded-circle mx-auto mb-4" style="width: 90px; height: 90px;">
                    <i class="fa fa-heart fa-3x text-primary"></i>
                  </div>
                  <h4 class="mb-0">Desarrollo humano</h4>
                </div>
              </div>
              <div class="col-12 wow fadeIn" data-wow-delay="0.5s">
                <div class="text-center rounded py-5 px-4" style="box-shadow: 0 0 45px rgba(0,0,0,.08);">
                  <div class="btn-square bg-light rounded-circle mx-auto mb-4" style="width: 90px; height: 90px;">
                    <i class="fa fa-leaf fa-3x text-primary"></i>
                  </div>
                  <h4 class="mb-0">Sostenibilidad ambiental</h4>
                </div>
              </div>
            </div>
          </div>
          <div class="col-md-6 wow fadeIn" data-wow-delay="0.7s">
            <div class="text-center rounded py-5 px-4" style="box-shadow: 0 0 45px rgba(0,0,0,.08);">
              <div class="btn-square bg-light rounded-circle mx-auto mb-4" style="width: 90px; height: 90px;">
                <i class="fa fa-handshake fa-3x text-primary"></i>
              </div>
              <h4 class="mb-0">Vinculación con la sociedad</h4>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Features End -->

<!-- Service Start -->
<div class="container-xxl py-5 home-services">
  <div class="container">
    <div class="text-center mx-auto wow fadeInUp" data-wow-delay="0.1s" style="max-width: 600px;">
      <p class="fs-5 fw-bold text-primary">¿En qué trabaja la DIRSU?</p>
      <h1 class="display-5 mb-5">Líneas estratégicas de responsabilidad social universitaria</h1>
    </div>
    <div class="row g-4">
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-university fa-2x text-primary"></i>
          </div>
          <div><h4>Formación con sentido ético</h4><p>Integramos valores, ODS y responsabilidad social en la formación académica.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-seedling fa-2x text-primary"></i>
          </div>
          <div><h4>Campus responsable</h4><p>Promovemos sostenibilidad ambiental, equidad, inclusion y bienestar.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-handshake fa-2x text-primary"></i>
          </div>
          <div><h4>Vinculación territorial</h4><p>Realizamos proyectos con impacto en comunidades priorizadas.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-users fa-2x text-primary"></i>
          </div>
          <div><h4>Voluntariado universitario</h4><p>Fomentamos la participación activa de estudiantes con conciencia social.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-chart-line fa-2x text-primary"></i>
          </div>
          <div><h4>Evaluación e impacto</h4><p>Monitoreamos y mejoramos nuestros proyectos con indicadores claros.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-network-wired fa-2x text-primary"></i>
          </div>
          <div><h4>Alianzas estratégicas</h4><p>Colaboramos con municipios, ONGs, redes universitarias y sociedad civil.</p></div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Service End -->

<!-- Quote Start -->
<div class="container-fluid quote my-5 py-5" data-parallax="scroll" data-image-src="<?php echo htmlspecialchars($imgConvocatoriaBg, ENT_QUOTES, 'UTF-8'); ?>">
  <div class="container py-5">
    <div class="row justify-content-center">
      <div class="col-lg-7">
        <?php $convocatoria_activa = false; $disabled_attr = $convocatoria_activa ? '' : 'disabled'; ?>
        <div class="bg-white rounded p-4 p-sm-5 wow fadeIn convocatoria-card position-relative" data-wow-delay="0.5s">
          <?php if (!$convocatoria_activa): ?>
            <div class="convocatoria-overlay"><i class="bi bi-exclamation-circle"></i><span>Por el momento no tenemos convocatorias activas.</span></div>
          <?php endif; ?>

          <h1 class="display-5 text-center mb-5">Regístrate en la convocatoria</h1>
          <div class="row g-3">
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?php echo $disabled_attr; ?> type="text" class="form-control bg-light border-0" id="gname" placeholder="Tu nombre" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>">
                <label for="gname">Tu nombre</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?php echo $disabled_attr; ?> type="email" class="form-control bg-light border-0" id="gmail" placeholder="Tu correo" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>">
                <label for="gmail">Tu correo</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?php echo $disabled_attr; ?> type="text" class="form-control bg-light border-0" id="cname" placeholder="Tu celular" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>">
                <label for="cname">Tu celular</label>
              </div>
            </div>
            <div class="col-sm-6">
              <div class="form-floating">
                <input <?php echo $disabled_attr; ?> type="text" class="form-control bg-light border-0" id="cage" placeholder="Tu escuela" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>">
                <label for="cage">Tu escuela</label>
              </div>
            </div>
            <div class="col-12">
              <div class="form-floating">
                <textarea <?php echo $disabled_attr; ?> class="form-control bg-light border-0" placeholder="Tu ciclo" id="message" style="height: 100px" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>"></textarea>
                <label for="message">Tu ciclo</label>
              </div>
            </div>
            <div class="col-12 text-center">
              <button <?php echo $disabled_attr; ?> class="btn btn-primary py-3 px-4" type="submit" aria-disabled="<?php echo $convocatoria_activa ? 'false' : 'true'; ?>">Quiero unirme</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- Quote End -->

<!-- DIRSU Brief Start -->
<div class="container-xxl py-5" id="dirsu-brief">
  <div class="container">
    <div class="row g-5 align-items-center dirsu-brief">
      <div class="col-lg-6 wow fadeInUp" data-wow-delay="0.1s">
        <p class="fs-5 fw-bold text-primary">Conócenos</p>
        <h1 class="display-5 mb-4">¿Qué hace la DIRSU?</h1>
        <p class="mb-3">
          Impulsamos la Responsabilidad Social Universitaria articulando la formación, la investigación y la extensión con las necesidades del entorno, para generar impacto social y desarrollo sostenible.
        </p>
        <ul class="mb-4">
          <li>Acompañamos a las facultades en la formulación y evaluación de proyectos de RSU y estandarizamos la calidad con lineamientos institucionales.</li>
          <li>Fortalecemos capacidades con cursos y talleres, y modernizamos la gestión con una plataforma digital para registrar y dar seguimiento a los proyectos.</li>
          <li>Promovemos voluntariado universitario y campañas ambientales (p.e. RAEEcicla, UNT libre de plástico) en articulación con entidades públicas y redes regionales.</li>
        </ul>
        <a class="btn btn-primary py-3 px-4" href="index.php?p=areas_proyectos">Conoce nuestra labor</a>
      </div>

      <div class="col-lg-6">
        <div class="row g-4">
          <div class="col-12 wow fadeInUp" data-wow-delay="0.2s">
            <div class="person-card d-flex align-items-center p-4 rounded bg-white shadow-sm h-100">
              <img src="<?php echo htmlspecialchars($imgLourdes, ENT_QUOTES, 'UTF-8'); ?>" alt="Directora DIRSU" class="person-photo me-3">
              <div>
                <h4 class="mb-1">Dr. Lourdes Tuesta Collantes</h4>
                <p class="text-primary mb-2">Directora DIRSU</p>
                <p class="mb-0 small">
                  Conduce la estrategia institucional de RSU y la articulación con las funciones formativa, investigativa y de extensión, priorizando proyectos con impacto y la mejora continua.
                </p>
              </div>
            </div>
          </div>

          <div class="col-12 wow fadeInUp" data-wow-delay="0.3s">
            <div class="person-card d-flex align-items-center p-4 rounded bg-white shadow-sm h-100">
              <img src="<?php echo htmlspecialchars($imgYsmael, ENT_QUOTES, 'UTF-8'); ?>" alt="Administrador DIRSU" class="person-photo me-3">
              <div>
                <h4 class="mb-1">Ysmael Linares Neyra</h4>
                <p class="text-primary mb-2">Administrador DIRSU</p>
                <p class="mb-0 small">
                  Gestiona los procesos administrativos y el soporte operativo para la ejecución, seguimiento y evaluación de los programas y proyectos de RSU.
                </p>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>
  </div>
</div>
<!-- DIRSU Brief End -->
