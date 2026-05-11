<?php
include_once __DIR__ . '/helpers.php';
include_once __DIR__ . '/consultas.php';

$items = array();
$respuestas_por_proyecto = array();
$supports_period_start = true;
$total_items = 0;
$total_pages = 1;
$por_pagina = 20;
$pagina = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$forms_migracion_2024ii = array();
$total_migrables = 0;
$total_migrados = 0;
$total_pendientes = 0;

$id_py_detalle = isset($_GET['id_py']) ? max(0, (int)$_GET['id_py']) : 0;
$id_respuesta_detalle = isset($_GET['id_respuesta']) ? max(0, (int)$_GET['id_respuesta']) : 0;
$id_periodo_detalle = isset($_GET['id_periodo']) ? max(0, (int)$_GET['id_periodo']) : 0;
$tipo_detalle = isset($_GET['tipo_resp']) ? trim((string)$_GET['tipo_resp']) : '';

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    error_log('op_especiales: conexion mysqli no disponible.');
} else {
    $result = opesp_obtener_proyectos($conexion, $pagina, $por_pagina);
    $items = isset($result['rows']) && is_array($result['rows']) ? $result['rows'] : array();
    $respuestas_por_proyecto = isset($result['respuestas_por_proyecto']) && is_array($result['respuestas_por_proyecto']) ? $result['respuestas_por_proyecto'] : array();
    $supports_period_start = isset($result['supports_period_start']) ? (bool)$result['supports_period_start'] : true;
    $total_items = isset($result['total_items']) ? (int)$result['total_items'] : 0;
    $total_pages = isset($result['total_pages']) ? max(1, (int)$result['total_pages']) : 1;
    $pagina = isset($result['pagina']) ? max(1, (int)$result['pagina']) : $pagina;
    $por_pagina = isset($result['por_pagina']) ? max(1, (int)$result['por_pagina']) : $por_pagina;
    $forms_migracion_2024ii = isset($result['forms_migracion_2024ii']) && is_array($result['forms_migracion_2024ii']) ? $result['forms_migracion_2024ii'] : array();
    $total_migrables = isset($result['total_migrables']) ? (int)$result['total_migrables'] : 0;
    $total_migrados = isset($result['total_migrados']) ? (int)$result['total_migrados'] : 0;
    $total_pendientes = isset($result['total_pendientes']) ? (int)$result['total_pendientes'] : 0;
}

$desde = ($total_items > 0) ? (($pagina - 1) * $por_pagina + 1) : 0;
$hasta = ($total_items > 0) ? (($pagina - 1) * $por_pagina + count($items)) : 0;

function opesp_link_estado($p, $id_py = 0, $id_respuesta = 0, $id_periodo = 0, $tipo_resp = '')
{
    $params = $_GET;
    $params['pagina'] = (int)$p;

    if ((int)$id_py > 0) {
        $params['id_py'] = (int)$id_py;
    } else {
        unset($params['id_py']);
    }

    if ((int)$id_respuesta > 0) {
        $params['id_respuesta'] = (int)$id_respuesta;
    } else {
        unset($params['id_respuesta']);
    }

    if ((int)$id_periodo > 0) {
        $params['id_periodo'] = (int)$id_periodo;
    } else {
        unset($params['id_periodo']);
    }

    if ($tipo_resp !== '') {
        $params['tipo_resp'] = (string)$tipo_resp;
    } else {
        unset($params['tipo_resp']);
    }

    return '?' . http_build_query($params);
}
?>
<link rel="stylesheet" href="../op_especiales/assets/op_especiales.css">

