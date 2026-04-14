# Bitácora de implementación: copia de ítems entre formularios

Fecha: 2026-04-14
Módulo: `direccion_rsu/control_proyectos.php` -> card **Administración de formularios**

## Objetivo
Implementar un flujo seguro para copiar ítems de un formulario origen hacia un formulario destino vacío (sin ítems activos), incluyendo copia física e independiente de archivos adjuntos.

## Progreso realizado
- Se creó el servicio `formulario_copy_service.php` con lógica transaccional para:
  - Validar origen/destino.
  - Validar que el destino no tenga ítems activos.
  - Clonar archivos locales de `files_forms/...` con nombre único.
  - Insertar nuevos `sm_items` y nuevas relaciones en `sm_formulario_items`.
  - Hacer rollback de BD y archivos si algo falla.
- Se agregó backend en `card_crear_formulario.php`:
  - Acción `copy_candidates` (lista destinos válidos).
  - Acción `copy_items_between_forms` (ejecuta la copia).
  - Restricción por rol Dirección RSU (`id_rol = 1`).
- Se agregó interfaz en `card_crear_formulario.php`:
  - Botón **Copiar** por fila.
  - Modal para elegir destino.
  - Manejo de errores con detalle de incidencias detectadas.
- Se ajustó `card_items_srv.php` para compatibilidad de rutas:
  - Normalización de rutas locales a formato `files_forms/...`.
  - URLs públicas generadas de forma consistente.
  - Subida y borrado usando helpers seguros de ruta.

## Incidencias detectadas
- Se detectó coexistencia histórica de rutas con y sin `/` inicial en archivos (`/files_forms/...` y `files_forms/...`).
- Se aplicó normalización para aceptar ambas y operar con una convención consistente.

## Tareas de verificación ejecutadas
- Revisión estática de consultas SQL y validaciones de permisos.
- Revisión de consistencia de rutas de archivos (listado, detalle, subida, borrado y copia).
- Verificación de no dependencia de URL absoluta a la raíz para los nuevos cambios.

## Pendientes
- Ninguno bloqueante en código para este alcance.
- Recomendación operativa: prueba manual en entorno QA con formularios reales que incluyan adjuntos.
