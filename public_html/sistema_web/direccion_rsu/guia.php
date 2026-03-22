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
    <title>EvaluaciÃ³n por Cotejo</title>
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
    <style>
        body, html {
    height: 100%;
    overflow: hidden; /* evita scroll de toda la pÃ¡gina */
}

#header-div {
    height: 100px; /* altura del header */
}

#footer-div {
    height: 100px; /* altura del footer */
}

/* Ãrea central entre header y footer */
.content-scroll {
    position: absolute;
    top: 100px; /* mismo alto del header */
    bottom: 100px; /* mismo alto del footer */
    left: 0;
    right: 0;
    overflow: hidden;
}

/* Scroll interno para cada div */
#div-3, #div-4 {
    height: 100%;
    overflow-y: auto;
}

    </style>
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
        <div class="content-wrapper">
<section class="content px-3">
    <div class="container-fluid d-flex flex-column p-0" style="height: calc(100vh - 150px);">
<!-- Div Superior -->
<div class="bg-white shadow-sm p-3">
    <div class="row">
        <!-- Columna izquierda (7 espacios) -->
        <div class="col-md-7 d-flex flex-column justify-content-center align-items-center text-center">
            <h5 class="mb-2">Progreso de proyecto en perÃ­odo 2025 - I</h5>
            <div class="progress w-100">
                <div class="progress-bar bg-success" role="progressbar" style="width: 40%;" 
                    aria-valuenow="40" aria-valuemin="0" aria-valuemax="100">
                </div>
            </div>
        </div>

        <!-- Columna derecha (5 espacios) -->
        <div class="col-md-5 d-flex flex-column justify-content-center align-items-center text-center">
            <span class="fw-bold mb-2">40% para el objetivo</span>
            <small class="text-muted">3 de 8 Ã­tems completados</small>
        </div>
    </div>
</div>
        <!-- Contenido central (ocupa todo el espacio disponible) -->
        <div class="row g-0 flex-grow-1" style="overflow: hidden;">
            <div class="col-md-8 border-end p-3" style="height: 100%; overflow-y: auto;">
                <h5>2.3.5. AnÃ¡lisis de impacto del proyecto ejecutado</h5>
                <p>Lorem ipsum dolor sit amet...</p>
                <p>Contenido adicional...</p>
                <p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae nunc non enim aliquam volutpat. 
Donec vulputate dolor sed mi faucibus, vitae convallis neque tincidunt. Integer vehicula luctus mi, 
sed sodales ligula bibendum nec. Nullam vel dignissim est, eget eleifend ex. Duis non felis nec 
dolor hendrerit efficitur. Suspendisse potenti. Cras porta semper ligula at feugiat. 
Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. 
Sed ut orci non neque viverra pulvinar. Integer maximus, magna sed finibus fermentum, 
turpis ligula varius turpis, vitae tristique lorem ipsum vel augue. 
</p>
<p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae nunc non enim aliquam volutpat. 
Donec vulputate dolor sed mi faucibus, vitae convallis neque tincidunt. Integer vehicula luctus mi, 
sed sodales ligula bibendum nec. Nullam vel dignissim est, eget eleifend ex. Duis non felis nec 
dolor hendrerit efficitur. Suspendisse potenti. Cras porta semper ligula at feugiat. 
Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. 
Sed ut orci non neque viverra pulvinar. Integer maximus, magna sed finibus fermentum, 
turpis ligula varius turpis, vitae tristique lorem ipsum vel augue. 
</p>
            </div>
            <div class="col-md-4 p-3" style="height: 100%; overflow-y: auto;">
                <h5>Â¿CÃ³mo llenar este Ã­tem?</h5>
                <p>Lorem ipsum dolor sit amet...</p>
                <p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae nunc non enim aliquam volutpat. 
Donec vulputate dolor sed mi faucibus, vitae convallis neque tincidunt. Integer vehicula luctus mi, 
sed sodales ligula bibendum nec. Nullam vel dignissim est, eget eleifend ex. Duis non felis nec 
dolor hendrerit efficitur. Suspendisse potenti. Cras porta semper ligula at feugiat. 
Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. 
Sed ut orci non neque viverra pulvinar. Integer maximus, magna sed finibus fermentum, 
turpis ligula varius turpis, vitae tristique lorem ipsum vel augue. 
</p>
<p>
Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed vitae nunc non enim aliquam volutpat. 
Donec vulputate dolor sed mi faucibus, vitae convallis neque tincidunt. Integer vehicula luctus mi, 
sed sodales ligula bibendum nec. Nullam vel dignissim est, eget eleifend ex. Duis non felis nec 
dolor hendrerit efficitur. Suspendisse potenti. Cras porta semper ligula at feugiat. 
Pellentesque habitant morbi tristique senectus et netus et malesuada fames ac turpis egestas. 
Sed ut orci non neque viverra pulvinar. Integer maximus, magna sed finibus fermentum, 
turpis ligula varius turpis, vitae tristique lorem ipsum vel augue. 
</p>
                <button class="btn btn-danger btn-sm mb-1">Descargar PDF</button>
                <button class="btn btn-success btn-sm mb-1">Ver ejemplo</button>
                <button class="btn btn-warning btn-sm mb-1">ExplicaciÃ³n en video</button>
                <button class="btn btn-info btn-sm mb-1">Importancia</button>
            </div>
        </div>

        <!-- Div Inferior (siempre al fondo sin espacio) -->
        <div class="bg-white shadow-sm p-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">

                <div class="btn-group mb-2">
                    <button class="btn btn-success">1</button>
                    <button class="btn btn-success">2</button>
                    <button class="btn btn-success">3</button>
                    <button class="btn btn-success active">4</button>
                    <button class="btn btn-secondary">5</button>
                    <button class="btn btn-secondary">6</button>
                    <button class="btn btn-secondary">7</button>
                    <button class="btn btn-secondary">8</button>
                    <button class="btn btn-secondary">9</button>
                    <button class="btn btn-secondary">10</button>
                    <button class="btn btn-secondary">11</button>
                    <button class="btn btn-secondary">12</button>                    
                </div>
                                <div class="mb-2">
                    <button class="btn btn-success">Guardar e ir al siguiente</button>
                </div>
                <div class="mb-2">
                    <button class="btn btn-primary">Solicitar RevisiÃ³n de Informe Semestral</button>
                </div>
            </div>
        </div>
    </div>
</section>
        </div>
        <footer class="main-footer">
            <strong>Â© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://adminlte.io"> Ãrea  informÃ¡tica - DIRSU</a></p>
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
</body>
</html>

