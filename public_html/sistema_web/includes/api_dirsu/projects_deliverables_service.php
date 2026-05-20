<?php
/**
 * Servicio de entregables por proyecto para lista_proyectos.
 * Fuente de verdad:
 * - sm_proyecto_semestres (tipo='semestral', vigente=1)
 * - sm_respuestas (existencia por id_semestre)
 */

if (!function_exists('rsu_projects_deliverables_normalize_ids')) {
    function rsu_projects_deliverables_normalize_ids($project_ids)
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

if (!function_exists('rsu_projects_deliverables_empty_map')) {
    function rsu_projects_deliverables_empty_map($project_ids)
    {
        $map = array();
        foreach ($project_ids as $id_py) {
            $map[(int)$id_py] = array();
        }
        return $map;
    }
}

if (!function_exists('rsu_projects_deliverables_by_project_ids')) {
    function rsu_projects_deliverables_by_project_ids($conexion, $project_ids)
    {
        $ids = rsu_projects_deliverables_normalize_ids($project_ids);
        if (empty($ids)) {
            return array();
        }

        $map = rsu_projects_deliverables_empty_map($ids);
        if (!($conexion instanceof mysqli)) {
            return $map;
        }

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
            if ($id_py <= 0 || $id_semestre <= 0) {
                continue;
            }

            $anio = isset($row['anio']) ? (int)$row['anio'] : 0;
            $periodo = trim((string)($row['periodo'] ?? ''));
            $es_final = isset($row['es_final']) ? ((int)$row['es_final'] === 1) : false;

            $item = array(
                'id_semestre' => $id_semestre,
                'periodo' => ($anio > 0 && $periodo !== '') ? ($anio . '-' . $periodo) : 'No definido',
                'tipo' => $es_final ? 'final' : 'semestral',
                'label' => $es_final ? 'Inf. Final' : 'Inf. Semestral',
                'has_response' => false,
            );

            $map[$id_py][] = $item;
            $sem_by_id[$id_semestre] = array('id_py' => $id_py, 'idx' => count($map[$id_py]) - 1);
            $sem_ids[] = $id_semestre;
        }
        mysqli_free_result($rs_sem);

        if (empty($sem_ids)) {
            return $map;
        }

        $in_sem_ids = implode(',', array_map('intval', array_values(array_unique($sem_ids))));
        $sql_resp = "
            SELECT
                r.id_semestre,
                MAX(r.id) AS last_id
            FROM sm_respuestas r
            WHERE r.id_semestre IN (" . $in_sem_ids . ")
            GROUP BY r.id_semestre
        ";

        $rs_resp = mysqli_query($conexion, $sql_resp);
        if (!($rs_resp instanceof mysqli_result)) {
            return $map;
        }

        while ($row = mysqli_fetch_assoc($rs_resp)) {
            $id_semestre = isset($row['id_semestre']) ? (int)$row['id_semestre'] : 0;
            if ($id_semestre <= 0 || !isset($sem_by_id[$id_semestre])) {
                continue;
            }

            $pos = $sem_by_id[$id_semestre];
            $id_py = (int)$pos['id_py'];
            $idx = (int)$pos['idx'];
            if (isset($map[$id_py][$idx])) {
                $map[$id_py][$idx]['has_response'] = true;
            }
        }
        mysqli_free_result($rs_resp);

        return $map;
    }
}

