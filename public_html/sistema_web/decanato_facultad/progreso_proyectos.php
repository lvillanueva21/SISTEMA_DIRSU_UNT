<?php
// Incluir configSesion.php para verificar la sesión
include "../componentes/configSesion.php";

// Incluir la conexión a la base de datos
include('../componentes/db.php');

// Arreglo de facultades para el filtro y nombre de facultad
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
    '13' => 'Medicina'
];
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
    <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
    <!-- Librería para imprimir info en excel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
    <script>
    function verDetalle(id_py) {
        // Abre el modal
        $('#modalVerDetalle').modal('show');
       
        // Realiza la petición AJAX
        $.ajax({
            url: '../direccion_rsu/logica/presentacion_py.php',
            method: 'GET',
            data: { id_py: id_py },
            success: function(response) {
                // Inserta la respuesta en el cuerpo del modal
                $('#contenidoModal').html(response);
            },
            error: function() {
                alert('Hubo un error al cargar la Presentación de proyecto');
            }
        });
    }
    function verSemestre(id_py) {
        // Abre el modal
        $('#modalVerSemestre').modal('show');
       
        // Realiza la petición AJAX
        $.ajax({
            url: '../direccion_rsu/logica/semestre_py.php',
            method: 'GET',
            data: { id_py: id_py },
            success: function(response) {
                // Inserta la respuesta en el cuerpo del modal
                $('#contenidoModal2').html(response);
            },
            error: function() {
                alert('Hubo un error al cargar el Informe Semestral');
            }
        });
    }
    function verEvaluacion(id_py) {
        // Abre el modal
        $('#modalVerEvaluacion').modal('show');
       
        // Realiza la petición AJAX
        $.ajax({
            url: '../direccion_rsu/logica/evaluacion_py.php',
            method: 'GET',
            data: { id_py: id_py },
            success: function(response) {
                // Inserta la respuesta en el cuerpo del modal
                $('#contenidoModal3').html(response);
            },
            error: function() {
                alert('Hubo un error al cargar el Informe Semestral');
            }
        });
    }  
    </script>
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
                    <a href="https://rsu.unitru.edu.pe" class="nav-link" target="_blank">
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
                            <a href="progreso_proyectos.php" class="nav-link active">
                                <i class="fa fa-chart-line nav-icon"></i>
                                <p>Progreso de Proyectos</p>
                            </a>
                        </li>
                        <li class="nav-header">Evaluación de Proyectos</li>
                        <li class="nav-item">
                        <a href="visto.php" class="nav-link">
                           <i class="fa fa-eye nav-icon"></i><i class="fa fa-check nav-icon"></i>
                           <p>Visto Bueno</p>
                        </a>
                     </li>
                    </ul>
                </nav>
            </div>
        </aside>
        <div class="content-wrapper">
            <section class="content" style="height: 400px;">
                <div class="card">
                <h6 class="card-header bg-primary text-white">
    Estado de informe semestral de proyectos del período <b><u>2024-II</u></b> Facultad de 
    <b><?php echo isset($facultades[$id_escuela]) ? $facultades[$id_escuela] : 'Facultad no encontrada'; ?></b>
</h6>
                    <div class="card">
<form method="GET" action="progreso_proyectos.php" style="margin: 10px 0;">
  <div class="row mx-3">
    <div class="col-md-4">
      <label for="keyword">Texto:</label>
      <input type="text" class="form-control" name="keyword" id="keyword" placeholder="Ingrese nombre, apellido o título" value="<?php echo isset($_GET['keyword']) ? htmlspecialchars($_GET['keyword']) : ''; ?>">
    </div>
    <div class="col-md-2">
      <label for="estado">Estado:</label>
      <select class="form-control" name="estado" id="estado">
        <option value="">Todos</option>
        <option value="1" <?php echo (isset($_GET['estado']) && $_GET['estado'] === "1") ? 'selected' : ''; ?>>Revisión</option>
        <option value="0" <?php echo (isset($_GET['estado']) && $_GET['estado'] === "0") ? 'selected' : ''; ?>>En Espera</option>
        <option value="2" <?php echo (isset($_GET['estado']) && $_GET['estado'] === "2") ? 'selected' : ''; ?>>Aprobado</option>
      </select>
    </div>
    <div class="col-md-2 d-flex align-items-end">
    <button type="submit" class="btn btn-primary btn-sm">
  <i class="fas fa-filter"></i>
</button>
<!-- Botón para imprimir la tabla -->
<button type="button" class="btn btn-secondary btn-sm ml-2" onclick="imprimirTabla()">
  <i class="fas fa-print"></i>
</button>
<!-- Botón para exportar a Excel -->
<button type="button" id="btnExportExcel" class="btn btn-success btn-sm ml-2" onclick="exportTableToExcel()">
  <i class="fas fa-file-excel"></i>
</button>
</div>
  </div>
</form>
                    </div>
                    <?php include('logica/proyectos_facultad.php') ?>
                </div>
            </section>
        </div>
        <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://rsu.unitru.edu.pe/"> Área  informática - DIRSU</a></p>
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
    <script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote').summernote({
            lang: 'es-ES'
        });
    });
    </script>

    <!-- Modal -->
    <div class="modal fade" id="modalVerDetalle" tabindex="-1" role="dialog" aria-labelledby="modalVerDetalleLabel" aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerDetalleLabel">Fase 01: Presentación y formulación de Proyecto</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenidoModal" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                    <!-- Aquí se cargarán los detalles del proyecto con AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalVerSemestre" tabindex="-1" role="dialog" aria-labelledby="modalVerSemestreLabel" aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerSemestreLabel">Fase 03: Evaluación e informe Semestral / Final</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenidoModal2" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                    <!-- Aquí se cargarán los detalles del proyecto con AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" id="modalVerEvaluacion" tabindex="-1" role="dialog" aria-labelledby="modalVerEvaluacionLabel" aria-hidden="true">
        <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="modalVerEvaluacionLabel">Evaluación de Informe Semestral de Proyectos 2024 - II</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body" id="contenidoModal3" style="max-width: 100%; overflow-x: auto; white-space: nowrap;">
                    <!-- Aquí se cargarán los detalles del proyecto con AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    <script>
function imprimirTabla(){
    // Obtener el contenido de la tabla
    var divToPrint = document.getElementById("tablaProyectos");
    // Abrir una nueva ventana
    var newWin = window.open("", "Print-Window");
    newWin.document.open();
    newWin.document.write('<html><head><title>Estado de informe semestral de proyectos del período 2024-II</title>');
    // Incluir hojas de estilo para mantener la apariencia
    newWin.document.write('<link rel="stylesheet" href="../dust/css/adminlte.min.css">');
    newWin.document.write('<link rel="stylesheet" href="../plogins/bootstrap/css/bootstrap.min.css">');
    newWin.document.write('<link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">');
    newWin.document.write('<link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">');
    newWin.document.write('</head><body onload="window.print()">');
    newWin.document.write(divToPrint.innerHTML);
    newWin.document.write('</body></html>');
    newWin.document.close();
}
function exportTableToExcel(){
    // Obtiene la tabla por su id (asegúrate de que el id coincida con el que asignaste en la tabla)
    var table = document.getElementById("tablaProyectosTable");
    // Convierte la tabla a un libro Excel
    var workbook = XLSX.utils.table_to_book(table, {sheet:"Sheet1"});
    // Descarga el archivo Excel con el nombre indicado
    XLSX.writeFile(workbook, 'tabla_proyectos.xlsx');
}
</script>

</body>
</html>