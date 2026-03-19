<?php 
include('../../componentes/db.php');

if (isset($_GET['id_py'])) {
    $id_py = $_GET['id_py'];

    // Asignamos el valor de 'id_py' a la variable temporal 'id_py_temp'
    $id_py_temp = $id_py;

    if ($id_py == 0) {
        echo '<div class="alert alert-warning" role="alert">No se ha registrado presentación de proyecto.</div>';
    } else {
        $query_proyecto = "
            SELECT estado, p1, p2, p3, p4, p5, p6, p7_1, p7_2, infantes, ninos, adolescentes, jovenes, adultos, adultos_mayores, 
            sector, caserio, distrito, provincia, departamento, fecha_inicio, fecha_fin, planificacion, ejecucion, monitoreo, 
            p10_1s, p10_2s, p10_3s, p10_1h, p10_2h, p10_3h, disciplinar, facultad, programa_estudios, departamento_academico, 
            coordinador, componentes, integrantes_docentes, delegados_estudiantes, diagnostico, justificacion, general, especificos, 
            metas, cronograma1, cronograma2, metodologia, entregables, impacto, matriz, pre_dis, pre_nodis, ser_dis, ser_nodis, resumen, 
            monto_uni, monto_auto, monto_otro 
            FROM proyectos
            WHERE id = ?";

        $stmt_proyecto = mysqli_prepare($conexion, $query_proyecto);
        mysqli_stmt_bind_param($stmt_proyecto, 'i', $id_py);
        mysqli_stmt_execute($stmt_proyecto);
        $result_proyecto = mysqli_stmt_get_result($stmt_proyecto);

        if ($row_proyecto = mysqli_fetch_assoc($result_proyecto)) {
            // Crear un contenedor para el modal

            echo '<div class="card">';
            echo '<div class="card card-primary">';
            echo '<div class="card-header">';    
            echo '<h4><i class="bi bi-card-text"></i>Generalidades</h4>'; 
            echo '</div>'; 
            echo '<div class="card-body">';

            echo '<div class="row">';
            echo '<div class="col-12 col-md-12 col-lg-8 order-2 order-md-1">';
            // 3 divs de Presentación
            echo '<div class="row">';

            echo '<div class="col-12 col-sm-4">';
            echo '<div class="info-box bg-light" style="background-color: #12377B; color: white; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">';
            echo '<div class="info-box-content" style="text-align: center;">';

            echo '<span class="info-box-text">Fecha Inicio</span>';
if (empty($row_proyecto['fecha_inicio'])) {
    echo '<span class="info-box-text" style="color: darkred;">Vacío</span>';
} else {
    echo '<span class="info-box-number mb-0">' . htmlspecialchars($row_proyecto['fecha_inicio']) . '</span>';
}

// Mostrar Fecha de Fin
echo '<span class="info-box-text">Fecha Fin</span>';
if (empty($row_proyecto['fecha_fin'])) {
    echo '<span class="info-box-text" style="color: darkred;">Vacío</span>';
} else {
    echo '<span class="info-box-number mb-0">' . htmlspecialchars($row_proyecto['fecha_fin']) . '</span>';
}
            echo '</div>'; 
            echo '</div>'; 
            echo '</div>';
//2
echo '<div class="col-12 col-sm-4">';
echo '<div class="info-box bg-light" style="background-color: #12377B; color: white; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">';
echo '<div class="info-box-content" style="text-align: center;">';

echo '<span class="info-box-text">Lugar de Ejecución</span>';

// Crear un array de los valores a concatenar
$values = [];

// Comprobar y añadir los valores a la lista
$values[] = empty($row_proyecto['sector']) ? '-' : htmlspecialchars($row_proyecto['sector']);
$values[] = empty($row_proyecto['caserio']) ? '-' : htmlspecialchars($row_proyecto['caserio']);
$values[] = empty($row_proyecto['distrito']) ? '-' : htmlspecialchars($row_proyecto['distrito']);
$values[] = empty($row_proyecto['provincia']) ? '-' : htmlspecialchars($row_proyecto['provincia']);
$values[] = empty($row_proyecto['departamento']) ? '-' : htmlspecialchars($row_proyecto['departamento']);

// Unir todos los valores con un espacio
$concatenated = implode(' ', $values);

// Mostrar el resultado
echo '<span class="info-box-number mb-0" style="word-wrap: break-word; white-space: normal; display: inline-block; max-width: 100%;">' . $concatenated . '</span>';

echo '</div>';
echo '</div>';
echo '</div>';

//.2       
//3

echo '<div class="col-12 col-sm-4">'; 
echo '<div class="info-box bg-light" style="background-color: #12377B; color: white; display: flex; flex-direction: column; justify-content: flex-start; height: 100%;">';
echo '<div class="info-box-content" style="text-align: center;">';

// Mostrar número de beneficiados
echo '<span class="info-box-text">Número de beneficiados</span>';
$infantes = isset($row_proyecto['infantes']) && !empty($row_proyecto['infantes']) ? (int)$row_proyecto['infantes'] : 0;
$ninos = isset($row_proyecto['ninos']) && !empty($row_proyecto['ninos']) ? (int)$row_proyecto['ninos'] : 0;
$adolescentes = isset($row_proyecto['adolescentes']) && !empty($row_proyecto['adolescentes']) ? (int)$row_proyecto['adolescentes'] : 0;
$jovenes = isset($row_proyecto['jovenes']) && !empty($row_proyecto['jovenes']) ? (int)$row_proyecto['jovenes'] : 0;
$adultos = isset($row_proyecto['adultos']) && !empty($row_proyecto['adultos']) ? (int)$row_proyecto['adultos'] : 0;
$adultos_mayores = isset($row_proyecto['adultos_mayores']) && !empty($row_proyecto['adultos_mayores']) ? (int)$row_proyecto['adultos_mayores'] : 0;
$total = $infantes + $ninos + $adolescentes + $jovenes + $adultos + $adultos_mayores;
echo '<span class="info-box-number mb-0">' . $total . '</span>';

// Mostrar Nivel
echo '<span class="info-box-text">Nivel</span>';
$disciplinares = [
    '1' => 'Disciplinar',
    '2' => 'Interdisciplinar',
    '3' => 'Interfacultativo',
];

if (isset($row_proyecto['disciplinar']) && isset($disciplinares[$row_proyecto['disciplinar']])) {
    echo '<span class="info-box-number mb-0">' . htmlspecialchars($disciplinares[$row_proyecto['disciplinar']]) . '</span>';
} else {
    echo '<span class="info-box-number mb-0" style="color: darkred;">Vacío</span>';
}

echo '</div>'; 
echo '</div>'; 


echo '</div>';


//Más contenido de presentación de Proyecto
echo '      <!-- Grupo(s) de Interés a los que está orientado el proyecto -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Grupo(s) de Interés a los que está orientado el proyecto</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote12" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['p5']) && !empty($row_proyecto['p5']) ? $row_proyecto['p5'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Necesidades y/o problemas de los Grupo(s) de Interés -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Necesidades y/o problemas de los Grupo(s) de Interés</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote13" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['p6']) && !empty($row_proyecto['p6']) ? $row_proyecto['p6'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Instituciones participantes -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Instituciones participantes</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote14" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['p7_1']) && !empty($row_proyecto['p7_1']) ? $row_proyecto['p7_1'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Poblaciones participantes -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Poblaciones participantes</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote15" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['p7_2']) && !empty($row_proyecto['p7_2']) ? $row_proyecto['p7_2'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Coordinador de componentes del proyecto -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Coordinador de componentes del proyecto</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote16" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['componentes']) && !empty($row_proyecto['componentes']) ? $row_proyecto['componentes'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Integrantes del equipo de docentes -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Integrantes del equipo de docentes</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote17" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['integrantes_docentes']) && !empty($row_proyecto['integrantes_docentes']) ? $row_proyecto['integrantes_docentes'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

echo '      <!-- Representantes o delegados del equipo de estudiantes -->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <br><br><img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Representantes o delegados del equipo de estudiantes</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote18" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['delegados_estudiantes']) && !empty($row_proyecto['delegados_estudiantes']) ? $row_proyecto['delegados_estudiantes'] : '<b style="color: red">Sin información Registrada</b>') . '</textarea>';
echo '         </p>';
echo '      </div>';

//.Más contenido de presentación de Proyecto

//.3
// plan
echo '<div class="row" style="max-width: 100%; overflow-x: hidden;">'; // Asegura que el ancho de la fila no se desborde
echo '   <div class="col-12" style="max-width: 100%; overflow-x: hidden;">'; // Asegura que el ancho de la columna no se desborde
echo '      <br>';
echo '      <div class="card card-primary" style="max-width: 100%;">'; // Asegura que el card no se desborde
echo '         <div class="card-header" style="max-width: 100%;">';
echo '            <h4>';
echo '               <i class="bi bi-diagram-3"></i> Plan de Proyecto';
echo '            </h4>';
echo '         </div>';
echo '      </div>';
echo '      <!-- Diagnóstico-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/diagnostico.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Diagnóstico</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote" rows="12" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['diagnostico']) && !empty($row_proyecto['diagnostico']) ? $row_proyecto['diagnostico'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Justificación-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/justificacion.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Justificación</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote2" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['justificacion']) && !empty($row_proyecto['justificacion']) ? $row_proyecto['justificacion'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Objetivo General-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/general.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Objetivo General</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote3" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['general']) && !empty($row_proyecto['general']) ? $row_proyecto['general'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Objetivos Específicos-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/especificos.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Objetivos Específicos</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote4" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['especificos']) && !empty($row_proyecto['especificos']) ? $row_proyecto['especificos'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Metas por semestre-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/metas.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Metas por semestre</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote5" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['metas']) && !empty($row_proyecto['metas']) ? $row_proyecto['metas'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Cronograma Actual-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/cronograma1.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Cronograma Actual</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote6" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['cronograma1']) && !empty($row_proyecto['cronograma1']) ? $row_proyecto['cronograma1'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Próximos Cronogramas-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/cronograma2.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Próximos Cronogramas</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote7" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['cronograma2']) && !empty($row_proyecto['cronograma2']) ? $row_proyecto['cronograma2'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Metodología-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/metodologia.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Metodología</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote8" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['metodologia']) && !empty($row_proyecto['metodologia']) ? $row_proyecto['metodologia'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Entregables-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/entregables.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Entregables a los beneficiarios</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote9" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['entregables']) && !empty($row_proyecto['entregables']) ? $row_proyecto['entregables'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Tipo de impacto-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/impacto.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Tipo de impacto</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote10" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['impacto']) && !empty($row_proyecto['impacto']) ? $row_proyecto['impacto'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '      <!-- Matriz de indicadores de impacto-->';
echo '      <div class="post" style="max-width: 100%; overflow-x: hidden;">'; // Añadir control de ancho
echo '         <div class="user-block" style="max-width: 100%;">';
echo '            <img class="img-sm" src="../imagenes/plan_proyecto/matriz.png" alt="user image">';
echo '            <span class="username">';
echo '               <b class="text-primary">Matriz de indicadores de impacto</b>';
echo '            </span>';
echo '         </div>';
echo '         <p>';
echo '            <textarea id="summernote11" rows="5" style="width: 100%; max-width: 600px; box-sizing: border-box;">' . htmlspecialchars(isset($row_proyecto['matriz']) && !empty($row_proyecto['matriz']) ? $row_proyecto['matriz'] : 'Vacío') . '</textarea>';
echo '         </p>';
echo '      </div>';
echo '   </div>';
echo '</div>';
// .plan

            echo '</div>'; 
            echo '</div>'; 
            //gen2
echo '<div class="col-12 col-md-12 col-lg-4 order-1 order-md-2">';
echo '   <h5 class="text-primary" style="text-align: justify; word-wrap: break-word; white-space: normal;">';
echo '      <i class="bi bi-file-earmark"></i> Programa: ' . htmlspecialchars($row_proyecto['p1']);
echo '   </h5>';
echo '   <div class="text-muted" style="text-align: justify;">';
echo '      <p class="text-sm" style="word-wrap: break-word; white-space: normal;">Título:';
echo '         <b class="d-block" style="word-wrap: break-word; white-space: normal;">' . htmlspecialchars($row_proyecto['p2']) . '</b>';
echo '      </p>';
echo '      <p class="text-sm">Coordinador de proyecto:';
echo '         <b class="d-block">' . htmlspecialchars($row_proyecto['coordinador']). '</b>';
echo '      </p>';
echo '      <p class="text-sm">Facultad:';
echo '         <b class="d-block">';
echo '            ';

$facultades = [
    '1' => 'Ciencias Agropecuarias',
    '2' => 'Ciencias Biológicas',
    '3' => 'Ciencias Económicas',
    '4' => 'Ciencias Físicas y Matemáticas',
    '5' => 'Ciencias Sociales',
    '6' => 'Derecho y Ciencias Políticas',
    '7' => 'Educación y Ciencias de la Comunicación',
    '8' => 'Enfermería',
    '9' => 'Estomatología',
    '10' => 'Farmacia y Bioquímica',
    '11' => 'Ingeniería',
    '12' => 'Ingeniería Química',
    '13' => 'Medicina',
];

if (isset($row_proyecto['facultad']) && isset($facultades[$row_proyecto['facultad']])) {
    echo htmlspecialchars($facultades[$row_proyecto['facultad']]);
} else {
    echo '<h5>No hay facultad</h5>';
}

echo '         </b>';
echo '      </p>';
echo '      <p class="text-sm">Programa de estudios';
echo '         <b class="d-block">';

$programas_estudios = [
    '1' => 'Administración',
    '2' => 'Agronomía',
    '3' => 'Antropología',
    '4' => 'Arqueología',
    '5' => 'Arquitectura y Urbanismo',
    '6' => 'Biología Pesquera',
    '7' => 'Ciencias Biológicas',
    '8' => 'Ciencias de la Comunicación',
    '9' => 'Ciencias Políticas y Gobernabilidad',
    '10' => 'Contabilidad y Finanzas',
    '11' => 'Derecho',
    '12' => 'Economía',
    '13' => 'Educación Inicial',
    '14' => 'Educación Primaria',
    '15' => 'Educación Secundaria Mención Ciencias Naturales',
    '16' => 'Educación Secundaria Mención Filosofía, Psicología y Ciencias Sociales',
    '17' => 'Educación Secundaria Mención Historia y Geografía',
    '18' => 'Educación Secundaria Mención Idiomas',
    '19' => 'Educación Secundaria Mención Lengua y Literatura',
    '20' => 'Educación Secundaria Mención Matemáticas',
    '21' => 'Enfermería',
    '22' => 'Estadística',
    '23' => 'Estomatología',
    '24' => 'Farmacia y Bioquímica',
    '25' => 'Física',
    '26' => 'Historia',
    '27' => 'Informática',
    '28' => 'Ingeniería Agrícola',
    '29' => 'Ingeniería Agroindustrial',
    '30' => 'Ingeniería Ambiental',
    '31' => 'Ingeniería Civil',
    '32' => 'Ingeniería de Materiales',
    '33' => 'Ingeniería de Minas',
    '34' => 'Ingeniería de Sistemas',
    '35' => 'Ingeniería Industrial',
    '36' => 'Ingeniería Mecánica',
    '37' => 'Ingeniería Mecatrónica',
    '38' => 'Ingeniería Metalúrgica',
    '39' => 'Ingeniería Química',
    '40' => 'Matemáticas',
    '41' => 'Medicina',
    '42' => 'Microbiología y Parasitología',
    '43' => 'Trabajo Social',
    '44' => 'Turismo',
    '45' => 'Zootecnia',
];

if (isset($row_proyecto['programa_estudios']) && isset($programas_estudios[$row_proyecto['programa_estudios']])) {
    echo htmlspecialchars($programas_estudios[$row_proyecto['programa_estudios']]);
} else {
    echo '<h5>No hay programa de estudios seleccionado</h5>';
}

echo '         </b>';
echo '      </p>';
echo '      <p class="text-sm">Departamento académico';
echo '         <b class="d-block">';

$departamentos_academicos = [
    '1' => 'Agronomía y Zootecnia',
    '2' => 'Ciencias Agroindustriales',
    '3' => 'Ciencias Biológicas',
    '4' => 'Microbiología y Parasitología',
    '5' => 'Pesquería',
    '6' => 'Química Biológica y Fisiología Animal',
    '7' => 'Administración',
    '8' => 'Contabilidad y Finanzas',
    '9' => 'Economía',
    '10' => 'Ciencias Básicas Estomatológicas',
    '11' => 'Estomatología',
    '12' => 'Estadística',
    '13' => 'Física',
    '14' => 'Informática',
    '15' => 'Matemáticas',
    '16' => 'Arqueología y Antropología',
    '17' => 'Ciencias Sociales',
    '18' => 'Ciencias Jurídicas Públicas y Políticas',
    '19' => 'Ciencias Jurídicas Privadas y Sociales',
    '20' => 'Ciencia Política y Gobernabilidad',
    '21' => 'Ciencias de la Educación',
    '22' => 'Ciencias Psicológicas',
    '23' => 'Comunicación Social',
    '24' => 'Filosofía y Arte',
    '25' => 'Historia y Geografía',
    '26' => 'Idiomas y Lingüística',
    '27' => 'Lengua Nacional y Literatura',
    '28' => 'Enfermería de la Mujer, Niño y Adolescente',
    '29' => 'Salud del Adulto',
    '30' => 'Salud Familiar y Comunitaria',
    '31' => 'Farmacotecnia',
    '32' => 'Farmacología',
    '33' => 'Bioquímica',
    '34' => 'Ingeniería Civil, Arquitectura y Urbanismo',
    '35' => 'Ingeniería Industrial',
    '36' => 'Ingeniería de Materiales',
    '37' => 'Mecánica y Energía',
    '38' => 'Ingeniería Metalúrgica',
    '39' => 'Ingeniería de Minas',
    '40' => 'Ingeniería de Sistemas',
    '41' => 'Ingeniería Química',
    '42' => 'Ingeniería Ambiental',
    '43' => 'Química',
    '44' => 'Ciencias Básicas Médicas',
    '45' => 'Cirugía',
    '46' => 'Fisiología Humana',
    '47' => 'Ginecología-Obstetricia',
    '48' => 'Medicina',
    '49' => 'Medicina Preventiva y Salud Pública',
    '50' => 'Morfología Humana',
    '51' => 'Pediatría',
    '52' => 'Ingeniería Mecatrónica',
];

if (isset($row_proyecto['departamento_academico']) && isset($departamentos_academicos[$row_proyecto['departamento_academico']])) {
    echo htmlspecialchars($departamentos_academicos[$row_proyecto['departamento_academico']]);
} else {
    echo '<h5>No hay departamento académico seleccionado</h5>';
}

echo '</b></p>';


// Supongamos que $row_proyecto['p3'] contiene un valor como "1,2,3" o "1,3,6,17"
$ods_seleccionados = explode(',', $row_proyecto['p3']);  // Divide los números separados por comas en un arreglo.

// El arreglo de ODS
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

// Empezamos a crear el contenido que se mostrará
echo '<p class="text-sm">ODS del proyecto:<b class="d-block" style="display: block; font-weight: bold;">';

// Añadimos un contenedor para los labels
echo '<div style="display: flex; flex-direction: column; gap: 5px; overflow: hidden; max-width: 100%; box-sizing: border-box;">';

// Iteramos sobre los números que tenemos en el arreglo $ods_seleccionados
foreach ($ods_seleccionados as $numero) {
    if (isset($ods[$numero])) {  // Verificamos que el ODS correspondiente exista en el arreglo
        echo '<label class="btn btn-primary" style="display: block; padding: 5px 10px; font-size: 12px; cursor: pointer; word-wrap: break-word; text-align: center; line-height: 1.4;">' . $ods[$numero] . '</label>';
    }
}

echo '</div>';  // Cerramos el contenedor de los labels
echo '</b></p>';


// Supongamos que $row_proyecto['p3'] contiene un valor como "1,2,3" o "1,3,6,17"
$tipos_seleccionados = explode(',', $row_proyecto['p3']);  // Divide los números separados por comas en un arreglo.

// El arreglo de tipos de proyecto
$tipos_proyecto = [
    '1' => 'Programas de formación continua y formación de capacidades',
    '2' => 'Consultoría/asesoría',
    '3' => 'Gestión cultural',
    '4' => 'Desarrollo económico y social',
    '5' => 'Desarrollo humano y democracia',
    '6' => 'Desarrollo técnico científico sostenible',
    '7' => 'Protección del medio ambiente',
    '8' => 'Innovación',
    '9' => 'Creatividad',
    '10' => 'Otras áreas de acuerdo a las necesidades de la comunidad',
    '11' => 'Salud'
];

// Empezamos a crear el contenido que se mostrará
echo '<p class="text-sm">Tipos de Proyecto:<b class="d-block">';

// Añadimos un contenedor para los labels
echo '<div style="display: flex; flex-direction: column; gap: 5px; overflow: hidden; max-width: 100%; box-sizing: border-box;">';

// Iteramos sobre los números que tenemos en el arreglo $tipos_seleccionados
foreach ($tipos_seleccionados as $numero) {
    if (isset($tipos_proyecto[$numero])) {  // Verificamos que el tipo de proyecto correspondiente exista en el arreglo
        echo '<label class="btn btn-primary" style="display: block; padding: 5px 10px; font-size: 12px; cursor: pointer; word-wrap: break-word; text-align: center; line-height: 1.4;">' . $tipos_proyecto[$numero] . '</label>';
    }
}

echo '</div>';  // Cerramos el contenedor de los labels
echo '</b></p>';

echo '   </div>';


//Anexos

echo '<!-- Anexos-->';
echo '<BR>';
echo '<div class="card card-primary">';
echo '<div class="card-header">';
echo '<h4>';
echo '<i class="far fa-file-pdf"></i> Anexos del Proyecto';
echo '</h4>';
echo '</div>';
echo '</div>';
include ('invocar_archivos.php');
echo '<!-- .Anexos--> ';


//.Anexos

echo '</div>';
//.gen2




            echo '</div>'; //row
            
            echo '</div>'; // Cierre de card-body
            
            echo '</div>'; // Cierre de card card-primary
            
            echo '</div>'; // Cierre de card
        } else {
            echo 'No se encontraron detalles para este proyecto.';
        }
        mysqli_stmt_close($stmt_proyecto);
    }
} else {
    echo 'ID de proyecto no válido.';
}
echo '<script> window.addEventListener("load", function() {window.print();});</script>';

mysqli_close($conexion);
?>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote2').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote3').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote4').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote5').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote6').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote7').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote8').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote9').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote10').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote11').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote12').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote13').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote14').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote15').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote16').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote17').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote18').summernote({
            lang: 'es-ES'
        });
    });
</script>
<script>
    $(function () {
        // Inicialización de editor de texto Summernote con el idioma en español
        $('#summernote19').summernote({
            lang: 'es-ES'
        });
    });
</script>
