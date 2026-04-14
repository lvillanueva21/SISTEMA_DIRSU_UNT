
<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Incluir configSesion.php para verificar la sesión
include "../componentes/configSesion.php";
// Incluir la conexión a la base de datos
include('../componentes/db.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de Proyectos</title>
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
                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
                        <p style="color: white;">Ir a página DIRSU</p>
                    </a>
                </li>
                <li class="nav-item d-none d-sm-inline-block">
                    <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
                </li>
            </ul>
        </nav>
        <!-- Sidebar -->
                <?php include_once "../includes/sidebar.php"; ?>
        <div class="content-wrapper">
<section class="content p-3">
<?php
// ==================== PRE-LOGICA (se ejecuta al cargar la sección) ====================
$alerts = [];
$errors = [];
$readyRows = [];
$createdRows = 0;
$deletedRows = 0;
$estadoFiltro = isset($_GET['f_estado']) ? trim($_GET['f_estado']) : '';
if (!in_array($estadoFiltro, ['', '0', '1'], true)) $estadoFiltro = '';

// NUEVO: parámetro para tracker de proyecto por id_py
$trackPy = isset($_GET['track_py']) ? (int)$_GET['track_py'] : 0;
$tracker = null;

try {
    // Forzar zona horaria en MySQL (Lima, Perú)
    if (isset($conexion) && $conexion instanceof mysqli) {
        @$conexion->query("SET time_zone='-05:00'");
    }

    // Helpers
    $tableExists = function($table) use ($conexion) {
        if (!$conexion) return false;
        $res = @$conexion->query("SHOW TABLES LIKE '". $conexion->real_escape_string($table) ."'");
        return $res && $res->num_rows > 0;
    };

    $firstOfficeId = null;
    if ($tableExists('eva_oficinas')) {
        $res = @$conexion->query("SELECT id FROM eva_oficinas WHERE activo=1 ORDER BY orden ASC LIMIT 1");
        if ($res && $res->num_rows) {
            $row = $res->fetch_assoc();
            $firstOfficeId = (int)$row['id'];
        }
    }

    // -------------------- Acciones (POST) --------------------
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $accion = $_POST['accion'] ?? '';

        // Buscar respuestas listas (estado=1) sin evaluación
        if ($accion === 'buscar_listos') {
            $sql = "SELECT r.id, r.id_py, r.id_formulario, r.id_semestre, r.creado_at
                    FROM sm_respuestas r
                    LEFT JOIN eva_evaluaciones e ON e.id_respuesta = r.id
                    WHERE r.estado = 1 AND e.id IS NULL
                    ORDER BY r.creado_at DESC LIMIT 200";
            $rs = @$conexion->query($sql);
            if ($rs) $readyRows = $rs->fetch_all(MYSQLI_ASSOC);
            else $errors[] = "Error al buscar respuestas listas: " . $conexion->error;
        }

        // Crear ruta para seleccionados (PCF)
        if ($accion === 'crear_ruta' && isset($_POST['ids']) && is_array($_POST['ids'])) {
            if (!$firstOfficeId) {
                $errors[] = "No se encontró la primera oficina (revisa eva_oficinas).";
            } else {
                foreach ($_POST['ids'] as $rid) {
                    $rid = (int)$rid;
                    // Verificar respuesta estado=1
                    $stmt = $conexion->prepare("SELECT id FROM sm_respuestas WHERE id=? AND estado=1");
                    $stmt->bind_param('i', $rid);
                    if (!$stmt->execute()) { $errors[]="Error verificación sm_respuestas: ".$stmt->error; continue; }
                    $rchk = $stmt->get_result();
                    if (!$rchk->num_rows) { $stmt->close(); continue; } // ignorar si no está lista
                    $stmt->close();

                    // Saltar si ya tiene evaluación
                    $stmt = $conexion->prepare("SELECT id FROM eva_evaluaciones WHERE id_respuesta=?");
                    $stmt->bind_param('i', $rid);
                    $stmt->execute(); $echk = $stmt->get_result();
                    if ($echk && $echk->num_rows) { $stmt->close(); continue; }
                    $stmt->close();

                    // Crear eva_evaluaciones
                    $stmt = $conexion->prepare("INSERT INTO eva_evaluaciones (id_respuesta, situacion, id_oficina_actual) VALUES (?, 'en_oficina', ?)");
                    $stmt->bind_param('ii', $rid, $firstOfficeId);
                    if (!$stmt->execute()) { $errors[]="Error insert eva_evaluaciones: ".$stmt->error; $stmt->close(); continue; }
                    $id_eval = $stmt->insert_id;
                    $stmt->close();

                    // Crear instancia de oficina (llegada ahora)
                    $stmt = $conexion->prepare("INSERT INTO eva_oficina_instancias (id_evaluacion, id_oficina, llegada, estado) VALUES (?,?,NOW(),'en_espera')");
                    $stmt->bind_param('ii', $id_eval, $firstOfficeId);
                    if (!$stmt->execute()) { $errors[]="Error insert eva_oficina_instancias: ".$stmt->error; $stmt->close(); continue; }
                    $stmt->close();

                    // Crear calificaciones base para PCF: cotejo + rubrica (en_espera)
                    $tipos = ['cotejo','rubrica'];
                    foreach ($tipos as $t) {
                        $stmt = $conexion->prepare("INSERT INTO eva_calificaciones (id_evaluacion,id_oficina,tipo,estado) VALUES (?,?,?,'en_espera')");
                        $stmt->bind_param('iis', $id_eval, $firstOfficeId, $t);
                        if (!$stmt->execute()) { $errors[]="Error insert eva_calificaciones ($t): ".$stmt->error; }
                        $stmt->close();
                    }

                    $createdRows++;
                }
                if ($createdRows>0) $alerts[] = "Se crearon $createdRows evaluaciones e instancias en la primera oficina.";
            }
        }

        // Eliminar evaluaciones por id_respuesta (rollback de pruebas)
        if ($accion === 'eliminar_ruta' && isset($_POST['id_respuesta_del'])) {
            $rid = (int)$_POST['id_respuesta_del'];
            // Borrado en cascada: eva_evaluaciones -> instancias/calificaciones/aspectos
            $stmt = $conexion->prepare("DELETE FROM eva_evaluaciones WHERE id_respuesta=?");
            $stmt->bind_param('i', $rid);
            if (!$stmt->execute()) { $errors[]="Error delete eva_evaluaciones: ".$stmt->error; }
            else { $deletedRows = $stmt->affected_rows; if ($deletedRows>0) $alerts[]="Eliminadas $deletedRows evaluación(es) (con cascada)."; }
            $stmt->close();
        }

        // NUEVO: Eliminar TODAS las evaluaciones (reset de pruebas)
        if ($accion === 'eliminar_todo') {
            if (!$tableExists('eva_evaluaciones')) {
                $errors[] = "No existe la tabla eva_evaluaciones.";
            } else {
                try {
                    if (method_exists($conexion, 'begin_transaction')) { $conexion->begin_transaction(); }
                    $ok = @$conexion->query("DELETE FROM eva_evaluaciones");
                    if (!$ok) throw new Exception("Error al eliminar todo: " . $conexion->error);
                    if (!empty($_POST['reset_ai'])) {
                        @$conexion->query("ALTER TABLE eva_evaluaciones AUTO_INCREMENT=1");
                        if ($tableExists('eva_oficina_instancias'))  @$conexion->query("ALTER TABLE eva_oficina_instancias AUTO_INCREMENT=1");
                        if ($tableExists('eva_calificaciones'))       @$conexion->query("ALTER TABLE eva_calificaciones AUTO_INCREMENT=1");
                        if ($tableExists('eva_rubrica_aspectos'))     @$conexion->query("ALTER TABLE eva_rubrica_aspectos AUTO_INCREMENT=1");
                    }
                    if (method_exists($conexion, 'commit')) { $conexion->commit(); }
                    $alerts[] = "Se eliminaron todas las evaluaciones y dependientes (eva_*).";
                } catch (Throwable $e) {
                    if (method_exists($conexion, 'rollback')) { $conexion->rollback(); }
                    $errors[] = $e->getMessage();
                }
            }
        }
    }

    // -------------------- Contadores --------------------
    $counts = ['eva_evaluaciones'=>'—','eva_oficina_instancias'=>'—','eva_calificaciones'=>'—','eva_rubrica_aspectos'=>'—'];
    foreach ($counts as $t => $v) {
        if ($tableExists($t)) {
            $res = @$conexion->query("SELECT COUNT(*) c FROM $t");
            if ($res) { $row = $res->fetch_assoc(); $counts[$t] = (int)$row['c']; }
        }
    }

    // -------------------- Listado sm_respuestas (Card 3) --------------------
    $respRows = [];
    $sql = "SELECT id, id_py, id_formulario, id_cronograma, id_semestre, estado, creado_at, actualizado_at
            FROM sm_respuestas";
    if ($estadoFiltro !== '') {
        $sql .= " WHERE estado = ".($estadoFiltro==='1'?'1':'0');
    }
    $sql .= " ORDER BY id DESC LIMIT 300";
    $rs = @$conexion->query($sql);
    if ($rs) { $respRows = $rs->fetch_all(MYSQLI_ASSOC); }

    // -------------------- Listado evaluaciones creadas (Card 4) --------------------
    $evalRows = [];
    if ($tableExists('eva_evaluaciones') && $tableExists('eva_oficina_instancias') && $tableExists('eva_oficinas')) {
        $sql = "SELECT e.id AS eval_id, r.id AS respuesta_id, r.id_py, e.creado_at, e.actualizado_at,
                       o.codigo AS oficina_actual, oi.estado AS estado_oficina,
                       oi.llegada, oi.salida
                FROM eva_evaluaciones e
                JOIN sm_respuestas r ON r.id = e.id_respuesta
                LEFT JOIN eva_oficinas o ON o.id = e.id_oficina_actual
                LEFT JOIN eva_oficina_instancias oi
                       ON oi.id_evaluacion = e.id AND oi.id_oficina = e.id_oficina_actual
                ORDER BY e.id DESC LIMIT 200";
        $rs = @$conexion->query($sql);
        if ($rs) { $evalRows = $rs->fetch_all(MYSQLI_ASSOC); }
    }

    // -------------------- NUEVO: Tracker por id_py --------------------
    if ($trackPy > 0) {
        $tracker = [
            'py_id'        => $trackPy,
            'titulo'       => '—',
            'coordinador'  => '—',
            'current_key'  => 'proceso', // 'proceso' | 'PCF' | 'DD' | 'DF' | 'RSU' | 'aprobado'
            'eval_id'      => null,
            'oficina_cod'  => null,
            'oficina_nom'  => null,
            'situacion'    => null,
            'califs'       => [] // tipo => estado
        ];

// Info de proyecto (titulo) y coordinador via usuarios_proyectos
if ($tableExists('proyectos')) {
    // Título del proyecto (p2)
    $sqlP = "SELECT p2 AS titulo FROM proyectos WHERE id = ?";
    if ($stmt = $conexion->prepare($sqlP)) {
        $stmt->bind_param('i', $trackPy);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $res->num_rows) {
                $row = $res->fetch_assoc();
                $tracker['titulo'] = $row['titulo'] ?? '—';
            }
        }
        $stmt->close();
    }
}

