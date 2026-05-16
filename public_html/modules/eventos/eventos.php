<?php
declare(strict_types=1);

// modules/eventos/eventos.php
// Se incluye desde un módulo de página. Si no se incluye, no aparece nada.
// Bloquear acceso directo:
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

if (defined('MOD_EVENTOS_CARGADO')) {
    return; // evita doble render accidental
}
define('MOD_EVENTOS_CARGADO', 1);

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';

date_default_timezone_set('America/Lima');

$pageKey = $_GET['p'] ?? '';
$pageKey = is_string($pageKey) ? $pageKey : '';
if ($pageKey === '' || !preg_match('/^[a-z0-9\-\_]+$/i', $pageKey)) {
    return; // eventos solo para páginas del router ?p=
}

$user = auth_user();
$isLogged = (bool)$user;
$rolCodigo = ($user && isset($user['rol']['codigo'])) ? (string)$user['rol']['codigo'] : '';
$canManage = in_array($rolCodigo, array('desarrollador', 'director', 'secretaria'), true);

$apiUrl = 'modules/eventos/eventos_api.php'; // relativo a index.php (sin raíz)
$csrf = csrf_token();
?>

<style>
/* ====== MOD EVENTOS (aislado) ====== */
#evtSection .evt-card{
  border:0;
  border-radius:18px;
  overflow:hidden;
  box-shadow:0 8px 22px rgba(0,0,0,.08);
  height:100%;
  background:#fff;
}

#evtSection .evt-clickable{
  cursor: pointer;
}
#evtSection .evt-clickable:hover{
  transform: translateY(-2px);
  transition: transform .12s ease;
}

/* ===== Modal Vista Evento (lectura) ===== */
/* OJO: el modal está FUERA de #evtSection, por eso se estiliza por #evtModalView */
#evtModalView .evt-modal-img{
  width: 100%;
  max-height: 340px;
  height: auto;
  object-fit: contain;
  background:#f4f6f8;
  border-radius: 16px;
  display: block;
}
#evtModalView .evt-modal-img-ph{
  width: 100%;
  height: 230px;
  border-radius: 16px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#eef2f7,#f8fafc);
  color:#6c757d;
  font-size:56px;
}
#evtModalView .evt-view-details{
  background:#f8f9fa;
  border-radius: 16px;
  padding: 14px;
}
#evtModalView .evt-modal-meta{
  font-size: 13px;
  color:#6c757d;
  line-height: 1.35;
}
#evtModalView .evt-view-media-wrap{
  position:relative;
}
#evtModalView .evt-view-expand{
  position:absolute;
  right:10px;
  bottom:10px;
  z-index:2;
  border:0;
  padding:6px 10px;
  font-size:12px;
  font-weight:600;
  color:#fff;
  background:rgba(0,0,0,.72);
}
#evtModalView .evt-modal-tags{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
  margin-top: 10px;
}
#evtModalView .evt-modal-tags .evt-tag{
  background:#e9ecef;
}
#evtModalView .evt-view-title{
  font-size: 22px;
  font-weight: 700;
  line-height: 1.2;
}
#evtModalView .evt-view-parrafo{
  white-space: pre-wrap;
  font-size: 15px;
  line-height: 1.55;
}


#evtSection .evt-img{
  width:100%;
  height:150px;
  object-fit:cover;
  display:block;
}
#evtSection .evt-img-ph{
  height:150px;
  display:flex;
  align-items:center;
  justify-content:center;
  background:linear-gradient(135deg,#eef2f7,#f8fafc);
  color:#6c757d;
  font-size:44px;
}
#evtSection .evt-parrafo{
  display:-webkit-box;
  -webkit-line-clamp:4;
  -webkit-box-orient:vertical;
  overflow:hidden;
  min-height: 96px;
}
#evtSection .evt-meta{
  font-size:13px;
  color:#6c757d;
}
#evtSection .evt-tags{
  display:flex;
  flex-wrap:wrap;
  gap:6px;
}
#evtSection .evt-tag{
  display:inline-flex;
  align-items:center;
  padding:3px 10px;
  border-radius:999px;
  background:#f1f3f5;
  font-size:12px;
}
#evtSection .evt-actions{
  display:flex;
  gap:8px;
}
#evtSection .evt-skel{
  height: 330px;
  background:linear-gradient(90deg,#f3f3f3,#e9e9e9,#f3f3f3);
  background-size:200% 100%;
  animation:evtSk 1.1s infinite;
  border-radius:18px;
}
@keyframes evtSk{
  0%{background-position:0% 0%}
  100%{background-position:-200% 0%}
}
#evtPanelAlert, #evtFormAlert { display:none; }
#evtTagsChips .evt-chip{
  display:inline-flex;
  align-items:center;
  gap:6px;
  padding:6px 10px;
  border-radius:999px;
  background:#f1f3f5;
  margin:4px 6px 0 0;
  font-size:13px;
}
#evtTagsChips .evt-chip button{
  border:0;
  background:transparent;
  cursor:pointer;
  font-weight:bold;
}

