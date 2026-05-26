<?php
include_once __DIR__ . '/../includes/api_dirsu/projects_real_service.php';
include_once __DIR__ . '/../includes/api_dirsu/projects_progress_service.php';

if (!function_exists('prj_h')) {
    function prj_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('prj_int_get')) {
    function prj_int_get($key, $default = 0)
    {
        if (!isset($_GET[$key])) {
            return (int)$default;
        }
        $v = (int)$_GET[$key];
        return ($v > 0) ? $v : 0;
    }
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;
$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$scope = rsu_projects_real_default_scope();
$scope_facultad_id = isset($scope['id_escuela']) ? (int)$scope['id_escuela'] : 0;

$filtros = array(
    'facultad_id' => prj_int_get('facultad_id', 0),
    'departamento_id' => prj_int_get('departamento_id', 0),
    'creacion_periodo_id' => prj_int_get('creacion_periodo_id', 0),
    'search_text' => isset($_GET['q']) ? trim((string)$_GET['q']) : '',
);

$showFacultad = ($id_rol === 1 || $id_rol === 0);
$showDepartamento = ($id_rol === 1 || $id_rol === 0 || $id_rol === 3 || $id_rol === 5);
$showBusqueda = true;
$showCreacion = true;

if ($id_rol === 4 || $id_rol === 2) {
    $showFacultad = false;
    $showDepartamento = false;
}

$facultad_context_id = $showFacultad ? (int)$filtros['facultad_id'] : 0;
if (!$showFacultad && ($id_rol === 3 || $id_rol === 5) && $scope_facultad_id > 0) {
    $facultad_context_id = $scope_facultad_id;
    $filtros['facultad_id'] = $scope_facultad_id;
} elseif (!$showFacultad) {
    $filtros['facultad_id'] = 0;
}

$facultades = $showFacultad ? rsu_projects_real_filter_facultades($conexion, $scope) : array();
$periodos_creacion = rsu_projects_real_filter_periodos_creacion($conexion, $scope);
$dep_disabled = (!$showDepartamento || $facultad_context_id <= 0);
$departamentos = $dep_disabled ? array() : rsu_projects_real_filter_departamentos($conexion, $facultad_context_id, $scope);
if ($dep_disabled || !$showDepartamento) {
    $filtros['departamento_id'] = 0;
} elseif ($filtros['departamento_id'] > 0 && !isset($departamentos[$filtros['departamento_id']])) {
    $filtros['departamento_id'] = 0;
}

$resultado = rsu_projects_real_list($conexion, $pagina, $por_pagina, $filtros, $scope);
$items = isset($resultado['rows']) && is_array($resultado['rows']) ? $resultado['rows'] : array();
$total_items = isset($resultado['total_items']) ? (int)$resultado['total_items'] : 0;
$total_pages = isset($resultado['total_pages']) ? max(1, (int)$resultado['total_pages']) : 1;
$pagina = isset($resultado['pagina']) ? max(1, (int)$resultado['pagina']) : 1;

$desde = ($total_items > 0) ? (($pagina - 1) * $por_pagina + 1) : 0;
$hasta = ($total_items > 0) ? (($pagina - 1) * $por_pagina + count($items)) : 0;

$role_map = array(
    1 => 'Dirección RSU',
    2 => 'Coordinador de Proyecto',
    3 => 'Decanato de Facultad',
    4 => 'Dirección de Departamento',
    5 => 'Comité de Facultad',
);
$usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
$rol_texto = isset($role_map[$id_rol]) ? $role_map[$id_rol] : ('Rol ' . $id_rol);

$project_ids = array();
foreach ($items as $row) {
    if (isset($row['id_py'])) {
        $project_ids[] = (int)$row['id_py'];
    }
}
$progress_map = rsu_projects_progress_by_project_ids($conexion, $project_ids, $id_rol);

function prj_link_pagina($p)
{
    global $filtros;
    $params = array(
        'pagina' => (int)$p,
        'facultad_id' => isset($filtros['facultad_id']) ? (int)$filtros['facultad_id'] : 0,
        'departamento_id' => isset($filtros['departamento_id']) ? (int)$filtros['departamento_id'] : 0,
        'creacion_periodo_id' => isset($filtros['creacion_periodo_id']) ? (int)$filtros['creacion_periodo_id'] : 0,
        'q' => isset($filtros['search_text']) ? (string)$filtros['search_text'] : '',
    );
    return '?' . http_build_query($params);
}

function prj_link_limpiar_filtros()
{
    return '?pagina=1';
}
?>

<div class="mb-2 p-2 border rounded">
  <strong>Rol:</strong> <?= prj_h($rol_texto) ?> &nbsp;&nbsp;
  <strong>Usuario:</strong> <?= prj_h($usuario) ?>
</div>

<div class="card prj-filters-card mb-2">
  <div class="card-body py-2">
    <form id="prjFiltersForm" method="get" class="mb-0">
      <input type="hidden" name="pagina" value="1">
      <?php if (!$showFacultad && $facultad_context_id > 0): ?>
        <input type="hidden" name="facultad_id" value="<?= prj_h($facultad_context_id) ?>">
      <?php endif; ?>
      <div class="row align-items-end">
        <?php if ($showFacultad): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="prj-filter-label" for="prjFacultad">Facultad:</label>
            <select name="facultad_id" id="prjFacultad" class="form-control form-control-sm">
              <option value="0" <?= ($filtros['facultad_id'] === 0) ? 'selected' : '' ?>>Todas</option>
              <?php foreach ($facultades as $fac_id => $fac_name): ?>
                <option value="<?= prj_h((int)$fac_id) ?>" <?= ((int)$filtros['facultad_id'] === (int)$fac_id) ? 'selected' : '' ?>>
                  <?= prj_h($fac_name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($showDepartamento): ?>
          <div class="col-12 col-md-4 col-lg-3">
            <label class="prj-filter-label" for="prjDepartamento">Departamento:</label>
            <select name="departamento_id" id="prjDepartamento" class="form-control form-control-sm" <?= $dep_disabled ? 'disabled' : '' ?>>
              <?php if ($dep_disabled): ?>
                <option value="0" selected>Seleccione facultad</option>
              <?php else: ?>
                <option value="0" <?= ($filtros['departamento_id'] === 0) ? 'selected' : '' ?>>Todos</option>
                <?php foreach ($departamentos as $dep_id => $dep_name): ?>
                  <option value="<?= prj_h((int)$dep_id) ?>" <?= ((int)$filtros['departamento_id'] === (int)$dep_id) ? 'selected' : '' ?>>
                    <?= prj_h($dep_name) ?>
                  </option>
                <?php endforeach; ?>
              <?php endif; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($showCreacion): ?>
          <div class="col-12 col-md-3 col-lg-2">
            <label class="prj-filter-label" for="prjCreacion">Creaci&oacute;n:</label>
            <select name="creacion_periodo_id" id="prjCreacion" class="form-control form-control-sm">
              <option value="0" <?= ($filtros['creacion_periodo_id'] === 0) ? 'selected' : '' ?>>Todos</option>
              <?php foreach ($periodos_creacion as $per_id => $per_name): ?>
                <option value="<?= prj_h((int)$per_id) ?>" <?= ((int)$filtros['creacion_periodo_id'] === (int)$per_id) ? 'selected' : '' ?>>
                  <?= prj_h($per_name) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        <?php endif; ?>

        <?php if ($showBusqueda): ?>
          <div class="col-12 col-md-4 col-lg-3">
            <label class="prj-filter-label" for="prjQ">Búsqueda:</label>
            <input
              type="text"
              name="q"
              id="prjQ"
              value="<?= prj_h($filtros['search_text']) ?>"
              class="form-control form-control-sm"
              placeholder="Coordinador, código, id o título">
          </div>
        <?php endif; ?>

        <div class="col-12 col-md-2 col-lg-1">
          <button type="submit" class="btn btn-primary btn-sm w-100" title="Aplicar filtros">
            <i class="fas fa-search"></i>
          </button>
        </div>
        <div class="col-12 col-md-2 col-lg-1">
          <a class="btn btn-danger btn-sm w-100" title="Limpiar filtros" href="<?= prj_h(prj_link_limpiar_filtros()) ?>">
            <i class="fas fa-broom"></i>
          </a>
        </div>
      </div>
    </form>
  </div>
</div>

<div class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-2" role="status" aria-live="polite">
  <div>
    <i class="fas fa-database"></i>
    Mostrando <strong><?= prj_h(($total_items > 0) ? ($desde . '-' . $hasta) : 0) ?></strong>
    de <strong><?= prj_h(number_format($total_items)) ?></strong> resultados.
  </div>
  <div class="text-muted small">
    Página <?= prj_h($pagina) ?> de <?= prj_h($total_pages) ?>
  </div>
</div>

<table class="table table-bordered table-hover mb-0 prj-table" width="100%">
  <thead class="thead-light">
    <tr>
      <th style="width: 4%;">#</th>
      <th style="width: 18%;">Proyecto</th>
      <th style="width: 18%;">Coordinador</th>
      <th style="width: 34%;">Progreso</th>
      <th style="width: 26%;">Estado actual</th>
    </tr>
  </thead>
  <tbody>
    <?php if (empty($items)): ?>
      <tr>
        <td colspan="5" class="text-center text-muted">Sin registros.</td>
      </tr>
    <?php else: ?>
      <?php foreach ($items as $i => $it): ?>
        <?php
          $idx = (($pagina - 1) * $por_pagina) + $i + 1;
          $detail_id = 'prj-detail-' . (int)$idx;
          $fecha_inicio = trim((string)$it['fecha_inicio']);
          $fecha_fin = trim((string)$it['fecha_fin']);
          $codigo_proyecto = trim((string)$it['codigo_proyecto']);
          $codigo_lower = function_exists('mb_strtolower') ? mb_strtolower($codigo_proyecto, 'UTF-8') : strtolower($codigo_proyecto);
          $codigo_pendiente = ($codigo_lower === '' || $codigo_lower === 'codigo pendiente' || $codigo_lower === 'código pendiente');
          $id_py = isset($it['id_py']) ? (int)$it['id_py'] : 0;
          $progress_items = isset($progress_map[$id_py]) && is_array($progress_map[$id_py]) ? $progress_map[$id_py] : array();
          $periodo_presentacion = 'Primer semestre';
          if (!empty($progress_items) && isset($progress_items[0]['periodo'])) {
              $periodo_presentacion = trim((string)$progress_items[0]['periodo']);
              if ($periodo_presentacion === '') {
                  $periodo_presentacion = 'Primer semestre';
              }
          }
        ?>
        <tr class="prj-row-toggle" data-target="<?= prj_h($detail_id) ?>">
          <td><?= prj_h($idx) ?></td>
          <td>
            <?= prj_h($it['titulo_proyecto']) ?><br>
            <?php if (!$codigo_pendiente): ?>
              <span class="badge prj-badge-code">C&Oacute;DIGO: <?= prj_h($codigo_proyecto) ?></span>
            <?php else: ?>
              <span class="prj-code-pending">Código pendiente</span>
            <?php endif; ?>
            <br>
            <small class="prj-created-text"><em>Creado en: <?= prj_h($it['periodo_creacion']) ?></em></small>
            <div class="prj-proyecto-fechas">
              <small>
                <strong>Inicio:</strong>
                <?= ($fecha_inicio === '') ? '<span class="text-danger font-weight-bold">Sin fecha</span>' : prj_h($fecha_inicio) ?>
              </small><br>
              <small>
                <strong>Fin:</strong>
                <?= ($fecha_fin === '') ? '<span class="text-danger font-weight-bold">Sin fecha</span>' : prj_h($fecha_fin) ?>
              </small>
            </div>
          </td>
          <td><?= prj_h($it['coordinador']) ?></td>
          <td>
            <div class="prj-progress-wrap">
              <div class="prj-progress-line" data-line-key="presentacion">
                <span class="prj-deliver-period"><?= prj_h($periodo_presentacion) ?>:</span>
                <button
                  type="button"
                  class="prj-deliver-btn prj-deliver-btn-pres prj-btn-presentacion"
                  data-project-id="<?= prj_h($id_py) ?>"
                  title="Presentaci&oacute;n de proyecto"
                ><i class="fas fa-folder-open"></i> Pres. de Proyecto</button>
                <button
                  type="button"
                  class="prj-eval-btn prj-eval-btn-pres"
                  title="Evaluaci&oacute;n de presentaci&oacute;n (pr&oacute;ximamente)"
                ><i class="fas fa-clipboard-check"></i> Evaluaci&oacute;n</button>
              </div>

              <?php if (empty($progress_items)): ?>
                <div class="prj-progress-line" data-line-key="sin_semestres">
                  <span class="prj-deliver-empty">Sin semestres calculados</span>
                </div>
              <?php else: ?>
                <?php foreach ($progress_items as $ent): ?>
                  <div class="prj-progress-line" data-line-key="<?= prj_h($ent['periodo']) ?>">
                    <span class="prj-deliver-period"><?= prj_h($ent['periodo']) ?>:</span>
                    <?php if (!empty($ent['has_response'])): ?>
                      <button
                        type="button"
                        class="prj-deliver-btn <?= ($ent['tipo'] === 'final') ? 'prj-deliver-btn-final' : 'prj-deliver-btn-semestral' ?> prj-btn-informe"
                        data-project-id="<?= prj_h($id_py) ?>"
                        data-response-id="<?= prj_h($ent['response_id']) ?>"
                        data-informe-tipo="<?= prj_h(($ent['tipo'] === 'final') ? 'final' : 'semestral') ?>"
                        title="<?= prj_h($ent['informe_label']) ?>"
                      ><i class="fas fa-file-alt"></i> <?= prj_h($ent['informe_label']) ?></button>
                      <button
                        type="button"
                        class="prj-eval-btn prj-btn-evaluacion"
                        data-project-id="<?= prj_h($id_py) ?>"
                        data-response-id="<?= prj_h($ent['response_id']) ?>"
                        title="Estado de evaluaci&oacute;n"
                      ><i class="fas fa-clipboard-check"></i> Evaluaci&oacute;n</button>
                    <?php else: ?>
                      <span class="prj-deliver-empty-inline"><?= ($ent['tipo'] === 'final') ? 'Informe final pendiente' : 'Informe semestral pendiente' ?></span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </td>
          <td>
            <div class="prj-status-wrap">
              <div class="prj-status-line" data-line-key="presentacion">
                <span class="badge badge-secondary">Sin ruta</span>
              </div>
              <?php if (empty($progress_items)): ?>
                <div class="prj-status-line" data-line-key="sin_semestres">
                  <span class="text-muted small">Sin semestres calculados</span>
                </div>
              <?php else: ?>
                <?php foreach ($progress_items as $ent): ?>
                  <div class="prj-status-line" data-line-key="<?= prj_h($ent['periodo']) ?>">
                    <?php if (!empty($ent['has_response'])): ?>
                      <?php if (isset($ent['eval']['badge_text'])): ?>
                        <span class="<?= prj_h($ent['eval']['badge_class']) ?>"><?= prj_h($ent['eval']['badge_text']) ?></span>
                      <?php endif; ?>
                      <?php
                        $oficina_badge_class = '';
                        $oficina_badge_text = '';
                        if (isset($ent['eval']['office_badge']) && is_array($ent['eval']['office_badge'])) {
                            $oficina_badge_class = trim((string)($ent['eval']['office_badge']['class'] ?? ''));
                            $oficina_badge_text = trim((string)($ent['eval']['office_badge']['text'] ?? ''));
                        }
                        if ($oficina_badge_text === '' && isset($ent['eval']['summary']['oficina_nom'])) {
                            $oficina_badge_text = trim((string)$ent['eval']['summary']['oficina_nom']);
                        }
                        if ($oficina_badge_class === '' && $oficina_badge_text !== '') {
                            $oficina_badge_class = 'badge badge-secondary';
                        }
                      ?>
                      <?php if ($oficina_badge_text !== ''): ?>
                        <span class="<?= prj_h($oficina_badge_class) ?>" title="Oficina actual"><?= prj_h($oficina_badge_text) ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="text-muted small">--</span>
                    <?php endif; ?>
                  </div>
                <?php endforeach; ?>
              <?php endif; ?>
            </div>
          </td>
        </tr>
        <tr id="<?= prj_h($detail_id) ?>" class="prj-detail-row" style="display:none;">
          <td colspan="5" style="text-align:center; padding:12px;">
            <p style="margin-bottom:6px;">
              <strong>Facultad:</strong> <?= prj_h($it['facultad']) ?> |
              <strong>Departamento Acad&eacute;mico:</strong> <?= prj_h($it['departamento']) ?>
            </p>
            <p style="margin:0;">
              <strong>C&oacute;digo docente:</strong> <?= prj_h($it['cod_docente']) ?> |
              <strong>id_py:</strong> <?= prj_h($it['id_py']) ?>
            </p>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($total_pages > 1): ?>
  <nav aria-label="Paginación" class="mt-2">
    <ul class="pagination justify-content-center mb-0">
      <?php for ($p = 1; $p <= $total_pages; $p++): ?>
        <?php if ($p === $pagina): ?>
          <li class="page-item active" aria-current="page"><span class="page-link"><?= prj_h($p) ?></span></li>
        <?php else: ?>
          <li class="page-item"><a class="page-link" href="<?= prj_h(prj_link_pagina($p)) ?>"><?= prj_h($p) ?></a></li>
        <?php endif; ?>
      <?php endfor; ?>
    </ul>
  </nav>
<?php endif; ?>

<div class="modal fade" id="modalInformeDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header prj-informe-header prj-informe-header-semestral" id="prjInformeHeader">
        <h5 class="modal-title d-flex align-items-center" id="prjInformeModalTitle">
          <i class="fas fa-file-alt mr-2" id="prjInformeModalIcon" aria-hidden="true"></i>
          <span id="prjInformeModalTitleText">Informe semestral</span>
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="prjInformeDetalleBody">
        <div class="text-muted">Cargando...</div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalPresentacionDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content border-info">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title mb-0 d-flex align-items-center">
          <i class="fas fa-info-circle mr-2"></i> Formulaci&oacute;n y presentaci&oacute;n de Proyecto
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <div id="prjPresentacionDetalleBody">
          <p class="text-center text-muted my-4">Cargando datos del proyecto...</p>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalEvaluacionDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title" id="prjModalEvalTitle">Ruta de evaluaci&oacute;n</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="prjEvalAlert" class="alert d-none"></div>
        <div class="mb-2 border rounded p-2 bg-light" id="prjEvalResumen"></div>
        <h6 class="mb-2">Ruta de evaluaci&oacute;n</h6>
        <div id="prjEvalTimeline" class="prj-eval-timeline"></div>
        <hr>
        <div id="prjCoordSection">
          <h6 class="mb-2">Flujo del coordinador</h6>
          <div id="prjCoordActions" class="prj-eval-actions"></div>
        </div>
        <div id="prjEvalSection">
          <hr>
          <div class="prj-tabs-head">
            <ul class="nav nav-tabs prj-eval-tabs" role="tablist">
              <li class="nav-item d-none" id="prjTabIndicacionesItem">
                <a class="nav-link prj-tab-yellow" id="prjTabIndicacionesLink" data-toggle="tab" href="#prjTabIndicaciones" role="tab" aria-controls="prjTabIndicaciones" aria-selected="false">
                  Indicaciones
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link prj-tab-yellow active" id="prjTabObsLink" data-toggle="tab" href="#prjTabObs" role="tab" aria-controls="prjTabObs" aria-selected="true">
                  Ver observaci&oacute;n
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link prj-tab-yellow" id="prjTabCotejoLink" data-toggle="tab" href="#prjTabCotejo" role="tab" aria-controls="prjTabCotejo" aria-selected="false">
                  Calificar Cotejo
                </a>
              </li>
              <li class="nav-item">
                <a class="nav-link prj-tab-yellow" id="prjTabRubricaLink" data-toggle="tab" href="#prjTabRubrica" role="tab" aria-controls="prjTabRubrica" aria-selected="false">
                  Calificar R&uacute;brica
                </a>
              </li>
            </ul>
            <strong id="prjEvalPendingText" class="prj-pending-text">Evaluaciones pendientes: 00</strong>
          </div>
          <div class="tab-content border border-top-0 rounded-bottom p-2 bg-white">
            <div class="tab-pane fade" id="prjTabIndicaciones" role="tabpanel" aria-labelledby="prjTabIndicacionesLink">
              <div id="prjEvalIndicacionesBody" class="prj-indicaciones-pane">
                <p class="mb-0 text-muted">Sin indicaciones para mostrar.</p>
              </div>
            </div>
            <div class="tab-pane fade show active" id="prjTabObs" role="tabpanel" aria-labelledby="prjTabObsLink">
              <div id="prjEvalObsInlineBody" class="prj-eval-obs-pane">Sin detalle cargado.</div>
            </div>
            <div class="tab-pane fade" id="prjTabCotejo" role="tabpanel" aria-labelledby="prjTabCotejoLink">
              <div id="prjEvalActionsCotejo" class="prj-eval-actions"></div>
            </div>
            <div class="tab-pane fade" id="prjTabRubrica" role="tabpanel" aria-labelledby="prjTabRubricaLink">
              <div id="prjEvalActionsRubrica" class="prj-eval-actions"></div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalObservacionesDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h5 class="modal-title"><i class="fas fa-exclamation-triangle mr-1"></i> Observaciones</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="prjObsDetalleBody">
        <div class="text-muted">Cargando...</div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRsuVideoRecurso" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title mb-0"><i class="fas fa-play-circle mr-1"></i> Video de apoyo</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="embed-responsive embed-responsive-16by9">
          <video id="prjRsuVideoPlayer" class="embed-responsive-item" controls preload="metadata"></video>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalRsuRecursoInfo" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning">
        <h6 class="modal-title mb-0"><i class="fas fa-info-circle mr-1"></i> Aviso</h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body py-3" id="prjRsuRecursoInfoBody">
        No se encontró el recurso consulte a RSU
      </div>
    </div>
  </div>
</div>
