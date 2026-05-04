<?php
// /sistema_web/informe_semestral/api/save_evaluacion.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../componentes/configSesion.php';
require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../funciones.php'; // testeo() + control_oficinas.php (puedeClickearAccion)
require_once __DIR__ . '/../core/ValidacionService.php';
require_once __DIR__ . '/../handlers/PCFHandler.php';
require_once __DIR__ . '/../handlers/DDHandler.php';
require_once __DIR__ . '/../handlers/DFHandler.php';
require_once __DIR__ . '/../handlers/RSUHandler.php';

use EvalV4\ValidacionService;
use EvalV4\PCFHandler;
use EvalV4\DDHandler;
use EvalV4\DFHandler;
use EvalV4\RSUHandler;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok' => false, 'error' => 'Método no permitido']);
        exit;
    }

    $usr = testeo();
    $id_rol = (int)$usr['id_rol'];

    // Payload base
    $id_py = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
    $id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
    $id_periodo = isset($_POST['semestral']) ? (int)$_POST['semestral'] : (isset($_POST['periodo']) ? (int)$_POST['periodo'] : 0);
    $accion = isset($_POST['accion']) ? trim((string)$_POST['accion']) : '';
    $oficina = isset($_POST['oficina']) ? trim((string)$_POST['oficina']) : '';

    if ($id_py <= 0 || $accion === '' || $oficina === '') {
        echo json_encode(['ok' => false, 'error' => 'Parámetros incompletos']);
        exit;
    }

    $periodoMatchExpr = " CONCAT(
        CAST(s.anio AS CHAR CHARACTER SET utf8mb4),
        '-',
        CAST(s.periodo AS CHAR CHARACTER SET utf8mb4)
      ) COLLATE utf8mb4_unicode_ci ";

    // Resolver/validar respuesta en contexto del periodo seleccionado
    if ($id_respuesta > 0) {
        $sqlResp = "SELECT id_py FROM sm_respuestas WHERE id = ? LIMIT 1";
        if (!($st = $conexion->prepare($sqlResp))) {
            echo json_encode(['ok' => false, 'error' => 'No se pudo validar la respuesta']);
            exit;
        }
        $st->bind_param('i', $id_respuesta);
        if (!$st->execute()) {
            $st->close();
            echo json_encode(['ok' => false, 'error' => 'No se pudo validar la respuesta']);
            exit;
        }
        $res = $st->get_result();
        $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
        $st->close();
        if (!$row) {
            echo json_encode(['ok' => false, 'error' => 'La respuesta seleccionada no existe']);
            exit;
        }
        if ((int)$row['id_py'] !== $id_py) {
            echo json_encode(['ok' => false, 'error' => 'La respuesta no pertenece al proyecto seleccionado']);
            exit;
        }

        if ($id_periodo > 0) {
            $sqlCtx = "SELECT r.id
                       FROM sm_respuestas r
                       JOIN sm_proyecto_semestres s
                         ON s.id = r.id_semestre
                        AND s.tipo = 'semestral'
                        AND COALESCE(s.vigente, 1) = 1
                       JOIN periodos prf ON prf.id = ?
                       WHERE r.id = ?
                         AND r.id_py = ?
                         AND prf.nombre COLLATE utf8mb4_unicode_ci = $periodoMatchExpr
                       LIMIT 1";
            if (!($st = $conexion->prepare($sqlCtx))) {
                echo json_encode(['ok' => false, 'error' => 'No se pudo validar el contexto semestral']);
                exit;
            }
            $st->bind_param('iii', $id_periodo, $id_respuesta, $id_py);
            if (!$st->execute()) {
                $st->close();
                echo json_encode(['ok' => false, 'error' => 'No se pudo validar el contexto semestral']);
                exit;
            }
            $res = $st->get_result();
            $okCtx = ($res && $res->num_rows > 0);
            $st->close();
            if (!$okCtx) {
                echo json_encode(['ok' => false, 'error' => 'La respuesta no corresponde al periodo semestral seleccionado']);
                exit;
            }
        }
    } else {
        // Compatibilidad: si no llega id_respuesta, resolver por proyecto (y periodo si aplica)
        if ($id_periodo > 0) {
            $sqlFind = "SELECT r.id
                        FROM sm_respuestas r
                        JOIN sm_proyecto_semestres s
                          ON s.id = r.id_semestre
                         AND s.tipo = 'semestral'
                         AND COALESCE(s.vigente, 1) = 1
                        JOIN periodos prf ON prf.id = ?
                        WHERE r.id_py = ?
                          AND prf.nombre COLLATE utf8mb4_unicode_ci = $periodoMatchExpr
                        ORDER BY r.actualizado_at DESC, r.id DESC
                        LIMIT 1";
            if ($st = $conexion->prepare($sqlFind)) {
                $st->bind_param('ii', $id_periodo, $id_py);
                if ($st->execute()) {
                    $res = $st->get_result();
                    if ($res && ($row = $res->fetch_assoc())) {
                        $id_respuesta = (int)$row['id'];
                    }
                }
                $st->close();
            }
        } else {
            $sqlFind = "SELECT id FROM sm_respuestas WHERE id_py = ? ORDER BY actualizado_at DESC, id DESC LIMIT 1";
            if ($st = $conexion->prepare($sqlFind)) {
                $st->bind_param('i', $id_py);
                if ($st->execute()) {
                    $res = $st->get_result();
                    if ($res && ($row = $res->fetch_assoc())) {
                        $id_respuesta = (int)$row['id'];
                    }
                }
                $st->close();
            }
        }

        if ($id_respuesta <= 0) {
            echo json_encode(['ok' => false, 'error' => 'No existe informe semestral para el periodo seleccionado']);
            exit;
        }
    }

    // Mismo criterio que la UI para habilitar botón
    $perm = puedeClickearAccion($id_rol, $accion, $id_py, $id_respuesta);
    if (!$perm['enabled'] && $id_rol !== 0) { // Admin (0) puede forzar en pruebas
        echo json_encode(['ok' => false, 'error' => $perm['why'] ?: 'No autorizado']);
        exit;
    }

    // Normaliza/valida según acción
    $val = ValidacionService::normalizar($accion, $_POST);

    // Despacho por oficina
    switch (strtoupper($oficina)) {
        case 'PCF': $handler = new PCFHandler($conexion); break;
        case 'DD':  $handler = new DDHandler($conexion); break;
        case 'DF':  $handler = new DFHandler($conexion); break;
        case 'RSU': $handler = new RSUHandler($conexion); break;
        default:
            echo json_encode(['ok' => false, 'error' => 'Oficina no válida']);
            exit;
    }

    $res = $handler->guardar($id_py, $id_respuesta, strtolower($accion), $val, $usr);
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => 'Excepción: ' . $e->getMessage()]);
}
