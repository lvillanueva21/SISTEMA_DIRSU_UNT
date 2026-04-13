<?php
/**
 * Auditoria de semestres por proyecto.
 * Solo lectura: compara lo esperado (por fechas de proyecto) vs lo vigente en sm_proyecto_semestres.
 */

include_once __DIR__ . '/../db_connection.php';

if (!function_exists('rsu_api_semester_audit_parse_date')) {
    function rsu_api_semester_audit_parse_date($value)
    {
        if (function_exists('rsu_api_projects_parse_date')) {
            return rsu_api_projects_parse_date($value);
        }

        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $formats = array('d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d');
        $i = 0;
        for ($i = 0; $i < count($formats); $i++) {
            $date = DateTime::createFromFormat($formats[$i], $value);
            if ($date instanceof DateTime) {
                $date->setTime(0, 0, 0);
                return $date;
            }
        }

        $timestamp = strtotime($value);
        if ($timestamp !== false) {
            $date = new DateTime();
            $date->setTimestamp($timestamp);
            $date->setTime(0, 0, 0);
            return $date;
        }

        return null;
    }
}

if (!function_exists('rsu_api_semester_audit_semester_of_date')) {
    function rsu_api_semester_audit_semester_of_date($date)
    {
        $anio = (int)$date->format('Y');
        $mes = (int)$date->format('n');
        $periodo = ($mes <= 6) ? 'I' : 'II';
        return array($anio, $periodo);
    }
}

if (!function_exists('rsu_api_semester_audit_next_semester')) {
    function rsu_api_semester_audit_next_semester($anio, $periodo)
    {
        if ($periodo === 'I') {
            return array((int)$anio, 'II');
        }
        return array(((int)$anio) + 1, 'I');
    }
}

if (!function_exists('rsu_api_semester_audit_natural_limits')) {
    function rsu_api_semester_audit_natural_limits($anio, $periodo)
    {
        if ($periodo === 'I') {
            return array(new DateTime($anio . '-01-01'), new DateTime($anio . '-06-30'));
        }
        return array(new DateTime($anio . '-07-01'), new DateTime($anio . '-12-31'));
    }
}

if (!function_exists('rsu_api_semester_audit_build_expected_rows')) {
    function rsu_api_semester_audit_build_expected_rows($fecha_inicio, $fecha_fin)
    {
        $fi = rsu_api_semester_audit_parse_date($fecha_inicio);
        $ff = rsu_api_semester_audit_parse_date($fecha_fin);
        if (!$fi instanceof DateTime || !$ff instanceof DateTime) {
            return array(
                'ok' => false,
                'reason' => 'invalid_dates',
                'rows' => array()
            );
        }

        if ($fi > $ff) {
            return array(
                'ok' => false,
                'reason' => 'inverted_dates',
                'rows' => array()
            );
        }

        $start = rsu_api_semester_audit_semester_of_date($fi);
        $end = rsu_api_semester_audit_semester_of_date($ff);
        $cursor_anio = (int)$start[0];
        $cursor_periodo = (string)$start[1];

        $lista = array();
        while (true) {
            $lista[] = array($cursor_anio, $cursor_periodo);
            if ($cursor_anio === (int)$end[0] && $cursor_periodo === (string)$end[1]) {
                break;
            }
            $next = rsu_api_semester_audit_next_semester($cursor_anio, $cursor_periodo);
            $cursor_anio = (int)$next[0];
            $cursor_periodo = (string)$next[1];
        }

        $rows = array();
        $numero = 1;
        $total = count($lista);
        $idx = 0;
        for ($idx = 0; $idx < $total; $idx++) {
            $anio = (int)$lista[$idx][0];
            $periodo = (string)$lista[$idx][1];
            $limits = rsu_api_semester_audit_natural_limits($anio, $periodo);
            $nat_ini = $limits[0];
            $nat_fin = $limits[1];

            $ts_inicio = max($nat_ini->getTimestamp(), $fi->getTimestamp());
            $ts_fin = min($nat_fin->getTimestamp(), $ff->getTimestamp());
            $fecha_ini = date('Y-m-d', $ts_inicio);
            $fecha_fin_sem = date('Y-m-d', $ts_fin);

            $es_primero = ($idx === 0);
            $es_ultimo = ($idx === ($total - 1));

            if ($es_primero) {
                $rows[] = array(
                    'anio' => $anio,
                    'periodo' => $periodo,
                    'tipo' => 'presentacion',
                    'numero' => null,
                    'fecha_inicio' => $fecha_ini,
                    'fecha_fin' => $fecha_fin_sem,
                    'final' => 0
                );
            }

            $rows[] = array(
                'anio' => $anio,
                'periodo' => $periodo,
                'tipo' => 'semestral',
                'numero' => $numero,
                'fecha_inicio' => $fecha_ini,
                'fecha_fin' => $fecha_fin_sem,
                'final' => $es_ultimo ? 1 : 0
            );
            $numero++;
        }

        return array(
            'ok' => true,
            'reason' => 'ok',
            'rows' => $rows
        );
    }
}

