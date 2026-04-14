<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
   include_once('../componentes/cronograma/visibilidad_fase1.php');
   include_once('../includes/access/project_interface_guard.php');
   // Incluir el archivo que carga los datos del proyecto
   include('../componentes/proyecto/cargar_proyecto.php');
   // Establecer la zona horaria a Lima, Perú
   date_default_timezone_set('America/Lima');
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
        <!-- dropzonejs -->
  <link rel="stylesheet" href="../plogins/dropzone/min/dropzone.min.css">
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
               </li>
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a> 
               </li>
            </ul>
         </nav>
         <!-- /.navbar -->
         <!-- Main Sidebar Container -->
         <!-- Contenedor de barra lateral principal -->
         <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
         <!-- Content Wrapper. Contains page content -->
         <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
               <div class="container-fluid">
                  <div class="row mb-2">
                     <div class="col-sm-7">
                        <h1 class="m-0">1.3. Anexos</h1>
                     </div>
                     <!-- /.col -->
                     <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                           <li class="breadcrumb-item"><a href="../inicio.php">Inicio</a></li>
                           <li class="breadcrumb-item active">Formulación y presentación</li>
                           <li class="breadcrumb-item active">Anexos</li>
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
         <?php
// 1. Verificar si $id_py es diferente de cero
if ($id_py == 0) {
    include '../integrados/mensaje_registrar_py.php';
} else {
    // Control de acceso centralizado por período activo, cronograma e interfaz.
    $rsu_access_eval = rsu_project_interface_guard($conexion, 'F1-ANEXOS');
    $result = false;
    $cron = array(
        'inicio' => '1970-01-01 00:00:00',
        'fin' => '1970-01-01 00:00:00'
    );
    if (!empty($rsu_access_eval['allow'])) {
        $result = true;
        $cron = array(
            'inicio' => isset($rsu_access_eval['interfaz_resuelta']['inicio']) ? (string)$rsu_access_eval['interfaz_resuelta']['inicio'] : '1970-01-01 00:00:00',
            'fin' => isset($rsu_access_eval['interfaz_resuelta']['fin']) ? (string)$rsu_access_eval['interfaz_resuelta']['fin'] : '1970-01-01 00:00:00'
        );
    }

    if ($result) {

        // 5. Convertir las fechas de inicio y fin a objetos DateTime
        $inicio = new DateTime($cron['inicio']);
        $fin    = new DateTime($cron['fin']);
        $ahora  = new DateTime(); // Hora actual en Lima (zona horaria establecida)

        // Verificar si la fecha actual está dentro del rango permitido
        if ($ahora >= $inicio && $ahora <= $fin) {
            // Validar el estado de la sesión (variable de sesión $estado)
            // Si $estado es igual a 1, se muestra el mensaje de solicitud de revisión
            if ($estado == 1) {
                include '../integrados/solicitud_revision_py.php';
            } else {
                // Si $estado es 0, se muestra el contenido controlado
                ?>
                  <!-- Inicio del bloque de código que se muestra -->   
               <div class="card card-default">
                  <!-- DATOS PRINCIPALES -->
                  <div class="card card-primary card-tabs">
                     <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="myTab" role="tablist">
                           <li class="pt-2 px-3">
                              <h3 class="card-title">Anexos</h3>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link active" id="lista_docentes-tab" data-toggle="tab" href="#lista_docentes" role="tab" aria-controls="lista_docentes" aria-selected="true">Lista Docentes</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="lista_alumnos-tab" data-toggle="tab" href="#lista_alumnos" role="tab" aria-controls="lista_alumnos" aria-selected="false">Lista Alumnos</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="visto_bueno-tab" data-toggle="tab" href="#visto_bueno" role="tab" aria-controls="visto_bueno" aria-selected="false">Visto Bueno</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="diagrama-tab" data-toggle="tab" href="#diagrama" role="tab" aria-controls="diagrama" aria-selected="false">Diagrama</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="compromiso-tab" data-toggle="tab" href="#compromiso" role="tab" aria-controls="compromiso" aria-selected="false">Compromiso</a>
                           </li>
                           <li class="nav-item">
                              <a class="nav-link" id="carta-tab" data-toggle="tab" href="#carta" role="tab" aria-controls="carta" aria-selected="false">Carta</a>
                           </li>
                        </ul>
                     </div>
                     <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="lista_docentes" role="tabpanel" aria-labelledby="lista_docentes-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="lista_alumnos" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->                              
                              <form action="../componentes/archivo/subir_lista_docentes.php" method="post" enctype="multipart/form-data">
                                 <label>1. Lista de docentes que forman parte del equipo de trabajo</label>
                                 <br>
                                 <p>La lista se debe subir en formato Excel (.xls, .xlsx) para facilitar el tratamiento y filtrado de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_docentes" name="file" accept=".xlsx, .xls" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_lista_docentes.php'; ?>
                           </div>
                        </div>
                        <div class="tab-pane fade" id="lista_alumnos" role="tabpanel" aria-labelledby="lista_alumnos-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="lista_docentes" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="visto_bueno" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                              <form action="../componentes/archivo/subir_lista_alumnos.php" method="post" enctype="multipart/form-data">
                                 <label>2. Lista de alumnos que forman parte del equipo de trabajo</label>
                                 <br>
                                 <p>La lista se debe subir en formato Excel (.xls, .xlsx) para facilitar el tratamiento y filtrado de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_alumnos" name="file" accept=".xlsx, .xls" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_lista_alumnos.php'; ?>
                           </div>
                        </div>
                        <div class="tab-pane fade" id="visto_bueno" role="tabpanel" aria-labelledby="visto_bueno-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="lista_alumnos" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="diagrama" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                              <form action="../componentes/archivo/subir_visto_bueno.php" method="post" enctype="multipart/form-data">
                                 <label>3. Documento de Visto Bueno del proyecto firmado por autoridades universitarias y administrativas.</label>
                                 <br>
                                 <p>El documento se debe subir en formato Pdf (.pdf) para facilitar la visualización de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_visto_bueno" name="file" accept=".pdf" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_visto_bueno.php'; ?>
                           </div>
                        </div>
                        <div class="tab-pane fade" id="diagrama" role="tabpanel" aria-labelledby="diagrama-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="visto_bueno" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="compromiso" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                              <form action="../componentes/archivo/subir_diagrama.php" method="post" enctype="multipart/form-data">
                                 <label>4. Diagrama del proyecto (Árbol de problemas, Espina de Ishikawa y Los 5 Porqués)</label>
                                 <br>
                                 <p>El documento se debe subir en formato Pdf (.pdf) para facilitar la visualización de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_diagrama" name="file" accept=".pdf" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_diagrama.php'; ?>
                           </div>
                        </div>
                        <div class="tab-pane fade" id="compromiso" role="tabpanel" aria-labelledby="compromiso-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="diagrama" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="carta" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                              <form action="../componentes/archivo/subir_compromiso.php" method="post" enctype="multipart/form-data">
                                 <label>5. Documento de Compromiso Ético</label>
<br>
<p>
  Para completar este requisito, <strong>descarga y edita</strong> el siguiente formato. 
  Posteriormente, deberás <strong>subirlo en formato PDF (.pdf)</strong> para facilitar la visualización. 
  <span style="color: red;">Recuerda: solo puedes subir un archivo; si subes otro, reemplazará al anterior.</span>
</p>
<p>
  <span style="color: black;">Haz clic en el botón de abajo para obtener el formato oficial del Compromiso Ético.</span> 
</p>
<a href="../recursos/formatos/FORMATO_DE_COMPROMISO_ETICO.docx" target="_blank" 
   style="display: inline-block; background-color: #007bff; color: white; padding: 10px 15px; 
          text-decoration: none; border-radius: 5px; font-weight: bold;">
   📄 Descargar Formato de Compromiso Ético
</a>
<br><br>
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_compromiso" name="file" accept=".pdf" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_compromiso.php'; ?>
                           </div>
                        </div>
                        <div class="tab-pane fade" id="carta" role="tabpanel" aria-labelledby="carta-tab">
                           <div class="card-body">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="compromiso" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<!-- Navegación -->
                              <form action="../componentes/archivo/subir_carta.php" method="post" enctype="multipart/form-data">
                                 <label>6. documento de Carta de intención</label>
                                 <br>
                                 <p>El documento se debe subir en formato Pdf (.pdf) para facilitar la visualización de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
                                 
                                 <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                    <tbody>
                                       <tr>
                                          <td style="width: 35%; text-align: center; vertical-align: middle; height: 100px;">
                                             <input type="file" id="file_carta" name="file" accept=".pdf" required>
                                          </td>
                                          <td style="width: 65%; text-align: center; vertical-align: middle; height: 100px;">
                                             <button type="submit" class="btn btn-primary">Subir al sistema</button>
                                          </td>
                                       </tr>
                                    </tbody>
                                 </table>
                              </form>
                              <?php include '../componentes/archivo/ver_carta.php'; ?>
                           </div>
                        </div>
                     </div>
                  </div>
                  <!-- /.card-body -->
                  <div class="card-footer">
                     El siguiente formulario se basa en el <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit">Formato de esquema de proyectos de RSU</a>
                  </div>
               </div>
               <!-- /.card -->
            <!-- Fin del bloque de código que se muestra -->
            <?php
            }
        } else {
            // Si la fecha actual está fuera del rango permitido
            include '../integrados/mensaje_fuera_tiempo.php';
        }
    } else {
        // Si no se encontró un cronograma que cumpla las condiciones
        include '../integrados/mensaje_fuera_tiempo.php';
    }
}
?>
            <!-- /.card -->
         </div>
      </div>
      <!-- /.row -->
   </div>
   <!-- /.container-fluid -->
   
   <!-- MODAL -->
   <div class="modal fade" id="uploadSuccessModal" tabindex="-1" role="dialog" aria-labelledby="uploadSuccessModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadSuccessModalLabel">Archivo subido con éxito</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
    <div class="modal-body d-flex">
    <div class="text-section d-flex align-items-center" style="flex: 1; padding-right: 10px;">
        <div>
            <p id="modalFileName" class="mb-0"></p>
            <p id="modalFileSize" class="mb-0"></p> <!-- Añade este párrafo para mostrar el tamaño -->
        </div>
    </div>
    <div class="image-section" style="flex: 1; display: flex; justify-content: center; align-items: center;">
        <img src="../imagenes/subida_exitosa.jpg" alt="Descripción de la imagen" style="max-width: 100%; max-height: 300px;">
    </div>
