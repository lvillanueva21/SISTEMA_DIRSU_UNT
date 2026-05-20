<?php
/**
 * Servicio central de progreso/evaluación por proyecto y semestre para lista_proyectos.
 * No altera datos, solo lectura.
 */

if (!function_exists('rsu_projects_progress_normalize_ids')) {
    function rsu_projects_progress_normalize_ids($project_ids)
    {
        $out = array();
        if (!is_array($project_ids)) {
            return $out;
        }
        foreach ($project_ids as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $out[$id] = $id;
            }
        }
        return array_values($out);
    }
}

if (!function_exists('rsu_projects_eval_role_office_code')) {
    function rsu_projects_eval_role_office_code($id_rol)
    {
        $id_rol = (int)$id_rol;
        if ($id_rol === 5) return 'PCF';
        if ($id_rol === 4) return 'DD';
        if ($id_rol === 3) return 'DF';
        if ($id_rol === 1) return 'RSU';
        return null;
    }
}

if (!function_exists('rsu_projects_eval_visible_actions')) {
    function rsu_projects_eval_visible_actions($id_rol)
    {
        $id_rol = (int)$id_rol;
        if ($id_rol === 1 || $id_rol === 5) {
            return array('cotejo', 'rubrica');
        }
        if ($id_rol === 3 || $id_rol === 4) {
            return array('vb');
        }
        return array();
    }
}

if (!function_exists('rsu_projects_eval_badge_from_summary')) {
    function rsu_projects_eval_badge_from_summary($eval)
    {
        if (!is_array($eval) || empty($eval['eval_id'])) {
            return array('text' => 'Sin ruta', 'class' => 'badge badge-secondary');
        }

        $situacion = isset($eval['situacion']) ? (string)$eval['situacion'] : '';
        if ($situacion === 'aprobado') {
            return array('text' => 'Aprobado', 'class' => 'badge badge-success');
        }

        $inst = isset($eval['instancia_estado']) ? (string)$eval['instancia_estado'] : '';
        if ($inst === 'observado') {
            return array('text' => 'Observado', 'class' => 'badge badge-danger');
        }
        if ($inst === 'en_espera') {
            return array('text' => 'En espera', 'class' => 'badge badge-primary');
        }
        if ($inst === 'aprobado') {
            return array('text' => 'Derivado', 'class' => 'badge badge-info');
        }

        return array('text' => 'En proceso', 'class' => 'badge badge-info');
    }
}

if (!function_exists('rsu_projects_eval_action_state')) {
    function rsu_projects_eval_action_state($id_rol, $accion, $eval)
    {
        $accion = (string)$accion;
        $visible = rsu_projects_eval_visible_actions($id_rol);
        if (!in_array($accion, $visible, true)) {
            return array('enabled' => false, 'reason' => 'No aplica para tu rol.');
        }

        if (!is_array($eval) || empty($eval['eval_id'])) {
            return array('enabled' => false, 'reason' => 'La ruta de evaluación aún no inicia.');
        }

        $situacion = isset($eval['situacion']) ? (string)$eval['situacion'] : '';
        if ($situacion === 'aprobado') {
            return array('enabled' => false, 'reason' => 'El informe ya tiene aprobación total.');
        }

        $inst = isset($eval['instancia_estado']) ? (string)$eval['instancia_estado'] : '';
        if ($inst === 'observado') {
            return array('enabled' => false, 'reason' => 'Pendiente de subsanación del coordinador.');
        }

        $roleOffice = rsu_projects_eval_role_office_code($id_rol);
        $currOffice = isset($eval['oficina_cod']) ? (string)$eval['oficina_cod'] : '';
        if ($roleOffice === null || $currOffice === '' || $roleOffice !== $currOffice) {
            $ofName = isset($eval['oficina_nom']) ? trim((string)$eval['oficina_nom']) : '';
            if ($ofName === '') {
                $ofName = 'otra oficina';
            }
            return array('enabled' => false, 'reason' => 'No puede calificar, está en ' . $ofName . '.');
        }

        if ($inst !== 'en_espera') {
            return array('enabled' => false, 'reason' => 'La oficina no está en espera de revisión.');
        }

        $actionKey = '';
        if ($accion === 'cotejo') $actionKey = 'cotejo_estado';
        if ($accion === 'rubrica') $actionKey = 'rubrica_estado';
        if ($accion === 'vb') $actionKey = 'vb_estado';

        if ($actionKey !== '' && isset($eval[$actionKey]) && (string)$eval[$actionKey] === 'aprobado') {
            return array('enabled' => false, 'reason' => 'Esta calificación ya fue aprobada.');
        }

        return array('enabled' => true, 'reason' => 'Listo para calificar.');
    }
}

