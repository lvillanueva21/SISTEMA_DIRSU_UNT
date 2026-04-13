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

$slides = array();

try {
  $sql = "SELECT imagen, titulo, mostrar_titulo, subtitulo, mostrar_subtitulo
          FROM cw_carrusel
          WHERE visible = 1
          ORDER BY orden ASC, id ASC";

  $rs = db()->query($sql);
  while ($row = $rs->fetch_assoc()) {
    $img = home_resolve_media_path((string)($row['imagen'] ?? ''));
    if ($img === '') continue;

    $slides[] = array(
      'imagen' => $img,
      'titulo' => (string)($row['titulo'] ?? ''),
      'mostrar_titulo' => ((int)($row['mostrar_titulo'] ?? 0) === 1),
      'subtitulo' => (string)($row['subtitulo'] ?? ''),
      'mostrar_subtitulo' => ((int)($row['mostrar_subtitulo'] ?? 0) === 1),
    );
  }
  $rs->free();
} catch (Throwable $e) {
  // Fallback below keeps home working even if cw_carrusel is unavailable.
}

if (count($slides) === 0) {
  $slides = array(
    array(
      'imagen' => 'img/1.JPG',
      'titulo' => 'La Universidad Nacional de Trujillo comprometida con los objetivos de desarrollo sostenible (ODS)',
      'mostrar_titulo' => true,
      'subtitulo' => '',
      'mostrar_subtitulo' => false,
    ),
    array(
      'imagen' => 'img/2.JPG',
      'titulo' => 'Bienvenido a la Direccion de Responsabilidad Social',
      'mostrar_titulo' => true,
      'subtitulo' => '',
      'mostrar_subtitulo' => false,
    ),
  );
}

$imgDirectora = home_resolve_media_path('img/directora_vertical.png');
$imgFactsBg = home_resolve_media_path('img/reserva-paracas_webdirsu.png');
$imgConvocatoriaBg = home_resolve_media_path('img/Pampa-Galeras-Barbara-DAchille_webdirsu.png');
$imgLourdes = home_resolve_media_path('img/lurdes.jpeg');
$imgYsmael = home_resolve_media_path('img/ysmael.jpeg');
?>

<!-- Carousel Start -->
<div class="container-fluid p-0 wow fadeIn" data-wow-delay="0.1s">
  <div id="header-carousel" class="carousel slide" data-bs-ride="carousel">
    <div class="carousel-inner">
      <?php foreach ($slides as $idx => $slide): ?>
        <div class="carousel-item <?php echo ($idx === 0) ? 'active' : ''; ?>">
          <div class="carousel-image-crop">
            <img src="<?php echo htmlspecialchars($slide['imagen'], ENT_QUOTES, 'UTF-8'); ?>" alt="Slide">
          </div>
          <div class="carousel-caption">
            <div class="container">
              <div class="row justify-content-center">
                <div class="col-lg-12">
                  <?php if ($slide['mostrar_titulo'] && $slide['titulo'] !== ''): ?>
                    <h5 class="display-1 text-white mb-3 animated slideInDown"><?php echo htmlspecialchars($slide['titulo'], ENT_QUOTES, 'UTF-8'); ?></h5>
                  <?php endif; ?>
                  <?php if ($slide['mostrar_subtitulo'] && $slide['subtitulo'] !== ''): ?>
                    <p class="lead text-white mb-4 animated fadeInUp"><?php echo htmlspecialchars($slide['subtitulo'], ENT_QUOTES, 'UTF-8'); ?></p>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
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
<!-- Carousel End -->

