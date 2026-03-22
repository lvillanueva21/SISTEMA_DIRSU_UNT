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
      <title>Mi proyecto - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- Select2 -->
      <link rel="stylesheet" href="../plogins/select2/css/select2.min.css">
      <link rel="stylesheet" href="../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
      <!-- BS Stepper -->
      <link rel="stylesheet" href="../plogins/bs-stepper/css/bs-stepper.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
      <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
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
                  <div class="row mb-0">
                     <div class="col-sm-7">
                        <h1 class="m-0">Mi proyecto</h1>
                     </div>
                     <!-- /.col -->
                     <!-- /.col -->
                  </div>
                  <!-- /.row -->
               </div>
               <!-- /.container-fluid -->
            </div>
            <!-- Main content -->
            <!-- Main content --> 
            <section class="content">
               <?php if ($id_py ==0) { ?>
               <?php
                  // Incluir otro archivo PHP
                  include '../integrados/mensaje_registrar_py.php'; // Aquí se inserta el contenido de 'otro_archivo.php'
                  
                  ?>
               <?php } ?>
               <!-- Default box -->
               <?php if ($id_py !=0) { ?>   
               <div class="card">
                  <div class="card card-primary">
                     <div class="card-header">
                        <h4>
                           <i class="bi bi-card-text"></i> Generalidades
                        </h4>
                     </div>
                  </div>
                  <div class="card-body">
                     <div class="row">
                        <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
                           <!-- 3 divs de Presentación -->
                           <div class="row">
                              <div class="col-12 col-sm-4">
                                 <div class="info-box bg-light" style="background-color: #12377B; color: white; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">
                                    <div class="info-box-content" style="text-align: center;">
                                       <span class="info-box-text">Fecha inicio</span>
                                       <span class="info-box-number mb-0">
                                       <?php echo isset($fecha_inicio) && !empty($fecha_inicio) ? ($fecha_inicio) : 'Vacío'; ?>
                                       </span>
                                       <span class="info-box-text">Fecha fin</span>
                                       <span class="info-box-number mb-0">
                                       <?php echo isset($fecha_fin) && !empty($fecha_fin) ? ($fecha_fin) : 'Vacío'; ?>
                                       </span>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-12 col-sm-4">
                                 <div class="info-box bg-light" style="background-color: #E6AD09; color: black; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">
                                    <div class="info-box-content" style="text-align: center;">
                                       <span class="info-box-text">Lugar de ejecución</span>
                                       <span class="info-box-number mb-0">
                                       <?php
                                          echo isset($sector) && !empty($sector) ? ($sector) : ' ';
                                          echo " ";
                                          echo isset($caserio) && !empty($caserio) ? ($caserio) : ' ';
                                          echo " ";
                                          echo isset($distrito) && !empty($distrito) ? ($distrito) : ' ';
                                          echo " ";
                                          echo isset($provincia) && !empty($provincia) ? ($provincia) : ' ';
                                          echo " ";
                                          echo isset($departamento) && !empty($departamento) ? ($departamento) : ' ';
                                          ?>
                                       </span>
                                    </div>
                                 </div>
                              </div>
                              <div class="col-12 col-sm-4">
                                 <div class="info-box bg-light" style="background-color: #12377B; color: white; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">
                                    <div class="info-box-content" style="text-align: center;">
                                       <span class="info-box-text">Número de beneficiados</span>
                                       <span class="info-box-number mb-0">
                                       <?php
                                          $infantes = isset($infantes) && !empty($infantes) ? (int)$infantes : 0;
                                          $ninos = isset($ninos) && !empty($ninos) ? (int)$ninos : 0;
                                          $adolescentes = isset($adolescentes) && !empty($adolescentes) ? (int)$adolescentes : 0;
                                          $jovenes = isset($jovenes) && !empty($jovenes) ? (int)$jovenes : 0;
                                          $adultos = isset($adultos) && !empty($adultos) ? (int)$adultos : 0;
                                          $adultos_mayores = isset($adultos_mayores) && !empty($adultos_mayores) ? (int)$adultos_mayores : 0;
                                          $total = $infantes + $ninos + $adolescentes + $jovenes + $adultos + $adultos_mayores;
                                          echo $total;
                                          ?>
                                       </span>
                                       <span class="info-box-text">Nivel</span>
                                       <span class="info-box-number mb-0">
                                       <?php
                                          // Array de facultades
                                          $disciplinares = [
                                              '1' => 'Disciplinar',
                                              '2' => 'Interdisciplinar',
                                              '3' => 'Interfacultativo',
                                          ];
                                          
                                          // Verificar si la facultad existe y mostrarla
                                          if (isset($disciplinar) && isset($disciplinares[$disciplinar])) {
                                              echo htmlspecialchars($disciplinares[$disciplinar]);
                                          } else {
                                              echo '<h5>No hay datos registrados</h5>';
                                          }
                                          ?>
                                       </span>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <!-- .3 divs de Presentación -->
                           <div class="row">
                              <div class="col-12">
                                 <br>
                                 <div class="card card-primary">
                                    <div class="card-header">
                                       <h4>
                                          <i class="bi bi-diagram-3"></i> Plan de Proyecto
                                       </h4>
                                    </div>
                                 </div>
                                 <!-- Información de Plan de Proyecto-->
                                 <!-- Diagnostico-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/diagnostico.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Diagnóstico</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($diagnostico) && !empty($diagnostico) ? ($diagnostico) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Justificación-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Justificación</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($justificacion) && !empty($justificacion) ? ($justificacion) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Objetivo General-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/general.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Objetivo General</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($general) && !empty($general) ? ($general) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Metodología-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/especificos.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Objetivos Específicos</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($especificos) && !empty($especificos) ? ($especificos) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Metas por semestre-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/metas.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Metas por semestre</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($metas) && !empty($metas) ? ($metas) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Cronograma1-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/cronograma1.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Cronograma Actual</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($cronograma1) && !empty($cronograma1) ? ($cronograma1) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Cronograma2-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/cronograma2.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Próximos Cronogramas</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($cronograma2) && !empty($cronograma2) ? ($cronograma2) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Metodología-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/metodologia.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Metodología</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($metodologia) && !empty($metodologia) ? ($metodologia) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Entregables-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/entregables.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Entregables a los beneficiarios</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($entregables) && !empty($entregables) ? ($entregables) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Tipo de impacto-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/impacto.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Tipo de impacto</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($impacto) && !empty($impacto) ? ($impacto) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Matriz-->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/matriz.png" alt="user image">
                                       <span class="username">
                                       <b class="text-primary">Matriz de indicadores de impacto</b>
                                       </span>
                                    </div>
                                    <p>
                                    <p class="text-muted" style="text-align: justify;"><?php echo isset($matriz) && !empty($matriz) ? ($matriz) : 'Vacío'; ?></p>
                                    </p>
                                 </div>
                                 <!-- Financiamiento -->
                                 <div class="post">
                                    <div class="user-block">
                                       <img class="img-sm" src="../imagenes/plan_proyecto/financiamiento.png" alt="financiamiento">
                                       <span class="username"><b class="text-primary">Presupuesto del proyecto</b></span>
                                    </div>
                                    <div class="card-body row">
                                       <div class="col-md-6">
                                          <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#bienes_disponibles"><i class="fas fa-box"></i></i> Bienes Disponibles</button>
                                          <button type="button" class="btn btn-info btn-block btn-flat" data-toggle="modal" data-target="#servicios_disponibles"><i class="fas fa-cogs"></i> Servicios Disponibles</button>
                                          <button type="button" class="btn btn-danger btn-block btn-sm" data-toggle="modal" data-target="#resumen"><i class="fas fa-file-alt"></i> Resumen Total</button>
                                       </div>
                                       <div class="col-md-6">
                                          <button type="button" class="btn btn-outline-primary btn-block" data-toggle="modal" data-target="#bienes_no_disponibles"><i class="fas fa-box"></i></i> Bienes No disponibles</button>
                                          <button type="button" class="btn btn-outline-info btn-block btn-flat" data-toggle="modal" data-target="#servicios_no_disponibles"><i class="fas fa-cogs"></i> Servicios No disponibles</button>
                                          <button type="button" class="btn btn-outline-danger btn-block btn-sm" data-toggle="modal" data-target="#financiamiento"><i class="fas fa-dollar-sign"></i> Financiamiento</button>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- .Información de Plan de Proyecto-->
                              </div>
                           </div>
                           <div style="text-align: right;">
                              <a href="desarrollo_informe.php" class="btn btn-sm btn-warning">Actualizar Plan de Proyecto</a>
                           </div>
                        </div>
                        <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
                           <h5 class="text-primary" style="text-align: justify;">
                              <i class="bi bi-file-earmark"></i> Programa: 
                              <?php echo htmlspecialchars($p1); ?>
                           </h5>
                           <div class="text-muted" style="text-align: justify;">
                              <p class="text-sm">Título:
                                 <b class="d-block"><?php echo htmlspecialchars($p2); ?></b>
                              </p>
                              <p class="text-sm">Coordinador de proyecto:
                                 <b class="d-block"><?php echo htmlspecialchars($nombres) . " " . htmlspecialchars($apellidos); ?></b>
                              </p>
                              <!-- Elegir que falcultad se mostrará -->
                              <p class="text-sm">Facultad:
                                 <b class="d-block"><?php
                                    // Array de facultades
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
                                        '13' => 'Medicina',
                                    ];
                                    
                                    // Verificar si la facultad existe y mostrarla
                                    if (isset($facultad) && isset($facultades[$facultad])) {
                                        echo htmlspecialchars($facultades[$facultad]);
                                    } else {
                                        echo '<h5>No hay facultad</h5>';
                                    }
                                    ?></b>
                              </p>
                              <!-- Elegir que programa de estudios se mostrará -->
                              <p class="text-sm">Programa de estudios
                                 <b class="d-block">
                                 <?php
                                    // Array de programas de estudios
                                    $programas_estudios = [
                                        '1' => 'Administración',
                                        '2' => 'Agronomía',
                                        '3' => 'Antropología',
                                        '4' => 'Arqueología',
                                        '5' => 'Arquitectura y Urbanismo',
                                        '6' => 'Biología Pesquera',
                                        '7' => 'Ciencias Biológicas',
                                        '8' => 'Ciencias de la Comunicación',
                                        '9' => 'Ciencias Políticas y Gobernabilidad',
                                        '10' => 'Contabilidad y Finanzas',
                                        '11' => 'Derecho',
                                        '12' => 'Economía',
                                        '13' => 'Educación Inicial',
                                        '14' => 'Educación Primaria',
                                        '15' => 'Educación Secundaria Mención Ciencias Naturales',
                                        '16' => 'Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales',
                                        '17' => 'Educación Secundaria Mención Historia y Geografía',
                                        '18' => 'Educación Secundaria Mención Idiomas',
                                        '19' => 'Educación Secundaria Mención Lengua y Literatura',
                                        '20' => 'Educación Secundaria Mención Matemáticas',
                                        '21' => 'Enfermería',
                                        '22' => 'Estadística',
                                        '23' => 'Estomatología',
                                        '24' => 'Farmacia y Bioquímica',
                                        '25' => 'Física',
                                        '26' => 'Historia',
                                        '27' => 'Informática',
                                        '28' => 'Ingeniería Agrícola',
                                        '29' => 'Ingeniería Agroindustrial',
                                        '30' => 'Ingeniería Ambiental',
                                        '31' => 'Ingeniería Civil',
                                        '32' => 'Ingeniería de Materiales',
                                        '33' => 'Ingeniería de Minas',
                                        '34' => 'Ingeniería de Sistemas',
                                        '35' => 'Ingeniería Industrial',
                                        '36' => 'Ingeniería Mecánica',
                                        '37' => 'Ingeniería Mecatrónica',
                                        '38' => 'Ingeniería Metalúrgica',
                                        '39' => 'Ingeniería Química',
                                        '40' => 'Matemáticas',
                                        '41' => 'Medicina',
                                        '42' => 'Microbiología y Parasitología',
                                        '43' => 'Trabajo Social',
                                        '44' => 'Turismo',
                                        '45' => 'Zootecnia',
                                    ];
                                    
                                    // Verificar si el programa de estudios existe y mostrarlo
                                    if (isset($programa_estudios) && isset($programas_estudios[$programa_estudios])) {
                                        echo htmlspecialchars($programas_estudios[$programa_estudios]);
                                    } else {
                                        echo '<h5>No hay programa de estudios seleccionado</h5>';
                                    }
                                    ?>
                                 </b>
                              </p>
                              <!-- Elegir que departamento académico mostrar -->
                              <p class="text-sm">Departamento académico
                                 <b class="d-block">
                                 <?php
                                    // Array de departamentos académicos
                                    $departamentos_academicos = [
                                        '1' => 'Agronomía y Zootecnia',
                                        '2' => 'Ciencias Agroindustriales',
                                        '3' => 'Ciencias Biológicas',
                                        '4' => 'Microbiología y Parasitología',
                                        '5' => 'Pesquería',
                                        '6' => 'Química Biológica y Fisiología Animal',
                                        '7' => 'Administración',
                                        '8' => 'Contabilidad y Finanzas',
                                        '9' => 'Economía',
                                        '10' => 'Ciencias Básicas Estomatológicas',
                                        '11' => 'Estomatología',
                                        '12' => 'Estadística',
                                        '13' => 'Física',
                                        '14' => 'Informática',
                                        '15' => 'Matemáticas',
                                        '16' => 'Arqueología y Antropología',
                                        '17' => 'Ciencias Sociales',
                                        '18' => 'Ciencias Jurídicas Públicas y Políticas',
                                        '19' => 'Ciencias Jurídicas Privadas y Sociales',
                                        '20' => 'Ciencia Política y Gobernabilidad',
                                        '21' => 'Ciencias de la Educación',
                                        '22' => 'Ciencias Psicológicas',
                                        '23' => 'Comunicación Social',
                                        '24' => 'Filosofía y Arte',
                                        '25' => 'Historia y Geografía',
                                        '26' => 'Idiomas y Lingüística',
                                        '27' => 'Lengua Nacional y Literatura',
                                        '28' => 'Enfermería de la Mujer, Niño y Adolescente',
                                        '29' => 'Salud del Adulto',
                                        '30' => 'Salud Familiar y Comunitaria',
                                        '31' => 'Farmacotecnia',
                                        '32' => 'Farmacología',
                                        '33' => 'Bioquímica',
                                        '34' => 'Ingeniería Civil, Arquitectura y Urbanismo',
                                        '35' => 'Ingeniería Industrial',
                                        '36' => 'Ingeniería de Materiales',
                                        '37' => 'Mecánica y Energía',
                                        '38' => 'Ingeniería Metalúrgica',
                                        '39' => 'Ingeniería de Minas',
                                        '40' => 'Ingeniería de Sistemas',
                                        '41' => 'Ingeniería Química',
                                        '42' => 'Ingeniería Ambiental',
                                        '43' => 'Química',
                                        '44' => 'Ciencias Básicas Médicas',
                                        '45' => 'Cirugía',
                                        '46' => 'Fisiología Humana',
                                        '47' => 'Ginecología-Obstetricia',
                                        '48' => 'Medicina',
                                        '49' => 'Medicina Preventiva y Salud Pública',
                                        '50' => 'Morfología Humana',
                                        '51' => 'Pediatría',
                                        '52' => 'Ingeniería Mecatrónica',
                                    ];
                                    
                                    // Verificar si el departamento académico existe y mostrarlo
                                    if (isset($departamento_academico) && isset($departamentos_academicos[$departamento_academico])) {
                                        echo htmlspecialchars($departamentos_academicos[$departamento_academico]);
                                    } else {
                                        echo '<h5>No hay departamento académico seleccionado</h5>';
                                    }
                                    ?>
                                 </b>
                              </p>
                              <!-- Elegir que ODS mostrar -->
                              <p class="text-sm">ODS del proyecto:
                                 <b class="d-block"><?php
                                    // Array de ODS (Objetivos de Desarrollo Sostenible)
                                    $ods = [
                                        '1' => 'ODS1: Reducción de los indicadores de la pobreza',
                                        '2' => 'ODS2: Hambre y seguridad alimentaria',
                                        '3' => 'ODS3: Salud y bienestar',
                                        '4' => 'ODS4: Educación de calidad',
                                        '5' => 'ODS5: Igualdad de género y empoderamiento de la mujer',
                                        '6' => 'ODS6: Agua limpia y saneamiento',
                                        '7' => 'ODS7: Energía asequible y no contaminante',
                                        '8' => 'ODS8: Trabajo decente y crecimiento económico',
                                        '9' => 'ODS9: Industria, innovación e infraestructura',
                                        '10' => 'ODS10: Reducir las desigualdades',
                                        '11' => 'ODS11: Ciudades y comunidades sostenibles',
                                        '12' => 'ODS12: Producción y consumo responsables',
                                        '13' => 'ODS13: Acción por el clima',
                                        '14' => 'ODS14: Vida submarina',
                                        '15' => 'ODS15: Vida y ecosistemas terrestres',
                                        '16' => 'ODS16: Paz y justicia e instituciones sólidas',
                                        '17' => 'ODS17: Alianzas para lograr los objetivos'
                                    ];
                                    
                                    // Verificar si $p3 tiene valores seleccionados
                                    if (isset($p3) && !empty($p3)) {
                                        // Recorrer los valores seleccionados y mostrarlos
                                        foreach ($p3 as $valor) {
                                            if (isset($ods[$valor])) {
                                                echo '<button type="button" class="btn btn-primary m-1">' . htmlspecialchars($ods[$valor]) . '</button>';
                                            }
                                        }
                                    } else {
                                        echo '<p>No se ha seleccionado ningún ODS.</p>';
                                    }
                                    ?>
                                 </b>
                              </p>
                              <p class="text-sm">Tipo de programa:
                                 <b class="d-block"><?php
                                    // Array de tipos de proyecto
                                    $tipos_proyecto = [
                                        '1' => 'Programas de formación continua y formación de capacidades',
                                        '2' => 'Consultoría/asesoría',
                                        '3' => 'Gestión cultural',
                                        '4' => 'Desarrollo económico y social',
                                        '5' => 'Desarrollo humano y democracia',
                                        '6' => 'Desarrollo técnico científico sostenible',
                                        '7' => 'Protección del medio ambiente',
                                        '8' => 'Innovación',
                                        '9' => 'Creatividad',
                                        '10' => 'Otras áreas de acuerdo a las necesidades de la comunidad',
                                        '11' => 'Salud'
                                    ];
                                    
                                    // Verificar si $p4 tiene valores seleccionados
                                    if (isset($p4) && !empty($p4)) {
                                        // Recorrer los valores seleccionados y mostrarlos como botones
                                        foreach ($p4 as $valor) {
                                            if (isset($tipos_proyecto[$valor])) {
                                                echo '<button type="button" class="btn btn-primary m-1">' . htmlspecialchars($tipos_proyecto[$valor]) . '</button>';
                                            }
                                        }
                                    } else {
                                        echo '<p>No se ha seleccionado ningún tipo de proyecto.</p>';
                                    }
                                    ?>
                                 </b>
                              </p>
                           </div>
                           <!-- -->
                           <table class="table">
                              <tr>
                                 <td><i class="bi bi-people"></i></td>
                                 <td>
                                    <p class="text-sm">Grupo(s) de Interés a los que está orientado el proyecto</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#poblacion">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-exclamation-circle"></i></td>
                                 <td>
                                    <p class="text-sm">Necesidades y/o problemas de los Grupo(s) de Interés</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#necesidades">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-building"></i></td>
                                 <td>
                                    <p class="text-sm">Instituciones participantes</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#instituciones">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-person"></i></td>
                                 <td>
                                    <p class="text-sm">Poblaciones participantes</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#poblaciones">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-clock"></i></td>
                                 <td>
                                    <p class="text-sm">Fases del proyecto</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#fases">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-person-lines-fill"></i></td>
                                 <td>
                                    <p class="text-sm">Coordinador de componentes del proyecto</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#componentes">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-book"></i></td>
                                 <td>
                                    <p class="text-sm">Integrantes del equipo de docentes</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#docentes">Ver</button>
                                 </td>
                              </tr>
                              <tr>
                                 <td><i class="bi bi-person-circle"></i></td>
                                 <td>
                                    <p class="text-sm">Representantes o delegados del equipo de estudiantes</p>
                                 </td>
                                 <td>
                                    <button type="button" class="btn btn-success m-1" data-toggle="modal" data-target="#alumnos">Ver</button>
                                 </td>
                              </tr>
                           </table>
                           <div style="text-align: right;">
                              <a href="datos_principales.php" class="btn btn-sm btn-warning">Actualizar Generalidades</a>
                           </div>
                           <!-- modal 1-->
                           <div class="modal fade" id="poblacion">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Grupo(s) de Interés a los que está orientado el proyecto</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($p5) && !empty($p5) ? ($p5) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 1-->
                           <!-- modal 2-->
                           <div class="modal fade" id="necesidades">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Necesidades y/o problemas de los Grupo(s) de Interés</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($p6) && !empty($p6) ? ($p6) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 2-->
                           <!-- modal 3-->
                           <div class="modal fade" id="instituciones">
                              <div class="modal-dialog modal-lg">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Instituciones participantes</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($p7_1) && !empty($p7_1) ? ($p7_1) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 3-->
                           <!-- modal 4-->
                           <div class="modal fade" id="poblaciones">
                              <div class="modal-dialog modal-lg">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Poblaciones participantes</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($p7_2) && !empty($p7_2) ? ($p7_2) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 4-->
                           <!-- modal 5-->
                           <div class="modal fade text-center" id="fases">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Fases del proyecto</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <!-- TABLE fases -->
                                       <table class="table">
                                          <thead>
                                             <tr>
                                                <th style="width: 10%;">Fases</th>
                                                <th style="width: 60%;">Descripción</th>
                                                <th style="width: 8%;">N° Semanas</th>
                                                <th style="width: 14%;">N° horas por semanas</th>
                                                <th style="width: 8%;">Total de horas</th>
                                             </tr>
                                          </thead>
                                          <tbody>
                                             <tr>
                                                <td>Planificación</td>
                                                <td><input type="text" class="form-control" id="planificacion" name="planificacion" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($planificacion); ?>" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_1s" name="p10_1s" min="0" max="10000" step="1" value="<?php echo isset($p10_1s) ? intval($p10_1s) : '0'; ?>" oninput="updateTotal(1)" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_1h" name="p10_1h" min="0" max="10000" step="1" value="<?php echo isset($p10_1h) ? intval($p10_1h) : '0'; ?>" oninput="updateTotal(1)" readonly></td>
                                                <td><input type="text" class="form-control" id="total1" value="0" readonly></td>
                                             </tr>
                                             <tr>
                                                <td>Ejecución</td>
                                                <td><input type="text" class="form-control" id="ejecucion" name="ejecucion" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($ejecucion); ?>" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_2s" name="p10_2s" min="0" max="10000" step="1" value="<?php echo isset($p10_2s) ? intval($p10_2s) : '0'; ?>" oninput="updateTotal(2)" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_2h" name="p10_2h" min="0" max="10000" step="1" value="<?php echo isset($p10_2h) ? intval($p10_2h) : '0'; ?>" oninput="updateTotal(2)" readonly></td>
                                                <td><input type="text" class="form-control" id="total2" value="0" readonly readonly></td>
                                             </tr>
                                             <tr>
                                                <td>Monitoreo y Evaluación</td>
                                                <td><input type="text" class="form-control" id="monitoreo" name="monitoreo" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($monitoreo); ?>" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_3s" name="p10_3s" min="0" max="10000" step="1" value="<?php echo isset($p10_3s) ? intval($p10_3s) : '0'; ?>" oninput="updateTotal(3)" readonly></td>
                                                <td><input type="number" class="form-control" id="p10_3h" name="p10_3h" min="0" max="10000" step="1" value="<?php echo isset($p10_3h) ? intval($p10_3h) : '0'; ?>" oninput="updateTotal(3)" readonly></td>
                                                <td><input type="text" class="form-control" id="total3" value="0" readonly readonly></td>
                                             </tr>
                                          </tbody>
                                          <tfoot>
                                             <tr>
                                                <td colspan="4"><strong>Total</strong></td>
                                                <td><input type="text" class="form-control" id="grandTotal" value="0" readonly></td>
                                             </tr>
                                          </tfoot>
                                       </table>
                                       <!-- .TABLE fases -->
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 5-->
                           <!-- modal 6-->
                           <div class="modal fade" id="componentes">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Coordinador de componentes del proyecto</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($componentes) && !empty($componentes) ? ($componentes) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 6-->
                           <!-- modal 7-->
                           <div class="modal fade" id="docentes">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Integrantes del equipo de docentes</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($integrantes_docentes) && !empty($integrantes_docentes) ? ($integrantes_docentes) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 7-->
                           <!-- modal 8-->
                           <div class="modal fade" id="alumnos">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header bg-secondary text-white">
                                       <h4 class="modal-title">Representantes o delegados del equipo de estudiantes</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($delegados_estudiantes) && !empty($delegados_estudiantes) ? ($delegados_estudiantes) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 8-->
                           <!-- modal 9-->
                           <div class="modal fade" id="bienes_disponibles">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Bienes Disponibles</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($pre_dis) && !empty($pre_dis) ? ($pre_dis) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 9-->
                           <!-- modal 10-->
                           <div class="modal fade" id="bienes_no_disponibles">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Bienes No Disponibles</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($pre_nodis) && !empty($pre_nodis) ? ($pre_nodis) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 10-->
                           <!-- modal 11-->
                           <div class="modal fade" id="servicios_disponibles">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Servicios Disponibles</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($ser_dis) && !empty($ser_dis) ? ($ser_dis) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 11-->
                           <!-- modal 12-->
                           <div class="modal fade" id="servicios_no_disponibles">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Servicios No Disponibles</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($ser_nodis) && !empty($ser_nodis) ? ($ser_nodis) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 12-->
                           <!-- modal 13-->
                           <div class="modal fade" id="resumen">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Resumen Total</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
                                       <p><?php echo isset($resumen) && !empty($resumen) ? ($resumen) : 'Sin información registrada ...'; ?></p>
                                    </div>
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 13-->
                           <!-- modal 14-->
                           <div class="modal fade" id="financiamiento">
                              <div class="modal-dialog modal-xl">
                                 <div class="modal-content">
                                    <div class="modal-header">
                                       <h4 class="modal-title">Financiamiento</h4>
                                       <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                       <span aria-hidden="true">&times;</span>
                                       </button>
                                    </div>
                                    <div class="modal-body">
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
                                                      oninput="updateTotal2()" readonly></td>
                                                </tr>
                                                <tr>
                                                   <td>Autofinanciamiento</td>
                                                   <td><input type="text" class="form-control" id="monto_auto" name="monto_auto" placeholder="0.00" 
                                                      value="<?php echo isset($monto_auto) ? htmlspecialchars(number_format($monto_auto, 2, ',', '')) : '0.00'; ?>" 
                                                      oninput="updateTotal2()" readonly></td>
                                                </tr>
                                                <tr>
                                                   <td>Otras</td>
                                                   <td><input type="text" class="form-control" id="monto_otro" name="monto_otro" placeholder="0.00" 
                                                      value="<?php echo isset($monto_otro) ? htmlspecialchars(number_format($monto_otro, 2, ',', '')) : '0.00'; ?>" 
                                                      oninput="updateTotal2()" readonly></td>
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
                                    <div class="modal-footer justify-content-between">
                                       <button type="button" class="btn btn-secondary ml-auto" data-dismiss="modal">Cerrar</button>
                                    </div>
                                 </div>
                                 <!-- /.modal-content -->
                              </div>
                              <!-- /.modal-dialog -->
                           </div>
                           <!-- .modal 14-->
                           <!-- Anexos-->
                           <BR>
                           <div class="card card-primary">
                              <div class="card-header">
                                 <h4>
                                    <i class="far fa-file-pdf"></i> Anexos del Proyecto
                                 </h4>
                              </div>
                           </div>
                           <?php include '../componentes/archivo/invocar_archivos.php'; ?>
                           <div class="text-center mt-5 mb-3">
                              <div style="text-align: right;">
                                 <a href="anexos.php" class="btn btn-sm btn-warning">Actualizar Anexos</a>
                              </div>
                           </div>
                           <!-- .Anexos-->
                        </div>
                     </div>
                  </div>
                  <!-- /.card-body -->
               </div>
               <?php } ?>
               <!-- /.card -->
            </section>
            <!-- /.content -->
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
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
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
             $('#reservationdate').datetimepicker({
                 format: 'L'
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
         function updateTotal2() {
             var montoUni = parseFloat(document.getElementById('monto_uni').value.replace(',', '.')) || 0;
             var montoAuto = parseFloat(document.getElementById('monto_auto').value.replace(',', '.')) || 0;
             var montoOtro = parseFloat(document.getElementById('monto_otro').value.replace(',', '.')) || 0;
         
             var total2 = montoUni + montoAuto + montoOtro;
             document.getElementById('total_montos').value = total2.toFixed(2);
         }
         
         function validateForm() {
             var inputs2 = ['monto_uni', 'monto_auto', 'monto_otro'];
             for (var j = 0; j < inputs2.length; j++) {
                 var value2 = document.getElementById(inputs2[j]).value.replace(',', '.');
                 if (isNaN(parseFloat(value2))) {
                     alert('Por favor, ingrese un monto válido en ' + inputs2[j]);
                     return false; // Evitar el envío
                 }
             }
             return true; // Permitir el envío
         }
         
         function updateTotal(row) {
             var horasPorSemana = parseFloat(document.getElementById('p10_' + row + 'h').value) || 0;
             var semanas = parseFloat(document.getElementById('p10_' + row + 's').value) || 0;
         
             var total = horasPorSemana * semanas;
         
             document.getElementById('total' + row).value = total;
         
             updateGrandTotal();
         }
         
         function updateGrandTotal() {
             var total1 = parseFloat(document.getElementById('total1').value) || 0;
             var total2 = parseFloat(document.getElementById('total2').value) || 0;
             var total3 = parseFloat(document.getElementById('total3').value) || 0;
         
             var grandTotal = total1 + total2 + total3;
         
             document.getElementById('grandTotal').value = grandTotal;
         }
         
         window.onload = function() {
             updateTotal2(); // Actualiza el total al cargar la página
             updateTotal(1); // Para Planificación
             updateTotal(2); // Para Ejecución
             updateTotal(3); // Para Monitoreo y Evaluación
         }
      </script>
   </body>
</html>