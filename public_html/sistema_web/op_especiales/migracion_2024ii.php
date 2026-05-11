<?php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../includes/db_connection.php';
include_once __DIR__ . '/helpers.php';
include_once __DIR__ . '/consultas.php';

if (!headers_sent()) {
    header('Content-Type: application/json; charset=UTF-8');
}

if (!function_exists('opesp_json_error')) {
    function opesp_json_error($status, $message, $extra = array())
    {
        http_response_code((int)$status);
        $payload = array_merge(
            array(
                'ok' => false,
                'message' => (string)$message
            ),
            is_array($extra) ? $extra : array()
        );
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('opesp_json_ok')) {
    function opesp_json_ok($data = array())
    {
        $payload = array_merge(
            array('ok' => true),
            is_array($data) ? $data : array()
        );
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

if (!function_exists('opesp_get_project_legacy')) {
    function opesp_get_project_legacy($conexion, $id_py)
    {
        $out = array('count' => 0, 'row' => null);
        $sql = "SELECT * FROM proyectos_finales WHERE id_py = ? ORDER BY id DESC LIMIT 2";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando legacy: ' . mysqli_error($conexion));
            return $out;
        }

        mysqli_stmt_bind_param($st, 'i', $id_py);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando legacy: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return $out;
        }

        $rs = mysqli_stmt_get_result($st);
        if ($rs) {
            $rows = array();
            while ($row = mysqli_fetch_assoc($rs)) {
                $rows[] = $row;
            }
            $out['count'] = count($rows);
            $out['row'] = ($out['count'] > 0) ? $rows[0] : null;
            mysqli_free_result($rs);
        }

        mysqli_stmt_close($st);
        return $out;
    }
}

if (!function_exists('opesp_get_real_coordinators')) {
    function opesp_get_real_coordinators($conexion, $id_py)
    {
        $list = array();
        $sql = "
            SELECT
                u.id,
                u.usuario,
                u.nombres,
                u.apellidos,
                u.id_depa
            FROM usuarios_proyectos up
            INNER JOIN usuarios u
                ON u.id = up.id_usuario
               AND u.id_rol = 2
            WHERE up.id_proyecto = ?
              AND up.activo = 1
            ORDER BY u.id ASC
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando coordinadores reales: ' . mysqli_error($conexion));
            return $list;
        }

        mysqli_stmt_bind_param($st, 'i', $id_py);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando coordinadores reales: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return $list;
        }

        $rs = mysqli_stmt_get_result($st);
        if ($rs) {
            while ($row = mysqli_fetch_assoc($rs)) {
                $list[] = array(
                    'id' => isset($row['id']) ? (int)$row['id'] : 0,
                    'usuario' => (string)($row['usuario'] ?? ''),
                    'nombres' => (string)($row['nombres'] ?? ''),
                    'apellidos' => (string)($row['apellidos'] ?? ''),
                    'id_depa' => isset($row['id_depa']) ? (int)$row['id_depa'] : 0,
                );
            }
            mysqli_free_result($rs);
        }

        mysqli_stmt_close($st);
        return $list;
    }
}

if (!function_exists('opesp_get_global_migration_summary')) {
    function opesp_get_global_migration_summary($conexion)
    {
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
            AND EXISTS (
                SELECT 1
                FROM usuarios_proyectos up
                INNER JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
                WHERE up.id_proyecto = p.id
                  AND up.activo = 1
            )
        ";
        $rsTotal = mysqli_query($conexion, $sqlTotal);
        if ($rsTotal !== false) {
            $rowTotal = mysqli_fetch_assoc($rsTotal);
            $summary['total_migrables'] = isset($rowTotal['total']) ? (int)$rowTotal['total'] : 0;
            mysqli_free_result($rsTotal);
        } else {
            error_log('op_especiales migracion: error total migrables: ' . mysqli_error($conexion));
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
                  AND EXISTS (
                      SELECT 1
                      FROM usuarios_proyectos up
                      INNER JOIN usuarios u
                          ON u.id = up.id_usuario
                         AND u.id_rol = 2
                      WHERE up.id_proyecto = r.id_py
                        AND up.activo = 1
                  )
            ";
            $rsMigrados = mysqli_query($conexion, $sqlMigrados);
            if ($rsMigrados !== false) {
                $rowMigrados = mysqli_fetch_assoc($rsMigrados);
                $summary['total_migrados'] = isset($rowMigrados['total']) ? (int)$rowMigrados['total'] : 0;
                mysqli_free_result($rsMigrados);
            } else {
                error_log('op_especiales migracion: error total migrados: ' . mysqli_error($conexion));
            }
        }

        $summary['total_pendientes'] = $summary['total_migrables'] - $summary['total_migrados'];
        if ($summary['total_pendientes'] < 0) {
            $summary['total_pendientes'] = 0;
        }

        return $summary;
    }
}

if (!function_exists('opesp_find_target_form_2024ii')) {
    function opesp_find_target_form_2024ii($conexion, $id_formulario_solicitado = 0)
    {
        $forms = opesp_resolver_formularios_migracion_2024ii($conexion);
        $result = array(
            'forms' => $forms,
            'selected' => null,
            'needs_selection' => false,
        );

        if (empty($forms)) {
            return $result;
        }

        if ($id_formulario_solicitado > 0) {
            foreach ($forms as $f) {
                if ((int)$f['id_formulario'] === (int)$id_formulario_solicitado) {
                    $result['selected'] = $f;
                    return $result;
                }
            }
            return $result;
        }

        if (count($forms) === 1) {
            $result['selected'] = $forms[0];
            return $result;
        }

        $result['needs_selection'] = true;
        return $result;
    }
}

