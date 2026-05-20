<?php
/**
 * Servicio central para listar "proyectos reales".
 * Reglas V1:
 * - p.id > 0
 * - Debe existir relacion activa en usuarios_proyectos con usuario id_rol=2
 * - Se toma el coordinador activo mas reciente por proyecto
 * - "Creado en" usa el primer proyectos_periodo (MIN id) por proyecto
 * - Orden default: fecha_creacion_derivada DESC, p.id DESC
 */

if (!function_exists('rsu_projects_real_normalize_page')) {
    function rsu_projects_real_normalize_page($pagina)
    {
        $pagina = (int)$pagina;
        return ($pagina > 0) ? $pagina : 1;
    }
}

if (!function_exists('rsu_projects_real_normalize_page_size')) {
    function rsu_projects_real_normalize_page_size($por_pagina)
    {
        $por_pagina = (int)$por_pagina;
        if ($por_pagina <= 0) {
            return 20;
        }
        return ($por_pagina > 200) ? 200 : $por_pagina;
    }
}

if (!function_exists('rsu_projects_real_from_sql')) {
    function rsu_projects_real_from_sql()
    {
        return "
            FROM proyectos p
            INNER JOIN (
                SELECT
                    up.id_proyecto,
                    MAX(up.id) AS up_id
                FROM usuarios_proyectos up
                INNER JOIN usuarios ux
                    ON ux.id = up.id_usuario
                   AND ux.id_rol = 2
                WHERE up.activo = 1
                GROUP BY up.id_proyecto
            ) up_pick
                ON up_pick.id_proyecto = p.id
            INNER JOIN usuarios_proyectos up
                ON up.id = up_pick.up_id
            INNER JOIN usuarios u
                ON u.id = up.id_usuario
            LEFT JOIN departamentos d
                ON d.id = u.id_depa
            LEFT JOIN facultades f
                ON f.id = d.id_facultad
            LEFT JOIN (
                SELECT
                    pp1.id_py,
                    pp1.id_periodo
                FROM proyectos_periodo pp1
                INNER JOIN (
                    SELECT id_py, MIN(id) AS min_id
                    FROM proyectos_periodo
                    GROUP BY id_py
                ) ppf
                    ON ppf.id_py = pp1.id_py
                   AND ppf.min_id = pp1.id
            ) pp_creacion
                ON pp_creacion.id_py = p.id
            LEFT JOIN periodos per
                ON per.id = pp_creacion.id_periodo
            LEFT JOIN (
                SELECT
                    pc1.id_py,
                    pc1.periodo_id,
                    pc1.codigo
                FROM proyecto_codigos pc1
                INNER JOIN (
                    SELECT id_py, periodo_id, MAX(id) AS max_id
                    FROM proyecto_codigos
                    GROUP BY id_py, periodo_id
                ) pcf
                    ON pcf.id_py = pc1.id_py
                   AND pcf.periodo_id = pc1.periodo_id
                   AND pcf.max_id = pc1.id
            ) pc
                ON pc.id_py = p.id
               AND pc.periodo_id = pp_creacion.id_periodo
            LEFT JOIN (
                SELECT
                    h.id_py,
                    MIN(h.fecha) AS fecha_creacion
                FROM historial_proyectos h
                WHERE h.descripcion LIKE 'Creación de proyecto%'
                   OR h.descripcion LIKE 'Se creó el proyecto%'
                GROUP BY h.id_py
            ) hc
                ON hc.id_py = p.id
            LEFT JOIN (
                SELECT
                    up2.id_proyecto,
                    MIN(up2.fecha_asignacion) AS fecha_primera_asignacion
                FROM usuarios_proyectos up2
                INNER JOIN usuarios u2
                    ON u2.id = up2.id_usuario
                   AND u2.id_rol = 2
                GROUP BY up2.id_proyecto
            ) fa
                ON fa.id_proyecto = p.id
            WHERE p.id > 0
        ";
    }
}

if (!function_exists('rsu_projects_real_normalize_filters')) {
    function rsu_projects_real_normalize_filters($filters)
    {
        $filters = is_array($filters) ? $filters : array();

        $facultad_id = isset($filters['facultad_id']) ? (int)$filters['facultad_id'] : 0;
        $departamento_id = isset($filters['departamento_id']) ? (int)$filters['departamento_id'] : 0;
        $creacion_periodo_id = isset($filters['creacion_periodo_id']) ? (int)$filters['creacion_periodo_id'] : 0;

        if ($facultad_id < 0) $facultad_id = 0;
        if ($departamento_id < 0) $departamento_id = 0;
        if ($creacion_periodo_id < 0) $creacion_periodo_id = 0;

        return array(
            'facultad_id' => $facultad_id,
            'departamento_id' => $departamento_id,
            'creacion_periodo_id' => $creacion_periodo_id,
        );
    }
}

