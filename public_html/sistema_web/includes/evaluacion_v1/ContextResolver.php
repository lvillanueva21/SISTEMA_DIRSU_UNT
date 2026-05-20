<?php
/**
 * Resolver de contexto de evaluación.
 * Reglas:
 * 1) id_respuesta explícita (si llega) y válida.
 * 2) Semestre/período explícito.
 * 3) Ruta activa en eva_* (situación en_oficina).
 * 4) Última respuesta semestral vigente.
 */

class RSUEvaluacionV1ContextResolver
{
    /** @var mysqli */
    private $db;

    public function __construct($db)
    {
        $this->db = $db;
    }

    public function resolve(array $in)
    {
        $id_py = isset($in['id_py']) ? (int)$in['id_py'] : 0;
        $id_respuesta = isset($in['id_respuesta']) ? (int)$in['id_respuesta'] : 0;
        $id_periodo = 0;
        if (isset($in['semestral'])) {
            $id_periodo = (int)$in['semestral'];
        } elseif (isset($in['periodo'])) {
            $id_periodo = (int)$in['periodo'];
        }

        if ($id_py <= 0) {
            return array(
                'ok' => false,
                'error_code' => 'missing_project',
                'error_message' => 'Falta id_py válido.',
            );
        }

        if ($id_respuesta > 0) {
            $ctx = $this->resolveByResponseId($id_py, $id_respuesta, $id_periodo);
            if ($ctx['ok']) {
                $ctx['resolution_path'] = 'id_respuesta_explicit';
            }
            return $ctx;
        }

        if ($id_periodo > 0) {
            $id_resuelta = $this->findByPeriodo($id_py, $id_periodo);
            if ($id_resuelta > 0) {
                return array(
                    'ok' => true,
                    'id_py' => $id_py,
                    'id_respuesta' => $id_resuelta,
                    'id_periodo' => $id_periodo,
                    'resolution_path' => 'periodo_match',
                );
            }

            return array(
                'ok' => false,
                'error_code' => 'periodo_without_response',
                'error_message' => 'No existe informe semestral para el período seleccionado.',
            );
        }

        $id_activa = $this->findByActiveRoute($id_py);
        if ($id_activa > 0) {
            return array(
                'ok' => true,
                'id_py' => $id_py,
                'id_respuesta' => $id_activa,
                'id_periodo' => $id_periodo,
                'resolution_path' => 'active_route',
            );
        }

        $id_ultima = $this->findLatestSemestralVigente($id_py);
        if ($id_ultima > 0) {
            return array(
                'ok' => true,
                'id_py' => $id_py,
                'id_respuesta' => $id_ultima,
                'id_periodo' => $id_periodo,
                'resolution_path' => 'fallback_latest_semestral',
            );
        }

        $id_legacy = $this->findLatestAnyResponse($id_py);
        if ($id_legacy > 0) {
            return array(
                'ok' => true,
                'id_py' => $id_py,
                'id_respuesta' => $id_legacy,
                'id_periodo' => $id_periodo,
                'resolution_path' => 'fallback_latest_any_response',
            );
        }

        return array(
            'ok' => false,
            'error_code' => 'respuesta_not_found',
            'error_message' => 'No existe respuesta semestral vigente para el proyecto.',
        );
    }

    private function resolveByResponseId($id_py, $id_respuesta, $id_periodo)
    {
        $sql = "SELECT id, id_py FROM sm_respuestas WHERE id = ? LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return array('ok' => false, 'error_code' => 'db_prepare', 'error_message' => 'No se pudo validar la respuesta.');
        }
        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            return array('ok' => false, 'error_code' => 'db_execute', 'error_message' => 'No se pudo validar la respuesta.');
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();

        if (!$row) {
            return array('ok' => false, 'error_code' => 'respuesta_missing', 'error_message' => 'La respuesta no existe.');
        }
        if ((int)$row['id_py'] !== (int)$id_py) {
            return array('ok' => false, 'error_code' => 'respuesta_project_mismatch', 'error_message' => 'La respuesta no pertenece al proyecto.');
        }

        if ($id_periodo > 0) {
            if (!$this->validatePeriodoMatch($id_respuesta, $id_py, $id_periodo)) {
                if ($this->allowLegacyPeriodoBypass($id_respuesta, $id_py)) {
                    return array(
                        'ok' => true,
                        'id_py' => $id_py,
                        'id_respuesta' => $id_respuesta,
                        'id_periodo' => $id_periodo,
                        'legacy_period_bypass' => true,
                    );
                }
                return array(
                    'ok' => false,
                    'error_code' => 'periodo_mismatch',
                    'error_message' => 'La respuesta no corresponde al período semestral seleccionado.',
                );
            }
        }

