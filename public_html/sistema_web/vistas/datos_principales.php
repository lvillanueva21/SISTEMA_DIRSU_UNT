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
      <title>Generalidades del proyecto - Sistema DIRSU</title>
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
      <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>
      <!-- Content Wrapper. Contains page content -->
      <div class="content-wrapper">
         <!-- Content Header (Page header) -->
         <div class="content-header">
            <div class="container-fluid">
               <div class="row mb-2">
                  <div class="col-sm-7">
                     <h1 class="m-0">1.1. Generalidades</h1>
                  </div>
                  <!-- /.col -->
                  <div class="col-sm-5">
                     <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="../inicio.php">Inicio</a></li>
                        <li class="breadcrumb-item active">Formulación y presentación</li>
                        <li class="breadcrumb-item active">Generalidades</li>
                     </ol>
                  </div>
                  <!-- /.col -->
               </div>
               <!-- /.row -->
            </div>
            <!-- /.container-fluid -->
         </div>
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
                                 <li class="nav-item">
                                    <a class="nav-link active" id="custom-tabs-titulo-tab" data-toggle="pill" href="#custom-tabs-titulo" role="tab" aria-controls="custom-tabs-titulo" aria-selected="true">Título</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-interesados-tab" data-toggle="pill" href="#custom-tabs-interesados" role="tab" aria-controls="custom-tabs-interesados" aria-selected="false">Interesados</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-beneficiados-tab" data-toggle="pill" href="#custom-tabs-beneficiados" role="tab" aria-controls="custom-tabs-beneficiados" aria-selected="false">Beneficiarios</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-lugar-tab" data-toggle="pill" href="#custom-tabs-lugar" role="tab" aria-controls="custom-tabs-lugar" aria-selected="false">Lugar y Tiempo</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-fases-tab" data-toggle="pill" href="#custom-tabs-fases" role="tab" aria-controls="custom-tabs-fases" aria-selected="false">Fases</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-facultad-tab" data-toggle="pill" href="#custom-tabs-facultad" role="tab" aria-controls="custom-tabs-facultad" aria-selected="false">Facultad y programa</a>
                                 </li>
                                 <li class="nav-item">
                                    <a class="nav-link" id="custom-tabs-responsables-tab" data-toggle="pill" href="#custom-tabs-responsables" role="tab" aria-controls="custom-tabs-responsables" aria-selected="false">Responsables</a>
                                 </li>
                              </ul>
                           </div>
                           <div class="card-body">
                              <div class="tab-content" id="custom-tabs-titulo-tabContent">
                                 <div class="tab-pane fade show active" id="custom-tabs-titulo" role="tabpanel" aria-labelledby="custom-tabs-titulo-tab">
                                    <!-- Navegación -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                       <button class="btn btn-app bg-primary next-tab" 
                                          data-next-tab="custom-tabs-interesados" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
                                       </button>
                                    </div>
                                    <!-- Navegación -->
                                    <form action="../componentes/proyecto/actualizar_titulo.php" method="POST">
                                       <div class="form-group">
                                          <label for="inputTituloPrograma">1. Título del Programa</label>
                                          <input type="text" class="form-control" id="p1" name="p1" 
                                             placeholder="Ingresa el título del programa" 
                                             value="<?php echo htmlspecialchars($p1); ?>" required>
                                       </div>
                                       <div class="form-group">
                                          <label for="inputTituloProyecto">2. Título del Proyecto</label>
                                          <input type="text" class="form-control" id="p2" name="p2" 
                                             placeholder="Ingresa el título del proyecto" 
                                             value="<?php echo htmlspecialchars($p2); ?>" required>
                                       </div>
                                       <div class="row">
                                          <div class="col-md-6">
                                             <label for="ods">3. Objetivo(s) de Desarrollo Sostenible que cumple el proyecto</label>
                                             <select class="select2" multiple="multiple" data-placeholder="Selecciona 1 o más ODS ..." 
                                                name="p3[]" id="p3" data-dropdown-css-class="select2" style="width: 100%;" required>
                                                <option value="1" <?php echo (in_array('1', $p3)) ? 'selected' : ''; ?>>ODS1: Reducción de los indicadores de la pobreza</option>
                                                <option value="2" <?php echo (in_array('2', $p3)) ? 'selected' : ''; ?>>ODS2: Hambre y seguridad alimentaria</option>
                                                <option value="3" <?php echo (in_array('3', $p3)) ? 'selected' : ''; ?>>ODS3: Salud y bienestar</option>
                                                <option value="4" <?php echo (in_array('4', $p3)) ? 'selected' : ''; ?>>ODS4: Educación de calidad</option>
                                                <option value="5" <?php echo (in_array('5', $p3)) ? 'selected' : ''; ?>>ODS5: Igualdad de género y empoderamiento de la mujer</option>
                                                <option value="6" <?php echo (in_array('6', $p3)) ? 'selected' : ''; ?>>ODS6: Agua limpia y saneamiento</option>
                                                <option value="7" <?php echo (in_array('7', $p3)) ? 'selected' : ''; ?>>ODS7: Energía asequible y no contaminante</option>
                                                <option value="8" <?php echo (in_array('8', $p3)) ? 'selected' : ''; ?>>ODS8: Trabajo decente y crecimiento económico</option>
                                                <option value="9" <?php echo (in_array('9', $p3)) ? 'selected' : ''; ?>>ODS9: Industria, innovación e infraestructura</option>
                                                <option value="10" <?php echo (in_array('10', $p3)) ? 'selected' : ''; ?>>ODS10: Reducir las desigualdades</option>
                                                <option value="11" <?php echo (in_array('11', $p3)) ? 'selected' : ''; ?>>ODS11: Ciudades y comunidades sostenibles</option>
                                                <option value="12" <?php echo (in_array('12', $p3)) ? 'selected' : ''; ?>>ODS12: Producción y consumo responsables</option>
                                                <option value="13" <?php echo (in_array('13', $p3)) ? 'selected' : ''; ?>>ODS13: Acción por el clima</option>
                                                <option value="14" <?php echo (in_array('14', $p3)) ? 'selected' : ''; ?>>ODS14: Vida submarina</option>
                                                <option value="15" <?php echo (in_array('15', $p3)) ? 'selected' : ''; ?>>ODS15: Vida y ecosistemas terrestres</option>
                                                <option value="16" <?php echo (in_array('16', $p3)) ? 'selected' : ''; ?>>ODS16: Paz y justicia e instituciones sólidas</option>
                                                <option value="17" <?php echo (in_array('17', $p3)) ? 'selected' : ''; ?>>ODS17: Alianzas para lograr los objetivos</option>
                                             </select>
                                          </div>
                                          <div class="col-md-6">
                                             <label>4. Tipo de proyecto</label>
                                             <select class="select2" multiple="multiple" data-placeholder="Elige 1 o más tipos de proyecto ..." 
                                                name="p4[]" id="p4" data-dropdown-css-class="select2" style="width: 100%;" required>
                                                <option value="1" <?php echo (in_array('1', $p4)) ? 'selected' : ''; ?>>1. Programas de formación continua y formación de capacidades</option>
                                                <option value="2" <?php echo (in_array('2', $p4)) ? 'selected' : ''; ?>>2. Consultoría/asesoría</option>
                                                <option value="3" <?php echo (in_array('3', $p4)) ? 'selected' : ''; ?>>3. Gestión cultural</option>
                                                <option value="4" <?php echo (in_array('4', $p4)) ? 'selected' : ''; ?>>4. Desarrollo económico y social</option>
                                                <option value="5" <?php echo (in_array('5', $p4)) ? 'selected' : ''; ?>>5. Desarrollo humano y democracia</option>
                                                <option value="6" <?php echo (in_array('6', $p4)) ? 'selected' : ''; ?>>6. Desarrollo técnico científico sostenible</option>
                                                <option value="7" <?php echo (in_array('7', $p4)) ? 'selected' : ''; ?>>7. Protección del medio ambiente</option>
                                                <option value="8" <?php echo (in_array('8', $p4)) ? 'selected' : ''; ?>>8. Innovación</option>
                                                <option value="9" <?php echo (in_array('9', $p4)) ? 'selected' : ''; ?>>9. Creatividad</option>
                                                <option value="10" <?php echo (in_array('10', $p4)) ? 'selected' : ''; ?>>10. Otras áreas de acuerdo a las necesidades de la comunidad</option>
                                                <option value="11" <?php echo (in_array('11', $p4)) ? 'selected' : ''; ?>>11. Salud</option>
                                             </select>
                                          </div>
                                       </div>
                                       <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                 </div>
                                 <div class="tab-pane fade" id="custom-tabs-interesados" role="tabpanel" aria-labelledby="custom-tabs-interesados-tab">
                                    <form action="../componentes/proyecto/actualizar_interesados.php" method="POST">
                                       <div class="form-group">
                                          <label for="inputInteres">5. Grupo(s) de Interés al que está orientado el proyecto (población afectada) </label>
                                          <div class="card-body">
                                             <textarea id="summernote" name="p5">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($p5) && !empty($p5) ? htmlspecialchars($p5) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD una tabla de los Grupos de Interés y sus representantes ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                          </div>
                                       </div>
                                       <div class="form-group">
                                          <label for="inputInteres">6. Necesidades y/o problemas de los Grupo(s) de Interés (alineados al grupo(s) de interés)</label>
                                          <div class="card-body">
                                             <textarea id="summernote2" name="p6">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($p6) && !empty($p6) ? htmlspecialchars($p6) : 'Aquí puedes diseñar o copiar desde un ARCHIVO WORD una tabla de los Grupos de Interés y sus necesidades ...'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                          </div>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </div>
                                    </form>
                                    <!-- Navegación -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                       <button class="btn btn-app bg-primary prev-tab" 
                                          data-prev-tab="custom-tabs-titulo" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                                       </button>
                                    </div>
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                       <button class="btn btn-app bg-primary next-tab" 
                                          data-next-tab="custom-tabs-beneficiados" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
                                       </button>
                                    </div>
                                    <!-- Navegación -->
                                 </div>
                                 <div class="tab-pane fade" id="custom-tabs-beneficiados" role="tabpanel" aria-labelledby="custom-tabs-beneficiados-tab">
                                    <form action="../componentes/proyecto/actualizar_beneficiados.php" method="POST">
                                       <div class="form-group">
                                          <label for="inputInteres">7. Instituciones con las que el proyecto interactúa</label>
                                          <h6>7.1. Instituciones participantes</h6>
                                          <div class="card-body">
                                             <textarea id="summernote3" name="p7_1">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($p7_1) && !empty($p7_1) ? htmlspecialchars($p7_1) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                          </div>
                                       </div>
                                       <div class="form-group">
                                          <h6>7.2. Poblaciones participantes</h6>
                                          <div class="card-body">
                                             <textarea id="summernote4" name="p7_2">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($p7_2) && !empty($p7_2) ? htmlspecialchars($p7_2) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Poblaciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                          </div>
                                       </div>
                                       <!-- Fin - Población participante -->
                                       <!-- Inicio - Población Infantes -->
                                       <h6 for="inputParticipantesEtario">Número de participantes (por grupos etarios)</h6>
                                       <div class="row">
                                          <div class="col-md-2">
                                             <h6 for="inputNinos">Infantes<br>(0 - 5 años)</h6>
                                             <input type="number" class="form-control" id="infantes" name="infantes" min="0" max="100000" step="1" value="<?php echo isset($infantes) ? intval($infantes) : '0'; ?>" required>
                                          </div>
                                          <!-- Inicio - Población Niños -->
                                          <div class="col-md-2">
                                             <h6 for="inputJovenes">Niños<br>(6 - 11 años)</h6>
                                             <input type="number" class="form-control" id="ninos" name="ninos" min="0" max="100000" step="1" value="<?php echo isset($ninos) ? intval($ninos) : '0'; ?>" required>
                                          </div>
                                          <!-- Inicio - Población Adolescentes -->
                                          <div class="col-md-2">
                                             <h6 for="inputAdultos">Adolescentes<br>(12 a 17 años)</h6>
                                             <input type="number" class="form-control" id="adolescentes" name="adolescentes" min="0" max="100000" step="1" value="<?php echo isset($adolescentes) ? intval($adolescentes) : '0'; ?>" required>
                                          </div>
                                          <!-- Inicio - Población Jovenes -->
                                          <div class="col-md-2">
                                             <h6 for="inputAdultos">Jóvenes<br>(18 a 26 años)</h6>
                                             <input type="number" class="form-control" id="jovenes" name="jovenes" min="0" max="100000" step="1" value="<?php echo isset($jovenes) ? intval($jovenes) : '0'; ?>" required>
                                          </div>
                                          <!-- Inicio - Población Adultos -->
                                          <div class="col-md-2">
                                             <h6 for="inputAdultos">Adultos<br>(27 a 59 años)</h6>
                                             <input type="number" class="form-control" id="adultos" name="adultos" min="0" max="100000" step="1" value="<?php echo isset($adultos) ? intval($adultos) : '0'; ?>" required>
                                          </div>
                                          <!-- Inicio - Población Adulto mayor -->
                                          <div class="col-md-2">
                                             <h6 for="inputAdultos">Adultos mayores<br>(60 a más)</h6>
                                             <input type="number" class="form-control" id="adultos_mayores" name="adultos_mayores" min="0" max="100000" step="1" value="<?php echo isset($adultos_mayores) ? intval($adultos_mayores) : '0'; ?>" required>
                                          </div>
                                       </div>
                                       <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                    <!-- Navegación -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                       <button class="btn btn-app bg-primary prev-tab" 
                                          data-prev-tab="custom-tabs-interesados" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                                       </button>
                                    </div>
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                       <button class="btn btn-app bg-primary next-tab" 
                                          data-next-tab="custom-tabs-lugar" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
                                       </button>
                                    </div>
                                    <!-- Navegación -->
                                 </div>
                                 <div class="tab-pane fade" id="custom-tabs-lugar" role="tabpanel" aria-labelledby="custom-tabs-lugar-tab">
                                    <form action="../componentes/proyecto/actualizar_lugar.php" method="POST">
                                       <div class="form-group">
                                          <label for="inputLugarEjecucion">8. Lugar(es) de ejecución del proyecto</label>
                                          <p>Ingresa el lugar principal en el que se desarrollará el proyecto.</p>
                                          <div class="row">
                                             <div class="col-md-3">
                                                <h6 for="inputSectorBarrio">Sector / Barrio</h6>
                                                <input type="text" class="form-control" id="sector" name="sector" placeholder="Ingresa el Sector/Barrio" value="<?php echo htmlspecialchars($sector); ?>">
                                             </div>
                                             <div class="col-md-3">
                                                <h6 for="inputCaserio">Caserío</h6>
                                                <input type="text" class="form-control" id="caserio" name="caserio" placeholder="Ingresa el Caserío (opcional)" value="<?php echo htmlspecialchars($caserio); ?>">
                                             </div>
                                             <div class="col-md-2">
                                                <h6 for="inputRegion">Departamento</h6>
                                                <select class="form-control" id="departamento" name="departamento" required>
                                                   <option value="">Selecciona un departamento</option>
                                                </select>
                                             </div>
                                             <div class="col-md-2">
                                                <h6 for="inputProvincia">Provincia</h6>
                                                <select class="form-control" id="provincia" name="provincia" required>
                                                   <option value="">Selecciona una provincia</option>
                                                </select>
                                             </div>
                                             <div class="col-md-2">
                                                <h6 for="inputDistrito">Distrito</h6>
                                                <select class="form-control" id="distrito" name="distrito" required>
                                                   <option value="">Selecciona un distrito</option>
                                                </select>
                                             </div>
                                          </div>
                                          <br>
                                          <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                             <thead style="background-color: #28a745; color: white;">
                                                <tr>
                                                   <th colspan="5" style="text-align: center;">Lugar de ejecución principal del proyecto</th>
                                                </tr>
                                             </thead>
                                             <tr>
                                                <td><b>Sector</b><br><?php echo $sector === null ? 'Sin sector / barrio' : $sector; ?></td>
                                                <td><b>Caserio</b><br><?php echo $caserio === null ? 'Sin caserio' : $caserio; ?></td>
                                                <td><b>Departamento</b><br><?php echo $departamento === null ? 'Sin departamento' : $departamento; ?></td>
                                                <td><b>Provincia</b><br><?php echo $provincia === null ? 'Sin provincia' : $provincia; ?></td>
                                                <td><b>Distrito</b><br><?php echo $distrito === null ? 'Sin distrito' : $distrito; ?></td>
                                             </tr>
                                          </table>
                                          <!-- Botón para agregar más lugares de ejecución -->
                                          <div class="row">
                                             <div class="col-md-8">
                                                <span style="color: red;">
                                                <b>Si tu proyecto tiene más lugares de ejecución, ingrésalos presionando el siguiente botón.</b> 
                                                </span>
                                             </div>
                                             <div class="col-md-4 text-center">
                                                <a href="javascript:void(0);" onclick="abrirFormulario()" class="btn btn-success btn-block">
                                                <i class="fa fa-plus"></i> Agregar más lugares
                                                </a>
                                             </div>
                                          </div>
                                          <br>
                                          <!-- Duración del proyecto -->
                                          <label for="inputDuracion">9. Duración del proyecto (de 2 a 5 años)</label>        
                                          <div class="row">
                                             <div class="col-md-6">
                                                <h6>9.1. Fecha de inicio del proyecto</h6>
                                                <div class="input-group date" id="startdate" data-target-input="nearest">
                                                   <input type="text" class="form-control datetimepicker-input" name="fecha_inicio" placeholder="01/01/1970" value="<?php echo htmlspecialchars($fecha_inicio); ?>" data-target="#startdate" required>
                                                   <div class="input-group-append" data-target="#startdate" data-toggle="datetimepicker">
                                                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                   </div>
                                                </div>
                                             </div>
                                             <div class="col-md-6">
                                                <h6>9.2. Fecha de fin del proyecto</h6>
                                                <div class="input-group date" id="enddate" data-target-input="nearest">
                                                   <input type="text" class="form-control datetimepicker-input" name="fecha_fin" placeholder="01/01/1970" value="<?php echo htmlspecialchars($fecha_fin); ?>" data-target="#enddate" required>
                                                   <div class="input-group-append" data-target="#enddate" data-toggle="datetimepicker">
                                                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                                          <br>
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </div>
                                    </form>
                                    <!-- Navegación de pestañas -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                       <button class="btn btn-app bg-primary prev-tab" 
                                          data-prev-tab="custom-tabs-beneficiados" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀
                                       </button>
                                    </div>
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                       <button class="btn btn-app bg-primary next-tab" 
                                          data-next-tab="custom-tabs-fases" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂
                                       </button>
                                    </div>
                                 </div>
                                 <script type="text/javascript">
                                    // Variable global para almacenar la referencia a la ventana
                                    var ventanaFormulario = null;
                                    
                                    function abrirFormulario() {
                                        // Si la ventana ya está abierta y no ha sido cerrada, simplemente la enfoca
                                        if (ventanaFormulario && !ventanaFormulario.closed) {
                                            ventanaFormulario.focus();
                                        } else {
                                            // Si la ventana no está abierta, la abre con las configuraciones deseadas
                                            ventanaFormulario = window.open('lugares_ejecucion.php', 'Formulario', 'width=1000,height=600');
                                        }
                                    }
                                 </script>
                                 <div class="tab-pane fade" id="custom-tabs-fases" role="tabpanel" aria-labelledby="custom-tabs-fases-tab">
                                    <!-- FASES -->
                                    <form action="../componentes/proyecto/actualizar_fases.php" method="POST">
                                       <div class="form-group">
                                          <label>10. Fases del proyecto</label>
                                          <!-- Tabla -->
                                          <table class="table">
                                             <thead>
                                                <tr>
                                                   <th style="width: 10%;">Fases</th>
                                                   <th style="width: 60%;">Descripción</th>
                                                   <th style="width: 10%;">N° Semanas</th>
                                                   <th style="width: 10%;">N° horas por semanas</th>
                                                   <th style="width: 10%;">Total de horas</th>
                                                </tr>
                                             </thead>
                                             <tbody>
                                                <tr>
                                                   <td>Planificación</td>
                                                   <td><input type="text" class="form-control" id="planificacion" name="planificacion" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($planificacion); ?>" required></td>
                                                   <td><input type="number" class="form-control" id="p10_1s" name="p10_1s" min="0" max="10000" step="1" value="<?php echo isset($p10_1s) ? intval($p10_1s) : '0'; ?>" oninput="updateTotal(1)"></td>
                                                   <td><input type="number" class="form-control" id="p10_1h" name="p10_1h" min="0" max="10000" step="1" value="<?php echo isset($p10_1h) ? intval($p10_1h) : '0'; ?>" oninput="updateTotal(1)"></td>
                                                   <td><input type="text" class="form-control" id="total1" value="0" readonly></td>
                                                </tr>
                                                <tr>
                                                   <td>Ejecución</td>
                                                   <td><input type="text" class="form-control" id="ejecucion" name="ejecucion" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($ejecucion); ?>" required></td>
                                                   <td><input type="number" class="form-control" id="p10_2s" name="p10_2s" min="0" max="10000" step="1" value="<?php echo isset($p10_2s) ? intval($p10_2s) : '0'; ?>" oninput="updateTotal(2)"></td>
                                                   <td><input type="number" class="form-control" id="p10_2h" name="p10_2h" min="0" max="10000" step="1" value="<?php echo isset($p10_2h) ? intval($p10_2h) : '0'; ?>" oninput="updateTotal(2)"></td>
                                                   <td><input type="text" class="form-control" id="total2" value="0" readonly></td>
                                                </tr>
                                                <tr>
                                                   <td>Monitoreo y Evaluación</td>
                                                   <td><input type="text" class="form-control" id="monitoreo" name="monitoreo" placeholder="Ingresar descripción" value= "<?php echo htmlspecialchars($monitoreo); ?>" required></td>
                                                   <td><input type="number" class="form-control" id="p10_3s" name="p10_3s" min="0" max="10000" step="1" value="<?php echo isset($p10_3s) ? intval($p10_3s) : '0'; ?>" oninput="updateTotal(3)"></td>
                                                   <td><input type="number" class="form-control" id="p10_3h" name="p10_3h" min="0" max="10000" step="1" value="<?php echo isset($p10_3h) ? intval($p10_3h) : '0'; ?>" oninput="updateTotal(3)"></td>
                                                   <td><input type="text" class="form-control" id="total3" value="0" readonly></td>
                                                </tr>
                                             </tbody>
                                             <tfoot>
                                                <tr>
                                                   <td colspan="4"><strong>Total</strong></td>
                                                   <td><input type="text" class="form-control" id="grandTotal" value="0" readonly></td>
                                                </tr>
                                             </tfoot>
                                          </table>
                                          <!-- Botones de navegación -->
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </div>
                                    </form>
                                    <!-- Navegación -->
                                    <!-- Navegación -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                       <button class="btn btn-app bg-primary prev-tab" 
                                          data-prev-tab="custom-tabs-lugar" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                                       </button>
                                    </div>
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                       <button class="btn btn-app bg-primary next-tab" 
                                          data-next-tab="custom-tabs-facultad" 
                                          style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
                                       </button>
                                    </div>
                                    <!-- Navegación -->
                                    <!-- Navegación -->
                                    <!-- .FASES -->
                                 </div>
                                 <div class="tab-pane fade" id="custom-tabs-facultad" role="tabpanel" aria-labelledby="custom-tabs-facultad-tab">
                                    <!-- FACULTAD -->
                                    <form action="../componentes/proyecto/actualizar_facultad.php" method="POST">
                                       <div class="form-group">
                                          <label>11. Nivel disciplinar</label>
                                          <div>
                                             <div class="form-check">
                                                <input class="form-check-input" type="radio" name="disciplinar" id="disciplinarios" value="1" <?php echo ($disciplinar == '1') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="disciplinarios">Disciplinar</label>
                                             </div>
                                             <div class="form-check">
                                                <input class="form-check-input" type="radio" name="disciplinar" id="interdisciplinarios" value="2" <?php echo ($disciplinar == '2') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="interdisciplinarios">Interdisciplinar</label>
                                             </div>
                                             <div class="form-check">
                                                <input class="form-check-input" type="radio" name="disciplinar" id="interfacultativos" value="3" <?php echo ($disciplinar == '3') ? 'checked' : ''; ?>>
                                                <label class="form-check-label" for="interfacultativos">Interfacultativo</label>
                                             </div>
                                          </div>
                                       </div>
                                       <div class="form-group">
                                          <label for="inputOtro">12. Unidad ejecutora</label>
                                          <div class="row">
                                             <div class="col-md-4">
                                                <h6>12.1. Facultad</h6>
                                                <select class="custom-select" name="facultad">
                                                   <option value="">Seleccione una opción</option>
                                                   <option value="1" <?php echo (isset($facultad) && $facultad == '1') ? 'selected' : ''; ?>>Ciencias Agropecuarias</option>
                                                   <option value="2" <?php echo (isset($facultad) && $facultad == '2') ? 'selected' : ''; ?>>Ciencias Biológicas</option>
                                                   <option value="3" <?php echo (isset($facultad) && $facultad == '3') ? 'selected' : ''; ?>>Ciencias Económicas</option>
                                                   <option value="4" <?php echo (isset($facultad) && $facultad == '4') ? 'selected' : ''; ?>>Ciencias Físicas y Matemáticas</option>
                                                   <option value="5" <?php echo (isset($facultad) && $facultad == '5') ? 'selected' : ''; ?>>Ciencias Sociales</option>
                                                   <option value="6" <?php echo (isset($facultad) && $facultad == '6') ? 'selected' : ''; ?>>Derecho y Ciencias Políticas</option>
                                                   <option value="7" <?php echo (isset($facultad) && $facultad == '7') ? 'selected' : ''; ?>>Educación y Ciencias de la Comunicación</option>
                                                   <option value="8" <?php echo (isset($facultad) && $facultad == '8') ? 'selected' : ''; ?>>Enfermería</option>
                                                   <option value="9" <?php echo (isset($facultad) && $facultad == '9') ? 'selected' : ''; ?>>Estomatología</option>
                                                   <option value="10" <?php echo (isset($facultad) && $facultad == '10') ? 'selected' : ''; ?>>Farmacia y Bioquímica</option>
                                                   <option value="11" <?php echo (isset($facultad) && $facultad == '11') ? 'selected' : ''; ?>>Ingeniería</option>
                                                   <option value="12" <?php echo (isset($facultad) && $facultad == '12') ? 'selected' : ''; ?>>Ingeniería Química</option>
                                                   <option value="13" <?php echo (isset($facultad) && $facultad == '13') ? 'selected' : ''; ?>>Medicina</option>
                                                </select>
                                             </div>
                                             <br>
                                             <div class="col-md-4">
                                                <h6>12.2. Programa de estudios</h6>
                                                <select class="custom-select" name="programa_estudios">
                                                   <option value="">Seleccione una opción</option>
                                                   <option value="1" <?php echo (isset($programa_estudios) && $programa_estudios == '1') ? 'selected' : ''; ?>>Administración</option>
                                                   <option value="2" <?php echo (isset($programa_estudios) && $programa_estudios == '2') ? 'selected' : ''; ?>>Agronomía</option>
                                                   <option value="3" <?php echo (isset($programa_estudios) && $programa_estudios == '3') ? 'selected' : ''; ?>>Antropología</option>
                                                   <option value="4" <?php echo (isset($programa_estudios) && $programa_estudios == '4') ? 'selected' : ''; ?>>Arqueología</option>
                                                   <option value="5" <?php echo (isset($programa_estudios) && $programa_estudios == '5') ? 'selected' : ''; ?>>Arquitectura y Urbanismo</option>
                                                   <option value="6" <?php echo (isset($programa_estudios) && $programa_estudios == '6') ? 'selected' : ''; ?>>Biología Pesquera</option>
                                                   <option value="7" <?php echo (isset($programa_estudios) && $programa_estudios == '7') ? 'selected' : ''; ?>>Ciencias Biológicas</option>
                                                   <option value="8" <?php echo (isset($programa_estudios) && $programa_estudios == '8') ? 'selected' : ''; ?>>Ciencias de la Comunicación</option>
                                                   <option value="9" <?php echo (isset($programa_estudios) && $programa_estudios == '9') ? 'selected' : ''; ?>>Ciencias Políticas y Gobernabilidad</option>
                                                   <option value="10" <?php echo (isset($programa_estudios) && $programa_estudios == '10') ? 'selected' : ''; ?>>Contabilidad y Finanzas</option>
                                                   <option value="11" <?php echo (isset($programa_estudios) && $programa_estudios == '11') ? 'selected' : ''; ?>>Derecho</option>
                                                   <option value="12" <?php echo (isset($programa_estudios) && $programa_estudios == '12') ? 'selected' : ''; ?>>Economía</option>
                                                   <option value="13" <?php echo (isset($programa_estudios) && $programa_estudios == '13') ? 'selected' : ''; ?>>Educación Inicial</option>
                                                   <option value="14" <?php echo (isset($programa_estudios) && $programa_estudios == '14') ? 'selected' : ''; ?>>Educación Primaria</option>
                                                   <option value="15" <?php echo (isset($programa_estudios) && $programa_estudios == '15') ? 'selected' : ''; ?>>Educación Secundaria Mención Ciencias Naturales</option>
                                                   <option value="16" <?php echo (isset($programa_estudios) && $programa_estudios == '16') ? 'selected' : ''; ?>>Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales</option>
                                                   <option value="17" <?php echo (isset($programa_estudios) && $programa_estudios == '17') ? 'selected' : ''; ?>>Educación Secundaria Mención Historia y Geografía</option>
                                                   <option value="18" <?php echo (isset($programa_estudios) && $programa_estudios == '18') ? 'selected' : ''; ?>>Educación Secundaria Mención Idiomas</option>
                                                   <option value="19" <?php echo (isset($programa_estudios) && $programa_estudios == '19') ? 'selected' : ''; ?>>Educación Secundaria Mención Lengua y Literatura</option>
                                                   <option value="20" <?php echo (isset($programa_estudios) && $programa_estudios == '20') ? 'selected' : ''; ?>>Educación Secundaria Mención Matemáticas</option>
                                                   <option value="21" <?php echo (isset($programa_estudios) && $programa_estudios == '21') ? 'selected' : ''; ?>>Enfermería</option>
                                                   <option value="22" <?php echo (isset($programa_estudios) && $programa_estudios == '22') ? 'selected' : ''; ?>>Estadística</option>
                                                   <option value="23" <?php echo (isset($programa_estudios) && $programa_estudios == '23') ? 'selected' : ''; ?>>Estomatología</option>
                                                   <option value="24" <?php echo (isset($programa_estudios) && $programa_estudios == '24') ? 'selected' : ''; ?>>Farmacia y Bioquímica</option>
                                                   <option value="25" <?php echo (isset($programa_estudios) && $programa_estudios == '25') ? 'selected' : ''; ?>>Física</option>
                                                   <option value="26" <?php echo (isset($programa_estudios) && $programa_estudios == '26') ? 'selected' : ''; ?>>Historia</option>
                                                   <option value="27" <?php echo (isset($programa_estudios) && $programa_estudios == '27') ? 'selected' : ''; ?>>Informática</option>
                                                   <option value="28" <?php echo (isset($programa_estudios) && $programa_estudios == '28') ? 'selected' : ''; ?>>Ingeniería Agrícola</option>
                                                   <option value="29" <?php echo (isset($programa_estudios) && $programa_estudios == '29') ? 'selected' : ''; ?>>Ingeniería Agroindustrial</option>
                                                   <option value="30" <?php echo (isset($programa_estudios) && $programa_estudios == '30') ? 'selected' : ''; ?>>Ingeniería Ambiental</option>
                                                   <option value="31" <?php echo (isset($programa_estudios) && $programa_estudios == '31') ? 'selected' : ''; ?>>Ingeniería Civil</option>
                                                   <option value="32" <?php echo (isset($programa_estudios) && $programa_estudios == '32') ? 'selected' : ''; ?>>Ingeniería de Materiales</option>
                                                   <option value="33" <?php echo (isset($programa_estudios) && $programa_estudios == '33') ? 'selected' : ''; ?>>Ingeniería de Minas</option>
                                                   <option value="34" <?php echo (isset($programa_estudios) && $programa_estudios == '34') ? 'selected' : ''; ?>>Ingeniería de Sistemas</option>
                                                   <option value="35" <?php echo (isset($programa_estudios) && $programa_estudios == '35') ? 'selected' : ''; ?>>Ingeniería Industrial</option>
                                                   <option value="36" <?php echo (isset($programa_estudios) && $programa_estudios == '36') ? 'selected' : ''; ?>>Ingeniería Mecánica</option>
                                                   <option value="37" <?php echo (isset($programa_estudios) && $programa_estudios == '37') ? 'selected' : ''; ?>>Ingeniería Mecatrónica</option>
                                                   <option value="38" <?php echo (isset($programa_estudios) && $programa_estudios == '38') ? 'selected' : ''; ?>>Ingeniería Metalúrgica</option>
                                                   <option value="39" <?php echo (isset($programa_estudios) && $programa_estudios == '39') ? 'selected' : ''; ?>>Ingeniería Química</option>
                                                   <option value="40" <?php echo (isset($programa_estudios) && $programa_estudios == '40') ? 'selected' : ''; ?>>Matemáticas</option>
                                                   <option value="41" <?php echo (isset($programa_estudios) && $programa_estudios == '41') ? 'selected' : ''; ?>>Medicina</option>
                                                   <option value="42" <?php echo (isset($programa_estudios) && $programa_estudios == '42') ? 'selected' : ''; ?>>Microbiología y Parasitología</option>
                                                   <option value="43" <?php echo (isset($programa_estudios) && $programa_estudios == '43') ? 'selected' : ''; ?>>Trabajo Social</option>
                                                   <option value="44" <?php echo (isset($programa_estudios) && $programa_estudios == '44') ? 'selected' : ''; ?>>Turismo</option>
                                                   <option value="45" <?php echo (isset($programa_estudios) && $programa_estudios == '45') ? 'selected' : ''; ?>>Zootecnia</option>
                                                </select>
                                             </div>
                                             <br>
                                             <div class="col-md-4">
                                                <h6>12.3. Departamento académico</h6>
                                                <select class="custom-select" name="departamento_academico">
                                                   <option value="">Seleccione una opción</option>
                                                   <option value="1" <?php echo (isset($departamento_academico) && $departamento_academico == '1') ? 'selected' : ''; ?>>Agronomía y Zootecnia</option>
                                                   <option value="2" <?php echo (isset($departamento_academico) && $departamento_academico == '2') ? 'selected' : ''; ?>>Ciencias Agroindustriales</option>
                                                   <option value="3" <?php echo (isset($departamento_academico) && $departamento_academico == '3') ? 'selected' : ''; ?>>Ciencias Biológicas</option>
                                                   <option value="4" <?php echo (isset($departamento_academico) && $departamento_academico == '4') ? 'selected' : ''; ?>>Microbiología y Parasitología</option>
                                                   <option value="5" <?php echo (isset($departamento_academico) && $departamento_academico == '5') ? 'selected' : ''; ?>>Pesquería</option>
                                                   <option value="6" <?php echo (isset($departamento_academico) && $departamento_academico == '6') ? 'selected' : ''; ?>>Química Biológica y Fisiología Animal</option>
                                                   <option value="7" <?php echo (isset($departamento_academico) && $departamento_academico == '7') ? 'selected' : ''; ?>>Administración</option>
                                                   <option value="8" <?php echo (isset($departamento_academico) && $departamento_academico == '8') ? 'selected' : ''; ?>>Contabilidad y Finanzas</option>
                                                   <option value="9" <?php echo (isset($departamento_academico) && $departamento_academico == '9') ? 'selected' : ''; ?>>Economía</option>
                                                   <option value="10" <?php echo (isset($departamento_academico) && $departamento_academico == '10') ? 'selected' : ''; ?>>Ciencias Básicas Estomatológicas</option>
                                                   <option value="11" <?php echo (isset($departamento_academico) && $departamento_academico == '11') ? 'selected' : ''; ?>>Estomatología</option>
                                                   <option value="12" <?php echo (isset($departamento_academico) && $departamento_academico == '12') ? 'selected' : ''; ?>>Estadística</option>
                                                   <option value="13" <?php echo (isset($departamento_academico) && $departamento_academico == '13') ? 'selected' : ''; ?>>Física</option>
                                                   <option value="14" <?php echo (isset($departamento_academico) && $departamento_academico == '14') ? 'selected' : ''; ?>>Informática</option>
                                                   <option value="15" <?php echo (isset($departamento_academico) && $departamento_academico == '15') ? 'selected' : ''; ?>>Matemáticas</option>
                                                   <option value="16" <?php echo (isset($departamento_academico) && $departamento_academico == '16') ? 'selected' : ''; ?>>Arqueología y Antropología</option>
                                                   <option value="17" <?php echo (isset($departamento_academico) && $departamento_academico == '17') ? 'selected' : ''; ?>>Ciencias Sociales</option>
                                                   <option value="18" <?php echo (isset($departamento_academico) && $departamento_academico == '18') ? 'selected' : ''; ?>>Ciencias Jurídicas Públicas y Políticas</option>
                                                   <option value="19" <?php echo (isset($departamento_academico) && $departamento_academico == '19') ? 'selected' : ''; ?>>Ciencias Jurídicas Privadas y Sociales</option>
                                                   <option value="20" <?php echo (isset($departamento_academico) && $departamento_academico == '20') ? 'selected' : ''; ?>>Ciencia Política y Gobernabilidad</option>
                                                   <option value="21" <?php echo (isset($departamento_academico) && $departamento_academico == '21') ? 'selected' : ''; ?>>Ciencias de la Educación</option>
                                                   <option value="22" <?php echo (isset($departamento_academico) && $departamento_academico == '22') ? 'selected' : ''; ?>>Ciencias Psicológicas</option>
                                                   <option value="23" <?php echo (isset($departamento_academico) && $departamento_academico == '23') ? 'selected' : ''; ?>>Comunicación Social</option>
                                                   <option value="24" <?php echo (isset($departamento_academico) && $departamento_academico == '24') ? 'selected' : ''; ?>>Filosofía y Arte</option>
                                                   <option value="25" <?php echo (isset($departamento_academico) && $departamento_academico == '25') ? 'selected' : ''; ?>>Historia y Geografía</option>
                                                   <option value="26" <?php echo (isset($departamento_academico) && $departamento_academico == '26') ? 'selected' : ''; ?>>Idiomas y Lingüística</option>
                                                   <option value="27" <?php echo (isset($departamento_academico) && $departamento_academico == '27') ? 'selected' : ''; ?>>Lengua Nacional y Literatura</option>
                                                   <option value="28" <?php echo (isset($departamento_academico) && $departamento_academico == '28') ? 'selected' : ''; ?>>Enfermería de la Mujer, Niño y Adolescente</option>
                                                   <option value="29" <?php echo (isset($departamento_academico) && $departamento_academico == '29') ? 'selected' : ''; ?>>Salud del Adulto</option>
                                                   <option value="30" <?php echo (isset($departamento_academico) && $departamento_academico == '30') ? 'selected' : ''; ?>>Salud Familiar y Comunitaria</option>
                                                   <option value="31" <?php echo (isset($departamento_academico) && $departamento_academico == '31') ? 'selected' : ''; ?>>Farmacotecnia</option>
                                                   <option value="32" <?php echo (isset($departamento_academico) && $departamento_academico == '32') ? 'selected' : ''; ?>>Farmacología</option>
                                                   <option value="33" <?php echo (isset($departamento_academico) && $departamento_academico == '33') ? 'selected' : ''; ?>>Bioquímica</option>
                                                   <option value="34" <?php echo (isset($departamento_academico) && $departamento_academico == '34') ? 'selected' : ''; ?>>Ingeniería Civil, Arquitectura y Urbanismo</option>
                                                   <option value="35" <?php echo (isset($departamento_academico) && $departamento_academico == '35') ? 'selected' : ''; ?>>Ingeniería Industrial</option>
                                                   <option value="36" <?php echo (isset($departamento_academico) && $departamento_academico == '36') ? 'selected' : ''; ?>>Ingeniería de Materiales</option>
                                                   <option value="37" <?php echo (isset($departamento_academico) && $departamento_academico == '37') ? 'selected' : ''; ?>>Mecánica y Energía</option>
                                                   <option value="38" <?php echo (isset($departamento_academico) && $departamento_academico == '38') ? 'selected' : ''; ?>>Ingeniería Metalúrgica</option>
                                                   <option value="39" <?php echo (isset($departamento_academico) && $departamento_academico == '39') ? 'selected' : ''; ?>>Ingeniería de Minas</option>
                                                   <option value="40" <?php echo (isset($departamento_academico) && $departamento_academico == '40') ? 'selected' : ''; ?>>Ingeniería de Sistemas</option>
                                                   <option value="41" <?php echo (isset($departamento_academico) && $departamento_academico == '41') ? 'selected' : ''; ?>>Ingeniería Química</option>
                                                   <option value="42" <?php echo (isset($departamento_academico) && $departamento_academico == '42') ? 'selected' : ''; ?>>Ingeniería Ambiental</option>
                                                   <option value="43" <?php echo (isset($departamento_academico) && $departamento_academico == '43') ? 'selected' : ''; ?>>Química</option>
                                                   <option value="44" <?php echo (isset($departamento_academico) && $departamento_academico == '44') ? 'selected' : ''; ?>>Ciencias Básicas Médicas</option>
                                                   <option value="45" <?php echo (isset($departamento_academico) && $departamento_academico == '45') ? 'selected' : ''; ?>>Cirugía</option>
                                                   <option value="46" <?php echo (isset($departamento_academico) && $departamento_academico == '46') ? 'selected' : ''; ?>>Fisiología Humana</option>
                                                   <option value="47" <?php echo (isset($departamento_academico) && $departamento_academico == '47') ? 'selected' : ''; ?>>Ginecología-Obstetricia</option>
                                                   <option value="48" <?php echo (isset($departamento_academico) && $departamento_academico == '48') ? 'selected' : ''; ?>>Medicina</option>
                                                   <option value="49" <?php echo (isset($departamento_academico) && $departamento_academico == '49') ? 'selected' : ''; ?>>Medicina Preventiva y Salud Pública</option>
                                                   <option value="50" <?php echo (isset($departamento_academico) && $departamento_academico == '50') ? 'selected' : ''; ?>>Morfología Humana</option>
                                                   <option value="51" <?php echo (isset($departamento_academico) && $departamento_academico == '51') ? 'selected' : ''; ?>>Pediatría</option>
                                                   <option value="52" <?php echo (isset($departamento_academico) && $departamento_academico == '52') ? 'selected' : ''; ?>>Ingeniería Mecatrónica</option>
                                                </select>
                                             </div>
                                          </div>
                                          <br>
                                          <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
                                             <thead style="background-color: #28a745; color: white;">
                                                <tr>
                                                   <!-- Encabezado combinado con colspan de 5, para que abarque todas las columnas -->
                                                   <th colspan="3" style="text-align: center;">Unidad ejecutora principal del proyecto</th>
                                                </tr>
                                             </thead>
                                             <tr>
                                                <td><b>Facultad</b><br><?php
                                                   // Array de facultades
                                                   $facultades = [
                                                       '1' => 'Ciencias Agropecuarias','2' => 'Ciencias Biológicas','3' => 'Ciencias Económicas','4' => 'Ciencias Físicas y Matemáticas',
                                                       '5' => 'Ciencias Sociales','6' => 'Derecho y Ciencias Políticas','7' => 'Educación y Ciencias de la Comunicación','8' => 'Enfermería',
                                                       '9' => 'Estomatología','10' => 'Farmacia y Bioquímica','11' => 'Ingeniería','12' => 'Ingeniería Química','13' => 'Medicina',
                                                   ];
                                                   
                                                   // Verificar si la facultad existe y mostrarla
                                                   if (isset($facultad) && isset($facultades[$facultad])) {
                                                       echo htmlspecialchars($facultades[$facultad]);
                                                   } else {
                                                       echo '<h5>No hay facultad</h5>';
                                                   }
                                                   ?></td>
                                                <td><b>Programa de Estudios</b><br><?php
                                                   // Array de programas de estudios
                                                   $programas_estudios = [
                                                       '1' => 'Administración','2' => 'Agronomía','3' => 'Antropología','4' => 'Arqueología','5' => 'Arquitectura y Urbanismo',
                                                       '6' => 'Biología Pesquera','7' => 'Ciencias Biológicas','8' => 'Ciencias de la Comunicación','9' => 'Ciencias Políticas y Gobernabilidad',
                                                       '10' => 'Contabilidad y Finanzas','11' => 'Derecho','12' => 'Economía','13' => 'Educación Inicial','14' => 'Educación Primaria',
                                                       '15' => 'Educación Secundaria Mención Ciencias Naturales','16' => 'Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales',
                                                       '17' => 'Educación Secundaria Mención Historia y Geografía','18' => 'Educación Secundaria Mención Idiomas','19' => 'Educación Secundaria Mención Lengua y Literatura',
                                                       '20' => 'Educación Secundaria Mención Matemáticas','21' => 'Enfermería','22' => 'Estadística','23' => 'Estomatología',
                                                       '24' => 'Farmacia y Bioquímica','25' => 'Física','26' => 'Historia','27' => 'Informática','28' => 'Ingeniería Agrícola',
                                                       '29' => 'Ingeniería Agroindustrial','30' => 'Ingeniería Ambiental','31' => 'Ingeniería Civil','32' => 'Ingeniería de Materiales',
                                                       '33' => 'Ingeniería de Minas','34' => 'Ingeniería de Sistemas','35' => 'Ingeniería Industrial','36' => 'Ingeniería Mecánica',
                                                       '37' => 'Ingeniería Mecatrónica','38' => 'Ingeniería Metalúrgica','39' => 'Ingeniería Química','40' => 'Matemáticas',
                                                       '41' => 'Medicina','42' => 'Microbiología y Parasitología','43' => 'Trabajo Social','44' => 'Turismo','45' => 'Zootecnia',
                                                   ];
                                                   
                                                   // Verificar si el programa de estudios existe y mostrarlo
                                                   if (isset($programa_estudios) && isset($programas_estudios[$programa_estudios])) {
                                                       echo htmlspecialchars($programas_estudios[$programa_estudios]);
                                                   } else {
                                                       echo '<h5>No hay programa de estudios seleccionado</h5>';
                                                   }
                                                   ?></td>
                                                <td><b>Departamento Académico</b><br><?php
                                                   // Array de departamentos académicos
                                                   $departamentos_academicos = [
                                                       '1' => 'Agronomía y Zootecnia','2' => 'Ciencias Agroindustriales','3' => 'Ciencias Biológicas','4' => 'Microbiología y Parasitología',
                                                       '5' => 'Pesquería','6' => 'Química Biológica y Fisiología Animal','7' => 'Administración','8' => 'Contabilidad y Finanzas',
                                                       '9' => 'Economía','10' => 'Ciencias Básicas Estomatológicas','11' => 'Estomatología','12' => 'Estadística', '13' => 'Física',
                                                       '14' => 'Informática','15' => 'Matemáticas','16' => 'Arqueología y Antropología','17' => 'Ciencias Sociales','18' => 'Ciencias Jurídicas Públicas y Políticas',
                                                       '19' => 'Ciencias Jurídicas Privadas y Sociales','20' => 'Ciencia Política y Gobernabilidad','21' => 'Ciencias de la Educación',
                                                       '22' => 'Ciencias Psicológicas','23' => 'Comunicación Social','24' => 'Filosofía y Arte','25' => 'Historia y Geografía',
                                                       '26' => 'Idiomas y Lingüística','27' => 'Lengua Nacional y Literatura','28' => 'Enfermería de la Mujer, Niño y Adolescente',
                                                       '29' => 'Salud del Adulto','30' => 'Salud Familiar y Comunitaria','31' => 'Farmacotecnia','32' => 'Farmacología','33' => 'Bioquímica',
                                                       '34' => 'Ingeniería Civil, Arquitectura y Urbanismo','35' => 'Ingeniería Industrial','36' => 'Ingeniería de Materiales',
                                                       '37' => 'Mecánica y Energía','38' => 'Ingeniería Metalúrgica','39' => 'Ingeniería de Minas','40' => 'Ingeniería de Sistemas',
                                                       '41' => 'Ingeniería Química','42' => 'Ingeniería Ambiental','43' => 'Química','44' => 'Ciencias Básicas Médicas','45' => 'Cirugía',
                                                       '46' => 'Fisiología Humana','47' => 'Ginecología-Obstetricia','48' => 'Medicina','49' => 'Medicina Preventiva y Salud Pública',
                                                       '50' => 'Morfología Humana','51' => 'Pediatría', '52' => 'Ingeniería Mecatrónica',
                                                   ];
                                                   
                                                   // Verificar si el departamento académico existe y mostrarlo
                                                   if (isset($departamento_academico) && isset($departamentos_academicos[$departamento_academico])) {
                                                       echo htmlspecialchars($departamentos_academicos[$departamento_academico]);
                                                   } else {
                                                       echo '<h5>No hay departamento académico seleccionado</h5>';
                                                   }
                                                   ?></td>
                                             </tr>
                                          </table>
                                             <script type="text/javascript">
                                                // Variable global para almacenar la referencia a la ventana
                                                var ventanaFormulario2 = null;
                                                
                                                function abrirFormulario2() {
                                                    // Si la ventana ya está abierta y no ha sido cerrada, simplemente la enfoca
                                                    if (ventanaFormulario2 && !ventanaFormulario2.closed) {
                                                        ventanaFormulario2.focus();
                                                    } else {
                                                        // Si la ventana no está abierta, la abre con las configuraciones deseadas
                                                        ventanaFormulario2 = window.open('unidades_ejecutoras.php', 'Formulario2', 'width=1000,height=600');
                                                    }
                                                }
                                             </script>
                                             <div class="row">
                                             <div class="col-md-8">
                                                <span style="color: red;">
                                                <b>Si tu proyecto tiene más unidades ejecutoras, ingrésalas presionando el siguiente botón.</b> 
                                                </span>
                                             </div>
                                             <div class="col-md-4 text-center">
                                                <!-- Al hacer clic en este enlace, se ejecuta la función abrirFormulario() -->
                                                <a href="javascript:void(0);" onclick="abrirFormulario2()" class="btn btn-success btn-block">
                                                <i class="fa fa-plus"></i> Agregar más unidades
                                                </a>
                                             </div>
                                             </div>
                                             <br>
                                             <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                    </form>
                                    <!-- .FACULTAD -->
                                    </div>
                                    <!-- Navegación -->
                                    <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                    <button class="btn btn-app bg-primary prev-tab" 
                                       data-prev-tab="custom-tabs-fases" 
                                       style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                                    </button>
                                    </div>
                                    <div class="text-right" style="position: absolute; top: 55px; right: 10px;">
                                    <button class="btn btn-app bg-primary next-tab" 
                                       data-next-tab="custom-tabs-responsables" 
                                       style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢂</i>
                                    </button>
                                    </div>
                                    <!-- Navegación -->
                                    </div>
                                    <div class="tab-pane fade" id="custom-tabs-responsables" role="tabpanel" aria-labelledby="custom-tabs-responsables-tab">
                                       <!-- RESPONSABLES -->
                                       <form action="../componentes/proyecto/actualizar_responsables.php" method="POST">
                                          <div class="form-group"> 
    <h6>13.1 Coordinador general del proyecto</h6>
    <div class="card-body">
        <input type="text" class="form-control" id="coordinador" name="coordinador" 
               placeholder="<?php echo htmlspecialchars($coordinador === null ? $nombres . ' ' . $apellidos : $coordinador); ?>" 
               value="<?php echo htmlspecialchars($coordinador === null ? $nombres . ' ' . $apellidos : $coordinador); ?>" 
               required readonly>
    </div>
