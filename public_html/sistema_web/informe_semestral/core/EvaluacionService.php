<?php 
namespace EvalV4;

class EvaluacionService {
  private \mysqli $db;
  public function __construct(\mysqli $db){ $this->db = $db; }

  public function begin(){ $this->db->begin_transaction(); }
  public function commit(){ $this->db->commit(); }
  public function rollback(){ $this->db->rollback(); }

  /**
   * Normaliza estados de calificaciones/instancias (no confundir con 'situacion' de eva_evaluaciones).
   * Se permite 'en_oficina' por robustez, aunque aquí normalmente no se usa.
   */
  private static function normEstado(string $estado): string {
    $e = strtolower(trim($estado));
    if (in_array($e, ['aprobado','observado','en_espera','en_oficina'], true)) return $e;
    return 'en_espera';
  }

  /**
   * Normaliza la 'situacion' de eva_evaluaciones (campo de alto nivel del flujo).
   * Durante el trámite: 'en_oficina'; al finalizar: 'aprobado'.
   */
  private static function normSituacion(string $situacion): string {
    $s = strtolower(trim($situacion));
    if (in_array($s, ['en_oficina','aprobado'], true)) return $s;
    // Fallback prudente
    return 'en_oficina';
  }

  /** Última evaluación del proyecto, con FOR UPDATE (debes estar en transacción) */
  public function getEvalForUpdate(int $id_py): ?array {
    $sql = "SELECT e.*
            FROM eva_evaluaciones e
            JOIN sm_respuestas r ON r.id = e.id_respuesta
            WHERE r.id_py = ?
            ORDER BY e.id DESC
            LIMIT 1 FOR UPDATE";
    $st = $this->db->prepare($sql);
    $st->bind_param('i', $id_py);
    $st->execute();
    $res = $st->get_result();
    $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
    $st->close();
    return $row;
  }

  /** Única por (evaluación, oficina) gracias a uk_eval_ofi */
  public function getInstanciaActual(int $eval_id, int $oficina_id): ?array {
    $sql = "SELECT *
            FROM eva_oficina_instancias
            WHERE id_evaluacion=? AND id_oficina=?
            LIMIT 1";
    $st = $this->db->prepare($sql);
    $st->bind_param('ii', $eval_id, $oficina_id);
    $st->execute();
    $res = $st->get_result();
    $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
    $st->close();
    return $row;
  }

  /** Cambia estado instancia; marca timestamp si es observado */
  public function updateInstanciaEstado(int $eval_id, int $oficina_id, string $estado): bool {
    $estado = self::normEstado($estado);
    if ($estado === 'observado') {
      $sql = "UPDATE eva_oficina_instancias
              SET estado='observado', ultima_observacion_at = NOW()
              WHERE id_evaluacion=? AND id_oficina=? LIMIT 1";
      $st = $this->db->prepare($sql);
      $st->bind_param('ii', $eval_id, $oficina_id);
    } else {
      $sql = "UPDATE eva_oficina_instancias
              SET estado=?
              WHERE id_evaluacion=? AND id_oficina=? LIMIT 1";
      $st = $this->db->prepare($sql);
      $st->bind_param('sii', $estado, $eval_id, $oficina_id);
    }
    $ok = $st->execute(); $st->close();
    return $ok;
  }

  public function cerrarInstancia(int $instancia_id): bool {
    $sql = "UPDATE eva_oficina_instancias
            SET salida = NOW(), estado = 'aprobado'
            WHERE id = ? LIMIT 1";
    $st = $this->db->prepare($sql);
    $st->bind_param('i', $instancia_id);
    $ok = $st->execute(); $st->close();
    return $ok;
  }

