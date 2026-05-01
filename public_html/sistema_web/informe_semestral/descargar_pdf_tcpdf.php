<?php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../includes/db_connection.php';
require_once __DIR__ . '/../modules/TCPDF/tcpdf.php';

$id_py = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$id_respuesta = isset($_GET['id_respuesta']) ? (int)$_GET['id_respuesta'] : 0;

if ($id_respuesta > 0) {
    $sqlResp = "SELECT id_py FROM sm_respuestas WHERE id = $id_respuesta LIMIT 1";
    if ($rsResp = mysqli_query($conexion, $sqlResp)) {
        if ($rowResp = mysqli_fetch_assoc($rsResp)) {
            $id_py = (int)($rowResp['id_py'] ?? 0);
        }
        mysqli_free_result($rsResp);
    }
}

if ($id_py <= 0) {
    http_response_code(400);
    exit('ID de proyecto invalido.');
}

function rsu_slug_pdf(string $txt): string {
    $txt = trim($txt);
    if ($txt === '') return '';
    if (function_exists('iconv')) {
        $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $txt);
        if ($tmp !== false) $txt = $tmp;
    }
    $txt = preg_replace('/[^A-Za-z0-9]+/', '_', $txt);
    $txt = preg_replace('/_+/', '_', $txt);
    return trim(strtoupper($txt), '_');
}

function rsu_h($v): string {
    return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
}

function rsu_plain($v): string {
    return nl2br(rsu_h((string)$v));
}

function rsu_normalize_richtext_typography(string $html): string {
    // Quita etiquetas font heredadas de editores antiguos.
    $html = preg_replace('~</?font\b[^>]*>~i', '', $html);

    // Quita font-size/font-family/line-height de estilos inline, conserva el resto.
    $html = preg_replace_callback('/\sstyle=("|\')(.*?)\1/i', function ($m) {
        $quote = $m[1];
        $style = (string)$m[2];
        $style = preg_replace('/(?:^|;)\s*font-size\s*:[^;]*/i', '', $style);
        $style = preg_replace('/(?:^|;)\s*font-family\s*:[^;]*/i', '', $style);
        $style = preg_replace('/(?:^|;)\s*line-height\s*:[^;]*/i', '', $style);
        $style = trim((string)$style, " \t\n\r\0\x0B;");
        $style = preg_replace('/\s*;\s*/', '; ', $style);
        if ($style === '') return '';
        return ' style=' . $quote . $style . $quote;
    }, $html);

    // Normaliza encabezados para evitar que TCPDF aplique tamaños propios.
    $html = preg_replace('~<h[1-6]\b[^>]*>~i', '<p>', $html);
    $html = preg_replace('~</h[1-6]>~i', '</p>', $html);

    return $html;
}

function rsu_normalize_lists_for_tcpdf(string $html): string {
    $guard = 0;
    $pattern = '~<(ul|ol)\b[^>]*>((?:(?!<(?:ul|ol)\b).)*)</\1>~is';

    while ($guard < 50 && preg_match($pattern, $html)) {
        $html = preg_replace_callback($pattern, function ($m) {
            $listTag = strtolower((string)$m[1]);
            $content = (string)$m[2];
            $isOrdered = ($listTag === 'ol');

            $rows = [];
            $index = 1;
            if (preg_match_all('~<li\b[^>]*>(.*?)</li>~is', $content, $liMatches)) {
                foreach ($liMatches[1] as $liRaw) {
                    $li = trim((string)$liRaw);
                    if ($li === '') continue;
                    // Evita doble salto cuando el item viene envuelto totalmente en <p>.
                    $li = preg_replace('~^\s*<p\b[^>]*>(.*?)</p>\s*$~is', '$1', $li);
                    $marker = $isOrdered ? ($index . '.') : '&bull;';
                    $rows[] = ''
                        . '<tr>'
                        . '<td style="width:16px;vertical-align:top;padding:0 4px 1px 0;font-weight:bold;">' . $marker . '</td>'
                        . '<td style="vertical-align:top;padding:0 0 1px 0;">' . $li . '</td>'
                        . '</tr>';
                    $index++;
                }
            }

            if (empty($rows)) return '';

            return ''
                . '<table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin:0 0 2px 0;">'
                . implode('', $rows)
                . '</table>';
        }, $html);
        $guard++;
    }

    // Limpieza defensiva si quedaran etiquetas sueltas.
    $html = preg_replace('~</?(ul|ol|li)\b[^>]*>~i', '', $html);
    return $html;
}

