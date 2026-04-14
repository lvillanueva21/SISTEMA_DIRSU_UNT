<?php
/**
 * Servicio de copia fiel de items entre formularios.
 * - Copia estructura y metadatos de sm_items.
 * - Copia vinculacion en sm_formulario_items.
 * - Clona archivos locales a nuevos nombres.
 * - Operacion atomica (BD + rollback de archivos creados si falla).
 */

if (!function_exists('rsu_fc_base_dir')) {
    function rsu_fc_base_dir()
    {
        static $baseDir = null;
        if ($baseDir !== null) {
            return $baseDir;
        }

        $resolved = realpath(__DIR__ . '/../../');
        if ($resolved === false) {
            $resolved = __DIR__ . '/../../';
        }
        $baseDir = rtrim(str_replace('\\', '/', $resolved), '/');
        return $baseDir;
    }
}

if (!function_exists('rsu_fc_normalize_relative_path')) {
    function rsu_fc_normalize_relative_path($path)
    {
        $path = trim((string)$path);
        if ($path === '') {
            return '';
        }

        $path = str_replace('\\', '/', $path);
        while (strpos($path, '//') !== false) {
            $path = str_replace('//', '/', $path);
        }

        if (strpos($path, './') === 0) {
            $path = substr($path, 2);
        }

        if (strpos($path, '/') === 0) {
            $path = substr($path, 1);
        }

        return $path;
    }
}

if (!function_exists('rsu_fc_is_local_storage_path')) {
    function rsu_fc_is_local_storage_path($path)
    {
        $rel = rsu_fc_normalize_relative_path($path);
        if ($rel === '') {
            return false;
        }
        return (strpos($rel, 'files_forms/') === 0);
    }
}

if (!function_exists('rsu_fc_relative_to_abs')) {
    function rsu_fc_relative_to_abs($path)
    {
        $rel = rsu_fc_normalize_relative_path($path);
        if ($rel === '') {
            return '';
        }

        $abs = rsu_fc_base_dir() . '/' . $rel;
        return str_replace('\\', '/', $abs);
    }
}

if (!function_exists('rsu_fc_unique_copy_name')) {
    function rsu_fc_unique_copy_name($sourceRel)
    {
        $sourceRel = rsu_fc_normalize_relative_path($sourceRel);
        $dir = trim((string)dirname($sourceRel), './');
        if ($dir === '' || $dir === '.') {
            $dir = 'files_forms';
        }

        $filename = (string)pathinfo($sourceRel, PATHINFO_FILENAME);
        $ext = (string)pathinfo($sourceRel, PATHINFO_EXTENSION);
        $filename = trim($filename);
        if ($filename === '') {
            $filename = 'archivo';
        }
        $filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
        $filename = trim((string)$filename, '_');
        if ($filename === '') {
            $filename = 'archivo';
        }

        $suffix = '_copy_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
        $extPart = ($ext !== '') ? ('.' . strtolower($ext)) : '';
        $candidate = $dir . '/' . $filename . $suffix . $extPart;
        $candidateAbs = rsu_fc_relative_to_abs($candidate);
        while ($candidateAbs !== '' && file_exists($candidateAbs)) {
            $suffix = '_copy_' . date('Ymd_His') . '_' . bin2hex(random_bytes(3));
            $candidate = $dir . '/' . $filename . $suffix . $extPart;
            $candidateAbs = rsu_fc_relative_to_abs($candidate);
        }

        return $candidate;
    }
}

if (!function_exists('rsu_fc_collect_source_items')) {
    function rsu_fc_collect_source_items(mysqli $conexion, $sourceFormId)
    {
        $items = array();

        $sql = "SELECT fi.id_item,
                       fi.orden,
                       fi.activo AS fi_activo,
                       i.nombre,
                       i.tipo,
                       i.ejemplo,
                       i.img_ruta,
                       i.pdf_ruta,
                       i.link,
                       i.formato,
                       i.video,
                       i.archivo,
                       i.activo AS item_activo
                FROM sm_formulario_items fi
                INNER JOIN sm_items i ON i.id = fi.id_item
                WHERE fi.id_formulario = ?
                  AND fi.activo = 1
                ORDER BY fi.orden ASC, fi.id_item ASC";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            return null;
        }

        mysqli_stmt_bind_param($st, 'i', $sourceFormId);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        if ($res instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($res)) {
                $items[] = $row;
            }
            mysqli_free_result($res);
        }
        mysqli_stmt_close($st);

        return $items;
    }
}

if (!function_exists('rsu_fc_rollback_files')) {
    function rsu_fc_rollback_files($createdAbsFiles)
    {
        foreach ((array)$createdAbsFiles as $abs) {
            $abs = trim((string)$abs);
            if ($abs !== '' && is_file($abs)) {
                @unlink($abs);
            }
        }
    }
}

