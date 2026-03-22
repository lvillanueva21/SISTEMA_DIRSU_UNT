<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Inicio - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- Ionicons -->
      <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
      <!-- Tempusdominus Bootstrap 4 -->
      <link rel="stylesheet" href="../plogins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
      <!-- iCheck -->
      <link rel="stylesheet" href="../plogins/icheck-bootstrap/icheck-bootstrap.min.css">
      <!-- JQVMap -->
      <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
      <!-- overlayScrollbars -->
      <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
      <!-- Daterange picker -->
      <link rel="stylesheet" href="../plogins/daterangepicker/daterangepicker.css">
      <!-- summernote -->
      <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
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
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
               </li>
            </ul>
         </nav>
         <!-- /.navbar -->
         <!-- Main Sidebar Container --><!-- Contenedor de barra lateral principal -->
         <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
         <!-- Content Wrapper. Contains page content -->
         <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
               <div class="container-fluid">
                  <div class="row mb-2">
                     <div class="col-sm-12">
                        <h5 class="m-0">!Bienvenido al Sistema de gestión de proyectos DIRSU!</h5>
                     </div>
                     <!-- /.col -->
                  </div>
                  <!-- /.row -->
               </div>
               <!-- /.container-fluid -->
            </div>
            <!-- /.content-header -->
            <!-- Main content -->
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <!-- Primera fila: Mi buzón de mensajes DIRSU y Mi próxima entrega -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card bg-light d-flex flex-fill">
                        <div class="card-header text-muted border-bottom-0">
                            <div class="row">
                                <div class="col-10">
                                    <div class="card-header">
                                        <h3 class="card-title">Mi buzón de mensajes DIRSU</h3>
                                    </div>
                                    <br>
                                    <h1 class="lead"><b>Hola <?php echo htmlspecialchars($nombres); ?></b></h1>
                                </div>
                                <div class="col-2 text-right">
                                    <img src="../dust/img/dirsu_bienvenida.jpg" alt="user-avatar" class="img-circle img-fluid">
                                </div>
                            </div>
                        </div>
                        <div class="card-body pt-0">
                            <div class="row">
                                <div class="col-12">
                                    <p class="text-muted text-sm text-justify">Como Director del departamento académico de <b><?php echo isset($nombre_depa) ? $nombre_depa : 'Departamento no encontrado'; ?></b> serás el encargado de dar el visto bueno a los <b>Informes Semestrales</b> presentados por los coordinadores de proyectos de tu departamento. <br>
                                    <br>
                                        <b>¿Cómo dar el visto bueno?</b> <br>
                                        Dirígete a la opción Evaluación de Proyectos --> Visto bueno. Podrás ver a detalle la información de los proyectos que están solicitando revisión y otorgar el visto bueno de la Dirección de Departamento.
                                        <br>
                                        
                                    </p>
                                    <ul class="ml-4 mb-0 fa-ul text-muted">
    <li class="small"><span class="fa-li"><i class="fas fa-lg fa-envelope"></i></span> Correo: dirsu@unitru.edu.pe</li>
    <li class="small"><span class="fa-li"><i class="fas fa-lg fa-map-marker-alt"></i></span> Jirón Diego De Almagro 344, Trujillo</li>
