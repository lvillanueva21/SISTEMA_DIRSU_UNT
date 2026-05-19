<?php

if (!function_exists('opesp_order_supports_period_start')) {
    function opesp_order_supports_period_start($conexion)
    {
        $sql = "SHOW COLUMNS FROM periodos LIKE 'fecha_inicio'";
        $rs = mysqli_query($conexion, $sql);
        if ($rs === false) {
            error_log('op_especiales: error verificando columna periodos.fecha_inicio: ' . mysqli_error($conexion));
            return false;
        }

        $exists = mysqli_num_rows($rs) > 0;
        mysqli_free_result($rs);
        return $exists;
    }
}

if (!function_exists('opesp_normalizar_vista_proyectos')) {
    function opesp_normalizar_vista_proyectos($valor)
    {
        $v = strtolower(trim((string)$valor));
        return ($v === 'desactivados') ? 'desactivados' : 'activos';
    }
}

if (!function_exists('opesp_from_sql')) {
    function opesp_from_sql($vista_proyectos = 'activos')
    {
        $vista = opesp_normalizar_vista_proyectos($vista_proyectos);
        $cond_rel = ($vista === 'desactivados') ? 'up.activo = 0' : 'up.activo = 1';

        return "
            FROM proyectos p
            LEFT JOIN (
                SELECT DISTINCT
                    up.id_proyecto AS id_py,
                    u.id AS id_usuario,
                    u.usuario,
                    u.nombres,
                    u.apellidos,
                    u.id_depa
                FROM usuarios_proyectos up
                INNER JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
                WHERE {$cond_rel}
            ) ca
                ON ca.id_py = p.id
            LEFT JOIN departamentos d
                ON d.id = ca.id_depa
            LEFT JOIN facultades f
                ON f.id = d.id_facultad
            LEFT JOIN (
                SELECT pp1.id_py, pp1.id_periodo, pp1.id AS id_pp
                FROM proyectos_periodo pp1
                INNER JOIN (
                    SELECT id_py, MAX(id) AS max_id
                    FROM proyectos_periodo
                    GROUP BY id_py
                ) ult_pp
                    ON ult_pp.id_py = pp1.id_py
                   AND ult_pp.max_id = pp1.id
            ) pp
                ON pp.id_py = p.id
            LEFT JOIN periodos pr
                ON pr.id = pp.id_periodo
            LEFT JOIN (
                SELECT pc1.id_py, pc1.periodo_id, pc1.codigo
                FROM proyecto_codigos pc1
                INNER JOIN (
                    SELECT id_py, periodo_id, MAX(id) AS max_id
                    FROM proyecto_codigos
                    GROUP BY id_py, periodo_id
                ) ult_pc
                    ON ult_pc.id_py = pc1.id_py
                   AND ult_pc.periodo_id = pc1.periodo_id
                   AND ult_pc.max_id = pc1.id
            ) pc
                ON pc.id_py = p.id
               AND pc.periodo_id = pp.id_periodo
            LEFT JOIN (
                SELECT
                    t.id_py,
                    COUNT(DISTINCT t.id_usuario) AS cant_coord_activos
                FROM (
                    SELECT DISTINCT
                        up.id_proyecto AS id_py,
                        u.id AS id_usuario
                    FROM usuarios_proyectos up
                    INNER JOIN usuarios u
                        ON u.id = up.id_usuario
                       AND u.id_rol = 2
                    WHERE {$cond_rel}
                ) t
                GROUP BY t.id_py
            ) dup
                ON dup.id_py = p.id
            WHERE p.id > 0
        ";
    }
}

if (!function_exists('opesp_order_sql')) {
    function opesp_order_sql($supports_period_start)
    {
        $order = "
            ORDER BY
                CASE
                    WHEN TRIM(COALESCE(ca.apellidos, '')) = '' AND TRIM(COALESCE(ca.nombres, '')) = '' THEN 1
                    ELSE 0
                END ASC,
                TRIM(CONCAT(COALESCE(ca.apellidos, ''), ' ', COALESCE(ca.nombres, ''))) ASC,
                CASE WHEN pp.id_pp IS NULL THEN 1 ELSE 0 END ASC,
                pp.id_pp DESC,
                p.id DESC
        ";

        if ($supports_period_start) {
            $order = "
                ORDER BY
                    CASE
                        WHEN TRIM(COALESCE(ca.apellidos, '')) = '' AND TRIM(COALESCE(ca.nombres, '')) = '' THEN 1
                        ELSE 0
                    END ASC,
                    TRIM(CONCAT(COALESCE(ca.apellidos, ''), ' ', COALESCE(ca.nombres, ''))) ASC,
                    CASE WHEN pp.id_pp IS NULL THEN 1 ELSE 0 END ASC,
                    pr.fecha_inicio DESC,
                    pp.id_pp DESC,
                    p.id DESC
            ";
        }

        return $order;
    }
}