if (!function_exists('rsu_api_semester_audit_fetch_respuestas_map')) {
    function rsu_api_semester_audit_fetch_respuestas_map($conexion, $semestre_ids)
    {
        $map = array();
        if (!$conexion instanceof mysqli) {
            return $map;
        }

        if (function_exists('rsu_api_projects_table_exists') && !rsu_api_projects_table_exists($conexion, 'sm_respuestas')) {
            return $map;
        }

        $in_clause = function_exists('rsu_api_projects_build_in_clause')
            ? rsu_api_projects_build_in_clause($semestre_ids)
            : '';
        if ($in_clause === '') {
            return $map;
        }

        $sql = "SELECT id, id_semestre, id_formulario, id_cronograma, estado, creado_at, actualizado_at
                FROM sm_respuestas
                WHERE id_semestre IN (" . $in_clause . ")";
        $res = @mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return $map;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $id_semestre = (int)$row['id_semestre'];
            if ($id_semestre <= 0) {
                continue;
            }

            $map[$id_semestre] = array(
                'id' => (int)$row['id'],
                'id_formulario' => (int)$row['id_formulario'],
                'id_cronograma' => (int)$row['id_cronograma'],
                'estado' => (int)$row['estado'],
                'creado_at' => isset($row['creado_at']) ? $row['creado_at'] : null,
                'actualizado_at' => isset($row['actualizado_at']) ? $row['actualizado_at'] : null
            );
        }
        mysqli_free_result($res);

        return $map;
    }
}

if (!function_exists('rsu_api_semester_audit_fetch_actual_rows')) {
    function rsu_api_semester_audit_fetch_actual_rows($conexion, $id_py)
    {
        $rows = array();
        $id_py = (int)$id_py;
        if ($id_py <= 0 || !$conexion instanceof mysqli) {
            return $rows;
        }

        if (function_exists('rsu_api_projects_table_exists') && !rsu_api_projects_table_exists($conexion, 'sm_proyecto_semestres')) {
            return $rows;
        }

        $where = "id_py = ?";
        $has_vigente = function_exists('rsu_api_projects_column_exists') ? rsu_api_projects_column_exists($conexion, 'sm_proyecto_semestres', 'vigente') : true;
        if ($has_vigente) {
            $where .= " AND vigente = 1";
        }

        $sql = "SELECT id, anio, periodo, tipo, numero, fecha_inicio, fecha_fin, final, estado, titulo
                FROM sm_proyecto_semestres
                WHERE " . $where . "
                ORDER BY anio, FIELD(periodo,'I','II'),
                         CASE tipo WHEN 'presentacion' THEN 0 ELSE 1 END,
                         COALESCE(numero,0)";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return $rows;
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_py);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res instanceof mysqli_result) {
            $semestre_ids = array();
            while ($row = mysqli_fetch_assoc($res)) {
                $id_semestre = (int)$row['id'];
                $semestre_ids[] = $id_semestre;
                $rows[] = array(
                    'id' => $id_semestre,
                    'anio' => (int)$row['anio'],
                    'periodo' => (string)$row['periodo'],
                    'tipo' => (string)$row['tipo'],
                    'numero' => $row['numero'] === null ? null : (int)$row['numero'],
                    'fecha_inicio' => (string)$row['fecha_inicio'],
                    'fecha_fin' => (string)$row['fecha_fin'],
                    'final' => isset($row['final']) ? (int)$row['final'] : 0,
                    'estado' => isset($row['estado']) ? (int)$row['estado'] : 0,
                    'titulo' => isset($row['titulo']) ? (string)$row['titulo'] : '',
                    'respuesta' => null
                );
            }
            mysqli_free_result($res);

            if (!empty($rows) && !empty($semestre_ids)) {
                $respuestas_map = rsu_api_semester_audit_fetch_respuestas_map($conexion, $semestre_ids);
                $i = 0;
                for ($i = 0; $i < count($rows); $i++) {
                    $sem_id = (int)$rows[$i]['id'];
                    if (isset($respuestas_map[$sem_id])) {
                        $rows[$i]['respuesta'] = $respuestas_map[$sem_id];
                    }
                }
            }
        }
        mysqli_stmt_close($stmt);

        return $rows;
    }
}