function rsu_file_name(string $raw): string {
    $parts = preg_split('/[?#]/', $raw, 2);
    $name = basename($parts[0] ?? $raw);
    return $name !== '' ? $name : $raw;
}

function rsu_file_style(string $ext): array {
    $ext = strtolower($ext);
    if ($ext === 'pdf') return ['#E53935', '#FFFFFF', 'Ver PDF'];
    if (in_array($ext, ['xls', 'xlsx', 'csv'], true)) return ['#1E7E34', '#FFFFFF', 'Descargar'];
    if (in_array($ext, ['doc', 'docx'], true)) return ['#185ABD', '#FFFFFF', 'Descargar'];
    return ['#6C757D', '#FFFFFF', 'Descargar'];
}

function rsu_request_scheme(): string {
    $forwarded = trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
    if ($forwarded !== '') {
        $parts = explode(',', $forwarded);
        $proto = strtolower(trim((string)$parts[0]));
        if ($proto === 'https' || $proto === 'http') return $proto;
    }
    $https = strtolower((string)($_SERVER['HTTPS'] ?? ''));
    if ($https !== '' && $https !== 'off' && $https !== '0') return 'https';
    $port = (int)($_SERVER['SERVER_PORT'] ?? 0);
    if ($port === 443) return 'https';
    return 'http';
}

function rsu_request_host(): string {
    $forwardedHost = trim((string)($_SERVER['HTTP_X_FORWARDED_HOST'] ?? ''));
    if ($forwardedHost !== '') {
        $parts = explode(',', $forwardedHost);
        $host = trim((string)$parts[0]);
        if ($host !== '') return $host;
    }
    $host = trim((string)($_SERVER['HTTP_HOST'] ?? ''));
    if ($host !== '') return $host;
    $name = trim((string)($_SERVER['SERVER_NAME'] ?? 'localhost'));
    $port = (int)($_SERVER['SERVER_PORT'] ?? 0);
    if ($port > 0 && $port !== 80 && $port !== 443) return $name . ':' . $port;
    return $name;
}

function rsu_absolute_from_path(string $path): string {
    $path = '/' . ltrim($path, '/');
    return rsu_request_scheme() . '://' . rsu_request_host() . $path;
}

function rsu_public_url(string $raw): ?string {
    $raw = trim($raw);
    if ($raw === '') return null;
    if (preg_match('~^https?://~i', $raw)) return $raw;

    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $appBasePath = rtrim(dirname(dirname($scriptName)), '/');
    if ($appBasePath === '' || $appBasePath === '.') {
        $appBasePath = '/sistema_web';
    }
    $webFilesBase = $appBasePath . '/files_answer/';

    $norm = str_replace('\\', '/', $raw);
    if (strpos($norm, '/') === 0) {
        $posLeading = stripos($norm, 'files_answer');
        $expectedPrefix = rtrim($appBasePath, '/') . '/files_answer/';
        if ($posLeading !== false && stripos($norm, $expectedPrefix) === false) {
            // Corrige rutas tipo /files_answer/... agregando el prefijo real del despliegue.
            $rel = ltrim(substr($norm, $posLeading + strlen('files_answer')), '/');
            $segments = array_map('rawurlencode', array_filter(explode('/', $rel), 'strlen'));
            $path = rtrim($webFilesBase, '/') . '/' . implode('/', $segments);
            return rsu_absolute_from_path($path);
        }
        return rsu_absolute_from_path($norm);
    }

    $pos = stripos($norm, 'files_answer');
    if ($pos !== false) {
        $rel = ltrim(substr($norm, $pos + strlen('files_answer')), '/');
        $segments = array_map('rawurlencode', array_filter(explode('/', $rel), 'strlen'));
        $path = rtrim($webFilesBase, '/') . '/' . implode('/', $segments);
        return rsu_absolute_from_path($path);
    }
    $base = basename($norm);
    if ($base === '' || $base === '.' || $base === '..') return null;
    $path = rtrim($webFilesBase, '/') . '/' . rawurlencode($base);
    return rsu_absolute_from_path($path);
}

