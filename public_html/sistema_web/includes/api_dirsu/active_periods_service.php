<?php
/**
 * Snapshot de periodos activos para tarjetas informativas y validaciones de permisos.
 * Regla de negocio base:
 * - periodos activos: periodos.activo = 1
 * - cronogramas activos: sm_cronogramas.activo = 1
 * - formularios activos: sm_formularios.activo = 1
 * - items activos: sm_formulario_items.activo = 1
 */

include_once __DIR__ . '/../db_connection.php';

if (!function_exists('rsu_api_active_periods_value')) {
    function rsu_api_active_periods_value($row, $key)
    {
        if (!is_array($row) || !isset($row[$key])) {
            return null;
        }
        return $row[$key];
    }
}

if (!function_exists('rsu_api_active_periods_build_in_clause')) {
    function rsu_api_active_periods_build_in_clause($ids)
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

if (!function_exists('rsu_api_active_periods_tipo_nombre')) {
    function rsu_api_active_periods_tipo_nombre($tipo)
    {
        $tipo = (int)$tipo;
        if ($tipo === 1) {
            return 'Presentacion de Proyecto';
        }
        if ($tipo === 2) {
            return 'Informe Semestral';
        }
        return 'Otros';
    }
}

if (!function_exists('rsu_api_active_periods_window_status')) {
    function rsu_api_active_periods_window_status($apertura, $cierre, $timezone_name)
    {
        $apertura = trim((string)$apertura);
        $cierre = trim((string)$cierre);
        $timezone_name = trim((string)$timezone_name);
        if ($timezone_name === '') {
            $timezone_name = 'America/Lima';
        }

        try {
            $tz = new DateTimeZone($timezone_name);
        } catch (Throwable $e) {
            $tz = new DateTimeZone('America/Lima');
        }

        if ($apertura === '' || $cierre === '') {
            return 'sin_fechas';
        }

        try {
            $dt_apertura = new DateTimeImmutable($apertura, $tz);
            $dt_cierre = new DateTimeImmutable($cierre, $tz);
            $now = new DateTimeImmutable('now', $tz);
        } catch (Throwable $e) {
            return 'sin_fechas';
        }

        if ($now < $dt_apertura) {
            return 'proximo';
        }
        if ($now > $dt_cierre) {
            return 'cerrado';
        }

        return 'abierto';
    }
}

if (!function_exists('rsu_api_active_periods_interface_definitions')) {
    function rsu_api_active_periods_interface_definitions()
    {
        return array(
            'F1-GENERALIDADES' => array(
                'nombre' => 'Generalidades',
                'ruta' => '/vistas/datos_principales.php'
            ),
            'F1-PLAN' => array(
                'nombre' => 'Plan de proyecto',
                'ruta' => '/vistas/desarrollo_informe.php'
            ),
            'F1-ANEXOS' => array(
                'nombre' => 'Anexos',
                'ruta' => '/vistas/anexos.php'
            ),
            'F3-SEMESTRAL' => array(
                'nombre' => 'Informe semestral',
                'ruta' => '/semestral/index.php'
            )
        );
    }
}

if (!function_exists('rsu_api_active_periods_fetch_interface_rules')) {
    function rsu_api_active_periods_fetch_interface_rules($conexion, $period_names)
    {
        $map = array();
        $names_source = (array)$period_names;
        $names_clean = array();
        $i = 0;
        for ($i = 0; $i < count($names_source); $i++) {
            $name = trim((string)$names_source[$i]);
            if ($name !== '') {
                $names_clean[$name] = $name;
            }
        }

        if (empty($names_clean)) {
            return $map;
        }

        $definitions = rsu_api_active_periods_interface_definitions();
        $codes = array_keys($definitions);
        if (empty($codes)) {
            return $map;
        }

        $names_list = array_values($names_clean);
        $names_placeholders = implode(',', array_fill(0, count($names_list), '?'));
        $codes_placeholders = implode(',', array_fill(0, count($codes), '?'));

        $sql = "SELECT id,
                       periodo,
                       codigo,
                       descripcion,
                       DATE_FORMAT(inicio, '%Y-%m-%d %H:%i:%s') AS inicio,
                       DATE_FORMAT(fin, '%Y-%m-%d %H:%i:%s') AS fin,
                       estado
                FROM cronogramas
                WHERE periodo IN (" . $names_placeholders . ")
                  AND codigo IN (" . $codes_placeholders . ")
                ORDER BY periodo ASC, codigo ASC, actualizado_en DESC, id DESC";

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return null;
        }

        $params = array_merge($names_list, $codes);
        $types = str_repeat('s', count($params));
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res)) {
                $periodo = isset($row['periodo']) ? (string)$row['periodo'] : '';
                $codigo = isset($row['codigo']) ? (string)$row['codigo'] : '';
                if ($periodo === '' || $codigo === '') {
                    continue;
                }
                if (!isset($map[$periodo])) {
                    $map[$periodo] = array();
                }
                if (!isset($map[$periodo][$codigo])) {
                    $map[$periodo][$codigo] = $row;
                }
            }
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);

        return $map;
    }
}

