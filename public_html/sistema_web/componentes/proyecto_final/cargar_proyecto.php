<?php
// Verificar que el usuario esté autenticado y que la sesión esté activa
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Primero debes iniciar sesión.');</script>";
    echo "<script>location.assign('https://rsu.unitru.edu.pe/sistema_web/login.php');</script>";
    exit();
}

// Obtener el id del proyecto desde la sesión
$id_proyecto = $_SESSION['id_py'];

// Consulta para obtener los datos del proyecto final desde la tabla proyectos_finales
$sql = "SELECT estado, titulo, programa, ods, coordinador, integrantes, estudiantes, omi, lugar, beneficiados, fecha_inicio, fecha_fin, resumen, actividades, resultados, matriz, comentarios, conclusiones, analisis, recomendaciones, fuentes, anexos, carga
        FROM proyectos_finales WHERE id_py = ?";
$stmt = $conexion->prepare($sql);
$stmt->bind_param("i", $id_proyecto);
$stmt->execute();
$result = $stmt->get_result();

// Verificar si se encontraron datos
if ($result->num_rows > 0) {
    $proyecto = $result->fetch_assoc();
    
    // Asignación de datos del proyecto
    $estado = $proyecto['estado'];
    $titulo = $proyecto['titulo'];
    $programa = $proyecto['programa'];
    $ods = explode(",", $proyecto['ods']);; // Inicializamos como un array vacío, según tu observación
    $coordinador = $proyecto['coordinador'];
    $integrantes = $proyecto['integrantes'];
    $estudiantes = $proyecto['estudiantes'];
    $omi = $proyecto['omi'];
    $lugar = $proyecto['lugar'];
    $beneficiados = $proyecto['beneficiados'];
    $fecha_inicio = $proyecto['fecha_inicio'];
    $fecha_fin = $proyecto['fecha_fin'];
    $resumen = $proyecto['resumen'];
    $actividades = $proyecto['actividades'];
    $resultados = $proyecto['resultados'];
    $matriz = $proyecto['matriz'];
    $comentarios = $proyecto['comentarios'];
    $conclusiones = $proyecto['conclusiones'];
    $analisis = $proyecto['analisis'];
    $recomendaciones = $proyecto['recomendaciones'];
    $fuentes = $proyecto['fuentes'];
    $anexos = $proyecto['anexos'];
    $carga = $proyecto['carga'];
} else {
    // Si no se encuentra el proyecto, se asignan valores predeterminados vacíos
    $estado = "";
    $titulo = "";
    $programa = "";
    $ods = []; // Mantener como array vacío
    $coordinador = "";
    $integrantes = "";
    $estudiantes = "";
    $omi = "";
    $lugar = "";
    $beneficiados = "";
    $fecha_inicio = "";
    $fecha_fin = "";
    $resumen = "";
    $actividades = "";
    $resultados = "";
    $matriz = "";
    $comentarios = "";
    $conclusiones = "";
    $analisis = "";
    $recomendaciones = "";
    $fuentes = "";
    $anexos = "";
    $carga = "";
}

// Cerrar la conexión
$stmt->close();
?>