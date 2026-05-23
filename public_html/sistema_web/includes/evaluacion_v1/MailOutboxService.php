<?php
/**
 * Outbox de mensajería para auditoría y reintentos.
 * Tabla esperada: msj_correos_outbox
 */

class RSUEvaluacionV1MailOutboxService
{
    /** @var mysqli */
    private $db;

    /** @var bool|null */
    private $tableExists = null;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function log(array $row)
    {
        if (!$this->hasTable()) {
            return array('ok' => false, 'error_code' => 'table_not_found');
        }

        $id_respuesta = isset($row['id_respuesta']) ? (int)$row['id_respuesta'] : 0;
        $event_code = isset($row['event_code']) ? strtoupper(substr(trim((string)$row['event_code']), 0, 40)) : '';
        $office = isset($row['office']) && $row['office'] !== '' ? (int)$row['office'] : null;
        $tipo = isset($row['tipo']) && $row['tipo'] !== '' ? (int)$row['tipo'] : null;
        $destinatarios = isset($row['destinatarios']) ? (string)$row['destinatarios'] : '';
        $asunto = isset($row['asunto']) ? (string)$row['asunto'] : '';
        $cuerpo_html = isset($row['cuerpo_html']) ? (string)$row['cuerpo_html'] : '';
        $cuerpo_texto = isset($row['cuerpo_texto']) ? (string)$row['cuerpo_texto'] : '';
        $estado = isset($row['estado']) ? strtolower(trim((string)$row['estado'])) : 'no_enviado';
        $motivo = isset($row['motivo']) ? (string)$row['motivo'] : '';
        $no_enviado_motivo = isset($row['no_enviado_motivo']) ? (string)$row['no_enviado_motivo'] : '';
        $error_detalle = isset($row['error_detalle']) ? (string)$row['error_detalle'] : '';
        $intentos = isset($row['intentos']) ? max(0, (int)$row['intentos']) : 0;
        $enviado_en = isset($row['enviado_en']) ? (string)$row['enviado_en'] : null;
        $created_by = isset($row['created_by']) ? (string)$row['created_by'] : null;
        $ip = isset($row['ip']) ? (string)$row['ip'] : null;
        $origen = isset($row['origen']) ? (string)$row['origen'] : 'evaluacion_v1';

        if ($id_respuesta <= 0 || $event_code === '') {
            return array('ok' => false, 'error_code' => 'invalid_payload');
        }
        if (!in_array($estado, array('enviado', 'no_enviado', 'error'), true)) {
            $estado = 'no_enviado';
        }

        $sql = "INSERT INTO msj_correos_outbox
                (id_respuesta, event_code, office, tipo, destinatarios, asunto, cuerpo_html, cuerpo_texto,
                 estado, motivo, no_enviado_motivo, error_detalle, intentos, enviado_en, created_by, ip, origen)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $st = $this->db->prepare($sql);
        if (!$st) {
            return array('ok' => false, 'error_code' => 'db_prepare', 'error_message' => $this->db->error);
        }

        $st->bind_param(
            'isiissssssssissss',
            $id_respuesta,
            $event_code,
            $office,
            $tipo,
            $destinatarios,
            $asunto,
            $cuerpo_html,
            $cuerpo_texto,
            $estado,
            $motivo,
            $no_enviado_motivo,
            $error_detalle,
            $intentos,
            $enviado_en,
            $created_by,
            $ip,
            $origen
        );

        $ok = $st->execute();
        if (!$ok) {
            $err = $st->error;
            $st->close();
            return array('ok' => false, 'error_code' => 'db_execute', 'error_message' => $err);
        }
        $insertId = (int)$st->insert_id;
        $st->close();

        return array('ok' => true, 'id' => $insertId);
    }

    private function hasTable()
    {
        if ($this->tableExists !== null) {
            return (bool)$this->tableExists;
        }

        $this->tableExists = false;
        $rs = @mysqli_query($this->db, "SHOW TABLES LIKE 'msj_correos_outbox'");
        if ($rs instanceof mysqli_result) {
            $this->tableExists = ($rs->num_rows > 0);
            mysqli_free_result($rs);
        }

        return (bool)$this->tableExists;
    }
}

