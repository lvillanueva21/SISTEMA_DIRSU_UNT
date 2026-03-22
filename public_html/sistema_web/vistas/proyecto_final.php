<?php
  // Incluir configSesion.php para verificar la sesión
  include "../componentes/configSesion.php";
  // Incluir la conexión a la base de datos
  include('../componentes/db.php');
  // Incluir el archivo que carga los datos del proyecto
  include('../componentes/proyecto_final/cargar_proyecto.php');
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
        <!-- Main content -->
        <section class="content">
          <div class="container-fluid">
            <!-- Stepper -->
            <div class="row">
              <div class="col-md-12">
                <?php if ($id_py ==0) { ?>
                <?php
                  // Incluir otro archivo PHP
                  include '../integrados/mensaje_registrar_py.php'; // Aquí se inserta el contenido de 'otro_archivo.php'
                  
                  ?>
                <?php } ?> 
                <?php if ($id_py !=0) { ?>
                <div class="card card-default">
                  <!-- DATOS PRINCIPALES -->
                  <div class="card card-primary card-tabs">
                    <div class="card-header p-0 pt-1">
                      <ul class="nav nav-tabs" id="custom-tabs-two-tab" role="tablist">
                        <li class="nav-item">
                          <a class="nav-link active" id="custom-tabs-titulo-tab" data-toggle="pill" href="#custom-tabs-titulo" role="tab" aria-controls="custom-tabs-titulo" aria-selected="true">I. GENERALIDADES</a>
                        </li>
                        <li class="nav-item">
                          <a class="nav-link" id="custom-tabs-interesados-tab" data-toggle="pill" href="#custom-tabs-interesados" role="tab" aria-controls="custom-tabs-interesados" aria-selected="false">II. RESULTADOS</a>
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
                          <!-- Formulario Título-->
                          <form action="../componentes/proyecto_final/actualizar_titulo.php" method="POST">
                            <div class="form-group">
                              <label for="inputTituloPrograma">1. Título del Programa</label>
                              <input type="text" class="form-control" id="programa" name="programa" 
                                placeholder="Ingresa el título del programa" 
                                value="<?php echo htmlspecialchars($programa); ?>" required>
                            </div>
                            <div class="form-group">
                              <label for="inputTituloProyecto">2. Título del Proyecto</label>
                              <input type="text" class="form-control" id="titulo" name="titulo" 
                                placeholder="Ingresa el título del proyecto" 
                                value="<?php echo htmlspecialchars($titulo); ?>" required>
                            </div>
                            <div class="form-group">
                              <label for="ods">3. Objetivo(s) de Desarrollo Sostenible que cumple el proyecto</label>
                              <select class="select2" multiple="multiple" data-placeholder="Selecciona 1 o más ODS ..." 
                                name="ods[]" id="ods" data-dropdown-css-class="select2" style="width: 100%;" required>
                                <option value="1" <?php echo (in_array('1', $ods)) ? 'selected' : ''; ?>>ODS1: Reducción de los indicadores de la pobreza</option>
                                <option value="2" <?php echo (in_array('2', $ods)) ? 'selected' : ''; ?>>ODS2: Hambre y seguridad alimentaria</option>
                                <option value="3" <?php echo (in_array('3', $ods)) ? 'selected' : ''; ?>>ODS3: Salud y bienestar</option>
                                <option value="4" <?php echo (in_array('4', $ods)) ? 'selected' : ''; ?>>ODS4: Educación de calidad</option>
                                <option value="5" <?php echo (in_array('5', $ods)) ? 'selected' : ''; ?>>ODS5: Igualdad de género y empoderamiento de la mujer</option>
                                <option value="6" <?php echo (in_array('6', $ods)) ? 'selected' : ''; ?>>ODS6: Agua limpia y saneamiento</option>
                                <option value="7" <?php echo (in_array('7', $ods)) ? 'selected' : ''; ?>>ODS7: Energía asequible y no contaminante</option>
                                <option value="8" <?php echo (in_array('8', $ods)) ? 'selected' : ''; ?>>ODS8: Trabajo decente y crecimiento económico</option>
                                <option value="9" <?php echo (in_array('9', $ods)) ? 'selected' : ''; ?>>ODS9: Industria, innovación e infraestructura</option>
                                <option value="10" <?php echo (in_array('10', $ods)) ? 'selected' : ''; ?>>ODS10: Reducir las desigualdades</option>
                                <option value="11" <?php echo (in_array('11', $ods)) ? 'selected' : ''; ?>>ODS11: Ciudades y comunidades sostenibles</option>
                                <option value="12" <?php echo (in_array('12', $ods)) ? 'selected' : ''; ?>>ODS12: Producción y consumo responsables</option>
                                <option value="13" <?php echo (in_array('13', $ods)) ? 'selected' : ''; ?>>ODS13: Acción por el clima</option>
                                <option value="14" <?php echo (in_array('14', $ods)) ? 'selected' : ''; ?>>ODS14: Vida submarina</option>
                                <option value="15" <?php echo (in_array('15', $ods)) ? 'selected' : ''; ?>>ODS15: Vida y ecosistemas terrestres</option>
                                <option value="16" <?php echo (in_array('16', $ods)) ? 'selected' : ''; ?>>ODS16: Paz y justicia e instituciones sólidas</option>
                                <option value="17" <?php echo (in_array('17', $ods)) ? 'selected' : ''; ?>>ODS17: Alianzas para lograr los objetivos</option>
                              </select>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <!-- .Formulario Título-->
                          <!-- Formulario Integrantes-->
                          <form action="../componentes/proyecto_final/actualizar_integrantes.php" method="POST">
                            <div class="form-group">
                              <label for="inputTituloIntegrante">1.4. Integrantes del proyecto</label>
                              <h6>Coordinador</h6>
                              <div class="card-body">
                                <input type="text" class="form-control" id="coordinador" name="coordinador" 
                                  placeholder="<?php echo htmlspecialchars($coordinador === null ? $nombres . ' ' . $apellidos : $coordinador); ?>" 
                                  value="<?php echo htmlspecialchars($coordinador === null ? $nombres . ' ' . $apellidos : $coordinador); ?>" 
                                  required readonly>
                              </div>
                              <div class="form-group">
                                <h6>Integrantes</h6>
                                <div class="card-body">
                                  <textarea id="summernote" name="integrantes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($integrantes) && !empty($integrantes) ? htmlspecialchars($integrantes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <h6>Estudiantes</h6>
                                <div class="card-body">
                                  <textarea id="summernote2" name="estudiantes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($estudiantes) && !empty($estudiantes) ? htmlspecialchars($estudiantes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                                <div class="form-group">
                                <h6>Objetivos, Metas e Indicadores.</h6>
                                <div class="card-body">
                                  <textarea id="summernote3" name="omi">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($omi) && !empty($omi) ? htmlspecialchars($omi) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                                </div>
                              </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <!-- .Formulario Integrantes-->
                          <!-- Formulario Lugar-->
                          <form action="../componentes/proyecto_final/actualizar_lugar.php" method="POST">
                            <div class="form-group">
                              <label for="inputTituloIntegrante">1.6. Lugar de Ejecución</label>
                                <div class="card-body">
                                  <textarea id="summernote4" name="lugar">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($lugar) && !empty($lugar) ? htmlspecialchars($lugar) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                                <label for="inputTituloIntegrante">1.7. Institución y Población Beneficiada</label>
                                <div class="card-body">
                                  <textarea id="summernote5" name="beneficiados">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($beneficiados) && !empty($beneficiados) ? htmlspecialchars($beneficiados) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                                <!-- Duración del proyecto -->
                                          <label for="inputDuracion">1.8. Duración del Proyecto o Actividad</label>        
                                          <div class="row">
                                             <div class="col-md-6">
                                                <h6>Fecha de inicio del proyecto</h6>
                                                <div class="input-group date" id="startdate" data-target-input="nearest">
                                                   <input type="text" class="form-control datetimepicker-input" name="fecha_inicio" placeholder="01/01/1970" value="<?php echo htmlspecialchars($fecha_inicio); ?>" data-target="#startdate" required>
                                                   <div class="input-group-append" data-target="#startdate" data-toggle="datetimepicker">
                                                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                   </div>
                                                </div>
                                             </div>
                                             <div class="col-md-6">
                                                <h6>Fecha de fin del proyecto</h6>
                                                <div class="input-group date" id="enddate" data-target-input="nearest">
                                                   <input type="text" class="form-control datetimepicker-input" name="fecha_fin" placeholder="01/01/1970" value="<?php echo htmlspecialchars($fecha_fin); ?>" data-target="#enddate" required>
                                                   <div class="input-group-append" data-target="#enddate" data-toggle="datetimepicker">
                                                      <div class="input-group-text"><i class="fa fa-calendar"></i></div>
                                                   </div>
                                                </div>
                                             </div>
                                          </div>
                            </div>
                            <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <!-- .Formulario Lugar-->
                        </div>
                        <div class="tab-pane fade" id="custom-tabs-interesados" role="tabpanel" aria-labelledby="custom-tabs-interesados-tab">
                          <form action="../componentes/proyecto_final/actualizar_resumen.php" method="POST">
                              <div class="form-group">
                                  <label for="inputTituloIntegrante">2.1. Resumen</label>
                                <div class="card-body">
                                  <textarea id="summernote6" name="resumen">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($resumen) && !empty($resumen) ? htmlspecialchars($resumen) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.2. Actividades Ejecutadas</label>
                                <div class="card-body">
                                  <textarea id="summernote7" name="actividades">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($actividades) && !empty($actividades) ? htmlspecialchars($actividades) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <form action="../componentes/proyecto_final/actualizar_resultados.php" method="POST">
                              <div class="form-group">
                                  <label for="inputTituloIntegrante">2.3. Resultados</label>
                                <div class="card-body">
                                  <textarea id="summernote8" name="resultados">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($resultados) && !empty($resultados) ? htmlspecialchars($resultados) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.1. Descripción de Resultados</label>
                                <div class="card-body">
                                  <textarea id="summernote9" name="descripcion">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($descripcion) && !empty($descripcion) ? htmlspecialchars($descripcion) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.2. Matriz de indicadores de impacto</label>
                                <div class="card-body">
                                  <textarea id="summernote10" name="matriz">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($matriz) && !empty($matriz) ? htmlspecialchars($matriz) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.3. Comentarios o Discusión de Resultados (Opcional)</label>
                                <div class="card-body">
                                  <textarea id="summernote11" name="comentarios">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($comentarios) && !empty($comentarios) ? htmlspecialchars($comentarios) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <form action="../componentes/proyecto_final/actualizar_conclusiones.php" method="POST">
                              <div class="form-group">
                                  <label for="inputTituloIntegrante">2.3.4. Conclusiones</label>
                                <div class="card-body">
                                  <textarea id="summernote12" name="conclusiones">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($conclusiones) && !empty($conclusiones) ? htmlspecialchars($conclusiones) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.5. Análisis de impacto del proyecto ejecutado</label>
                                <div class="card-body">
                                  <textarea id="summernote13" name="analisis">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($analisis) && !empty($analisis) ? htmlspecialchars($analisis) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.6. Recomendaciones (Opcional)</label>
                                <div class="card-body">
                                  <textarea id="summernote14" name="recomendaciones">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($recomendaciones) && !empty($recomendaciones) ? htmlspecialchars($recomendaciones) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <form action="../componentes/proyecto_final/actualizar_fuentes.php" method="POST">
                              <div class="form-group">
                                  <label for="inputTituloIntegrante">2.3.7. Fuentes consultadas</label>
                                <div class="card-body">
                                  <textarea id="summernote15" name="fuentes">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($fuentes) && !empty($fuentes) ? htmlspecialchars($fuentes) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <div class="form-group">
                                <label for="inputTituloIntegrante">2.3.8. Anexos (Fuentes de verificación)</label>
                                <div class="card-body">
                                  <textarea id="summernote16" name="anexos">
            <!-- INICIO Texto por defecto de cuadro de Necesidades y representantes -->
          <?php echo isset($anexos) && !empty($anexos) ? htmlspecialchars($anexos) : '<p></p><table class="table table-bordered" style="width: 1200px;"><tbody>Aquí puedes diseñar o copiar desde un ARCHIVO WORD la lista de Instituciones Participantes del proyecto ...</tbody></table>'; ?>
          <!-- FIN Texto por defecto de cuadro de Necesidades y representantes -->
        </textarea>
                                </div>
                              </div>
                              <button type="submit" class="btn btn-primary mt-3">Guardar</button>
                          </form>
                          <!-- Navegación -->
                          <div class="text-right" style="position: absolute; top: 55px; right: 100px;">
                            <button class="btn btn-app bg-primary prev-tab" 
                              data-prev-tab="custom-tabs-titulo" 
                              style="width: 80px; height: 30px; padding: 0; display: flex; align-items: center; justify-content: center;">🢀</i>
                            </button>
                          </div>
                          <!-- Navegación -->
                        </div>
                        <!-- hasta aqui el tab -->
                      </div>
                    </div>
                  </div>
                  <!-- /.card-body -->
                  <div class="card-footer">
                    El presente formulario se basa en el <a href="https://docs.google.com/document/d/14dvDBHFufIKKp0XhDid6boNzA3KC15gc/edit" target="_blank">Formato de Esquema de informe final de Proyecto de RSU</a>
                  </div>
                </div>
                <?php } ?>
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
      $(function () {
        // Summernote
        $('#summernote8').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote9').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote10').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote11').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote12').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote13').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote14').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote15').summernote({
      lang: 'es-ES'  // Uso del idioma español, solo para uno de los summernote
      })
      
      })
    </script>
    <script>
      $(function () {
        // Summernote
        $('#summernote16').summernote({
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