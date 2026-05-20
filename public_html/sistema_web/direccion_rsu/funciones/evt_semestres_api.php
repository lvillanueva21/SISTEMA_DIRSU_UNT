<?php
header('Content-Type: application/json; charset=utf-8');
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../../includes/evt_mantenimiento.php';
require_once __DIR__ . '/../../includes/api_dirsu/projects_real_service.php';
require_once __DIR__ . '/../../semestral/logica/funciones.php';

function evt_sem_api_exit($success, $msg, $data = null, $httpCode = 200)
{
    while (ob_get_level() > 0) {
        @ob_end_clean();
    }
    if (!headers_sent()) {
        http_response_code($httpCode);
    }
    $out = array('success' => (bool)$success, 'msg' => (string)$msg);
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out);
    exit;
}

function evt_sem_api_is_valid_dmy($value)
{
    $value = trim((string)$value);
    if ($value === '') {
        return false;
    }
    return (bool)preg_match('/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d{2}$/', $value);
}

function evt_sem_api_classify_project($row, $semCountMap)
{
    $idPy = isset($row['id_py']) ? (int)$row['id_py'] : 0;
    $titulo = isset($row['titulo_proyecto']) ? (string)$row['titulo_proyecto'] : 'Sin titulo';
    $fechaInicio = isset($row['fecha_inicio']) ? trim((string)$row['fecha_inicio']) : '';
    $fechaFin = isset($row['fecha_fin']) ? trim((string)$row['fecha_fin']) : '';
    $cantSemestres = isset($semCountMap[$idPy]) ? (int)$semCountMap[$idPy] : 0;

    if ($cantSemestres > 0) {
        return array(
            'bucket' => 'calculados',
            'payload' => array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'cantidad_semestres' => $cantSemestres
            )
        );
    }

    if (!evt_sem_api_is_valid_dmy($fechaInicio) || !evt_sem_api_is_valid_dmy($fechaFin)) {
        return array(
            'bucket' => 'no_elegibles',
            'payload' => array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'motivo' => 'Sin fechas validas en formato d/m/Y'
            )
        );
    }

    try {
        $fi = parseDMY_orThrow($fechaInicio);
        $ff = parseDMY_orThrow($fechaFin);
    } catch (Throwable $e) {
        return array(
            'bucket' => 'no_elegibles',
            'payload' => array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'motivo' => 'Fechas invalidas'
            )
        );
    }

    if ($fi > $ff) {
        return array(
            'bucket' => 'no_elegibles',
            'payload' => array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'fecha_inicio' => $fechaInicio,
                'fecha_fin' => $fechaFin,
                'motivo' => 'Fecha inicio mayor que fecha fin'
            )
        );
    }

    return array(
        'bucket' => 'pendientes',
        'payload' => array(
            'id_py' => $idPy,
            'titulo' => $titulo,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin
        )
    );
}

function evt_sem_api_fetch_real_projects($conexion)
{
    $sql = "
        SELECT
            p.id AS id_py,
            COALESCE(NULLIF(TRIM(p.p2), ''), 'Sin titulo') AS titulo_proyecto,
            COALESCE(NULLIF(TRIM(p.fecha_inicio), ''), '') AS fecha_inicio,
            COALESCE(NULLIF(TRIM(p.fecha_fin), ''), '') AS fecha_fin
    " . rsu_projects_real_from_sql() . "
        ORDER BY p.id DESC
    ";

    $rows = array();
    $rs = mysqli_query($conexion, $sql);
    if (!($rs instanceof mysqli_result)) {
        return $rows;
    }

    while ($row = mysqli_fetch_assoc($rs)) {
        $rows[] = $row;
    }
    mysqli_free_result($rs);
    return $rows;
}

