<?php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

include "../componentes/configSesion.php";
include "../includes/db_connection.php";
include_once "../includes/api_dirsu/projects_real_service.php";

function lp_obs_json_exit($ok, $msg, $data = null)
{
    $out = array('ok' => (bool)$ok, 'msg' => (string)$msg);
    if ($data !== null) {
        $out['data'] = $data;
    }
    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function lp_obs_add_business_days($datetime, $days)
{
    $ts = strtotime((string)$datetime);
    $days = (int)$days;
    if (!$ts || $days <= 0) {
        return $ts ?: null;
    }
    $cursor = $ts;
    while ($days > 0) {
        $cursor = strtotime('+1 day', $cursor);
        $dow = (int)date('w', $cursor); // 0: domingo, 6: sábado
        if ($dow !== 0 && $dow !== 6) {
            $days--;
        }
    }
    return $cursor;
}

function lp_obs_count_business_days_remaining($fromTs, $toTs)
{
    if (!$fromTs || !$toTs || $toTs <= $fromTs) {
        return 0;
    }
    $days = 0;
    $cursor = $fromTs;
    while (true) {
        $cursor = strtotime('+1 day', $cursor);
        if ($cursor > $toTs) {
            break;
        }
        $dow = (int)date('w', $cursor);
        if ($dow !== 0 && $dow !== 6) {
            $days++;
        }
    }
    return $days;
}

if (!($conexion instanceof mysqli)) {
    lp_obs_json_exit(false, 'Conexión no disponible.');
}

$response_id = isset($_GET['response_id']) ? (int)$_GET['response_id'] : 0;
if ($response_id <= 0) {
    lp_obs_json_exit(false, 'Parámetro response_id inválido.');
}

$sqlCtx = "
    SELECT
        r.id AS response_id,
        r.id_py,
        e.id AS eval_id,
        e.situacion,
        e.id_oficina_actual,
        o.codigo AS oficina_cod,
        o.nombre AS oficina_nom
    FROM sm_respuestas r
    LEFT JOIN eva_evaluaciones e
        ON e.id_respuesta = r.id
    LEFT JOIN eva_oficinas o
        ON o.id = e.id_oficina_actual
    WHERE r.id = " . $response_id . "
    LIMIT 1
";
$rsCtx = mysqli_query($conexion, $sqlCtx);
if (!($rsCtx instanceof mysqli_result)) {
    lp_obs_json_exit(false, 'No se pudo validar el contexto.');
}
$ctx = mysqli_fetch_assoc($rsCtx);
mysqli_free_result($rsCtx);
if (!$ctx) {
    lp_obs_json_exit(false, 'No se encontró la respuesta solicitada.');
}

$id_py = isset($ctx['id_py']) ? (int)$ctx['id_py'] : 0;
if ($id_py <= 0) {
    lp_obs_json_exit(false, 'El proyecto asociado no es válido.');
}

$scope = rsu_projects_real_default_scope();
$scopeSql = rsu_projects_real_scope_where_sql($conexion, $scope);
$sqlAccess = "
    SELECT p.id
    " . rsu_projects_real_from_sql() . "
    AND p.id = " . $id_py . "
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
    lp_obs_json_exit(false, 'No autorizado para ver este proyecto.');
}

$eval_id = isset($ctx['eval_id']) ? (int)$ctx['eval_id'] : 0;
if ($eval_id <= 0) {
    lp_obs_json_exit(true, 'Sin ruta de evaluación.', array(
        'response_id' => $response_id,
        'id_py' => $id_py,
        'has_observation' => false,
        'cotejo' => null,
        'rubrica' => null,
    ));
}

$notaLabel = array(
    0 => 'En espera',
    1 => 'Insuficiente',
    2 => 'Mejorable',
    3 => 'Satisfactorio',
    4 => 'Excelente',
);
$aspectoLabel = array(
    'estructura' => 'Estructura',
    'contenido' => 'Contenido',
    'redaccion' => 'Redacción',
    'calidad_info' => 'Calidad de información',
    'propuesta_mejora' => 'Propuesta de Mejora',
);