if (!function_exists('rsu_projects_real_filters_where_sql')) {
    function rsu_projects_real_filters_where_sql($filters)
    {
        $parts = array();
        $filters = rsu_projects_real_normalize_filters($filters);

        if ($filters['facultad_id'] > 0) {
            $parts[] = "f.id = " . (int)$filters['facultad_id'];
        }
        if ($filters['departamento_id'] > 0) {
            $parts[] = "d.id = " . (int)$filters['departamento_id'];
            if ($filters['facultad_id'] > 0) {
                $parts[] = "d.id_facultad = " . (int)$filters['facultad_id'];
            }
        }
        if ($filters['creacion_periodo_id'] > 0) {
            $parts[] = "pp_creacion.id_periodo = " . (int)$filters['creacion_periodo_id'];
        }

        if (empty($parts)) {
            return '';
        }

        return ' AND ' . implode(' AND ', $parts) . ' ';
    }
}

if (!function_exists('rsu_projects_real_count')) {
    function rsu_projects_real_count($conexion, $filters = array())
    {
        if (!($conexion instanceof mysqli)) {
            return 0;
        }

        $sql = "SELECT COUNT(*) AS total " . rsu_projects_real_from_sql() . rsu_projects_real_filters_where_sql($filters);
        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return 0;
        }

        $row = mysqli_fetch_assoc($rs);
        mysqli_free_result($rs);
        return isset($row['total']) ? (int)$row['total'] : 0;
    }
}

if (!function_exists('rsu_projects_real_list')) {
    function rsu_projects_real_list($conexion, $pagina = 1, $por_pagina = 20, $filters = array())
    {
        $pagina = rsu_projects_real_normalize_page($pagina);
        $por_pagina = rsu_projects_real_normalize_page_size($por_pagina);
        $filters = rsu_projects_real_normalize_filters($filters);

        $total_items = rsu_projects_real_count($conexion, $filters);
        $total_pages = max(1, (int)ceil($total_items / $por_pagina));
        if ($pagina > $total_pages) {
            $pagina = $total_pages;
        }
        $offset = ($pagina - 1) * $por_pagina;

        $rows = array();
        if (!($conexion instanceof mysqli)) {
            return array(
                'rows' => $rows,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'pagina' => $pagina,
                'por_pagina' => $por_pagina,
            );
        }

        $sql = "
            SELECT
                p.id AS id_py,
                COALESCE(NULLIF(TRIM(p.p2), ''), 'Sin titulo') AS titulo_proyecto,
                COALESCE(NULLIF(TRIM(CONCAT(u.nombres, ' ', u.apellidos)), ''), 'Sin coordinador') AS coordinador,
                COALESCE(NULLIF(TRIM(u.usuario), ''), 'Sin codigo docente') AS cod_docente,
                COALESCE(NULLIF(TRIM(f.nombre), ''), 'Sin Facultad') AS facultad,
                COALESCE(NULLIF(TRIM(d.nombre), ''), 'Sin Departamento Academico') AS departamento,
                COALESCE(NULLIF(TRIM(per.nombre), ''), 'No definido') AS periodo_creacion,
                COALESCE(NULLIF(TRIM(pc.codigo), ''), 'Codigo pendiente') AS codigo_proyecto,
                COALESCE(NULLIF(TRIM(p.fecha_inicio), ''), '') AS fecha_inicio,
                COALESCE(NULLIF(TRIM(p.fecha_fin), ''), '') AS fecha_fin,
                COALESCE(hc.fecha_creacion, fa.fecha_primera_asignacion, NULL) AS fecha_orden
            " . rsu_projects_real_from_sql() . rsu_projects_real_filters_where_sql($filters) . "
            ORDER BY
                CASE WHEN COALESCE(hc.fecha_creacion, fa.fecha_primera_asignacion) IS NULL THEN 1 ELSE 0 END ASC,
                COALESCE(hc.fecha_creacion, fa.fecha_primera_asignacion) DESC,
                p.id DESC
            LIMIT " . (int)$por_pagina . " OFFSET " . (int)$offset;

        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return array(
                'rows' => $rows,
                'total_items' => $total_items,
                'total_pages' => $total_pages,
                'pagina' => $pagina,
                'por_pagina' => $por_pagina,
            );
        }

        while ($row = mysqli_fetch_assoc($rs)) {
            $rows[] = array(
                'id_py' => isset($row['id_py']) ? (int)$row['id_py'] : 0,
                'titulo_proyecto' => (string)$row['titulo_proyecto'],
                'coordinador' => (string)$row['coordinador'],
                'cod_docente' => (string)$row['cod_docente'],
                'facultad' => (string)$row['facultad'],
                'departamento' => (string)$row['departamento'],
                'periodo_creacion' => (string)$row['periodo_creacion'],
                'codigo_proyecto' => (string)$row['codigo_proyecto'],
                'fecha_inicio' => (string)$row['fecha_inicio'],
                'fecha_fin' => (string)$row['fecha_fin'],
            );
        }
        mysqli_free_result($rs);

        return array(
            'rows' => $rows,
            'total_items' => $total_items,
            'total_pages' => $total_pages,
            'pagina' => $pagina,
            'por_pagina' => $por_pagina,
        );
    }
}