</div>
                                          <div class="form-group">
                                             <h6>13.2 Coordinador de componentes del proyecto</h6>
                                             <div class="card-body">
                                                <textarea id="summernote5" name="componentes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($componentes) && !empty($componentes) ? htmlspecialchars($componentes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia desde un  ARCHIVO WORD la tabla con los datos de los coordinadores de componentes del proyecto.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>13.3 Integrantes del equipo de docentes</h6>
                                             <div class="card-body">
                                                <textarea id="summernote6" name="integrantes_docentes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($integrantes_docentes) && !empty($integrantes_docentes) ? htmlspecialchars($integrantes_docentes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia desde un  ARCHIVO WORD la tabla con los datos de los integrantes del equipo de docentes.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <div class="form-group">
                                             <h6>13.4 Representantes o delegados del equipo de estudiantes</h6>
                                             <div class="card-body">
                                                <textarea id="summernote7" name="delegados_estudiantes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($delegados_estudiantes) && !empty($delegados_estudiantes) ? htmlspecialchars($delegados_estudiantes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Diseña o copia desde un  ARCHIVO WORD la tabla con los datos de los representantes del equipo de estudiantes.</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                             </div>
                                          </div>
                                          <!-- Botones de navegación -->
                                          <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                                       </form>
                                       <!-- Navegación -->
                                       <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                                          <button class="btn btn-app bg-primary prev-tab" 
                                             data-prev-tab="custom-tabs-facultad" 
                                             style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                                          </button>
                                       </div>
                                       <!-- Navegación -->
                                       <!-- .RESPONSABLES -->
                                    </div>
                                    <!-- hasta aqui el tab -->
                                 </div>
                              </div>
                           </div>
                           <!-- /.card-body -->
                           <div class="card-footer">
                              El presente formulario se basa en el <a href="https://docs.google.com/document/d/1v5PJt7fuEL8yh4NSQm8vNZhIon9Lo915/edit" target="_blank">Formato de esquema de proyectos de RSU</a>
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
            <strong>© 2024 Universidad Nacional de Trujillo.</strong>
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
           // Inicialización de editor de texto Summernote con el idioma en español
           $('#summernote').summernote({
             lang: 'es-ES'
           });
         });
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
         lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
         })
         
         })
      </script>
      <script>
         function updateTotal(row) {
           // Obtiene los valores de los campos de entrada
           var horasPorSemana = parseFloat(document.getElementById('p10_' + row + 'h').value) || 0;
           var semanas = parseFloat(document.getElementById('p10_' + row + 's').value) || 0;
           
           // Calcula la suma
           var total = horasPorSemana * semanas;
           
           // Actualiza el campo de texto bloqueado para cada fila
           document.getElementById('total' + row).value = total;
         
           // Actualiza la suma total de todas las filas
           updateGrandTotal();
         }
         
         function updateGrandTotal() {
           // Suma los valores de total1, total2 y total3
           var total1 = parseFloat(document.getElementById('total1').value) || 0;
           var total2 = parseFloat(document.getElementById('total2').value) || 0;
           var total3 = parseFloat(document.getElementById('total3').value) || 0;
           
           var grandTotal = total1 + total2 + total3;
         
           // Actualiza el cuadro de texto bloqueado para el total general
           document.getElementById('grandTotal').value = grandTotal;
         }
         window.onload = function() {
         // Llama a updateTotal para cada fila al cargar la página
         updateTotal(1); // Para Planificación
         updateTotal(2); // Para Ejecución
         updateTotal(3); // Para Monitoreo y Evaluación
         }
      </script>
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
             // Cargar departamentos
             $.ajax({
                 type: 'POST',
                 url: '../componentes/proyecto/cargar_departamentos.php',
                 success: function(data) {
                     $('#departamento').html(data);
                 }
             });
         
             // Cargar provincias cuando se seleccione un departamento
             $('#departamento').on('change', function() {
                 var departamentoId = $(this).val();
                 if (departamentoId) {
                     $.ajax({
                         type: 'POST',
                         url: '../componentes/proyecto/cargar_provincias.php',
                         data: { departamento_id: departamentoId },
                         success: function(data) {
                             $('#provincia').html(data);
                             $('#distrito').html('<option value="">Selecciona un distrito</option>'); // Limpiar distritos
                         }
                     });
                 } else {
                     $('#provincia').html('<option value="">Selecciona una provincia</option>');
                     $('#distrito').html('<option value="">Selecciona un distrito</option>');
                 }
             });
         
             // Cargar distritos cuando se seleccione una provincia
             $('#provincia').on('change', function() {
                 var provinciaId = $(this).val();
                 if (provinciaId) {
                     $.ajax({
                         type: 'POST',
                         url: '../componentes/proyecto/cargar_distritos.php',
                         data: { provincia_id: provinciaId },
                         success: function(data) {
                             $('#distrito').html(data);
                         }
                     });
                 } else {
                     $('#distrito').html('<option value="">Selecciona un distrito</option>');
                 }
             });
         });
      </script>
   </body>
</html>