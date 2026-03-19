<?php
// /sistema_web/evaluacion/api_eval.php
header('Content-Type: application/json; charset=utf-8');
date_default_timezone_set('America/Lima');

register_shutdown_function(function () {
    $e = error_get_last();
    if ($e && in_array($e['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        http_response_code(500);
        echo json_encode(['ok'=>false,'msg'=>'Fatal: '.$e['message'].' @'.$e['file'].':'.$e['line']], JSON_UNESCAPED_UNICODE);
    }
});

try {
    require_once __DIR__ . '/../componentes/configSesion.php';
    require_once __DIR__ . '/../componentes/db.php';
    require_once __DIR__ . '/funciones.php';

    $respond = function($ok,$msg='',$extra=[]){
        echo json_encode(array_merge(['ok'=>$ok,'msg'=>$msg],$extra), JSON_UNESCAPED_UNICODE);
        exit;
    };

    $do     = $_POST['do'] ?? $_GET['do'] ?? '';
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    $id_py  = (int)($_POST['id_py'] ?? $_GET['id_py'] ?? 0);

    if ($do === 'ping') $respond(true,'pong',['ts'=>date('Y-m-d H:i:s')]);

    /* ===================== HELPERS DB ===================== */
    function db_int($q){
        global $conexion;
        $rs = mysqli_query($conexion,$q);
        $v = 0;
        if($rs && ($r=mysqli_fetch_row($rs))) $v=(int)$r[0];
        if($rs) mysqli_free_result($rs);
        return $v;
    }
    function db_val($q){
        global $conexion;
        $rs = mysqli_query($conexion,$q);
        $v = null;
        if($rs && ($r=mysqli_fetch_row($rs))) $v=$r[0];
        if($rs) mysqli_free_result($rs);
        return $v;
    }
    function esc($s){ global $conexion; return mysqli_real_escape_string($conexion,(string)$s); }

    function next_intento($rid,$office,$tipo){
        $rid=(int)$rid; $office=(int)$office; $tipo=(int)$tipo;
        $sql = "SELECT COALESCE(MAX(intento),0)+1
                FROM ev_intentos
                WHERE id_respuesta=$rid AND office=$office AND tipo=$tipo";
        return db_int($sql);
    }
    function close_active($rid,$office,$tipo){
        global $conexion;
        $user = esc($_SESSION['usuario']??'system');
        $sql  = "UPDATE ev_intentos
                 SET active=0, closed_at=NOW(), closed_by='$user'
                 WHERE id_respuesta=".(int)$rid." AND office=".(int)$office." AND tipo=".(int)$tipo." AND active=1";
        mysqli_query($conexion,$sql);
    }

    /* ===== helpers de ruta/oficina/estados ===== */
    function current_office($rid){
        $rid=(int)$rid;
        // 1) ev_casos
        $o = db_val("SELECT current_office FROM ev_casos WHERE id_respuesta=$rid LIMIT 1");
        if ($o===null || $o==='') {
            // 2) tracking abierto
            $o = db_val("SELECT office FROM ev_oficinas_tracking WHERE id_respuesta=$rid AND left_at IS NULL ORDER BY arrived_at DESC, id DESC LIMIT 1");
        }
        return (int)$o;
    }
    function set_resp_estado($rid, $estado){
        global $conexion;
        $rid=(int)$rid; $estado=(int)$estado;
        mysqli_query($conexion,"UPDATE sm_respuestas SET estado=$estado, actualizado_at=NOW() WHERE id=$rid");
    }
    function open_track($rid,$office){
        global $conexion; $rid=(int)$rid; $office=(int)$office;
        mysqli_query($conexion,"INSERT INTO ev_oficinas_tracking (id_respuesta,office,arrived_at) VALUES ($rid,$office,NOW())");
        mysqli_query($conexion,"UPDATE ev_casos SET current_office=$office, route_status=1, updated_at=NOW() WHERE id_respuesta=$rid");
    }
    function close_track($rid,$office){
        global $conexion; $rid=(int)$rid; $office=(int)$office;
        mysqli_query($conexion,"UPDATE ev_oficinas_tracking SET left_at=NOW() WHERE id_respuesta=$rid AND office=$office AND left_at IS NULL");
    }
    function aprobar_total($rid){
        global $conexion;
        $rid=(int)$rid;
        // cerrar tracking de RSU
        close_track($rid,4);
        // caso cerrado
        mysqli_query($conexion,"UPDATE ev_casos SET route_status=2, current_office=NULL, aprobado_total_at=NOW(), updated_at=NOW() WHERE id_respuesta=$rid");
        // sm_respuestas aprobado
        set_resp_estado($rid,2);
        // evento
        $user = esc($_SESSION['usuario']??'system');
        $ip   = esc($_SERVER['REMOTE_ADDR']??'');
        mysqli_query($conexion,"INSERT INTO ev_eventos (id_respuesta,event_code,office,detalle,created_by,ip) VALUES ($rid,'APROBADO_TOTAL',4,'Cotejo y Rúbrica aprobados','{$user}','{$ip}')");
    }

    /**
     * Mira los intentos activos de la oficina actual y:
     * - PCF/RSU: si Cotejo==1 y Rubrica==1 → derivar (PCF→DD, RSU→Aprobación total).
     *            si alguno ==2 → Observado; si no, En revisión.
     * - DD/DF  : si VB==1 → derivar a la siguiente (DD→DF / DF→RSU), si no, En revisión.
     * Además actualiza sm_respuestas.estado según corresponda.
     */
    function evaluar_y_derivar($rid){
        $rid=(int)$rid;
        $office = current_office($rid);
        if (!$office) return;

        $getEstado = function($tipo) use ($rid){
            $q = "SELECT estado
                  FROM ev_intentos
                  WHERE id_respuesta=$rid AND office=".current_office($rid)." AND tipo=$tipo AND active=1
                  ORDER BY id DESC
                  LIMIT 1";
            $v = db_val($q);
            return ($v===null || $v==='') ? null : (int)$v;
        };

        if ($office===1 || $office===4) {
            $c = $getEstado(1); // cotejo
            $r = $getEstado(2); // rúbrica
            if ($c===2 || $r===2) { set_resp_estado($rid,3); return; } // observado
            if ($c===1 && $r===1) {
                if ($office===1) { // PCF→DD
                    close_track($rid,1);
                    open_track($rid,2);
                    set_resp_estado($rid,1);
                } else { // RSU: fin
                    aprobar_total($rid);
                }
                return;
            }
            set_resp_estado($rid,1); // en revisión
            return;
        }

        if ($office===2 || $office===3) {
            $vb = $getEstado(3);
            if ($vb===1) {
                $next = ($office===2) ? 3 : 4; // DD→DF, DF→RSU
                close_track($rid,$office);
                open_track($rid,$next);
                set_resp_estado($rid,1);
                return;
            }
            set_resp_estado($rid,1); // en revisión
            return;
        }
    }

    /* ===================== LOAD ===================== */
    if ($do === 'load') {
        if (!$id_py || !in_array($accion,['cotejo','rubrica','vb'],true)) $respond(false,'Parámetros incompletos.');
        $ev = ev_estado_para_ui($id_py);

        $header = '<div><strong>Proyecto:</strong> ID '.htmlspecialchars((string)$id_py).'</div>';
        $header.= '<div><strong>Revisión:</strong> '.htmlspecialchars($ev['estado_txt'] ?? 'Sin Informe').'</div>';
        $header.= '<div><strong>Oficina:</strong> '.htmlspecialchars($ev['oficina_txt'] ?? 'Sin Oficina').'</div>';

        $prefill = [];
        if ($accion === 'cotejo') {
            $prefill = ['estado'=>0,'obs_general'=>''];
            if (!empty($ev['respuesta_id'])) {
                $rid = (int)$ev['respuesta_id'];
                $sql = "SELECT estado, COALESCE(obs_general,'') AS obs_general
                        FROM ev_intentos
                        WHERE id_respuesta=$rid AND tipo=1
                        ORDER BY id DESC LIMIT 1";
                if ($rs = mysqli_query($conexion,$sql)) {
                    if ($r = mysqli_fetch_assoc($rs)) {
                        $prefill['estado'] = (int)$r['estado'];
                        $prefill['obs_general'] = (string)$r['obs_general'];
                    }
                    mysqli_free_result($rs);
                }
            }
        } elseif ($accion === 'vb') {
            $prefill = ['estado'=>0];
            if (!empty($ev['respuesta_id'])) {
                $rid = (int)$ev['respuesta_id'];
                $sql = "SELECT estado FROM ev_intentos WHERE id_respuesta=$rid AND tipo=3
                        ORDER BY id DESC LIMIT 1";
                if ($rs = mysqli_query($conexion,$sql)) {
                    if ($r = mysqli_fetch_assoc($rs)) $prefill['estado'] = (int)$r['estado'];
                    mysqli_free_result($rs);
                }
            }
        } else { // rubrica
            $prefill = ['notas'=>['1'=>0,'2'=>0,'3'=>0,'4'=>0,'5'=>0],'obs'=>[]];
            if (!empty($ev['respuesta_id'])) {
                $rid = (int)$ev['respuesta_id'];
                $rbId = 0;
                $rs = mysqli_query($conexion,"SELECT id FROM ev_intentos WHERE id_respuesta=$rid AND tipo=2 ORDER BY id DESC LIMIT 1");
                if ($rs && ($r = mysqli_fetch_assoc($rs))) { $rbId = (int)$r['id']; mysqli_free_result($rs); }
                if ($rbId) {
                    $rs = mysqli_query($conexion,"SELECT aspecto, nota, COALESCE(observacion,'') AS obs FROM ev_rubrica_aspectos WHERE intento_id=$rbId");
                    if ($rs) {
                        while ($r = mysqli_fetch_assoc($rs)) {
                            $prefill['notas'][(string)$r['aspecto']] = (int)$r['nota'];
                            if (trim($r['obs'])!=='') $prefill['obs'][(string)$r['aspecto']] = $r['obs'];
                        }
                        mysqli_free_result($rs);
                    }
                }
            }
        }

        $respond(true,'',['id_py'=>$id_py,'header_html'=>$header,'prefill'=>$prefill]);
    }

    /* ===================== GUARDAR COTEJO ===================== */
    if ($do === 'guardar_cotejo') {
        $estado = (int)($_POST['estado'] ?? 0);
        $obs    = trim((string)($_POST['obs_general'] ?? ''));
        $due    = (int)($_POST['due_days'] ?? 0);

        if (!$id_py) $respond(false,'Falta id_py.');
        if (!in_array($estado,[0,1,2],true)) $respond(false,'Estado inválido.');
        if ($estado===2 && $obs==='') $respond(false,'La observación es obligatoria si Observado.');
        if ($estado===2 && !in_array($due,[1,2],true)) $respond(false,'Debes elegir 1 o 2 días.');

        // respuesta vigente
        $rid = 0;
        $rs = mysqli_query($conexion,"SELECT id FROM sm_respuestas WHERE id_py=$id_py ORDER BY actualizado_at DESC, id DESC LIMIT 1");
        if ($rs && ($r=mysqli_fetch_assoc($rs))) { $rid=(int)$r['id']; mysqli_free_result($rs); }
        if (!$rid) $respond(false,'No tiene Informe Semestral.');

        // oficina actual (PCF o RSU)
        $ev = ev_estado_para_ui($id_py);
        $office = (int)($ev['oficina_id'] ?? 1);
        if (!in_array($office,[1,4],true)) $office = 1;

        // cerrar activos y crear nuevo intento
        close_active($rid,$office,1);
        $intento = next_intento($rid,$office,1);

        $user = esc($_SESSION['usuario']??'system');
        $obsSQL = ($obs!=='') ? ("'".esc($obs)."'") : "NULL";
        $dueAt  = ($estado===2) ? "DATE_ADD(NOW(), INTERVAL $due DAY)" : "NULL";

        $sql = "INSERT INTO ev_intentos
                  (id_respuesta, office, tipo, intento, active, estado, total, obs_general, observed_at, due_at, created_by)
                VALUES
                  ($rid,$office,1,$intento,1,$estado,0,$obsSQL,".($estado===2?"NOW()":"NULL").",$dueAt,'$user')";
        if (!mysqli_query($conexion,$sql)) $respond(false,'No se pudo guardar (cotejo): '.mysqli_error($conexion));

        // ← derivación/estado
        evaluar_y_derivar($rid);

        $respond(true,'Guardado.');
    }

    /* ===================== GUARDAR VB ===================== */
    if ($do === 'guardar_vb') {
        $estado = (int)($_POST['estado'] ?? 0);
        if (!$id_py) $respond(false,'Falta id_py.');
        if (!in_array($estado,[0,1],true)) $respond(false,'Estado inválido.');

        $rid = 0;
        $rs = mysqli_query($conexion,"SELECT id FROM sm_respuestas WHERE id_py=$id_py ORDER BY actualizado_at DESC, id DESC LIMIT 1");
        if ($rs && ($r=mysqli_fetch_assoc($rs))) { $rid=(int)$r['id']; mysqli_free_result($rs); }
        if (!$rid) $respond(false,'No tiene Informe Semestral.');

        $ev = ev_estado_para_ui($id_py);
        $office = (int)($ev['oficina_id'] ?? 2);
        if (!in_array($office,[2,3],true)) $office = 2; // DD/DF

        close_active($rid,$office,3);
        $intento = next_intento($rid,$office,3);

        $user = esc($_SESSION['usuario']??'system');
        $sql = "INSERT INTO ev_intentos
                  (id_respuesta, office, tipo, intento, active, estado, total, created_by)
                VALUES
                  ($rid,$office,3,$intento,1,$estado,0,'$user')";
        if (!mysqli_query($conexion,$sql)) $respond(false,'No se pudo guardar (VB): '.mysqli_error($conexion));

        // ← derivación/estado
        evaluar_y_derivar($rid);

        $respond(true,'Guardado.');
    }

    /* ===================== GUARDAR RÚBRICA ===================== */
    if ($do === 'guardar_rubrica') {
        if (!$id_py) $respond(false,'Falta id_py.');

        $notas = [];
        for ($i=1;$i<=5;$i++) {
            $v = (int)($_POST['rubrica'][$i] ?? 0);
            if ($v<0 || $v>4) $respond(false,"Nota inválida en aspecto $i.");
            $notas[$i]=$v;
        }
        $obs = [];
        for ($i=1;$i<=5;$i++) {
            $t = trim((string)($_POST['obs'][$i] ?? ''));
            $obs[$i] = $t!=='' ? $t : null;
        }

        $rid = 0;
        $rs = mysqli_query($conexion,"SELECT id FROM sm_respuestas WHERE id_py=$id_py ORDER BY actualizado_at DESC, id DESC LIMIT 1");
        if ($rs && ($r=mysqli_fetch_assoc($rs))) { $rid=(int)$r['id']; mysqli_free_result($rs); }
        if (!$rid) $respond(false,'No tiene Informe Semestral.');

        $ev = ev_estado_para_ui($id_py);
        $office = (int)($ev['oficina_id'] ?? 1);
        if (!in_array($office,[1,4],true)) $office = 1; // PCF/RSU

        $total = array_sum($notas);
        $anyZero = in_array(0,$notas,true);
        if ($anyZero) $estado = 0;
        elseif ($total>=14) $estado = 1;
        else $estado = 2;

        $due = (int)($_POST['due_days'] ?? 0);
        if ($estado===2 && !in_array($due,[1,2],true)) $respond(false,'Debes elegir 1 o 2 días.');

        close_active($rid,$office,2);
        $intento = next_intento($rid,$office,2);

        $user = esc($_SESSION['usuario']??'system');
        $sql = "INSERT INTO ev_intentos
                  (id_respuesta, office, tipo, intento, active, estado, total, observed_at, due_at, created_by)
                VALUES
                  ($rid,$office,2,$intento,1,$estado,$total,".($estado===2?"NOW()":"NULL").",".($estado===2?"DATE_ADD(NOW(), INTERVAL $due DAY)":"NULL").",'$user')";
        if (!mysqli_query($conexion,$sql)) $respond(false,'No se pudo crear el intento de rúbrica: '.mysqli_error($conexion));
        $intento_id = (int)mysqli_insert_id($conexion);

        for ($i=1;$i<=5;$i++) {
            $nota = (int)$notas[$i];
            $obst = $obs[$i]!==null ? ("'".esc($obs[$i])."'") : "NULL";
            $sql = "INSERT INTO ev_rubrica_aspectos (intento_id, aspecto, nota, observacion)
                    VALUES ($intento_id, $i, $nota, $obst)";
            if (!mysqli_query($conexion,$sql)) $respond(false,"No se pudo guardar aspecto $i: ".mysqli_error($conexion));
        }

        // ← derivación/estado
        evaluar_y_derivar($rid);

        $respond(true,'Guardado.',['estado'=>$estado,'total'=>$total]);
    }

    $respond(false,'Acción no especificada.');
} catch (Throwable $th) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>'Excepción: '.$th->getMessage()], JSON_UNESCAPED_UNICODE);
}
