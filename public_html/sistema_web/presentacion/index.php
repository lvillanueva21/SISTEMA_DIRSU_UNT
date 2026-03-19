<?php 
// ================== INDEX PRESENTACIÓN (con diagnóstico, sin include dentro de función) ==================

$DEBUG = isset($_GET['debug']) || isset($_GET['DEBUG']);
define('DEBUG_PRESENTACION', $DEBUG);

// Errores / logs
if ($DEBUG) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  ini_set('log_errors', '1');
  error_reporting(E_ALL);
  register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
      http_response_code(500);
      echo '<pre style="white-space:pre-wrap;background:#fee;border:1px solid #c00;padding:10px;margin:10px">';
      echo "FATAL: {$e['message']} in {$e['file']}:{$e['line']}";
      echo "</pre>";
    }
  });
} else {
  ini_set('display_errors', '0');
  ini_set('display_startup_errors', '0');
  ini_set('log_errors', '1');
}

define('DIR_PRESENTACION', __DIR__);
define('DIR_COMPONENTES', realpath(__DIR__ . '/../componentes'));
define('DIR_LOGICA',      realpath(__DIR__ . '/logica'));

require_once DIR_COMPONENTES . '/configSesion.php';
require_once DIR_COMPONENTES . '/db.php';

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Informe Semestral - Sistema DIRSU</title>
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../plogins/daterangepicker/daterangepicker.css">
    <link rel="stylesheet" href="../plogins/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css">
    <link rel="stylesheet" href="../plogins/select2/css/select2.min.css">
    <link rel="stylesheet" href="../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
    <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
    <link rel="stylesheet" href="../plogins/summernote/summernote-bs4.min.css">
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
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
          <li class="nav-item">
            <a class="nav-link" data-widget="fullscreen" href="#" role="button"><i class="fas fa-expand-arrows-alt"></i></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block" style="background-image:url('../web1.png');background-size:cover;background-position:center;color:white;padding:2px;list-style-type:none;filter:brightness(100%);text-shadow:2px 2px 4px rgba(0,0,0,.6);">
            <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank"><p style="color:white;size:8px">Ir a página DIRSU</p></a>
          </li>
          <li class="nav-item d-none d-sm-inline-block">
            <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
          </li>
        </ul>
      </nav>

      <!-- Sidebar -->
      <aside class="main-sidebar sidebar-dark-primary elevation-4">
        <a href="../inicio.php" class="brand-link">
          <img src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTE Logo" class="brand-image img-circle elevation-3" style="opacity:.8">
          <span class="brand-text font-weight-light">Sistema DIRSU</span>
        </a>
        <div class="sidebar">
          <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image"><img src="../dust/img/avatar.png" class="img-circle elevation-2" alt="User Image"></div>
            <?php
              $primer_apellido = explode(' ', htmlspecialchars($apellidos ?? ''))[0] ?? '';
              if (mb_strlen($nombres ?? '') > 22) {
                $texto_a_imprimir = htmlspecialchars($nombres ?? '');
              } elseif (mb_strlen(($nombres ?? '') . ' ' . $primer_apellido) <= 23) {
                $texto_a_imprimir = htmlspecialchars(($nombres ?? '') . ' ' . $primer_apellido);
              } else {
                $texto_a_imprimir = htmlspecialchars(mb_substr(($nombres ?? ''), 0, 23));
              }
            ?>
            <div class="info"><a href="perfil.php" class="d-block"><?php echo $texto_a_imprimir; ?></a></div>
          </div>

          <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
              <li class="nav-item"><a href="../inicio.php" class="nav-link"><i class="nav-icon fas fa-home"></i><p>INICIO</p></a></li>
              <li class="nav-item"><a href="guia.php" class="nav-link"><i class="fa fa-road nav-icon"></i><p>Guía de trabajo</p></a></li>

              <li class="nav-header">Información de proyecto</li>
              <li class="nav-item"><a href="proyecto.php" class="nav-link"><i class="fa fa-users nav-icon"></i><p>Mi proyecto</p></a></li>
              <li class="nav-item"><a href="progreso.php" class="nav-link"><i class="fa fa-chart-line nav-icon"></i><p>Mi progreso</p></a></li>
              <li class="nav-item"><a href="formato.php" class="nav-link"><i class="fa fa-file-word nav-icon"></i><p>Formatos</p></a></li>

              <li class="nav-header">Fases de proyecto</li>
              <li class="nav-item menu">
                <a href="#" class="nav-link"><span class="badge nav-icon">1</span><p>Formulación y presentación</p></a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="datos_principales.php" class="nav-link"><p>1.1. Generalidades</p></a></li>
                  <li class="nav-item"><a href="desarrollo_informe.php" class="nav-link"><p>1.2. Plan de proyecto</p></a></li>
                  <li class="nav-item"><a href="anexos.php" class="nav-link"><p>1.3. Anexos</p></a></li>
                </ul>
              </li>
              <li class="nav-item menu">
                <a href="#" class="nav-link"><span class="badge nav-icon">2</span><p>Ejecución y monitoreo</p></a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="cronograma.php" class="nav-link"><p>2.1. Cronograma de ejecución</p></a></li>
                  <li class="nav-item"><a href="revision_cronograma.php" class="nav-link"><p>2.2. Revisión de cronograma</p></a></li>
                </ul>
              </li>
              <li class="nav-item menu menu-open">
                <a href="#" class="nav-link active"><span class="badge nav-icon">3</span><p>Evaluación e informe</p></a>
                <ul class="nav nav-treeview">
                  <li class="nav-item"><a href="informe_final.php" class="nav-link active"><p>3.1. Informe semestral</p></a></li>
                  <li class="nav-item"><a href="revision_informe_final.php" class="nav-link"><p>3.2. Revisión de informe</p></a></li>
                </ul>
              </li>
              <li class="nav-item" style="height:100px;"></li>
            </ul>
          </nav>
        </div>
      </aside>

      <!-- Content Wrapper -->
      <div class="content-wrapper">
        <section>
