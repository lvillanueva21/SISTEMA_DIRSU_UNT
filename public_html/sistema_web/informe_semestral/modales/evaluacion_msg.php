<?php
// /sistema_web/informe_semestral/modales/evaluacion_msg.php
header('Content-Type: text/html; charset=utf-8');

include_once __DIR__ . '/../funciones.php'; // testeo(), whereFiltroPorRol(), $conexion

$usr        = testeo();
$id_rol     = (int)$usr['id_rol'];
$rol_nombre = $usr['rol'] ?? 'Rol no identificado';

$accion = isset($_GET['accion']) ? strtolower(trim((string)$_GET['accion'])) : '';
$id_py  = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_py_req = $id_py;
$id_respuesta = isset($_GET['id_respuesta']) ? (int)$_GET['id_respuesta'] : 0;
$id_periodo = isset($_GET['semestral']) ? (int)$_GET['semestral'] : (isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0);
$periodo_sel_nombre = '';
$forzar_sin_respuesta = false;

if ($id_periodo > 0) {
    $sqlNomPeriodo = "SELECT nombre FROM periodos WHERE id = $id_periodo LIMIT 1";
    if ($rsNomPeriodo = mysqli_query($conexion, $sqlNomPeriodo)) {
        if ($rNomPeriodo = mysqli_fetch_assoc($rsNomPeriodo)) {
            $periodo_sel_nombre = (string)($rNomPeriodo['nombre'] ?? '');
        }
        mysqli_free_result($rsNomPeriodo);
    }
}

$periodoMatchSql = '';
if ($id_periodo > 0) {
    $periodoMatchSql = " AND EXISTS (
      SELECT 1
      FROM periodos prf
      WHERE prf.id = $id_periodo
        AND prf.nombre COLLATE utf8mb4_unicode_ci = CONCAT(
          CAST(s.anio AS CHAR CHARACTER SET utf8mb4),
          '-',
          CAST(s.periodo AS CHAR CHARACTER SET utf8mb4)
        ) COLLATE utf8mb4_unicode_ci
    ) ";
}

if ($id_respuesta > 0) {
    $sqlResp = "SELECT id_py FROM sm_respuestas WHERE id = $id_respuesta LIMIT 1";
    if ($rsResp = mysqli_query($conexion, $sqlResp)) {
        if ($rResp = mysqli_fetch_assoc($rsResp)) {
            $id_py_resp = (int)($rResp['id_py'] ?? 0);
            if ($id_py_req > 0 && $id_py_resp > 0 && $id_py_req !== $id_py_resp) {
                mysqli_free_result($rsResp);
                echo '<div class="alert alert-danger mb-0">Parámetros inconsistentes entre proyecto y respuesta.</div>';
                exit;
            }
            $id_py = $id_py_resp;
        }
        mysqli_free_result($rsResp);
    }

    if ($id_periodo > 0) {
        $sqlRespPeriodo = "
          SELECT 1
          FROM sm_respuestas r
          JOIN sm_proyecto_semestres s
            ON s.id = r.id_semestre
           AND s.tipo = 'semestral'
           AND COALESCE(s.vigente, 1) = 1
          WHERE r.id = $id_respuesta
          $periodoMatchSql
          LIMIT 1
        ";
        $match = false;
        if ($rsRespPeriodo = mysqli_query($conexion, $sqlRespPeriodo)) {
            $match = (bool)mysqli_fetch_assoc($rsRespPeriodo);
            mysqli_free_result($rsRespPeriodo);
        }
        if (!$match) {
            $id_respuesta = 0;
            $forzar_sin_respuesta = true;
        }
    }
} elseif ($id_py > 0) {
    // Fallback controlado: resolver respuesta según periodo seleccionado (si existe)
    if ($id_periodo > 0) {
        $sqlUltRespPeriodo = "
          SELECT r.id
          FROM sm_respuestas r
          JOIN sm_proyecto_semestres s
            ON s.id = r.id_semestre
           AND s.tipo = 'semestral'
           AND COALESCE(s.vigente, 1) = 1
          WHERE r.id_py = $id_py
          $periodoMatchSql
          ORDER BY r.actualizado_at DESC, r.id DESC
          LIMIT 1
        ";
        if ($rsUlt = mysqli_query($conexion, $sqlUltRespPeriodo)) {
            if ($rUlt = mysqli_fetch_assoc($rsUlt)) {
                $id_respuesta = (int)($rUlt['id'] ?? 0);
            }
            mysqli_free_result($rsUlt);
        }
        if ($id_respuesta <= 0) $forzar_sin_respuesta = true;
    } else {
        $sqlUltResp = "SELECT id FROM sm_respuestas WHERE id_py = $id_py ORDER BY actualizado_at DESC, id DESC LIMIT 1";
        if ($rsUlt = mysqli_query($conexion, $sqlUltResp)) {
            if ($rUlt = mysqli_fetch_assoc($rsUlt)) {
                $id_respuesta = (int)($rUlt['id'] ?? 0);
            }
            mysqli_free_result($rsUlt);
        }
    }
}

