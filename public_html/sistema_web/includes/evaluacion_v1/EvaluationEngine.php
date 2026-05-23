<?php
/**
 * Fachada del motor Evaluación V1.
 * Paso 01: contexto + permisos + workflow + eventos + notificaciones (esqueleto funcional).
 */

class RSUEvaluacionV1EvaluationEngine
{
    /** @var mysqli */
    private $db;

    /** @var RSUEvaluacionV1ContextResolver */
    private $contextResolver;

    /** @var RSUEvaluacionV1PermissionService */
    private $permissionService;

    /** @var RSUEvaluacionV1WorkflowService */
    private $workflowService;

    /** @var RSUEvaluacionV1EventLoggerService */
    private $eventLogger;

    /** @var RSUEvaluacionV1NotificationService */
    private $notificationService;
    /** @var RSUEvaluacionV1MailOutboxService */
    private $mailOutboxService;

    /** @var RSUEvaluacionV1LegacyDispatcher */
    private $legacyDispatcher;

    /** @var RSUEvaluacionV1LegacyCompatibilityService */
    private $legacyCompatibility;

    public function __construct($db, $mail_sender = null)
    {
        $this->db = $db;
        $this->contextResolver = new RSUEvaluacionV1ContextResolver($db);
        $this->permissionService = new RSUEvaluacionV1PermissionService();
        $this->workflowService = new RSUEvaluacionV1WorkflowService($db);
        $this->eventLogger = new RSUEvaluacionV1EventLoggerService($db);
        $this->mailOutboxService = new RSUEvaluacionV1MailOutboxService($db);
        $this->notificationService = new RSUEvaluacionV1NotificationService($this->eventLogger, $mail_sender, $this->mailOutboxService);
        $this->legacyCompatibility = new RSUEvaluacionV1LegacyCompatibilityService($db);
        $this->legacyDispatcher = new RSUEvaluacionV1LegacyDispatcher($db);
    }

    public function resolveContext(array $input)
    {
        return $this->contextResolver->resolve($input);
    }

    public function canEvaluate($id_rol, $accion, $office_code, $instance_state)
    {
        return $this->permissionService->canEvaluate($id_rol, $accion, $office_code, $instance_state);
    }

    public function getEvaluationStateByRespuesta($id_respuesta)
    {
        $id_respuesta = (int)$id_respuesta;
        if ($id_respuesta <= 0) {
            return null;
        }

        $sql = "SELECT
                    e.id AS eval_id,
                    e.situacion AS situacion,
                    e.id_oficina_actual AS oficina_id,
                    o.codigo AS oficina_cod,
                    o.nombre AS oficina_nom,
                    (
                        SELECT oi.estado
                        FROM eva_oficina_instancias oi
                        WHERE oi.id_evaluacion = e.id
                          AND oi.id_oficina = e.id_oficina_actual
                        ORDER BY oi.id DESC
                        LIMIT 1
                    ) AS instancia_estado
                FROM eva_evaluaciones e
                LEFT JOIN eva_oficinas o
                    ON o.id = e.id_oficina_actual
                WHERE e.id_respuesta = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return null;
        }
        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            return null;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        if (!$row) {
            return null;
        }
        return array(
            'eval_id' => isset($row['eval_id']) ? (int)$row['eval_id'] : 0,
            'situacion' => isset($row['situacion']) ? (string)$row['situacion'] : '',
            'oficina_id' => isset($row['oficina_id']) ? (int)$row['oficina_id'] : 0,
            'oficina_cod' => isset($row['oficina_cod']) ? (string)$row['oficina_cod'] : '',
            'oficina_nom' => isset($row['oficina_nom']) ? (string)$row['oficina_nom'] : '',
            'instancia_estado' => isset($row['instancia_estado']) ? (string)$row['instancia_estado'] : '',
        );
    }

    public function authorizeEvaluation($id_rol, $accion, $office_code, $id_respuesta)
    {
        $compat = $this->legacyCompatibility->normalizeByRespuesta($id_respuesta);
        if (!$compat['ok']) {
            return array(
                'ok' => false,
                'why' => isset($compat['error']) ? (string)$compat['error'] : 'No se pudo normalizar expediente legacy.',
                'state' => null,
            );
        }

        $state = $this->getEvaluationStateByRespuesta($id_respuesta);
        if (!$state) {
            return array(
                'ok' => false,
                'why' => 'No hay evaluación para el informe semestral seleccionado.',
                'state' => null,
            );
        }
        $perm = $this->permissionService->canEvaluate(
            $id_rol,
            $accion,
            isset($state['oficina_cod']) ? $state['oficina_cod'] : '',
            isset($state['instancia_estado']) ? $state['instancia_estado'] : '',
            isset($state['situacion']) ? $state['situacion'] : ''
        );
        $perm['state'] = $state;
        return $perm;
    }

    public function listWorkflowOffices()
    {
        return $this->workflowService->listOffices();
    }

    public function getNextOfficeId($current_office_id)
    {
        return $this->workflowService->getNextOfficeId($current_office_id);
    }

    public function logEvent($id_respuesta, $event_code, $office = null, $tipo = null, $detalle = null, $created_by = null, $ip = null)
    {
        return $this->eventLogger->log($id_respuesta, $event_code, $office, $tipo, $detalle, $created_by, $ip);
    }

    public function notify(array $payload, $mode = 'log_only')
    {
        return $this->notificationService->notify($payload, $mode);
    }

    public function normalizeLegacyInput($accion, array $in)
    {
        return $this->legacyDispatcher->normalizeInput($accion, $in);
    }

    public function dispatchLegacyEvaluation($office_code, $id_py, $id_respuesta, $accion, array $val, array $usr)
    {
        return $this->legacyDispatcher->dispatch($office_code, $id_py, $id_respuesta, $accion, $val, $usr);
    }
}
