<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
   
   // Incluir el archivo que carga los datos del proyecto
   include('../componentes/proyecto/cargar_proyecto.php'); 
   ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Anexos - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
        <!-- dropzonejs -->
  <link rel="stylesheet" href="../plogins/dropzone/min/dropzone.min.css">
   </head>
   <body class="hold-transition sidebar-mini layout-fixed">
      <div class="wrapper">
         <div class="content-wrapper">
            <section class="content">
   <div class="container-fluid">
      <!-- Stepper -->
      <div class="row">
         <div class="col-md-12">
            <?php if ($id_py == 0) { ?>
               <?php include '../integrados/mensaje_registrar_py.php'; ?>
            <?php } ?>
            <?php if ($id_py != 0) { ?>
               <div class="card card-default">
                  <!-- DATOS PRINCIPALES -->
                  <div class="card card-primary card-tabs">
                     <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                           <li class="pt-2 px-3">
                              <h3 class="card-title">Anexos</h3>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link active" id="custom-tabs-titulo-tab" data-toggle="pill" href="#custom-tabs-titulo" role="tab" aria-controls="custom-tabs-titulo" aria-selected="true">Docentes participantes</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="custom-tabs-interesados-tab" data-toggle="pill" href="#custom-tabs-interesados" role="tab" aria-controls="custom-tabs-interesados" aria-selected="false">Alumnos participantes</a>
                           </li>
                        </ul>
                     </div>
                     <div class="card-body">
                        <div class="tab-content" id="custom-tabs-titulo-tabContent">
                           <div class="tab-pane fade show active" id="custom-tabs-titulo" role="tabpanel" aria-labelledby="custom-tabs-titulo-tab">
<?php
   // Incluir el archivo que descargar lista docentes
   include('../integrados/subir_archivo_docentes.php');
?>
                           </div>
                           <div class="tab-pane fade" id="custom-tabs-interesados" role="tabpanel" aria-labelledby="custom-tabs-interesados-tab">
<!-- subir, ver y descargar lista_alumnos-->
<?php
   // Incluir el archivo que descargar lista docentes
   include('../integrados/subir_archivo_alumnos.php');
?>
<!-- .subir, ver y descargar lista_alumnos-->
                           </div>
                        </div>
                     </div>
                  </div>
                  <!-- /.card-body -->
                  <div class="card-footer">
                     El siguiente formulario se basa en el <a href="https://github.com/Johann-S/bs-stepper/#how-to-use-it">Formato de registro de proyectos DIRSU</a>
                  </div>
               </div>
               <!-- /.card -->
            <?php } ?>
            <!-- /.card -->
         </div>
      </div>
      <!-- /.row -->
   </div>
</section>
         </div>
      </div>
      <script src="../plogins/jquery/jquery.min.js"></script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- bs-custom-file-input -->
<script src="../../plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- Control de botón anterior y siguiente -->

   </body>
</html>