$labels = [
  'cotejo'  => 'Calificar Cotejo',
  'rubrica' => 'Calificar Rúbrica',
  'vb'      => 'Visto Bueno',
];
$label = $labels[$accion] ?? 'Acción';

$coordinador = '';
$titulo      = '';
$formulario  = '';

/* ===========================
   CARGA BASE DEL PROYECTO (tu SQL estable)
   =========================== */
if ($id_py > 0) {
    $sql = "
      SELECT
        p.p2 AS titulo,
        TRIM(CONCAT(u.nombres,' ',u.apellidos)) AS coordinador,
        f.nombre AS formulario
      FROM proyectos p
      LEFT JOIN usuarios_proyectos up
             ON up.id_proyecto = p.id AND up.activo = 1
      LEFT JOIN usuarios u
             ON u.id = up.id_usuario
      LEFT JOIN sm_respuestas r
             ON r.id_py = p.id
            AND r.id = ?
      LEFT JOIN sm_formularios f ON f.id = r.id_formulario
      WHERE p.id = ?
      LIMIT 1
    ";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        $ridBind = ($id_respuesta > 0) ? $id_respuesta : 0;
        mysqli_stmt_bind_param($stmt, 'ii', $ridBind, $id_py);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $coordinador = $row['coordinador'] ?? '';
                $titulo      = $row['titulo'] ?? '';
                $formulario  = $row['formulario'] ?? '';
            }
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);
    }
}

/* ===========================
   PRECARGA DE CALIFICACIONES (solo campos existentes)
   =========================== */
$pre_cotejo_estado = null;      // en_espera | aprobado | observado
$pre_cotejo_obs    = null;      // texto (obs_general)

$pre_vb_estado     = null;      // en_espera | aprobado

$pre_rb_estado     = null;      // en_espera | aprobado | observado
$rb_scores         = [1=>0,2=>0,3=>0,4=>0,5=>0]; // nota 0..4
$rb_obss           = [1=>'',
                      2=>'',
                      3=>'',
                      4=>'',
                      5=>''];

if ($id_py > 0 && $id_respuesta > 0 && !$forzar_sin_respuesta) {
    // Ubicar evaluación de la respuesta seleccionada y oficina actual (si hay)
    $eval = null;
    $sqlEval = "SELECT e.id AS eval_id, e.id_oficina_actual
                FROM eva_evaluaciones e
                WHERE e.id_respuesta = ?
                LIMIT 1";
    if ($st = $conexion->prepare($sqlEval)) {
        $st->bind_param('i', $id_respuesta);
        if ($st->execute()) {
            $res = $st->get_result();
            $eval = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
        }
        $st->close();
    }

    if ($eval) {
        $eval_id   = (int)$eval['eval_id'];
        $oficinaId = (int)$eval['id_oficina_actual'];

        if ($accion === 'cotejo') {
            $sqlCal = "SELECT estado, obs_general
                       FROM eva_calificaciones
                       WHERE id_evaluacion=? AND id_oficina=? AND tipo='cotejo'
                       LIMIT 1";
            if ($st = $conexion->prepare($sqlCal)) {
                $st->bind_param('ii', $eval_id, $oficinaId);
                if ($st->execute()) {
                    $res = $st->get_result();
                    if ($res && $res->num_rows) {
                        $r = $res->fetch_assoc();
                        $pre_cotejo_estado = $r['estado'] ?? null;
                        $pre_cotejo_obs    = $r['obs_general'] ?? null;
                    }
                }
                $st->close();
            }
        } elseif ($accion === 'vb') {
            $sqlCal = "SELECT estado
                       FROM eva_calificaciones
                       WHERE id_evaluacion=? AND id_oficina=? AND tipo='vistobueno'
                       LIMIT 1";
            if ($st = $conexion->prepare($sqlCal)) {
                $st->bind_param('ii', $eval_id, $oficinaId);
                if ($st->execute()) {
                    $res = $st->get_result();
                    if ($res && $res->num_rows) {
                        $r = $res->fetch_assoc();
                        $pre_vb_estado = $r['estado'] ?? null;
                    }
                }
                $st->close();
            }
        } elseif ($accion === 'rubrica') {
            // Cabecera
            $cal_id = null;
            $sqlCal = "SELECT id, estado
                       FROM eva_calificaciones
                       WHERE id_evaluacion=? AND id_oficina=? AND tipo='rubrica'
                       LIMIT 1";
            if ($st = $conexion->prepare($sqlCal)) {
                $st->bind_param('ii', $eval_id, $oficinaId);
                if ($st->execute()) {
                    $res = $st->get_result();
                    if ($res && $res->num_rows) {
                        $r = $res->fetch_assoc();
                        $pre_rb_estado = $r['estado'] ?? null;
                        $cal_id        = (int)$r['id'];
                    }
                }
                $st->close();
            }
            // Detalle aspectos
            if ($cal_id) {
                $sqlAsp = "SELECT aspecto, nota, observacion
                           FROM eva_rubrica_aspectos
                           WHERE id_calificacion=?";
                if ($st = $conexion->prepare($sqlAsp)) {
                    $st->bind_param('i', $cal_id);
                    if ($st->execute()) {
                        $res = $st->get_result();
                        // Mapa ENUM -> 1..5
                        $map = [
                          'estructura'       => 1,
                          'contenido'        => 2,
                          'redaccion'        => 3,
                          'calidad_info'     => 4,
                          'propuesta_mejora' => 5,
                        ];
                        while ($res && ($row = $res->fetch_assoc())) {
                            $asp = $row['aspecto'];
                            $idx = isset($map[$asp]) ? (int)$map[$asp] : (ctype_digit((string)$asp) ? (int)$asp : 0);
                            if ($idx>=1 && $idx<=5) {
                                $rb_scores[$idx] = (int)($row['nota'] ?? 0);
                                $rb_obss[$idx]   = (string)($row['observacion'] ?? '');
                            }
                        }
                    }
                    $st->close();
                }
            }
        }
    }
}