<div class="opesp-page">
  <div class="mb-2">
    <h1 class="h4 mb-1">Operaciones especiales sobre proyectos</h1>
    <p class="text-muted mb-0">Vista inicial de analisis para revisar proyectos por coordinador y detectar posibles casos especiales.</p>
  </div>

  <div class="opesp-migration-summary mb-2">
    <div class="opesp-summary-item">
      <span class="opesp-summary-label">Migrables</span>
      <span class="opesp-summary-value" id="opesp-summary-migrables"><?= opesp_h(number_format($total_migrables)) ?></span>
    </div>
    <div class="opesp-summary-item opesp-summary-success">
      <span class="opesp-summary-label">Migrados</span>
      <span class="opesp-summary-value" id="opesp-summary-migrados"><?= opesp_h(number_format($total_migrados)) ?></span>
    </div>
    <div class="opesp-summary-item opesp-summary-warn">
      <span class="opesp-summary-label">Pendientes</span>
      <span class="opesp-summary-value" id="opesp-summary-pendientes"><?= opesp_h(number_format($total_pendientes)) ?></span>
    </div>
    <div class="opesp-summary-item opesp-summary-forms">
      <span class="opesp-summary-label">Formularios 2024-II detectados</span>
      <span class="opesp-summary-value"><?= opesp_h(count($forms_migracion_2024ii)) ?></span>
    </div>
  </div>

  <?php if (!$supports_period_start): ?>
    <div class="alert alert-warning py-2 mb-2" role="alert">
      Se aplico orden alterno porque no existe <strong>periodos.fecha_inicio</strong> en este entorno.
    </div>
  <?php endif; ?>

  <div class="opesp-split-layout">
    <div class="card card-primary opesp-card">
      <div class="card-header py-2">
        <h3 class="card-title mb-0">Listado de proyectos</h3>
      </div>
      <div class="card-body p-2 opesp-scroll-body">
        <div id="opesp-results-meta" class="alert alert-light border d-flex justify-content-between align-items-center py-2 px-3 mb-2" role="status" aria-live="polite">
          <div>
            <i class="fas fa-database"></i>
            Mostrando <strong><?= opesp_h(($total_items > 0) ? ($desde . ' - ' . $hasta) : 0) ?></strong>
            de <strong><?= opesp_h(number_format($total_items)) ?></strong> resultados.
          </div>
          <div class="text-muted small">
            Pagina <?= opesp_h($pagina) ?> de <?= opesp_h($total_pages) ?>
          </div>
        </div>

        <div class="opesp-table-wrap" id="opesp-table-wrap">
          <table class="table table-bordered table-hover table-sm mb-0" width="100%">
            <thead class="thead-dark">
              <tr>
                <th style="width: 5%;">#</th>
                <th style="width: 34%;">Proyecto</th>
                <th style="width: 18%;">Fechas</th>
                <th style="width: 18%;">Coordinador</th>
                <th style="width: 25%;">Acciones</th>
              </tr>
            </thead>
            <tbody>
            <?php if (empty($items)): ?>
              <tr>
                <td colspan="5" class="text-center text-muted">No hay proyectos para mostrar.</td>
              </tr>
            <?php else: ?>
              <?php foreach ($items as $i => $it): ?>
                <?php
                  $is_duplicate = opesp_is_truthy($it['flag_posible_duplicidad']);
                  $detail_class = 'opesp-fila-extra-' . (int)$i;
                  $id_py = (int)$it['id_py'];
                  $acciones = isset($respuestas_por_proyecto[$id_py]) && is_array($respuestas_por_proyecto[$id_py]) ? $respuestas_por_proyecto[$id_py] : array();
                ?>
                <tr class="opesp-row opesp-fila-toggle" data-id="<?= opesp_h((int)$i) ?>">
                  <td><?= opesp_h((($pagina - 1) * $por_pagina) + $i + 1) ?></td>
                  <td>
                    <?= opesp_h($it['titulo_proyecto']) ?>
                    <span class="badge badge-secondary bg-secondary"><?= opesp_h($it['periodo_creacion']) ?></span>
                    <br>
                    <?php if (trim((string)$it['codigo_proyecto']) !== '' && $it['codigo_proyecto'] !== 'Codigo pendiente'): ?>
                      <span class="badge opesp-badge-code">CODIGO: <?= opesp_h($it['codigo_proyecto']) ?></span>
                    <?php else: ?>
                      <span class="badge opesp-badge-code-pending">Codigo pendiente</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div><strong>Inicio:</strong>
                      <?php if (trim((string)($it['fecha_inicio'] ?? '')) === ''): ?>
                        <span class="text-danger font-weight-bold">Sin fecha</span>
                      <?php else: ?>
                        <span><?= opesp_h($it['fecha_inicio']) ?></span>
                      <?php endif; ?>
                    </div>
                    <div><strong>Fin:</strong>
                      <?php if (trim((string)($it['fecha_fin'] ?? '')) === ''): ?>
                        <span class="text-danger font-weight-bold">Sin fecha</span>
                      <?php else: ?>
                        <span><?= opesp_h($it['fecha_fin']) ?></span>
                      <?php endif; ?>
                    </div>
                  </td>
                  <td>
                    <?= opesp_h($it['coordinador']) ?>
                    <br>
                    <small class="text-muted">Codigo docente: <?= opesp_h($it['cod_docente']) ?></small>
                    <?php if ($is_duplicate): ?>
                      <br>
                      <span class="badge badge-warning opesp-dup-badge">Posible duplicidad de coordinador activo</span>
                    <?php endif; ?>
                  </td>
                  <td>
                    <div class="opesp-actions-stack">
                      <?php if ((int)($it['puede_migrar'] ?? 0) === 1): ?>
                        <a
                          href="<?= opesp_h(opesp_link_estado($pagina, $id_py, 0, 0, 'migracion_2024ii')) ?>"
                          class="btn btn-sm opesp-btn-formulario opesp-btn-migracion"
                          data-action="migracion_2024ii"
                          data-id-py="<?= opesp_h($id_py) ?>"
                          data-migrado="<?= opesp_h((int)($it['migrado_2024ii'] ?? 0)) ?>"
                          title="Migración 2024-II">
                          <i class="fas fa-random"></i>
                          Migración 2024-II
                        </a>
                      <?php elseif ((int)($it['tiene_legacy'] ?? 0) === 0): ?>
                        <span class="text-muted small">Sin informe semestral antiguo</span>
                      <?php else: ?>
                        <span class="text-muted small">Sin coordinador activo real</span>
                      <?php endif; ?>

                      <a
                        href="<?= opesp_h(opesp_link_estado($pagina, $id_py, 0, 0, 'proyecto')) ?>"
                        class="btn btn-sm opesp-btn-formulario opesp-btn-proyecto-base"
                        data-action="proyecto"
                        data-id-py="<?= opesp_h($id_py) ?>"
                        title="Proyecto">
                        <i class="fas fa-info-circle"></i>
                        Proyecto
                      </a>
                      <a
                        href="<?= opesp_h(opesp_link_estado($pagina, $id_py, 0, 0, 'semestral_legacy')) ?>"
                        class="btn btn-sm opesp-btn-formulario opesp-btn-semestral-legacy"
                        data-action="semestral_legacy"
                        data-id-py="<?= opesp_h($id_py) ?>"
                        title="Semestral">
                        <i class="fas fa-calendar-alt"></i>
                        Semestral
                      </a>

                      <?php if (empty($acciones)): ?>
                        <span class="text-muted small">Sin respuestas de formulario</span>
                      <?php else: ?>
                        <?php foreach ($acciones as $accion): ?>
                          <?php
                            $tipo = (string)($accion['tipo'] ?? 'otros');
                            $id_respuesta = (int)($accion['id_respuesta'] ?? 0);
                            $id_periodo = (int)($accion['id_periodo'] ?? 0);
                            $semestral_param = ($tipo === 'semestral') ? $id_periodo : 0;
                            $btn_class = 'opesp-btn-form-otros';
                            $icon = 'fas fa-layer-group';
                            if ($tipo === 'semestral') {
                                $btn_class = 'opesp-btn-form-semestral';
                                $icon = 'fas fa-calendar-alt';
                            } elseif ($tipo === 'presentacion') {
                                $btn_class = 'opesp-btn-form-proyecto';
                                $icon = 'fas fa-file-alt';
                            }
                          ?>
                          <a
                            href="<?= opesp_h(opesp_link_estado($pagina, $id_py, $id_respuesta, $semestral_param, $tipo)) ?>"
                            class="btn btn-sm opesp-btn-formulario <?= opesp_h($btn_class) ?>"
                            data-action="informe"
                            data-id-py="<?= opesp_h($id_py) ?>"
                            data-id-respuesta="<?= opesp_h($id_respuesta) ?>"
                            data-id-periodo="<?= opesp_h($id_periodo) ?>"
                            data-tipo="<?= opesp_h($tipo) ?>"
                            data-label="<?= opesp_h((string)($accion['label'] ?? 'Formulario')) ?>"
                            title="<?= opesp_h((string)($accion['label'] ?? 'Formulario')) ?>">
                            <i class="<?= opesp_h($icon) ?>"></i>
                            <?= opesp_h((string)($accion['label'] ?? 'Formulario')) ?>
                          </a>
                        <?php endforeach; ?>
                      <?php endif; ?>

                      <?php if ((int)($it['migrado_2024ii'] ?? 0) === 1): ?>
                        <span class="badge badge-success opesp-badge-migrado">Migración 2024-II aprobada</span>
                      <?php endif; ?>
                    </div>
                  </td>
                </tr>
                <tr class="opesp-detail-row <?= opesp_h($detail_class) ?>" style="display:none;">
                  <td colspan="5" style="text-align:center; padding:12px;">
                    <p style="margin-bottom:6px;">
                      <strong>Facultad:</strong> <?= opesp_h($it['facultad']) ?> |
                      <strong>Departamento Academico:</strong> <?= opesp_h($it['departamento']) ?>
                    </p>
                    <p style="margin:0;">
                      <strong>Codigo docente:</strong> <?= opesp_h($it['cod_docente']) ?> |
                      <strong>id_py:</strong> <?= opesp_h($it['id_py']) ?>
                    </p>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
            </tbody>
          </table>
        </div>

        <?php if ($total_pages > 1): ?>
          <nav id="opesp-pagination-wrap" aria-label="Paginacion" class="mt-2">
            <ul class="pagination justify-content-center mb-0">
              <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                <?php if ($p === $pagina): ?>
                  <li class="page-item active" aria-current="page"><span class="page-link"><?= opesp_h($p) ?></span></li>
                <?php else: ?>
                  <li class="page-item"><a class="page-link" href="<?= opesp_h(opesp_link_estado($p, $id_py_detalle, $id_respuesta_detalle, $id_periodo_detalle, $tipo_detalle)) ?>"><?= opesp_h($p) ?></a></li>
                <?php endif; ?>
              <?php endfor; ?>
            </ul>
          </nav>
        <?php endif; ?>
      </div>
    </div>

    <div class="card card-secondary opesp-card">
      <div class="card-header py-2">
        <h3 class="card-title mb-0">Detalle del proyecto / formulario</h3>
      </div>
      <div class="card-body opesp-scroll-body">
        <div class="opesp-detail-placeholder" id="opesp-detail-placeholder">
          <p class="text-muted mb-0">Seleccione <strong>Migración 2024-II</strong>, <strong>Proyecto</strong>, <strong>Semestral</strong> o un boton de respuesta en <strong>Acciones</strong> para ver el detalle en este bloque.</p>
        </div>
        <div
          id="opesp-informe-detalle"
          class="opesp-informe-detalle d-none"
          data-selected-id-py="<?= opesp_h($id_py_detalle) ?>"
          data-selected-id-respuesta="<?= opesp_h($id_respuesta_detalle) ?>"
          data-selected-id-periodo="<?= opesp_h($id_periodo_detalle) ?>"
          data-selected-tipo="<?= opesp_h($tipo_detalle) ?>">
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="opesp-form-selector-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title mb-0">Seleccionar formulario destino 2024-II</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-2">Se detectaron múltiples formularios semestrales 2024-II. Elige el destino de la migración:</p>
        <select id="opesp-form-selector" class="form-control">
          <option value="">Seleccione...</option>
        </select>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-primary btn-sm" id="opesp-form-selector-apply">Usar formulario</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="opesp-migration-run-modal" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title mb-0">Migración 2024-II en progreso</h5>
      </div>
      <div class="modal-body">
        <div class="progress mb-2">
          <div id="opesp-migration-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated" role="progressbar" style="width:0%">0%</div>
        </div>
        <div id="opesp-migration-live-status" class="text-muted small mb-2">Preparando migración...</div>
        <div id="opesp-migration-log" class="opesp-migration-log"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" id="opesp-migration-close-btn" disabled>Cerrar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="opesp-migration-confirm-modal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h5 class="modal-title mb-0">Confirmar re-migración</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="mb-0">
          Este formulario ya fue llenado anteriormente. Si continúas, se hará un <strong>reemplazo completo</strong>
          de la respuesta y su evaluación asociada para volver a migrar.
        </p>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" id="opesp-migration-confirm-btn">Sí, re-migrar</button>
      </div>
    </div>
  </div>
</div>

<script src="../op_especiales/assets/op_especiales.js"></script>
