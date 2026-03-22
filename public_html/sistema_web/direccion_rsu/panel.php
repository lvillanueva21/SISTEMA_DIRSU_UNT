<?php 
// Incluir configSesion.php para verificar la sesiÃ³n
include "../componentes/configSesion.php";

// Incluir la conexiÃ³n a la base de datos
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
          <a class="nav-link" data-widget="pushmenu" href="#" role="button">
            <i class="fas fa-bars"></i>
          </a>
        </li>
      </ul>
      <ul class="navbar-nav ml-auto">
        <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
          <a href="https://rsu.unitru.edu.pe" class="nav-link" target="_blank">
            <p style="color: white;">Ir a pÃ¡gina DIRSU</p>
          </a>
        </li>
        <li class="nav-item d-none d-sm-inline-block">
          <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesiÃ³n</a>
        </li>
      </ul>
    </nav>
    <!-- Sidebar -->
            <?php include_once "../includes/sidebar.php"; ?>
    <!-- Content Wrapper -->
    <div class="content-wrapper">
      <section class="content" style="min-height: 400px;">
        <div class="card">
          <h6 class="card-header bg-primary text-white">Bienvenid@ al Panel de Control de la <b><u>DIRSU</u></b></h6>
          <div class="row">
            <!-- Primera columna: Crear Usuarios de Autoridades -->
            <div class="col-md-5">
              <div class="card">
                <div class="card bg-light d-flex flex-fill">
                  <div class="card-header text-muted border-bottom-0">
                    <div class="row">
                      <div class="col-10">
                        <div class="card-header">
                          <b>CREAR USUARIOS DE AUTORIDADES</b>
                        </div>
                      </div>
                      <?php
                      if (isset($_GET['alert'])) {
                        $alert_type = $_GET['alert'];
                        if ($alert_type == 1) {
                          echo "<div class='alert alert-warning alert-dismissible fade show' role='alert'>
                                  <strong>Advertencia:</strong> El usuario ingresado ya existe, inicie sesiÃ³n o registre otro.
                                  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                  </button>
                                </div>";
                        } elseif ($alert_type == 2) {
                          echo "<div class='alert alert-success alert-dismissible fade show' role='alert'>
                                  <strong>Ã‰xito:</strong> Registro exitoso, haz clic para <a href='/sistema_web/login.php'>Iniciar sesiÃ³n</a>.
                                  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                  </button>
                                </div>";
                        } elseif ($alert_type == 3) {
                          echo "<div class='alert alert-danger alert-dismissible fade show' role='alert'>
                                  <strong>Error:</strong> Error al registrar, intente nuevamente.
                                  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                  </button>
                                </div>";
                        } elseif ($alert_type == 4) {
                          echo "<div class='alert alert-info alert-dismissible fade show' role='alert'>
                                  <strong>InformaciÃ³n:</strong> Las contraseÃ±as ingresadas no coinciden.
                                  <button type='button' class='close' data-dismiss='alert' aria-label='Close'>
                                    <span aria-hidden='true'>&times;</span>
                                  </button>
                                </div>";
                        }
                      }
                      ?>
<div class="col-2 text-right">
  <img src="../dust/img/dirsu_bienvenida.jpg" alt="user-avatar" class="img-circle img-fluid" style="width: 50px; height: 50px;">
</div>
                    </div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="row">
                      <div class="col-12">
                        <?php include('logica_panel/crear_autoridad.php'); ?>
                      </div>
                    </div>
                  </div>
                  <div class="card-footer">
                    <div class="text-right text-muted small">
                      Luigi Villanueva - Ã¡rea InformÃ¡tica - DIRSU
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- Segunda columna: Cronograma Editable -->
            <div class="col-md-7">
              <div class="card">
                <div class="card bg-light d-flex flex-fill">
                  <div class="card-header text-muted border-bottom-0">
                    <div class="row">
                      <div class="col-10">
                        <div class="card-header">
                          <b>CONTROL DE CRONOGRAMA DE PROYECTOS</b>
                        </div>
                      </div>
                      <div class="col-2 text-right">
  <img src="../dust/img/dirsu_bienvenida.jpg" alt="user-avatar" class="img-circle img-fluid" style="width: 50px; height: 50px;">
</div>
                    </div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="row">
                      <div class="col-12">
                      <?php include('logica_panel/actualizar_eventos.php'); ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <div class="text-right text-muted small">
                    Luigi Villanueva - Ã¡rea InformÃ¡tica - DIRSU
                  </div>
                </div>
              </div>
            </div>
            <!-- Fin de las columnas -->
          </div>
          <div class="row">
            <!-- Primera columna: Cronograma editable -->
            <div class="col-md-12">
              <div class="card">
                <div class="card bg-light d-flex flex-fill">
                  <div class="card-header text-muted border-bottom-0">
                    <div class="row">
                      <div class="col-10">
                        <div class="card-header">
                          <b>CONTROL DE CRONOGRAMA DE PROYECTOS</b>
                        </div>
                      </div>
                      <div class="col-2 text-right">
  <img src="../dust/img/dirsu_bienvenida.jpg" alt="user-avatar" class="img-circle img-fluid" style="width: 50px; height: 50px;">
</div>
                    </div>
                  </div>
                  <div class="card-body pt-0">
                    <div class="row">
                      <div class="col-12">
                        <?php include('logica_panel/actualizar_cronograma.php'); ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="card-footer">
                  <div class="text-right text-muted small">
                    Luigi Villanueva - Ã¡rea InformÃ¡tica - DIRSU
                  </div>
                </div>
              </div>
            </div>
            <!-- Fin de las columnas -->
          </div>
        </div>
      </section>
    </div>
    <!-- Footer -->
    <footer class="main-footer">
      <strong>Â© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
      <div class="float-right d-none d-sm-inline-block">
        <p>Desarrollado por el <a href="#">Ãrea informÃ¡tica - DIRSU</a></p>
      </div>
    </footer>
  </div>
  <!-- jQuery -->
  <script src="../plogins/jquery/jquery.min.js"></script>
  <!-- jQuery UI 1.11.4 -->
  <script src="../plogins/jquery-ui/jquery-ui.min.js"></script>
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
</body>
</html>

