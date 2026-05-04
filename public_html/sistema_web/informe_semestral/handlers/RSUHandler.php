<?php
namespace EvalV4;

require_once __DIR__ . '/../notificaciones_observacion.php';
require_once __DIR__ . '/../notificaciones_ruta.php';

class RSUHandler {
  private \mysqli $db;
  private EvaluacionService $svc;
  private RutaService $ruta;

  public function __construct(\mysqli $db){
    $this->db   = $db;
    $this->svc  = new EvaluacionService($db);
    $this->ruta = new RutaService($db);
  }

  public function guardar(int $id_py, int $id_respuesta, string $accion, array $val, array $usr): array {
    $this->svc->begin();
    try{
      $eval = $this->svc->getEvalForUpdateByRespuesta($id_respuesta);
      if(!$eval){ $this->svc->rollback(); return ['ok'=>false,'error'=>'El proyecto no inició su ruta']; }

      $eval_id   = (int)$eval['id'];
      $oficinaId = (int)$eval['id_oficina_actual'];

      if ($accion === 'cotejo') {
        $estado = (string)($val['estado'] ?? 'en_espera');
        $obs    = $val['obs'] ?? null;
        $dias   = (int)($val['dias'] ?? 0);

        if(!$this->svc->upsertCalificacionSimple($eval_id,$oficinaId,'cotejo',$estado,$obs,$dias)){
          throw new \Exception('No se pudo guardar cotejo');
        }

        $this->svc->updateInstanciaEstado($eval_id,$oficinaId, $estado==='observado' ? 'observado' : 'en_espera');

        $inst_id = null; $aprobadoTotal = false;
        if ($estado === 'aprobado') {
          $rub = $this->svc->getCalifEstado($eval_id,$oficinaId,'rubrica');
          if ($rub === 'aprobado') {
            $inst = $this->svc->getInstanciaActual($eval_id,$oficinaId);
            if($inst && !empty($inst['id'])) { $inst_id = (int)$inst['id']; $this->svc->cerrarInstancia($inst_id); }
            $aprobadoTotal = true;
            $this->svc->setOficinaActual($eval_id, null, 'aprobado');
          }
        }

        $this->svc->commit();

        if ($estado === 'observado') {
          \notif_observacion_personalizada($this->db, [
            'id_py'      => $id_py,
            'eval_id'    => $eval_id,
            'oficina_id' => $oficinaId,
            'tipo'       => 'cotejo',
            'obs_text'   => is_string($obs) ? $obs : null,
          ]);
        } elseif ($inst_id && $aprobadoTotal) {
          \notif_aprobacion_total($this->db, [
            'id_py'        => $id_py,
            'eval_id'      => $eval_id,
            'of_ultima_id' => $oficinaId,
            'instancia_id' => $inst_id,
          ]);
        }

        return ['ok'=>true];
      }

      if ($accion === 'rubrica') {
        $asp    = $val['aspectos'] ?? [];
        $total  = array_sum(array_map(fn($a)=>(int)($a['score']??0), $asp));
        $estado = ($total>=14) ? 'aprobado' : (($total===0)?'en_espera':'observado');
        $dias   = (int)($val['dias'] ?? 0);

        if(!$this->svc->upsertRubrica($eval_id,$oficinaId,$asp,$total,$estado,$dias)){
          throw new \Exception('No se pudo guardar rúbrica');
        }

        $this->svc->updateInstanciaEstado($eval_id,$oficinaId, $estado==='observado' ? 'observado' : 'en_espera');

        $inst_id = null; $aprobadoTotal = false;
        if ($estado === 'aprobado') {
          $cot = $this->svc->getCalifEstado($eval_id,$oficinaId,'cotejo');
          if ($cot === 'aprobado') {
            $inst = $this->svc->getInstanciaActual($eval_id,$oficinaId);
            if($inst && !empty($inst['id'])) { $inst_id = (int)$inst['id']; $this->svc->cerrarInstancia($inst_id); }
            $aprobadoTotal = true;
            $this->svc->setOficinaActual($eval_id, null, 'aprobado');
          }
        }

        $this->svc->commit();

        if ($estado === 'observado') {
          \notif_observacion_personalizada($this->db, [
            'id_py'      => $id_py,
            'eval_id'    => $eval_id,
            'oficina_id' => $oficinaId,
            'tipo'       => 'rubrica',
            'obs_text'   => null,
          ]);
        } elseif ($inst_id && $aprobadoTotal) {
          \notif_aprobacion_total($this->db, [
            'id_py'        => $id_py,
            'eval_id'      => $eval_id,
            'of_ultima_id' => $oficinaId,
            'instancia_id' => $inst_id,
          ]);
        }

        return ['ok'=>true, 'total'=>$total, 'estado'=>$estado];
      }

      $this->svc->rollback();
      return ['ok'=>false,'error'=>'Acción no soportada en RSU'];

    }catch(\Throwable $e){
      $this->svc->rollback();
      return ['ok'=>false,'error'=>$e->getMessage()];
    }
  }
}
