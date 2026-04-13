<?php
// semestral/logica/borrar_archivo.php — elimina archivo del ítem y muestra modal vía flash.
// Acepta POST (preferente) y GET por compatibilidad.
// Espera: id_respuesta, id_item, (opcional) return_item

declare(strict_types=1);
date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../componentes/db.php'; // $conexion (mysqli)

// FS base portable para borrar archivos (raiz de sistema_web).
$FS_BASE          = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
$DIR_BASE_ALLOWED = rtrim($FS_BASE, '/\\') . '/files_answer/'; // whitelist de seguridad

// Helpers
function back_to_index($item = null) {
    // Redirige al index del modulo semestral con ruta relativa (portable).
    $url = '../index.php';
    if ($item !== null) {
        $sep = (strpos($url, '?') === false) ? '?' : '&';
        $url .= $sep . 'item=' . (int)$item;
    }
    header('Location: ' . $url, true, 303); // 303 para evitar re-POST
    exit;
}
function flash($msg, $type='info'){
    $_SESSION['form_msg'] = $msg;
    $_SESSION['form_msg_type'] = $type;
}

// Inputs (prioriza POST; admite GET por compatibilidad)
$id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : (int)($_GET['id_respuesta'] ?? 0);
$id_item      = isset($_POST['id_item'])      ? (int)$_POST['id_item']      : (int)($_GET['id_item'] ?? 0);
$return_item  = isset($_POST['return_item'])  ? (int)$_POST['return_item']  : (isset($_GET['return_item']) ? (int)$_GET['return_item'] : null);

if ($id_respuesta <= 0 || $id_item <= 0) {
    flash('Solicitud inválida para borrar archivo.', 'danger'); back_to_index($return_item);
}

$id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
if ($id_py <= 0) { flash('Sesión inválida o proyecto no seleccionado.', 'danger'); back_to_index($return_item); }