if (!function_exists('opesp_get_form_items')) {
    function opesp_get_form_items($conexion, $id_formulario)
    {
        $items = array();
        $sql = "
            SELECT
                fi.id_item,
                fi.orden,
                i.nombre,
                i.tipo
            FROM sm_formulario_items fi
            INNER JOIN sm_items i
                ON i.id = fi.id_item
            WHERE fi.id_formulario = ?
              AND fi.activo = 1
            ORDER BY fi.orden ASC
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando items de formulario: ' . mysqli_error($conexion));
            return $items;
        }

        mysqli_stmt_bind_param($st, 'i', $id_formulario);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando items de formulario: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return $items;
        }

        $rs = mysqli_stmt_get_result($st);
        if ($rs) {
            while ($row = mysqli_fetch_assoc($rs)) {
                $items[] = array(
                    'id_item' => isset($row['id_item']) ? (int)$row['id_item'] : 0,
                    'orden' => isset($row['orden']) ? (int)$row['orden'] : 0,
                    'nombre' => (string)($row['nombre'] ?? ''),
                    'tipo' => (string)($row['tipo'] ?? 'varchar'),
                );
            }
            mysqli_free_result($rs);
        }

        mysqli_stmt_close($st);
        return $items;
    }
}

if (!function_exists('opesp_guess_legacy_field_from_item')) {
    function opesp_guess_legacy_field_from_item($item_nombre, $orden)
    {
        $norm = opesp_normalizar_texto($item_nombre);
        if ($norm === '') {
            $norm = 'ITEM' . (int)$orden;
        }

        if (strpos($norm, 'TITULODELPROGRAMA') !== false) return 'programa';
        if (strpos($norm, 'TITULODELPROYECTO') !== false) return 'titulo';
        if (strpos($norm, 'COORDINADOR') !== false) return 'coordinador';
        if (strpos($norm, 'INTEGRANTES') !== false && strpos($norm, 'DOCENTES') !== false) return 'integrantes';
        if (strpos($norm, 'INTEGRANTES') !== false && strpos($norm, 'ESTUDIANTES') !== false) return 'estudiantes';
        if (strpos($norm, 'OBJETIVOS') !== false && strpos($norm, 'METAS') !== false && strpos($norm, 'INDICADORES') !== false) return 'omi';
        if (strpos($norm, 'LUGARDEEJECUCION') !== false || strpos($norm, 'LUGAR') !== false) return 'lugar';
        if (strpos($norm, 'INSTITUCION') !== false && strpos($norm, 'POBLACION') !== false) return 'beneficiados';
        if (strpos($norm, 'RESUMEN') !== false) return 'resumen';
        if (strpos($norm, 'ACTIVIDADES') !== false) return 'actividades';
        if (strpos($norm, 'DESCRIPCIONDERESULTADOS') !== false) return 'resultados';
        if (strpos($norm, 'COMENTARIOS') !== false || strpos($norm, 'DISCUSION') !== false) return 'comentarios';
        if (strpos($norm, 'CONCLUSIONES') !== false) return 'conclusiones';
        if (strpos($norm, 'ANALISIS') !== false) return 'analisis';
        if (strpos($norm, 'RECOMENDACIONES') !== false) return 'recomendaciones';
        if (strpos($norm, 'FUENTES') !== false) return 'fuentes';
        if (strpos($norm, 'ANEXOS') !== false) return 'anexos';
        if (strpos($norm, 'NUMERODEHORAS') !== false || (strpos($norm, 'HORAS') !== false && strpos($norm, 'RESPONSABILIDADSOCIAL') !== false)) return 'carga';

        $fallback_by_order = array(
            1 => 'programa',
            2 => 'titulo',
            3 => 'coordinador',
            4 => 'integrantes',
            5 => 'estudiantes',
            6 => 'omi',
            7 => 'lugar',
            8 => 'beneficiados',
            9 => 'resumen',
            10 => 'actividades',
            11 => 'resultados',
            12 => 'comentarios',
            13 => 'conclusiones',
            14 => 'analisis',
            15 => 'recomendaciones',
            16 => 'fuentes',
            17 => 'anexos',
            18 => 'carga'
        );

        return isset($fallback_by_order[(int)$orden]) ? $fallback_by_order[(int)$orden] : '';
    }
}

if (!function_exists('opesp_prepare_items_for_migration')) {
    function opesp_prepare_items_for_migration($items_formulario, $legacy_row)
    {
        $rows = array();
        foreach ((array)$items_formulario as $it) {
            $campo = opesp_guess_legacy_field_from_item($it['nombre'] ?? '', $it['orden'] ?? 0);
            $valor = '';
            if ($campo !== '' && isset($legacy_row[$campo])) {
                $valor = (string)$legacy_row[$campo];
            }

            $rows[] = array(
                'id_item' => isset($it['id_item']) ? (int)$it['id_item'] : 0,
                'orden' => isset($it['orden']) ? (int)$it['orden'] : 0,
                'nombre' => (string)($it['nombre'] ?? ''),
                'tipo' => (string)($it['tipo'] ?? 'varchar'),
                'legacy_field' => $campo,
                'valor' => (string)$valor,
            );
        }
        return $rows;
    }
}

