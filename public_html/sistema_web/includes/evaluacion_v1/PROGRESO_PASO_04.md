# Bitácora Paso 04 - Compatibilidad fina

Fecha: 2026-05-20

## Objetivo ejecutado
- Agregar validaciones/normalizaciones para expedientes antiguos o incompletos:
  1. Evaluaciones sin oficina actual.
  2. Instancias con estados heredados/inconsistentes.
  3. Respuestas históricas sin contexto moderno completo.

## Implementación aplicada
1. Nuevo servicio `LegacyCompatibilityService`:
   - Normaliza `eva_evaluaciones.situacion` inválida a `en_oficina`.
   - Recupera `id_oficina_actual` cuando está vacío:
     - instancia abierta,
     - última instancia,
     - última calificación,
     - primera oficina activa.
   - Crea instancia faltante (`en_espera`) si no existe.
   - Normaliza estado de instancia heredado a:
     - `en_espera`, `aprobado`, `observado`.
   - Si detecta instancia aprobada y flujo quedó estancado:
     - avanza a siguiente oficina y abre instancia,
     - o cierra como aprobación total si era la última oficina.
   - Si no existe evaluación para la respuesta y `sm_respuestas.estado > 0`:
     - bootstrap de `eva_evaluaciones` + primera instancia.
   - Registra recuperaciones en `ev_eventos` (`LEGACY_NORMALIZED`, `LEGACY_EVAL_BOOTSTRAP`).

2. `EvaluationEngine::authorizeEvaluation()` ahora ejecuta normalización legacy antes de autorizar.

3. `ContextResolver` mejorado para históricos:
   - Si hay período y falla validación estricta, permite bypass legacy cuando faltan datos modernos del semestre de esa respuesta.
   - Búsqueda por período agrega fallback legacy (sin exigir `tipo='semestral'` ni `vigente=1`).
   - Fallback final adicional: última respuesta del proyecto aunque no tenga semestral moderno completo.

## Resultado esperado
- Casos pendientes históricos en comité/departamento/decanato/RSU pueden volver a evaluarse sin romper flujo por inconsistencias de estructura moderna.

## Riesgos / observaciones
1. El avance automático desde instancia `aprobado` legacy se aplica para destrabar expedientes incompletos.
2. Si se requiere desactivar este comportamiento, puede ponerse detrás de una bandera de evento en sprint siguiente.
