<?php
/**
 * Compatibilidad fina para expedientes legacy o incompletos.
 */

class RSUEvaluacionV1LegacyCompatibilityService
{
    /** @var mysqli */
    private $db;

    /** @var RSUEvaluacionV1EventLoggerService */
    private $logger;

    /** @var RSUEvaluacionV1WorkflowService */
    private $workflow;

    public function __construct($db)
    {
        $this->db = $db;
        $this->logger = new RSUEvaluacionV1EventLoggerService($db);
        $this->workflow = new RSUEvaluacionV1WorkflowService($db);
    }

    public function normalizeByRespuesta($id_respuesta)
    {
        $id_respuesta = (int)$id_respuesta;
        if ($id_respuesta <= 0) {
            return array('ok' => false, 'error' => 'id_respuesta inválido');
        }

        $eval = $this->fetchEval($id_respuesta);
        if (!$eval) {
            return $this->bootstrapEvalIfNeeded($id_respuesta);
        }

        $changes = array();
        $eval_id = (int)$eval['id'];
        $situacion = isset($eval['situacion']) ? strtolower(trim((string)$eval['situacion'])) : '';
        $oficina_id = isset($eval['id_oficina_actual']) ? (int)$eval['id_oficina_actual'] : 0;

        if ($situacion !== 'aprobado' && $situacion !== 'en_oficina') {
            $situacion = 'en_oficina';
            $this->updateEvalSituacion($eval_id, $situacion);
            $changes[] = 'situacion_normalizada';
        }

        if ($situacion === 'aprobado') {
            if ($oficina_id > 0) {
                $this->setEvalOffice($eval_id, null);
                $changes[] = 'oficina_limpiada_en_aprobado';
            }
            $this->logRecovery($id_respuesta, 'LEGACY_NORMALIZED', array('changes' => $changes));
            return array('ok' => true, 'changed' => !empty($changes), 'changes' => $changes);
        }

        if ($oficina_id <= 0) {
            $oficina_id = $this->inferCurrentOfficeId($eval_id);
            if ($oficina_id <= 0) {
                $oficina_id = $this->firstOfficeId();
            }
            if ($oficina_id > 0) {
                $this->setEvalOffice($eval_id, $oficina_id);
                $changes[] = 'oficina_actual_recuperada';
            }
        }

        if ($oficina_id > 0) {
            $inst = $this->fetchLatestInstancia($eval_id, $oficina_id);
            if (!$inst) {
                $this->createInstancia($eval_id, $oficina_id, 'en_espera');
                $changes[] = 'instancia_creada';
            } else {
                $estado_norm = $this->normalizeInstanciaEstado(isset($inst['estado']) ? (string)$inst['estado'] : '');
                if ($estado_norm !== (string)$inst['estado']) {
                    $this->updateInstanciaEstado((int)$inst['id'], $estado_norm);
                    $changes[] = 'instancia_estado_normalizado';
                }
                if ($estado_norm === 'aprobado' && empty($inst['salida'])) {
                    $this->forceInstanciaSalida((int)$inst['id']);
                    $changes[] = 'instancia_salida_reparada';
                }

                if ($estado_norm === 'aprobado') {
                    $next_id = $this->workflow->getNextOfficeId($oficina_id);
                    if ($next_id !== null) {
                        $this->setEvalOffice($eval_id, (int)$next_id);
                        $this->ensureInstancia($eval_id, (int)$next_id);
                        $changes[] = 'oficina_avanzada_desde_aprobado_legacy';
                    } else {
                        $this->updateEvalSituacion($eval_id, 'aprobado');
                        $this->setEvalOffice($eval_id, null);
                        $changes[] = 'situacion_finalizada_desde_aprobado_legacy';
                    }
                }
            }
        }

        if (!empty($changes)) {
            $this->logRecovery($id_respuesta, 'LEGACY_NORMALIZED', array('changes' => $changes));
        }

        return array('ok' => true, 'changed' => !empty($changes), 'changes' => $changes);
    }

