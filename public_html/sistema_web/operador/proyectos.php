<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
   
   // Incluir el archivo que carga los datos del proyecto
   include('../componentes/operador/cargar_proyectos.php'); 
   ?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>DIRSU - PROYECTOS</title>
  <!-- Favicon -->
      <link href="../../imagenes/dirsu_128_128.ico" rel="icon">
  <!-- Google Font: Source Sans Pro -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <!-- DataTables -->
  <link rel="stylesheet" href="../plogins/datatables-bs4/css/dataTables.bootstrap4.min.css">
  <link rel="stylesheet" href="../plogins/datatables-responsive/css/responsive.bootstrap4.min.css">
  <link rel="stylesheet" href="../plogins/datatables-buttons/css/buttons.bootstrap4.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../dust/css/adminlte.min.css">
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<!-- Site wrapper -->
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
                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
                     <p style="color: white;
                        size: 8px">Ir a página DIRSU</p>
                  </a>
               </li>
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
               </li>
            </ul>
         </nav>  
  <!-- /.navbar -->
  <!-- Main Sidebar Container -->
<aside class="main-sidebar sidebar-dark-primary elevation-4">
            <!-- Brand Logo -->
            <a href="." class="brand-link">
            <img src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
            <span class="brand-text font-weight-light">Sistema DIRSU</span>
            </a>
    <!-- Sidebar -->
    <div class="sidebar">
               <!-- Sidebar user panel (optional) -->
               <div class="user-panel mt-3 pb-3 mb-3 d-flex">
                  <div class="image">
                     <img src="../dust/img/user2-160x160.jpg" class="img-circle elevation-2" alt="User Image">
                  </div>
                  <div class="info">
                     <a href="vistas/perfil.php" class="d-block"><?php echo htmlspecialchars($nombres) . " " . htmlspecialchars($apellidos); ?></a>
                  </div>
               </div>
      <!-- SidebarSearch Form -->
      <div class="form-inline">
        <div class="input-group" data-widget="sidebar-search">
          <input class="form-control form-control-sidebar" type="search" placeholder="Search" aria-label="Search">
          <div class="input-group-append">
            <button class="btn btn-sidebar">
              <i class="fas fa-search fa-fw"></i>
            </button>
          </div>
        </div>
      </div>
      <!-- Sidebar Menu -->
               <nav class="mt-2">
                  <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                     <!-- Add icons to the links using the .nav-icon class
                        with font-awesome or any other icon font library -->
                     <li class="nav-item">
                        <a href="." class="nav-link active">
                           <i class="nav-icon fas fa-home"></i>
                           <p>
                              INICIO
                              <span class="right badge badge-danger">2</span>
                           </p>
                        </a>
                     </li>
                     <!-- INICIO - SUB MENU MI PROYECTO - NIVEL 1 -->
                     <!-- Texto simple dentro de una side bar, sin enlaces  -->
                     <li class="nav-header">Información de proyecto</li>
                     <li class="nav-item">
                        <a href="proyectos.php" class="nav-link">
                           <i class="fa fa-users nav-icon"></i>
                           <p>Proyecto</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="vistas/ruta.php" class="nav-link">
                           <i class="fa fa-road nav-icon"></i>
                           <p>Ruta de trabajo</p>
                        </a>
                     </li>
                     <li class="nav-item">
                        <a href="vistas/formato.php" class="nav-link">
                           <i class="fa fa-file-word nav-icon"></i>
                           <p>Formatos</p>
                        </a>
                     </li>
                     <!-- FIN - SUB MENU MI PROYECTO - NIVEL 1 -->
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
    <section class="content-header">
      <div class="container-fluid">
        <div class="row mb-2">
          <div class="col-sm-6">
            <h1>Proyectos</h1>
          </div>
          <div class="col-sm-6">
            <ol class="breadcrumb float-sm-right">
              <li class="breadcrumb-item"><a href="#">Inicio</a></li>
              <li class="breadcrumb-item active">Proyectos</li>
            </ol>
          </div>
        </div>
      </div><!-- /.container-fluid -->
    </section>

    <!-- Main content -->
    <section class="content">
      <!-- /.card -->
<div class="card">
              <div class="card-header">
                <h3 class="card-title">Revisión de proyectos - PERÍODO 2024</h3>
              </div>
              <!-- /.card-header -->
              <div class="card-body">
                <table id="example1" class="table table-bordered table-striped">
    <thead>
        <tr>
            <th style="width: 3%">#</th>
            <th style="width: 30%">Título</th>
            <th style="width: 16%">Departamento Académico</th>
            <th style="width: 15%">Coordinador</th>
            <th style="width: 16%">Estado</th>
            <th style="width: 22%">Acciones</th>
        </tr>
    </thead>
    <tbody>
        <?php
