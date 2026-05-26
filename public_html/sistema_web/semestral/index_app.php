<?php 
// ================== INDEX PRESENTACIÓN (con diagnóstico, sin include dentro de función) ==================

$DEBUG = isset($_GET['debug']) || isset($_GET['DEBUG']);
define('DEBUG_PRESENTACION', $DEBUG);

ini_set('log_errors', '1');
if ($DEBUG) {
  ini_set('display_errors', '1');
  ini_set('display_startup_errors', '1');
  error_reporting(E_ALL);
} else {
  ini_set('display_errors', '0');
  ini_set('display_startup_errors', '0');
  error_reporting(E_ALL);
}

if (!function_exists('rsu_sf_diag_stringify')) {
  function rsu_sf_diag_stringify($value)
  {
    if (is_scalar($value) || $value === null) {
      return (string)$value;
    }
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
  }
}

if (!function_exists('rsu_sf_diag_build_context')) {
  function rsu_sf_diag_build_context()
  {
    $sessionData = array();
    if (session_status() === PHP_SESSION_ACTIVE && isset($_SESSION) && is_array($_SESSION)) {
      $allowed = array('usuario', 'id_rol', 'id_py', 'id_escuela', 'id_sede', 'id_depa');
      foreach ($allowed as $k) {
        if (array_key_exists($k, $_SESSION)) {
          $sessionData[$k] = $_SESSION[$k];
        }
      }
    }

    return array(
      'timestamp' => date('Y-m-d H:i:s'),
      'php_version' => PHP_VERSION,
      'request_uri' => isset($_SERVER['REQUEST_URI']) ? (string)$_SERVER['REQUEST_URI'] : '',
      'script_name' => isset($_SERVER['SCRIPT_NAME']) ? (string)$_SERVER['SCRIPT_NAME'] : '',
      'script_filename' => isset($_SERVER['SCRIPT_FILENAME']) ? (string)$_SERVER['SCRIPT_FILENAME'] : '',
      'method' => isset($_SERVER['REQUEST_METHOD']) ? (string)$_SERVER['REQUEST_METHOD'] : '',
      'remote_addr' => isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : '',
      'query' => isset($_GET) ? $_GET : array(),
      'post_keys' => isset($_POST) && is_array($_POST) ? array_keys($_POST) : array(),
      'session' => $sessionData
    );
  }
}

