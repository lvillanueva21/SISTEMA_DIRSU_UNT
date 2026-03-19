<?php
declare(strict_types=1);

date_default_timezone_set('America/Lima');

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/csrf.php';
require_once __DIR__ . '/../../includes/conexion.php';
require_once __DIR__ . '/../../includes/gestion_archivos.php';

function json_out($ok, $data = array(), $status = 200) {
  http_response_code((int)$status);
  echo json_encode(array_merge(array('ok' => (bool)$ok), $data), JSON_UNESCAPED_UNICODE);
  exit;
}

function req_str($k) {
  $v = $_REQUEST[$k] ?? '';
  return is_string($v) ? trim($v) : '';
}

function is_valid_pagekey($s) {
  return $s !== '' && preg_match('/^[a-z0-9\-\_]+$/i', $s);
}

function user_can_manage() {
  $u = auth_user();
  if (!$u || !isset($u['rol']['codigo'])) return false;
  $c = (string)$u['rol']['codigo'];
  return ($c === 'desarrollador' || $c === 'director');
}

function ip_addr() {
  $ip = $_SERVER['REMOTE_ADDR'] ?? '';
  return is_string($ip) ? $ip : '';
}

function ua_str() {
  $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
  return is_string($ua) ? substr($ua, 0, 255) : '';
}

function normalize_date($s) {
  $s = trim((string)$s);
  if ($s === '') return null;
  if (!preg_match('/^\d{4}\-\d{2}\-\d{2}$/', $s)) return null;
  return $s;
}

function normalize_time($s) {
  $s = trim((string)$s);
  if ($s === '') return null;
  // HH:MM o HH:MM:SS
  if (preg_match('/^\d{2}\:\d{2}$/', $s)) return $s . ':00';
  if (!preg_match('/^\d{2}\:\d{2}\:\d{2}$/', $s)) return null;
  return $s;
}

function validate_start_end($sf, $sh, $ef, $eh) {
  // Si hay ambas fechas (inicio y fin) podemos comparar (horas opcionales -> 00:00:00)
  if ($sf && $ef) {
    $sh2 = $sh ? $sh : '00:00:00';
    $eh2 = $eh ? $eh : '00:00:00';
    $a = strtotime($sf . ' ' . $sh2);
    $b = strtotime($ef . ' ' . $eh2);
    if ($a !== false && $b !== false && $b < $a) {
      return 'La fecha/hora de fin no puede ser menor que la de inicio.';
    }
  }
  return '';
}

function log_evento($mysqli, $accion, $eventoId, $pageKey, $datos) {
  $u = auth_user();
  $uid = $u ? (int)$u['id'] : null;
  $rol = ($u && isset($u['rol']['codigo'])) ? (string)$u['rol']['codigo'] : null;

  $ip = ip_addr();
  $ua = ua_str();
  $json = is_array($datos) ? json_encode($datos, JSON_UNESCAPED_UNICODE) : (string)$datos;

  $sql = "INSERT INTO l2601_eventos_log
          (evento_id, page_key, accion, usuario_id, rol_codigo, ip, user_agent, datos)
          VALUES (?,?,?,?,?,?,?,?)";
  $st = $mysqli->prepare($sql);
  // tipos: i s s i s s s s  (evento_id y usuario_id pueden ser null -> pasamos NULL via variables)
  $eid = $eventoId ? (int)$eventoId : null;
  $uid2 = $uid ? (int)$uid : null;

  $st->bind_param('ississss', $eid, $pageKey, $accion, $uid2, $rol, $ip, $ua, $json);
  $st->execute();
  $st->close();
}

$action = req_str('action');
$pageKey = req_str('page_key');

if (!is_valid_pagekey($pageKey)) {
  json_out(false, array('error' => 'page_key inválido.'), 400);
}

$mysqli = db();

/* ========= LECTURAS ========= */