if (!function_exists('opesp_get_target_semester')) {
    function opesp_get_target_semester($conexion, $id_py)
    {
        $sql = "
            SELECT *
            FROM sm_proyecto_semestres
            WHERE id_py = ?
              AND anio = 2024
              AND periodo = 'II'
              AND tipo = 'semestral'
            ORDER BY id DESC
            LIMIT 1
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando consulta semestre 2024-II: ' . mysqli_error($conexion));
            return null;
        }

        mysqli_stmt_bind_param($st, 'i', $id_py);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando consulta semestre 2024-II: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return null;
        }

        $rs = mysqli_stmt_get_result($st);
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if ($rs) {
            mysqli_free_result($rs);
        }
        mysqli_stmt_close($st);
        return $row ?: null;
    }
}

if (!function_exists('opesp_get_period_by_id')) {
    function opesp_get_period_by_id($conexion, $id_periodo)
    {
        $sql = "SELECT id, nombre, fecha_inicio, fecha_fin FROM periodos WHERE id = ? LIMIT 1";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando consulta periodo: ' . mysqli_error($conexion));
            return null;
        }

        mysqli_stmt_bind_param($st, 'i', $id_periodo);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando consulta periodo: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return null;
        }

        $rs = mysqli_stmt_get_result($st);
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if ($rs) {
            mysqli_free_result($rs);
        }
        mysqli_stmt_close($st);
        return $row ?: null;
    }
}

if (!function_exists('opesp_find_existing_response')) {
    function opesp_find_existing_response($conexion, $id_py, $id_formulario)
    {
        $sql = "
            SELECT
                r.id AS id_respuesta,
                r.id_semestre,
                e.situacion AS situacion_eval
            FROM sm_respuestas r
            LEFT JOIN eva_evaluaciones e
                ON e.id_respuesta = r.id
            INNER JOIN sm_proyecto_semestres s
                ON s.id = r.id_semestre
            WHERE r.id_py = ?
              AND r.id_formulario = ?
              AND s.anio = 2024
              AND s.periodo = 'II'
              AND s.tipo = 'semestral'
            ORDER BY r.id DESC
            LIMIT 1
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            error_log('op_especiales migracion: error preparando respuesta existente: ' . mysqli_error($conexion));
            return null;
        }

        mysqli_stmt_bind_param($st, 'ii', $id_py, $id_formulario);
        if (!mysqli_stmt_execute($st)) {
            error_log('op_especiales migracion: error ejecutando respuesta existente: ' . mysqli_stmt_error($st));
            mysqli_stmt_close($st);
            return null;
        }

        $rs = mysqli_stmt_get_result($st);
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if ($rs) {
            mysqli_free_result($rs);
        }
        mysqli_stmt_close($st);
        return $row ?: null;
    }
}

if (!function_exists('opesp_ensure_semester_2024ii')) {
    function opesp_ensure_semester_2024ii($conexion, $id_py, $periodo_row, &$steps)
    {
        $sem = opesp_get_target_semester($conexion, $id_py);
        if ($sem) {
            $id_sem = isset($sem['id']) ? (int)$sem['id'] : 0;
            $vigente = isset($sem['vigente']) ? (int)$sem['vigente'] : 0;
            if ($id_sem > 0 && $vigente !== 1) {
                $stUp = mysqli_prepare($conexion, "UPDATE sm_proyecto_semestres SET vigente = 1, updated_at = NOW() WHERE id = ? LIMIT 1");
                if ($stUp) {
                    mysqli_stmt_bind_param($stUp, 'i', $id_sem);
                    if (!mysqli_stmt_execute($stUp)) {
                        $err = mysqli_stmt_error($stUp);
                        mysqli_stmt_close($stUp);
                        throw new Exception('No se pudo activar el semestre 2024-II existente: ' . $err);
                    }
                    mysqli_stmt_close($stUp);
                    $steps[] = 'Se activó el semestre 2024-II existente (vigente=1).';
                }
            } else {
                $steps[] = 'Se utilizará el semestre 2024-II existente.';
            }
            return $id_sem;
        }

        $fecha_inicio = isset($periodo_row['fecha_inicio']) && trim((string)$periodo_row['fecha_inicio']) !== '' ? (string)$periodo_row['fecha_inicio'] : '2024-07-01';
        $fecha_fin = isset($periodo_row['fecha_fin']) && trim((string)$periodo_row['fecha_fin']) !== '' ? (string)$periodo_row['fecha_fin'] : '2024-12-31';

        $max_numero = 0;
        $sqlNum = "SELECT MAX(numero) AS max_numero FROM sm_proyecto_semestres WHERE id_py = ? AND tipo = 'semestral'";
        $stNum = mysqli_prepare($conexion, $sqlNum);
        if ($stNum) {
            mysqli_stmt_bind_param($stNum, 'i', $id_py);
            if (mysqli_stmt_execute($stNum)) {
                $rsNum = mysqli_stmt_get_result($stNum);
                if ($rsNum) {
                    $rowNum = mysqli_fetch_assoc($rsNum);
                    if ($rowNum && isset($rowNum['max_numero'])) {
                        $max_numero = (int)$rowNum['max_numero'];
                    }
                    mysqli_free_result($rsNum);
                }
            }
            mysqli_stmt_close($stNum);
        }
        $nuevo_numero = $max_numero + 1;
        if ($nuevo_numero <= 0) {
            $nuevo_numero = 1;
        }

        $titulo = 'Informe Semestral ' . str_pad((string)$nuevo_numero, 2, '0', STR_PAD_LEFT) . ' (Migración 2024-II)';
        $stIns = mysqli_prepare(
            $conexion,
            "INSERT INTO sm_proyecto_semestres
                (id_py, anio, periodo, fecha_inicio, fecha_fin, tipo, numero, final, estado, vigente, titulo, created_at, updated_at)
             VALUES
                (?, 2024, 'II', ?, ?, 'semestral', ?, 0, 0, 1, ?, NOW(), NOW())"
        );
        if (!$stIns) {
            throw new Exception('No se pudo preparar la creación de semestre 2024-II.');
        }

        mysqli_stmt_bind_param($stIns, 'issis', $id_py, $fecha_inicio, $fecha_fin, $nuevo_numero, $titulo);
        if (!mysqli_stmt_execute($stIns)) {
            $err = mysqli_stmt_error($stIns);
            mysqli_stmt_close($stIns);
            throw new Exception('No se pudo crear el semestre 2024-II: ' . $err);
        }
        $id_semestre = (int)mysqli_insert_id($conexion);
        mysqli_stmt_close($stIns);

        if ($id_semestre <= 0) {
            throw new Exception('No se pudo recuperar el id del semestre 2024-II creado.');
        }

        $steps[] = 'Se creó el semestre 2024-II faltante para el proyecto.';
        return $id_semestre;
    }
}

