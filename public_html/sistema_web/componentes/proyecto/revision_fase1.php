<?php
// Se asume que la sesión ya está iniciada, la conexión ($conexion) y los datos del proyecto ($id_py, $estado, $p2, etc.) han sido cargados previamente.

// Procesamiento de la solicitud de revisión cuando se confirma desde el modal
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['confirmar_revision'])) {
    // Solo se procesa si el estado es 0 (en progreso)
    if ($estado == 0) {
        $id_proyecto = $id_py;  // Utilizamos el id del proyecto cargado

        // Actualizar el estado del proyecto a 1 (Enviado para revisión)
        $sql_update = "UPDATE proyectos SET estado = 1 WHERE id = ?";
        $stmt_update = $conexion->prepare($sql_update);
        $stmt_update->bind_param("i", $id_proyecto);

        if ($stmt_update->execute()) {
            // Configurar la zona horaria y obtener la fecha/hora actual
            date_default_timezone_set('America/Lima');
            $fecha_actual = date('Y-m-d H:i:s');

            // Insertar registro en historial_proyectos
            $descripcion = "Creación de solicitud de revisión en Fase 1 de proyectos";
            $sql_historial = "INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, ?, ?)";
            $stmt_historial = $conexion->prepare($sql_historial);
            $stmt_historial->bind_param("ssi", $descripcion, $fecha_actual, $id_proyecto);
            $stmt_historial->execute();
            $stmt_historial->close();

            // Verificar si ya existe un registro en rutas_semestrales
            $sql_check_ruta = "SELECT 1 FROM rutas_semestrales WHERE id_py = ?";
            $stmt_check_ruta = $conexion->prepare($sql_check_ruta);
            $stmt_check_ruta->bind_param("i", $id_proyecto);
            $stmt_check_ruta->execute();
            $stmt_check_ruta->store_result();

            if ($stmt_check_ruta->num_rows == 0) {
                // Insertar nuevo registro si no existe
                $sql_insert_rutas = "INSERT INTO rutas_semestrales (id_py, estado, pcf_cot, pcf_rub, dd_vb, df_vb, rsu_cot, rsu_rub) 
                                     VALUES (?, 0, 0, 0, 0, 0, 0, 0)";
                $stmt_insert_rutas = $conexion->prepare($sql_insert_rutas);
                $stmt_insert_rutas->bind_param("i", $id_proyecto);
                $stmt_insert_rutas->execute();
                $stmt_insert_rutas->close();
            }
            $stmt_check_ruta->close();

            // Verificar si ya existe un registro en cronogramas_semestrales
            $sql_check_cronograma = "SELECT 1 FROM cronogramas_semestrales WHERE id_py = ?";
            $stmt_check_cronograma = $conexion->prepare($sql_check_cronograma);
            $stmt_check_cronograma->bind_param("i", $id_proyecto);
            $stmt_check_cronograma->execute();
            $stmt_check_cronograma->store_result();

            if ($stmt_check_cronograma->num_rows == 0) {
                // Insertar nuevo cronograma si no existe
                $sql_insert_cronograma = "INSERT INTO cronogramas_semestrales 
                (id_py, estado, pcf_inicio, pcf_limite, pcf_fin, dd_inicio, dd_fin, df_inicio, df_fin, rsu_inicio, rsu_limite, rsu_fin) 
                VALUES (?, 0, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                $stmt_cronograma = $conexion->prepare($sql_insert_cronograma);
                $stmt_cronograma->bind_param(
                    "issssssssss",
                    $id_proyecto,
                    $fecha_actual, $fecha_actual, $fecha_actual,
                    $fecha_actual, $fecha_actual,
                    $fecha_actual, $fecha_actual,
                    $fecha_actual, $fecha_actual, $fecha_actual
                );
                $stmt_cronograma->execute();
                $stmt_cronograma->close();
            }
            $stmt_check_cronograma->close();

            $stmt_update->close();

            // Actualizar la variable de estado para reflejar el cambio en la vista
            $estado = 1;
        } else {
            echo "Error al actualizar el estado: " . $stmt_update->error;
        }
    }
}
?>

<!-- Tabla de Solicitudes de Revisión -->
<div class="card-body">
  <table class="table table-striped" style="table-layout: fixed; width: 100%; border-radius: 0.5rem; overflow: hidden;">
    <thead style="background-color: #28a745; color: white;">
       <tr>
         <th style="width: 55%;">Título de proyecto</th>
         <th style="width: 18%; text-align: center;">Fase actual</th>
         <th style="width: 18%; text-align: center;">Informe a Revisar</th>
         <th style="width: 12%; text-align: center;">Estado</th>
         <th style="width: 15%; text-align: center;">Acción</th>
       </tr>
    </thead>
    <tbody>
      <tr>
         <td style="font-size: 14px">
           <?php 
             echo isset($p2) && !empty($p2)
                 ? htmlspecialchars($p2)
                 : '<p style="color: #B22222;"><b>No tienes un título registrado para tu proyecto</b></p>'; 
           ?>
         </td>
         <td style="font-size: 14px">FASE 3: Evaluación e informe de proyecto</td>
         <td style="width: 18%; text-align: center;">
           Informe semestral o final según <br>
           <a href="https://docs.google.com/document/d/14dvDBHFufIKKp0XhDid6boNzA3KC15gc/edit?tab=t.0" target="_blank">Anexo 8_ESQUEMA DE INFORME</a>
         </td>
         <td style="font-size: 14px">
           <?php
             if ($estado == 0) {
                 echo '<span class="badge bg-primary d-inline-block" style="white-space: normal;">En progreso</span>';
             } elseif ($estado == 1) {
                 echo '<span class="badge bg-warning text-dark d-inline-block" style="white-space: normal;">Enviado para revisión</span>';
             } elseif ($estado == 2) {
                 echo '<span class="badge bg-success d-inline-block" style="white-space: normal;">Aprobado</span>';
             }
           ?>
         </td>
         <td style="font-size: 14px; text-align: center;">
           <?php if ($estado == 0): ?>
              <!-- Botón que activa el modal para solicitar revisión -->
              <button type="button" class="btn btn-primary" data-toggle="modal" data-target="#modalRevision">
                Solicitar Revisión
              </button>
           <?php else: ?>
              <!-- Si el estado es 1 o 2 se muestra el mensaje sin acción -->
              <button type="button" class="btn btn-danger" disabled style="font-size: 0.7rem; line-height: 1.2;">
                A espera de<br>observación o Aprobación
              </button>
           <?php endif; ?>
         </td>
      </tr>
    </tbody>
  </table>
</div>

<!-- Modal de Advertencia para Solicitar Revisión -->
<div class="modal fade" id="modalRevision" tabindex="-1" role="dialog" aria-labelledby="modalRevisionLabel" aria-hidden="true">
  <div class="modal-dialog" role="document" style="max-width: 95%; width: 95%;">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="modalRevisionLabel">Confirmar Solicitud de Revisión</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        ¿Estás seguro de Solicitar revisión de proyecto? Una vez solicitada, no podrás editar la información de tu proyecto a menos que uno de los evaluadores observe tu proyecto. Al observar tu proyecto se te dará un plazo para volver a editar la información. En caso de haber presionado el botón por error, enviar un correo a <a href="mailto:dirsu@unitru.edu.pe">dirsu@unitru.edu.pe</a> solicitando la anulación de tu solicitud de revisión.
      </div>
      <div class="modal-footer">
        <form method="post" action="">
           <input type="hidden" name="confirmar_revision" value="1">
           <button type="submit" class="btn btn-primary">Solicitar</button>
           <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        </form>
      </div>
    </div>
  </div>
</div>