// Coordinador: usuarios_proyectos -> usuarios
if ($tableExists('usuarios_proyectos') && $tableExists('usuarios')) {
    // Toma primero los activos; si hay varios, el más reciente por fecha_asignacion
    $sqlU = "SELECT u.nombres, u.apellidos
             FROM usuarios_proyectos up
             JOIN usuarios u ON u.id = up.id_usuario
             WHERE up.id_proyecto = ?
             ORDER BY up.activo DESC, up.fecha_asignacion DESC, up.id DESC
             LIMIT 1";
    if ($stmt = $conexion->prepare($sqlU)) {
        $stmt->bind_param('i', $trackPy);
        if ($stmt->execute()) {
            $res = $stmt->get_result();
            if ($res && $res->num_rows) {
                $u = $res->fetch_assoc();
                $nombreCompleto = trim(($u['nombres'] ?? '').' '.($u['apellidos'] ?? ''));
                if ($nombreCompleto !== '') $tracker['coordinador'] = $nombreCompleto;
            }
        }
        $stmt->close();
    }
}

        // Evaluación más reciente del proyecto
        if ($tableExists('eva_evaluaciones') && $tableExists('sm_respuestas')) {
            $sqlE = "SELECT e.id AS eval_id, e.situacion, e.id_oficina_actual,
                            o.codigo, o.nombre
                     FROM sm_respuestas r
                     JOIN eva_evaluaciones e ON e.id_respuesta = r.id
                     LEFT JOIN eva_oficinas o ON o.id = e.id_oficina_actual
                     WHERE r.id_py = ?
                     ORDER BY e.id DESC
                     LIMIT 1";
            if ($stmt = $conexion->prepare($sqlE)) {
                $stmt->bind_param('i', $trackPy);
                if ($stmt->execute()) {
                    $res = $stmt->get_result();
                    if ($res && $res->num_rows) {
                        $row = $res->fetch_assoc();
                        $tracker['eval_id']     = (int)$row['eval_id'];
                        $tracker['situacion']   = $row['situacion'];
                        $tracker['oficina_cod'] = $row['codigo'];
                        $tracker['oficina_nom'] = $row['nombre'];

                        if ($row['situacion'] === 'aprobado') {
                            $tracker['current_key'] = 'aprobado';
                        } else {
                            $tracker['current_key'] = $row['codigo'] ?: 'proceso';
                        }

                        // Calificaciones de la oficina actual (si aplica)
                        if ($tracker['current_key'] !== 'aprobado' && !empty($row['id_oficina_actual'])) {
                            $idEval = (int)$row['eval_id'];
                            $idOfi  = (int)$row['id_oficina_actual'];
                            $sqlC = "SELECT tipo, estado FROM eva_calificaciones
                                     WHERE id_evaluacion=? AND id_oficina=?";
                            if ($stmt2 = $conexion->prepare($sqlC)) {
                                $stmt2->bind_param('ii', $idEval, $idOfi);
                                if ($stmt2->execute()) {
                                    $res2 = $stmt2->get_result();
                                    while ($c = $res2->fetch_assoc()) {
                                        $tracker['califs'][$c['tipo']] = $c['estado'];
                                    }
                                }
                                $stmt2->close();
                            }
                        }
                    } else {
                        $tracker['current_key'] = 'proceso';
                    }
                }
                $stmt->close();
            }
        }
    }
} catch (Throwable $th) {
    $errors[] = "Excepción: ".$th->getMessage();
}
?>

