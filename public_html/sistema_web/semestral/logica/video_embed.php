<?php
// presentacion/logica/video_embed.php
// Página mínima que recibe ?u=<URL de YouTube> y renderiza un iframe embebido (16:9)

declare(strict_types=1);
header('X-Frame-Options: SAMEORIGIN'); // opcional
// No necesitamos DB aquí.

// Extrae ID de YouTube desde distintos formatos
function yt_id(string $input): ?string {
    $input = trim($input);
    if ($input === '') return null;

    // Si ya parece un ID
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $input)) return $input;

    if (!preg_match('~^https?://~i', $input)) $input = 'https://' . $input;

    $parts = parse_url($input);
    if (!$parts || empty($parts['host'])) return null;

    $host = strtolower($parts['host']);
    $path = $parts['path'] ?? '';
    parse_str($parts['query'] ?? '', $q);

    // youtu.be/<id>
    if (strpos($host, 'youtu.be') !== false) {
        $seg = explode('/', trim($path, '/'));
        return !empty($seg[0]) ? substr($seg[0], 0, 11) : null;
    }

    // youtube.com/watch?v=ID, /embed/ID, /shorts/ID
    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtube-nocookie.com') !== false) {
        if (!empty($q['v'])) return substr((string)$q['v'], 0, 11);
        if (preg_match('~/(embed|shorts)/([A-Za-z0-9_-]{11})~', $path, $m)) return $m[2];
    }

    return null;
}

// Lee entrada
$raw = isset($_GET['u']) ? (string)$_GET['u'] : '';
$raw = trim($raw);
$id  = yt_id($raw);

$embedSrc = '';
if ($id) {
    // Conserva algunos parámetros útiles
    $u = (!preg_match('~^https?://~i', $raw)) ? 'https://' . $raw : $raw;
    $url = parse_url($u);
    parse_str($url['query'] ?? '', $q);

    $params = [];
    if (!empty($q['list']))  $params['list']  = $q['list'];
    if (!empty($q['t']))     $params['start'] = preg_replace('~\D~', '', (string)$q['t']);
    if (!empty($q['start'])) $params['start'] = preg_replace('~\D~', '', (string)$q['start']);
    $params['autoplay'] = '1';
    $params['rel']      = '0';

    $embedSrc = 'https://www.youtube.com/embed/' . rawurlencode($id);
    if ($params) $embedSrc .= '?' . http_build_query($params);
}
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <title>Video</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <style>
    html, body { margin:0; padding:0; background:#000; }
    .wrap { position:relative; width:100%; padding-bottom:56.25%; background:#000; }
    iframe { position:absolute; inset:0; width:100%; height:100%; border:0; }
    .msg { color:#fff; font-family:system-ui,-apple-system,Segoe UI,Roboto,Arial,sans-serif; padding:16px; }
  </style>
</head>
<body>
<?php if ($embedSrc): ?>
  <div class="wrap">
    <iframe
      src="<?= htmlspecialchars($embedSrc, ENT_QUOTES, 'UTF-8') ?>"
      allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
      allowfullscreen
      referrerpolicy="strict-origin-when-cross-origin"
    ></iframe>
  </div>
<?php else: ?>
  <div class="msg">No se pudo interpretar la URL de YouTube.</div>
<?php endif; ?>
</body>
</html>
