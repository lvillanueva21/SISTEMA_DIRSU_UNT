<?php
/**
 * Notificaciones del motor V1.
 * - Respeta modo send_and_log / log_only.
 * - Audita en ev_eventos.
 * - Registra salida en msj_correos_outbox cuando existe.
 */

class RSUEvaluacionV1NotificationService
{
    /** @var RSUEvaluacionV1EventLoggerService */
    private $logger;
    /** @var callable|null */
    private $mail_sender;
    /** @var RSUEvaluacionV1MailOutboxService|null */
    private $outbox;

    public function __construct($logger, $mail_sender = null, $outbox = null)
    {
        $this->logger = $logger;
        $this->mail_sender = $mail_sender;
        $this->outbox = $outbox;
    }

    public function notify(array $payload, $mode = 'log_only')
    {
        $mode = strtolower(trim((string)$mode));
        if ($mode !== 'send_and_log') {
            $mode = 'log_only';
        }

        $id_respuesta = isset($payload['id_respuesta']) ? (int)$payload['id_respuesta'] : 0;
        $event_code = isset($payload['event_code']) ? (string)$payload['event_code'] : 'MAIL_EVENT';
        $office = isset($payload['office']) ? $payload['office'] : null;
        $tipo = isset($payload['tipo']) ? $payload['tipo'] : null;
        $recipients = $this->normalizeRecipients(isset($payload['to']) ? $payload['to'] : '');
        $to = implode(';', $recipients);
        $subject = isset($payload['subject']) ? (string)$payload['subject'] : '';
        $message = isset($payload['message']) ? (string)$payload['message'] : '';
        $html = isset($payload['html']) ? (string)$payload['html'] : $message;
        $text = isset($payload['text']) ? (string)$payload['text'] : strip_tags($message);
        $created_by = isset($payload['created_by']) ? (string)$payload['created_by'] : null;
        $ip = isset($payload['ip']) ? (string)$payload['ip'] : null;
        $skip_reason = isset($payload['skip_reason']) ? trim((string)$payload['skip_reason']) : '';
        $mail_sender = null;
        if (isset($payload['mail_sender']) && is_callable($payload['mail_sender'])) {
            $mail_sender = $payload['mail_sender'];
        } elseif (is_callable($this->mail_sender)) {
            $mail_sender = $this->mail_sender;
        }

        $status = 'skipped';
        $status_reason = '';
        $mail_error = '';
        $friendly = '';
        $attempts = 0;
        $sent_at = null;

        if ($mode !== 'send_and_log') {
            $status = 'skipped';
            $status_reason = ($skip_reason !== '') ? $skip_reason : 'mensajeria_desactivada';
        } elseif (empty($recipients)) {
            $status = 'skipped';
            $status_reason = 'sin_destinatarios';
            $friendly = 'La evaluación se guardó correctamente, pero no se encontró un correo destino.';
        } elseif (!is_callable($mail_sender)) {
            $status = 'error';
            $status_reason = 'transportador_no_configurado';
            $mail_error = 'No hay transportador de correo configurado.';
            $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
            $attempts = 1;
        } else {
            $attempts = 1;
            try {
                $sent = call_user_func($mail_sender, array(
                    'to' => $to,
                    'subject' => $subject,
                    'message' => $message,
                    'html' => $html,
                    'text' => $text,
                ));
                if ($sent) {
                    $status = 'sent';
                    $status_reason = 'enviado';
                    $sent_at = date('Y-m-d H:i:s');
                } else {
                    $status = 'error';
                    $status_reason = 'envio_fallido';
                    $mail_error = 'El transportador de correo devolvió false.';
                    $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
                }
            } catch (Throwable $e) {
                $status = 'error';
                $status_reason = 'excepcion_envio';
                $mail_error = $e->getMessage();
                $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
            }
        }

        $detail = array(
            'event_code' => $event_code,
            'mode' => $mode,
            'status' => $status,
            'status_reason' => $status_reason,
            'to' => $to,
            'subject' => $subject,
            'error' => $mail_error,
            'html' => $html,
            'text' => $text,
        );

        $log = $this->logger->log($id_respuesta, $event_code, $office, $tipo, $detail, $created_by, $ip);
        if (!$log['ok']) {
            return array(
                'ok' => false,
                'error_code' => 'log_failed',
                'error_message' => isset($log['error_message']) ? $log['error_message'] : 'No se pudo registrar el evento de notificación.',
                'mail_status' => $status,
                'friendly_message' => $friendly,
            );
        }

        $this->logOutbox(array(
            'id_respuesta' => $id_respuesta,
            'event_code' => $event_code,
            'office' => $office,
            'tipo' => $tipo,
            'destinatarios' => $to,
            'asunto' => $subject,
            'cuerpo_html' => $html,
            'cuerpo_texto' => $text,
            'estado' => $this->mapOutboxStatus($status),
            'motivo' => $status_reason,
            'no_enviado_motivo' => ($status === 'sent') ? '' : $status_reason,
            'error_detalle' => $mail_error,
            'intentos' => $attempts,
            'enviado_en' => $sent_at,
            'created_by' => $created_by,
            'ip' => $ip,
            'origen' => 'evaluacion_v1',
        ));

        return array(
            'ok' => true,
            'event_id' => isset($log['event_id']) ? (int)$log['event_id'] : 0,
            'mail_status' => $status,
            'friendly_message' => $friendly,
        );
    }

    private function mapOutboxStatus($status)
    {
        if ($status === 'sent') {
            return 'enviado';
        }
        if ($status === 'error') {
            return 'error';
        }
        return 'no_enviado';
    }

    private function normalizeRecipients($raw)
    {
        $list = array();

        if (is_array($raw)) {
            foreach ($raw as $it) {
                $v = trim((string)$it);
                if ($v !== '') {
                    $list[$v] = true;
                }
            }
            return array_keys($list);
        }

        $raw = trim((string)$raw);
        if ($raw === '') {
            return array();
        }

        $parts = preg_split('/[;,]+/', $raw);
        if (!is_array($parts)) {
            return array();
        }
        foreach ($parts as $p) {
            $v = trim((string)$p);
            if ($v !== '') {
                $list[$v] = true;
            }
        }
        return array_keys($list);
    }

    private function logOutbox(array $row)
    {
        if (!($this->outbox instanceof RSUEvaluacionV1MailOutboxService)) {
            return;
        }
        try {
            $this->outbox->log($row);
        } catch (Throwable $e) {
            // No bloquear flujo funcional por falla de auditoría extendida.
        }
    }
}

