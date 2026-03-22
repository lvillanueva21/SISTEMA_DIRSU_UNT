<?php
/**
 * Datos de prueba para la interfaz Api Dirsu.
 */

if (!function_exists('rsu_api_dirsu_mock_items')) {
    function rsu_api_dirsu_mock_items()
    {
        return array(
            array(
                'id' => 1,
                'nombre' => 'Listado de proyectos vigentes',
                'modulo' => 'proyectos',
                'metodo' => 'GET',
                'endpoint' => '/api/dirsu/proyectos',
                'estado' => 'activo',
                'actualizado_en' => '2026-03-20 09:30:00',
                'responsable' => 'equipo_rsu'
            ),
            array(
                'id' => 2,
                'nombre' => 'Crear proyecto de extension',
                'modulo' => 'proyectos',
                'metodo' => 'POST',
                'endpoint' => '/api/dirsu/proyectos',
                'estado' => 'borrador',
                'actualizado_en' => '2026-03-20 11:10:00',
                'responsable' => 'equipo_rsu'
            ),
            array(
                'id' => 3,
                'nombre' => 'Actualizar cronograma',
                'modulo' => 'cronograma',
                'metodo' => 'PUT',
                'endpoint' => '/api/dirsu/cronograma/{id}',
                'estado' => 'borrador',
                'actualizado_en' => '2026-03-20 14:25:00',
                'responsable' => 'coordinacion'
            ),
            array(
                'id' => 4,
                'nombre' => 'Eliminar actividad de cronograma',
                'modulo' => 'cronograma',
                'metodo' => 'DELETE',
                'endpoint' => '/api/dirsu/cronograma/{id}',
                'estado' => 'deshabilitado',
                'actualizado_en' => '2026-03-21 08:05:00',
                'responsable' => 'coordinacion'
            ),
            array(
                'id' => 5,
                'nombre' => 'Revision de informe final',
                'modulo' => 'evaluacion',
                'metodo' => 'GET',
                'endpoint' => '/api/dirsu/revision/informe-final',
                'estado' => 'activo',
                'actualizado_en' => '2026-03-21 10:45:00',
                'responsable' => 'decanato'
            ),
            array(
                'id' => 6,
                'nombre' => 'Actualizar estado de evaluacion',
                'modulo' => 'evaluacion',
                'metodo' => 'PATCH',
                'endpoint' => '/api/dirsu/evaluacion/{id}/estado',
                'estado' => 'borrador',
                'actualizado_en' => '2026-03-21 12:20:00',
                'responsable' => 'direccion_rsu'
            )
        );
    }
}
