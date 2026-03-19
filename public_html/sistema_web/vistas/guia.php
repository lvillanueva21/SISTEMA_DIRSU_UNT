<?php include "../componentes/configSesion.php"; ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Guía de trabajo - Sistema DIRSU</title>
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
      <!-- BS Stepper -->
      <link rel="stylesheet" href="../plogins/bs-stepper/css/bs-stepper.min.css">
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
                  <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank">
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
// Suponiendo que $apellidos es una cadena con los apellidos separados por espacios
$primer_apellido = explode(' ', htmlspecialchars($apellidos))[0]; // Obtener el primer apellido

if (mb_strlen($nombres) > 22) {
    // Si $nombres tiene más de 22 caracteres, solo se imprime $nombres
    $texto_a_imprimir = htmlspecialchars($nombres);
} elseif (mb_strlen($nombres . ' ' . $primer_apellido) <= 23) {
    // Si la combinación de $nombres y el primer apellido tiene 23 caracteres o menos
    $texto_a_imprimir = htmlspecialchars($nombres . ' ' . $primer_apellido);
} else {
    // Si ninguna de las condiciones anteriores se cumple, imprimir 23 caracteres de $nombres
    $texto_a_imprimir = htmlspecialchars(mb_substr($nombres, 0, 23));
}
?>

<div class="info">
    <a href="perfil.php" class="d-block"><?php echo $texto_a_imprimir; ?></a>
</div>

               </div>
               <!-- SidebarSearch Form -->
               <!--
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
               -->
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
                              <!-- <span class="right badge badge-danger">2</span> -->
                           </p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="guia.php" class="nav-link active">
                           <i class="fa fa-road nav-icon"></i>
                           <p>Guía de trabajo</p>
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
    <a href="progreso.php" class="nav-link">
        <i class="fa fa-chart-line nav-icon"></i>
        <p>Mi progreso</p>
    </a>
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
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">1</span>
                           <p>
                              Formulación y presentación
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="datos_principales.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.1. Generalidades</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="desarrollo_informe.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.2. Plan de proyecto</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="anexos.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.3. Anexos</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <!-- FIN - SUB MENU FASE 1 - NIVEL 1 -->
                     <!-- INICIO - SUB MENU FASE 2 - NIVEL 1 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">2</span>
                           <p>
                              Ejecución y monitoreo
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="cronograma.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>2.1. Cronograma de ejecución</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_cronograma.php" class="nav-link">
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
                           <span class="badge nav-icon">3</span>
                           <p>
                              Evaluación e informe
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="../semestral/index.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>3.1. Informe semestral</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_informe_final.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>3.2. Revisión de informe</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <li class="nav-item" style="height: 100px;"></li>
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
                  <div class="row mb-0">
                     <div class="col-sm-7">
                        <h1 class="m-0">Guía de trabajo DIRSU</h1>
                     </div>
                     <!-- /.col -->
                     
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

        <!-- Timelime example  -->
        <div class="row">
          <div class="col-md-12">
            <!-- The time line -->
            <div class="timeline">
              <!-- timeline time label -->
              <div class="time-label">
                <span class="bg-blue">PASO 1: Conociendo la pantalla de INICIO</span>
              </div>
              <!-- /.timeline-label -->
              <!-- timeline item -->
              <div>
    <i class="fas fa-envelope bg-blue"></i>
    <div class="timeline-item">
        <span class="time"><i class="fas fa-clock"></i> Hasta el 09 de septiembre 2024</span>
        <h3 class="timeline-header"><a href="#">Reunión de coordinación y formación de equipos</a></h3>

        <div class="timeline-body" style="margin: 20px;">
            <!-- Imagen adaptable -->
            <img src="../imagenes/guia/uno.png" alt="AdminLTELogo" class="img-fluid">
        </div>
    </div>
