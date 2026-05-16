<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=UTF-8');

require_once __DIR__ . '/../includes/conexion.php';

if (!function_exists('home_api_resolve_media_path')) {
  function home_api_resolve_media_path(string $path): string {
    $path = trim($path);
    if ($path === '') return '';

    if (preg_match('~^(?:https?:)?//~i', $path) === 1) return $path;
    if (str_starts_with($path, '/') || str_starts_with($path, 'data:') || str_starts_with($path, 'blob:')) return $path;

    $normalized = ltrim(str_replace('\\', '/', $path), '/');
    $webPath = dirname(__DIR__) . '/' . $normalized;
    if (is_file($webPath)) return $normalized;

    $rootPath = dirname(__DIR__, 2) . '/' . $normalized;
    if (is_file($rootPath)) return '../' . $normalized;

    return $normalized;
  }
}

if (!function_exists('home_api_page_label')) {
  function home_api_page_label(string $pageKey): string {
    $map = array(
      'areas_proyectos' => 'Áreas - Proyectos',
      'areas_ambiental' => 'Áreas - Ambiental',
      'vol_cdn' => 'Voluntariado - CDN',
      'vol_cvgen' => 'Voluntariado - CVGEN',
      'vol_grd' => 'Voluntariado - GRD',
      'vol_promam' => 'Voluntariado - PROMAM',
      'vol_sbc' => 'Voluntariado - SBC',
      'vol_unippets' => 'Voluntariado - UNIPPETS',
      'cec_teatro' => 'CECUNT - Teatro',
      'cec_orfeon' => 'CECUNT - Orfeón',
      'cec_danza' => 'CECUNT - Danza',
      'cec_banda' => 'CECUNT - Banda',
      'cec_musica' => 'CECUNT - Música',
    );
    return $map[$pageKey] ?? strtoupper(str_replace('_', ' ', $pageKey));
  }
}

if (!function_exists('home_api_fmt_date')) {
  function home_api_fmt_date(?string $date): string {
    if (!is_string($date) || trim($date) === '') return 'Fecha por definir';
    $ts = strtotime($date);
    if ($ts === false) return 'Fecha por definir';
    return date('d M, Y', $ts);
  }
}

try {
  $sql = "
    SELECT *
    FROM (
      SELECT
        'noticia' AS tipo,
        n.id,
        n.page_key,
        n.titulo,
        COALESCE(NULLIF(n.resumen, ''), NULLIF(n.cuerpo, '')) AS texto,
        n.imagen_portada AS imagen,
        n.creado_en AS fecha_orden,
        n.publicada_en AS fecha_mostrar
      FROM l2601_noticias n
      WHERE n.estado <> 'oculta'

      UNION ALL

      SELECT
        'evento' AS tipo,
        e.id,
        e.page_key,
        e.titulo,
        NULLIF(e.parrafo, '') AS texto,
        e.foto_evento AS imagen,
        e.creado_en AS fecha_orden,
        CASE
          WHEN e.inicio_fecha IS NOT NULL
          THEN TIMESTAMP(e.inicio_fecha, COALESCE(e.inicio_hora, '00:00:00'))
          ELSE NULL
        END AS fecha_mostrar
      FROM l2601_eventos e
      WHERE e.estado <> 'inactivo'
    ) z
    ORDER BY z.fecha_orden DESC
    LIMIT 7
  ";

  $items = array();
  $rs = db()->query($sql);

  while ($row = $rs->fetch_assoc()) {
    $tipo = (string)($row['tipo'] ?? '');
    $id = (int)($row['id'] ?? 0);
    $pageKey = (string)($row['page_key'] ?? '');
    if ($id <= 0 || $pageKey === '') continue;

    $img = home_api_resolve_media_path((string)($row['imagen'] ?? ''));
    $titulo = trim((string)($row['titulo'] ?? ''));
    if ($titulo === '') $titulo = 'Novedad';

    $texto = trim((string)($row['texto'] ?? ''));
    if ($texto === '') $texto = 'Sin descripción por ahora.';
    if (mb_strlen($texto) > 180) $texto = mb_substr($texto, 0, 180) . '...';

    $badge = ($tipo === 'evento') ? 'Evento' : 'Noticia';
    $badgeClass = ($tipo === 'evento') ? 'nov-badge-evento' : 'nov-badge-comunicado';
    $link = ($tipo === 'evento')
      ? ('index.php?p=' . rawurlencode($pageKey) . '&evt_id=' . $id . '#evtSection')
      : ('index.php?p=' . rawurlencode($pageKey) . '&news_id=' . $id . '#newsSection');

    $items[] = array(
      'tipo' => $tipo,
      'id' => $id,
      'page_key' => $pageKey,
      'page_label' => home_api_page_label($pageKey),
      'titulo' => $titulo,
      'texto' => $texto,
      'fecha' => home_api_fmt_date(isset($row['fecha_mostrar']) ? (string)$row['fecha_mostrar'] : null),
      'badge' => $badge,
      'badge_class' => $badgeClass,
      'imagen' => $img,
      'link' => $link,
    );
  }
  $rs->free();

  echo json_encode(array('ok' => true, 'items' => $items), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(array(
    'ok' => false,
    'message' => 'No se pudo cargar novedades.'
  ), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
}
