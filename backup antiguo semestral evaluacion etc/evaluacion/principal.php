<?php
include_once __DIR__ . '/funciones.php';

/* ===================== PARÁMETROS Y CATÁLOGOS ===================== */

$por_pagina = 20;
$pagina     = isset($_GET['pagina']) ? max(1, (int) $_GET['pagina']) : 1;

$usr = testeo(); // rol, usuario, ids

// Filtros desde GET
$facultad     = isset($_GET['facultad']) ? (int)$_GET['facultad'] : 0;
$departamento = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
$revision     = isset($_GET['revision']) ? (string)$_GET['revision'] : '';
$periodo      = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0;
$q            = isset($_GET['q']) ? trim((string)$_GET['q']) : '';

// Catálogos
$facultades   = obtenerFacultades();

// Facultad “base” para cargar departamentos en el select
$fac_for_deps = $facultad;
if ($fac_for_deps <= 0) {
    if (in_array((int)$usr['id_rol'], [3,5], true)) $fac_for_deps = (int)$usr['id_escuela']; // decano / comité
}
$departamentos_cat = obtenerDepartamentos((int)$fac_for_deps);
$periodos          = obtenerPeriodos();

// Filtros agrupados para consultas
$filtros = [
    'facultad'     => $facultad,
    'departamento' => $departamento,
    'revision'     => $revision,  // '', '1', '0', 'sin'
    'periodo'      => $periodo,
    'q'            => $q,
];

// Totales + items
$total_items   = totalProyectos($usr, $filtros);
$total_pages   = max(1, (int) ceil($total_items / $por_pagina));
$acciones_usr  = accionesPorRol($usr['id_rol'], $usr['rol']);
$items         = proyectosListado($pagina, $por_pagina, $usr, $filtros);

// Info usuario
$info = [
    'rol'     => $usr['rol'],
    'usuario' => $usr['usuario'],
];

/* ===================== PAGINACIÓN COMPACTA ===================== */

function compact_pages($current, $total)
{
    if ($total <= 7) return range(1, $total);
    $first = [1, 2, 3];
    $last  = [$total - 2, $total - 1, $total];
    $pages = $first;
    if ($first[2] + 1 < $last[0]) $pages[] = '...';
    foreach ($last as $p) if (!in_array($p, $pages, true)) $pages[] = $p;
    return $pages;
}
$pages = compact_pages($pagina, $total_pages);

// Helper para links con filtros
function link_con_filtros($p, $f)
{
    $qs = [
        'pagina'      => (int)$p,
        'facultad'    => (int)$f['facultad'],
        'departamento'=> (int)$f['departamento'],
        'revision'    => (string)$f['revision'],
        'periodo'     => (int)$f['periodo'],
        'q'           => (string)$f['q'],
    ];
    return '?' . http_build_query($qs);
}

// Rango mostrado
$desde = ($total_items > 0) ? (($pagina - 1) * $por_pagina + 1) : 0;
$hasta = ($total_items > 0) ? (($pagina - 1) * $por_pagina + count($items)) : 0;

// Visibilidad de controles por rol
$id_rol = (int)$usr['id_rol'];
$mostrarFac   = in_array($id_rol, [0,1], true);          // Admin/RSU ven "Facultad"
$mostrarDep   = in_array($id_rol, [0,1,3,5], true);      // RSU/Admin/Decano/Comité
$mostrarRev   = true;
$mostrarPer   = true;
$mostrarBusq  = true;

// ¿Departamento deshabilitado?
$dep_disabled = $mostrarDep && $fac_for_deps <= 0;
?>
<style>
/* Modal Ver Informe: altura fija + scroll interno */
#modalInforme .modal-dialog { max-width: 1140px; }
#modalInforme .modal-dialog.modal-dialog-scrollable { height: 90vh; }
#modalInforme .modal-content { height: 100%; }
#modalInforme .modal-body { padding:0; overflow:hidden !important; }
#contenidoInforme { height: 78vh; overflow:hidden; }

/* UI filtros — compatible con BS4/BS5 */
.filtros-card .form-label{ font-weight:600; margin-bottom:.25rem; }
.filtros-card .form-control{ min-width: 120px; width:100%; }
.filtros-card .row > [class*="col-"]{ margin-bottom:.5rem; } /* “gap” para BS4 */
</style>
<style>
/* Botón deshabilitado: cursor y 🚫 en hover */
.btn-eval[disabled], .btn-eval.disabled { cursor: not-allowed !important; }
.btn-eval[disabled]:hover::after,
.btn-eval[aria-disabled="true"]:hover::after{
  content:" 🚫";
  font-size: 14px;
}
</style>

