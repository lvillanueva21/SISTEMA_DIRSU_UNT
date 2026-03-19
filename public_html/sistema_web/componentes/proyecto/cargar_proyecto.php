<?php
// Verificar que el usuario esté autenticado y que la sesión esté activa
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Primero debes iniciar sesión.');</script>";
    echo "<script>location.assign('https://rsu.unitru.edu.pe/sistema_web/login.php');</script>";
    exit();
}

// Obtener el id del proyecto desde la sesión
$id_proyecto = $_SESSION['id_py'];

// Consulta para obtener los datos del proyecto actual
$sql = "SELECT estado, p1, p2, p3, p4, p5, p6, p7_1, p7_2, infantes, ninos, adolescentes, jovenes, adultos, adultos_mayores, sector, caserio, distrito, provincia, departamento, fecha_inicio, fecha_fin, planificacion, ejecucion, monitoreo, p10_1s, p10_2s, p10_3s, p10_1h, p10_2h, p10_3h, disciplinar, facultad, programa_estudios, departamento_academico, coordinador, componentes, integrantes_docentes, delegados_estudiantes, diagnostico, justificacion, general, especificos, metas, cronograma1, cronograma2, metodologia, entregables, impacto, matriz, pre_dis, pre_nodis, ser_dis, ser_nodis, resumen, monto_uni, monto_auto, monto_otro FROM proyectos WHERE id = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontraron datos
if ($result->num_rows > 0) {
    $proyecto = $result->fetch_assoc();
    $estado = $proyecto['estado'];
    $p1 = $proyecto['p1'];
    $p2 = $proyecto['p2'];
    $p3 = explode(",", $proyecto['p3']); // Convertir la cadena a un array
    $p4 = explode(",", $proyecto['p4']); // Convertir la cadena a un array
    $p5 = $proyecto['p5'];
    $p6 = $proyecto['p6'];
    $p7_1 = $proyecto['p7_1'];
    $p7_2 = $proyecto['p7_2'];
    
    $infantes = $proyecto['infantes'];
    $ninos = $proyecto['ninos'];
    $adolescentes = $proyecto['adolescentes'];
    $jovenes = $proyecto['jovenes'];
    $adultos = $proyecto['adultos'];
    $adultos_mayores = $proyecto['adultos_mayores'];
    
    $sector = $proyecto['sector'];
    $caserio = $proyecto['caserio'];
    $distrito = $proyecto['distrito'];
    $provincia = $proyecto['provincia'];
    $departamento = $proyecto['departamento'];
    
    $fecha_inicio = $proyecto['fecha_inicio'];
    $fecha_fin = $proyecto['fecha_fin'];
    
    $planificacion = $proyecto['planificacion'];
    $ejecucion = $proyecto['ejecucion'];
    $monitoreo = $proyecto['monitoreo'];
    $p10_1s = $proyecto['p10_1s'];
    $p10_2s = $proyecto['p10_2s'];
    $p10_3s = $proyecto['p10_3s'];
    $p10_1h = $proyecto['p10_1h'];
    $p10_2h = $proyecto['p10_2h'];
    $p10_3h = $proyecto['p10_3h'];
    
    $disciplinar = $proyecto['disciplinar'];
    $facultad = $proyecto['facultad'];
    $programa_estudios = $proyecto['programa_estudios'];
    $departamento_academico = $proyecto['departamento_academico'];
    
    $coordinador = $proyecto['coordinador'];
    $componentes = $proyecto['componentes'];
    $integrantes_docentes = $proyecto['integrantes_docentes'];
    $delegados_estudiantes = $proyecto['delegados_estudiantes'];
    
    $diagnostico = $proyecto['diagnostico'];
    
    $justificacion = $proyecto['justificacion'];
    $general = $proyecto['general'];
    $especificos = $proyecto['especificos'];
    $metas = $proyecto['metas'];
    
    $cronograma1 = $proyecto['cronograma1'];
    $cronograma2 = $proyecto['cronograma2'];
    
    $metodologia = $proyecto['metodologia'];
    
    $entregables = $proyecto['entregables'];
    $impacto = $proyecto['impacto'];
    
    $matriz = $proyecto['matriz'];
    
    $pre_dis = $proyecto['pre_dis'];
    $pre_nodis = $proyecto['pre_nodis'];
    
    $ser_dis = $proyecto['ser_dis'];
    $ser_nodis = $proyecto['ser_nodis'];
    
    $resumen = $proyecto['resumen'];
    $monto_uni = $proyecto['monto_uni'];
    $monto_auto = $proyecto['monto_auto'];
    $monto_otro = $proyecto['monto_otro'];
    
} else {
    $estado = "";
    $p1 = "";
    $p2 = "";
    $p3 = [];
    $p4 = [];
    $p5 = "";
    $p6 = "";
    $p7_1 = "";
    $p7_2 = "";
    
    $infantes = "";
    $ninos = "";
    $adolescentes = "";
    $jovenes = "";
    $adultos = "";
    $adultos_mayores = "";
    
    $sector = "";
    $caserio = "";
    $distrito = "";
    $provincia = "";
    $departamento = "";
    
    $fecha_inicio = "";
    $fecha_fin = "";
    
    $planificacion = "";
    $ejecucion = "";
    $monitoreo = "";
    $p10_1s = "";
    $p10_2s = "";
    $p10_3s = "";
    $p10_1h = "";
    $p10_2h = "";
    $p10_3h = "";
    
    $disciplinar = "";
    $facultad = "";
    $programa_estudios = "";
    $departamento_academico = "";
    
    $coordinador = "";
    $componentes = "";
    $integrantes_docentes = "";
    $delegados_estudiantes = "";
    
    $diagnostico = "";
    
    $justificacion = "";
    $general = "";
    $especificos = "";
    $metas = "";
    
    $cronograma1 = "";
    $cronograma2 = "";
    
    $metodologia = "";
    
    $entregables = "";
    $impacto = "";
    
    $matriz = ""; 
    
    $pre_dis = "";
    $pre_nodis = "";
    
    $ser_dis = "";
    $ser_nodis = "";
    
    $resumen = "";
    $monto_uni = "";
    $monto_auto = "";
    $monto_otro = "";
    
}

// Cerrar la conexión
$stmt->close();
?>
