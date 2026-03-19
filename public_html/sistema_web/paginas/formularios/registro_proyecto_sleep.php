<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Sistema DIRSU</title>
    <!-- Favicon -->
    <link href="../../dust/img/dirsu_ico_128_128.ico" rel="icon">

  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../../plogins/fontawesome-free/css/all.min.css">
    <!-- Select2 -->
  <link rel="stylesheet" href="../../plogins/select2/css/select2.min.css">
  <link rel="stylesheet" href="../../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <!-- Bootstrap4 Duallistbox -->
  <link rel="stylesheet" href="../../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
    <!-- BS Stepper -->
  <link rel="stylesheet" href="../../plogins/bs-stepper/css/bs-stepper.min.css">
    <!-- Theme style -->
  <link rel="stylesheet" href="../../dust/css/adminlte.min.css">
</head>

<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <!-- Navbar -->
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <!-- Left navbar links -->
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>

    <!-- Right navbar links -->
    <ul class="navbar-nav ml-auto">
      <!-- Messages Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-comments"></i>
          <span class="badge badge-danger navbar-badge">3</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dust/img/user1-128x128.jpg" alt="User Avatar" class="img-size-50 mr-3 img-circle">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Brad Diesel
                  <span class="float-right text-sm text-danger"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">Call me whenever you can...</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dust/img/user8-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  John Pierce
                  <span class="float-right text-sm text-muted"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">I got your message bro</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <!-- Message Start -->
            <div class="media">
              <img src="dust/img/user3-128x128.jpg" alt="User Avatar" class="img-size-50 img-circle mr-3">
              <div class="media-body">
                <h3 class="dropdown-item-title">
                  Nora Silvester
                  <span class="float-right text-sm text-warning"><i class="fas fa-star"></i></span>
                </h3>
                <p class="text-sm">The subject goes here</p>
                <p class="text-sm text-muted"><i class="far fa-clock mr-1"></i> 4 Hours Ago</p>
              </div>
            </div>
            <!-- Message End -->
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Messages</a>
        </div>
      </li>
      <!-- Notifications Dropdown Menu -->
      <li class="nav-item dropdown">
        <a class="nav-link" data-toggle="dropdown" href="#">
          <i class="far fa-bell"></i>
          <span class="badge badge-warning navbar-badge">15</span>
        </a>
        <div class="dropdown-menu dropdown-menu-lg dropdown-menu-right">
          <span class="dropdown-item dropdown-header">15 Notifications</span>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-envelope mr-2"></i> 4 new messages
            <span class="float-right text-muted text-sm">3 mins</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-users mr-2"></i> 8 friend requests
            <span class="float-right text-muted text-sm">12 hours</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item">
            <i class="fas fa-file mr-2"></i> 3 new reports
            <span class="float-right text-muted text-sm">2 days</span>
          </a>
          <div class="dropdown-divider"></div>
          <a href="#" class="dropdown-item dropdown-footer">See All Notifications</a>
        </div>
      </li>
      <li class="nav-item">
        <a class="nav-link" data-widget="fullscreen" href="#" role="button">
          <i class="fas fa-expand-arrows-alt"></i>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block" style="background-image: url('web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
        <a href="https://gla.pe/demo/" class="nav-link" target="_blank"><p style="color: white;
        size: 8px">Ir a página DIRSU</p></a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="#" class="nav-link">Cerrar sesión</a>
      </li>
    </ul>
  </nav>
  <!-- /.navbar -->
  <!-- /.navbar -->

  <!-- Main Sidebar Container -->
  <!-- Main Sidebar Container --><!-- Contenedor de barra lateral principal -->
  <aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="." class="brand-link">
      <img src="../../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
      <span class="brand-text font-weight-light">Sistema DIRSU</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
      <!-- Sidebar user panel (optional) -->
      <div class="user-panel mt-3 pb-3 mb-3 d-flex">
        <div class="image">
          <img src="../../dust/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
        </div>
        <div class="info">
          <a href="#" class="d-block">Luigi Villanueva Perez</a>
        </div>
      </div>

      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Búsqueda" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>

      <!-- Sidebar Menu -->
      <nav class="mt-2">
        <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
          <!-- Add icons to the links using the .nav-icon class
               with font-awesome or any other icon font library -->
          <li class="nav-item">
            <a href="." class="nav-link active">
              <i class="nav-icon fas fa-home"></i>
              <p>
                INICIO
                <span class="right badge badge-danger">2</span>
              </p>
            </a>
          </li>
          
          <!-- INICIO - SUB MENU MI PROYECTO - NIVEL 1 -->
          <!-- Texto simple dentro de una side bar, sin enlaces  -->
          <li class="nav-header">Información de proyecto</li>
          <li class="nav-item">
                <a href="paginas/equipo.php" class="nav-link">
                  <i class="fa fa-users nav-icon"></i>
                  <p>Mi equipo</p>
                </a>
              </li>
          <li class="nav-item">
                <a href="paginas/ruta.php" class="nav-link">
                  <i class="fa fa-road nav-icon"></i>
                  <p>Ruta de trabajo</p>
                </a>
              </li>
          <li class="nav-item">
                <a href="paginas/formatos.php" class="nav-link">
                  <i class="fa fa-file-word nav-icon"></i>
                  <p>Formatos</p>
                </a>
              </li>
          
          <!-- FIN - SUB MENU MI PROYECTO - NIVEL 1 -->
          <!-- Texto simple dentro de una side bar, sin enlaces  -->
          <li class="nav-header">Fases de proyecto</li>
          <!-- INICIO - SUB MENU FASE 1 - NIVEL 1 -->
          <li class="nav-item menu">
            
            <a href="#" class="nav-link">
              <p>
                1. Formulación y presentación
                <!-- <i class="right fas fa-angle-left"></i> -->
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="https://gla.pe/sistema_web/paginas/formularios/registro_proyecto.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>1.1. Registro de proyectos</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="paginas/equipo_proyecto.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>1.2. Equipo de proyecto</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="paginas/informe_proyecto.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>1.3. Informe de proyecto</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="paginas/revision_informe.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>1.4. Revisión de informe</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- FIN - SUB MENU FASE 1 - NIVEL 1 -->
          <!-- INICIO - SUB MENU FASE 2 - NIVEL 1 -->
          <li class="nav-item menu">
            
            <a href="#" class="nav-link">
              <p>
                2. Ejecución y monitoreo
                <!-- <i class="right fas fa-angle-left"></i> -->
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="paginas/cronograma_ejecucion.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>2.1. Cronograma de ejecución</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="paginas/revision_cronograma.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>2.2. Revisión de cronograma</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- FIN - SUB MENU FASE 2 - NIVEL 1 -->
          <!-- INICIO - SUB MENU FASE 3 - NIVEL 1 -->
          <li class="nav-item menu">
            
            <a href="#" class="nav-link">
              <p>
                3. Evaluación e informe
                <!-- <i class="right fas fa-angle-left"></i> -->
              </p>
            </a>
            <ul class="nav nav-treeview">
              <li class="nav-item">
                <a href="paginas/informe_final.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>3.1. Informe final</p>
                </a>
              </li>
              <li class="nav-item">
                <a href="paginas/revision_informe_final.php" class="nav-link">
                  <!-- <i class="far fa-circle nav-icon"></i> -->
                  <p>3.2. Revisión de informe</p>
                </a>
              </li>
            </ul>
          </li>
          <!-- FIN - SUB MENU FASE 3 - NIVEL 1 -->
        </ul>
      </nav>
      <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
  </aside>

  <!-- Content Wrapper. Contains page content -->
  <div class="content-wrapper">
    <!-- Content Header (Page header) -->
    <div class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-7">
            <h1 class="m-0">1.1. Registra tu proyecto</h1>
          </div><!-- /.col -->
          <div class="col-sm-5">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href=".">Inicio</a></li>
              <li class="breadcrumb-item active">Formulación y presentación</li>
              <li class="breadcrumb-item active">Registro de proyectos</li>
            </ol>
          </div><!-- /.col -->
        </div><!-- /.row -->
      </div><!-- /.container-fluid -->
    </div>

    <!-- Main content -->
    <!-- Main content -->
    <section class="content">
  <div class="container-fluid">
    <!-- Stepper -->
    <div class="row">
      <div class="col-md-12">
        <div class="card card-default">
          <div class="card-header">
            <h6 class="card-title"><b>¡Hola!</b>  Para registrar tu proyecto de Responsabilidad Social Universitaria,responde las 14 preguntas siguientes.<br>
1. <b>Completa cada pregunta</b> con la información solicitada.<br>
2. <b>Presiona "Siguiente"</b> para guardar cada respuesta y avanzar a la siguiente pregunta.<br>
3. <b>Al finalizar todas las preguntas</b>, presiona "Finalizar formulario".<br>
<b>Notas importantes:</b><br>
Puedes <b>retroceder y editar</b> respuestas en cualquier momento antes de presionar "Finalizar formulario".<br>
Cada pregunta incluye un <b>cuadro de ayuda</b> para orientarte.<br>
¡Gracias por tu colaboración!<br></h6>

          </div>
          <div class="card-body p-0">
            <div class="bs-stepper">
              <div class="bs-stepper-header" role="tablist" style="display: flex; flex-wrap: wrap;">
  <div class="step" data-target="#uno-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="uno-part" id="uno-part-trigger">
      <span class="bs-stepper-circle">1</span>
      <!--<span class="bs-stepper-label">uno</span>-->
    </button>
  </div>
  <div class="step" data-target="#dos-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="dos-part" id="dos-part-trigger">
      <span class="bs-stepper-circle">2</span>
      <!--<span class="bs-stepper-label">dos</span>-->
    </button>
  </div>
  <div class="step" data-target="#tres-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="tres-part" id="tres-part-trigger">
      <span class="bs-stepper-circle">3</span>
      <!--<span class="bs-stepper-label">tres</span>-->
    </button>
  </div>
  <div class="step" data-target="#cuatro-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="cuatro-part" id="cuatro-part-trigger">
      <span class="bs-stepper-circle">4</span>
      <!--<span class="bs-stepper-label">cuatro</span>-->
    </button>
  </div>
  <div class="step" data-target="#cinco-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="cinco-part" id="cinco-part-trigger">
      <span class="bs-stepper-circle">5</span>
      <!--<span class="bs-stepper-label">cinco</span>-->
    </button>
  </div>
  <div class="step" data-target="#seis-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="seis-part" id="seis-part-trigger">
      <span class="bs-stepper-circle">6</span>
      <!--<span class="bs-stepper-label">seis</span>-->
    </button>
  </div>
  <div class="step" data-target="#siete-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="siete-part" id="siete-part-trigger">
      <span class="bs-stepper-circle">7</span>
      <!--<span class="bs-stepper-label">siete</span>-->
    </button>
  </div>
  <div class="step" data-target="#ocho-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="ocho-part" id="ocho-part-trigger">
      <span class="bs-stepper-circle">8</span>
      <!--<span class="bs-stepper-label">ocho</span>-->
    </button>
  </div>
  <div class="step" data-target="#nueve-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="nueve-part" id="nueve-part-trigger">
      <span class="bs-stepper-circle">9</span>
      <!--<span class="bs-stepper-label">nueve</span>-->
    </button>
  </div>
  <div class="step" data-target="#diez-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="diez-part" id="diez-part-trigger">
      <span class="bs-stepper-circle">10</span>
      <!--<span class="bs-stepper-label">diez</span>-->
    </button>
  </div>
  <div class="step" data-target="#once-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="once-part" id="once-part-trigger">
      <span class="bs-stepper-circle">11</span>
      <!--<span class="bs-stepper-label">once</span>-->
    </button>
  </div>
  <div class="step" data-target="#doce-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="doce-part" id="doce-part-trigger">
      <span class="bs-stepper-circle">12</span>
      <!--<span class="bs-stepper-label">doce</span>-->
    </button>
  </div>
  <div class="step" data-target="#trece-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="trece-part" id="trece-part-trigger">
      <span class="bs-stepper-circle">13</span>
      <!--<span class="bs-stepper-label">trece</span>-->
    </button>
  </div>
  <div class="step" data-target="#catorce-part">
    <button type="button" class="step-trigger" role="tab" aria-controls="catorce-part" id="catorce-part-trigger">
      <span class="bs-stepper-circle">14</span>
      <!--<span class="bs-stepper-label">catorce</span>-->
    </button>
  </div>
</div>
              <div class="bs-stepper-content">
                <!-- CONTENIDO DE STEPS -->
                <!-- Inicio - Step 1 -->
                <div id="uno-part" class="content" role="tabpanel" aria-labelledby="uno-part-trigger">
                  <!-- Inicio - Título del Programa -->  
                  <div class="form-group">
                    <label for="inputTitulo">Título del Programa</label>
                    <input type="text" class="form-control" id="inputTitulo" placeholder="Ingresa el título del programa">
                  </div>
                  <!-- Fin - Título del Programa -->
                  <!-- Inicio - Título del Proyecto --> 
                  <div class="form-group">
                    <label for="inputTitulo">Título del Proyecto</label>
                    <input type="text" class="form-control" id="inputTitulo" placeholder="Ingresa el título del proyecto">
                  </div>
                  <!-- Fin - Título del Proyecto -->
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <!-- Fin - Step 1 -->
                <!-- Inicio - Step 2 -->
                <div id="dos-part" class="content" role="tabpanel" aria-labelledby="dos-part-trigger">
                <div class="card-body">
            <div class="row">
              
              <!-- /.col -->
              <div class="col-md-6">
                  <label>Objetivo(s) de Desarrollo Sostenible que cumple el proyecto</label>
                  <select class="select2" multiple="multiple" data-placeholder="Selecciona 1 o más ODS ..." data-dropdown-css-class="select2"
                          style="width: 100%;">
                    <option>ODS1: Reducción de los indicadores de la pobreza</option>
                    <option>ODS2: Hambre y seguridad alimentaria</option>
                    <option>ODS3: Salud y bienestar</option>
                    <option>ODS4: Educación de calidad</option>
                    <option>ODS5: Igualdad de género y empoderamiento de la mujer</option>
                    <option>ODS6: Agua limpia y saneamiento</option>
                    <option>ODS7: Energía asequible y no contaminante</option>
                    <option>ODS8: Trabajo decente y crecimiento económico</option>
                    <option>ODS9: Industria, innovación e infraestructura</option>
                    <option>ODS10: Reducir las desigualdades</option>
                    <option>ODS11: Ciudades y comunidades sostenibles</option>
                    <option>ODS12: Producción y consumo responsables</option>
                    <option>ODS13: Acción por el clima</option>
                    <option>ODS14: Vida submarina</option>
                    <option>ODS15: Vida y ecosistemas terrestres</option>
                    <option>ODS16: Paz y justicia e instituciones sólidas</option>
                    <option>ODS17: Alianzas para lograr los objetivos</option>
                  </select>
              </div>
                <!-- /.form-group -->
              <div class="col-md-6">
                  <label>Tipo de proyecto</label>
                  <select class="select2" multiple="multiple" data-placeholder="Elige el tipo de proyecto ..." data-dropdown-css-class="select2"
                          style="width: 100%;">
                    <option>1. Programas de formación continua y formación de capacidades</option>
                    <option>2. Consultoría/asesoría</option>
                    <option>3. Gestión cultural</option>
                    <option>4. Desarrollo económico y social</option>
                    <option>5. Desarrollo humano y democracia</option>
                    <option>6. Desarrollo técnico científico sostenible</option>
                    <option>7. Protección del medio ambiente</option>
                    <option>8. Innovación</option>
                    <option>9. Creatividad</option>
                    <option>10. Otras áreas de acuerdo a las necesidades de la comunidad donde se va adesarrollar el proyecto</option>
                    <option>11. Salud</option>
                  </select>
              </div>
                <!-- /.form-group -->
              <!-- /.col -->
            </div>
            <!-- /.row -->
          </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <!-- Fin - Step 2 -->
                <div id="tres-part" class="content" role="tabpanel" aria-labelledby="tres-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Grupo(s) de Interés al que está orientado </label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Necesidades de los Grupo(s) de Interés  </label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <!-- -->
                <div id="cuatro-part" class="content" role="tabpanel" aria-labelledby="cuatro-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Institución participante</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Población participante</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Niños</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Jóvenes</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Adultos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Anterior</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Siguiente</button>
                </div>
                <div id="cinco-part" class="content" role="tabpanel" aria-labelledby="cinco-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Lugar de ejecución</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Duración de Proyecto</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="seis-part" class="content" role="tabpanel" aria-labelledby="seis-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Nivel disciplinar</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <div class="form-group">
                    <label for="apellidos">Cronograma</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="siete-part" class="content" role="tabpanel" aria-labelledby="siete-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="ocho-part" class="content" role="tabpanel" aria-labelledby="ocho-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="nueve-part" class="content" role="tabpanel" aria-labelledby="nueve-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="diez-part" class="content" role="tabpanel" aria-labelledby="diez-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="once-part" class="content" role="tabpanel" aria-labelledby="once-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="doce-part" class="content" role="tabpanel" aria-labelledby="doce-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <div id="trece-part" class="content" role="tabpanel" aria-labelledby="trece-part-trigger">
                  <div class="form-group">
                    <label for="apellidos">Apellidos</label>
                    <input type="text" class="form-control" id="apellidos" placeholder="Ingresa tus apellidos">
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button class="btn btn-primary" onclick="stepper.next()">Next</button>
                </div>
                <!-- -->
                <div id="catorce-part" class="content" role="tabpanel" aria-labelledby="catorce-part-trigger">
                  <div class="form-group">
                    <label for="exampleInputFile">File input</label>
                    <div class="input-group">
                      <div class="custom-file">
                        <input type="file" class="custom-file-input" id="exampleInputFile">
                        <label class="custom-file-label" for="exampleInputFile">Choose file</label>
                      </div>
                      <div class="input-group-append">
                        <span class="input-group-text">Upload</span>
                      </div>
                    </div>
                  </div>
                  <button class="btn btn-primary" onclick="stepper.previous()">Previous</button>
                  <button type="submit" class="btn btn-primary">Submit</button>
                </div>
              </div>
            </div>
          </div>
          <!-- /.card-body -->
          <div class="card-footer">
            Visit <a href="https://github.com/Johann-S/bs-stepper/#how-to-use-it">bs-stepper documentation</a> for more examples and information about the plugin.
          </div>
        </div>
        <!-- /.card -->
      </div>
    </div>
    <!-- /.row -->
  </div>
  <!-- /.container-fluid -->
</section>
    <!-- /.content -->
  </div>
  <!-- /.content-wrapper -->
  <footer class="main-footer">
    <div class="float-right d-none d-sm-block">
      <b>Version</b> 3.1.0
    </div>
    <strong>Copyright &copy; 2014-2021 <a href="https://adminlte.io">AdminLTE.io</a>.</strong> All rights reserved.
  </footer>

  <!-- Control Sidebar -->
  <aside class="control-sidebar control-sidebar-dark">
    <!-- Control sidebar content goes here -->
  </aside>
  <!-- /.control-sidebar -->
</div>
<!-- ./wrapper -->
<!-- jQuery -->
<script src="../../plogins/jquery/jquery.min.js"></script>
<!-- Bootstrap 4 -->
<script src="../../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<!-- Select2 -->
<script src="../../plogins/select2/js/select2.full.min.js"></script>
<!-- BS-Stepper -->
<script src="../../plogins/bs-stepper/js/bs-stepper.min.js"></script>
<!-- AdminLTE App -->
<script src="../../dust/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="../../dust/js/demo.js"></script>
<!-- Page specific script -->
<script>
  $(function () {
    //Initialize Select2 Elements
    $('.select2').select2()

    //Initialize Select2 Elements
    $('.select2bs4').select2({
      theme: 'bootstrap4'
    })

    //Datemask dd/mm/yyyy
    $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
    //Datemask2 mm/dd/yyyy
    $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
    //Money Euro
    $('[data-mask]').inputmask()

    //Date picker
    $('#reservationdate').datetimepicker({
        format: 'L'
    });

    //Date and time picker
    $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });

    //Date range picker
    $('#reservation').daterangepicker()
    //Date range picker with time picker
    $('#reservationtime').daterangepicker({
      timePicker: true,
      timePickerIncrement: 30,
      locale: {
        format: 'MM/DD/YYYY hh:mm A'
      }
    })
    //Date range as a button
    $('#daterange-btn').daterangepicker(
      {
        ranges   : {
          'Today'       : [moment(), moment()],
          'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
          'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
          'Last 30 Days': [moment().subtract(29, 'days'), moment()],
          'This Month'  : [moment().startOf('month'), moment().endOf('month')],
          'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
        },
        startDate: moment().subtract(29, 'days'),
        endDate  : moment()
      },
      function (start, end) {
        $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
      }
    )

    //Timepicker
    $('#timepicker').datetimepicker({
      format: 'LT'
    })

    //Bootstrap Duallistbox
    $('.duallistbox').bootstrapDualListbox()

    //Colorpicker
    $('.my-colorpicker1').colorpicker()
    //color picker with addon
    $('.my-colorpicker2').colorpicker()

    $('.my-colorpicker2').on('colorpickerChange', function(event) {
      $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
    })

    $("input[data-bootstrap-switch]").each(function(){
      $(this).bootstrapSwitch('state', $(this).prop('checked'));
    })

  })
  // BS-Stepper Init
  document.addEventListener('DOMContentLoaded', function () {
    window.stepper = new Stepper(document.querySelector('.bs-stepper'))
  })
</script>
</body>
</html>
