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

if (!function_exists('opesp_from_sql')) {
    function opesp_from_sql()
    {
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
                WHERE up.activo = 1
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
                    WHERE up.activo = 1
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
    function opesp_total_filas($conexion)
    {
        $sql = "SELECT COUNT(*) AS total " . opesp_from_sql();
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

if (!function_exists('opesp_obtener_proyectos')) {
    function opesp_obtener_proyectos($conexion, $pagina = 1, $por_pagina = 20)
    {
        $pagina = max(1, (int)$pagina);
        $por_pagina = max(1, (int)$por_pagina);
        $offset = ($pagina - 1) * $por_pagina;

        $supports_period_start = opesp_order_supports_period_start($conexion);
        $total_items = opesp_total_filas($conexion);
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
                COALESCE(dup.cant_coord_activos, 0) AS cant_coord_activos,
                CASE WHEN COALESCE(dup.cant_coord_activos, 0) > 1 THEN 1 ELSE 0 END AS flag_posible_duplicidad
            " . opesp_from_sql() . "
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
                'cant_coord_activos' => isset($row['cant_coord_activos']) ? (int)$row['cant_coord_activos'] : 0,
                'flag_posible_duplicidad' => isset($row['flag_posible_duplicidad']) ? (int)$row['flag_posible_duplicidad'] : 0,
            );
        }
        mysqli_free_result($rs);

        $respuestas = opesp_obtener_respuestas_por_proyectos($conexion, array_values($ids));

        return array(
            'rows' => $rows,
            'respuestas_por_proyecto' => $respuestas,
            'supports_period_start' => $supports_period_start,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
        );
    }
}
