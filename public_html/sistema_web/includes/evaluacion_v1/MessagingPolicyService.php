<?php
/**
 * Política de mensajería para evaluación.
 * - send_and_log: envía correo y audita.
 * - log_only: solo audita (sin envío real).
 *
 * Fuente de estado:
 * - evt_eventos.codigo = 'evaluacion_mensajeria'
 * - estado=1 => send_and_log
 * - estado=0 => log_only
 * - fallback por defecto => send_and_log
 */

class RSUEvaluacionV1MessagingPolicyService
{
    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function getMode()
    {
        static $cache = null;
        if (is_string($cache) && $cache !== '') {
            return $cache;
        }

        $mode = 'send_and_log';
        $sql = "SELECT estado
                FROM evt_eventos
                WHERE codigo = 'evaluacion_mensajeria'
                LIMIT 1";
        $rs = @mysqli_query($this->db, $sql);
        if ($rs instanceof mysqli_result) {
            $row = mysqli_fetch_assoc($rs);
            mysqli_free_result($rs);
            if ($row && isset($row['estado'])) {
                $mode = ((int)$row['estado'] === 1) ? 'send_and_log' : 'log_only';
            }
        }

        $cache = $mode;
        return $mode;
    }

    public function getModeForEventCode($eventCode)
    {
        $globalMode = $this->getMode();
        if ($globalMode !== 'send_and_log') {
            return 'log_only';
        }

        $eventCode = strtoupper(trim((string)$eventCode));
        if ($eventCode === '') {
            return $globalMode;
        }

        $toggleCode = $this->toggleCodeForEventCode($eventCode);
        if ($toggleCode === '') {
            return $globalMode;
        }

        $sql = "SELECT estado FROM evt_eventos WHERE codigo = ? LIMIT 1";
        $st = @mysqli_prepare($this->db, $sql);
        if (!$st) {
            return $globalMode;
        }
        mysqli_stmt_bind_param($st, 's', $toggleCode);
        $ok = @mysqli_stmt_execute($st);
        if (!$ok) {
            mysqli_stmt_close($st);
            return $globalMode;
        }
        $rs = mysqli_stmt_get_result($st);
        $row = ($rs instanceof mysqli_result) ? mysqli_fetch_assoc($rs) : null;
        mysqli_stmt_close($st);

        if (!$row || !isset($row['estado'])) {
            return $globalMode;
        }

        return ((int)$row['estado'] === 1) ? 'send_and_log' : 'log_only';
    }

    private function toggleCodeForEventCode($eventCode)
    {
        $map = array(
            'MAIL_DERIVACION' => 'evaluacion_mail_derivacion',
            'MAIL_OBSERVACION' => 'evaluacion_mail_observacion',
            'MAIL_APROB_TOTAL' => 'evaluacion_mail_aprob_total',
            'MAIL_SOLICITUD_REVISION' => 'evaluacion_mail_solicitud_revision',
            'MAIL_SUBSANACION' => 'evaluacion_mail_subsanacion',
        );

        return isset($map[$eventCode]) ? $map[$eventCode] : '';
    }
}
