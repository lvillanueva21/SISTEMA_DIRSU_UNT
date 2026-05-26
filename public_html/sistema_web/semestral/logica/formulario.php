<?php
// presentacion/logica/formulario.php — versión con flujo de estado desacoplado del progreso

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
date_default_timezone_set('America/Lima');

// --------- Datos base del flujo ----------
$respuestaId = (int)($sm_info['respuesta_id'] ?? 0);
$formulario  = $sm_info['form_activo'] ?? null;
$periodoNom  = $sm_info['periodo_activo']['nombre'] ?? '-';
// --------- Proyecto desde funciones.php ----------
$proyTitulo = (string)($sm_info['titulo'] ?? '');
$proyIniTxt = (string)($sm_info['inicio'] ?? '');
$proyFinTxt = (string)($sm_info['fin'] ?? '');

if ($respuestaId <= 0 || !$formulario) {
    echo "<div class='alert alert-danger'>No se pudo cargar el formulario actual.</div>";
    return;
}

// --------- Cargar ítems activos y ordenados ----------
$st = $conexion->prepare("
  SELECT fi.id_item,
         fi.orden,
         i.nombre,
         i.tipo,
         i.ejemplo,
         i.img_ruta,
         i.pdf_ruta,
         i.link,
         i.formato,
         i.video
  FROM sm_formulario_items fi
  JOIN sm_items i ON i.id = fi.id_item
  WHERE fi.id_formulario=? AND fi.activo=1
  ORDER BY fi.orden ASC
");
$st->bind_param("i", $formulario['id']);
$st->execute();
$rs = $st->get_result();
$items = $rs->fetch_all(MYSQLI_ASSOC);
$st->close();

$totalItems = count($items);
if ($totalItems === 0) {
    echo "<div class='alert alert-warning'>Este formulario no tiene ítems activos.</div>";
    return;
}

// --------- Cargar respuestas existentes de esta cabecera ----------
$st2 = $conexion->prepare("
  SELECT id_item, tipo,
         val_varchar, val_longtext, val_tinyint, val_int, val_boolean,
         val_datetime, val_date, val_decimal, archivo_url
  FROM sm_respuesta_items
  WHERE id_respuesta=?
");
$st2->bind_param("i", $respuestaId);
$st2->execute();
$rs2 = $st2->get_result();

$respuestas = []; // id_item => row
while ($row = $rs2->fetch_assoc()) {
    $respuestas[(int)$row['id_item']] = $row;
}
$st2->close();

// --------- Estado actual de la cabecera (sm_respuestas) ----------
$estadoRespuesta = 0;
$idProyecto      = 0;

$stER = $conexion->prepare("SELECT id_py, estado FROM sm_respuestas WHERE id=? LIMIT 1");
$stER->bind_param("i", $respuestaId);
$stER->execute();
$rsER = $stER->get_result();

if ($rowER = $rsER->fetch_assoc()) {
  $estadoRespuesta = (int)$rowER['estado'];
  $idProyecto      = (int)$rowER['id_py'];
}

$stER->close();

if ($idProyecto <= 0 && isset($_SESSION['id_py'])) {
  $idProyecto = (int)$_SESSION['id_py'];
}

// --------- Resumen de semestres para modal ----------
$semestreObjetivoId = isset($sm_info['semestre_objetivo_id']) ? (int)$sm_info['semestre_objetivo_id'] : 0;
$periodoActivoNombre = isset($sm_info['periodo_activo']['nombre']) ? trim((string)$sm_info['periodo_activo']['nombre']) : '';
$periodoActivoAnio = isset($sm_info['periodo_activo']['anio']) ? (int)$sm_info['periodo_activo']['anio'] : 0;
$periodoActivoCodigo = isset($sm_info['periodo_activo']['periodo']) ? strtoupper(trim((string)$sm_info['periodo_activo']['periodo'])) : '';
$formularioActivoNombre = isset($sm_info['form_activo']['nombre']) ? trim((string)$sm_info['form_activo']['nombre']) : '';
$ventanaAperturaActual = isset($sm_info['apertura']) ? trim((string)$sm_info['apertura']) : '';
$ventanaCierreActual = isset($sm_info['cierre']) ? trim((string)$sm_info['cierre']) : '';
$cronogramaTipoActual = isset($sm_info['cronograma_tipo']) ? (int)$sm_info['cronograma_tipo'] : 0;
$tipoSemestreEsperadoActual = ($cronogramaTipoActual === 1) ? 'presentacion' : 'semestral';

$correspondeActualTexto = 'Informe semestral';
if ($cronogramaTipoActual === 1) {
  $correspondeActualTexto = 'Presentación de proyecto';
} elseif ($cronogramaTipoActual === 2) {
  $correspondeActualTexto = 'Informe semestral';
}

if (
  $semestreObjetivoId <= 0
  && $idProyecto > 0
  && $periodoActivoAnio > 0
  && ($periodoActivoCodigo === 'I' || $periodoActivoCodigo === 'II')
) {
  $stSemObj = $conexion->prepare("
    SELECT id
    FROM sm_proyecto_semestres
    WHERE id_py = ? AND anio = ? AND periodo = ? AND tipo = ? AND vigente = 1
    LIMIT 1
  ");
  if ($stSemObj) {
    $stSemObj->bind_param("iiss", $idProyecto, $periodoActivoAnio, $periodoActivoCodigo, $tipoSemestreEsperadoActual);
    $stSemObj->execute();
    $stSemObj->bind_result($semIdFound);
    if ($stSemObj->fetch()) {
      $semestreObjetivoId = (int)$semIdFound;
    }
    $stSemObj->close();
  }
}

$semestresResumenModal = [];
$haySemestreActualModal = false;
$esInformeFinalActualFormulario = false;
if ($idProyecto > 0) {
  $stSem = $conexion->prepare("
    SELECT id, anio, periodo, tipo, final, numero, fecha_inicio, fecha_fin, titulo
    FROM sm_proyecto_semestres
    WHERE id_py = ? AND vigente = 1
    ORDER BY
      anio ASC,
      FIELD(periodo, 'I', 'II') ASC,
      CASE tipo WHEN 'presentacion' THEN 0 ELSE 1 END ASC
  ");

  if ($stSem) {
    $stSem->bind_param("i", $idProyecto);
    $stSem->execute();
    $rsSem = $stSem->get_result();

    $mapSem = [];
    while ($rowSem = $rsSem->fetch_assoc()) {
      $idSem = isset($rowSem['id']) ? (int)$rowSem['id'] : 0;
      $anio = isset($rowSem['anio']) ? (int)$rowSem['anio'] : 0;
      $periodo = isset($rowSem['periodo']) ? strtoupper(trim((string)$rowSem['periodo'])) : '';
      if ($anio <= 0 || ($periodo !== 'I' && $periodo !== 'II')) {
        continue;
      }

      $clave = $anio . '-' . $periodo;
      if (!isset($mapSem[$clave])) {
        $mapSem[$clave] = [
          'semestre' => $clave,
          'entregas' => [],
          'es_actual' => false,
          'periodo_actual' => '',
          'corresponde' => '',
          'formulario' => '',
          'ventana_apertura' => '',
          'ventana_cierre' => ''
        ];
      }

      $tipo = isset($rowSem['tipo']) ? trim((string)$rowSem['tipo']) : '';
      $esFinal = isset($rowSem['final']) ? (int)$rowSem['final'] : 0;
      $entrega = 'Entrega de semestre';

      if ($tipo === 'presentacion') {
        $entrega = 'Presentación de proyecto';
      } elseif ($tipo === 'semestral' && $esFinal === 1) {
        $entrega = 'Informe final';
      } elseif ($tipo === 'semestral') {
        $entrega = 'Informe semestral';
      }

      if (!in_array($entrega, $mapSem[$clave]['entregas'], true)) {
        $mapSem[$clave]['entregas'][] = $entrega;
      }

      $esSemestreActual = false;
      if ($semestreObjetivoId > 0 && $idSem === $semestreObjetivoId) {
        $esSemestreActual = true;
      } elseif ($semestreObjetivoId <= 0 && $periodoActivoAnio > 0 && $periodoActivoCodigo !== '') {
        if (
          $anio === $periodoActivoAnio
          && $periodo === $periodoActivoCodigo
          && $tipo === $tipoSemestreEsperadoActual
        ) {
          $esSemestreActual = true;
        }
      }

      if ($esSemestreActual) {
        $correspondeActualSemestre = 'Informe semestral';
        if ($tipo === 'presentacion') {
          $correspondeActualSemestre = 'Presentación de proyecto';
        } elseif ($tipo === 'semestral' && $esFinal === 1) {
          $correspondeActualSemestre = 'Informe final';
          $esInformeFinalActualFormulario = true;
        } elseif ($tipo === 'semestral') {
          $correspondeActualSemestre = 'Informe semestral';
        }

        $mapSem[$clave]['es_actual'] = true;
        $mapSem[$clave]['periodo_actual'] = ($periodoActivoNombre !== '') ? $periodoActivoNombre : $clave;
        $mapSem[$clave]['corresponde'] = $correspondeActualSemestre;
        $mapSem[$clave]['formulario'] = $formularioActivoNombre;
        $mapSem[$clave]['ventana_apertura'] = $ventanaAperturaActual;
        $mapSem[$clave]['ventana_cierre'] = $ventanaCierreActual;
        $haySemestreActualModal = true;
      }
    }

    foreach ($mapSem as $semData) {
      $semestresResumenModal[] = $semData;
    }

    if (!$haySemestreActualModal && $periodoActivoAnio > 0 && $periodoActivoCodigo !== '') {
      $claveActualFallback = $periodoActivoAnio . '-' . $periodoActivoCodigo;
      $idxSem = 0;
      for ($idxSem = 0; $idxSem < count($semestresResumenModal); $idxSem++) {
        $semItem = isset($semestresResumenModal[$idxSem]) ? $semestresResumenModal[$idxSem] : [];
        if (!is_array($semItem)) {
          continue;
        }
        $entregasSem = (isset($semItem['entregas']) && is_array($semItem['entregas'])) ? $semItem['entregas'] : [];
        $coincideTipoFallback = false;
        if ($tipoSemestreEsperadoActual === 'presentacion') {
          $coincideTipoFallback = in_array('Presentación de proyecto', $entregasSem, true);
        } else {
          $coincideTipoFallback = in_array('Informe semestral', $entregasSem, true) || in_array('Informe final', $entregasSem, true);
        }

        if (
          isset($semItem['semestre'])
          && (string)$semItem['semestre'] === $claveActualFallback
          && $coincideTipoFallback
        ) {
          $semestresResumenModal[$idxSem]['es_actual'] = true;
          $semestresResumenModal[$idxSem]['periodo_actual'] = ($periodoActivoNombre !== '') ? $periodoActivoNombre : $claveActualFallback;
          $semestresResumenModal[$idxSem]['corresponde'] = $correspondeActualTexto;
          $semestresResumenModal[$idxSem]['formulario'] = $formularioActivoNombre;
          $semestresResumenModal[$idxSem]['ventana_apertura'] = $ventanaAperturaActual;
          $semestresResumenModal[$idxSem]['ventana_cierre'] = $ventanaCierreActual;
          $haySemestreActualModal = true;
          break;
        }
      }
    }

    $stSem->close();
  }
}

if (!empty($semestresResumenModal)) {
  $idxSemAct = 0;
  for ($idxSemAct = 0; $idxSemAct < count($semestresResumenModal); $idxSemAct++) {
    $semAct = isset($semestresResumenModal[$idxSemAct]) ? $semestresResumenModal[$idxSemAct] : [];
    if (!is_array($semAct) || empty($semAct['es_actual'])) {
      continue;
    }

    $entregasAct = (isset($semAct['entregas']) && is_array($semAct['entregas'])) ? $semAct['entregas'] : [];
    if (in_array('Informe final', $entregasAct, true)) {
      $esInformeFinalActualFormulario = true;
      if (!isset($semestresResumenModal[$idxSemAct]['corresponde']) || trim((string)$semestresResumenModal[$idxSemAct]['corresponde']) === '' || trim((string)$semestresResumenModal[$idxSemAct]['corresponde']) === 'Informe semestral') {
        $semestresResumenModal[$idxSemAct]['corresponde'] = 'Informe final';
      }
    }
  }
}

// --------- Estado de la RUTA eva_* (si existe) ----------
$ruta = [
  'situacion'         => null,  // 'en_oficina' | 'aprobado'
  'id_oficina_actual' => null,
  'oficina_cod'       => null,
  'oficina_nom'       => null,
  'instancia_estado'  => null,  // 'en_espera' | 'observado' | 'aprobado'
  'cj_estado'         => null,  // estado del cotejo en la oficina actual
  'rb_estado'         => null,  // estado de la rúbrica en la oficina actual
];
$stR = $conexion->prepare("
  SELECT
    e.id                AS id_eval,
    e.situacion,
    e.id_oficina_actual,
    o.codigo            AS oficina_cod,
    o.nombre            AS oficina_nom,
    oi.estado           AS instancia_estado,
    oi.ultima_observacion_at AS obs_dt,
    cj.estado           AS cj_estado,
    rb.estado           AS rb_estado
  FROM eva_evaluaciones e
  LEFT JOIN eva_oficinas o
         ON o.id = e.id_oficina_actual
  LEFT JOIN (
     SELECT id_evaluacion, id_oficina, MAX(id) AS last_id
     FROM eva_oficina_instancias
     GROUP BY id_evaluacion, id_oficina
  ) lastoi
    ON lastoi.id_evaluacion = e.id
   AND lastoi.id_oficina    = e.id_oficina_actual
  LEFT JOIN eva_oficina_instancias oi
    ON oi.id = lastoi.last_id
  LEFT JOIN eva_calificaciones cj
    ON cj.id_evaluacion = e.id AND cj.id_oficina = e.id_oficina_actual AND cj.tipo='cotejo'
  LEFT JOIN eva_calificaciones rb
    ON rb.id_evaluacion = e.id AND rb.id_oficina = e.id_oficina_actual AND rb.tipo='rubrica'
  WHERE e.id_respuesta = ?
  LIMIT 1
");

$stR->bind_param("i", $respuestaId);
$stR->execute();
if ($rowR = $stR->get_result()->fetch_assoc()) {
  foreach ($ruta as $k => $_) { $ruta[$k] = $rowR[$k] ?? null; }
}
$stR->close();

$enOficina         = !empty($ruta['id_oficina_actual']);
$observadoOficina  = ( ($ruta['cj_estado'] ?? '') === 'observado' ) || ( ($ruta['rb_estado'] ?? '') === 'observado' );
$aprobacionTotal   = ($ruta['situacion'] ?? '') === 'aprobado' || $estadoRespuesta === 2;

// ¿Cuántos ítems se guardaron después de la observación de la oficina actual?
$cambiosDesdeObs = null; // null = no se pudo calcular (compatibilidad con datos antiguos)
if ($enOficina && $observadoOficina && !empty($ruta['obs_dt'])) {
  $stCH = $conexion->prepare("SELECT COUNT(*) c FROM sm_respuesta_items WHERE id_respuesta=? AND actualizado_at > ?");
  $stCH->bind_param("is", $respuestaId, $ruta['obs_dt']);
  $stCH->execute();
  $rowCH = $stCH->get_result()->fetch_assoc();
  $stCH->close();
  $cambiosDesdeObs = isset($rowCH['c']) ? (int)$rowCH['c'] : 0;
}


// --------- Reglas de edición (NUEVAS) ----------
// - Borrador (0): editable
// - En revisión (1) + OBSERVADO en oficina: editable
// - Aprobado total: no editable
$editable = false;
if ($aprobacionTotal) {
  $editable = false;
} elseif ($estadoRespuesta === 0) {
  $editable = true;
} elseif ($estadoRespuesta === 1 && $enOficina && $observadoOficina) {
  $editable = true; // observado en oficina actual
}

// --------- Reglas de botones ----------
// Compleción de ítems
if (!isset($completados)) {
  $completados = 0;
}
$todoCompleto   = ($completados === $totalItems);
$faltan         = max(0, $totalItems - $completados);

// Acción principal y label según estado real
$btnAccion = null;  // 'solicitar' | 'anular' | 'subsanar' | null
$btnLabel  = '';
$btnClase  = 'btn-secondary';
$btnTitle  = '';
$btnDisabled = false;

if ($aprobacionTotal) {
  $btnAccion = null;
  $btnLabel  = 'Aprobado (solo lectura)';
  $btnClase  = 'btn-outline-secondary';
  $btnDisabled = true;
  $btnTitle  = 'El informe ya está aprobado.';
} else {
  if (!$enOficina) {
    // Fuera de oficinas
    if (in_array($estadoRespuesta, [0,3], true)) {
      $btnAccion  = 'solicitar';
      $btnLabel   = 'Solicitar Revisión de Informe';
      $btnClase   = 'btn-primary';
      $btnDisabled= !$todoCompleto;
      $btnTitle   = $todoCompleto ? 'Enviar para revisión' : 'Completa todos los ítems para poder solicitar la revisión.';
    } elseif ($estadoRespuesta === 1) {
      $btnAccion  = 'anular';
      $btnLabel   = 'Anular solicitud de revisión';
      $btnClase   = 'btn-outline-danger';
      $btnDisabled= false; // solo porque NO está en oficina
      $btnTitle   = 'Volver a borrador para editar.';
    }
  } else {
    // En una oficina
    if ($observadoOficina) {
  $btnAccion  = 'subsanar';
  $btnLabel   = 'Enviar Subsanación';
  $btnClase   = 'btn-warning';

  // Regla nueva: habilitar si hubo AL MENOS UN cambio después de la observación.
  // Si no hay timestamp de observación (datos antiguos), permitimos enviar.
  $hayCambios = ($cambiosDesdeObs === null) ? true : ($cambiosDesdeObs > 0);

  $btnDisabled = !$hayCambios;
  $btnTitle    = $hayCambios
                  ? 'Enviar cambios para que la oficina revise de nuevo.'
                  : 'Realiza al menos un cambio después de la observación para habilitar el envío.';
}
 else {
      // en_espera / aprobado en la oficina actual → sin anulación, sin solicitar
      $btnAccion  = null;
      $btnLabel   = 'En revisión en ' . ($ruta['oficina_nom'] ?? 'oficina');
      $btnClase   = 'btn-outline-secondary';
      $btnDisabled= true;
      $btnTitle   = 'Esperando evaluación de la oficina.';
    }
  }
}

// --------- Helpers ----------
function ri_val_esta_lleno(array $row, string $tipo): bool {
    switch ($tipo) {
        case 'varchar':       return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'longtext':      return isset($row['val_longtext']) && trim((string)$row['val_longtext']) !== '';
        case 'tinyint':       return $row['val_tinyint'] !== null;
        case 'int':           return $row['val_int'] !== null;
        case 'boolean':       return $row['val_boolean'] !== null;
        case 'datetime':      return !empty($row['val_datetime']);
        case 'date':          return !empty($row['val_date']);
        case 'decimal':       return $row['val_decimal'] !== null;
        // nuevos:
        case 'programa_ods':  return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'ods':           return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'pdf':
        case 'excel':
        case 'word':          return isset($row['archivo_url']) && trim((string)$row['archivo_url']) !== '';
        default:              return false;
    }
}
function pillClass($estado) {
    return match ($estado) {
        'done'   => 'btn-success',
        'active' => 'btn-primary',
        'next'   => 'btn-outline-primary',
        default  => 'btn-secondary'
    };
}
function val($arr, $key) { return isset($arr[$key]) ? $arr[$key] : ''; }

/** Normaliza rutas internas a formato portable (relativas a /semestral/index.php). */
function public_url(string $p): string {
    $p = trim($p);
    if ($p === '') return '';
    if (preg_match('~^https?://~i', $p)) return $p;

    $p = str_replace('\\', '/', $p);
    if (preg_match('~^\\./|^\\.\\./~', $p)) return $p;

    if ($p[0] === '/') $p = ltrim($p, '/');

    // Si viene con prefijo de despliegue (p.ej. rsu/sistema_web/...), recorta hasta sistema_web/.
    $pos = stripos($p, 'sistema_web/');
    if ($pos !== false) $p = substr($p, $pos + strlen('sistema_web/'));

    return '../' . ltrim($p, '/');
}

/** === Helper para renderizar los botones de recursos del ítem actual === */
function render_help_buttons(array $itemActual): string {
    $img = trim((string)($itemActual['img_ruta'] ?? ''));
    $pdf = trim((string)($itemActual['pdf_ruta'] ?? ''));
    $lnk = trim((string)($itemActual['link'] ?? ''));
    $fmt = trim((string)($itemActual['formato'] ?? ''));
    $vid = trim((string)($itemActual['video'] ?? ''));

    $imgUrl = $img !== '' ? public_url($img) : '';
    $pdfUrl = $pdf !== '' ? public_url($pdf) : '';
    $fmtUrl = $fmt !== '' ? public_url($fmt) : '';

    if ($imgUrl === '' && $pdfUrl === '' && $lnk === '' && $fmtUrl === '' && $vid === '') {
        return '';
    }

    ob_start(); ?>
    <div class="mb-2">
        <?php if ($imgUrl !== ''): ?>
            <button class="btn btn-outline-secondary btn-sm mr-1" type="button"
                    onclick="showImg('<?= htmlspecialchars($imgUrl, ENT_QUOTES) ?>')">Imagen</button>
        <?php endif; ?>

        <?php if ($pdfUrl !== ''): ?>
            <a class="btn btn-outline-secondary btn-sm mr-1" target="_blank" href="<?= htmlspecialchars($pdfUrl) ?>">Ver PDF</a>
        <?php endif; ?>

        <?php if ($lnk !== ''):
            $href = (preg_match('~^https?://~i', $lnk)) ? $lnk : ('https://' . $lnk);
        ?>
            <a class="btn btn-outline-secondary btn-sm mr-1" target="_blank" href="<?= htmlspecialchars($href) ?>">Recurso Web</a>
        <?php endif; ?>

        <?php if ($fmtUrl !== ''): ?>
            <a class="btn btn-outline-secondary btn-sm mr-1" download target="_blank" href="<?= htmlspecialchars($fmtUrl) ?>">Descargar formato</a>
        <?php endif; ?>

        <?php if ($vid !== ''):
            $data = htmlspecialchars($vid, ENT_QUOTES, 'UTF-8');
        ?>
            <button class="btn btn-outline-secondary btn-sm mr-1 video-btn" type="button" data-video="<?= $data ?>">Tutorial</button>
        <?php endif; ?>
    </div>
    <?php
    return (string)ob_get_clean();
}

// --------- Índice del primer incompleto y progreso ----------
$completados = 0;
$primerIncompletoIdx = 1; // 1-based
foreach ($items as $idx0 => $it) {
    $idItem = (int)$it['id_item'];
    $tipo   = $it['tipo'];
    $tiene  = isset($respuestas[$idItem]) ? ri_val_esta_lleno($respuestas[$idItem], $tipo) : false;
    if ($tiene) $completados++;
    if (!$tiene && $primerIncompletoIdx === 1) {
        $primerIncompletoIdx = $idx0 + 1;
        break;
    }
}
if ($completados === $totalItems) $primerIncompletoIdx = $totalItems;

// --------- Elegir el ítem actual (1-based), con bloqueo de salto ----------
$requested = isset($_GET['item']) ? (int)$_GET['item'] : $primerIncompletoIdx;
if ($requested < 1) $requested = 1;
if ($requested > $totalItems) $requested = $totalItems;

$maxPermitido = max(1, $primerIncompletoIdx);
if ($requested > $maxPermitido) {
    $qs = $_GET;
    $qs['item'] = $maxPermitido;
    $url = strtok($_SERVER["REQUEST_URI"], '?') . '?' . http_build_query($qs);
    header("Location: ".$url);
    exit;
}

$itemActualIdx = $requested;
$itemActual    = $items[$itemActualIdx - 1];

// --------- Cálculo de progreso ----------
$porcentaje = ($totalItems > 0) ? round(($completados / $totalItems) * 100) : 0;

// --------- Ajuste de bloqueo del botón principal ----------
// Importante: $completados se calcula recién en este bloque.
// Recalcular aquí evita bloquear "Solicitar Revisión" por un valor no inicializado.
$todoCompleto = ($completados === $totalItems);
$faltan       = max(0, $totalItems - $completados);
if ($btnAccion === 'solicitar') {
    $btnDisabled = !$todoCompleto;
    $btnTitle    = $todoCompleto
        ? 'Enviar para revisión'
        : 'Completa todos los ítems para poder solicitar la revisión.';
}

// aqui se borró el segundo

// --------- Valores actuales del ítem ----------
$valorExistente = $respuestas[(int)$itemActual['id_item']] ?? null;

// --------- Catálogos nuevos ----------

// ODS libres
$odsLibres = [];
$stO = $conexion->prepare("
  SELECT o.id, o.nombre
  FROM ods o
  LEFT JOIN programa_ods po ON po.ods_id = o.id
  WHERE po.ods_id IS NULL
  ORDER BY o.id
");
$stO->execute();
$resO = $stO->get_result();
while ($row = $resO->fetch_assoc()) {
    $odsLibres[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre']];
}
$stO->close();

// PROGRAMAS
$programas = [];
$stP = $conexion->prepare("SELECT id, nombre FROM programas WHERE activo=1 ORDER BY nombre");
$stP->execute();
$resP = $stP->get_result();
while ($row = $resP->fetch_assoc()) {
    $programas[] = ['id' => (int)$row['id'], 'nombre' => $row['nombre']];
}
$stP->close();

// Mapa programa → ODS
$progOdsMap = []; // programa_id => "ODS 1, ODS 2"
$stM = $conexion->prepare("
  SELECT po.programa_id, GROUP_CONCAT(o.nombre ORDER BY o.id SEPARATOR ', ') AS ods
  FROM programa_ods po
  JOIN ods o ON o.id = po.ods_id
  GROUP BY po.programa_id
");
$stM->execute();
$resM = $stM->get_result();
while ($row = $resM->fetch_assoc()) {
    $progOdsMap[(int)$row['programa_id']] = $row['ods'];
}
$stM->close();

$fechaInicioModal = trim((string)$proyIniTxt);
$fechaFinModal = trim((string)$proyFinTxt);
$fechaInicioFaltante = ($fechaInicioModal === '' || $fechaInicioModal === '-' || stripos($fechaInicioModal, 'No se') === 0);
$fechaFinFaltante = ($fechaFinModal === '' || $fechaFinModal === '-' || stripos($fechaFinModal, 'No se') === 0);
?>
<div id="semestralShell" class="container-fluid d-flex flex-column p-0 rsu-shell">
<!-- Div Superior (dos columnas, compacto, degradado verde Bootstrap) -->
<style>
  /* Estilos del header */
  .rsu-header{
    background: linear-gradient(100deg, #218838 0%, #28a745 60%, #34ce57 100%);
    color:#fff;
    border:0;
    border-radius:.5rem;
    box-shadow: inset 0 1px 2px rgba(0,0,0,.05), 0 2px 6px rgba(0,0,0,.08);
  }
  .rsu-header--final{
    background: linear-gradient(100deg, #1f2937 0%, #111827 62%, #000000 100%);
  }
  .rsu-header .text-muted { color: rgba(255,255,255,.92) !important; }
  /* Chips (Apertura / Cierre) compactos */
  .rsu-chip{
    display:inline-flex; align-items:center;
    padding:.15rem .4rem;
    border-radius:999px;
    background:rgba(255,255,255,.92);
    border:1px solid rgba(255,255,255,.6);
    font-size:.75rem;
    margin-right:.35rem; margin-bottom:.25rem;
    line-height:1.1; color:#123327;
  }
  .rsu-chip i{margin-right:.25rem; opacity:.9}
  .rsu-chip .time{
    font-weight:600; padding:.05rem .3rem;
    border-radius:.25rem; margin-left:.25rem;
    font-size:.7rem;
  }
  .rsu-chip--open  .time{background:rgba(25,135,84,.15); color:#0b3a24}
  .rsu-chip--close .time{background:rgba(220,53,69,.12); color:#7a0f17}

  /* Botonera compacta */
  .rsu-btn-tray{
    display:inline-flex; gap:.4rem; flex-wrap:wrap; white-space:normal;
  }
  .rsu-btn-tray .btn{
    font-size:.75rem;
    padding:.2rem .45rem;
    line-height:1.1;
    border-radius:.25rem;
  }
  .rsu-btn-tray .btn i{
    margin-right:.25rem; font-size:.8em;
  }
  /* Título del proyecto */
  .rsu-proy-title{
    font-weight:700; font-size:1.02rem;
    white-space:normal;
    overflow-wrap:anywhere;
    word-break:break-word;
    line-height:1.15;
  }
  .rsu-shell{
    width:100%;
    min-height:420px;
  }
  .rsu-item-card{
    margin-top:8px;
    border:1px solid #ccc;
    border-radius:8px;
    background-color:#fff;
    padding:20px;
    width:100%;
    max-width:none;
    box-shadow:0 2px 8px rgba(0,0,0,.1);
  }
  .rsu-sem-modal-header {
    background:#111111;
    color:#ffffff;
    border-bottom:0;
  }
  .rsu-sem-modal-header .close {
    color:#ffffff;
    opacity:.9;
    text-shadow:none;
  }
  .rsu-sem-help {
    border:1px solid #e9ecef;
    background:#f8f9fa;
    border-radius:.5rem;
    padding:.75rem;
    font-size:.9rem;
    color:#374151;
  }
  .rsu-sem-timeline {
    position:relative;
    padding-left:1.35rem;
    margin-top:.8rem;
  }
  .rsu-sem-timeline::before {
    content:'';
    position:absolute;
    left:.36rem;
    top:.25rem;
    bottom:.25rem;
    width:2px;
    background:linear-gradient(180deg,#111111 0%, #198754 52%, #111111 100%);
    opacity:.35;
  }
  .rsu-sem-node {
    position:relative;
    margin-bottom:.85rem;
    padding-left:.35rem;
  }
  .rsu-sem-node:last-child {
    margin-bottom:0;
  }
  .rsu-sem-dot {
    position:absolute;
    left:-1.03rem;
    top:.55rem;
    width:.72rem;
    height:.72rem;
    border-radius:50%;
    border:2px solid #ffffff;
    box-shadow:0 0 0 2px rgba(17,17,17,.14);
    background:#198754;
  }
  .rsu-sem-dot--limit {
    background:#111111;
  }
  .rsu-sem-card {
    border:1px solid #e5e7eb;
    border-radius:.55rem;
    background:#ffffff;
    padding:.62rem .7rem;
  }
  .rsu-sem-card-title {
    font-weight:700;
    color:#111827;
    margin-bottom:.2rem;
    font-size:.92rem;
  }
  .rsu-sem-card-text {
    margin:0;
    color:#374151;
    font-size:.86rem;
  }
  .rsu-sem-missing {
    color:#b91c1c;
    font-weight:700;
  }
  .rsu-sem-empty {
    color:#6b7280;
    font-style:italic;
    font-size:.88rem;
  }
  .rsu-sem-node--actual .rsu-sem-dot {
    background:#16a34a;
    box-shadow:0 0 0 2px rgba(22,163,74,.35);
  }
  .rsu-sem-card--actual {
    border:1px solid #16a34a;
    background:linear-gradient(180deg, #f2fff6 0%, #eafbf1 100%);
  }
  .rsu-sem-actual-badge {
    display:inline-flex;
    align-items:center;
    font-size:.72rem;
    font-weight:700;
    color:#0f5132;
    background:#d1fae5;
    border:1px solid #86efac;
    border-radius:999px;
    padding:.13rem .5rem;
    margin-bottom:.35rem;
  }
  .rsu-sem-current-box {
    border:1px dashed #9ae6b4;
    border-radius:.45rem;
    background:#f0fdf4;
    padding:.45rem .55rem;
    margin-top:.45rem;
  }
  .rsu-sem-current-box .line {
    font-size:.82rem;
    color:#14532d;
    margin:0 0 .15rem 0;
  }
  .rsu-sem-current-box .line:last-child {
    margin-bottom:0;
  }
</style>
<div class="rsu-header <?php echo $esInformeFinalActualFormulario ? 'rsu-header--final' : ''; ?> px-3 py-2 mb-2">
  <div class="row align-items-start">
    <!-- Izquierda: período + chips -->
    <div class="col-12 col-lg-3 mb-2 mb-lg-0">
      <div class="d-flex align-items-start mb-1">
        <i class="fas fa-calendar mr-2" style="opacity:.95;"></i>
        <span class="font-weight-bold" style="font-size:1.05rem;">
          Período de presentación — <?= htmlspecialchars($periodoNom) ?>
        </span>
      </div>
      <div class="d-flex flex-wrap">
        <span class="rsu-chip rsu-chip--open">
          <i class="far fa-check-circle"></i> Apertura
          <span class="time"><?= htmlspecialchars($sm_info['apertura'] ?? '-') ?></span>
        </span>
        <span class="rsu-chip rsu-chip--close">
          <i class="far fa-times-circle"></i> Cierre
          <span class="time"><?= htmlspecialchars($sm_info['cierre'] ?? '-') ?></span>
        </span>
      </div>
    </div>
    <!-- Centro: Título del proyecto + Fechas -->
    <div class="col-12 col-lg-6 mb-2 mb-lg-0">
      <div class="d-flex align-items-start">
        <i class="fas fa-project-diagram mr-2 mt-1" style="opacity:.95;"></i>
        <div class="w-100">
          <div class="rsu-proy-title" title="<?= htmlspecialchars($proyTitulo) ?>">
            <?= htmlspecialchars($proyTitulo ?: 'Proyecto sin título') ?>
          </div>
          <div class="text-muted small mt-1">
            <i class="far fa-calendar-alt"></i>
            <?= htmlspecialchars($proyIniTxt ?: '-') ?> — <?= htmlspecialchars($proyFinTxt ?: '-') ?>
          </div>
        </div>
      </div>
    </div>
    <!-- Derecha: mensaje + botones -->
    <div class="col-12 col-lg-3 text-start">
      <div class="text-muted small mb-2">
        Completa los ítems en orden. Puedes editar anteriores, pero no saltarte los siguientes sin completar.
      </div>
      <div class="rsu-btn-tray">
        <a href="#" id="btnObservaciones"
   class="btn btn-warning btn-sm d-inline-flex align-items-center"
   data-id-py="<?= (int)$idProyecto ?>">
  <i class="fas fa-exclamation-triangle"></i> Observaciones
</a>
        <a href="#" id="btnSemestresResumen" class="btn btn-dark btn-sm text-white d-inline-flex align-items-center">
          <i class="fas fa-layer-group"></i> Semestres
        </a>
      </div>
    </div>
  </div>
</div>

    <!-- Contenido central -->
    <div class="row g-0 flex-grow-1" style="overflow: hidden;">
        <!-- Panel izquierdo -->
        <div class="col-md-9 border-end p-2" style="height: 100%; overflow-y: auto;">

            <!-- Mensajes según estado -->
<?php if ($aprobacionTotal): ?>
  <div class="alert alert-success py-2">
    Tu informe está <strong>aprobado</strong>. La edición está deshabilitada.
  </div>
<?php elseif ($enOficina && $observadoOficina): ?>
  <div class="alert alert-warning py-2">
    Tu informe fue <strong>observado</strong> en la oficina de <strong><?= htmlspecialchars($ruta['oficina_nom'] ?? '') ?></strong>.
    Puedes editar y luego presionar <strong>Enviar Subsanación</strong>.
  </div>
<?php elseif ($estadoRespuesta === 1 && !$enOficina): ?>
  <div class="alert alert-info py-2">
    Tu informe está <strong>en revisión</strong> (aún fuera de oficinas). Puedes <em>anular</em> para volver a editar.
  </div>
<?php elseif (in_array($estadoRespuesta, [0,3], true)): ?>
  <div class="alert alert-light py-2 border">
    Completa todos los ítems y presiona <strong>Solicitar Revisión</strong>.
  </div>
<?php endif; ?>            

            <!-- Bloque del Ítem Actual -->
            <div class="rsu-item-card">
                <h5 class="mb-2">Ítem <?= $itemActualIdx ?> de <?= $totalItems ?> — <?= htmlspecialchars($itemActual['nombre']) ?></h5>
<!-- Párrafo de ejemplo (versión compacta y justificada) -->
<?php
  // 1) Tomar y normalizar el texto original
  $raw = (string)($itemActual['ejemplo'] ?? '');
  $raw = str_replace(["\r\n", "\r"], "\n", $raw);

  // 2) Quitar sangrías comunes por línea y espacios a izquierda
  $lines = explode("\n", $raw);
  $minIndent = null;
  foreach ($lines as $ln) {
    if (trim($ln) === '') continue;
    if (preg_match('/^[ \t]+/', $ln, $m)) {
      $len = strlen($m[0]);
      $minIndent = ($minIndent === null) ? $len : min($minIndent, $len);
    } else {
      $minIndent = 0;
      break;
    }
  }
  if ($minIndent && $minIndent > 0) {
    foreach ($lines as &$ln) { $ln = preg_replace('/^[ \t]{0,' . $minIndent . '}/', '', $ln); }
    unset($ln);
  }
  foreach ($lines as &$ln) {
    $ln = ltrim($ln);
    $ln = preg_replace('/[ \t]+/', ' ', $ln);
  }
  unset($ln);
  $txt = implode("\n", $lines);

  // 3) Compactación de saltos
  $txt = preg_replace('/\n{3,}/', "\n\n", $txt);
  $marker = "__BR2__";
  $txt = str_replace("\n\n", $marker, $txt);
  $txt = str_replace("\n", ' ', $txt);
  $txt = preg_replace('/[ \t]{2,}/', ' ', $txt);
  $txt = trim($txt);
  $txt = htmlspecialchars($txt, ENT_QUOTES, 'UTF-8');
  $txt = str_replace($marker, '<br>', $txt);
?>
<div class="mb-2">
  <div class="d-flex align-items-start p-2"
       style="border:1px solid #b8dbff; background:#eef7ff; border-radius:.5rem; color:#0b2e3b;">
    <i class="far fa-lightbulb mr-2 mt-1" style="font-size:1rem; color:#0b57d0;"></i>
    <div class="w-100">
      <div style="font-weight:600; font-size:.9rem; color:#0b2e3b;">Guía para completar este ítem</div>
      <div style="margin-top:.2rem; text-align:justify; line-height:1.25; font-size:.9rem;">
        <?php
          if ($txt !== '') echo $txt;
          else echo '<em>Sin ejemplo disponible para este ítem.</em>';
        ?>
      </div>
    </div>
  </div>
</div>
                <?php
                $uploadMax = ini_get('upload_max_filesize') ?: '2M';
                $postMax   = ini_get('post_max_size') ?: '8M';

                $tipo = $itemActual['tipo'];
                $v    = $valorExistente;
                $isFileType = in_array($tipo, ['pdf','excel','word'], true);

                // Si no editable, deshabilitamos inputs y el botón de guardar
                $disabledAttr = $editable ? '' : 'disabled';
                ?>
                <form method="post" action="guardar_item.php" class="mt-2" <?= $isFileType ? 'enctype="multipart/form-data"' : '' ?>>
                    <input type="hidden" name="id_respuesta" value="<?= $respuestaId ?>">
                    <input type="hidden" name="id_item" value="<?= (int)$itemActual['id_item'] ?>">
                    <input type="hidden" name="tipo" value="<?= htmlspecialchars($tipo) ?>">
                    <input type="hidden" name="next" value="<?= min($itemActualIdx+1, $totalItems) ?>">

                    <?php if ($tipo === 'varchar'): ?>
                        <input class="form-control" type="text" name="val_varchar" maxlength="1000"
                               value="<?= htmlspecialchars(val($v,'val_varchar')) ?>"
                               placeholder="Escribe tu respuesta (máx 1000 caracteres)" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'longtext'): ?>
                        <label class="form-label mb-1">Respuesta</label>
                        <textarea class="form-control js-summernote-longtext" name="val_longtext" placeholder="Escribe tu respuesta" <?= $disabledAttr ?>><?= isset($v['val_longtext']) ? $v['val_longtext'] : '' ?></textarea>

                    <?php elseif ($tipo === 'tinyint'): ?>
                        <input class="form-control" type="number" name="val_tinyint" min="0" max="9"
                               value="<?= htmlspecialchars(val($v,'val_tinyint')) ?>" placeholder="0-9" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'int'): ?>
                        <input class="form-control" type="number" name="val_int" min="0"
                               value="<?= htmlspecialchars(val($v,'val_int')) ?>" placeholder="Número entero positivo" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'boolean'): ?>
                        <?php $checked = (isset($v['val_boolean']) && (int)$v['val_boolean'] === 1) ? 'checked' : ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="chk_bool" name="val_boolean" value="1" <?= $checked ?> <?= $disabledAttr ?>>
                            <label class="form-check-label" for="chk_bool">Marcar si aplica</label>
                        </div>

                    <?php elseif ($tipo === 'datetime'): ?>
                        <input class="form-control" type="datetime-local" name="val_datetime"
                               value="<?= htmlspecialchars(val($v,'val_datetime')) ?>" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'date'): ?>
                        <input class="form-control" type="date" name="val_date"
                               value="<?= htmlspecialchars(val($v,'val_date')) ?>" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'decimal'): ?>
                        <input class="form-control" type="text" name="val_decimal"
                               value="<?= htmlspecialchars(val($v,'val_decimal')) ?>"
                               placeholder="Ej. 1234.56 o 1,234.56 (se normalizará a 2 decimales)" <?= $disabledAttr ?>>

                    <?php elseif ($tipo === 'programa_ods'): ?>
                        <?php $selProg = trim((string)val($v,'val_varchar')); ?>
                        <label class="form-label">Programa priorizado</label>
                        <select class="form-control" name="val_varchar" id="programa_select" <?= $disabledAttr ?>>
                            <option value="">-- Selecciona un programa --</option>
                            <?php foreach ($programas as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ($selProg !== '' && (int)$selProg === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2 p-2" style="background:#f8f9fa; border:1px solid #e3e3e3; border-radius:6px">
                            <strong>ODS de este programa:</strong>
                            <div id="prog_ods_view" class="mt-1"></div>
                        </div>

                    <?php elseif ($tipo === 'ods'): ?>
                        <?php
                        $csvExist = trim((string)val($v, 'val_varchar'));
                        $idsExist = array_filter($csvExist !== '' ? array_map('intval', explode(',', $csvExist)) : []);
                        ?>
                        <label class="form-label">Selecciona ODS (no asociados a programas)</label>

                        <select class="form-control" id="ods_select" name="ods_ids[]" multiple
                                data-placeholder="Escribe para buscar y selecciona varios ODS..." style="width:100%" <?= $disabledAttr ?>>
                            <?php foreach ($odsLibres as $o): ?>
                                <option value="<?= (int)$o['id'] ?>" <?= in_array((int)$o['id'], $idsExist, true) ? 'selected' : '' ?>>
                                    <?= (int)$o['id'] ?> — <?= htmlspecialchars($o['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <input type="hidden" name="val_varchar" id="ods_hidden_csv" value="<?= htmlspecialchars($csvExist) ?>">

                        <small class="text-muted d-block mt-1">
                            Puedes seleccionar múltiples ODS; cada etiqueta tiene una <strong>x</strong> para quitarla.
                        </small>

                    <?php elseif (in_array($tipo, ['pdf','excel','word'], true)): ?>
                        <?php
                        $exist_url = trim((string)val($v, 'archivo_url'));
                        $openUrl   = $exist_url !== '' ? public_url($exist_url) : '';
                        $accept    = ($tipo === 'pdf')
                            ? '.pdf,application/pdf'
                            : (($tipo === 'excel')
                                ? '.xls,.xlsx,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
                                : '.doc,.docx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document');
                        ?>
                        <div class="mb-2">
                            <label class="form-label">Subir archivo <?= strtoupper($tipo) ?></label>
                            <input class="form-control" type="file" name="upload_file" accept="<?= htmlspecialchars($accept) ?>" <?= $disabledAttr ?>>
                            <small class="text-muted d-block mt-1">Tamaño máx (referencial): upload_max_filesize=<?= htmlspecialchars($uploadMax) ?>, post_max_size=<?= htmlspecialchars($postMax) ?>.</small>
                        </div>

                        <div class="mt-3 p-2" style="background:#f8f9fa; border:1px solid #e3e3e3; border-radius:6px">
                            <strong>Archivo actual:</strong>
                            <?php if ($exist_url !== ''): ?>
                                <div class="d-flex align-items-center mt-1">
                                    <code class="mr-2" style="word-break:break-all"><?= htmlspecialchars($exist_url) ?></code>
                                    <a class="btn btn-outline-primary btn-xs mr-2" target="_blank" href="<?= htmlspecialchars($openUrl) ?>">Abrir</a>
                                    <?php if ($editable): ?>
                                      <a class="btn btn-outline-danger btn-xs"
                                         href="logica/borrar_archivo.php?id_respuesta=<?= (int)$respuestaId ?>&id_item=<?= (int)$itemActual['id_item'] ?>&return_item=<?= (int)$itemActualIdx ?>"
                                         onclick="return confirm('¿Eliminar el archivo actual?');">Borrar</a>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-muted mt-1"><em>No hay archivo subido aún.</em></div>
                            <?php endif; ?>
                        </div>

                    <?php else: ?>
                        <div class="alert alert-warning">Tipo no soportado: <?= htmlspecialchars($tipo) ?></div>
                    <?php endif; ?>

                    <div class="mt-3 d-flex justify-content-end">
                        <button type="submit" class="btn btn-success" <?= $editable ? '' : 'disabled' ?>>
                          Guardar e ir al siguiente
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Panel derecho -->
        <div class="col-md-3 p-2" style="height: 100%; overflow-y: auto;">

            <!-- ¿Cómo va tu avance? -->
            <div class="card shadow-sm border-0 mb-3">
              <div class="card-header bg-white py-1 d-flex align-items-center" style="border-bottom:1px solid rgba(0,0,0,.05)">
                <i class="fas fa-rocket text-primary mr-2"></i>
                <strong>¿Cómo va tu avance?</strong>
              </div>
              <div class="card-body">
                <div class="d-flex justify-content-between mb-1">
                  <div><i class="far fa-check-circle text-success mr-1"></i><strong>Completados:</strong></div>
                  <div><span class="badge badge-light"><?= $completados ?> / <?= $totalItems ?></span></div>
                </div>
                <div class="progress mb-3" style="height:8px;">
                  <div class="progress-bar" role="progressbar"
                       style="width: <?= $porcentaje ?>%;" aria-valuenow="<?= $porcentaje ?>" aria-valuemin="0" aria-valuemax="100"></div>
                </div>
                <div class="d-flex justify-content-between">
                  <div><i class="fas fa-list-ol text-secondary mr-1"></i><strong> Ítem Actual:</strong></div>
                  <div><span class="badge badge-primary">Ítem <?= $itemActualIdx ?></span></div>
                </div>
              </div>
            </div>
            <?php
              $help = render_help_buttons($itemActual);
              if ($help !== ''):
            ?>
            <div class="card shadow-sm border-0">
              <div class="card-header bg-white py-2 d-flex align-items-center" style="border-bottom:1px solid rgba(0,0,0,.05)">
                <i class="far fa-life-ring text-info mr-2"></i>
                <strong>Recursos del ítem</strong>
              </div>
              <div class="card-body">
                <?= $help ?>
              </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Div Inferior -->
    <div class="bg-white shadow-sm py-1 px-2">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div class="btn-group mb-0" role="group" aria-label="Navegación por ítems">
                <?php
                for ($i=1; $i <= $totalItems; $i++) {
                    $it = $items[$i-1];
                    $estado = 'lock';
                    if ($i < $primerIncompletoIdx)      $estado = 'done';
                    elseif ($i === $itemActualIdx)      $estado = 'active';
                    elseif ($i === $primerIncompletoIdx)$estado = 'next';

                    $cls = pillClass($estado);
                    $isCurrent = ($i === $itemActualIdx);
                    $disabled  = ($estado === 'lock') ? 'disabled' : '';
                    $url       = strtok($_SERVER["REQUEST_URI"], '?') . '?item=' . $i;
                    $extraClasses = 'item-nav' . ($isCurrent ? ' active' : '');

                    echo "<a href='{$url}' class='btn {$cls} {$disabled} {$extraClasses}' aria-current='".($isCurrent?'true':'false')."' style='min-width:42px'>{$i}</a>";
                }
                ?>
            </div>
<div class="mb-2">
<button
  id="btnSolicitarRevision"
  class="btn <?= htmlspecialchars($btnClase) ?>"
  type="button"
  <?= $btnDisabled ? 'disabled ' : '' ?>
  <?= $btnTitle ? 'title="'.htmlspecialchars($btnTitle, ENT_QUOTES, 'UTF-8').'" ' : '' ?>
  data-toggle="tooltip" data-bs-toggle="tooltip"

  data-respuesta-id="<?= (int)$respuestaId ?>"
  data-proy-titulo="<?= htmlspecialchars($proyTitulo, ENT_QUOTES, 'UTF-8') ?>"
  data-form-nombre="<?= htmlspecialchars($formulario['nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>"

  data-estado="<?= (int)$estadoRespuesta ?>"
  data-accion="<?= htmlspecialchars($btnAccion ?: '') ?>"
>
  <?= htmlspecialchars($btnLabel) ?>
</button>
</div>
        </div>
    </div>
</div>

<!-- Modales -->
<div class="modal fade" id="modalSemestresResumen" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header rsu-sem-modal-header">
        <h5 class="modal-title mb-0">
          <i class="fas fa-layer-group mr-2"></i>Semestres del proyecto
        </h5>
        <button type="button" class="close" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="rsu-sem-help">
          Aqu&iacute; podr&aacute;s ver los semestres que comprende tu proyecto basado en la fecha de inicio y fin que definiste en Presentaci&oacute;n de proyecto - generalidades.
          Recuerda que cada semestre se presenta un informe semestral.
          Y el &uacute;ltimo informe semestral es tambi&eacute;n tu informe final.
        </div>
        <?php if (!$haySemestreActualModal): ?>
          <div class="alert alert-warning mt-2 mb-0 py-2">
            No se pudo identificar con precisi&oacute;n el semestre actual del formulario en esta vista.
          </div>
        <?php endif; ?>

        <div class="rsu-sem-timeline">
          <div class="rsu-sem-node">
            <span class="rsu-sem-dot rsu-sem-dot--limit"></span>
            <div class="rsu-sem-card">
              <div class="rsu-sem-card-title">Inicio del proyecto</div>
              <p class="rsu-sem-card-text">
                <?php if ($fechaInicioFaltante): ?>
                  <span class="rsu-sem-missing">No registrado</span>
                <?php else: ?>
                  <?php echo htmlspecialchars($fechaInicioModal, ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <?php if (!empty($semestresResumenModal)): ?>
            <?php foreach ($semestresResumenModal as $semItem): ?>
              <?php
                $esActual = !empty($semItem['es_actual']);
                $nodeClass = $esActual ? 'rsu-sem-node rsu-sem-node--actual' : 'rsu-sem-node';
                $cardClass = $esActual ? 'rsu-sem-card rsu-sem-card--actual' : 'rsu-sem-card';
                $entregasTexto = '';
                if (isset($semItem['entregas']) && is_array($semItem['entregas']) && !empty($semItem['entregas'])) {
                  $entregasTexto = implode(' | ', $semItem['entregas']);
                }
              ?>
              <div class="<?php echo htmlspecialchars($nodeClass, ENT_QUOTES, 'UTF-8'); ?>">
                <span class="rsu-sem-dot"></span>
                <div class="<?php echo htmlspecialchars($cardClass, ENT_QUOTES, 'UTF-8'); ?>">
                  <?php if ($esActual): ?>
                    <span class="rsu-sem-actual-badge">Semestre actual del formulario</span>
                  <?php endif; ?>
                  <div class="rsu-sem-card-title"><?php echo htmlspecialchars((string)$semItem['semestre'], ENT_QUOTES, 'UTF-8'); ?></div>
                  <p class="rsu-sem-card-text">
                    <?php if ($entregasTexto !== ''): ?>
                      <?php echo htmlspecialchars($entregasTexto, ENT_QUOTES, 'UTF-8'); ?>
                    <?php else: ?>
                      Entrega pendiente de definir.
                    <?php endif; ?>
                  </p>
                  <?php if ($esActual): ?>
                    <div class="rsu-sem-current-box">
                      <p class="line"><strong>Per&iacute;odo activo:</strong> <?php echo htmlspecialchars((string)($semItem['periodo_actual'] ?? '-'), ENT_QUOTES, 'UTF-8'); ?></p>
                      <p class="line"><strong>Corresponde:</strong> <?php echo htmlspecialchars((string)($semItem['corresponde'] ?? 'Informe semestral'), ENT_QUOTES, 'UTF-8'); ?></p>
                      <p class="line"><strong>Debes completar:</strong> <?php echo htmlspecialchars((string)($semItem['formulario'] ?? 'Formulario del cronograma activo'), ENT_QUOTES, 'UTF-8'); ?></p>
                      <p class="line">
                        <strong>Ventana para completar:</strong>
                        <?php
                          $ap = isset($semItem['ventana_apertura']) ? trim((string)$semItem['ventana_apertura']) : '';
                          $ci = isset($semItem['ventana_cierre']) ? trim((string)$semItem['ventana_cierre']) : '';
                          if ($ap === '' && $ci === '') {
                            echo 'No registrada';
                          } else {
                            echo htmlspecialchars(($ap !== '' ? $ap : '-') . ' | ' . ($ci !== '' ? $ci : '-'), ENT_QUOTES, 'UTF-8');
                          }
                        ?>
                      </p>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="rsu-sem-node">
              <span class="rsu-sem-dot"></span>
              <div class="rsu-sem-card">
                <div class="rsu-sem-card-title">Semestres del proyecto</div>
                <p class="rsu-sem-empty mb-0">No se encontr&oacute; un resumen de semestres para este proyecto.</p>
              </div>
            </div>
          <?php endif; ?>

          <div class="rsu-sem-node">
            <span class="rsu-sem-dot rsu-sem-dot--limit"></span>
            <div class="rsu-sem-card">
              <div class="rsu-sem-card-title">Fin del proyecto</div>
              <p class="rsu-sem-card-text">
                <?php if ($fechaFinFaltante): ?>
                  <span class="rsu-sem-missing">No registrado</span>
                <?php else: ?>
                  <?php echo htmlspecialchars($fechaFinModal, ENT_QUOTES, 'UTF-8'); ?>
                <?php endif; ?>
              </p>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="imgModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="imgModalSrc" src="" alt="Imagen" style="width:100%; height:auto; display:block;">
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
// ========= Programa → vista ODS relacionados =========
const PROG_ODS_MAP = <?= json_encode($progOdsMap, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
function renderProgOdsView() {
  const sel = document.getElementById('programa_select');
  if (!sel) return;
  const val = sel.value.trim();
  const box = document.getElementById('prog_ods_view');
  if (!box) return;
  if (val === '') { box.innerHTML = '<em>Selecciona un programa para ver sus ODS.</em>'; return; }
  const txt = PROG_ODS_MAP[val] || '';
  box.innerHTML = txt ? txt : '<em>Este programa no tiene ODS asociados.</em>';
}
document.addEventListener('change', e => { if (e.target && e.target.id === 'programa_select') renderProgOdsView(); });

document.addEventListener('DOMContentLoaded', () => {
  renderProgOdsView();

  // ========= ODS con Select2 (chips con "x") =========
  (function initOdsSelect2() {
    var $sel = window.jQuery ? jQuery('#ods_select') : null;
    if ($sel && $sel.select2) {
      $sel.select2({
        theme: 'bootstrap4',
        width: '100%',
        placeholder: $sel.attr('data-placeholder') || 'Selecciona ODS',
        allowClear: true,
      });

      // Sincroniza a CSV oculto para compatibilidad con backend
      var $hid = jQuery('#ods_hidden_csv');
      var sync = function() {
        var vals = ($sel.val() || []).map(function(v){ return String(v).trim(); }).filter(Boolean);
        $hid.val(vals.join(','));
      };
      $sel.on('change', sync);
      sync(); // inicial
    } else {
      // Fallback sin jQuery/Select2
      const sel = document.getElementById('ods_select');
      const hid = document.getElementById('ods_hidden_csv');
      if (sel && hid) {
        const sync = () => {
          const vals = Array.from(sel.selectedOptions).map(o => o.value.trim()).filter(Boolean);
          hid.value = vals.join(',');
        };
        sel.addEventListener('change', sync);
        sync();
      }
    }
  })();
});

// ========= Imagen =========
function showImg(url) {
  const img = document.getElementById('imgModalSrc');
  img.src = url;
  if (window.jQuery && typeof jQuery.fn.modal === 'function') {
    jQuery('#imgModal').modal('show');
  } else if (window.bootstrap && window.bootstrap.Modal) {
    new bootstrap.Modal(document.getElementById('imgModal')).show();
  }
}

// ========= Utilidad para mostrar modal (BS4 o BS5) =========
function showBsModal(element) {
  if (window.jQuery && typeof jQuery.fn.modal === 'function') {
    jQuery(element).modal('show');
  } else if (window.bootstrap && window.bootstrap.Modal) {
    const m = new bootstrap.Modal(element);
    m.show();
  } else {
    element.style.display = 'block';
  }
}

// ========= Modal de VIDEO dinámico (crea y destruye) =========
document.addEventListener('DOMContentLoaded', function () {
  var btnSemestres = document.getElementById('btnSemestresResumen');
  var modalSemestres = document.getElementById('modalSemestresResumen');
  if (!btnSemestres || !modalSemestres) return;

  btnSemestres.addEventListener('click', function (event) {
    event.preventDefault();
    showBsModal(modalSemestres);
  });
});

function openVideoModal(videoUrl) {
  const old = document.getElementById('ytModalDyn');
  if (old && old.parentNode) old.parentNode.removeChild(old);

  const modal = document.createElement('div');
  modal.className = 'modal fade';
  modal.id = 'ytModalDyn';
  modal.setAttribute('tabindex', '-1');
  modal.setAttribute('aria-hidden', 'true');

  modal.innerHTML = `
    <div class="modal-dialog modal-xl modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-body p-0">
          <div style="position:relative; width:100%; height:0; padding-bottom:56.25%; background:#000;">
            <iframe id="ytFrameDyn"
              src="logica/video_embed.php?u=${encodeURIComponent(videoUrl)}"
              style="position:absolute; top:0; left:0; width:100%; height:100%; border:0;"
              allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
              allowfullscreen>
            </iframe>
          </div>
        </div>
        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
        </div>
      </div>
    </div>
  `;

  document.body.appendChild(modal);

  const teardown = () => {
    try {
      const f = modal.querySelector('#ytFrameDyn');
      if (f && f.contentWindow) {
        f.contentWindow.postMessage('STOP', '*');
      }
    } catch (e) {}
    if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
  };

  if (window.jQuery) {
    jQuery(modal).on('hidden.bs.modal', teardown);
  }
  modal.addEventListener('hidden.bs.modal', teardown);

  showBsModal(modal);
}

// Delegación: click en botón de video
document.addEventListener('click', function (ev) {
  const btn = ev.target.closest('.video-btn');
  if (!btn) return;
  const url = btn.getAttribute('data-video') || '';
  if (!url) return;
  openVideoModal(url);
});
</script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    var $ = window.jQuery;
    if (!$ || !$.fn || !$.fn.summernote) return; // si no está cargado, no rompe
    $('.js-summernote-longtext').summernote({
      lang: 'es-ES',
      height: 220,
      placeholder: 'Escribe o pega contenido (tablas, listas, etc.)…',
      toolbar: [
        ['style',  ['style']],
        ['font',   ['bold','italic','underline','clear']],
        ['para',   ['ul','ol','paragraph']],
        ['table',  ['table']],
        ['insert', ['link']],
        ['view',   ['fullscreen','codeview','help']]
      ]
    });
  });
</script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  // Tooltip (BS4/BS5)
  (function initTooltip(){
    var b = document.getElementById('btnSolicitarRevision');
    if (!b) return;
    if (window.jQuery && jQuery.fn.tooltip) jQuery(b).tooltip();
    else if (window.bootstrap && window.bootstrap.Tooltip) new bootstrap.Tooltip(b);
  })();

  const btn = document.getElementById('btnSolicitarRevision');
  if (!btn) return;

btn.addEventListener('click', async function () {
  if (btn.disabled) return;

  const idRespuesta = btn.getAttribute('data-respuesta-id');
  const titulo      = btn.getAttribute('data-proy-titulo') || '';
  const formNombre  = btn.getAttribute('data-form-nombre') || '';
  const accion      = (btn.getAttribute('data-accion') || '').trim(); // 'solicitar' | 'anular' | 'subsanar'

  if (!idRespuesta) { alert('No se encontró la respuesta actual.'); return; }
  if (!accion) { return; } // botón informativo, sin acción

  let url = '';
  let confirmMsg = '';
  let sendingTxt = '';

  if (accion === 'solicitar') {
    confirmMsg = '¿Enviar el informe para revisión? Ya no podrás editar hasta recibir respuesta.';
    url = 'logica/solicitar_revision.php';
    sendingTxt = 'Enviando…';
  } else if (accion === 'anular') {
    confirmMsg = '¿Anular la solicitud de revisión y volver el estado a borrador? Podrás editar nuevamente.';
    url = 'logica/anular_revision.php';
    sendingTxt = 'Anulando…';
  } else if (accion === 'subsanar') {
    confirmMsg = '¿Enviar esta subsanación a la oficina para nueva revisión?';
    url = 'logica/enviar_subsanacion.php';
    sendingTxt = 'Enviando…';
  }

  if (!confirm(confirmMsg)) return;

  btn.disabled = true;
  const oldText = btn.textContent;
  btn.textContent = sendingTxt;

  try {
    const params = new URLSearchParams({ id_respuesta: idRespuesta });
    if (accion === 'solicitar') {
      params.append('proy_titulo', titulo);
      params.append('form_nombre', formNombre);
    }

    const resp = await fetch(url, {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
      body: params.toString()
    });

    const raw = await resp.text();
    let data = null;
    try { data = raw ? JSON.parse(raw) : null; } catch (_) {}

    if (!resp.ok || !data || data.status !== 'ok') {
      const serverMsg =
        (data && data.msg) ? data.msg :
        (raw && raw.trim()) ? raw.trim() :
        `Respuesta ${resp.status} sin cuerpo válido`;
      throw new Error(serverMsg);
    }

    alert(
      accion === 'solicitar' ? 'Solicitud enviada a revisión.' :
      accion === 'anular'    ? 'Solicitud anulada. El informe vuelve a estado En proceso (editable).' :
                               'Subsanación enviada. Se reanuda la revisión en la oficina.'
    );
    location.reload();

  } catch (e) {
    alert('No se pudo completar la acción: ' + (e && e.message ? e.message : e));
    btn.disabled = false;
    btn.textContent = oldText;
  }
});

});
</script>
<script src="../evaluacion/js/observaciones_ui.js"></script>
<script>
(function () {
  function ajustarAltoSemestral() {
    var shell = document.getElementById('semestralShell');
    if (!shell) return;

    var footer = document.querySelector('.main-footer');
    var shellTop = shell.getBoundingClientRect().top;
    var limiteInferior = footer ? footer.getBoundingClientRect().top : window.innerHeight;
    var altoDisponible = Math.floor(limiteInferior - shellTop - 4);

    if (altoDisponible < 420) altoDisponible = 420;
    shell.style.height = altoDisponible + 'px';
  }

  window.addEventListener('load', ajustarAltoSemestral);
  window.addEventListener('resize', function () {
    clearTimeout(window.__rsuShellResize);
    window.__rsuShellResize = setTimeout(ajustarAltoSemestral, 120);
  });

  if (window.jQuery) {
    jQuery(document).on('collapsed.lte.pushmenu expanded.lte.pushmenu', function () {
      setTimeout(ajustarAltoSemestral, 250);
    });
  }
})();
</script>
