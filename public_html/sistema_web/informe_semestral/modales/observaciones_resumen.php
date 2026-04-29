<?php
// /sistema_web/informe_semestral/modales/observaciones_resumen.php
declare(strict_types=1);
header('Content-Type: text/html; charset=utf-8');

require_once __DIR__ . '/../funciones.php'; // $conexion

$id_py = isset($_GET['id_py']) ? (int)$_GET['id_py'] : 0;
if ($id_py <= 0) { echo '<div class="alert alert-danger mb-0">Parámetros inválidos.</div>'; exit; }

// Consumimos el API interno para no duplicar lógica
$_GET['id_py'] = $id_py;
ob_start();
include __DIR__ . '/../api/observaciones_estado.php';
$json = trim(ob_get_clean());
$dat = json_decode($json, true);
if (!$dat || empty($dat['ok'])) { echo '<div class="alert alert-danger">No se pudo cargar observaciones.</div>'; exit; }

$C = $dat['tipos']['cotejo'] ?? [];
$R = $dat['tipos']['rubrica'] ?? [];

$names = [
  'estructura'=>'Estructura',
  'contenido'=>'Contenido',
  'redaccion'=>'Redacción',
  'calidad_info'=>'Calidad de información',
  'propuesta_mejora'=>'Propuesta de Mejora',
];
$notaLabel = [0=>'En espera',1=>'Insuficiente',2=>'Mejorable',3=>'Satisfactorio',4=>'Excelente'];

function print_header($titulo){
  echo '<div class="alert alert-warning d-flex align-items-center" role="alert" style="margin-bottom:.6rem">';
  echo '  <i class="fas fa-exclamation-triangle me-2"></i>';
  echo '  <strong>'.htmlspecialchars($titulo).'</strong>';
  echo '</div>';
}
?>
<div class="container-fluid p-2">
<?php if (($C['exists'] ?? false) === false && ($R['exists'] ?? false) === false): ?>
  <div class="alert alert-warning mb-0"><strong>No has recibido observaciones por el momento.</strong></div>

<?php else: ?>

  <?php if (!empty($C['exists'])): ?>
    <?php print_header('Observación de Lista de Cotejo'); ?>
    <div class="card border-warning mb-3">
      <div class="card-body">
        <div class="mb-1"><strong>Oficina:</strong> <?= htmlspecialchars($C['oficina_nom'] ?? '') ?></div>
        <div class="mb-1"><strong>Fecha/Hora:</strong> <?= htmlspecialchars($C['obs_at'] ?? '—') ?></div>
        <div class="mb-1"><strong>Fecha máxima de subsanación:</strong> <?= htmlspecialchars($C['limite'] ?? '—') ?></div>
        <hr>
        <div class="fw-semibold mb-1">Observación</div>
        <div class="border rounded p-2 bg-light"><?= nl2br(htmlspecialchars((string)($C['obs_text'] ?? ''))) ?></div>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($R['exists'])): ?>
    <?php
      // Filtrar aquí los aspectos (si el API trae todos)
      $aspectos = (array)($R['aspectos'] ?? []);
      $aspectos = array_values(array_filter($aspectos, function($ax){
        $nota = (int)($ax['nota'] ?? 0);
        $obs  = trim((string)($ax['obs'] ?? ''));
        return $nota > 0 && $nota <= 2 && $obs !== '';
      }));
    ?>
    <?php print_header('Observación de Rúbrica'); ?>
    <div class="card border-warning mb-3">
      <div class="card-body">
        <div class="mb-1"><strong>Oficina:</strong> <?= htmlspecialchars($R['oficina_nom'] ?? '') ?></div>
        <div class="mb-1"><strong>Fecha/Hora:</strong> <?= htmlspecialchars($R['obs_at'] ?? '—') ?></div>
        <div class="mb-1"><strong>Fecha máxima de subsanación:</strong> <?= htmlspecialchars($R['limite'] ?? '—') ?></div>

        <div class="d-flex justify-content-between align-items-center mt-2">
          <div class="fw-semibold">Calificación total</div>
          <div class="badge bg-secondary"><?= (int)($R['total'] ?? 0) ?> / 20</div>
        </div>

        <div class="table-responsive mt-2">
          <table class="table table-sm table-bordered">
            <thead>
              <tr class="table-warning">
                <th>Aspecto</th><th style="width:140px" class="text-center">Nota</th><th>Observación</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($aspectos)): ?>
                <tr><td colspan="3" class="text-center text-muted">Sin observaciones específicas.</td></tr>
              <?php else: foreach ($aspectos as $ax):
                $nom  = $names[$ax['aspecto']] ?? $ax['aspecto'];
                $nota = (int)$ax['nota'];
                $tx   = $notaLabel[$nota] ?? (string)$nota;
                $obs  = (string)$ax['obs'];
              ?>
                <tr>
                  <td><?= htmlspecialchars($nom) ?></td>
                  <td class="text-center"><span class="fw-bold">(<?= $nota ?>)</span> <?= htmlspecialchars($tx) ?></td>
                  <td><?= nl2br(htmlspecialchars($obs)) ?></td>
                </tr>
              <?php endforeach; endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>

<?php endif; ?>
</div>