<!-- Top Feature Start -->
<div class="container-fluid top-feature py-5 pt-lg-0">
  <div class="container py-5 pt-lg-0">
    <div class="row gx-4 gy-4">
      <div class="col-lg-4 wow fadeIn" data-wow-delay="0.1s">
        <div class="feature-card">
          <div class="feature-icon"><i class="fa fa-leaf text-primary"></i></div>
          <div class="feature-body">
            <h4 class="feature-title">Desarrollo Sostenible</h4>
            <p class="feature-desc">Impulsamos educacion ambiental y gestion de residuos con campanas UNT libre de plastico y RAEEcicla.</p>
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
        <p class="text-primary mb-4">Anos de compromiso con la comunidad</p>
        <h1 class="display-5 mb-4">
          La Direccion de Responsabilidad Social Universitaria impulsa el impacto positivo de la UNT a nivel local, regional y global
        </h1>
        <p class="mb-4">
          La RSU es un eje transversal en nuestra universidad. A traves de proyectos sostenibles, extension universitaria, voluntariado y alianzas estrategicas, promovemos una formacion integral con sentido etico, inclusivo y solidario, alineada con los Objetivos de Desarrollo Sostenible (ODS).
        </p>
        <a class="btn btn-primary py-3 px-4" href="index.php?p=areas_proyectos">Conoce nuestra labor</a>
      </div>
      <div class="col-lg-3 col-md-12 wow fadeInUp" data-wow-delay="0.5s">
        <div class="row g-5">
          <div class="col-12 col-sm-6 col-lg-12">
            <div class="border-start ps-4">
              <i class="fa fa-award fa-3x text-primary mb-3"></i>
              <h4 class="mb-3">Estandares internacionales</h4>
              <span>Seguimos los lineamientos de la Ley Universitaria N. 30220 y nos alineamos con los ODS de la Agenda 2030.</span>
            </div>
          </div>
          <div class="col-12 col-sm-6 col-lg-12">
            <div class="border-start ps-4">
              <i class="fa fa-users fa-3x text-primary mb-3"></i>
              <h4 class="mb-3">Equipo dedicado</h4>
              <span>Contamos con docentes y profesionales comprometidos con la sostenibilidad, la inclusion y la transformacion social.</span>
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
        <p class="fs-5 fw-bold text-primary">Que impulsa a la Direccion de Responsabilidad Social Universitaria?</p>
        <h1 class="display-5 mb-4">Compromiso institucional con el desarrollo sostenible</h1>
        <p class="mb-4">
          La DIRSU impulsa una universidad comprometida con su entorno. Promovemos el trabajo articulado entre academia y sociedad, contribuyendo al bienestar humano, al cuidado ambiental y a la transformacion social desde un enfoque inclusivo y participativo.
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
              <h4 class="mb-0">Vinculacion con la sociedad</h4>
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
      <p class="fs-5 fw-bold text-primary">En que trabaja la DIRSU?</p>
      <h1 class="display-5 mb-5">Lineas estrategicas de responsabilidad social universitaria</h1>
    </div>
    <div class="row g-4">
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-university fa-2x text-primary"></i>
          </div>
          <div><h4>Formacion con sentido etico</h4><p>Integramos valores, ODS y responsabilidad social en la formacion academica.</p></div>
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
          <div><h4>Vinculacion territorial</h4><p>Realizamos proyectos con impacto en comunidades priorizadas.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.1s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-users fa-2x text-primary"></i>
          </div>
          <div><h4>Voluntariado universitario</h4><p>Fomentamos la participacion activa de estudiantes con conciencia social.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.2s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-chart-line fa-2x text-primary"></i>
          </div>
          <div><h4>Evaluacion e impacto</h4><p>Monitoreamos y mejoramos nuestros proyectos con indicadores claros.</p></div>
        </div>
      </div>
      <div class="col-lg-4 col-md-6 wow fadeInUp" data-wow-delay="0.3s">
        <div class="service-item rounded d-flex h-100 p-4">
          <div class="btn-square bg-light rounded-circle flex-shrink-0 me-3" style="width: 60px; height: 60px;">
            <i class="fa fa-network-wired fa-2x text-primary"></i>
          </div>
          <div><h4>Alianzas estrategicas</h4><p>Colaboramos con municipios, ONGs, redes universitarias y sociedad civil.</p></div>
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
            <div class="convocatoria-overlay"><i class="bi bi-exclamation-circle"></i><span>Por el momento no tenemos convocatoria activas.</span></div>
          <?php endif; ?>

          <h1 class="display-5 text-center mb-5">Registrate en la convocatoria</h1>
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
        <p class="fs-5 fw-bold text-primary">Conocenos</p>
        <h1 class="display-5 mb-4">Que hace la DIRSU?</h1>
        <p class="mb-3">
          Impulsamos la Responsabilidad Social Universitaria articulando la formacion, la investigacion y la extension con las necesidades del entorno, para generar impacto social y desarrollo sostenible.
        </p>
        <ul class="mb-4">
          <li>Acompanamos a las facultades en la formulacion y evaluacion de proyectos de RSU y estandarizamos la calidad con lineamientos institucionales.</li>
          <li>Fortalecemos capacidades con cursos y talleres, y modernizamos la gestion con una plataforma digital para registrar y dar seguimiento a los proyectos.</li>
          <li>Promovemos voluntariado universitario y campanas ambientales (p.e. RAEEcicla, UNT libre de plastico) en articulacion con entidades publicas y redes regionales.</li>
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
                  Conduce la estrategia institucional de RSU y la articulacion con las funciones formativa, investigativa y de extension, priorizando proyectos con impacto y la mejora continua.
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
                  Gestiona los procesos administrativos y el soporte operativo para la ejecucion, seguimiento y evaluacion de los programas y proyectos de RSU.
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
