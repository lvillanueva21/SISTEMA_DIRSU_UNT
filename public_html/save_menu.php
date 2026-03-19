<?php
require_once __DIR__.'/cw_config.php';

if (!isset($_POST['data'])) {
    http_response_code(400);
    exit(json_encode(['status'=>'error','msg'=>'Sin datos']));
}

$estructura = json_decode($_POST['data'], true);
if (!is_array($estructura)) {
    http_response_code(400);
    exit(json_encode(['status'=>'error','msg'=>'JSON malformado']));
}

$mysqli->begin_transaction();
try {
// 1) Borra todo y resetea autoincremento (funciona con claves foráneas)
$mysqli->query("DELETE FROM cw_opciones_menu");              // cascada a cw_log_menu si está ON DELETE CASCADE
$mysqli->query("ALTER TABLE cw_opciones_menu AUTO_INCREMENT = 1");

    // 2) Insertamos de arriba abajo, guardando equivalencias tmp→real
    $idMap = [];                 // tmpID => newID

    foreach ($estructura as $item) {
        // se insertarán primero los padres (parent_id = null) y luego los hijos
        if ($item['parent_id'] === null) {
            $stmt = $mysqli->prepare(
                "INSERT INTO cw_opciones_menu(texto,url,parent_id,visible,orden)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param(
                'ssiii',
                $item['texto'],
                $item['url'],
                $item['parent_id'],
                $item['visible'],
                $item['orden']
            );
            $stmt->execute();
            $idMap[$item['id']] = $stmt->insert_id;
        }
    }
    // 3) Ahora los hijos, usando el mapa
    foreach ($estructura as $item) {
        if ($item['parent_id'] !== null) {
            $parentReal = $idMap[$item['parent_id']];
            $stmt = $mysqli->prepare(
                "INSERT INTO cw_opciones_menu(texto,url,parent_id,visible,orden)
                 VALUES (?,?,?,?,?)"
            );
            $stmt->bind_param(
                'ssiii',
                $item['texto'],
                $item['url'],
                $parentReal,
                $item['visible'],
                $item['orden']
            );
            $stmt->execute();
        }
    }

    $mysqli->commit();
    echo json_encode(['status'=>'ok','msg'=>'Guardado correctamente']);
} catch (Throwable $e) {
    $mysqli->rollback();
    http_response_code(500);
    echo json_encode(['status'=>'error','msg'=>$e->getMessage()]);
}