    private function fetchEval($id_respuesta)
    {
        $sql = "SELECT id, situacion, id_oficina_actual
                FROM eva_evaluaciones
                WHERE id_respuesta = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return null;
        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            return null;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row;
    }

    private function bootstrapEvalIfNeeded($id_respuesta)
    {
        $resp = $this->fetchRespuesta($id_respuesta);
        if (!$resp) {
            return array('ok' => false, 'error' => 'Respuesta no existe');
        }
        $estado = isset($resp['estado']) ? (int)$resp['estado'] : 0;
        if ($estado <= 0) {
            return array('ok' => true, 'changed' => false, 'changes' => array());
        }

        $oficina_id = $this->firstOfficeId();
        if ($oficina_id <= 0) {
            return array('ok' => false, 'error' => 'No hay oficinas activas para iniciar ruta.');
        }

        $sql = "INSERT INTO eva_evaluaciones (id_respuesta, situacion, id_oficina_actual, creado_at, actualizado_at)
                VALUES (?, 'en_oficina', ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    situacion = VALUES(situacion),
                    id_oficina_actual = VALUES(id_oficina_actual),
                    actualizado_at = NOW()";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return array('ok' => false, 'error' => 'No se pudo crear evaluación legacy.');
        }
        $st->bind_param('ii', $id_respuesta, $oficina_id);
        $ok = $st->execute();
        $st->close();
        if (!$ok) {
            return array('ok' => false, 'error' => 'No se pudo crear evaluación legacy.');
        }

        $eval = $this->fetchEval($id_respuesta);
        if ($eval && !empty($eval['id'])) {
            $this->ensureInstancia((int)$eval['id'], $oficina_id);
        }

        $this->logRecovery($id_respuesta, 'LEGACY_EVAL_BOOTSTRAP', array('oficina_id' => $oficina_id));
        return array('ok' => true, 'changed' => true, 'changes' => array('eval_creada_legacy'));
    }

    private function fetchRespuesta($id_respuesta)
    {
        $sql = "SELECT id, id_py, estado, id_semestre
                FROM sm_respuestas
                WHERE id = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return null;
        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            return null;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row;
    }

    private function inferCurrentOfficeId($eval_id)
    {
        $sqlOpen = "SELECT id_oficina
                    FROM eva_oficina_instancias
                    WHERE id_evaluacion = ?
                      AND salida IS NULL
                    ORDER BY id DESC
                    LIMIT 1";
        $st = $this->db->prepare($sqlOpen);
        if ($st) {
            $st->bind_param('i', $eval_id);
            if ($st->execute()) {
                $res = $st->get_result();
                $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
                $st->close();
                if ($row && isset($row['id_oficina'])) {
                    return (int)$row['id_oficina'];
                }
            } else {
                $st->close();
            }
        }

        $sqlLastInst = "SELECT id_oficina
                        FROM eva_oficina_instancias
                        WHERE id_evaluacion = ?
                        ORDER BY id DESC
                        LIMIT 1";
        $st = $this->db->prepare($sqlLastInst);
        if ($st) {
            $st->bind_param('i', $eval_id);
            if ($st->execute()) {
                $res = $st->get_result();
                $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
                $st->close();
                if ($row && isset($row['id_oficina'])) {
                    return (int)$row['id_oficina'];
                }
            } else {
                $st->close();
            }
        }

        $sqlLastCal = "SELECT id_oficina
                       FROM eva_calificaciones
                       WHERE id_evaluacion = ?
                       ORDER BY id DESC
                       LIMIT 1";
        $st = $this->db->prepare($sqlLastCal);
        if ($st) {
            $st->bind_param('i', $eval_id);
            if ($st->execute()) {
                $res = $st->get_result();
                $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
                $st->close();
                if ($row && isset($row['id_oficina'])) {
                    return (int)$row['id_oficina'];
                }
            } else {
                $st->close();
            }
        }

        return 0;
    }