function rsu_extract_drive_links(string $htmlOrText): array {
    $found = [];
    if (preg_match_all('~https?://drive\.google\.com[^\s"<>\']+~i', $htmlOrText, $m)) {
        $found = array_merge($found, $m[0]);
    }
    if (preg_match_all('~href=[\'"]([^\'"]*drive\.google\.com[^\'"]*)[\'"]~i', $htmlOrText, $m2)) {
        $found = array_merge($found, $m2[1]);
    }
    return array_values(array_unique(array_map('trim', $found)));
}

function rsu_strip_drive_links(string $html): string {
    $html = preg_replace('~<a[^>]+href=[\'"]https?://drive\.google\.com[^\'"]*[\'"][^>]*>.*?</a>~is', '', $html);
    $html = preg_replace('~https?://drive\.google\.com[^\s"<>\']+~i', '', $html);
    return preg_replace('/[ \t]{2,}/', ' ', $html ?? '');
}

function rsu_render_archivos_table(string $rawList): string {
    $rawList = trim($rawList);
    if ($rawList === '') return '<span style="color:#6c757d;">No hay archivo</span>';

    $parts = preg_split('/[\r\n,;]+/', $rawList, -1, PREG_SPLIT_NO_EMPTY);
    if (!$parts) return '<span style="color:#6c757d;">No hay archivo</span>';

    $rows = [];
    foreach ($parts as $raw) {
        $raw = trim($raw);
        if ($raw === '') continue;
        $url = rsu_public_url($raw);
        if (!$url) continue;
        $path = parse_url($url, PHP_URL_PATH) ?? '';
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));
        [$bg, $fg, $label] = rsu_file_style($ext);
        $name = rsu_h(rsu_file_name($raw));
        $safeUrl = rsu_h($url);

        $rows[] = ''
            . '<tr>'
            . '<td style="border:1px solid #d8d8d8;padding:6px;">' . $name . '</td>'
            . '<td style="border:1px solid #d8d8d8;padding:6px;text-align:center;background-color:' . $bg . ';color:' . $fg . ';">'
            . '<a href="' . $safeUrl . '" style="color:' . $fg . ';text-decoration:none;font-weight:bold;">' . rsu_h($label) . '</a>'
            . '</td>'
            . '</tr>';
    }

    if (empty($rows)) return '<span style="color:#6c757d;">No hay archivo</span>';

    return ''
        . '<table cellspacing="0" cellpadding="0" border="0" width="100%">'
        . '<tr>'
        . '<td style="border:1px solid #d8d8d8;padding:6px;background-color:#f3f4f6;font-weight:bold;">Archivo</td>'
        . '<td style="border:1px solid #d8d8d8;padding:6px;background-color:#f3f4f6;font-weight:bold;text-align:center;width:140px;">Accion</td>'
        . '</tr>'
        . implode('', $rows)
        . '</table>';
}

