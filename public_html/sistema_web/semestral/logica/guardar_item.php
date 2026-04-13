<?php
// presentacion/logica/guardar_item.php — permite editar si está OBSERVADO en la oficina actual

date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../componentes/db.php'; // $conexion (mysqli)

// Paths base (FS y URL) para subidas del usuario.
$FS_BASE   = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
$URL_BASE  = '';
$DIR_PDF   = '/files_answer/pdf';
$DIR_FORM  = '/files_answer/formato';

// Helpers
function back_to_index($item = null) {
    // Si existe un wrapper público (semestral/guardar_item.php), este define la ruta correcta.
    // Fallback: cuando se ejecuta directamente desde /semestral/logica/.
    $url = defined('SM_SEMESTRAL_INDEX_REL') ? SM_SEMESTRAL_INDEX_REL : '../index.php';
    if ($item !== null) {
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . 'item=' . (int)$item;
    }
    header("Location: $url", true, 303); // 303 para evitar re-POST
    exit;
}
function flash($msg, $type = 'info') {
    $_SESSION['form_msg'] = $msg;
    $_SESSION['form_msg_type'] = $type;
}

/**
 * Retorna true si, estando la respuesta en revisión (1), la oficina actual la dejó OBSERVADA.
 * Bloquea si no hay evaluación, si la situación es 'aprobado' o si la instancia no está 'observado'.
 */
function puede_editar_en_revision(mysqli $cx, int $id_respuesta): bool {
    $sql = "
        SELECT
            e.situacion,
            e.id_oficina_actual,
            (
              SELECT i.estado
              FROM eva_oficina_instancias i
              WHERE i.id_evaluacion = e.id
                AND i.id_oficina    = e.id_oficina_actual
              ORDER BY i.id DESC
              LIMIT 1
            ) AS inst_estado
        FROM eva_evaluaciones e
        WHERE e.id_respuesta = ?
        LIMIT 1
    ";
    $st = $cx->prepare($sql);
    $st->bind_param("i", $id_respuesta);
    $st->execute();
    $row = $st->get_result()->fetch_assoc();
    $st->close();

    if (!$row) return false; // aún no inició ruta
    if ((string)$row['situacion'] === 'aprobado') return false; // aprobación total
    return ((string)$row['inst_estado'] === 'observado');       // observación vigente
}

// Validación básica
$id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
$id_item      = isset($_POST['id_item']) ? (int)$_POST['id_item'] : 0;
$tipo         = isset($_POST['tipo']) ? trim((string)$_POST['tipo']) : '';
$next         = isset($_POST['next']) ? (int)$_POST['next'] : null;

if ($id_respuesta <= 0 || $id_item <= 0 || $tipo === '') {
    flash('Solicitud inválida.', 'danger'); back_to_index();
}

$id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
if ($id_py <= 0) { flash('Sesión inválida o proyecto no seleccionado.', 'danger'); back_to_index(); }