if (!function_exists('opesp_insert_migrated_item_value')) {
    function opesp_insert_migrated_item_value($conexion, $id_respuesta, $item, $valor)
    {
        $id_item = isset($item['id_item']) ? (int)$item['id_item'] : 0;
        $tipo = isset($item['tipo']) ? (string)$item['tipo'] : 'varchar';
        if ($id_item <= 0) {
            throw new Exception('Item inválido durante la migración.');
        }

        $val_varchar = null;
        $val_longtext = null;
        $val_tinyint = null;
        $val_int = null;
        $val_boolean = null;
        $val_datetime = null;
        $val_date = null;
        $val_decimal = null;
        $archivo_url = null;

        $valor = (string)$valor;
        switch ($tipo) {
            case 'longtext':
                $val_longtext = $valor;
                break;
            case 'tinyint':
                $val_tinyint = (trim($valor) === '') ? null : (int)$valor;
                break;
            case 'int':
                $val_int = (trim($valor) === '') ? null : (int)$valor;
                break;
            case 'boolean':
                $val_boolean = (trim($valor) === '') ? null : ((int)$valor ? 1 : 0);
                break;
            case 'datetime':
                $val_datetime = (trim($valor) === '') ? null : $valor;
                break;
            case 'date':
                $val_date = (trim($valor) === '') ? null : $valor;
                break;
            case 'decimal':
                $val_decimal = (trim($valor) === '') ? null : (float)$valor;
                break;
            case 'pdf':
            case 'excel':
            case 'word':
                $archivo_url = (trim($valor) === '') ? null : $valor;
                break;
            default:
                $val_varchar = $valor;
                break;
        }

        $sql = "
            INSERT INTO sm_respuesta_items
                (id_respuesta, id_item, tipo, val_varchar, val_longtext, val_tinyint, val_int, val_boolean, val_datetime, val_date, val_decimal, archivo_url, estado, creado_at, actualizado_at)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
                tipo = VALUES(tipo),
                val_varchar = VALUES(val_varchar),
                val_longtext = VALUES(val_longtext),
                val_tinyint = VALUES(val_tinyint),
                val_int = VALUES(val_int),
                val_boolean = VALUES(val_boolean),
                val_datetime = VALUES(val_datetime),
                val_date = VALUES(val_date),
                val_decimal = VALUES(val_decimal),
                archivo_url = VALUES(archivo_url),
                estado = 0,
                actualizado_at = NOW()
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar la inserción de respuesta-item.');
        }

        mysqli_stmt_bind_param(
            $st,
            'iisssiiissds',
            $id_respuesta,
            $id_item,
            $tipo,
            $val_varchar,
            $val_longtext,
            $val_tinyint,
            $val_int,
            $val_boolean,
            $val_datetime,
            $val_date,
            $val_decimal,
            $archivo_url
        );

        if (!mysqli_stmt_execute($st)) {
            $err = mysqli_stmt_error($st);
            mysqli_stmt_close($st);
            throw new Exception('No se pudo guardar el ítem ' . $id_item . ': ' . $err);
        }
        mysqli_stmt_close($st);
    }
}