// Helper para selected
function sel($v, $cur){ return ($v === $cur) ? ' selected' : ''; }
// Normaliza en_espera -> espera para la UI
$ui_cotejo = $pre_cotejo_estado ? ($pre_cotejo_estado==='en_espera' ? 'espera' : $pre_cotejo_estado) : '';
$ui_vb     = $pre_vb_estado     ? ($pre_vb_estado==='en_espera'     ? 'espera' : $pre_vb_estado)     : '';
$ui_rb     = $pre_rb_estado     ? ($pre_rb_estado==='en_espera'     ? 'espera' : $pre_rb_estado)     : '';
?>
<style>
  /* Solo separación segura entre botones (BS4/BS5) */
  #modalEval .ev-actions .btn + .btn{ margin-left:12px; }

  /* Aseguramos que el contenedor del modal sea el punto de referencia del overlay */
  #contenidoEval{ position: relative; }

  /* Overlay de "Guardando..." (compatible BS4/BS5) */
  #contenidoEval .saving-mask{
    position: absolute; inset: 0;
    background: rgba(255,255,255,.75);
    display: none; /* .show -> display:flex */
    align-items: center; justify-content: center;
    z-index: 50;
  }
  #contenidoEval .saving-mask.show{ display: flex; }
  #contenidoEval .saving-mask .mask-inner{
    text-align: center;
    padding: 12px 16px;
    background: rgba(255,255,255,.9);
    border: 1px solid #e9ecef;
    border-radius: .5rem;
    box-shadow: 0 3px 12px rgba(0,0,0,.08);
  }
  /* Margen utilitario para BS4/BS5 */
  .mr-2{ margin-right:.5rem; } .me-2{ margin-inline-end:.5rem; }
</style>

<div class="container-fluid py-2">
<?php if (!$id_py): ?>
  <div class="alert alert-danger mb-0">No se pudo obtener el proyecto (ID: <?= (int)$id_py ?>) o no tienes acceso.</div>
<?php elseif ($forzar_sin_respuesta || $id_respuesta <= 0): ?>
  <div class="alert alert-warning mb-0">
    No existe informe semestral para el periodo seleccionado<?= $periodo_sel_nombre !== '' ? ' (' . htmlspecialchars($periodo_sel_nombre, ENT_QUOTES, 'UTF-8') . ')' : '' ?>.
    No se puede registrar evaluación sin informe del periodo activo.
  </div>
<?php elseif ($titulo === ''): ?>
  <div class="alert alert-danger mb-0">No se pudo obtener el contexto del proyecto o no tienes acceso.</div>
