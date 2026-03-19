<?php 

// Recuperar filtros desde GET
$facultad_filtro = isset($_GET['facultad']) ? $_GET['facultad'] : '';
$keyword = isset($_GET['keyword']) ? trim($_GET['keyword']) : '';
$estado_filtro = isset($_GET['estado']) ? $_GET['estado'] : '';
$ods_filtro = isset($_GET['ods']) ? trim($_GET['ods']) : '';

// Arreglos de departamentos y facultades
$departamentos_academicos = [
    '1' => 'Agronomía y Zootecnia', '2' => 'Ciencias Agroindustriales', '3' => 'Ciencias Biológicas',
    '4' => 'Microbiología y Parasitología', '5' => 'Pesquería', '6' => 'Química Biológica y Fisiología Animal',
    '7' => 'Administración', '8' => 'Contabilidad y Finanzas', '9' => 'Economía',
    '10' => 'Ciencias Básicas Estomatológicas', '11' => 'Estomatología', '12' => 'Estadística', '13' => 'Física',
    '14' => 'Informática', '15' => 'Matemáticas', '16' => 'Arqueología y Antropología', '17' => 'Ciencias Sociales',
    '18' => 'Ciencias Jurídicas Públicas y Políticas', '19' => 'Ciencias Jurídicas Privadas y Sociales',
    '20' => 'Ciencia Política y Gobernabilidad', '21' => 'Ciencias de la Educación', '22' => 'Ciencias Psicológicas',
    '23' => 'Comunicación Social', '24' => 'Filosofía y Arte', '25' => 'Historia y Geografía', '26' => 'Idiomas y Lingüística',
    '27' => 'Lengua Nacional y Literatura', '28' => 'Enfermería de la Mujer, Niño y Adolescente', '29' => 'Salud del Adulto',
    '30' => 'Salud Familiar y Comunitaria', '31' => 'Farmacotecnia', '32' => 'Farmacología', '33' => 'Bioquímica',
    '34' => 'Ingeniería Civil, Arquitectura y Urbanismo', '35' => 'Ingeniería Industrial', '36' => 'Ingeniería de Materiales',
    '37' => 'Mecánica y Energía', '38' => 'Ingeniería Metalúrgica', '39' => 'Ingeniería de Minas', '40' => 'Ingeniería de Sistemas',
    '41' => 'Ingeniería Química', '42' => 'Ingeniería Ambiental', '43' => 'Química', '44' => 'Ciencias Básicas Médicas',
    '45' => 'Cirugía', '46' => 'Fisiología Humana', '47' => 'Ginecología-Obstetricia', '48' => 'Medicina',
    '49' => 'Medicina Preventiva y Salud Pública', '50' => 'Morfología Humana', '51' => 'Pediatría', '52' => 'Ingeniería Mecatrónica'
];

$facultades = [
    '1' => 'Ciencias Agropecuarias', '2' => 'Ciencias Biológicas', '3' => 'Ciencias Económicas', '4' => 'Ciencias Físicas y Matemáticas',
    '5' => 'Ciencias Sociales', '6' => 'Derecho y Ciencias Políticas', '7' => 'Educación y Ciencias de la Comunicación', '8' => 'Enfermería',
    '9' => 'Estomatología', '10' => 'Farmacia y Bioquímica', '11' => 'Ingeniería', '12' => 'Ingeniería Química', '13' => 'Medicina'
];

// Construir la consulta de usuarios
// Se realiza un INNER JOIN con departamentos y un LEFT JOIN con proyectos para permitir buscar en el título
$query_usuarios = "
    SELECT u.id, u.usuario, u.nombres, u.apellidos, u.id_depa, u.id_py, p.p3
    FROM usuarios u
    INNER JOIN departamentos d ON u.id_depa = d.id
    LEFT JOIN proyectos p ON u.id_py = p.id
    WHERE u.id_rol = 2
      AND u.id_depa IS NOT NULL
      AND CHAR_LENGTH(u.usuario) = 4
";

// Filtro por facultad
if ($facultad_filtro != '') {
    $query_usuarios .= " AND d.id_facultad = ? ";
}

// Filtro por palabra clave (buscando en nombre completo o título del proyecto)
if ($keyword != '') {
    $query_usuarios .= " AND (LOWER(CONCAT(u.nombres, ' ', u.apellidos)) LIKE CONCAT('%', LOWER(?), '%') OR LOWER(p.p2) LIKE CONCAT('%', LOWER(?), '%')) ";
}