if (!function_exists('opesp_create_full_approval_route')) {
    function opesp_create_full_approval_route($conexion, $id_respuesta, &$steps)
    {
        $stEval = mysqli_prepare(
            $conexion,
            "INSERT INTO eva_evaluaciones (id_respuesta, situacion, id_oficina_actual, creado_at, actualizado_at)
             VALUES (?, 'aprobado', NULL, NOW(), NOW())"
        );
        if (!$stEval) {
            throw new Exception('No se pudo preparar la evaluación del informe.');
        }
        mysqli_stmt_bind_param($stEval, 'i', $id_respuesta);
        if (!mysqli_stmt_execute($stEval)) {
            $err = mysqli_stmt_error($stEval);
            mysqli_stmt_close($stEval);
            throw new Exception('No se pudo crear la evaluación del informe: ' . $err);
        }
        $id_eval = (int)mysqli_insert_id($conexion);
        mysqli_stmt_close($stEval);
        if ($id_eval <= 0) {
            throw new Exception('No se pudo obtener el id de evaluación.');
        }
        $steps[] = 'Se creó la evaluación base del informe.';

        $oficinas = array(
            1 => 'Presidente de Comité de Facultad',
            2 => 'Dirección de Departamento',
            3 => 'Decanato de Facultad',
            4 => 'Dirección de RSU'
        );
        foreach ($oficinas as $id_oficina => $nombre_oficina) {
            $stInst = mysqli_prepare(
                $conexion,
                "INSERT INTO eva_oficina_instancias
                    (id_evaluacion, id_oficina, llegada, salida, estado, reintentos, anulaciones, ultima_observacion_at, ultima_revision_solicitada_at)
                 VALUES
                    (?, ?, NOW(), NOW(), 'aprobado', 0, 0, NULL, NOW())"
            );
            if (!$stInst) {
                throw new Exception('No se pudo preparar la instancia de ' . $nombre_oficina . '.');
            }
            mysqli_stmt_bind_param($stInst, 'ii', $id_eval, $id_oficina);
            if (!mysqli_stmt_execute($stInst)) {
                $err = mysqli_stmt_error($stInst);
                mysqli_stmt_close($stInst);
                throw new Exception('No se pudo aprobar la instancia de ' . $nombre_oficina . ': ' . $err);
            }
            mysqli_stmt_close($stInst);
            $steps[] = 'Se aprobó por ' . $nombre_oficina . '.';
        }

        $calificaciones = array(
            array('oficina' => 1, 'tipo' => 'cotejo', 'estado' => 'aprobado', 'total' => null),
            array('oficina' => 1, 'tipo' => 'rubrica', 'estado' => 'aprobado', 'total' => 20),
            array('oficina' => 2, 'tipo' => 'vistobueno', 'estado' => 'aprobado', 'total' => null),
            array('oficina' => 3, 'tipo' => 'vistobueno', 'estado' => 'aprobado', 'total' => null),
            array('oficina' => 4, 'tipo' => 'cotejo', 'estado' => 'aprobado', 'total' => null),
            array('oficina' => 4, 'tipo' => 'rubrica', 'estado' => 'aprobado', 'total' => 20),
        );

        $rubrica_calificaciones = array();
        foreach ($calificaciones as $cal) {
            $stCal = mysqli_prepare(
                $conexion,
                "INSERT INTO eva_calificaciones
                    (id_evaluacion, id_oficina, tipo, estado, dias_subsanacion, total, obs_general, reintentos, anulaciones, ultimo_observado_at, ultima_revision_solicitada_at, creado_at, actualizado_at)
                 VALUES
                    (?, ?, ?, ?, NULL, ?, NULL, 0, 0, NULL, NOW(), NOW(), NOW())"
            );
            if (!$stCal) {
                throw new Exception('No se pudo preparar la calificación de la ruta.');
            }

            $total = isset($cal['total']) ? $cal['total'] : null;
            mysqli_stmt_bind_param($stCal, 'iissi', $id_eval, $cal['oficina'], $cal['tipo'], $cal['estado'], $total);
            if (!mysqli_stmt_execute($stCal)) {
                $err = mysqli_stmt_error($stCal);
                mysqli_stmt_close($stCal);
                throw new Exception('No se pudo registrar una calificación de ruta: ' . $err);
            }
            $id_cal = (int)mysqli_insert_id($conexion);
            mysqli_stmt_close($stCal);

            if ($cal['tipo'] === 'rubrica' && $id_cal > 0) {
                $rubrica_calificaciones[] = $id_cal;
            }
        }
        $steps[] = 'Se registraron las calificaciones y vistos buenos requeridos.';

        $aspectos = array('estructura', 'contenido', 'redaccion', 'calidad_info', 'propuesta_mejora');
        foreach ($rubrica_calificaciones as $id_cal) {
            foreach ($aspectos as $asp) {
                $nota = 4;
                $obs = null;
                $stAsp = mysqli_prepare(
                    $conexion,
                    "INSERT INTO eva_rubrica_aspectos (id_calificacion, aspecto, nota, observacion)
                     VALUES (?, ?, ?, ?)"
                );
                if (!$stAsp) {
                    throw new Exception('No se pudo preparar detalle de rúbrica.');
                }
                mysqli_stmt_bind_param($stAsp, 'isis', $id_cal, $asp, $nota, $obs);
                if (!mysqli_stmt_execute($stAsp)) {
                    $err = mysqli_stmt_error($stAsp);
                    mysqli_stmt_close($stAsp);
                    throw new Exception('No se pudo registrar aspecto de rúbrica: ' . $err);
                }
                mysqli_stmt_close($stAsp);
            }
        }
        $steps[] = 'Se asignó rúbrica con nota máxima en todas las oficinas correspondientes.';
    }
}

if (!function_exists('opesp_get_session_user_id')) {
    function opesp_get_session_user_id($conexion)
    {
        $usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
        if ($usuario === '') {
            return null;
        }
        $sql = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            return null;
        }
        mysqli_stmt_bind_param($st, 's', $usuario);
        if (!mysqli_stmt_execute($st)) {
            mysqli_stmt_close($st);
            return null;
        }
        $rs = mysqli_stmt_get_result($st);
        $row = $rs ? mysqli_fetch_assoc($rs) : null;
        if ($rs) {
            mysqli_free_result($rs);
        }
        mysqli_stmt_close($st);
        if (!$row || !isset($row['id'])) {
            return null;
        }
        return (int)$row['id'];
    }
}