if (!function_exists('opesp_total_filas')) {
    function opesp_total_filas($conexion, $where_extra = '', $vista_proyectos = 'activos')
    {
        $sql = "SELECT COUNT(*) AS total " . opesp_from_sql($vista_proyectos);
        if (trim((string)$where_extra) !== '') {
            $sql .= "\n" . $where_extra;
        }
        $rs = mysqli_query($conexion, $sql);
        if ($rs === false) {
            error_log('op_especiales: error contando filas: ' . mysqli_error($conexion));
            return 0;
        }

        $row = mysqli_fetch_assoc($rs);
        mysqli_free_result($rs);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }
}

if (!function_exists('opesp_normalizar_filtro_migracion')) {
    function opesp_normalizar_filtro_migracion($valor)
    {
        $v = strtolower(trim((string)$valor));
        $permitidos = array('todos', 'no_necesita', 'necesita', 'migrado');
        return in_array($v, $permitidos, true) ? $v : 'todos';
    }
}

if (!function_exists('opesp_normalizar_tipo_formulario')) {
    function opesp_normalizar_tipo_formulario($row)
    {
        $tipo_semestre = strtolower(trim((string)($row['tipo_semestre'] ?? '')));
        $tipo_cronograma = isset($row['tipo_cronograma']) ? (int)$row['tipo_cronograma'] : 0;
        $nombre_formulario = strtolower(trim((string)($row['nombre_formulario'] ?? '')));

        if ($tipo_semestre === 'semestral') {
            return 'semestral';
        }
        if ($tipo_semestre === 'presentacion') {
            return 'presentacion';
        }

        if (strpos($nombre_formulario, 'semestral') !== false) {
            return 'semestral';
        }
        if (strpos($nombre_formulario, 'presentaci') !== false || strpos($nombre_formulario, 'proyecto') !== false) {
            return 'presentacion';
        }

        if ($tipo_cronograma === 2) {
            return 'semestral';
        }
        if ($tipo_cronograma === 1) {
            return 'presentacion';
        }

        return 'otros';
    }
}

if (!function_exists('opesp_periodo_respuesta')) {
    function opesp_periodo_respuesta($row)
    {
        $nombre = trim((string)($row['periodo_nombre'] ?? ''));
        if ($nombre !== '') {
            return $nombre;
        }

        $anio = isset($row['anio_semestre']) ? (int)$row['anio_semestre'] : 0;
        $periodo = trim((string)($row['periodo_semestre'] ?? ''));
        if ($anio > 0 && $periodo !== '') {
            return $anio . '-' . $periodo;
        }

        return 'No definido';
    }
}

if (!function_exists('opesp_armar_label_respuesta')) {
    function opesp_armar_label_respuesta($tipo, $periodo)
    {
        if ($tipo === 'semestral') {
            return 'Inf. Semestral ' . $periodo;
        }
        if ($tipo === 'presentacion') {
            return 'Pres. Proyecto ' . $periodo;
        }
        return $periodo . ' Otros';
    }
}

