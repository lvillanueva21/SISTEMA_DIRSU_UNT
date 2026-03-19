<?php
// presentacion/logica/borrar_archivo.php — NUEVO
// Elimina físicamente el archivo y pone archivo_url=NULL para tipos pdf/excel/word.
//
// Params esperados (GET):
//   id_respuesta, id_item
//
// Requisitos: ../componentes/db.php, sesión con id_py

date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../../componentes/db.php';

function flash($msg, $type='info'){ $_SESSION['form_msg']=$msg; $_SESSION['form_msg_type']=$type; }
function back($item=null){
    $url = '../index.php';
    if ($item !== null) $url .= (strpos($url,'?')===false?'?':'&') . 'item='.(int)$item;
    header("Location: $url");
    exit;
}

// FS base para borrar:
$FS_BASE = '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web';

$id_respuesta = isset($_GET['id_respuesta']) ? (int)$_GET['id_respuesta'] : 0;
$id_item      = isset($_GET['id_item']) ? (int)$_GET['id_item'] : 0;

if ($id_respuesta<=0 || $id_item<=0) { flash('Solicitud inválida.','danger'); back(); }

$id_py = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
if ($id_py<=0) { flash('Sesión inválida.','danger'); back(); }

// 1) Validar que la respuesta pertenece al proyecto del usuario
$st = $conexion->prepare("SELECT id, id_py, id_formulario, id_cronograma FROM sm_respuestas WHERE id=? AND id_py=?");
$st->bind_param("ii",$id_respuesta,$id_py);
$st->execute();
$r = $st->get_result()->fetch_assoc();
$st->close();
if (!$r) { flash('Respuesta no encontrada.','danger'); back(); }

// 2) Validar cronograma activo tipo 2 y ventana
$st2 = $conexion->prepare("SELECT tipo, activo, apertura, cierre FROM sm_cronogramas WHERE id=?");
$st2->bind_param("i",$r['id_cronograma']);
$st2->execute();
$cr = $st2->get_result()->fetch_assoc();
$st2->close();
if (!$cr || (int)$cr['tipo']!==2 || (int)$cr['activo']!==1) { flash('Cronograma inactivo.','danger'); back(); }
$now=new DateTime('now', new DateTimeZone('America/Lima'));
$ap = new DateTime($cr['apertura'], new DateTimeZone('America/Lima'));
$ci = new DateTime($cr['cierre'],   new DateTimeZone('America/Lima'));
if (!($now>=$ap && $now<=$ci)) { flash('Fuera de la ventana de presentación.','warning'); back(); }

// 3) Verificar que el ítem pertenece al formulario y es de tipo archivo
$st3=$conexion->prepare("
  SELECT fi.orden, i.tipo
  FROM sm_formulario_items fi
  JOIN sm_items i ON i.id=fi.id_item
  WHERE fi.id_formulario=? AND fi.id_item=? AND fi.activo=1
  LIMIT 1
");
$st3->bind_param("ii", $r['id_formulario'], $id_item);
$st3->execute();
$fi=$st3->get_result()->fetch_assoc();
$st3->close();
if (!$fi) { flash('Ítem no válido para este formulario.','danger'); back(); }
$ordenItem = (int)$fi['orden'];
$tipoItem  = (string)$fi['tipo'];
if (!in_array($tipoItem, ['pdf','excel','word'], true)) { flash('El ítem no es de tipo archivo.','danger'); back($ordenItem); }

// 4) Obtener archivo actual
$st4=$conexion->prepare("SELECT archivo_url FROM sm_respuesta_items WHERE id_respuesta=? AND id_item=?");
$st4->bind_param("ii",$id_respuesta,$id_item);
$st4->execute();
$st4->bind_result($archivo_url);
$has=$st4->fetch();
$st4->close();

if (!$has || !$archivo_url) { flash('No hay archivo que borrar.','info'); back($ordenItem); }

// 5) Borrar físico (protegido)
$fsPath = $FS_BASE . $archivo_url;
if (strpos($fsPath, $FS_BASE . '/files_answer/')===0 && file_exists($fsPath)) {
    @unlink($fsPath);
}

// 6) Poner NULL en DB
$up=$conexion->prepare("UPDATE sm_respuesta_items SET archivo_url=NULL, actualizado_at=CURRENT_TIMESTAMP WHERE id_respuesta=? AND id_item=?");
$up->bind_param("ii",$id_respuesta,$id_item);
$ok=$up->execute();
$up->close();

if (!$ok) { flash('No se pudo actualizar la base de datos.','danger'); back($ordenItem); }

flash('Archivo eliminado.','success');
back($ordenItem);
