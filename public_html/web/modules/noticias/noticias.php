<?php
declare(strict_types=1);

// modules/noticias/noticias.php
// Se incluye desde un módulo de página. Si no se incluye, no aparece nada.
// Bloquear acceso directo:
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

if (defined('MOD_NOTICIAS_CARGADO')) {
    return;
}
define('MOD_NOTICIAS_CARGADO', 1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

date_default_timezone_set('America/Lima');

$pageKey = $_GET['p'] ?? '';
$pageKey = is_string($pageKey) ? $pageKey : '';
if ($pageKey === '' || !preg_match('/^[a-z0-9\-\_]+$/i', $pageKey)) {
    return; // solo para páginas del router ?p=
}

$user = auth_user();
$isLogged = (bool)$user;

$rolCodigo = ($user && isset($user['rol']['codigo'])) ? (string)$user['rol']['codigo'] : '';
$canManage = in_array($rolCodigo, array('desarrollador','director','secretaria'), true);

$apiUrl = 'modules/noticias/noticias_api.php'; // relativo (sin raíz)
$csrf = csrf_token();
?>

<style>
/* ====== MOD NOTICIAS (aislado) ====== */
#newsSection .news-card{
  border:0;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 8px 22px rgba(0,0,0,.08);
  background:#fff;
}
#newsSection .news-feature{
  position:relative;
  min-height: 360px;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 10px 26px rgba(0,0,0,.12);
}
#newsSection .news-feature .news-feature-img{
  width:100%;
  height:360px;
  object-fit:cover;
  display:block;
}
#newsSection .news-feature .news-feature-ph{
  width:100%;
  height:360px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#eef2f7,#f8fafc);
  color:#6c757d;
  font-size:64px;
}
#newsSection .news-feature .news-feature-overlay{
  position:absolute;
  inset:0;
  background:linear-gradient(180deg, rgba(0,0,0,0.0) 0%, rgba(0,0,0,0.65) 75%);
}
#newsSection .news-feature .news-feature-body{
  position:absolute;
  left:0;
  right:0;
  bottom:0;
  padding:18px 18px 16px 18px;
  color:#fff;
}
#newsSection .news-feature .news-feature-title{
  font-size: 26px;
  font-weight: 700;
  line-height: 1.2;
  margin:0 0 8px 0;
}
#newsSection .news-feature .news-feature-meta{
  font-size: 13px;
  opacity:.9;
  margin-bottom:10px;
}
#newsSection .news-feature .news-feature-resumen{
  font-size: 14px;
  opacity:.95;
  display:-webkit-box;
  -webkit-line-clamp:3;
  -webkit-box-orient:vertical;
  overflow:hidden;
  max-width: 95%;
}

#newsSection .news-mini{
  border:1px solid rgba(0,0,0,.06);
  border-radius:14px;
  padding:12px;
  background:#fff;
  box-shadow:0 6px 18px rgba(0,0,0,.05);
  cursor:pointer;
  transition:transform .12s ease;
}
#newsSection .news-mini:hover{ transform: translateY(-2px); }
#newsSection .news-mini .mini-img{
  width:64px;
  height:64px;
  border-radius:12px;
  object-fit:cover;
  display:block;
}
#newsSection .news-mini .mini-ph{
  width:64px;
  height:64px;
  border-radius:12px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#eef2f7,#f8fafc);
  color:#6c757d;
  font-size:26px;
}
#newsSection .news-mini .mini-title{
  font-weight:700;
  font-size:14px;
  line-height:1.25;
  margin:0;
  display:-webkit-box;
  -webkit-line-clamp:2;
  -webkit-box-orient:vertical;
  overflow:hidden;
}
#newsSection .news-mini .mini-meta{
  font-size:12px;
  color:#6c757d;
  margin-top:4px;
}
#newsSection .news-mini .mini-badge{
  font-size:11px;
}

