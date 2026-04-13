<?php 
include('../../componentes/db.php');

if (!isset($_GET['id_py'])) {
    echo 'ID de proyecto no válido.';
    exit;
}

$id_py = intval($_GET['id_py']); // Convierte el valor a entero para mayor seguridad

if ($id_py == 0) {
    echo '<div class="alert alert-warning" role="alert">No se ha registrado presentación de proyecto.</div>';
    exit;
}

if (!$conexion) {
    die('<div class="alert alert-danger">Error de conexión a la base de datos.</div>');
}

$query_proyecto = "
    SELECT pf.id, pf.id_py, pf.estado, pf.titulo, pf.programa, 
           pf.cot_cf, pf.obs_cotejo_cf, pf.rub_cf, pf.obs_rubrica_cf, 
           pf.vb_df, pf.vb_dd, pf.cot_dr, pf.obs_cotejo_dr, pf.rub_dr, pf.obs_rubrica_dr,
           p.coordinador
    FROM proyectos_finales AS pf
    INNER JOIN proyectos AS p ON pf.id_py = p.id
    WHERE pf.id_py = ?";

// Función para mostrar las observaciones
function mostrar_observacion($valor) {
    if ($valor === null || trim($valor) === '') {
        return '<b style="color:red;">Sin Observación Registrada</b>';
    }
    if (is_string($valor) && preg_match('/^\[.*\]$/', $valor)) {
        $inner = trim($valor, '[]');
        $items = array_map(function($item) {
            return trim($item, " \t\n\r\0\x0B\"");
        }, explode(',', $inner));
        $aspectos = [
            'ASPECTO: ESTRUCTURA',
            'ASPECTO: CONTENIDO',
            'ASPECTO: REDACCIÓN',
            'ASPECTO: CALIDAD DE INFORMACIÓN',
            'ASPECTO: PROPUESTA DE MEJORA'
        ];
        $lines = [];
        if (count($items) === 5) {
            foreach ($items as $index => $item) {
                if ($item === '' || trim($item) === '') {
                    $lines[] = '<span style="color: darkgreen; font-weight: bold;">' . $aspectos[$index] . '</span> <b style="color:red;">Sin Observación Registrada</b>';
                } else {
                    $lines[] = '<span style="color: darkgreen; font-weight: bold;">' . $aspectos[$index] . '</span> ' . htmlspecialchars($item);
                }
            }
        } else {
            foreach ($items as $item) {
                if ($item === '' || trim($item) === '') {
                    $lines[] = '<b style="color:red;">Sin Observación Registrada</b>';
                } else {
                    $lines[] = htmlspecialchars($item);
                }
            }
        }
        return implode('<br>', $lines);
    }
    return htmlspecialchars($valor);
}

// Función para procesar cada aspecto de evaluación (para rúbricas)
function procesar_aspecto($dato, $aspecto) {
    if (is_string($dato)) {
        $dato = trim($dato, " \t\n\r\0\x0B\"");
    }
    if (is_numeric($dato)) {
        $num = (int)$dato;
        $mapping = [
            0 => "En Espera",
            1 => "Insuficiente",
            2 => "Mejorable",
            3 => "Satisfactorio",
            4 => "Excelente"
        ];
        $emojiMapping = [
            0 => "⏱",
            1 => "⭐",
            2 => "⭐⭐",
            3 => "⭐⭐⭐",
            4 => "⭐⭐⭐⭐"
        ];
        $description = isset($mapping[$num]) ? $mapping[$num] : htmlspecialchars($dato);
        $puntaje = $num;
        $puntoLabel = ($puntaje == 1 ? "punto" : "puntos");
        $emoji = isset($emojiMapping[$num]) ? $emojiMapping[$num] : "";
        return '<span style="color: darkgreen; font-weight: bold;">' . $aspecto . '</span> ' 
               . $description . " ($puntaje $puntoLabel) " . $emoji;
    }
    if (is_string($dato) && trim($dato) === '') {
        $dato = '<b style="color:red;">Sin Observación Registrada</b>';
        return '<span style="color: darkgreen; font-weight: bold;">' . $aspecto . '</span> ' . $dato;
    }
    return '<span style="color: darkgreen; font-weight: bold;">' . $aspecto . '</span> ' . htmlspecialchars($dato);
}