// Filtro por estado del proyecto
if ($estado_filtro != '') {
    $query_usuarios .= " AND p.estado = ? ";
}

// Filtro por ODS en p3
if ($ods_filtro != '') {
    // Convertir la cadena de ODS a un arreglo
    $ods_ids = explode(',', $ods_filtro);
    $ods_ids = array_map('trim', $ods_ids);
    $ods_ids = array_filter($ods_ids, function($val) { return $val !== ''; });
    if (!empty($ods_ids)) {
        $ods_conditions = [];
        foreach ($ods_ids as $value) {
            $ods_conditions[] = "FIND_IN_SET(?, p.p3) > 0";
        }
        $query_usuarios .= " AND (" . implode(" OR ", $ods_conditions) . ") ";
    }
}

// Preparar la consulta
$stmt_usuarios = mysqli_prepare($conexion, $query_usuarios);

// Acumular parámetros y tipos en el orden en que aparecen en la consulta
$param_types = "";
$params = [];

// Filtro por facultad
if ($facultad_filtro != '') {
    $param_types .= "i";
    $params[] = $facultad_filtro;
}

// Filtro por keyword (se agregan dos parámetros)
if ($keyword != '') {
    $param_types .= "ss";
    $params[] = $keyword;
    $params[] = $keyword;
}

// Filtro por estado
if ($estado_filtro != '') {
    $param_types .= "i";
    $params[] = $estado_filtro;
}

// Filtro por ODS: cada uno es un entero
if ($ods_filtro != '' && !empty($ods_ids)) {
    foreach ($ods_ids as $value) {
        $param_types .= "i";
        $params[] = $value;
    }
}

// Enlazar los parámetros si existen
if (!empty($params)) {
    // Preparar el arreglo de referencias para bind_param
    $bind_names = [];
    $bind_names[] = $param_types;
    for ($i = 0; $i < count($params); $i++) {
        $bind_names[] = &$params[$i];
    }
    call_user_func_array(array($stmt_usuarios, 'bind_param'), $bind_names);
}

// Ejecutar la consulta
mysqli_stmt_execute($stmt_usuarios);
$result_usuarios = mysqli_stmt_get_result($stmt_usuarios);

