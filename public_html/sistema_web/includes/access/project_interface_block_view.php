<?php
if (!function_exists('rsu_block_escape')) {
    function rsu_block_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

$rsu_eval = null;
if (isset($rsu_access_eval) && is_array($rsu_access_eval)) {
    $rsu_eval = $rsu_access_eval;
}

if (!is_array($rsu_eval)) {
    $rsu_eval = array(
        'allow' => false,
        'reason_message' => 'No es posible acceder a esta página en este momento.',
        'periodo_resuelto' => null,
        'proyecto' => null,
        'semestres_proyecto' => array(),
        'interfaces_activas_periodo' => array()
    );
}

$periodo = isset($rsu_eval['periodo_resuelto']) && is_array($rsu_eval['periodo_resuelto']) ? $rsu_eval['periodo_resuelto'] : null;
$proyecto = isset($rsu_eval['proyecto']) && is_array($rsu_eval['proyecto']) ? $rsu_eval['proyecto'] : null;
$semestres = isset($rsu_eval['semestres_proyecto']) && is_array($rsu_eval['semestres_proyecto']) ? $rsu_eval['semestres_proyecto'] : array();
$interfaces = isset($rsu_eval['interfaces_activas_periodo']) && is_array($rsu_eval['interfaces_activas_periodo']) ? $rsu_eval['interfaces_activas_periodo'] : array();

if (!empty($interfaces)) {
    usort($interfaces, function ($a, $b) {
        $order = array('F1-GENERALIDADES' => 1, 'F1-PLAN' => 2, 'F1-ANEXOS' => 3, 'F3-SEMESTRAL' => 4);
        $aCode = isset($a['codigo']) ? (string)$a['codigo'] : '';
        $bCode = isset($b['codigo']) ? (string)$b['codigo'] : '';
        $aOrder = isset($order[$aCode]) ? $order[$aCode] : 999;
        $bOrder = isset($order[$bCode]) ? $order[$bCode] : 999;
        if ($aOrder === $bOrder) {
            return strcmp($aCode, $bCode);
        }
        return ($aOrder < $bOrder) ? -1 : 1;
    });
}

$image_path = '../imagenes/fuera_tiempo_proyecto.png';
?>
<div class="card card-solid">
  <div class="card-body">
    <div class="row">
      <div class="col-12 col-md-5 d-flex flex-column justify-content-start align-items-center mb-3 mb-md-0">
        <img src="<?php echo rsu_block_escape($image_path); ?>" class="img-fluid" alt="Fuera de tiempo">
      </div>
      <div class="col-12 col-md-7">
        <div class="mb-3">
          <h3 class="mb-2"><strong>FUERA DE TIEMPO</strong></h3>
          <p class="mb-2">
            <?php echo rsu_block_escape(isset($rsu_eval['reason_message']) ? $rsu_eval['reason_message'] : 'No es posible acceder a esta página en este momento.'); ?>
          </p>
          <?php if (is_array($periodo) && isset($periodo['nombre'])): ?>
            <p class="mb-0">
              Período activo de referencia DIRSU: <strong><?php echo rsu_block_escape($periodo['nombre']); ?></strong>
            </p>
          <?php endif; ?>
        </div>

        <?php if (is_array($proyecto)): ?>
          <div class="table-responsive mb-3">
            <table class="table table-bordered table-sm mb-0">
              <thead class="thead-light">
                <tr>
                  <th colspan="3">Resumen de tu proyecto</th>
                </tr>
              </thead>
              <tbody>
                <tr>
                  <th style="width: 22%;">Título</th>
                  <td colspan="2"><?php echo rsu_block_escape(isset($proyecto['titulo']) ? $proyecto['titulo'] : ''); ?></td>
                </tr>
                <tr>
                  <th>Inicio</th>
                  <td><?php echo rsu_block_escape(isset($proyecto['fecha_inicio']) ? $proyecto['fecha_inicio'] : '-'); ?></td>
                  <td><?php echo rsu_block_escape(isset($proyecto['fecha_fin']) ? $proyecto['fecha_fin'] : '-'); ?></td>
                </tr>
              </tbody>
            </table>
          </div>
        <?php endif; ?>

        <div class="table-responsive mb-3">
          <table class="table table-bordered table-sm mb-0">
            <thead class="card-header" style="background-color:#28a745;color:#fff;">
              <tr>
                <th>N&deg;</th>
                <th>Interfaz activa</th>
                <th>Descripción</th>
                <th>Inicio</th>
                <th>Fin</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($interfaces)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No hay interfaces activas para mostrar en el período de referencia.</td>
                </tr>
              <?php else: ?>
                <?php $i = 1; foreach ($interfaces as $it): ?>
                  <tr>
                    <td><?php echo $i++; ?></td>
                    <td><?php echo rsu_block_escape(isset($it['nombre']) ? $it['nombre'] : (isset($it['codigo']) ? $it['codigo'] : 'Interfaz')); ?></td>
                    <td><?php echo rsu_block_escape(isset($it['descripcion']) ? $it['descripcion'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($it['inicio']) ? $it['inicio'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($it['fin']) ? $it['fin'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($it['ventana_estado']) ? $it['ventana_estado'] : '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <div class="row mt-2">
      <div class="col-12">
        <div class="table-responsive">
          <table class="table table-bordered table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th colspan="6">Semestres del proyecto y entregables</th>
              </tr>
              <tr>
                <th>N&deg;</th>
                <th>Semestre</th>
                <th>Tipo</th>
                <th>Título</th>
                <th>Inicio</th>
                <th>Fin</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($semestres)): ?>
                <tr>
                  <td colspan="6" class="text-center text-muted">No hay semestres calculados para el proyecto actual.</td>
                </tr>
              <?php else: ?>
                <?php $j = 1; foreach ($semestres as $sem): ?>
                  <tr>
                    <td><?php echo $j++; ?></td>
                    <td><?php echo rsu_block_escape(isset($sem['semestre']) ? $sem['semestre'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($sem['tipo']) ? $sem['tipo'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($sem['titulo']) ? $sem['titulo'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($sem['fecha_inicio']) ? $sem['fecha_inicio'] : '-'); ?></td>
                    <td><?php echo rsu_block_escape(isset($sem['fecha_fin']) ? $sem['fecha_fin'] : '-'); ?></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>
