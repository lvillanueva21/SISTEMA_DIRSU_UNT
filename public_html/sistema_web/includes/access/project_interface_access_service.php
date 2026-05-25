<?php
/**
 * Servicio central de control de acceso por interfaz.
 * Reglas:
 * - Solo coordinador (id_rol = 2).
 * - Proyecto en sesión.
 * - Validación por período activo + cronograma activo + regla de interfaz activa.
 * - F1 exige semestre objetivo "presentacion".
 * - F3 exige semestre objetivo "semestral".
 */

include_once __DIR__ . '/../config.php';
include_once __DIR__ . '/../db_connection.php';

if (!function_exists('rsu_access_start_session')) {
    function rsu_access_start_session()
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
    }
}

if (!function_exists('rsu_access_defs')) {
    function rsu_access_defs()
    {
        return array(
            'F1-GENERALIDADES' => array('nombre' => 'Generalidades', 'ruta' => 'vistas/datos_principales.php', 'tipo_semestre' => 'presentacion', 'tipo_cronograma' => 1, 'orden' => 1),
            'F1-PLAN' => array('nombre' => 'Plan de proyecto', 'ruta' => 'vistas/desarrollo_informe.php', 'tipo_semestre' => 'presentacion', 'tipo_cronograma' => 1, 'orden' => 2),
            'F1-ANEXOS' => array('nombre' => 'Anexos', 'ruta' => 'vistas/anexos.php', 'tipo_semestre' => 'presentacion', 'tipo_cronograma' => 1, 'orden' => 3),
            'F3-SEMESTRAL' => array('nombre' => 'Informe semestral', 'ruta' => 'semestral/index.php', 'tipo_semestre' => 'semestral', 'tipo_cronograma' => 2, 'orden' => 4)
        );
    }
}

if (!function_exists('rsu_access_tz')) {
    function rsu_access_tz($timezone_name)
    {
        $timezone_name = trim((string)$timezone_name);
        if ($timezone_name === '') {
            $timezone_name = 'America/Lima';
        }
        try {
            return new DateTimeZone($timezone_name);
        } catch (Throwable $e) {
            return new DateTimeZone('America/Lima');
        }
    }
}

if (!function_exists('rsu_access_window_status')) {
    function rsu_access_window_status($inicio, $fin, $timezone_name)
    {
        $inicio = trim((string)$inicio);
        $fin = trim((string)$fin);
        if ($inicio === '' || $fin === '') {
            return 'sin_fechas';
        }

        $tz = rsu_access_tz($timezone_name);
        try {
            $dt_inicio = new DateTimeImmutable($inicio, $tz);
            $dt_fin = new DateTimeImmutable($fin, $tz);
            $now = new DateTimeImmutable('now', $tz);
        } catch (Throwable $e) {
            return 'sin_fechas';
        }

        if ($now < $dt_inicio) {
            return 'proximo';
        }
        if ($now > $dt_fin) {
            return 'cerrado';
        }
        return 'abierto';
    }
}

if (!function_exists('rsu_access_period_parse')) {
    function rsu_access_period_parse($period_name)
    {
        $period_name = trim((string)$period_name);
        if (!preg_match('/^(\d{4})\s*-\s*(I|II)$/u', $period_name, $m)) {
            return null;
        }
        return array('anio' => (int)$m[1], 'periodo' => strtoupper((string)$m[2]));
    }
}

if (!function_exists('rsu_access_period_sort_key')) {
    function rsu_access_period_sort_key($period_name)
    {
        $p = rsu_access_period_parse($period_name);
        if (!is_array($p)) {
            return 0;
        }
        return ((int)$p['anio'] * 10) + ($p['periodo'] === 'II' ? 2 : 1);
    }
}

