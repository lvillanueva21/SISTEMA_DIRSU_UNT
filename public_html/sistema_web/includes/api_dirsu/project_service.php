<?php
/**
 * Servicio de proyectos para coordinadores en API Dirsu.
 * Entrega proyectos por usuario/id con periodo y total de semestres.
 */

include_once __DIR__ . '/../db_connection.php';

if (!function_exists('rsu_api_projects_value')) {
    function rsu_api_projects_value($row, $key)
    {
        if (!is_array($row) || !isset($row[$key])) {
            return null;
        }
        return $row[$key];
    }
}

if (!function_exists('rsu_api_projects_table_exists')) {
    function rsu_api_projects_table_exists($conexion, $table_name)
    {
        static $cache = array();

        if (!$conexion instanceof mysqli) {
            return false;
        }

        $table_name = trim((string)$table_name);
        if ($table_name === '') {
            return false;
        }

        if (isset($cache[$table_name])) {
            return $cache[$table_name];
        }

        $safe = mysqli_real_escape_string($conexion, $table_name);
        $sql = "SHOW TABLES LIKE '" . $safe . "'";
        $res = @mysqli_query($conexion, $sql);

        $exists = ($res instanceof mysqli_result) && ($res->num_rows > 0);
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }

        $cache[$table_name] = $exists;
        return $exists;
    }
}

if (!function_exists('rsu_api_projects_column_exists')) {
    function rsu_api_projects_column_exists($conexion, $table_name, $column_name)
    {
        static $cache = array();

        if (!$conexion instanceof mysqli) {
            return false;
        }

        $table_name = trim((string)$table_name);
        $column_name = trim((string)$column_name);
        if ($table_name === '' || $column_name === '') {
            return false;
        }

        $cache_key = $table_name . '::' . $column_name;
        if (isset($cache[$cache_key])) {
            return $cache[$cache_key];
        }

        if (!rsu_api_projects_table_exists($conexion, $table_name)) {
            $cache[$cache_key] = false;
            return false;
        }

        $safe_table = mysqli_real_escape_string($conexion, $table_name);
        $safe_col = mysqli_real_escape_string($conexion, $column_name);
        $sql = "SHOW COLUMNS FROM `" . $safe_table . "` LIKE '" . $safe_col . "'";
        $res = @mysqli_query($conexion, $sql);

        $exists = ($res instanceof mysqli_result) && ($res->num_rows > 0);
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }

        $cache[$cache_key] = $exists;
        return $exists;
    }
}

if (!function_exists('rsu_api_projects_parse_date')) {
    function rsu_api_projects_parse_date($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }

        $formats = array('d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d');
        foreach ($formats as $format) {
            $date = DateTime::createFromFormat($format, $value);
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

if (!function_exists('rsu_api_projects_semesters_from_dates')) {
    function rsu_api_projects_semesters_from_dates($fecha_inicio, $fecha_fin)
    {
        $fi = rsu_api_projects_parse_date($fecha_inicio);
        $ff = rsu_api_projects_parse_date($fecha_fin);
        if (!$fi instanceof DateTime || !$ff instanceof DateTime) {
            return 0;
        }
        if ($fi > $ff) {
            return 0;
        }

        $fi_year = (int)$fi->format('Y');
        $fi_sem = ((int)$fi->format('n') <= 6) ? 1 : 2;

        $ff_year = (int)$ff->format('Y');
        $ff_sem = ((int)$ff->format('n') <= 6) ? 1 : 2;

        $index_ini = ($fi_year * 2) + $fi_sem;
        $index_fin = ($ff_year * 2) + $ff_sem;

        $total = $index_fin - $index_ini + 1;
        return ($total > 0) ? $total : 0;
    }
}

if (!function_exists('rsu_api_projects_fetch_user')) {
    function rsu_api_projects_fetch_user($conexion, $id, $usuario)
    {
        $id = (int)$id;
        $usuario = trim((string)$usuario);

        $sql = "SELECT u.id, u.usuario, u.id_rol, u.nombres, u.apellidos, u.id_py, r.nombre AS rol_nombre
                FROM usuarios u
                LEFT JOIN rol r ON r.id = u.id_rol
                WHERE ";
        $bind_type = '';
        $bind_value = null;

        if ($id > 0) {
            $sql .= "u.id = ? LIMIT 1";
            $bind_type = 'i';
            $bind_value = $id;
        } else {
            $sql .= "u.usuario = ? LIMIT 1";
            $bind_type = 's';
            $bind_value = $usuario;
        }

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return null;
        }
        mysqli_stmt_bind_param($stmt, $bind_type, $bind_value);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);

        return is_array($row) ? $row : null;
    }
}