if (!function_exists('rsu_api_semester_audit_compare_rows')) {
    function rsu_api_semester_audit_compare_rows($expected_rows, $actual_rows)
    {
        $expected_map = array();
        $actual_map = array();
        $diffs = array();

        $i = 0;
        for ($i = 0; $i < count($expected_rows); $i++) {
            $r = $expected_rows[$i];
            $key = ((int)$r['anio']) . '|' . (string)$r['periodo'] . '|' . (string)$r['tipo'];
            $expected_map[$key] = $r;
        }

        for ($i = 0; $i < count($actual_rows); $i++) {
            $r = $actual_rows[$i];
            $key = ((int)$r['anio']) . '|' . (string)$r['periodo'] . '|' . (string)$r['tipo'];
            $actual_map[$key] = $r;
        }

        foreach ($expected_map as $key => $exp) {
            if (!isset($actual_map[$key])) {
                $diffs[] = array(
                    'tipo' => 'faltante_en_bd',
                    'clave' => $key,
                    'esperado' => $exp,
                    'actual' => null
                );
                continue;
            }

            $act = $actual_map[$key];
            $numero_exp = isset($exp['numero']) ? $exp['numero'] : null;
            $numero_act = isset($act['numero']) ? $act['numero'] : null;

            if (
                (string)$exp['fecha_inicio'] !== (string)$act['fecha_inicio'] ||
                (string)$exp['fecha_fin'] !== (string)$act['fecha_fin'] ||
                $numero_exp !== $numero_act
            ) {
                $diffs[] = array(
                    'tipo' => 'dato_distinto',
                    'clave' => $key,
                    'esperado' => $exp,
                    'actual' => $act
                );
            }
        }

        foreach ($actual_map as $key => $act) {
            if (!isset($expected_map[$key])) {
                $diffs[] = array(
                    'tipo' => 'extra_en_bd',
                    'clave' => $key,
                    'esperado' => null,
                    'actual' => $act
                );
            }
        }

        return $diffs;
    }
}

if (!function_exists('rsu_api_semester_audit_fetch_projects_by_ids')) {
    function rsu_api_semester_audit_fetch_projects_by_ids($conexion, $project_ids)
    {
        $items = array();
        if (!$conexion instanceof mysqli) {
            return $items;
        }

        $in_clause = function_exists('rsu_api_projects_build_in_clause')
            ? rsu_api_projects_build_in_clause($project_ids)
            : '';
        if ($in_clause === '') {
            return $items;
        }

        $sql = "SELECT id, p2, fecha_inicio, fecha_fin
                FROM proyectos
                WHERE id IN (" . $in_clause . ")
                ORDER BY id DESC";
        $res = mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return $items;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $id = (int)$row['id'];
            if ($id > 0) {
                $items[$id] = $row;
            }
        }
        mysqli_free_result($res);

        return $items;
    }
}

if (!function_exists('rsu_api_semester_audit_build_project_payload')) {
    function rsu_api_semester_audit_build_project_payload($conexion, $project_row)
    {
        $project_id = (int)$project_row['id'];
        $fecha_inicio = isset($project_row['fecha_inicio']) ? (string)$project_row['fecha_inicio'] : '';
        $fecha_fin = isset($project_row['fecha_fin']) ? (string)$project_row['fecha_fin'] : '';

        $expected = rsu_api_semester_audit_build_expected_rows($fecha_inicio, $fecha_fin);
        $actual_rows = rsu_api_semester_audit_fetch_actual_rows($conexion, $project_id);

        if (!$expected['ok']) {
            return array(
                'id' => $project_id,
                'titulo' => isset($project_row['p2']) ? $project_row['p2'] : null,
                'fecha_inicio' => $fecha_inicio,
                'fecha_fin' => $fecha_fin,
                'estado' => 'warning',
                'mensaje' => 'No se pudo calcular semestres esperados por fechas faltantes o invalidas.',
                'resumen' => array(
                    'total_esperado' => 0,
                    'total_bd_vigente' => count($actual_rows),
                    'diferencias' => count($actual_rows),
                    'desactualizado' => 1
                ),
                'semestres_esperados' => array(),
                'semestres_bd' => $actual_rows,
                'diferencias' => array()
            );
        }

        $expected_rows = $expected['rows'];
        $diffs = rsu_api_semester_audit_compare_rows($expected_rows, $actual_rows);
        $desactualizado = count($diffs) > 0 ? 1 : 0;

        return array(
            'id' => $project_id,
            'titulo' => isset($project_row['p2']) ? $project_row['p2'] : null,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'estado' => $desactualizado ? 'warning' : 'ok',
            'mensaje' => $desactualizado
                ? 'Semestres desactualizados: revisar diferencias entre fechas del proyecto y tabla de semestres.'
                : 'Semestres alineados con las fechas del proyecto.',
            'resumen' => array(
                'total_esperado' => count($expected_rows),
                'total_bd_vigente' => count($actual_rows),
                'diferencias' => count($diffs),
                'desactualizado' => $desactualizado
            ),
            'semestres_esperados' => $expected_rows,
            'semestres_bd' => $actual_rows,
            'diferencias' => $diffs
        );
    }
}

