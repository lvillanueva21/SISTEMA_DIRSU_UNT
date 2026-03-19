<?php 
include "../componentes/configSesion.php"; 
// Incluir la conexión a la base de datos
include('../componentes/db.php');
// Incluir el archivo que carga los datos del proyecto (se definen variables como $id_py, $estado, $p2, etc.)
include('../componentes/proyecto/cargar_proyecto.php');
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Mi Progreso - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- Select2 -->
      <link rel="stylesheet" href="../plogins/select2/css/select2.min.css">
      <link rel="stylesheet" href="../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
      <!-- Summernote -->
      <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
      <!-- BS Stepper -->
      <link rel="stylesheet" href="../plogins/bs-stepper/css/bs-stepper.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
   </head>
   <body class="hold-transition sidebar-mini layout-fixed">
      <div class="wrapper">
         <!-- Preloader -->
         <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTELogo" height="60" width="60">
         </div>
         <!-- Navbar -->
         <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
               <li class="nav-item">
                  <a class="nav-link" data-widget="pushmenu" href="#" role="button">
                     <i class="fas fa-bars"></i>
                  </a>
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
                  <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank">
                     <p style="color: white; size: 8px">Ir a página DIRSU</p>
                  </a>
               </li>
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a> 
               </li>
            </ul>
         </nav>
         <!-- /.navbar -->
         <!-- Main Sidebar Container -->
         <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="../inicio.php" class="brand-link">
               <img src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
               <span class="brand-text font-weight-light">Sistema DIRSU</span>
            </a>
            <!-- Sidebar -->
            <div class="sidebar">
               <!-- Sidebar user panel (optional) -->
               <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                  <div class="image">
                     <img src="../dust/img/avatar.png" class="img-circle elevation-2" alt="User Image">
                  </div>
                  <?php
                  // Ajuste del nombre a mostrar según longitud
                  $primer_apellido = explode(' ', htmlspecialchars($apellidos))[0];
                  if (mb_strlen($nombres) > 22) {
                      $texto_a_imprimir = htmlspecialchars($nombres);
                  } elseif (mb_strlen($nombres . ' ' . $primer_apellido) <= 23) {
                      $texto_a_imprimir = htmlspecialchars($nombres . ' ' . $primer_apellido);
                  } else {
                      $texto_a_imprimir = htmlspecialchars(mb_substr($nombres, 0, 23));
                  }
                  ?>
                  <div class="info">
                     <a href="perfil.php" class="d-block"><?php echo $texto_a_imprimir; ?></a>
                  </div>
               </div>
               <!-- Sidebar Menu -->
               <nav class="mt-2">
                  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                     <li class="nav-item">
                        <a href="../inicio.php" class="nav-link">
                           <i class="nav-icon fas fa-home"></i>
                           <p>INICIO</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="guia.php" class="nav-link">
                           <i class="fa fa-road nav-icon"></i>
                           <p>Guía de trabajo</p>
                        </a>
                     </li>
                     <!-- SUB MENÚ MI PROYECTO -->
                     <li class="nav-header">Información de proyecto</li>
                     <li class="nav-item">
                        <a href="proyecto.php" class="nav-link">
                           <i class="fa fa-users nav-icon"></i>
                           <p>Mi proyecto</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="progreso.php" class="nav-link active">
                           <i class="fa fa-chart-line nav-icon"></i>
                           <p>Mi progreso</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="formato.php" class="nav-link">
                           <i class="fa fa-file-word nav-icon"></i>
                           <p>Formatos</p>
                        </a>
                     </li>
                     <!-- FIN SUB MENÚ MI PROYECTO -->
                     <li class="nav-header">Fases de proyecto</li>
                     <!-- FASE 1 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">1</span>
                           <p>Formulación y presentación</p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="datos_principales.php" class="nav-link">
                                 <p>1.1. Generalidades</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="desarrollo_informe.php" class="nav-link">
                                 <p>1.2. Plan de proyecto</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="anexos.php" class="nav-link">
                                 <p>1.3. Anexos</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <!-- FASE 2 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">2</span>
                           <p>Ejecución y monitoreo</p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="cronograma.php" class="nav-link">
                                 <p>2.1. Cronograma de ejecución</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_cronograma.php" class="nav-link">
                                 <p>2.2. Revisión de cronograma</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <!-- FASE 3 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">3</span>
                           <p>Evaluación e informe</p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="../semestral/index.php" class="nav-link">
                                 <p>3.1. Informe semestral</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_informe_final.php" class="nav-link">
                                 <p>3.2. Revisión de informe</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <li class="nav-item" style="height: 100px;"></li>
                  </ul>
               </nav>
               <!-- /.sidebar-menu -->
            </div>
            <!-- /.sidebar -->
         </aside>
         <!-- Content Wrapper -->
         <div class="content-wrapper">
            <!-- Content Header (Solicitud de Revisión) -->
            <div class="content-header">
               <div class="container-fluid">
                  <div class="row mb-0">
                     <div class="col-sm-7">
                        <h1 class="m-0">Solicitudes de Revisión</h1>
                     </div>
                  </div>
               </div>
            </div>
            <!-- Main content -->
            <section class="content">
               <div class="container-fluid">
                  <!-- Se incluye la tabla y lógica de Solicitud de Revisión -->
                  <?php include('../componentes/proyecto/revision_fase1.php'); ?>
               </div>
            </section>
            <!-- Se mantiene la otra sección con información de proyectos -->
            <div class="content-header">
               <div class="container-fluid">
                  <div class="row mb-0">
                     <div class="col-sm-7">
                        <h1 class="m-0">Progreso de la Evaluación de mi Informe Semestral</h1>
                     </div>
                  </div>
               </div>
            </div>
            <div class="col-md-12">
               <div class="card-body">
                  <?php include ('proyectos_facultad.php'); ?>
               </div>
            </div>
         </div>
         <!-- /.content-wrapper -->
         <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
               <p>Desarrollado por el <a href="#">Área informática - DIRSU</a></p>
            </div>
         </footer>
         <!-- Control Sidebar -->
         <aside class="control-sidebar control-sidebar-dark">
         </aside>
      </div>
      <!-- jQuery -->
      <script src="../plogins/jquery/jquery.min.js"></script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- Demo -->
      <script src="../dust/js/demo.js"></script>
      <!-- Summernote -->
      <script src="../plogins/summernote/summernote-bs4.min.js"></script>
      <script src="../plogins/summernote/lang/summernote-es-ES.js"></script>
      <script>
         $(function () {
             $('#summernote').summernote({
                 lang: 'es-ES'
             });
         });
      </script>
      <!-- Funciones existentes para cargar detalles, semestre y evaluación -->
      <script>
      function verDetalle(id_py) {
          $('#modalVerDetalle').modal('show');
          $.ajax({
              url: '../direccion_rsu/logica/presentacion_py.php',
              method: 'GET',
              data: { id_py: id_py },
              success: function(response) {
                  $('#contenidoModal').html(response);
              },
              error: function() {
                  alert('Hubo un error al cargar la Presentación de proyecto');
              }
          });
      }
      function verSemestre(id_py) {
          $('#modalVerSemestre').modal('show');
          $.ajax({
              url: '../direccion_rsu/logica/semestre_py.php',
              method: 'GET',
              data: { id_py: id_py },
              success: function(response) {
                  $('#contenidoModal2').html(response);
              },
              error: function() {
                  alert('Hubo un error al cargar el Informe Semestral');
              }
          });
      }
      function verEvaluacion(id_py) {
          $('#modalVerEvaluacion').modal('show');
          $.ajax({
              url: '../direccion_rsu/logica/evaluacion_py.php',
              method: 'GET',
              data: { id_py: id_py },
              success: function(response) {
                  $('#contenidoModal3').html(response);
              },
              error: function() {
                  alert('Hubo un error al cargar el Informe Semestral');
              }
          });
      }  
      </script>
      <!-- Modales para ver detalle, semestre y evaluación -->
      <div class="modal fade" id="modalVerDetalle" tabindex="-1" role="dialog" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
          <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="modalVerDetalleLabel">Fase 01: Presentación y formulación de Proyecto</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                  <div class="modal-body" id="contenidoModal" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                      <!-- Contenido cargado vía AJAX -->
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal fade" id="modalVerSemestre" tabindex="-1" role="dialog" aria-labelledby="modalVerSemestreLabel" aria-hidden="true">
          <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="modalVerSemestreLabel">Fase 03: Evaluación e informe Semestral / Final</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                  <div class="modal-body" id="contenidoModal2" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                      <!-- Contenido cargado vía AJAX -->
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                  </div>
              </div>
          </div>
      </div>
      <div class="modal fade" id="modalVerEvaluacion" tabindex="-1" role="dialog" aria-labelledby="modalVerEvaluacionLabel" aria-hidden="true">
          <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="modalVerEvaluacionLabel">Evaluación de Informe Semestral de Proyectos 2024 - II</h5>
                      <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                          <span aria-hidden="true">&times;</span>
                      </button>
                  </div>
                  <div class="modal-body" id="contenidoModal3" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                      <!-- Contenido cargado vía AJAX -->
                  </div>
                  <div class="modal-footer">
                      <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                  </div>
              </div>
          </div>
      </div>
   </body>
</html>