<?php else: ?>

  <!-- ——— Encabezado compacto: Proyecto / Revisión / Coordinador ——— -->
  <div class="border rounded-3 p-3 mb-3 bg-white">
    <div class="mb-2">
      <strong>Proyecto:</strong>
      <span class="ms-1"><?= htmlspecialchars($titulo) ?></span>
    </div>
    <div class="d-flex flex-column flex-md-row gap-3">
      <div>
        <strong>Revisión de:</strong>
        <span class="ms-1"><?= htmlspecialchars($formulario ?: 'Formulario no identificado') ?></span>
      </div>
      <div class="ms-md-auto">
        <strong>Coordinador:</strong>
        <span class="ms-1"><?= htmlspecialchars($coordinador) ?></span>
      </div>
    </div>
  </div>

  <?php if ($accion === 'cotejo'): ?>
    <!-- ========== COTEJO ========== -->
    <div class="row g-3" id="cjRow">
      <!-- COLUMNA IZQUIERDA (se hace full-width cuando NO es “observado”) -->
      <div id="cjColLeft" class="col-12 col-md-6">
        <!-- Calificación -->
        <div class="card mb-3">
          <div class="card-body">
            <label for="evCalificacion" class="form-label fw-semibold">Calificación</label>
            <select id="evCalificacion" class="form-select form-control">
              <option value=""<?= sel('', $ui_cotejo) ?>>Seleccionar</option>
              <option value="aprobado"<?= sel('aprobado', $ui_cotejo) ?>>✅ Aprobado</option>
              <option value="observado"<?= sel('observado', $ui_cotejo) ?>>⚠️ Observado</option>
              <option value="espera"<?= sel('espera', $ui_cotejo) ?>>⏳ En espera</option>
            </select>
          </div>
        </div>

        <!-- Días + Fecha (se oculta cuando NO es “observado”) -->
        <div class="card" id="cjDiasCard">
          <div class="card-body">
            <div class="row g-3 align-items-start">
              <div class="col-sm-6">
                <label for="evDias" class="form-label fw-semibold">Días para subsanar</label>
                <select id="evDias" class="form-select form-control">
                  <option value="">Seleccionar</option>
                  <option value="1">1 día</option>
                  <option value="2">2 días</option>
                  <!--<option value="3">3 días</option>
                  <option value="4">4 días</option>
                  <option value="5">5 días</option> -->
                </select>
                <div class="form-text">Se calcula con fecha y hora actuales (Lima, Perú). Sin sábados ni domingos.</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Fecha límite</label>
                <div id="evFecha" class="alert alert-primary text-center fw-semibold py-2 mb-0" aria-live="polite">—</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA: Observación (se oculta cuando NO es “observado”) -->
      <div id="cjColObs" class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <label for="evObs" class="form-label fw-semibold">Observación</label>
            <textarea id="evObs" class="form-control" maxlength="3000" rows="6"
              placeholder="Escribe tus observaciones (máx. 3000 caracteres)"><?= htmlspecialchars((string)($pre_cotejo_obs ?? 'No necesita observación')) ?></textarea>
            <div class="form-text"><span id="evCont">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($accion === 'vb'): ?>
    <!-- ========== VISTO BUENO ========== -->
    <div class="card">
      <div class="card-body">
        <label for="vbCalificacion" class="form-label fw-semibold">Calificación</label>
        <select id="vbCalificacion" class="form-select form-control">
          <option value=""<?= sel('', $ui_vb) ?>>Seleccionar</option>
          <option value="aprobado"<?= sel('aprobado', $ui_vb) ?>>✅ Aprobado</option>
          <option value="espera"<?= sel('espera', $ui_vb) ?>>⏳ En espera</option>
        </select>
      </div>
    </div>

  <?php elseif ($accion === 'rubrica'): ?>
    <!-- ========== RÚBRICA ========== -->
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
        <div class="fw-semibold">Puntaje Total: <span id="rbTotal">0</span> / 20</div>
        <div class="fw-semibold">Estado: <span id="rbEstado" class="badge bg-secondary">En espera</span></div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <!-- A1 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 1: Estructura</div>
          <select class="form-select form-control rb-sel" id="rbSel1" data-asp="1">
            <option value="0"<?= sel('0', (string)$rb_scores[1]) ?>>0 - En espera</option>
            <option value="1"<?= sel('1', (string)$rb_scores[1]) ?>>1 - Insuficiente</option>
            <option value="2"<?= sel('2', (string)$rb_scores[1]) ?>>2 - Mejorable</option>
            <option value="3"<?= sel('3', (string)$rb_scores[1]) ?>>3 - Satisfactorio</option>
            <option value="4"<?= sel('4', (string)$rb_scores[1]) ?>>4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox1">
            <label class="form-label fw-semibold" id="rbObsLabel1">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs1" maxlength="3000" rows="4"><?= htmlspecialchars($rb_obss[1]) ?></textarea>
            <div class="form-text"><span id="rbCount1">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A3 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 3: Redacción</div>
          <select class="form-select form-control rb-sel" id="rbSel3" data-asp="3">
            <option value="0"<?= sel('0', (string)$rb_scores[3]) ?>>0 - En espera</option>
            <option value="1"<?= sel('1', (string)$rb_scores[3]) ?>>1 - Insuficiente</option>
            <option value="2"<?= sel('2', (string)$rb_scores[3]) ?>>2 - Mejorable</option>
            <option value="3"<?= sel('3', (string)$rb_scores[3]) ?>>3 - Satisfactorio</option>
            <option value="4"<?= sel('4', (string)$rb_scores[3]) ?>>4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox3">
            <label class="form-label fw-semibold" id="rbObsLabel3">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs3" maxlength="3000" rows="4"><?= htmlspecialchars($rb_obss[3]) ?></textarea>
            <div class="form-text"><span id="rbCount3">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A5 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 5: Propuesta de Mejora</div>
          <select class="form-select form-control rb-sel" id="rbSel5" data-asp="5">
            <option value="0"<?= sel('0', (string)$rb_scores[5]) ?>>0 - En espera</option>
            <option value="1"<?= sel('1', (string)$rb_scores[5]) ?>>1 - Insuficiente</option>
            <option value="2"<?= sel('2', (string)$rb_scores[5]) ?>>2 - Mejorable</option>
            <option value="3"<?= sel('3', (string)$rb_scores[5]) ?>>3 - Satisfactorio</option>
            <option value="4"<?= sel('4', (string)$rb_scores[5]) ?>>4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox5">
            <label class="form-label fw-semibold" id="rbObsLabel5">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs5" maxlength="3000" rows="4"><?= htmlspecialchars($rb_obss[5]) ?></textarea>
            <div class="form-text"><span id="rbCount5">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <!-- A2 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 2: Contenido</div>
          <select class="form-select form-control rb-sel" id="rbSel2" data-asp="2">
            <option value="0"<?= sel('0', (string)$rb_scores[2]) ?>>0 - En espera</option>
            <option value="1"<?= sel('1', (string)$rb_scores[2]) ?>>1 - Insuficiente</option>
            <option value="2"<?= sel('2', (string)$rb_scores[2]) ?>>2 - Mejorable</option>
            <option value="3"<?= sel('3', (string)$rb_scores[2]) ?>>3 - Satisfactorio</option>
            <option value="4"<?= sel('4', (string)$rb_scores[2]) ?>>4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox2">
            <label class="form-label fw-semibold" id="rbObsLabel2">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs2" maxlength="3000" rows="4"><?= htmlspecialchars($rb_obss[2]) ?></textarea>
            <div class="form-text"><span id="rbCount2">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A4 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 4: Calidad de información</div>
          <select class="form-select form-control rb-sel" id="rbSel4" data-asp="4">
            <option value="0"<?= sel('0', (string)$rb_scores[4]) ?>>0 - En espera</option>
            <option value="1"<?= sel('1', (string)$rb_scores[4]) ?>>1 - Insuficiente</option>
            <option value="2"<?= sel('2', (string)$rb_scores[4]) ?>>2 - Mejorable</option>
            <option value="3"<?= sel('3', (string)$rb_scores[4]) ?>>3 - Satisfactorio</option>
            <option value="4"<?= sel('4', (string)$rb_scores[4]) ?>>4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox4">
            <label class="form-label fw-semibold" id="rbObsLabel4">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs4" maxlength="3000" rows="4"><?= htmlspecialchars($rb_obss[4]) ?></textarea>
            <div class="form-text"><span id="rbCount4">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Días / Fecha (solo si Observado) -->
    <div id="rbCorrBox" class="card d-none">
      <div class="card-body">
        <div class="row g-3 align-items-start">
          <div class="col-sm-6">
            <label for="rbDias" class="form-label fw-semibold">Días para subsanar</label>
            <select id="rbDias" class="form-select form-control">
              <option value="">Seleccionar</option>
              <option value="1">1 día</option>
              <option value="2">2 días</option>
              <!-- <option value="3">3 días</option>
              <option value="4">4 días</option>
              <option value="5">5 días</option> -->
            </select>
            <div class="form-text">Se calcula con fecha y hora actuales (Lima, Perú). Sin sábados ni domingos.</div>
          </div>
          <div class="col-sm-6">
            <label class="form-label fw-semibold">Fecha límite</label>
            <div id="rbFecha" class="alert alert-primary text-center fw-semibold py-2 mb-0" aria-live="polite">—</div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="alert alert-secondary">Acción no reconocida.</div>
  <?php endif; ?>

