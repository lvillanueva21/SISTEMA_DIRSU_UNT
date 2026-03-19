<?php
// Requiere que index.php haya cargado $sm_info
$form = $sm_info['form_activo'] ?? null;
$semId = $sm_info['semestre_objetivo_id'] ?? null;
$periodoNombre = $sm_info['periodo_activo']['nombre'] ?? '-';
?>

<div style="display: flex; border: 1px solid #ccc; border-radius: 8px; overflow: hidden; max-width: 700px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    <!-- Lado izquierdo -->
    <div style="flex: 1; padding: 20px; background-color: #f8f9fa; display: flex; flex-direction:column; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; text-align: center;">
        Te encuentras en el período de presentación de informes semestrales <?= htmlspecialchars($periodoNombre) ?>.
        <br>
        <?php 
        echo "Apertura: " . htmlspecialchars($sm_info['apertura'] ?? '-') . "<br>";
        echo "Cierre: "   . htmlspecialchars($sm_info['cierre']   ?? '-') . "<br>";
        ?>
    </div>

    <!-- Lado derecho -->
    <div style="flex: 1; padding: 20px; background-color: #ffffff; display: flex; align-items: center; justify-content: center; font-size: 14px; text-align: center;">
        <div>
        <?php if (!$form): ?>
            <div style="color:#b00;font-weight:600">No hay formulario activo para el cronograma actual.</div>
        <?php elseif (!$semId): ?>
            <div style="color:#b00;font-weight:600">
                Tu proyecto no necesita un <b><?= htmlspecialchars($form['nombre']) ?></b> 
                pues dentro de él no existe el semestre <b><?= htmlspecialchars($periodoNombre) ?></b> necesario para completar este formulario.
            </div>
        <?php else: ?>
            <div style="margin-bottom:10px">
                Presiona el botón para crear tu nuevo <b><?= htmlspecialchars($form['nombre']) ?></b> correspondiente al semestre <?= htmlspecialchars($periodoNombre) ?>.
            </div>
<div style="font-size:12px;color:#555; background:#f8f9fa; border:1px dashed #ccc; padding:6px; margin-bottom:8px">
  Debug POST previsto:
  id_formulario = <?= (int)$form['id'] ?>,
  id_semestre = <?= (int)$semId ?>,
  id_cronograma = <?= (int)$sm_info['cronograma_id'] ?>
</div>

            <form method="post" action="crear_respuesta.php" style="display:inline">
                <input type="hidden" name="id_formulario" value="<?= (int)$form['id'] ?>">
                <input type="hidden" name="id_semestre"   value="<?= (int)$semId ?>">
                <input type="hidden" name="id_cronograma" value="<?= (int)$sm_info['cronograma_id'] ?>">
                <button type="submit" class="btn btn-primary">Crear <?= htmlspecialchars($form['nombre']) ?></button>
            </form>
        <?php endif; ?>
        </div>
    </div>
</div>
