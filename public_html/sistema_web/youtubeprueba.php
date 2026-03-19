<?php
// youtubeprueba.php
// Carga la conexión
require_once __DIR__ . '/componentes/db.php';

// Función para extraer el ID de YouTube desde distintos formatos
function yt_id($input) {
    $input = trim($input);

    // Si ya parece un ID (11 caracteres alfanuméricos, _ o -)
    if (preg_match('~^[A-Za-z0-9_-]{11}$~', $input)) {
        return $input;
    }

    // Asegurar que tenga esquema para parse_url
    if (!preg_match('~^https?://~i', $input)) {
        $input = 'https://' . $input;
    }

    $parts = parse_url($input);
    if (!$parts || empty($parts['host'])) return null;

    $host = strtolower($parts['host']);
    $path = isset($parts['path']) ? $parts['path'] : '';
    parse_str($parts['query'] ?? '', $query);

    // youtu.be/<id>
    if (strpos($host, 'youtu.be') !== false) {
        $segments = explode('/', trim($path, '/'));
        return !empty($segments[0]) ? substr($segments[0], 0, 11) : null;
    }

    // youtube.com
    if (strpos($host, 'youtube.com') !== false || strpos($host, 'youtube-nocookie.com') !== false) {
        // /watch?v=<id>
        if (!empty($query['v'])) {
            return substr($query['v'], 0, 11);
        }
        // /embed/<id>  o  /shorts/<id>
        if (preg_match('~/(embed|shorts)/([A-Za-z0-9_-]{11})~', $path, $m)) {
            return $m[2];
        }
    }

    return null;
}

// Consulta de videos no vacíos y activos
$sql = "SELECT id, nombre, video 
        FROM sm_items 
        WHERE activo = 1 
          AND video IS NOT NULL 
          AND TRIM(video) <> '' 
        ORDER BY id DESC";

$result = mysqli_query($conexion, $sql);
if ($result === false) {
    http_response_code(500);
    echo "Error en la consulta: " . htmlspecialchars(mysqli_error($conexion));
    exit;
}

// Construimos un arreglo limpio con id_video extraído
$videos = [];
while ($row = mysqli_fetch_assoc($result)) {
    $idYt = yt_id($row['video']);
    if ($idYt) {
        $videos[] = [
            'id'     => (int)$row['id'],
            'nombre' => $row['nombre'],
            'ytid'   => $idYt,
            'video'  => $row['video'],
        ];
    }
}

mysqli_free_result($result);
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Videos YouTube — sm_items</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <!-- Bootstrap 5 (CDN) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .video-btn { text-align: left; }
        .yt-frame-wrapper {
            position: relative;
            width: 100%;
            /* Relación 16:9 */
            padding-bottom: 56.25%;
            background: #000;
            border-radius: .5rem;
            overflow: hidden;
        }
        .yt-frame-wrapper iframe {
            position: absolute; inset: 0;
            width: 100%; height: 100%;
            border: 0;
        }
        .list-area {
            max-height: 70vh;
            overflow: auto;
        }
        @media (max-width: 991.98px) {
            .list-area { max-height: none; }
        }
    </style>
</head>
<body class="bg-light">
<div class="container py-4">
    <h1 class="h3 mb-4">Videos de YouTube (tabla <code>sm_items</code>)</h1>

    <?php if (empty($videos)): ?>
        <div class="alert alert-warning">
            No se encontraron registros con enlaces de YouTube.
        </div>
    <?php else: ?>
        <div class="row g-4">
            <div class="col-lg-5">
                <div class="card shadow-sm">
                    <div class="card-header">Lista de videos</div>
                    <div class="card-body list-area">
                        <div class="d-grid gap-2">
                            <?php foreach ($videos as $v): ?>
                                <?php
                                    $label = $v['nombre'] ?: ('Video #' . (int)$v['id']);
                                ?>
                                <button
                                    type="button"
                                    class="btn btn-outline-primary video-btn"
                                    data-ytid="<?php echo htmlspecialchars($v['ytid'], ENT_QUOTES, 'UTF-8'); ?>"
                                    data-label="<?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?>"
                                >
                                    <div class="d-flex align-items-center">
                                        <span class="me-2" aria-hidden="true">▶️</span>
                                        <span class="fw-semibold"><?php echo htmlspecialchars($label, ENT_QUOTES, 'UTF-8'); ?></span>
                                    </div>
                                    <div class="small text-muted text-truncate">
                                        <?php echo htmlspecialchars($v['video'], ENT_QUOTES, 'UTF-8'); ?>
                                    </div>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="card shadow-sm">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span id="player-title">Selecciona un video</span>
                        <button id="clear-btn" class="btn btn-sm btn-outline-secondary">Limpiar</button>
                    </div>
                    <div class="card-body">
                        <div class="yt-frame-wrapper">
                            <iframe
                                id="yt-player"
                                title="YouTube player"
                                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
                                allowfullscreen
                                src=""
                                referrerpolicy="strict-origin-when-cross-origin"
                            ></iframe>
                        </div>
                        <div class="form-text mt-2">
                            El reproductor cargará aquí el video seleccionado.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<script>
(function () {
    const buttons = document.querySelectorAll('.video-btn');
    const iframe  = document.getElementById('yt-player');
    const titleEl = document.getElementById('player-title');
    const clear   = document.getElementById('clear-btn');

    function play(id, label) {
        // Carga embebido seguro
        const src = "https://www.youtube.com/embed/" + encodeURIComponent(id) + "?autoplay=1&rel=0";
        iframe.src = src;
        titleEl.textContent = label || 'Reproduciendo…';
        // Desplaza a la zona del player en móviles
        document.querySelector('.yt-frame-wrapper').scrollIntoView({ behavior: 'smooth', block: 'start' });
    }

    buttons.forEach(btn => {
        btn.addEventListener('click', () => {
            const id = btn.getAttribute('data-ytid');
            const label = btn.getAttribute('data-label');
            if (id) play(id, label);
        });
    });

    clear.addEventListener('click', () => {
        iframe.src = "";
        titleEl.textContent = "Selecciona un video";
    });
})();
</script>

</body>
</html>