<?php if (isset($_GET['err'])): ?>
  <div style="margin:10px; padding:10px; border:1px solid #c00; color:#900; background:#fee;">
    Error: <?= htmlspecialchars($_GET['err']) ?>
  </div>
<?php endif; ?>

<?php
// Mensajes flash
if (!empty($_SESSION['form_msg'])): ?>
  <div class="alert alert-<?= htmlspecialchars($_SESSION['form_msg_type'] ?? 'info') ?>" style="margin:10px;">
    <?= htmlspecialchars($_SESSION['form_msg']) ?>
  </div>
<?php
  unset($_SESSION['form_msg'], $_SESSION['form_msg_type']);
endif;

// ================== LÓGICA PRINCIPAL ==================
require_once DIR_LOGICA . '/funciones.php';
$sm_info = obtenerInfoSemestral($conexion, $id_py);

// Si hubo error
if (isset($sm_info['error'])) {
    echo "<p style='color:#b00;font-weight:bold'>" . htmlspecialchars($sm_info['error']) . "</p>";
} else {
    // Resumen mínimo
    echo "Inicio: "     . htmlspecialchars((string)($sm_info['inicio'] ?? '')) . "<br>";
    echo "Fin: "        . htmlspecialchars((string)($sm_info['fin'] ?? '')) . "<br>";
    echo "Cantidad: "   . htmlspecialchars((string)($sm_info['cantidad'] ?? '')) . "<br>";
    echo "Semestres: "  . htmlspecialchars(implode(' ', $sm_info['semestres'] ?? [])) . "<br>";
    echo "Interfaz: "   . htmlspecialchars((string)($sm_info['interfaz'] ?? '')) . " (" . htmlspecialchars((string)($sm_info['motivo'] ?? '')) . ")<br>";
    echo "Ahora Lima: " . htmlspecialchars((string)($sm_info['ahora_lima'] ?? '')) . "<br>";
    echo "Apertura: "   . htmlspecialchars((string)($sm_info['apertura'] ?? '-')) . "<br>";
    echo "Cierre: "     . htmlspecialchars((string)($sm_info['cierre'] ?? '-')) . "<br>";

    // Decide vista
    $vista = null;
    if ((int)$sm_info['interfaz'] === 2) {
        if (!empty($sm_info['respuesta_id'])) {
            $vista = DIR_LOGICA . '/formulario.php';
        } else {
            $vista = DIR_LOGICA . '/dentroRango.php';
        }
    } else {
        switch ((int)$sm_info['interfaz']) {
            case 0: $vista = DIR_LOGICA . '/sinCronograma.php'; break;
            case 1: $vista = DIR_LOGICA . '/fueraRango.php';    break;
            case 3: $vista = DIR_LOGICA . '/sinFechas.php';     break;
            default: $vista = null;
        }
    }

    // Diagnóstico del include
    if ($vista === null) {
        echo "<div class='alert alert-danger' style='margin:10px'>No se pudo determinar la vista a incluir.</div>";
    } else {
        clearstatcache();
        $exists = file_exists($vista);

        if ($DEBUG) {
            echo "<div style='margin:8px;padding:10px;border:1px dashed #999;background:#f8f9fa'>";
            echo "<strong>DEBUG include</strong><br>";
            echo "Vista: <code>" . htmlspecialchars($vista) . "</code><br>";
            echo "Existe: " . ($exists ? "sí" : "NO") . "<br>";
            if ($exists) echo "mtime: " . date('Y-m-d H:i:s', filemtime($vista)) . "<br>";
            echo "respuesta_id: <code>" . htmlspecialchars((string)($sm_info['respuesta_id'] ?? '')) . "</code><br>";
            echo "form_activo.id: <code>" . htmlspecialchars((string)($sm_info['form_activo']['id'] ?? '')) . "</code>";
            echo "</div>";
        }

        if (!$exists) {
            echo "<div class='alert alert-danger' style='margin:10px'>No se encontró la vista: <code>" . htmlspecialchars($vista) . "</code></div>";
        } else {
            $headersBefore = headers_list();
            ob_start();
            /** @noinspection PhpIncludeInspection */
            $ret = include $vista;
            $html = ob_get_clean();

            $headersAfter = headers_list();
            $redirHeader = null;
            foreach ($headersAfter as $h) {
              if (stripos($h, 'Location:') === 0) { $redirHeader = $h; if ($DEBUG) header_remove('Location'); }
            }

            if ($DEBUG) {
              echo "<div style='margin:8px;padding:10px;border:1px dashed #999;background:#fffaf6'>";
              echo "Resultado include: <code>" . htmlspecialchars(var_export($ret, true)) . "</code><br>";
              echo "Bytes de salida: <strong>" . strlen($html) . "</strong><br>";
              if ($redirHeader) {
                echo "<div style='color:#b35'><strong>OJO:</strong> el include intentó redirigir con <code>" . htmlspecialchars($redirHeader) . "</code>. (Anulada en debug)</div>";
              }
              echo "</div>";
            }

            echo $html ?: ($DEBUG ? "<div class='alert alert-warning' style='margin:10px'>El include no produjo salida (0 bytes).</div>" : "");
        }
    }
}