#evtImageViewerModal .evt-iv-stage{
  position: relative;
  width:100%;
  height:72vh;
  background:#0f1115;
  overflow:hidden;
  cursor: grab;
}
#evtImageViewerModal .evt-iv-stage.is-dragging{
  cursor: grabbing;
}
#evtImageViewerModal .evt-iv-img{
  position:absolute;
  left:0;
  top:0;
  transform-origin: 0 0;
  user-select:none;
  -webkit-user-drag:none;
  max-width:none;
}
#evtImageViewerModal .evt-iv-toolbar .btn{
  min-width: 44px;
}
</style>

<section class="container-xxl py-5" id="evtSection"
         data-pagekey="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>"
         data-api="<?php echo htmlspecialchars($apiUrl, ENT_QUOTES, 'UTF-8'); ?>"
         data-csrf="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>"
         data-logged="<?php echo $isLogged ? '1' : '0'; ?>"
         data-canmanage="<?php echo $canManage ? '1' : '0'; ?>">

  <div class="container">
    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-3">
      <div>
        <p class="fs-5 fw-bold text-primary mb-1">Próximos eventos</p>
        <h2 class="mb-0">Actividades para esta página</h2>
      </div>

      <?php if ($isLogged): ?>
        <button type="button" class="btn btn-outline-primary"
                data-bs-toggle="modal" data-bs-target="#evtModalPanel">
          Ver todos los eventos
        </button>
      <?php endif; ?>
    </div>

    <div class="row g-4" id="evtTop4">
      <div class="col-lg-3 col-md-6"><div class="evt-skel"></div></div>
      <div class="col-lg-3 col-md-6"><div class="evt-skel"></div></div>
      <div class="col-lg-3 col-md-6"><div class="evt-skel"></div></div>
      <div class="col-lg-3 col-md-6"><div class="evt-skel"></div></div>
    </div>
  </div>
</section>

<!-- ===== MODAL VISTA EVENTO (lectura) ===== -->
<div class="modal fade" id="evtModalView" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title mb-0">Detalle del evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div class="row g-4 align-items-start">

          <!-- IZQUIERDA: imagen + detalles debajo -->
          <div class="col-lg-4">
            <div class="evt-view-media-wrap">
              <img id="evtViewImg" class="evt-modal-img" alt="Evento" style="display:none;">
              <div id="evtViewImgPh" class="evt-modal-img-ph"><i class="bi bi-calendar-event"></i></div>
              <button type="button" class="btn evt-view-expand" id="evtViewExpand" style="display:none;">Expandir</button>
            </div>

            <div class="evt-view-details mt-3">
              <div class="d-flex justify-content-between align-items-center gap-2">
                <span class="badge bg-secondary" id="evtViewEstado">indefinido</span>
              </div>

              <div class="evt-modal-meta mt-2" id="evtViewMeta">Fecha por definir</div>

              <div class="evt-modal-meta mt-2" id="evtViewCoordWrap" style="display:none;">
                <strong>Coordinador:</strong> <span id="evtViewCoord"></span>
              </div>

              <div class="evt-modal-meta mt-1" id="evtViewPonWrap" style="display:none;">
                <strong>Ponente:</strong> <span id="evtViewPon"></span>
              </div>

              <div class="evt-modal-tags" id="evtViewTags"></div>
            </div>
          </div>

          <!-- DERECHA: título + párrafo -->
          <div class="col-lg-8">
            <div class="evt-view-title mb-3" id="evtViewTitle">Evento</div>
            <div class="evt-view-parrafo" id="evtViewParrafo">Sin descripción.</div>
          </div>

        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" type="button" data-bs-dismiss="modal">Cerrar</button>
      </div>

    </div>
  </div>
</div>

<!-- ===== MODAL VISOR DE IMAGEN (EVENTOS) ===== -->
<div class="modal fade" id="evtImageViewerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title mb-0">Imagen completa</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body pt-2">
        <div class="d-flex justify-content-end gap-2 mb-2 evt-iv-toolbar">
          <button type="button" class="btn btn-outline-secondary btn-sm" id="evtIvZoomOut">-</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="evtIvReset">100%</button>
          <button type="button" class="btn btn-outline-secondary btn-sm" id="evtIvZoomIn">+</button>
        </div>
        <div class="evt-iv-stage" id="evtIvStage">
          <img id="evtIvImg" class="evt-iv-img" alt="Imagen evento">
        </div>
      </div>
    </div>
  </div>
</div>


