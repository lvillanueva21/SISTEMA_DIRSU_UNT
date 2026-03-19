<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

include('../../componentes/db.php');

$id_escuela = $_SESSION['id_escuela'];
$filtro = $id_escuela;

$registros_por_pagina = 2;
$pagina_actual = isset($_GET['pagina']) ? (int)$_GET['pagina'] : 1;
if ($pagina_actual <= 0) $pagina_actual = 1;
$offset = ($pagina_actual - 1) * $registros_por_pagina;

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

$proyectos_filtrados = [];
while ($row = mysqli_fetch_assoc($result)) {
    $proyectos_filtrados[] = $row;
}
mysqli_stmt_close($stmt);

if (count($proyectos_filtrados) > 0) {
    echo '<table class="table table-bordered table-sm">';
    echo '<thead>
            <tr>
              <th>#</th>
              <th>Proyecto</th>
              <th>Estado</th>
              <th>Cotejo</th>
              <th>Derivación</th>
              <th>Coordinador</th>
              <th>Acción</th>
            </tr>
          </thead>';
    echo '<tbody>';
    $contador = 1;
    foreach ($proyectos_filtrados as $proyecto) {
        $estado_label = match ((int)$proyecto['estado']) {
            0 => '<span class="badge bg-primary">En Espera</span>',
            1 => '<span class="badge bg-warning">Revisión</span>',
            2 => '<span class="badge bg-success">Aprobado</span>',
            default => '<span class="badge bg-secondary">Desconocido</span>',
        };

        $pcf_cot_btn = '<span class="badge bg-secondary">No definido</span>';
        $id_py = $proyecto['id'];

        // Consultar pcf_cot
        $pcf_cot = null;
        $q1 = "SELECT pcf_cot FROM rutas_semestrales WHERE id_py = ?";
        if ($stmt1 = mysqli_prepare($conexion, $q1)) {
            mysqli_stmt_bind_param($stmt1, "i", $id_py);
            mysqli_stmt_execute($stmt1);
            mysqli_stmt_bind_result($stmt1, $pcf_cot);
            mysqli_stmt_fetch($stmt1);
            mysqli_stmt_close($stmt1);
        }

        // Determinar estilo
        if ($pcf_cot !== null) {
            if ($pcf_cot == 0) {
                $pcf_cot_btn = '<span class="badge bg-primary">En Espera</span>';
            } elseif ($pcf_cot == 1) {
                $pcf_cot_btn = '<span class="badge bg-success">Aprobado</span>';
            } elseif ($pcf_cot == 2) {
                $pcf_cot_btn = '<span class="badge bg-danger">Observado</span>';

                // Solo si es observado, buscamos pcf_limite
                $pcf_limite = null;
                $q_limite = "SELECT pcf_limite FROM cronogramas_semestrales WHERE id_py = ?";
                if ($stmt_limite = mysqli_prepare($conexion, $q_limite)) {
                    mysqli_stmt_bind_param($stmt_limite, "i", $id_py);
                    mysqli_stmt_execute($stmt_limite);
                    mysqli_stmt_bind_result($stmt_limite, $pcf_limite);
                    if (mysqli_stmt_fetch($stmt_limite) && !empty($pcf_limite)) {
                        $fecha_format = date('d/m/Y H:i:s', strtotime($pcf_limite));
                        $pcf_cot_btn .= "<br>⏰Fecha límite:<br><b>{$fecha_format}</b>";
                    }
                    mysqli_stmt_close($stmt_limite);
                }
            }
        }

        // Derivación: pcf_inicio
        $fecha_derivacion = 'No registrada';
        $q2 = "SELECT pcf_inicio FROM cronogramas_semestrales WHERE id_py = ?";
        if ($stmt2 = mysqli_prepare($conexion, $q2)) {
            mysqli_stmt_bind_param($stmt2, "i", $id_py);
            mysqli_stmt_execute($stmt2);
            mysqli_stmt_bind_result($stmt2, $pcf_inicio);
            if (mysqli_stmt_fetch($stmt2) && !empty($pcf_inicio)) {
                $fecha_derivacion = date('d/m/Y H:i:s', strtotime($pcf_inicio));
            }
            mysqli_stmt_close($stmt2);
        }

        echo '<tr>';
        echo '<td>' . $contador . '</td>';
        echo '<td>' . htmlspecialchars($proyecto['p2']) . '</td>';
        echo '<td>' . $estado_label . '</td>';
        echo '<td>' . $pcf_cot_btn . '</td>';
        echo '<td>' . $fecha_derivacion . '</td>';
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

    // Paginación
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

    echo '<nav><ul class="pagination justify-content-center">';
    echo $pagina_actual > 1
        ? '<li class="page-item"><a class="page-link" href="cotejo.php?pagina=' . ($pagina_actual - 1) . '">&laquo; Anterior</a></li>'
        : '<li class="page-item disabled"><span class="page-link">&laquo; Anterior</span></li>';

    for ($i = 1; $i <= $total_paginas; $i++) {
        $active = ($i == $pagina_actual) ? 'active' : '';
        echo '<li class="page-item ' . $active . '"><a class="page-link" href="cotejo.php?pagina=' . $i . '">' . $i . '</a></li>';
    }

    echo $pagina_actual < $total_paginas
        ? '<li class="page-item"><a class="page-link" href="cotejo.php?pagina=' . ($pagina_actual + 1) . '">Siguiente &raquo;</a></li>'
        : '<li class="page-item disabled"><span class="page-link">Siguiente &raquo;</span></li>';
    echo '</ul></nav>';
} else {
    echo 'No se encontraron proyectos.';
}
?>
