<?php
header('Content-Type: application/json; charset=utf-8');

include "../componentes/configSesion.php";
include "../includes/db_connection.php";
include_once "../includes/api_dirsu/projects_progress_service.php";
include_once "../includes/api_dirsu/projects_real_service.php";

function lp_eval_json_exit($ok, $msg, $data = null)
{
    $out = array('ok' => (bool)$ok, 'msg' => (string)$msg);
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out);
    exit;
}

if (!($conexion instanceof mysqli)) {
    lp_eval_json_exit(false, 'Conexión no disponible.');
}

$response_id = isset($_GET['response_id']) ? (int)$_GET['response_id'] : 0;
if ($response_id <= 0) {
    lp_eval_json_exit(false, 'Parámetro response_id inválido.');
}

$requested_project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;

$sqlInfo = "
    SELECT
        r.id AS response_id,
        r.id_py,
        r.estado AS response_state,
        r.id_formulario,
        e.id AS eval_id,
        e.situacion,
        e.id_oficina_actual,
        e.actualizado_at AS eval_actualizado_at,
        o.codigo AS oficina_cod,
        o.nombre AS oficina_nom,
        p.p2 AS titulo_proyecto,
        s.anio,
        s.periodo,
        COALESCE(s.final, 0) AS es_final
    FROM sm_respuestas r
    LEFT JOIN eva_evaluaciones e
        ON e.id_respuesta = r.id
    LEFT JOIN eva_oficinas o
        ON o.id = e.id_oficina_actual
    LEFT JOIN proyectos p
        ON p.id = r.id_py
    LEFT JOIN sm_proyecto_semestres s
        ON s.id = r.id_semestre
    WHERE r.id = " . $response_id . "
    LIMIT 1
";
$rsInfo = mysqli_query($conexion, $sqlInfo);
if (!($rsInfo instanceof mysqli_result)) {
    lp_eval_json_exit(false, 'No se pudo cargar evaluación.');
}
$info = mysqli_fetch_assoc($rsInfo);
mysqli_free_result($rsInfo);
if (!$info) {
    lp_eval_json_exit(false, 'No se encontró la respuesta.');
}

$id_py_info = isset($info['id_py']) ? (int)$info['id_py'] : 0;
if ($requested_project_id > 0 && $id_py_info > 0 && $requested_project_id !== $id_py_info) {
    lp_eval_json_exit(false, 'Parámetros inconsistentes entre respuesta y proyecto.');
}

$scope = rsu_projects_real_default_scope();
$scopeSql = rsu_projects_real_scope_where_sql($conexion, $scope);
$sqlAccess = "
    SELECT p.id
    " . rsu_projects_real_from_sql() . "
    AND p.id = " . $id_py_info . "
    " . $scopeSql . "
    LIMIT 1
";
$rsAccess = mysqli_query($conexion, $sqlAccess);
$allowed = false;
if ($rsAccess instanceof mysqli_result) {
    $allowed = (bool)mysqli_fetch_assoc($rsAccess);
    mysqli_free_result($rsAccess);
}
if (!$allowed) {
    lp_eval_json_exit(false, 'No autorizado para ver este proyecto.');
}

$periodo_txt = 'No definido';
if ((int)($info['anio'] ?? 0) > 0 && trim((string)($info['periodo'] ?? '')) !== '') {
    $periodo_txt = (int)$info['anio'] . '-' . trim((string)$info['periodo']);
}
$tipo_txt = ((int)($info['es_final'] ?? 0) === 1) ? 'Informe Final' : 'Informe Semestral';

$response_state = isset($info['response_state']) ? (int)$info['response_state'] : 0;
$formulario_id = isset($info['id_formulario']) ? (int)$info['id_formulario'] : 0;
$formulario_nombre = '';
if ($formulario_id > 0) {
    $sqlForm = "SELECT nombre FROM sm_formularios WHERE id = " . $formulario_id . " LIMIT 1";
    $rsForm = mysqli_query($conexion, $sqlForm);
    if ($rsForm instanceof mysqli_result) {
        $rowForm = mysqli_fetch_assoc($rsForm);
        if ($rowForm && isset($rowForm['nombre'])) {
            $formulario_nombre = (string)$rowForm['nombre'];
        }
        mysqli_free_result($rsForm);
    }
}

$evalSummaryMap = rsu_projects_progress_eval_by_response_ids($conexion, array($response_id));
$eval = isset($evalSummaryMap[$response_id]) ? $evalSummaryMap[$response_id] : null;
$badge = rsu_projects_eval_badge_from_summary($eval);