if (!function_exists('rsu_fc_validate_sources_before_copy')) {
    function rsu_fc_validate_sources_before_copy($items)
    {
        $errors = array();
        $seenOrders = array();
        $fileColumns = array('img_ruta', 'pdf_ruta', 'formato', 'archivo');

        foreach ((array)$items as $idx => $item) {
            $orden = (int)($item['orden'] ?? 0);
            if ($orden <= 0) {
                $errors[] = 'Se detectó un ítem con orden inválido en el formulario origen.';
                continue;
            }
            if (isset($seenOrders[$orden])) {
                $errors[] = 'Se detectaron órdenes duplicados en el formulario origen (orden ' . $orden . ').';
            } else {
                $seenOrders[$orden] = 1;
            }

            foreach ($fileColumns as $col) {
                $raw = trim((string)($item[$col] ?? ''));
                if ($raw === '') {
                    continue;
                }
                if (!rsu_fc_is_local_storage_path($raw)) {
                    $errors[] = 'El ítem de orden ' . $orden . ' tiene una ruta no local en ' . $col . ' (' . $raw . ').';
                    continue;
                }

                $abs = rsu_fc_relative_to_abs($raw);
                if ($abs === '' || !is_file($abs)) {
                    $errors[] = 'No se encontró el archivo origen del ítem de orden ' . $orden . ' en ' . $col . ' (' . $raw . ').';
                }
            }
        }

        return $errors;
    }
}

if (!function_exists('rsu_fc_copy_local_file')) {
    function rsu_fc_copy_local_file($sourcePath, &$createdAbsFiles)
    {
        $sourceRel = rsu_fc_normalize_relative_path($sourcePath);
        if ($sourceRel === '') {
            return '';
        }
        if (!rsu_fc_is_local_storage_path($sourceRel)) {
            throw new RuntimeException('Ruta no local detectada durante copia de archivo: ' . $sourcePath);
        }

        $sourceAbs = rsu_fc_relative_to_abs($sourceRel);
        if ($sourceAbs === '' || !is_file($sourceAbs)) {
            throw new RuntimeException('No se encontró el archivo origen: ' . $sourceRel);
        }

        $targetRel = rsu_fc_unique_copy_name($sourceRel);
        $targetAbs = rsu_fc_relative_to_abs($targetRel);
        $targetDir = dirname($targetAbs);
        if (!is_dir($targetDir)) {
            if (!@mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
                throw new RuntimeException('No se pudo crear el directorio destino para copiar archivos.');
            }
        }

        if (!@copy($sourceAbs, $targetAbs)) {
            throw new RuntimeException('No se pudo copiar el archivo "' . basename($sourceRel) . '".');
        }

        $createdAbsFiles[] = $targetAbs;
        return $targetRel;
    }
}

if (!function_exists('rsu_fc_count_active_items_for_form')) {
    function rsu_fc_count_active_items_for_form(mysqli $conexion, $formId)
    {
        $sql = "SELECT COUNT(*) AS total
                FROM sm_formulario_items
                WHERE id_formulario = ?
                  AND activo = 1";
        $st = mysqli_prepare($conexion, $sql);
        if (!$st) {
            return null;
        }

        mysqli_stmt_bind_param($st, 'i', $formId);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }
        mysqli_stmt_close($st);
        return is_array($row) ? (int)$row['total'] : null;
    }
}

