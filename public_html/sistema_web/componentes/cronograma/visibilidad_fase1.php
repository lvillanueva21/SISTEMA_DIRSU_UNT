<?php
if (!function_exists('rsu_vf1_codes')) {
    function rsu_vf1_codes()
    {
        return array(
            'F1-GENERALIDADES' => 'Generalidades',
            'F1-PLAN' => 'Plan de proyecto',
            'F1-ANEXOS' => 'Anexos'
        );
    }
}

if (!function_exists('rsu_vf1_normalize_datetime')) {
    function rsu_vf1_normalize_datetime($value, $fallback)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return $fallback;
        }

        $value = str_replace('T', ' ', $value);
        if (strlen($value) === 16) {
            $value .= ':00';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return $fallback;
        }

        return date('Y-m-d H:i:s', $ts);
    }
}

if (!function_exists('rsu_vf1_datetime_for_input')) {
    function rsu_vf1_datetime_for_input($value)
    {
        $value = trim((string)$value);
        if ($value === '') {
            return '';
        }

        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }

        return date('Y-m-d\TH:i', $ts);
    }
}

if (!function_exists('rsu_vf1_get_cronograma_by_id')) {
    function rsu_vf1_get_cronograma_by_id(mysqli $conexion, $idCronograma)
    {
        $idCronograma = (int)$idCronograma;
        if ($idCronograma <= 0) {
            return null;
        }

        $sql = "SELECT c.id, c.id_periodo, c.tipo, c.activo, c.apertura, c.cierre, p.nombre AS periodo
                FROM sm_cronogramas c
                INNER JOIN periodos p ON p.id = c.id_periodo
                WHERE c.id = ?
                LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return null;
        }

        mysqli_stmt_bind_param($stmt, 'i', $idCronograma);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        return $row ?: null;
    }
}

if (!function_exists('rsu_vf1_get_active_presentacion')) {
    function rsu_vf1_get_active_presentacion(mysqli $conexion)
    {
        $sql = "SELECT c.id, c.id_periodo, c.tipo, c.activo, c.apertura, c.cierre, p.nombre AS periodo
                FROM sm_cronogramas c
                INNER JOIN periodos p ON p.id = c.id_periodo
                WHERE c.tipo = 1 AND c.activo = 1
                ORDER BY c.fecha_creacion DESC, c.id DESC
                LIMIT 1";
        $res = mysqli_query($conexion, $sql);
        if (!$res) {
            return null;
        }
        $row = mysqli_fetch_assoc($res);
        return $row ?: null;
    }
}

if (!function_exists('rsu_vf1_get_rows_by_period')) {
    function rsu_vf1_get_rows_by_period(mysqli $conexion, $periodo)
    {
        $periodo = trim((string)$periodo);
        if ($periodo === '') {
            return array();
        }

        $codes = array_keys(rsu_vf1_codes());
        $placeholders = implode(',', array_fill(0, count($codes), '?'));
        $types = 's' . str_repeat('s', count($codes));

        $sql = "SELECT id, periodo, codigo, descripcion, inicio, fin, estado
                FROM cronogramas
                WHERE periodo = ?
                  AND codigo IN ($placeholders)";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return array();
        }

        $params = array_merge(array($periodo), $codes);
        mysqli_stmt_bind_param($stmt, $types, ...$params);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $rows = array();
        while ($row = $res ? mysqli_fetch_assoc($res) : null) {
            $rows[$row['codigo']] = $row;
        }
        mysqli_stmt_close($stmt);

        return $rows;
    }
}

if (!function_exists('rsu_vf1_find_source_rows')) {
    function rsu_vf1_find_source_rows(mysqli $conexion, $excludeCronogramaId)
    {
        $excludeCronogramaId = (int)$excludeCronogramaId;

        $sql = "SELECT c.id, p.nombre AS periodo
                FROM sm_cronogramas c
                INNER JOIN periodos p ON p.id = c.id_periodo
                WHERE c.tipo = 1
                  AND c.id <> ?
                ORDER BY c.activo DESC, c.fecha_creacion DESC, c.id DESC";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return array();
        }

        mysqli_stmt_bind_param($stmt, 'i', $excludeCronogramaId);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        if (!$res) {
            mysqli_stmt_close($stmt);
            return array();
        }

        while ($cron = mysqli_fetch_assoc($res)) {
            $rows = rsu_vf1_get_rows_by_period($conexion, $cron['periodo']);
            if (!empty($rows)) {
                mysqli_stmt_close($stmt);
                return $rows;
            }
        }

        mysqli_stmt_close($stmt);
        return array();
    }
}

