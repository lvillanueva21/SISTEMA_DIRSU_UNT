<?php
/*-------------------------------------------------------------------------
 |  LÓGICA AJAX para cronogramas
 |  Ruta : direccion_rsu/funciones/logica_cronogramas.php
 *------------------------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

require_once '../../componentes/db.php';
$accion = $_POST['action'] ?? '';

try {
    switch ($accion) {

        /*===== LISTAR PERIODOS ============================================*/
        case 'periodos':
            $sql = "SELECT id, nombre FROM periodos ORDER BY nombre";
            $res = mysqli_query($conexion, $sql);
            echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
            break;

/*===== LISTAR CRONOGRAMAS =========================================*/
case 'list':
    $filtro = $_POST['filtro'] ?? 'todos';
    $params = [];

    $sql = "SELECT c.id,
                   c.id_periodo,           -- <- añadimos el id del período
                   p.nombre AS periodo,
                   DATE_FORMAT(c.apertura,'%Y-%m-%d %H:%i') AS apertura,
                   DATE_FORMAT(c.cierre  ,'%Y-%m-%d %H:%i') AS cierre,
                   c.activo,
                   c.tipo,
                   CASE c.tipo
                       WHEN 1 THEN 'Presentación de Proyecto'
                       WHEN 2 THEN 'Informe Semestral'
                       ELSE 'Otros'
                   END AS tipo_nombre
            FROM sm_cronogramas c
            JOIN periodos p ON p.id = c.id_periodo ";

    if ($filtro !== 'todos') {
        $sql .= "WHERE p.id = ? ";
        $params[] = $filtro;
    }

    $sql .= "ORDER BY c.fecha_creacion DESC LIMIT 5";

    $stmt = mysqli_prepare($conexion, $sql);
    if ($params) {
        mysqli_stmt_bind_param($stmt, 'i', $params[0]);
    }
    mysqli_stmt_execute($stmt);
    $res = mysqli_stmt_get_result($stmt);
    echo json_encode(mysqli_fetch_all($res, MYSQLI_ASSOC));
    break;

        /*===== OBTENER INFO DE UN REGISTRO (para modal delete) =============*/
        case 'info':
            $id = intval($_POST['id'] ?? 0);
            $sql = "SELECT c.id, p.nombre AS periodo,
                           DATE_FORMAT(c.apertura,'%Y-%m-%d %H:%i') AS apertura,
                           DATE_FORMAT(c.cierre  ,'%Y-%m-%d %H:%i') AS cierre,
                           c.activo
                    FROM sm_cronogramas c
                    JOIN periodos p ON p.id = c.id_periodo
                    WHERE c.id = ?";
            $stmt = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($stmt, 'i', $id);
            mysqli_stmt_execute($stmt);
            $res = mysqli_stmt_get_result($stmt);
            echo json_encode(mysqli_fetch_assoc($res));
            break;

        /*===== CREAR =======================================================*/
        case 'create':
            $id_periodo = intval($_POST['id_periodo'] ?? 0);
            $tipo       = intval($_POST['tipo'] ?? 0);
            $apertura   = $_POST['apertura'] ?? '';
            $cierre     = $_POST['cierre'] ?? '';
            $activo     = isset($_POST['activo']) ? 1 : 0;

            if (!$id_periodo || !$tipo || !$apertura || !$cierre) {
                throw new Exception('❌ Faltan datos requeridos.');
            }

            if (strtotime($apertura) >= strtotime($cierre)) {
                throw new Exception('❌ La fecha de apertura debe ser menor que la de cierre.');
            }

            if ($activo) {
                $sql = "UPDATE sm_cronogramas SET activo=0 WHERE id_periodo=? AND tipo=?";
                $st = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($st, 'ii', $id_periodo, $tipo);
                mysqli_stmt_execute($st);
            }

            $sql = "INSERT INTO sm_cronogramas (id_periodo, tipo, apertura, cierre, activo)
                    VALUES (?, ?, ?, ?, ?)";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'iissi', $id_periodo, $tipo, $apertura, $cierre, $activo);
            mysqli_stmt_execute($st);

            $id_new = mysqli_insert_id($conexion);
            $sql2 = "SELECT nombre AS periodo FROM periodos WHERE id=?";
            $st2 = mysqli_prepare($conexion, $sql2);
            mysqli_stmt_bind_param($st2, 'i', $id_periodo);
            mysqli_stmt_execute($st2);
            $res2 = mysqli_stmt_get_result($st2);
            $rowP = mysqli_fetch_assoc($res2);

            $tipo_nombre = match ($tipo) {
    1 => 'Presentación de Proyecto',
    2 => 'Informe Semestral',
    default => 'Otros'
};

echo json_encode([
    'success' => true,
    'data' => [
        'id'          => $id_new,
        'periodo'     => $rowP['periodo'],
        'tipo_nombre' => $tipo_nombre,
        'apertura'    => $apertura,
        'cierre'      => $cierre,
        'activo'      => $activo
    ]
]);

            break;

        /*===== ACTUALIZAR ==================================================*/
        case 'update':
            $id         = intval($_POST['id'] ?? 0);
            $id_periodo = intval($_POST['id_periodo'] ?? 0);
            $tipo       = intval($_POST['tipo'] ?? 0);
            $apertura   = $_POST['apertura'] ?? '';
            $cierre     = $_POST['cierre'] ?? '';
            $activo     = intval($_POST['activo'] ?? 0);

            if (!$id || !$id_periodo || !$tipo || !$apertura || !$cierre) {
    throw new Exception('❌ Datos incompletos');
}

            if (strtotime($apertura) >= strtotime($cierre)) {
                throw new Exception('❌ Apertura debe ser menor que cierre');
            }

            mysqli_begin_transaction($conexion);

            if ($activo) {
                $sql = "UPDATE sm_cronogramas SET activo=0 WHERE id_periodo=? AND tipo=?";
                $st = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($st, 'ii', $id_periodo, $tipo);
                mysqli_stmt_execute($st);
            }

            $sql = "UPDATE sm_cronogramas
                    SET id_periodo=?, tipo=?, apertura=?, cierre=?, activo=?
                    WHERE id=?";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'iissii', $id_periodo, $tipo, $apertura, $cierre, $activo, $id);
            mysqli_stmt_execute($st);

            mysqli_commit($conexion);
            echo json_encode(['success' => true]);
            break;

        /*===== ELIMINAR ====================================================*/
        case 'delete':
            $id = intval($_POST['id'] ?? 0);
            if (!$id) throw new Exception('ID inválido');
            $sql = "DELETE FROM sm_cronogramas WHERE id=?";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'i', $id);
            mysqli_stmt_execute($st);
            echo json_encode(['success' => true]);
            break;

        /*===== ACCIÓN DESCONOCIDA ==========================================*/
        default:
            throw new Exception('Acción no válida');
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
