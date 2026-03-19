<?php
  // Incluir configSesion.php para verificar la sesión
  include "../componentes/configSesion.php";
  
  // Incluir la conexión a la base de datos
  include('../componentes/db.php');
  
  // Arreglo de facultades para el filtro y nombre de facultad
  $facultades = [
   '1' => 'Ciencias Agropecuarias', 
   '2' => 'Ciencias Biológicas', 
   '3' => 'Ciencias Económicas', 
   '4' => 'Ciencias Físicas y Matemáticas',
   '5' => 'Ciencias Sociales', 
   '6' => 'Derecho y Ciencias Políticas', 
   '7' => 'Educación y Ciencias de la Comunicación', 
   '8' => 'Enfermería',
   '9' => 'Estomatología', 
   '10' => 'Farmacia y Bioquímica', 
   '11' => 'Ingeniería', 
   '12' => 'Ingeniería Química', 
   '13' => 'Medicina'
  ];
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
            <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
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
      <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <!-- Brand Logo -->
        <a href="inicio.php" class="brand-link">
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
              <a href="inicio.php" class="d-block"><?php echo $texto_a_imprimir; ?></a>
            </div>
          </div>
          <!-- SidebarSearch Form -->
          <div class="form-inline">
          </div>
          <!-- Sidebar Menu -->
          <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
              <!-- Add icons to the links using the .nav-icon class
                with font-awesome or any other icon font library -->
              <li class="nav-item">
                <a href="inicio.php" class="nav-link active">
                  <i class="nav-icon fas fa-home"></i>
                  <p>
                    INICIO
                    <!-- <span class="right badge badge-danger">2</span> -->
                  </p>
                </a>
              </li>
              <!-- <li class="nav-item">
                <a href="progreso_proyectos.php" class="nav-link">
                   <i class="fa fa-chart-line nav-icon"></i>
                   <p>Progreso de Proyectos</p>
                </a>
                </li> -->
              <li class="nav-header">Evaluación de Proyectos</li>
              <li class="nav-item">
                <a href="evaluacion.php" class="nav-link">
                  <i class="fa fa-calendar nav-icon"></i>
                  <p>Informe Semestral 2025-I</p>
                </a>
              </li>
              <li class="nav-item menu">
                <a href="#" class="nav-link">
                  <span class="badge nav-icon"><i class="fas fa-user-cog"></i></span>
                  <p>Evaluaciones anteriores</p>
                </a>
                <ul class="nav nav-treeview">
                  <li class="nav-item">
                    <a href="cotejo.php" class="nav-link">
                      <i class="fa fa-clipboard-list nav-icon"></i>
                      <p>Lista de Cotejo (2024)</p>
                    </a>
                  </li>
                  <li class="nav-item">
                    <a href="rubrica.php" class="nav-link">
                      <i class="fa fa-table nav-icon"></i>
                      <p>Rúbrica (2024)</p>
                    </a>
                  </li>
                </ul>
              </li>
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
            <?php include('../inicio/index.php'); ?>
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