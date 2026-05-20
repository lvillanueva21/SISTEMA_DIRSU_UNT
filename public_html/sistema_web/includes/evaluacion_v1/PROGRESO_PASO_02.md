# Bitácora Paso 02 - Adaptadores backend

Fecha: 2026-05-20

## Objetivo ejecutado
- Conectar:
  - `sistema_web/evaluacion/api/save_evaluacion.php`
  - `sistema_web/informe_semestral/api/save_evaluacion.php`
- Ambos ahora delegan al motor `includes/evaluacion_v1/`.

## Hallazgos críticos durante implementación
1. Mapeo real de roles/evaluación en código legacy:
   - `5 => PCF`
   - `4 => DD`
   - `3 => DF`
   - `1 => RSU`
   - `2 => Coordinador (no califica)`
   - `0 => Admin`
2. Había dos familias de handlers:
   - `evaluacion/*`: firma por `id_py`.
   - `informe_semestral/*`: firma por `id_py + id_respuesta`.
3. Para evitar divergencia, se eligió usar una sola familia en adaptador:
   - `informe_semestral/handlers/*` (firma más precisa por `id_respuesta`).

## Decisiones aplicadas
1. Se creó `LegacyDispatcher` en el motor para despacho único a handlers legacy.
2. `ContextResolver` del motor define la `id_respuesta` efectiva antes de calificar.
3. La autorización ya no depende de funciones del módulo antiguo:
   - Se consulta estado real por `id_respuesta` (`eva_*`).
   - Se valida rol/oficina/estado desde `PermissionService`.
4. Se valida coherencia de oficina:
   - Si oficina enviada por POST no coincide con oficina actual del expediente, se bloquea (excepto admin).
5. Se cambió conexión de endpoint `evaluacion/api/save_evaluacion.php`:
   - de `componentes/db.php` a `includes/db_connection.php`.

## Riesgos/compatibilidad observada
1. La lógica de negocio principal sigue siendo legacy (handlers actuales), pero invocada por motor único.
2. `php -l` no se pudo ejecutar en este entorno local por ausencia de binario `php`; validar en servidor/CI.
3. Se mantiene sin cambios la UI de módulos antiguos.

## Próximo paso sugerido
- Paso 03: centralizar mensajería (`send_and_log` / `log_only`) y auditar eventos de correo por acción.
