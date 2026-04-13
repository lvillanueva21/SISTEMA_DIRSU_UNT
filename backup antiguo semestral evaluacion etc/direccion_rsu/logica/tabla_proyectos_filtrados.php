<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
include('../../componentes/db.php');

// Arrays
$facultades = ['1'=>'Ciencias Agropecuarias','2'=>'Ciencias Biológicas','3'=>'Ciencias Económicas','4'=>'Ciencias Físicas y Matemáticas','5'=>'Ciencias Sociales','6'=>'Derecho y Ciencias Políticas','7'=>'Educación y Ciencias de la Comunicación','8'=>'Enfermería','9'=>'Estomatología','10'=>'Farmacia y Bioquímica','11'=>'Ingeniería','12'=>'Ingeniería Química','13'=>'Medicina'];
$iconos = ['1'=>'fas fa-tractor','2'=>'fas fa-dna','3'=>'fas fa-chart-line','4'=>'fas fa-calculator','5'=>'fas fa-users','6'=>'fas fa-balance-scale','7'=>'fas fa-chalkboard-teacher','8'=>'fas fa-heartbeat','9'=>'fas fa-tooth','10'=>'fas fa-flask','11'=>'fas fa-cogs','12'=>'fas fa-vial','13'=>'fas fa-user-md'];

// Array completo de departamentos (puedes pegar el completo aquí)
$departamentos_academicos = [
    '1' => 'Agronomía y Zootecnia', '2' => 'Ciencias Agroindustriales', '3' => 'Ciencias Biológicas',
    '4' => 'Microbiología y Parasitología', '5' => 'Pesquería', '6' => 'Química Biológica y Fisiología Animal',
    '7' => 'Administración', '8' => 'Contabilidad y Finanzas', '9' => 'Economía',
    '10' => 'Ciencias Básicas Estomatológicas', '11' => 'Estomatología', '12' => 'Estadística', '13' => 'Física',
    '14' => 'Informática', '15' => 'Matemáticas', '16' => 'Arqueología y Antropología'
];

$facultad = isset($_GET['facultad']) ? (int) $_GET['facultad'] : 1;
$registros_por_pagina = 2;
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
if ($pagina_actual <= 0) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta principal
$query = "SELECT p.*, u.nombres, u.apellidos 
          FROM proyectos p 
          INNER JOIN usuarios u ON u.id_py = p.id 
          WHERE p.facultad = ? AND CHAR_LENGTH(u.usuario) < 5
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conexion, $query);
if (!$stmt) die("Error en prepare: " . mysqli_error($conexion));
mysqli_stmt_bind_param($stmt, "iii", $facultad, $registros_por_pagina, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

$proyectos_filtrados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proyectos_filtrados[] = $row;
}
mysqli_stmt_close($stmt);
?>

<!-- Navegación por Facultades -->
<nav><ul class="pagination justify-content-center">
<?php
foreach ($facultades as $id => $nombre) {
    $active = ($id == $facultad) ? 'active' : '';
    $icono = isset($iconos[$id]) ? $iconos[$id] : 'fas fa-university';
    echo '<li class="page-item ' . $active . '"><a class="page-link" href="cotejo.php?facultad=' . $id . '" title="' . $nombre . '"><i class="' . $icono . '"></i></a></li>';
}
?>
</ul></nav>