#newsSection .news-skel{
  height: 360px;
  background:linear-gradient(90deg,#f3f3f3,#e9e9e9,#f3f3f3);
  background-size:200% 100%;
  animation:newsSk 1.1s infinite;
  border-radius:18px;
}
#newsSection .news-skel-mini{
  height: 92px;
  background:linear-gradient(90deg,#f3f3f3,#e9e9e9,#f3f3f3);
  background-size:200% 100%;
  animation:newsSk 1.1s infinite;
  border-radius:14px;
}
@keyframes newsSk{
  0%{background-position:0% 0%}
  100%{background-position:-200% 0%}
}

#newsView .news-view-img{
  width:100%;
  max-height: 260px;
  object-fit: cover;
  border-radius:16px;
  display:block;
}
#newsView .news-view-img-ph{
  width:100%;
  height:260px;
  border-radius:16px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#eef2f7,#f8fafc);
  color:#6c757d;
  font-size:54px;
}
#newsView .news-view-meta{
  font-size:13px;
  color:#6c757d;
}
#newsView .news-view-tags{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
}
#newsView .news-tag{
  display:inline-flex;
  align-items:center;
  padding:3px 10px;
  border-radius:999px;
  background:#f1f3f5;
  font-size:12px;
}

#newsPanelAlert, #newsFormAlert { display:none; }
</style>

<section class="container-xxl py-5" id="newsSection"
         data-pagekey="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>"
         data-api="<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>"
         data-csrf="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>"
         data-logged="<?php echo $isLogged ? '1' : '0'; ?>"
         data-canmanage="<?php echo $canManage ? '1' : '0'; ?>">

  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <p class="fs-5 fw-bold text-primary mb-1">Noticias</p>
        <h2 class="mb-0">Comunicados de esta página</h2>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#newsModalPanel">
          Ver todas las noticias
        </button>

        <?php if ($canManage): ?>
          <button type="button" class="btn btn-success" id="newsBtnNuevo">
            Nueva noticia
          </button>
        <?php endif; ?>
      </div>
    </div>

    <div class="row g-4 align-items-stretch">
      <div class="col-lg-7" id="newsFeaturedWrap">
        <div class="news-skel"></div>
      </div>

      <div class="col-lg-5">
        <div class="d-flex flex-column gap-3" id="newsMiniWrap">
          <div class="news-skel-mini"></div>
          <div class="news-skel-mini"></div>
          <div class="news-skel-mini"></div>
          <div class="news-skel-mini"></div>
          <div class="news-skel-mini"></div>
        </div>
      </div>
    </div>
  </div>
</section>

<!-- ===== MODAL VISTA NOTICIA (publico) ===== -->
<div class="modal fade" id="newsModalView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content" id="newsView">

      <div class="modal-header">
        <div class="d-flex flex-column">
          <h5 class="modal-title mb-0" id="newsViewTitle">Noticia</h5>
          <div class="small text-muted">
            <span class="badge bg-secondary" id="newsViewEstado">publicada</span>
          </div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="row g-4 align-items-start">
          <!-- IZQ: imagen + detalles abajo -->
          <div class="col-lg-4">
            <img id="newsViewImg" class="news-view-img" alt="Noticia" style="display:none;">
            <div id="newsViewImgPh" class="news-view-img-ph"><i class="bi bi-newspaper"></i></div>

            <div class="mt-3">
              <div class="news-view-meta mb-2" id="newsViewMeta">Fecha por definir</div>
              <div class="news-view-tags" id="newsViewTags"></div>
            </div>
          </div>

          <!-- DER: titulo + texto -->
          <div class="col-lg-8">
            <div class="bg-light rounded p-3">
              <div class="fw-bold mb-2">Contenido</div>
              <div id="newsViewCuerpo" style="white-space:pre-wrap;">Sin contenido.</div>
            </div>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- ===== MODAL ARCHIVO (publico) ===== -->