if (!function_exists('rsu_api_active_periods_interface_payload')) {
    function rsu_api_active_periods_interface_payload($codigo, $definition, $row, $timezone_name)
    {
        $nombre = isset($definition['nombre']) ? (string)$definition['nombre'] : $codigo;
        $ruta = isset($definition['ruta']) ? (string)$definition['ruta'] : null;

        if (!is_array($row)) {
            return array(
                'codigo' => $codigo,
                'nombre' => $nombre,
                'ruta' => $ruta,
                'configurada' => false,
                'regla_activa' => false,
                'visible_ahora' => false,
                'estado_visualizacion' => 'sin_regla',
                'estado_texto' => 'Sin regla',
                'ventana_estado' => 'sin_fechas',
                'inicio' => null,
                'fin' => null,
                'descripcion' => null
            );
        }

        $inicio = (string)rsu_api_active_periods_value($row, 'inicio');
        $fin = (string)rsu_api_active_periods_value($row, 'fin');
        $regla_activa = (int)rsu_api_active_periods_value($row, 'estado') === 1;
        $ventana_estado = rsu_api_active_periods_window_status($inicio, $fin, $timezone_name);
        $visible_ahora = ($regla_activa && $ventana_estado === 'abierto');

        $estado_visualizacion = 'inactiva';
        $estado_texto = 'Inactiva';
        if ($regla_activa) {
            if ($visible_ahora) {
                $estado_visualizacion = 'visible_ahora';
                $estado_texto = 'Visible ahora';
            } else {
                $estado_visualizacion = 'activa_fuera_ventana';
                $estado_texto = 'Activa fuera de ventana';
            }
        }

        return array(
            'codigo' => $codigo,
            'nombre' => $nombre,
            'ruta' => $ruta,
            'configurada' => true,
            'regla_activa' => $regla_activa,
            'visible_ahora' => $visible_ahora,
            'estado_visualizacion' => $estado_visualizacion,
            'estado_texto' => $estado_texto,
            'ventana_estado' => $ventana_estado,
            'inicio' => ($inicio !== '' ? $inicio : null),
            'fin' => ($fin !== '' ? $fin : null),
            'descripcion' => (string)rsu_api_active_periods_value($row, 'descripcion')
        );
    }
}

if (!function_exists('rsu_api_active_periods_fetch_periods')) {
    function rsu_api_active_periods_fetch_periods($conexion, $id_periodo)
    {
        $id_periodo = (int)$id_periodo;
        $rows = array();

        $sql = "SELECT id,
                       nombre,
                       DATE_FORMAT(fecha_inicio, '%Y-%m-%d') AS fecha_inicio,
                       DATE_FORMAT(fecha_fin, '%Y-%m-%d') AS fecha_fin,
                       activo
                FROM periodos
                WHERE activo = 1";
        if ($id_periodo > 0) {
            $sql .= " AND id = ?";
        }
        $sql .= " ORDER BY fecha_inicio DESC, id DESC";

        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return null;
        }

        if ($id_periodo > 0) {
            mysqli_stmt_bind_param($stmt, 'i', $id_periodo);
        }

        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if ($res instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res)) {
                $rows[] = $row;
            }
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);

        return $rows;
    }
}

if (!function_exists('rsu_api_active_periods_fetch_cronograms')) {
    function rsu_api_active_periods_fetch_cronograms($conexion, $period_ids)
    {
        $items = array();
        $in_clause = rsu_api_active_periods_build_in_clause($period_ids);
        if ($in_clause === '') {
            return $items;
        }

        $sql = "SELECT id,
                       id_periodo,
                       tipo,
                       DATE_FORMAT(apertura, '%Y-%m-%d %H:%i:%s') AS apertura,
                       DATE_FORMAT(cierre, '%Y-%m-%d %H:%i:%s') AS cierre,
                       activo
                FROM sm_cronogramas
                WHERE activo = 1
                  AND tipo IN (1,2,3)
                  AND id_periodo IN (" . $in_clause . ")
                ORDER BY id_periodo ASC, tipo ASC, apertura ASC, id ASC";
        $res = mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return null;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
        mysqli_free_result($res);

        return $items;
    }
}

