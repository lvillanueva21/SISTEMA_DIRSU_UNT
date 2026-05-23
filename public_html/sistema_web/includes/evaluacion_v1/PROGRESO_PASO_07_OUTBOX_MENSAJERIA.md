# Paso 07 - Outbox de mensajería y trazabilidad completa

## Objetivo
Registrar cada correo generado por evaluación, incluso cuando no se envía.

## Decisiones implementadas
1. Se integra `msj_correos_outbox` como bitácora formal.
2. Se conserva `ev_eventos` como auditoría de eventos del motor.
3. Si no hay destinatario, el flujo no se rompe:
   - se guarda en outbox como `no_enviado`,
   - motivo `sin_destinatarios`.
4. Si mensajería global o por evento está desactivada:
   - se guarda en outbox como `no_enviado`,
   - motivo según política (`mensajeria_global_desactivada` o `evento_desactivado`).
5. Si falla el envío SMTP:
   - se guarda en outbox como `error`,
   - con detalle técnico en `error_detalle`.

## Hallazgos relevantes
1. Antes había retornos tempranos en notificaciones (`return false`) al no encontrar destinatarios; eso evitaba auditar el caso.
2. Ahora esos casos igual pasan por el motor central para dejar trazabilidad.
3. La UI de switches de mensajería ya existía en `control_eventos`; esta mejora completa la parte de persistencia de resultados.

## Riesgos y mitigaciones
1. Si la tabla `msj_correos_outbox` no existe, el sistema sigue funcionando y solo audita en `ev_eventos`.
2. El outbox no bloquea acciones funcionales: fallas de bitácora extendida no impiden guardar evaluaciones.