function evt_sem_api_fetch_semesters_count_map($conexion)
{
    $map = array();
    $sql = "
        SELECT
            s.id_py,
            COUNT(*) AS total_semestres
        FROM sm_proyecto_semestres s
        WHERE s.vigente = 1
          AND s.tipo = 'semestral'
        GROUP BY s.id_py
    ";
    $rs = mysqli_query($conexion, $sql);
    if (!($rs instanceof mysqli_result)) {
        return $map;
    }
    while ($row = mysqli_fetch_assoc($rs)) {
        $idPy = isset($row['id_py']) ? (int)$row['id_py'] : 0;
        if ($idPy <= 0) {
            continue;
        }
        $map[$idPy] = isset($row['total_semestres']) ? (int)$row['total_semestres'] : 0;
    }
    mysqli_free_result($rs);
    return $map;
}

function evt_sem_api_build_status($conexion)
{
    $projects = evt_sem_api_fetch_real_projects($conexion);
    $semCountMap = evt_sem_api_fetch_semesters_count_map($conexion);

    $calculados = array();
    $pendientes = array();
    $noElegibles = array();

    foreach ($projects as $row) {
        $classified = evt_sem_api_classify_project($row, $semCountMap);
        $bucket = isset($classified['bucket']) ? $classified['bucket'] : '';
        $payload = isset($classified['payload']) ? $classified['payload'] : array();

        if ($bucket === 'calculados') {
            $calculados[] = $payload;
            continue;
        }
        if ($bucket === 'pendientes') {
            $pendientes[] = $payload;
            continue;
        }
        $noElegibles[] = $payload;
    }

    return array(
        'totales' => array(
            'proyectos_reales' => count($projects),
            'calculados' => count($calculados),
            'pendientes' => count($pendientes),
            'no_elegibles' => count($noElegibles)
        ),
        'calculados' => $calculados,
        'pendientes' => $pendientes,
        'no_elegibles' => $noElegibles
    );
}

function evt_sem_api_job_key()
{
    return 'evt_semestres_calc_job_v1';
}

function evt_sem_api_clear_job()
{
    $key = evt_sem_api_job_key();
    unset($_SESSION[$key]);
}

function evt_sem_api_get_job()
{
    $key = evt_sem_api_job_key();
    if (!isset($_SESSION[$key]) || !is_array($_SESSION[$key])) {
        return null;
    }
    return $_SESSION[$key];
}

function evt_sem_api_set_job($job)
{
    $key = evt_sem_api_job_key();
    $_SESSION[$key] = $job;
}

function evt_sem_api_job_progress_payload($job)
{
    $total = isset($job['total']) ? (int)$job['total'] : 0;
    $cursor = isset($job['cursor']) ? (int)$job['cursor'] : 0;
    $processed = max(0, min($cursor, $total));
    $percent = ($total > 0) ? (int)floor(($processed * 100) / $total) : 100;
    if ($percent > 100) {
        $percent = 100;
    }

    return array(
        'total' => $total,
        'procesados' => $processed,
        'pendientes' => max(0, $total - $processed),
        'porcentaje' => $percent,
        'creados' => isset($job['creados']) ? (int)$job['creados'] : 0,
        'actualizados' => isset($job['actualizados']) ? (int)$job['actualizados'] : 0,
        'desactivados' => isset($job['desactivados']) ? (int)$job['desactivados'] : 0,
        'errores' => isset($job['errores']) && is_array($job['errores']) ? $job['errores'] : array(),
        'finalizado' => ($processed >= $total)
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    evt_sem_api_exit(false, 'Metodo no permitido.', null, 405);
}

if (!isset($_SESSION['usuario'])) {
    evt_sem_api_exit(false, 'Sesion no valida.', null, 401);
}

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
    evt_sem_api_exit(false, 'No autorizado.', null, 403);
}

$csrfToken = isset($_POST['csrf_token']) ? (string)$_POST['csrf_token'] : '';
if (!evt_mto_validate_csrf_token($csrfToken, 'evt_mantenimiento_admin_csrf')) {
    evt_sem_api_exit(false, 'Token CSRF invalido.', null, 403);
}

$conexion = evt_mto_db_connect();
if (!($conexion instanceof mysqli)) {
    evt_sem_api_exit(false, 'No se pudo conectar a la base de datos.', null, 500);
}
@mysqli_set_charset($conexion, 'utf8mb4');

$action = isset($_POST['action']) ? trim((string)$_POST['action']) : '';