<!-- Info del usuario -->
<div class="mb-2 p-2 border rounded">
    <strong>Rol:</strong> <?= htmlspecialchars($info['rol']) ?> &nbsp;&nbsp;
    <strong>Usuario:</strong> <?= htmlspecialchars($info['usuario']) ?>
</div>

<!-- ======= FILTROS ======= -->
<div class="card filtros-card mb-2">
  <div class="card-body py-2">
    <form id="frmFiltros" method="get" class="mb-0">
      <input type="hidden" name="pagina" value="1">
      <div class="row align-items-end">
        <?php if ($mostrarFac): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selFacultad">Facultad:</label>
            <select name="facultad" id="selFacultad" class="form-control">
              <option value="0" <?= $facultad===0?'selected':''; ?>>Todas</option>
<?php foreach ($facultades as $id=>$nom): if ((int)$id === 0) continue; ?>
  <option value="<?= (int)$id ?>" <?= ($facultad===(int)$id)?'selected':''; ?>>
    <?= htmlspecialchars($nom) ?>
  </option>
<?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarDep): ?>
          <div class="col-12 col-md-3 col-lg-3">
            <label class="form-label" for="selDepartamento">Departamento:</label>
            <select name="departamento" id="selDepartamento" class="form-control" <?= $dep_disabled?'disabled':''; ?>>
              <?php if ($dep_disabled): ?>
                <option value="0" selected>Sin Departamento Académico</option>
              <?php else: ?>
                <option value="0" <?= $departamento===0?'selected':''; ?>>Todos</option>
                <?php foreach ($departamentos_cat as $id=>$nom): ?>
                  <option value="<?= (int)$id ?>" <?= ($departamento===(int)$id)?'selected':''; ?>>
                    <?= htmlspecialchars($nom) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarRev): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selRevision">Revisión:</label>
            <select name="revision" id="selRevision" class="form-control">
<option value=""   <?= $revision===''?'selected':''; ?>>Todos</option>
<option value="0"  <?= $revision==='0'?'selected':''; ?>>No solicitó</option>
<option value="1"  <?= $revision==='1'?'selected':''; ?>>Si solicitó</option>
<option value="2"  <?= $revision==='2'?'selected':''; ?>>Aprobado</option>
<option value="3"  <?= $revision==='3'?'selected':''; ?>>Observado</option>
<option value="sin"<?= $revision==='sin'?'selected':''; ?>>Sin Informe Semestral</option>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarPer): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="form-label" for="selPeriodo">Período:</label>
            <select name="periodo" id="selPeriodo" class="form-control">
              <option value="0" <?= $periodo===0?'selected':''; ?>>Todos</option>
              <?php foreach ($periodos as $id=>$nom): ?>
                <option value="<?= (int)$id ?>" <?= ($periodo===(int)$id)?'selected':''; ?>>
                  <?= htmlspecialchars($nom) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($mostrarBusq): ?>
          <div class="col-12 col-md-6 col-lg-3">
            <label class="form-label" for="txtQ">Búsqueda:</label>
            <input type="text" name="q" id="txtQ" value="<?= htmlspecialchars($q) ?>" class="form-control"
                   placeholder="Coordinador, código, id, título">
          </div>
        <?php endif; ?>

        <div class="col-12 col-md-6 col-lg-2 d-flex align-items-end justify-content-end">
          <div class="d-flex w-100" style="gap:6px;">
            <button type="submit" class="btn btn-primary w-100">
              <i class="fas fa-search"></i>
            </button>
            <a class="btn btn-danger" title="Limpiar filtros"
               href="<?= htmlspecialchars(link_con_filtros(1, ['facultad'=>0,'departamento'=>0,'revision'=>'','periodo'=>0,'q'=>''])) ?>">
              <i class="fas fa-broom"></i>
            </a>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>

<!-- Resumen/contador de resultados -->
<div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-2" role="status" aria-live="polite">
  <div>
    <i class="fas fa-database"></i>
    Mostrando <strong><?= ($total_items > 0) ? $desde . '–' . $hasta : 0 ?></strong>
    de <strong><?= number_format($total_items) ?></strong> resultado<?= ($total_items === 1) ? '' : 's' ?>.
  </div>
  <div class="text-muted small">
    Página <?= (int)$pagina ?> de <?= (int)$total_pages ?>
  </div>
</div>

