<?php
require_once("../componentes/db.php");

// Obtener períodos disponibles
$periodos = mysqli_query($conexion, "SELECT id, nombre FROM periodos ORDER BY fecha_inicio DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $modo = $_POST['modo']; // migrar o vaciar
    $id_periodo = isset($_POST['periodo']) ? (int) $_POST['periodo'] : 0;

    $pp = isset($_POST['tabla_pp']);
    $rp = isset($_POST['tabla_rp']);
    $he = isset($_POST['tabla_he']);
    $ev = isset($_POST['tabla_ev']); // evaluaciones
    $ra = isset($_POST['tabla_ra']); // rubrica_aspectos
    $oc = isset($_POST['tabla_oc']); // observaciones_cotejo

    if ($modo === 'vaciar') {
        if ($pp) mysqli_query($conexion, "TRUNCATE TABLE proyectos_periodo");
        if ($rp) mysqli_query($conexion, "TRUNCATE TABLE revisiones_proyectos");
        if ($he) mysqli_query($conexion, "TRUNCATE TABLE historial_estados");
        if ($ev) mysqli_query($conexion, "TRUNCATE TABLE evaluaciones");
        if ($ra) mysqli_query($conexion, "TRUNCATE TABLE rubrica_aspectos");
        if ($oc) mysqli_query($conexion, "TRUNCATE TABLE observaciones_cotejo");

        echo '<div class="alert alert-warning mt-2">🧨 Vaciado completado. Todas las tablas seleccionadas fueron limpiadas y sus IDs reiniciados.</div>';
    }

    if ($modo === 'migrar' && $id_periodo > 0) {
      if ($pp) {
        mysqli_query($conexion, "
            INSERT INTO proyectos_periodo (id_py, id_periodo)
            SELECT DISTINCT u.id_py, $id_periodo
            FROM usuarios u
            LEFT JOIN proyectos_periodo pp ON pp.id_py = u.id_py
            WHERE LENGTH(u.usuario) = 4
              AND u.id_py IS NOT NULL AND u.id_py != 0 AND u.id_py != ''
              AND u.id_rol = 2
              AND u.id_depa IS NOT NULL AND u.id_depa != 0
              AND pp.id_py IS NULL
        ");
    }
    
    if ($rp) {
        mysqli_query($conexion, "
            INSERT INTO revisiones_proyectos (id_py, id_periodo, oficina_actual, estado, fecha_solicitud)
            SELECT DISTINCT u.id_py, $id_periodo, 'pcf', 'editable', NOW()
            FROM usuarios u
            JOIN proyectos_periodo pp ON pp.id_py = u.id_py AND pp.id_periodo = $id_periodo
            LEFT JOIN revisiones_proyectos rp ON rp.id_py = u.id_py AND rp.id_periodo = pp.id_periodo
            WHERE LENGTH(u.usuario) = 4
              AND u.id_py IS NOT NULL AND u.id_py != 0 AND u.id_py != ''
              AND u.id_rol = 2
              AND u.id_depa IS NOT NULL AND u.id_depa != 0
              AND rp.id IS NULL
        ");
    }
    
    if ($he) {
        mysqli_query($conexion, "
            INSERT INTO historial_estados (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
            SELECT DISTINCT u.id_py, $id_periodo, NOW(), 'Migración Inicial', 'Proyecto migrado al nuevo sistema', NULL
            FROM usuarios u
            JOIN proyectos_periodo pp ON pp.id_py = u.id_py AND pp.id_periodo = $id_periodo
            LEFT JOIN historial_estados he ON he.id_py = u.id_py AND he.accion = 'Migración Inicial'
            WHERE LENGTH(u.usuario) = 4
              AND u.id_py IS NOT NULL AND u.id_py != 0 AND u.id_py != ''
              AND u.id_rol = 2
              AND u.id_depa IS NOT NULL AND u.id_depa != 0
              AND he.id IS NULL
        ");
    }    
        echo '<div class="alert alert-success mt-2">✅ Migración completada correctamente.</div>';
    }
}
?>

<form method="POST">
  <!-- Modo -->
  <div class="form-group">
    <label><strong>Modo de operación:</strong></label><br>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="modo" value="migrar" id="modoMigrar" checked>
      <label class="form-check-label" for="modoMigrar">Migrar proyectos
        <i class="fas fa-info-circle text-primary" title="Realiza la migración sin eliminar datos existentes. Solo añade lo que falte."></i>
      </label>
    </div>
    <div class="form-check form-check-inline">
      <input class="form-check-input" type="radio" name="modo" value="vaciar" id="modoVaciar">
      <label class="form-check-label text-danger" for="modoVaciar">Vaciar tablas
        <i class="fas fa-info-circle text-danger" title="Elimina todos los datos de las tablas seleccionadas. Esta acción es irreversible."></i>
      </label>
    </div>
  </div>

  <!-- Selección de período -->
  <div class="form-group" id="periodoGroup">
    <label><strong>Seleccionar período académico:</strong></label>
    <select name="periodo" class="form-control" id="selectPeriodo">
      <option value="">-- Seleccionar período --</option>
      <?php while ($p = mysqli_fetch_assoc($periodos)): ?>
        <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['nombre']) ?></option>
      <?php endwhile; ?>
    </select>
  </div>

  <!-- Tablas a afectar -->
  <div class="form-group">
    <label><strong>Seleccionar tablas a afectar:</strong></label><br>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="tabla_pp" id="tabla_pp" checked>
      <label class="form-check-label" for="tabla_pp">proyectos_periodo</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="tabla_rp" id="tabla_rp" checked>
      <label class="form-check-label" for="tabla_rp">revisiones_proyectos</label>
    </div>
    <div class="form-check">
      <input class="form-check-input" type="checkbox" name="tabla_he" id="tabla_he">
      <label class="form-check-label" for="tabla_he">historial_estados</label>
    </div>

    <!-- Tablas evaluativas (solo visibles en modo vaciar) -->
    <div id="grupoEvaluaciones" class="mt-3 pt-3 border-top">
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="tabla_ev" id="tabla_ev">
        <label class="form-check-label" for="tabla_ev">evaluaciones</label>
        <i class="fas fa-info-circle text-warning" title="Se perderán todas las notas y observaciones."></i>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="tabla_ra" id="tabla_ra">
        <label class="form-check-label" for="tabla_ra">rubrica_aspectos</label>
      </div>
      <div class="form-check">
        <input class="form-check-input" type="checkbox" name="tabla_oc" id="tabla_oc">
        <label class="form-check-label" for="tabla_oc">observaciones_cotejo</label>
      </div>
    </div>
  </div>

  <button type="submit" class="btn btn-danger btn-sm">
    <i class="fas fa-exchange-alt"></i> Ejecutar orden
  </button>
</form>

<script>
  const modoMigrar = document.getElementById("modoMigrar");
  const modoVaciar = document.getElementById("modoVaciar");
  const periodoGroup = document.getElementById("periodoGroup");
  const grupoEvaluaciones = document.getElementById("grupoEvaluaciones");

  function toggleUI() {
    const isMigrar = modoMigrar.checked;
    periodoGroup.style.display = isMigrar ? 'block' : 'none';
    periodoGroup.querySelector("select").disabled = !isMigrar;
    grupoEvaluaciones.style.display = isMigrar ? 'none' : 'block';
  }

  modoMigrar.addEventListener("change", toggleUI);
  modoVaciar.addEventListener("change", toggleUI);
  window.addEventListener("DOMContentLoaded", toggleUI);
</script>
