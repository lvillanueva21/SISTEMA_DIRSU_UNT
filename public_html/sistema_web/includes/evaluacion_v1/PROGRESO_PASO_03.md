# Bitácora Paso 03 - Mensajería controlada

Fecha: 2026-05-20

## Objetivo ejecutado
- Implementar control central de notificaciones:
  - `send_and_log`
  - `log_only`
- Auditar eventos de correo en `ev_eventos`.
- No bloquear la evaluación si falla el correo.

## Implementación aplicada
1. Política de modo de mensajería:
   - Se lee `evt_eventos.codigo='evaluacion_mensajeria'`.
   - `estado=1` => `send_and_log`.
   - `estado=0` => `log_only`.
   - Si no existe configuración, fallback: `send_and_log`.
2. Notificación central:
   - `NotificationService` ahora registra estado final: `sent`, `skipped`, `error`.
   - Registra detalle compacto en `ev_eventos.detalle` (máx 500 chars).
3. Bridge para notificaciones legacy:
   - `messaging_helpers.php` enlaza notificaciones antiguas con motor V1.
4. Warnings amigables:
   - Si falla correo, se guarda warning global y el endpoint devuelve:
     - `warning_message`
     - `warnings[]`
   - Frontend de modal muestra ese warning en éxito.

## Decisiones de compatibilidad
1. Se preservó el flujo de evaluación legacy (handlers), cambiando solo la capa de notificación.
2. No se alteró esquema de DB en este paso.
3. Se priorizó no romper UX ni bloquear evaluaciones por fallas de SMTP.

## Riesgos / pendientes
1. `ev_eventos.detalle` es `VARCHAR(500)`, por eso la auditoría de correo está resumida.
2. Si se desea guardar HTML/texto completo por correo, se debe ejecutar migración de columnas en un sprint posterior.