// Función para generar la etiqueta de estado a partir del valor de evaluación
function generar_etiqueta($valor) {
    if (is_numeric($valor)) {
        $num = (int)$valor;
        if ($num === 0) {
            return '<span class="bg-primary text-white fw-bold">En Espera</span>';
        } elseif ($num === 1) {
            return '<span class="bg-success text-white fw-bold">Aprobado</span>';
        } elseif ($num === 2) {
            return '<span class="bg-warning text-white fw-bold">Observado</span>';
        } else {
            return htmlspecialchars($valor);
        }
    }
    $espera_valores = [null, "0", "", []];
    if (in_array($valor, $espera_valores, true)) {
        return '<span class="text-secondary fw-bold">En Espera</span>';
    }
    if (is_array($valor) && count($valor) === 5) {
        $puntajes = [];
        foreach ($valor as $dato) {
            if (is_numeric($dato)) {
                $puntajes[] = (int)$dato;
            } else {
                $puntajes[] = (trim($dato) === '' ? 0 : intval($dato));
            }
        }
        $sum = array_sum($puntajes);
        if ($sum == 0) {
            $label = '<span class="bg-primary text-white fw-bold">En Espera (puntaje total: ' . $sum . ')</span>';
        } elseif ($sum < 14) {
            $label = '<span class="bg-warning text-white fw-bold">Observado (puntaje total: ' . $sum . ')</span>';
        } else {
            $label = '<span class="bg-success text-white fw-bold">Aprobado (puntaje total: ' . $sum . ')</span>';
        }
        $aspectos = [
            'ASPECTO: ESTRUCTURA',
            'ASPECTO: CONTENIDO',
            'ASPECTO: REDACCIÓN',
            'ASPECTO: CALIDAD DE INFORMACIÓN',
            'ASPECTO: PROPUESTA DE MEJORA'
        ];
        $lines = [];
        foreach ($valor as $index => $dato) {
            $lines[] = procesar_aspecto($dato, $aspectos[$index]);
        }
        return $label . '<br>' . implode('<br>', $lines);
    }
    if (is_string($valor) && preg_match('/^\[.*\]$/', $valor)) {
        $inner = trim($valor, '[]');
        $items = array_map(function($item) {
            return trim($item, " \t\n\r\0\x0B\""); 
        }, explode(',', $inner));
        if (count($items) === 5) {
            $puntajes = [];
            foreach ($items as $item) {
                if (is_numeric($item)) {
                    $puntajes[] = (int)$item;
                } else {
                    $puntajes[] = (trim($item) === '' ? 0 : intval($item));
                }
            }
            $sum = array_sum($puntajes);
            if ($sum == 0) {
                $label = '<span class="bg-primary text-white fw-bold">En Espera (puntaje total: ' . $sum . ')</span>';
            } elseif ($sum < 14) {
                $label = '<span class="bg-warning text-white fw-bold">Observado (puntaje total: ' . $sum . ')</span>';
            } else {
                $label = '<span class="bg-success text-white fw-bold">Aprobado (puntaje total: ' . $sum . ')</span>';
            }
            $aspectos = [
                'ASPECTO: ESTRUCTURA',
                'ASPECTO: CONTENIDO',
                'ASPECTO: REDACCIÓN',
                'ASPECTO: CALIDAD DE INFORMACIÓN',
                'ASPECTO: PROPUESTA DE MEJORA'
            ];
            $lines = [];
            foreach ($items as $index => $item) {
                $lines[] = procesar_aspecto($item, $aspectos[$index]);
            }
            return $label . '<br>' . implode('<br>', $lines);
        } else {
            return implode('<br>', array_map('htmlspecialchars', $items));
        }
    }
    return $valor;
}