<?php if ($isLogged): ?>
<!-- ===== MODAL PANEL ===== -->
<div class="modal fade" id="evtModalPanel" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <div>
          <h5 class="modal-title mb-0">Panel de eventos</h5>
          <div class="text-muted small">Página: <strong><?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?></strong></div>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="evtPanelAlert" class="alert"></div>

        <div class="d-flex flex-wrap gap-2 align-items-end mb-3">
          <div>
            <label class="form-label mb-1">Desde</label>
            <input type="date" class="form-control" id="evtDesde">
          </div>
          <div>
            <label class="form-label mb-1">Hasta</label>
            <input type="date" class="form-control" id="evtHasta">
          </div>
          <div>
            <label class="form-label mb-1">Estado</label>
            <select class="form-select" id="evtEstado">
              <option value="">Todos</option>
              <option value="activo">Activo</option>
              <option value="inactivo">Inactivo</option>
              <option value="reprogramado">Reprogramado</option>
              <option value="cancelado">Cancelado</option>
              <option value="indefinido">Indefinido</option>
            </select>
          </div>
          <div class="form-check mb-1">
            <input class="form-check-input" type="checkbox" id="evtInclSinFecha" checked>
            <label class="form-check-label" for="evtInclSinFecha">Incluir sin fecha</label>
          </div>
          <div>
            <button class="btn btn-primary" type="button" id="evtBtnFiltrar">Filtrar</button>
          </div>

          <?php if ($canManage): ?>
          <div class="ms-auto">
            <button class="btn btn-success" type="button" id="evtBtnNuevo">Nuevo evento</button>
          </div>
          <?php endif; ?>
        </div>

        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead>
              <tr>
                <th style="width:80px;">Foto</th>
                <th>Título</th>
                <th style="width:210px;">Fecha/Hora</th>
                <th style="width:140px;">Estado</th>
                <th style="width:260px;">Etiquetas</th>
                <th style="width:180px;">Acciones</th>
              </tr>
            </thead>
            <tbody id="evtTbody">
              <tr><td colspan="6" class="text-muted">Cargando...</td></tr>
            </tbody>
          </table>
        </div>

        <div class="d-flex align-items-center justify-content-between">
          <div class="text-muted small" id="evtInfo"></div>
          <div class="btn-group" role="group">
            <button class="btn btn-outline-secondary" type="button" id="evtPrev">Anterior</button>
            <button class="btn btn-outline-secondary" type="button" id="evtNext">Siguiente</button>
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
<div class="modal fade" id="evtModalForm" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">

      <div class="modal-header">
        <h5 class="modal-title" id="evtFormTitle">Evento</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>

      <div class="modal-body">
        <div id="evtFormAlert" class="alert"></div>

        <form id="evtForm" enctype="multipart/form-data">
          <input type="hidden" name="csrf" value="<?php echo htmlspecialchars($csrf, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="page_key" value="<?php echo htmlspecialchars($pageKey, ENT_QUOTES, 'UTF-8'); ?>">
          <input type="hidden" name="id" id="evtId" value="">
          <input type="hidden" name="tags_csv" id="evtTagsCsv" value="">

          <div class="mb-3">
            <label class="form-label">Título (obligatorio)</label>
            <input class="form-control" name="titulo" id="evtTitulo" maxlength="200" required>
          </div>

          <div class="mb-3">
            <label class="form-label">Párrafo (opcional)</label>
            <textarea class="form-control" name="parrafo" id="evtParrafo" maxlength="2000" rows="6"
                      placeholder="Escribe una descripción..."></textarea>
            <div class="text-muted small mt-1">En la card pública se recorta para mantener tamaño.</div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Coordinador (opcional)</label>
              <input class="form-control" name="coordinador" id="evtCoordinador" maxlength="160">
            </div>
            <div class="col-md-6">
              <label class="form-label">Ponente (opcional)</label>
              <input class="form-control" name="ponente" id="evtPonente" maxlength="160">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-3">
              <label class="form-label">Inicio fecha</label>
              <input type="date" class="form-control" name="inicio_fecha" id="evtInicioFecha">
            </div>
            <div class="col-md-3">
              <label class="form-label">Inicio hora</label>
              <input type="time" class="form-control" name="inicio_hora" id="evtInicioHora">
            </div>
            <div class="col-md-3">
              <label class="form-label">Fin fecha</label>
              <input type="date" class="form-control" name="fin_fecha" id="evtFinFecha">
            </div>
            <div class="col-md-3">
              <label class="form-label">Fin hora</label>
              <input type="time" class="form-control" name="fin_hora" id="evtFinHora">
            </div>
          </div>

          <div class="row g-3 mb-3">
            <div class="col-md-6">
              <label class="form-label">Estado</label>
              <select class="form-select" name="estado" id="evtEstadoForm">
                <option value="activo">Activo</option>
                <option value="inactivo">Inactivo</option>
                <option value="reprogramado">Reprogramado</option>
                <option value="cancelado">Cancelado</option>
                <option value="indefinido">Indefinido</option>
              </select>
              <div class="text-muted small mt-1">El público NO ve los inactivos.</div>
            </div>

            <div class="col-md-6">
              <label class="form-label">Foto del evento (opcional)</label>
              <input type="file" class="form-control" name="foto" id="evtFoto" accept="image/*">
              <div class="mt-2 d-flex align-items-center gap-2">
                <img id="evtFotoPrev" alt="Foto" style="display:none;max-width:180px;border-radius:14px;">
                <div class="text-muted small" id="evtFotoMsg"></div>
              </div>
            </div>
          </div>

          <div class="mb-2">
            <label class="form-label">Etiquetas (opcional)</label>
            <input class="form-control" id="evtTagInput" list="evtTagsDL"
                   placeholder="Escribe y presiona Enter (o coma). Ej: feria, voluntariado">
            <datalist id="evtTagsDL"></datalist>
            <div class="mt-2" id="evtTagsChips"></div>
          </div>
        </form>
      </div>

      <div class="modal-footer">
        <button class="btn btn-outline-secondary" data-bs-dismiss="modal" type="button">Cancelar</button>
        <button class="btn btn-primary" type="button" id="evtBtnGuardar">Guardar</button>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