<!-- Footer -->
<div class="d-flex justify-content-center mt-3 ev-actions">
  <button type="button" id="btnGuardarEval" class="btn btn-success">
    Calificar y notificar al correo
  </button>
  <button type="button" id="btnCancelarEval" class="btn btn-secondary ml-2" data-dismiss="modal" data-bs-dismiss="modal">
    Cancelar
  </button>
</div>

<!-- Overlay "Guardando..." dentro de #contenidoEval -->
<div id="evSavingMask" class="saving-mask" aria-hidden="true">
  <div class="mask-inner">
    <div class="spinner-border spinner-border-sm" role="status" aria-label="Cargando"></div>
    <div class="mt-2">Guardando…</div>
  </div>
</div>

  <?php if ($accion === 'cotejo'): ?>
  <script>
  (function(){
    const ZT='America/Lima', DEFAULT_OBS='No necesita observación';
    function nowLimaParts(){
      const fmt=new Intl.DateTimeFormat('en-CA',{timeZone:ZT,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false});
      const p=fmt.formatToParts(new Date()); const g=t=>p.find(x=>x.type===t)?.value||'';
      return {y:+g('year'),mo:+g('month'),d:+g('day'),h:+g('hour'),mi:+g('minute')};
    }
    function daysInMonthUTC(y,m){return new Date(Date.UTC(y,m,0)).getUTCDate();}
    function addBusinessDays(b,n){
      let {y,mo,d,h,mi}=b, rest=parseInt(n,10)||0;
      if(rest<=0) return {y,mo,d,h,mi};
      while(rest>0){
        const dim=daysInMonthUTC(y,mo), rem=dim-d;
        if(rem>=1){ d+=1; } else { d=1; mo++; if(mo>12){mo=1;y++;} }
        const dt=new Date(Date.UTC(y,mo-1,d));
        const w=dt.getUTCDay(); // 0=Dom,6=Sab
        if(w!==0 && w!==6){ rest--; }
      }
      return {y,mo,d,h,mi};
    }
    function fmt(o){return `${String(o.d).padStart(2,'0')}/${String(o.mo).padStart(2,'0')}/${o.y} a las ${String(o.h).padStart(2,'0')}:${String(o.mi).padStart(2,'0')} hrs`}

    const $cal   = document.getElementById('evCalificacion');
    const $dias  = document.getElementById('evDias');
    const $fecha = document.getElementById('evFecha');
    const $obs   = document.getElementById('evObs');
    const $cont  = document.getElementById('evCont');
    const colLeft = document.getElementById('cjColLeft');
    const diasCard = document.getElementById('cjDiasCard');
    const colObs = document.getElementById('cjColObs');

    function setObservedMode(on){
      diasCard.classList.toggle('d-none', !on);
      colObs.classList.toggle('d-none', !on);

      if (on) {
        if (!colLeft.classList.contains('col-md-6')) colLeft.classList.add('col-md-6');
        if ($obs && $obs.value.trim()==='No necesita observación') $obs.value='';
      } else {
        colLeft.classList.remove('col-md-6');
        if ($obs && !$obs.value.trim()) $obs.value='No necesita observación';
        $dias.value=''; $fecha.textContent='—';
      }
      $cont && ($cont.textContent = $obs ? String(($obs.value||'').length) : '0');
    }

    function onCal(){ setObservedMode(($cal.value||'').toLowerCase()==='observado'); }
    function onDias(){
      const v=parseInt($dias.value||'0',10); if(!v){$fecha.textContent='—';return;}
      const dest=addBusinessDays(nowLimaParts(), v);
      $fecha.textContent=fmt(dest);
    }
    function updateCounter(){ if(!$cont)return; $cont.textContent= String(($obs && !$obs.disabled) ? ($obs.value||'').length : 0); }

    // Estado inicial según precarga (el <select> ya llegó con selected)
    onCal();
    updateCounter();

    $cal.addEventListener('change',onCal);
    $dias.addEventListener('change',onDias);
    $obs && $obs.addEventListener('input',updateCounter);
  })();
  </script>
  <?php elseif ($accion === 'rubrica'): ?>
  <script>
  (function(){
    const ZT='America/Lima';
    function nowLimaParts(){const fmt=new Intl.DateTimeFormat('en-CA',{timeZone:ZT,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false});const p=fmt.formatToParts(new Date());const g=t=>p.find(x=>x.type===t)?.value||'';return{y:+g('year'),mo:+g('month'),d:+g('day'),h:+g('hour'),mi:+g('minute')}}
    function daysInMonthUTC(y,m){return new Date(Date.UTC(y,m,0)).getUTCDate();}
    function addBusinessDays(b,n){
      let{y,mo,d,h,mi}=b, rest=parseInt(n,10)||0;
      if(rest<=0) return {y,mo,d,h,mi};
      while(rest>0){
        const dim=daysInMonthUTC(y,mo), rem=dim-d;
        if(rem>=1){ d+=1; } else { d=1; mo++; if(mo>12){mo=1;y++;} }
        const dt=new Date(Date.UTC(y,mo-1,d));
        const w=dt.getUTCDay(); // 0=Dom,6=Sab
        if(w!==0 && w!==6){ rest--; }
      }
      return {y,mo,d,h,mi};
    }
    function fmt(o){return `${String(o.d).padStart(2,'0')}/${String(o.mo).padStart(2,'0')}/${o.y} a las ${String(o.h).padStart(2,'0')}:${String(o.mi).padStart(2,'0')} hrs`}

    const sels=[1,2,3,4,5].map(i=>document.getElementById('rbSel'+i));
    const obsBoxes=[1,2,3,4,5].map(i=>document.getElementById('rbObsBox'+i));
    const obsLabels=[1,2,3,4,5].map(i=>document.getElementById('rbObsLabel'+i));
    const obsAreas=[1,2,3,4,5].map(i=>document.getElementById('rbObs'+i));
    const counts=[1,2,3,4,5].map(i=>document.getElementById('rbCount'+i));
    const totalEl=document.getElementById('rbTotal');
    const estadoEl=document.getElementById('rbEstado');
    const boxCorreccion=document.getElementById('rbCorrBox');
    const diasSel=document.getElementById('rbDias');
    const fechaLbl=document.getElementById('rbFecha');

    function setEstadoBadge(estado){
      let cls='bg-secondary', txt='En espera';
      if(estado==='observado'){cls='bg-warning text-dark';txt='Observado';}
      else if(estado==='aprobado'){cls='bg-success';txt='Aprobado';}
      estadoEl.className='badge '+cls; estadoEl.textContent=txt;
    }

    function updateAspectObs(idx, val, labelText, labelClass){
      const show=(val==='1'||val==='2');
      const box=obsBoxes[idx], lbl=obsLabels[idx];
      if(show){
        box.classList.remove('d-none');
        lbl.textContent=labelText;
        lbl.classList.remove('text-success','text-danger');
        if(labelClass) lbl.classList.add(labelClass);
      }else{
        box.classList.add('d-none');
        counts[idx].textContent=String((obsAreas[idx]?.value||'').length);
        lbl.classList.remove('text-success','text-danger');
      }
    }

    function recalc(){
      let total=0, allZero=true;
      sels.forEach(s=>{const v=parseInt(s.value||'0',10); total+=v; if(v!==0) allZero=false;});
      totalEl.textContent=String(total);

      let estado='observado';
      if(allZero) estado='espera';
      else if(total>=14) estado='aprobado';
      setEstadoBadge(estado);

      if(estado==='observado'){ boxCorreccion.classList.remove('d-none'); }
      else { boxCorreccion.classList.add('d-none'); if(diasSel){ diasSel.value=''; } fechaLbl.textContent='—'; }

      const labelText=(estado==='aprobado')?'Recomendación':'Observación';
      const labelClass=(estado==='aprobado')?'text-success':'text-danger';
      sels.forEach((s,idx)=>updateAspectObs(idx,s.value,labelText,labelClass));
    }

    // Estado inicial (selects ya llegan “selected” según DB)
    recalc();
    // contadores de texto
    obsAreas.forEach((ta,idx)=>{ counts[idx].textContent=String((ta.value||'').length); });

    // Listeners
    sels.forEach(s=> s.addEventListener('change', recalc));
    diasSel && diasSel.addEventListener('change', function(){
      const v=parseInt(this.value||'0',10);
      if(!v){ fechaLbl.textContent='—'; return; }
      const dest=addBusinessDays(nowLimaParts(), v);
      fechaLbl.textContent = fmt(dest);
    });
  })();
  </script>
  <?php endif; ?>

