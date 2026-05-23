<?php
/**
 * Resuelve tipo de informe para mensajeria de evaluacion.
 * Fuente primaria: sm_respuestas.id_semestre -> sm_proyecto_semestres.final (tipo='semestral').
 */

class RSUEvaluacionV1ReportTypeResolverService
{
    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function resolveByRespuesta($id_respuesta)
    {
        $id_respuesta = (int)$id_respuesta;
        if ($id_respuesta <= 0) {
            return array(
                'ok' => false,
                'reason' => 'id_respuesta_invalido',
                'message' => 'No se pudo determinar el tipo de informe: id_respuesta invalido.',
            );
        }

        $sql = "SELECT
                    r.id AS id_respuesta,
                    r.id_py,
                    r.id_semestre,
                    s.id AS sm_semestre_id,
                    s.tipo AS sm_tipo,
                    COALESCE(s.final, 0) AS sm_final,
                    s.anio,
                    s.periodo
                FROM sm_respuestas r
                LEFT JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                WHERE r.id = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return array(
                'ok' => false,
                'reason' => 'db_prepare',
                'message' => 'No se pudo determinar el tipo de informe (prepare).',
            );
        }

        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            return array(
                'ok' => false,
                'reason' => 'db_execute',
                'message' => 'No se pudo determinar el tipo de informe (execute).',
            );
        }

        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();

        if (!$row) {
            return array(
                'ok' => false,
                'reason' => 'respuesta_no_encontrada',
                'message' => 'No se pudo determinar el tipo de informe: respuesta no encontrada.',
            );
        }

        $id_semestre = isset($row['id_semestre']) ? (int)$row['id_semestre'] : 0;
        if ($id_semestre <= 0) {
            return array(
                'ok' => false,
                'reason' => 'respuesta_sin_semestre',
                'message' => 'No se pudo determinar el tipo de informe: la respuesta no tiene semestre asociado.',
            );
        }

        $sm_semestre_id = isset($row['sm_semestre_id']) ? (int)$row['sm_semestre_id'] : 0;
        if ($sm_semestre_id <= 0) {
            return array(
                'ok' => false,
                'reason' => 'semestre_no_encontrado',
                'message' => 'No se pudo determinar el tipo de informe: semestre del proyecto no encontrado.',
            );
        }

        $sm_tipo = trim((string)($row['sm_tipo'] ?? ''));
        if ($sm_tipo !== 'semestral') {
            return array(
                'ok' => false,
                'reason' => 'semestre_tipo_invalido',
                'message' => 'No se pudo determinar el tipo de informe: el semestre asociado no es de tipo semestral.',
            );
        }

        $es_final = ((int)($row['sm_final'] ?? 0) === 1);
        $tipo_informe = $es_final ? 'final' : 'semestral';
        $label_title = $es_final ? 'Informe final' : 'Informe semestral';
        $label_lower = $es_final ? 'informe final' : 'informe semestral';
        $anio = isset($row['anio']) ? (int)$row['anio'] : 0;
        $periodo = trim((string)($row['periodo'] ?? ''));
        $periodo_label = ($anio > 0 && $periodo !== '') ? ($anio . '-' . $periodo) : '';

        return array(
            'ok' => true,
            'id_respuesta' => (int)$row['id_respuesta'],
            'id_py' => isset($row['id_py']) ? (int)$row['id_py'] : 0,
            'id_semestre' => $id_semestre,
            'tipo_informe' => $tipo_informe,
            'es_final' => $es_final,
            'label_title' => $label_title,
            'label_lower' => $label_lower,
            'periodo_label' => $periodo_label,
            'source' => 'sm_respuestas.id_semestre->sm_proyecto_semestres.final',
        );
    }
}