if (!empty($proyectos)) {
    $numero = 1; // Para mostrar el número de cada fila
    foreach ($proyectos as $proyecto) {
        echo "<tr>";
        echo "<td>" . $numero++ . "</td>";
        echo "<td>" . htmlspecialchars($proyecto['p2']) . "</td>";
        echo "<td>" . htmlspecialchars($proyecto['departamento_academico']) . "</td>";
        echo "<td>" . htmlspecialchars($proyecto['coordinador']) . "</td>";
        
        // Mostrar botón de estado
        echo "<td>";
        if ($proyecto['estado'] == 0) {
            echo "<button class='btn btn-success'>En registro</button>";
        } else {
            echo htmlspecialchars($proyecto['estado']);
        }
        echo "</td>";

        // Columna de acciones (Info y Calificar)
        echo "<td>";
        echo "<button class='btn btn-secondary info-btn' data-id='" . htmlspecialchars($proyecto['id']) . "' data-toggle='modal' data-target='#modal-xl'><i class='fas fa-info-circle'></i> Info</button>";
        echo "<button class='btn btn-warning'><i class='fas fa-star'></i> Calificar</button>";
        echo "</td>";
        echo "</tr>";
    }
} else {
    echo "<tr><td colspan='6'>No se encontraron proyectos.</td></tr>";
}
?>
    </tbody>
    <tfoot>
        <tr>
            <th style="width: 3%">#</th>
            <th style="width: 30%">Título</th>
            <th style="width: 16%">Departamento Académico</th>
            <th style="width: 15%">Coordinador</th>
            <th style="width: 16%">Estado</th>
            <th style="width: 22%">Acciones</th>
        </tr>
    </tfoot>
</table>

<!-- Modal para mostrar el ID -->
<!-- Modal -->
<!-- Modal -->
<!-- Modal Extra Large -->
<div class="modal fade" id="modal-xl">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title">Información del Proyecto</h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p>El ID del proyecto es: <span id="projectId"></span></p>
                <!-- MODAL CON TODA LA INFO DEL PROYECTO -->
                <div class="card">
        <div class="card-header">
          <h3 class="card-title">Projects Detail</h3>

          <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse" title="Collapse">
              <i class="fas fa-minus"></i>
            </button>
            <button type="button" class="btn btn-tool" data-card-widget="remove" title="Remove">
              <i class="fas fa-times"></i>
            </button>
          </div>
        </div>
        <div class="card-body">
          <div class="row">
            <div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">
              <div class="row">
                <div class="col-12 col-sm-4">
                  <div class="info-box bg-light">
                    <div class="info-box-content">
                      <span class="info-box-text text-center text-muted">Estimated budget</span>
                      <span class="info-box-number text-center text-muted mb-0">2300</span>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="info-box bg-light">
                    <div class="info-box-content">
                      <span class="info-box-text text-center text-muted">Total amount spent</span>
                      <span class="info-box-number text-center text-muted mb-0">2000</span>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-sm-4">
                  <div class="info-box bg-light">
                    <div class="info-box-content">
                      <span class="info-box-text text-center text-muted">Estimated project duration</span>
                      <span class="info-box-number text-center text-muted mb-0">20</span>
                    </div>
                  </div>
                </div>
              </div>
              <div class="row">
                <div class="col-12">
                  <h4>Recent Activity</h4>
                    <div class="post">
                      <div class="user-block">
                        <img class="img-circle img-bordered-sm" src="../../dist/img/user1-128x128.jpg" alt="user image">
                        <span class="username">
                          <a href="#">Jonathan Burke Jr.</a>
                        </span>
                        <span class="description">Shared publicly - 7:45 PM today</span>
                      </div>
                      <!-- /.user-block -->
                      <p>
                        Lorem ipsum represents a long-held tradition for designers,
                        typographers and the like. Some people hate it and argue for
                        its demise, but others ignore.
                      </p>

                      <p>
                        <a href="#" class="link-black text-sm"><i class="fas fa-link mr-1"></i> Demo File 1 v2</a>
                      </p>
                    </div>

                
                </div>
              </div>
            </div>
            <div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">
              <h3 class="text-primary"><i class="fas fa-paint-brush"></i> <?php echo htmlspecialchars($proyecto['p1']); ?></h3>
              <p class="text-muted"><?php echo htmlspecialchars($proyecto['p1']); ?>  Raw denim you probably haven't heard of them jean shorts Austin. Nesciunt tofu stumptown aliqua butcher retro keffiyeh dreamcatcher synth. Cosby sweater eu banh mi, qui irure terr.</p>
              <br>
              <div class="text-muted">
                <p class="text-sm">Client Company
                  <b class="d-block">Deveint Inc</b>
                </p>
                <p class="text-sm">Project Leader
                  <b class="d-block">Tony Chicken</b>
                </p>
              </div>

              <h5 class="mt-5 text-muted">Project files</h5>
              <ul class="list-unstyled">
                <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-file-word"></i> Functional-requirements.docx</a>
                </li>
                <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-file-pdf"></i> UAT.pdf</a>
                </li>
                <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-envelope"></i> Email-from-flatbal.mln</a>
                </li>
                <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-image "></i> Logo.png</a>
                </li>
                <li>
                  <a href="" class="btn-link text-secondary"><i class="far fa-fw fa-file-word"></i> Contract-10_12_2014.docx</a>
                </li>
              </ul>
              <div class="text-center mt-5 mb-3">
                <a href="#" class="btn btn-sm btn-primary">Add files</a>
                <a href="#" class="btn btn-sm btn-warning">Report contact</a>
              </div>
            </div>
          </div>
        </div>
        <!-- /.card-body -->
      </div>
                <!-- .MODAL CON TODA LA INFO DEL PROYECTO -->
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-default" data-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-primary">Calificar</button>
            </div>
        </div>
        <!-- /.modal-content -->
    </div>
    <!-- /.modal-dialog -->
