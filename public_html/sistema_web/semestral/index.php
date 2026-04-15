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
require_once DIR_COMPONENTES . '/cronograma/visibilidad_fase1.php';
require_once __DIR__ . '/../includes/access/project_interface_guard.php';

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
      <?php include_once __DIR__ . '/../includes/sidebar.php'; ?>

      <!-- Content Wrapper -->
      <div class="content-wrapper">
        <section>
<?php if (isset($_GET['err'])): ?>
  <div style="margin:10px; padding:10px; border:1px solid #c00; color:#900; background:#fee;">
    Error: <?= htmlspecialchars($_GET['err']) ?>
  </div>
<?php endif; ?>

<?php
// ====== MENSAJES FLASH -> MODAL (SOLO MARCAUP, sin JS aquí) ======
if (!empty($_SESSION['form_msg'])):
  $msg   = $_SESSION['form_msg'];
  $type  = $_SESSION['form_msg_type'] ?? 'info';
  $title = ($type === 'success') ? 'Éxito' : (($type === 'danger') ? 'Error' : 'Aviso');
?>
  <!-- Modal flash -->
  <div class="modal fade" id="flashModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header py-2">
          <h6 class="modal-title mb-0"><?= htmlspecialchars($title) ?></h6>
          <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
            <span aria-hidden="true">&times;</span>
          </button>
        </div>
        <div class="modal-body">
          <?= htmlspecialchars($msg) ?>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">Aceptar</button>
        </div>
      </div>
    </div>
  </div>
<?php
  // Limpiar el flash tras pintarlo (evita que reaparezca al refrescar)
  unset($_SESSION['form_msg'], $_SESSION['form_msg_type']);
endif;


// ================== LÓGICA PRINCIPAL ==================
$rsu_access_eval = rsu_project_interface_guard($conexion, 'F3-SEMESTRAL');

// Si hubo error
if (empty($rsu_access_eval['allow'])) {
    include __DIR__ . '/../integrados/mensaje_fuera_tiempo.php';
} else {
    require_once DIR_LOGICA . '/funciones.php';
    $sm_info = obtenerInfoSemestral($conexion, $id_py, is_array($rsu_access_eval) ? $rsu_access_eval : null);
    if (isset($sm_info['error'])) {
        echo "<p style='color:#b00;font-weight:bold'>" . htmlspecialchars($sm_info['error']) . "</p>";
    } else {
    // Resumen mínimo
    //echo "Inicio: "     . htmlspecialchars((string)($sm_info['inicio'] ?? '')) . "<br>";
    //echo "Fin: "        . htmlspecialchars((string)($sm_info['fin'] ?? '')) . "<br>";
    //echo "Cantidad: "   . htmlspecialchars((string)($sm_info['cantidad'] ?? '')) . "<br>";
    //echo "Semestres: "  . htmlspecialchars(implode(' ', $sm_info['semestres'] ?? [])) . "<br>";
    //echo "Interfaz: "   . htmlspecialchars((string)($sm_info['interfaz'] ?? '')) . " (" . htmlspecialchars((string)($sm_info['motivo'] ?? '')) . ")<br>";
    //echo "Ahora Lima: " . htmlspecialchars((string)($sm_info['ahora_lima'] ?? '')) . "<br>";
    //echo "Apertura: "   . htmlspecialchars((string)($sm_info['apertura'] ?? '-')) . "<br>";
    //echo "Cierre: "     . htmlspecialchars((string)($sm_info['cierre'] ?? '-')) . "<br>";

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
    <script>
  // Abre el modal de flash en cuanto jQuery/Bootstrap estén listos.
  (function showFlashModalWhenReady() {
    var tried = 0;
    function tryShow() {
      var el = document.getElementById('flashModal');
      if (!el) return; // no hay flash que mostrar

      // Bootstrap 4 (vía jQuery)
      if (window.jQuery && jQuery.fn && typeof jQuery.fn.modal === 'function') {
        jQuery('#flashModal').modal('show');
        return;
      }
      // Bootstrap 5 (sin jQuery)
      if (window.bootstrap && window.bootstrap.Modal) {
        new bootstrap.Modal(el).show();
        return;
      }
      // Aún no cargan las libs (porque están al final): reintenta un ratito.
      if (tried++ < 100) { // ~5s a intervalos de 50ms
        return setTimeout(tryShow, 50);
      }
    }
    tryShow();
  })();
</script>

  </body>
</html>