<!-- ======= TABLA ======= -->
<div style="padding: 10px; border: 1px solid #ddd; border-radius: 6px;">
    <table class="table table-bordered table-hover" width="100%">
        <thead>
            <tr>
                <th style="width: 4%;">#</th>
                <th style="width: 34%;">Título del proyecto</th>
                <th style="width: 18%;">Coordinador</th>
                <th style="width: 12%;">Revisión</th>
                <th style="width: 14%;">Estado / Oficina</th>
                <th style="width: 18%;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($items)) : ?>
                <tr>
                    <td colspan="6" class="text-center">Sin registros</td>
                </tr>
            <?php else : ?>
                <?php foreach ($items as $i => $it) : ?>
                    <tr class="fila-toggle" data-id="<?= $i ?>">
                        <td><?= ($pagina - 1) * $por_pagina + $i + 1 ?></td>
                        <td>
                            <?= htmlspecialchars($it['titulo']) ?><br>
                            <span class="badge badge-secondary bg-secondary"><?= htmlspecialchars($it['periodo']) ?></span>
                        </td>
                        <td><?= htmlspecialchars($it['coordinador']) ?></td>

                        <!-- Columna Revisión (vacía) -->
                        <td>&nbsp;</td>

                        <!-- Columna Estado / Oficina (vacía) -->
                        <td>&nbsp;</td>

                        <td>
                            <?= renderBotonesAccion($acciones_usr, (int) $it['id_py']); ?>
                        </td>
                    </tr>

                    <tr class="fila-extra fila-extra-<?= $i ?>" style="display: none; background-color: #f9f9f9;">
                        <td colspan="6" style="text-align: center; padding: 12px;">
                            <p style="margin-bottom: 6px;">
                                <strong>Facultad:</strong> <?= htmlspecialchars($it['facultad']) ?> |
                                <strong>Departamento:</strong> <?= htmlspecialchars($it['departamento']) ?>
                            </p>
                            <p style="margin: 0;">
                                <strong>Código Docente:</strong> <?= htmlspecialchars($it['cod_docente']) ?> |
                                <strong>id_py:</strong> <?= htmlspecialchars($it['id_py']) ?>
                            </p>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- Paginación compacta -->
<?php if ($total_pages > 1) : ?>
    <nav aria-label="Paginación" style="margin-top: 10px;">
        <ul class="pagination justify-content-center">
            <?php foreach ($pages as $p) : ?>
                <?php if ($p === '...') : ?>
                    <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
                    <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
                    <li class="page-item disabled"><span class="page-link" style="border:none;background:transparent;">•</span></li>
                <?php else : ?>
                    <?php if ((int)$p === (int)$pagina) : ?>
                        <li class="page-item active" aria-current="page"><span class="page-link"><?= (int)$p ?></span></li>
                    <?php else : ?>
                        <li class="page-item">
                            <a class="page-link" href="<?= htmlspecialchars(link_con_filtros((int)$p, $filtros)) ?>"><?= (int)$p ?></a>
                        </li>
                    <?php endif; ?>
                <?php endif; ?>
            <?php endforeach; ?>
        </ul>
    </nav>
<?php endif; ?>

<!-- ======= MODAL: VER INFORME ======= -->
<div class="modal fade" id="modalInforme" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
        <div class="modal-content border-primary">
            <div class="modal-header bg-primary text-white py-2">
                <h5 class="modal-title"><i class="fas fa-info-circle"></i> Informe del Proyecto</h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <div id="contenidoInforme" style="height:78vh; overflow:hidden;">
                    <p class="text-center text-muted my-4">Cargando informe...</p>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- ======= MODAL: EVALUACIÓN (GENÉRICO) ======= -->
<div class="modal fade" id="modalEval" tabindex="-1" role="dialog" aria-labelledby="tituloEval" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content border-warning">
      <div class="modal-header bg-warning text-dark py-2">
        <h5 class="modal-title" id="tituloEval"><i class="fas fa-info-circle"></i> Acción</h5>
        <button type="button" class="close text-dark" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div id="contenidoEval" class="modal-body">
        <p class="text-center text-muted my-4">Cargando…</p>
      </div>
    </div>
  </div>
</div>

<script>
/* Auto-submit + cascada de filtros + debounce de búsqueda */
(function(){
  const form = document.getElementById('frmFiltros');
  if (!form) return;

  const fac = document.getElementById('selFacultad');
  const dep = document.getElementById('selDepartamento');
  const rev = document.getElementById('selRevision');
  const per = document.getElementById('selPeriodo');
  const q   = document.getElementById('txtQ');

  function submit(){ form.requestSubmit ? form.requestSubmit() : form.submit(); }

  [fac, dep, rev, per].forEach(el => {
    if (!el) return;
    el.addEventListener('change', function(){
      // Si cambia la facultad, reinicia Departamento (cascada) y (des)habilita
      if (this === fac && dep) {
        dep.value = '0';
        if (fac.value === '0') { dep.setAttribute('disabled','disabled'); }
        else { dep.removeAttribute('disabled'); }
      }
      submit();
    });
  });

  // Al cargar, forzar estado de dep
  if (dep && fac) {
    if (fac.value === '0' && !dep.hasAttribute('disabled')) dep.setAttribute('disabled','disabled');
  }

  // Debounce para la búsqueda
  if (q) {
    let t = null;
    q.addEventListener('input', function(){
      clearTimeout(t);
      t = setTimeout(submit, 600);
    });
    q.addEventListener('keypress', function(e){
      if (e.key === 'Enter') { e.preventDefault(); submit(); }
    });
  }
})();
</script>