</div>



              </div>
            </div>
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
<!-- DataTables  & Plugins -->
<script src="../plogins/datatables/jquery.dataTables.min.js"></script>
<script src="../plogins/datatables-bs4/js/dataTables.bootstrap4.min.js"></script>
<script src="../plogins/datatables-responsive/js/dataTables.responsive.min.js"></script>
<script src="../plogins/datatables-responsive/js/responsive.bootstrap4.min.js"></script>
<script src="../plogins/datatables-buttons/js/dataTables.buttons.min.js"></script>
<script src="../plogins/datatables-buttons/js/buttons.bootstrap4.min.js"></script>
<script src="../plogins/jszip/jszip.min.js"></script>
<script src="../plogins/pdfmake/pdfmake.min.js"></script>
<script src="../plogins/pdfmake/vfs_fonts.js"></script>
<script src="../plogins/datatables-buttons/js/buttons.html5.min.js"></script>
<script src="../plogins/datatables-buttons/js/buttons.print.min.js"></script>
<script src="../plogins/datatables-buttons/js/buttons.colVis.min.js"></script>
<!-- AdminLTE App -->
<script src="../dust/js/adminlte.min.js"></script>
<!-- AdminLTE for demo purposes -->
<script src="../dust/js/demo.js"></script>
<!-- Page specific script -->
<script>
  $(function () {
    $("#example1").DataTable({
      "responsive": true,"lengthChange": false,"autoWidth": false,"buttons": [{extend: 'copy',text: 'Copiar'},{extend: 'csv',text: 'CSV'},
        {extend: 'excel',text: 'Excel'},{extend: 'pdf',text: 'PDF'},{extend: 'print',text: 'Imprimir'},{extend: 'colvis',text: 'Columnas visibles'}
      ],
      "language": {
        "url": '//cdn.datatables.net/plug-ins/2.1.6/i18n/es-ES.json'
      },
      dom: 'Bfrtip'  // Aquí se asegura de incluir el contenedor para los botones
    }).buttons().container().appendTo('#example1_wrapper .col-md-6:eq(0)');
  });
</script>
<!--<script>
    // Esperar a que el documento esté listo
    $(document).ready(function() {
        // Añadir un manejador de eventos de clic a los botones de info
        $('.info-btn').on('click', function() {
            // Obtener el ID del proyecto desde el atributo data-id del botón clicado
            var projectId = $(this).data('id');
            // Mostrar el ID en el modal
            $('#projectId').text(projectId);
        });
    });
</script> -->
<script>
$(document).ready(function() {
    $('.info-btn').on('click', function() {
        var proyectoId = $(this).data('id');

        $.ajax({
            url: 'info_proyecto.php', // Archivo PHP que realizará la consulta
            method: 'POST',
            data: { id: proyectoId },
            success: function(response) {
                var data = JSON.parse(response);  // Convertimos la respuesta a un objeto
                if (data.success) {
                    alert('Proyecto encontrado: ' + data.proyecto.p1);  // Mostramos uno de los datos del proyecto
                } else {
                    alert('No se encontró el proyecto con ese ID');
                }
            },
            error: function() {
                alert('Ocurrió un error en la solicitud.');
            }
        });
    });
});
</script>
</body>
</html>