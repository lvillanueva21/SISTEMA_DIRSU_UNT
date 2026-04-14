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
    <title>Progreso de Proyectos</title>
    <!-- Favicon -->
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <!-- Bootstrap Icons -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
    <!-- Librería para imprimir info en excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <!-- Navbar -->
        <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
                </li>
            </ul>
            <ul class="navbar-nav ml-auto">
                <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
                        <p style="color: white;">Ir a página DIRSU</p>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
                </li>
            </ul>
        </nav>
        <!-- Sidebar -->
                <?php include_once "../includes/sidebar.php"; ?>
        <div class="content-wrapper">
            <section class="content" style="height: 400px;">
<!-- 4 divs -->
<div class="container-fluid mt-3">
  <div class="row">
    <!-- Card 1 -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-primary text-white">
          <h5 class="card-title mb-0">Card 1</h5>
        </div>
        <div class="card-body">
        <?php include 'cards/card1_tablas_estado.php'; ?>
        </div>
      </div>
    </div>
    <!-- Card 2 -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-success text-white">
          <h5 class="card-title mb-0">Card 2</h5>
        </div>
        <div class="card-body">
        <?php include 'cards/card2_gestion_periodos.php'; ?>
        </div>
      </div>
    </div>
    <!-- Card 3 -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-warning text-white">
          <h5 class="card-title mb-0">Card 3</h5>
        </div>
        <div class="card-body">
        <?php include 'cards/card3_migraciones.php'; ?>
        </div>
      </div>
    </div>
    <!-- Card 4 -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-info text-white">
          <h5 class="card-title mb-0">Card 4</h5>
        </div>
        <div class="card-body">
          <?php include 'cards/card4_usuario_proyecto.php'; ?>
        </div>
      </div>
    </div>
  </div>
</div>
<!-- .4 divs -->
<!-- Nueva fila con Card 8 -->
<div class="container-fluid mt-4">
  <div class="row">
    <!-- Card 8 -->
    <div class="col-md-6">
      <div class="card shadow">
        <div class="card-header bg-dark text-white">
          <h5 class="card-title mb-0">Card 8: Cambiar período de un proyecto</h5>
        </div>
        <div class="card-body">
          <?php include 'cards/card8_cambio_periodo.php'; ?>
        </div>
      </div>
    </div>
<!-- Nueva fila con Card 9 -->
<div class="container-fluid mt-4">
  <div class="row">
    <div class="col-md-12">
      <div class="card shadow">
        <div class="card-header bg-danger text-white">
          <h5 class="card-title mb-0">
            Card 9: Reiniciar proyectos aprobados
          </h5>
        </div>
        <div class="card-body">
          <?php include 'cards/card9_reiniciar_proyecto.php'; ?>
        </div>
      </div>
    </div>
  </div>
</div>
            </section>
        </div>
        <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://adminlte.io"> Área  informática - DIRSU</a></p>
            </div>
        </footer>
    </div>
    <!-- jQuery -->
    <script src="../plogins/jquery/jquery.min.js"></script>
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
    <!-- Sparkline -->
    <script src="../plogins/sparklines/sparkline.js"></script>
    <!-- JQVMap -->
    <script src="../plogins/jqvmap/jquery.vmap.min.js"></script>
    <script src="../plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
    <!-- jQuery Knob Chart -->
    <script src="../plogins/jquery-knob/jquery.knob.min.js"></script>
    <!-- AdminLTE App -->
    <script src="../dust/js/adminlte.js"></script>
    <!-- AdminLTE for demo purposes -->
    <script src="../dust/js/demo.js"></script>
    <!-- AdminLTE dashboard demo (This is only for demo purposes) -->
    <script src="../dust/js/pages/dashboard.js"></script>
    <!-- Summernote -->
    <script src="../plogins/summernote/summernote-bs4.min.js"></script>
    <script src="../plogins/summernote/lang/summernote-es-ES.js"></script>
</body>
</html>