    private function firstOfficeId()
    {
        $offices = $this->workflow->listOffices();
        if (empty($offices)) {
            return 0;
        }
        return isset($offices[0]['id']) ? (int)$offices[0]['id'] : 0;
    }

    private function setEvalOffice($eval_id, $oficina_id = null)
    {
        if ($oficina_id === null) {
            $sql = "UPDATE eva_evaluaciones
                    SET id_oficina_actual = NULL, actualizado_at = NOW()
                    WHERE id = ?
                    LIMIT 1";
            $st = $this->db->prepare($sql);
            if (!$st) return;
            $st->bind_param('i', $eval_id);
            $st->execute();
            $st->close();
            return;
        }
        $sql = "UPDATE eva_evaluaciones
                SET id_oficina_actual = ?, situacion='en_oficina', actualizado_at = NOW()
                WHERE id = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return;
        $st->bind_param('ii', $oficina_id, $eval_id);
        $st->execute();
        $st->close();
    }

    private function updateEvalSituacion($eval_id, $situacion)
    {
        $sql = "UPDATE eva_evaluaciones
                SET situacion = ?, actualizado_at = NOW()
                WHERE id = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return;
        $st->bind_param('si', $situacion, $eval_id);
        $st->execute();
        $st->close();
    }

    private function fetchLatestInstancia($eval_id, $oficina_id)
    {
        $sql = "SELECT id, estado, llegada, salida
                FROM eva_oficina_instancias
                WHERE id_evaluacion = ?
                  AND id_oficina = ?
                ORDER BY id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return null;
        $st->bind_param('ii', $eval_id, $oficina_id);
        if (!$st->execute()) {
            $st->close();
            return null;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row;
    }

    private function ensureInstancia($eval_id, $oficina_id)
    {
        $inst = $this->fetchLatestInstancia($eval_id, $oficina_id);
        if (!$inst) {
            $this->createInstancia($eval_id, $oficina_id, 'en_espera');
        }
    }

    private function createInstancia($eval_id, $oficina_id, $estado)
    {
        $sql = "INSERT INTO eva_oficina_instancias (id_evaluacion, id_oficina, llegada, estado)
                VALUES (?, ?, NOW(), ?)
                ON DUPLICATE KEY UPDATE
                    llegada = COALESCE(llegada, NOW()),
                    salida = NULL,
                    estado = VALUES(estado)";
        $st = $this->db->prepare($sql);
        if (!$st) return;
        $st->bind_param('iis', $eval_id, $oficina_id, $estado);
        $st->execute();
        $st->close();
    }

    private function normalizeInstanciaEstado($estado)
    {
        $e = strtolower(trim((string)$estado));
        if ($e === 'aprobado' || $e === 'observado' || $e === 'en_espera') {
            return $e;
        }
        if ($e === 'espera' || $e === 'en_revision' || $e === 'en_oficina' || $e === '') {
            return 'en_espera';
        }
        if ($e === 'aprobada') {
            return 'aprobado';
        }
        if ($e === 'observada') {
            return 'observado';
        }
        return 'en_espera';
    }

    private function updateInstanciaEstado($instancia_id, $estado)
    {
        $sql = "UPDATE eva_oficina_instancias
                SET estado = ?
                WHERE id = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return;
        $st->bind_param('si', $estado, $instancia_id);
        $st->execute();
        $st->close();
    }

    private function forceInstanciaSalida($instancia_id)
    {
        $sql = "UPDATE eva_oficina_instancias
                SET salida = NOW()
                WHERE id = ?
                  AND salida IS NULL
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return;
        $st->bind_param('i', $instancia_id);
        $st->execute();
        $st->close();
    }

    private function logRecovery($id_respuesta, $event_code, $detail)
    {
        $this->logger->log(
            (int)$id_respuesta,
            (string)$event_code,
            null,
            null,
            $detail,
            isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : null,
            isset($_SERVER['REMOTE_ADDR']) ? (string)$_SERVER['REMOTE_ADDR'] : null
        );
    }
}
