<?php
/*--------------------------------------------------------------
 | ENDPOINT JSON – Ítems de formulario
 | Tablas: sm_items · sm_formularios · sm_formulario_items
 *-------------------------------------------------------------*/
if (session_status() === PHP_SESSION_NONE) { session_start(); }

/* Conexión */
if (!isset($conexion) || !$conexion) require_once '../../componentes/db.php';
mysqli_set_charset($conexion, 'utf8mb4');
date_default_timezone_set('America/Lima');

header('Content-Type: application/json; charset=utf-8');

/* BASE pública de la app:
   Si este archivo está en /sistema_web/funciones/card_items_srv.php,
   $APP_BASE = "/sistema_web" y las URLs devueltas serán
   /sistema_web/files_forms/... */
$APP_BASE = rtrim(dirname(dirname(dirname($_SERVER['SCRIPT_NAME']))), '/');

/* ===== Helpers de tamaño ini ===== */
function _bytes_ini($val){
    $val = trim((string)$val);
    if ($val === '') return 0;
    $last = strtolower($val[strlen($val)-1]);
    $num  = (float)$val;
    switch ($last) {
        case 'g': $num *= 1024;
        case 'm': $num *= 1024;
        case 'k': $num *= 1024;
    }
    return (int)$num;
}

/* ===== Detección temprana de post_max_size excedido =====
   Cuando el body excede post_max_size, PHP deja $_POST y $_FILES vacíos. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && empty($_POST) && empty($_FILES)) {
    $cl   = (int)($_SERVER['CONTENT_LENGTH'] ?? 0);
    $pmsB = _bytes_ini(ini_get('post_max_size'));
    if ($cl > 0 && $pmsB > 0 && $cl > $pmsB) {
        http_response_code(413);
        echo json_encode([
            'success' => false,
            'code'    => 'POST_MAX_SIZE',
            'msg'     => 'El tamaño total de la petición excede el límite del servidor (post_max_size='
                         . ini_get('post_max_size') . ').'
        ]);
        exit;
    }
}

try {
    $act = $_POST['action'] ?? '';
    /* ───────────────────── Listar formularios ───────────────────── */
    if ($act === 'listar_forms') {
        $sql = "SELECT id, nombre
                FROM sm_formularios
                WHERE activo = 1
                ORDER BY id DESC";
        $res = mysqli_query($conexion, $sql);
        echo json_encode(['success' => true, 'data' => mysqli_fetch_all($res, MYSQLI_ASSOC)]);
        exit;
    }

    /* ─────────────── Listar ítems (solo vínculos activos) ─────────────── */
    if ($act === 'listar_items') {
        $idf = (int)($_POST['id_formulario'] ?? 0);
        if (!$idf) throw new Exception('ID de formulario faltante');

        $sql = "SELECT
                    fi.id_item AS id,
                    i.nombre,
                    i.tipo,
                    COALESCE(i.ejemplo,'') AS descripcion,
                    fi.orden,
                    CASE WHEN i.ejemplo IS NOT NULL AND i.ejemplo <> '' THEN 1 ELSE 0 END AS hasEjemplo,
                    CASE WHEN i.pdf_ruta IS NOT NULL AND i.pdf_ruta <> '' THEN 1 ELSE 0 END AS hasPdf,
                    CASE WHEN i.link     IS NOT NULL AND i.link     <> '' THEN 1 ELSE 0 END AS hasLink,
                    CASE WHEN i.formato  IS NOT NULL AND i.formato  <> '' THEN 1 ELSE 0 END AS hasFormato,
                    CASE WHEN i.video    IS NOT NULL AND i.video    <> '' THEN 1 ELSE 0 END AS hasVideo,
                    CASE WHEN i.archivo  IS NOT NULL AND i.archivo  <> '' THEN 1 ELSE 0 END AS hasArchivo,
                    CASE WHEN i.img_ruta IS NOT NULL AND i.img_ruta <> '' THEN 1 ELSE 0 END AS hasImg,
                    i.img_ruta,
                    i.pdf_ruta,
                    i.formato
                FROM sm_formulario_items fi
                JOIN sm_items i ON i.id = fi.id_item
                WHERE fi.id_formulario = ? AND fi.activo = 1
                ORDER BY fi.orden";
        $st = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($st, 'i', $idf);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);

        /* Construimos URLs públicas */
        $rows = [];
        while ($r = mysqli_fetch_assoc($res)) {
            $r['img_url'] = !empty($r['img_ruta']) ? $APP_BASE . $r['img_ruta'] : null;
            $r['pdf_url'] = !empty($r['pdf_ruta']) ? $APP_BASE . $r['pdf_ruta'] : null;
            // Si "formato" es una ruta local (empieza con /) exponemos su URL pública
            if (!empty($r['formato']) && substr($r['formato'], 0, 1) === '/') {
                $r['formato_url'] = $APP_BASE . $r['formato'];
            }
            unset($r['img_ruta'], $r['pdf_ruta']);
            $rows[] = $r;
        }

        echo json_encode(['success' => true, 'data' => $rows]);
        exit;
    }

    /* ───────────── Crear ítem + vincular (reutiliza orden inactivo) ───────────── */
    if ($act === 'crear_item') {
        $idf = (int)($_POST['id_formulario'] ?? 0);
        $nom = trim($_POST['nombre']        ?? '');
        $des = trim($_POST['descripcion']   ?? '');
        $tip = $_POST['tipo']               ?? '';
        $ord = (int)($_POST['orden']        ?? 0);
        $link  = trim($_POST['link']  ?? '');
        $video = trim($_POST['video'] ?? '');

        /* Sanitiza URL de forma simple */
        $sanitizeUrl = function($u){
            if ($u === '') return '';
            if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
            return substr($u, 0, 500);
        };
        $link  = $sanitizeUrl($link);
        $video = $sanitizeUrl($video);

        if (!$idf || $nom === '' || $tip === '' || $ord < 1) {
            throw new Exception('Datos incompletos');
        }

        /* 1) ¿Existe esa fila (con ese orden) ya en la tabla puente? */
        $qChk = "SELECT id_item, activo
                 FROM sm_formulario_items
                 WHERE id_formulario = ? AND orden = ?
                 LIMIT 1";
        $stC = mysqli_prepare($conexion, $qChk);
        mysqli_stmt_bind_param($stC, 'ii', $idf, $ord);
        mysqli_stmt_execute($stC);
        $prev = mysqli_fetch_assoc(mysqli_stmt_get_result($stC));

        /* Si existe y está ACTIVA → bloquear */
        if ($prev && (int)$prev['activo'] === 1) {
            throw new Exception('Ya existe un ítem activo con ese orden');
        }

        mysqli_begin_transaction($conexion);

        /* 2) Crear el ítem en catálogo (formato se deja NULL inicialmente) */
        $qi = "INSERT INTO sm_items(nombre, tipo, ejemplo, link, video, activo)
               VALUES (?, ?, ?, ?, ?, 1)";
        $sti = mysqli_prepare($conexion, $qi);
        mysqli_stmt_bind_param($sti, 'sssss', $nom, $tip, $des, $link, $video);
        mysqli_stmt_execute($sti);
        $newId = mysqli_insert_id($conexion);

        /* 3) Reutilizar fila inactiva o insertar vínculo nuevo */
        if ($prev) {
            $qUp = "UPDATE sm_formulario_items
                    SET id_item = ?, activo = 1, fecha_actualizacion = NOW()
                    WHERE id_formulario = ? AND orden = ?";
            $stU = mysqli_prepare($conexion, $qUp);
            mysqli_stmt_bind_param($stU, 'iii', $newId, $idf, $ord);
            mysqli_stmt_execute($stU);
        } else {
            $qf = "INSERT INTO sm_formulario_items(id_formulario, id_item, orden, activo)
                   VALUES (?, ?, ?, 1)";
            $stf = mysqli_prepare($conexion, $qf);
            mysqli_stmt_bind_param($stf, 'iii', $idf, $newId, $ord);
            mysqli_stmt_execute($stf);
        }

        mysqli_commit($conexion);
        echo json_encode(['success' => true, 'id' => $newId]);
        exit;
    }
    /* ───────────── Información 1 ítem (para editar + resumen) ───────────── */
    if ($act === 'detalle_item') {
        $idi = (int)($_POST['id_item'] ?? 0);
        if (!$idi) throw new Exception('ID faltante');

        $sql = "SELECT
                    i.id AS id_item,
                    i.nombre,
                    i.tipo,
                    COALESCE(i.ejemplo,'') AS descripcion,
                    fi.orden,
                    i.img_ruta,
                    i.pdf_ruta,
                    COALESCE(i.link,'')    AS link,
                    COALESCE(i.formato,'') AS formato,
                    COALESCE(i.video,'')   AS video,
                    COALESCE(i.archivo,'') AS archivo
                FROM sm_items i
                JOIN sm_formulario_items fi ON fi.id_item = i.id
                WHERE i.id = ? AND fi.activo = 1
                LIMIT 1";
        $st  = mysqli_prepare($conexion, $sql);
        mysqli_stmt_bind_param($st, 'i', $idi);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        $row = mysqli_fetch_assoc($res);

        if (!$row) throw new Exception('Ítem no encontrado');

        /* URLs públicas */
        $row['img_url'] = !empty($row['img_ruta']) ? $APP_BASE . $row['img_ruta'] : null;
        $row['pdf_url'] = !empty($row['pdf_ruta']) ? $APP_BASE . $row['pdf_ruta'] : null;
        $row['formato_url'] = (!empty($row['formato']) && substr($row['formato'],0,1) === '/')
            ? $APP_BASE . $row['formato']
            : null;

        /* Flags booleanos (como en listar_items) */
        $row['hasEjemplo'] = ($row['descripcion'] !== '');
        $row['hasImg']     = !empty($row['img_url']);
        $row['hasPdf']     = !empty($row['pdf_url']);
        $row['hasLink']    = ($row['link']    !== '');
        $row['hasFormato'] = ($row['formato'] !== '');
        $row['hasVideo']   = ($row['video']   !== '');
        $row['hasArchivo'] = ($row['archivo'] !== '');

        echo json_encode(['success' => true, 'data' => $row]);
        exit;
    }

    /* ───────────── Actualizar ítem (catálogo + orden) ───────────── */
    if ($act === 'actualizar_item') {
        $idf = (int)($_POST['id_formulario'] ?? 0);
        $idi = (int)($_POST['id_item']       ?? 0);
        $nom = trim($_POST['nombre']         ?? '');
        $des = trim($_POST['descripcion']    ?? '');
        $tip = $_POST['tipo']                ?? '';
        $ord = (int)($_POST['orden']         ?? 0);
        $link  = trim($_POST['link']  ?? '');
        $video = trim($_POST['video'] ?? '');

        /* Validación/sanitización simple de URL */
        $sanitizeUrl = function($u){
            if ($u === '') return '';
            if (!preg_match('~^https?://~i', $u)) $u = 'https://' . $u;
            return substr($u, 0, 500);
        };
        $link  = $sanitizeUrl($link);
        $video = $sanitizeUrl($video);

        if (!$idf || !$idi || $nom === '' || $tip === '' || $ord < 1)
            throw new Exception('Datos incompletos');

        /* Duplicidad de orden (excluye el propio id_item) */
        $dup = mysqli_prepare($conexion,
               "SELECT 1 FROM sm_formulario_items
                WHERE id_formulario = ? AND orden = ? AND id_item <> ? AND activo = 1
                LIMIT 1");
        mysqli_stmt_bind_param($dup, 'iii', $idf, $ord, $idi);
        mysqli_stmt_execute($dup);
        if (mysqli_stmt_get_result($dup)->fetch_row())
            throw new Exception('Otro ítem activo ya usa ese orden');

        mysqli_begin_transaction($conexion);

        /* 1) Actualizar catálogo */
        $ui = "UPDATE sm_items
               SET nombre = ?, tipo = ?, ejemplo = ?, link = ?, video = ?
               WHERE id = ?";
        $sti = mysqli_prepare($conexion, $ui);
        mysqli_stmt_bind_param($sti, 'sssssi', $nom, $tip, $des, $link, $video, $idi);
        mysqli_stmt_execute($sti);

        /* 2) Actualizar orden en la tabla puente */
        $uf = "UPDATE sm_formulario_items
               SET orden = ?, fecha_actualizacion = NOW()
               WHERE id_formulario = ? AND id_item = ?";
        $stf = mysqli_prepare($conexion, $uf);
        mysqli_stmt_bind_param($stf, 'iii', $ord, $idf, $idi);
        mysqli_stmt_execute($stf);

        mysqli_commit($conexion);
        echo json_encode(['success' => true]);
        exit;
    }
    /* ───────────── Eliminar (soft) ───────────── */
    if ($act === 'eliminar_item') {
        $idf = (int)($_POST['id_formulario'] ?? 0);
        $idi = (int)($_POST['id_item']       ?? 0);
        if (!$idf || !$idi) throw new Exception('Parámetros inválidos');

        $q = "UPDATE sm_formulario_items
              SET activo = 0, fecha_actualizacion = NOW()
              WHERE id_formulario = ? AND id_item = ?";
        $st = mysqli_prepare($conexion, $q);
        mysqli_stmt_bind_param($st, 'ii', $idf, $idi);
        mysqli_stmt_execute($st);

        echo json_encode(['success' => true]);
        exit;
    }

    /* ───────────── Subir imagen / PDF / FORMATO ───────────── */
    if ($act === 'subir_archivo') {
        $idi  = (int)($_POST['id_item'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        if (!$idi || !in_array($tipo, ['img', 'pdf', 'formato'], true)) {
            throw new Exception('Parámetros inválidos');
        }

        if (!isset($_FILES['file'])) {
            throw new Exception('No se recibió el archivo (posiblemente excedió post_max_size=' . ini_get('post_max_size') . ').');
        }

        $err = $_FILES['file']['error'];
        if ($err !== UPLOAD_ERR_OK) {
            $map = [
                UPLOAD_ERR_INI_SIZE   => 'El archivo excede upload_max_filesize (' . ini_get('upload_max_filesize') . ').',
                UPLOAD_ERR_FORM_SIZE  => 'El archivo excede MAX_FILE_SIZE definido en el formulario.',
                UPLOAD_ERR_PARTIAL    => 'El archivo se subió parcialmente.',
                UPLOAD_ERR_NO_FILE    => 'No se subió ningún archivo.',
                UPLOAD_ERR_NO_TMP_DIR => 'Falta la carpeta temporal del servidor.',
                UPLOAD_ERR_CANT_WRITE => 'No se pudo escribir el archivo en disco.',
                UPLOAD_ERR_EXTENSION  => 'Una extensión de PHP detuvo la subida.',
            ];
            $msg = $map[$err] ?? ('Error de subida (código ' . $err . ').');
            throw new Exception($msg);
        }

        /* Carpeta destino */
        if ($tipo === 'img')      $destDir = '../../files_forms/img';
        elseif ($tipo === 'pdf')  $destDir = '../../files_forms/pdf';
        else                      $destDir = '../../files_forms/formato'; // formato
        if (!is_dir($destDir)) { @mkdir($destDir, 0775, true); }

        /* Validar extensión */
        $ext = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
        if ($tipo === 'pdf' && $ext !== 'pdf') {
            throw new Exception('Solo PDF permitido');
        }
        if ($tipo === 'img' && !in_array($ext, ['jpg','jpeg','png','gif','webp'], true)) {
            throw new Exception('Extensión de imagen no permitida');
        }
        if ($tipo === 'formato' && !in_array($ext, ['doc','docx','xls','xlsx'], true)) {
            throw new Exception('Solo Word/Excel permitido (.doc, .docx, .xls, .xlsx)');
        }

        /* Nombre seguro */
        $safe    = preg_replace('/[^a-zA-Z0-9_\-\.]/', '_', uniqid('', true) . '.' . $ext);
        $rutaAbs = rtrim($destDir, '/').'/'.$safe;
        if ($tipo === 'img')      $rutaRel = '/files_forms/img/' . $safe;
        elseif ($tipo === 'pdf')  $rutaRel = '/files_forms/pdf/' . $safe;
        else                      $rutaRel = '/files_forms/formato/' . $safe;

        /* Columna a actualizar */
        $col = ($tipo === 'img') ? 'img_ruta' : (($tipo === 'pdf') ? 'pdf_ruta' : 'formato');

        /* Borrar archivo previo si existiera (solo si era ruta local) */
        $qPrev = "SELECT $col FROM sm_items WHERE id = ? LIMIT 1";
        $stP   = mysqli_prepare($conexion, $qPrev);
        mysqli_stmt_bind_param($stP, 'i', $idi);
        mysqli_stmt_execute($stP);
        $resP  = mysqli_stmt_get_result($stP);
        $rowP  = mysqli_fetch_assoc($resP);
        $prev  = $rowP[$col] ?? null;
        if ($prev && substr($prev,0,1)==='/' && is_file('../..'.$prev)) { @unlink('../..'.$prev); }

        /* Mover subida */
        if (!move_uploaded_file($_FILES['file']['tmp_name'], $rutaAbs)) {
            throw new Exception('No se pudo mover el archivo');
        }

        /* Actualizar BD */
        $qU = "UPDATE sm_items SET $col = ? WHERE id = ?";
        $stU = mysqli_prepare($conexion, $qU);
        mysqli_stmt_bind_param($stU, 'si', $rutaRel, $idi);
        mysqli_stmt_execute($stU);

        echo json_encode(['success' => true, 'ruta' => $rutaRel, 'url' => $APP_BASE . $rutaRel]);
        exit;
    }

    /* ───────────── Borrar imagen / PDF / FORMATO ───────────── */
    if ($act === 'borrar_archivo') {
        $idi  = (int)($_POST['id_item'] ?? 0);
        $tipo = $_POST['tipo'] ?? '';
        if (!$idi || !in_array($tipo, ['img','pdf','formato'], true)) {
            throw new Exception('Parámetros inválidos');
        }

        $col  = ($tipo === 'img') ? 'img_ruta' : (($tipo === 'pdf') ? 'pdf_ruta' : 'formato');
        $qSel = "SELECT $col FROM sm_items WHERE id = ? LIMIT 1";
        $stS  = mysqli_prepare($conexion, $qSel);
        mysqli_stmt_bind_param($stS, 'i', $idi);
        mysqli_stmt_execute($stS);
        $resS = mysqli_stmt_get_result($stS);
        $rowS = mysqli_fetch_assoc($resS);
        $ruta = $rowS[$col] ?? null;

        if ($ruta && substr($ruta,0,1)==='/' && is_file('../..'.$ruta)) { @unlink('../..'.$ruta); }

        $qU = "UPDATE sm_items SET $col = NULL WHERE id = ?";
        $stU = mysqli_prepare($conexion, $qU);
        mysqli_stmt_bind_param($stU, 'i', $idi);
        mysqli_stmt_execute($stU);

        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'msg' => 'Acción no reconocida']);
} catch (Throwable $e) {
    @mysqli_rollback($conexion);
    echo json_encode(['success' => false, 'msg' => $e->getMessage()]);
}
