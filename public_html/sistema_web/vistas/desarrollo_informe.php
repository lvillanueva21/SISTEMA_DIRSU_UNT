<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
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
      <title>Plan de proyecto - Sistema DIRSU</title>
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
         <aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="../inicio.php" class="brand-link">
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
                     <a href="perfil.php" class="d-block"><?php echo $texto_a_imprimir; ?></a>
</div>

               </div>
               <!-- SidebarSearch Form -->
               <!-- Sidebar Menu -->
               <nav class="mt-2">
                  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                     <!-- Add icons to the links using the .nav-icon class
                        with font-awesome or any other icon font library -->
                     <li class="nav-item">
                        <a href="../inicio.php" class="nav-link">
                           <i class="nav-icon fas fa-home"></i>
                           <p>
                              INICIO
                              <!-- <span class="right badge badge-danger">2</span> -->
                           </p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="guia.php" class="nav-link">
                           <i class="fa fa-road nav-icon"></i>
                           <p>Guía de trabajo</p>
                        </a>
                     </li>
                     <!-- INICIO - SUB MENU MI PROYECTO - NIVEL 1 -->
                     <!-- Texto simple dentro de una side bar, sin enlaces  -->
                     <li class="nav-header">Información de proyecto</li>
                     <li class="nav-item">
                        <a href="proyecto.php" class="nav-link">
                           <i class="fa fa-users nav-icon"></i>
                           <p>Mi proyecto</p>
                        </a>
                     </li>
                     <li class="nav-item"> 
    <a href="progreso.php" class="nav-link">
        <i class="fa fa-chart-line nav-icon"></i>
        <p>Mi progreso</p>
    </a>
                     <li class="nav-item">
                        <a href="formato.php" class="nav-link">
                           <i class="fa fa-file-word nav-icon"></i>
                           <p>Formatos</p>
                        </a>
                     </li>
                     <!-- FIN - SUB MENU MI PROYECTO - NIVEL 1 -->
                     <!-- Texto simple dentro de una side bar, sin enlaces  -->
                     <li class="nav-header">Fases de proyecto</li>
                     <!-- INICIO - SUB MENU FASE 1 - NIVEL 1 -->
                     <li class="nav-item menu menu-open">
                        <a href="#" class="nav-link active">
                           <span class="badge nav-icon">1</span>
                           <p>
                              Formulación y presentación
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="datos_principales.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.1. Generalidades</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="desarrollo_informe.php" class="nav-link active">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.2. Plan de proyecto</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="anexos.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>1.3. Anexos</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <!-- FIN - SUB MENU FASE 1 - NIVEL 1 -->
                     <!-- INICIO - SUB MENU FASE 2 - NIVEL 1 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">2</span>
                           <p>
                              Ejecución y monitoreo
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="cronograma.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>2.1. Cronograma de ejecución</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_cronograma.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>2.2. Revisión de cronograma</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <!-- FIN - SUB MENU FASE 2 - NIVEL 1 -->
                     <!-- INICIO - SUB MENU FASE 3 - NIVEL 1 -->
                     <li class="nav-item menu">
                        <a href="#" class="nav-link">
                           <span class="badge nav-icon">3</span>
                           <p>
                              Evaluación e informe
                              <!-- <i class="right fas fa-angle-left"></i> -->
                           </p>
                        </a>
                        <ul class="nav nav-treeview">
                           <li class="nav-item">
                              <a href="../semestral/index.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>3.1. Informe semestral</p>
                              </a>
                           </li>
                           <li class="nav-item">
                              <a href="revision_informe_final.php" class="nav-link">
                                 <!-- <i class="far fa-circle nav-icon"></i> -->
                                 <p>3.2. Revisión de informe</p>
                              </a>
                           </li>
                        </ul>
                     </li>
                     <li class="nav-item" style="height: 100px;"></li>
                     <!-- FIN - SUB MENU FASE 3 - NIVEL 1 -->
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
                     <div class="col-sm-7">
                        <h1 class="m-0">1.2. Plan de proyecto</h1>
                     </div>
                     <!-- /.col -->
                     <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                           <li class="breadcrumb-item"><a href="../inicio.php">Inicio</a></li>
                           <li class="breadcrumb-item active">Formulación y presentación</li>
                           <li class="breadcrumb-item active">Plan de proyecto</li>
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
    // 2, 3 y 4. Consultar la tabla de cronogramas para el período 2024-II, código F1-GENERALIDADES y estado 1
    $sql = "SELECT * FROM cronogramas 
            WHERE periodo = '2024-II' 
              AND codigo = 'F1-GENERALIDADES' 
              AND estado = 1 
            LIMIT 1";
    $result = mysqli_query($conexion, $sql);

    if ($result && mysqli_num_rows($result) > 0) {
        $cron = mysqli_fetch_assoc($result);

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
                                 <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                                    <li class="pt-2 px-3">
                                       <h3 class="card-title">Plan</h3>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link active" id="custom-tabs-diagnostico-tab" data-toggle="pill" href="#custom-tabs-diagnostico" role="tab" aria-controls="custom-tabs-diagnostico" aria-selected="true">Diagnóstico</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-objetivos-tab" data-toggle="pill" href="#custom-tabs-objetivos" role="tab" aria-controls="custom-tabs-objetivos" aria-selected="false">objetivos</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-cronograma-tab" data-toggle="pill" href="#custom-tabs-cronograma" role="tab" aria-controls="custom-tabs-cronograma" aria-selected="false">Cronograma</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-metodologia-tab" data-toggle="pill" href="#custom-tabs-metodologia" role="tab" aria-controls="custom-tabs-metodologia" aria-selected="false">Metodología</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-impacto-tab" data-toggle="pill" href="#custom-tabs-impacto" role="tab" aria-controls="custom-tabs-impacto" aria-selected="false">Impacto</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-bienes-tab" data-toggle="pill" href="#custom-tabs-bienes" role="tab" aria-controls="custom-tabs-bienes" aria-selected="false">Bienes</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-servicios-tab" data-toggle="pill" href="#custom-tabs-servicios" role="tab" aria-controls="custom-tabs-servicios" aria-selected="false">Servicios</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="custom-tabs-financiamiento-tab" data-toggle="pill" href="#custom-tabs-financiamiento" role="tab" aria-controls="custom-tabs-financiamiento" aria-selected="false">Financiamiento</a>
                                    </li>
                                 </ul>
                              </div>
                              <div class="card-body">
                                 <div class="tab-content" id="custom-tabs-diagnostico-tabContent">
                                    <div class="tab-pane fade show active" id="custom-tabs-diagnostico" role="tabpanel" aria-labelledby="custom-tabs-diagnostico-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-objetivos" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_diagnostico.php" method="POST">
                                          <div class="form-group">
                                             <label for="inputDiagnostico">14. Responsables del proyecto por la Universidad Nacional de Trujillo</label>
                                             <h6>14.1. Diagnóstico</h6>
                                             <div class="card-body">
                                                <textarea id="summernote" name="diagnostico">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($diagnostico) && !empty($diagnostico) ? htmlspecialchars($diagnostico) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Redacta o copia desde un  ARCHIVO WORD el diagnóstico del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea> 
                                             </div>
                                          </div>
                                        <div class="form-group">
                                             <h6>14.2. Justificación del proyecto</h6>
                                             <div class="card-body">
                                                <textarea id="summernote2" name="justificacion">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($justificacion) && !empty($justificacion) ? htmlspecialchars($justificacion) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Redacta o copia desde un  ARCHIVO WORD la justificación del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>  
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </form>
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-objetivos" role="tabpanel" aria-labelledby="custom-tabs-objetivos-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-diagnostico" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-cronograma" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_objetivos.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.3. Objetivos del proyecto</h6>
                                             <br>
                                             <h6>14.3.1 Objetivo general</h6>
                                             <div class="card-body">
                                                <textarea id="summernote3" name="general">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($general) && !empty($general) ? htmlspecialchars($general) : 'Redacta o copia desde un  ARCHIVO WORD el objetivo general del proyecto.'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
            <div class="form-group">
                                             <h6>14.3.2 Objetivos específicos</h6>
                                             <div class="card-body">
                                                <textarea id="summernote4" name="especificos">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($especificos) && !empty($especificos) ? htmlspecialchars($especificos) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Redacta o copia desde un  ARCHIVO WORD los objetivos específicos del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
            <div class="form-group">
                                             <h6>14.3.3 Metas por semestre</h6>
                                             <div class="card-body">
                                                <textarea id="summernote5" name="metas">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($metas) && !empty($metas) ? htmlspecialchars($metas) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Redacta o copia desde un  ARCHIVO WORD las metas del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                             <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                          </div>
                                       </form>
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-cronograma" role="tabpanel" aria-labelledby="custom-tabs-cronograma-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-objetivos" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-metodologia" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_cronograma.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.4 Cronograma de actividades</h6><br>
                                             <h6>Cronograma actual</h6>
                                             <div class="card-body">
                                                <textarea id="summernote6" name="cronograma1">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($cronograma1) && !empty($cronograma1) ? htmlspecialchars($cronograma1) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Copia y pega tu cronograma desde un archivo WORD.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>Cronogramas secundarios</h6>
                                             <div class="card-body">
                                                <textarea id="summernote7" name="cronograma2">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($cronograma2) && !empty($cronograma2) ? htmlspecialchars($cronograma2) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Copia y pega tu cronograma desde un archivo WORD.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                    </div>
                                    
                                    <div class="tab-pane fade" id="custom-tabs-metodologia" role="tabpanel" aria-labelledby="custom-tabs-metodologia-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-cronograma" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-impacto" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_metodologia.php" method="POST">
                                        <div class="form-group">
                                             <h6>14.5 Metodología</h6>
                                             <div class="card-body">
                                                <textarea id="summernote8" name="metodologia">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($metodologia) && !empty($metodologia) ? htmlspecialchars($metodologia) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Redacta o copia desde un  ARCHIVO WORD la metodología del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>  
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </form>
                                       
                                    </div>
                                    
                                    <div class="tab-pane fade" id="custom-tabs-impacto" role     ="tabpanel" aria-labelledby="custom-tabs-impacto-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-metodologia" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-bienes" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_impacto.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.6 Entregables a los beneficiarios</h6>
                                             <div class="card-body">
                                                <textarea id="summernote9" name="entregables">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($entregables) && !empty($entregables) ? htmlspecialchars($entregables) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia desde un  ARCHIVO WORD los entregables a los beneficiarios del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>14.7 Tipo de impacto</h6>
                                             <div class="card-body">
                                                <textarea id="summernote10" name="impacto">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($impacto) && !empty($impacto) ? htmlspecialchars($impacto) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody><table class="table table-bordered"><tbody><tr><td><h1 style="text-align: center; "><span style="font-family: &quot;Times New Roman&quot;;">IMPACTO</span></h1></td><td style="text-align: center;"><h1><span style="font-family: &quot;Times New Roman&quot;;">DESCRIPCIÓN</span></h1></td></tr><tr><td><blockquote class="blockquote"><b>SOCIAL</b></blockquote></td><td><span style="font-family: &quot;Times New Roman&quot;;">No se ha registrado información de impacto social (Edita este texto para añadir una descripción)</span></td></tr><tr><td><blockquote class="blockquote"><b>AMBIENTAL</b></blockquote></td><td><span style="font-family: &quot;Times New Roman&quot;;">No se ha registrado información de impacto ambiental (Edita este texto para añadir una descripción)</span></td></tr><tr><td><blockquote class="blockquote"><b>ECONÓMICO</b></blockquote></td><td><span style="font-family: &quot;Times New Roman&quot;;">No se ha registrado información de impacto económico (Edita este texto para añadir una descripción)</span></td></tr></tbody></table></tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>14.8 Matriz de indicadores de impacto</h6> 
                                             <div class="card-body">
                                                <textarea id="summernote11" name="matriz">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($matriz) && !empty($matriz) ? htmlspecialchars($matriz) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Copia y pega tu matriz de indicadores desde un archivo WORD.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                       <!-- .RESPONSABLES -->
                                    </div>
                                    
                                    <div class="tab-pane fade" id="custom-tabs-bienes" role     ="tabpanel" aria-labelledby="custom-tabs-bienes-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-impacto" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-servicios" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_bienes.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.9 Presupuesto</h6><br>
<div class="row">
    <div class="col-md-8">
        <p class="text-sm">Antes de ingresar la información, se recomienda revisar el Clasificador Económico de Gastos para el año final 2024.</p>
    </div>
    <div class="col-md-2 text-start">
    <a href="https://cdn.www.gob.pe/uploads/document/file/5627853/4986320-anexo-ii-clasificador-economico-de-gastos-para-el-ano-fiscal-2024.pdf?v=1704045258" class="btn btn-danger btn-block btn-sm" target="_blank">
        <i class="fa fa-book"></i> Ver clasificador
    </a>
</div>
</div>

                                             <h6>14.9.1 Bienes disponibles</h6>
                                             <div class="card-body">
                                                <textarea id="summernote12" name="pre_dis">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($pre_dis) && !empty($pre_dis) ? htmlspecialchars($pre_dis) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia el cuadro de Presupuesto de los Bienes Disponibles.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>14.9.2 Bienes no disponibles</h6>
                                             <div class="card-body">
                                                <textarea id="summernote13" name="pre_nodis">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($pre_nodis) && !empty($pre_nodis) ? htmlspecialchars($pre_nodis) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia el cuadro de Presupuesto de los Bienes No Disponibles.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                       <!-- .RESPONSABLES -->
                                    </div>
                                    
                                    <div class="tab-pane fade" id="custom-tabs-servicios" role     ="tabpanel" aria-labelledby="custom-tabs-servicios-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-bienes" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<div class="text-right" style="position: absolute; top: 55px; right: 10px;">
        <button class="btn btn-app bg-primary next-tab" 
                data-next-tab="custom-tabs-financiamiento" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_servicios.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.9.3 Servicios</h6><br>
<div class="row">
    <div class="col-md-8">
        <p class="text-sm">Antes de ingresar la información, se recomienda revisar el Clasificador Económico de Gastos para el año final 2024.</p>
    </div>
    <div class="col-md-2 text-start">
    <a href="https://cdn.www.gob.pe/uploads/document/file/5627853/4986320-anexo-ii-clasificador-economico-de-gastos-para-el-ano-fiscal-2024.pdf?v=1704045258" class="btn btn-danger btn-block btn-sm" target="_blank">
        <i class="fa fa-book"></i> Ver clasificador
    </a>
</div>
</div>                                            
                                             <h6>Disponibles</h6>
                                             <div class="card-body">
                                                <textarea id="summernote14" name="ser_dis">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($ser_dis) && !empty($ser_dis) ? htmlspecialchars($ser_dis) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia el cuadro de Presupuesto de los Servicios Disponibles.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>No disponibles</h6>
                                             <div class="card-body">
                                                <textarea id="summernote15" name="ser_nodis">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($ser_nodis) && !empty($ser_nodis) ? htmlspecialchars($ser_nodis) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia el cuadro de Presupuesto de los Servicios No Disponibles.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea> 
                                             </div>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                       <!-- .RESPONSABLES -->
                                    </div>
                                    
                                    
                                    
                                    <div class="tab-pane fade" id="custom-tabs-financiamiento" role     ="tabpanel" aria-labelledby="custom-tabs-financiamiento-tab">
<!-- Navegación -->
<div class="text-right" style="position: absolute; top: 55px; right: 100px;">
        <button class="btn btn-app bg-primary prev-tab" 
                data-prev-tab="custom-tabs-servicios" 
                style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
        </button>
    </div>
<!-- Navegación -->
                                       <form action="../componentes/proyecto/actualizar_financiamiento.php" method="POST">
                                          <div class="form-group">
                                             <h6>14.9.4 Resumen total</h6>
                                             <div class="card-body">
                                                <textarea id="summernote16" name="resumen">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($resumen) && !empty($resumen) ? htmlspecialchars($resumen) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Copia y pega tu resumen financiero desde un archivo WORD.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>14.9.5 Financiamiento</h6>
                                             <div class="card-body">
<!-- -->
<table class="table">
    <thead>
        <tr>
            <th>Fuente</th>
            <th>Monto</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td>Universidad y/o Facultad</td>
            <td><input type="text" class="form-control" id="monto_uni" name="monto_uni" placeholder="0.00" 
                           value="<?php echo isset($monto_uni) ? htmlspecialchars(number_format($monto_uni, 2, ',', '')) : '0.00'; ?>" 
                           oninput="updateTotal()"></td>
        </tr>
        <tr>
            <td>Autofinanciamiento</td>
            <td><input type="text" class="form-control" id="monto_auto" name="monto_auto" placeholder="0.00" 
                           value="<?php echo isset($monto_auto) ? htmlspecialchars(number_format($monto_auto, 2, ',', '')) : '0.00'; ?>" 
                           oninput="updateTotal()"></td>
        </tr>
        <tr>
            <td>Otras</td>
            <td><input type="text" class="form-control" id="monto_otro" name="monto_otro" placeholder="0.00" 
                           value="<?php echo isset($monto_otro) ? htmlspecialchars(number_format($monto_otro, 2, ',', '')) : '0.00'; ?>" 
                           oninput="updateTotal()"></td>
        </tr>
        <tr>
            <td><strong>Total</strong></td>
            <td><input type="text" class="form-control" id="total_montos" value="0.00" readonly></td>
        </tr>
    </tbody>
</table>
<!-- -->
                                             </div>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                       <!-- .RESPONSABLES -->
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <!-- /.card-body -->
                           <div class="card-footer">
                     El siguiente formulario se basa en el <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit">Formato de esquema de proyectos de RSU</a>
                  </div>
                        </div>
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
      <!-- InputMask -->
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/inputmask/jquery.inputmask.min.js"></script>
      <!-- date-range-picker -->
      <script src="../plogins/daterangepicker/daterangepicker.js"></script>
      <!-- Tempusdominus Bootstrap 4 -->
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
      <!-- Summernote -->
      <script src="../plogins/summernote/summernote-bs4.min.js"></script>
      <script src="../plogins/summernote/lang/summernote-es-ES.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- Page specific script -->
      <script>
         $(function () {
             //Initialize Select2 Elements
             $('.select2').select2()
         
             //Initialize Select2 Elements
             $('.select2bs4').select2({
               theme: 'bootstrap4'
             })
         
             //Datemask dd/mm/yyyy
             $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
             //Datemask2 mm/dd/yyyy
             $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
             //Money Euro
             $('[data-mask]').inputmask()
         
            //Date picker
            $('#startdate').datetimepicker({
            format: 'DD/MM/YYYY'
            });
            
            $('#enddate').datetimepicker({
            format: 'DD/MM/YYYY'
            });
         
             //Date and time picker
             $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });
         
             //Date range picker
             $('#reservation').daterangepicker()
             //Date range picker with time picker
             $('#reservationtime').daterangepicker({
               timePicker: true,
               timePickerIncrement: 30,
               locale: {
                 format: 'MM/DD/YYYY hh:mm A'
               }
             })
             //Date range as a button
             $('#daterange-btn').daterangepicker(
               {
                 ranges   : {
                   'Today'       : [moment(), moment()],
                   'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month'  : [moment().startOf('month'), moment().endOf('month')],
                   'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                 },
                 startDate: moment().subtract(29, 'days'),
                 endDate  : moment()
               },
               function (start, end) {
                 $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
               }
             )
         
             //Timepicker
             $('#timepicker').datetimepicker({
               format: 'LT'
             })
         
             //Bootstrap Duallistbox
             $('.duallistbox').bootstrapDualListbox()
         
             //Colorpicker
             $('.my-colorpicker1').colorpicker()
             //color picker with addon
             $('.my-colorpicker2').colorpicker()
         
             $('.my-colorpicker2').on('colorpickerChange', function(event) {
               $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
             })
         
             $("input[data-bootstrap-switch]").each(function(){
               $(this).bootstrapSwitch('state', $(this).prop('checked'));
             })
         
           })
           // BS-Stepper Init
           document.addEventListener('DOMContentLoaded', function () {
             window.stepper = new Stepper(document.querySelector('.bs-stepper'))
           })
         
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote2').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote3').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote4').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote5').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote6').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote7').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote8').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote9').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote10').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote11').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote12').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote13').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote14').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote15').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
      <script>
         $(function () {
           // Summernote
           $('#summernote16').summernote({
         lang: 'es-ES'
         })
         
         })
      </script>
<script>
    function updateTotal() {
        var montoUni = parseFloat(document.getElementById('monto_uni').value.replace(',', '.')) || 0;
        var montoAuto = parseFloat(document.getElementById('monto_auto').value.replace(',', '.')) || 0;
        var montoOtro = parseFloat(document.getElementById('monto_otro').value.replace(',', '.')) || 0;
        
        var total = montoUni + montoAuto + montoOtro;
        document.getElementById('total_montos').value = total.toFixed(2);
    }

    function validateForm() {
        // Validar que los inputs sean decimales válidos
        var inputs = ['monto_uni', 'monto_auto', 'monto_otro'];
        for (var i = 0; i < inputs.length; i++) {
            var value = document.getElementById(inputs[i]).value.replace(',', '.');
            if (isNaN(parseFloat(value))) {
                alert('Por favor, ingrese un monto válido en ' + inputs[i]);
                return false; // Evitar el envío
            }
        }
        return true; // Permitir el envío
    }

    window.onload = function() {
        updateTotal(); // Actualiza el total al cargar la página
    }
</script>


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
   </body>
</html>