function rsu_render_drive_table(array $urls): string {
    if (empty($urls)) return '';
    $rows = [];
    foreach ($urls as $u) {
        $u = trim($u);
        if ($u === '') continue;
        $fullUrl = rsu_public_url($u);
        if (!$fullUrl) continue;
        $safe = rsu_h($fullUrl);
        $rows[] = ''
            . '<tr>'
            . '<td style="border:1px solid #d8d8d8;padding:6px;">' . $safe . '</td>'
            . '<td style="border:1px solid #d8d8d8;padding:6px;text-align:center;background-color:#FFEB3B;color:#111;">'
            . '<a href="' . $safe . '" style="color:#111;text-decoration:none;font-weight:bold;">Abrir drive</a>'
            . '</td>'
            . '</tr>';
    }
    if (empty($rows)) return '';
    return ''
        . '<table cellspacing="0" cellpadding="0" border="0" width="100%" style="margin-top:6px;">'
        . '<tr>'
        . '<td style="border:1px solid #d8d8d8;padding:6px;background-color:#fff8c7;font-weight:bold;">Enlace</td>'
        . '<td style="border:1px solid #d8d8d8;padding:6px;background-color:#fff8c7;font-weight:bold;text-align:center;width:140px;">Accion</td>'
        . '</tr>'
        . implode('', $rows)
        . '</table>';
}

function rsu_chip(string $txt, ?string $bg = null, ?string $fg = null): string {
    $bg = $bg ?: '#6c757d';
    $fg = $fg ?: '#ffffff';
    return '<span style="background-color:' . rsu_h($bg) . ';color:' . rsu_h($fg) . ';padding:3px 6px;font-size:9pt;font-family:Arial,Helvetica,sans-serif;font-weight:bold;">' . rsu_h($txt) . '</span>';
}

$ODS_STYLES = [
    1 => ['#E5243B', '#ffffff'], 2 => ['#DDA63A', '#111111'], 3 => ['#4C9F38', '#ffffff'], 4 => ['#C5192D', '#ffffff'],
    5 => ['#FF3A21', '#ffffff'], 6 => ['#26BDE2', '#111111'], 7 => ['#FCC30B', '#111111'], 8 => ['#A21942', '#ffffff'],
    9 => ['#FD6925', '#111111'], 10 => ['#DD1367', '#ffffff'], 11 => ['#FD9D24', '#111111'], 12 => ['#BF8B2E', '#111111'],
    13 => ['#3F7E44', '#ffffff'], 14 => ['#0A97D9', '#ffffff'], 15 => ['#56C02B', '#111111'], 16 => ['#00689D', '#ffffff'],
    17 => ['#19486A', '#ffffff']
];

