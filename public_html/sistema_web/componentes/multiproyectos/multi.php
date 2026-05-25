<?php
if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 2) {
    return;
}

$rsu_modal_auto_show = !isset($_SESSION['multiproyectos_mostrado']);
if ($rsu_modal_auto_show) {
    $_SESSION['multiproyectos_mostrado'] = true;
}

include_once __DIR__ . '/../../includes/db_connection.php';
include_once __DIR__ . '/../../includes/api_dirsu/project_service.php';
include_once __DIR__ . '/../../includes/api_dirsu/semester_audit_service.php';
include_once __DIR__ . '/../../includes/api_dirsu/active_periods_service.php';
include_once __DIR__ . '/../../includes/api_dirsu/project_interface_access_service.php';

if (!function_exists('rsu_modal_coordinator_is_missing_date')) {
    function rsu_modal_coordinator_is_missing_date($value)
    {
        $value = trim((string)$value);
        return ($value === '' || $value === '0000-00-00' || $value === '00/00/0000');
    }
}

if (!function_exists('rsu_modal_coordinator_format_date')) {
    function rsu_modal_coordinator_format_date($value)
    {
        $value = trim((string)$value);
        if (rsu_modal_coordinator_is_missing_date($value)) {
            return null;
        }

        $formats = array('Y-m-d', 'd/m/Y', 'd-m-Y', 'Y/m/d', 'Y-m-d H:i:s');
        $i = 0;
        for ($i = 0; $i < count($formats); $i++) {
            $date = DateTime::createFromFormat($formats[$i], $value);
            if ($date instanceof DateTime) {
                return $date->format('d/m/Y');
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            return date('d/m/Y', $timestamp);
        }

        return $value;
    }
}

if (!function_exists('rsu_modal_coordinator_semester_delivery_label')) {
    function rsu_modal_coordinator_semester_delivery_label($tipo, $final)
    {
        $tipo = trim((string)$tipo);
        if ($tipo === 'presentacion') {
            return 'Presentación de proyecto';
        }

        if ($tipo === 'semestral' && (int)$final === 1) {
            return 'Informe final';
        }

        if ($tipo === 'semestral') {
            return 'Informe semestral';
        }

        return 'Entrega de semestre';
    }
}

if (!function_exists('rsu_modal_coordinator_build_semester_resume')) {
    function rsu_modal_coordinator_build_semester_resume($rows)
    {
        $rows = is_array($rows) ? $rows : array();
        $resume = array();

        $i = 0;
        for ($i = 0; $i < count($rows); $i++) {
            $row = is_array($rows[$i]) ? $rows[$i] : array();
            $anio = isset($row['anio']) ? (int)$row['anio'] : 0;
            $periodo = isset($row['periodo']) ? strtoupper(trim((string)$row['periodo'])) : '';
            if ($anio <= 0 || ($periodo !== 'I' && $periodo !== 'II')) {
                continue;
            }

            $key = $anio . '-' . $periodo;
            if (!isset($resume[$key])) {
                $resume[$key] = array(
                    'semestre' => $key,
                    'entregas' => array()
                );
            }

            $entrega = rsu_modal_coordinator_semester_delivery_label(
                isset($row['tipo']) ? $row['tipo'] : '',
                isset($row['final']) ? (int)$row['final'] : 0
            );

            if (!in_array($entrega, $resume[$key]['entregas'], true)) {
                $resume[$key]['entregas'][] = $entrega;
            }
        }

        return array_values($resume);
    }
}

if (!function_exists('rsu_modal_coordinator_status_badge')) {
    function rsu_modal_coordinator_status_badge($status)
    {
        $status = trim((string)$status);
        if ($status === 'abierto') {
            return array('Abierto', 'badge badge-success');
        }
        if ($status === 'proximo') {
            return array('Próximo', 'badge badge-warning');
        }
        if ($status === 'cerrado') {
            return array('Cerrado', 'badge badge-secondary');
        }
        return array('Sin fechas', 'badge badge-dark');
    }
}

if (!function_exists('rsu_modal_coordinator_parse_period_name')) {
    function rsu_modal_coordinator_parse_period_name($period_name)
    {
        $period_name = trim((string)$period_name);
        if (!preg_match('/^(\d{4})\s*-\s*(I|II)$/u', $period_name, $match)) {
            return null;
        }

        return array(
            'anio' => (int)$match[1],
            'periodo' => strtoupper((string)$match[2])
        );
    }
}

if (!function_exists('rsu_modal_coordinator_is_safe_route')) {
    function rsu_modal_coordinator_is_safe_route($route)
    {
        $route = trim((string)$route);
        if ($route === '') {
            return false;
        }
        if (strpos($route, '://') !== false) {
            return false;
        }
        if (preg_match('/^(\/|\\\\)/', $route)) {
            return false;
        }
        if (strpos($route, '..') !== false) {
            return false;
        }
        return true;
    }
}

if (!function_exists('rsu_modal_coordinator_extract_route')) {
    function rsu_modal_coordinator_extract_route($interface_access)
    {
        $route = '';
        if (is_array($interface_access) && isset($interface_access['interface']) && is_array($interface_access['interface'])) {
            $route = isset($interface_access['interface']['ruta']) ? trim((string)$interface_access['interface']['ruta']) : '';
        }

        return rsu_modal_coordinator_is_safe_route($route) ? $route : '';
    }
}

if (!function_exists('rsu_modal_coordinator_build_in_clause')) {
    function rsu_modal_coordinator_build_in_clause($ids)
    {
        $clean = array();
        $source = is_array($ids) ? $ids : array();
        $i = 0;
        for ($i = 0; $i < count($source); $i++) {
            $id = (int)$source[$i];
            if ($id > 0) {
                $clean[$id] = $id;
            }
        }

        if (empty($clean)) {
            return '';
        }

        return implode(',', array_values($clean));
    }
}

if (!function_exists('rsu_modal_coordinator_is_final_delivery')) {
    function rsu_modal_coordinator_is_final_delivery($f3_access)
    {
        if (!is_array($f3_access) || empty($f3_access['allow'])) {
            return false;
        }

        $period_name = '';
        if (isset($f3_access['periodo_resuelto']) && is_array($f3_access['periodo_resuelto'])) {
            $period_name = isset($f3_access['periodo_resuelto']['nombre']) ? (string)$f3_access['periodo_resuelto']['nombre'] : '';
        }

        $parsed_period = rsu_modal_coordinator_parse_period_name($period_name);
        if (!is_array($parsed_period)) {
            return false;
        }

        $semestres = isset($f3_access['semestres_proyecto']) && is_array($f3_access['semestres_proyecto'])
            ? $f3_access['semestres_proyecto']
            : array();

        $i = 0;
        for ($i = 0; $i < count($semestres); $i++) {
            $item = is_array($semestres[$i]) ? $semestres[$i] : array();
            $anio = isset($item['anio']) ? (int)$item['anio'] : 0;
            $periodo = isset($item['periodo']) ? strtoupper((string)$item['periodo']) : '';
            $tipo = isset($item['tipo']) ? (string)$item['tipo'] : '';
            $is_final = isset($item['final']) ? (int)$item['final'] : 0;

            if (
                $anio === (int)$parsed_period['anio']
                && $periodo === (string)$parsed_period['periodo']
                && $tipo === 'semestral'
            ) {
                return $is_final === 1;
            }
        }

        return false;
    }
}

$usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
$user_id = 0;
$id_py_actual = 0;
$proyectos = array();
$periodos_activos = array();
$periodos_activos_error = '';
$interface_codes = array('F1-GENERALIDADES', 'F1-PLAN', 'F1-ANEXOS', 'F3-SEMESTRAL');

if ($usuario !== '' && isset($conexion) && $conexion instanceof mysqli) {
    $stmt_user = $conexion->prepare('SELECT id, id_py FROM usuarios WHERE usuario = ? LIMIT 1');
    if ($stmt_user) {
        $stmt_user->bind_param('s', $usuario);
        $stmt_user->execute();
        $stmt_user->bind_result($user_id, $id_py_actual);
        $stmt_user->fetch();
        $stmt_user->close();
    }
}

if ($user_id > 0 && isset($conexion) && $conexion instanceof mysqli) {
    $sql_proyectos = "
        SELECT
            p.id AS id_proyecto,
            p.p2 AS nombre,
            p.fecha_inicio,
            p.fecha_fin,
            GROUP_CONCAT(DISTINCT per.nombre ORDER BY per.fecha_inicio DESC SEPARATOR ' | ') AS periodos
        FROM usuarios_proyectos up
        INNER JOIN proyectos p ON p.id = up.id_proyecto
        LEFT JOIN proyectos_periodo pp ON pp.id_py = p.id
        LEFT JOIN periodos per ON per.id = pp.id_periodo
        WHERE up.id_usuario = ?
          AND up.activo = 1
        GROUP BY p.id, p.p2, p.fecha_inicio, p.fecha_fin
        ORDER BY
            CASE WHEN COALESCE(MAX(per.fecha_inicio), '') = '' THEN 1 ELSE 0 END,
            MAX(per.fecha_inicio) DESC,
            MAX(per.fecha_fin) DESC,
            p.id DESC
    ";

    $stmt_projects = $conexion->prepare($sql_proyectos);
    if ($stmt_projects) {
        $stmt_projects->bind_param('i', $user_id);
        $stmt_projects->execute();
        $result_projects = $stmt_projects->get_result();

        if ($result_projects instanceof mysqli_result) {
            while ($row = $result_projects->fetch_assoc()) {
                $project_id = isset($row['id_proyecto']) ? (int)$row['id_proyecto'] : 0;
                if ($project_id <= 0) {
                    continue;
                }

                $semestres_resumen = array();
                if (function_exists('rsu_api_project_semesters_audit_get')) {
                    $audit = rsu_api_project_semesters_audit_get($project_id, 0, '');
                    if (is_array($audit) && !empty($audit['ok']) && isset($audit['data']['proyectos'][0])) {
                        $project_audit = $audit['data']['proyectos'][0];
                        $rows_semestres = array();

                        if (isset($project_audit['semestres_esperados']) && is_array($project_audit['semestres_esperados'])) {
                            $rows_semestres = $project_audit['semestres_esperados'];
                        }

                        if (empty($rows_semestres) && isset($project_audit['semestres_bd']) && is_array($project_audit['semestres_bd'])) {
                            $rows_semestres = $project_audit['semestres_bd'];
                        }

                        $semestres_resumen = rsu_modal_coordinator_build_semester_resume($rows_semestres);
                    }
                }

                $interfaces_access = array();
                $code_index = 0;
                for ($code_index = 0; $code_index < count($interface_codes); $code_index++) {
                    $interface_code = (string)$interface_codes[$code_index];
                    $interfaces_access[$interface_code] = array(
                        'allow' => false,
                        'reason_code' => 'not_evaluated',
                        'reason_message' => 'No se pudo evaluar el acceso para esta interfaz.',
                        'interface' => array(
                            'codigo' => $interface_code,
                            'ruta' => ''
                        )
                    );

                    if (function_exists('rsu_api_project_interface_access_get')) {
                        $access_result = rsu_api_project_interface_access_get($interface_code, $project_id, 'America/Lima');
                        if (is_array($access_result) && !empty($access_result['ok']) && isset($access_result['data']) && is_array($access_result['data'])) {
                            $interfaces_access[$interface_code] = $access_result['data'];
                        } elseif (is_array($access_result)) {
                            $interfaces_access[$interface_code]['reason_code'] = isset($access_result['error_code']) ? (string)$access_result['error_code'] : 'evaluation_error';
                            $interfaces_access[$interface_code]['reason_message'] = isset($access_result['error_message']) ? (string)$access_result['error_message'] : 'No se pudo evaluar el acceso para esta interfaz.';
                        }
                    }
                }

                $access_f3 = isset($interfaces_access['F3-SEMESTRAL']) ? $interfaces_access['F3-SEMESTRAL'] : array();
                $allow_f3 = !empty($access_f3['allow']);
                $is_final_delivery = $allow_f3 ? rsu_modal_coordinator_is_final_delivery($access_f3) : false;

                $presentation_actions = array();
                $presentation_codes = array(
                    'F1-GENERALIDADES' => 'Completar Presentación de Proyecto - Generalidades',
                    'F1-PLAN' => 'Completar Presentación de Proyecto - Plan de Proyecto',
                    'F1-ANEXOS' => 'Completar Presentación de Proyecto - Anexos'
                );

                foreach ($presentation_codes as $code => $label) {
                    $access = isset($interfaces_access[$code]) ? $interfaces_access[$code] : array();
                    if (!empty($access['allow'])) {
                        $route = rsu_modal_coordinator_extract_route($access);
                        if ($route !== '') {
                            $presentation_actions[] = array(
                                'label' => $label,
                                'route' => $route
                            );
                        }
                    }
                }

                $quick_actions = array();
                if ($allow_f3) {
                    $f3_route = rsu_modal_coordinator_extract_route($access_f3);
                    if ($f3_route !== '') {
                        if ($is_final_delivery) {
                            $quick_actions[] = array(
                                'label' => 'Completar Informe Final',
                                'route' => $f3_route,
                                'class' => 'btn btn-dark btn-sm btn-accion-rapida'
                            );
                        } else {
                            $quick_actions[] = array(
                                'label' => 'Completar Inf. Semestral',
                                'route' => $f3_route,
                                'class' => 'btn btn-success btn-sm btn-accion-rapida'
                            );
                        }
                    }
                }

                $has_presentation_actions = count($presentation_actions) > 0;
                $status_text = '';
                $status_class = 'coor-status-none';

                if ($allow_f3 && $is_final_delivery) {
                    $status_text = 'Te corresponde en este período: Informe final';
                    $status_class = 'coor-status-ok';
                } elseif ($allow_f3) {
                    $status_text = 'Te corresponde en este período: Informe semestral';
                    $status_class = 'coor-status-ok';
                } elseif ($has_presentation_actions) {
                    $status_text = 'Te corresponde en este período: Presentación de proyecto';
                    $status_class = 'coor-status-ok';
                } else {
                    $status_text = 'No te corresponde presentar nada en este período por la fecha en que se desarrolla tu proyecto.';
                    $status_class = 'coor-status-none';
                }

                $proyectos[] = array(
                    'id' => $project_id,
                    'nombre' => isset($row['nombre']) ? (string)$row['nombre'] : '',
                    'fecha_inicio' => isset($row['fecha_inicio']) ? (string)$row['fecha_inicio'] : '',
                    'fecha_fin' => isset($row['fecha_fin']) ? (string)$row['fecha_fin'] : '',
                    'periodos' => isset($row['periodos']) ? (string)$row['periodos'] : '',
                    'es_actual' => ($id_py_actual > 0 && $id_py_actual === $project_id) ? 1 : 0,
                    'semestres_resumen' => $semestres_resumen,
                    'status_text' => $status_text,
                    'status_class' => $status_class,
                    'quick_actions' => $quick_actions,
                    'presentation_actions' => $presentation_actions
                );
            }
            $result_projects->free();
        }

        $stmt_projects->close();
    }
}

if (function_exists('rsu_api_periods_active_snapshot_get')) {
    $snapshot = rsu_api_periods_active_snapshot_get(0, 1, 'America/Lima');

    if (is_array($snapshot) && !empty($snapshot['ok']) && isset($snapshot['data']['periodos']) && is_array($snapshot['data']['periodos'])) {
        $periodos_source = $snapshot['data']['periodos'];
        $idx_period = 0;

        for ($idx_period = 0; $idx_period < count($periodos_source); $idx_period++) {
            $periodo = is_array($periodos_source[$idx_period]) ? $periodos_source[$idx_period] : array();
            $cronogramas = isset($periodo['cronogramas_activos']) && is_array($periodo['cronogramas_activos'])
                ? $periodo['cronogramas_activos']
                : array();

            $cron_filtrados = array();
            $idx_cron = 0;
            for ($idx_cron = 0; $idx_cron < count($cronogramas); $idx_cron++) {
                $cron = is_array($cronogramas[$idx_cron]) ? $cronogramas[$idx_cron] : array();
                $tipo_id = isset($cron['tipo_id']) ? (int)$cron['tipo_id'] : 0;

                if ($tipo_id !== 1 && $tipo_id !== 2) {
                    continue;
                }

                $formulario = isset($cron['formulario']) && is_array($cron['formulario']) ? $cron['formulario'] : array();
                $cron_filtrados[] = array(
                    'tipo_id' => $tipo_id,
                    'tipo_nombre' => isset($cron['tipo_nombre']) ? (string)$cron['tipo_nombre'] : (($tipo_id === 1) ? 'Presentación de proyecto' : 'Informe semestral'),
                    'apertura' => isset($cron['apertura']) ? (string)$cron['apertura'] : '',
                    'cierre' => isset($cron['cierre']) ? (string)$cron['cierre'] : '',
                    'ventana_estado' => isset($cron['ventana_estado']) ? (string)$cron['ventana_estado'] : '',
                    'formulario_existe' => !empty($formulario['existe']) ? 1 : 0,
                    'formulario_nombre' => isset($formulario['nombre']) ? (string)$formulario['nombre'] : '',
                    'items_activos' => isset($formulario['items_activos']) ? (int)$formulario['items_activos'] : 0
                );
            }

            $periodos_activos[] = array(
                'nombre' => isset($periodo['nombre']) ? (string)$periodo['nombre'] : '',
                'fecha_inicio' => isset($periodo['fecha_inicio']) ? (string)$periodo['fecha_inicio'] : '',
                'fecha_fin' => isset($periodo['fecha_fin']) ? (string)$periodo['fecha_fin'] : '',
                'cronogramas' => $cron_filtrados
            );
        }
    } else {
        $periodos_activos_error = 'No se pudo obtener el resumen de períodos activos en este momento.';
    }
} else {
    $periodos_activos_error = 'No se encontró el servicio de períodos activos para mostrar esta información.';
}

$has_convocatoria_presentacion = false;
$idx_periodo_conv = 0;
for ($idx_periodo_conv = 0; $idx_periodo_conv < count($periodos_activos); $idx_periodo_conv++) {
    $periodo_item = is_array($periodos_activos[$idx_periodo_conv]) ? $periodos_activos[$idx_periodo_conv] : array();
    $cronogramas_item = isset($periodo_item['cronogramas']) && is_array($periodo_item['cronogramas'])
        ? $periodo_item['cronogramas']
        : array();

    $idx_cron_conv = 0;
    for ($idx_cron_conv = 0; $idx_cron_conv < count($cronogramas_item); $idx_cron_conv++) {
        $cron_item = is_array($cronogramas_item[$idx_cron_conv]) ? $cronogramas_item[$idx_cron_conv] : array();
        $tipo_id = isset($cron_item['tipo_id']) ? (int)$cron_item['tipo_id'] : 0;
        $ventana = isset($cron_item['ventana_estado']) ? strtolower(trim((string)$cron_item['ventana_estado'])) : '';
        $formulario_ok = isset($cron_item['formulario_existe']) ? ((int)$cron_item['formulario_existe'] === 1) : false;
        if ($tipo_id === 1 && $ventana === 'abierto' && $formulario_ok) {
            $has_convocatoria_presentacion = true;
            break 2;
        }
    }
}

$project_ids_gate = array();
$idx_gate = 0;
for ($idx_gate = 0; $idx_gate < count($proyectos); $idx_gate++) {
    $project_id_gate = isset($proyectos[$idx_gate]['id']) ? (int)$proyectos[$idx_gate]['id'] : 0;
    if ($project_id_gate > 0) {
        $project_ids_gate[$project_id_gate] = $project_id_gate;
    }
}
$project_ids_gate = array_values($project_ids_gate);

$final_approval_map = array();
$all_projects_final_approved = false;
$pending_final_projects = array();

if (!empty($project_ids_gate) && isset($conexion) && $conexion instanceof mysqli) {
    $in_clause_gate = rsu_modal_coordinator_build_in_clause($project_ids_gate);
    if ($in_clause_gate !== '') {
        $sql_gate = "
            SELECT
                s.id_py,
                MAX(1) AS has_final_semester,
                MAX(CASE WHEN e.situacion = 'aprobado' THEN 1 ELSE 0 END) AS has_final_approved
            FROM sm_proyecto_semestres s
            LEFT JOIN sm_respuestas r
                ON r.id_semestre = s.id
            LEFT JOIN eva_evaluaciones e
                ON e.id_respuesta = r.id
            WHERE s.id_py IN (" . $in_clause_gate . ")
              AND s.tipo = 'semestral'
              AND COALESCE(s.vigente, 1) = 1
              AND COALESCE(s.final, 0) = 1
            GROUP BY s.id_py
        ";
        $rs_gate = mysqli_query($conexion, $sql_gate);
        if ($rs_gate instanceof mysqli_result) {
            while ($row_gate = mysqli_fetch_assoc($rs_gate)) {
                $id_gate = isset($row_gate['id_py']) ? (int)$row_gate['id_py'] : 0;
                if ($id_gate <= 0) {
                    continue;
                }
                $has_final_sem = isset($row_gate['has_final_semester']) ? ((int)$row_gate['has_final_semester'] === 1) : false;
                $has_final_apr = isset($row_gate['has_final_approved']) ? ((int)$row_gate['has_final_approved'] === 1) : false;
                $final_approval_map[$id_gate] = array(
                    'has_final_semester' => $has_final_sem,
                    'has_final_approved' => $has_final_apr
                );
            }
            mysqli_free_result($rs_gate);
        }
    }
}

if (!empty($project_ids_gate)) {
    $all_projects_final_approved = true;
    $idx_pending = 0;
    for ($idx_pending = 0; $idx_pending < count($proyectos); $idx_pending++) {
        $project_item = is_array($proyectos[$idx_pending]) ? $proyectos[$idx_pending] : array();
        $project_id_item = isset($project_item['id']) ? (int)$project_item['id'] : 0;
        if ($project_id_item <= 0) {
            continue;
        }

        $final_info = isset($final_approval_map[$project_id_item]) ? $final_approval_map[$project_id_item] : null;
        $is_approved = is_array($final_info)
            && !empty($final_info['has_final_semester'])
            && !empty($final_info['has_final_approved']);

        if (!$is_approved) {
            $all_projects_final_approved = false;
            $titulo_pending = trim((string)(isset($project_item['nombre']) ? $project_item['nombre'] : ''));
            if ($titulo_pending === '') {
                $titulo_pending = 'Proyecto ID ' . $project_id_item;
            }
            $pending_final_projects[] = $titulo_pending;
        }
    }
}

$show_create_project_cta = false;
$create_project_enabled = false;
$create_project_message = '';
$create_project_button_title = '';

if ($has_convocatoria_presentacion) {
    $show_create_project_cta = true;
    if (empty($proyectos)) {
        $create_project_enabled = true;
        $create_project_message = 'RSU tiene disponible un cronograma de creación de proyectos. ¿Deseas crear tu primer proyecto? Presiona el botón.';
    } elseif ($all_projects_final_approved) {
        $create_project_enabled = true;
        $create_project_message = 'RSU tiene disponible un cronograma de creación de proyectos. ¿Deseas crear un nuevo proyecto? Presiona el botón.';
    } else {
        $create_project_enabled = false;
        $create_project_message = 'RSU tiene disponible un cronograma de creación de proyectos. Cuando completes tus informes finales pendientes podrás crear un nuevo proyecto.';
        $pending_count = count($pending_final_projects);
        if ($pending_count === 1) {
            $create_project_button_title = 'La creación de proyecto se desbloquea al completar tus informes finales pendientes de proyecto pasado.';
        } else {
            $create_project_button_title = 'La creación de proyecto se desbloquea al completar tus informes finales pendientes de proyectos pasados.';
        }
    }
}
?>

<style>
  .coor-modal-content {
    border: 0;
    border-radius: 0.75rem;
    overflow: hidden;
  }
  .coor-periodo-box {
    border: 1px solid #e5e7eb;
    border-radius: 0.5rem;
    padding: 0.75rem;
    margin-bottom: 0.75rem;
    background-color: #fafafa;
  }
  .coor-cron-item {
    border-left: 3px solid #17a2b8;
    padding: 0.5rem 0.75rem;
    background: #ffffff;
    border-radius: 0.35rem;
    margin-bottom: 0.5rem;
  }
  .coor-cron-item:last-child {
    margin-bottom: 0;
  }
  .coor-project-card {
    border: 1px solid #dce3ea;
    border-radius: 0.65rem;
    padding: 0.85rem;
    margin-bottom: 0.85rem;
    background-color: #ffffff;
  }
  .coor-project-card:last-child {
    margin-bottom: 0;
  }
  .coor-date-missing {
    color: #dc3545;
    font-weight: 700;
  }
  .coor-status-block {
    border-radius: 0.55rem;
    padding: 0.55rem 0.7rem;
    font-size: 0.91rem;
    margin-top: 0.7rem;
    margin-bottom: 0.25rem;
  }
  .coor-status-ok {
    background: #e8f5ee;
    border: 1px solid #b8e3c6;
    color: #146c43;
  }
  .coor-status-none {
    background: #fff5f5;
    border: 1px solid #f0c4c8;
    color: #b4232f;
  }
  .coor-main-actions {
    margin-top: 0.75rem;
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .coor-actions-label {
    font-size: 0.82rem;
    font-weight: 700;
    color: #4a5568;
    margin-top: 0.85rem;
    margin-bottom: 0.45rem;
  }
  .coor-actions-wrap {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
  }
  .coor-presentation-wrap {
    margin-top: 0.55rem;
    border: 1px dashed #b7d4ff;
    border-radius: 0.5rem;
    padding: 0.55rem;
    background: #f4f9ff;
  }
  .coor-presentation-label {
    font-size: 0.8rem;
    font-weight: 700;
    color: #235fa7;
    margin-bottom: 0.45rem;
  }
  .coor-semestres-resumen {
    border: 1px solid #e9ecef;
    border-radius: 0.5rem;
    background: #f8f9fa;
    padding: 0.75rem;
    margin-top: 0.75rem;
  }
  .btn-crear-proyecto-modal[data-disabled="1"] {
    cursor: not-allowed;
    opacity: 0.85;
  }
</style>

<div class="modal fade" id="modalMultiproyectos" tabindex="-1" role="dialog" aria-labelledby="modalMultiproyectosLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false" data-auto-show="<?php echo $rsu_modal_auto_show ? '1' : '0'; ?>">
  <div class="modal-dialog modal-dialog-centered modal-xl" role="document">
    <div class="modal-content coor-modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="modalMultiproyectosLabel">Selecciona uno de tus proyectos para iniciar sesión</h5>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <h6 class="mb-2"><strong>Período actual definido por DIRSU:</strong></h6>

          <?php if (!empty($periodos_activos_error)): ?>
            <div class="alert alert-warning mb-2"><?php echo htmlspecialchars($periodos_activos_error, ENT_QUOTES, 'UTF-8'); ?></div>
          <?php elseif (empty($periodos_activos)): ?>
            <div class="text-danger"><strong>Ninguno por el momento</strong></div>
          <?php else: ?>
            <?php foreach ($periodos_activos as $periodo_item): ?>
              <div class="coor-periodo-box">
                <div><strong><?php echo htmlspecialchars($periodo_item['nombre'] !== '' ? $periodo_item['nombre'] : 'Período sin nombre', ENT_QUOTES, 'UTF-8'); ?></strong></div>
                <div class="small text-muted mt-1">
                  Inicio: <?php echo htmlspecialchars(rsu_modal_coordinator_format_date($periodo_item['fecha_inicio']) ?: 'No registrado', ENT_QUOTES, 'UTF-8'); ?>
                  | Fin: <?php echo htmlspecialchars(rsu_modal_coordinator_format_date($periodo_item['fecha_fin']) ?: 'No registrado', ENT_QUOTES, 'UTF-8'); ?>
                </div>

                <?php if (empty($periodo_item['cronogramas'])): ?>
                  <div class="small text-muted mt-2">Sin cronogramas activos de presentación o informe para este período.</div>
                <?php else: ?>
                  <div class="mt-2">
                    <?php foreach ($periodo_item['cronogramas'] as $cron_item): ?>
                      <?php list($estado_texto, $estado_class) = rsu_modal_coordinator_status_badge($cron_item['ventana_estado']); ?>
                      <div class="coor-cron-item">
                        <div>
                          <strong><?php echo htmlspecialchars($cron_item['tipo_nombre'], ENT_QUOTES, 'UTF-8'); ?></strong>
                          <span class="<?php echo htmlspecialchars($estado_class, ENT_QUOTES, 'UTF-8'); ?> ml-1"><?php echo htmlspecialchars($estado_texto, ENT_QUOTES, 'UTF-8'); ?></span>
                        </div>
                        <div class="small text-muted mt-1">
                          Apertura: <?php echo htmlspecialchars(rsu_modal_coordinator_format_date($cron_item['apertura']) ?: 'No registrado', ENT_QUOTES, 'UTF-8'); ?>
                          | Cierre: <?php echo htmlspecialchars(rsu_modal_coordinator_format_date($cron_item['cierre']) ?: 'No registrado', ENT_QUOTES, 'UTF-8'); ?>
                        </div>
                        <?php if ((int)$cron_item['formulario_existe'] === 1): ?>
                          <div class="small mt-1">
                            Formulario activo: <strong><?php echo htmlspecialchars($cron_item['formulario_nombre'] !== '' ? $cron_item['formulario_nombre'] : 'Sin nombre', ENT_QUOTES, 'UTF-8'); ?></strong>
                            | Ítems activos: <strong><?php echo (int)$cron_item['items_activos']; ?></strong>
                          </div>
                        <?php else: ?>
                          <div class="small text-danger mt-1"><strong>Sin formulario activo</strong> | Ítems activos: <strong>0</strong></div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            <?php endforeach; ?>
          <?php endif; ?>

          <div class="small text-muted mt-2">Consulta tus semestres, para ver si te corresponde completar el formulario asignado.</div>
        </div>

        <?php if ($show_create_project_cta): ?>
          <div class="alert <?php echo $create_project_enabled ? 'alert-success' : 'alert-warning'; ?> mb-3">
            <p class="mb-2"><strong><?php echo htmlspecialchars($create_project_message, ENT_QUOTES, 'UTF-8'); ?></strong></p>
            <button
              type="button"
              class="btn <?php echo $create_project_enabled ? 'btn-primary' : 'btn-secondary'; ?> btn-sm btn-crear-proyecto-modal"
              data-target-route="vistas/datos_principales.php"
              data-disabled="<?php echo $create_project_enabled ? '0' : '1'; ?>"
              title="<?php echo htmlspecialchars($create_project_button_title, ENT_QUOTES, 'UTF-8'); ?>"
            >
              Crear proyecto
            </button>
          </div>
        <?php endif; ?>

        <?php if (empty($proyectos)): ?>
          <?php if (!$show_create_project_cta): ?>
            <div class="alert alert-info mb-0">
              <p class="mb-2"><strong>Aún no tienes proyectos vinculados para ingresar al sistema.</strong></p>
              <p class="mb-2">Por ahora, la creación de proyectos no está disponible en esta ventana.</p>
              <p class="mb-1">Correo de contacto: <strong>proyectosdirsu@unitru.edu.pe</strong></p>
              <p class="mb-0">Puedes consultar a este correo si existen fechas disponibles o próximas convocatorias para la presentación de nuevos proyectos.</p>
            </div>
          <?php endif; ?>
        <?php else: ?>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0"><strong>Proyectos vinculados al coordinador</strong></h6>
            <span class="badge badge-info">Total: <?php echo count($proyectos); ?></span>
          </div>

          <?php foreach ($proyectos as $proyecto): ?>
            <?php
              $titulo = trim((string)$proyecto['nombre']);
              $periodos_texto = trim((string)$proyecto['periodos']);
              $fecha_inicio_fmt = rsu_modal_coordinator_format_date($proyecto['fecha_inicio']);
              $fecha_fin_fmt = rsu_modal_coordinator_format_date($proyecto['fecha_fin']);
              $semestres_id = 'semestresProyecto_' . (int)$proyecto['id'];
              $quick_actions = isset($proyecto['quick_actions']) && is_array($proyecto['quick_actions']) ? $proyecto['quick_actions'] : array();
              $presentation_actions = isset($proyecto['presentation_actions']) && is_array($proyecto['presentation_actions']) ? $proyecto['presentation_actions'] : array();
            ?>
            <div class="coor-project-card">
              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <?php if ($titulo === ''): ?>
                    <div><em class="text-danger">Proyecto con título por registrar</em></div>
                  <?php else: ?>
                    <div><strong><?php echo htmlspecialchars($titulo, ENT_QUOTES, 'UTF-8'); ?></strong></div>
                  <?php endif; ?>

                  <div class="small text-muted mt-1">Período(s): <?php echo htmlspecialchars($periodos_texto !== '' ? $periodos_texto : 'Sin período registrado', ENT_QUOTES, 'UTF-8'); ?></div>
                  <div class="small mt-1">
                    Fecha de inicio:
                    <?php if ($fecha_inicio_fmt === null): ?>
                      <span class="coor-date-missing">No registrado</span>
                    <?php else: ?>
                      <strong><?php echo htmlspecialchars($fecha_inicio_fmt, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php endif; ?>
                    | Fecha de fin:
                    <?php if ($fecha_fin_fmt === null): ?>
                      <span class="coor-date-missing">No registrado</span>
                    <?php else: ?>
                      <strong><?php echo htmlspecialchars($fecha_fin_fmt, ENT_QUOTES, 'UTF-8'); ?></strong>
                    <?php endif; ?>
                  </div>
                </div>

                <?php if ((int)$proyecto['es_actual'] === 1): ?>
                  <span class="badge badge-secondary">Proyecto activo actual</span>
                <?php endif; ?>
              </div>

              <div class="coor-status-block <?php echo htmlspecialchars((string)$proyecto['status_class'], ENT_QUOTES, 'UTF-8'); ?>">
                <?php echo htmlspecialchars((string)$proyecto['status_text'], ENT_QUOTES, 'UTF-8'); ?>
              </div>

              <div class="coor-main-actions">
                <button type="button" class="btn btn-primary btn-sm btn-continuar-proyecto" data-id="<?php echo (int)$proyecto['id']; ?>">Continuar con este proyecto</button>
                <button type="button" class="btn btn-dark btn-sm btn-toggle-semestres" data-target="<?php echo htmlspecialchars($semestres_id, ENT_QUOTES, 'UTF-8'); ?>">Semestres</button>
              </div>

              <div class="coor-actions-label">Acciones disponibles</div>
              <?php if (empty($quick_actions) && empty($presentation_actions)): ?>
                <div class="small text-danger">No te corresponde presentar nada en este período por la fecha en que se desarrolla tu proyecto.</div>
              <?php else: ?>
                <?php if (!empty($quick_actions)): ?>
                  <div class="coor-actions-wrap">
                    <?php foreach ($quick_actions as $quick_action): ?>
                      <button
                        type="button"
                        class="<?php echo htmlspecialchars(isset($quick_action['class']) ? $quick_action['class'] : 'btn btn-secondary btn-sm btn-accion-rapida', ENT_QUOTES, 'UTF-8'); ?>"
                        data-id="<?php echo (int)$proyecto['id']; ?>"
                        data-target-route="<?php echo htmlspecialchars((string)$quick_action['route'], ENT_QUOTES, 'UTF-8'); ?>"
                      >
                        <?php echo htmlspecialchars((string)$quick_action['label'], ENT_QUOTES, 'UTF-8'); ?>
                      </button>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <?php if (!empty($presentation_actions)): ?>
                  <div class="coor-presentation-wrap">
                    <div class="coor-presentation-label">Presentación de proyecto disponible</div>
                    <div class="coor-actions-wrap">
                      <?php foreach ($presentation_actions as $presentation_action): ?>
                        <button
                          type="button"
                          class="btn btn-outline-primary btn-sm btn-accion-rapida"
                          data-id="<?php echo (int)$proyecto['id']; ?>"
                          data-target-route="<?php echo htmlspecialchars((string)$presentation_action['route'], ENT_QUOTES, 'UTF-8'); ?>"
                        >
                          <?php echo htmlspecialchars((string)$presentation_action['label'], ENT_QUOTES, 'UTF-8'); ?>
                        </button>
                      <?php endforeach; ?>
                    </div>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <div id="<?php echo htmlspecialchars($semestres_id, ENT_QUOTES, 'UTF-8'); ?>" class="coor-semestres-resumen d-none">
                <?php if (!empty($proyecto['semestres_resumen'])): ?>
                  <div class="small text-muted mb-2">Resumen de semestres del proyecto</div>
                  <ul class="mb-0 pl-3">
                    <?php foreach ($proyecto['semestres_resumen'] as $sem_item): ?>
                      <li>
                        <strong><?php echo htmlspecialchars($sem_item['semestre'], ENT_QUOTES, 'UTF-8'); ?>:</strong>
                        <?php echo htmlspecialchars(implode(', ', $sem_item['entregas']), ENT_QUOTES, 'UTF-8'); ?>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <div class="small text-muted">No se encontró un resumen de semestres para este proyecto.</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>

      <div class="modal-footer justify-content-between">
        <small class="text-muted mb-0">Debes seleccionar un proyecto para ingresar al sistema.</small>
        <a href="componentes/sesion/cerrarSesion.php" class="btn btn-outline-danger">Cerrar sesión</a>
      </div>
    </div>
  </div>
</div>

<script>
(function () {
  function requestProjectSelection(projectId) {
    var params = new URLSearchParams();
    params.append('id_proyecto', projectId);

    return fetch('componentes/multiproyectos/seleccionar_proyecto.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
      },
      body: params.toString()
    }).then(function (response) {
      return response.json();
    });
  }

  function initModal() {
    var modalElement = document.getElementById('modalMultiproyectos');
    if (!modalElement) {
      return;
    }

    var hasJQueryModal = typeof window.jQuery !== 'undefined' && typeof window.jQuery.fn.modal === 'function';
    var bsModal = null;

    function openModal() {
      modalElement.setAttribute('data-allow-close', '0');
      if (hasJQueryModal) {
        window.jQuery(modalElement).modal('show');
        return;
      }
      if (bsModal) {
        bsModal.show();
      }
    }

    if (hasJQueryModal) {
      window.jQuery(modalElement).modal({
        backdrop: 'static',
        keyboard: false,
        show: false
      });
    } else if (window.bootstrap && window.bootstrap.Modal) {
      bsModal = new window.bootstrap.Modal(modalElement, {
        backdrop: 'static',
        keyboard: false
      });
    }

    window.rsuOpenProjectSelectorModal = openModal;

    var triggerButtons = document.querySelectorAll('[data-open-project-selector="1"]');
    var t = 0;
    for (t = 0; t < triggerButtons.length; t++) {
      triggerButtons[t].addEventListener('click', function (event) {
        event.preventDefault();
        openModal();
      });
    }

    if (modalElement.getAttribute('data-auto-show') === '1') {
      openModal();
    }

    modalElement.addEventListener('hide.bs.modal', function (event) {
      if (modalElement.getAttribute('data-allow-close') !== '1') {
        event.preventDefault();
      }
    });

    var toggleButtons = document.querySelectorAll('.btn-toggle-semestres');
    var i = 0;
    for (i = 0; i < toggleButtons.length; i++) {
      toggleButtons[i].addEventListener('click', function () {
        var targetId = this.getAttribute('data-target');
        if (!targetId) {
          return;
        }

        var targetNode = document.getElementById(targetId);
        if (!targetNode) {
          return;
        }

        if (targetNode.classList.contains('d-none')) {
          targetNode.classList.remove('d-none');
          this.textContent = 'Ocultar semestres';
        } else {
          targetNode.classList.add('d-none');
          this.textContent = 'Semestres';
        }
      });
    }

    var continueButtons = document.querySelectorAll('.btn-continuar-proyecto');
    for (i = 0; i < continueButtons.length; i++) {
      continueButtons[i].addEventListener('click', function () {
        var button = this;
        var projectId = button.getAttribute('data-id');
        if (!projectId) {
          return;
        }

        var originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Procesando...';

        requestProjectSelection(projectId)
          .then(function (payload) {
            if (!payload || payload.ok !== true) {
              throw new Error(payload && payload.message ? payload.message : 'No se pudo seleccionar el proyecto.');
            }

            modalElement.setAttribute('data-allow-close', '1');
            if (hasJQueryModal) {
              window.jQuery(modalElement).modal('hide');
            }
            window.location.reload();
          })
          .catch(function (error) {
            alert(error && error.message ? error.message : 'Ocurrió un error al seleccionar el proyecto.');
          })
          .finally(function () {
            button.disabled = false;
            button.textContent = originalText;
          });
      });
    }

    var createButtons = document.querySelectorAll('.btn-crear-proyecto-modal');
    for (i = 0; i < createButtons.length; i++) {
      createButtons[i].addEventListener('click', function () {
        var button = this;
        var isDisabled = button.getAttribute('data-disabled') === '1';
        var targetRoute = button.getAttribute('data-target-route');
        var disabledMessage = button.getAttribute('title') || 'La creación de proyecto está temporalmente bloqueada.';

        if (isDisabled) {
          alert(disabledMessage);
          return;
        }

        if (!targetRoute) {
          return;
        }

        window.location.href = targetRoute;
      });
    }

    var quickActionButtons = document.querySelectorAll('.btn-accion-rapida');
    for (i = 0; i < quickActionButtons.length; i++) {
      quickActionButtons[i].addEventListener('click', function () {
        var button = this;
        var projectId = button.getAttribute('data-id');
        var targetRoute = button.getAttribute('data-target-route');

        if (!projectId || !targetRoute) {
          return;
        }

        var originalText = button.textContent;
        button.disabled = true;
        button.textContent = 'Abriendo...';

        requestProjectSelection(projectId)
          .then(function (payload) {
            if (!payload || payload.ok !== true) {
              throw new Error(payload && payload.message ? payload.message : 'No se pudo seleccionar el proyecto.');
            }

            modalElement.setAttribute('data-allow-close', '1');
            if (hasJQueryModal) {
              window.jQuery(modalElement).modal('hide');
            }
            window.location.href = targetRoute;
          })
          .catch(function (error) {
            alert(error && error.message ? error.message : 'Ocurrió un error al abrir la acción solicitada.');
          })
          .finally(function () {
            button.disabled = false;
            button.textContent = originalText;
          });
      });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initModal);
  } else {
    initModal();
  }
})();
</script>