<style>
  /* 2x2 grid y tarjetas con scroll interno */
  .cp-row { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); grid-gap: 1rem; }
  @media (max-width: 991.98px){ .cp-row { grid-template-columns: 1fr; } }
  .cp-card { height: 420px; }
  .cp-card .card-body { overflow: auto; max-height: 360px; }
  .nowrap { white-space: nowrap; }
  .table-sm td, .table-sm th { padding: .35rem .5rem; }

  /* NUEVO: estilos del tracker */
  .cp-card-wide { grid-column: 1 / -1; }
  .tracker { display: flex; gap: 16px; align-items: flex-start; overflow-x: auto; padding-bottom: 6px; }
  .stagebox {
    min-width: 190px; background: #6c757d; color: #fff; text-align: center;
    padding: 8px 12px; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 4px rgba(0,0,0,.08);
  }
  .stagebox.active { background: #28a745; }
  .stage-sub { margin-top: 10px; display: flex; flex-direction: column; gap: 8px; }
  .chip { border-radius: 6px; padding: 6px 10px; color: #fff; font-weight: 600; }
  .chip.ok { background: #28a745; }
  .chip.wait { background: #17a2b8; }
  .chip.obs { background: #dc3545; }

  /* --- Tracker compacto (override) --- */
.tracker { gap: 12px; }                 /* antes 16px */
.stagebox{
  min-width: 135px;                     /* antes 190px */
  padding: 6px 8px;                     /* antes 8px 12px */
  font-size: 13px;                      /* antes ~14/16 por defecto */
  border-radius: 6px;                   /* antes 8px */
}
.stage-sub{
  margin-top: 6px;                      /* antes 10px */
  gap: 6px;                             /* antes 8px */
}
.chip{
  padding: 4px 8px;                     /* antes 6px 10px */
  font-size: 12px;                      /* más pequeño */
  border-radius: 5px;                   /* antes 6px */
}

</style>

<div class="container-fluid">
  <!-- Mensajes -->
  <?php if($alerts): ?>
    <div class="alert alert-success">
      <?= implode("<br>", array_map('htmlspecialchars',$alerts)) ?>
    </div>
  <?php endif; ?>
  <?php if($errors): ?>
    <div class="alert alert-danger">
      <?= implode("<br>", array_map('htmlspecialchars',$errors)) ?>
    </div>
  <?php endif; ?>

  <!-- Aviso si no hay primera oficina -->
  <?php if($firstOfficeId === null): ?>
    <div class="alert alert-warning">
      No se encontró la primera oficina en <code>eva_oficinas</code>. Asegúrate de haber insertado PCF, DD, DF, RSU (orden 1..4).
    </div>
  <?php endif; ?>

  <!-- FILA 1 (igual que antes) -->
  <div class="cp-row mb-3">
    <!-- Card 1: Control (crear / eliminar) -->
    <div class="card cp-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-cogs mr-2"></i>Control de creación / eliminación</h3>
      </div>
      <div class="card-body">
        <!-- Buscar respuestas listas -->
        <form method="post" class="mb-2">
          <input type="hidden" name="accion" value="buscar_listos">
          <button class="btn btn-primary btn-sm">
            <i class="fas fa-search mr-1"></i> Buscar respuestas (estado=1) sin evaluación
          </button>
          <small class="text-muted ml-2">Máx. 200</small>
        </form>

        <?php if(!empty($readyRows)): ?>
          <form method="post" id="form-crear">
            <input type="hidden" name="accion" value="crear_ruta">
            <div class="mb-2 d-flex justify-content-between align-items-center">
              <div><strong>Resultados:</strong> <?= count($readyRows) ?></div>
              <div>
                <button type="button" class="btn btn-link btn-sm p-0" onclick="marcar(true)">Marcar todo</button> ·
                <button type="button" class="btn btn-link btn-sm p-0" onclick="marcar(false)">Desmarcar</button>
              </div>
            </div>
            <div class="table-responsive">
              <table class="table table-sm table-hover table-striped">
                <thead class="thead-light">
                  <tr>
                    <th style="width:28px"></th>
                    <th>ID Resp</th>
                    <th>Proyecto</th>
                    <th>Formulario</th>
                    <th>Semestre</th>
                    <th>Creado</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach($readyRows as $r): ?>
                  <tr>
                    <td><input type="checkbox" name="ids[]" value="<?= (int)$r['id'] ?>"></td>
                    <td class="nowrap"><?= (int)$r['id'] ?></td>
                    <td><?= (int)$r['id_py'] ?></td>
                    <td><?= (int)$r['id_formulario'] ?></td>
                    <td><?= (int)$r['id_semestre'] ?></td>
                    <td class="nowrap"><?= htmlspecialchars($r['creado_at']) ?></td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
            <button class="btn btn-success btn-sm mt-2" <?= $firstOfficeId===null?'disabled':'' ?>>
              <i class="fas fa-play mr-1"></i> Crear ruta (PCF) para seleccionados
            </button>
          </form>
        <?php else: ?>
          <p class="text-muted">Usa el botón de búsqueda para listar respuestas listas sin evaluación.</p>
        <?php endif; ?>

        <hr>

        <!-- Eliminar evaluaciones por id_respuesta -->
        <form method="post" class="form-inline mb-2">
          <input type="hidden" name="accion" value="eliminar_ruta">
          <div class="form-group mr-2">
            <label for="id_respuesta_del" class="mr-2">Eliminar por <b>id_respuesta</b>:</label>
            <input type="number" class="form-control form-control-sm" id="id_respuesta_del" name="id_respuesta_del" placeholder="ej. 57" required>
          </div>
          <button class="btn btn-danger btn-sm"><i class="fas fa-trash-alt mr-1"></i> Eliminar evaluación</button>
          <small class="text-muted ml-2">Borrado en cascada</small>
        </form>

        <!-- Eliminar TODO -->
        <form method="post" onsubmit="return confirm('Esta acción eliminará TODAS las evaluaciones y sus dependientes (eva_*), pero NO las respuestas ni las oficinas. ¿Continuar?');">
          <input type="hidden" name="accion" value="eliminar_todo">
          <div class="form-check mb-2">
            <input class="form-check-input" type="checkbox" value="1" id="reset_ai" name="reset_ai">
            <label class="form-check-label" for="reset_ai">Resetear AUTO_INCREMENT</label>
          </div>
          <button class="btn btn-outline-danger btn-sm">
            <i class="fas fa-exclamation-triangle mr-1"></i> Eliminar TODO (eva_evaluaciones + dependientes)
          </button>
          <small class="text-muted ml-2">No borra <code>sm_respuestas</code> ni <code>eva_oficinas</code>.</small>
        </form>
      </div>
    </div>

    <!-- Card 2: Contadores -->
    <div class="card cp-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-tachometer-alt mr-2"></i>Contadores por tabla</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped">
            <thead class="thead-light">
              <tr><th>Tabla</th><th class="text-right">Total</th></tr>
            </thead>
            <tbody>
              <tr><td>eva_evaluaciones</td><td class="text-right"><?= htmlspecialchars((string)$counts['eva_evaluaciones']) ?></td></tr>
              <tr><td>eva_oficina_instancias</td><td class="text-right"><?= htmlspecialchars((string)$counts['eva_oficina_instancias']) ?></td></tr>
              <tr><td>eva_calificaciones</td><td class="text-right"><?= htmlspecialchars((string)$counts['eva_calificaciones']) ?></td></tr>
              <tr><td>eva_rubrica_aspectos</td><td class="text-right"><?= htmlspecialchars((string)$counts['eva_rubrica_aspectos']) ?></td></tr>
            </tbody>
          </table>
        </div>
        <p class="mt-2 text-muted">
          Si ves "?", aún no existe la tabla.  
          La primera oficina se toma por <b>orden ascendente</b> de <code>eva_oficinas</code>.
        </p>
      </div>
    </div>
  </div>

  <!-- FILA 2 (igual que antes) -->
  <div class="cp-row">
    <!-- Card 3: sm_respuestas filtrable -->
    <div class="card cp-card">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="fas fa-database mr-2"></i>sm_respuestas (filtro por estado)</h3>
        <form method="get" class="form-inline">
          <label class="mr-2">Estado:</label>
          <select name="f_estado" class="form-control form-control-sm" onchange="this.form.submit()">
            <option value=""  <?= $estadoFiltro===''?'selected':'' ?>>Todos</option>
            <option value="1" <?= $estadoFiltro==='1'?'selected':'' ?>>1 (Terminado)</option>
            <option value="0" <?= $estadoFiltro==='0'?'selected':'' ?>>0 (En desarrollo)</option>
          </select>
        </form>
      </div>
      <div class="card-body">
        <div class="mb-2"><strong>Total:</strong> <?= count($respRows) ?></div>
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-light">
              <tr>
                <th>ID</th><th>Proyecto</th><th>Formulario</th><th>Cronograma</th><th>Semestre</th><th>Estado</th><th>Creado</th><th>Actualizado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($respRows as $r): ?>
              <tr>
                <td class="nowrap"><?= (int)$r['id'] ?></td>
                <td><?= (int)$r['id_py'] ?></td>
                <td><?= (int)$r['id_formulario'] ?></td>
                <td><?= (int)$r['id_cronograma'] ?></td>
                <td><?= (int)$r['id_semestre'] ?></td>
                <td><?= (int)$r['estado'] ?></td>
                <td class="nowrap"><?= htmlspecialchars($r['creado_at']) ?></td>
                <td class="nowrap"><?= htmlspecialchars($r['actualizado_at']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($respRows)): ?>
              <tr><td colspan="8" class="text-muted">Sin registros.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Card 4: Evaluaciones creadas -->
    <div class="card cp-card">
      <div class="card-header">
        <h3 class="card-title"><i class="fas fa-project-diagram mr-2"></i>Evaluaciones creadas (últimas 200)</h3>
      </div>
      <div class="card-body">
        <div class="table-responsive">
          <table class="table table-sm table-striped table-hover">
            <thead class="thead-light">
              <tr>
                <th>Eval ID</th>
                <th>ID Resp</th>
                <th>Proyecto</th>
                <th>Oficina actual</th>
                <th>Estado oficina</th>
                <th>Llegada</th>
                <th>Salida</th>
                <th>Creado</th>
                <th>Actualizado</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($evalRows as $e): ?>
              <tr>
                <td class="nowrap"><?= (int)$e['eval_id'] ?></td>
                <td class="nowrap"><?= (int)$e['respuesta_id'] ?></td>
                <td><?= (int)$e['id_py'] ?></td>
                <td><?= htmlspecialchars($e['oficina_actual'] ?? '') ?></td>
                <td><?= htmlspecialchars($e['estado_oficina'] ?? '') ?></td>
                <td class="nowrap"><?= htmlspecialchars($e['llegada'] ?? '') ?></td>
                <td class="nowrap"><?= htmlspecialchars($e['salida'] ?? '') ?></td>
                <td class="nowrap"><?= htmlspecialchars($e['creado_at']) ?></td>
                <td class="nowrap"><?= htmlspecialchars($e['actualizado_at']) ?></td>
              </tr>
              <?php endforeach; ?>
              <?php if(empty($evalRows)): ?>
              <tr><td colspan="9" class="text-muted">Aún no hay evaluaciones creadas.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>

  <!-- FILA 3: TRACKER VISUAL POR id_py -->
  <div class="cp-row mt-3">
    <div class="card cp-card cp-card-wide">
      <div class="card-header d-flex align-items-center justify-content-between">
        <h3 class="card-title"><i class="fas fa-route mr-2"></i>Tracker de proyecto por <b>id_py</b></h3>
        <form method="get" class="form-inline">
          <!-- preserva filtro de card 3 si existiera -->
          <?php if($estadoFiltro!==''): ?>
            <input type="hidden" name="f_estado" value="<?= htmlspecialchars($estadoFiltro) ?>">
          <?php endif; ?>
          <label class="mr-2">id_py:</label>
          <input type="number" class="form-control form-control-sm mr-2" name="track_py" value="<?= $trackPy>0? (int)$trackPy : '' ?>" placeholder="ej. 101" required>
          <button class="btn btn-sm btn-primary"><i class="fas fa-search mr-1"></i> Ver</button>
        </form>
      </div>
      <div class="card-body">
        <?php if($trackPy>0): ?>
          <div class="mb-2">
            <div><strong>Proyecto:</strong> <?= htmlspecialchars($tracker['titulo'] ?? '—') ?> <small class="text-muted">(id_py <?= (int)$trackPy ?>)</small></div>
            <div><strong>Coordinador:</strong> <?= htmlspecialchars($tracker['coordinador'] ?? '—') ?></div>
          </div>

          <?php
            // Helper de clase para chip por estado
            $chipClass = function($estado) {
              switch ($estado) {
                case 'aprobado':   return 'chip ok';
                case 'observado':  return 'chip obs';
                case 'en_espera':  return 'chip wait';
                default:           return 'chip wait';
              }
            };
            $current = $tracker['current_key']; // proceso | PCF | DD | DF | RSU | aprobado
            $califs  = $tracker['califs'] ?? [];
          ?>

          <div class="tracker">
            <!-- En proceso -->
            <div class="stagebox <?= $current==='proceso'?'active':'' ?>">En proceso</div>

            <!-- PCF -->
            <div class="stagebox <?= $current==='PCF'?'active':'' ?>">
              Comité de Facultad
              <?php if($current==='PCF'): ?>
                <div class="stage-sub">
                  <div class="<?= $chipClass($califs['cotejo'] ?? 'en_espera') ?>">Cotejo</div>
                  <div class="<?= $chipClass($califs['rubrica'] ?? 'en_espera') ?>">Rúbrica</div>
                </div>
              <?php endif; ?>
            </div>

            <!-- DD -->
            <div class="stagebox <?= $current==='DD'?'active':'' ?>">
              Dirección de Departamento
              <?php if($current==='DD'): ?>
                <div class="stage-sub">
                  <div class="<?= $chipClass($califs['vistobueno'] ?? 'en_espera') ?>">Visto Bueno</div>
                </div>
              <?php endif; ?>
            </div>

            <!-- DF -->
            <div class="stagebox <?= $current==='DF'?'active':'' ?>">
              Decanato de Facultad
              <?php if($current==='DF'): ?>
                <div class="stage-sub">
                  <div class="<?= $chipClass($califs['vistobueno'] ?? 'en_espera') ?>">Visto Bueno</div>
                </div>
              <?php endif; ?>
            </div>

            <!-- RSU -->
            <div class="stagebox <?= $current==='RSU'?'active':'' ?>">
              Dirección RSU
              <?php if($current==='RSU'): ?>
                <div class="stage-sub">
                  <div class="<?= $chipClass($califs['cotejo'] ?? 'en_espera') ?>">Cotejo</div>
                  <div class="<?= $chipClass($califs['rubrica'] ?? 'en_espera') ?>">Rúbrica</div>
                </div>
              <?php endif; ?>
            </div>

            <!-- Aprobación total -->
            <div class="stagebox <?= $current==='aprobado'?'active':'' ?>">Aprobación Total</div>
          </div>

          <?php if($tracker['eval_id']===null): ?>
            <p class="text-muted mt-2">Este proyecto aún no ha iniciado su ruta de evaluación.</p>
          <?php endif; ?>

        <?php else: ?>
          <p class="text-muted">Ingresa un <b>id_py</b> y presiona "Ver" para visualizar el estado.</p>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<script>
  function marcar(val){
    document.querySelectorAll('#form-crear input[type=checkbox]').forEach(ch => ch.checked = val);
  }
</script>
</section>
        </div>
        <footer class="main-footer">
            <strong>© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
                <p>Desarrollado por el <a href="https://adminlte.io"> Área  informática - DIRSU</a></p>
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
</body>
</html>