if ($action === 'top4') {
  // Top4 público: NO incluir inactivos
  $sql = "SELECT id, titulo, parrafo, inicio_fecha, inicio_hora, fin_fecha, fin_hora,
                 coordinador, ponente, estado, tags_csv, foto_evento
          FROM l2601_eventos
          WHERE page_key = ?
            AND estado <> 'inactivo'
          ORDER BY
            (inicio_fecha IS NULL) ASC,
            ABS(TIMESTAMPDIFF(SECOND,
                TIMESTAMP(inicio_fecha, COALESCE(inicio_hora,'00:00:00')),
                NOW()
            )) ASC,
            creado_en DESC
          LIMIT 4";
  $st = $mysqli->prepare($sql);
  $st->bind_param('s', $pageKey);
  $st->execute();
  $res = $st->get_result();

  $items = array();
  while ($r = $res->fetch_assoc()) {
    $items[] = $r;
  }
  $st->close();

  json_out(true, array('items' => $items));
}

if ($action === 'listar') {
  if (!auth_check()) {
    json_out(false, array('error' => 'No autorizado.'), 403);
  }

  $page = (int)($_GET['page'] ?? 1);
  if ($page < 1) $page = 1;
  $per = 10;

  $desde = normalize_date(req_str('desde'));
  $hasta = normalize_date(req_str('hasta'));
  $estado = req_str('estado');
  $inclSinFecha = req_str('incl_sin_fecha') === '1';

  $where = " page_key = ? ";
  $params = array($pageKey);
  $types = "s";

  $allowedEstados = array('activo','inactivo','reprogramado','cancelado','indefinido');
  if ($estado !== '' && in_array($estado, $allowedEstados, true)) {
    $where .= " AND estado = ? ";
    $params[] = $estado;
    $types .= "s";
  }

  // Filtro calendario: aplica a inicio_fecha; opción incluir sin fecha
  if ($desde || $hasta) {
    $range = array();
    if ($desde && $hasta) {
      $range[] = " (inicio_fecha BETWEEN ? AND ?) ";
      $params[] = $desde; $types .= "s";
      $params[] = $hasta; $types .= "s";
    } elseif ($desde) {
      $range[] = " (inicio_fecha >= ?) ";
      $params[] = $desde; $types .= "s";
    } elseif ($hasta) {
      $range[] = " (inicio_fecha <= ?) ";
      $params[] = $hasta; $types .= "s";
    }

    if ($inclSinFecha) {
      $where .= " AND ( (inicio_fecha IS NULL) OR " . implode(' AND ', $range) . " ) ";
    } else {
      $where .= " AND " . implode(' AND ', $range) . " ";
    }
  } else {
    if (!$inclSinFecha) {
      $where .= " AND inicio_fecha IS NOT NULL ";
    }
  }

  // total
  $sqlCount = "SELECT COUNT(*) AS c FROM l2601_eventos WHERE $where";
  $stc = $mysqli->prepare($sqlCount);
  $stc->bind_param($types, ...$params);
  $stc->execute();
  $rc = $stc->get_result()->fetch_assoc();
  $total = (int)($rc['c'] ?? 0);
  $stc->close();

  $off = ($page - 1) * $per;

  $sql = "SELECT id, titulo, parrafo, inicio_fecha, inicio_hora, fin_fecha, fin_hora,
                 coordinador, ponente, estado, tags_csv, foto_evento, creado_en, actualizado_en
          FROM l2601_eventos
          WHERE $where
          ORDER BY
            (inicio_fecha IS NULL) ASC,
            ABS(TIMESTAMPDIFF(SECOND,
              TIMESTAMP(inicio_fecha, COALESCE(inicio_hora,'00:00:00')),
              NOW()
            )) ASC,
            creado_en DESC
          LIMIT $per OFFSET $off";

  $st = $mysqli->prepare($sql);
  $st->bind_param($types, ...$params);
  $st->execute();
  $res = $st->get_result();

  $items = array();
  while ($r = $res->fetch_assoc()) {
    $items[] = $r;
  }
  $st->close();

  json_out(true, array(
    'items' => $items,
    'total' => $total,
    'per_page' => $per,
    'page' => $page
  ));
}