function rsu_render_valor(array $row, array $ODS, array $PROGS, array $PROG_ODS, array $ODS_STYLES): string {
    $tipo = (string)($row['tipo'] ?? '');

    switch ($tipo) {
        case 'varchar':
            return rsu_h($row['val_varchar'] ?? '');

        case 'longtext':
            $txt = trim((string)($row['val_longtext'] ?? ''));
            if ($txt === '') return '<span style="color:#6c757d;">Sin respuesta</span>';
            $txt = preg_replace('~<script\b[^>]*>.*?</script>~is', '', $txt);
            $txt = preg_replace('~<style\b[^>]*>.*?</style>~is', '', $txt);
            $txt = rsu_normalize_richtext_typography($txt);
            $txt = rsu_normalize_lists_for_tcpdf($txt);
            $links = rsu_extract_drive_links($txt);
            if (!empty($links)) {
                $clean = rsu_strip_drive_links($txt);
                return $clean . rsu_render_drive_table($links);
            }
            return $txt;

        case 'tinyint':
            return ($row['val_tinyint'] !== null) ? rsu_h($row['val_tinyint']) : '<span style="color:#6c757d;">-</span>';

        case 'int':
            return ($row['val_int'] !== null) ? rsu_h($row['val_int']) : '<span style="color:#6c757d;">-</span>';

        case 'boolean':
            if ($row['val_boolean'] === null) return '<span style="color:#6c757d;">-</span>';
            return ((int)$row['val_boolean'] === 1) ? 'Si' : 'No';

        case 'datetime':
            return !empty($row['val_datetime']) ? rsu_h($row['val_datetime']) : '<span style="color:#6c757d;">-</span>';

        case 'date':
            return !empty($row['val_date']) ? rsu_h($row['val_date']) : '<span style="color:#6c757d;">-</span>';

        case 'decimal':
            return ($row['val_decimal'] !== null) ? rsu_h($row['val_decimal']) : '<span style="color:#6c757d;">-</span>';

        case 'ubicacion':
            if (!empty($row['val_longtext'])) return rsu_plain($row['val_longtext']);
            if (!empty($row['val_varchar'])) return rsu_h($row['val_varchar']);
            return '<span style="color:#6c757d;">-</span>';

        case 'pdf':
        case 'excel':
        case 'word':
            return rsu_render_archivos_table((string)($row['archivo_url'] ?? ''));

        case 'ods':
            $csv = trim((string)($row['val_varchar'] ?? ''));
            if ($csv === '') return '<span style="color:#6c757d;">-</span>';
            $ids = array_filter(array_map('intval', explode(',', $csv)));
            if (!$ids) return '<span style="color:#6c757d;">-</span>';
            $chips = [];
            foreach ($ids as $id) {
                $nom = $ODS[$id] ?? ('ODS ' . $id);
                [$bg, $fg] = $ODS_STYLES[$id] ?? [null, null];
                $chips[] = rsu_chip('ODS ' . $id . ' - ' . $nom, $bg, $fg);
            }
            return implode(' ', $chips);

        case 'programa_ods':
            $pid = (int)trim((string)($row['val_varchar'] ?? '0'));
            if ($pid <= 0) return '<span style="color:#6c757d;">-</span>';
            $pnom = $PROGS[$pid] ?? ('Programa #' . $pid);
            $odsArr = $PROG_ODS[$pid] ?? [];
            if (empty($odsArr)) {
                return '<span style="color:#6c757d;">Sin ODS asociados</span>';
            }
            $rows = [];
            foreach ($odsArr as $o) {
                $id = (int)$o['id'];
                $nom = (string)$o['nombre'];
                [$bg, $fg] = $ODS_STYLES[$id] ?? [null, null];
                $rows[] = ''
                    . '<tr>'
                    . '<td style="border:1px solid #d8d8d8;padding:6px;">' . rsu_chip('ODS ' . $id . ' - ' . $nom, $bg, $fg) . '</td>'
                    . '<td style="border:1px solid #d8d8d8;padding:6px;">' . rsu_h($pnom) . '</td>'
                    . '</tr>';
            }
            return ''
                . '<table cellspacing="0" cellpadding="0" border="0" width="100%">'
                . '<tr>'
                . '<td style="border:1px solid #111;padding:6px;background-color:#111;color:#fff;font-weight:bold;">OBJETIVO DE DESARROLLO SOSTENIBLE</td>'
                . '<td style="border:1px solid #111;padding:6px;background-color:#111;color:#fff;font-weight:bold;">PROGRAMA PRIORIZADO</td>'
                . '</tr>'
                . implode('', $rows)
                . '</table>';

        default:
            foreach (['val_varchar', 'val_longtext', 'val_int', 'val_tinyint', 'val_boolean', 'val_datetime', 'val_date', 'val_decimal', 'archivo_url'] as $k) {
                if (isset($row[$k]) && $row[$k] !== null && $row[$k] !== '') {
                    return rsu_plain((string)$row[$k]);
                }
            }
            return '<span style="color:#6c757d;">Sin respuesta</span>';
    }
}

$proy = ['titulo' => '', 'coordinador' => '', 'cod_docente' => ''];
$sqlProy = "
  SELECT p.p2 AS titulo, u.nombres, u.apellidos, u.usuario AS cod_docente
  FROM usuarios_proyectos up
  JOIN proyectos p ON p.id = up.id_proyecto
  JOIN usuarios u ON u.id = up.id_usuario
  WHERE up.id_proyecto = $id_py AND up.activo = 1
  LIMIT 1