$fetchObs = function ($tipo) use ($conexion, $eval_id, $notaLabel, $aspectoLabel) {
    $tipo = (string)$tipo;
    if ($tipo !== 'cotejo' && $tipo !== 'rubrica') {
        return null;
    }
    $sql = "
        SELECT
            c.id,
            c.id_oficina,
            c.estado,
            c.obs_general,
            c.total,
            COALESCE(c.dias_subsanacion, 0) AS dias_subsanacion,
            COALESCE(c.ultimo_observado_at, c.actualizado_at) AS obs_at,
            o.codigo AS oficina_cod,
            o.nombre AS oficina_nom
        FROM eva_calificaciones c
        LEFT JOIN eva_oficinas o
            ON o.id = c.id_oficina
        WHERE c.id_evaluacion = " . (int)$eval_id . "
          AND c.tipo = '" . mysqli_real_escape_string($conexion, $tipo) . "'
          AND c.estado = 'observado'
        ORDER BY COALESCE(c.ultimo_observado_at, c.actualizado_at) DESC, c.id DESC
        LIMIT 1
    ";
    $rs = mysqli_query($conexion, $sql);
    if (!($rs instanceof mysqli_result)) {
        return null;
    }
    $row = mysqli_fetch_assoc($rs);
    mysqli_free_result($rs);
    if (!$row) {
        return null;
    }

    $obsAtRaw = isset($row['obs_at']) ? (string)$row['obs_at'] : '';
    $obsAtTs = $obsAtRaw !== '' ? strtotime($obsAtRaw) : false;
    $diasSub = isset($row['dias_subsanacion']) ? (int)$row['dias_subsanacion'] : 0;
    if ($diasSub <= 0) {
        $diasSub = ($tipo === 'rubrica') ? 1 : 2;
    }

    $limiteTs = $obsAtTs ? lp_obs_add_business_days($obsAtRaw, $diasSub) : null;
    $ahoraTs = time();
    $laborablesRestantes = ($limiteTs && $limiteTs > $ahoraTs)
        ? lp_obs_count_business_days_remaining($ahoraTs, $limiteTs)
        : 0;

    $out = array(
        'tipo' => $tipo,
        'oficina_cod' => (string)($row['oficina_cod'] ?? ''),
        'oficina_nom' => (string)($row['oficina_nom'] ?? ''),
        'obs_at' => $obsAtRaw,
        'obs_at_fmt' => $obsAtTs ? date('d/m/Y H:i', $obsAtTs) : '-',
        'dias_subsanacion' => $diasSub,
        'fecha_limite' => $limiteTs ? date('Y-m-d H:i:s', $limiteTs) : '',
        'fecha_limite_fmt' => $limiteTs ? date('d/m/Y H:i', $limiteTs) : '-',
        'dias_laborables_restantes' => $laborablesRestantes,
    );

    if ($tipo === 'cotejo') {
        $out['obs_text'] = (string)($row['obs_general'] ?? '');
    } else {
        $out['total'] = isset($row['total']) ? (int)$row['total'] : null;
        $out['aspectos'] = array();
        $sqlAx = "
            SELECT aspecto, nota, observacion
            FROM eva_rubrica_aspectos
            WHERE id_calificacion = " . (int)$row['id'] . "
            ORDER BY FIELD(aspecto,'estructura','contenido','redaccion','calidad_info','propuesta_mejora')
        ";
        $rsAx = mysqli_query($conexion, $sqlAx);
        if ($rsAx instanceof mysqli_result) {
            while ($ax = mysqli_fetch_assoc($rsAx)) {
                $nota = isset($ax['nota']) ? (int)$ax['nota'] : 0;
                $out['aspectos'][] = array(
                    'aspecto' => isset($aspectoLabel[$ax['aspecto']]) ? $aspectoLabel[$ax['aspecto']] : (string)$ax['aspecto'],
                    'nota' => $nota,
                    'notaTx' => isset($notaLabel[$nota]) ? $notaLabel[$nota] : (string)$nota,
                    'obs' => (string)($ax['observacion'] ?? ''),
                );
            }
            mysqli_free_result($rsAx);
        }
    }

    return $out;
};

$cotejo = $fetchObs('cotejo');
$rubrica = $fetchObs('rubrica');

lp_obs_json_exit(true, 'OK', array(
    'response_id' => $response_id,
    'id_py' => $id_py,
    'situacion' => (string)($ctx['situacion'] ?? ''),
    'oficina_cod' => (string)($ctx['oficina_cod'] ?? ''),
    'oficina_nom' => (string)($ctx['oficina_nom'] ?? ''),
    'has_observation' => (bool)($cotejo || $rubrica),
    'cotejo' => $cotejo,
    'rubrica' => $rubrica,
));