  /** Abre o reabre la siguiente oficina (id único por eval+oficina) */
  public function abrirSiguienteOficina(int $eval_id, int $sig_oficina_id): bool {
    $sql = "INSERT INTO eva_oficina_instancias (id_evaluacion, id_oficina, llegada, estado)
            VALUES (?,?,NOW(),'en_espera')
            ON DUPLICATE KEY UPDATE llegada=VALUES(llegada), salida=NULL, estado='en_espera'";
    $st = $this->db->prepare($sql);
    $st->bind_param('ii', $eval_id, $sig_oficina_id);
    $ok = $st->execute(); $st->close();
    return $ok;
  }

  /** Usa normSituacion para escribir en eva_evaluaciones.situacion (evita el truncado) */
  public function setOficinaActual(int $eval_id, ?int $oficina_id, string $situacion): bool {
    $situacion = self::normSituacion($situacion);
    if ($oficina_id === null) {
      $sql = "UPDATE eva_evaluaciones
              SET id_oficina_actual = NULL, situacion = ?, actualizado_at = NOW()
              WHERE id = ? LIMIT 1";
      $st  = $this->db->prepare($sql);
      $st->bind_param('si', $situacion, $eval_id);
    } else {
      $sql = "UPDATE eva_evaluaciones
              SET id_oficina_actual = ?, situacion = ?, actualizado_at = NOW()
              WHERE id = ? LIMIT 1";
      $st  = $this->db->prepare($sql);
      $st->bind_param('isi', $oficina_id, $situacion, $eval_id);
    }
    $ok = $st->execute(); $st->close();
    return $ok;
  }

