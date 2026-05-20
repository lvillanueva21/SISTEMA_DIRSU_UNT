<?php
// /sistema_web/evaluacion/api/save_evaluacion.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/../../componentes/configSesion.php';
require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../../includes/evaluacion_v1/bootstrap.php';

function rsu_eval_v1_actor($conexion)
{
    $id_rol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
    $usuario = isset($_SESSION['usuario']) ? (string)$_SESSION['usuario'] : '';
    $id_escuela = isset($_SESSION['id_escuela']) ? (int)$_SESSION['id_escuela'] : 0;
    $id_depa = isset($_SESSION['id_depa']) ? (int)$_SESSION['id_depa'] : 0;
    $rol_nombre = 'Rol no identificado';

    if ($conexion instanceof mysqli) {
        $sql = "SELECT nombre FROM rol WHERE id = ? LIMIT 1";
        if ($st = $conexion->prepare($sql)) {
            $st->bind_param('i', $id_rol);
            if ($st->execute()) {
                $res = $st->get_result();
                if ($res instanceof mysqli_result && $res->num_rows > 0) {
                    $row = $res->fetch_assoc();
                    $rol_nombre = isset($row['nombre']) ? (string)$row['nombre'] : $rol_nombre;
                }
            }
            $st->close();
        }
    }

    return array(
        'rol' => $rol_nombre,
        'usuario' => $usuario,
        'id_rol' => $id_rol,
        'id_escuela' => $id_escuela,
        'id_depa' => $id_depa,
    );
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(array('ok' => false, 'error' => 'Método no permitido'));
        exit;
    }

    if (!($conexion instanceof mysqli)) {
        http_response_code(500);
        echo json_encode(array('ok' => false, 'error' => 'No hay conexión a base de datos.'));
        exit;
    }

    $id_py = isset($_POST['id_py']) ? (int)$_POST['id_py'] : 0;
    $id_respuesta = isset($_POST['id_respuesta']) ? (int)$_POST['id_respuesta'] : 0;
    $id_periodo = isset($_POST['semestral']) ? (int)$_POST['semestral'] : (isset($_POST['periodo']) ? (int)$_POST['periodo'] : 0);
    $accion = isset($_POST['accion']) ? trim((string)$_POST['accion']) : '';
    $oficina = isset($_POST['oficina']) ? trim((string)$_POST['oficina']) : '';

    if ($id_py <= 0 || $accion === '' || $oficina === '') {
        echo json_encode(array('ok' => false, 'error' => 'Parámetros incompletos'));
        exit;
    }

    $engine = rsu_eval_v1_engine($conexion);
    if (!$engine) {
        http_response_code(500);
        echo json_encode(array('ok' => false, 'error' => 'No se pudo inicializar el motor de evaluación.'));
        exit;
    }

    $ctx = $engine->resolveContext(array(
        'id_py' => $id_py,
        'id_respuesta' => $id_respuesta,
        'semestral' => $id_periodo,
        'periodo' => $id_periodo,
    ));
    if (empty($ctx['ok'])) {
        $msg = isset($ctx['error_message']) ? (string)$ctx['error_message'] : 'No se pudo resolver el contexto de evaluación.';
        echo json_encode(array('ok' => false, 'error' => $msg));
        exit;
    }

    $id_respuesta_ctx = isset($ctx['id_respuesta']) ? (int)$ctx['id_respuesta'] : 0;
    if ($id_respuesta_ctx <= 0) {
        echo json_encode(array('ok' => false, 'error' => 'No se encontró respuesta válida para evaluar.'));
        exit;
    }

    $usr = rsu_eval_v1_actor($conexion);
    $perm = $engine->authorizeEvaluation((int)$usr['id_rol'], $accion, $oficina, $id_respuesta_ctx);
    if (empty($perm['ok'])) {
        $why = isset($perm['why']) ? (string)$perm['why'] : 'No autorizado';
        echo json_encode(array('ok' => false, 'error' => $why));
        exit;
    }

    $state = isset($perm['state']) && is_array($perm['state']) ? $perm['state'] : array();
    $state_office = isset($state['oficina_cod']) ? strtoupper((string)$state['oficina_cod']) : '';
    $office_req = strtoupper($oficina);
    if ((int)$usr['id_rol'] !== 0 && $state_office !== '' && $office_req !== $state_office) {
        echo json_encode(array('ok' => false, 'error' => 'La oficina enviada no coincide con la oficina actual del expediente.'));
        exit;
    }

    $val = $engine->normalizeLegacyInput($accion, $_POST);
    $res = $engine->dispatchLegacyEvaluation($oficina, $id_py, $id_respuesta_ctx, $accion, $val, $usr);
    if (is_array($res) && !empty($res['ok'])) {
        $warnings = rsu_eval_v1_notification_get_warnings(true);
        if (!empty($warnings)) {
            $res['warning_message'] = 'La evaluación se guardó correctamente, pero no se pudo enviar uno o más correos.';
            $res['warnings'] = array_values($warnings);
        }
    }
    echo json_encode($res);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(array('ok' => false, 'error' => 'Excepción: ' . $e->getMessage()));
}
