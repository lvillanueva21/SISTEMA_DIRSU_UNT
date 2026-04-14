# Bitácora de implementación: Control de acceso por períodos e interfaces

Fecha: 2026-04-14

## Objetivo
Implementar un control fuerte, centralizado y dinámico para el acceso de coordinadores a:
- F1-GENERALIDADES (`vistas/datos_principales.php`)
- F1-PLAN (`vistas/desarrollo_informe.php`)
- F1-ANEXOS (`vistas/anexos.php`)
- F3-SEMESTRAL (`semestral/index.php`)

## Criterios funcionales acordados
1. Solo rol coordinador (`id_rol = 2`).
2. Proyecto en sesión obligatorio.
3. Evaluación por períodos activos DIRSU y cronogramas/interfaz activa.
4. F1 exige semestre objetivo de tipo `presentacion` para el período resuelto.
5. F3 exige semestre objetivo de tipo `semestral` para el período resuelto.
6. Si hay varios períodos activos, resolver con criterio inteligente y determinista.
7. Mensaje de bloqueo dinámico, claro, amigable y sin hardcode.
8. Mostrar solo interfaces activas en tabla cronograma (ordenadas: Generalidades, Plan, Anexos, Informe semestral).
9. Evitar mojibake, respetar tildes y Ñ.
10. Evitar rutas absolutas desde raíz en payloads/rutas de referencia.

## Progreso
- [x] Creada bitácora de implementación.
- [x] Crear servicio central de evaluación de acceso en `includes/access`.
- [x] Crear guard reutilizable para páginas.
- [x] Crear vista dinámica de bloqueo.
- [x] Integrar API nueva en `includes/api_dirsu/api.php`.
- [x] Integrar API en catálogo de pruebas `includes/api_dirsu/index.php`.
- [x] Migrar `datos_principales.php` al control central.
- [x] Migrar `desarrollo_informe.php` al control central.
- [x] Migrar `anexos.php` al control central.
- [x] Migrar `semestral/index.php` al control central.
- [x] Sustituir mensaje estático en `integrados/mensaje_fuera_tiempo.php`.
- [ ] Validar sintaxis PHP de archivos modificados.
- [x] Revisión final de texto en español (tildes/Ñ) y ausencia de mojibake introducido.

## Implementado
- Se creó `includes/access/project_interface_access_service.php` con selección inteligente de período activo y motivo de acceso/bloqueo.
- Se creó `includes/access/project_interface_guard.php` para reutilizar la evaluación en páginas.
- Se creó `includes/access/project_interface_block_view.php` con mensaje dinámico, tabla de interfaces activas, resumen de proyecto y semestres.
- Se reemplazó `integrados/mensaje_fuera_tiempo.php` para usar la vista dinámica.
- Se migraron F1 y F3 al nuevo guard central:
  - `vistas/datos_principales.php`
  - `vistas/desarrollo_informe.php`
  - `vistas/anexos.php`
  - `semestral/index.php`
- Se expuso la evaluación por API:
  - Acción nueva `project.interface.access.evaluate` en `includes/api_dirsu/api.php`.
  - Wrapper `includes/api_dirsu/project_interface_access_service.php`.
  - Catálogo/UI de pruebas actualizado en `includes/api_dirsu/index.php`.
- Se ajustaron rutas de interfaces en `active_periods_service.php` para evitar rutas absolutas desde raíz.

## Problemas detectados (durante implementación)
- El entorno local no tiene `php` CLI instalado, por lo que no se pudo ejecutar `php -l` para lint automático.

## Problemas detectados (inicial)
- Archivo `integrados/mensaje_fuera_tiempo.php` con contenido estático y mojibake.
- Flujo de acceso disperso entre páginas y lógica legacy en `componentes/cronograma`.
- Inconsistencias entre “múltiples períodos activos” y “cronograma activo por tipo”.

## Tareas de corrección
- Centralizar decisión en una sola función reusable.
- Unificar mensajes por `reason_code`.
- Exponer evaluación por API para reutilización futura.
- Ejecutar validación de sintaxis PHP en un entorno con CLI disponible.
