<?php
require_once("../componentes/db.php");

// Arreglo de tablas a consultar
$tablas = [
    'periodos',
    'proyectos_periodo',
    'revisiones_proyectos',
    'evaluaciones',
    'observaciones_cotejo',
    'rubrica_aspectos',
    'historial_estados'
];

$resultados = [];

if (isset($_POST['consultar'])) {
    foreach ($tablas as $tabla) {
        $sql = "SELECT COUNT(*) AS total FROM $tabla";
        $res = mysqli_query($conexion, $sql);
        $row = mysqli_fetch_assoc($res);
        $resultados[$tabla] = $row['total'];
    }
}
?>

<form method="POST">
    <div class="form-group">
        <button type="submit" name="consultar" class="btn btn-primary btn-sm">
            Consultar cantidad de registros
        </button>
    </div>
</form>

<?php if (!empty($resultados)) : ?>
<div class="table-responsive">
    <table class="table table-bordered table-sm">
        <thead class="thead-dark">
            <tr>
                <th>Tabla</th>
                <th>Total de registros</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($resultados as $tabla => $total) : ?>
            <tr>
                <td><?= htmlspecialchars($tabla) ?></td>
                <td><strong><?= $total ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
