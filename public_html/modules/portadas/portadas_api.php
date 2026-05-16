<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/gestion_archivos.php';

date_default_timezone_set('America/Lima');
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function json_out(bool $ok, array $data = [], int $status = 200): void {
    http_response_code($status);
    echo json_encode(array_merge(['ok' => $ok], $data), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function req_str(string $key): string {
    $v = $_REQUEST[$key] ?? '';
    return is_string($v) ? trim($v) : '';
}

function clean_page_key(string $s): string {
    return preg_match('/^[a-z0-9\-_]+$/i', $s) === 1 ? $s : '';
}

function can_manage_header(): bool {
    $u = auth_user();
    if (!$u || !isset($u['rol']['codigo'])) return false;
    $code = (string)$u['rol']['codigo'];
    return in_array($code, ['desarrollador', 'director', 'secretaria'], true);
}

function upload_error_msg(int $code): string {
    $limitUpload = (string)ini_get('upload_max_filesize');
    $limitPost = (string)ini_get('post_max_size');
    $limitInfo = trim($limitUpload) !== '' || trim($limitPost) !== ''
        ? " Limites del servidor: upload_max_filesize={$limitUpload}, post_max_size={$limitPost}."
        : '';
    if ($code === UPLOAD_ERR_OK) return '';
    if ($code === UPLOAD_ERR_INI_SIZE) return 'La imagen excede el limite permitido por el servidor.' . $limitInfo;
    if ($code === UPLOAD_ERR_FORM_SIZE) return 'La imagen excede el limite permitido por el formulario.';
    if ($code === UPLOAD_ERR_PARTIAL) return 'El archivo se subio parcialmente.';
    if ($code === UPLOAD_ERR_NO_FILE) return 'No se selecciono archivo.';
    if ($code === UPLOAD_ERR_NO_TMP_DIR) return 'Falta la carpeta temporal del servidor.';
    if ($code === UPLOAD_ERR_CANT_WRITE) return 'No se pudo escribir el archivo en disco.';
    if ($code === UPLOAD_ERR_EXTENSION) return 'Subida bloqueada por extension del servidor.';
    return 'Error al subir archivo.';
}

function detect_image_mime(string $tmpPath): ?string {
    $mime = null;
    if (function_exists('finfo_open')) {
        $fi = finfo_open(FILEINFO_MIME_TYPE);
        if ($fi !== false) {
            $m = finfo_file($fi, $tmpPath);
            finfo_close($fi);
            if (is_string($m) && trim($m) !== '') $mime = trim($m);
        }
    }
    if (!$mime && function_exists('mime_content_type')) {
        $m = @mime_content_type($tmpPath);
        if (is_string($m) && trim($m) !== '') $mime = trim($m);
    }
    return $mime;
}

function normalize_file_extension_by_mime(array &$file, string $mime): void {
    $map = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'image/bmp' => 'bmp',
        'image/tiff' => 'tiff',
        'image/svg+xml' => 'svg',
        'image/x-icon' => 'ico',
        'image/heic' => 'heic',
        'image/heif' => 'heif',
        'image/avif' => 'avif',
    ];
    $name = (string)($file['name'] ?? '');
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if ($ext !== '') return;
    $mimeExt = $map[$mime] ?? '';
    if ($mimeExt === '' && strpos($mime, 'image/') === 0) {
        $raw = strtolower(substr($mime, 6));
        $raw = preg_replace('/[^a-z0-9]+/', '', $raw ?? '');
        $mimeExt = $raw !== '' ? $raw : 'img';
    }
    if ($mimeExt === '') return;
    $base = trim(pathinfo($name, PATHINFO_FILENAME));
    if ($base === '') $base = 'portada';
    $file['name'] = $base . '.' . $mimeExt;
}

function fetch_portada(mysqli $mysqli, string $pageKey): ?array {
    $sql = "SELECT id, page_key, imagen_portada, descripcion, creado_por, actualizado_por,
                   DATE_FORMAT(creado_en, '%Y-%m-%d %H:%i:%s') AS creado_en,
                   DATE_FORMAT(actualizado_en, '%Y-%m-%d %H:%i:%s') AS actualizado_en
            FROM l2601_portadas_paginas
            WHERE page_key = ?
            LIMIT 1";
    $st = $mysqli->prepare($sql);
    $st->bind_param('s', $pageKey);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function log_portada(mysqli $mysqli, string $pageKey, string $accion, ?int $usuarioId, ?array $snapshot): void {
    try {
        $snapshotJson = $snapshot ? json_encode($snapshot, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
        $sql = "INSERT INTO l2601_portadas_paginas_log (page_key, accion, usuario_id, snapshot_json)
                VALUES (?,?,?,?)";
        $st = $mysqli->prepare($sql);
        $st->bind_param('ssis', $pageKey, $accion, $usuarioId, $snapshotJson);
        $st->execute();
        $st->close();
    } catch (Throwable $e) {
        // Keep feature working if log table is not available yet.
    }
}

$action = req_str('action');
$pageKey = clean_page_key(req_str('page_key'));
if ($pageKey === '') {
    json_out(false, ['error' => 'page_key inválido.'], 400);
}

$mysqli = db();

if ($action === 'get') {
    try {
        $row = fetch_portada($mysqli, $pageKey);
        json_out(true, ['item' => $row]);
    } catch (Throwable $e) {
        json_out(false, ['error' => 'No se pudo consultar la portada.'], 500);
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, ['error' => 'Método no permitido.'], 405);
}

if (!auth_check() || !can_manage_header()) {
    json_out(false, ['error' => 'No autorizado.'], 403);
}

if (!csrf_validate($_POST['csrf'] ?? null)) {
    json_out(false, ['error' => 'CSRF inválido. Recarga la página e intenta otra vez.'], 400);
}

$user = auth_user();
$userId = $user ? (int)$user['id'] : null;

if ($action === 'clear_image') {
    try {
        $prev = fetch_portada($mysqli, $pageKey);
        if (!$prev) {
            $sqlI = "INSERT INTO l2601_portadas_paginas (page_key, imagen_portada, descripcion, creado_por, actualizado_por)
                     VALUES (?, NULL, NULL, ?, ?)";
            $st = $mysqli->prepare($sqlI);
            $st->bind_param('sii', $pageKey, $userId, $userId);
            $st->execute();
            $st->close();
        } else {
            if (!empty($prev['imagen_portada'])) {
                ga_delete_rel((string)$prev['imagen_portada']);
            }
            $sqlU = "UPDATE l2601_portadas_paginas
                     SET imagen_portada = NULL, actualizado_por = ?
                     WHERE page_key = ?
                     LIMIT 1";
            $st = $mysqli->prepare($sqlU);
            $st->bind_param('is', $userId, $pageKey);
            $st->execute();
            $st->close();
        }

        $after = fetch_portada($mysqli, $pageKey);
        log_portada($mysqli, $pageKey, 'clear_image', $userId, ['before' => $prev, 'after' => $after]);
        json_out(true, ['msg' => 'Imagen restablecida a la predeterminada.', 'item' => $after]);
    } catch (Throwable $e) {
        json_out(false, ['error' => 'No se pudo restablecer la imagen.'], 500);
    }
}

if ($action !== 'save') {
    json_out(false, ['error' => 'Acción no válida.'], 400);
}

$descripcion = trim((string)($_POST['descripcion'] ?? ''));
if ($descripcion === '' || $descripcion === 'Descripción pendiente.') {
    $descripcion = null;
} elseif (mb_strlen($descripcion) > 500) {
    json_out(false, ['error' => 'La descripción supera 500 caracteres.'], 400);
}

$hasUpload = isset($_FILES['foto_portada']) && is_array($_FILES['foto_portada']) &&
             (int)($_FILES['foto_portada']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

$newImageRel = null;
if ($hasUpload) {
    $err = (int)($_FILES['foto_portada']['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err !== UPLOAD_ERR_OK) {
        json_out(false, ['error' => upload_error_msg($err)], 400);
    }

    $size = (int)($_FILES['foto_portada']['size'] ?? 0);
    if ($size <= 0) {
        json_out(false, ['error' => 'La imagen seleccionada está vacía.'], 400);
    }

    $tmpPath = (string)($_FILES['foto_portada']['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        json_out(false, ['error' => 'No se pudo leer el archivo subido. Intenta nuevamente.'], 400);
    }

    $mime = detect_image_mime($tmpPath);
    if (!is_string($mime) || stripos($mime, 'image/') !== 0) {
        json_out(false, ['error' => 'El archivo seleccionado no es una imagen valida.'], 400);
    }

    normalize_file_extension_by_mime($_FILES['foto_portada'], strtolower($mime));
}

try {
    $before = fetch_portada($mysqli, $pageKey);

    if ($hasUpload) {
        $up = ga_save_upload($mysqli, $_FILES['foto_portada'], 'foto_portada', 'portada', 'portadas', 'pagina', $pageKey);
        $newImageRel = (string)$up['ruta_relativa'];
    }

    if (!$before) {
        $imgToSave = $newImageRel !== null ? $newImageRel : null;
        $sqlI = "INSERT INTO l2601_portadas_paginas (page_key, imagen_portada, descripcion, creado_por, actualizado_por)
                 VALUES (?,?,?,?,?)";
        $st = $mysqli->prepare($sqlI);
        $st->bind_param('sssii', $pageKey, $imgToSave, $descripcion, $userId, $userId);
        $st->execute();
        $st->close();
    } else {
        $imgToSave = $newImageRel !== null ? $newImageRel : (isset($before['imagen_portada']) ? (string)$before['imagen_portada'] : null);
        $sqlU = "UPDATE l2601_portadas_paginas
                 SET imagen_portada = ?, descripcion = ?, actualizado_por = ?
                 WHERE page_key = ?
                 LIMIT 1";
        $st = $mysqli->prepare($sqlU);
        $st->bind_param('ssis', $imgToSave, $descripcion, $userId, $pageKey);
        $st->execute();
        $st->close();

        if ($newImageRel !== null && !empty($before['imagen_portada']) && (string)$before['imagen_portada'] !== $newImageRel) {
            ga_delete_rel((string)$before['imagen_portada']);
        }
    }

    $after = fetch_portada($mysqli, $pageKey);
    log_portada($mysqli, $pageKey, 'save', $userId, ['before' => $before, 'after' => $after]);
    json_out(true, ['msg' => 'Portada actualizada.', 'item' => $after]);
} catch (Throwable $e) {
    if ($newImageRel !== null) {
        ga_delete_rel($newImageRel);
    }
    $msg = trim($e->getMessage()) !== '' ? $e->getMessage() : 'Error interno al guardar.';
    json_out(false, ['error' => 'No se pudo guardar la portada: ' . $msg], 500);
}