<?php endif; ?>
<?php
  // Código/Oficina según rol del usuario autenticado
  $ofCode = function_exists('oficinaCodigoPorRol') ? oficinaCodigoPorRol((int)$id_rol) : '';
?>
<script>
(function(){
  const API = '/sistema_web/informe_semestral/api/save_evaluacion.php';
  const ID_PY   = <?= (int)$id_py ?>;
  const ID_RESP = <?= (int)$id_respuesta ?>;
  const SEMESTRAL = <?= (int)$id_periodo ?>;
  const ACCION  = '<?= htmlspecialchars($accion, ENT_QUOTES) ?>';
  const OFICINA = '<?= htmlspecialchars($ofCode ?? '', ENT_QUOTES) ?>';

  const $btnGuardar = document.getElementById('btnGuardarEval');
  const $btnCancelar = document.getElementById('btnCancelarEval');
  const $mask = document.getElementById('evSavingMask');
  const $contenedor = document.getElementById('contenidoEval');

  if (!$btnGuardar || !ID_PY || !ID_RESP || !ACCION || !OFICINA) return;

  /* ========= Helpers de red ========= */
  function post(data){
    if (window.jQuery) {
      return jQuery.post(API, data).then(r => (typeof r==='string'? JSON.parse(r): r));
    }
    return fetch(API, {
      method:'POST',
      body:(()=>{ const fd=new FormData(); Object.entries(data).forEach(([k,v])=>fd.append(k,v)); return fd; })()
    }).then(r=>r.json());
  }

  /* ========= UI: overlay + bloquear controles ========= */
  function setBusy(isBusy){
    try{
      // Overlay
      if ($mask) $mask.classList.toggle('show', !!isBusy);

      // Botón Guardar: deshabilitar y cambiar texto
      if ($btnGuardar){
        if (isBusy){
          if (!$btnGuardar.dataset._label) $btnGuardar.dataset._label = $btnGuardar.innerHTML;
          $btnGuardar.innerHTML = '<span class="spinner-border spinner-border-sm mr-2 me-2" role="status" aria-hidden="true"></span>Guardando…';
          $btnGuardar.disabled = true;
          $btnGuardar.setAttribute('aria-busy','true');
        }else{
          $btnGuardar.innerHTML = $btnGuardar.dataset._label || 'Calificar y notificar al correo';
          $btnGuardar.disabled = false;
          $btnGuardar.removeAttribute('aria-busy');
        }
      }

      // Botón Cancelar
      if ($btnCancelar) $btnCancelar.disabled = !!isBusy;

      // Deshabilitar inputs dentro del contenido (sin romper estados previos)
      if ($contenedor){
        const controls = $contenedor.querySelectorAll('input, select, textarea, button');
        controls.forEach(el=>{
          // no tocar el propio overlay
          if (el === $btnGuardar || el === $btnCancelar) return;
          if (isBusy){
            // guardamos estado previo en data attr
            if (!el.hasAttribute('data-prev-disabled')){
              el.setAttribute('data-prev-disabled', el.disabled ? '1' : '0');
            }
            el.disabled = true;
          }else{
            // restaurar estado previo
            if (el.hasAttribute('data-prev-disabled')){
              const was = el.getAttribute('data-prev-disabled') === '1';
              el.disabled = was;
              el.removeAttribute('data-prev-disabled');
            }else{
              el.disabled = false;
            }
          }
        });
      }
    }catch(_){}
  }

  function alertOK(msg){
    const c = $contenedor;
    if (c) c.innerHTML = '<div class="alert alert-success">'+ (msg||'Guardado') +'</div>';
    // Quitamos modo busy antes de cerrar/recargar (por si tarda la redirección)
    setBusy(false);
    setTimeout(()=>{
      if (window.jQuery && window.jQuery('#modalEval').modal) {
        jQuery('#modalEval').modal('hide');
      }
      location.reload();
    }, 900);
  }
  function alertERR(msg){
    const c = $contenedor;
    if (c) c.insertAdjacentHTML('afterbegin','<div class="alert alert-danger">'+ (msg||'Error') +'</div>');
    setBusy(false);
  }

  /* ========= Click Guardar ========= */
  $btnGuardar.addEventListener('click', async function(){
    setBusy(true);
    try{
      let payload = {
        id_py: ID_PY,
        id_respuesta: ID_RESP,
        semestral: SEMESTRAL,
        accion: ACCION,
        oficina: OFICINA
      };

      if (ACCION === 'cotejo') {
        const cal   = document.getElementById('evCalificacion');
        const obs   = document.getElementById('evObs');
        const dias  = document.getElementById('evDias');
        const v = (cal && cal.value) ? String(cal.value).toLowerCase() : 'espera';
        payload.estado = (v==='aprobado'||v==='observado'||v==='en_espera'||v==='espera')
          ? (v==='espera' ? 'en_espera' : v)
          : 'en_espera';
        if (payload.estado === 'observado') {
          const ds = (dias && parseInt(dias.value||'0',10)) || 0;
          if (!ds) { setBusy(false); return alertERR('Debes indicar días para subsanar'); }
          payload.dias_subsanacion = ds;
          payload.obs = obs ? String(obs.value||'').slice(0,3000) : '';
        } else {
          payload.dias_subsanacion = 0;
          payload.obs = obs ? String(obs.value||'').slice(0,3000) : '';
        }
      }
      else if (ACCION === 'vb') {
        const vb = document.getElementById('vbCalificacion');
        const v = (vb && vb.value) ? String(vb.value).toLowerCase() : 'espera';
        payload.estado = (v==='aprobado') ? 'aprobado' : 'en_espera';
      }
      else if (ACCION === 'rubrica') {
        let total = 0, allZero = true;
        for (let i=1;i<=5;i++){
          const s = document.getElementById('rbSel'+i);
          const o = document.getElementById('rbObs'+i);
          const val = s ? parseInt(s.value||'0',10) : 0;
          payload['a'+i] = isNaN(val)?0:val;
          total += payload['a'+i];
          if (payload['a'+i] !== 0) allZero = false;
          payload['o'+i] = o ? String(o.value||'').slice(0,3000) : '';
        }
        let estado = 'observado';
        if (allZero) estado = 'en_espera';
        else if (total >= 14) estado = 'aprobado';
        payload.estado = estado;

        const ds = document.getElementById('rbDias');
        payload.dias_subsanacion = (estado==='observado') ? ((ds && parseInt(ds.value||'0',10)) || 0) : 0;
        if (estado==='observado' && !payload.dias_subsanacion) { setBusy(false); return alertERR('Debes indicar días para subsanar'); }
      }

      const r = await post(payload);
      if (r && r.ok) return alertOK('✅ Evaluación guardada correctamente');
      return alertERR(r && r.error ? r.error : 'No se pudo guardar');

    }catch(err){
      return alertERR('Excepción: '+ (err && err.message ? err.message : err));
    }
  });
})();
</script>


