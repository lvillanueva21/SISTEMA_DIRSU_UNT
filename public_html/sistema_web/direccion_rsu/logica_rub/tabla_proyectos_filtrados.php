<?php
// Array de facultades, iconos y departamentos académicos
$facultades = ['1'=>'Ciencias Agropecuarias','2'=>'Ciencias Biológicas','3'=>'Ciencias Económicas','4'=>'Ciencias Físicas y Matemáticas','5'=>'Ciencias Sociales','6'=>'Derecho y Ciencias Políticas','7'=>'Educación y Ciencias de la Comunicación','8'=>'Enfermería','9'=>'Estomatología','10'=>'Farmacia y Bioquímica','11'=>'Ingeniería','12'=>'Ingeniería Química','13'=>'Medicina'];
$iconos = ['1'=>'fas fa-tractor','2'=>'fas fa-dna','3'=>'fas fa-chart-line','4'=>'fas fa-calculator','5'=>'fas fa-users','6'=>'fas fa-balance-scale','7'=>'fas fa-chalkboard-teacher','8'=>'fas fa-heartbeat','9'=>'fas fa-tooth','10'=>'fas fa-flask','11'=>'fas fa-cogs','12'=>'fas fa-vial','13'=>'fas fa-user-md'];
$departamentos_academicos = ['1'=>'Agronomía y Zootecnia', '2'=>'Ciencias Agroindustriales', '3'=>'Ciencias Biológicas', '4'=>'Microbiología y Parasitología', '5'=>'Pesquería', '6'=>'Química Biológica y Fisiología Animal', '7'=>'Administración', '8'=>'Contabilidad y Finanzas', '9'=>'Economía', '10'=>'Ciencias Básicas Estomatológicas', '11'=>'Estomatología', '12'=>'Estadística', '13'=>'Física', '14'=>'Informática', '15'=>'Matemáticas', '16'=>'Arqueología y Antropología', '17'=>'Ciencias Sociales', '18'=>'Ciencias Jurídicas Públicas y Políticas', '19'=>'Ciencias Jurídicas Privadas y Sociales', '20'=>'Ciencia Política y Gobernabilidad', '21'=>'Ciencias de la Educación', '22'=>'Ciencias Psicológicas', '23'=>'Comunicación Social', '24'=>'Filosofía y Arte', '25'=>'Historia y Geografía', '26'=>'Idiomas y Lingüística', '27'=>'Lengua Nacional y Literatura', '28'=>'Enfermería de la Mujer, Niño y Adolescente', '29'=>'Salud del Adulto', '30'=>'Salud Familiar y Comunitaria', '31'=>'Farmacotecnia', '32'=>'Farmacología', '33'=>'Bioquímica', '34'=>'Ingeniería Civil, Arquitectura y Urbanismo', '35'=>'Ingeniería Industrial', '36'=>'Ingeniería de Materiales', '37'=>'Mecánica y Energía', '38'=>'Ingeniería Metalúrgica', '39'=>'Ingeniería de Minas', '40'=>'Ingeniería de Sistemas', '41'=>'Ingeniería Química', '42'=>'Ingeniería Ambiental', '43'=>'Química', '44'=>'Ciencias Básicas Médicas', '45'=>'Cirugía', '46'=>'Fisiología Humana', '47'=>'Ginecología-Obstetricia', '48'=>'Medicina', '49'=>'Medicina Preventiva y Salud Pública', '50'=>'Morfología Humana', '51'=>'Pediatría', '52'=>'Ingeniería Mecatrónica'];

// Captura el parámetro de facultad de la URL o usa la facultad 1 por defecto
$facultad = isset($_GET['facultad']) ? (int) $_GET['facultad'] : 1;

// Filtros básicos: estado y valor mínimo de id
$filtro1 = 1;    // Estado = 1 (activo)
$filtro2 = 250;  // id > 250