// 1) Validar que la respuesta pertenece al proyecto del usuario
$st = $conexion->prepare("
  SELECT id, id_py, id_formulario, id_cronograma, estado
  FROM sm_respuestas
  WHERE id=? AND id_py=?");
$st->bind_param("ii", $id_respuesta, $id_py);
$st->execute();
$resp = $st->get_result()->fetch_assoc();
$st->close();
if (!$resp) { flash('No se encontró la respuesta o no pertenece a tu proyecto.', 'danger'); back_to_index($return_item); }

// 2) Validar cronograma activo tipo 2 y ventana
$st2 = $conexion->prepare("SELECT tipo, activo, apertura, cierre FROM sm_cronogramas WHERE id=?");
$st2->bind_param("i", $resp['id_cronograma']);
$st2->execute();
$cr = $st2->get_result()->fetch_assoc();
$st2->close();
if (!$cr || (int)$cr['tipo'] !== 2 || (int)$cr['activo'] !== 1) { flash('No hay un cronograma activo válido.', 'danger'); back_to_index($return_item); }

$tz = new DateTimeZone('America/Lima');
$now      = new DateTime('now', $tz);
$apertura = new DateTime($cr['apertura'], $tz);
$cierre   = new DateTime($cr['cierre'],   $tz);
if (!($now >= $apertura && $now <= $cierre)) { flash('Fuera de la ventana de presentación. No se puede borrar.', 'warning'); back_to_index($return_item); }

// 3) Verificar que el ítem pertenece al formulario y es de tipo archivo; obtener su orden
$st3 = $conexion->prepare("
  SELECT fi.orden, i.tipo
  FROM sm_formulario_items fi
  JOIN sm_items i ON i.id = fi.id_item
  WHERE fi.id_formulario=? AND fi.id_item=? AND fi.activo=1
  LIMIT 1
");
$st3->bind_param("ii", $resp['id_formulario'], $id_item);
$st3->execute();
$fi = $st3->get_result()->fetch_assoc();
$st3->close();
if (!$fi) { flash('Ítem no válido para este formulario.', 'danger'); back_to_index($return_item); }
$ordenItem = (int)$fi['orden'];
$tipoItem  = (string)$fi['tipo'];
if (!in_array($tipoItem, ['pdf','excel','word'], true)) { flash('El ítem no es de tipo archivo.', 'danger'); back_to_index($return_item ?? $ordenItem); }

// 4) Obtener archivo actual
$st4 = $conexion->prepare("SELECT archivo_url FROM sm_respuesta_items WHERE id_respuesta=? AND id_item=? LIMIT 1");
$st4->bind_param("ii", $id_respuesta, $id_item);
$st4->execute();
$st4->bind_result($archivo_url);
$has = $st4->fetch();
$st4->close();

if (!$has || !$archivo_url) {
    flash('No hay archivo que borrar en este ítem.', 'info'); back_to_index($return_item ?? $ordenItem);
}

// 5) Borrar físico (con whitelist para seguridad)
$fsPath = $FS_BASE . $archivo_url;
if (strpos($fsPath, $DIR_BASE_ALLOWED) === 0 && file_exists($fsPath)) {
    @unlink($fsPath);
}

// 6) Dejar archivo_url en NULL y recalcular estado de la cabecera (nueva lógica)
$conexion->begin_transaction();
try {
    // 6.1 Actualiza el ítem (borra vínculo al archivo)
    $up = $conexion->prepare("
      UPDATE sm_respuesta_items
      SET archivo_url = NULL, actualizado_at = CURRENT_TIMESTAMP
      WHERE id_respuesta=? AND id_item=?");
    $up->bind_param("ii", $id_respuesta, $id_item);
    if (!$up->execute()) throw new RuntimeException("Error al actualizar ítem: ".$up->error);
    $up->close();

    // 6.2 Obtener todos los ítems del formulario (para evaluar completitud)
    $stI = $conexion->prepare("
      SELECT fi.id_item, i.tipo
      FROM sm_formulario_items fi
      JOIN sm_items i ON i.id = fi.id_item
      WHERE fi.id_formulario=? AND fi.activo=1
      ORDER BY fi.orden ASC
    ");
    $stI->bind_param("i", $resp['id_formulario']);
    $stI->execute();
    $rsI = $stI->get_result();
    $items = $rsI->fetch_all(MYSQLI_ASSOC);
    $stI->close();

    // 6.3 Valores actuales
    $stV = $conexion->prepare("
      SELECT id_item, tipo,
             val_varchar, val_longtext, val_tinyint, val_int, val_boolean, val_datetime, val_date, val_decimal, archivo_url
      FROM sm_respuesta_items
      WHERE id_respuesta=?");
    $stV->bind_param("i", $id_respuesta);
    $stV->execute();
    $rsV = $stV->get_result();
    $vals = [];
    while ($r = $rsV->fetch_assoc()) $vals[(int)$r['id_item']] = $r;
    $stV->close();

    // 6.4 Helper "está lleno"
    $esta_lleno = function(array $row, string $tipo): bool {
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
    };

    // 6.5 Contar completados
    $completos = 0;
    foreach ($items as $it) {
        $idIt = (int)$it['id_item'];
        $tp   = $it['tipo'];
        if (isset($vals[$idIt]) && $esta_lleno($vals[$idIt], $tp)) $completos++;
    }

    // 6.6 Recalcular estado de la cabecera — NUEVA LÓGICA:
    //     - Solo modificamos si la cabecera está en estado 0 (En proceso).
    //     - No cambiamos a 1 (En revisión) ni a 2 (Aprobado) ni a 3 (Observado) desde aquí.
    $stHead = $conexion->prepare("SELECT estado FROM sm_respuestas WHERE id=?");
    $stHead->bind_param("i", $id_respuesta);
    $stHead->execute();
    $stHead->bind_result($estPrev);
    $stHead->fetch();
    $stHead->close();

    $nuevoEstado = (int)$estPrev;

    if ($nuevoEstado === 0) {
        // Se mantiene en 0, independientemente de si hay 0, algunos o todos completos.
        // La transición a 1 solo se hace desde solicitar_revision.php,
        // y 2/3 las hace DIRSU.
        $nuevoEstado = 0;
    }
    // Si está en 1, 2 o 3, NO tocamos el estado.

    $upH = $conexion->prepare("UPDATE sm_respuestas SET estado=?, actualizado_at=CURRENT_TIMESTAMP WHERE id=?");
    $upH->bind_param("ii", $nuevoEstado, $id_respuesta);
    if (!$upH->execute()) throw new RuntimeException("Error al actualizar cabecera: ".$upH->error);
    $upH->close();

    $conexion->commit();
} catch (Throwable $e) {
    $conexion->rollback();
    flash('No se pudo borrar el archivo: '.$e->getMessage(), 'danger');
    back_to_index($return_item ?? $ordenItem);
}

// Mensaje de éxito → el index.php mostrará el modal automáticamente
$numero = $return_item ?? $ordenItem; // prioriza el índice que ya usa el front
flash('Item ' . (int)$numero . ': archivo eliminado con éxito.', 'success');
back_to_index($numero);