if (!function_exists('rsu_vf1_default_description')) {
    function rsu_vf1_default_description($codigo)
    {
        if ($codigo === 'F1-GENERALIDADES') {
            return 'Habilita la edicion de la interfaz Generalidades.';
        }
        if ($codigo === 'F1-PLAN') {
            return 'Habilita la edicion de la interfaz Plan de proyecto.';
        }
        return 'Habilita la edicion de la interfaz Anexos.';
    }
}

if (!function_exists('rsu_vf1_ensure_rows_for_period')) {
    function rsu_vf1_ensure_rows_for_period(mysqli $conexion, $periodo, $apertura, $cierre, $excludeCronogramaId)
    {
        $periodo = trim((string)$periodo);
        if ($periodo === '') {
            return;
        }

        $codes = rsu_vf1_codes();
        $currentRows = rsu_vf1_get_rows_by_period($conexion, $periodo);
        $sourceRows = rsu_vf1_find_source_rows($conexion, (int)$excludeCronogramaId);

        $aperturaNorm = rsu_vf1_normalize_datetime($apertura, date('Y-m-d H:i:s'));
        $cierreNorm = rsu_vf1_normalize_datetime($cierre, $aperturaNorm);

        $sqlIns = "INSERT INTO cronogramas (periodo, codigo, descripcion, inicio, fin, estado)
                   VALUES (?, ?, ?, ?, ?, ?)";
        $stmtIns = mysqli_prepare($conexion, $sqlIns);
        if (!$stmtIns) {
            return;
        }

        foreach ($codes as $codigo => $nombre) {
            if (isset($currentRows[$codigo])) {
                continue;
            }

            $descripcion = rsu_vf1_default_description($codigo);
            $inicio = $aperturaNorm;
            $fin = $cierreNorm;
            $estado = 0;

            if (isset($sourceRows[$codigo])) {
                $src = $sourceRows[$codigo];
                $descripcion = trim((string)$src['descripcion']) !== '' ? (string)$src['descripcion'] : $descripcion;
                $inicio = rsu_vf1_normalize_datetime($src['inicio'], $inicio);
                $fin = rsu_vf1_normalize_datetime($src['fin'], $fin);
                $estado = (int)$src['estado'] === 1 ? 1 : 0;
            }

            mysqli_stmt_bind_param($stmtIns, 'sssssi', $periodo, $codigo, $descripcion, $inicio, $fin, $estado);
            mysqli_stmt_execute($stmtIns);
        }

        mysqli_stmt_close($stmtIns);
    }
}

if (!function_exists('rsu_vf1_get_rows_for_cronograma')) {
    function rsu_vf1_get_rows_for_cronograma(mysqli $conexion, $idCronograma)
    {
        $cron = rsu_vf1_get_cronograma_by_id($conexion, $idCronograma);
        if (!$cron || (int)$cron['tipo'] !== 1) {
            return array('success' => false, 'msg' => 'Cronograma invalido para visibilidad F1.');
        }

        rsu_vf1_ensure_rows_for_period(
            $conexion,
            $cron['periodo'],
            $cron['apertura'],
            $cron['cierre'],
            (int)$cron['id']
        );

        $rowsByCode = rsu_vf1_get_rows_by_period($conexion, $cron['periodo']);
        $outputRows = array();
        foreach (rsu_vf1_codes() as $codigo => $nombre) {
            $row = isset($rowsByCode[$codigo]) ? $rowsByCode[$codigo] : null;
            $outputRows[] = array(
                'codigo' => $codigo,
                'nombre' => $nombre,
                'descripcion' => $row ? (string)$row['descripcion'] : rsu_vf1_default_description($codigo),
                'inicio' => $row ? rsu_vf1_datetime_for_input($row['inicio']) : rsu_vf1_datetime_for_input($cron['apertura']),
                'fin' => $row ? rsu_vf1_datetime_for_input($row['fin']) : rsu_vf1_datetime_for_input($cron['cierre']),
                'estado' => $row ? (int)$row['estado'] : 0
            );
        }

        return array(
            'success' => true,
            'data' => array(
                'id_cronograma' => (int)$cron['id'],
                'periodo' => (string)$cron['periodo'],
                'rows' => $outputRows
            )
        );
    }
}

