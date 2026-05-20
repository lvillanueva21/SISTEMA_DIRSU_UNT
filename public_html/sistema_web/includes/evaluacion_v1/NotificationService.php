<?php
/**
 * Notificaciones del motor V1.
 * Paso 01: base para control central (send_and_log / log_only).
 */

class RSUEvaluacionV1NotificationService
{
    /** @var RSUEvaluacionV1EventLoggerService */
    private $logger;

    /** @var callable|null */
    private $mail_sender;

    public function __construct($logger, $mail_sender = null)
    {
        $this->logger = $logger;
        $this->mail_sender = $mail_sender;
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
        $to = '';
        if (isset($payload['to']) && is_array($payload['to'])) {
            $to = implode(';', array_map('strval', $payload['to']));
        } else {
            $to = isset($payload['to']) ? (string)$payload['to'] : '';
        }
        $subject = isset($payload['subject']) ? (string)$payload['subject'] : '';
        $message = isset($payload['message']) ? (string)$payload['message'] : '';
        $html = isset($payload['html']) ? (string)$payload['html'] : $message;
        $text = isset($payload['text']) ? (string)$payload['text'] : strip_tags($message);
        $created_by = isset($payload['created_by']) ? (string)$payload['created_by'] : null;
        $ip = isset($payload['ip']) ? (string)$payload['ip'] : null;
        $mail_sender = null;
        if (isset($payload['mail_sender']) && is_callable($payload['mail_sender'])) {
            $mail_sender = $payload['mail_sender'];
        } elseif (is_callable($this->mail_sender)) {
            $mail_sender = $this->mail_sender;
        }

        $status = 'skipped';
        $mail_error = '';
        $friendly = '';

        if ($mode === 'send_and_log') {
            if (!is_callable($mail_sender)) {
                $status = 'error';
                $mail_error = 'No hay transportador de correo configurado.';
                $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
            } else {
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
                    } else {
                        $status = 'error';
                        $mail_error = 'El transportador de correo devolvió false.';
                        $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
                    }
                } catch (Throwable $e) {
                    $status = 'error';
                    $mail_error = $e->getMessage();
                    $friendly = 'La evaluación se guardó correctamente, pero no se pudo enviar el correo.';
                }
            }
        }

        $detail = array(
            'event_code' => $event_code,
            'mode' => $mode,
            'status' => $status,
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

        return array(
            'ok' => true,
            'event_id' => isset($log['event_id']) ? (int)$log['event_id'] : 0,
            'mail_status' => $status,
            'friendly_message' => $friendly,
        );
    }
}
