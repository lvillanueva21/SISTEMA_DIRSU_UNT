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
        <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <a href="inicio.php" class="brand-link">
                <img src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
                <span class="brand-text font-weight-light">Sistema DIRSU</span>
            </a>
            <div class="sidebar">
                <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                    <div class="image">
                        <img src="../dust/img/avatar.png" class="img-circle elevation-2" alt="User Image">
                    </div>
                    <div class="info">
                        <a href="inicio.php" class="d-block"><?php echo htmlspecialchars($nombres) . " " . htmlspecialchars($apellidos); ?></a>
                    </div>
                </div>
                <nav class="mt-2">
                    <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                        <li class="nav-item">
                            <a href="inicio.php" class="nav-link">
                                <i class="nav-icon fas fa-home"></i>
                                <p>INICIO</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="panel.php" class="nav-link">
                                <i class="nav-icon fas fa-users-cog"></i>
                                <p>PANEL DE CONTROL</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="progreso_proyectos.php" class="nav-link active">
                                <i class="fa fa-chart-line nav-icon"></i>
                                <p>Progreso de Proyectos</p>
                            </a>
                        </li>
                        <li class="nav-header">Evaluación de Proyectos</li>
                        <li class="nav-item">
                            <a href="cotejo.php" class="nav-link">
                                <i class="fa fa-clipboard-list nav-icon"></i>
                                <p>Por Lista de Cotejo</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="rubrica.php" class="nav-link">
                                <i class="fa fa-table nav-icon"></i>
                                <p>Por Rúbrica</p>
                            </a>
                        </li>
                    </ul>
                </nav>
            </div>
        </aside>
        <div class="content-wrapper">
        <section class="content">
  <div class="container-fluid">
    <div class="row">
      <!-- Card de ancho completo -->
      <div class="col-12">
        <div class="card shadow">
          <div class="card-header bg-primary text-white">
            <h3 class="card-title mb-0">Título del Card</h3>
          </div>
          <div class="card-body">
            <!-- Aquí va tu contenido dinámico o estático -->
            <?php include 'cards/card5_filtro_rsu.php'; ?>
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
