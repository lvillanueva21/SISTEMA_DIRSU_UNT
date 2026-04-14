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
    <title>DIRSU ANALITICS</title>
    <!-- Favicon -->
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <!-- Ionicons -->
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <!-- JQVMap -->
    <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
    <!-- Theme style -->
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <!-- overlayScrollbars -->
    <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
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
            <section class="content p-3">
                <!-- =============== CARD ANALÍTICA DIRSU =============== -->
<div class="card shadow-sm">
  <div class="card-header bg-primary text-white">
    <h5 class="card-title mb-0">
      <i class="fas fa-chart-pie"></i> Analítica por Facultad
    </h5>
  </div>

  <!-- NAV-TABS -->
  <div class="card-body">
    <ul class="nav nav-tabs" id="tabsAnalitica" role="tablist">
      <li class="nav-item">
        <a class="nav-link active" id="tab-periodo" data-toggle="tab" href="#contenido-periodo" role="tab">
          Período
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-estado" data-toggle="tab" href="#contenido-estado" role="tab">
          Estado
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-oficina" data-toggle="tab" href="#contenido-oficina" role="tab">
          Oficina
        </a>
      </li>
      <li class="nav-item">
        <a class="nav-link" id="tab-avance" data-toggle="tab" href="#contenido-avance" role="tab">
          Avance
        </a>
      </li>
    </ul>

    <!-- TAB-PANES -->
    <div class="tab-content pt-3">

      <!-- TAB 1 : PERÍODO -->
      <div class="tab-pane fade show active" id="contenido-periodo" role="tabpanel">
        <?php include 'charts/data_periodo.php'; ?>
      </div>

      <!-- TAB 2 : ESTADO -->
      <div class="tab-pane fade" id="contenido-estado" role="tabpanel">
        <?php include 'charts/data_estado.php'; ?>
      </div>

      <!-- TAB 3 : OFICINA -->
      <div class="tab-pane fade" id="contenido-oficina" role="tabpanel">
        <?php include 'charts/data_oficina.php'; ?>
      </div>

      <!-- TAB 4 : AVANCE (vacío de momento) -->
<div class="tab-pane fade" id="contenido-avance" role="tabpanel">
  <?php include 'charts/data_avance.php'; ?>
</div>
    </div>
  </div>
</div>
<!-- =========== /CARD ANALÍTICA =========== -->
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
      <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
</body>
</html>