if (!function_exists('rsu_api_project_semesters_audit_get')) {
    function rsu_api_project_semesters_audit_get($id_py, $id, $usuario)
    {
        $id_py = (int)$id_py;
        $id = (int)$id;
        $usuario = trim((string)$usuario);

        if ($id_py <= 0 && $id <= 0 && $usuario === '') {
            return array(
                'ok' => false,
                'error_code' => 'missing_filter',
                'error_message' => 'Debes indicar id_py o id/usuario.'
            );
        }

        $conexion = rsu_db_connect();
        if (!$conexion) {
            return array(
                'ok' => false,
                'error_code' => 'db_connection_error',
                'error_message' => 'No fue posible conectar con la base de datos.'
            );
        }

        $project_ids = array();
        $meta = array();
        $user_payload = null;

        if ($id_py > 0) {
            $project_ids[$id_py] = $id_py;
            $meta['search_mode'] = 'id_py';
            $meta['search_value'] = $id_py;
        } else {
            if (!function_exists('rsu_api_projects_fetch_user') || !function_exists('rsu_api_projects_fetch_project_ids')) {
                return array(
                    'ok' => false,
                    'error_code' => 'internal_error',
                    'error_message' => 'No se pudo cargar el modulo de proyectos.'
                );
            }

            $user = rsu_api_projects_fetch_user($conexion, $id, $usuario);
            if (!is_array($user)) {
                return array(
                    'ok' => false,
                    'error_code' => 'not_found',
                    'error_message' => 'No se encontro el usuario solicitado.'
                );
            }

            $ids = rsu_api_projects_fetch_project_ids($conexion, $user);
            $i = 0;
            for ($i = 0; $i < count($ids); $i++) {
                $v = (int)$ids[$i];
                if ($v > 0) {
                    $project_ids[$v] = $v;
                }
            }

            $user_payload = array(
                'id' => (int)(isset($user['id']) ? $user['id'] : 0),
                'usuario' => isset($user['usuario']) ? (string)$user['usuario'] : '',
                'nombres' => isset($user['nombres']) ? $user['nombres'] : null,
                'apellidos' => isset($user['apellidos']) ? $user['apellidos'] : null,
                'id_py_actual' => (int)(isset($user['id_py']) ? $user['id_py'] : 0)
            );

            $meta['search_mode'] = $id > 0 ? 'id' : 'usuario';
            $meta['search_value'] = $id > 0 ? $id : $usuario;
        }

        $projects = rsu_api_semester_audit_fetch_projects_by_ids($conexion, array_values($project_ids));
        if ($id_py > 0 && !isset($projects[$id_py])) {
            return array(
                'ok' => false,
                'error_code' => 'not_found',
                'error_message' => 'No se encontro el proyecto indicado por id_py.'
            );
        }

        $items = array();
        $total_desactualizados = 0;
        foreach ($projects as $project_row) {
            $payload = rsu_api_semester_audit_build_project_payload($conexion, $project_row);
            if ((int)$payload['resumen']['desactualizado'] === 1) {
                $total_desactualizados++;
            }
            $items[] = $payload;
        }

        return array(
            'ok' => true,
            'data' => array(
                'usuario' => $user_payload,
                'proyectos' => $items,
                'resumen' => array(
                    'total_proyectos' => count($items),
                    'total_desactualizados' => $total_desactualizados
                )
            ),
            'meta' => $meta
        );
    }
}