// Comprobar si se encontraron registros
if (mysqli_num_rows($result_usuarios) > 0) {
    // Mostrar la tabla con los resultados
    echo '<div id="tablaProyectos">';
    echo '<table class="table table-striped" id="tablaProyectosTable">';
    echo '<thead><tr><th>ID USUARIO</th><th>CODIGO</th><th>NOMBRE</th><th>PRESENTACIÓN PROYECTO</th><th>PROGRESO</th></tr></thead>';
    echo '<tbody>';
   
    // Inicializar el contador de filas
    $contador_filas = 1;
   
    // Mostrar cada registro
    while ($row = mysqli_fetch_assoc($result_usuarios)) {
        // Obtener el nombre completo concatenando nombres y apellidos
        $nombre_completo = $row['nombres'] . ' ' . $row['apellidos'];
       
        // Obtener el nombre del departamento usando el id_depa
        $depa_nombre = isset($departamentos_academicos[$row['id_depa']]) ? $departamentos_academicos[$row['id_depa']] : 'Desconocido';
       
        // Obtener el id_facultad correspondiente al id_depa desde la tabla departamentos
        $query_facultad = "SELECT id_facultad FROM departamentos WHERE id = ?";
        $stmt_facultad = mysqli_prepare($conexion, $query_facultad);
        mysqli_stmt_bind_param($stmt_facultad, 'i', $row['id_depa']);
        mysqli_stmt_execute($stmt_facultad);
        $result_facultad = mysqli_stmt_get_result($stmt_facultad);
       
        // Obtener el nombre de la facultad
        $facultad_nombre = 'Desconocida';
        if ($row_facultad = mysqli_fetch_assoc($result_facultad)) {
            $id_facultad = $row_facultad['id_facultad'];
            $facultad_nombre = isset($facultades[$id_facultad]) ? $facultades[$id_facultad] : 'Desconocida';
        }
       
        // Obtener los datos del proyecto usando el id_py
        $query_proyecto = "SELECT p2, p1, estado, id FROM proyectos WHERE id = ?";
        $stmt_proyecto = mysqli_prepare($conexion, $query_proyecto);
        mysqli_stmt_bind_param($stmt_proyecto, 'i', $row['id_py']);
        mysqli_stmt_execute($stmt_proyecto);
        $result_proyecto = mysqli_stmt_get_result($stmt_proyecto);
       
        // Inicializar las variables del proyecto
        $proyecto_registrado = false;
        $proyecto_info = '<b style="color: red;">No se ha registrado presentación de proyecto</b>';
        if ($row_proyecto = mysqli_fetch_assoc($result_proyecto)) {
            $proyecto_registrado = true;
            $titulo_proyecto = $row_proyecto['p2'];
            $programa_proyecto = $row_proyecto['p1'];
            $estado_proyecto = $row_proyecto['estado'];
            $id_proyecto = $row_proyecto['id'];
           
            // Generar el texto del proyecto
            $proyecto_info = '<b>Título: </b>' . htmlspecialchars($titulo_proyecto) . '<br>';
            $proyecto_info .= '<b>Programa: </b>' . htmlspecialchars($programa_proyecto) . '<br>';
            $proyecto_info .= '<b>id_py: </b>' . htmlspecialchars($id_proyecto) . '<br>';
           
            // Mostrar estado del proyecto
            if ($estado_proyecto == 1) {
                $proyecto_info .= '<span class="badge badge-warning">Revisión</span>';
            } elseif ($estado_proyecto == 0) {
                $proyecto_info .= '<span class="badge badge-primary">En Espera</span>';
            } elseif ($estado_proyecto == 2) {
                $proyecto_info .= '<span class="badge badge-success">Aprobación Total</span>';
            }
        }
       
        // Incluir un salto de línea y mostrar primero la facultad y luego el departamento
        $nombre_completo .= '<br>';
        $nombre_completo .= ' <label style="background-color: #d1c4e9; padding: 3px; margin-left: 5px;">' . $facultad_nombre . '</label>';
        $nombre_completo .= ' <label style="background-color:rgb(250, 238, 189); padding: 3px; margin-left: 5px;">' . $depa_nombre . '</label>';
       
        // Mostrar la fila con el número de la fila en rojo y negrita al lado del id
        echo '<tr>';
        echo '<td><span style="color: darkred; font-weight: bold;">' . $contador_filas . ' </span>' . htmlspecialchars($row['id']) . '</td>';
        echo '<td>' . htmlspecialchars($row['usuario']) . '</td>';
        echo '<td>' . $nombre_completo . '</td>';
        echo '<td>' . $proyecto_info . '</td>';
       
        // Mostrar la columna PROGRESO con los botones, habilitados o deshabilitados según si se encontró la presentación del proyecto
        echo '<td>';
        echo '<div class="btn-group-vertical">';
        if ($proyecto_registrado) {
            echo '<button class="btn btn-primary btn-sm" style="font-size: 10px;" onclick="verDetalle(' . $id_proyecto . ')"><i class="fas fa-info-circle"></i> Proyecto</button>';
            echo '<button class="btn btn-success btn-sm" style="font-size: 10px;" onclick="verSemestre(' . $id_proyecto . ')"><i class="fas fa-calendar"></i> Semestral</button>';
            echo '<button class="btn btn-warning btn-sm" style="font-size: 10px;" onclick="verEvaluacion(' . $id_proyecto . ')"><i class="fas fa-star"></i> Evaluación</button>';
        } else {
            echo '<button class="btn btn-primary btn-sm" style="font-size: 10px;" disabled><i class="fas fa-info-circle"></i> Proyecto</button>';
            echo '<button class="btn btn-success btn-sm" style="font-size: 10px;" disabled><i class="fas fa-calendar"></i> Semestral</button>';
            echo '<button class="btn btn-warning btn-sm" style="font-size: 10px;" disabled><i class="fas fa-star"></i> Evaluación</button>';
        }
        echo '</div>';
        echo '</td>';
        echo '</tr>';
       
        $contador_filas++;
       
        // Cerrar las sentencias preparadas para facultad y proyecto
        mysqli_stmt_close($stmt_facultad);
        mysqli_stmt_close($stmt_proyecto);
    }
   
    echo '</tbody>';
    echo '</table>';
    echo '</div>';
   
} else {
    echo '<h2 class="text-primary text-center mt-5">
            <i class="fas fa-exclamation-triangle fa-3x"></i><br>
            No se encontraron usuarios que cumplieran con los filtros.
          </h2>';
}

mysqli_stmt_close($stmt_usuarios);
?>