$actionsPayload = array();
    $labels = array(
    'cotejo' => 'Calificar Cotejo',
    'rubrica' => 'Calificar Rúbrica',
    'vb' => 'Visto Bueno',
);
foreach (rsu_projects_eval_visible_actions($id_rol) as $accion) {
    $state = rsu_projects_eval_action_state($id_rol, $accion, $eval);
    $actionsPayload[] = array(
        'key' => $accion,
        'label' => isset($labels[$accion]) ? $labels[$accion] : $accion,
        'enabled' => !empty($state['enabled']),
        'reason' => isset($state['reason']) ? (string)$state['reason'] : '',
    );
}

$timeline = array();
$sqlOffices = "SELECT id, codigo, nombre, orden FROM eva_oficinas WHERE activo = 1 ORDER BY orden ASC";
$rsOffices = mysqli_query($conexion, $sqlOffices);
$offices = array();
if ($rsOffices instanceof mysqli_result) {
    while ($o = mysqli_fetch_assoc($rsOffices)) {
        $offices[] = array(
            'id' => (int)$o['id'],
            'codigo' => (string)$o['codigo'],
            'nombre' => (string)$o['nombre'],
            'orden' => (int)$o['orden']
        );
    }
    mysqli_free_result($rsOffices);
}

$instByOffice = array();
$calByOffice = array();
$eval_id = isset($info['eval_id']) ? (int)$info['eval_id'] : 0;
if ($eval_id > 0) {
    $sqlInst = "
        SELECT oi.*
        FROM eva_oficina_instancias oi
        INNER JOIN (
            SELECT id_oficina, MAX(id) AS last_id
            FROM eva_oficina_instancias
            WHERE id_evaluacion = " . $eval_id . "
            GROUP BY id_oficina
        ) x
            ON x.last_id = oi.id
    ";
    $rsInst = mysqli_query($conexion, $sqlInst);
    if ($rsInst instanceof mysqli_result) {
        while ($ri = mysqli_fetch_assoc($rsInst)) {
            $oid = isset($ri['id_oficina']) ? (int)$ri['id_oficina'] : 0;
            if ($oid > 0) {
                $instByOffice[$oid] = $ri;
            }
        }
        mysqli_free_result($rsInst);
    }

    $sqlCal = "
        SELECT id_oficina, tipo, estado, actualizado_at
        FROM eva_calificaciones
        WHERE id_evaluacion = " . $eval_id;
    $rsCal = mysqli_query($conexion, $sqlCal);
    if ($rsCal instanceof mysqli_result) {
        while ($rc = mysqli_fetch_assoc($rsCal)) {
            $oid = isset($rc['id_oficina']) ? (int)$rc['id_oficina'] : 0;
            if ($oid <= 0) continue;
            if (!isset($calByOffice[$oid])) $calByOffice[$oid] = array();
            $calByOffice[$oid][(string)$rc['tipo']] = array(
                'estado' => (string)($rc['estado'] ?? ''),
                'at' => (string)($rc['actualizado_at'] ?? '')
            );
        }
        mysqli_free_result($rsCal);
    }
}

foreach ($offices as $o) {
    $oid = (int)$o['id'];
    $inst = isset($instByOffice[$oid]) ? $instByOffice[$oid] : null;
    $estado = 'pendiente';
    if (is_array($inst) && isset($inst['estado'])) {
        $estado = (string)$inst['estado'];
    }
    if (($eval_id > 0) && isset($info['situacion']) && (string)$info['situacion'] === 'aprobado' && $estado === 'pendiente') {
        $estado = 'cerrado';
    }

    $timeline[] = array(
        'codigo' => $o['codigo'],
        'nombre' => $o['nombre'],
        'estado' => $estado,
        'llegada' => is_array($inst) ? (string)($inst['llegada'] ?? '') : '',
        'salida' => is_array($inst) ? (string)($inst['salida'] ?? '') : '',
        'obs_at' => is_array($inst) ? (string)($inst['ultima_observacion_at'] ?? '') : '',
        'rev_at' => is_array($inst) ? (string)($inst['ultima_revision_solicitada_at'] ?? '') : '',
        'calificaciones' => isset($calByOffice[$oid]) ? $calByOffice[$oid] : array(),
    );
}

