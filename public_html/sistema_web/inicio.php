<?php
   // Incluir configSesion.php para verificar la sesión
   include "componentes/configSesion.php";

   // Incluir la conexión a la base de datos
   include('componentes/db.php');

   // Incluir el archivo que carga los datos del proyecto
   include('componentes/proyecto/cargar_proyecto.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Inicio - Sistema DIRSU</title>
  <!-- Favicon -->
  <link href="imagenes/dirsu_128_128.ico" rel="icon">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="plogins/fontawesome-free/css/all.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Tempusdominus Bootstrap 4 -->
  <link rel="stylesheet" href="plogins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="plogins/icheck-bootstrap/icheck-bootstrap.min.css">
  <!-- JQVMap -->
  <link rel="stylesheet" href="plogins/jqvmap/jqvmap.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="dust/css/adminlte.min.css">
  <!-- overlayScrollbars -->
  <link rel="stylesheet" href="plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <!-- Daterange picker -->
  <link rel="stylesheet" href="plogins/daterangepicker/daterangepicker.css">
  <!-- summernote -->
  <link rel="stylesheet" href="plogins/summernote/summernote-bs4.min.css">

  <style>
    /* Counter antiguo (se mantiene por compatibilidad) */
    .counter-box {
      display: inline-block;
      margin: 0 5px;
      padding: 10px;
      background-color: #f8f9fa;
      border: 1px solid #dee2e6;
      border-radius: 5px;
      text-align: center;
      min-width: 70px;
    }
    .counter-box h3 { margin: 0; font-size: 1.5rem; }
    .counter-box p  { margin: 0; font-size: .9rem; }

    /* ==============================
       Igualar alturas de cards
       ============================== */
    /* Contenedores (grids) con 2 columnas por fila */
    .grid-two .col-md-6 { display:flex; }
    .grid-two .card     { width:100%; display:flex; flex-direction:column; }

    /* Altura visual uniforme por defecto (fallback si JS no corre) */
    :root{ --cardHeight: 360px; }
    .grid-two.equal-fixed .card { height: var(--cardHeight); }

    /* Imagenes en cards con recorte suave para uniformidad */
    .image-wrapper{
      height: 300px;       /* hace que ambas cards luzcan parejas */
      overflow: hidden;
    }
    @media (max-width: 575.98px){
      .image-wrapper{ height: 220px; }
    }
.img-thumb{
  width:100%; height:100%;
  object-fit:cover;
  object-position: top; /* anclar arriba */
  cursor:zoom-in;
}
    /* Deadline chip compacto en header */
    .deadline-badge{
      display:inline-flex; align-items:center; white-space:nowrap;
      background:#fff; color:#dc3545; border:1px dashed #f3c2c2;
      padding:.15rem .5rem; border-radius:999px; font-size:.78rem; line-height:1;
    }
    .deadline-card .counter-box.compact{
      min-width:72px; padding:.4rem .5rem; text-align:center;
      background:#fff; border:1px solid #f1c1c1; border-radius:.6rem;
    }
    .deadline-card .counter-num{ font-size:1.25rem; font-weight:700; margin:0; }
    .deadline-card .counter-label{ font-size:.72rem; margin:0; color:#6c757d; }
    .btn-cta{ width:100%; } @media (min-width:576px){ .btn-cta{ width:auto; } }
    .gap-8{ gap:8px; }

    /* ==============================
       Modal visor con zoom/arrastre
       ============================== */
    .viewer-container{
      position:relative; background:#000; height:75vh; overflow:hidden; cursor:grab;
    }
    .viewer-container:active{ cursor:grabbing; }
    #modalImage{
      position:absolute; top:50%; left:50%;
      transform:translate(-50%, -50%) scale(1);
      max-width:none; user-select:none; pointer-events:none;
    }
  </style>
  <style>
  /* Grid principal: 2 columnas, CTA y alerta a todo el ancho */
  .deadline-grid{
    display:grid;
    grid-template-columns: 1fr 1fr;
    grid-template-rows: auto 1fr auto auto; /* meta+msg | (libre) | CTA | alerta */
    gap:14px;
    height:100%;
  }
  .deadline-grid .left{
    grid-column:1; grid-row:1 / span 2;
    display:flex; flex-direction:column; justify-content:flex-start;
  }
  .deadline-grid .right{
    grid-column:2; grid-row:1 / span 2;
    display:flex; /* centra el grid interno */
  }
  .deadline-grid .cta{ grid-column:1 / -1; }
  #deadline-ended{ grid-column:1 / -1; }

  /* Chip de fecha */
  .meta-chip{
    background:#fff; color:#dc3545;
    border:1px dashed #f3c2c2; border-radius:999px;
    padding:.2rem .6rem; white-space:nowrap; display:inline-flex; align-items:center;
  }

  /* Contador en tiles que llenan todo el alto disponible */
  .countdown-grid{
    display:grid; gap:12px; width:100%;
    grid-template-columns: repeat(2, minmax(0,1fr));
    grid-auto-rows: 1fr;   /* todas las filas misma altura */
    align-content:stretch; /* ocupar verticalmente */
    flex:1;                /* que el contenedor crezca a todo el alto */
  }
  .time-tile{
    border:1px solid #f1c1c1; border-radius:.75rem;
    box-shadow:0 1px 2px rgba(0,0,0,.04);
    display:flex; flex-direction:column; align-items:center; justify-content:center;
    padding:1rem;
  }
  .time-number{ font-size:3rem; font-weight:800; line-height:1; margin-bottom:.25rem; }
  .time-label{ font-size:.9rem; color:#6c757d; }

  /* Responsivo */
  @media (max-width: 991.98px){
    .deadline-grid{
      grid-template-columns: 1fr;
      grid-template-rows: auto auto auto auto; /* meta+msg | contador | CTA | alerta */
    }
    .deadline-grid .left{ grid-column:1; grid-row:auto; }
    .deadline-grid .right{ grid-column:1; grid-row:auto; min-height:180px; }
    .time-number{ font-size:2.4rem; }
  }
</style>
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<?php include 'componentes/multiproyectos/multi.php'; ?>
<?php
if (isset($_SESSION['id_rol']) && (int)$_SESSION['id_rol'] === 2) {
    $rsu_card_auditoria_api_url = 'includes/api_dirsu/api.php';
    $rsu_card_auditoria_mostrar_todos = true;

    // Si quieres mostrar solo el proyecto activo (id_py actual), cambia a false:
    // $rsu_card_auditoria_mostrar_todos = false;
    if ($rsu_card_auditoria_mostrar_todos) {
        include 'includes/cards/card_auditoria_semestres_todos.php';
    } else {
        include 'includes/cards/card_auditoria_semestres_actual.php';
    }
}
?>
<div class="wrapper">
  <!-- Preloader -->
  <div class="preloader flex-column justify-content-center align-items-center">
    <img class="animation__shake" src="dust/img/dirsu_logo_128_128.png" alt="AdminLTELogo" height="60" width="60">
  </div>

  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="background-image: url('web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0,0,0,.6);">
        <a href="https://rsu.unitru.edu.pe" class="nav-link" target="_blank">
          <p style="color:white;size:8px">Ir a página DIRSU</p>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->

  <!-- Sidebar -->
  <?php include_once __DIR__ . '/includes/sidebar.php'; ?>
  <!-- /.sidebar -->

  <!-- Content Wrapper -->
  <div class="content-wrapper">
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-7"><h5 class="m-0">¡Bienvenido al Sistema de gestión de proyectos DIRSU!</h5></div>
        </div>
      </div>
    </div>

    <!-- Main content -->
    <section class="content">
      <div class="container-fluid">

        <!-- ====== FILA 1: CUADROS PEQUEÑOS (4) ====== -->
        <div class="row grid-two equal-fixed">
          <!-- Tipo de usuario -->
          <div class="col-md-6 col-lg-3 col-6 mb-3">
            <div class="card h-100">
              <div class="small-box bg-success mb-0">
                <div class="inner"><h3>Coordinador</h3><p>Tipo de usuario</p></div>
                <div class="icon"><i class="ion ion-person"></i></div>
                <a href="vistas/perfil.php" class="small-box-footer">Editar información de usuario <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
          <!-- Importante -->
          <div class="col-md-6 col-lg-3 col-6 mb-3">
            <div class="card h-100">
              <div class="small-box bg-danger mb-0">
                <div class="inner"><h3>¡Importante!<sup style="font-size:20px"></sup></h3><p>Actualiza tu sede y departamento.</p></div>
                <div class="icon"><i class="ionicons ion-alert"></i></div>
                <a href="vistas/perfil.php" class="small-box-footer">Ir a actualizar <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
          <!-- Proyectos registrados -->
          <div class="col-md-6 col-lg-3 col-6 mb-3">
            <div class="card h-100">
              <div class="small-box bg-warning mb-0">
                <div class="inner"><h3><?php echo (!empty($p2) ? "01": "0"); ?></h3><p>Proyectos registrados</p></div>
                <div class="icon"><i class="ionicons ion-android-archive"></i></div>
                <a href="vistas/datos_principales.php" class="small-box-footer">Registrar proyecto <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
          <!-- Items pendientes -->
          <div class="col-md-6 col-lg-3 col-6 mb-3">
            <div class="card h-100">
              <div class="small-box bg-info mb-0">
                <div class="inner">
                  <h3>
                    <?php
                      $null_count = 0;
                      $variables = [
                        '$estado' => $estado, '$p1' => $p1, '$p2' => $p2, '$p3' => $p3, '$p4' => $p4, '$p5' => $p5,
                        '$p6' => $p6, '$p7_1' => $p7_1, '$p7_2' => $p7_2, '$infantes' => $infantes, '$ninos' => $ninos,
                        '$adolescentes' => $adolescentes, '$jovenes' => $jovenes, '$adultos' => $adultos, '$adultos_mayores' => $adultos_mayores,
                        '$sector' => $sector, '$caserio' => $caserio, '$distrito' => $distrito, '$provincia' => $provincia, '$departamento' => $departamento,
                        '$fecha_inicio' => $fecha_inicio, '$fecha_fin' => $fecha_fin, '$planificacion' => $planificacion, '$ejecucion' => $ejecucion, '$monitoreo' => $monitoreo,
                        '$p10_1s' => $p10_1s, '$p10_2s' => $p10_2s, '$p10_3s' => $p10_3s, '$p10_1h' => $p10_1h, '$p10_2h' => $p10_2h, '$p10_3h' => $p10_3h,
                        '$disciplinar' => $disciplinar, '$facultad' => $facultad, '$programa_estudios' => $programa_estudios, '$departamento_academico' => $departamento_academico,
                        '$coordinador' => $coordinador, '$componentes' => $componentes, '$integrantes_docentes' => $integrantes_docentes, '$delegados_estudiantes' => $delegados_estudiantes,
                        '$diagnostico' => $diagnostico, '$justificacion' => $justificacion, '$general' => $general, '$especificos' => $especificos, '$metas' => $metas,
                        '$cronograma1' => $cronograma1, '$cronograma2' => $cronograma2, '$metodologia' => $metodologia, '$entregables' => $entregables, '$impacto' => $impacto,
                        '$matriz' => $matriz, '$pre_dis' => $pre_dis, '$pre_nodis' => $pre_nodis, '$ser_dis' => $ser_dis, '$ser_nodis' => $ser_nodis,
                        '$resumen' => $resumen, '$monto_uni' => $monto_uni, '$monto_auto' => $monto_auto, '$monto_otro' => $monto_otro,
                      ];
                      function isNullOrEmpty($var){ return is_null($var) || $var === "" || (is_array($var) && empty($var)); }
                      foreach ($variables as $name => $value) { if (isNullOrEmpty($value)) { $null_count++; } }
                      echo $null_count;
                    ?>
                  </h3>
                  <p>Items pendientes del proyecto</p>
                </div>
                <div class="icon"><i class="ionicons ion-ios-list"></i></div>
                <a href="vistas/datos_principales.php" class="small-box-footer">Llenar items pendientes <i class="fas fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
        </div>
        <!-- /.row -->
        <!-- ====== FILA 4: IMÁGENES GRANDES (2 columnas, mismas dimensiones) ====== -->
        <div class="row grid-two">
          <!-- Imagen 1 -->
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <!-- Imagen 1 -->
<div class="card-header bg-primary text-white d-flex justify-content-between align-items-center py-2">
  <h3 class="card-title text-center m-0">Ruta de evaluación del informe semestral</h3>
  <div class="btn-group">
    <a class="btn btn-sm btn-light" href="imagenes/temporal/ruta_informe_semestral2025.jpg"
       download="ruta_informe_semestral2025.jpg" title="Descargar imagen" aria-label="Descargar imagen">
      <i class="fas fa-download"></i>
    </a>
    <button type="button" class="btn btn-sm btn-light open-image"
            data-src="imagenes/temporal/ruta_informe_semestral2025.jpg"
            title="Expandir imagen" aria-label="Expandir imagen">
      <i class="fas fa-expand-arrows-alt"></i>
    </button>
  </div>
</div>
              <div class="image-wrapper">
                <img
                  src="imagenes/temporal/ruta_informe_semestral2025.jpg"
                  alt="Ruta de evaluación del informe semestral 2025-I"
                  class="img-thumb"
                  loading="lazy"
                  data-full-src="imagenes/temporal/ruta_informe_semestral2025.jpg"
                />
              </div>
            </div>
          </div>

          <!-- Imagen 2 -->
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-header bg-info text-white d-flex justify-content-between align-items-center py-2">
  <h3 class="card-title text-center m-0">Comunicado por el Vencimiento del Plazo de Informe Semestrales</h3>
  <div class="btn-group">
    <a class="btn btn-sm btn-light" href="imagenes/temporal/comunicado_vencimiento.jpeg"
       download="cronograma_informe_semestral2025.jpeg" title="Descargar imagen" aria-label="Descargar imagen">
      <i class="fas fa-download"></i>
    </a>
    <button type="button" class="btn btn-sm btn-light open-image"
            data-src="imagenes/temporal/comunicado_vencimiento.jpeg"
            title="Expandir imagen" aria-label="Expandir imagen">
      <i class="fas fa-expand-arrows-alt"></i>
    </button>
  </div>
</div>
              <div class="image-wrapper">
                <img
                  src="imagenes/temporal/comunicado_vencimiento.jpeg"
                  alt="Cronograma de revisión de informes semestrales 2025-I"
                  class="img-thumb"
                  loading="lazy"
                  data-full-src="imagenes/temporal/comunicado_vencimiento.jpeg"
                />
              </div>
            </div>
          </div>
        </div>
        <!-- /.row -->


        <!-- ====== FILA 2: BUZÓN & DEADLINE (2 columnas) ====== -->
        <div class="row grid-two">
          <!-- Buzón -->
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card bg-light d-flex flex-fill m-0">
                <div class="card-header text-muted border-bottom-0">
                  <div class="row">
                    <div class="col-10">
                      <div class="card-header"><h3 class="card-title m-0">Mi buzón de mensajes DIRSU</h3></div>
                      <br>
                      <h1 class="lead"><b>Hola <?php echo htmlspecialchars($nombres); ?></b></h1>
                    </div>
                    <div class="col-2 text-right"><img src="dust/img/dirsu_bienvenida.jpg" alt="user-avatar" class="img-circle img-fluid"></div>
                  </div>
                </div>
                <div class="card-body pt-0">
                  <div class="row">
                    <div class="col-12">
                      <p class="text-muted text-sm text-justify">
                        Tu proyecto de Responsabilidad Social <b>
                        <?php
                          echo (!empty($p2)
                          ? $p2 . "</b> se encuentra en la fase I del proceso de presentación de Proyectos de Responsabilidad Social -  2024. Esta fase comprende la Formulación y Presentación de tu Proyecto.<br><br>
                              <b>¿Qué debo hacer en esta fase?</b> <br>
                              <b>1. Registra Generalidades de Proyecto: </b>Deberás ingresar a la pestaña: <a href='https://rsu.unitru.edu.pe/sistema_web/vistas/datos_principales.php'>Generalidades</a> y completar todos los items con la información de tu proyecto.<br>
                              <b>2. Registrar Plan de Proyecto: </b>Deberás ingresar la información correspondiente a tu <a href='https://rsu.unitru.edu.pe/sistema_web/vistas/desarrollo_informe.php'>Plan de Proyectos</a> en la pestaña del mismo nombre.<br>
                              <b>3. Subir Anexos de proyecto: </b>Finalmente, deberás ingresar a la pestaña <a href='https://rsu.unitru.edu.pe/sistema_web/vistas/anexos.php'>Anexos</a> para subir los archivos que complementan la presentación de tu proyecto."
                          : "</b> aún no cuenta con un título registrado.<br><br>
                              <b>¿Cuál es el primer paso en mi proyecto?</b> <br>
                              Como coordinador de proyecto deberás subir la información y documentos referentes a la <b>Fase 1 Formulación y Presentación del Proyecto</b>  al sistema.<br><br>
                              <b>Ve a la barra de menú lateral a tu izquierda y busca Fases de proyecto:</b><br>
                              Haz clic en la pestaña 1. Formulación y presentación.<br>
                              &nbsp;&nbsp;1.1. Primero deberás ingresar las Generalidades de tu proyecto.<br>
                              &nbsp;&nbsp;1.2. Posteriormente, ingresa el Plan de proyecto.<br>
                              &nbsp;&nbsp;1.3. Finalmente ingresa tus Anexos del proyecto.");
                        ?>
                      </p>
                      <ul class="ml-4 mb-0 fa-ul text-muted">
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-envelope"></i></span> Correo: dirsu@unitru.edu.pe</li>
                        <li class="small"><span class="fa-li"><i class="fas fa-lg fa-map-marker-alt"></i></span> Jirón Diego De Almagro 344, Trujillo</li>
                      </ul>
                    </div>
                  </div>
                </div>
                <div class="card-footer"><div class="text-right text-muted small">Alexandra Tirado - Coordinadora de Proyectos DIRSU</div></div>
              </div>
            </div>
          </div>

          <!-- Deadline -->
          <!-- Deadline (layout grid: meta izq, contador der, CTA abajo) -->
<div class="col-md-6 mb-3">
  <div class="card h-100" style="border:1px solid #f3c2c2;border-radius:.5rem;overflow:hidden;">
    <!-- Header -->
    <div class="card-header py-2" style="background:#dc3545;color:#fff;display:flex;align-items:center;justify-content:space-between;">
      <div style="display:flex;align-items:center;">
        <i class="fas fa-bell mr-2"></i>
        <h6 class="m-0">Mi próxima entrega</h6>
      </div>
      <span id="deadline-status" class="badge badge-light" style="color:#dc3545;background:#fff;border:1px solid #f3c2c2;">Activo</span>
    </div>

    <!-- Body -->
    <div class="card-body" style="padding:12px;">
      <!-- Chip fecha -->
      <div style="display:flex;flex-wrap:wrap;align-items:center;margin-bottom:10px;">
        <span style="display:inline-flex;align-items:center;border:1px dashed #f3c2c2;color:#dc3545;background:#fff;border-radius:999px;padding:4px 10px;margin-right:8px;margin-bottom:8px;">
          <i class="far fa-calendar-alt mr-2"></i>
          <strong style="margin-right:4px;">Fecha límite:</strong>
          <span id="deadline-text">31/08/2025 23:59</span>
        </span>
      </div>

      <p class="text-muted small" style="margin-bottom:10px;">
        ¡IMPORTANTE! Subir Informe semestral 2025 - I. Para proyectos creados en 2024 y 2025.
      </p>

      <!-- Contador -->
      <div id="countdown" style="display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;">
        <div style="border:1px solid #f1c1c1;border-radius:12px;padding:14px;text-align:center;">
          <div id="days" style="font-size:32px;font-weight:800;line-height:1;">0</div>
          <div style="font-size:12px;color:#6c757d;">Días</div>
        </div>
        <div style="border:1px solid #f1c1c1;border-radius:12px;padding:14px;text-align:center;">
          <div id="hours" style="font-size:32px;font-weight:800;line-height:1;">0</div>
          <div style="font-size:12px;color:#6c757d;">Horas</div>
        </div>
        <div style="border:1px solid #f1c1c1;border-radius:12px;padding:14px;text-align:center;">
          <div id="minutes" style="font-size:32px;font-weight:800;line-height:1;">0</div>
          <div style="font-size:12px;color:#6c757d;">Minutos</div>
        </div>
        <div style="border:1px solid #f1c1c1;border-radius:12px;padding:14px;text-align:center;">
          <div id="seconds" style="font-size:32px;font-weight:800;line-height:1;">0</div>
          <div style="font-size:12px;color:#6c757d;">Segundos</div>
        </div>
      </div>
    </div>

    <!-- Footer -->
    <div class="card-footer" style="padding:12px;">
      <a id="deadline-cta" href="semestral/" class="btn btn-danger btn-block">
        <i class="fas fa-upload mr-1"></i> Subir Informe Semestral
      </a>
      <div id="deadline-ended" class="alert alert-danger mb-0 d-none"
           style="margin-top:10px;padding:8px 10px;font-size:14px;">
        <i class="fas fa-exclamation-triangle mr-1"></i>
        ¡El deadline ha pasado! Contacta a DIRSU si necesitas asistencia.
      </div>
    </div>
  </div>
</div>
        </div>
        <!-- /.row -->
        <!-- ====== FILA 3: CRONOGRAMA GLOBAL & NOTAS (placeholder) ====== -->
        <div class="row grid-two">
          <div class="col-md-6 mb-3">
            <div class="card h-100">
              <div class="card-header" style="background-color:#28a745;color:#fff;">
                <h3 class="card-title text-center m-0">Cronograma de proyectos DIRSU - Período I - 2024</h3>
              </div>
              <div class="card-body"><?php include('integrados/cronograma_general.php'); ?></div>
            </div>
          </div>

          <!-- Aquí puedes poner otra card si deseas mantener 2 por fila -->
<!-- Notas / Comunicados — Propuesta visual corregida -->
<div class="col-md-6 mb-3">
  <div class="card h-100 messenger-card">
    <!-- HEADER -->
    <div class="card-header bg-secondary text-white d-flex align-items-center justify-content-between py-2">
      <div class="d-flex align-items-center">
        <i class="fas fa-comments mr-2"></i>
        <h6 class="m-0">Notas / Comunicados</h6>
      </div>
      <div class="d-flex align-items-center">
        <span class="badge badge-light text-dark mr-2">3 no leídos</span>
        <button class="btn btn-light btn-sm" title="Marcar todos como leídos" disabled>
          <i class="fas fa-check-double"></i>
        </button>
      </div>
    </div>

    <!-- SUB‑HEADER (barra de búsqueda / filtros) -->
    <div class="card-subheader p-2 border-bottom">
      <div class="input-group input-group-sm">
        <div class="input-group-prepend">
          <span class="input-group-text"><i class="fas fa-search"></i></span>
        </div>
        <input type="text" class="form-control" placeholder="Buscar en mensajes" disabled>
        <div class="input-group-append">
          <button class="btn btn-outline-secondary" type="button" disabled title="Filtrar">
            <i class="fas fa-filter"></i>
          </button>
        </div>
      </div>
    </div>

    <!-- BODY (scrollable) -->
    <div class="card-body p-2 messenger-body">
      <!-- Día / separador -->
      <div class="text-center text-muted small my-2">Hoy</div>

      <!-- Mensaje entrante -->
      <div class="media mb-3 messenger-message">
        <img src="dust/img/avatar.png" class="mr-2 rounded-circle border" width="36" height="36" alt="Usuario">
        <div class="media-body">
          <div class="d-flex align-items-center">
            <strong class="mr-2">Comité RSU</strong>
            <span class="badge badge-success badge-dot mr-2" title="En línea"></span>
            <small class="text-muted">09:15</small>
          </div>
          <div class="msg-bubble msg-in">
            Recordatorio: sube tu informe semestral antes del <b>31/08/2025 – 23:59</b>.
          </div>
          <div class="small text-muted mt-1">Vía sistema</div>
        </div>
      </div>

      <!-- Mensaje saliente -->
      <div class="media mb-3 flex-row-reverse messenger-message">
        <img src="dust/img/avatar.png" class="ml-2 rounded-circle border" width="36" height="36" alt="Yo">
        <div class="media-body text-right">
          <div class="d-flex align-items-center justify-content-end">
            <small class="text-muted">09:22</small>
            <span class="badge badge-secondary badge-dot ml-2" title="Ausente"></span>
            <strong class="ml-2">Yo</strong>
          </div>
          <div class="msg-bubble msg-out ml-auto">
            Recibido. Estoy completando los anexos y lo subiré hoy.
          </div>
          <div class="small text-muted mt-1">Entregado</div>
        </div>
      </div>

      <!-- Mensaje entrante -->
      <div class="media mb-3 messenger-message">
        <img src="dust/img/avatar.png" class="mr-2 rounded-circle border" width="36" height="36" alt="Usuario">
        <div class="media-body">
          <div class="d-flex align-items-center">
            <strong class="mr-2">Dirección RSU</strong>
            <span class="badge badge-success badge-dot mr-2" title="En línea"></span>
            <small class="text-muted">10:05</small>
          </div>
          <div class="msg-bubble msg-in">
            Compartimos el <a href="imagenes/temporal/cronograma_informe_semestral2025.jpeg" target="_blank">cronograma de revisión</a>.
          </div>
        </div>
      </div>

      <!-- Día / separador -->
      <div class="text-center text-muted small my-2">Ayer</div>

      <!-- Mensaje entrante largo -->
      <div class="media mb-3 messenger-message">
        <img src="dust/img/avatar.png" class="mr-2 rounded-circle border" width="36" height="36" alt="Usuario">
        <div class="media-body">
          <div class="d-flex align-items-center">
            <strong class="mr-2">Comité de Facultad</strong>
            <span class="badge badge-secondary badge-dot mr-2" title="Ausente"></span>
            <small class="text-muted">18:41</small>
          </div>
          <div class="msg-bubble msg-in">
            Revisa por favor el punto 2 del plan de proyecto y la rúbrica adjunta. Si tienes dudas, responde por este medio.
          </div>
        </div>
      </div>
    </div>

    <!-- FOOTER -->
    <div class="card-footer">
      <div class="input-group">
        <input type="text" class="form-control" placeholder="Escribe un mensaje... (próximamente)" disabled>
        <div class="input-group-append">
          <button class="btn btn-primary" type="button" disabled title="Enviar">
            <i class="fas fa-paper-plane"></i>
          </button>
        </div>
      </div>
      <div class="text-muted small mt-2 d-flex align-items-center">
        <span class="badge badge-success badge-dot mr-2"></span> En línea
        <span class="mx-2">·</span>
        <span class="badge badge-secondary badge-dot mr-2"></span> Ausente
      </div>
    </div>
  </div>
</div>

        <!-- /.row -->
      </div>
    </section>
  </div>
  <!-- /.content-wrapper -->

  <footer class="main-footer">
    <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
    <div class="float-right d-none d-sm-inline-block">
      <p>Desarrollado por el <a href="#"> Área  informática - DIRSU</a></p>
    </div>
  </footer>

  <aside class="control-sidebar control-sidebar-dark"></aside>
</div>
<!-- ./wrapper -->

<!-- Modal reutilizable para imágenes -->
<div class="modal fade" id="imgModal" tabindex="-1" role="dialog" aria-labelledby="imgModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0" id="imgModalLabel">Vista de imagen</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar"><span aria-hidden="true">&times;</span></button>
      </div>
      <div class="modal-body p-0">
        <div id="viewer" class="viewer-container">
          <img id="modalImage" alt="Imagen ampliada" />
        </div>
      </div>
      <div class="modal-footer py-2">
        <div class="btn-group mr-auto" role="group" aria-label="Zoom">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomOut"><i class="fas fa-minus"></i></button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomReset"><i class="fas fa-compress"></i></button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="zoomIn"><i class="fas fa-plus"></i></button>
        </div>
        <a id="downloadImage" class="btn btn-primary btn-sm" href="#" download><i class="fas fa-download mr-1"></i> Descargar</a>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- jQuery -->
<script src="plogins/jquery/jquery.min.js"></script>
<!-- jQuery UI 1.11.4 -->
<script src="plogins/jquery-ui/jquery-ui.min.js"></script>
<script> $.widget.bridge('uibutton', $.ui.button) </script>
<!-- Bootstrap 4 -->
<script src="plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- ChartJS -->
<script src="plogins/chart.js/Chart.min.js"></script>
<!-- Sparkline -->
<script src="plogins/sparklines/sparkline.js"></script>
<!-- JQVMap -->
<script src="plogins/jqvmap/jquery.vmap.min.js"></script>
<script src="plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
<!-- jQuery Knob Chart -->
<script src="plogins/jquery-knob/jquery.knob.min.js"></script>
<!-- daterangepicker -->
<script src="plogins/moment/moment.min.js"></script>
<script src="plogins/daterangepicker/daterangepicker.js"></script>
<!-- Tempusdominus Bootstrap 4 -->
<script src="plogins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
<!-- Summernote -->
<script src="plogins/summernote/summernote-bs4.min.js"></script>
<!-- overlayScrollbars -->
<script src="plogins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
<!-- AdminLTE App -->
<script src="dust/js/adminlte.js"></script>
<script src="dust/js/demo.js"></script>
<script src="dust/js/pages/dashboard.js"></script>

<!-- ===== DEADLINE SCRIPT ===== -->
<script>
  // Fecha límite para la entrega
  const DEADLINE_ISO = "2025-08-31T23:59:59";

  function updateCountdown() {
    const deadlineDate = new Date(DEADLINE_ISO).getTime();
    const now = new Date().getTime();
    const timeLeft = deadlineDate - now;

    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

    const status = document.getElementById("deadline-status");
    const deadlineText = document.getElementById("deadline-text");
    const countdown = document.getElementById("countdown");
    const endedAlert = document.getElementById("deadline-ended");
    const ctaButton = document.getElementById("deadline-cta");

    if (timeLeft <= 0) {
      clearInterval(countdownInterval);
      if (status){ status.className = "badge badge-dark"; status.textContent = "Cerrado"; }
      if (countdown) countdown.innerHTML = "";
      if (endedAlert) endedAlert.classList.remove("d-none");
      if (ctaButton) ctaButton.classList.add("d-none");
    } else {
      if (status){ status.className = "badge badge-light text-danger"; status.textContent = "Activo"; }
      if (deadlineText){ deadlineText.textContent = "31/08/2025 23:59"; }
      const el = (id,v)=>{ const n=document.getElementById(id); if(n) n.textContent=v; };
      el("days",days); el("hours",hours); el("minutes",minutes); el("seconds",seconds);
      if (endedAlert) endedAlert.classList.add("d-none");
      if (ctaButton) ctaButton.classList.remove("d-none");
    }
  }
  const countdownInterval = setInterval(updateCountdown, 1000);
  updateCountdown();
</script>

<!-- ===== IGUALAR ALTURAS DE CARDS (2 columnas por fila) ===== -->
<script>
  function equalizeCards(){
    // Para cada contenedor con clase grid-two, iguala altura de sus .card
    document.querySelectorAll('.grid-two').forEach(grid=>{
      const cards = Array.from(grid.querySelectorAll('.card'));
      cards.forEach(c=> c.style.height = ''); // reset
      // Altura máxima
      let maxH = 0;
      cards.forEach(c=> { maxH = Math.max(maxH, c.getBoundingClientRect().height); });
      cards.forEach(c=> c.style.height = maxH + 'px');
    });
  }
  window.addEventListener('load', equalizeCards);
  window.addEventListener('resize', ()=>{ clearTimeout(window.__eqt); window.__eqt = setTimeout(equalizeCards, 150); });
  // Re-equalizar cuando se abra/cierre el menú lateral (AdminLTE)
  $(document).on('collapsed.lte.pushmenu expanded.lte.pushmenu', function(){ setTimeout(equalizeCards, 300); });
</script>

<!-- ===== MODAL IMÁGENES: abrir, zoom, arrastre, descargar ===== -->
<script>
(function(){
  const $modal = $('#imgModal');
  const imgEl = document.getElementById('modalImage');
  const viewer = document.getElementById('viewer');
  const dlModal = document.getElementById('downloadImage');

  let scale = 1, minScale = 0.5, maxScale = 6;
  let posX = 0, posY = 0, isDown = false, startX = 0, startY = 0;

  function applyTransform(){
    imgEl.style.transform = `translate(calc(-50% + ${posX}px), calc(-50% + ${posY}px)) scale(${scale})`;
  }
  function resetTransform(){ scale = 1; posX = 0; posY = 0; applyTransform(); }
  function filenameFromPath(path){ try { return path.split('/').pop() || 'imagen.jpg'; } catch(e){ return 'imagen.jpg'; } }

  function openModal(src, alt){
    imgEl.src = src;
    imgEl.alt = alt || 'Imagen ampliada';
    dlModal.href = src;
    dlModal.download = filenameFromPath(src);
    imgEl.onload = resetTransform;
    $modal.modal('show');
  }

  // Abrir desde botón con ícono
  document.querySelectorAll('.open-image').forEach(btn=>{
    btn.addEventListener('click', ()=> openModal(btn.dataset.src, btn.title || btn.ariaLabel));
  });
  // Abrir desde miniatura
  document.querySelectorAll('.img-thumb').forEach(el=>{
    el.addEventListener('click', ()=> openModal(el.dataset.fullSrc || el.src, el.alt));
  });

  // Zoom con rueda
  viewer.addEventListener('wheel', function(e){
    e.preventDefault();
    const delta = e.deltaY < 0 ? 0.1 : -0.1;
    const newScale = Math.min(maxScale, Math.max(minScale, scale + delta));
    if (newScale !== scale){
      const rect = viewer.getBoundingClientRect();
      const cx = e.clientX - rect.left - rect.width/2 - posX;
      const cy = e.clientY - rect.top  - rect.height/2 - posY;
      posX -= cx * (newScale/scale - 1);
      posY -= cy * (newScale/scale - 1);
      scale = newScale;
      applyTransform();
    }
  }, { passive:false });

  // Arrastre
  viewer.addEventListener('mousedown', function(e){ isDown = true; startX = e.clientX - posX; startY = e.clientY - posY; });
  viewer.addEventListener('mousemove', function(e){ if(!isDown) return; posX = e.clientX - startX; posY = e.clientY - startY; applyTransform(); });
  ['mouseup','mouseleave'].forEach(evt=> viewer.addEventListener(evt, ()=>{ isDown=false; }));

  // Botones zoom
  document.getElementById('zoomIn').addEventListener('click', ()=>{ scale = Math.min(maxScale, scale + 0.2); applyTransform(); });
  document.getElementById('zoomOut').addEventListener('click', ()=>{ scale = Math.max(minScale, scale - 0.2); applyTransform(); });
  document.getElementById('zoomReset').addEventListener('click', resetTransform);

  // Doble clic: zoom/normal
  viewer.addEventListener('dblclick', function(){ if (scale === 1) { scale = 2; } else { resetTransform(); return; } applyTransform(); });

  // Limpieza al cerrar
  $modal.on('hidden.bs.modal', function(){ imgEl.src = ''; });
})();
</script>

</body>
</html>
