<?php
// Incluir la conexión a la base de datos
include('conexion.php');

// Verificar si el usuario está autenticado
session_start();
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['success' => false, 'message' => 'Debes iniciar sesión.']);
    exit();
}

// Verificar que se haya enviado un id
if (isset($_POST['id'])) {
    $id_proyecto = $_POST['id'];

    // Consulta para obtener los datos del proyecto con el id recibido
    $sql = "SELECT p1, p2, p3, p4, p5, p6, p7_1, p7_2, infantes, ninos, adolescentes, jovenes, adultos, adultos_mayores, sector, caserio, distrito, provincia, departamento, fecha_inicio, fecha_fin, planificacion, ejecucion, monitoreo, p10_1s, p10_2s, p10_3s, p10_1h, p10_2h, p10_3h, disciplinar, facultad, programa_estudios, departamento_academico, coordinador, componentes, integrantes_docentes, delegados_estudiantes 
            FROM proyectos WHERE id = ?";
    
    $stmt = $conexion->prepare($sql);
    $stmt->bind_param("i", $id_proyecto);
    $stmt->execute();
    $result = $stmt->get_result();

    // Verificar si se encontró el proyecto
    if ($result->num_rows > 0) {
        $proyecto = $result->fetch_assoc();

        // Devolver los datos del proyecto como JSON
        echo json_encode(['success' => true, 'proyecto' => $proyecto]);
    } else {
        // Si no se encuentra el proyecto
        echo json_encode(['success' => false, 'message' => 'No se encontró el proyecto']);
    }

    // Cerrar la consulta
    $stmt->close();
} else {
    // Si no se envía un id
    echo json_encode(['success' => false, 'message' => 'ID de proyecto no proporcionado']);
}
?>