if ($action === 'tags') {
  if (!auth_check()) {
    json_out(false, array('error' => 'No autorizado.'), 403);
  }
  $q = req_str('q');
  if ($q === '') {
    json_out(true, array('items' => array()));
  }

  $like = '%' . $q . '%';
  $sql = "SELECT nombre
          FROM l2601_etiquetas
          WHERE nombre LIKE ?
          ORDER BY nombre ASC
          LIMIT 12";
  $st = $mysqli->prepare($sql);
  $st->bind_param('s', $like);
  $st->execute();
  $res = $st->get_result();

  $items = array();
  while ($r = $res->fetch_assoc()) {
    $items[] = (string)$r['nombre'];
  }
  $st->close();

  json_out(true, array('items' => $items));
}

if ($action === 'get') {
  if (!auth_check() || !user_can_manage()) {
    json_out(false, array('error' => 'No autorizado.'), 403);
  }

  $id = (int)req_str('id');
  if ($id <= 0) json_out(false, array('error' => 'ID inválido.'), 400);

  $sql = "SELECT id, titulo, parrafo, inicio_fecha, inicio_hora, fin_fecha, fin_hora,
                 coordinador, ponente, estado, tags_csv, foto_evento
          FROM l2601_eventos
          WHERE id = ? AND page_key = ?
          LIMIT 1";
  $st = $mysqli->prepare($sql);
  $st->bind_param('is', $id, $pageKey);
  $st->execute();
  $res = $st->get_result();
  $it = $res->fetch_assoc();
  $st->close();

  if (!$it) json_out(false, array('error' => 'Evento no encontrado.'), 404);
  json_out(true, array('item' => $it));
}

/* ========= ESCRITURAS ========= */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  json_out(false, array('error' => 'Método no permitido.'), 405);
}