if (!function_exists('rsu_access_date_parse')) {
    function rsu_access_date_parse($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return null;
        }
        $formats = array('d/m/Y', 'Y-m-d', 'd-m-Y', 'Y/m/d');
        foreach ($formats as $format) {
            $d = DateTimeImmutable::createFromFormat($format, $value, new DateTimeZone('America/Lima'));
            if ($d instanceof DateTimeImmutable) {
                return $d->setTime(0, 0, 0);
            }
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return null;
        }
        return (new DateTimeImmutable('@' . $ts))->setTimezone(new DateTimeZone('America/Lima'))->setTime(0, 0, 0);
    }
}

if (!function_exists('rsu_access_build_project_semesters')) {
    function rsu_access_build_project_semesters($fecha_inicio, $fecha_fin)
    {
        $fi = rsu_access_date_parse($fecha_inicio);
        $ff = rsu_access_date_parse($fecha_fin);
        if (!($fi instanceof DateTimeImmutable) || !($ff instanceof DateTimeImmutable) || $fi > $ff) {
            return array();
        }

        $semesterOf = function (DateTimeImmutable $date) {
            $y = (int)$date->format('Y');
            $p = ((int)$date->format('n') <= 6) ? 'I' : 'II';
            return array($y, $p);
        };
        $next = function ($y, $p) {
            return ($p === 'I') ? array($y, 'II') : array($y + 1, 'I');
        };
        $limits = function ($y, $p) {
            if ($p === 'I') {
                return array(new DateTimeImmutable($y . '-01-01'), new DateTimeImmutable($y . '-06-30'));
            }
            return array(new DateTimeImmutable($y . '-07-01'), new DateTimeImmutable($y . '-12-31'));
        };

        list($yi, $pi) = $semesterOf($fi);
        list($yf, $pf) = $semesterOf($ff);

        $cursorY = $yi;
        $cursorP = $pi;
        $list = array();
        while (true) {
            $list[] = array($cursorY, $cursorP);
            if ($cursorY === $yf && $cursorP === $pf) {
                break;
            }
            list($cursorY, $cursorP) = $next($cursorY, $cursorP);
        }

        $rows = array();
        $numero = 1;
        $total = count($list);
        for ($i = 0; $i < $total; $i++) {
            $y = (int)$list[$i][0];
            $p = (string)$list[$i][1];
            list($li, $lf) = $limits($y, $p);
            $ini = date('Y-m-d', max($li->getTimestamp(), $fi->getTimestamp()));
            $fin = date('Y-m-d', min($lf->getTimestamp(), $ff->getTimestamp()));
            $first = ($i === 0);
            $last = ($i === ($total - 1));

            if ($first) {
                $rows[] = array('anio' => $y, 'periodo' => $p, 'semestre' => $y . '-' . $p, 'tipo' => 'presentacion', 'numero' => null, 'final' => 0, 'titulo' => 'Presentación de proyecto', 'fecha_inicio' => $ini, 'fecha_fin' => $fin);
            }

            $rows[] = array('anio' => $y, 'periodo' => $p, 'semestre' => $y . '-' . $p, 'tipo' => 'semestral', 'numero' => $numero, 'final' => $last ? 1 : 0, 'titulo' => $last ? sprintf('Informe Semestral %02d (Informe Final)', $numero) : sprintf('Informe Semestral %02d', $numero), 'fecha_inicio' => $ini, 'fecha_fin' => $fin);
            $numero++;
        }
        return $rows;
    }
}

if (!function_exists('rsu_access_has_project_semester')) {
    function rsu_access_has_project_semester($rows, $anio, $periodo, $tipo)
    {
        foreach ((array)$rows as $row) {
            if ((int)$row['anio'] === (int)$anio && (string)$row['periodo'] === (string)$periodo && (string)$row['tipo'] === (string)$tipo) {
                return true;
            }
        }
        return false;
    }
}