        return array(
            'ok' => true,
            'id_py' => $id_py,
            'id_respuesta' => $id_respuesta,
            'id_periodo' => $id_periodo,
        );
    }

    private function validatePeriodoMatch($id_respuesta, $id_py, $id_periodo)
    {
        $periodoMatchExpr = "CONCAT(CAST(s.anio AS CHAR CHARACTER SET utf8mb4), '-', CAST(s.periodo AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_unicode_ci";
        $sql = "SELECT r.id
                FROM sm_respuestas r
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                   AND s.tipo = 'semestral'
                   AND COALESCE(s.vigente, 1) = 1
                INNER JOIN periodos prf
                    ON prf.id = ?
                WHERE r.id = ?
                  AND r.id_py = ?
                  AND prf.nombre COLLATE utf8mb4_unicode_ci = $periodoMatchExpr
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return false;
        }
        $st->bind_param('iii', $id_periodo, $id_respuesta, $id_py);
        $ok = $st->execute();
        if (!$ok) {
            $st->close();
            return false;
        }
        $res = $st->get_result();
        $valid = ($res instanceof mysqli_result && $res->num_rows > 0);
        $st->close();
        return $valid;
    }

    private function findByPeriodo($id_py, $id_periodo)
    {
        $id = $this->findByPeriodoStrict($id_py, $id_periodo);
        if ($id > 0) {
            return $id;
        }
        return $this->findByPeriodoLegacy($id_py, $id_periodo);
    }

    private function findByPeriodoStrict($id_py, $id_periodo)
    {
        $periodoMatchExpr = "CONCAT(CAST(s.anio AS CHAR CHARACTER SET utf8mb4), '-', CAST(s.periodo AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_unicode_ci";
        $sql = "SELECT r.id
                FROM sm_respuestas r
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                   AND s.tipo = 'semestral'
                   AND COALESCE(s.vigente, 1) = 1
                INNER JOIN periodos prf
                    ON prf.id = ?
                WHERE r.id_py = ?
                  AND prf.nombre COLLATE utf8mb4_unicode_ci = $periodoMatchExpr
                ORDER BY r.actualizado_at DESC, r.id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return 0;
        $st->bind_param('ii', $id_periodo, $id_py);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ? (int)$row['id'] : 0;
    }

    private function findByPeriodoLegacy($id_py, $id_periodo)
    {
        $periodoMatchExpr = "CONCAT(CAST(s.anio AS CHAR CHARACTER SET utf8mb4), '-', CAST(s.periodo AS CHAR CHARACTER SET utf8mb4)) COLLATE utf8mb4_unicode_ci";
        $sql = "SELECT r.id
                FROM sm_respuestas r
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                INNER JOIN periodos prf
                    ON prf.id = ?
                WHERE r.id_py = ?
                  AND prf.nombre COLLATE utf8mb4_unicode_ci = $periodoMatchExpr
                ORDER BY r.actualizado_at DESC, r.id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return 0;
        $st->bind_param('ii', $id_periodo, $id_py);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ? (int)$row['id'] : 0;
    }

    private function findByActiveRoute($id_py)
    {
        $sql = "SELECT r.id
                FROM sm_respuestas r
                INNER JOIN eva_evaluaciones e
                    ON e.id_respuesta = r.id
                   AND e.situacion = 'en_oficina'
                WHERE r.id_py = ?
                ORDER BY e.actualizado_at DESC, r.actualizado_at DESC, r.id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return 0;
        }
        $st->bind_param('i', $id_py);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ? (int)$row['id'] : 0;
    }

    private function findLatestSemestralVigente($id_py)
    {
        $sql = "SELECT r.id
                FROM sm_respuestas r
                INNER JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                   AND s.tipo = 'semestral'
                   AND COALESCE(s.vigente, 1) = 1
                WHERE r.id_py = ?
                ORDER BY s.anio DESC,
                         FIELD(s.periodo, 'I', 'II') DESC,
                         r.actualizado_at DESC,
                         r.id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return 0;
        $st->bind_param('i', $id_py);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ? (int)$row['id'] : 0;
    }

    public function findLatestAnyResponse($id_py)
    {
        $sql = "SELECT id
                FROM sm_respuestas
                WHERE id_py = ?
                ORDER BY actualizado_at DESC, id DESC
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) return 0;
        $st->bind_param('i', $id_py);
        if (!$st->execute()) {
            $st->close();
            return 0;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        return $row ? (int)$row['id'] : 0;
    }

    private function allowLegacyPeriodoBypass($id_respuesta, $id_py)
    {
        $sql = "SELECT
                    r.id,
                    r.id_py,
                    r.id_semestre,
                    s.id AS sem_id,
                    s.anio,
                    s.periodo
                FROM sm_respuestas r
                LEFT JOIN sm_proyecto_semestres s
                    ON s.id = r.id_semestre
                WHERE r.id = ?
                  AND r.id_py = ?
                LIMIT 1";
        $st = $this->db->prepare($sql);
        if (!$st) {
            return false;
        }
        $st->bind_param('ii', $id_respuesta, $id_py);
        if (!$st->execute()) {
            $st->close();
            return false;
        }
        $res = $st->get_result();
        $row = ($res instanceof mysqli_result && $res->num_rows > 0) ? $res->fetch_assoc() : null;
        $st->close();
        if (!$row) {
            return false;
        }
        if (!isset($row['sem_id']) || $row['sem_id'] === null) {
            return true;
        }
        $periodo = isset($row['periodo']) ? (string)$row['periodo'] : '';
        $anio = isset($row['anio']) ? (int)$row['anio'] : 0;
        if ($anio <= 0) {
            return true;
        }
        if ($periodo !== 'I' && $periodo !== 'II') {
            return true;
        }
        return false;
    }
}
