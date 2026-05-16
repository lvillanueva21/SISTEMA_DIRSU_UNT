<?php
declare(strict_types=1);

/**
 * modules/noticias/noticias_api.php
 * API JSON para noticias (HOME / GET / LISTAR / SAVE / DELETE)
 * - Responde SIEMPRE JSON
 * - Debug detallado SOLO si: debug=1 y rol permitido
 * - Fix profesional: bind_param() solo con VARIABLES (no ternarios/expresiones)
 */

header('Content-Type: application/json; charset=UTF-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');

@ini_set('display_errors', '0');
@ini_set('html_errors', '0');
if (!ob_get_level()) { @ob_start(); }

$__dbg = false; // se activa luego, cuando ya sepamos el rol

// Captura excepción/errores fatales y devuelve JSON
set_exception_handler(function ($e) use (&$__dbg) {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    http_response_code(500);

    $out = ['ok' => false, 'error' => 'Error de servidor.'];

    if ($__dbg) {
        $out['error'] = 'ERROR DEBUG (servidor)';
        $out['debug'] = [
            'type'    => get_class($e),
            'message' => $e->getMessage(),
            'file'    => $e->getFile(),
            'line'    => $e->getLine(),
            'trace'   => $e->getTraceAsString(),
        ];

        // Si es error de MySQLi, añade más info (si existe)
        if ($e instanceof mysqli_sql_exception) {
            $out['debug']['sql_state'] = $e->getSqlState();
            $out['debug']['code']      = $e->getCode();
        }
    }

    echo json_encode($out, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
});

set_error_handler(function ($severity, $message, $file, $line) {
    // Convierte warnings/notices en excepción para que el handler JSON los muestre
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Dependencias (después de handlers para que cualquier error salga en JSON)
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/gestion_archivos.php';

date_default_timezone_set('America/Lima');

// MySQLi en modo estricto (errores SQL => excepciones => JSON debug)
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function json_out(array $arr, int $httpCode = 200): void {
    if (function_exists('ob_get_length') && ob_get_length()) { @ob_clean(); }
    http_response_code($httpCode);
    echo json_encode($arr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_param(string $k) {
    if (isset($_POST[$k])) return $_POST[$k];
    if (isset($_GET[$k])) return $_GET[$k];
    return null;
}

function clean_page_key($s): string {
    $s = is_string($s) ? trim($s) : '';
    if ($s === '') return '';
    if (!preg_match('/^[a-z0-9\-\_]+$/i', $s)) return '';
    return $s;
}

function can_manage_role($user): bool {
    $rc = ($user && isset($user['rol']['codigo'])) ? (string)$user['rol']['codigo'] : '';
    return in_array($rc, ['desarrollador','director','secretaria'], true);
}

function upload_error_msg(int $code): string {
    if ($code === UPLOAD_ERR_OK) return '';
    if ($code === UPLOAD_ERR_INI_SIZE) return 'El archivo excede el límite del servidor (upload_max_filesize).';
    if ($code === UPLOAD_ERR_FORM_SIZE) return 'El archivo excede el límite del formulario.';
    if ($code === UPLOAD_ERR_PARTIAL) return 'El archivo se subió parcialmente.';
    if ($code === UPLOAD_ERR_NO_FILE) return 'No se seleccionó archivo.';
    if ($code === UPLOAD_ERR_NO_TMP_DIR) return 'Falta la carpeta temporal del servidor.';
    if ($code === UPLOAD_ERR_CANT_WRITE) return 'No se pudo escribir el archivo en disco.';
    if ($code === UPLOAD_ERR_EXTENSION) return 'Subida bloqueada por extensión del servidor.';
    return 'Error al subir archivo.';
}

/**
 * bind_param requiere variables por referencia:
 * - NO se puede pasar ...$params directamente
 * - NO se puede pasar ternarios/expresiones
 */
function stmt_bind_params(mysqli_stmt $st, string $types, array &$params): void {
    $bind = [$types];
    foreach ($params as $k => $v) {
        $bind[] = &$params[$k];
    }
    call_user_func_array([$st, 'bind_param'], $bind);
}

function fetch_noticia(mysqli $mysqli, int $id, string $pageKey, bool $allowHidden): ?array {
    $sql = "
        SELECT id, page_key, titulo, resumen, cuerpo, imagen_portada, estado, destacada,
               DATE_FORMAT(publicada_en, '%Y-%m-%d %H:%i:%s') AS publicada_en,
               etiquetas_csv, creado_por, actualizado_por,
               DATE_FORMAT(creado_en, '%Y-%m-%d %H:%i:%s') AS creado_en,
               DATE_FORMAT(actualizado_en, '%Y-%m-%d %H:%i:%s') AS actualizado_en
        FROM l2601_noticias
        WHERE id = ? AND page_key = ?
        " . ($allowHidden ? "" : " AND estado <> 'oculta' ") . "
        LIMIT 1
    ";
    $st = $mysqli->prepare($sql);
    $st->bind_param('is', $id, $pageKey);
    $st->execute();
    $res = $st->get_result();
    $row = $res->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function log_action(mysqli $mysqli, string $pageKey, string $accion, ?int $usuarioId, ?int $noticiaId, ?array $snapshotArr): void {
    $snap = $snapshotArr ? json_encode($snapshotArr, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null;
    $sql = "INSERT INTO l2601_noticias_log (noticia_id, page_key, accion, usuario_id, snapshot_json)
            VALUES (?,?,?,?,?)";
    $st = $mysqli->prepare($sql);

    $nid = $noticiaId !== null ? (int)$noticiaId : null;
    $uid = $usuarioId !== null ? (int)$usuarioId : null;

    $st->bind_param('issis', $nid, $pageKey, $accion, $uid, $snap);
    $st->execute();
    $st->close();
}

// ===== Contexto / permisos / debug =====
$user = auth_user();
$canManage = can_manage_role($user);

$__dbgParam = (
    (isset($_GET['debug']) && (string)$_GET['debug'] === '1') ||
    (isset($_POST['debug']) && (string)$_POST['debug'] === '1')
);
$__dbg = ($__dbgParam && $canManage);

$action = get_param('action');
$action = is_string($action) ? trim($action) : '';

$pageKey = clean_page_key(get_param('page_key'));
if ($pageKey === '') {
    json_out(['ok' => false, 'error' => 'page_key inválido.'], 400);
}

$mysqli = db();

// =====================
// PUBLIC: HOME
// =====================
if ($action === 'home') {
    $where = "page_key = ? AND estado <> 'oculta'";

    // Featured (destacada=1) si existe
    $sqlF = "
        SELECT id, page_key, titulo, resumen, cuerpo, imagen_portada, estado, destacada,
               DATE_FORMAT(publicada_en, '%Y-%m-%d %H:%i:%s') AS publicada_en,
               etiquetas_csv
        FROM l2601_noticias
        WHERE $where AND destacada = 1
        ORDER BY (publicada_en IS NULL) ASC, publicada_en DESC, creado_en DESC
        LIMIT 1
    ";
    $st = $mysqli->prepare($sqlF);
    $st->bind_param('s', $pageKey);
    $st->execute();
    $res = $st->get_result();
    $featured = $res->fetch_assoc();
    $st->close();

    // Si no hay destacada, toma la última
    if (!$featured) {
        $sqlF2 = "
            SELECT id, page_key, titulo, resumen, cuerpo, imagen_portada, estado, destacada,
                   DATE_FORMAT(publicada_en, '%Y-%m-%d %H:%i:%s') AS publicada_en,
                   etiquetas_csv
            FROM l2601_noticias
            WHERE $where
            ORDER BY (publicada_en IS NULL) ASC, publicada_en DESC, creado_en DESC
            LIMIT 1
        ";
        $st = $mysqli->prepare($sqlF2);
        $st->bind_param('s', $pageKey);
        $st->execute();
        $res = $st->get_result();
        $featured = $res->fetch_assoc();
        $st->close();
    }

    $excludeId = $featured ? (int)$featured['id'] : 0;

    // Minis
    $sqlM = "
        SELECT id, page_key, titulo, resumen, cuerpo, imagen_portada, estado, destacada,
               DATE_FORMAT(publicada_en, '%Y-%m-%d %H:%i:%s') AS publicada_en,
               etiquetas_csv
        FROM l2601_noticias
        WHERE $where " . ($excludeId > 0 ? " AND id <> ? " : "") . "
        ORDER BY (publicada_en IS NULL) ASC, publicada_en DESC, creado_en DESC
        LIMIT 5
    ";

    if ($excludeId > 0) {
        $st = $mysqli->prepare($sqlM);
        $st->bind_param('si', $pageKey, $excludeId);
    } else {
        $st = $mysqli->prepare($sqlM);
        $st->bind_param('s', $pageKey);
    }

    $st->execute();
    $res = $st->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) $items[] = $r;
    $st->close();

    json_out(['ok' => true, 'featured' => $featured, 'items' => $items]);
}

// =====================
// PUBLIC: GET
// =====================
if ($action === 'get') {
    $id = (int)get_param('id');
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);

    $row = fetch_noticia($mysqli, $id, $pageKey, $canManage);
    if (!$row) json_out(['ok' => false, 'error' => 'No encontrado.'], 404);

    json_out(['ok' => true, 'item' => $row]);
}

// =====================
// PUBLIC: LISTAR
// =====================
if ($action === 'listar') {
    $page = (int)get_param('page');
    if ($page < 1) $page = 1;

    $per = 10;
    $off = ($page - 1) * $per;

    $desde  = is_string(get_param('desde')) ? trim((string)get_param('desde')) : '';
    $hasta  = is_string(get_param('hasta')) ? trim((string)get_param('hasta')) : '';
    $q      = is_string(get_param('q')) ? trim((string)get_param('q')) : '';
    $estado = is_string(get_param('estado')) ? trim((string)get_param('estado')) : '';

    $where  = "page_key = ?";
    $types  = "s";
    $params = [$pageKey];

    if (!$canManage) {
        $where .= " AND estado <> 'oculta'";
    }

    if ($estado !== '') {
        if (!($estado === 'oculta' && !$canManage)) {
            $where .= " AND estado = ?";
            $types .= "s";
            $params[] = $estado;
        }
    }

    if ($q !== '') {
        $where .= " AND titulo LIKE ?";
        $types .= "s";
        $params[] = '%' . $q . '%';
    }

    if ($desde !== '') {
        $where .= " AND (publicada_en IS NOT NULL AND DATE(publicada_en) >= ?)";
        $types .= "s";
        $params[] = $desde;
    }

    if ($hasta !== '') {
        $where .= " AND (publicada_en IS NOT NULL AND DATE(publicada_en) <= ?)";
        $types .= "s";
        $params[] = $hasta;
    }

    // total
    $sqlT = "SELECT COUNT(*) AS c FROM l2601_noticias WHERE $where";
    $st = $mysqli->prepare($sqlT);
    stmt_bind_params($st, $types, $params);
    $st->execute();
    $res = $st->get_result();
    $total = 0;
    if ($r = $res->fetch_assoc()) $total = (int)$r['c'];
    $st->close();

    // items
    $sql = "
        SELECT id, page_key, titulo, resumen, imagen_portada, estado, destacada,
               DATE_FORMAT(publicada_en, '%Y-%m-%d %H:%i:%s') AS publicada_en
        FROM l2601_noticias
        WHERE $where
        ORDER BY (publicada_en IS NULL) ASC, publicada_en DESC, creado_en DESC
        LIMIT $per OFFSET $off
    ";
    $st = $mysqli->prepare($sql);
    stmt_bind_params($st, $types, $params);
    $st->execute();
    $res = $st->get_result();
    $items = [];
    while ($r = $res->fetch_assoc()) $items[] = $r;
    $st->close();

    json_out([
        'ok'       => true,
        'items'    => $items,
        'total'    => $total,
        'per_page' => $per,
        'page'     => $page
    ]);
}

// =====================
// ADMIN: SAVE
// =====================
if ($action === 'save') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'error' => 'Método no permitido.'], 405);
    }
    if (!$canManage) {
        json_out(['ok' => false, 'error' => 'No autorizado.'], 403);
    }
    if (!csrf_validate(get_param('csrf'))) {
        json_out(['ok' => false, 'error' => 'CSRF inválido. Recarga e intenta otra vez.'], 400);
    }

    $id        = (int)get_param('id');
    $titulo    = is_string(get_param('titulo')) ? trim((string)get_param('titulo')) : '';
    $resumen   = is_string(get_param('resumen')) ? trim((string)get_param('resumen')) : '';
    $cuerpo    = is_string(get_param('cuerpo')) ? trim((string)get_param('cuerpo')) : '';
    $estado    = is_string(get_param('estado')) ? trim((string)get_param('estado')) : 'publicada';
    $destacada = get_param('destacada');
    $pub_fecha = is_string(get_param('pub_fecha')) ? trim((string)get_param('pub_fecha')) : '';
    $pub_hora  = is_string(get_param('pub_hora')) ? trim((string)get_param('pub_hora')) : '';
    $tags      = is_string(get_param('etiquetas_csv')) ? trim((string)get_param('etiquetas_csv')) : '';

    if ($titulo === '') json_out(['ok' => false, 'error' => 'El título es obligatorio.'], 400);
    if (mb_strlen($titulo) > 200)  $titulo  = mb_substr($titulo, 0, 200);
    if (mb_strlen($resumen) > 500) $resumen = mb_substr($resumen, 0, 500);
    if (mb_strlen($tags) > 500)    $tags    = mb_substr($tags, 0, 500);

    $validEstados = ['publicada','archivada','oculta'];
    if (!in_array($estado, $validEstados, true)) $estado = 'publicada';

    $dest = ($destacada === '1' || $destacada === 1 || $destacada === true) ? 1 : 0;

    // publicada_en (nullable)
    $publicada_en = null;
    if ($pub_fecha !== '' || $pub_hora !== '') {
        $f = ($pub_fecha !== '') ? $pub_fecha : date('Y-m-d');
        $h = ($pub_hora !== '') ? ($pub_hora . ':00') : '00:00:00';
        $publicada_en = $f . ' ' . $h;
    }

    $uid = $user ? (int)$user['id'] : null;

    // ===== IMPORTANTÍSIMO: variables para bind (nada de ternarios en bind_param)
    $resumen_db = ($resumen !== '') ? $resumen : null;
    $cuerpo_db  = ($cuerpo !== '') ? $cuerpo : null;
    $tags_db    = ($tags !== '') ? $tags : null;

    // foto (opcional)
    $newFotoRel = null;
    $hasFile = isset($_FILES['foto']) && is_array($_FILES['foto'])
        && isset($_FILES['foto']['error'])
        && ((int)$_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE);

    if ($hasFile) {
        $err = upload_error_msg((int)$_FILES['foto']['error']);
        if ($err !== '' && (int)$_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
            json_out(['ok' => false, 'error' => $err], 400);
        }
    }

    // ===== UPDATE =====
    if ($id > 0) {
        $old = fetch_noticia($mysqli, $id, $pageKey, true);
        if (!$old) json_out(['ok' => false, 'error' => 'No encontrado.'], 404);

        // Subir nueva foto si vino
        if ($hasFile && (int)$_FILES['foto']['error'] === UPLOAD_ERR_OK) {
            try {
                $saved = ga_save_upload($mysqli, $_FILES['foto'], 'foto_noticia', 'noticia', 'noticias', 'noticia', $id);
                $newFotoRel = (string)$saved['ruta_relativa'];
            } catch (Throwable $e) {
                json_out(['ok' => false, 'error' => 'No se pudo guardar la foto. Revisa permisos de carpeta almacen/.'], 500);
            }
        }

        // Si es destacada, apagar otras destacadas en la misma page
        if ($dest === 1) {
            $st0 = $mysqli->prepare("UPDATE l2601_noticias SET destacada = 0 WHERE page_key = ? AND id <> ?");
            $st0->bind_param('si', $pageKey, $id);
            $st0->execute();
            $st0->close();
        }

        $sql = "
            UPDATE l2601_noticias
            SET titulo=?, resumen=?, cuerpo=?, estado=?, destacada=?, publicada_en=?, etiquetas_csv=?, actualizado_por=?
            " . ($newFotoRel ? ", imagen_portada=? " : "") . "
            WHERE id=? AND page_key=? LIMIT 1
        ";

        if ($newFotoRel) {
            // s s s s i s s i s i s
            $st = $mysqli->prepare($sql);
            $st->bind_param(
                'ssssissisis',
                $titulo,
                $resumen_db,
                $cuerpo_db,
                $estado,
                $dest,
                $publicada_en,
                $tags_db,
                $uid,
                $newFotoRel,
                $id,
                $pageKey
            );
        } else {
            // s s s s i s s i i s
            $st = $mysqli->prepare($sql);
            $st->bind_param(
                'ssssissiis',
                $titulo,
                $resumen_db,
                $cuerpo_db,
                $estado,
                $dest,
                $publicada_en,
                $tags_db,
                $uid,
                $id,
                $pageKey
            );
        }

        $st->execute();
        $st->close();

        // borrar foto vieja si reemplazó
        if ($newFotoRel && !empty($old['imagen_portada'])) {
            ga_delete_rel((string)$old['imagen_portada']);
        }

        $now = fetch_noticia($mysqli, $id, $pageKey, true);
        log_action($mysqli, $pageKey, 'editar', $uid, $id, $now);

        json_out(['ok' => true, 'id' => $id]);
    }

    // ===== INSERT =====
    $sqlI = "
        INSERT INTO l2601_noticias
        (page_key, titulo, resumen, cuerpo, imagen_portada, estado, destacada, publicada_en, etiquetas_csv, creado_por, actualizado_por)
        VALUES (?,?,?,?,?,?,?,?,?,?,?)
    ";
    $img_db = null;

    $st = $mysqli->prepare($sqlI);
    $st->bind_param(
        'ssssssissii',
        $pageKey,
        $titulo,
        $resumen_db,
        $cuerpo_db,
        $img_db,
        $estado,
        $dest,
        $publicada_en,
        $tags_db,
        $uid,
        $uid
    );
    $st->execute();
    $newId = (int)$mysqli->insert_id;
    $st->close();

    // Si destacada, apagar otras
    if ($dest === 1) {
        $st0 = $mysqli->prepare("UPDATE l2601_noticias SET destacada = 0 WHERE page_key = ? AND id <> ?");
        $st0->bind_param('si', $pageKey, $newId);
        $st0->execute();
        $st0->close();
    }

    // Subir foto si vino
    if ($hasFile && (int)$_FILES['foto']['error'] === UPLOAD_ERR_OK) {
        try {
            $saved = ga_save_upload($mysqli, $_FILES['foto'], 'foto_noticia', 'noticia', 'noticias', 'noticia', $newId);
            $newFotoRel = (string)$saved['ruta_relativa'];

            $stU = $mysqli->prepare("UPDATE l2601_noticias SET imagen_portada=? WHERE id=? AND page_key=? LIMIT 1");
            $stU->bind_param('sis', $newFotoRel, $newId, $pageKey);
            $stU->execute();
            $stU->close();
        } catch (Throwable $e) {
            // No rompemos el insert, pero queda sin foto
        }
    }

    $now = fetch_noticia($mysqli, $newId, $pageKey, true);
    log_action($mysqli, $pageKey, 'crear', $uid, $newId, $now);

    json_out(['ok' => true, 'id' => $newId]);
}

// =====================
// ADMIN: DELETE
// =====================
if ($action === 'delete') {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        json_out(['ok' => false, 'error' => 'Método no permitido.'], 405);
    }
    if (!$canManage) {
        json_out(['ok' => false, 'error' => 'No autorizado.'], 403);
    }
    if (!csrf_validate(get_param('csrf'))) {
        json_out(['ok' => false, 'error' => 'CSRF inválido. Recarga e intenta otra vez.'], 400);
    }

    $id = (int)get_param('id');
    if ($id <= 0) json_out(['ok' => false, 'error' => 'ID inválido.'], 400);

    $old = fetch_noticia($mysqli, $id, $pageKey, true);
    if (!$old) json_out(['ok' => false, 'error' => 'No encontrado.'], 404);

    $uid = $user ? (int)$user['id'] : null;
    log_action($mysqli, $pageKey, 'eliminar', $uid, $id, $old);

    if (!empty($old['imagen_portada'])) {
        ga_delete_rel((string)$old['imagen_portada']);
    }

    $st = $mysqli->prepare("DELETE FROM l2601_noticias WHERE id=? AND page_key=? LIMIT 1");
    $st->bind_param('is', $id, $pageKey);
    $st->execute();
    $st->close();

    json_out(['ok' => true]);
}

json_out(['ok' => false, 'error' => 'Acción inválida.'], 400);