$stmt_proyecto = mysqli_prepare($conexion, $query_proyecto);
if ($stmt_proyecto) {
    mysqli_stmt_bind_param($stmt_proyecto, 'i', $id_py);
    mysqli_stmt_execute($stmt_proyecto);
    $result_proyecto = mysqli_stmt_get_result($stmt_proyecto);

    if ($row_proyecto = mysqli_fetch_assoc($result_proyecto)) {
        // --- Determinar el botón de Estado ---
        // Se usan los valores de evaluación: cot_cf, rub_cf, vb_df, vb_dd, cot_dr y rub_dr
        $evals = [
            $row_proyecto["cot_cf"], 
            $row_proyecto["rub_cf"], 
            $row_proyecto["vb_df"], 
            $row_proyecto["vb_dd"], 
            $row_proyecto["cot_dr"], 
            $row_proyecto["rub_dr"]
        ];
        $hasWarning = false;
        $hasPrimary = false;
        $allSuccess = true;
        foreach ($evals as $val) {
            $evalLabel = generar_etiqueta($val);
            if (strpos($evalLabel, 'bg-warning') !== false) {
                $hasWarning = true;
            }
            if (strpos($evalLabel, 'bg-primary') !== false) {
                $hasPrimary = true;
            }
            if (strpos($evalLabel, 'bg-success') === false) {
                $allSuccess = false;
            }
        }
        if ($hasWarning) {
            $overallText = "Proyecto con Observaciones";
            $btnClass = "btn-warning";
        } elseif ($hasPrimary) { // Si no hay warning y hay al menos 1 primary
            $overallText = "En Espera de evaluación";
            $btnClass = "btn-primary";
        } elseif ($allSuccess) {
            $overallText = "Evaluación Aprobada con Éxito";
            $btnClass = "btn-success";
        } else {
            $overallText = "Proyecto con Observaciones";
            $btnClass = "btn-warning";
        }
        $btnEstado = '<button type="button" class="btn ' . $btnClass . '">' . $overallText . '</button>';

        // --- Actualización de estado global en la tabla "proyectos" ---
        // Si el proyecto está globalmente aprobado ($allSuccess es true),
        // se actualiza el campo "estado" a 2.
        if ($allSuccess) {
            $query_update = "UPDATE proyectos SET estado = 2 WHERE id = ?";
            $stmt_update = mysqli_prepare($conexion, $query_update);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, 'i', $id_py);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);
            } else {
                echo '<div class="alert alert-danger">Error en la preparación de la actualización.</div>';
            }
        }

        // --- Imprimir tabla resumen del proyecto ---
        echo '<div class="text-wrap"><div class="card card-primary">';
        echo '<table class="table"><thead class="bg-primary text-white"><tr><th>ID Proyecto</th><th>Responsable</th><th>Estado</th><th>Título</th><th>Programa</th></tr></thead>';
        echo '<tbody><tr><td>' . $row_proyecto['id_py'] . '</td><td>' . $row_proyecto['coordinador'] . '</td><td>' . $btnEstado . '</td><td>' . $row_proyecto['titulo'] . '</td><td>' . $row_proyecto['programa'] . '</td></tr></tbody></table>';

        // --- Imprimir segunda tabla: Evaluaciones y observaciones ---
        echo '<table class="table table-bordered"><thead style="background-color: #28a745; color: white;">';
        echo '<tr><th>Categoría</th><th>Evaluación</th><th>Observación</th></tr></thead><tbody>';
        echo '<tr><th>Cotejo - Comité de Facultad</th><td>' . generar_etiqueta($row_proyecto["cot_cf"]) . '</td><td>' . mostrar_observacion($row_proyecto["obs_cotejo_cf"]) . '</td></tr>';
        echo '<tr><th>Rúbrica - Comité de Facultad</th><td>' . generar_etiqueta($row_proyecto["rub_cf"]) . '</td><td>' . mostrar_observacion($row_proyecto["obs_rubrica_cf"]) . '</td></tr>';
        echo '<tr><th>Visto Bueno Decanato</th><td>' . generar_etiqueta($row_proyecto["vb_df"]) . '</td><td><b>No aplica</b></td></tr>';
        echo '<tr><th>Visto Bueno Dirección de Departamento</th><td>' . generar_etiqueta($row_proyecto["vb_dd"]) . '</td><td><b>No aplica</b></td></tr>';
        echo '<tr><th>Cotejo - DIRSU</th><td>' . generar_etiqueta($row_proyecto["cot_dr"]) . '</td><td>' . mostrar_observacion($row_proyecto["obs_cotejo_dr"]) . '</td></tr>';
        echo '<tr><th>Rúbrica - DIRSU</th><td>' . generar_etiqueta($row_proyecto["rub_dr"]) . '</td><td>' . mostrar_observacion($row_proyecto["obs_rubrica_dr"]) . '</td></tr>';
        echo '</tbody></table>';

        echo '</div>'; // Cierre de card-primary
        echo '</div>'; // Cierre de text-wrap
    } else {
        echo '<div class="alert alert-info">No se encontraron detalles para este proyecto.</div>';
    }
    mysqli_stmt_close($stmt_proyecto);
} else {
    echo '<div class="alert alert-danger">Error en la consulta a la base de datos.</div>';
}

mysqli_close($conexion);
?>
