<?php include "../componentes/configSesion.php"; ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Registro de Proyecto - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- daterange picker -->
      <link rel="stylesheet" href="../plogins/daterangepicker/daterangepicker.css">
      <!-- Tempusdominus Bootstrap 4 -->
      <link rel="stylesheet" href="../plogins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
      <!-- Select2 -->
      <link rel="stylesheet" href="../plogins/select2/css/select2.min.css">
      <link rel="stylesheet" href="../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
      <!-- BS Stepper -->
      <link rel="stylesheet" href="../plogins/bs-stepper/css/bs-stepper.min.css">
      <!-- summernote -->
      <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
   </head>
   <body class="hold-transition sidebar-mini layout-fixed">
      <div class="wrapper">
         <!-- Preloader --><!-- Icono que se muestra mientras está cargando el sistema -->
         <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTELogo" height="60" width="60">
         </div>
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
               <li class="nav-item">
                  <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                  <i class="fas fa-expand-arrows-alt"></i>
                  </a>
               </li>
               <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
                     <p style="color: white;
                        size: 8px">Ir a página DIRSU</p>
                  </a>
               </li>
               </li>
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a> 
               </li>
            </ul>
         </nav>
         <!-- /.navbar -->
         <!-- Main Sidebar Container -->
         <!-- Contenedor de barra lateral principal -->
         <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="." class="brand-link">
            <img src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Sistema DIRSU</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
               <!-- Sidebar user panel (optional) -->
               <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                  <div class="image">
                     <img src="../dust/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                  </div>
                  <div class="info">
                     <a href="perfil.php" class="d-block"><?php echo htmlspecialchars($nombres) . " " . htmlspecialchars($apellidos); ?></a>
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
                        <a href="../inicio.php" class="nav-link">
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
                        <a href="proyecto.php" class="nav-link">
                           <i class="fa fa-users nav-icon"></i>
                           <p>Mi proyecto</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="ruta.php" class="nav-link">
                           <i class="fa fa-road nav-icon"></i>
                           <p>Ruta de trabajo</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="formato.php" class="nav-link">
                           <i class="fa fa-file-word nav-icon"></i>
                           <p>Formatos</p>
                        </a>
                     </li>
                     <!-- FIN - SUB MENU MI PROYECTO - NIVEL 1 -->
                     <!-- Texto simple dentro de una side bar, sin enlaces  -->
                     <li class="nav-header">Fases de proyecto</li>
                     <!-- INICIO - SUB MENU FASE 1 - NIVEL 1 -->
                     <li class="nav-item menu menu-open">
                        <a href="#" class="nav-link active">
                           <p>
                              1. Formulación y presentación
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview active">
                           <li class="nav-item active">
                              <a href="registro_proyecto.php" class="nav-link active">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.1. Registro de proyectos</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="equipo_proyecto.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.2. Equipo de proyecto</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="informe_proyecto.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.3. Informe de proyecto</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_informe.php" class="nav-link">
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
                     </div>
                     <!-- /.col -->
                     <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                           <li class="breadcrumb-item"><a href=".">Inicio</a></li>
                           <li class="breadcrumb-item active">Formulación y presentación</li>
                           <li class="breadcrumb-item active">Registro de proyectos</li>
                        </ol>
                     </div>
                     <!-- /.col -->
                  </div>
                  <!-- /.row -->
               </div>
               <!-- /.container-fluid -->
            </div>
            <!-- Main content -->
            <!-- Main content -->
            <section class="content">
               <div class="container-fluid">
                  <!-- Stepper -->
                  <div class="row">
                     <div class="col-md-12">
                        <?php if ($id_py ==0) { ?>
                              <?php

// Incluir otro archivo PHP
include '../integrados/mensaje_registrar_py.php'; // Aquí se inserta el contenido de 'otro_archivo.php'

?>
                              <?php } ?> 
                         
                         
                         <?php if ($id_py !=0) { ?>
                        <div class="card card-default">
                           <div class="card-body p-0">
                              
                              
                              <div class="card-header">
                                 <h6 class="card-title"><b>¡Hola!</b> Para registrar tu proyecto de Responsabilidad Social Universitaria, responde las 14 preguntas siguientes.<br>
                                    1. <b>Completa cada pregunta</b> con la información solicitada.<br>
                                    2. <b>Presiona "Siguiente"</b> para guardar cada respuesta y avanzar a la siguiente pregunta.<br>
                                    3. <b>Al finalizar todas las preguntas</b>, presiona "Finalizar formulario".<br>
                                    <b>Notas importantes:</b><br>
                                    Puedes <b>retroceder y editar</b> respuestas en cualquier momento antes de presionar "Finalizar formulario".<br>
                                    Cada pregunta incluye un <b>cuadro de ayuda</b> para orientarte.<br>
                                    ¡Gracias por tu colaboración!<br>
                                 </h6>
                              </div>
                              <form action="../componentes/formulario/validarFormulario.php" method="post">
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
                                             <!--<span class="bs-stepper-label">dos</span>-->
                                          </button>
                                       </div>
                                       <div class="step" data-target="#cuatro-part">
                                          <button type="button" class="step-trigger" role="tab" aria-controls="cuatro-part" id="cuatro-part-trigger">
                                             <span class="bs-stepper-circle">4</span>
                                             <!--<span class="bs-stepper-label">dos</span>-->
                                          </button>
                                       </div>
                                       <div class="step" data-target="#cinco-part">
                                          <button type="button" class="step-trigger" role="tab" aria-controls="cinco-part" id="cinco-part-trigger">
                                             <span class="bs-stepper-circle">5</span>
                                             <!--<span class="bs-stepper-label">dos</span>-->
                                          </button>
                                       </div>
                                       <div class="step" data-target="#catorce-part">
                                          <button type="button" class="step-trigger" role="tab" aria-controls="catorce-part" id="catorce-part-trigger">
                                             <span class="bs-stepper-circle">6</span>
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
                                             <label for="inputTituloPrograma">1. Título del Programa</label>
                                             <input type="text" class="form-control" id="p1" name="titulo_programa" placeholder="Ingresa el título del programa" required>
                                          </div>
                                          <!-- Fin - Título del Programa -->
                                          <!-- Inicio - Título del Proyecto -->
                                          <div class="form-group">
                                             <label for="inputTituloProyecto">2. Título del Proyecto</label>
                                             <input type="text" class="form-control" id="p2" name="titulo_proyecto" placeholder="Ingresa el título del proyecto" required>
                                          </div>
                                          <!-- Fin - Título del Proyecto -->
                                          <div class="row">
                                             <div class="col-md-6">
                                                <label for="ods">3. Objetivo(s) de Desarrollo Sostenible que cumple el proyecto</label>
                                                <select class="select2" multiple="multiple" data-placeholder="Selecciona 1 o más ODS ..." name="ods[]" id="p3" data-dropdown-css-class="select2" style="width: 100%;">
                                                   <option value="1">ODS1: Reducción de los indicadores de la pobreza</option>
                                                   <option value="2">ODS2: Hambre y seguridad alimentaria</option>
                                                   <option value="3">ODS3: Salud y bienestar</option>
                                                   <option value="4">ODS4: Educación de calidad</option>
                                                   <option value="5">ODS5: Igualdad de género y empoderamiento de la mujer</option>
                                                   <option value="6">ODS6: Agua limpia y saneamiento</option>
                                                   <option value="7">ODS7: Energía asequible y no contaminante</option>
                                                   <option value="8">ODS8: Trabajo decente y crecimiento económico</option>
                                                   <option value="9">ODS9: In../dustria, innovación e infraestructura</option>
                                                   <option value="10">ODS10: Reducir las desigualdades</option>
                                                   <option value="11">ODS11: Ciudades y comunidades sostenibles</option>
                                                   <option value="12">ODS12: Producción y consumo responsables</option>
                                                   <option value="13">ODS13: Acción por el clima</option>
                                                   <option value="14">ODS14: Vida submarina</option>
                                                   <option value="15">ODS15: Vida y ecosistemas terrestres</option>
                                                   <option value="16">ODS16: Paz y justicia e instituciones sólidas</option>
                                                   <option value="17">ODS17: Alianzas para lograr los objetivos</option>
                                                </select>
                                             </div>
                                             <div class="col-md-6">
                                                <label>4. Tipo de proyecto</label>
                                                <select class="select2" multiple="multiple" data-placeholder="Elige el tipo de proyecto ..." name="tipo_proyecto[]" id="p4" data-dropdown-css-class="select2" style="width: 100%;">
                                                   <option value="1">1. Programas de formación continua y formación de capacidades</option>
                                                   <option value="2">2. Consultoría/asesoría</option>
                                                   <option value="3">3. Gestión cultural</option>
                                                   <option value="4">4. Desarrollo económico y social</option>
                                                   <option value="5">5. Desarrollo humano y democracia</option>
                                                   <option value="6">6. Desarrollo técnico científico sostenible</option>
                                                   <option value="7">7. Protección del medio ambiente</option>
                                                   <option value="8">8. Innovación</option>
                                                   <option value="9">9. Creatividad</option>
                                                   <option value="10">10. Otras áreas de acuerdo a las necesidades de la comunidad donde se va adesarrollar el proyecto</option>
                                                   <option>11. Salud</option>
                                                </select>
                                             </div>
                                          </div>
                                          <br>
                                          <!-- Fin - Título del Proyecto -->
                                          <button class="btn btn-primary" type="button" onclick="stepper.next()">Siguiente</button>
                                       </div>
                                       <!-- Fin - Step 1 -->
                                       <!-- Inicio - Step 2 -->
                                       <div id="dos-part" class="content" role="tabpanel" aria-labelledby="dos-part-trigger">
                                          <div class="form-group">
                                             <label for="inputInteres">5 y 6. Grupos de interés, sus representantes y sus necesidades orientadas al proyecto (identificado por el equipo de docentes del proyecto) </label>
                                             <div class="card-body">
                                                <textarea id="summernote" name="grupoInteres">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <p></p><table class="table table-bordered" style="width: 1200px;"><tbody><tr><td style="padding-left: 1.5rem;"><span style="font-family: &quot;Arial Black&quot;;"><span style="font-weight: bolder;">N°</span></span></td><td><span style="font-family: &quot;Arial Black&quot;;"><span style="font-weight: bolder;">PARTE INTERESADAS</span></span></td><td><span style="font-weight: bolder;"><span style="font-family: &quot;Arial Black&quot;;">REPRESENTANTES</span></span></td><td style="padding-right: 1.5rem;"><span style="font-family: &quot;Arial Black&quot;;"><span style="font-weight: bolder;">NECESIDADES</span></span></td></tr><tr><td style="padding-left: 1.5rem;"><b><span style="font-family: &quot;Times New Roman&quot;;">1</span></b></td><td><font color="#000000" style="background-color: rgb(255, 255, 0);"><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></font></td><td><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></td><td style="padding-right: 1.5rem;"><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></td></tr><tr><td style="padding-left: 1.5rem;"><b><span style="font-family: &quot;Times New Roman&quot;;">2</span></b></td><td><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></td><td><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></td><td style="padding-right: 1.5rem;"><p><span style="font-family: &quot;Times New Roman&quot;;">Puedes editar o agregar filas y columnas</span></p></td></tr></tbody></table>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <button class="btn btn-primary" type="button" onclick="stepper.previous()">Anterior</button>
                                          <button class="btn btn-primary" type="button" onclick="stepper.next()">Siguiente</button>
                                       </div>
                                       <div id="tres-part" class="content" role="tabpanel" aria-labelledby="tres-part-trigger">
                                          <!-- Inicio - Institución participante -->
                                          <div class="form-group">
                                             <label for="inputInstitucionParticipante">7. Instituciones con las que el proyecto interactúa</label> <br> 
                                             <h6 for="inputInstitucionParticipante">7.1. Institución participante</h6>
                                             <input type="text" class="form-control" id="p7_1" name="institucion_participante" placeholder="Ingresa el nombre de la institución participante">
                                          </div>
                                          <!-- Fin - Institución participante -->
                                          <!-- Inicio - Población participante -->
                                          <div class="form-group">
                                             <h6 for="inputPoblacionParticipante">7.2. Población participante</h6>
                                             <input type="text" class="form-control" id="p7_2" name="poblacion_participante" placeholder="Describe a la población participante">
                                          </div>
                                          <!-- Fin - Población participante -->
                                          <!-- Inicio - Población Niños -->
                                          <h6 for="inputParticipantesEtario">Número de participantes (por grupos etarios)</h6>
                                          <div class="row">
                                             <div class="col-md-3">
                                                <h6 for="inputNinos">Niños (0 - 12 años)</h6>
                                                <input type="number" class="form-control" id="ninos" name="poblacion_ninos" min="0" max="10000" step="1" value="0" required>
                                             </div>
                                             <!-- Inicio - Población Adolescentes -->
                                             <div class="col-md-3">
                                                <h6 for="inputJovenes">Jóvenes (13 - 17 años)</h6>
                                                <input type="number" class="form-control" id="jovenes" name="poblacion_jovenes" min="0" max="10000" step="1" value="0" required>
                                             </div>
                                             <!-- Inicio - Población Adultos -->
                                             <div class="col-md-3">
                                                <h6 for="inputAdultos">Adultos (18 a más)</h6>
                                                <input type="number" class="form-control" id="adultos" name="poblacion_adultos" min="0" max="10000" step="1" value="0" required>
                                             </div>
                                          </div>
                                          <br>
                                          <button class="btn btn-primary" type="button" onclick="stepper.previous()">Anterior</button>
                                          <button class="btn btn-primary" type="button" onclick="stepper.next()">Siguiente</button>
                                       </div>
                                       <!-- INICIO apartado 4 -->
                                       <div id="cuatro-part" class="content" role="tabpanel" aria-labelledby="cuatro-part-trigger">
                                          <!-- Inicio - Institución participante -->
                                          <div class="form-group">
                                             <label for="inputLugarEjecucion">8. Lugar de ejecución</label> <br> 
                                             <div class="row">
                                                <div class="col-md-2">
                                                   <h6 for="inputSectorBarrio">Sector / Barrio</h6>
                                                   <input type="text" class="form-control" id="sector" name="sector" placeholder="Ingresa el Sector/Barrio" value= "--" required>
                                                </div>
                                                <div class="col-md-2">
                                                   <h6 for="inputCaserio">Caserío</h6>
                                                   <input type="text" class="form-control" id="caserio" name="caserio" placeholder="Ingresa el Caserío" value= "--" required>
                                                </div>
                                                <div class="col-md-2">
                                                   <h6 for="inputDistrito">Distrito</h6>
                                                   <input type="text" class="form-control" id="distrito" name="distrito" placeholder="Ingresa el Distrito" value= "--" required>
                                                </div>
                                                <div class="col-md-2">
                                                   <h6 for="inputProvincia">Provincia</h6>
                                                   <input type="text" class="form-control" id="provincia" name="provincia" placeholder="Ingresa el Provincia" value= "--" required>
                                                </div>
                                                <div class="col-md-2">
                                                   <h6 for="inputRegion">Departamento</h6>
                                                   <input type="text" class="form-control" id="region" name="region" placeholder="Ingresa el Región" value= "--" required>   
                                                </div>
                                                <div>
                                                   <br>
                                                   <label for="inputDuracion">9. Duración del proyecto</label>        
                                                   <div class="row">
                                                      <div class="col-md-6">
                                                         <h6>9.1. Fecha de inicio del proyecto</h6>
                                                         <div class="input-group date" id="startdate" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input" name="fecha_inicio" data-target="#startdate" required>
                                                            <div class="input-group-append" data-target="#startdate" data-toggle="datetimepicker">
                                                               <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                      <div class="col-md-6">
                                                         <h6>9.2. Fecha de fin del proyecto</h6>
                                                         <div class="input-group date" id="enddate" data-target-input="nearest">
                                                            <input type="text" class="form-control datetimepicker-input" name="fecha_fin" data-target="#enddate" required>
                                                            <div class="input-group-append" data-target="#enddate" data-toggle="datetimepicker">
                                                               <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                            </div>
                                                         </div>
                                                      </div>
                                                   </div>
                                                </div>
                                             </div>
                                             <br>
                                             <button class="btn btn-primary" type="button" onclick="stepper.previous()">Anterior</button>
                                             <button class="btn btn-primary" type="button" onclick="stepper.next()">Siguiente</button>
                                          </div>
                                       </div>
                                       <!-- FIN apartado 4 -->
                                       <!-- INICIO apartado 5 -->
                                       <div id="cinco-part" class="content" role="tabpanel" aria-labelledby="cinco-part-trigger">
  <!-- Inicio - Institución participante -->
  <div class="form-group">
      <label>10. Fases del proyecto</label>
    <!-- Tabla -->
<table class="table">
  <thead>
    <tr>
      <th>Fases</th>
      <th>Descripción</th>
      <th>N° Semanas</th>
      <th>N° horas por semanas</th>
      <th>Total de horas</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Planificación</td>
      <td><input type="text" class="form-control" id="p10_1" name="planificacion" placeholder="Ingresar descripción"></td>
      <td><input type="number" class="form-control" id="p10_1s" name="p10_1s" min="0" max="10000" step="1" value="0" oninput="updateTotal(1)"></td>
      <td><input type="number" class="form-control" id="p10_1h" name="p10_1h" min="0" max="10000" step="1" value="0" oninput="updateTotal(1)"></td>
      <td><input type="text" class="form-control" id="total1" value="0" readonly></td>
    </tr>
    <tr>
      <td>Ejecución</td>
      <td><input type="text" class="form-control" id="p10_2" name="ejecucion" placeholder="Ingresar descripción"></td>
      <td><input type="number" class="form-control" id="p10_2s" name="p10_2s" min="0" max="10000" step="1" value="0" oninput="updateTotal(2)"></td>
      <td><input type="number" class="form-control" id="p10_2h" name="p10_2h" min="0" max="10000" step="1" value="0" oninput="updateTotal(2)"></td>
      <td><input type="text" class="form-control" id="total2" value="0" readonly></td>
    </tr>
    <tr>
      <td>Monitoreo y Evaluación</td>
      <td><input type="text" class="form-control" id="p10_3" name="monitoreo" placeholder="Ingresar descripción"></td>
      <td><input type="number" class="form-control" id="p10_3s" name="p10_3s" min="0" max="10000" step="1" value="0" oninput="updateTotal(3)"></td>
      <td><input type="number" class="form-control" id="p10_3h" name="p10_3h" min="0" max="10000" step="1" value="0" oninput="updateTotal(3)"></td>
      <td><input type="text" class="form-control" id="total3" value="0" readonly></td>
    </tr>
  </tbody>
  <tfoot>
    <tr>
      <td colspan="4"><strong>Total</strong></td>
      <td><input type="text" class="form-control" id="grandTotal" value="0" readonly></td>
    </tr>
  </tfoot>
</table>
    
    <!-- Botones de navegación -->
    <button class="btn btn-primary" type="button" onclick="stepper.previous()">Anterior</button>
    <button class="btn btn-primary" type="button" onclick="stepper.next()">Siguiente</button>
  </div>
</div>

                                       <!-- FIN apartado 5 -->
                                       <div id="catorce-part" class="content" role="tabpanel" aria-labelledby="catorce-part-trigger">
  <div class="form-group">
    <label>11. Nivel disciplinar</label>
    <div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="disciplinar" id="disciplinarios" value="1">
        <label class="form-check-label" for="disciplinarios">Disciplinar</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="disciplinar" id="interdisciplinarios" value="2">
        <label class="form-check-label" for="interdisciplinarios">Interdisciplinar</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="radio" name="disciplinar" id="interfacultativos" value="3">
        <label class="form-check-label" for="interfacultativos">Interfacultativo</label>
      </div>
    </div>
  </div>
  <div class="form-group">
    <label for="inputOtro">12. Unidad ejecutora</label>
    <div class="row">
    <div class="col-md-4">
    <h6>12.1. Facultad</h6>
    <select class="custom-select" name="facultad">
                          <option value="">Seleccione una opción</option>
                          <option value="1">Ciencias Agropecuarias</option>
                          <option value="2">Ciencias Biológicas</option>
                          <option value="3">Ciencias Económicas</option>
                          <option value="4">Ciencias Físicas y Matemáticas</option>
                          <option value="5">Ciencias Sociales</option>
                          <option value="6">Derecho y Ciencias Políticas</option>
                          <option value="7">Educación y Ciencias de la Comunicación</option>
                          <option value="8">Enfermería</option>
                          <option value="9">Estomatología</option>
                          <option value="10">Farmacia y Bioquímica</option>
                          <option value="12">Ingeniería</option>
                          <option value="13">Ingeniería Química</option>
                          <option value="14">Medicina</option>
                        </select>
    </div>
    <br>
    <div class="col-md-4">
    <h6>12.2. Programa de estudios</h6>
    <select class="custom-select" name="programa_estudios">
                          <option value="">Seleccione una opción</option>
                                    <option value="1">Administración</option>
                                    <option value="2">Agronomía</option>
                                    <option value="3">Antropología</option>
                                    <option value="4">Arqueología</option>
                                    <option value="5">Arquitectura y Urbanismo</option>
                                    <option value="6">Biología Pesquera</option>
                                    <option value="7">Ciencias Biológicas</option>
                                    <option value="8">Ciencias de la Comunicación</option>
                                    <option value="9">Ciencias Políticas y Gobernabilidad</option>
                                    <option value="10">Contabilidad y Finanzas</option>
                                    <option value="11">Derecho</option>
                                    <option value="12">Economía</option>
                                    <option value="13">Educación Inicial</option>
                                    <option value="14">Educación Primaria</option>
                                    <option value="15">Educación Secundaria Mención Ciencias Naturales</option>
                                    <option value="16">Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales</option>
                                    <option value="17">Educación Secundaria Mención Historia y Geografía</option>
                                    <option value="18">Educación Secundaria Mención Idiomas</option>
                                    <option value="19">Educación Secundaria Mención Lengua y Literatura</option>
                                    <option value="20">Educación Secundaria Mención Matemáticas</option>
                                    <option value="21">Enfermería</option>
                                    <option value="22">Estadística</option>
                                    <option value="23">Estomatología</option>
                                    <option value="24">Farmacia y Bioquímica</option>
                                    <option value="25">Física</option>
                                    <option value="26">Historia</option>
                                    <option value="27">Informática</option>
                                    <option value="28">Ingeniería Agrícola</option>
                                    <option value="29">Ingeniería Agroindustrial</option>
                                    <option value="30">Ingeniería Ambiental</option>
                                    <option value="31">Ingeniería Civil</option>
                                    <option value="32">Ingeniería de Materiales</option>
                                    <option value="33">Ingeniería de Minas</option>
                                    <option value="34">Ingeniería de Sistemas</option>
                                    <option value="35">Ingeniería Industrial</option>
                                    <option value="36">Ingeniería Mecánica</option>
                                    <option value="37">Ingeniería Mecatrónica</option>
                                    <option value="38">Ingeniería Metalúrgica</option>
                                    <option value="39">Ingeniería Química</option>
                                    <option value="40">Matemáticas</option>
                                    <option value="41">Medicina</option>
                                    <option value="42">Microbiología y Parasitología</option>
                                    <option value="43">Trabajo Social</option>
                                    <option value="44">Turismo</option>
                                    <option value="45">Zootecnia</option>
                        </select>
    </div>
    <br>
    <div class="col-md-4">
    <h6>12.3. Departamento académico</h6>
    <select class="custom-select" name="departamento_academico">
    <option value="">Seleccione una opción</option>
    <option value="1">Agronomía y Zootecnia</option>
    <option value="2">Ciencias Agroindustriales</option>
    <option value="3">Ciencias Biológicas</option>
    <option value="4">Microbiología y Parasitología</option>
    <option value="5">Pesquería</option>
    <option value="6">Química Biológica y Fisiología Animal</option>
    <option value="7">Administración</option>
    <option value="8">Contabilidad y Finanzas</option>
    <option value="9">Economía</option>
    <option value="10">Ciencias Básicas Estomatológicas</option>
    <option value="11">Estomatología</option>
    <option value="12">Estadística</option>
    <option value="13">Física</option>
    <option value="14">Informática</option>
    <option value="15">Matemáticas</option>
    <option value="16">Arqueología y Antropología</option>
    <option value="17">Ciencias Sociales</option>
    <option value="18">Ciencias Jurídicas Públicas y Políticas</option>
    <option value="19">Ciencias Jurídicas Privadas y Sociales</option>
    <option value="20">Ciencia Política y Gobernabilidad</option>

    <!-- Lista agregada -->
    <option value="21">Ciencias de la Educación</option>
    <option value="22">Ciencias Psicológicas</option>
    <option value="23">Comunicación Social</option>
    <option value="24">Filosofía y Arte</option>
    <option value="25">Historia y Geografía</option>
    <option value="26">Idiomas y Lingüística</option>
    <option value="27">Lengua Nacional y Literatura</option>
    <option value="28">Enfermería de la Mujer, Niño y Adolescente</option>
    <option value="29">Salud del Adulto</option>
    <option value="30">Salud Familiar y Comunitaria</option>
    <option value="31">Farmacotecnia</option>
    <option value="32">Farmacología</option>
    <option value="33">Bioquímica</option>
    <option value="34">Ingeniería Civil, Arquitectura y Urbanismo</option>
    <option value="35">Ingeniería Industrial</option>
    <option value="36">Ingeniería de Materiales</option>
    <option value="37">Mecánica y Energía</option>
    <option value="38">Ingeniería Metalúrgica</option>
    <option value="39">Ingeniería de Minas</option>
    <option value="40">Ingeniería de Sistemas</option>
    <option value="41">Ingeniería Química</option>
    <option value="42">Ingeniería Ambiental</option>
    <option value="43">Química</option>
    <option value="44">Ciencias Básicas Médicas</option>
    <option value="45">Cirugía</option>
    <option value="46">Fisiología Humana</option>
    <option value="47">Ginecología-Obstetricia</option>
    <option value="48">Medicina</option>
    <option value="49">Medicina Preventiva y Salud Pública</option>
    <option value="50">Morfología Humana</option>
    <option value="51">Pediatría</option>
</select>
    </div>
    </div>
  </div>

  <button class="btn btn-primary" type="button" onclick="stepper.previous()">Anterior</button>
  <button type="submit" class="btn btn-primary">Enviar</button>
  
</div>

                                    </div>
                                 </div>
                              </form>
                              
                           </div>
                           
                           <!-- /.card-body -->
                           <div class="card-footer">
                              El siguiente formulario se basa en el <a href="https://github.com/Johann-S/bs-stepper/#how-to-use-it">Formato de registro de proyectos DIRSU</a>
                           </div>
                        </div>
                        <?php } ?>
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
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
               <p>Desarrollado por el <a href="https://adminlte.io"> Área  informática - DIRSU</a></p>
            </div>
         </footer>
         <!-- Control Sidebar -->
         <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
         </aside>
         <!-- /.control-sidebar -->
      </div>
      <!-- ./wrapper -->
      <!-- jQuery -->
      <script src="../plogins/jquery/jquery.min.js"></script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- InputMask -->
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/inputmask/jquery.inputmask.min.js"></script>
      <!-- date-range-picker -->
      <script src="../plogins/daterangepicker/daterangepicker.js"></script>
      <!-- Tempusdominus Bootstrap 4 -->
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
      <!-- Summernote -->
      <script src="../plogins/summernote/summernote-bs4.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
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
            $('#startdate').datetimepicker({
            format: 'DD/MM/YYYY'
            });
            
            $('#enddate').datetimepicker({
            format: 'DD/MM/YYYY'
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
      <script>
         $(function () {
           // Summernote
           $('#summernote').summernote()
         
         })
      </script>
<script>
  function updateTotal(row) {
    // Obtiene los valores de los campos de entrada
    var horasPorSemana = parseFloat(document.getElementById('p10_' + row + 'h').value) || 0;
    var semanas = parseFloat(document.getElementById('p10_' + row + 's').value) || 0;
    
    // Calcula la suma
    var total = horasPorSemana * semanas;
    
    // Actualiza el campo de texto bloqueado para cada fila
    document.getElementById('total' + row).value = total;

    // Actualiza la suma total de todas las filas
    updateGrandTotal();
  }

  function updateGrandTotal() {
    // Suma los valores de total1, total2 y total3
    var total1 = parseFloat(document.getElementById('total1').value) || 0;
    var total2 = parseFloat(document.getElementById('total2').value) || 0;
    var total3 = parseFloat(document.getElementById('total3').value) || 0;
    
    var grandTotal = total1 + total2 + total3;

    // Actualiza el cuadro de texto bloqueado para el total general
    document.getElementById('grandTotal').value = grandTotal;
  }
</script>
   </body>
</html>