<div class="modal fade" id="newsModalPanel" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Archivo de noticias</h5>
          <div class="text-muted small">Página: <strong><?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?></strong></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="newsPanelAlert" class="alert"></div>

        <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
          <div>
            <label class="form-label mb-1">Desde</label>
            <input type="date" class="form-control" id="newsDesde">
          </div>
          <div>
            <label class="form-label mb-1">Hasta</label>
            <input type="date" class="form-control" id="newsHasta">
          </div>
          <div>
            <label class="form-label mb-1">Buscar (título)</label>
            <input type="text" class="form-control" id="newsQ" placeholder="Ej: convocatoria">
          </div>
          <div>
            <label class="form-label mb-1">Estado</label>
            <select class="form-select" id="newsEstado">
              <option value="">Todos</option>
              <option value="publicada">Publicada</option>
              <option value="archivada">Archivada</option>
              <?php if ($canManage): ?>
                <option value="oculta">Oculta</option>
              <?php endif; ?>
            </select>
          </div>
          <div>
            <button class="btn btn-primary" type="button" id="newsBtnFiltrar">Filtrar</button>
          </div>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:90px;">Foto</th>
                <th>Título</th>
                <th style="width:180px;">Publicación</th>
                <th style="width:130px;">Estado</th>
                <th style="width:180px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="newsTbody">
              <tr><td colspan="5" class="text-muted">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex align-items-center justify-content-between">
          <div class="text-muted small" id="newsInfo"></div>
          <div class="btn-group" role="group">
            <button class="btn btn-outline-secondary" type="button" id="newsPrev">Anterior</button>
            <button class="btn btn-outline-secondary" type="button" id="newsNext">Siguiente</button>
          </div>
        </div>

      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<?php if ($canManage): ?>
<!-- ===== MODAL FORM (CREAR/EDITAR) ===== -->
<div class="modal fade" id="newsModalForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="newsFormTitle">Noticia</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="newsFormAlert" class="alert"></div>

        <form id="newsForm" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="id" id="newsId" value="">

          <div class="mb-3">
            <label class="form-label">Título (obligatorio)</label>
            <input class="form-control" name="titulo" id="newsTitulo" maxlength="200" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Resumen (opcional)</label>
            <textarea class="form-control" name="resumen" id="newsResumen" maxlength="500" rows="3"
                      placeholder="Se usa para vista pequeña / destacada"></textarea>
          </div>

          <div class="mb-3">
            <label class="form-label">Contenido (opcional)</label>
            <textarea class="form-control" name="cuerpo" id="newsCuerpo" maxlength="6000" rows="8"
                      placeholder="Escribe el contenido..."></textarea>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Publicación fecha (opcional)</label>
              <input type="date" class="form-control" name="pub_fecha" id="newsPubFecha">
            </div>
            <div class="col-md-6">
              <label class="form-label">Publicación hora (opcional)</label>
              <input type="time" class="form-control" name="pub_hora" id="newsPubHora">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="estado" id="newsEstadoForm">
                <option value="publicada">Publicada</option>
                <option value="archivada">Archivada</option>
                <option value="oculta">Oculta</option>
              </select>
              <div class="text-muted small mt-1">Oculta NO se muestra al público.</div>
            </div>
            <div class="col-md-6">
              <label class="form-label">Destacada</label>
              <div class="form-check mt-2">
                <input class="form-check-input" type="checkbox" value="1" id="newsDestacada" name="destacada">
                <label class="form-check-label" for="newsDestacada">Mostrar como noticia grande</label>
              </div>
              <div class="text-muted small mt-1">Solo una destacada por página (se ajusta automáticamente).</div>
            </div>
          </div>

          <div class="mb-3">
            <label class="form-label">Etiquetas (opcional, separadas por coma)</label>
            <input class="form-control" name="etiquetas_csv" id="newsTags" maxlength="500"
                   placeholder="Ej: convocatoria, voluntariado">
          </div>

          <div class="mb-1">
            <label class="form-label">Foto (opcional)</label>
            <input type="file" class="form-control" name="foto" id="newsFoto" accept="image/*">
            <div class="mt-2 d-flex align-items-center gap-2">
              <img id="newsFotoPrev" alt="Foto" style="display:none;max-width:220px;border-radius:14px;">
              <div class="text-muted small" id="newsFotoMsg"></div>
            </div>
          </div>

        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-primary" type="button" id="newsBtnGuardar">Guardar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
