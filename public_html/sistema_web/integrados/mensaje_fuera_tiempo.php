<?php 
include('../../componentes/db.php');

// Consulta para obtener los cronogramas activos
$sql = "SELECT * FROM cronogramas WHERE estado = 1 ORDER BY inicio ASC";
$result = mysqli_query($conexion, $sql);
?>

<div class="card card-solid">
    <div class="card-body">
        <div class="row">
            <!-- Imagen a la izquierda: siempre en la parte superior -->
            <div class="col-12 col-md-6 d-flex flex-column justify-content-start align-items-center">
                <img src="../imagenes/fuera_tiempo_proyecto.png" class="product-image img-fluid" alt="Fuera de tiempo">
            </div>

            <!-- Mensaje y tabla a la derecha -->
            <div class="col-12 col-md-6">
                <div class="text-center text-md-left my-4">
                    <h3 class="mb-3">
                        ⏰ <strong>FUERA DE TIEMPO</strong> ⏰
                    </h3>
                    <p class="mb-4">
                        No puedes editar la información en esta página porque te encuentras fuera del <strong>plazo asignado por la Dirección de Responsabilidad Social Universitaria (DIRSU)</strong>⏳
                    </p>
                    <p class="mb-4">
                        📅 <strong>¡Revisa el Cronograma de Proyectos DIRSU - 2024 - II</strong> que se muestra a continuación para conocer las fechas exactas en las que se desbloqueará esta página y podrás hacer las modificaciones necesarias. 🔓✍️
                    </p>
                    <p class="mb-4">
                        🔔 <strong>Recuerda:</strong> Las ediciones estarán disponibles solo durante las fechas indicadas. ⏰
                    </p>
                </div>

                <!-- Tabla con los cronogramas activos -->
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <thead class="card-header" style="background-color: #28a745; color: white;">
                            <tr>
                                <th>N°</th>
                                <th>Objetivo</th>
                                <th>Inicio</th>
                                <th>Fin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $i = 1;
                            while ($row = mysqli_fetch_assoc($result)) { ?>
                                <tr>
                                    <td><?php echo $i++; ?></td>
                                    <td><?php echo htmlspecialchars($row['descripcion']); ?></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($row['inicio'])); ?></td>
                                    <td><?php echo date("d/m/Y H:i", strtotime($row['fin'])); ?></td>
                                </tr>
                            <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div> <!-- Fin div derecho -->
        </div>
    </div>
</div>
