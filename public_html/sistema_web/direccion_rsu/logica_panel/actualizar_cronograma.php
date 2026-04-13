<?php
include('../../componentes/db.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] === 'actualizar') {
    if (!isset($_POST['periodo']) || !is_array($_POST['periodo'])) {
        echo 'No se recibieron datos para actualizar.';
        exit();
    }

    foreach ($_POST['periodo'] as $key => $periodo) {
        $codigo = isset($_POST['codigo'][$key]) ? $_POST['codigo'][$key] : '';
        $descripcion = isset($_POST['descripcion'][$key]) ? $_POST['descripcion'][$key] : '';
        $inicio = isset($_POST['inicio'][$key]) ? $_POST['inicio'][$key] : '';
        $fin = isset($_POST['fin'][$key]) ? $_POST['fin'][$key] : '';
        $estado = isset($_POST['estado'][$key]) ? (int)$_POST['estado'][$key] : 0;

        if (is_numeric($key)) {
            $sql = 'UPDATE cronogramas SET periodo = ?, codigo = ?, descripcion = ?, inicio = ?, fin = ?, estado = ? WHERE id = ?';
            $stmt = $conexion->prepare($sql);
            $id = (int)$key;
            $stmt->bind_param('sssssii', $periodo, $codigo, $descripcion, $inicio, $fin, $estado, $id);
            $stmt->execute();
            continue;
        }

        $sql = 'INSERT INTO cronogramas (periodo, codigo, descripcion, inicio, fin, estado) VALUES (?, ?, ?, ?, ?, ?)';
        $stmt = $conexion->prepare($sql);
        $stmt->bind_param('sssssi', $periodo, $codigo, $descripcion, $inicio, $fin, $estado);
        $stmt->execute();
    }

    echo 'Cronogramas actualizados correctamente';
    exit();
}

$sql = 'SELECT * FROM cronogramas ORDER BY inicio ASC';
$result = mysqli_query($conexion, $sql);

if ($result === false) {
    echo '<div class="alert alert-danger mb-0">No se pudieron cargar los cronogramas.</div>';
    return;
}
?>
<style>
#cronogramaPanel .table-container { margin-bottom: 0; }
#cronogramaPanel table, #cronogramaPanel th, #cronogramaPanel td { font-size: 0.85rem; padding: 4px 8px; }
#cronogramaPanel .activo { background-color: #d4edda !important; }
#cronogramaPanel .inactivo { background-color: #f8d7da !important; }
#cronogramaPanel textarea.form-control { height: 40px; resize: none; }
</style>