if (!function_exists('rsu_projects_progress_empty_map')) {
    function rsu_projects_progress_empty_map($ids)
    {
        $map = array();
        foreach ($ids as $id) {
            $map[(int)$id] = array();
        }
        return $map;
    }
}

if (!function_exists('rsu_projects_progress_eval_by_response_ids')) {
    function rsu_projects_progress_eval_by_response_ids($conexion, $response_ids)
    {
        $out = array();
        $ids = array();
        foreach ((array)$response_ids as $id) {
            $id = (int)$id;
            if ($id > 0) $ids[$id] = $id;
        }
        if (empty($ids) || !($conexion instanceof mysqli)) {
            return $out;
        }

        $in = implode(',', array_map('intval', array_values($ids)));
        $sql = "
            SELECT
                e.id AS eval_id,
                e.id_respuesta,
                e.situacion,
                e.id_oficina_actual,
                e.actualizado_at,
                o.codigo AS oficina_cod,
                o.nombre AS oficina_nom,
                oi.estado AS instancia_estado,
                oi.llegada AS instancia_llegada,
                oi.salida AS instancia_salida,
                oi.ultima_observacion_at,
                oi.ultima_revision_solicitada_at,
                cj.estado AS cotejo_estado,
                cj.actualizado_at AS cotejo_at,
                rb.estado AS rubrica_estado,
                rb.actualizado_at AS rubrica_at,
                vb.estado AS vb_estado,
                vb.actualizado_at AS vb_at
            FROM eva_evaluaciones e
            LEFT JOIN eva_oficinas o
                ON o.id = e.id_oficina_actual
            LEFT JOIN (
                SELECT id_evaluacion, id_oficina, MAX(id) AS last_id
                FROM eva_oficina_instancias
                GROUP BY id_evaluacion, id_oficina
            ) lastoi
                ON lastoi.id_evaluacion = e.id
               AND lastoi.id_oficina = e.id_oficina_actual
            LEFT JOIN eva_oficina_instancias oi
                ON oi.id = lastoi.last_id
            LEFT JOIN eva_calificaciones cj
                ON cj.id_evaluacion = e.id
               AND cj.id_oficina = e.id_oficina_actual
               AND cj.tipo = 'cotejo'
            LEFT JOIN eva_calificaciones rb
                ON rb.id_evaluacion = e.id
               AND rb.id_oficina = e.id_oficina_actual
               AND rb.tipo = 'rubrica'
            LEFT JOIN eva_calificaciones vb
                ON vb.id_evaluacion = e.id
               AND vb.id_oficina = e.id_oficina_actual
               AND vb.tipo = 'vistobueno'
            WHERE e.id_respuesta IN (" . $in . ")
        ";

        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return $out;
        }
        while ($row = mysqli_fetch_assoc($rs)) {
            $rid = isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
            if ($rid <= 0) continue;
            $out[$rid] = array(
                'eval_id' => isset($row['eval_id']) ? (int)$row['eval_id'] : 0,
                'id_respuesta' => $rid,
                'situacion' => (string)($row['situacion'] ?? ''),
                'id_oficina_actual' => isset($row['id_oficina_actual']) ? (int)$row['id_oficina_actual'] : null,
                'oficina_cod' => (string)($row['oficina_cod'] ?? ''),
                'oficina_nom' => (string)($row['oficina_nom'] ?? ''),
                'instancia_estado' => (string)($row['instancia_estado'] ?? ''),
                'instancia_llegada' => (string)($row['instancia_llegada'] ?? ''),
                'instancia_salida' => (string)($row['instancia_salida'] ?? ''),
                'ultima_observacion_at' => (string)($row['ultima_observacion_at'] ?? ''),
                'ultima_revision_solicitada_at' => (string)($row['ultima_revision_solicitada_at'] ?? ''),
                'cotejo_estado' => (string)($row['cotejo_estado'] ?? ''),
                'cotejo_at' => (string)($row['cotejo_at'] ?? ''),
                'rubrica_estado' => (string)($row['rubrica_estado'] ?? ''),
                'rubrica_at' => (string)($row['rubrica_at'] ?? ''),
                'vb_estado' => (string)($row['vb_estado'] ?? ''),
                'vb_at' => (string)($row['vb_at'] ?? '')
            );
        }
        mysqli_free_result($rs);
        return $out;
    }
}

