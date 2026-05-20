# Evaluación V1 - Motor base

Este directorio centraliza el motor nuevo de evaluación para informes semestrales/finales.

Objetivos del Paso 01:
- Definir servicios base sin romper interfaces existentes.
- Resolver contexto (`id_respuesta`) con reglas de compatibilidad.
- Preparar reglas de permisos, flujo, eventos y notificaciones.
- Exponer una fachada reutilizable para los adaptadores del Paso 02.

Archivos principales:
- `bootstrap.php`: carga del motor.
- `EvaluationEngine.php`: fachada principal.
- `ContextResolver.php`: resolución de contexto de respuesta.
- `PermissionService.php`: validaciones por rol/oficina/acción.
- `WorkflowService.php`: utilidades de transición por oficinas.
- `EventLoggerService.php`: registro en `ev_eventos`.
- `NotificationService.php`: notificación con modo `send_and_log`/`log_only`.
- `LegacyDispatcher.php`: adaptador a handlers legacy (`EvalV4`) para compatibilidad.
- `MessagingPolicyService.php`: política de modo de mensajería según `evt_eventos`.
- `NotificationWarnings.php`: buffer de warnings de envío para respuestas API.
- `messaging_helpers.php`: puente para notificaciones legacy + motor V1.
- `LegacyCompatibilityService.php`: normalización de expedientes incompletos (`eva_*`).
- `PROGRESO_PASO_01.md`: bitácora viva de hallazgos y decisiones.
- `PROGRESO_PASO_02.md`: decisiones de migración de endpoints al motor.
- `PROGRESO_PASO_04.md`: compatibilidad fina para pendientes históricos.

Reglas de implementación:
- Usa `includes/db_connection.php` para conexión.
- No depende de `componentes/db.php`.
- No modifica esquema de base de datos en este paso.
