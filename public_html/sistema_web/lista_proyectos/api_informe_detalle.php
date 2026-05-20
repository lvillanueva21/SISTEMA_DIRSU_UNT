<?php
include "../componentes/configSesion.php";
include "../includes/db_connection.php";

if (!function_exists('lp_h')) {
    function lp_h($v)
    {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('lp_web_files_base')) {
    function lp_web_files_base()
    {
        $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
        $appBasePath = rtrim(dirname(dirname($scriptName)), '/');
        if ($appBasePath === '' || $appBasePath === '.') {
            $appBasePath = '/sistema_web';
        }
        return $appBasePath . '/files_answer/';
    }
}

if (!function_exists('lp_public_file_url')) {
    function lp_public_file_url($raw)
    {
        $raw = trim((string)$raw);
        if ($raw === '') {
            return null;
        }
        if (preg_match('~^https?://~i', $raw)) {
            return $raw;
        }

        $norm = str_replace('\\', '/', $raw);
        $pos = stripos($norm, 'files_answer');
        if ($pos !== false) {
            $rel = ltrim(substr($norm, $pos + strlen('files_answer')), '/');
            $segments = array_map('rawurlencode', array_filter(explode('/', $rel), 'strlen'));
            return rtrim(lp_web_files_base(), '/') . '/' . implode('/', $segments);
        }

        $base = basename($norm);
        if ($base === '' || $base === '.' || $base === '..') {
            return null;
        }
        return rtrim(lp_web_files_base(), '/') . '/' . rawurlencode($base);
    }
}

if (!function_exists('lp_format_item_value')) {
    function lp_format_item_value($row)
    {
        $tipo = (string)($row['tipo'] ?? '');
        $vv = isset($row['val_varchar']) ? (string)$row['val_varchar'] : '';
        $vl = isset($row['val_longtext']) ? (string)$row['val_longtext'] : '';
        $vb = isset($row['val_boolean']) ? $row['val_boolean'] : null;
        $vi = isset($row['val_int']) ? $row['val_int'] : null;
        $vt = isset($row['val_tinyint']) ? $row['val_tinyint'] : null;
        $vd = isset($row['val_date']) ? (string)$row['val_date'] : '';
        $vdt = isset($row['val_datetime']) ? (string)$row['val_datetime'] : '';
        $vde = isset($row['val_decimal']) ? $row['val_decimal'] : null;
        $file = isset($row['archivo_url']) ? (string)$row['archivo_url'] : '';

        if ($tipo === 'longtext' || $tipo === 'ubicacion') {
            return ($vl !== '') ? nl2br(lp_h($vl)) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'boolean') {
            if ($vb === null || $vb === '') return '<em class="text-muted">Sin respuesta</em>';
            return ((int)$vb === 1) ? 'Sí' : 'No';
        }
        if ($tipo === 'int') {
            return ($vi !== null && $vi !== '') ? lp_h($vi) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'tinyint') {
            return ($vt !== null && $vt !== '') ? lp_h($vt) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'date') {
            return ($vd !== '') ? lp_h($vd) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'datetime') {
            return ($vdt !== '') ? lp_h($vdt) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'decimal') {
            return ($vde !== null && $vde !== '') ? lp_h($vde) : '<em class="text-muted">Sin respuesta</em>';
        }
        if ($tipo === 'pdf' || $tipo === 'excel' || $tipo === 'word') {
            $url = lp_public_file_url($file);
            if ($url === null) return '<em class="text-muted">Sin archivo</em>';
            $safe = lp_h($url);
            if ($tipo === 'pdf') {
                return '<a href="' . $safe . '" target="_blank" rel="noopener" class="btn btn-sm btn-danger">Ver PDF</a>';
            }
            return '<a href="' . $safe . '" target="_blank" rel="noopener" class="btn btn-sm btn-secondary">Descargar</a>';
        }

        if ($vv !== '') return lp_h($vv);
        if ($vl !== '') return nl2br(lp_h($vl));
        return '<em class="text-muted">Sin respuesta</em>';
    }
}

$response_id = isset($_GET['response_id']) ? (int)$_GET['response_id'] : 0;
if ($response_id <= 0) {
    echo '<div class="alert alert-danger mb-0">Respuesta inválida.</div>';
    exit;
}

$sqlHeader = "
    SELECT
        r.id AS response_id,
        r.id_formulario,
        r.actualizado_at,
        p.id AS id_py,
        COALESCE(NULLIF(TRIM(p.p2), ''), 'Sin título') AS titulo_proyecto,
        COALESCE(NULLIF(TRIM(CONCAT(u.nombres, ' ', u.apellidos)), ''), 'Sin coordinador') AS coordinador,
        COALESCE(NULLIF(TRIM(fm.nombre), ''), 'Formulario') AS formulario,
        s.anio,
        s.periodo,
        COALESCE(s.final, 0) AS es_final
    FROM sm_respuestas r
    INNER JOIN proyectos p
        ON p.id = r.id_py
    LEFT JOIN sm_formularios fm
        ON fm.id = r.id_formulario
    LEFT JOIN sm_proyecto_semestres s
        ON s.id = r.id_semestre
    LEFT JOIN (
        SELECT
            up1.id_proyecto,
            MAX(up1.id) AS up_id
        FROM usuarios_proyectos up1
        INNER JOIN usuarios uu
            ON uu.id = up1.id_usuario
           AND uu.id_rol = 2
        WHERE up1.activo = 1
        GROUP BY up1.id_proyecto
    ) up_pick
        ON up_pick.id_proyecto = p.id
    LEFT JOIN usuarios_proyectos up
        ON up.id = up_pick.up_id
    LEFT JOIN usuarios u
        ON u.id = up.id_usuario
    WHERE r.id = ?
    LIMIT 1
";
$stHeader = mysqli_prepare($conexion, $sqlHeader);
if (!$stHeader) {
    echo '<div class="alert alert-danger mb-0">No se pudo cargar el informe.</div>';
    exit;
}
mysqli_stmt_bind_param($stHeader, 'i', $response_id);
$header = null;
if (mysqli_stmt_execute($stHeader)) {
    mysqli_stmt_bind_result(
        $stHeader,
        $h_response_id,
        $h_id_formulario,
        $h_actualizado_at,
        $h_id_py,
        $h_titulo_proyecto,
        $h_coordinador,
        $h_formulario,
        $h_anio,
        $h_periodo,
        $h_es_final
    );
    if (mysqli_stmt_fetch($stHeader)) {
        $header = array(
            'response_id' => $h_response_id,
            'id_formulario' => $h_id_formulario,
            'actualizado_at' => $h_actualizado_at,
            'id_py' => $h_id_py,
            'titulo_proyecto' => $h_titulo_proyecto,
            'coordinador' => $h_coordinador,
            'formulario' => $h_formulario,
            'anio' => $h_anio,
            'periodo' => $h_periodo,
            'es_final' => $h_es_final
        );
    }
}
mysqli_stmt_close($stHeader);

if (!$header) {
    echo '<div class="alert alert-warning mb-0">No se encontró la respuesta solicitada.</div>';
    exit;
}

$id_formulario = isset($header['id_formulario']) ? (int)$header['id_formulario'] : 0;
if ($id_formulario <= 0) {
    echo '<div class="alert alert-warning mb-0">La respuesta no tiene formulario asociado.</div>';
    exit;
}

$sqlItems = "
    SELECT
        fi.orden,
        i.nombre AS item_nombre,
        i.tipo,
        ri.val_varchar,
        ri.val_longtext,
        ri.val_tinyint,
        ri.val_int,
        ri.val_boolean,
        ri.val_datetime,
        ri.val_date,
        ri.val_decimal,
        ri.archivo_url
    FROM sm_formulario_items fi
    INNER JOIN sm_items i
        ON i.id = fi.id_item
    LEFT JOIN sm_respuesta_items ri
        ON ri.id_respuesta = ?
       AND ri.id_item = i.id
    WHERE fi.id_formulario = ?
      AND fi.activo = 1
    ORDER BY fi.orden ASC
";
$stItems = mysqli_prepare($conexion, $sqlItems);
if (!$stItems) {
    echo '<div class="alert alert-danger mb-0">No se pudo cargar ítems del informe.</div>';
    exit;
}
mysqli_stmt_bind_param($stItems, 'ii', $response_id, $id_formulario);
$items = array();
if (mysqli_stmt_execute($stItems)) {
    mysqli_stmt_bind_result(
        $stItems,
        $it_orden,
        $it_item_nombre,
        $it_tipo,
        $it_val_varchar,
        $it_val_longtext,
        $it_val_tinyint,
        $it_val_int,
        $it_val_boolean,
        $it_val_datetime,
        $it_val_date,
        $it_val_decimal,
        $it_archivo_url
    );
    while (mysqli_stmt_fetch($stItems)) {
        $items[] = array(
            'orden' => $it_orden,
            'item_nombre' => $it_item_nombre,
            'tipo' => $it_tipo,
            'val_varchar' => $it_val_varchar,
            'val_longtext' => $it_val_longtext,
            'val_tinyint' => $it_val_tinyint,
            'val_int' => $it_val_int,
            'val_boolean' => $it_val_boolean,
            'val_datetime' => $it_val_datetime,
            'val_date' => $it_val_date,
            'val_decimal' => $it_val_decimal,
            'archivo_url' => $it_archivo_url
        );
    }
}
mysqli_stmt_close($stItems);

$periodoTxt = 'No definido';
if (isset($header['anio'], $header['periodo']) && (int)$header['anio'] > 0 && trim((string)$header['periodo']) !== '') {
    $periodoTxt = (int)$header['anio'] . '-' . trim((string)$header['periodo']);
}
$tipoTxt = ((int)($header['es_final'] ?? 0) === 1) ? 'Informe Final' : 'Informe Semestral';
?>
<div class="p-2">
  <div class="border rounded p-2 mb-2 bg-light">
    <div><strong>Proyecto:</strong> <?= lp_h($header['titulo_proyecto']) ?></div>
    <div><strong>Coordinador:</strong> <?= lp_h($header['coordinador']) ?></div>
    <div><strong>Periodo:</strong> <?= lp_h($periodoTxt) ?> | <strong>Tipo:</strong> <?= lp_h($tipoTxt) ?></div>
    <div><strong>Formulario:</strong> <?= lp_h($header['formulario']) ?></div>
  </div>

  <?php if (empty($items)): ?>
    <div class="alert alert-warning mb-0">Este informe no contiene ítems activos.</div>
  <?php else: ?>
    <div class="table-responsive" style="max-height:65vh; overflow:auto;">
      <table class="table table-sm table-bordered mb-0">
        <thead class="thead-light">
          <tr>
            <th style="width: 8%;">#</th>
            <th style="width: 42%;">Ítem</th>
            <th style="width: 10%;">Tipo</th>
            <th style="width: 40%;">Respuesta</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($items as $it): ?>
            <tr>
              <td><?= lp_h($it['orden']) ?></td>
              <td><?= lp_h($it['item_nombre']) ?></td>
              <td><?= lp_h($it['tipo']) ?></td>
              <td><?= lp_format_item_value($it) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