<?php if (count($proyectos_filtrados) > 0): ?>
<table class="table table-bordered table-sm">
<thead><tr><th>#</th><th>Proyecto</th><th>Estado</th><th>Cotejo</th><th>Derivación</th><th>Coordinador</th><th>Acción</th></tr></thead>
<tbody>
<?php
$contador = $offset + 1;
foreach ($proyectos_filtrados as $proyecto):
    $id_py = $proyecto['id'];
    $estado_label = match ((int)$proyecto['estado']) {
        0 => '<span class="badge bg-primary">En Espera</span>',
        1 => '<span class="badge bg-warning">Revisión</span>',
        2 => '<span class="badge bg-success">Aprobado</span>',
        default => '<span class="badge bg-secondary">Desconocido</span>'
    };

    // Cotejo
    $rsu_cot_btn = '<span class="badge bg-secondary">No definido</span>';
    $rsu_cot = null;
    $q1 = "SELECT rsu_cot FROM rutas_semestrales WHERE id_py = ?";
    $stmt1 = mysqli_prepare($conexion, $q1);
    mysqli_stmt_bind_param($stmt1, "i", $id_py);
    mysqli_stmt_execute($stmt1);
    mysqli_stmt_bind_result($stmt1, $rsu_cot);
    mysqli_stmt_fetch($stmt1);
    mysqli_stmt_close($stmt1);

    if ($rsu_cot !== null) {
        if ($rsu_cot == 0) {
            $rsu_cot_btn = '<span class="badge bg-primary">En Espera</span>';
        } elseif ($rsu_cot == 1) {
            $rsu_cot_btn = '<span class="badge bg-success">Aprobado</span>';
        } elseif ($rsu_cot == 2) {
            $rsu_cot_btn = '<span class="badge bg-danger">Observado</span>';
            $q2 = "SELECT rsu_limite FROM cronogramas_semestrales WHERE id_py = ?";
            $stmt2 = mysqli_prepare($conexion, $q2);
            mysqli_stmt_bind_param($stmt2, "i", $id_py);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_bind_result($stmt2, $rsu_limite);
            if (mysqli_stmt_fetch($stmt2) && !empty($rsu_limite)) {
                $rsu_cot_btn .= "<br><small><i class='fas fa-clock'></i> Límite:<br><b>" . date('d/m/Y H:i:s', strtotime($rsu_limite)) . "</b></small>";
            }
            mysqli_stmt_close($stmt2);
        }
    }

    // Derivacion
    $fecha_derivacion = 'No registrada';
    $q3 = "SELECT rsu_inicio FROM cronogramas_semestrales WHERE id_py = ?";
    $stmt3 = mysqli_prepare($conexion, $q3);
    mysqli_stmt_bind_param($stmt3, "i", $id_py);
    mysqli_stmt_execute($stmt3);
    mysqli_stmt_bind_result($stmt3, $rsu_inicio);
    if (mysqli_stmt_fetch($stmt3) && !empty($rsu_inicio)) {
        $fecha_derivacion = date('d/m/Y H:i:s', strtotime($rsu_inicio));
    }
    mysqli_stmt_close($stmt3);

    $departamento = isset($departamentos_academicos[$proyecto['departamento_academico']]) ? $departamentos_academicos[$proyecto['departamento_academico']] : 'No definido';
    echo "<tr><td>{$contador}</td><td>" . htmlspecialchars($proyecto['p2']) . "</td><td>{$estado_label}</td><td>{$rsu_cot_btn}</td><td>{$fecha_derivacion}</td><td>" . htmlspecialchars($proyecto['nombres'] . ' ' . $proyecto['apellidos']) . "</td><td><button class='btn btn-info btn-sm' data-id='{$id_py}'><i class='fas fa-info-circle'></i> Ver detalles</button> <button class='btn btn-warning btn-sm btn-calificar' data-id='{$id_py}' data-toggle='modal' data-target='#modalCalificar'><i class='fas fa-star'></i> Calificar</button></td></tr>";
    $contador++;
endforeach;
?>
</tbody>
</table>
<?php
$query_count = "SELECT COUNT(*) FROM proyectos p INNER JOIN usuarios u ON u.id_py = p.id WHERE p.facultad = ? AND CHAR_LENGTH(u.usuario) < 5";
$stmt_count = mysqli_prepare($conexion, $query_count);
mysqli_stmt_bind_param($stmt_count, "i", $facultad);
mysqli_stmt_execute($stmt_count);
mysqli_stmt_bind_result($stmt_count, $total_proyectos);
mysqli_stmt_fetch($stmt_count);
mysqli_stmt_close($stmt_count);

$total_paginas = ceil($total_proyectos / $registros_por_pagina);
echo '<nav><ul class="pagination justify-content-center">';
if ($pagina_actual > 1) {
    echo '<li class="page-item"><a class="page-link" href="cotejo.php?facultad=' . $facultad . '&pagina=' . ($pagina_actual - 1) . '">&laquo; Anterior</a></li>';
} else {
    echo '<li class="page-item disabled"><span class="page-link">&laquo; Anterior</span></li>';
}
for ($i = 1; $i <= $total_paginas; $i++) {
    $active = ($i == $pagina_actual) ? 'active' : '';
    echo '<li class="page-item ' . $active . '"><a class="page-link" href="cotejo.php?facultad=' . $facultad . '&pagina=' . $i . '">' . $i . '</a></li>';
}
if ($pagina_actual < $total_paginas) {
    echo '<li class="page-item"><a class="page-link" href="cotejo.php?facultad=' . $facultad . '&pagina=' . ($pagina_actual + 1) . '">Siguiente &raquo;</a></li>';
} else {
    echo '<li class="page-item disabled"><span class="page-link">Siguiente &raquo;</span></li>';
}
echo '</ul></nav>';
else:
    $nombreFacultad = $facultades[$facultad] ?? 'desconocida';
    echo '<div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle"></i> No hay proyectos para la facultad de <strong>' . $nombreFacultad . '</strong>.</div>';
endif;
?>
