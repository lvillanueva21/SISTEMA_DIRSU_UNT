<?php
namespace EvalV4;

require_once __DIR__ . '/../notificaciones_ruta.php';

class DDHandler {
  private \mysqli $db;
  private EvaluacionService $svc;
  private RutaService $ruta;

  public function __construct(\mysqli $db){
    $this->db   = $db;
    $this->svc  = new EvaluacionService($db);
    $this->ruta = new RutaService($db);
  }

  /**
   * Guarda el "Visto Bueno" en Dirección de Departamento (DD).
   * - No usa observación: se envía NULL.
   * - No usa días: se envía 0.
   */
  public function guardar(int $id_py, int $id_respuesta, string $accion, array $val, array $usr): array {
    if ($accion !== 'vb') return ['ok'=>false,'error'=>'Acción no soportada en DD'];

    $this->svc->begin();
    try{
      $eval = $this->svc->getEvalForUpdateByRespuesta($id_respuesta);
      if(!$eval){
        $this->svc->rollback();
        return ['ok'=>false,'error'=>'El proyecto no inició su ruta'];
      }

      $eval_id   = (int)$eval['id'];
      $oficinaId = (int)$eval['id_oficina_actual'];
      $estado    = (string)($val['estado'] ?? 'espera'); // 'aprobado' | 'espera'

      // Observación: NULL (no se registra en DD)
      // Días: 0 (no aplica en DD)
      if (!$this->svc->upsertCalificacionSimple($eval_id, $oficinaId, 'vistobueno', $estado, null, 0)) {
        throw new \Exception('No se pudo guardar visto bueno');
      }

      // Estado de la instancia en DD
      $this->svc->updateInstanciaEstado(
        $eval_id,
        $oficinaId,
        $estado === 'aprobado' ? 'aprobado' : 'en_espera'
      );

      // Flujo de avance si fue aprobado
      $inst_id = null; $nextId = null; $aprobadoTotal = false;
      if ($estado === 'aprobado') {
        $inst = $this->svc->getInstanciaActual($eval_id, $oficinaId);
        if ($inst && !empty($inst['id'])) {
          $inst_id = (int)$inst['id'];
          $this->svc->cerrarInstancia($inst_id);
        }

        $next = $this->ruta->siguienteOficinaId($oficinaId);
        if ($next !== null) {
          $nextId = (int)$next;
          $this->svc->abrirSiguienteOficina($eval_id, $nextId);
          $this->svc->setOficinaActual($eval_id, $nextId, 'en_oficina');
        } else {
          $aprobadoTotal = true;
          $this->svc->setOficinaActual($eval_id, null, 'aprobado');
        }
      }

      $this->svc->commit();

      // Notificaciones de derivación o aprobación total
      if ($inst_id) {
        if ($nextId) {
          \notif_derivacion($this->db, [
            'id_py'         => $id_py,
            'eval_id'       => $eval_id,
            'of_origen_id'  => $oficinaId,
            'of_destino_id' => $nextId,
            'instancia_id'  => $inst_id,
          ]);
        } elseif ($aprobadoTotal) {
          \notif_aprobacion_total($this->db, [
            'id_py'        => $id_py,
            'eval_id'      => $eval_id,
            'of_ultima_id' => $oficinaId,
            'instancia_id' => $inst_id,
          ]);
        }
      }

      return ['ok'=>true];

    } catch (\Throwable $e) {
      $this->svc->rollback();
      return ['ok'=>false,'error'=>$e->getMessage()];
    }
  }
}
