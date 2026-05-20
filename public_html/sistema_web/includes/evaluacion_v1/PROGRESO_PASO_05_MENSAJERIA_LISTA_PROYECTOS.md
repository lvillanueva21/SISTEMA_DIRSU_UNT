# Progreso Paso 05 - Mensajeria y Evaluacion en Lista de Proyectos

Fecha: 2026-05-20

## Objetivo
- Habilitar evaluacion real desde `lista_proyectos` (cotejo, rubrica, visto bueno) usando el motor V1.
- Agregar control de mensajeria en `control_eventos` con switches por actividad.
- Auditar correos enviados y no enviados.

## Cambios implementados
1. `lista_proyectos` ahora carga formularios reales de calificacion por semestre:
   - Se usa `id_respuesta` del boton seleccionado.
   - Formulario fuente: `informe_semestral/modales/evaluacion_msg.php`.
   - Guardado: `informe_semestral/api/save_evaluacion.php` (motor V1).

2. Mensajeria por actividad:
   - Se agregaron codigos de control en `evt_eventos`:
     - `evaluacion_mensajeria` (global)
     - `evaluacion_mail_derivacion`
     - `evaluacion_mail_observacion`
     - `evaluacion_mail_aprob_total`
     - `evaluacion_mail_solicitud_revision` (reservado)
     - `evaluacion_mail_subsanacion` (reservado)
   - `control_eventos` permite configurarlos por modal.

3. Politica de envio:
   - `MessagingPolicyService` ahora decide modo por `event_code`.
   - Si global esta apagado => todo `log_only`.
   - Si global esta encendido => cada evento respeta su switch.

4. Auditoria de correo:
   - `NotificationService` ahora incluye en `detalle`:
     - `event_code`, `mode`, `status`, `to`, `subject`, `error`, `html`, `text`.
   - `EventLoggerService` detecta tipo real de columna `ev_eventos.detalle`:
     - Si es `TEXT/MEDIUMTEXT/LONGTEXT`, no recorta.
     - Si es `VARCHAR(N)`, recorta a `N`.

## Hallazgos / Riesgos
1. En varias bases antiguas `ev_eventos.detalle` es `VARCHAR(500)`.
   - Resultado: el contenido de correo se recorta.
   - Solucion recomendada: migrar a `LONGTEXT`.

2. En Paso 06 se conectaron los disparadores de `solicitud_revision` y `subsanacion`
   a sus `event_code` en handlers.

## Query recomendada para auditoria completa
```sql
ALTER TABLE ev_eventos
  MODIFY detalle LONGTEXT NULL;
```

## Compatibilidad
- No se reemplazo flujo legacy visible de otras interfaces.
- El guardado sigue pasando por motor V1 y handlers compatibles.