<script>
/* Mostrar/ocultar fila extra */
document.querySelectorAll('.fila-toggle').forEach((row) => {
  row.addEventListener('click', () => {
    const id = row.dataset.id;
    const extra = document.querySelector('.fila-extra-' + id);
    extra.style.display = (extra.style.display === 'none' || !extra.style.display)
      ? 'table-row'
      : 'none';
  });
});
</script>

<script>
/* Abrir modal "Ver Informe" y cargar HTML (único botón funcional) */
(function () {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-ver-informe');
    if (!btn) return;

    e.stopPropagation();
    e.preventDefault();

    const idpy = btn.getAttribute('data-id_py');
    if (!idpy) return;

    const $contenedor = window.jQuery ? jQuery('#contenidoInforme') : null;
    if ($contenedor) $contenedor.html('<p class="text-center text-muted my-4">Cargando informe...</p>');

    if (window.jQuery) {
      jQuery.get('/sistema_web/evaluacion/ver_informe.php', { id: idpy }, function (html) {
        jQuery('#contenidoInforme').html(html);
      }, 'html');
      jQuery('#modalInforme').modal('show');
      return;
    }

    fetch('/sistema_web/evaluacion/ver_informe.php?id=' + encodeURIComponent(idpy))
      .then((r) => r.text())
      .then((html) => { document.getElementById('contenidoInforme').innerHTML = html; })
      .catch(() => { document.getElementById('contenidoInforme').innerHTML = '<div class="text-danger p-3">Error cargando informe.</div>'; });

    const modal = document.getElementById('modalInforme');
    if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(modal).show();
    else modal.style.display = 'block';
  });

  // Scroll suave dentro del modal
  document.addEventListener('click', function (e) {
    const a = e.target.closest('#contenidoInforme a[href^="#"]');
    if (!a) return;
    const id = a.getAttribute('href');
    if (!id || id === '#') return;
    const target = document.querySelector(id);
    if (!target) return;
    e.preventDefault();
    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
  });
})();
</script>
<script>
/* Botones de evaluación: abren modal demo y cargan contenido remoto */
(function () {
  const labels = { cotejo: 'Calificar Cotejo', rubrica: 'Calificar Rúbrica', vb: 'Visto Bueno' };

  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-eval');
    if (!btn) return;

    // No abrir si está deshabilitado
    if (btn.hasAttribute('disabled') || btn.classList.contains('disabled') || btn.getAttribute('aria-disabled') === 'true' || btn.disabled) {
      e.preventDefault();
      e.stopPropagation();
      return;
    }

    e.preventDefault();
    e.stopPropagation();

    const accion = btn.getAttribute('data-accion') || '';
    const idpy   = btn.getAttribute('data-id_py')    || '';

    const titulo = labels[accion] || 'Acción';
    const $t = document.getElementById('tituloEval');
    const $c = document.getElementById('contenidoEval');

    if ($t) $t.innerHTML = '<i class="fas fa-info-circle"></i> ' + titulo;
    if ($c) $c.innerHTML = '<p class="text-center text-muted my-4">Cargando…</p>';

    const url = '/sistema_web/evaluacion/modales/evaluacion_msg.php?accion='
              + encodeURIComponent(accion) + '&id=' + encodeURIComponent(idpy);

    // jQuery si existe, si no fetch nativo
    if (window.jQuery) {
      jQuery.get(url, function (html) {
        jQuery('#contenidoEval').html(html);
      }, 'html');
      jQuery('#modalEval').modal('show');
      return;
    }

    fetch(url)
      .then(r => r.text())
      .then(html => { document.getElementById('contenidoEval').innerHTML = html; })
      .catch(() => { document.getElementById('contenidoEval').innerHTML = '<div class="text-danger p-3">No se pudo cargar el contenido.</div>'; });

    const modal = document.getElementById('modalEval');
    if (window.bootstrap && window.bootstrap.Modal) {
      new bootstrap.Modal(modal).show();
    } else {
      // Fallback mínimo si no hay Bootstrap: mostrar el contenedor
      modal.classList.add('show');
      modal.style.display = 'block';
      modal.removeAttribute('aria-hidden');
      modal.setAttribute('aria-modal', 'true');
    }
  }, false);
})();
</script>

