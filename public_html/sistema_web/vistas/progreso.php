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
         <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
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