</ul>
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <div class="text-right text-muted small">
                                Luigi Villanueva - área Informática - DIRSU
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header" style="background-color: #dc3545; color: white;">
                        <h5>Mi próxima entrega</h5>
                    </div>
                    <div class="card-body">
                        <h3 class="card-title text-center p-3" style="font-size: 1.5rem; margin: 0;">¡IMPORTANTE! Subir Informe Semestral 2024 II en Evaluación e Informe --> INFORME FINAL</h3>
                        <h3 class="text-center" style="font-size: 1.2rem; margin-bottom: 1.5rem;">Nueva fecha límite: 15 de enero de 2025 a las 23:59</h3>
                        <div class="row justify-content-center" style="margin-top: 20px;">
                            <div class="col-md-12 text-center">
                                <i class="fas fa-clock fa-3x mb-3"></i>
                                <div id="countdown" style="display: flex; justify-content: center;">
                                    <div class="counter-box text-center" style="margin: 0 10px;">
                                        <h6 id="days" style="font-size: 1.5rem; margin: 0;">0</h6>
                                        <p style="margin: 0;">Días</p>
                                    </div>
                                    <div class="counter-box text-center" style="margin: 0 10px;">
                                        <h6 id="hours" style="font-size: 1.5rem; margin: 0;">0</h6>
                                        <p style="margin: 0;">Horas</p>
                                    </div>
                                    <div class="counter-box text-center" style="margin: 0 10px;">
                                        <h6 id="minutes" style="font-size: 1.5rem; margin: 0;">0</h6>
                                        <p style="margin: 0;">Minutos</p>
                                    </div>
                                    <div class="counter-box text-center" style="margin: 0 10px;">
                                        <h6 id="seconds" style="font-size: 1.5rem; margin: 0;">0</h6>
                                        <p style="margin: 0;">Segundos</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <!-- Fin de la primera fila -->

            <!-- Segunda fila: Cronograma de proyectos DIRSU y Mis notas -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header" style="background-color: #28a745; color: white;">
                        <h3 class="card-title text-center">Cronograma de proyectos DIRSU - Período I - 2024</h3>
                    </div>
                    <div class="card-body">
                    <?php include('../integrados/cronograma_general.php'); ?>
                    </div>
                </div>
            </div>

            <!-- <div class="col-md-6">
                <div class="card">
                    <div class="card-header" style="background-color: #ffc107; color: black;">
                        <h3 class="card-title">
                            <i class="ion ion-clipboard mr-1"></i>
                            Mis notas
                        </h3>
                        <div class="card-tools">
                            <ul class="pagination pagination-sm">
                                <li class="page-item"><a href="#" class="page-link">&laquo;</a></li>
                                <li class="page-item"><a href="#" class="page-link">1</a></li>
                                <li class="page-item"><a href="#" class="page-link">2</a></li>
                                <li class="page-item"><a href="#" class="page-link">3</a></li>
                                <li class="page-item"><a href="#" class="page-link">&raquo;</a></li>
                            </ul>
                        </div>
                    </div>
                    <div class="card-body">
                        <ul class="todo-list" data-widget="todo-list">
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo1" id="todoCheck1">
                                    <label for="todoCheck1"></label>
                                </div>
                                <span class="text">Design a nice theme</span>
                                <small class="badge badge-danger"><i class="far fa-clock"></i> 2 mins</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo2" id="todoCheck2" checked>
                                    <label for="todoCheck2"></label>
                                </div>
                                <span class="text">Make the theme responsive</span>
                                <small class="badge badge-info"><i class="far fa-clock"></i> 4 hours</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo3" id="todoCheck3">
                                    <label for="todoCheck3"></label>
                                </div>
                                <span class="text">Let theme shine like a star</span>
                                <small class="badge badge-warning"><i class="far fa-clock"></i> 1 day</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo4" id="todoCheck4">
                                    <label for="todoCheck4"></label>
                                </div>
                                <span class="text">Let theme shine like a star</span>
                                <small class="badge badge-success"><i class="far fa-clock"></i> 3 days</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo5" id="todoCheck5">
                                    <label for="todoCheck5"></label>
                                </div>
                                <span class="text">Check your messages and notifications</span>
                                <small class="badge badge-primary"><i class="far fa-clock"></i> 1 week</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                            <li>
                                <span class="handle">
                                    <i class="fas fa-ellipsis-v"></i>
                                    <i class="fas fa-ellipsis-v"></i>
                                </span>
                                <div class="icheck-primary d-inline ml-2">
                                    <input type="checkbox" value="" name="todo6" id="todoCheck6">
                                    <label for="todoCheck6"></label>
                                </div>
                                <span class="text">Let theme shine like a star</span>
                                <small class="badge badge-secondary"><i class="far fa-clock"></i> 1 month</small>
                                <div class="tools">
                                    <i class="fas fa-edit"></i>
                                    <i class="fas fa-trash-o"></i>
                                </div>
                            </li>
                        </ul>
                    </div>
                    <div class="card-footer clearfix">
                        <button type="button" class="btn btn-primary float-right"><i class="fas fa-plus"></i> Añadir nota</button>
                    </div>
                </div>
            </div> -->
            <!-- Fin de la segunda fila -->
        </div>
    </div>
               </div>
            </section>
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
      <!-- jQuery UI 1.11.4 -->
      <script src="../plogins/jquery-ui/jquery-ui.min.js"></script>
      <!-- Resolve conflict in jQuery UI tooltip with Bootstrap tooltip -->
      <script>
         $.widget.bridge('uibutton', $.ui.button)
      </script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- ChartJS -->
      <script src="../plogins/chart.js/Chart.min.js"></script>
      <!-- Sparkline -->
      <script src="../plogins/sparklines/sparkline.js"></script>
      <!-- JQVMap -->
      <script src="../plogins/jqvmap/jquery.vmap.min.js"></script>
      <script src="../plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
      <!-- jQuery Knob Chart -->
      <script src="../plogins/jquery-knob/jquery.knob.min.js"></script>
      <!-- daterangepicker -->
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/daterangepicker/daterangepicker.js"></script>
      <!-- Tempusdominus Bootstrap 4 -->
      <script src="../plogins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
      <!-- Summernote -->
      <script src="../plogins/summernote/summernote-bs4.min.js"></script>
      <!-- overlayScrollbars -->
      <script src="../plogins/overlayScrollbars/js/jquery.overlayScrollbars.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
      <script src="../dust/js/pages/dashboard.js"></script>
      <script>
         // Función para actualizar el contador cada segundo
         function updateCountdown() {
             // Fecha de deadline en formato Año/Mes/Día Hora:Minutos:Segundos
             const deadlineDate = new Date("2025-01-15T23:59:59").getTime();
         
             const now = new Date().getTime();
             const timeLeft = deadlineDate - now;
         
             // Cálculo de días, horas, minutos y segundos restantes
             const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
             const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
             const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
             const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);
         
             // Asignar valores a los elementos HTML
             document.getElementById("days").innerHTML = days;
             document.getElementById("hours").innerHTML = hours;
             document.getElementById("minutes").innerHTML = minutes;
             document.getElementById("seconds").innerHTML = seconds;
         
             // Si el tiempo ha llegado a 0, se detiene el contador
             if (timeLeft < 0) {
                 clearInterval(countdownInterval);
                 document.getElementById("countdown").innerHTML = "<div class='alert alert-danger'>¡El deadline ha pasado!</div>";
             }
         }
         // Ejecutar la función de actualización cada segundo
         const countdownInterval = setInterval(updateCountdown, 1000);
      </script>
   </body>
</html>