";
if ($rs = mysqli_query($conexion, $sqlProy)) {
    if ($row = mysqli_fetch_assoc($rs)) {
        $proy['titulo'] = (string)($row['titulo'] ?? '');
        $proy['coordinador'] = trim((string)($row['nombres'] ?? '') . ' ' . (string)($row['apellidos'] ?? ''));
        $proy['cod_docente'] = (string)($row['cod_docente'] ?? '');
    }
    mysqli_free_result($rs);
}

$periodo = 'SIN_PERIODO';
if ($id_respuesta > 0) {
    $sqlPeriodo = "
      SELECT CONCAT(s.anio, '-', s.periodo) AS periodo
      FROM sm_respuestas r
      JOIN sm_proyecto_semestres s ON s.id = r.id_semestre
      WHERE r.id = $id_respuesta
      LIMIT 1
    ";
} else {
    $sqlPeriodo = "
      SELECT CONCAT(s.anio, '-', s.periodo) AS periodo
      FROM sm_respuestas r
      JOIN sm_proyecto_semestres s ON s.id = r.id_semestre
      WHERE r.id_py = $id_py
        AND s.tipo = 'semestral'
      ORDER BY r.actualizado_at DESC, r.id DESC
      LIMIT 1
    ";
}
if ($rsP = mysqli_query($conexion, $sqlPeriodo)) {
    if ($row = mysqli_fetch_assoc($rsP)) {
        $periodo = (string)($row['periodo'] ?? 'SIN_PERIODO');
    }
    mysqli_free_result($rsP);
}

$filename = rsu_slug_pdf($proy['coordinador'] ?: 'DOCENTE')
    . '_' . rsu_slug_pdf($proy['cod_docente'] ?: 'SIN_CODIGO')
    . '_' . strtoupper(preg_replace('/[^A-Za-z0-9\-]/', '', str_replace(' ', '', $periodo)))
    . '.pdf';

$ODS = [];
$PROGS = [];
$PROG_ODS = [];

if ($q = mysqli_query($conexion, "SELECT id, nombre FROM ods ORDER BY id")) {
    while ($r = mysqli_fetch_assoc($q)) $ODS[(int)$r['id']] = (string)$r['nombre'];
    mysqli_free_result($q);
}
if ($q = mysqli_query($conexion, "SELECT id, nombre FROM programas WHERE activo = 1 ORDER BY nombre")) {
    while ($r = mysqli_fetch_assoc($q)) $PROGS[(int)$r['id']] = (string)$r['nombre'];
    mysqli_free_result($q);
}
if ($q = mysqli_query($conexion, "SELECT po.programa_id, o.id AS ods_id, o.nombre AS ods_nombre FROM programa_ods po JOIN ods o ON o.id = po.ods_id ORDER BY po.programa_id, o.id")) {
    while ($r = mysqli_fetch_assoc($q)) {
        $pid = (int)$r['programa_id'];
        if (!isset($PROG_ODS[$pid])) $PROG_ODS[$pid] = [];
        $PROG_ODS[$pid][] = ['id' => (int)$r['ods_id'], 'nombre' => (string)$r['ods_nombre']];
    }
    mysqli_free_result($q);
}

$cabs = [];
$sqlCab = "
  SELECT r.id, r.id_formulario, f.nombre AS formulario
  FROM sm_respuestas r
  JOIN sm_formularios f ON f.id = r.id_formulario
  WHERE r.id_py = $id_py
  " . ($id_respuesta > 0 ? " AND r.id = $id_respuesta " : "") . "
  ORDER BY f.nombre ASC, r.id ASC
";
if ($rs = mysqli_query($conexion, $sqlCab)) {
    while ($r = mysqli_fetch_assoc($rs)) $cabs[] = $r;
    mysqli_free_result($rs);
}

