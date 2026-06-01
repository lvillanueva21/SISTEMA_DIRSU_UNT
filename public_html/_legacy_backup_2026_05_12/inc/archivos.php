<?php
if (!defined('APP_ROOT')) { die('No access'); }

/**
 * Devuelve la ruta absoluta de la carpeta destino dentro de /almacen/<tipo>/YYYY/MM/DD
 * y la crea si no existe. Retorna [absPath, relPath].
 */
function carpeta_almacen_diaria(string $tipo): array {
  $tipo = preg_replace('~[^a-zA-Z0-9_-]~','', $tipo ?: 'otros');

  $y = date('Y');
  $m = date('m');
  $d = date('d');

  $rel = "almacen/{$tipo}/{$y}/{$m}/{$d}";
  $abs = APP_ROOT . '/' . $rel;

  if (!is_dir($abs)) {
    @mkdir($abs, 0775, true);
  }
  return [$abs, $rel];
}

/**
 * Guarda un archivo subido ($_FILES[$inputName]) en /almacen/<tipo>/YYYY/MM/DD
 * Retorna ruta relativa (ej: almacen/perfil/2025/10/19/foto_abc.jpg) o null si no hay archivo.
 */
function guardar_subida(string $tipo, string $inputName): ?string {
  if (empty($_FILES[$inputName]) || $_FILES[$inputName]['error'] !== UPLOAD_ERR_OK) {
    return null;
  }
  [$abs, $rel] = carpeta_almacen_diaria($tipo);

  $orig = $_FILES[$inputName]['name'] ?? 'archivo';
  $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
  $base = pathinfo($orig, PATHINFO_FILENAME);
  $base = preg_replace('~[^a-zA-Z0-9_-]~','_', $base);
  if ($base === '') $base = 'archivo';

  $nombre = $base.'_'.substr(sha1(uniqid('', true)), 0, 8);
  $final  = "{$nombre}".($ext ? ".{$ext}" : '');

  $destAbs = $abs.'/'.$final;
  if (!move_uploaded_file($_FILES[$inputName]['tmp_name'], $destAbs)) {
    return null;
  }
  // Opcional: ajustar permisos
  @chmod($destAbs, 0644);

  return $rel.'/'.$final; // ruta relativa servible con asset()
}