if (!function_exists('rsu_access_reason_message')) {
    function rsu_access_reason_message($reason_code, $ctx)
    {
        $period = isset($ctx['periodo_resuelto']['nombre']) ? (string)$ctx['periodo_resuelto']['nombre'] : '';
        if ($reason_code === 'allowed') return 'Acceso habilitado. Cumples las condiciones de período y cronograma.';
        if ($reason_code === 'forbidden_role') return 'Esta sección está disponible solo para coordinadores de proyecto.';
        if ($reason_code === 'missing_project_session') return 'No tienes proyecto seleccionado en sesión.';
        if ($reason_code === 'project_not_found') return 'No se encontró el proyecto actual.';
        if ($reason_code === 'no_active_periods') return 'No hay períodos activos definidos por la Dirección RSU.';
        if ($reason_code === 'no_active_schedule_type') return 'No hay cronogramas activos del tipo requerido para esta página.';
        if ($reason_code === 'no_active_interface_rule') return $period !== '' ? 'El período activo ' . $period . ' no tiene regla activa para esta interfaz.' : 'No existe regla activa para esta interfaz.';
        if ($reason_code === 'project_outside_active_period') return $period !== '' ? 'Fuera de tiempo: tu proyecto no pertenece al período ' . $period . ' activado por la Dirección RSU.' : 'Fuera de tiempo: tu proyecto no pertenece al período activo.';
        if ($reason_code === 'outside_window_proximo') return 'Aún no inicia la ventana habilitada para esta interfaz.';
        if ($reason_code === 'outside_window_cerrado') return 'La ventana habilitada para esta interfaz ya cerró.';
        if ($reason_code === 'outside_window_sin_fechas') return 'La interfaz no tiene fechas válidas configuradas.';
        if ($reason_code === 'invalid_interface_code') return 'La interfaz solicitada no está soportada.';
        return 'No fue posible habilitar el acceso.';
    }
}

