<?php
include "../componentes/configSesion.php";
include "../includes/db_connection.php";
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Lista de Proyectos</title>
  <link href="../imagenes/dirsu_128_128.ico" rel="icon">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
  <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
  <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
  <link rel="stylesheet" href="../dust/css/adminlte.min.css">
  <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
  <link rel="stylesheet" href="../lista_proyectos/assets/proyectos.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
  <nav class="main-header navbar navbar-expand navbar-white navbar-light">
    <ul class="navbar-nav">
      <li class="nav-item">
        <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
      </li>
    </ul>
    <ul class="navbar-nav ml-auto">
      <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
        <a href="https://rsu.unitru.edu.pe" class="nav-link" target="_blank" rel="noopener noreferrer">
          <p style="color: white;">Ir a página DIRSU</p>
        </a>
      </li>
      <li class="nav-item d-none d-sm-inline-block">
        <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
      </li>
    </ul>
  </nav>

  <?php include_once "../includes/sidebar.php"; ?>

  <div class="content-wrapper">
    <section class="content p-3">
      <?php include __DIR__ . "/principal.php"; ?>
    </section>
  </div>

  <footer class="main-footer">
    <strong>&copy; 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
    <div class="float-right d-none d-sm-inline-block">
      <p>Desarrollado por el <a href="#">Área informática - DIRSU</a></p>
    </div>
  </footer>
</div>

<script src="../plogins/jquery/jquery.min.js"></script>
<script src="../plogins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button);</script>
<script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plogins/sparklines/sparkline.js"></script>
<script src="../plogins/jqvmap/jquery.vmap.min.js"></script>
<script src="../plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
<script src="../plogins/jquery-knob/jquery.knob.min.js"></script>
<script src="../dust/js/adminlte.js"></script>
<script src="../dust/js/demo.js"></script>
<script src="../dust/js/pages/dashboard.js"></script>
<script src="../lista_proyectos/assets/proyectos.js"></script>
</body>
</html>
