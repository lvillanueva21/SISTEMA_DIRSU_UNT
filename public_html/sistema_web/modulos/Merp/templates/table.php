<?php
use Merp\MerpConfig;

/** @var array $datos  ← viene de MerpEngine */
$cols = MerpConfig::COLUMNAS;
?>
<table class="table table-bordered align-middle">
    <thead class="table-dark">
        <tr>
            <?php foreach ($cols as $nombre => $_): ?>
                <th><?= $nombre ?></th>
            <?php endforeach; ?>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($datos as $i => $fila): ?>
            <tr>
                <td><?= $i + 1 ?></td>
                <td><?= $fila['proyecto_fmt'] ?></td>
                <td><?= $fila['coordinador_fmt'] ?></td>
                <td><?= $fila['estado_fmt'] ?></td>
                <td><?= $fila['pendiente_fmt'] ?></td>
                <td><?= $fila['responsable_fmt'] ?></td>
                <td><?= $fila['observacion_fmt'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