$html = '';
$html .= '<style>
* {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt !important;
}
body, td, th, div, p, span, a, li, b, strong, em, i {
  font-family: Arial, Helvetica, sans-serif;
  font-size: 9pt !important;
  color: #111111;
}
.head-box { border: 1px solid #d8d8d8; background-color: #f5f7fa; padding: 8px; }
.form-box { border: 1px solid #d8d8d8; margin-top: 10px; }
.form-head { background-color: #e9f4ff; border-bottom: 1px solid #d8d8d8; padding: 7px; font-weight: bold; color: #0d6efd; }
.item-block { border-bottom: 1px dashed #d8d8d8; padding: 8px; }
.item-title { font-size: 9pt; font-weight: bold; color: #0d6efd; margin-bottom: 4px; }
.item-type { color: #6b7280; font-size: 9pt; }
.item-body { color: #111111; line-height: 1.35; }
</style>';

$html .= '<div class="head-box">';
$html .= '<div><b>Proyecto:</b> ' . rsu_h($proy['titulo'] ?: '-') . '</div>';
$html .= '<div><b>Coordinador:</b> ' . rsu_h($proy['coordinador'] ?: '-') . ' <span style="color:#6b7280;">(' . rsu_h($proy['cod_docente'] ?: '-') . ')</span></div>';
$html .= '<div><b>Periodo:</b> ' . rsu_h($periodo ?: '-') . '</div>';
$html .= '</div>';

if (empty($cabs)) {
    $html .= '<div style="margin-top:10px;color:#856404;background-color:#fff3cd;border:1px solid #ffeeba;padding:8px;">Este proyecto aun no tiene respuestas registradas.</div>';
} else {
    foreach ($cabs as $cab) {
        $rid = (int)$cab['id'];
        $fid = (int)$cab['id_formulario'];
        $items = [];
        $sqlItems = "
          SELECT
            fi.orden,
            i.id AS id_item,
            i.nombre AS item_nombre,
            i.tipo,
            ri.val_varchar, ri.val_longtext, ri.val_tinyint, ri.val_int, ri.val_boolean,
            ri.val_datetime, ri.val_date, ri.val_decimal, ri.archivo_url
          FROM sm_formulario_items fi
          JOIN sm_items i ON i.id = fi.id_item
          LEFT JOIN sm_respuesta_items ri
            ON ri.id_respuesta = $rid AND ri.id_item = i.id
          WHERE fi.id_formulario = $fid AND fi.activo = 1
          ORDER BY fi.orden ASC
        ";
        if ($rsi = mysqli_query($conexion, $sqlItems)) {
            while ($row = mysqli_fetch_assoc($rsi)) $items[] = $row;
            mysqli_free_result($rsi);
        }

        $html .= '<div class="form-box">';
        $html .= '<div class="form-head">Formulario: ' . rsu_h($cab['formulario']) . ' <span style="color:#6b7280;font-weight:normal;">(Resp. #' . $rid . ')</span></div>';

        if (empty($items)) {
            $html .= '<div class="item-block"><span style="color:#6c757d;">Este formulario no tiene items activos.</span></div>';
        } else {
            foreach ($items as $it) {
                $valor = rsu_render_valor($it, $ODS, $PROGS, $PROG_ODS, $ODS_STYLES);
                $html .= '<div class="item-block">';
                $html .= '<div class="item-title">' . (int)$it['orden'] . '. ' . rsu_h($it['item_nombre'] ?? '') . ' <span class="item-type">(' . rsu_h($it['tipo'] ?? '') . ')</span></div>';
                $html .= '<div class="item-body">' . $valor . '</div>';
                $html .= '</div>';
            }
        }
        $html .= '</div>';
    }
}

$pdf = new TCPDF('P', 'mm', 'A4', true, 'UTF-8', false);
$pdf->SetCreator('Sistema DIRSU');
$pdf->SetAuthor('Sistema DIRSU');
$pdf->SetTitle('Informe semestral');
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->SetMargins(10, 10, 10);
$pdf->SetAutoPageBreak(true, 10);
$pdf->SetFont('helvetica', '', 9, '', true);
$pdf->AddPage();
$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Output($filename, 'D');
exit;