if (!function_exists('opesp_obtener_respuestas_por_proyectos')) {
    function opesp_obtener_respuestas_por_proyectos($conexion, $ids_proyectos)
    {
        $mapa = array();

        if (!is_array($ids_proyectos) || empty($ids_proyectos)) {
            return $mapa;
        }

        $ids = array();
        foreach ($ids_proyectos as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }

        if (empty($ids)) {
            return $mapa;
        }

        $ids_sql = implode(',', $ids);

        $sql = "
            SELECT
                r.id AS id_respuesta,
                r.id_py,
                r.id_formulario,
                r.id_cronograma,
                r.id_semestre,
                r.actualizado_at,
                COALESCE(f.nombre, 'Formulario') AS nombre_formulario,
                sc.id_periodo,
                sc.tipo AS tipo_cronograma,
                COALESCE(pr.nombre, '') AS periodo_nombre,
                s.tipo AS tipo_semestre,
                s.anio AS anio_semestre,
                s.periodo AS periodo_semestre
            FROM sm_respuestas r
            LEFT JOIN sm_formularios f
                ON f.id = r.id_formulario
            LEFT JOIN sm_cronogramas sc
                ON sc.id = r.id_cronograma
            LEFT JOIN periodos pr
                ON pr.id = sc.id_periodo
            LEFT JOIN sm_proyecto_semestres s
                ON s.id = r.id_semestre
            WHERE r.id_py IN ($ids_sql)
            ORDER BY
                r.id_py ASC,
                CASE WHEN pr.fecha_inicio IS NULL THEN 1 ELSE 0 END ASC,
                pr.fecha_inicio DESC,
                s.anio DESC,
                CASE s.periodo WHEN 'II' THEN 2 WHEN 'I' THEN 1 ELSE 0 END DESC,
                r.actualizado_at DESC,
                r.id DESC
        ";

        $rs = mysqli_query($conexion, $sql);
        if ($rs === false) {
            error_log('op_especiales: error listando respuestas por proyecto: ' . mysqli_error($conexion));
            return $mapa;
        }

        while ($row = mysqli_fetch_assoc($rs)) {
            $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
            if ($id_py <= 0) {
                continue;
            }

            $tipo = opesp_normalizar_tipo_formulario($row);
            $periodo = opesp_periodo_respuesta($row);
            $label = opesp_armar_label_respuesta($tipo, $periodo);

            if (!isset($mapa[$id_py])) {
                $mapa[$id_py] = array();
            }

            $mapa[$id_py][] = array(
                'id_respuesta' => isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0,
                'id_py' => $id_py,
                'id_periodo' => isset($row['id_periodo']) ? (int)$row['id_periodo'] : 0,
                'id_formulario' => isset($row['id_formulario']) ? (int)$row['id_formulario'] : 0,
                'id_cronograma' => isset($row['id_cronograma']) ? (int)$row['id_cronograma'] : 0,
                'tipo' => $tipo,
                'periodo' => $periodo,
                'label' => $label,
                'nombre_formulario' => (string)$row['nombre_formulario'],
                'actualizado_at' => (string)($row['actualizado_at'] ?? ''),
            );
        }

        mysqli_free_result($rs);
        return $mapa;
    }
}

if (!function_exists('opesp_normalizar_texto')) {
    function opesp_normalizar_texto($valor)
    {
        $txt = trim((string)$valor);
        if ($txt === '') {
            return '';
        }

        $map = array(
            'á' => 'a', 'à' => 'a', 'ä' => 'a', 'â' => 'a',
            'Á' => 'A', 'À' => 'A', 'Ä' => 'A', 'Â' => 'A',
            'é' => 'e', 'è' => 'e', 'ë' => 'e', 'ê' => 'e',
            'É' => 'E', 'È' => 'E', 'Ë' => 'E', 'Ê' => 'E',
            'í' => 'i', 'ì' => 'i', 'ï' => 'i', 'î' => 'i',
            'Í' => 'I', 'Ì' => 'I', 'Ï' => 'I', 'Î' => 'I',
            'ó' => 'o', 'ò' => 'o', 'ö' => 'o', 'ô' => 'o',
            'Ó' => 'O', 'Ò' => 'O', 'Ö' => 'O', 'Ô' => 'O',
            'ú' => 'u', 'ù' => 'u', 'ü' => 'u', 'û' => 'u',
            'Ú' => 'U', 'Ù' => 'U', 'Ü' => 'U', 'Û' => 'U',
            'ñ' => 'n', 'Ñ' => 'N'
        );

        $txt = strtr($txt, $map);
        $txt = mb_strtoupper($txt, 'UTF-8');
        $txt = preg_replace('/\s+/', '', $txt);
        return $txt;
    }
}

