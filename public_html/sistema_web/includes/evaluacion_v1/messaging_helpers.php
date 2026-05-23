<?php
/**
 * Helpers de mensajería reutilizables desde notificaciones legacy.
 */

require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/NotificationWarnings.php';

if (!function_exists('rsu_eval_v1_eval_to_respuesta')) {
    function rsu_eval_v1_eval_to_respuesta(mysqli $db, $eval_id)
    {
        $eval_id = (int)$eval_id;
        if ($eval_id <= 0) {
            return 0;
        }
        $sql = "SELECT id_respuesta FROM eva_evaluaciones WHERE id = ? LIMIT 1";
        $st = $db->prepare($sql);
        if (!$st) {
            return 0;
        }
        $st->bind_param('i', $eval_id);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row && isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
    }
}

if (!function_exists('rsu_eval_v1_messaging_mode')) {
    function rsu_eval_v1_messaging_mode(mysqli $db)
    {
        $svc = new RSUEvaluacionV1MessagingPolicyService($db);
        return $svc->getMode();
    }
}

if (!function_exists('rsu_eval_v1_report_type')) {
    function rsu_eval_v1_report_type(mysqli $db, $id_respuesta)
    {
        $svc = new RSUEvaluacionV1ReportTypeResolverService($db);
        return $svc->resolveByRespuesta((int)$id_respuesta);
    }
}

if (!function_exists('rsu_eval_v1_report_type_from_eval')) {
    function rsu_eval_v1_report_type_from_eval(mysqli $db, $eval_id)
    {
        $id_respuesta = rsu_eval_v1_eval_to_respuesta($db, $eval_id);
        if ($id_respuesta <= 0) {
            return array(
                'ok' => false,
                'reason' => 'eval_sin_respuesta',
                'message' => 'No se pudo determinar el tipo de informe: la evaluacion no tiene respuesta asociada.',
            );
        }
        return rsu_eval_v1_report_type($db, $id_respuesta);
    }
}

if (!function_exists('rsu_eval_v1_notify_mail')) {
    function rsu_eval_v1_notify_mail(mysqli $db, array $payload, callable $sender)
    {
        $engine = rsu_eval_v1_engine($db);
        if (!$engine) {
            rsu_eval_v1_notification_add_warning('No se pudo inicializar mensajería; se omitió el envío de correo.');
            return false;
        }

        $eventCode = isset($payload['event_code']) ? strtoupper(trim((string)$payload['event_code'])) : '';
        $policy = new RSUEvaluacionV1MessagingPolicyService($db);
        $decision = $policy->getDecisionForEventCode($eventCode);
        $mode = isset($decision['mode']) ? (string)$decision['mode'] : 'log_only';
        if ($mode !== 'send_and_log') {
            $payload['skip_reason'] = isset($decision['reason']) ? (string)$decision['reason'] : 'mensajeria_desactivada';
        }
        $payload['mail_sender'] = $sender;
        $result = $engine->notify($payload, $mode);

        $status = isset($result['mail_status']) ? (string)$result['mail_status'] : '';
        if ($status === 'error') {
            $msg = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
            rsu_eval_v1_notification_add_warning($msg);
            return false;
        }

        if (!$result || empty($result['ok'])) {
            rsu_eval_v1_notification_add_warning('La evaluación se guardó correctamente, pero no se pudo auditar la mensajería.');
            return false;
        }

        return true;
    }
}
