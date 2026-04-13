# Bitacora de cambios - Auditoria de semestres

Fecha: 2026-03-26

## Objetivo
- Crear una tercera API de auditoria de semestres.
- Crear cards reutilizables en `includes/cards/`.
- Mostrar el card en el modal de inicio sin romper la logica actual.

## Cambios aplicados
- Se agrego `semester_audit_service.php` en `includes/api_dirsu/`.
- Se agrego la accion `project.semesters.audit` en `includes/api_dirsu/api.php`.
- Se agrego la API al catalogo de pruebas en `includes/api_dirsu/index.php`.
- Se crearon dos cards:
  - `includes/cards/card_auditoria_semestres_actual.php`
  - `includes/cards/card_auditoria_semestres_todos.php`
- Se mejoro la vista de cards para mostrar detalle por semestre:
  - tabla de semestres esperados
  - tabla de semestres vigentes en BD
  - lista de diferencias detectadas
- Se agrego informacion de informe/respuesta por semestre (si existe en `sm_respuestas`).
- Se integro en `inicio.php` un flag para elegir:
  - mostrar auditoria de todos los proyectos
  - o mostrar solo el proyecto activo (`id_py`)

## Criterio de comparacion usado
- Comparacion exacta entre:
  - semestres calculados por `proyectos.fecha_inicio/fecha_fin`
  - filas vigentes de `sm_proyecto_semestres` (`anio`, `periodo`, `tipo`, `numero`, `fecha_inicio`, `fecha_fin`)

## Notas de despliegue
- El modal original de multiproyectos mantiene su flujo (continuar/crear proyecto).
- El nuevo bloque es solo lectura y se inyecta de forma adicional.

## Verificaciones ejecutadas
- Escaneo de mojibake en archivos modificados:
  - `rg -n "<patron_mojibake>" <archivos_modificados>`
  - Resultado: sin coincidencias en los archivos modificados.
- Verificacion UTF-8 sin BOM:
  - Todos los archivos modificados reportan `utf8_bom=False`.
- Revision de integracion:
  - `project.semesters.audit` agregado en `api.php` y catalogado en `index.php`.
  - `inicio.php` usa flag para alternar entre card de todos o card de proyecto activo.