// Tablas (solo en debug)
if ($DEBUG) {
  function mostrarTabla($titulo, $array) {
      echo "<h2 style='margin-top:16px'>{$titulo}</h2>";
      if (!empty($array)) {
          echo "<table border='1' cellpadding='5' cellspacing='0'>";
          echo "<tr><th>Clave</th><th>Valor</th></tr>";
          foreach ($array as $clave => $valor) {
              echo "<tr>";
              echo "<td>" . htmlspecialchars((string)$clave) . "</td>";
              echo "<td><pre>" . htmlspecialchars(print_r($valor, true)) . "</pre></td>";
              echo "</tr>";
          }
          echo "</table><br>";
      } else {
          echo "<p><i>No hay datos.</i></p>";
      }
  }
  mostrarTabla("Variables de Sesión (\$_SESSION)", $_SESSION);
  mostrarTabla("Variables GET (\$_GET)", $_GET);
  mostrarTabla("Variables POST (\$_POST)", $_POST);
  mostrarTabla("Variables COOKIE (\$_COOKIE)", $_COOKIE);
  mostrarTabla("Variables SERVER (\$_SERVER)", $_SERVER);
}
?>
        </section>
      </div>

      <footer class="main-footer">
        <strong>© 2024 Universidad Nacional de Trujillo.</strong>
        <div class="float-right d-none d-sm-inline-block">
          <p>Desarrollado por el <a href="#"> Área informática - DIRSU</a></p>
        </div>
      </footer>

      <aside class="control-sidebar control-sidebar-dark"></aside>
    </div>

    <!-- JS -->
    <script src="../plogins/jquery/jquery.min.js"></script>
    <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../plogins/moment/moment.min.js"></script>
    <script src="../plogins/inputmask/jquery.inputmask.min.js"></script>
    <script src="../plogins/daterangepicker/daterangepicker.js"></script>
    <script src="../plogins/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js"></script>
    <script src="../plogins/select2/js/select2.full.min.js"></script>
    <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
    <script src="../plogins/summernote/summernote-bs4.min.js"></script>
    <script src="../plogins/summernote/lang/summernote-es-ES.js"></script>
    <script src="../dust/js/adminlte.min.js"></script>
    <script src="../dust/js/demo.js"></script>
    <script>
      $(function () {
        if ($('#summernote').length) $('#summernote').summernote({ lang: 'es-ES' });
      });
    </script>
  </body>
</html>
