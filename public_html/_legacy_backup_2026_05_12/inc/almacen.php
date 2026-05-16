<?php
// Debe cargarse DESPUÉS de definir APP_ROOT (lo hace app_boot.php)

if (!defined('RUTA_ALMACEN_REL')) define('RUTA_ALMACEN_REL', 'almacen');                  // ruta visible web
if (!defined('RUTA_ALMACEN_ABS')) define('RUTA_ALMACEN_ABS', APP_ROOT.'/'.RUTA_ALMACEN_REL); // ruta en disco

/**
 * Crea (si no existe) y devuelve [ruta_abs, ruta_rel] para propósito/año/mes/día
 * Ej.: propósito = 'perfil' -> almacen/perfil/2025/03/10
 */
function asegurar_arbol_almacen($proposito) {
  $proposito = trim($proposito,'/');
  $y = date('Y'); $m = date('m'); $d = date('d');

  $rel = RUTA_ALMACEN_REL.'/'.$proposito."/$y/$m/$d"; // p.ej. almacen/perfil/2025/03/10
  $abs = APP_ROOT.'/'.$rel;                           // absoluto en disco

  if (!is_dir($abs)) @mkdir($abs, 0775, true);
  return array($abs, $rel);
}

/** Intenta detectar MIME (opcional; compatible con PHP 5.6+ si finfo está habilitado) */
function _mime($tmp) {
  if (function_exists('finfo_open')) {
    $f = finfo_open(FILEINFO_MIME_TYPE);
    $t = finfo_file($f, $tmp);
    finfo_close($f);
    return $t;
  }
  return null;
}

/**
 * Guarda un upload en "almacen/<proposito>/Y/m/d".
 * Devuelve array [ok(bool), mensaje | ruta_rel_web].
 * La ruta devuelta es relativa web (ej. almacen/perfil/2025/03/10/foto.jpg).
 */
function guardar_upload($campo, $proposito, $ext_permitidas = array('jpg','jpeg','png','webp')) {
  if (empty($_FILES[$campo]) || $_FILES[$campo]['error'] !== UPLOAD_ERR_OK) {
    return array(false, 'Archivo inválido');
  }
  $f = $_FILES[$campo];

  // Valida extensión (simple y compatible)
  $ext = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
  if (!in_array($ext, $ext_permitidas)) {
    return array(false, 'Extensión no permitida');
  }

  // (Opcional) valida MIME si disponible
  $mime = _mime($f['tmp_name']);
  if ($mime && strpos($mime, 'image/') !== 0) {
    return array(false, 'Tipo de archivo no permitido');
  }

  list($abs, $rel) = asegurar_arbol_almacen($proposito);

  // Nombre aleatorio: HHMMSS_rand_uniqid.ext (compatible PHP 5.6+)
  $rand = mt_rand(1000,9999).'_'.str_replace('.', '', uniqid('', true));
  $nombre = date('His')."_$rand.$ext";
  $destAbs = rtrim($abs,'/').'/'.$nombre;

  if (!move_uploaded_file($f['tmp_name'], $destAbs)) {
    return array(false, 'No se pudo guardar el archivo');
  }

  // Devolvemos ruta RELATIVA web: ej. almacen/perfil/2025/03/10/archivo.jpg
  return array(true, $rel.'/'.$nombre);
}