</div>

              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-user bg-green"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 10 de septiembre 2024</span>
                  <h3 class="timeline-header no-border"><a href="#">Acompañamiento técnico de DIRSU</a></h3>
                  <div class="timeline-body">
                    Detalle
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 11 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Presentación de proyecto  a Mesa de Partes de su Facultad</a></h3>
                  <div class="timeline-body">
                    Detalle
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 12 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">CRSUF</a></h3>
                  <div class="timeline-body">
                    Evaluación del proyecto
                  </div>
                  <div class="timeline-footer">
                    <a href="#" class="btn btn-sm bg-maroon">No cumple</a>
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 13 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">CRSUF</a></h3>
                  <div class="timeline-body">
                    Visto bueno y remisión de proyectos de RSU al departamento académico
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 14 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Director de departamento</a></h3>
                  <div class="timeline-body">
                    Verificación y visto bueno de los proyectos de acuerdo a la carga horaria.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 15 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Decanato</a></h3>
                  <div class="timeline-body">
                    Envío de proyectos de RSU consolidados con VB del comité de RSU, Dirección de Departamento y Decanato a DIRSU.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 16 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">DIRSU</a></h3>
                  <div class="timeline-body">
                    Verificación de los requisitos establecidos en la directiva.
                  </div>
                  <div class="timeline-footer">
                    <a href="#" class="btn btn-sm bg-maroon">No cumple</a>
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 17 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">DIRSU</a></h3>
                  <div class="timeline-body">
                    Registro de proyectos de RSU.
                  </div>
                  <div class="timeline-footer">
                    <a href="#" class="btn btn-sm bg-maroon">No cumple</a>
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 18 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">PROYECTO APROBADO Y REGISTRADO</a></h3>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline time label -->
              <div class="time-label">
                <span class="bg-green">Fase 2: Ejecución y monitoreo del proyecto</span>
              </div>
              <!-- /.timeline-label -->
              <!-- timeline item -->
              <div>
                <i class="fa fa-camera bg-purple"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 23 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Director de Departamento</a></h3>
                  <div class="timeline-body">
                    Monitoreo del desarrollo del proyecto.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-video bg-maroon"></i>

                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 23 de septiembre 2024</span>

                  <h3 class="timeline-header"><a href="#">Reporte sobre el cumplimiento de la ejecución del proyecto a DIRSU</a></h3>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- 3ER ITEM -->
              <!-- timeline time label -->
              <div class="time-label">
                <span class="bg-yellow">Fase 3: Evaluación e informe de proyecto</span>
              </div>
              <!-- /.timeline-label -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-envelope bg-blue"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 09 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">EFP</a></h3>

                  <div class="timeline-body">
                    Presentación de informe a mesa de partes de su facultad.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-user bg-green"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 25 de septiembre 2024</span>
                  <h3 class="timeline-header no-border"><a href="#">CRSUF</a></h3>
                  <div class="timeline-body">
                    Evaluación del proyecto.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Informe</a></h3>
                  <div class="timeline-body">
                    Informe final de resultados.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">CRSUF</a></h3>
                  <div class="timeline-body">
                    Aprobación y remisión de informes de decanato.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">DECANATO</a></h3>
                  <div class="timeline-body">
                    Visto bueno y envío de informes de RSU a la Dirección de Responsabilidad Social Universitaria.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">CRSUF</a></h3>
                  <div class="timeline-body">
                    Aprobación y remisión de informes de decanato.
                  </div>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">INFORME APROBADO</a></h3>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- timeline item -->
              <div>
                <i class="fas fa-comments bg-yellow"></i>
                <div class="timeline-item">
                  <span class="time"><i class="fas fa-clock"></i> Hasta el 20 de septiembre 2024</span>
                  <h3 class="timeline-header"><a href="#">Jornada de presentación de avances y/o resultados finales de proyectos de RSU</a></h3>
                </div>
              </div>
              <!-- END timeline item -->
              <!-- .3ER ITEM -->
              <div>
                <i class="fas fa-clock bg-gray"></i>
              </div>
            </div>
          </div>
          <!-- /.col -->
        </div>
      </div>
      <!-- /.timeline -->

    </section>
    <!-- /.content -->
            <!-- /.content -->
         </div>
         <!-- /.content-wrapper -->
         <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
               <p>Desarrollado por el <a href="#"> Área  informática - DIRSU</a></p>
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
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
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