if (!function_exists('opesp_resolver_formularios_migracion_2024ii')) {
    function opesp_resolver_formularios_migracion_2024ii($conexion)
    {
        $items = array();
        $sql = "
            SELECT
                f.id AS id_formulario,
                f.nombre AS nombre_formulario,
                f.activo AS formulario_activo,
                f.id_cronograma,
                sc.id_periodo,
                sc.tipo AS tipo_cronograma,
                sc.activo AS cronograma_activo,
                sc.apertura,
                sc.cierre,
                pr.nombre AS periodo_nombre
            FROM sm_formularios f
            INNER JOIN sm_cronogramas sc
                ON sc.id = f.id_cronograma
            INNER JOIN periodos pr
                ON pr.id = sc.id_periodo
            WHERE sc.tipo = 2
              AND UPPER(REPLACE(TRIM(pr.nombre), ' ', '')) = '2024-II'
            ORDER BY
                f.activo DESC,
                sc.activo DESC,
                f.id DESC
        ";

        $rs = mysqli_query($conexion, $sql);
        if ($rs === false) {
            error_log('op_especiales: error resolviendo formularios migracion 2024-II: ' . mysqli_error($conexion));
            return $items;
        }

        while ($row = mysqli_fetch_assoc($rs)) {
            $items[] = array(
                'id_formulario' => isset($row['id_formulario']) ? (int)$row['id_formulario'] : 0,
                'nombre_formulario' => (string)($row['nombre_formulario'] ?? ''),
                'formulario_activo' => isset($row['formulario_activo']) ? (int)$row['formulario_activo'] : 0,
                'id_cronograma' => isset($row['id_cronograma']) ? (int)$row['id_cronograma'] : 0,
                'id_periodo' => isset($row['id_periodo']) ? (int)$row['id_periodo'] : 0,
                'tipo_cronograma' => isset($row['tipo_cronograma']) ? (int)$row['tipo_cronograma'] : 0,
                'cronograma_activo' => isset($row['cronograma_activo']) ? (int)$row['cronograma_activo'] : 0,
                'apertura' => (string)($row['apertura'] ?? ''),
                'cierre' => (string)($row['cierre'] ?? ''),
                'periodo_nombre' => (string)($row['periodo_nombre'] ?? ''),
            );
        }
        mysqli_free_result($rs);

        return $items;
    }
}

if (!function_exists('opesp_sql_condicion_migrado_2024ii')) {
    function opesp_sql_condicion_migrado_2024ii($aliasProyecto, $form_ids)
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$aliasProyecto);
        if ($alias === '') {
            $alias = 'p';
        }

        $ids = array();
        foreach ((array)$form_ids as $fid) {
            $fid = (int)$fid;
            if ($fid > 0) {
                $ids[$fid] = $fid;
            }
        }
        if (empty($ids)) {
            return '0 = 1';
        }

        $ids_sql = implode(',', $ids);
        return "
            EXISTS (
                SELECT 1
                FROM sm_respuestas r
                INNER JOIN eva_evaluaciones e
                    ON e.id_respuesta = r.id
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                WHERE r.id_py = {$alias}.id
                  AND r.id_formulario IN ({$ids_sql})
                  AND s.anio = 2024
                  AND s.periodo = 'II'
                  AND s.tipo = 'semestral'
                  AND e.situacion = 'aprobado'
            )
        ";
    }
}

if (!function_exists('opesp_sql_condicion_migrable_base')) {
    function opesp_sql_condicion_migrable_base($aliasProyecto)
    {
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$aliasProyecto);
        if ($alias === '') {
            $alias = 'p';
        }

        return "
            EXISTS (
                SELECT 1
                FROM proyectos_finales pf
                WHERE pf.id_py = {$alias}.id
            )
            AND EXISTS (
                SELECT 1
                FROM usuarios_proyectos up
                INNER JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
                WHERE up.id_proyecto = {$alias}.id
                  AND up.activo = 1
            )
        ";
    }
}

if (!function_exists('opesp_where_filtro_estado_migracion')) {
    function opesp_where_filtro_estado_migracion($filtro, $form_ids, $aliasProyecto = 'p')
    {
        $f = opesp_normalizar_filtro_migracion($filtro);
        if ($f === 'todos') {
            return '';
        }

        $condMigrable = opesp_sql_condicion_migrable_base($aliasProyecto);
        $condMigrado = opesp_sql_condicion_migrado_2024ii($aliasProyecto, $form_ids);
        $where = '';

        if ($f === 'migrado') {
            $where = $condMigrado;
        } elseif ($f === 'necesita') {
            $where = '(' . $condMigrable . ') AND NOT (' . $condMigrado . ')';
        } elseif ($f === 'no_necesita') {
            $where = 'NOT (' . $condMigrable . ') AND NOT (' . $condMigrado . ')';
        }

        if (trim($where) === '') {
            return '';
        }

        return " AND (\n{$where}\n)";
    }
}