</div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
   <!-- .MODAL -->
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
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- bs-custom-file-input -->
<script src="../../plugins/bs-custom-file-input/bs-custom-file-input.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- Control de botón anterior y siguiente -->
<script>
    $(document).ready(function() {
        $('.next-tab').on('click', function() {
            var nextTabId = $(this).data('next-tab');
            $('.nav-tabs .nav-item a[href="#' + nextTabId + '"]').tab('show');
        });
        
        $('.prev-tab').on('click', function() {
            var prevTabId = $(this).data('prev-tab');
            $('.nav-tabs .nav-item a[href="#' + prevTabId + '"]').tab('show');
        });
    });
</script>
<script>
    $(document).ready(function() {
        $('form').on('submit', function(event) {
            event.preventDefault(); // Evitar el envío del formulario tradicional
            var formData = new FormData(this);
            $.ajax({
                url: $(this).attr('action'),
                type: 'POST',
                data: formData,
                contentType: false,
                processData: false,
                success: function(response) {
                    var result = JSON.parse(response);
                    if (result.success) {
    $('#modalFileName').html('<b>Detalles:</b><br><br>' + 'Nombre de archivo:<br><i>' + result.fileName + '</i>');
    $('#modalFileSize').html('Tamaño:<br><i>' + (result.fileSize / 1024).toFixed(2) + ' KB</i>'); // Convertir a KB
    $('#uploadSuccessModal').modal('show');
} else {
    alert(result.error); // Mostrar error en caso de fallo
}
                },
                error: function() {
                    alert('Error en la solicitud. Inténtalo de nuevo más tarde.');
                }
            });
        });
        // Recargar la página al cerrar el modal
        $('#uploadSuccessModal').on('hidden.bs.modal', function () {
            location.reload();
        });
    });
</script>
   </body>
</html>