if (!function_exists('rsu_api_active_periods_fetch_forms')) {
    function rsu_api_active_periods_fetch_forms($conexion, $cronogram_ids)
    {
        $items = array();
        $in_clause = rsu_api_active_periods_build_in_clause($cronogram_ids);
        if ($in_clause === '') {
            return $items;
        }

        $sql = "SELECT id,
                       id_cronograma,
                       nombre,
                       DATE_FORMAT(fecha_actualizacion, '%Y-%m-%d %H:%i:%s') AS fecha_actualizacion
                FROM sm_formularios
                WHERE activo = 1
                  AND id_cronograma IN (" . $in_clause . ")
                ORDER BY id_cronograma ASC, id DESC";
        $res = mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return null;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $items[] = $row;
        }
        mysqli_free_result($res);

        return $items;
    }
}

if (!function_exists('rsu_api_active_periods_fetch_item_counts')) {
    function rsu_api_active_periods_fetch_item_counts($conexion, $form_ids)
    {
        $map = array();
        $in_clause = rsu_api_active_periods_build_in_clause($form_ids);
        if ($in_clause === '') {
            return $map;
        }

        $sql = "SELECT id_formulario, COUNT(*) AS total_items_activos
                FROM sm_formulario_items
                WHERE activo = 1
                  AND id_formulario IN (" . $in_clause . ")
                GROUP BY id_formulario";
        $res = mysqli_query($conexion, $sql);
        if (!($res instanceof mysqli_result)) {
            return null;
        }

        while ($row = mysqli_fetch_assoc($res)) {
            $id_formulario = (int)rsu_api_active_periods_value($row, 'id_formulario');
            if ($id_formulario > 0) {
                $map[$id_formulario] = (int)rsu_api_active_periods_value($row, 'total_items_activos');
            }
        }
        mysqli_free_result($res);

        return $map;
    }
}