  /**
   * Cotejo / VB: usa obs_general. Guarda dias_subsanacion solo si estado='observado'.
   * Si el estado != 'observado', se limpia obs_general para evitar residuos.
   */
  public function upsertCalificacionSimple(
    int $eval_id, int $oficina_id, string $tipo, string $estado, ?string $obs, int $dias
  ): bool {
    $tipo   = strtolower($tipo);
    $estado = self::normEstado($estado);

    $dias_to_save = ($estado === 'observado' && $dias > 0) ? $dias : null;
    $obs_to_save  = ($estado === 'observado') ? $obs : null;

    $sql = "INSERT INTO eva_calificaciones (id_evaluacion, id_oficina, tipo, estado, obs_general, dias_subsanacion, creado_at, actualizado_at)
            VALUES (?,?,?,?,?,?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              estado = VALUES(estado),
              obs_general = VALUES(obs_general),
              dias_subsanacion = VALUES(dias_subsanacion),
              actualizado_at = NOW()";
    $st = $this->db->prepare($sql);
    $st->bind_param('iisssi', $eval_id, $oficina_id, $tipo, $estado, $obs_to_save, $dias_to_save);
    $ok = $st->execute(); $st->close();
    if (!$ok) return false;

    if ($estado === 'observado') {
      $sql2 = "UPDATE eva_calificaciones
               SET ultimo_observado_at = NOW()
               WHERE id_evaluacion=? AND id_oficina=? AND tipo=? LIMIT 1";
      $st2 = $this->db->prepare($sql2);
      $st2->bind_param('iis', $eval_id, $oficina_id, $tipo);
      $st2->execute(); $st2->close();
    }
    return true;
  }

  /**
   * RÚBRICA:
   * - upsert en eva_calificaciones (tipo='rubrica', columnas: estado, total, dias_subsanacion),
   * - upsert en eva_rubrica_aspectos por (id_calificacion, aspecto).
   * REGLAS:
   *   - Si $total === 0 => forzamos estado 'en_espera'.
   *   - Solo se guarda 'observacion' cuando la nota del aspecto es 1 o 2.
   *   - Si el global NO es 'observado' => se limpian TODAS las observaciones.
   */
  public function upsertRubrica(
    int $eval_id, int $oficina_id, array $aspectos, int $total, string $estado, int $dias
  ): bool {
    $tipo   = 'rubrica';
    $estado = self::normEstado($estado);

    if ($total === 0) $estado = 'en_espera';

    $dias_to_save = ($estado === 'observado' && $dias > 0) ? $dias : null;

    $sql = "INSERT INTO eva_calificaciones (id_evaluacion,id_oficina,tipo,estado,total,dias_subsanacion,creado_at,actualizado_at)
            VALUES (?,?,?,?,?,?, NOW(), NOW())
            ON DUPLICATE KEY UPDATE
              estado = VALUES(estado),
              total  = VALUES(total),
              dias_subsanacion = VALUES(dias_subsanacion),
              actualizado_at = NOW()";
    $st = $this->db->prepare($sql);
    $st->bind_param('iissii', $eval_id, $oficina_id, $tipo, $estado, $total, $dias_to_save);
    if (!$st->execute()) { $st->close(); return false; }
    $st->close();

    if ($estado === 'observado') {
      $sql2 = "UPDATE eva_calificaciones
               SET ultimo_observado_at = NOW()
               WHERE id_evaluacion=? AND id_oficina=? AND tipo='rubrica' LIMIT 1";
      $st2 = $this->db->prepare($sql2);
      $st2->bind_param('ii', $eval_id, $oficina_id);
      $st2->execute(); $st2->close();
    }

    // 2) id de la calificación rubrica
    $cal_id = null;
    $st2 = $this->db->prepare("SELECT id FROM eva_calificaciones WHERE id_evaluacion=? AND id_oficina=? AND tipo='rubrica' LIMIT 1");
    $st2->bind_param('ii', $eval_id, $oficina_id);
    $st2->execute(); $res2 = $st2->get_result();
    if ($res2 && $res2->num_rows) $cal_id = (int)$res2->fetch_assoc()['id'];
    $st2->close();
    if (!$cal_id) return false;

    // 3) upsert de aspectos (orden fijo) usando índices 1..5 del payload
    $orden = ['estructura','contenido','redaccion','calidad_info','propuesta_mejora'];

    $sqlA = "INSERT INTO eva_rubrica_aspectos (id_calificacion, aspecto, nota, observacion)
             VALUES (?,?,?,?)
             ON DUPLICATE KEY UPDATE
               nota = VALUES(nota),
               observacion = VALUES(observacion)";
    $stA = $this->db->prepare($sqlA);

    for ($i = 0; $i < count($orden); $i++) {
      $asp = $orden[$i];
      $a   = $aspectos[$i+1] ?? [];

      $nota = (int)($a['score'] ?? 0);
      if ($nota < 0) $nota = 0;
      if ($nota > 4) $nota = 4;

      // Regla de negocio:
      //   Solo permitir observación cuando la nota es 1 o 2 Y el global es 'observado'.
      //   Para 0 (en espera) o 3/4, dejamos NULL y así se borra cualquier residuo anterior.
      $obs = null;
      if ($estado === 'observado' && ($nota === 1 || $nota === 2)) {
        $raw = array_key_exists('obs', $a) ? (string)$a['obs'] : null;
        if (isset($raw)) {
          $raw = trim($raw);
          if ($raw !== '') $obs = substr($raw, 0, 3000);
        }
      }

      $stA->bind_param('isis', $cal_id, $asp, $nota, $obs);
      if (!$stA->execute()) { $stA->close(); return false; }
    }
    $stA->close();

    // 4) Limpieza masiva si el global NO es 'observado'
    if ($estado !== 'observado') {
      if ($stC = $this->db->prepare("UPDATE eva_rubrica_aspectos SET observacion = NULL WHERE id_calificacion = ?")) {
        $stC->bind_param('i', $cal_id);
        $stC->execute(); $stC->close();
      }
    }

    return true;
  }

  public function getCalifEstado(int $eval_id, int $oficina_id, string $tipo): ?string {
    $sql = "SELECT estado
            FROM eva_calificaciones
            WHERE id_evaluacion=? AND id_oficina=? AND tipo=? LIMIT 1";
    $st = $this->db->prepare($sql);
    $st->bind_param('iis', $eval_id, $oficina_id, $tipo);
    $st->execute(); $res = $st->get_result();
    $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
    $st->close();
    return $row ? (string)$row['estado'] : null;
  }
}