if (!function_exists('rsu_project_interface_access_evaluate')) {
    function rsu_project_interface_access_evaluate($conexion, $interface_code, $options = array())
    {
        if (!$conexion instanceof mysqli) $conexion = rsu_db_connect();
        rsu_access_start_session();

        $defs = rsu_access_defs();
        $interface_code = trim((string)$interface_code);
        $def = isset($defs[$interface_code]) ? $defs[$interface_code] : null;

        $tz_name = isset($options['timezone']) ? trim((string)$options['timezone']) : 'America/Lima';
        if ($tz_name === '') $tz_name = 'America/Lima';

        $id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
        $id_py = isset($options['id_py']) ? (int)$options['id_py'] : 0;
        if ($id_py <= 0) $id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;

        $result = array(
            'ok' => true,
            'allow' => false,
            'reason_code' => 'unknown',
            'reason_message' => '',
            'timezone' => $tz_name,
            'evaluated_at' => date('Y-m-d H:i:s'),
            'user' => array('usuario' => isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '', 'id_rol' => $id_rol, 'id_py' => $id_py),
            'interface' => array('codigo' => $interface_code, 'nombre' => is_array($def) ? (string)$def['nombre'] : '', 'ruta' => is_array($def) ? (string)$def['ruta'] : '', 'tipo_semestre' => is_array($def) ? (string)$def['tipo_semestre'] : '', 'tipo_cronograma' => is_array($def) ? (int)$def['tipo_cronograma'] : 0),
            'proyecto' => null,
            'semestres_proyecto' => array(),
            'periodo_resuelto' => null,
            'cronograma_resuelto' => null,
            'interfaz_resuelta' => null,
            'interfaces_activas_periodo' => array(),
            'trace' => array()
        );

        if (!$conexion instanceof mysqli) {
            $result['ok'] = false; $result['reason_code'] = 'db_connection_error'; $result['reason_message'] = 'No fue posible conectar con la base de datos.'; return $result;
        }
        if (!is_array($def)) {
            $result['reason_code'] = 'invalid_interface_code'; $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result); return $result;
        }
        if ($id_rol !== 2) {
            $result['reason_code'] = 'forbidden_role'; $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result); return $result;
        }
        if ($id_py <= 0) {
            $result['reason_code'] = 'missing_project_session'; $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result); return $result;
        }

        $stmt = mysqli_prepare($conexion, "SELECT id, p2, fecha_inicio, fecha_fin FROM proyectos WHERE id = ? LIMIT 1");
        if (!$stmt) { $result['ok'] = false; $result['reason_code'] = 'db_query_error'; $result['reason_message'] = 'No se pudo consultar el proyecto.'; return $result; }
        mysqli_stmt_bind_param($stmt, 'i', $id_py); mysqli_stmt_execute($stmt); $resp = mysqli_stmt_get_result($stmt); $project = ($resp instanceof mysqli_result) ? mysqli_fetch_assoc($resp) : null; if ($resp instanceof mysqli_result) mysqli_free_result($resp); mysqli_stmt_close($stmt);
        if (!is_array($project)) { $result['reason_code'] = 'project_not_found'; $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result); return $result; }

        $result['proyecto'] = array('id' => (int)$project['id'], 'titulo' => (string)$project['p2'], 'fecha_inicio' => (string)$project['fecha_inicio'], 'fecha_fin' => (string)$project['fecha_fin']);
        $project_semesters = rsu_access_build_project_semesters($project['fecha_inicio'], $project['fecha_fin']);
        $result['semestres_proyecto'] = $project_semesters;
        $has_project_semesters = !empty($project_semesters);
        $project_has_blank_dates = (trim((string)$project['fecha_inicio']) === '' && trim((string)$project['fecha_fin']) === '');

        $periods = array(); $res_periods = mysqli_query($conexion, "SELECT id, nombre, DATE_FORMAT(fecha_inicio, '%Y-%m-%d') AS fecha_inicio, DATE_FORMAT(fecha_fin, '%Y-%m-%d') AS fecha_fin FROM periodos WHERE activo = 1 ORDER BY fecha_inicio DESC, id DESC");
        if (!($res_periods instanceof mysqli_result)) { $result['ok'] = false; $result['reason_code'] = 'db_query_error'; $result['reason_message'] = 'No se pudo consultar períodos activos.'; return $result; }
        while ($r = mysqli_fetch_assoc($res_periods)) $periods[] = $r; mysqli_free_result($res_periods);
        if (empty($periods)) { $result['reason_code'] = 'no_active_periods'; $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result); return $result; }

        $period_ids = array(); $period_names = array();
        foreach ($periods as $p) { $period_ids[] = (int)$p['id']; $period_names[] = (string)$p['nombre']; }
        $in_ids = implode(',', array_values(array_unique(array_filter($period_ids))));
        $cronos = array();
        if ($in_ids !== '') {
            $sql_cronos = "SELECT id, id_periodo, tipo, DATE_FORMAT(apertura, '%Y-%m-%d %H:%i:%s') AS apertura, DATE_FORMAT(cierre, '%Y-%m-%d %H:%i:%s') AS cierre FROM sm_cronogramas WHERE activo = 1 AND tipo IN (1,2) AND id_periodo IN (" . $in_ids . ") ORDER BY id_periodo ASC, tipo ASC, apertura DESC, id DESC";
            $res_cronos = mysqli_query($conexion, $sql_cronos);
            if (!($res_cronos instanceof mysqli_result)) { $result['ok'] = false; $result['reason_code'] = 'db_query_error'; $result['reason_message'] = 'No se pudo consultar cronogramas activos.'; return $result; }
            while ($c = mysqli_fetch_assoc($res_cronos)) { $pid = (int)$c['id_periodo']; $tipo = (int)$c['tipo']; if (!isset($cronos[$pid])) $cronos[$pid] = array(); if (!isset($cronos[$pid][$tipo])) $cronos[$pid][$tipo] = array(); $cronos[$pid][$tipo][] = $c; }
            mysqli_free_result($res_cronos);
        }

        $rules = array();
        if (!empty($period_names)) {
            $codes = array_keys($defs);
            $ph_periods = implode(',', array_fill(0, count($period_names), '?'));
            $ph_codes = implode(',', array_fill(0, count($codes), '?'));
            $sql_rules = "SELECT id, periodo, codigo, descripcion, DATE_FORMAT(inicio, '%Y-%m-%d %H:%i:%s') AS inicio, DATE_FORMAT(fin, '%Y-%m-%d %H:%i:%s') AS fin FROM cronogramas WHERE estado = 1 AND periodo IN (" . $ph_periods . ") AND codigo IN (" . $ph_codes . ") ORDER BY periodo ASC, codigo ASC, id DESC";
            $stmt_rules = mysqli_prepare($conexion, $sql_rules);
            if (!$stmt_rules) { $result['ok'] = false; $result['reason_code'] = 'db_query_error'; $result['reason_message'] = 'No se pudo consultar reglas de interfaces.'; return $result; }
            $params = array_merge($period_names, $codes); $types = str_repeat('s', count($params)); mysqli_stmt_bind_param($stmt_rules, $types, ...$params); mysqli_stmt_execute($stmt_rules); $res_rules = mysqli_stmt_get_result($stmt_rules);
            if ($res_rules instanceof mysqli_result) { while ($rw = mysqli_fetch_assoc($res_rules)) { $pn = (string)$rw['periodo']; $cd = (string)$rw['codigo']; if (!isset($rules[$pn])) $rules[$pn] = array(); if (!isset($rules[$pn][$cd])) $rules[$pn][$cd] = $rw; } mysqli_free_result($res_rules); }
            mysqli_stmt_close($stmt_rules);
        }

        $snapshots = array();
        foreach ($periods as $period) {
            $period_id = (int)$period['id']; $period_name = (string)$period['nombre']; $parsed = rsu_access_period_parse($period_name);
            $cron_items = isset($cronos[$period_id][(int)$def['tipo_cronograma']]) ? $cronos[$period_id][(int)$def['tipo_cronograma']] : array();
            $rule = isset($rules[$period_name][$interface_code]) ? $rules[$period_name][$interface_code] : null;
            $window = rsu_access_window_status(is_array($rule) ? $rule['inicio'] : '', is_array($rule) ? $rule['fin'] : '', $tz_name);
            $match = is_array($parsed) ? rsu_access_has_project_semester($project_semesters, $parsed['anio'], $parsed['periodo'], (string)$def['tipo_semestre']) : false;
            $fallback_presentacion_blank_dates = false;
            if (
                !$match
                && !$has_project_semesters
                && (string)$def['tipo_semestre'] === 'presentacion'
                && $project_has_blank_dates
            ) {
                $match = true;
                $fallback_presentacion_blank_dates = true;
            }

            $active_interfaces = array();
            $ordered_defs = $defs;
            uasort($ordered_defs, function ($a, $b) { return (int)$a['orden'] <=> (int)$b['orden']; });
            foreach ($ordered_defs as $code => $d) {
                if (!isset($rules[$period_name][$code])) continue;
                $rw = $rules[$period_name][$code];
                $w = rsu_access_window_status($rw['inicio'], $rw['fin'], $tz_name);
                $active_interfaces[] = array('codigo' => $code, 'nombre' => (string)$d['nombre'], 'ruta' => (string)$d['ruta'], 'descripcion' => (string)$rw['descripcion'], 'inicio' => (string)$rw['inicio'], 'fin' => (string)$rw['fin'], 'ventana_estado' => $w);
            }

            $snapshots[] = array('periodo' => $period, 'sort_key' => rsu_access_period_sort_key($period_name), 'cron' => (count($cron_items) > 0 ? $cron_items[0] : null), 'rule' => $rule, 'window' => $window, 'match' => $match, 'candidate' => (count($cron_items) > 0 && is_array($rule)), 'interfaces_activas' => $active_interfaces, 'fallback_presentacion_blank_dates' => $fallback_presentacion_blank_dates);
        }

        usort($snapshots, function ($a, $b) {
            $aCand = !empty($a['candidate']) ? 1 : 0; $bCand = !empty($b['candidate']) ? 1 : 0;
            if ($aCand !== $bCand) return ($aCand < $bCand) ? 1 : -1;
            $aMatch = !empty($a['match']) ? 1 : 0; $bMatch = !empty($b['match']) ? 1 : 0;
            if ($aMatch !== $bMatch) return ($aMatch < $bMatch) ? 1 : -1;
            $rank = array('abierto' => 0, 'proximo' => 1, 'cerrado' => 2, 'sin_fechas' => 3);
            $aR = isset($rank[$a['window']]) ? $rank[$a['window']] : 3; $bR = isset($rank[$b['window']]) ? $rank[$b['window']] : 3;
            if ($aR !== $bR) return ($aR > $bR) ? 1 : -1;
            if ((int)$a['sort_key'] !== (int)$b['sort_key']) return ((int)$a['sort_key'] < (int)$b['sort_key']) ? 1 : -1;
            return 0;
        });

        $ref = !empty($snapshots) ? $snapshots[0] : null;
        if (is_array($ref)) {
            $result['periodo_resuelto'] = array('id' => (int)$ref['periodo']['id'], 'nombre' => (string)$ref['periodo']['nombre'], 'fecha_inicio' => (string)$ref['periodo']['fecha_inicio'], 'fecha_fin' => (string)$ref['periodo']['fecha_fin']);
            if (is_array($ref['cron'])) $result['cronograma_resuelto'] = array('id' => (int)$ref['cron']['id'], 'tipo' => (int)$ref['cron']['tipo'], 'apertura' => (string)$ref['cron']['apertura'], 'cierre' => (string)$ref['cron']['cierre'], 'ventana_estado' => rsu_access_window_status($ref['cron']['apertura'], $ref['cron']['cierre'], $tz_name));
            if (is_array($ref['rule'])) $result['interfaz_resuelta'] = array('codigo' => (string)$ref['rule']['codigo'], 'descripcion' => (string)$ref['rule']['descripcion'], 'inicio' => (string)$ref['rule']['inicio'], 'fin' => (string)$ref['rule']['fin'], 'ventana_estado' => (string)$ref['window']);
            $result['interfaces_activas_periodo'] = $ref['interfaces_activas'];
        }

        $has_candidate = (is_array($ref) && !empty($ref['candidate']));
        $has_cron_type = false;
        foreach ($snapshots as $s) { if (is_array($s['cron'])) { $has_cron_type = true; break; } }

        if (!$has_candidate) {
            $result['reason_code'] = $has_cron_type ? 'no_active_interface_rule' : 'no_active_schedule_type';
        } elseif (empty($ref['match'])) {
            $result['reason_code'] = 'project_outside_active_period';
        } elseif ($ref['window'] !== 'abierto') {
            $result['reason_code'] = ($ref['window'] === 'proximo') ? 'outside_window_proximo' : (($ref['window'] === 'cerrado') ? 'outside_window_cerrado' : 'outside_window_sin_fechas');
        } else {
            $result['allow'] = true;
            $result['reason_code'] = 'allowed';
        }

        $result['reason_message'] = rsu_access_reason_message($result['reason_code'], $result);
        $result['trace'] = array('total_periodos_activos' => count($periods), 'snapshots' => count($snapshots), 'has_candidate' => $has_candidate, 'has_cron_type' => $has_cron_type, 'has_project_semesters' => $has_project_semesters, 'project_has_blank_dates' => $project_has_blank_dates, 'used_fallback_presentacion_blank_dates' => (is_array($ref) && !empty($ref['fallback_presentacion_blank_dates'])));
        return $result;
    }
}