if (!function_exists('rsu_fc_copy_items_between_forms')) {
    function rsu_fc_copy_items_between_forms(mysqli $conexion, $sourceFormId, $targetFormId)
    {
        $sourceFormId = (int)$sourceFormId;
        $targetFormId = (int)$targetFormId;

        if ($sourceFormId <= 0 || $targetFormId <= 0) {
            return array('success' => false, 'msg' => 'Formulario origen o destino inválido.');
        }
        if ($sourceFormId === $targetFormId) {
            return array('success' => false, 'msg' => 'El formulario destino debe ser diferente al origen.');
        }

        // Validar existencia de formularios
        $sqlForm = "SELECT id, nombre, activo
                    FROM sm_formularios
                    WHERE id IN (?, ?)";
        $stForm = mysqli_prepare($conexion, $sqlForm);
        if (!$stForm) {
            return array('success' => false, 'msg' => 'No se pudo validar los formularios.');
        }
        mysqli_stmt_bind_param($stForm, 'ii', $sourceFormId, $targetFormId);
        mysqli_stmt_execute($stForm);
        $resForm = mysqli_stmt_get_result($stForm);
        $forms = array();
        if ($resForm instanceof mysqli_result) {
            while ($row = mysqli_fetch_assoc($resForm)) {
                $forms[(int)$row['id']] = $row;
            }
            mysqli_free_result($resForm);
        }
        mysqli_stmt_close($stForm);

        if (!isset($forms[$sourceFormId]) || !isset($forms[$targetFormId])) {
            return array('success' => false, 'msg' => 'No se encontró el formulario origen o destino.');
        }

        $targetActiveCount = rsu_fc_count_active_items_for_form($conexion, $targetFormId);
        if ($targetActiveCount === null) {
            return array('success' => false, 'msg' => 'No se pudo validar ítems del formulario destino.');
        }
        if ($targetActiveCount > 0) {
            return array('success' => false, 'msg' => 'El formulario destino ya tiene ítems activos. Elimínalos primero.');
        }

        $sourceItems = rsu_fc_collect_source_items($conexion, $sourceFormId);
        if (!is_array($sourceItems)) {
            return array('success' => false, 'msg' => 'No se pudo consultar ítems del formulario origen.');
        }
        if (count($sourceItems) === 0) {
            return array('success' => false, 'msg' => 'El formulario origen no tiene ítems activos para copiar.');
        }

        $validationErrors = rsu_fc_validate_sources_before_copy($sourceItems);
        if (!empty($validationErrors)) {
            return array(
                'success' => false,
                'msg' => 'No se pudo iniciar la copia porque faltan archivos o hay rutas inválidas.',
                'details' => $validationErrors
            );
        }

        $createdAbsFiles = array();
        $copiedFiles = 0;
        $copiedItems = 0;
        $now = date('Y-m-d H:i:s');

        mysqli_begin_transaction($conexion);
        try {
            $insertItemSql = "INSERT INTO sm_items
                                (nombre, tipo, ejemplo, img_ruta, pdf_ruta, link, formato, video, archivo, activo, fecha_creacion, fecha_actualizacion)
                              VALUES
                                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stInsertItem = mysqli_prepare($conexion, $insertItemSql);
            if (!$stInsertItem) {
                throw new RuntimeException('No se pudo preparar la inserción de ítems.');
            }

            $insertPivotSql = "INSERT INTO sm_formulario_items
                                 (id_formulario, id_item, orden, activo, fecha_creacion, fecha_actualizacion)
                               VALUES
                                 (?, ?, ?, ?, ?, ?)";
            $stInsertPivot = mysqli_prepare($conexion, $insertPivotSql);
            if (!$stInsertPivot) {
                throw new RuntimeException('No se pudo preparar la vinculación de ítems al formulario destino.');
            }

            foreach ($sourceItems as $item) {
                $imgRuta = trim((string)($item['img_ruta'] ?? ''));
                $pdfRuta = trim((string)($item['pdf_ruta'] ?? ''));
                $fmtRuta = trim((string)($item['formato'] ?? ''));
                $arcRuta = trim((string)($item['archivo'] ?? ''));

                $newImgRuta = $imgRuta !== '' ? rsu_fc_copy_local_file($imgRuta, $createdAbsFiles) : null;
                $newPdfRuta = $pdfRuta !== '' ? rsu_fc_copy_local_file($pdfRuta, $createdAbsFiles) : null;
                $newFmtRuta = $fmtRuta !== '' ? rsu_fc_copy_local_file($fmtRuta, $createdAbsFiles) : null;
                $newArcRuta = $arcRuta !== '' ? rsu_fc_copy_local_file($arcRuta, $createdAbsFiles) : null;

                if ($newImgRuta !== null) { $copiedFiles++; }
                if ($newPdfRuta !== null) { $copiedFiles++; }
                if ($newFmtRuta !== null) { $copiedFiles++; }
                if ($newArcRuta !== null) { $copiedFiles++; }

                $nombre = (string)($item['nombre'] ?? '');
                $tipo = (string)($item['tipo'] ?? '');
                $ejemplo = (string)($item['ejemplo'] ?? '');
                $link = (string)($item['link'] ?? '');
                $video = (string)($item['video'] ?? '');
                $itemActivo = (int)($item['item_activo'] ?? 1);

                mysqli_stmt_bind_param(
                    $stInsertItem,
                    'sssssssssiss',
                    $nombre,
                    $tipo,
                    $ejemplo,
                    $newImgRuta,
                    $newPdfRuta,
                    $link,
                    $newFmtRuta,
                    $video,
                    $newArcRuta,
                    $itemActivo,
                    $now,
                    $now
                );
                if (!mysqli_stmt_execute($stInsertItem)) {
                    throw new RuntimeException('No se pudo insertar un ítem copiado.');
                }
                $newItemId = (int)mysqli_insert_id($conexion);

                $orden = (int)($item['orden'] ?? 0);
                $pivotActivo = (int)($item['fi_activo'] ?? 1);
                mysqli_stmt_bind_param(
                    $stInsertPivot,
                    'iiiiss',
                    $targetFormId,
                    $newItemId,
                    $orden,
                    $pivotActivo,
                    $now,
                    $now
                );
                if (!mysqli_stmt_execute($stInsertPivot)) {
                    throw new RuntimeException('No se pudo vincular un ítem copiado al formulario destino.');
                }

                $copiedItems++;
            }

            mysqli_stmt_close($stInsertItem);
            mysqli_stmt_close($stInsertPivot);
            mysqli_commit($conexion);
        } catch (Throwable $e) {
            @mysqli_rollback($conexion);
            rsu_fc_rollback_files($createdAbsFiles);
            return array(
                'success' => false,
                'msg' => 'No se pudo completar la copia. Se revirtió la operación.',
                'details' => array($e->getMessage())
            );
        }

        return array(
            'success' => true,
            'copied_items' => $copiedItems,
            'copied_files' => $copiedFiles,
            'source_form' => array('id' => $sourceFormId, 'nombre' => (string)$forms[$sourceFormId]['nombre']),
            'target_form' => array('id' => $targetFormId, 'nombre' => (string)$forms[$targetFormId]['nombre'])
        );
    }
}
