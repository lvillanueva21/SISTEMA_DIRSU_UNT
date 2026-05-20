<?php
/**
 * Registro de eventos del motor V1.
 * En Paso 01 usa estructura actual de ev_eventos.
 */

class RSUEvaluacionV1EventLoggerService
{
    /** @var mysqli */
    private $db;
    private $detalleMaxLen = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function log($id_respuesta, $event_code, $office = null, $tipo = null, $detalle = null, $created_by = null, $ip = null)
    {
        $id_respuesta = (int)$id_respuesta;
        $event_code = strtoupper(trim((string)$event_code));
        $office = ($office === null || $office === '') ? null : (int)$office;
        $tipo = ($tipo === null || $tipo === '') ? null : (int)$tipo;
        if (is_array($detalle) || is_object($detalle)) {
            $detalle = json_encode($detalle, JSON_UNESCAPED_UNICODE);
        }
        $detalle = ($detalle === null) ? null : (string)$detalle;
        $created_by = ($created_by === null) ? null : (string)$created_by;
        $ip = ($ip === null) ? null : (string)$ip;

        if ($event_code !== '') {
            $event_code = strtoupper(substr($event_code, 0, 40));
        }
        if ($detalle !== null) {
            $maxLen = $this->getDetalleMaxLength();
            if ($maxLen > 0) {
                $detalle = substr($detalle, 0, $maxLen);
            }
        }
        if ($created_by !== null) {
            $created_by = substr($created_by, 0, 32);
        }
        if ($ip !== null) {
            $ip = substr($ip, 0, 45);
        }

        if ($id_respuesta <= 0 || $event_code === '') {
            return array('ok' => false, 'error_code' => 'invalid_event_payload');
        }

        $sql = "INSERT INTO ev_eventos
                (id_respuesta, event_code, office, tipo, detalle, created_by, ip)
                VALUES (?, ?, ?, ?, ?, ?, ?)";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return array('ok' => false, 'error_code' => 'db_prepare', 'error_message' => 'No se pudo preparar el evento.');
        }

        $st->bind_param(
            'isiisss',
            $id_respuesta,
            $event_code,
            $office,
            $tipo,
            $detalle,
            $created_by,
            $ip
        );
        $ok = $st->execute();
        if (!$ok) {
            $err = $st->error;
            $st->close();
            return array('ok' => false, 'error_code' => 'db_execute', 'error_message' => $err);
        }

        $event_id = (int)$st->insert_id;
        $st->close();
        return array('ok' => true, 'event_id' => $event_id);
    }

    private function getDetalleMaxLength()
    {
        if ($this->detalleMaxLen !== null) {
            return (int)$this->detalleMaxLen;
        }

        $this->detalleMaxLen = 500;
        $sql = "SELECT DATA_TYPE, CHARACTER_MAXIMUM_LENGTH
                  FROM information_schema.COLUMNS
                 WHERE TABLE_SCHEMA = DATABASE()
                   AND TABLE_NAME = 'ev_eventos'
                   AND COLUMN_NAME = 'detalle'
                 LIMIT 1";
        $rs = @mysqli_query($this->db, $sql);
        if (!($rs instanceof mysqli_result)) {
            return (int)$this->detalleMaxLen;
        }
        $row = mysqli_fetch_assoc($rs);
        mysqli_free_result($rs);
        if (!$row) {
            return (int)$this->detalleMaxLen;
        }

        $type = strtolower(trim((string)($row['DATA_TYPE'] ?? '')));
        $len = isset($row['CHARACTER_MAXIMUM_LENGTH']) ? (int)$row['CHARACTER_MAXIMUM_LENGTH'] : 0;
        if (in_array($type, array('text', 'mediumtext', 'longtext'), true)) {
            $this->detalleMaxLen = 0;
        } elseif ($len > 0) {
            $this->detalleMaxLen = $len;
        }

        return (int)$this->detalleMaxLen;
    }
}