if (!function_exists('opesp_insert_migration_history')) {
    function opesp_insert_migration_history($conexion, $id_py, $id_periodo, $id_formulario, $id_respuesta, $id_usuario_historial)
    {
        $accion = 'Migración 2024-II';
        $descripcion = 'Migración automática completada a formulario ' . $id_formulario . ' (respuesta ' . $id_respuesta . ').';
        $sql = "
            INSERT INTO historial_estados
                (id_py, id_periodo, fecha, accion, descripcion, usuario_id)
            VALUES
                (?, ?, NOW(), ?, ?, ?)
        ";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            throw new Exception('No se pudo preparar el historial de migración.');
        }
        mysqli_stmt_bind_param($st, 'iissi', $id_py, $id_periodo, $accion, $descripcion, $id_usuario_historial);
        if (!mysqli_stmt_execute($st)) {
            $err = mysqli_stmt_error($st);
            mysqli_stmt_close($st);
            throw new Exception('No se pudo registrar historial de migración: ' . $err);
        }
        mysqli_stmt_close($st);
    }
}

if (!function_exists('opesp_validate_access')) {
    function opesp_validate_access()
    {
        $idRol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
        if ($idRol !== 1) {
            opesp_json_error(403, 'Acceso no autorizado para esta operación.');
        }
    }
}

opesp_validate_access();

if (!isset($conexion) || !($conexion instanceof mysqli)) {
    opesp_json_error(500, 'No hay conexión de base de datos disponible.');
}

$action = isset($_REQUEST['action']) ? trim((string)$_REQUEST['action']) : '';
$id_py = isset($_REQUEST['id_py']) ? (int)$_REQUEST['id_py'] : 0;
$id_formulario_req = isset($_REQUEST['id_formulario']) ? (int)$_REQUEST['id_formulario'] : 0;

if ($id_py <= 0) {
    opesp_json_error(400, 'Proyecto inválido para la migración.');
}

$legacyInfo = opesp_get_project_legacy($conexion, $id_py);
$coordinadores = opesp_get_real_coordinators($conexion, $id_py);
$summary = opesp_get_global_migration_summary($conexion);

if ($action === 'context' || $action === 'preview') {
    $resForm = opesp_find_target_form_2024ii($conexion, $id_formulario_req);
    $forms = isset($resForm['forms']) ? $resForm['forms'] : array();
    $selected = isset($resForm['selected']) ? $resForm['selected'] : null;
    $needsSelection = !empty($resForm['needs_selection']);

    if (empty($forms)) {
        opesp_json_error(409, 'No se encontró un formulario semestral 2024-II disponible.', array(
            'summary' => $summary
        ));
    }

    if ($action === 'context') {
        opesp_json_ok(array(
            'id_py' => $id_py,
            'legacy_count' => isset($legacyInfo['count']) ? (int)$legacyInfo['count'] : 0,
            'has_legacy' => (!empty($legacyInfo['row'])) ? 1 : 0,
            'coordinador_count' => count($coordinadores),
            'has_coordinador_real' => (count($coordinadores) > 0) ? 1 : 0,
            'forms' => $forms,
            'needs_form_selection' => $needsSelection ? 1 : 0,
            'summary' => $summary,
        ));
    }

    if (empty($legacyInfo['row'])) {
        opesp_json_error(409, 'El proyecto no tiene informe semestral antiguo en proyectos_finales.', array(
            'summary' => $summary
        ));
    }
    if ((int)$legacyInfo['count'] > 1) {
        opesp_json_error(409, 'Se detectaron múltiples filas en proyectos_finales para el proyecto. No se puede migrar hasta corregirlo.', array(
            'summary' => $summary
        ));
    }
    if (empty($coordinadores)) {
        opesp_json_error(409, 'El proyecto no tiene coordinador activo real (activo=1 + rol=2).', array(
            'summary' => $summary
        ));
    }

    if ($selected === null) {
        opesp_json_ok(array(
            'requires_form_selection' => 1,
            'forms' => $forms,
            'summary' => $summary,
        ));
    }

    $itemsForm = opesp_get_form_items($conexion, (int)$selected['id_formulario']);
    if (empty($itemsForm)) {
        opesp_json_error(409, 'El formulario seleccionado no tiene ítems activos para migración.', array(
            'summary' => $summary
        ));
    }

    $legacyRow = $legacyInfo['row'];
    $itemsPrepared = opesp_prepare_items_for_migration($itemsForm, $legacyRow);
    $existingResponse = opesp_find_existing_response($conexion, $id_py, (int)$selected['id_formulario']);

    $legacyView = array(
        array('key' => 'programa', 'label' => '1. Título del Programa', 'value' => (string)($legacyRow['programa'] ?? '')),
        array('key' => 'titulo', 'label' => '2. Título del Proyecto', 'value' => (string)($legacyRow['titulo'] ?? '')),
        array('key' => 'coordinador', 'label' => '1.4 Coordinador', 'value' => (string)($legacyRow['coordinador'] ?? '')),
        array('key' => 'integrantes', 'label' => '1.4 Integrantes (Docentes)', 'value' => (string)($legacyRow['integrantes'] ?? '')),
        array('key' => 'estudiantes', 'label' => '1.4 Integrantes (Estudiantes)', 'value' => (string)($legacyRow['estudiantes'] ?? '')),
        array('key' => 'omi', 'label' => '1.5 OMI', 'value' => (string)($legacyRow['omi'] ?? '')),
        array('key' => 'lugar', 'label' => '1.6 Lugar de Ejecución', 'value' => (string)($legacyRow['lugar'] ?? '')),
        array('key' => 'beneficiados', 'label' => '1.7 Institución / Población', 'value' => (string)($legacyRow['beneficiados'] ?? '')),
        array('key' => 'resumen', 'label' => '2.1 Resumen', 'value' => (string)($legacyRow['resumen'] ?? '')),
        array('key' => 'actividades', 'label' => '2.2 Actividades', 'value' => (string)($legacyRow['actividades'] ?? '')),
        array('key' => 'resultados', 'label' => '2.3.1 Descripción de resultados', 'value' => (string)($legacyRow['resultados'] ?? '')),
        array('key' => 'comentarios', 'label' => '2.3.3 Comentarios/Discusión', 'value' => (string)($legacyRow['comentarios'] ?? '')),
        array('key' => 'conclusiones', 'label' => '2.3.4 Conclusiones', 'value' => (string)($legacyRow['conclusiones'] ?? '')),
        array('key' => 'analisis', 'label' => '2.3.5 Análisis de impacto', 'value' => (string)($legacyRow['analisis'] ?? '')),
        array('key' => 'recomendaciones', 'label' => '2.3.6 Recomendaciones', 'value' => (string)($legacyRow['recomendaciones'] ?? '')),
        array('key' => 'fuentes', 'label' => '2.3.7 Fuentes consultadas', 'value' => (string)($legacyRow['fuentes'] ?? '')),
        array('key' => 'anexos', 'label' => '2.3.8 Anexos', 'value' => (string)($legacyRow['anexos'] ?? '')),
        array('key' => 'carga', 'label' => '3.1 Número de horas RSU', 'value' => (string)($legacyRow['carga'] ?? '')),
    );

    opesp_json_ok(array(
        'requires_form_selection' => 0,
        'id_py' => $id_py,
        'form' => $selected,
        'legacy_count' => (int)$legacyInfo['count'],
        'coordinador_count' => count($coordinadores),
        'coordinadores' => $coordinadores,
        'legacy_items' => $legacyView,
        'target_items' => $itemsPrepared,
        'existing_response' => array(
            'exists' => ($existingResponse !== null) ? 1 : 0,
            'id_respuesta' => ($existingResponse && isset($existingResponse['id_respuesta'])) ? (int)$existingResponse['id_respuesta'] : 0,
            'situacion_eval' => ($existingResponse && isset($existingResponse['situacion_eval'])) ? (string)$existingResponse['situacion_eval'] : '',
        ),
        'summary' => $summary,
    ));
}