if (!function_exists('rsu_vf1_save_rows_for_cronograma')) {
    function rsu_vf1_save_rows_for_cronograma(mysqli $conexion, $idCronograma, $rowsInput)
    {
        $cron = rsu_vf1_get_cronograma_by_id($conexion, $idCronograma);
        if (!$cron || (int)$cron['tipo'] !== 1) {
            return array('success' => false, 'msg' => 'Cronograma invalido para visibilidad F1.');
        }

        if (!is_array($rowsInput)) {
            return array('success' => false, 'msg' => 'No se recibieron filas para guardar.');
        }

        rsu_vf1_ensure_rows_for_period(
            $conexion,
            $cron['periodo'],
            $cron['apertura'],
            $cron['cierre'],
            (int)$cron['id']
        );

        $codes = rsu_vf1_codes();
        $existing = rsu_vf1_get_rows_by_period($conexion, $cron['periodo']);

        mysqli_begin_transaction($conexion);
        try {
            $sqlUp = "UPDATE cronogramas
                      SET descripcion = ?, inicio = ?, fin = ?, estado = ?
                      WHERE id = ?";
            $stmtUp = mysqli_prepare($conexion, $sqlUp);
            if (!$stmtUp) {
                throw new Exception('No se pudo preparar la actualizacion de visibilidad.');
            }

            foreach ($codes as $codigo => $nombre) {
                if (!isset($existing[$codigo])) {
                    continue;
                }

                $rowIn = isset($rowsInput[$codigo]) && is_array($rowsInput[$codigo]) ? $rowsInput[$codigo] : array();
                $descripcion = isset($rowIn['descripcion']) ? trim((string)$rowIn['descripcion']) : rsu_vf1_default_description($codigo);
                if ($descripcion === '') {
                    $descripcion = rsu_vf1_default_description($codigo);
                }
                if (function_exists('mb_substr')) {
                    $descripcion = mb_substr($descripcion, 0, 250, 'UTF-8');
                } else {
                    $descripcion = substr($descripcion, 0, 250);
                }

                $inicio = rsu_vf1_normalize_datetime(
                    isset($rowIn['inicio']) ? $rowIn['inicio'] : '',
                    rsu_vf1_normalize_datetime($cron['apertura'], date('Y-m-d H:i:s'))
                );
                $fin = rsu_vf1_normalize_datetime(
                    isset($rowIn['fin']) ? $rowIn['fin'] : '',
                    rsu_vf1_normalize_datetime($cron['cierre'], $inicio)
                );
                $estado = (isset($rowIn['estado']) && (int)$rowIn['estado'] === 1) ? 1 : 0;
                $idRow = (int)$existing[$codigo]['id'];

                if (strtotime($inicio) === false || strtotime($fin) === false) {
                    throw new Exception('Las fechas de visibilidad son invalidas.');
                }
                if (strtotime($inicio) > strtotime($fin)) {
                    throw new Exception('La fecha inicio no puede ser mayor que la fecha fin.');
                }

                mysqli_stmt_bind_param($stmtUp, 'sssii', $descripcion, $inicio, $fin, $estado, $idRow);
                if (!mysqli_stmt_execute($stmtUp)) {
                    throw new Exception('No se pudo actualizar la configuracion de visibilidad.');
                }
            }

            mysqli_stmt_close($stmtUp);
            mysqli_commit($conexion);
        } catch (Throwable $e) {
            mysqli_rollback($conexion);
            return array('success' => false, 'msg' => $e->getMessage());
        }

        return array('success' => true, 'msg' => 'Visibilidad de interfaces guardada correctamente.');
    }
}

if (!function_exists('rsu_vf1_can_access_interface')) {
    function rsu_vf1_can_access_interface(mysqli $conexion, $codigo)
    {
        $codigo = trim((string)$codigo);
        $codes = rsu_vf1_codes();
        if (!isset($codes[$codigo])) {
            return array('permitido' => false, 'motivo' => 'codigo_no_soportado');
        }

        $cron = rsu_vf1_get_active_presentacion($conexion);
        if (!$cron) {
            return array('permitido' => false, 'motivo' => 'sin_cronograma_activo');
        }

        $sql = "SELECT id, descripcion, inicio, fin, estado
                FROM cronogramas
                WHERE periodo = ?
                  AND codigo = ?
                  AND estado = 1
                ORDER BY actualizado_en DESC, id DESC
                LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return array('permitido' => false, 'motivo' => 'error_prepare');
        }

        mysqli_stmt_bind_param($stmt, 'ss', $cron['periodo'], $codigo);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = $res ? mysqli_fetch_assoc($res) : null;
        mysqli_stmt_close($stmt);

        if (!$row) {
            return array('permitido' => false, 'motivo' => 'sin_regla_activa');
        }

        $tz = new DateTimeZone('America/Lima');
        $ahora = new DateTime('now', $tz);
        $inicio = new DateTime((string)$row['inicio'], $tz);
        $fin = new DateTime((string)$row['fin'], $tz);

        if ($ahora < $inicio || $ahora > $fin) {
            return array('permitido' => false, 'motivo' => 'fuera_de_rango');
        }

        return array(
            'permitido' => true,
            'motivo' => 'ok',
            'periodo' => (string)$cron['periodo'],
            'regla' => $row
        );
    }
}

