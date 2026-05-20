# Bitácora Paso 01 - Motor Evaluación V1

Fecha: 2026-05-20

## Estado
- Paso 01 completado.
- Esqueleto creado en `includes/evaluacion_v1/`.

## Hallazgos confirmados (código + backup SQL)
1. `eva_evaluaciones.situacion` solo admite:
   - `en_oficina`
   - `aprobado`
2. `eva_oficina_instancias.estado` solo admite:
   - `en_espera`
   - `aprobado`
   - `observado`
3. `ev_eventos` actual (sin alteraciones en este paso):
   - `id_respuesta`, `event_code`, `office`, `tipo`, `detalle`, `created_at`, `created_by`, `ip`.
4. `sm_respuestas` contiene:
   - `id_py`, `id_semestre`, `estado`, `creado_at`, `actualizado_at`.
5. `sm_proyecto_semestres` contiene:
   - `anio`, `periodo`, `tipo`, `final`, `vigente`.
6. Catálogo de oficinas activo en `eva_oficinas`:
   - `PCF`, `DD`, `DF`, `RSU`.

## Decisiones aplicadas en este paso
1. Resolver `id_respuesta` con orden oficial:
   - explícita
   - por período
   - por ruta activa (`situacion='en_oficina'`)
   - fallback a última semestral vigente
   - cuando llega período explícito y no hay respuesta, se devuelve error (no se hace fallback cruzado).
2. No tocar esquema de DB aún.
3. No tocar interfaces antiguas en Paso 01.
4. Base de notificaciones preparada con dos modos:
   - `log_only`
   - `send_and_log`

## Riesgos detectados para Paso 02
1. Algunas APIs legacy usan `componentes/db.php`; el adaptador deberá redirigir de forma segura a `includes/db_connection.php`.
2. Hay coexistencia de rutas legacy (`ev_intentos`, `rutas_semestrales`) y rutas nuevas (`eva_*`); el adaptador debe priorizar `eva_*` sin romper históricos.
3. Existen archivos con texto mojibake heredado fuera de este paso; no se corrigieron para evitar cambios colaterales.

## Pendiente inmediato
- Conectar `sistema_web/evaluacion/api/save_evaluacion.php` y `sistema_web/informe_semestral/api/save_evaluacion.php` al motor nuevo (Paso 02), sin cambiar UI.