// Cabecera respuesta
$st = $conexion->prepare("
    SELECT r.id, r.id_py, r.id_formulario, r.id_cronograma, r.id_semestre, r.estado
    FROM sm_respuestas r
    WHERE r.id = ? AND r.id_py = ?
");
$st->bind_param("ii", $id_respuesta, $id_py);
$st->execute();
$resp = $st->get_result()->fetch_assoc();
$st->close();
if (!$resp) { flash('No se encontró la respuesta o no pertenece a tu proyecto.', 'danger'); back_to_index(); }

// === Candado de edición actualizado ===
// - estado=2 (aprobado) => bloquea siempre
// - estado=1 (en revisión) => solo permite si la oficina actual dejó OBSERVACIÓN
if ((int)$resp['estado'] === 2) {
    flash('No puedes guardar porque el informe ya está aprobado.', 'warning');
    back_to_index();
}
if ((int)$resp['estado'] === 1 && !puede_editar_en_revision($conexion, $id_respuesta)) {
    flash('No puedes guardar porque el informe está en revisión y no tiene observaciones activas.', 'warning');
    back_to_index();
}

// Cronograma
$st2 = $conexion->prepare("SELECT tipo, activo, apertura, cierre FROM sm_cronogramas WHERE id=?");
$st2->bind_param("i", $resp['id_cronograma']);
$st2->execute();
$cr = $st2->get_result()->fetch_assoc();
$st2->close();
if (!$cr || (int)$cr['tipo'] !== 2 || (int)$cr['activo'] !== 1) { flash('No hay un cronograma activo válido para guardar.', 'danger'); back_to_index(); }

$now = new DateTime('now', new DateTimeZone('America/Lima'));
$apertura = new DateTime($cr['apertura'], new DateTimeZone('America/Lima'));
$cierre   = new DateTime($cr['cierre'],   new DateTimeZone('America/Lima'));
if (!($now >= $apertura && $now <= $cierre)) { flash('Fuera de la ventana de presentación. No se puede guardar.', 'warning'); back_to_index(); }

// Ítems del formulario
$st3 = $conexion->prepare("
  SELECT fi.id_item, fi.orden, i.tipo
  FROM sm_formulario_items fi
  JOIN sm_items i ON i.id = fi.id_item
  WHERE fi.id_formulario=? AND fi.activo=1
  ORDER BY fi.orden ASC
");
$st3->bind_param("i", $resp['id_formulario']);
$st3->execute();
$rs3 = $st3->get_result();
$items = $rs3->fetch_all(MYSQLI_ASSOC);
$st3->close();
if (empty($items)) { flash('El formulario no tiene ítems activos.', 'danger'); back_to_index(); }

$idxPorItem = []; $tipoPorItem = [];
foreach ($items as $i => $it) {
    $idxPorItem[(int)$it['id_item']] = $i + 1;
    $tipoPorItem[(int)$it['id_item']] = $it['tipo'];
}
$totalItems = count($items);
if (!isset($idxPorItem[$id_item])) { flash('El ítem no pertenece al formulario actual.', 'danger'); back_to_index(); }
$itemIdx = $idxPorItem[$id_item];

// Respuestas existentes
$st4 = $conexion->prepare("
  SELECT id_item, tipo,
         val_varchar, val_longtext, val_tinyint, val_int, val_boolean, val_datetime, val_date, val_decimal, archivo_url
  FROM sm_respuesta_items
  WHERE id_respuesta=?
");
$st4->bind_param("i", $id_respuesta);
$st4->execute();
$rs4 = $st4->get_result();
$exist = [];
while ($row = $rs4->fetch_assoc()) $exist[(int)$row['id_item']] = $row;
$st4->close();

function ri_val_esta_lleno(array $row, string $tipo): bool {
    switch ($tipo) {
        case 'varchar':           return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'longtext':
        case 'longtext_parrafo':  return isset($row['val_longtext']) && trim((string)$row['val_longtext']) !== '';
        case 'tinyint':           return $row['val_tinyint'] !== null;
        case 'int':               return $row['val_int'] !== null;
        case 'boolean':           return $row['val_boolean'] !== null;
        case 'datetime':          return !empty($row['val_datetime']);
        case 'date':              return !empty($row['val_date']);
        case 'decimal':           return $row['val_decimal'] !== null;
        case 'ods':               return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'programa_ods':      return isset($row['val_varchar']) && trim((string)$row['val_varchar']) !== '';
        case 'pdf':
        case 'excel':
        case 'word':              return !empty($row['archivo_url']);
        default:                  return false;
    }
}

// Primer incompleto
$primerIncompletoIdx = 1;
foreach ($items as $i => $it) {
    $idIt = (int)$it['id_item'];
    $tp = $it['tipo'];
    $tiene = isset($exist[$idIt]) ? ri_val_esta_lleno($exist[$idIt], $tp) : false;
    if (!$tiene) { $primerIncompletoIdx = $i + 1; break; }
    if ($i === $totalItems - 1) $primerIncompletoIdx = $totalItems;
}
$maxPermitido = max(1, $primerIncompletoIdx);
if ($itemIdx > $maxPermitido) { flash('Debes completar los ítems en orden. No puedes saltarte preguntas.', 'warning'); back_to_index($maxPermitido); }

// Tipo real
$tipoItemCatalogo = $tipoPorItem[$id_item];
if ($tipoItemCatalogo !== $tipo) $tipo = $tipoItemCatalogo;

// Estructura valores
$val = [
    'val_varchar'  => null,
    'val_longtext' => null,
    'val_tinyint'  => null,
    'val_int'      => null,
    'val_boolean'  => null,
    'val_datetime' => null,
    'val_date'     => null,
    'val_decimal'  => null,
    'archivo_url'  => null,
];
function invalid($msg) { flash($msg, 'warning'); back_to_index(); }

// Tamaños
function parse_size_to_bytes($v) {
    $v = trim((string)$v);
    if ($v === '') return null;
    $unit = strtolower(substr($v,-1));
    $num  = (float)$v;
    return match ($unit) { 'g'=>(int)($num*1024*1024*1024),'m'=>(int)($num*1024*1024),'k'=>(int)($num*1024), default=>(int)$num };
}
$upload_max = parse_size_to_bytes(ini_get('upload_max_filesize'));
$post_max   = parse_size_to_bytes(ini_get('post_max_size'));
$max_bytes  = min($upload_max ?: PHP_INT_MAX, $post_max ?: PHP_INT_MAX);

// Switch por tipo
switch ($tipo) {
    case 'varchar':
        $v = isset($_POST['val_varchar']) ? (string)$_POST['val_varchar'] : '';
        if (mb_strlen($v) > 1000) invalid('El texto excede 1000 caracteres.');
        $val['val_varchar'] = (trim($v) === '') ? null : $v;
        break;

    case 'longtext':
    case 'longtext_parrafo':
        $v = isset($_POST['val_longtext']) ? (string)$_POST['val_longtext'] : '';
        $val['val_longtext'] = (trim($v) === '') ? null : $v;
        break;

    case 'tinyint':
        $v = isset($_POST['val_tinyint']) ? trim((string)$_POST['val_tinyint']) : '';
        if ($v === '') { $val['val_tinyint'] = null; }
        else {
            if (!preg_match('/^[0-9]$/', $v)) invalid('TINYINT: Debe ser un dígito 0-9.');
            $val['val_tinyint'] = (int)$v;
        }
        break;

    case 'int':
        $v = isset($_POST['val_int']) ? trim((string)$_POST['val_int']) : '';
        if ($v === '') { $val['val_int'] = null; }
        else {
            if (!preg_match('/^\d+$/', $v)) invalid('INT: Debe ser un entero positivo.');
            $val['val_int'] = (int)$v;
        }
        break;

    case 'boolean':
        $val['val_boolean'] = isset($_POST['val_boolean']) ? 1 : 0;
        break;

    case 'datetime':
        $v = isset($_POST['val_datetime']) ? trim((string)$_POST['val_datetime']) : '';
        if ($v !== '') {
            $dt = DateTime::createFromFormat('Y-m-d\TH:i', $v, new DateTimeZone('America/Lima'));
            if (!$dt) invalid('Fecha/hora inválida.');
            $val['val_datetime'] = $dt->format('Y-m-d H:i:00');
        } else { $val['val_datetime'] = null; }
        break;

    case 'date':
        $v = isset($_POST['val_date']) ? trim((string)$_POST['val_date']) : '';
        if ($v !== '') {
            $dt = DateTime::createFromFormat('Y-m-d', $v, new DateTimeZone('America/Lima'));
            if (!$dt) invalid('Fecha inválida.');
            $val['val_date'] = $dt->format('Y-m-d');
        } else { $val['val_date'] = null; }
        break;

    case 'decimal':
        $v = isset($_POST['val_decimal']) ? trim((string)$_POST['val_decimal']) : '';
        if ($v === '') { $val['val_decimal'] = null; }
        else {
            $nv = str_replace(',', '.', $v);
            if (!preg_match('/^-?\d+(\.\d+)?$/', $nv)) invalid('Decimal inválido.');
            $val['val_decimal'] = number_format((float)$nv, 2, '.', '');
        }
        break;

    case 'ods':
        $ids = [];
        if (isset($_POST['ods_ids']) && is_array($_POST['ods_ids'])) {
            foreach ($_POST['ods_ids'] as $sid) if (ctype_digit((string)$sid)) $ids[] = (int)$sid;
        } elseif (!empty($_POST['val_varchar'])) {
            foreach (explode(',', (string)$_POST['val_varchar']) as $sid) {
                $sid = trim($sid);
                if ($sid !== '' && ctype_digit($sid)) $ids[] = (int)$sid;
            }
        }
        $ids = array_unique($ids);
        if (!empty($ids)) {
            $place = implode(',', array_fill(0, count($ids), '?'));
            $types = str_repeat('i', count($ids));
            $sql = "
                SELECT o.id
                FROM ods o
                LEFT JOIN programa_ods po ON po.ods_id = o.id
                WHERE po.ods_id IS NULL AND o.id IN ($place)
            ";
            $stmtVal = $conexion->prepare($sql);
            $stmtVal->bind_param($types, ...$ids);
            $stmtVal->execute();
            $okIds = [];
            $rsv = $stmtVal->get_result();
            while ($r = $rsv->fetch_assoc()) $okIds[] = (int)$r['id'];
            $stmtVal->close();
            sort($okIds);
            $val['val_varchar'] = implode(',', $okIds);
        } else {
            $val['val_varchar'] = null;
        }
        break;

    case 'programa_ods':
        $pid = isset($_POST['programa_id']) ? trim((string)$_POST['programa_id'])
              : (isset($_POST['val_varchar']) ? trim((string)$_POST['val_varchar']) : '');
        if ($pid === '') { $val['val_varchar'] = null; }
        else {
            if (!ctype_digit($pid)) invalid('Programa inválido.');
            $pid_i = (int)$pid;
            $stmtP = $conexion->prepare("SELECT 1 FROM programas WHERE id=? AND activo=1");
            $stmtP->bind_param("i", $pid_i);
            $stmtP->execute();
            $ok = (bool)$stmtP->get_result()->fetch_row();
            $stmtP->close();
            if (!$ok) invalid('Programa no encontrado o inactivo.');
            $val['val_varchar'] = (string)$pid_i;
        }
        break;

    case 'pdf':
    case 'excel':
    case 'word':
        $f = null;
        if (isset($_FILES['archivo']) && is_array($_FILES['archivo']))      $f = $_FILES['archivo'];
        elseif (isset($_FILES['upload_file']) && is_array($_FILES['upload_file'])) $f = $_FILES['upload_file'];

        if ($f === null || $f['error'] === UPLOAD_ERR_NO_FILE) break;
        if ($f['error'] === UPLOAD_ERR_INI_SIZE || $f['error'] === UPLOAD_ERR_FORM_SIZE) {
            flash('El archivo excede el tamaño permitido por el servidor.', 'danger'); back_to_index($itemIdx);
        }
        if ($f['error'] !== UPLOAD_ERR_OK) {
            flash('Error al subir archivo (código '.$f['error'].').', 'danger'); back_to_index($itemIdx);
        }
        if ($f['size'] > $max_bytes) {
            flash('El archivo excede el tamaño máximo permitido.', 'danger'); back_to_index($itemIdx);
        }

        $orig = (string)$f['name']; $tmp  = (string)$f['tmp_name'];
        $ext  = strtolower(pathinfo($orig, PATHINFO_EXTENSION));
        $allowed = []; $subdir = '';
        if ($tipo === 'pdf')   { $allowed = ['pdf'];            $subdir = $DIR_PDF; }
        if ($tipo === 'excel') { $allowed = ['xls','xlsx'];     $subdir = $DIR_FORM; }
        if ($tipo === 'word')  { $allowed = ['doc','docx'];     $subdir = $DIR_FORM; }
        if (!in_array($ext, $allowed, true)) { flash('Extensión de archivo no permitida para '.$tipo.'.', 'danger'); back_to_index($itemIdx); }

        $uniq = date('YmdHis') . '_' . $id_respuesta . '_' . $id_item . '_' . bin2hex(random_bytes(4)) . '.' . $ext;

        $fsDir = rtrim($FS_BASE . $subdir, '/');
        if (!is_dir($fsDir)) @mkdir($fsDir, 0775, true);

        $fsPath = $fsDir . '/' . $uniq;
        if (!move_uploaded_file($tmp, $fsPath)) { flash('No se pudo guardar el archivo en el servidor.', 'danger'); back_to_index($itemIdx); }

        $prev = $exist[$id_item]['archivo_url'] ?? null;
        if ($prev) {
            $prevFs = $FS_BASE . $prev;
            if (strpos($prevFs, $FS_BASE . '/files_answer/') === 0 && file_exists($prevFs)) @unlink($prevFs);
        }
        $val['archivo_url'] = $subdir . '/' . $uniq;
        break;

    default: invalid('Tipo de ítem no soportado.');
}

// UPSERT
$conexion->begin_transaction();
try {
    $ins = $conexion->prepare("
      INSERT INTO sm_respuesta_items
        (id_respuesta, id_item, tipo,
         val_varchar, val_longtext, val_tinyint, val_int, val_boolean, val_datetime, val_date, val_decimal,
         archivo_url, estado)
      VALUES (?,?,?,?,?,?,?,?,?,?,?, ?, 0)
      ON DUPLICATE KEY UPDATE
        tipo=VALUES(tipo),
        val_varchar = VALUES(val_varchar),
        val_longtext= VALUES(val_longtext),
        val_tinyint = VALUES(val_tinyint),
        val_int     = VALUES(val_int),
        val_boolean = VALUES(val_boolean),
        val_datetime= VALUES(val_datetime),
        val_date    = VALUES(val_date),
        val_decimal = VALUES(val_decimal),
        archivo_url = VALUES(archivo_url),
        actualizado_at = CURRENT_TIMESTAMP
    ");
    $ins->bind_param(
        "iisssiiissss",
        $id_respuesta, $id_item, $tipo,
        $val['val_varchar'], $val['val_longtext'], $val['val_tinyint'], $val['val_int'], $val['val_boolean'],
        $val['val_datetime'], $val['val_date'], $val['val_decimal'], $val['archivo_url']
    );
    if (!$ins->execute()) throw new RuntimeException("Error al guardar el ítem: ".$ins->error);
    $ins->close();

    // Sin cambiar sm_respuestas.estado aquí.
    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    flash('No se pudo guardar: '.$e->getMessage(), 'danger'); back_to_index($itemIdx);
}

// Recalcular progreso (para decidir a dónde volver)
$st5 = $conexion->prepare("
  SELECT ri.id_item, ri.tipo,
         ri.val_varchar, ri.val_longtext, ri.val_tinyint, ri.val_int, ri.val_boolean, ri.val_datetime, ri.val_date, ri.val_decimal, ri.archivo_url
  FROM sm_respuesta_items ri
  WHERE ri.id_respuesta=?
");
$st5->bind_param("i", $id_respuesta);
$st5->execute();
$rs5 = $st5->get_result();
$vals = [];
while ($r = $rs5->fetch_assoc()) $vals[(int)$r['id_item']] = $r;
$st5->close();

$primerIncompletoIdxNuevo = 1;
foreach ($items as $i => $it) {
    $idIt = (int)$it['id_item']; $tp = $it['tipo'];
    $tiene = isset($vals[$idIt]) ? ri_val_esta_lleno($vals[$idIt], $tp) : false;
    if (!$tiene) { $primerIncompletoIdxNuevo = $i + 1; break; }
    if ($i === $totalItems - 1) $primerIncompletoIdxNuevo = $totalItems;
}

$dest = $itemIdx;
if ($itemIdx === $primerIncompletoIdx) $dest = min($primerIncompletoIdxNuevo, $totalItems);

// Mensaje con número de ítem
flash('Item ' . $itemIdx . ' guardado con éxito.', 'success');

back_to_index($dest);