$coordinator_flow = array(
    'visible' => false,
    'action' => '',
    'label' => '',
    'enabled' => false,
    'reason' => '',
    'obs_enabled' => false,
    'obs_reason' => '',
    'response_state' => $response_state,
);

if ($id_rol === 2) {
    $coordinator_flow['visible'] = true;
    $in_office = ($eval_id > 0 && !empty($info['id_oficina_actual']));
    $obs_oficina = false;
    if (is_array($eval)) {
        $obs_oficina = ((string)($eval['cotejo_estado'] ?? '') === 'observado')
            || ((string)($eval['rubrica_estado'] ?? '') === 'observado');
    }

    $changes_since_obs = null;
    if ($in_office && $obs_oficina && is_array($eval) && trim((string)($eval['ultima_observacion_at'] ?? '')) !== '') {
        $obsAtEsc = mysqli_real_escape_string($conexion, (string)$eval['ultima_observacion_at']);
        $sqlCh = "
            SELECT COUNT(*) AS c
            FROM sm_respuesta_items
            WHERE id_respuesta = " . $response_id . "
              AND actualizado_at > '" . $obsAtEsc . "'
        ";
        $rsCh = mysqli_query($conexion, $sqlCh);
        if ($rsCh instanceof mysqli_result) {
            $rowCh = mysqli_fetch_assoc($rsCh);
            $changes_since_obs = isset($rowCh['c']) ? (int)$rowCh['c'] : 0;
            mysqli_free_result($rsCh);
        }
    }

    $has_route = is_array($eval) && !empty($eval['eval_id']);
    $is_total_aprobado = $has_route && ((string)($eval['situacion'] ?? '') === 'aprobado');

    if ($is_total_aprobado || $response_state === 2) {
        $coordinator_flow['action'] = '';
        $coordinator_flow['label'] = 'Aprobado (solo lectura)';
        $coordinator_flow['enabled'] = false;
        $coordinator_flow['reason'] = 'El informe ya fue aprobado totalmente.';
    } elseif (!$in_office) {
        if ($response_state === 0 || $response_state === 3) {
            $coordinator_flow['action'] = 'solicitar';
            $coordinator_flow['label'] = 'Solicitar revisión';
            $coordinator_flow['enabled'] = true;
            $coordinator_flow['reason'] = 'Enviar el informe para revisión.';
        } elseif ($response_state === 1) {
            $coordinator_flow['action'] = 'anular';
            $coordinator_flow['label'] = 'Anular solicitud';
            $coordinator_flow['enabled'] = true;
            $coordinator_flow['reason'] = 'Volver a borrador para editar.';
        } else {
            $coordinator_flow['action'] = '';
            $coordinator_flow['label'] = 'Sin acción disponible';
            $coordinator_flow['enabled'] = false;
            $coordinator_flow['reason'] = 'Estado actual sin acciones para coordinador.';
        }
    } else {
        if ($obs_oficina) {
            $coordinator_flow['action'] = 'subsanar';
            $coordinator_flow['label'] = 'Enviar subsanación';
            $hay_cambios = ($changes_since_obs === null) ? true : ($changes_since_obs > 0);
            $coordinator_flow['enabled'] = $hay_cambios;
            $coordinator_flow['reason'] = $hay_cambios
                ? 'Enviar cambios para nueva revisión en la oficina.'
                : 'Realiza al menos un cambio en el informe antes de enviar la subsanación.';
            $coordinator_flow['obs_enabled'] = true;
            $coordinator_flow['obs_reason'] = 'Ver observaciones actuales.';
        } else {
            $coordinator_flow['action'] = '';
            $coordinator_flow['label'] = 'En revisión en oficina';
            $coordinator_flow['enabled'] = false;
            $coordinator_flow['reason'] = 'Esperando evaluación de la oficina actual.';
            $coordinator_flow['obs_enabled'] = false;
            $coordinator_flow['obs_reason'] = 'No hay observaciones activas.';
        }
    }
}

lp_eval_json_exit(true, 'OK', array(
    'response_id' => $response_id,
    'id_py' => $id_py_info,
    'titulo_proyecto' => (string)($info['titulo_proyecto'] ?? 'Sin título'),
    'periodo' => $periodo_txt,
    'tipo_informe' => $tipo_txt,
    'eval_badge' => array(
        'text' => $badge['text'],
        'class' => $badge['class']
    ),
    'timeline' => $timeline,
    'actions' => $actionsPayload,
    'coordinator_flow' => $coordinator_flow,
    'formulario_nombre' => $formulario_nombre
));
