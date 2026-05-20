<?php
/**
 * Adaptador de compatibilidad para handlers legacy (EvalV4).
 * Se usa en Paso 02 para mantener UI antigua sin reescribir lógica de negocio.
 */

class RSUEvaluacionV1LegacyDispatcher
{
    /** @var mysqli */
    private $db;

    private static $loaded = false;

    public function __construct($db)
    {
        $this->db = $db;
        self::loadLegacyDependencies();
    }

    private static function loadLegacyDependencies()
    {
        if (self::$loaded) {
            return;
        }

        $base = dirname(dirname(__DIR__));
        $mod = $base . '/informe_semestral';

        require_once $mod . '/core/ValidacionService.php';
        require_once $mod . '/core/EvaluacionService.php';
        require_once $mod . '/core/RutaService.php';
        require_once $mod . '/handlers/PCFHandler.php';
        require_once $mod . '/handlers/DDHandler.php';
        require_once $mod . '/handlers/DFHandler.php';
        require_once $mod . '/handlers/RSUHandler.php';

        self::$loaded = true;
    }

    private function normalizeOfficeCode($office_code)
    {
        return strtoupper(trim((string)$office_code));
    }

    private function normalizeAction($accion)
    {
        $accion = strtolower(trim((string)$accion));
        if ($accion === 'visto_bueno') {
            return 'vb';
        }
        return $accion;
    }

    public function normalizeInput($accion, array $in)
    {
        $accion = $this->normalizeAction($accion);
        return \EvalV4\ValidacionService::normalizar($accion, $in);
    }

    public function dispatch($office_code, $id_py, $id_respuesta, $accion, array $val, array $usr)
    {
        $office_code = $this->normalizeOfficeCode($office_code);
        $accion = $this->normalizeAction($accion);
        $id_py = (int)$id_py;
        $id_respuesta = (int)$id_respuesta;

        if ($id_py <= 0 || $id_respuesta <= 0) {
            return array('ok' => false, 'error' => 'Parámetros incompletos');
        }

        switch ($office_code) {
            case 'PCF':
                $handler = new \EvalV4\PCFHandler($this->db);
                break;
            case 'DD':
                $handler = new \EvalV4\DDHandler($this->db);
                break;
            case 'DF':
                $handler = new \EvalV4\DFHandler($this->db);
                break;
            case 'RSU':
                $handler = new \EvalV4\RSUHandler($this->db);
                break;
            default:
                return array('ok' => false, 'error' => 'Oficina no válida');
        }

        return $handler->guardar($id_py, $id_respuesta, $accion, $val, $usr);
    }
}
