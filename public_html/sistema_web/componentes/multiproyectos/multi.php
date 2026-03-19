<?php
if (isset($_SESSION["id_rol"]) && $_SESSION["id_rol"] == 2 && !isset($_SESSION["multiproyectos_mostrado"])): 
    $_SESSION["multiproyectos_mostrado"] = true;

    include_once("componentes/db.php");

    $usuario = $_SESSION["usuario"];
    $proyectos_2024 = [];
    $proyectos_2025 = [];

    $stmt_user = $conexion->prepare("SELECT id, id_py FROM usuarios WHERE usuario = ?");
    $stmt_user->bind_param("s", $usuario);
    $stmt_user->execute();
    $stmt_user->bind_result($id_usuario, $id_py_actual);
    $stmt_user->fetch();
    $stmt_user->close();

    $stmt = $conexion->prepare("
        SELECT 
            p.id AS id_proyecto,
            p.p2 AS nombre,
            per.nombre AS periodo
        FROM usuarios_proyectos up
        JOIN proyectos p ON p.id = up.id_proyecto
        JOIN proyectos_periodo pp ON pp.id_py = p.id
        JOIN periodos per ON per.id = pp.id_periodo
        WHERE up.id_usuario = ?
    ");
    $stmt->bind_param("i", $id_usuario);
    $stmt->execute();
    $result = $stmt->get_result();

    while ($row = $result->fetch_assoc()) {
        if (str_starts_with($row['periodo'], '2025')) {
            $proyectos_2025[] = $row;
        } elseif (str_starts_with($row['periodo'], '2024')) {
            $proyectos_2024[] = $row;
        }
    }

    $stmt->close();
?>

<div class="modal fade" id="modalMultiproyectos" tabindex="-1" aria-labelledby="modalMultiproyectosLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-xl">
    <div class="modal-content">

      <!-- Header -->
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalMultiproyectosLabel">
          ¡IMPORTANTE! Antes de continuar, elige un proyecto para iniciar sesión:
        </h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <!-- Body -->
      <div class="modal-body">
        <div class="row">
          <div class="col-md-6 border-right">
            <p><em>(Puedes elegir o crear un proyecto cada vez que inicies sesión)</em></p>
            <p>✅ <strong>Crear Proyecto en período 2025-I:</strong><br>
              Este proyecto pertenecerá al período <strong>2025-I</strong>. Podrás registrar la Fase 1: Formulación y presentación.
            </p>
            <p>✅ <strong>Continuar con Proyecto del 2024-II:</strong><br>
              Solo podrás ver la información del proyecto. Próximamente se habilitará la Fase 2 para reportar avances.
            </p>
          </div>

          <div class="col-md-6">
            <?php if (empty($proyectos_2024) && empty($proyectos_2025)): ?>
              <div class="mb-4">
                <h6><strong>🟢 Crea tu primer proyecto en el período 2025-I</strong></h6>
                <div class="list-group-item d-flex justify-content-between align-items-center border">
                  <div>
                    <strong>Nuevo proyecto</strong><br>
                    <small>Este proyecto pertenecerá al período <strong>2025-I</strong>. Podrás registrar información del proyecto en la Fase 1: Formulación y presentación del proyecto<br><li>Generalidades</li><li>Plan de Proyecto</li><li>Anexos</li></small>
                  </div>
                  <button id="crearProyectoBtn" class="btn btn-success btn-sm">📌 Crear proyecto</button>
                </div>
              </div>
            <?php else: ?>
              <?php if (!empty($proyectos_2025)): ?>
                <div class="mb-4">
                  <h6><strong>🟢 Continuar trabajando con el proyecto del período 2025-I</strong></h6>
                  <?php foreach ($proyectos_2025 as $proyecto): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center border">
                    <?php
  $titulo = trim($proyecto['nombre']);
  if ($titulo === '' || is_null($titulo)) {
      $titulo_formateado = '<span class="text-danger"><em>Proyecto con título por registrar</em></span>';
  } else {
      $titulo_formateado = '<em>' . htmlspecialchars($titulo) . '</em>';
  }
?>
<div>
  <?= $titulo_formateado ?><br>
  <small>Período: <?= htmlspecialchars($proyecto['periodo']) ?></small>
</div>
                      <button 
                        class="btn btn-sm btn-primary btn-continuar-proyecto" 
                        data-id="<?= $proyecto['id_proyecto'] ?>">
                        Continuar con este proyecto
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="mb-4">
                  <h6><strong>🆕 Crear un nuevo proyecto en el período 2025-I</strong></h6>
                  <button id="crearProyectoBtn2" class="btn btn-success btn-block">
  📌 Crear proyecto en período 2025-I
</button>
                </div>
              <?php endif; ?>

              <?php if (!empty($proyectos_2024)): ?>
                <div>
                  <h6><strong>🟡 Continuar trabajando con el proyecto del período 2024-II</strong></h6>
                  <?php foreach ($proyectos_2024 as $proyecto): ?>
                    <div class="list-group-item d-flex justify-content-between align-items-center border">
                    <?php
  $titulo = trim($proyecto['nombre']);
  if ($titulo === '' || is_null($titulo)) {
      $titulo_formateado = '<span class="text-danger"><em>Proyecto con título por registrar</em></span>';
  } else {
      $titulo_formateado = '<em>' . htmlspecialchars($titulo) . '</em>';
  }
?>
<div>
  <?= $titulo_formateado ?><br>
  <small>Período: <?= htmlspecialchars($proyecto['periodo']) ?></small>
</div>
                      <button 
                        class="btn btn-sm btn-primary btn-continuar-proyecto" 
                        data-id="<?= $proyecto['id_proyecto'] ?>">
                        Continuar con este proyecto
                      </button>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const modal = new bootstrap.Modal(document.getElementById('modalMultiproyectos'));
  modal.show();

  // Botones para continuar con un proyecto existente
  document.querySelectorAll('.btn-continuar-proyecto').forEach(btn => {
    btn.addEventListener('click', function () {
      const idProyectoSeleccionado = this.getAttribute('data-id');
      fetch('componentes/multiproyectos/seleccionar_proyecto.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'id_proyecto=' + idProyectoSeleccionado
      })
      .then(response => response.text())
      .then(() => {
        modal.hide();
        location.reload();
      });
    });
  });

  // Botones para crear un nuevo proyecto
  ['crearProyectoBtn', 'crearProyectoBtn2'].forEach(id => {
    const boton = document.getElementById(id);
    if (boton) {
      boton.addEventListener('click', function () {
        fetch('componentes/proyecto/crear_proyecto.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
          body: 'crear_proyecto=true'
        })
        .then(response => response.text())
        .then(() => {
          modal.hide();
          window.location.href = 'vistas/datos_principales.php';
        })
        .catch(err => alert('Error al crear el proyecto: ' + err));
      });
    }
  });
});
</script>

<?php endif; ?>
