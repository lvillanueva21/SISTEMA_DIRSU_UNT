# Plan V1 - Motor de Evaluacion Compatible

## Objetivo final
Construir un motor nuevo de evaluacion de informes semestrales/finales, inspirado en el flujo antiguo, compatible con evaluaciones en curso, y sin romper interfaces antiguas (comite/departamento/decanato/dirsu).  
La interfaz principal nueva sera `lista_proyectos/`; las interfaces antiguas solo se adaptan por detras.

## Decision clave sobre mensajeria y auditoria
Si, se puede guardar mucha mas informacion de correo alterando la naturaleza de datos en tablas existentes.

### Opcion recomendada (sin crear tabla nueva en V1)
Usar `ev_eventos` como bitacora temporal oficial y extenderla:

1. Cambiar `ev_eventos.detalle` a `LONGTEXT`.
2. Agregar columnas:
   - `mail_to` (`TEXT`) - destinatarios (uno o varios, separados por `;` o JSON simple).
   - `mail_subject` (`VARCHAR(255)`).
   - `mail_html` (`LONGTEXT`) - cuerpo HTML completo.
   - `mail_text` (`LONGTEXT`) - cuerpo texto plano.
   - `mail_status` (`VARCHAR(20)`) - `sent`, `skipped`, `error`.
   - `mail_error` (`TEXT`) - mensaje tecnico de fallo.
   - `source_module` (`VARCHAR(60)`) - de donde salio (`evaluacion_v1`, `semestral`, etc.).

Con esto podras ver en tu plataforma de Mensajeria: quien recibio, que asunto, que contenido, estado real del envio y origen del evento.

---

## Paso 01 - Esqueleto del motor nuevo (sin tocar interfaces)
### Alcance
Crear carpeta de motor en `includes/` con servicios base:
- `ContextResolver` (resuelve `id_respuesta` de forma segura).
- `PermissionService` (reglas por rol/oficina/estado).
- `WorkflowService` (transiciones de estado).
- `EventLoggerService` (registro en `ev_eventos`).
- `NotificationService` (enviar/omitir correo + auditar).

### Regla de compatibilidad critica
Resolver `id_respuesta` en este orden:
1. Si llega `id_respuesta`: validar y usar.
2. Si llega `semestral/periodo`: buscar respuesta de ese semestre.
3. Si no llega periodo: priorizar respuesta con ruta activa en `eva_*`.
4. Fallback final: ultima respuesta semestral vigente del proyecto.

### Criterio de terminado
Existe API interna utilizable por adaptadores, sin cambiar UI aun.

---

## Paso 02 - Adaptadores backend para interfaces antiguas
### Alcance
Adaptar:
- `sistema_web/evaluacion/api/save_evaluacion.php`
- `sistema_web/informe_semestral/api/save_evaluacion.php`

Ambos delegan al motor nuevo.  
No se cambia el aspecto visual de pantallas antiguas.

### Comportamiento obligatorio
1. Si no hay respuesta valida para semestre seleccionado -> no evalua.
2. Coordinador no califica.
3. DD/DF solo VB.
4. PCF/RSU cotejo + rubrica.

### Criterio de terminado
Las pantallas antiguas siguen viendose igual, pero guardan/transicionan via motor nuevo.

---

## Paso 03 - Mensajeria controlada y auditoria completa
### Alcance
Implementar control central de notificaciones:
1. Modo `send_and_log`.
2. Modo `log_only`.

En ambos casos se registra evento completo en `ev_eventos` (con columnas extendidas).

### Regla funcional
Si falla el correo:
- No bloquear accion principal de evaluacion/subsanacion.
- Mostrar mensaje amigable:
  - "La evaluacion se guardo correctamente, pero no se pudo enviar el correo."

### Criterio de terminado
Cada accion relevante deja trazabilidad completa de mensajeria.

---

## Paso 04 - Compatibilidad fina con casos a medio camino
### Alcance
Agregar validaciones y normalizaciones para expedientes antiguos o incompletos:
1. Evaluaciones sin oficina actual.
2. Instancias en estados heredados.
3. Respuestas historicas sin ciertos campos modernos.

### Criterio de terminado
Comite puede seguir revisando pendientes historicos sin romper flujo.

---

## Paso 05 - Integracion gradual con lista_proyectos y cierre V1
### Alcance
1. Conectar botones de evaluacion de `lista_proyectos/` al motor nuevo.
2. Mantener interfaces antiguas operativas (adaptadas por backend).
3. Entregar checklist de pruebas por rol:
   - coordinador
   - comite
   - direccion departamento
   - decanato
   - dirsu

### Criterio de terminado
Motor unico funcionando, interfaces antiguas compatibles, y nueva interfaz lista para crecer.

---

## Modo de trabajo recomendado contigo
1. "Ejecuta Paso 01"
2. "Corrige Paso 01"
3. "Ejecuta Paso 02"
4. "Corrige Paso 02"
5. Repetir hasta Paso 05

Este esquema reduce riesgo, mantiene calidad y evita mezclar demasiados cambios en un solo sprint.