<div id="cronogramaPanel" class="container-fluid px-0">
  <div class="d-flex justify-content-end align-items-center mb-3">
    <button id="btnInsertarCronograma" class="btn btn-success mr-2" type="button" title="Insertar registro">
      <i class="fa fa-plus"></i>
    </button>
    <button id="btnActualizarCronograma" class="btn btn-primary" type="button" title="Actualizar cronogramas">
      <i class="fa fa-save"></i>
    </button>
  </div>

  <form id="formCronogramasPanel">
    <input type="hidden" name="accion" value="actualizar">
    <div class="table-responsive table-container">
      <table class="table table-bordered table-sm">
        <thead class="bg-success text-white">
          <tr>
            <th>Periodo</th>
            <th>Codigo</th>
            <th>Descripcion</th>
            <th>Inicio</th>
            <th>Fin</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody id="tablaCronogramasPanel">
          <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <?php
            $inicioVal = date('Y-m-d\TH:i', strtotime($row['inicio']));
            $finVal = date('Y-m-d\TH:i', strtotime($row['fin']));
            $filaClase = ((int)$row['estado'] === 1) ? 'activo' : 'inactivo';
            ?>
            <tr class="<?php echo $filaClase; ?>" data-id="<?php echo (int)$row['id']; ?>">
              <td>
                <textarea class="form-control" name="periodo[<?php echo (int)$row['id']; ?>]"><?php echo htmlspecialchars($row['periodo'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </td>
              <td>
                <select class="form-control" name="codigo[<?php echo (int)$row['id']; ?>]">
                  <?php
                  $opciones = array('F1-GENERALIDADES', 'F1-PLAN', 'F1-ANEXOS', 'F3-SEMESTRAL', 'EV-PCF', 'EV-DD', 'EV-DF', 'EV-RSU');
                  foreach ($opciones as $opcion) {
                      $selected = ($row['codigo'] === $opcion) ? 'selected' : '';
                      echo '<option value="' . htmlspecialchars($opcion, ENT_QUOTES, 'UTF-8') . '" ' . $selected . '>' . htmlspecialchars($opcion, ENT_QUOTES, 'UTF-8') . '</option>';
                  }
                  ?>
                </select>
              </td>
              <td>
                <textarea class="form-control" name="descripcion[<?php echo (int)$row['id']; ?>]" style="height:80px;"><?php echo htmlspecialchars($row['descripcion'], ENT_QUOTES, 'UTF-8'); ?></textarea>
              </td>
              <td>
                <input type="datetime-local" class="form-control" name="inicio[<?php echo (int)$row['id']; ?>]" value="<?php echo htmlspecialchars($inicioVal, ENT_QUOTES, 'UTF-8'); ?>">
              </td>
              <td>
                <input type="datetime-local" class="form-control" name="fin[<?php echo (int)$row['id']; ?>]" value="<?php echo htmlspecialchars($finVal, ENT_QUOTES, 'UTF-8'); ?>">
              </td>
              <td>
                <select name="estado[<?php echo (int)$row['id']; ?>]" class="form-control">
                  <option value="1" <?php echo ((int)$row['estado'] === 1) ? 'selected' : ''; ?>>Activo</option>
                  <option value="0" <?php echo ((int)$row['estado'] === 0) ? 'selected' : ''; ?>>Inactivo</option>
                </select>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </form>
</div>

<script>
(function ($) {
  var nuevoContador = 1;

  $('#btnActualizarCronograma').off('click').on('click', function (e) {
    e.preventDefault();
    $.post('logica_panel/actualizar_cronograma.php', $('#formCronogramasPanel').serialize(), function (response) {
      alert(response);
      location.reload();
    });
  });

  $('#btnInsertarCronograma').off('click').on('click', function (e) {
    e.preventDefault();
    var newIndex = 'new_' + (nuevoContador++);
    var nuevaFila = '' +
      '<tr class="activo" data-id="' + newIndex + '">' +
        '<td><textarea class="form-control" name="periodo[' + newIndex + ']" placeholder="Ej. 2025-I"></textarea></td>' +
        '<td>' +
          '<select class="form-control" name="codigo[' + newIndex + ']">' +
            '<option value="F1-GENERALIDADES">F1-GENERALIDADES</option>' +
            '<option value="F1-PLAN">F1-PLAN</option>' +
            '<option value="F1-ANEXOS">F1-ANEXOS</option>' +
            '<option value="F3-SEMESTRAL">F3-SEMESTRAL</option>' +
            '<option value="EV-PCF">EV-PCF</option>' +
            '<option value="EV-DD">EV-DD</option>' +
            '<option value="EV-DF">EV-DF</option>' +
            '<option value="EV-RSU">EV-RSU</option>' +
          '</select>' +
        '</td>' +
        '<td><textarea class="form-control" name="descripcion[' + newIndex + ']" placeholder="Descripcion"></textarea></td>' +
        '<td><input type="datetime-local" class="form-control" name="inicio[' + newIndex + ']" value=""></td>' +
        '<td><input type="datetime-local" class="form-control" name="fin[' + newIndex + ']" value=""></td>' +
        '<td>' +
          '<select name="estado[' + newIndex + ']" class="form-control">' +
            '<option value="1" selected>Activo</option>' +
            '<option value="0">Inactivo</option>' +
          '</select>' +
        '</td>' +
      '</tr>';

    $('#tablaCronogramasPanel').append(nuevaFila);
  });
})(jQuery);
</script>