(function(){
  var sec = document.getElementById('evtSection');
  if (!sec) return;

  var pageKey = sec.getAttribute('data-pagekey') || '';
  var api = sec.getAttribute('data-api') || 'modules/eventos/eventos_api.php';
  var csrf = sec.getAttribute('data-csrf') || '';
    var logged = (sec.getAttribute('data-logged') === '1');
  var canManage = (sec.getAttribute('data-canmanage') === '1');

  // Cache de los 4 eventos para abrir modal sin otro request
  var top4Cache = {};
  var currentViewImg = '';


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

  function fmtFH(it){
    var t = [];
    var hasStart = it.inicio_fecha || it.inicio_hora;
    var hasEnd = it.fin_fecha || it.fin_hora;

    if (!hasStart && !hasEnd) return 'Fecha por definir';

    if (hasStart){
      var a = [];
      if (it.inicio_fecha) a.push(it.inicio_fecha);
      if (it.inicio_hora) a.push(it.inicio_hora.substring(0,5));
      t.push('Inicio: ' + a.join(' '));
    }
    if (hasEnd){
      var b = [];
      if (it.fin_fecha) b.push(it.fin_fecha);
      if (it.fin_hora) b.push(it.fin_hora.substring(0,5));
      t.push('Fin: ' + b.join(' '));
    }
    return t.join(' | ');
  }

    function openView(it){
    if (!it) return;

    var title = document.getElementById('evtViewTitle');
    var estado = document.getElementById('evtViewEstado');
    var meta = document.getElementById('evtViewMeta');

    var img = document.getElementById('evtViewImg');
    var imgPh = document.getElementById('evtViewImgPh');
    var imgExpand = document.getElementById('evtViewExpand');

    var coordWrap = document.getElementById('evtViewCoordWrap');
    var coord = document.getElementById('evtViewCoord');
    var ponWrap = document.getElementById('evtViewPonWrap');
    var pon = document.getElementById('evtViewPon');

    var tags = document.getElementById('evtViewTags');
    var parrafo = document.getElementById('evtViewParrafo');

    if (title) title.textContent = (it.titulo || 'Evento');
    if (estado) estado.textContent = (it.estado || 'indefinido');
    if (meta) meta.textContent = fmtFH(it);

    // Imagen
    if (img && imgPh) {
      if (it.foto_evento) {
        currentViewImg = String(it.foto_evento);
        img.src = currentViewImg;
        img.style.display = 'block';
        imgPh.style.display = 'none';
        if (imgExpand) imgExpand.style.display = 'inline-flex';
      } else {
        currentViewImg = '';
        img.removeAttribute('src');
        img.style.display = 'none';
        imgPh.style.display = 'flex';
        if (imgExpand) imgExpand.style.display = 'none';
      }
    }

    // Coordinador / Ponente
    if (coordWrap && coord) {
      if (it.coordinador) {
        coord.textContent = String(it.coordinador);
        coordWrap.style.display = 'block';
      } else {
        coord.textContent = '';
        coordWrap.style.display = 'none';
      }
    }
    if (ponWrap && pon) {
      if (it.ponente) {
        pon.textContent = String(it.ponente);
        ponWrap.style.display = 'block';
      } else {
        pon.textContent = '';
        ponWrap.style.display = 'none';
      }
    }

        // Tags
    if (tags) {
      var arr = parseTags(it.tags_csv);
      if (!arr.length) {
        tags.innerHTML = '<span class="text-muted small">Sin etiquetas</span>';
      } else {
        tags.innerHTML = arr.map(function(t){
          return '<span class="evt-tag">'+esc(t)+'</span>';
        }).join('');
      }
    }

    // Párrafo
    if (parrafo) {
      var txt = it.parrafo ? String(it.parrafo) : '';
      txt = txt.trim();
      parrafo.textContent = (txt !== '') ? txt : 'Sin descripción.';
    }


    // Mostrar modal
    var el = document.getElementById('evtModalView');
    if (el && window.bootstrap) {
      new bootstrap.Modal(el).show();
    }
  }


  function parseTags(csv){
    if (!csv) return [];
    return String(csv).split(',')
      .map(function(x){ return x.trim(); })
      .filter(function(x){ return x.length > 0; })
      .slice(0, 25);
  }

  function tagsHtml(csv){
    var arr = parseTags(csv);
    if (!arr.length) return '';
    return arr.map(function(t){
      return '<span class="evt-tag">'+esc(t)+'</span>';
    }).join('');
  }

    function cardHtml(it){
    var img = '';
    if (it.foto_evento) {
      img = '<img class="evt-img" src="'+esc(it.foto_evento)+'" alt="Evento">';
    } else {
      img = '<div class="evt-img-ph"><i class="bi bi-calendar-event"></i></div>';
    }

    var par = it.parrafo ? String(it.parrafo) : '';
    if (par.length > 220) par = par.substring(0, 220) + '...';

    var manageBtns = '';
    if (logged && canManage && it.id) {
      manageBtns =
        '<div class="evt-actions mt-2">' +
          '<button type="button" class="btn btn-sm btn-outline-primary" data-evt-edit="'+esc(it.id)+'">Editar</button>' +
          '<button type="button" class="btn btn-sm btn-outline-danger" data-evt-del="'+esc(it.id)+'">Eliminar</button>' +
        '</div>';
    }

    var clickable = (it.id ? ' evt-clickable' : '');
    var viewAttr = (it.id ? ' data-evt-view="'+esc(it.id)+'"' : '');

    return '' +
      '<div class="card evt-card'+clickable+'"'+viewAttr+'>' +
        img +
        '<div class="card-body">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<h5 class="mb-1">'+esc(it.titulo || 'Disponible')+'</h5>' +
            '<span class="badge bg-secondary">'+esc(it.estado || 'indefinido')+'</span>' +
          '</div>' +
          '<div class="evt-meta mb-2">'+esc(fmtFH(it))+'</div>' +
          (it.coordinador ? '<div class="evt-meta">Coordinador: '+esc(it.coordinador)+'</div>' : '') +
          (it.ponente ? '<div class="evt-meta">Ponente: '+esc(it.ponente)+'</div>' : '') +
          '<p class="mt-2 evt-parrafo mb-2">'+esc(par)+'</p>' +
          '<div class="evt-tags">'+tagsHtml(it.tags_csv)+'</div>' +
          manageBtns +
        '</div>' +
      '</div>';
  }


  function cardDisponibleHtml(){
    return '' +
      '<div class="card evt-card">' +
        '<div class="evt-img-ph"><i class="bi bi-plus-circle"></i></div>' +
        '<div class="card-body">' +
          '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<h5 class="mb-1">Disponible</h5>' +
            '<span class="badge bg-light text-dark">sin evento</span>' +
          '</div>' +
          '<div class="evt-meta mb-2">Aún no hay un evento configurado.</div>' +
          '<p class="mt-2 evt-parrafo mb-2">Cuando el equipo publique un evento, aparecerá aquí.</p>' +
        '</div>' +
      '</div>';
  }

  function apiGet(params){
    var u = api + '?' + params;
    return fetch(u, { credentials: 'same-origin' })
      .then(function(r){ return r.json(); });
  }

  function apiPost(formData){
    return fetch(api, {
      method: 'POST',
      credentials: 'same-origin',
      body: formData
    }).then(function(r){ return r.json(); });
  }

      function loadTop4(){
    apiGet('action=top4&page_key=' + encodeURIComponent(pageKey))
      .then(function(j){
        var wrap = document.getElementById('evtTop4');
        if (!wrap) return;

        if (!j || !j.ok) {
          wrap.innerHTML = '<div class="col-12 text-danger">No se pudo cargar eventos.</div>';
          return;
        }

        var items = j.items || [];

        // Cache para abrir modal sin otro request
        top4Cache = {};
        items.forEach(function(it){
          if (it && it.id) top4Cache[String(it.id)] = it;
        });

        var html = '';
        for (var i=0;i<4;i++){
          var it = items[i] || null;
          html += '<div class="col-lg-3 col-md-6">' + (it ? cardHtml(it) : cardDisponibleHtml()) + '</div>';
        }
        wrap.innerHTML = html;

        bindTop4Actions(); // ahora también bind del click para abrir modal
      })
      .catch(function(){
        var wrap = document.getElementById('evtTop4');
        if (wrap) wrap.innerHTML = '<div class="col-12 text-danger">Error cargando eventos.</div>';
      });
  }


    function bindTop4Actions(){
    var wrap = document.getElementById('evtTop4');
    if (!wrap) return;

    // Editar
    wrap.querySelectorAll('[data-evt-edit]').forEach(function(b){
      b.addEventListener('click', function(ev){
        ev.preventDefault();
        ev.stopPropagation();
        openFormEdit(this.getAttribute('data-evt-edit'));
      });
    });

    // Eliminar
    wrap.querySelectorAll('[data-evt-del]').forEach(function(b){
      b.addEventListener('click', function(ev){
        ev.preventDefault();
        ev.stopPropagation();
        var id = this.getAttribute('data-evt-del');
        deleteEvento(id);
      });
    });

    // Ver (clic en card)
    wrap.querySelectorAll('[data-evt-view]').forEach(function(card){
      card.addEventListener('click', function(ev){
        // Si se clickea un botón/link dentro, no abrir modal
        var t = ev.target;
        if (t && (t.tagName === 'BUTTON' || t.tagName === 'A')) return;

        // Fallback si el navegador soporta closest:
        if (t && t.closest) {
          if (t.closest('[data-evt-edit]') || t.closest('[data-evt-del]')) return;
        }

        var id = this.getAttribute('data-evt-view') || '';
        if (id && top4Cache[id]) {
          openView(top4Cache[id]);
        }
      });
    });
  }


  // ===== PANEL (LISTAR) =====
  var panelPage = 1;
  function panelParams(){
    var desde = document.getElementById('evtDesde') ? document.getElementById('evtDesde').value : '';
    var hasta = document.getElementById('evtHasta') ? document.getElementById('evtHasta').value : '';
    var estado = document.getElementById('evtEstado') ? document.getElementById('evtEstado').value : '';
    var incl = document.getElementById('evtInclSinFecha') ? (document.getElementById('evtInclSinFecha').checked ? '1' : '0') : '1';
    return {
      action: 'listar',
      page_key: pageKey,
      page: String(panelPage),
      desde: desde,
      hasta: hasta,
      estado: estado,
      incl_sin_fecha: incl
    };
  }

  function loadPanel(){
    if (!logged) return;

    hideAlert('evtPanelAlert');

    var p = panelParams();
    var qs = Object.keys(p).map(function(k){
      return encodeURIComponent(k) + '=' + encodeURIComponent(p[k]);
    }).join('&');

    apiGet(qs).then(function(j){
      var tb = document.getElementById('evtTbody');
      if (!tb) return;

      if (!j || !j.ok) {
        tb.innerHTML = '<tr><td colspan="6" class="text-danger">No se pudo listar eventos.</td></tr>';
        return;
      }

      var items = j.items || [];
      if (!items.length){
        tb.innerHTML = '<tr><td colspan="6" class="text-muted">No hay eventos para estos filtros.</td></tr>';
      } else {
        tb.innerHTML = items.map(function(it){
          var img = it.foto_evento
            ? '<img src="'+esc(it.foto_evento)+'" style="width:60px;height:40px;object-fit:cover;border-radius:10px;">'
            : '<span class="text-muted">-</span>';

          var tags = parseTags(it.tags_csv).slice(0,6).join(', ');
          if (!tags) tags = '-';

          var acc = '<span class="text-muted">-</span>';
          if (canManage){
            acc =
              '<button class="btn btn-sm btn-outline-primary me-1" data-panel-edit="'+esc(it.id)+'">Editar</button>' +
              '<button class="btn btn-sm btn-outline-danger" data-panel-del="'+esc(it.id)+'">Eliminar</button>';
          }

          return '' +
            '<tr>' +
              '<td>'+img+'</td>' +
              '<td><div class="fw-bold">'+esc(it.titulo)+'</div>' +
                  (it.parrafo ? '<div class="text-muted small">'+esc(String(it.parrafo).substring(0,120))+'...</div>' : '') +
              '</td>' +
              '<td class="small">'+esc(fmtFH(it))+'</td>' +
              '<td><span class="badge bg-secondary">'+esc(it.estado)+'</span></td>' +
              '<td class="small">'+esc(tags)+'</td>' +
              '<td>'+acc+'</td>' +
            '</tr>';
        }).join('');

        if (canManage){
          tb.querySelectorAll('[data-panel-edit]').forEach(function(b){
            b.addEventListener('click', function(){
              openFormEdit(this.getAttribute('data-panel-edit'));
            });
          });
          tb.querySelectorAll('[data-panel-del]').forEach(function(b){
            b.addEventListener('click', function(){
              deleteEvento(this.getAttribute('data-panel-del'));
            });
          });
        }
      }

      var info = document.getElementById('evtInfo');
      if (info){
        var total = j.total || 0;
        var per = j.per_page || 10;
        var from = total ? ((panelPage-1)*per + 1) : 0;
        var to = Math.min(panelPage*per, total);
        info.textContent = 'Mostrando ' + from + ' - ' + to + ' de ' + total;
      }
    }).catch(function(){
      var tb = document.getElementById('evtTbody');
      if (tb) tb.innerHTML = '<tr><td colspan="6" class="text-danger">Error de red.</td></tr>';
    });
  }

  // ===== FORM (CREAR/EDITAR) =====
  var tagsArr = [];

  function resetForm(){
    hideAlert('evtFormAlert');
    tagsArr = [];
    document.getElementById('evtId').value = '';
    document.getElementById('evtTitulo').value = '';
    document.getElementById('evtParrafo').value = '';
    document.getElementById('evtCoordinador').value = '';
    document.getElementById('evtPonente').value = '';
    document.getElementById('evtInicioFecha').value = '';
    document.getElementById('evtInicioHora').value = '';
    document.getElementById('evtFinFecha').value = '';
    document.getElementById('evtFinHora').value = '';
    document.getElementById('evtEstadoForm').value = 'activo';
    document.getElementById('evtFoto').value = '';
    setFotoPrev('', '');
    renderTagChips();
    document.getElementById('evtTagInput').value = '';
    document.getElementById('evtTagsCsv').value = '';
  }

  function setFotoPrev(url, msg){
    var img = document.getElementById('evtFotoPrev');
    var m = document.getElementById('evtFotoMsg');
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

  function renderTagChips(){
    var box = document.getElementById('evtTagsChips');
    if (!box) return;
    if (!tagsArr.length) {
      box.innerHTML = '<span class="text-muted small">Sin etiquetas.</span>';
      return;
    }
    box.innerHTML = tagsArr.map(function(t, i){
      return '<span class="evt-chip">'+esc(t)+' <button type="button" data-tag-rm="'+i+'">×</button></span>';
    }).join('');

    box.querySelectorAll('[data-tag-rm]').forEach(function(b){
      b.addEventListener('click', function(){
        var idx = parseInt(this.getAttribute('data-tag-rm'), 10);
        if (!isNaN(idx)) {
          tagsArr.splice(idx, 1);
          renderTagChips();
        }
      });
    });
  }

  function addTag(t){
    t = String(t || '').trim();
    if (!t) return;
    if (t.length > 60) t = t.substring(0, 60);
    if (tagsArr.indexOf(t) !== -1) return;
    if (tagsArr.length >= 25) return;
    tagsArr.push(t);
    renderTagChips();
  }

  function loadTagSuggestions(q){
    if (!q) return;
    apiGet('action=tags&page_key=' + encodeURIComponent(pageKey) + '&q=' + encodeURIComponent(q))
      .then(function(j){
        if (!j || !j.ok) return;
        var dl = document.getElementById('evtTagsDL');
        if (!dl) return;
        dl.innerHTML = (j.items || []).map(function(n){
          return '<option value="'+esc(n)+'"></option>';
        }).join('');
      }).catch(function(){});
  }

  function openFormNew(){
    if (!canManage) return;
    resetForm();
    document.getElementById('evtFormTitle').textContent = 'Nuevo evento';
    var el = document.getElementById('evtModalForm');
    if (el && window.bootstrap) new bootstrap.Modal(el).show();
  }

  function openFormEdit(id){
    if (!canManage) return;
    resetForm();
    document.getElementById('evtFormTitle').textContent = 'Editar evento';

    apiGet('action=get&page_key=' + encodeURIComponent(pageKey) + '&id=' + encodeURIComponent(id))
      .then(function(j){
        if (!j || !j.ok) {
          showAlert('evtPanelAlert', 'danger', (j && j.error) ? j.error : 'No se pudo abrir el evento.');
          return;
        }
        var it = j.item;

        document.getElementById('evtId').value = it.id;
        document.getElementById('evtTitulo').value = it.titulo || '';
        document.getElementById('evtParrafo').value = it.parrafo || '';
        document.getElementById('evtCoordinador').value = it.coordinador || '';
        document.getElementById('evtPonente').value = it.ponente || '';
        document.getElementById('evtInicioFecha').value = it.inicio_fecha || '';
        document.getElementById('evtInicioHora').value = it.inicio_hora ? it.inicio_hora.substring(0,5) : '';
        document.getElementById('evtFinFecha').value = it.fin_fecha || '';
        document.getElementById('evtFinHora').value = it.fin_hora ? it.fin_hora.substring(0,5) : '';
        document.getElementById('evtEstadoForm').value = it.estado || 'activo';

        tagsArr = parseTags(it.tags_csv);
        renderTagChips();

        if (it.foto_evento) {
          setFotoPrev(it.foto_evento, 'Foto actual');
        }

        var el = document.getElementById('evtModalForm');
        if (el && window.bootstrap) new bootstrap.Modal(el).show();
      })
      .catch(function(){
        showAlert('evtPanelAlert', 'danger', 'Error de red al abrir el evento.');
      });
  }

  function saveEvento(){
    if (!canManage) return;

    hideAlert('evtFormAlert');

    // tags_csv hidden
    document.getElementById('evtTagsCsv').value = tagsArr.join(', ');

    var fd = new FormData(document.getElementById('evtForm'));
    fd.append('action', 'save');

    apiPost(fd).then(function(j){
      if (!j || !j.ok) {
        showAlert('evtFormAlert', 'danger', (j && j.error) ? j.error : 'No se pudo guardar.');
        return;
      }
      showAlert('evtFormAlert', 'success', 'Guardado correctamente.');

      loadTop4();
      loadPanel();

      setTimeout(function(){
        var el = document.getElementById('evtModalForm');
        if (el && window.bootstrap) bootstrap.Modal.getInstance(el).hide();
      }, 650);
    }).catch(function(){
      showAlert('evtFormAlert', 'danger', 'Error de red o servidor.');
    });
  }

  function deleteEvento(id){
    if (!canManage) return;
    if (!confirm('¿Eliminar este evento? Se borrará la información y la foto (si existe).')) return;

    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('csrf', csrf);
    fd.append('page_key', pageKey);
    fd.append('id', String(id));

    apiPost(fd).then(function(j){
      if (!j || !j.ok) {
        showAlert('evtPanelAlert', 'danger', (j && j.error) ? j.error : 'No se pudo eliminar.');
        return;
      }
      showAlert('evtPanelAlert', 'success', 'Evento eliminado.');
      loadTop4();
      loadPanel();
    }).catch(function(){
      showAlert('evtPanelAlert', 'danger', 'Error de red o servidor.');
    });
  }

  // ===== Visor imagen (zoom + drag) =====
  var ivModalEl = document.getElementById('evtImageViewerModal');
  var ivStage = document.getElementById('evtIvStage');
  var ivImg = document.getElementById('evtIvImg');
  var ivZoomIn = document.getElementById('evtIvZoomIn');
  var ivZoomOut = document.getElementById('evtIvZoomOut');
  var ivResetBtn = document.getElementById('evtIvReset');
  var ivScale = 1;
  var ivX = 0;
  var ivY = 0;
  var ivDragging = false;
  var ivStartX = 0;
  var ivStartY = 0;

  function ivApply(){
    if (!ivImg) return;
    ivImg.style.transform = 'translate(' + ivX + 'px,' + ivY + 'px) scale(' + ivScale + ')';
  }

  function ivReset(){
    ivScale = 1;
    ivX = 0;
    ivY = 0;
    ivApply();
  }

  function ivZoom(step, cx, cy){
    if (!ivStage || !ivImg) return;
    var old = ivScale;
    ivScale = Math.max(0.3, Math.min(6, ivScale + step));
    var ratio = ivScale / old;
    ivX = cx - (cx - ivX) * ratio;
    ivY = cy - (cy - ivY) * ratio;
    ivApply();
  }

  function openImageViewer(src){
    if (!src || !ivImg) return;
    if (!ivModalEl || !window.bootstrap) {
      window.open(src, '_blank');
      return;
    }
    ivImg.src = src;
    ivReset();
    var modalInst = bootstrap.Modal.getInstance(ivModalEl);
    if (!modalInst) modalInst = new bootstrap.Modal(ivModalEl);
    modalInst.show();
  }

  function openFromQuery(){
    try {
      var usp = new URLSearchParams(window.location.search || '');
      var id = usp.get('evt_id');
      if (!id || !/^\d+$/.test(id)) return;

      apiGet('action=get&page_key=' + encodeURIComponent(pageKey) + '&id=' + encodeURIComponent(id))
        .then(function(j){
          if (j && j.ok && j.item) openView(j.item);
        });
    } catch (e) {
      // Ignore parsing errors.
    }
  }

  // ====== BIND ======
  document.addEventListener('DOMContentLoaded', function(){
    loadTop4();
    openFromQuery();

    var viewExpandBtn = document.getElementById('evtViewExpand');
    if (viewExpandBtn) {
      viewExpandBtn.addEventListener('click', function(){
        if (currentViewImg) openImageViewer(currentViewImg);
      });
    }

    if (ivStage && ivImg) {
      ivStage.addEventListener('wheel', function(ev){
        ev.preventDefault();
        var rect = ivStage.getBoundingClientRect();
        var cx = ev.clientX - rect.left;
        var cy = ev.clientY - rect.top;
        ivZoom(ev.deltaY < 0 ? 0.2 : -0.2, cx, cy);
      }, { passive: false });

      ivStage.addEventListener('mousedown', function(ev){
        if (ivScale <= 1) return;
        ivDragging = true;
        ivStartX = ev.clientX - ivX;
        ivStartY = ev.clientY - ivY;
        ivStage.classList.add('is-dragging');
      });

      window.addEventListener('mousemove', function(ev){
        if (!ivDragging) return;
        ivX = ev.clientX - ivStartX;
        ivY = ev.clientY - ivStartY;
        ivApply();
      });

      window.addEventListener('mouseup', function(){
        ivDragging = false;
        if (ivStage) ivStage.classList.remove('is-dragging');
      });

      ivStage.addEventListener('dblclick', function(ev){
        var rect = ivStage.getBoundingClientRect();
        var cx = ev.clientX - rect.left;
        var cy = ev.clientY - rect.top;
        if (ivScale > 1.05) ivReset();
        else ivZoom(0.9, cx, cy);
      });
    }

    if (ivZoomIn) ivZoomIn.addEventListener('click', function(){
      if (!ivStage) return;
      ivZoom(0.2, ivStage.clientWidth / 2, ivStage.clientHeight / 2);
    });
    if (ivZoomOut) ivZoomOut.addEventListener('click', function(){
      if (!ivStage) return;
      ivZoom(-0.2, ivStage.clientWidth / 2, ivStage.clientHeight / 2);
    });
    if (ivResetBtn) ivResetBtn.addEventListener('click', ivReset);

    if (logged) {
      var panelEl = document.getElementById('evtModalPanel');
      if (panelEl) {
        panelEl.addEventListener('shown.bs.modal', function(){
          panelPage = 1;
          loadPanel();
        });
      }

      var btnF = document.getElementById('evtBtnFiltrar');
      if (btnF) btnF.addEventListener('click', function(){ panelPage = 1; loadPanel(); });

      var prev = document.getElementById('evtPrev');
      var next = document.getElementById('evtNext');
      if (prev) prev.addEventListener('click', function(){ if (panelPage > 1){ panelPage--; loadPanel(); } });
      if (next) next.addEventListener('click', function(){ panelPage++; loadPanel(); });

      if (canManage) {
        var btnNew = document.getElementById('evtBtnNuevo');
        if (btnNew) btnNew.addEventListener('click', openFormNew);

        var btnSave = document.getElementById('evtBtnGuardar');
        if (btnSave) btnSave.addEventListener('click', saveEvento);

        var foto = document.getElementById('evtFoto');
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

        var tagIn = document.getElementById('evtTagInput');
        if (tagIn) {
          tagIn.addEventListener('input', function(){
            var q = this.value.trim();
            if (q.length >= 2) loadTagSuggestions(q);
          });

          tagIn.addEventListener('keydown', function(ev){
            if (ev.key === 'Enter' || ev.key === ',') {
              ev.preventDefault();
              var v = this.value.replace(',', '').trim();
              if (v) addTag(v);
              this.value = '';
            }
          });

          tagIn.addEventListener('blur', function(){
            var v = this.value.trim();
            if (v) addTag(v);
            this.value = '';
          });
        }

        renderTagChips();
      }
    }
  });
})();
</script>