if (!function_exists('rsu_api_projects_fetch_project_ids')) {
    function rsu_api_projects_fetch_project_ids($conexion, $user_row)
    {
        $ids = array();
        $user_id = (int)rsu_api_projects_value($user_row, 'id');
        $current_id_py = (int)rsu_api_projects_value($user_row, 'id_py');

        if ($user_id > 0 && rsu_api_projects_table_exists($conexion, 'usuarios_proyectos')) {
            $sql = "SELECT id_proyecto FROM usuarios_proyectos WHERE id_usuario = ?";
            $stmt = @mysqli_prepare($conexion, $sql);
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, 'i', $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($res instanceof mysqli_result) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $id_proyecto = (int)rsu_api_projects_value($row, 'id_proyecto');
                        if ($id_proyecto > 0) {
                            $ids[$id_proyecto] = $id_proyecto;
                        }
                    }
                    mysqli_free_result($res);
                }
                mysqli_stmt_close($stmt);
            }
        }

        if ($current_id_py > 0) {
            $ids[$current_id_py] = $current_id_py;
        }

        return array_values($ids);
    }
}

if (!function_exists('rsu_api_projects_build_in_clause')) {
    function rsu_api_projects_build_in_clause($ids)
    {
        $source = (array)$ids;
        $clean = array();
        $i = 0;
        for ($i = 0; $i < count($source); $i++) {
            $v = (int)$source[$i];
            if ($v > 0) {
                $clean[$v] = $v;
            }
        }

        if (empty($clean)) {
            return '';
        }

        return implode(',', array_values($clean));
    }
}

if (!function_exists('rsu_api_projects_fetch_periods_map')) {
    function rsu_api_projects_fetch_periods_map($conexion, $project_ids)
    {
        $map = array();
        $in_clause = rsu_api_projects_build_in_clause($project_ids);
        if ($in_clause === '') {
            return $map;
        }

        if (!rsu_api_projects_table_exists($conexion, 'proyectos_periodo') || !rsu_api_projects_table_exists($conexion, 'periodos')) {
            return $map;
        }

        $sql = "SELECT pp.id_py, pp.id_periodo, per.nombre
                FROM proyectos_periodo pp
                LEFT JOIN periodos per ON per.id = pp.id_periodo
                WHERE pp.id_py IN (" . $in_clause . ")
                ORDER BY pp.id_periodo DESC";
        $res = @mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return $map;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $id_py = (int)rsu_api_projects_value($row, 'id_py');
            $id_periodo = (int)rsu_api_projects_value($row, 'id_periodo');
            $nombre = rsu_api_projects_value($row, 'nombre');
            if ($id_py <= 0) {
                continue;
            }
            if (!isset($map[$id_py])) {
                $map[$id_py] = array();
            }

            $unique_key = $id_periodo . '::' . (string)$nombre;
            $already = false;
            $i = 0;
            for ($i = 0; $i < count($map[$id_py]); $i++) {
                $existing = $map[$id_py][$i];
                $existing_key = ((int)$existing['id']) . '::' . (string)$existing['nombre'];
                if ($existing_key === $unique_key) {
                    $already = true;
                    break;
                }
            }
            if (!$already) {
                $map[$id_py][] = array(
                    'id' => ($id_periodo > 0) ? $id_periodo : null,
                    'nombre' => ($nombre !== null && trim((string)$nombre) !== '') ? (string)$nombre : null
                );
            }
        }
        mysqli_free_result($res);

        return $map;
    }
}

if (!function_exists('rsu_api_projects_fetch_semester_map')) {
    function rsu_api_projects_fetch_semester_map($conexion, $project_ids)
    {
        $map = array();
        $in_clause = rsu_api_projects_build_in_clause($project_ids);
        if ($in_clause === '') {
            return $map;
        }

        if (!rsu_api_projects_table_exists($conexion, 'sm_proyecto_semestres')) {
            return $map;
        }

        $where = "id_py IN (" . $in_clause . ")";
        if (rsu_api_projects_column_exists($conexion, 'sm_proyecto_semestres', 'tipo')) {
            $where .= " AND tipo = 'semestral'";
        }
        if (rsu_api_projects_column_exists($conexion, 'sm_proyecto_semestres', 'vigente')) {
            $where .= " AND vigente = 1";
        }

        $sql = "SELECT id_py, COUNT(*) AS total
                FROM sm_proyecto_semestres
                WHERE " . $where . "
                GROUP BY id_py";
        $res = @mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return $map;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $id_py = (int)rsu_api_projects_value($row, 'id_py');
            $total = (int)rsu_api_projects_value($row, 'total');
            if ($id_py > 0) {
                $map[$id_py] = $total;
            }
        }
        mysqli_free_result($res);

        return $map;
    }
}

