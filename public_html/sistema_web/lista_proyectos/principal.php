<?php
include_once __DIR__ . '/../includes/api_dirsu/projects_real_service.php';
include_once __DIR__ . '/../includes/api_dirsu/projects_progress_service.php';

if (!function_exists('prj_h')) {
    function prj_h($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$pagina = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
$por_pagina = 20;

$resultado = rsu_projects_real_list($conexion, $pagina, $por_pagina);
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
$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
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
    $params = $_GET;
    $params['pagina'] = (int)$p;
    return '?' . http_build_query($params);
}
?>

<div class="mb-2 p-2 border rounded">
  <strong>Rol:</strong> <?= prj_h($rol_texto) ?> &nbsp;&nbsp;
  <strong>Usuario:</strong> <?= prj_h($usuario) ?>
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
      <th style="width: 30%;">Título de proyecto</th>
      <th style="width: 16%;">Coordinador</th>
      <th style="width: 12%;">Fechas</th>
      <th style="width: 38%;">Progreso</th>
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
          $id_py = isset($it['id_py']) ? (int)$it['id_py'] : 0;
          $progress_items = isset($progress_map[$id_py]) && is_array($progress_map[$id_py]) ? $progress_map[$id_py] : array();
        ?>
        <tr class="prj-row-toggle" data-target="<?= prj_h($detail_id) ?>">
          <td><?= prj_h($idx) ?></td>
          <td>
            <?= prj_h($it['titulo_proyecto']) ?><br>
            <?php if (trim((string)$it['codigo_proyecto']) !== '' && trim((string)$it['codigo_proyecto']) !== 'Codigo pendiente'): ?>
              <span class="badge prj-badge-code">CÓDIGO: <?= prj_h($it['codigo_proyecto']) ?></span>
            <?php else: ?>
              <span class="badge prj-badge-code-pending">Código pendiente</span>
            <?php endif; ?>
            <br>
            <small class="prj-created-text"><em>Creado en: <?= prj_h($it['periodo_creacion']) ?></em></small>
          </td>
          <td><?= prj_h($it['coordinador']) ?></td>
          <td>
            <div class="prj-date-block">
              <strong>Inicio:</strong><br>
              <?= ($fecha_inicio === '') ? '<span class="text-danger font-weight-bold">Sin fecha</span>' : prj_h($fecha_inicio) ?>
            </div>
            <div class="prj-date-block">
              <strong>Fin:</strong><br>
              <?= ($fecha_fin === '') ? '<span class="text-danger font-weight-bold">Sin fecha</span>' : prj_h($fecha_fin) ?>
            </div>
          </td>
          <td>
            <div class="prj-progress-wrap">
              <div class="prj-progress-line">
                <span class="prj-deliver-period">Presentación:</span>
                <button type="button" class="prj-deliver-btn prj-deliver-btn-pres" title="Presentación de proyecto">
                  Pres. de Proyecto
                </button>
                <button type="button" class="prj-eval-btn prj-eval-btn-pres" disabled title="Evaluación de presentación (próximamente)">
                  Evaluación
                </button>
                <span class="badge badge-secondary">Sin ruta</span>
              </div>

              <?php if (empty($progress_items)): ?>
                <div class="prj-deliver-empty">Sin semestres calculados</div>
              <?php else: ?>
                <?php foreach ($progress_items as $ent): ?>
                  <div class="prj-progress-line">
                    <span class="prj-deliver-period"><?= prj_h($ent['periodo']) ?>:</span>
                    <?php if (!empty($ent['has_response'])): ?>
                      <button
                        type="button"
                        class="prj-deliver-btn <?= ($ent['tipo'] === 'final') ? 'prj-deliver-btn-final' : 'prj-deliver-btn-semestral' ?> prj-btn-informe"
                        data-response-id="<?= prj_h($ent['response_id']) ?>"
                        title="<?= prj_h($ent['informe_label']) ?>"
                      ><?= prj_h($ent['informe_label']) ?></button>
                      <button
                        type="button"
                        class="prj-eval-btn prj-btn-evaluacion"
                        data-response-id="<?= prj_h($ent['response_id']) ?>"
                        title="Estado de evaluación"
                      >Evaluación</button>
                      <?php if (isset($ent['eval']['badge_text'])): ?>
                        <span class="<?= prj_h($ent['eval']['badge_class']) ?>"><?= prj_h($ent['eval']['badge_text']) ?></span>
                      <?php endif; ?>
                    <?php else: ?>
                      <span class="prj-deliver-empty-inline">vacío</span>
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
              <strong>Departamento Academico:</strong> <?= prj_h($it['departamento']) ?>
            </p>
            <p style="margin:0;">
              <strong>Codigo docente:</strong> <?= prj_h($it['cod_docente']) ?> |
              <strong>id_py:</strong> <?= prj_h($it['id_py']) ?>
            </p>
          </td>
        </tr>
      <?php endforeach; ?>
    <?php endif; ?>
  </tbody>
</table>

<?php if ($total_pages > 1): ?>
  <nav aria-label="Paginacion" class="mt-2">
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
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Detalle de Informe</h5>
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

<div class="modal fade" id="modalEvaluacionDetalle" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
    <div class="modal-content">
      <div class="modal-header bg-light">
        <h5 class="modal-title">Estado de Evaluación</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div id="prjEvalAlert" class="alert d-none"></div>
        <div class="mb-2 border rounded p-2 bg-light" id="prjEvalResumen"></div>
        <div id="prjEvalTimeline" class="prj-eval-timeline"></div>
        <hr>
        <h6 class="mb-2">Acciones de evaluación</h6>
        <div id="prjEvalActions" class="prj-eval-actions"></div>
      </div>
    </div>
  </div>
</div>