if ($action === 'get_status') {
    $status = evt_sem_api_build_status($conexion);
    evt_sem_api_exit(true, 'Estado de semestres cargado.', $status);
}

if ($action === 'start_calc') {
    $status = evt_sem_api_build_status($conexion);
    $pendientes = isset($status['pendientes']) && is_array($status['pendientes']) ? $status['pendientes'] : array();

    if (empty($pendientes)) {
        evt_sem_api_clear_job();
        evt_sem_api_exit(true, 'No hay proyectos pendientes por calcular.', array(
            'job' => null,
            'totales' => $status['totales']
        ));
    }

    $job = array(
        'cursor' => 0,
        'total' => count($pendientes),
        'items' => $pendientes,
        'creados' => 0,
        'actualizados' => 0,
        'desactivados' => 0,
        'errores' => array(),
        'iniciado_en' => date('Y-m-d H:i:s')
    );
    evt_sem_api_set_job($job);

    evt_sem_api_exit(true, 'Cola de calculo preparada.', array(
        'job' => evt_sem_api_job_progress_payload($job),
        'totales' => $status['totales']
    ));
}

if ($action === 'run_step') {
    $job = evt_sem_api_get_job();
    if (!is_array($job)) {
        evt_sem_api_exit(false, 'No hay un proceso en curso. Inicia el calculo primero.', null, 400);
    }

    $batch = isset($_POST['batch']) ? (int)$_POST['batch'] : 5;
    if ($batch < 1) {
        $batch = 1;
    }
    if ($batch > 20) {
        $batch = 20;
    }

    $items = isset($job['items']) && is_array($job['items']) ? $job['items'] : array();
    $total = isset($job['total']) ? (int)$job['total'] : 0;
    $cursor = isset($job['cursor']) ? (int)$job['cursor'] : 0;
    $end = min($total, $cursor + $batch);

    for ($i = $cursor; $i < $end; $i++) {
        $item = isset($items[$i]) ? $items[$i] : null;
        if (!is_array($item)) {
            $job['errores'][] = array(
                'id_py' => 0,
                'titulo' => 'Desconocido',
                'mensaje' => 'Item de cola invalido'
            );
            continue;
        }

        $idPy = isset($item['id_py']) ? (int)$item['id_py'] : 0;
        $titulo = isset($item['titulo']) ? (string)$item['titulo'] : 'Sin titulo';
        $fiTxt = isset($item['fecha_inicio']) ? (string)$item['fecha_inicio'] : '';
        $ffTxt = isset($item['fecha_fin']) ? (string)$item['fecha_fin'] : '';

        if ($idPy <= 0) {
            $job['errores'][] = array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'mensaje' => 'ID de proyecto invalido'
            );
            continue;
        }

        try {
            $fi = parseDMY_orThrow($fiTxt);
            $ff = parseDMY_orThrow($ffTxt);
            if ($fi > $ff) {
                throw new RuntimeException('Fecha inicio mayor que fecha fin');
            }

            $sync = syncProyectoSemestres($conexion, $idPy, $fi, $ff);
            $job['creados'] += isset($sync['creados']) ? (int)$sync['creados'] : 0;
            $job['actualizados'] += isset($sync['actualizados']) ? (int)$sync['actualizados'] : 0;
            $job['desactivados'] += isset($sync['desactivados']) ? (int)$sync['desactivados'] : 0;
        } catch (Throwable $e) {
            $job['errores'][] = array(
                'id_py' => $idPy,
                'titulo' => $titulo,
                'mensaje' => $e->getMessage()
            );
        }
    }

    $job['cursor'] = $end;
    evt_sem_api_set_job($job);

    $progress = evt_sem_api_job_progress_payload($job);
    if (!empty($progress['finalizado'])) {
        evt_sem_api_clear_job();
    }

    evt_sem_api_exit(true, 'Paso ejecutado.', array('job' => $progress));
}

if ($action === 'reset_job') {
    evt_sem_api_clear_job();
    evt_sem_api_exit(true, 'Proceso reiniciado.');
}

evt_sem_api_exit(false, 'Accion no permitida.', null, 400);

