<?php
// cronograma_general.php

// Incluir la conexión a la base de datos
include('../componentes/db.php');

// Consulta para obtener los cronogramas activos (estado = 1) ordenados por fecha de inicio
$sql = "SELECT * FROM cronogramas WHERE estado = 1 ORDER BY inicio ASC";
$result = mysqli_query($conexion, $sql);
?>

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