if (!function_exists('rsu_api_periods_active_snapshot_get')) {
    function rsu_api_periods_active_snapshot_get($id_periodo, $include_empty, $timezone_name)
    {
        $id_periodo = (int)$id_periodo;
        $include_empty = (int)$include_empty === 0 ? 0 : 1;
        $timezone_name = trim((string)$timezone_name);
        if ($timezone_name === '') {
            $timezone_name = 'America/Lima';
        }

        if ($id_periodo < 0) {
            return array(
                'ok' => false,
                'error_code' => 'invalid_id_periodo',
                'error_message' => 'El parametro id_periodo es invalido.'
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

        $periods = rsu_api_active_periods_fetch_periods($conexion, $id_periodo);
        if ($periods === null) {
            return array(
                'ok' => false,
                'error_code' => 'db_prepare_error',
                'error_message' => 'No se pudo preparar la consulta de periodos.'
            );
        }

        $period_ids = array();
        $i = 0;
        for ($i = 0; $i < count($periods); $i++) {
            $period_id = (int)rsu_api_active_periods_value($periods[$i], 'id');
            if ($period_id > 0) {
                $period_ids[] = $period_id;
            }
        }

        $period_names = array();
        for ($i = 0; $i < count($periods); $i++) {
            $period_name = trim((string)rsu_api_active_periods_value($periods[$i], 'nombre'));
            if ($period_name !== '') {
                $period_names[$period_name] = $period_name;
            }
        }

        $interface_rules_by_period = rsu_api_active_periods_fetch_interface_rules($conexion, array_values($period_names));
        if ($interface_rules_by_period === null) {
            return array(
                'ok' => false,
                'error_code' => 'db_query_error',
                'error_message' => 'No se pudo consultar reglas de visibilidad por periodo.'
            );
        }
        $interface_definitions = rsu_api_active_periods_interface_definitions();

        $cronograms = rsu_api_active_periods_fetch_cronograms($conexion, $period_ids);
        if ($cronograms === null) {
            return array(
                'ok' => false,
                'error_code' => 'db_query_error',
                'error_message' => 'No se pudo consultar cronogramas activos.'
            );
        }

        $cronograms_by_period = array();
        $cronogram_ids = array();
        for ($i = 0; $i < count($cronograms); $i++) {
            $cron = $cronograms[$i];
            $cron_id = (int)rsu_api_active_periods_value($cron, 'id');
            $period_id = (int)rsu_api_active_periods_value($cron, 'id_periodo');
            if ($cron_id <= 0 || $period_id <= 0) {
                continue;
            }
            if (!isset($cronograms_by_period[$period_id])) {
                $cronograms_by_period[$period_id] = array();
            }
            $cronograms_by_period[$period_id][] = $cron;
            $cronogram_ids[$cron_id] = $cron_id;
        }

        $forms = rsu_api_active_periods_fetch_forms($conexion, array_values($cronogram_ids));
        if ($forms === null) {
            return array(
                'ok' => false,
                'error_code' => 'db_query_error',
                'error_message' => 'No se pudo consultar formularios activos.'
            );
        }

        $forms_by_cronogram = array();
        $form_ids = array();
        for ($i = 0; $i < count($forms); $i++) {
            $form = $forms[$i];
            $cron_id = (int)rsu_api_active_periods_value($form, 'id_cronograma');
            $form_id = (int)rsu_api_active_periods_value($form, 'id');
            if ($cron_id <= 0 || $form_id <= 0) {
                continue;
            }
            if (!isset($forms_by_cronogram[$cron_id])) {
                $forms_by_cronogram[$cron_id] = array();
            }
            $forms_by_cronogram[$cron_id][] = $form;
            $form_ids[$form_id] = $form_id;
        }

        $item_counts = rsu_api_active_periods_fetch_item_counts($conexion, array_values($form_ids));
        if ($item_counts === null) {
            return array(
                'ok' => false,
                'error_code' => 'db_query_error',
                'error_message' => 'No se pudo consultar items activos por formulario.'
            );
        }

        $period_items = array();
        $total_periodos_con_cronos = 0;
        $total_periodos_sin_cronos = 0;
        $total_cronogramas = 0;
        $total_formularios = 0;
        $total_cron_con_formulario = 0;
        $total_cron_sin_formulario = 0;
        $total_cron_form_sin_items = 0;
        $total_interfaces_configuradas = 0;
        $total_interfaces_regla_activa = 0;
        $total_interfaces_visibles_ahora = 0;
        $total_interfaces_sin_regla = 0;

        for ($i = 0; $i < count($periods); $i++) {
            $period = $periods[$i];
            $period_id = (int)rsu_api_active_periods_value($period, 'id');
            if ($period_id <= 0) {
                continue;
            }

            $period_cronograms = isset($cronograms_by_period[$period_id]) ? $cronograms_by_period[$period_id] : array();
            $has_cronograms = count($period_cronograms) > 0;

            if (!$has_cronograms && $include_empty === 0) {
                continue;
            }

            if ($has_cronograms) {
                $total_periodos_con_cronos++;
            } else {
                $total_periodos_sin_cronos++;
            }

            $cron_payload = array();
            $j = 0;
            for ($j = 0; $j < count($period_cronograms); $j++) {
                $cron = $period_cronograms[$j];
                $cron_id = (int)rsu_api_active_periods_value($cron, 'id');
                $tipo = (int)rsu_api_active_periods_value($cron, 'tipo');
                $apertura = (string)rsu_api_active_periods_value($cron, 'apertura');
                $cierre = (string)rsu_api_active_periods_value($cron, 'cierre');
                $forms_for_cron = isset($forms_by_cronogram[$cron_id]) ? $forms_by_cronogram[$cron_id] : array();
                $primary_form = count($forms_for_cron) > 0 ? $forms_for_cron[0] : null;
                $items_activos = 0;
                $form_estado = 'sin_formulario';
                $form_payload = array(
                    'estado' => 'sin_formulario',
                    'existe' => false,
                    'id' => null,
                    'nombre' => null,
                    'items_activos' => 0,
                    'total_formularios_activos_en_cronograma' => count($forms_for_cron)
                );
                $cron_inconsistencias = array();

                if ($primary_form) {
                    $form_id = (int)rsu_api_active_periods_value($primary_form, 'id');
                    $items_activos = isset($item_counts[$form_id]) ? (int)$item_counts[$form_id] : 0;
                    $form_estado = ($items_activos > 0) ? 'formulario_listo' : 'formulario_sin_items';

                    $form_payload = array(
                        'estado' => $form_estado,
                        'existe' => true,
                        'id' => $form_id,
                        'nombre' => (string)rsu_api_active_periods_value($primary_form, 'nombre'),
                        'items_activos' => $items_activos,
                        'total_formularios_activos_en_cronograma' => count($forms_for_cron)
                    );

                    if ($items_activos <= 0) {
                        $total_cron_form_sin_items++;
                    }
                    $total_cron_con_formulario++;
                    $total_formularios += count($forms_for_cron);
                } else {
                    $total_cron_sin_formulario++;
                }

                if (count($forms_for_cron) > 1) {
                    $cron_inconsistencias[] = 'cronograma_con_multiples_formularios_activos';
                }

                $cron_payload[] = array(
                    'id' => $cron_id,
                    'tipo_id' => $tipo,
                    'tipo_nombre' => rsu_api_active_periods_tipo_nombre($tipo),
                    'apertura' => $apertura,
                    'cierre' => $cierre,
                    'ventana_estado' => rsu_api_active_periods_window_status($apertura, $cierre, $timezone_name),
                    'formulario' => $form_payload,
                    'inconsistencias' => $cron_inconsistencias
                );
            }

            $total_cronogramas += count($cron_payload);
            $period_name = (string)rsu_api_active_periods_value($period, 'nombre');
            $rules_for_period = isset($interface_rules_by_period[$period_name]) && is_array($interface_rules_by_period[$period_name])
                ? $interface_rules_by_period[$period_name]
                : array();
            $interfaces_payload = array();
            foreach ($interface_definitions as $codigo_if => $definition_if) {
                $rule_if = isset($rules_for_period[$codigo_if]) ? $rules_for_period[$codigo_if] : null;
                $interface_item = rsu_api_active_periods_interface_payload($codigo_if, $definition_if, $rule_if, $timezone_name);
                $interfaces_payload[] = $interface_item;

                if (!empty($interface_item['configurada'])) {
                    $total_interfaces_configuradas++;
                } else {
                    $total_interfaces_sin_regla++;
                }
                if (!empty($interface_item['regla_activa'])) {
                    $total_interfaces_regla_activa++;
                }
                if (!empty($interface_item['visible_ahora'])) {
                    $total_interfaces_visibles_ahora++;
                }
            }

            $period_items[] = array(
                'id' => $period_id,
                'nombre' => (string)rsu_api_active_periods_value($period, 'nombre'),
                'fecha_inicio' => (string)rsu_api_active_periods_value($period, 'fecha_inicio'),
                'fecha_fin' => (string)rsu_api_active_periods_value($period, 'fecha_fin'),
                'activo' => 1,
                'estado_periodo' => $has_cronograms ? 'con_cronogramas_activos' : 'sin_cronogramas_activos',
                'cronogramas_activos' => $cron_payload,
                'visibilidad_interfaces' => $interfaces_payload
            );
        }

        $meta = array(
            'timezone' => $timezone_name,
            'filters' => array(
                'id_periodo' => ($id_periodo > 0) ? $id_periodo : null,
                'include_empty' => $include_empty
            ),
            'logic_notes' => array(
                'periodo_activo' => 'periodos.activo = 1',
                'cronograma_activo' => 'sm_cronogramas.activo = 1',
                'formulario_activo' => 'sm_formularios.activo = 1',
                'item_activo' => 'sm_formulario_items.activo = 1',
                'visibilidad_interfaces' => 'cronogramas(periodo+codigo) para F1-GENERALIDADES, F1-PLAN, F1-ANEXOS y F3-SEMESTRAL.',
                'consistencia' => 'Se detectan cronogramas con multiples formularios activos, sin alterar datos.'
            )
        );

        return array(
            'ok' => true,
            'data' => array(
                'permissions' => array(
                    'can_view_card_periodos_activos' => true,
                    'role_id' => 1,
                    'role_name' => 'Direccion RSU'
                ),
                'resumen' => array(
                    'total_periodos_activos' => count($period_items),
                    'total_periodos_con_cronogramas_activos' => $total_periodos_con_cronos,
                    'total_periodos_sin_cronogramas_activos' => $total_periodos_sin_cronos,
                    'total_cronogramas_activos' => $total_cronogramas,
                    'total_formularios_activos_vinculados' => $total_formularios,
                    'total_cronogramas_con_formulario' => $total_cron_con_formulario,
                    'total_cronogramas_sin_formulario' => $total_cron_sin_formulario,
                    'total_cronogramas_con_formulario_sin_items' => $total_cron_form_sin_items,
                    'total_interfaces_configuradas' => $total_interfaces_configuradas,
                    'total_interfaces_con_regla_activa' => $total_interfaces_regla_activa,
                    'total_interfaces_visibles_ahora' => $total_interfaces_visibles_ahora,
                    'total_interfaces_sin_regla' => $total_interfaces_sin_regla
                ),
                'periodos' => $period_items
            ),
            'meta' => $meta
        );
    }
}