// Paginación interna
$registros_por_pagina = 2;
$pagina_actual = isset($_GET['pagina']) ? (int) $_GET['pagina'] : 1;
$pagina_actual = max(1, $pagina_actual); // Asegura que la página no sea menor que 1
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta SQL
$query = "SELECT p.*, CONCAT(u.nombres, ' ', u.apellidos) as coordinador FROM proyectos p LEFT JOIN usuarios u ON u.id_py = p.id WHERE p.estado = ? AND p.id > ? AND p.facultad = ? LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "iiiii", $filtro1, $filtro2, $facultad, $registros_por_pagina, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Almacenar los proyectos filtrados
$proyectos_filtrados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proyectos_filtrados[] = $row;
}
mysqli_stmt_close($stmt);

// Navegación por Facultades (con paginación de Bootstrap)
echo '<nav><ul class="pagination justify-content-center">';
foreach ($facultades as $id => $nombre) {
    $active = ($id == $facultad) ? 'active' : '';
    $icono = $iconos[$id] ?? 'fas fa-university'; // Icono por defecto si no existe
    echo '<li class="page-item ' . $active . '"><a class="page-link" href="rubrica.php?facultad=' . $id . '" style="font-size:0.8rem;" title="' . $nombre . '"><i class="' . $icono . '"></i></a></li>';
}
echo '</ul></nav>';

// Mostrar tabla de proyectos o mensaje en caso de no haber resultados
if (count($proyectos_filtrados) > 0) {
    echo '<table class="table table-bordered table-sm"><thead><tr><th>#</th><th>Proyecto</th><th>Coordinador</th><th>Departamento</th><th>Acción</th></tr></thead><tbody>';
    $contador = $offset + 1;
    foreach ($proyectos_filtrados as $proyecto) {
        $departamento = $departamentos_academicos[$proyecto['departamento_academico']] ?? 'No definido';
        echo '<tr><td>' . $contador . '</td><td>' . htmlspecialchars($proyecto['p2']) . '</td><td>' . htmlspecialchars($proyecto['coordinador']) . '</td><td>' . htmlspecialchars($departamento) . '</td><td><button class="btn btn-info btn-sm" data-id="' . $proyecto['id'] . '"><i class="fas fa-info-circle"></i> Ver detalles</button><button class="btn btn-warning btn-sm btn-calificar" data-id="' . $proyecto['id'] . '" data-toggle="modal" data-target="#modalCalificar"><i class="fas fa-star"></i> Calificar</button></td></tr>';
        $contador++;
    }
    echo '</tbody></table>';

    // Paginación: contar el total de proyectos
    $query_count = "SELECT COUNT(*) FROM proyectos WHERE estado = ? AND id > ? AND facultad = ?";
    $stmt_count = mysqli_prepare($conexion, $query_count);
    mysqli_stmt_bind_param($stmt_count, "iii", $filtro1, $filtro2, $facultad);
    mysqli_stmt_execute($stmt_count);
    mysqli_stmt_bind_result($stmt_count, $total_proyectos);
    mysqli_stmt_fetch($stmt_count);
    mysqli_stmt_close($stmt_count);

    $total_paginas = ceil($total_proyectos / $registros_por_pagina);
    if ($total_paginas > 1) {
        echo '<nav><ul class="pagination justify-content-center">';
        if ($pagina_actual > 1) echo '<li class="page-item"><a class="page-link" href="rubrica.php?facultad=' . $facultad . '&pagina=' . ($pagina_actual - 1) . '">&laquo; Anterior</a></li>';
        for ($i = 1; $i <= $total_paginas; $i++) {
            $active = ($i == $pagina_actual) ? 'active' : '';
            echo '<li class="page-item ' . $active . '"><a class="page-link" href="rubrica.php?facultad=' . $facultad . '&pagina=' . $i . '">' . $i . '</a></li>';
        }
        if ($pagina_actual < $total_paginas) echo '<li class="page-item"><a class="page-link" href="rubrica.php?facultad=' . $facultad . '&pagina=' . ($pagina_actual + 1) . '">Siguiente &raquo;</a></li>';
        echo '</ul></nav>';
    }
} else {
    $nombreFacultad = $facultades[$facultad] ?? 'desconocida';
    echo '<div class="alert alert-warning" role="alert"><i class="fas fa-exclamation-triangle"></i> No hay proyectos para la facultad de <strong>' . $nombreFacultad . '</strong>.</div>';
}
?>