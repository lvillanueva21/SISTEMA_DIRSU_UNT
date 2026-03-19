<?php
// Iniciar sesión si no está iniciada
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Obtener la variable de sesión $id_escuela
$id_escuela = $_SESSION['id_escuela'];

// Filtro para la facultad: se usará $id_escuela
$filtro = $id_escuela;

// Establecer la cantidad de registros por página
$registros_por_pagina = 2;

// Obtener el número de la página actual desde el parámetro URL, si no está definido se asume página 1
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual <= 0) {
    $pagina_actual = 1;
}

// Calcular el OFFSET para la consulta SQL
$offset = ($pagina_actual - 1) * $registros_por_pagina;

// Consulta SQL para obtener los proyectos filtrados
// Se hace INNER JOIN con la tabla usuarios para obtener los nombres y apellidos (Coordinador)
// Se filtran los proyectos por facultad y se muestran solo aquellos cuyo dato usuario tenga una longitud menor a 5.
$query = "SELECT p.*, u.nombres, u.apellidos 
          FROM proyectos p
          INNER JOIN usuarios u ON u.id_py = p.id
          WHERE p.facultad = ?
            AND CHAR_LENGTH(u.usuario) < 5
          LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conexion, $query);
mysqli_stmt_bind_param($stmt, "iii", $filtro, $registros_por_pagina, $offset);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Arreglo para almacenar los proyectos filtrados
$proyectos_filtrados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proyectos_filtrados[] = $row;
}
mysqli_stmt_close($stmt);

// Mostrar los proyectos filtrados en una tabla
if (count($proyectos_filtrados) > 0) {
    echo '<table class="table table-bordered table-sm">';
    // Cabecera de la tabla: se añade la columna Estado después de Proyecto
    echo '<thead>
            <tr>
              <th>#</th>
              <th>Proyecto</th>
              <th>Estado</th>
              <th>Coordinador</th>
              <th>Acción</th>
            </tr>
          </thead>';
    echo '<tbody>';
    $contador = 1;
    foreach ($proyectos_filtrados as $proyecto) {
        // Definir la etiqueta para el Estado según el valor de p.estado
        if ($proyecto['estado'] == 0) {
            $estado_label = '<label class="badge bg-primary">En Espera</label>';
        } elseif ($proyecto['estado'] == 1) {
            $estado_label = '<label class="badge bg-warning">Revisión</label>';
        } elseif ($proyecto['estado'] == 2) {
            $estado_label = '<label class="badge bg-success">Aprobado</label>';
        } else {
            $estado_label = '<label class="badge bg-secondary">Desconocido</label>';
        }
        
        echo '<tr>';
        echo '<td>' . $contador . '</td>';
        echo '<td>' . htmlspecialchars($proyecto['p2']) . '</td>';
        echo '<td>' . $estado_label . '</td>';
        // La columna Coordinador muestra los datos concatenados de nombres y apellidos provenientes de la tabla usuarios
        echo '<td>' . htmlspecialchars($proyecto['nombres'] . ' ' . $proyecto['apellidos']) . '</td>';
        echo '<td>
                <button class="btn btn-info btn-sm" data-id="' . $proyecto['id'] . '">
                  <i class="fas fa-info-circle"></i> Ver detalles
                </button>
                <button class="btn btn-warning btn-sm btn-calificar" data-id="' . $proyecto['id'] . '" data-toggle="modal" data-target="#modalCalificar">
                  <i class="fas fa-star"></i> Calificar
                </button>
              </td>';
        echo '</tr>';
        $contador++;
    }
    echo '</tbody>';
    echo '</table>';
    
    // Paginación: Consulta para contar el total de proyectos que cumplen con el filtro
    $query_count = "SELECT COUNT(*) 
                    FROM proyectos p
                    INNER JOIN usuarios u ON u.id_py = p.id
                    WHERE p.facultad = ?
                      AND CHAR_LENGTH(u.usuario) < 5";
    $stmt_count = mysqli_prepare($conexion, $query_count);
    mysqli_stmt_bind_param($stmt_count, "i", $filtro);
    mysqli_stmt_execute($stmt_count);
    mysqli_stmt_bind_result($stmt_count, $total_proyectos);
    mysqli_stmt_fetch($stmt_count);
    mysqli_stmt_close($stmt_count);

    $total_paginas = ceil($total_proyectos / $registros_por_pagina);

    echo '<nav>';
    echo '<ul class="pagination justify-content-center">';
    // Botón "Anterior"
    if ($pagina_actual > 1) {
        echo '<li class="page-item"><a class="page-link" href="visto.php?pagina=' . ($pagina_actual - 1) . '">&laquo; Anterior</a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link">&laquo; Anterior</span></li>';
    }
    
    // Enlaces de las páginas
    for ($i = 1; $i <= $total_paginas; $i++) {
        $active = ($i == $pagina_actual) ? 'active' : '';
        echo '<li class="page-item ' . $active . '">
                <a class="page-link" href="visto.php?pagina=' . $i . '">' . $i . '</a>
              </li>';
    }
    
    // Botón "Siguiente"
    if ($pagina_actual < $total_paginas) {
        echo '<li class="page-item"><a class="page-link" href="visto.php?pagina=' . ($pagina_actual + 1) . '">Siguiente &raquo;</a></li>';
    } else {
        echo '<li class="page-item disabled"><span class="page-link">Siguiente &raquo;</span></li>';
    }
    
    echo '</ul>';
    echo '</nav>';
} else {
    echo 'No se encontraron proyectos.';
}
?>
