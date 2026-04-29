<?php
// /sistema_web/informe_semestral/api/save_evaluacion.php
header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', 1); error_reporting(E_ALL);

require_once __DIR__ . '/../../componentes/configSesion.php';
require_once __DIR__ . '/../../includes/db_connection.php';
require_once __DIR__ . '/../funciones.php'; // testeo() + incluye control_oficinas.php (puedeClickearAccion)
require_once __DIR__ . '/../core/ValidacionService.php';
require_once __DIR__ . '/../core/EvaluacionService.php';
require_once __DIR__ . '/../core/RutaService.php';
require_once __DIR__ . '/../handlers/PCFHandler.php';
require_once __DIR__ . '/../handlers/DDHandler.php';
require_once __DIR__ . '/../handlers/DFHandler.php';
require_once __DIR__ . '/../handlers/RSUHandler.php';

use EvalV4\ValidacionService;
use EvalV4\EvaluacionService;
use EvalV4\RutaService;
use EvalV4\PCFHandler;
use EvalV4\DDHandler;
use EvalV4\DFHandler;
use EvalV4\RSUHandler;

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['ok'=>false, 'error'=>'MÃ©todo no permitido']); exit;
    }

    $usr    = testeo();
    $id_rol = (int)$usr['id_rol'];

    // Payload base
    $id_py   = isset($_POST['id_py'])   ? (int)$_POST['id_py']   : 0;
    $accion  = isset($_POST['accion'])  ? trim((string)$_POST['accion']) : '';
    $oficina = isset($_POST['oficina']) ? trim((string)$_POST['oficina']) : '';

    if ($id_py <= 0 || $accion === '' || $oficina === '') {
        echo json_encode(['ok'=>false, 'error'=>'ParÃ¡metros incompletos']); exit;
    }

    // Mismo criterio que la UI para habilitar botÃ³n
    $perm = puedeClickearAccion($id_rol, $accion, $id_py);
    if (!$perm['enabled'] && $id_rol !== 0) { // Admin (0) puede forzar en pruebas
        echo json_encode(['ok'=>false, 'error'=>$perm['why'] ?: 'No autorizado']); exit;
    }

    // Normaliza/valida segÃºn acciÃ³n
    $val = ValidacionService::normalizar($accion, $_POST);

    // Despacho por oficina
    switch (strtoupper($oficina)) {
        case 'PCF': $handler = new PCFHandler($conexion); break;
        case 'DD' : $handler = new DDHandler($conexion); break;
        case 'DF' : $handler = new DFHandler($conexion); break;
        case 'RSU': $handler = new RSUHandler($conexion); break;
        default   : echo json_encode(['ok'=>false, 'error'=>'Oficina no vÃ¡lida']); exit;
    }

    $res = $handler->guardar($id_py, strtolower($accion), $val, $usr);
    echo json_encode($res);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false, 'error'=>'ExcepciÃ³n: '.$e->getMessage()]);
}

