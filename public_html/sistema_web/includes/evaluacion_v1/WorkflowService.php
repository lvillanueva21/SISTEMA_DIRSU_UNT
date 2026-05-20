<?php
/**
 * Utilidades de flujo por oficinas.
 */

class RSUEvaluacionV1WorkflowService
{
    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function listOffices()
    {
        $out = array();
        $sql = "SELECT id, codigo, nombre, orden
                FROM eva_oficinas
                WHERE activo = 1
                ORDER BY orden ASC";
        $rs = $this->db->query($sql);
        if ($rs instanceof mysqli_result) {
            while ($row = $rs->fetch_assoc()) {
                $out[] = array(
                    'id' => isset($row['id']) ? (int)$row['id'] : 0,
                    'codigo' => isset($row['codigo']) ? (string)$row['codigo'] : '',
                    'nombre' => isset($row['nombre']) ? (string)$row['nombre'] : '',
                    'orden' => isset($row['orden']) ? (int)$row['orden'] : 0,
                );
            }
            $rs->free();
        }
        return $out;
    }

    public function getOfficeByCode($office_code)
    {
        $office_code = strtoupper(trim((string)$office_code));
        if ($office_code === '') {
            return null;
        }
        $sql = "SELECT id, codigo, nombre, orden
                FROM eva_oficinas
                WHERE codigo = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return null;
        }
        $st->bind_param('s', $office_code);
        if (!$st->execute()) {
            $st->close();
            return null;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ?: null;
    }

    public function getNextOfficeId($current_office_id)
    {
        $current_office_id = (int)$current_office_id;
        if ($current_office_id <= 0) {
            return null;
        }
        $offices = $this->listOffices();
        $count = count($offices);
        for ($i = 0; $i < $count; $i++) {
            if ((int)$offices[$i]['id'] === $current_office_id) {
                if ($i + 1 < $count) {
                    return (int)$offices[$i + 1]['id'];
                }
                return null;
            }
        }
        return null;
    }

    public function targetSituacionByNextOffice($next_office_id)
    {
        return ($next_office_id === null) ? 'aprobado' : 'en_oficina';
    }
}
