<?php
header('Content-Type: application/json; charset=utf-8');

include "../componentes/configSesion.php";

function rr_json_exit($ok, $msg, $data = null, $http = 200)
{
    if (!headers_sent()) {
        http_response_code((int)$http);
    }
    $out = array(
        'ok' => (bool)$ok,
        'msg' => (string)$msg,
    );
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function rr_role_code($roleId)
{
    $role = (int)$roleId;
    if ($role === 5) return 'pcf';
    if ($role === 4) return 'dd';
    if ($role === 3) return 'df';
    if ($role === 1) return 'rsu';
    return '';
}

function rr_scan_latest_file($dir, array $allowedExt)
{
    if (!is_dir($dir)) return null;
    $entries = @scandir($dir);
    if (!is_array($entries)) return null;

    $rows = array();
    foreach ($entries as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $entry;
        if (!is_file($path)) continue;
        $ext = strtolower((string)pathinfo($entry, PATHINFO_EXTENSION));
        if (!in_array($ext, $allowedExt, true)) continue;
        $mtime = @filemtime($path);
        $rows[] = array(
            'name' => $entry,
            'path' => $path,
            'mtime' => ($mtime === false ? 0 : (int)$mtime),
        );
    }
    if (empty($rows)) return null;

    usort($rows, function ($a, $b) {
        $am = (int)$a['mtime'];
        $bm = (int)$b['mtime'];
        if ($am > 0 && $bm > 0 && $am !== $bm) {
            return ($bm <=> $am);
        }
        return strnatcasecmp((string)$a['name'], (string)$b['name']);
    });
    return $rows[0];
}

function rr_resolve_resource($resourceKey, $roleId)
{
    $base = realpath(__DIR__ . '/../sources_rsu');
    if ($base === false) return array('ok' => false, 'msg' => 'Carpeta base no disponible.');

    $key = strtolower(trim((string)$resourceKey));
    $roleCode = rr_role_code($roleId);
    $sub = '';
    $exts = array();
    $type = '';

    if ($key === 'pdf_cotejo') {
        $sub = 'cotejo_informe';
        $exts = array('pdf');
        $type = 'pdf';
    } elseif ($key === 'pdf_rubrica') {
        $sub = 'rubrica_informe';
        $exts = array('pdf');
        $type = 'pdf';
    } elseif ($key === 'doc_anexo08') {
        $sub = 'anexo_08_informe';
        $exts = array('docx', 'doc', 'odt', 'rtf');
        $type = 'download';
    } elseif ($key === 'video_calificar') {
        if ($roleCode === '') {
            return array('ok' => false, 'msg' => 'Rol sin recurso de video.');
        }
        $sub = $roleCode . '/video_calificar_informe';
        $exts = array('mp4', 'webm', 'ogg');
        $type = 'video';
    } elseif ($key === 'video_coord_revision_subsanacion') {
        if ((int)$roleId !== 2) {
            return array('ok' => false, 'msg' => 'Recurso no disponible para este rol.');
        }
        $sub = 'coordinador/video_solicitar_revision_subsanacion';
        $exts = array('mp4', 'webm', 'ogg');
        $type = 'video';
    } else {
        return array('ok' => false, 'msg' => 'Recurso no reconocido.');
    }

    $targetDir = realpath($base . DIRECTORY_SEPARATOR . $sub);
    if ($targetDir === false || strpos($targetDir, $base) !== 0) {
        return array('ok' => false, 'msg' => 'Carpeta del recurso no disponible.');
    }

    $file = rr_scan_latest_file($targetDir, $exts);
    if (!$file) {
        return array('ok' => false, 'msg' => 'No se encontró el recurso consulte a RSU');
    }

    return array(
        'ok' => true,
        'type' => $type,
        'path' => $file['path'],
        'name' => $file['name'],
    );
}

function rr_mime_by_ext($name)
{
    $ext = strtolower((string)pathinfo((string)$name, PATHINFO_EXTENSION));
    if ($ext === 'pdf') return 'application/pdf';
    if ($ext === 'mp4') return 'video/mp4';
    if ($ext === 'webm') return 'video/webm';
    if ($ext === 'ogg') return 'video/ogg';
    if ($ext === 'doc') return 'application/msword';
    if ($ext === 'docx') return 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';
    if ($ext === 'odt') return 'application/vnd.oasis.opendocument.text';
    if ($ext === 'rtf') return 'application/rtf';
    return 'application/octet-stream';
}

$roleId = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
if ($roleId <= 0) {
    rr_json_exit(false, 'Sesión no válida.', null, 401);
}

$action = isset($_GET['action']) ? strtolower(trim((string)$_GET['action'])) : 'get';
$resource = isset($_GET['resource']) ? (string)$_GET['resource'] : '';
if ($resource === '') {
    rr_json_exit(false, 'Parámetro resource requerido.', null, 422);
}

$resolved = rr_resolve_resource($resource, $roleId);
if (empty($resolved['ok'])) {
    rr_json_exit(false, isset($resolved['msg']) ? $resolved['msg'] : 'No se pudo resolver el recurso.', null, 404);
}

$path = (string)$resolved['path'];
$name = (string)$resolved['name'];
$type = (string)$resolved['type'];

if ($action === 'stream') {
    if (!is_file($path) || !is_readable($path)) {
        rr_json_exit(false, 'Archivo no disponible.', null, 404);
    }

    $mime = rr_mime_by_ext($name);
    if (!headers_sent()) {
        header_remove('Content-Type');
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . (string)filesize($path));
        $dispType = ($type === 'download') ? 'attachment' : 'inline';
        header('Content-Disposition: ' . $dispType . '; filename="' . rawurlencode($name) . '"');
        header('X-Content-Type-Options: nosniff');
        header('Accept-Ranges: bytes');
    }
    @readfile($path);
    exit;
}

$url = 'api_recursos_rsu.php?action=stream&resource=' . rawurlencode($resource);
rr_json_exit(true, 'OK', array(
    'resource' => $resource,
    'type' => $type,
    'name' => $name,
    'url' => $url,
));