if (!function_exists('rsu_api_user_projects_get')) {
    function rsu_api_user_projects_get($id, $usuario)
    {
        $id = (int)$id;
        $usuario = trim((string)$usuario);

        if ($id <= 0 && $usuario === '') {
            return array(
                'ok' => false,
                'error_code' => 'missing_filter',
                'error_message' => 'Debes indicar id o usuario para la consulta.'
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

        $user = rsu_api_projects_fetch_user($conexion, $id, $usuario);
        if (!$user) {
            return array(
                'ok' => false,
                'error_code' => 'not_found',
                'error_message' => 'No se encontro el usuario solicitado.'
            );
        }

        $id_rol = (int)rsu_api_projects_value($user, 'id_rol');
        $usuario_codigo = trim((string)rsu_api_projects_value($user, 'usuario'));
        if ($id_rol !== 2 || !preg_match('/^\d{4}$/', $usuario_codigo)) {
            return array(
                'ok' => false,
                'error_code' => 'not_coordinator',
                'error_message' => 'El usuario consultado no corresponde a un coordinador activo.'
            );
        }

        $project_ids = rsu_api_projects_fetch_project_ids($conexion, $user);
        $projects = array();

        if (!empty($project_ids)) {
            $in_clause = rsu_api_projects_build_in_clause($project_ids);
            if ($in_clause !== '') {
                $sql = "SELECT id, p2, fecha_inicio, fecha_fin
                        FROM proyectos
                        WHERE id IN (" . $in_clause . ")
                        ORDER BY id DESC";
                $res = @mysqli_query($conexion, $sql);
                if ($res instanceof mysqli_result) {
                    while ($row = mysqli_fetch_assoc($res)) {
                        $project_id = (int)rsu_api_projects_value($row, 'id');
                        if ($project_id > 0) {
                            $projects[$project_id] = $row;
                        }
                    }
                    mysqli_free_result($res);
                }
            }
        }

        $periods_map = rsu_api_projects_fetch_periods_map($conexion, array_keys($projects));
        $semesters_map = rsu_api_projects_fetch_semester_map($conexion, array_keys($projects));

        $items = array();
        $current_project_id = (int)rsu_api_projects_value($user, 'id_py');
        foreach ($projects as $project_id => $project_row) {
            $periods = isset($periods_map[$project_id]) ? $periods_map[$project_id] : array();
            $period_main = count($periods) > 0 ? $periods[0] : array('id' => null, 'nombre' => null);

            $semesters_total = isset($semesters_map[$project_id]) ? (int)$semesters_map[$project_id] : 0;
            if ($semesters_total <= 0) {
                $semesters_total = rsu_api_projects_semesters_from_dates(
                    rsu_api_projects_value($project_row, 'fecha_inicio'),
                    rsu_api_projects_value($project_row, 'fecha_fin')
                );
            }

            $items[] = array(
                'id' => $project_id,
                'titulo' => rsu_api_projects_value($project_row, 'p2'),
                'fecha_inicio' => rsu_api_projects_value($project_row, 'fecha_inicio'),
                'fecha_fin' => rsu_api_projects_value($project_row, 'fecha_fin'),
                'periodo' => $period_main,
                'periodos' => $periods,
                'semestres_total' => $semesters_total,
                'es_proyecto_activo' => ($current_project_id > 0 && $current_project_id === $project_id) ? 1 : 0
            );
        }

        return array(
            'ok' => true,
            'data' => array(
                'usuario' => array(
                    'id' => (int)rsu_api_projects_value($user, 'id'),
                    'usuario' => $usuario_codigo,
                    'nombres' => rsu_api_projects_value($user, 'nombres'),
                    'apellidos' => rsu_api_projects_value($user, 'apellidos'),
                    'rol' => array(
                        'id' => $id_rol,
                        'nombre' => rsu_api_projects_value($user, 'rol_nombre')
                    ),
                    'id_py_actual' => $current_project_id > 0 ? $current_project_id : 0
                ),
                'proyectos' => $items,
                'resumen' => array(
                    'total_proyectos' => count($items)
                )
            ),
            'meta' => array(
                'search_mode' => $id > 0 ? 'id' : 'usuario',
                'search_value' => $id > 0 ? $id : $usuario_codigo
            )
        );
    }
}
