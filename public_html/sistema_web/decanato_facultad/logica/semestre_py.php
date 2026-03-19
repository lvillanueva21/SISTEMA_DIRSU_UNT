<?php 
include('../../componentes/db.php');

// Definir los ODS
$ods = ['1' => '1: Reducción de los indicadores de la pobreza', '2' => '2: Hambre y seguridad alimentaria', '3' => '3: Salud y bienestar', '4' => '4: Educación de calidad', '5' => '5: Igualdad de género y empoderamiento de la mujer', '6' => '6: Agua limpia y saneamiento', '7' => '7: Energía asequible y no contaminante', '8' => '8: Trabajo decente y crecimiento económico', '9' => '9: Industria, innovación e infraestructura', '10' => '10: Reducir las desigualdades', '11' => '11: Ciudades y comunidades sostenibles', '12' => '12: Producción y consumo responsables', '13' => '13: Acción por el clima', '14' => '14: Vida submarina', '15' => '15: Vida y ecosistemas terrestres', '16' => '16: Paz y justicia e instituciones sólidas', '17' => '17: Alianzas para lograr los objetivos'];

if (!isset($_GET['id_py'])) {
    echo 'ID de proyecto no válido.';
    exit;
}

$id_py = intval($_GET['id_py']); // Convierte el valor a entero para mayor seguridad

if ($id_py == 0) {
    echo '<div class="alert alert-warning" role="alert">No se ha registrado presentación de proyecto.</div>';
} else {
    // Verificar que la conexión esté establecida
    if (!$conexion) {
        die('<div class="alert alert-danger">Error de conexión a la base de datos.</div>');
    }

    $query_proyecto = "
        SELECT id, id_py, titulo, programa, ods, coordinador, integrantes, estudiantes, omi, lugar, beneficiados, fecha_inicio, fecha_fin, resumen, actividades, resultados, descripcion, matriz, comentarios, conclusiones, analisis, recomendaciones, fuentes, anexos, carga
        FROM proyectos_finales WHERE id_py = ?";

    $stmt_proyecto = mysqli_prepare($conexion, $query_proyecto);

    if ($stmt_proyecto) {
        mysqli_stmt_bind_param($stmt_proyecto, 'i', $id_py);
        mysqli_stmt_execute($stmt_proyecto);
        $result_proyecto = mysqli_stmt_get_result($stmt_proyecto);

        if ($row_proyecto = mysqli_fetch_assoc($result_proyecto)) {
            // Contenido de MODAL Semestral Proyecto
            echo '<div class="text-wrap">';
            echo '<div class="card card-primary">';
            
            // Fila para ODS, ID Proyecto, Fecha de Inicio y Fecha de Fin
            echo '<div class="row">';

            // ODS
            echo '<div class="col-md-3"><div class="card mb-3"><div class="card-body"><h5><i class="fas fa-globe"></i> ODS</h5><p>' . mostrarODS($row_proyecto['ods'], $ods) . '</p></div></div></div>';

            // ID Proyecto
            echo '<div class="col-md-3"><div class="card mb-3"><div class="card-body text-center"><h5><i class="fas fa-hashtag"></i> ID Proyecto</h5><p>' . mostrarDato($row_proyecto['id_py']) . '</p></div></div></div>';

            // Fecha de Inicio
            echo '<div class="col-md-3"><div class="card mb-3"><div class="card-body text-center"><h5><i class="fas fa-calendar"></i> Fecha de Inicio</h5><p>' . mostrarDato($row_proyecto['fecha_inicio']) . '</p></div></div></div>';

            // Fecha de Fin
            echo '<div class="col-md-3"><div class="card mb-3"><div class="card-body text-center"><h5><i class="fas fa-calendar-check"></i> Fecha de Fin</h5><p>' . mostrarDato($row_proyecto['fecha_fin']) . '</p></div></div></div>';

            echo '</div>'; // Cierre de fila (row)

            // Fila para Título y Programa
            echo '<div class="row">';

            // Título
            echo '<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5><i class="fas fa-book"></i> Título</h5><p>' . mostrarDato($row_proyecto['titulo']) . '</p></div></div></div>';

            // Programa
            echo '<div class="col-md-6"><div class="card mb-3"><div class="card-body"><h5><i class="fas fa-university"></i> Programa</h5><p>' . mostrarDato($row_proyecto['programa']) . '</p></div></div></div>';

            echo '</div>'; // Cierre de fila (row)

            // Resto de campos
            $campos_grandes = ['coordinador' => 'user-tie', 'lugar' => 'map-marker-alt', 'beneficiados' => 'users', 'integrantes' => 'users-cog', 'estudiantes' => 'user-graduate', 'omi' => 'globe-americas', 'resumen' => 'file-alt', 'actividades' => 'tasks', 'resultados' => 'chart-bar', 'descripcion' => 'align-left', 'matriz' => 'table', 'analisis' => 'search', 'recomendaciones' => 'clipboard-check', 'carga' => 'file-upload'];

            foreach ($campos_grandes as $campo => $icono) {
                echo '<div class="col-12"><div class="card mb-3"><div class="card-body"><h5><i class="fas fa-' . $icono . '"></i> ' . ucfirst($campo) . '</h5><p>' . mostrarDato($row_proyecto[$campo]) . '</p></div></div></div>';
            }

            echo '</div>'; // Cierre de card-primary
            echo '</div>'; // Cierre de card
        } else {
            echo '<div class="alert alert-info">No se encontraron detalles para este proyecto.</div>';
        }
        
        mysqli_stmt_close($stmt_proyecto);
    } else {
        echo '<div class="alert alert-danger">Error en la consulta a la base de datos.</div>';
    }
}

mysqli_close($conexion);

// Función para limpiar espacios en blanco excesivos y múltiples saltos de línea
function limpiarTexto($texto) {
    $texto = trim($texto); // Elimina espacios al inicio y final
    $texto = preg_replace('/\s{2,}/', ' ', $texto); // Reemplaza múltiples espacios por uno solo
    $texto = preg_replace('/(\r?\n){3,}/', "\n\n", $texto); // Reduce múltiples saltos de línea a un máximo de 2
    return nl2br($texto); // Mantiene saltos de línea relevantes
}

// Función para mostrar datos o mensaje si están vacíos
function mostrarDato($dato) {
    $dato = limpiarTexto($dato); // Aplica la limpieza
    return !empty($dato) ? $dato : '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
}

// Función para mostrar ODS como botones con desplazamiento de texto
function mostrarODS($valorODS, $ods) {
    if (empty($valorODS)) {
        return '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
    }

    $listaODS = explode(',', $valorODS); // Separar valores por coma
    $botones = '';

    foreach ($listaODS as $num) {
        $num = trim($num); // Eliminar espacios en blanco
        if (isset($ods[$num])) {
            // Botón con texto desplazándose dentro del botón, envuelto en un div para salto de línea
            $botones .= '<div class="mb-2"><span class="btn btn-primary btn-sm ods-marquee"><span class="text-marquee">' . htmlspecialchars($ods[$num]) . '</span></span></div>';
        }
    }

    return !empty($botones) ? $botones : '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
}
?>

<style>
/* Estilo para hacer que solo el texto dentro del botón se desplace */
.ods-marquee {
  display: inline-block;
  width: 150px; /* Ajusta el tamaño según necesites */
  overflow: hidden;
  position: relative;
}

.text-marquee {
  display: inline-block;
  white-space: nowrap;
  animation: scroll-text 10s linear infinite;
}

@keyframes scroll-text {
  0% { transform: translateX(100%); }
  100% { transform: translateX(-100%); }
}
</style>