if (!function_exists('opesp_where_filtro_relacion_activa')) {
    function opesp_where_filtro_relacion_activa($vista_proyectos, $aliasProyecto = 'p')
    {
        $vista = opesp_normalizar_vista_proyectos($vista_proyectos);
        $alias = preg_replace('/[^a-zA-Z0-9_]/', '', (string)$aliasProyecto);
        if ($alias === '') {
            $alias = 'p';
        }

        $existsAny = "
            EXISTS (
                SELECT 1
                FROM usuarios_proyectos up
                INNER JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
                WHERE up.id_proyecto = {$alias}.id
            )
        ";
        $existsActive = "
            EXISTS (
                SELECT 1
                FROM usuarios_proyectos up
                INNER JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
                WHERE up.id_proyecto = {$alias}.id
                  AND up.activo = 1
            )
        ";

        if ($vista === 'desactivados') {
            return " AND (\n{$existsAny}\n) AND NOT (\n{$existsActive}\n)";
        }

        return " AND (\n{$existsActive}\n)";
    }
}

if (!function_exists('opesp_obtener_estado_migracion_por_proyectos')) {
    function opesp_obtener_estado_migracion_por_proyectos($conexion, $ids_proyectos)
    {
        $mapa = array();
        $ids = array();
        foreach ((array)$ids_proyectos as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        if (empty($ids)) {
            return $mapa;
        }

        $ids_sql = implode(',', $ids);
        foreach ($ids as $id) {
            $mapa[$id] = array(
                'tiene_legacy' => 0,
                'cant_legacy' => 0,
                'legacy_id' => 0,
                'cant_coord_reales' => 0,
                'tiene_coordinador_real' => 0,
                'puede_migrar' => 0,
                'migrado_2024ii' => 0,
            );
        }

        $sqlLegacy = "
            SELECT id_py, COUNT(*) AS cant, MAX(id) AS legacy_id
            FROM proyectos_finales
            WHERE id_py IN ($ids_sql)
            GROUP BY id_py
        ";
        $rsLegacy = mysqli_query($conexion, $sqlLegacy);
        if ($rsLegacy !== false) {
            while ($row = mysqli_fetch_assoc($rsLegacy)) {
                $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
                if ($id_py > 0 && isset($mapa[$id_py])) {
                    $cant = isset($row['cant']) ? (int)$row['cant'] : 0;
                    $legacy_id = isset($row['legacy_id']) ? (int)$row['legacy_id'] : 0;
                    $mapa[$id_py]['cant_legacy'] = $cant;
                    $mapa[$id_py]['legacy_id'] = $legacy_id;
                    $mapa[$id_py]['tiene_legacy'] = ($cant > 0) ? 1 : 0;
                }
            }
            mysqli_free_result($rsLegacy);
        } else {
            error_log('op_especiales: error listando legacy por proyecto: ' . mysqli_error($conexion));
        }

        $sqlCoord = "
            SELECT
                up.id_proyecto AS id_py,
                COUNT(DISTINCT u.id) AS cant_coord
            FROM usuarios_proyectos up
            INNER JOIN usuarios u
                ON u.id = up.id_usuario
               AND u.id_rol = 2
            WHERE up.activo = 1
              AND up.id_proyecto IN ($ids_sql)
            GROUP BY up.id_proyecto
        ";
        $rsCoord = mysqli_query($conexion, $sqlCoord);
        if ($rsCoord !== false) {
            while ($row = mysqli_fetch_assoc($rsCoord)) {
                $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
                if ($id_py > 0 && isset($mapa[$id_py])) {
                    $cant = isset($row['cant_coord']) ? (int)$row['cant_coord'] : 0;
                    $mapa[$id_py]['cant_coord_reales'] = $cant;
                    $mapa[$id_py]['tiene_coordinador_real'] = ($cant > 0) ? 1 : 0;
                }
            }
            mysqli_free_result($rsCoord);
        } else {
            error_log('op_especiales: error listando coordinadores reales por proyecto: ' . mysqli_error($conexion));
        }

        $forms = opesp_resolver_formularios_migracion_2024ii($conexion);
        $form_ids = array();
        foreach ($forms as $f) {
            $fid = isset($f['id_formulario']) ? (int)$f['id_formulario'] : 0;
            if ($fid > 0) {
                $form_ids[$fid] = $fid;
            }
        }
        if (!empty($form_ids)) {
            $forms_sql = implode(',', $form_ids);
            $sqlMig = "
                SELECT
                    r.id_py
                FROM sm_respuestas r
                INNER JOIN eva_evaluaciones e
                    ON e.id_respuesta = r.id
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                WHERE r.id_py IN ($ids_sql)
                  AND r.id_formulario IN ($forms_sql)
                  AND s.anio = 2024
                  AND s.periodo = 'II'
                  AND s.tipo = 'semestral'
                  AND e.situacion = 'aprobado'
                GROUP BY r.id_py
            ";
            $rsMig = mysqli_query($conexion, $sqlMig);
            if ($rsMig !== false) {
                while ($row = mysqli_fetch_assoc($rsMig)) {
                    $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
                    if ($id_py > 0 && isset($mapa[$id_py])) {
                        $mapa[$id_py]['migrado_2024ii'] = 1;
                    }
                }
                mysqli_free_result($rsMig);
            } else {
                error_log('op_especiales: error validando migrados 2024-II: ' . mysqli_error($conexion));
            }
        }

        foreach ($mapa as $id_py => $estado) {
            $mapa[$id_py]['puede_migrar'] = ($estado['tiene_legacy'] && $estado['tiene_coordinador_real']) ? 1 : 0;
        }

        return $mapa;
    }
}

if (!function_exists('opesp_obtener_resumen_global_migracion')) {
    function opesp_obtener_resumen_global_migracion($conexion, $vista_proyectos = 'activos')
    {
        $vista = opesp_normalizar_vista_proyectos($vista_proyectos);
        $where_rel = opesp_where_filtro_relacion_activa($vista, 'p');

        $summary = array(
            'total_migrables' => 0,
            'total_migrados' => 0,
            'total_pendientes' => 0,
        );

        $sqlTotal = "
            SELECT COUNT(*) AS total
            FROM proyectos p
            WHERE EXISTS (
                SELECT 1
                FROM proyectos_finales pf
                WHERE pf.id_py = p.id
            )
            {$where_rel}
        ";
        $rsTotal = mysqli_query($conexion, $sqlTotal);
        if ($rsTotal !== false) {
            $rowTotal = mysqli_fetch_assoc($rsTotal);
            $summary['total_migrables'] = isset($rowTotal['total']) ? (int)$rowTotal['total'] : 0;
            mysqli_free_result($rsTotal);
        } else {
            error_log('op_especiales: error calculando total_migrables global: ' . mysqli_error($conexion));
        }

        $forms = opesp_resolver_formularios_migracion_2024ii($conexion);
        $form_ids = array();
        foreach ($forms as $f) {
            $fid = isset($f['id_formulario']) ? (int)$f['id_formulario'] : 0;
            if ($fid > 0) {
                $form_ids[$fid] = $fid;
            }
        }

        if (!empty($form_ids)) {
            $ids_sql = implode(',', $form_ids);
            $sqlMigrados = "
                SELECT COUNT(DISTINCT r.id_py) AS total
                FROM sm_respuestas r
                INNER JOIN eva_evaluaciones e
                    ON e.id_respuesta = r.id
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                WHERE r.id_formulario IN ($ids_sql)
                  AND s.anio = 2024
                  AND s.periodo = 'II'
                  AND s.tipo = 'semestral'
                  AND e.situacion = 'aprobado'
                  AND EXISTS (
                      SELECT 1
                      FROM proyectos_finales pf
                      WHERE pf.id_py = r.id_py
                  )
                  AND (
                      " . (($vista === 'desactivados')
                          ? "EXISTS (
                                SELECT 1
                                FROM usuarios_proyectos upx
                                INNER JOIN usuarios ux ON ux.id = upx.id_usuario AND ux.id_rol = 2
                                WHERE upx.id_proyecto = r.id_py
                            )
                            AND NOT EXISTS (
                                SELECT 1
                                FROM usuarios_proyectos upx
                                INNER JOIN usuarios ux ON ux.id = upx.id_usuario AND ux.id_rol = 2
                                WHERE upx.id_proyecto = r.id_py
                                  AND upx.activo = 1
                            )"
                          : "EXISTS (
                                SELECT 1
                                FROM usuarios_proyectos upx
                                INNER JOIN usuarios ux ON ux.id = upx.id_usuario AND ux.id_rol = 2
                                WHERE upx.id_proyecto = r.id_py
                                  AND upx.activo = 1
                            )") . "
                  )
            ";
            $rsMigrados = mysqli_query($conexion, $sqlMigrados);
            if ($rsMigrados !== false) {
                $rowMigrados = mysqli_fetch_assoc($rsMigrados);
                $summary['total_migrados'] = isset($rowMigrados['total']) ? (int)$rowMigrados['total'] : 0;
                mysqli_free_result($rsMigrados);
            } else {
                error_log('op_especiales: error calculando total_migrados global: ' . mysqli_error($conexion));
            }
        }

        $summary['total_pendientes'] = $summary['total_migrables'] - $summary['total_migrados'];
        if ($summary['total_pendientes'] < 0) {
            $summary['total_pendientes'] = 0;
        }

        return $summary;
    }
}