(function(){
  var sec = document.getElementById('newsSection');
  if (!sec) return;

  var pageKey = sec.getAttribute('data-pagekey') || '';
  var api = sec.getAttribute('data-api') || 'modules/noticias/noticias_api.php';
  var csrf = sec.getAttribute('data-csrf') || '';
  var logged = (sec.getAttribute('data-logged') === '1');
  var canManage = (sec.getAttribute('data-canmanage') === '1');

  var homeCache = {}; // id => item

  function esc(s){
    s = (s === null || s === undefined) ? '' : String(s);
    return s.replace(/[&<>"']/g, function(m){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]);
    });
  }

  function showAlert(id, type, msg){
    var el = document.getElementById(id);
    if (!el) return;
    el.className = 'alert alert-' + type;
    el.textContent = msg;
    el.style.display = 'block';
  }
  function hideAlert(id){
    var el = document.getElementById(id);
    if (!el) return;
    el.style.display = 'none';
  }

  function errMsg(j, fallback){
  var m = (j && j.error) ? String(j.error) : String(fallback || 'Error.');
  if (j && j.debug) {
    if (j.debug.message) m += "\n\n" + String(j.debug.message);
    if (j.debug.file) m += "\n" + String(j.debug.file) + ":" + String(j.debug.line || '');
    if (j.debug.trace) m += "\n\n" + String(j.debug.trace);
    if (j.debug.raw) m += "\n\nRAW:\n" + String(j.debug.raw);
  }
  return m;
}


  function parseTags(csv){
    if (!csv) return [];
    return String(csv).split(',')
      .map(function(x){ return x.trim(); })
      .filter(function(x){ return x.length > 0; })
      .slice(0, 25);
  }

  function fmtPub(it){
    if (!it) return 'Fecha por definir';
    if (it.publicada_en && String(it.publicada_en).trim() !== '') return String(it.publicada_en);
    return 'Fecha por definir';
  }

  function openView(it){
    if (!it) return;

    var title = document.getElementById('newsViewTitle');
    var estado = document.getElementById('newsViewEstado');
    var meta = document.getElementById('newsViewMeta');

    var img = document.getElementById('newsViewImg');
    var imgPh = document.getElementById('newsViewImgPh');

    var tagsWrap = document.getElementById('newsViewTags');
    var cuerpo = document.getElementById('newsViewCuerpo');

    if (title) title.textContent = (it.titulo || 'Noticia');
    if (estado) estado.textContent = (it.estado || 'publicada');
    if (meta) meta.textContent = fmtPub(it);

    if (img && imgPh) {
      if (it.imagen_portada) {
        img.src = String(it.imagen_portada);
        img.style.display = 'block';
        imgPh.style.display = 'none';
      } else {
        img.removeAttribute('src');
        img.style.display = 'none';
        imgPh.style.display = 'flex';
      }
    }

    if (tagsWrap) {
      var arr = parseTags(it.etiquetas_csv);
      if (!arr.length) {
        tagsWrap.innerHTML = '';
      } else {
        tagsWrap.innerHTML = arr.map(function(t){
          return '<span class="news-tag">' + esc(t) + '</span>';
        }).join('');
      }
    }

    if (cuerpo) {
      var txt = '';
      if (it.cuerpo && String(it.cuerpo).trim() !== '') txt = String(it.cuerpo);
      else if (it.resumen && String(it.resumen).trim() !== '') txt = String(it.resumen);
      else txt = 'Sin contenido.';
      cuerpo.textContent = txt;
    }

    var el = document.getElementById('newsModalView');
    if (el && window.bootstrap) {
      new bootstrap.Modal(el).show();
    }
  }

  function readJsonResponse(r){
  return r.text().then(function(txt){
    try { return JSON.parse(txt); }
    catch(e){
      return { ok:false, error:'Respuesta no JSON del servidor.', debug:{ raw: txt } };
    }
  });
}

function apiGet(params){
  var u = api + '?' + params + (canManage ? '&debug=1' : '');
  return fetch(u, { credentials: 'same-origin' })
    .then(readJsonResponse);
}

function apiPost(formData){
  if (canManage) formData.append('debug', '1');
  return fetch(api, {
    method: 'POST',
    credentials: 'same-origin',
    body: formData
  }).then(readJsonResponse);
}


  function featuredHtml(it){
    var img = '';
    if (it.imagen_portada) {
      img = '<img class="news-feature-img" src="'+esc(it.imagen_portada)+'" alt="Noticia">';
    } else {
      img = '<div class="news-feature-ph"><i class="bi bi-newspaper"></i></div>';
    }

    var resumen = it.resumen ? String(it.resumen) : '';
    if (!resumen && it.cuerpo) resumen = String(it.cuerpo);
    if (resumen.length > 220) resumen = resumen.substring(0, 220) + '...';

    return '' +
      '<div class="news-feature" data-news-view="'+esc(it.id)+'">' +
        img +
        '<div class="news-feature-overlay"></div>' +
        '<div class="news-feature-body">' +
          '<div class="d-flex align-items-center justify-content-between gap-2">' +
            '<span class="badge bg-light text-dark">'+esc(it.estado || 'publicada')+'</span>' +
            '<span class="news-feature-meta">'+esc(fmtPub(it))+'</span>' +
          '</div>' +
          '<div class="news-feature-title">'+esc(it.titulo || 'Noticia')+'</div>' +
          '<div class="news-feature-resumen">'+esc(resumen)+'</div>' +
        '</div>' +
      '</div>';
  }

  function featuredEmptyHtml(){
    return '' +
      '<div class="news-feature">' +
        '<div class="news-feature-ph"><i class="bi bi-newspaper"></i></div>' +
        '<div class="news-feature-overlay"></div>' +
        '<div class="news-feature-body">' +
          '<span class="badge bg-light text-dark">Sin noticias</span>' +
          '<div class="news-feature-title">Aún no hay noticias</div>' +
          '<div class="news-feature-resumen">Cuando publiquen una noticia, aparecerá aquí.</div>' +
        '</div>' +
      '</div>';
  }

  function miniHtml(it){
    var img = it.imagen_portada
      ? '<img class="mini-img" src="'+esc(it.imagen_portada)+'" alt="Noticia">'
      : '<div class="mini-ph"><i class="bi bi-newspaper"></i></div>';

    return '' +
      '<div class="news-mini d-flex gap-3 align-items-start" data-news-view="'+esc(it.id)+'">' +
        img +
        '<div class="flex-grow-1">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<p class="mini-title">'+esc(it.titulo || 'Noticia')+'</p>' +
            '<span class="badge bg-secondary mini-badge">'+esc(it.estado || 'publicada')+'</span>' +
          '</div>' +
          '<div class="mini-meta">'+esc(fmtPub(it))+'</div>' +
        '</div>' +
      '</div>';
  }

  function loadHome(){
    apiGet('action=home&page_key=' + encodeURIComponent(pageKey))
      .then(function(j){
        var fwrap = document.getElementById('newsFeaturedWrap');
        var mwrap = document.getElementById('newsMiniWrap');
        if (!fwrap || !mwrap) return;

if (!j || !j.ok) {
  fwrap.innerHTML = featuredEmptyHtml();
  mwrap.innerHTML = '<pre class="text-danger small mb-0" style="white-space:pre-wrap;">'+esc(errMsg(j, 'No se pudo cargar noticias.'))+'</pre>';
  return;
}


        homeCache = {};
        if (j.featured && j.featured.id) homeCache[String(j.featured.id)] = j.featured;
        (j.items || []).forEach(function(it){
          if (it && it.id) homeCache[String(it.id)] = it;
        });

        if (j.featured) fwrap.innerHTML = featuredHtml(j.featured);
        else fwrap.innerHTML = featuredEmptyHtml();

        var items = j.items || [];
        if (!items.length) {
          mwrap.innerHTML = '<div class="text-muted">No hay más noticias por ahora.</div>';
        } else {
          mwrap.innerHTML = items.map(miniHtml).join('');
        }

        bindHomeClicks();
      })
      .catch(function(){
        var fwrap = document.getElementById('newsFeaturedWrap');
        var mwrap = document.getElementById('newsMiniWrap');
        if (fwrap) fwrap.innerHTML = featuredEmptyHtml();
        if (mwrap) mwrap.innerHTML = '<div class="text-danger">Error de red.</div><div class="text-muted small">Si estás logueado con permisos, abre el Panel y reintenta (debug activado).</div>';

      });
  }

  function bindHomeClicks(){
    var root = document.getElementById('newsSection');
    if (!root) return;

    root.querySelectorAll('[data-news-view]').forEach(function(el){
      el.addEventListener('click', function(){
        var id = this.getAttribute('data-news-view') || '';
        if (id && homeCache[id]) {
          openView(homeCache[id]);
        } else if (id) {
          // fallback
          apiGet('action=get&page_key=' + encodeURIComponent(pageKey) + '&id=' + encodeURIComponent(id))
            .then(function(j){
              if (j && j.ok && j.item) openView(j.item);
            });
        }
      });
    });
  }

  // ===== ARCHIVO (listar/paginacion) =====
  var panelPage = 1;

  function panelParams(){
    var desde = document.getElementById('newsDesde') ? document.getElementById('newsDesde').value : '';
    var hasta = document.getElementById('newsHasta') ? document.getElementById('newsHasta').value : '';
    var q = document.getElementById('newsQ') ? document.getElementById('newsQ').value : '';
    var estado = document.getElementById('newsEstado') ? document.getElementById('newsEstado').value : '';
    return {
      action: 'listar',
      page_key: pageKey,
      page: String(panelPage),
      desde: desde,
      hasta: hasta,
      q: q,
      estado: estado
    };
  }

  function loadPanel(){
    hideAlert('newsPanelAlert');

    var p = panelParams();
    var qs = Object.keys(p).map(function(k){
      return encodeURIComponent(k) + '=' + encodeURIComponent(p[k]);
    }).join('&');

    apiGet(qs).then(function(j){
      var tb = document.getElementById('newsTbody');
      if (!tb) return;

      if (!j || !j.ok) {
  tb.innerHTML = '<tr><td colspan="5"><pre class="text-danger small mb-0">'+esc(errMsg(j, 'No se pudo listar noticias.'))+'</pre></td></tr>';
  return;
}


      var items = j.items || [];
      if (!items.length){
        tb.innerHTML = '<tr><td colspan="5" class="text-muted">No hay noticias para estos filtros.</td></tr>';
      } else {
        tb.innerHTML = items.map(function(it){
          var img = it.imagen_portada
            ? '<img src="'+esc(it.imagen_portada)+'" style="width:70px;height:44px;object-fit:cover;border-radius:10px;">'
            : '<span class="text-muted">-</span>';

          var acc = '<button class="btn btn-sm btn-outline-primary" data-news-open="'+esc(it.id)+'">Ver</button>';
          if (canManage){
            acc =
              '<button class="btn btn-sm btn-outline-primary me-1" data-news-edit="'+esc(it.id)+'">Editar</button>' +
              '<button class="btn btn-sm btn-outline-danger me-1" data-news-del="'+esc(it.id)+'">Eliminar</button>' +
              '<button class="btn btn-sm btn-outline-secondary" data-news-open="'+esc(it.id)+'">Ver</button>';
          }

          var sub = it.resumen ? String(it.resumen) : '';
          if (sub.length > 110) sub = sub.substring(0,110) + '...';

          return '' +
            '<tr>' +
              '<td>'+img+'</td>' +
              '<td><div class="fw-bold">'+esc(it.titulo)+'</div>' +
                  (sub ? '<div class="text-muted small">'+esc(sub)+'</div>' : '') +
              '</td>' +
              '<td class="small">'+esc(fmtPub(it))+'</td>' +
              '<td><span class="badge bg-secondary">'+esc(it.estado)+'</span></td>' +
              '<td>'+acc+'</td>' +
            '</tr>';
        }).join('');

        tb.querySelectorAll('[data-news-open]').forEach(function(b){
          b.addEventListener('click', function(){
            var id = this.getAttribute('data-news-open');
            apiGet('action=get&page_key=' + encodeURIComponent(pageKey) + '&id=' + encodeURIComponent(id))
              .then(function(j){
                if (j && j.ok && j.item) openView(j.item);
              });
          });
        });

        if (canManage){
          tb.querySelectorAll('[data-news-edit]').forEach(function(b){
            b.addEventListener('click', function(){
              openFormEdit(this.getAttribute('data-news-edit'));
            });
          });
          tb.querySelectorAll('[data-news-del]').forEach(function(b){
            b.addEventListener('click', function(){
              deleteNoticia(this.getAttribute('data-news-del'));
            });
          });
        }
      }

      var info = document.getElementById('newsInfo');
      if (info){
        var total = j.total || 0;
        var per = j.per_page || 10;
        var from = total ? ((panelPage-1)*per + 1) : 0;
        var to = Math.min(panelPage*per, total);
        info.textContent = 'Mostrando ' + from + ' - ' + to + ' de ' + total;
      }
    }).catch(function(){
      var tb = document.getElementById('newsTbody');
      if (tb) tb.innerHTML = '<tr><td colspan="5" class="text-danger">Error de red.</td></tr>';
    });
  }

  // ===== FORM =====
  function setFotoPrev(url, msg){
    var img = document.getElementById('newsFotoPrev');
    var m = document.getElementById('newsFotoMsg');
    if (!img || !m) return;

    if (url) {
      img.src = url;
      img.style.display = 'block';
    } else {
      img.removeAttribute('src');
      img.style.display = 'none';
    }
    m.textContent = msg || '';
  }

  function resetForm(){
    hideAlert('newsFormAlert');
    document.getElementById('newsId').value = '';
    document.getElementById('newsTitulo').value = '';
    document.getElementById('newsResumen').value = '';
    document.getElementById('newsCuerpo').value = '';
    document.getElementById('newsPubFecha').value = '';
    document.getElementById('newsPubHora').value = '';
    document.getElementById('newsEstadoForm').value = 'publicada';
    document.getElementById('newsDestacada').checked = false;
    document.getElementById('newsTags').value = '';
    document.getElementById('newsFoto').value = '';
    setFotoPrev('', '');
  }

  function openFormNew(){
    if (!canManage) return;
    resetForm();
    document.getElementById('newsFormTitle').textContent = 'Nueva noticia';
    var el = document.getElementById('newsModalForm');
    if (el && window.bootstrap) new bootstrap.Modal(el).show();
  }

  function openFormEdit(id){
    if (!canManage) return;
    resetForm();
    document.getElementById('newsFormTitle').textContent = 'Editar noticia';

    apiGet('action=get&page_key=' + encodeURIComponent(pageKey) + '&id=' + encodeURIComponent(id))
      .then(function(j){
        if (!j || !j.ok) {
  showAlert('newsPanelAlert', 'danger', errMsg(j, 'No se pudo abrir la noticia.'));
  return;
}


        var it = j.item;
        document.getElementById('newsId').value = it.id;
        document.getElementById('newsTitulo').value = it.titulo || '';
        document.getElementById('newsResumen').value = it.resumen || '';
        document.getElementById('newsCuerpo').value = it.cuerpo || '';
        document.getElementById('newsEstadoForm').value = it.estado || 'publicada';
        document.getElementById('newsDestacada').checked = (String(it.destacada) === '1');

        document.getElementById('newsTags').value = it.etiquetas_csv || '';

        // publicada_en (YYYY-MM-DD HH:MM:SS)
        if (it.publicada_en) {
          var p = String(it.publicada_en);
          if (p.indexOf(' ') !== -1) {
            var parts = p.split(' ');
            document.getElementById('newsPubFecha').value = parts[0] || '';
            document.getElementById('newsPubHora').value = (parts[1] || '').substring(0,5);
          }
        }

        if (it.imagen_portada) {
          setFotoPrev(it.imagen_portada, 'Foto actual');
        }

        var el = document.getElementById('newsModalForm');
        if (el && window.bootstrap) new bootstrap.Modal(el).show();
      })
      .catch(function(){
        showAlert('newsPanelAlert', 'danger', 'Error de red al abrir la noticia.');
      });
  }

  function saveNoticia(){
    if (!canManage) return;
    hideAlert('newsFormAlert');

    var fd = new FormData(document.getElementById('newsForm'));
    fd.append('action', 'save');

    apiPost(fd).then(function(j){
      if (!j || !j.ok) {
  showAlert('newsFormAlert', 'danger', errMsg(j, 'No se pudo guardar.'));
  return;
}


      showAlert('newsFormAlert', 'success', 'Guardado correctamente.');
      loadHome();
      loadPanel();

      setTimeout(function(){
        var el = document.getElementById('newsModalForm');
        if (el && window.bootstrap) {
          var inst = bootstrap.Modal.getInstance(el);
          if (inst) inst.hide();
        }
      }, 650);
    }).catch(function(){
      showAlert('newsFormAlert', 'danger', 'Error de red o servidor.');
    });
  }

  function deleteNoticia(id){
    if (!canManage) return;
    if (!confirm('¿Eliminar esta noticia? Se borrará la información y la foto (si existe).')) return;

    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('csrf', csrf);
    fd.append('page_key', pageKey);
    fd.append('id', String(id));

    apiPost(fd).then(function(j){
if (!j || !j.ok) {
  showAlert('newsPanelAlert', 'danger', errMsg(j, 'No se pudo eliminar.'));
  return;
}

      showAlert('newsPanelAlert', 'success', 'Noticia eliminada.');
      loadHome();
      loadPanel();
    }).catch(function(){
      showAlert('newsPanelAlert', 'danger', 'Error de red o servidor.');
    });
  }

  // ====== BIND ======
  document.addEventListener('DOMContentLoaded', function(){
    loadHome();

    var panelEl = document.getElementById('newsModalPanel');
    if (panelEl) {
      panelEl.addEventListener('shown.bs.modal', function(){
        panelPage = 1;
        loadPanel();
      });
    }

    var btnF = document.getElementById('newsBtnFiltrar');
    if (btnF) btnF.addEventListener('click', function(){ panelPage = 1; loadPanel(); });

    var prev = document.getElementById('newsPrev');
    var next = document.getElementById('newsNext');
    if (prev) prev.addEventListener('click', function(){ if (panelPage > 1){ panelPage--; loadPanel(); } });
    if (next) next.addEventListener('click', function(){ panelPage++; loadPanel(); });

    if (canManage) {
      var btnNewTop = document.getElementById('newsBtnNuevo');
      if (btnNewTop) btnNewTop.addEventListener('click', openFormNew);

      var btnSave = document.getElementById('newsBtnGuardar');
      if (btnSave) btnSave.addEventListener('click', saveNoticia);

      var foto = document.getElementById('newsFoto');
      if (foto) {
        foto.addEventListener('change', function(){
          if (!this.files || !this.files[0]) {
            setFotoPrev('', '');
            return;
          }
          var f = this.files[0];
          var url = URL.createObjectURL(f);
          setFotoPrev(url, 'Nueva foto seleccionada');
        });
      }
    }
  });
})();
</script>
