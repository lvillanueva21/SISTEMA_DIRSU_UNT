<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/csrf.php';
require_once __DIR__ . '/conexion.php';

$pageHeaderTitle  = $pageHeaderTitle ?? '';
$pageHeaderCrumbs = $pageHeaderCrumbs ?? [];
$pageKey = isset($pageKey) && is_string($pageKey) ? $pageKey : (is_string($_GET['p'] ?? null) ? (string)$_GET['p'] : '');
$isConfigurableHeader = isset($pageHeaderConfigurable) ? (bool)$pageHeaderConfigurable : true;

$defaultHeaderImage = 'img/carousel-1.jpg';
$defaultHeaderDesc = 'Descripción pendiente.';
$headerImage = $defaultHeaderImage;
$headerDesc = $defaultHeaderDesc;

if (!function_exists('page_header_parse_ini_bytes')) {
    function page_header_parse_ini_bytes(string $val): int {
        $v = trim($val);
        if ($v === '') return 0;
        $last = strtolower(substr($v, -1));
        $num = (float)$v;
        $mult = 1;
        if ($last === 'g') $mult = 1024 * 1024 * 1024;
        elseif ($last === 'm') $mult = 1024 * 1024;
        elseif ($last === 'k') $mult = 1024;
        return (int)round($num * $mult);
    }
}

$uploadMaxBytes = min(
    page_header_parse_ini_bytes((string)ini_get('upload_max_filesize')),
    page_header_parse_ini_bytes((string)ini_get('post_max_size'))
);
$uploadMaxLabel = (string)ini_get('upload_max_filesize') . ' (upload_max_filesize), ' . (string)ini_get('post_max_size') . ' (post_max_size)';

if (!function_exists('page_header_resolve_media_path')) {
    function page_header_resolve_media_path(string $path): string {
        $path = trim($path);
        if ($path === '') return '';
        if (preg_match('~^(?:https?:)?//~i', $path) === 1) return $path;
        if (str_starts_with($path, '/') || str_starts_with($path, 'data:') || str_starts_with($path, 'blob:')) return $path;

        $normalized = ltrim(str_replace('\\', '/', $path), '/');
        $webPath = dirname(__DIR__) . '/' . $normalized;
        if (is_file($webPath)) return $normalized;

        return $normalized;
    }
}

$validPageKey = ($isConfigurableHeader && $pageKey !== '' && preg_match('/^[a-z0-9\-_]+$/i', $pageKey) === 1);

if ($validPageKey) {
    try {
        $mysqli = db();
        $sql = "SELECT imagen_portada, descripcion
                FROM l2601_portadas_paginas
                WHERE page_key = ?
                LIMIT 1";
        $st = $mysqli->prepare($sql);
        $st->bind_param('s', $pageKey);
        $st->execute();
        $res = $st->get_result();
        $row = $res->fetch_assoc();
        $st->close();

        if ($row) {
            $img = page_header_resolve_media_path((string)($row['imagen_portada'] ?? ''));
            if ($img !== '') $headerImage = $img;

            $desc = trim((string)($row['descripcion'] ?? ''));
            if ($desc !== '') $headerDesc = $desc;
        }
    } catch (Throwable $e) {
        // Keep fallbacks if table is not present yet.
    }
}

$user = auth_user();
$roleCode = ($user && isset($user['rol']['codigo'])) ? (string)$user['rol']['codigo'] : '';
$canManageHeader = ($isConfigurableHeader && in_array($roleCode, ['desarrollador', 'director', 'secretaria'], true));
$headerBgStyle = "background:linear-gradient(rgba(15,66,41,.6), rgba(15,66,41,.6)), url('" . htmlspecialchars($headerImage, ENT_QUOTES, 'UTF-8') . "') center center / cover no-repeat;";
?>
<div
    id="pageHeaderBlock"
    class="container-fluid page-header py-5 mb-5 wow fadeIn"
    data-wow-delay="0.1s"
    data-pagekey="<?= htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') ?>"
    data-canmanage="<?= $canManageHeader ? '1' : '0' ?>"
    data-api="modules/portadas/portadas_api.php"
    data-csrf="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>"
    data-currentimg="<?= htmlspecialchars($headerImage, ENT_QUOTES, 'UTF-8') ?>"
    data-currentdesc="<?= htmlspecialchars($headerDesc, ENT_QUOTES, 'UTF-8') ?>"
    data-defaultimg="<?= htmlspecialchars($defaultHeaderImage, ENT_QUOTES, 'UTF-8') ?>"
    data-defaultdesc="<?= htmlspecialchars($defaultHeaderDesc, ENT_QUOTES, 'UTF-8') ?>"
    data-uploadmaxbytes="<?= (int)$uploadMaxBytes ?>"
    data-uploadmaxlabel="<?= htmlspecialchars($uploadMaxLabel, ENT_QUOTES, 'UTF-8') ?>"
    style="<?= $headerBgStyle ?>"
