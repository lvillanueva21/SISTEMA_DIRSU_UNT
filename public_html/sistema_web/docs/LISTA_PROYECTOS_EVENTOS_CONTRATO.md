# Lista Proyectos: Contrato de Eventos UI

## Objetivo
Evitar fallas donde los botones de `Informe` y `Evaluación` no respondan por conflicto con el click de fila (`toggle` de detalle).

## Regla 1: Un solo dueño del click de acción
- Los botones de acción (`.prj-btn-informe`, `.prj-btn-evaluacion`) se atienden con delegación en `document` (jQuery `on`).
- No agregar `stopPropagation()` global en un `bind` separado para esos botones, porque bloquea el handler delegado.

## Regla 2: El toggle de fila debe ignorar botones
- La fila clickeable (`.prj-row-toggle`) debe validar el `event.target`.
- Si el click proviene de un botón de acción, la fila no se abre/cierra.
- Esto evita doble comportamiento (abrir modal + abrir fila detalle).

## Regla 3: Prioridad de compatibilidad
- Usar `jQuery.ajax` y `$(document).on(...)` como camino principal.
- Evitar depender solo de APIs modernas del navegador para eventos críticos.

## Selectores protegidos
- `.prj-deliver-btn`
- `.prj-eval-btn`
- `.prj-btn-informe`
- `.prj-btn-evaluacion`

## Checklist rápido antes de cerrar cambios
1. Click en `Inf. Semestral` abre modal y no despliega la fila.
2. Click en `Evaluación` abre modal y no despliega la fila.
3. Click en cualquier otra parte de la fila sí despliega/oculta detalle.
4. Probar en coordinador y evaluador.