if ($action === 'save') {
  if (!auth_check() || !user_can_manage()) {
    json_out(false, array('error' => 'No autorizado.'), 403);
  }
  if (!csrf_validate($_POST['csrf'] ?? null)) {
    json_out(false, array('error' => 'CSRF inválido. Recarga la página e intenta otra vez.'), 400);
  }

  $u = auth_user();
  $uid = $u ? (int)$u['id'] : null;

  $id = (int)($_POST['id'] ?? 0);
  $titulo = trim((string)($_POST['titulo'] ?? ''));
  $parrafo = trim((string)($_POST['parrafo'] ?? ''));

  $coordinador = trim((string)($_POST['coordinador'] ?? ''));
  $ponente = trim((string)($_POST['ponente'] ?? ''));

  $estado = trim((string)($_POST['estado'] ?? 'activo'));
  $allowedEstados = array('activo','inactivo','reprogramado','cancelado','indefinido');
  if (!in_array($estado, $allowedEstados, true)) $estado = 'activo';

  if ($titulo === '') {
    json_out(false, array('error' => 'El título es obligatorio.'), 400);
  }
  if (mb_strlen($titulo) > 200) {
    json_out(false, array('error' => 'El título supera 200 caracteres.'), 400);
  }
  if ($parrafo !== '' && mb_strlen($parrafo) > 2000) {
    json_out(false, array('error' => 'El párrafo supera 2000 caracteres.'), 400);
  }

  $inicio_fecha = normalize_date((string)($_POST['inicio_fecha'] ?? ''));
  $inicio_hora  = normalize_time((string)($_POST['inicio_hora'] ?? ''));
  $fin_fecha    = normalize_date((string)($_POST['fin_fecha'] ?? ''));
  $fin_hora     = normalize_time((string)($_POST['fin_hora'] ?? ''));

  $err = validate_start_end($inicio_fecha, $inicio_hora, $fin_fecha, $fin_hora);
  if ($err !== '') {
    json_out(false, array('error' => $err), 400);
  }

  // tags_csv
  $tagsCsv = trim((string)($_POST['tags_csv'] ?? ''));
  if ($tagsCsv !== '') {
    // normalizar y cortar
    $parts = explode(',', $tagsCsv);
    $clean = array();
    foreach ($parts as $p) {
      $t = trim($p);
      if ($t === '') continue;
      if (mb_strlen($t) > 60) $t = mb_substr($t, 0, 60);
      if (!in_array($t, $clean, true)) $clean[] = $t;
      if (count($clean) >= 25) break;
    }
    $tagsCsv = implode(', ', $clean);

    // Insertar etiquetas si no existen (autocompletar futuro)
    foreach ($clean as $t) {
      $sqlTag = "INSERT IGNORE INTO l2601_etiquetas (nombre, creado_por) VALUES (?, ?)";
      $stt = $mysqli->prepare($sqlTag);
      $stt->bind_param('si', $t, $uid);
      $stt->execute();
      $stt->close();
    }
  } else {
    $tagsCsv = null;
  }

  // foto (opcional)
  $hasFoto = isset($_FILES['foto']) && is_array($_FILES['foto']) && (int)($_FILES['foto']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE;

  // Para mensajes de tamaño permitido
  $uploadMax = (string)ini_get('upload_max_filesize');
  $postMax = (string)ini_get('post_max_size');

  if ($hasFoto) {
    $e = (int)($_FILES['foto']['error'] ?? UPLOAD_ERR_OK);
    if ($e === UPLOAD_ERR_INI_SIZE || $e === UPLOAD_ERR_FORM_SIZE) {
      json_out(false, array('error' => "La imagen es demasiado grande. Límite del servidor: upload_max_filesize={$uploadMax}, post_max_size={$postMax}."), 400);
    }
    if ($e !== UPLOAD_ERR_OK) {
      json_out(false, array('error' => 'Error subiendo la imagen.'), 400);
    }
    // validar que sea imagen (amigable)
    $tmp = (string)($_FILES['foto']['tmp_name'] ?? '');
    if ($tmp === '' || !is_uploaded_file($tmp) || @getimagesize($tmp) === false) {
      json_out(false, array('error' => 'El archivo no parece ser una imagen válida.'), 400);
    }
  }

  // ====== CREAR ======
  if ($id <= 0) {
    // Insert sin foto primero, luego subimos foto con id para nombre
    $sql = "INSERT INTO l2601_eventos
            (page_key, titulo, parrafo, inicio_fecha, inicio_hora, fin_fecha, fin_hora,
             coordinador, ponente, estado, tags_csv, foto_evento, creado_por, actualizado_por)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

    $fotoPath = null;

    $st = $mysqli->prepare($sql);
    $st->bind_param(
      'ssssssssssssii',
      $pageKey, $titulo, $parrafo,
      $inicio_fecha, $inicio_hora, $fin_fecha, $fin_hora,
      $coordinador, $ponente, $estado, $tagsCsv, $fotoPath,
      $uid, $uid
    );
    $st->execute();
    $newId = (int)$mysqli->insert_id;
    $st->close();

    $before = null;
    $after = array(
      'id' => $newId,
      'page_key' => $pageKey,
      'titulo' => $titulo,
      'parrafo' => $parrafo,
      'inicio_fecha' => $inicio_fecha,
      'inicio_hora' => $inicio_hora,
      'fin_fecha' => $fin_fecha,
      'fin_hora' => $fin_hora,
      'coordinador' => $coordinador,
      'ponente' => $ponente,
      'estado' => $estado,
      'tags_csv' => $tagsCsv
    );

    // Foto después del insert
    if ($hasFoto) {
      try {
        $up = ga_save_upload($mysqli, $_FILES['foto'], 'foto_evento', 'evento', 'eventos', 'evento', $newId);
        $fotoPath = (string)$up['ruta_relativa'];

        $st2 = $mysqli->prepare("UPDATE l2601_eventos SET foto_evento=? WHERE id=? AND page_key=?");
        $st2->bind_param('sis', $fotoPath, $newId, $pageKey);
        $st2->execute();
        $st2->close();

        $after['foto_evento'] = $fotoPath;
      } catch (Throwable $ex) {
        // si falla foto, dejamos evento creado sin foto (no rompemos)
      }
    }

    log_evento($mysqli, 'crear', $newId, $pageKey, array('before' => $before, 'after' => $after));

    json_out(true, array('id' => $newId));
  }

  // ====== EDITAR ======
  // obtener anterior
  $st0 = $mysqli->prepare("SELECT * FROM l2601_eventos WHERE id=? AND page_key=? LIMIT 1");
  $st0->bind_param('is', $id, $pageKey);
  $st0->execute();
  $prev = $st0->get_result()->fetch_assoc();
  $st0->close();

  if (!$prev) {
    json_out(false, array('error' => 'Evento no encontrado.'), 404);
  }

  $oldFoto = $prev['foto_evento'] ? (string)$prev['foto_evento'] : '';

  $newFotoPath = $oldFoto;
  $newFotoTemp = '';

  // si viene foto nueva, primero la guardamos
  if ($hasFoto) {
    try {
      $up = ga_save_upload($mysqli, $_FILES['foto'], 'foto_evento', 'evento', 'eventos', 'evento', $id);
      $newFotoTemp = (string)$up['ruta_relativa'];
      $newFotoPath = $newFotoTemp;
    } catch (Throwable $ex) {
      json_out(false, array('error' => 'No se pudo guardar la foto. Verifica permisos de carpeta.'), 500);
    }
  }

  $sqlU = "UPDATE l2601_eventos SET
            titulo=?, parrafo=?, inicio_fecha=?, inicio_hora=?, fin_fecha=?, fin_hora=?,
            coordinador=?, ponente=?, estado=?, tags_csv=?, foto_evento=?, actualizado_por=?
          WHERE id=? AND page_key=?";

  $stU = $mysqli->prepare($sqlU);
  $stU->bind_param(
    'sssssssssssiss',
    $titulo, $parrafo,
    $inicio_fecha, $inicio_hora, $fin_fecha, $fin_hora,
    $coordinador, $ponente, $estado, $tagsCsv,
    $newFotoPath,
    $uid,
    $id, $pageKey
  );

  try {
    $stU->execute();
  } catch (Throwable $exU) {
    $stU->close();
    // si falló update y habíamos subido foto nueva, la borramos para no dejar basura
    if ($newFotoTemp !== '') ga_delete_rel($newFotoTemp);
    json_out(false, array('error' => 'No se pudo actualizar el evento.'), 500);
  }
  $stU->close();

  // si hubo foto nueva, borrar la anterior
  if ($newFotoTemp !== '' && $oldFoto !== '' && $oldFoto !== $newFotoTemp) {
    ga_delete_rel($oldFoto);
  }

  $after = array(
    'id' => $id,
    'page_key' => $pageKey,
    'titulo' => $titulo,
    'parrafo' => $parrafo,
    'inicio_fecha' => $inicio_fecha,
    'inicio_hora' => $inicio_hora,
    'fin_fecha' => $fin_fecha,
    'fin_hora' => $fin_hora,
    'coordinador' => $coordinador,
    'ponente' => $ponente,
    'estado' => $estado,
    'tags_csv' => $tagsCsv,
    'foto_evento' => $newFotoPath
  );

  log_evento($mysqli, 'editar', $id, $pageKey, array('before' => $prev, 'after' => $after));

  json_out(true, array('id' => $id));
}

if ($action === 'delete') {
  if (!auth_check() || !user_can_manage()) {
    json_out(false, array('error' => 'No autorizado.'), 403);
  }
  if (!csrf_validate($_POST['csrf'] ?? null)) {
    json_out(false, array('error' => 'CSRF inválido. Recarga la página e intenta otra vez.'), 400);
  }

  $id = (int)req_str('id');
  if ($id <= 0) json_out(false, array('error' => 'ID inválido.'), 400);

  $st0 = $mysqli->prepare("SELECT * FROM l2601_eventos WHERE id=? AND page_key=? LIMIT 1");
  $st0->bind_param('is', $id, $pageKey);
  $st0->execute();
  $row = $st0->get_result()->fetch_assoc();
  $st0->close();

  if (!$row) json_out(false, array('error' => 'Evento no encontrado.'), 404);

  $foto = $row['foto_evento'] ? (string)$row['foto_evento'] : '';

  // log antes de borrar
  log_evento($mysqli, 'eliminar', $id, $pageKey, array('before' => $row, 'after' => null));

  // borrar fila
  $st = $mysqli->prepare("DELETE FROM l2601_eventos WHERE id=? AND page_key=?");
  $st->bind_param('is', $id, $pageKey);
  $st->execute();
  $st->close();

  // borrar foto física
  if ($foto !== '') {
    ga_delete_rel($foto);
  }

  json_out(true, array('deleted' => 1));
}

json_out(false, array('error' => 'Acción inválida.'), 400);