>
    <div class="container text-center py-5">
        <h1 class="display-3 text-white mb-3 animated slideInDown">
            <?= htmlspecialchars($pageHeaderTitle, ENT_QUOTES, 'UTF-8') ?>
        </h1>

        <p class="page-header-desc text-white mx-auto mb-4 animated fadeInUp">
            <?= htmlspecialchars($headerDesc, ENT_QUOTES, 'UTF-8') ?>
        </p>

        <nav aria-label="breadcrumb animated slideInDown">
            <ol class="breadcrumb justify-content-center mb-0">
                <?php foreach ($pageHeaderCrumbs as $i => $c): ?>
                    <?php
                        $label = $c[0] ?? '';
                        $href  = $c[1] ?? null;
                        $isLast = ($i === count($pageHeaderCrumbs) - 1);
                    ?>
                    <?php if ($isLast || !$href): ?>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                        </li>
                    <?php else: ?>
                        <li class="breadcrumb-item">
                            <a href="<?= htmlspecialchars($href, ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ol>
        </nav>

        <?php if ($canManageHeader && $validPageKey): ?>
            <div class="mt-4">
                <button type="button" class="btn btn-light btn-sm" id="btnEditPageHeader" data-bs-toggle="modal" data-bs-target="#pageHeaderModal">
                    Editar portada de esta página
                </button>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($canManageHeader && $validPageKey): ?>
<div class="modal fade" id="pageHeaderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Portada de página</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="pageHeaderAlert" class="alert" style="display:none;"></div>

        <form id="pageHeaderForm" enctype="multipart/form-data">
          <input type="hidden" name="action" value="save">
          <input type="hidden" name="page_key" value="<?= htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8') ?>">
          <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">

          <div class="mb-3">
            <label class="form-label">Descripción</label>
            <textarea class="form-control" id="phDescripcion" name="descripcion" rows="3" maxlength="500" placeholder="Descripción de la subpágina..."><?= htmlspecialchars($headerDesc, ENT_QUOTES, 'UTF-8') ?></textarea>
            <div class="form-text">Máximo 500 caracteres.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Imagen de portada (opcional)</label>
            <input class="form-control" type="file" id="phFoto" name="foto_portada" accept="image/*">
            <div class="form-text">Se permiten imagenes de cualquier formato compatible con tu navegador. Limite del servidor: <?= htmlspecialchars($uploadMaxLabel, ENT_QUOTES, 'UTF-8') ?>.</div>
          </div>

          <div class="mb-3">
            <label class="form-label">Vista previa</label>
            <div class="border p-2 bg-light">
              <img id="phPreviewImg" src="" alt="Vista previa portada" style="display:none;max-width:100%;max-height:220px;object-fit:cover;">
              <div id="phPreviewEmpty" class="text-muted small">No se seleccionó una nueva imagen.</div>
            </div>
          </div>

          <div id="phProgressWrap" class="mb-2" style="display:none;">
            <label class="form-label mb-1">Subiendo imagen...</label>
            <div class="progress" role="progressbar" aria-label="Progreso de subida" aria-valuemin="0" aria-valuemax="100">
              <div id="phProgressBar" class="progress-bar bg-success" style="width:0%">0%</div>
            </div>
          </div>
        </form>
      </div>
      <div class="modal-footer d-flex justify-content-between">
        <button type="button" class="btn btn-outline-danger" id="btnClearPageHeaderImage">Usar imagen por defecto</button>
        <div class="d-flex gap-2">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
          <button type="button" class="btn btn-primary" id="btnSavePageHeader">Guardar cambios</button>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="pageHeaderStatusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header" id="pageHeaderStatusHead">
        <h5 class="modal-title" id="pageHeaderStatusTitle">Estado</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <p class="mb-0" id="pageHeaderStatusBody"></p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Aceptar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>