if (!function_exists('rsu_projects_real_filter_facultades')) {
    function rsu_projects_real_filter_facultades($conexion)
    {
        $rows = array();
        if (!($conexion instanceof mysqli)) {
            return $rows;
        }

        $sql = "
            SELECT DISTINCT
                f.id AS id_facultad,
                COALESCE(NULLIF(TRIM(f.nombre), ''), 'Sin Facultad') AS nombre
            " . rsu_projects_real_from_sql() . "
            AND f.id IS NOT NULL
            ORDER BY nombre ASC
        ";

        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return $rows;
        }
        while ($row = mysqli_fetch_assoc($rs)) {
            $id = isset($row['id_facultad']) ? (int)$row['id_facultad'] : 0;
            if ($id <= 0) continue;
            $rows[$id] = (string)$row['nombre'];
        }
        mysqli_free_result($rs);
        return $rows;
    }
}

if (!function_exists('rsu_projects_real_filter_departamentos')) {
    function rsu_projects_real_filter_departamentos($conexion, $facultad_id = 0)
    {
        $rows = array();
        if (!($conexion instanceof mysqli)) {
            return $rows;
        }

        $facultad_id = (int)$facultad_id;
        $extra = '';
        if ($facultad_id > 0) {
            $extra = " AND d.id_facultad = " . $facultad_id . " ";
        }

        $sql = "
            SELECT DISTINCT
                d.id AS id_departamento,
                COALESCE(NULLIF(TRIM(d.nombre), ''), 'Sin Departamento Academico') AS nombre
            " . rsu_projects_real_from_sql() . "
            AND d.id IS NOT NULL
            " . $extra . "
            ORDER BY nombre ASC
        ";

        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return $rows;
        }
        while ($row = mysqli_fetch_assoc($rs)) {
            $id = isset($row['id_departamento']) ? (int)$row['id_departamento'] : 0;
            if ($id <= 0) continue;
            $rows[$id] = (string)$row['nombre'];
        }
        mysqli_free_result($rs);
        return $rows;
    }
}

if (!function_exists('rsu_projects_real_filter_periodos_creacion')) {
    function rsu_projects_real_filter_periodos_creacion($conexion)
    {
        $rows = array();
        if (!($conexion instanceof mysqli)) {
            return $rows;
        }

        $sql = "
            SELECT DISTINCT
                pp_creacion.id_periodo AS id_periodo,
                COALESCE(NULLIF(TRIM(per.nombre), ''), 'No definido') AS nombre,
                per.fecha_inicio
            " . rsu_projects_real_from_sql() . "
            AND pp_creacion.id_periodo IS NOT NULL
            ORDER BY
                CASE WHEN per.fecha_inicio IS NULL THEN 1 ELSE 0 END ASC,
                per.fecha_inicio DESC,
                nombre DESC
        ";

        $rs = mysqli_query($conexion, $sql);
        if (!($rs instanceof mysqli_result)) {
            return $rows;
        }
        while ($row = mysqli_fetch_assoc($rs)) {
            $id = isset($row['id_periodo']) ? (int)$row['id_periodo'] : 0;
            if ($id <= 0) continue;
            $rows[$id] = (string)$row['nombre'];
        }
        mysqli_free_result($rs);
        return $rows;
    }
}