if (!function_exists('rsu_sf_diag_render_modal_page')) {
  function rsu_sf_diag_render_modal_page($title, array $detail, $httpCode = 500)
  {
    while (ob_get_level() > 0) {
      @ob_end_clean();
    }

    if (!headers_sent()) {
      http_response_code((int)$httpCode);
      header('Content-Type: text/html; charset=UTF-8');
    }

    $safeTitle = htmlspecialchars((string)$title, ENT_QUOTES, 'UTF-8');
    $rawJson = json_encode($detail, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $safeJson = htmlspecialchars((string)$rawJson, ENT_QUOTES, 'UTF-8');

    echo '<!doctype html><html lang="es"><head><meta charset="utf-8">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1">';
    echo '<title>Error detallado - Informe semestral</title>';
    echo '<style>
      body{margin:0;font-family:Arial,Helvetica,sans-serif;background:#f4f6f9;color:#1f2937;}
      .wrap{max-width:980px;margin:30px auto;padding:0 16px;}
      .top{background:#fff3cd;border:1px solid #ffe69c;color:#664d03;padding:14px 16px;border-radius:8px;}
      .btn{display:inline-block;margin-top:12px;padding:9px 14px;border:0;border-radius:6px;background:#0d6efd;color:#fff;cursor:pointer;font-weight:600;}
      .btn:active{transform:translateY(1px);}
      .overlay{position:fixed;inset:0;background:rgba(0,0,0,.4);display:flex;align-items:center;justify-content:center;padding:18px;}
      .modal{width:min(980px,100%);max-height:92vh;overflow:auto;background:#fff;border-radius:10px;box-shadow:0 15px 45px rgba(0,0,0,.25);}
      .head{padding:14px 16px;background:#dc3545;color:#fff;font-size:18px;font-weight:700;}
      .body{padding:16px;}
      .hint{margin:0 0 12px 0;color:#4b5563;}
      textarea{width:100%;min-height:420px;font-family:Consolas,Monaco,monospace;font-size:13px;line-height:1.45;padding:12px;border:1px solid #d1d5db;border-radius:8px;box-sizing:border-box;}
      .actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:12px;}
      .btn-secondary{background:#6c757d;}
    </style>';
    echo '</head><body>';
    echo '<div class="wrap">';
    echo '<div class="top"><strong>Se detecto un error interno.</strong><br>Se abrio un diagnostico tecnico completo para copiar y compartir.</div>';
    echo '</div>';
    echo '<div class="overlay">';
    echo '<div class="modal" role="dialog" aria-modal="true">';
    echo '<div class="head">' . $safeTitle . '</div>';
    echo '<div class="body">';
    echo '<p class="hint">Copia este detalle completo y pasamelo para resolverlo con precision.</p>';
    echo '<textarea id="diagText" readonly>' . $safeJson . '</textarea>';
    echo '<div class="actions">';
    echo '<button type="button" class="btn" id="btnCopyDiag">Copiar detalle</button>';
    echo '<button type="button" class="btn btn-secondary" onclick="location.reload()">Recargar</button>';
    echo '</div></div></div></div>';
    echo '<script>
      (function(){
        var btn = document.getElementById("btnCopyDiag");
        var txt = document.getElementById("diagText");
        if(!btn || !txt){ return; }
        btn.addEventListener("click", function(){
          txt.focus(); txt.select(); txt.setSelectionRange(0, txt.value.length);
          var ok = false;
          try { ok = document.execCommand("copy"); } catch(e) { ok = false; }
          if(ok){ btn.textContent = "Copiado"; } else { btn.textContent = "No se pudo copiar"; }
        });
      })();
    </script>';
    echo '</body></html>';
    exit;
  }
}

if (!function_exists('rsu_sf_diag_handle_exception')) {
  function rsu_sf_diag_handle_exception(Throwable $ex)
  {
    $detail = array(
      'kind' => 'uncaught_exception',
      'type' => get_class($ex),
      'message' => $ex->getMessage(),
      'file' => $ex->getFile(),
      'line' => $ex->getLine(),
      'trace' => $ex->getTraceAsString(),
      'context' => rsu_sf_diag_build_context()
    );
    error_log('[semestral/index][exception] ' . rsu_sf_diag_stringify($detail));
    rsu_sf_diag_render_modal_page('Error detallado del sistema (excepcion)', $detail, 500);
  }
}

if (!function_exists('rsu_sf_diag_handle_shutdown')) {
  function rsu_sf_diag_handle_shutdown()
  {
    $e = error_get_last();
    if (!$e || !isset($e['type'])) {
      return;
    }
    $fatalTypes = array(E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR, E_RECOVERABLE_ERROR);
    if (!in_array((int)$e['type'], $fatalTypes, true)) {
      return;
    }

    $detail = array(
      'kind' => 'fatal_shutdown',
      'type' => (int)$e['type'],
      'message' => isset($e['message']) ? (string)$e['message'] : '',
      'file' => isset($e['file']) ? (string)$e['file'] : '',
      'line' => isset($e['line']) ? (int)$e['line'] : 0,
      'context' => rsu_sf_diag_build_context()
    );
    error_log('[semestral/index][fatal] ' . rsu_sf_diag_stringify($detail));
    rsu_sf_diag_render_modal_page('Error fatal detallado del sistema', $detail, 500);
  }
}

set_exception_handler('rsu_sf_diag_handle_exception');
register_shutdown_function('rsu_sf_diag_handle_shutdown');

define('DIR_PRESENTACION', __DIR__);
define('DIR_COMPONENTES', realpath(__DIR__ . '/../componentes'));
define('DIR_LOGICA',      realpath(__DIR__ . '/logica'));

require_once DIR_COMPONENTES . '/configSesion.php';
require_once DIR_COMPONENTES . '/db.php';
require_once DIR_COMPONENTES . '/cronograma/visibilidad_fase1.php';
require_once __DIR__ . '/../includes/access/project_interface_guard.php';
require_once __DIR__ . '/../includes/access/project_initial_data_gate.php';

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
$rsu_initial_data_status = rsu_project_initial_data_get_status($conexion, isset($id_py) ? (int)$id_py : 0);
if ((int)$id_py > 0 && !empty($rsu_initial_data_status['needs_block'])) {
    rsu_project_initial_data_render_modal($rsu_initial_data_status, array(
        'save_url' => '../componentes/proyecto/guardar_datos_iniciales.php',
        'preview_api_url' => '../includes/api_dirsu/api.php',
        'fallback_return' => '../semestral/index.php'
    ));
} else {
    $rsu_access_eval = rsu_project_interface_guard($conexion, 'F3-SEMESTRAL');

    // Si hubo error
    if (empty($rsu_access_eval['allow'])) {
        include __DIR__ . '/../integrados/mensaje_fuera_tiempo.php';
    } else {
    require_once DIR_LOGICA . '/funciones.php';
    try {
        $sm_info = obtenerInfoSemestral($conexion, $id_py, is_array($rsu_access_eval) ? $rsu_access_eval : null);
    } catch (Throwable $e) {
        rsu_sf_diag_handle_exception($e);
    }
    if (isset($sm_info['error'])) {
        $diagError = array(
            'kind' => 'handled_flow_error',
            'message' => (string)$sm_info['error'],
            'access_allow' => isset($rsu_access_eval['allow']) ? (bool)$rsu_access_eval['allow'] : null,
            'context' => rsu_sf_diag_build_context()
        );
        rsu_sf_diag_render_modal_page('Error detallado del flujo del informe', $diagError, 500);
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
