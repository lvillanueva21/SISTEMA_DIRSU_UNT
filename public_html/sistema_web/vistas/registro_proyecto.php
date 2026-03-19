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
                              1. Registro de proyectos
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview active">
                           <li class="nav-item active">
                              <a href="datos_principales.php" class="nav-link active">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.1. Datos principales</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="desarrollo_informe.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.2. Desarrollo de informe</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="anexos.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.3. Anexos de informe</p>
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
                        <h1 class="m-0">1.1. Registro de proyectos</h1>
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
            <!-- DESARROLLO -->
            <div class="card card-primary card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                  <li class="pt-2 px-3"><h3 class="card-title">Datos principales</h3></li>
                  <li class="nav-item">
                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#custom-tabs-two-home" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true">Título</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-profile-tab" data-toggle="pill" href="#custom-tabs-two-profile" role="tab" aria-controls="custom-tabs-two-profile" aria-selected="false">Interesados</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-messages-tab" data-toggle="pill" href="#custom-tabs-two-messages" role="tab" aria-controls="custom-tabs-two-messages" aria-selected="false">Beneficiados</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-settings-tab" data-toggle="pill" href="#custom-tabs-two-settings" role="tab" aria-controls="custom-tabs-two-settings" aria-selected="false">Lugar y fecha</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-two-tabContent">
                  <div class="tab-pane fade show active" id="custom-tabs-two-home" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                     Título
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-profile" role="tabpanel" aria-labelledby="custom-tabs-two-profile-tab">
                     Interesados
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-messages" role="tabpanel" aria-labelledby="custom-tabs-two-messages-tab">
                     Beneficiados
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-settings" role="tabpanel" aria-labelledby="custom-tabs-two-settings-tab">
                     Lugar y fecha
                  </div>
                </div>
              </div>
              
            </div>
            
            <!-- DESARROLLO -->
            <div class="card card-primary card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                  <li class="pt-2 px-3"><h3 class="card-title">Desarrollo</h3></li>
                  <li class="nav-item">
                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#custom-tabs-two-home" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true">Home</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-profile-tab" data-toggle="pill" href="#custom-tabs-two-profile" role="tab" aria-controls="custom-tabs-two-profile" aria-selected="false">Profile</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-messages-tab" data-toggle="pill" href="#custom-tabs-two-messages" role="tab" aria-controls="custom-tabs-two-messages" aria-selected="false">Messages</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-settings-tab" data-toggle="pill" href="#custom-tabs-two-settings" role="tab" aria-controls="custom-tabs-two-settings" aria-selected="false">Settings</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-two-tabContent">
                  <div class="tab-pane fade show active" id="custom-tabs-two-home" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                     Lorem
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-profile" role="tabpanel" aria-labelledby="custom-tabs-two-profile-tab">
                     Mau
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-messages" role="tabpanel" aria-labelledby="custom-tabs-two-messages-tab">
                     Morbi
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-settings" role="tabpanel" aria-labelledby="custom-tabs-two-settings-tab">
                     Pel
                  </div>
                </div>
              </div>
              
            </div>
            <!-- ANEXOS -->
            <div class="card card-primary card-tabs">
              <div class="card-header p-0 pt-1">
                <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                  <li class="pt-2 px-3"><h3 class="card-title">Anexos</h3></li>
                  <li class="nav-item">
                    <a class="nav-link active" id="custom-tabs-two-home-tab" data-toggle="pill" href="#custom-tabs-two-home" role="tab" aria-controls="custom-tabs-two-home" aria-selected="true">Home</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-profile-tab" data-toggle="pill" href="#custom-tabs-two-profile" role="tab" aria-controls="custom-tabs-two-profile" aria-selected="false">Profile</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-messages-tab" data-toggle="pill" href="#custom-tabs-two-messages" role="tab" aria-controls="custom-tabs-two-messages" aria-selected="false">Messages</a>
                  </li>
                  <li class="nav-item">
                    <a class="nav-link" id="custom-tabs-two-settings-tab" data-toggle="pill" href="#custom-tabs-two-settings" role="tab" aria-controls="custom-tabs-two-settings" aria-selected="false">Settings</a>
                  </li>
                </ul>
              </div>
              <div class="card-body">
                <div class="tab-content" id="custom-tabs-two-tabContent">
                  <div class="tab-pane fade show active" id="custom-tabs-two-home" role="tabpanel" aria-labelledby="custom-tabs-two-home-tab">
                     Lorem
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-profile" role="tabpanel" aria-labelledby="custom-tabs-two-profile-tab">
                     Mau
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-messages" role="tabpanel" aria-labelledby="custom-tabs-two-messages-tab">
                     Morbi
                  </div>
                  <div class="tab-pane fade" id="custom-tabs-two-settings" role="tabpanel" aria-labelledby="custom-tabs-two-settings-tab">
                     Pel
                  </div>
                </div>
              </div>
              
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