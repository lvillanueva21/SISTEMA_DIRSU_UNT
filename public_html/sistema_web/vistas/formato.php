<?php include "../componentes/configSesion.php"; ?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Formatos de trabajo - Sistema DIRSU</title>
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
                        <h1 class="m-0">Formatos del proyecto</h1>
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
               <div class="container-fluid">
                  <div class="row">
                     <div class="col-md-12">
                        <div class="card card-primary card-tabs">
<!-- Header de formatos-->
                           <div class="card-header p-0 pt-1">
                              <ul class="nav nav-tabs" id="custom-tabs-five-tab" role="tablist">
                                 <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-five-normal-tab1" data-toggle="pill" href="#custom-tabs-five-normal1" role="tab" aria-controls="custom-tabs-five-normal1" aria-selected="true">Proyecto RSU</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-five-normal-tab2" data-toggle="pill" href="#custom-tabs-five-normal2" role="tab" aria-controls="custom-tabs-five-normal2" aria-selected="false">Monitoreo y seguimiento</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-five-normal-tab3" data-toggle="pill" href="#custom-tabs-five-normal3" role="tab" aria-controls="custom-tabs-five-normal3" aria-selected="false">Informe de RSU</a>
                                 </li>
                              </ul>
                           </div>
<!-- .Header de formatos-->
                           <div class="card-body">
                              <div class="tab-content" id="custom-tabs-five-tabContent">
                                  <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal1" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab1">
                                    <div class="card">
    <div class="card-body row">
        <div class="col-8 text-left d-flex align-items-center justify-content-center">
            <div>
                <h2>Formato de esquema de <strong>Proyectos de RSU</strong></h2>
                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">
                    Este documento proporciona a los miembros del proyecto una guía completa para registrar las generalidades del futuro proyecto. Además, facilita la formulación del Plan de Proyecto y ofrece ejemplos de los anexos requeridos por DIRSU. Este documento es el primer paso en la Formulación y Presentación de proyecto y pertenece a la Fase 1 del Cronograma de Presentación de Proyectos DIRSU.
                </p>
            </div>
        </div>
        <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit" target="_blank">
            <div class="col-4">
                <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                    <i class="far fa-file-word"></i>
                </span>
                <div class="mailbox-attachment-info">
                    <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit" class="mailbox-attachment-name" target="_blank">
                        <i class="fas fa-paperclip"></i> Anexo 4_ESQUEMA PROYECTO RSU.docx
                    </a>
                    <span class="mailbox-attachment-size clearfix mt-1">
                        <span>121 KB</span>
                        <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit" target="_blank" class="btn btn-default btn-sm float-right">
                            <i class="fas fa-cloud-download-alt"></i>
                        </a>
                    </span>
                </div>
            </div>
        </a>
    </div>
</div>

                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                 <div class="tab-pane fade" id="custom-tabs-five-normal2" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab2">
                                    <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal2" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab2">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Formato de monitoreo y seguimiento de ejecución de <strong>Proyectos RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente formato es parte de la Fase 2 de los Proyectos de Responsabilidad Social: Ejecución y Monitoreo. Es clave para evaluar la calidad de los proyectos, basado en cuatro aspectos:

<br>- Organización de la Información.
<br>- Contenido y Coherencia.
<br>- Precisión.
<br>- Ortografía y Redacción.
<br>Este formato asegura una gestión efectiva de los proyectos de RSU, facilitando ajustes y mejoras en su ejecución.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/1g9b4HRIsjkx5rsjdqtTOmJKkPEfm1Ibi/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/1g9b4HRIsjkx5rsjdqtTOmJKkPEfm1Ibi/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> Anexo 7_FORMATO DE MONITOREO Y SEGUIMIENTO DE EJECUCIÓN DE PROYECTOS DE RSU.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>73 KB</span>
                                                <a href="https://docs.google.com/document/d/1g9b4HRIsjkx5rsjdqtTOmJKkPEfm1Ibi/edit" class="btn btn-default btn-sm float-right" target="_blank"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </a>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                 </div>
                                 <div class="tab-pane fade" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Esquema de informe semestral de <strong>Proyecto de RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente esquema forma parte de la Fase 3 de la elaboración de Proyectos titulada: Evaluación e Informe y proporciona una estructura clara para documentar proyectos, incluyendo:

<br>- Generalidades.
<br>- Objetivos, Metas e Indicadores.
<br>- Resultados.
<br>- Cumplimiento de la Carga Horaria.
<br>- Presentación y Aprobación del Informe Semestral.
<br>Este esquema es esencial para evaluar y comunicar el impacto de los proyectos de RSU, asegurando la transparencia y el cumplimiento de los objetivos planteados.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/14dvDBHFufIKKp0XhDid6boNzA3KC15gc/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/14dvDBHFufIKKp0XhDid6boNzA3KC15gc/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> Anexo 8_ ESQUEMA DE INFORME DE RESPONSABILIDAD SOCIAL UNIVERSITARIA.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>85 KB</span>
                                                <a href="https://docs.google.com/document/d/14dvDBHFufIKKp0XhDid6boNzA3KC15gc/edit" target="_blank" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </ac>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
                  <!-- Segunda sección -->
                  <div class="container-fluid">
                     <div class="row">
                        <div class="col-12">
                           <h4>Cotejos y rúbicas de evaluación</h4>
                        </div>
                     </div>
                     <div class="row">
                        <div class="col-md-12">
                           <div class="card card-primary card-tabs">
                              <div class="card-header p-0 pt-1">
                                 <ul class="nav nav-tabs" id="custom-tabs-six-tab" role="tablist">
                                    <li class="nav-item">
                                       <a class="nav-link active" id="tab4" data-toggle="pill" href="#custom-tabs-six-normal1" role="tab" aria-controls="custom-tabs-six-normal1" aria-selected="true">Cotejo de proyecto</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="tab5" data-toggle="pill" href="#custom-tabs-six-normal2" role="tab" aria-controls="custom-tabs-six-normal2" aria-selected="false">Rúbrica de proyecto</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="tab6" data-toggle="pill" href="#custom-tabs-six-normal3" role="tab" aria-controls="custom-tabs-six-normal3" aria-selected="false">Cotejo de informe semestral</a>
                                    </li>
                                    <li class="nav-item">
                                       <a class="nav-link" id="tab6" data-toggle="pill" href="#custom-tabs-six-normal4" role="tab" aria-controls="custom-tabs-six-normal4" aria-selected="false">Rúbrica de informe semestral</a>
                                    </li>
                                 </ul>
                              </div>
                              <div class="card-body">
                                 <div class="tab-content" id="custom-tabs-six-tabContent">
                                    <div class="tab-pane fade show active" id="custom-tabs-six-normal1" role="tabpanel" aria-labelledby="tab4">
                                       <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Lista de cotejo para evaluar el esquema de los <strong>Proyectos de RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente documento es parte de la Fase 1 de la presentación de proyectos de Responsabilidad Social.<br>
                                                   Permite registrar los datos del proyecto a presentar. Una vez llenado, este será subido en el apartado 1.2. Subir informe de proyecto.
                                                   El proyecto será revisado por área de Proyectos de la Oficina de Responsabilidad Social hasta ser aprobado.
                                                   Se puede revisar el progreso del proyecto en el apartado 1.5. Progreso del proyecto.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/1GtM-AfDcfRfUtTV9uhUi7RJFUtt26Y2h/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/1GtM-AfDcfRfUtTV9uhUi7RJFUtt26Y2h/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> ANEXO 5_ LISTA DE COTEJO PARA EVALUAR EL ESQUEMA DE PROYECTO DE RSU.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>346 KB</span>
                                                <a href="https://docs.google.com/document/d/1GtM-AfDcfRfUtTV9uhUi7RJFUtt26Y2h/edit" target="_blank" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </ac>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-six-normal2" role="tabpanel" aria-labelledby="tab5">
                                       <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Rúbrica de calificación para los <strong>Proyectos de RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente documento es parte de la Fase 1 de la presentación de proyectos de Responsabilidad Social.<br>
                                                   Permite registrar los datos del proyecto a presentar. Una vez llenado, este será subido en el apartado 1.2. Subir informe de proyecto.
                                                   El proyecto será revisado por área de Proyectos de la Oficina de Responsabilidad Social hasta ser aprobado.
                                                   Se puede revisar el progreso del proyecto en el apartado 1.5. Progreso del proyecto.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/1OMSd0CKJsNAOMo3q3UPyDLVve2aAHBQP/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/1OMSd0CKJsNAOMo3q3UPyDLVve2aAHBQP/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> ANEXO 6_ RÚBRICA DE CALIFICACIÓN PARA EL PROYECTO DE RSU.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>345 KB</span>
                                                <a href="https://docs.google.com/document/d/1OMSd0CKJsNAOMo3q3UPyDLVve2aAHBQP/edit" target="_blank" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </ac>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-six-normal3" role="tabpanel" aria-labelledby="tab6">
                                       <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Lista de cotejo para evaluar el esquema de informe semestral de los<strong>Proyectos de RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente documento es parte de la Fase 1 de la presentación de proyectos de Responsabilidad Social.<br>
                                                   Permite registrar los datos del proyecto a presentar. Una vez llenado, este será subido en el apartado 1.2. Subir informe de proyecto.
                                                   El proyecto será revisado por área de Proyectos de la Oficina de Responsabilidad Social hasta ser aprobado.
                                                   Se puede revisar el progreso del proyecto en el apartado 1.5. Progreso del proyecto.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/15RQPqVJTUDukv6mVG_lGnSf1Hx1Xdnd2/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/15RQPqVJTUDukv6mVG_lGnSf1Hx1Xdnd2/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> ANEXO 9_ LISTA DE COTEJO PARA EVALUAR EL ESQUEMA DE INFORME DEL PROYECTO DE RSU.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>70 KB</span>
                                                <a href="https://docs.google.com/document/d/15RQPqVJTUDukv6mVG_lGnSf1Hx1Xdnd2/edit" target="_blank" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </ac>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-six-normal4" role="tabpanel" aria-labelledby="tab6">
                                       <!-- INICIO Contenido a doble columna -->
                                 <div class="tab-pane fade show active" id="custom-tabs-five-normal3" role="tabpanel" aria-labelledby="custom-tabs-five-normal-tab3">
                                    <div class="card">
                                       <div class="card-body row">
                                          <div class="col-8 text-left d-flex align-items-center justify-content-center">
                                             <div class="">
                                                <h2>Esquema de informe semestral de <strong>Proyecto de RSU</strong></h2>
                                                <p class="lead mb-5" style="padding-left: 10px; padding-right: 30px; text-align: justify;">El presente documento es parte de la Fase 1 de la presentación de proyectos de Responsabilidad Social.<br>
                                                   Permite registrar los datos del proyecto a presentar. Una vez llenado, este será subido en el apartado 1.2. Subir informe de proyecto.
                                                   El proyecto será revisado por área de Proyectos de la Oficina de Responsabilidad Social hasta ser aprobado.
                                                   Se puede revisar el progreso del proyecto en el apartado 1.5. Progreso del proyecto.
                                                </p>
                                             </div>
                                          </div>
                                          <a href="https://docs.google.com/document/d/1Mbee2jAqKR_rApJrfkANbt5bPn2ljuUr/edit" target="_blank">
                                          <div class="col-4">
                                             <span class="mailbox-attachment-icon" style="background-color: blue; color: white; padding: 10px; border-radius: 5px;">
                                             <i class="far fa-file-word"></i>
                                             </span>
                                             <div class="mailbox-attachment-info">
                                                <a href="https://docs.google.com/document/d/1Mbee2jAqKR_rApJrfkANbt5bPn2ljuUr/edit" target="_blank" class="mailbox-attachment-name"><i class="fas fa-paperclip"></i> ANEXO 10_RUBRICA DE CALIFICACION PARA EL INFORME DEL PROYECTO DE RSU.docx</a>
                                                <span class="mailbox-attachment-size clearfix mt-1">
                                                <span>72 KB</span>
                                                <a href="https://docs.google.com/document/d/1Mbee2jAqKR_rApJrfkANbt5bPn2ljuUr/edit" target="_blank" class="btn btn-default btn-sm float-right"><i class="fas fa-cloud-download-alt"></i></a>
                                                </span>
                                             </div>
                                          </div>
                                          </ac>
                                       </div>
                                    </div>
                                 </div>
                                 <!-- FIN Contenido a doble columna -->
                                    </div>
                                 </div>
                              </div>
                           </div>
                        </div>
                     </div>
                  </div>
               </div>
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
   </body>
</html>