<?php 
include("../../componentes/db.php");

// Verificar si se pasó un ID de proyecto en la solicitud AJAX
if (isset($_GET['id'])) {
    $id_proyecto = (int)$_GET['id']; // Obtener el ID del proyecto

    // Consulta SQL para obtener los detalles del proyecto de la tabla proyectos_finales
    $query = "SELECT programa, titulo, ods, coordinador, integrantes, estudiantes, omi, lugar, beneficiados, fecha_inicio, fecha_fin, resumen, actividades, resultados, descripcion, matriz, comentarios, conclusiones, analisis, recomendaciones, fuentes, anexos, carga FROM proyectos_finales WHERE id_py = ?";
    $stmt = mysqli_prepare($conexion, $query);
    mysqli_stmt_bind_param($stmt, "i", $id_proyecto);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $proyecto_filtrado = mysqli_fetch_assoc($result);

    // Cerrar la consulta
    mysqli_stmt_close($stmt);

    // Verificar si se encontraron los detalles
    if ($proyecto_filtrado) {
        // Imprimir los detalles del proyecto con el formato solicitado
        echo '<div>';
        
        echo '<h3 class="text-primary"><br>I. GENERALIDADES</h3><br>';
        echo '<b class="text-primary">1.1. Título del Programa</b>';
        echo '<p>' . (isset($proyecto_filtrado['programa']) && !empty($proyecto_filtrado['programa']) ? ($proyecto_filtrado['programa']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">1.2. Título del Proyecto</b>';
        echo '<p>' . (isset($proyecto_filtrado['titulo']) && !empty($proyecto_filtrado['titulo']) ? ($proyecto_filtrado['titulo']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        // Mostrar los ODS seleccionados
        echo '<b class="text-primary">1.3. Objetivo(s) de Desarrollo Sostenible que cumple el proyecto</b>';
        echo '<p class="d-block">';

        // Array de ODS (Objetivos de Desarrollo Sostenible)
        $ods = [
            '1' => 'ODS1: Reducción de los indicadores de la pobreza',
            '2' => 'ODS2: Hambre y seguridad alimentaria',
            '3' => 'ODS3: Salud y bienestar',
            '4' => 'ODS4: Educación de calidad',
            '5' => 'ODS5: Igualdad de género y empoderamiento de la mujer',
            '6' => 'ODS6: Agua limpia y saneamiento',
            '7' => 'ODS7: Energía asequible y no contaminante',
            '8' => 'ODS8: Trabajo decente y crecimiento económico',
            '9' => 'ODS9: Industria, innovación e infraestructura',
            '10' => 'ODS10: Reducir las desigualdades',
            '11' => 'ODS11: Ciudades y comunidades sostenibles',
            '12' => 'ODS12: Producción y consumo responsables',
            '13' => 'ODS13: Acción por el clima',
            '14' => 'ODS14: Vida submarina',
            '15' => 'ODS15: Vida y ecosistemas terrestres',
            '16' => 'ODS16: Paz y justicia e instituciones sólidas',
            '17' => 'ODS17: Alianzas para lograr los objetivos'
        ];

        // Verificar si el campo 'ods' tiene valores seleccionados
        if (isset($proyecto_filtrado['ods']) && !empty($proyecto_filtrado['ods'])) {
            // Convertir la cadena de ODS seleccionados en un array
            $ods_seleccionados = explode(',', $proyecto_filtrado['ods']);
            
            // Recorrer los valores seleccionados y mostrarlos
            foreach ($ods_seleccionados as $valor) {
                if (isset($ods[$valor])) {
                    echo '<button type="button" class="btn btn-primary m-1">' . ($ods[$valor]) . '</button>';
                }
            }
        } else {
            echo '<p><span style="color:red;">Ítem sin información.</span></p>';
        }

        echo '</p>';
        
        echo '<b class="text-primary">1.4. Integrantes del proyecto</b>';
        echo '<p class="text-primary">Coordinador</p>';
        echo '<p>' . (isset($proyecto_filtrado['coordinador']) && !empty($proyecto_filtrado['coordinador']) ? ($proyecto_filtrado['coordinador']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<p class="text-primary">Integrantes</p>';
        echo '<p>' . (isset($proyecto_filtrado['integrantes']) && !empty($proyecto_filtrado['integrantes']) ? ($proyecto_filtrado['integrantes']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<p class="text-primary">Estudiantes</p>';
        echo '<p>' . (isset($proyecto_filtrado['estudiantes']) && !empty($proyecto_filtrado['estudiantes']) ? ($proyecto_filtrado['estudiantes']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">1.5. Objetivos, Metas e Indicadores</b>';
        echo '<p>' . (isset($proyecto_filtrado['omi']) && !empty($proyecto_filtrado['omi']) ? ($proyecto_filtrado['omi']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">1.6. Integrantes del proyecto</b>';
        echo '<p>' . (isset($proyecto_filtrado['lugar']) && !empty($proyecto_filtrado['lugar']) ? ($proyecto_filtrado['lugar']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">1.7. Institución y Población Beneficiada</b>';
        echo '<p>' . (isset($proyecto_filtrado['beneficiados']) && !empty($proyecto_filtrado['beneficiados']) ? ($proyecto_filtrado['beneficiados']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">1.8. Duración del Proyecto o Actividad</b><br>';
        echo '<p class="text-primary">Fecha de inicio del proyecto</p>';
        echo '<p>' . (isset($proyecto_filtrado['fecha_inicio']) && !empty($proyecto_filtrado['fecha_inicio']) ? ($proyecto_filtrado['fecha_inicio']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<p class="text-primary">Fecha de fin del proyecto</p>';
        echo '<p>' . (isset($proyecto_filtrado['fecha_fin']) && !empty($proyecto_filtrado['fecha_fin']) ? ($proyecto_filtrado['fecha_fin']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<h3 class="text-primary">II. RESULTADOS</h3><br>';
        echo '<b class="text-primary">2.1. Resumen</b>';
        echo '<p>' . (isset($proyecto_filtrado['resumen']) && !empty($proyecto_filtrado['resumen']) ? ($proyecto_filtrado['resumen']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.2. Actividades Ejecutadas</b>';
        echo '<p>' . (isset($proyecto_filtrado['actividades']) && !empty($proyecto_filtrado['actividades']) ? ($proyecto_filtrado['actividades']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3. Resultados</b><br>';
        echo '<b class="text-primary">2.3.1. Descripción de resultados</b>';
        echo '<p>' . (isset($proyecto_filtrado['descripcion']) && !empty($proyecto_filtrado['descripcion']) ? ($proyecto_filtrado['descripcion']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.2. Matriz de indicadores de impacto</b>';
        echo '<p>' . (isset($proyecto_filtrado['matriz']) && !empty($proyecto_filtrado['matriz']) ? ($proyecto_filtrado['matriz']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.3. Comentarios o Discusión de Resultados (Opcional)</b>';
        echo '<p>' . (isset($proyecto_filtrado['comentarios']) && !empty($proyecto_filtrado['comentarios']) ? ($proyecto_filtrado['comentarios']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.4. Conclusiones</b>';
        echo '<p>' . (isset($proyecto_filtrado['conclusiones']) && !empty($proyecto_filtrado['conclusiones']) ? ($proyecto_filtrado['conclusiones']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.5. Análisis de impacto del proyecto ejecutado</b>';
        echo '<p>' . (isset($proyecto_filtrado['analisis']) && !empty($proyecto_filtrado['analisis']) ? ($proyecto_filtrado['analisis']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.6. Recomendaciones (Opcional)</b>';
        echo '<p>' . (isset($proyecto_filtrado['recomendaciones']) && !empty($proyecto_filtrado['recomendaciones']) ? ($proyecto_filtrado['recomendaciones']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.7. Fuentes consultadas</b>';
        echo '<p>' . (isset($proyecto_filtrado['fuentes']) && !empty($proyecto_filtrado['fuentes']) ? ($proyecto_filtrado['fuentes']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<b class="text-primary">2.3.8. Anexos (Fuentes de verificación)</b>';
        echo '<p>' . (isset($proyecto_filtrado['anexos']) && !empty($proyecto_filtrado['anexos']) ? ($proyecto_filtrado['anexos']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '<h3 class="text-primary">III. CUMPLIMIENTO DE LA CARGA HORARIA</h3><br>';
        echo '<b class="text-primary">3.1. Número de horas de Responsabilidad Social</b>';
        echo '<p>' . (isset($proyecto_filtrado['carga']) && !empty($proyecto_filtrado['carga']) ? ($proyecto_filtrado['carga']) : '<span style="color:red;">Ítem sin información.</span>') . '</p>';
        
        echo '</div>';
    } else {
        echo '<p>No se encontraron detalles para este proyecto.</p>';
    }
} else {
    // Si no se pasa un ID, mostrar el mensaje por defecto
    echo '<div>';
    echo '<p>Selecciona Ver detalles en algún proyecto.</p>';
    echo '</div>';
}
?>
