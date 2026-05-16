<?php
// includes/gestion_archivos.php
// Guarda archivos en: almacen/AAAA/MM/DD/<categoria>/NOMBRE.ext
// Rutas RELATIVAS: "almacen/2026/01/27/foto_perfil/archivo.jpg"

// Bloquear acceso directo
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

date_default_timezone_set('America/Lima');

function ga_storage_rel_base() {
    return 'almacen';
}

// Raíz del proyecto web (carpeta donde está index.php)
function ga_project_root() {
    // /web/includes -> /web
    return dirname(__DIR__);
}

function ga_abs_from_rel($rutaRel) {
    $rel = ltrim((string)$rutaRel, "/\\");
    return rtrim(ga_project_root(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;
}

function ga_slugify($s) {
    $s = strtolower((string)$s);
    $s = preg_replace('/[^a-z0-9\-\_\.]+/', '-', $s);
    $s = preg_replace('/-+/', '-', $s);
    return trim($s, '-_.');
}

function ga_build_rel_dir($categoria) {
    $y = date('Y'); $m = date('m'); $d = date('d');
    $cat = ga_slugify($categoria);
    return ga_storage_rel_base() . "/$y/$m/$d/$cat";
}

function ga_ensure_dir($absDir) {
    if (!is_dir($absDir)) {
        @mkdir($absDir, 0775, true);
    }
    if (!is_dir($absDir) || !is_writable($absDir)) {
        throw new RuntimeException('La carpeta no es escribible: ' . $absDir);
    }
}

function ga_ext_from_upload($file) {
    $name = isset($file['name']) ? (string)$file['name'] : '';
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    return $ext !== '' ? $ext : 'bin';
}

function ga_random6() {
    return substr(bin2hex(random_bytes(6)), 0, 6);
}

function ga_generate_filename($basename, $entidad, $entidad_id, $ext) {
    $slug = ga_slugify($basename);
    $eid  = ($entidad && $entidad_id !== null && $entidad_id !== '')
        ? ('-' . ga_slugify($entidad) . '-' . ga_slugify((string)$entidad_id))
        : '';
    $ts   = date('Ymd\THis');
    $rnd  = ga_random6();
    return $slug . $eid . '-' . $ts . '-' . $rnd . '.' . strtolower((string)$ext);
}

/**
 * Firma COMPATIBLE con tu ejemplo (recibe mysqli aunque aquí no se use):
 * ga_save_upload(mysqli $mysqli, array $file, string $categoria, string $basename, string $triada, ?string $entidad, $entidad_id)
 */
function ga_save_upload($mysqli, $file, $categoria, $basename, $triada, $entidad, $entidad_id) {
    if (!isset($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        throw new RuntimeException('No hay archivo subido.');
    }
    if (!isset($file['error']) || (int)$file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Error de subida de archivo.');
    }

    $ext  = ga_ext_from_upload($file);

    $relDir = ga_build_rel_dir($categoria);
    $absDir = ga_abs_from_rel($relDir);
    ga_ensure_dir($absDir);

    $filename = ga_generate_filename($basename, $entidad, $entidad_id, $ext);
    $rutaRel  = $relDir . '/' . $filename;
    $absPath  = ga_abs_from_rel($rutaRel);

    if (!@move_uploaded_file($file['tmp_name'], $absPath)) {
        throw new RuntimeException('No se pudo guardar el archivo en disco.');
    }

    return array(
        'ruta_relativa' => $rutaRel,
        'nombre_final'  => $filename,
        'abs_path'      => $absPath,
    );
}

function ga_delete_rel($rutaRel) {
    $rutaRel = ltrim((string)$rutaRel, "/\\");
    if ($rutaRel === '') return;
    $abs = ga_abs_from_rel($rutaRel);
    if (is_file($abs)) @unlink($abs);
}
