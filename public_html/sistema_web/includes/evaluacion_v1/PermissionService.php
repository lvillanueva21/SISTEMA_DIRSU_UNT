<?php
/**
 * Reglas de permisos del motor V1.
 */

class RSUEvaluacionV1PermissionService
{
    private function normalizeAction($accion)
    {
        $accion = strtolower(trim((string)$accion));
        if ($accion === 'visto_bueno') {
            return 'vb';
        }
        return $accion;
    }

    public function roleOfficeCode($id_rol)
    {
        $id_rol = (int)$id_rol;
        if ($id_rol === 5) {
            return 'PCF';
        }
        if ($id_rol === 4) {
            return 'DD';
        }
        if ($id_rol === 3) {
            return 'DF';
        }
        if ($id_rol === 1) {
            return 'RSU';
        }
        return null;
    }

    public function canEvaluate($id_rol, $accion, $office_code, $instance_state, $situacion = null)
    {
        $id_rol = (int)$id_rol;
        $accion = $this->normalizeAction($accion);
        $office_code = strtoupper(trim((string)$office_code));
        $instance_state = strtolower(trim((string)$instance_state));
        $situacion = strtolower(trim((string)$situacion));

        if ($situacion === 'aprobado') {
            return array('ok' => false, 'why' => 'Proyecto ya aprobado.');
        }

        if ($id_rol === 2) {
            return array('ok' => false, 'why' => 'El coordinador no puede calificar.');
        }

        if ($id_rol === 0) {
            if ($instance_state === 'observado') {
                return array('ok' => false, 'why' => 'Proyecto observado; se requiere subsanación del coordinador.');
            }
            if ($instance_state !== 'en_espera') {
                return array('ok' => false, 'why' => 'La oficina no está en espera de revisión.');
            }
            return array('ok' => true, 'why' => '');
        }

        $expectedOffice = $this->roleOfficeCode($id_rol);
        if ($expectedOffice === null) {
            return array('ok' => false, 'why' => 'Rol no autorizado para evaluación.');
        }
        if ($expectedOffice !== $office_code) {
            return array('ok' => false, 'why' => 'La oficina no corresponde al rol actual.');
        }

        if ($instance_state === 'observado') {
            return array('ok' => false, 'why' => 'Proyecto observado; se requiere subsanación del coordinador.');
        }

        $allowed = array();
        if ($office_code === 'PCF' || $office_code === 'RSU') {
            $allowed = array('cotejo', 'rubrica');
        } elseif ($office_code === 'DD' || $office_code === 'DF') {
            $allowed = array('vb');
        }

        if (!in_array($accion, $allowed, true)) {
            return array('ok' => false, 'why' => 'La acción no está permitida para esta oficina.');
        }

        return array('ok' => true, 'why' => '');
    }
}
