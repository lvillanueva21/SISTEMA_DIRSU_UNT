<?php  
// Iniciar la sesión
session_start();

// Incluir el archivo de conexión a la base de datos
include('../db.php');

// Validar que la sesión esté activa y que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Debe iniciar sesión para continuar.');</script>";
    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
    exit();
}

// Obtener el usuario de la sesión
$usuario = $_SESSION['usuario'];
// ID del período actual (modificable fácilmente)
$id_periodo_actual = 6; // Por defecto: 2025-I

// Establecer el valor del "candado" (0 o 1)
// 0 = No ejecutar el query en proyectos_finales
// 1 = Ejecutar el query en proyectos_finales
$ejecutar_proyectos_finales = 1; // Cambia esto a 0 si no deseas ejecutar la consulta

// Verificar si el botón "Crear nuevo proyecto" ha sido presionado
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crear_proyecto'])) {
    // Iniciar la transacción
    $conexion->begin_transaction();
    
    try {
        // 1. Insertar un nuevo registro vacío en la tabla proyectos
        $sql_insert = "INSERT INTO proyectos (id) VALUES (NULL)";
        if (!$conexion->query($sql_insert)) {
            throw new Exception("Error al insertar el nuevo proyecto: " . $conexion->error);
        }

        // Obtener el ID del nuevo registro
        $nuevo_id_proyecto = $conexion->insert_id;
        
        // 1. Asignar el id de la tabla proyectos a la variable de sesión
        $_SESSION['id_py'] = $nuevo_id_proyecto;

        // 2. Actualizar el id_py del usuario actualmente en sesión con el id de la tabla proyectos
        $sql_update_usuario = "UPDATE usuarios SET id_py = ? WHERE usuario = ?";
        $stmt = $conexion->prepare($sql_update_usuario);
        if (!$stmt) {
            throw new Exception("Error al preparar la consulta: " . $conexion->error);
        }
        $stmt->bind_param('is', $nuevo_id_proyecto, $usuario);
        if (!$stmt->execute()) {
            throw new Exception("Error al actualizar el id_py del usuario: " . $stmt->error);
        }
        // Obtener el ID del usuario
$sql_usuario_id = "SELECT id FROM usuarios WHERE usuario = ?";
$stmt_usuario_id = $conexion->prepare($sql_usuario_id);
$stmt_usuario_id->bind_param("s", $usuario);
$stmt_usuario_id->execute();
$stmt_usuario_id->bind_result($id_usuario);
$stmt_usuario_id->fetch();
$stmt_usuario_id->close();

// Relacionar con usuarios_proyectos
$sql_up = "INSERT INTO usuarios_proyectos (id_usuario, id_proyecto) VALUES (?, ?)";
$stmt_up = $conexion->prepare($sql_up);
$stmt_up->bind_param("ii", $id_usuario, $nuevo_id_proyecto);
if (!$stmt_up->execute()) {
    throw new Exception("Error al insertar en usuarios_proyectos: " . $stmt_up->error);
}

// Relacionar con proyectos_periodo
$sql_pp = "INSERT INTO proyectos_periodo (id_py, id_periodo) VALUES (?, ?)";
$stmt_pp = $conexion->prepare($sql_pp);
$stmt_pp->bind_param("ii", $nuevo_id_proyecto, $id_periodo_actual);
if (!$stmt_pp->execute()) {
    throw new Exception("Error al insertar en proyectos_periodo: " . $stmt_pp->error);
}


        // 3. Insertar en historial_proyectos
        date_default_timezone_set('America/Lima');
        $fecha_actual = date('Y-m-d H:i:s');
        // Obtener período del nuevo proyecto
$periodo_nuevo = "Desconocido";
$stmt_periodo = $conexion->prepare("
    SELECT per.nombre
    FROM proyectos_periodo pp
    JOIN periodos per ON per.id = pp.id_periodo
    WHERE pp.id_py = ?
    LIMIT 1
");
$stmt_periodo->bind_param("i", $nuevo_id_proyecto);
$stmt_periodo->execute();
$stmt_periodo->bind_result($periodo_nuevo);
$stmt_periodo->fetch();
$stmt_periodo->close();

$descripcion = "Se creó el proyecto con ID: $nuevo_id_proyecto para el período: $periodo_nuevo.";


        $sql_historial = "INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, ?, ?)";
        $stmt_historial = $conexion->prepare($sql_historial);
        if (!$stmt_historial) {
            throw new Exception("Error al preparar la consulta de historial: " . $conexion->error);
        }
        $stmt_historial->bind_param("ssi", $descripcion, $fecha_actual, $nuevo_id_proyecto);
        if (!$stmt_historial->execute()) {
            throw new Exception("Error al insertar en historial_proyectos: " . $stmt_historial->error);
        }

        // 4. Insertar en progresos_f1
        $sql_progresos = "INSERT INTO progresos_f1 (id_py, com_fac, dir_depa, dec_fac, dir_rsu, id_estado) VALUES (?, 0, 0, 0, 0, 0)";
        $stmt_progresos = $conexion->prepare($sql_progresos);
        if (!$stmt_progresos) {
            throw new Exception("Error al preparar la consulta de progresos_f1: " . $conexion->error);
        }
        $stmt_progresos->bind_param('i', $nuevo_id_proyecto);
        if (!$stmt_progresos->execute()) {
            throw new Exception("Error al insertar en progresos_f1: " . $stmt_progresos->error);
        }

        // 5. Condicional para ejecutar la consulta en proyectos_finales según el valor de $ejecutar_proyectos_finales
        if ($ejecutar_proyectos_finales == 1) {
            $sql_proyectos_finales = "
                INSERT INTO proyectos_finales (
                    id_py, estado, cot_cf, rub_cf, vb_df, vb_dd, cot_dr, rub_dr,
                    titulo, programa, ods, coordinador, integrantes, estudiantes, omi,
                    lugar, beneficiados, fecha_inicio, fecha_fin, resumen, actividades,
                    resultados, matriz, comentarios, conclusiones, analisis, recomendaciones,
                    fuentes, anexos, carga, obs_cotejo_cf, obs_rubrica_cf, obs_cotejo_dr,
                    obs_rubrica_dr
                )
                SELECT 
                    id,                     -- id_py es el valor de la columna id de la tabla proyectos
                    0,                      -- estado = 0
                    0,                      -- cot_cf = 0
                    0,                      -- rub_cf = 0
                    0,                      -- vb_df = 0
                    0,                      -- vb_dd = 0
                    0,                      -- cot_dr = 0
                    0,                      -- rub_dr = 0
                    NULL,                   -- titulo = NULL
                    NULL,                   -- programa = NULL
                    NULL,                   -- ods = NULL
                    NULL,                   -- coordinador = NULL
                    NULL,                   -- integrantes = NULL
                    NULL,                   -- estudiantes = NULL
                    NULL,                   -- omi = NULL
                    NULL,                   -- lugar = NULL
                    NULL,                   -- beneficiados = NULL
                    NULL,                   -- fecha_inicio = NULL
                    NULL,                   -- fecha_fin = NULL
                    NULL,                   -- resumen = NULL
                    NULL,                   -- actividades = NULL
                    NULL,                   -- resultados = NULL
                    NULL,                   -- matriz = NULL
                    NULL,                   -- comentarios = NULL
                    NULL,                   -- conclusiones = NULL
                    NULL,                   -- analisis = NULL
                    NULL,                   -- recomendaciones = NULL
                    NULL,                   -- fuentes = NULL
                    NULL,                   -- anexos = NULL
                    NULL,                   -- carga = NULL
                    NULL,                   -- obs_cotejo_cf = NULL
                    NULL,                   -- obs_rubrica_cf = NULL
                    NULL,                   -- obs_cotejo_dr = NULL
                    NULL                    -- obs_rubrica_dr = NULL
                FROM proyectos p
                WHERE p.id = ?";
            
            $stmt_proyectos_finales = $conexion->prepare($sql_proyectos_finales);
            if (!$stmt_proyectos_finales) {
                throw new Exception("Error al preparar la consulta para proyectos_finales: " . $conexion->error);
            }
            $stmt_proyectos_finales->bind_param('i', $nuevo_id_proyecto);
            if (!$stmt_proyectos_finales->execute()) {
                throw new Exception("Error al insertar en proyectos_finales: " . $stmt_proyectos_finales->error);
            }
        }

        // Confirmar la transacción
        $conexion->commit();

        echo "<script>alert('Nuevo proyecto creado y asignado exitosamente.');</script>";
        echo "<script>location.assign('inicio.php');</script>";
    } catch (Exception $e) {
        // Revertir la transacción en caso de error
        $conexion->rollback();
        echo "<script>alert('Error: " . $e->getMessage() . "');</script>";
        echo "<script>location.assign('../../vistas/datos_principales.php');</script>";
    }

    // Cerrar las declaraciones y la conexión
    if (isset($stmt)) $stmt->close();
    if (isset($stmt_historial)) $stmt_historial->close();
    if (isset($stmt_progresos)) $stmt_progresos->close();
    if (isset($stmt_proyectos_finales)) $stmt_proyectos_finales->close();
    if (isset($stmt_up)) $stmt_up->close(); // 👈 nueva línea
    if (isset($stmt_pp)) $stmt_pp->close(); // 👈 nueva línea
    $conexion->close();    
}
?>
