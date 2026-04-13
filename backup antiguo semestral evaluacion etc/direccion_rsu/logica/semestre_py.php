<?php 
include('../../componentes/db.php');

// Definir los ODS
$ods = [
    '1' => '1: Reducción de los indicadores de la pobreza', 
    '2' => '2: Hambre y seguridad alimentaria', 
    '3' => '3: Salud y bienestar', 
    '4' => '4: Educación de calidad', 
    '5' => '5: Igualdad de género y empoderamiento de la mujer', 
    '6' => '6: Agua limpia y saneamiento', 
    '7' => '7: Energía asequible y no contaminante', 
    '8' => '8: Trabajo decente y crecimiento económico', 
    '9' => '9: Industria, innovación e infraestructura', 
    '10' => '10: Reducir las desigualdades', 
    '11' => '11: Ciudades y comunidades sostenibles', 
    '12' => '12: Producción y consumo responsables', 
    '13' => '13: Acción por el clima', 
    '14' => '14: Vida submarina', 
    '15' => '15: Vida y ecosistemas terrestres', 
    '16' => '16: Paz y justicia e instituciones sólidas', 
    '17' => '17: Alianzas para lograr los objetivos'
];

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

            // Arreglo de campos para mostrar con sus respectivos íconos
            $campos_grandes = [
                'coordinador'    => 'user-tie',
                'lugar'          => 'map-marker-alt',
                'beneficiados'   => 'users',
                'integrantes'    => 'users-cog',
                'estudiantes'    => 'user-graduate',
                'omi'            => 'globe-americas',
                'resumen'        => 'file-alt',
                'actividades'    => 'tasks',
                'resultados'     => 'chart-bar',
                'descripcion'    => 'align-left',
                'matriz'         => 'table',
                'analisis'       => 'search',
                'recomendaciones'=> 'clipboard-check',
                'carga'          => 'file-upload',
                'comentarios'    => 'comments',
                'conclusiones'   => 'check-circle',
                'fuentes'        => 'book',
                'anexos'         => 'paperclip'
            ];

            echo '<div class="row">';
            foreach ($campos_grandes as $campo => $icono) {
                $contenido = mostrarDato($row_proyecto[$campo]);
                // Aplicar la conversión de enlaces solo para el campo "anexos"
                if ($campo === 'anexos') {
                    $contenido = convertirEnlaces($contenido);
                }
                echo '<div class="col-12"><div class="card mb-3"><div class="card-body"><h5><i class="fas fa-' . $icono . '"></i> ' . ucfirst($campo) . '</h5><p>' . $contenido . '</p></div></div></div>';
            }
            echo '</div>'; // Cierre de row

            echo '</div>'; // Cierre de card-primary
            echo '</div>'; // Cierre de text-wrap
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
    $texto = trim($texto);
    $texto = preg_replace('/\s{2,}/', ' ', $texto);
    $texto = preg_replace('/(\r?\n){3,}/', "\n\n", $texto);
    return nl2br($texto);
}

// Función para mostrar datos o mensaje si están vacíos
function mostrarDato($dato) {
    $dato = limpiarTexto($dato);
    return !empty($dato) ? $dato : '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
}

// Función para mostrar ODS como botones con desplazamiento de texto
function mostrarODS($valorODS, $ods) {
    if (empty($valorODS)) {
        return '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
    }

    $listaODS = explode(',', $valorODS);
    $botones = '';

    foreach ($listaODS as $num) {
        $num = trim($num);
        if (isset($ods[$num])) {
            $botones .= '<div class="mb-2"><span class="btn btn-primary btn-sm ods-marquee"><span class="text-marquee">' . htmlspecialchars($ods[$num]) . '</span></span></div>';
        }
    }

    return !empty($botones) ? $botones : '<b class="text-danger">No se ha ingresado información en este ítem.</b>';
}

// Función para convertir URLs en enlaces clicables eliminando etiquetas HTML sobrantes
function convertirEnlaces($texto) {
    // Se eliminan todas las etiquetas HTML para evitar código extra
    $textoLimpio = strip_tags($texto);
    $patron = '/(https?:\/\/[^\s]+)/';
    $reemplazo = '<a href="$1" target="_blank">$1</a>';
    return preg_replace($patron, $reemplazo, $textoLimpio);
}
?>

<style>
/* Estilo para que solo el texto dentro del botón se desplace */
.ods-marquee {
  display: inline-block;
  width: 150px;
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
