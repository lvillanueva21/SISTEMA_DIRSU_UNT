# Progreso Paso 06 - Mensajeria en Solicitud de Revision y Subsanacion

Fecha: 2026-05-20

## Objetivo
- Conectar los dos eventos faltantes de mensajeria al motor V1:
  - `MAIL_SOLICITUD_REVISION`
  - `MAIL_SUBSANACION`
- Evitar que fallos de correo bloqueen la operacion de subsanacion.
- Mantener auditoria en `ev_eventos` para enviado y no enviado.

## Cambios implementados
1. `semestral/logica/solicitar_revision.php`
   - Se integra `includes/evaluacion_v1/messaging_helpers.php`.
   - La notificacion al coordinador pasa por `rsu_eval_v1_notify_mail(...)`.
   - Evento auditado: `MAIL_SOLICITUD_REVISION`.
   - Se respeta el switch global y el switch especifico de solicitud de revision.

2. `semestral/logica/notificaciones_subsanacion_autoridades.php`
   - Se integra `includes/evaluacion_v1/messaging_helpers.php`.
   - El envio a autoridades ahora pasa por `rsu_eval_v1_notify_mail(...)`.
   - Evento auditado: `MAIL_SUBSANACION`.
   - Se mantiene `enviarCorreoSubsanacion(...)` como transportador SMTP.

3. `semestral/logica/enviar_subsanacion.php`
   - La subsanacion ya no falla por error de correo.
   - Si la mensajeria falla, devuelve `mail_ok=false` + `mail_msg` amigable.
   - La accion principal (subsanacion) queda confirmada.

## Hallazgos
- La logica anterior de subsanacion hacia `jfail(500)` si no enviaba correo.
- Con el cambio actual, correo y auditoria pasan a ser best-effort sin romper flujo.

## Pendiente sugerido
- Si se requiere trazabilidad completa del cuerpo de correo, mantener `ev_eventos.detalle` en `LONGTEXT`.