if ($action === 'execute') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        opesp_json_error(405, 'Método no permitido para ejecutar migración.');
    }

    $force_replace = isset($_POST['force_replace']) ? (int)$_POST['force_replace'] : 0;
    $steps = array();

    $resForm = opesp_find_target_form_2024ii($conexion, $id_formulario_req);
    $selected = isset($resForm['selected']) ? $resForm['selected'] : null;
    if ($selected === null) {
        opesp_json_error(409, 'Debes seleccionar un formulario objetivo válido para migrar.', array(
            'forms' => isset($resForm['forms']) ? $resForm['forms'] : array(),
            'summary' => $summary
        ));
    }

    if (empty($legacyInfo['row'])) {
        opesp_json_error(409, 'No existe informe semestral antiguo para migrar.');
    }
    if ((int)$legacyInfo['count'] > 1) {
        opesp_json_error(409, 'Se detectaron múltiples filas en proyectos_finales para el proyecto. Migración cancelada.');
    }
    if (empty($coordinadores)) {
        opesp_json_error(409, 'No existe coordinador activo real en el proyecto. Migración cancelada.');
    }

    $coordinadorPrincipal = $coordinadores[0];
    $id_formulario = (int)$selected['id_formulario'];
    $id_cronograma = (int)$selected['id_cronograma'];
    $id_periodo = (int)$selected['id_periodo'];
    $periodoRow = opesp_get_period_by_id($conexion, $id_periodo);
    if (!$periodoRow) {
        opesp_json_error(409, 'No se pudo obtener el período del formulario destino.');
    }

    $itemsForm = opesp_get_form_items($conexion, $id_formulario);
    if (empty($itemsForm)) {
        opesp_json_error(409, 'El formulario destino no contiene ítems activos.');
    }
    $itemsPrepared = opesp_prepare_items_for_migration($itemsForm, $legacyInfo['row']);

    mysqli_begin_transaction($conexion);
    try {
        $steps[] = 'Inicio de migración transaccional.';

        $semestreId = opesp_ensure_semester_2024ii($conexion, $id_py, $periodoRow, $steps);
        if ($semestreId <= 0) {
            throw new Exception('No se pudo asegurar el semestre objetivo 2024-II.');
        }

        $usuarioCoord = isset($coordinadorPrincipal['usuario']) ? trim((string)$coordinadorPrincipal['usuario']) : '';
        if ($usuarioCoord === '') {
            throw new Exception('No se pudo determinar el código docente del coordinador principal.');
        }

        $emailGenerico = 'migracion.automatica@unitru.edu.pe';
        $celGenerico = '900000000';
        $stContacto = mysqli_prepare(
            $conexion,
            "INSERT INTO usuario_contactos (usuario, email, telefono, created_at, updated_at)
             VALUES (?, ?, ?, NOW(), NOW())
             ON DUPLICATE KEY UPDATE
                email = VALUES(email),
                telefono = VALUES(telefono),
                updated_at = NOW()"
        );
        if (!$stContacto) {
            throw new Exception('No se pudo preparar el registro de contacto.');
        }
        mysqli_stmt_bind_param($stContacto, 'sss', $usuarioCoord, $emailGenerico, $celGenerico);
        if (!mysqli_stmt_execute($stContacto)) {
            $err = mysqli_stmt_error($stContacto);
            mysqli_stmt_close($stContacto);
            throw new Exception('No se pudo registrar contacto del coordinador: ' . $err);
        }
        mysqli_stmt_close($stContacto);
        $steps[] = 'Se registró/actualizó contacto del coordinador (correo/celular genérico de migración).';

        $existingResponse = opesp_find_existing_response($conexion, $id_py, $id_formulario);
        if ($existingResponse) {
            if ($force_replace !== 1) {
                throw new Exception('El formulario ya tiene respuesta en 2024-II. Debes confirmar reemplazo completo.');
            }
            $idRespVieja = isset($existingResponse['id_respuesta']) ? (int)$existingResponse['id_respuesta'] : 0;
            if ($idRespVieja > 0) {
                $stDel = mysqli_prepare($conexion, "DELETE FROM sm_respuestas WHERE id = ? LIMIT 1");
                if (!$stDel) {
                    throw new Exception('No se pudo preparar eliminación de respuesta previa.');
                }
                mysqli_stmt_bind_param($stDel, 'i', $idRespVieja);
                if (!mysqli_stmt_execute($stDel)) {
                    $err = mysqli_stmt_error($stDel);
                    mysqli_stmt_close($stDel);
                    throw new Exception('No se pudo eliminar respuesta previa para re-migración: ' . $err);
                }
                mysqli_stmt_close($stDel);
                $steps[] = 'Se eliminó respuesta previa para reemplazo completo.';
            }
        }

        $stResp = mysqli_prepare(
            $conexion,
            "INSERT INTO sm_respuestas (id_py, id_formulario, id_cronograma, id_semestre, estado, creado_at, actualizado_at)
             VALUES (?, ?, ?, ?, 1, NOW(), NOW())"
        );
        if (!$stResp) {
            throw new Exception('No se pudo preparar la creación de respuesta destino.');
        }
        mysqli_stmt_bind_param($stResp, 'iiii', $id_py, $id_formulario, $id_cronograma, $semestreId);
        if (!mysqli_stmt_execute($stResp)) {
            $err = mysqli_stmt_error($stResp);
            mysqli_stmt_close($stResp);
            throw new Exception('No se pudo crear respuesta destino: ' . $err);
        }
        $id_respuesta_nueva = (int)mysqli_insert_id($conexion);
        mysqli_stmt_close($stResp);
        if ($id_respuesta_nueva <= 0) {
            throw new Exception('No se pudo obtener el ID de respuesta creada.');
        }
        $steps[] = 'Se creó la respuesta destino del formulario semestral 2024-II.';

        foreach ($itemsPrepared as $idx => $item) {
            opesp_insert_migrated_item_value($conexion, $id_respuesta_nueva, $item, isset($item['valor']) ? $item['valor'] : '');
            $n = $idx + 1;
            $steps[] = 'Se llenó ítem ' . str_pad((string)$n, 2, '0', STR_PAD_LEFT) . ': ' . (string)$item['nombre'];
        }

        opesp_create_full_approval_route($conexion, $id_respuesta_nueva, $steps);

        $stEstadoFinal = mysqli_prepare(
            $conexion,
            "UPDATE sm_respuestas SET estado = 2, actualizado_at = NOW() WHERE id = ? LIMIT 1"
        );
        if (!$stEstadoFinal) {
            throw new Exception('No se pudo preparar el cierre de estado final.');
        }
        mysqli_stmt_bind_param($stEstadoFinal, 'i', $id_respuesta_nueva);
        if (!mysqli_stmt_execute($stEstadoFinal)) {
            $err = mysqli_stmt_error($stEstadoFinal);
            mysqli_stmt_close($stEstadoFinal);
            throw new Exception('No se pudo marcar estado final aprobado en sm_respuestas: ' . $err);
        }
        mysqli_stmt_close($stEstadoFinal);
        $steps[] = 'La respuesta quedó en estado aprobado.';

        $idUsuarioHistorial = opesp_get_session_user_id($conexion);
        opesp_insert_migration_history($conexion, $id_py, $id_periodo, $id_formulario, $id_respuesta_nueva, $idUsuarioHistorial);
        $steps[] = 'Se registró historial_estados con acción Migración 2024-II.';

        mysqli_commit($conexion);
        $steps[] = 'Migración completada con aprobación total.';

        $summaryAfter = opesp_get_global_migration_summary($conexion);
        opesp_json_ok(array(
            'message' => 'Migración 2024-II completada y aprobada en todas las oficinas.',
            'id_py' => $id_py,
            'id_formulario' => $id_formulario,
            'id_respuesta' => $id_respuesta_nueva,
            'steps' => $steps,
            'summary' => $summaryAfter
        ));
    } catch (Throwable $e) {
        mysqli_rollback($conexion);
        error_log('op_especiales migracion: rollback por error en proyecto ' . $id_py . ': ' . $e->getMessage());
        $steps[] = 'Se canceló la migración y se revirtieron todos los cambios.';
        opesp_json_error(500, 'Falló la migración y se ejecutó rollback total: ' . $e->getMessage(), array(
            'steps' => $steps,
            'summary' => $summary
        ));
    }
}

opesp_json_error(400, 'Acción de migración no reconocida.');