if (!function_exists('opesp_obtener_proyectos')) {
    function opesp_obtener_proyectos($conexion, $pagina = 1, $por_pagina = 20, $filtro_estado = 'todos', $vista_proyectos = 'activos')
    {
        $pagina = max(1, (int)$pagina);
        $por_pagina = max(1, (int)$por_pagina);
        $offset = ($pagina - 1) * $por_pagina;
        $filtro_estado = opesp_normalizar_filtro_migracion($filtro_estado);
        $vista_proyectos = opesp_normalizar_vista_proyectos($vista_proyectos);
        $forms_migracion_2024ii = opesp_resolver_formularios_migracion_2024ii($conexion);
        $form_ids = array();
        foreach ($forms_migracion_2024ii as $frow) {
            $fid = isset($frow['id_formulario']) ? (int)$frow['id_formulario'] : 0;
            if ($fid > 0) {
                $form_ids[$fid] = $fid;
            }
        }
        $where_filtro = opesp_where_filtro_estado_migracion($filtro_estado, array_values($form_ids), 'p');
        $where_relacion = opesp_where_filtro_relacion_activa($vista_proyectos, 'p');
        $where_extra = trim($where_relacion . "\n" . $where_filtro);

        $supports_period_start = opesp_order_supports_period_start($conexion);
        $total_items = opesp_total_filas($conexion, $where_extra, $vista_proyectos);
        $total_pages = max(1, (int)ceil($total_items / $por_pagina));

        if ($pagina > $total_pages) {
            $pagina = $total_pages;
            $offset = ($pagina - 1) * $por_pagina;
        }

        $sql = "
            SELECT
                p.id AS id_py,
                COALESCE(NULLIF(TRIM(CONCAT(ca.nombres, ' ', ca.apellidos)), ''), 'Sin coordinador') AS coordinador,
                COALESCE(NULLIF(TRIM(p.p2), ''), 'Sin titulo') AS titulo_proyecto,
                COALESCE(pr.nombre, 'No definido') AS periodo_creacion,
                COALESCE(NULLIF(TRIM(pc.codigo), ''), 'Codigo pendiente') AS codigo_proyecto,
                COALESCE(f.nombre, 'Sin Facultad') AS facultad,
                COALESCE(d.nombre, 'Sin Departamento Academico') AS departamento,
                COALESCE(NULLIF(TRIM(ca.usuario), ''), 'Sin codigo docente') AS cod_docente,
                COALESCE(NULLIF(TRIM(p.fecha_inicio), ''), '') AS fecha_inicio,
                COALESCE(NULLIF(TRIM(p.fecha_fin), ''), '') AS fecha_fin,
                COALESCE(dup.cant_coord_activos, 0) AS cant_coord_activos,
                CASE WHEN COALESCE(dup.cant_coord_activos, 0) > 1 THEN 1 ELSE 0 END AS flag_posible_duplicidad
            " . opesp_from_sql($vista_proyectos) . "
            " . $where_extra . "
            " . opesp_order_sql($supports_period_start) . "
            LIMIT $por_pagina OFFSET $offset
        ";

        $rows = array();
        $rs = mysqli_query($conexion, $sql);

        if ($rs === false) {
            error_log('op_especiales: error listando proyectos: ' . mysqli_error($conexion));
            return array(
                'rows' => $rows,
                'respuestas_por_proyecto' => array(),
                'supports_period_start' => $supports_period_start,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'pagina' => $pagina,
                'por_pagina' => $por_pagina,
                'forms_migracion_2024ii' => $forms_migracion_2024ii,
                'filtro_estado' => $filtro_estado,
                'vista_proyectos' => $vista_proyectos,
            );
        }

        $ids = array();
        while ($row = mysqli_fetch_assoc($rs)) {
            $id_py = isset($row['id_py']) ? (int)$row['id_py'] : 0;
            if ($id_py > 0) {
                $ids[$id_py] = $id_py;
            }

            $rows[] = array(
                'id_py' => $id_py,
                'coordinador' => (string)$row['coordinador'],
                'titulo_proyecto' => (string)$row['titulo_proyecto'],
                'periodo_creacion' => (string)$row['periodo_creacion'],
                'codigo_proyecto' => (string)$row['codigo_proyecto'],
                'facultad' => (string)$row['facultad'],
                'departamento' => (string)$row['departamento'],
                'cod_docente' => (string)$row['cod_docente'],
                'fecha_inicio' => (string)$row['fecha_inicio'],
                'fecha_fin' => (string)$row['fecha_fin'],
                'cant_coord_activos' => isset($row['cant_coord_activos']) ? (int)$row['cant_coord_activos'] : 0,
                'flag_posible_duplicidad' => isset($row['flag_posible_duplicidad']) ? (int)$row['flag_posible_duplicidad'] : 0,
            );
        }
        mysqli_free_result($rs);

        $ids_lista = array_values($ids);
        $respuestas = opesp_obtener_respuestas_por_proyectos($conexion, $ids_lista);
        $estado_migracion = opesp_obtener_estado_migracion_por_proyectos($conexion, $ids_lista);

        foreach ($rows as $idx => $row_data) {
            $id_py = isset($row_data['id_py']) ? (int)$row_data['id_py'] : 0;
            $estado = isset($estado_migracion[$id_py]) && is_array($estado_migracion[$id_py]) ? $estado_migracion[$id_py] : array();

            $tiene_legacy = isset($estado['tiene_legacy']) ? (int)$estado['tiene_legacy'] : 0;
            $cant_legacy = isset($estado['cant_legacy']) ? (int)$estado['cant_legacy'] : 0;
            $legacy_id = isset($estado['legacy_id']) ? (int)$estado['legacy_id'] : 0;
            $tiene_coord = isset($estado['tiene_coordinador_real']) ? (int)$estado['tiene_coordinador_real'] : 0;
            $cant_coord = isset($estado['cant_coord_reales']) ? (int)$estado['cant_coord_reales'] : 0;
            $puede_migrar = isset($estado['puede_migrar']) ? (int)$estado['puede_migrar'] : 0;
            $migrado = isset($estado['migrado_2024ii']) ? (int)$estado['migrado_2024ii'] : 0;

            $rows[$idx]['tiene_legacy'] = $tiene_legacy;
            $rows[$idx]['cant_legacy'] = $cant_legacy;
            $rows[$idx]['legacy_id'] = $legacy_id;
            $rows[$idx]['tiene_coordinador_real'] = $tiene_coord;
            $rows[$idx]['cant_coord_reales'] = $cant_coord;
            $rows[$idx]['puede_migrar'] = $puede_migrar;
            $rows[$idx]['migrado_2024ii'] = $migrado;
        }

        $summary_global = opesp_obtener_resumen_global_migracion($conexion, $vista_proyectos);
        $total_migrables = isset($summary_global['total_migrables']) ? (int)$summary_global['total_migrables'] : 0;
        $total_migrados = isset($summary_global['total_migrados']) ? (int)$summary_global['total_migrados'] : 0;
        $total_pendientes = isset($summary_global['total_pendientes']) ? (int)$summary_global['total_pendientes'] : 0;

        return array(
            'rows' => $rows,
            'respuestas_por_proyecto' => $respuestas,
            'forms_migracion_2024ii' => $forms_migracion_2024ii,
            'supports_period_start' => $supports_period_start,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
            'total_migrables' => $total_migrables,
            'total_migrados' => $total_migrados,
            'total_pendientes' => $total_pendientes,
            'filtro_estado' => $filtro_estado,
            'vista_proyectos' => $vista_proyectos,
        );
    }
}