if (!function_exists('rsu_projects_progress_by_project_ids')) {
    function rsu_projects_progress_by_project_ids($conexion, $project_ids, $id_rol = 0)
    {
        $ids = rsu_projects_progress_normalize_ids($project_ids);
        if (empty($ids)) return array();

        $map = rsu_projects_progress_empty_map($ids);
        if (!($conexion instanceof mysqli)) return $map;

        $in_ids = implode(',', array_map('intval', $ids));
        $sem_by_id = array();
        $sem_ids = array();

        $sql_sem = "
            SELECT
                s.id AS id_semestre,
                s.id_py,
                s.anio,
                s.periodo,
                COALESCE(s.final, 0) AS es_final
            FROM sm_proyecto_semestres s
            WHERE s.vigente = 1
              AND s.tipo = 'semestral'
              AND s.id_py IN (" . $in_ids . ")
            ORDER BY
                s.id_py ASC,
                s.anio ASC,
                CASE s.periodo WHEN 'I' THEN 1 WHEN 'II' THEN 2 ELSE 3 END ASC,
                COALESCE(s.numero, 0) ASC,
                s.id ASC
        ";

        $rs_sem = mysqli_query($conexion, $sql_sem);
        if (!($rs_sem instanceof mysqli_result)) {
            return $map;
        }

        while ($row = mysqli_fetch_assoc($rs_sem)) {
            $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
            $id_semestre = isset($row['id_semestre']) ? (int)$row['id_semestre'] : 0;
            if ($id_py <= 0 || $id_semestre <= 0) continue;

            $anio = isset($row['anio']) ? (int)$row['anio'] : 0;
            $periodo = trim((string)($row['periodo'] ?? ''));
            $es_final = isset($row['es_final']) ? ((int)$row['es_final'] === 1) : false;

            $item = array(
                'id_semestre' => $id_semestre,
                'periodo' => ($anio > 0 && $periodo !== '') ? ($anio . '-' . $periodo) : 'No definido',
                'tipo' => $es_final ? 'final' : 'semestral',
                'informe_label' => $es_final ? 'Inf. Final' : 'Inf. Semestral',
                'has_response' => false,
                'response_id' => null,
                'eval' => null,
            );
            $map[$id_py][] = $item;
            $sem_by_id[$id_semestre] = array('id_py' => $id_py, 'idx' => count($map[$id_py]) - 1);
            $sem_ids[] = $id_semestre;
        }
        mysqli_free_result($rs_sem);

        if (empty($sem_ids)) {
            return $map;
        }

        $in_sem = implode(',', array_map('intval', array_values(array_unique($sem_ids))));
        $response_by_sem = array();
        $response_ids = array();
        $sql_resp = "
            SELECT r.id_semestre, MAX(r.id) AS id_respuesta
            FROM sm_respuestas r
            WHERE r.id_semestre IN (" . $in_sem . ")
            GROUP BY r.id_semestre
        ";
        $rs_resp = mysqli_query($conexion, $sql_resp);
        if ($rs_resp instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($rs_resp)) {
                $id_sem = isset($row['id_semestre']) ? (int)$row['id_semestre'] : 0;
                $id_resp = isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
                if ($id_sem > 0 && $id_resp > 0) {
                    $response_by_sem[$id_sem] = $id_resp;
                    $response_ids[$id_resp] = $id_resp;
                }
            }
            mysqli_free_result($rs_resp);
        }

        $eval_by_response = rsu_projects_progress_eval_by_response_ids($conexion, array_values($response_ids));

        foreach ($response_by_sem as $id_sem => $id_resp) {
            if (!isset($sem_by_id[$id_sem])) continue;
            $pos = $sem_by_id[$id_sem];
            $id_py = (int)$pos['id_py'];
            $idx = (int)$pos['idx'];
            if (!isset($map[$id_py][$idx])) continue;

            $eval = isset($eval_by_response[$id_resp]) ? $eval_by_response[$id_resp] : null;
            $badge = rsu_projects_eval_badge_from_summary($eval);
            $actions = array();
            foreach (rsu_projects_eval_visible_actions($id_rol) as $accion) {
                $actions[$accion] = rsu_projects_eval_action_state($id_rol, $accion, $eval);
            }

            $map[$id_py][$idx]['has_response'] = true;
            $map[$id_py][$idx]['response_id'] = $id_resp;
            $map[$id_py][$idx]['eval'] = array(
                'badge_text' => $badge['text'],
                'badge_class' => $badge['class'],
                'summary' => $eval,
                'actions' => $actions
            );
        }

        return $map;
    }
}

