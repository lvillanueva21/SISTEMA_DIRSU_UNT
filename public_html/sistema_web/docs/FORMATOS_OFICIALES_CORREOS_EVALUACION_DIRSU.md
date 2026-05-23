# FORMATOS OFICIALES DE CORREOS - EVALUACION DE PROYECTOS DIRSU

## Alcance
Este documento normaliza los formatos de correo usados en evaluación y flujo de aprobación/derivación de proyectos.

Fuente de verdad: ejemplos reales históricos proporcionados (2025).

---

## Convención de variables
Las partes dinámicas se representan como `{{variable}}`.

Variables comunes:
- `{{fecha_hora}}` -> formato `dd/mm/yyyy HH:MM`
- `{{proyecto_titulo}}`
- `{{proyecto_id}}`
- `{{periodo}}` -> ejemplo `2025-I`
- `{{proyecto_linea}}` -> `{{proyecto_titulo}} (ID {{proyecto_id}}) — {{periodo}}`
- `{{url_sistema}}`

Variables de oficina/flujo:
- `{{oficina_origen}}` -> ejemplo: `Comité de Facultad`
- `{{oficina_destino}}` -> ejemplo: `Dirección de Departamento`
- `{{oficina_sigla}}` -> ejemplo: `PCF`, `RSU`

Variables de observación/subsanación:
- `{{tipo_evaluacion}}` -> `Cotejo` o `Rúbrica`
- `{{observacion_texto}}`
- `{{fecha_max_subsanacion}}`
- `{{dias_restantes}}`

Variables de subsanación enviada:
- `{{coordinador_nombre}}`
- `{{facultad_nombre}}`
- `{{departamento_nombre}}`

---

## 1) Correo de Aprobación Total

### Metadatos
- Remitente fijo: `Sistema DIRSU <proyectosdirsu@unitru.edu.pe>`
- Asunto oficial: `¡Aprobación Total!`

### Destinatario esperado
- Coordinador/a o responsable del proyecto.

### Plantilla oficial

```txt
¡Aprobación Total!

Tu proyecto fue aprobado en la Oficina {{oficina_origen}} el {{fecha_hora}}.

Con esta aprobación, el proceso de revisión ha culminado exitosamente. No quedan tareas pendientes por realizar.

Proyecto: {{proyecto_linea}}

Ingresar al Sistema DIRSU
```

### Texto fijo
- `¡Aprobación Total!`
- `Tu proyecto fue aprobado en la Oficina`
- `Con esta aprobación, el proceso de revisión ha culminado exitosamente. No quedan tareas pendientes por realizar.`
- `Proyecto:`
- `Ingresar al Sistema DIRSU`

### Variables
- `{{oficina_origen}}`
- `{{fecha_hora}}`
- `{{proyecto_linea}}`

---

## 2) Correo de Derivación entre Oficinas

### Metadatos
- Remitente fijo: `Sistema DIRSU <proyectosdirsu@unitru.edu.pe>`
- Asunto recomendado: `Proyecto derivado a {{oficina_destino}} - Sistema DIRSU`

### Destinatario esperado
- Coordinador/a o responsable del proyecto.

### Plantilla oficial

```txt
Tu proyecto fue aprobado en la Oficina {{oficina_origen}} y ha sido derivado a la Oficina {{oficina_destino}}.

Fecha y hora: {{fecha_hora}}

Proyecto: {{proyecto_linea}}

Ingresar al Sistema DIRSU

Este es un correo automático de notificación de derivación.
```

### Texto fijo
- `Tu proyecto fue aprobado en la Oficina`
- `y ha sido derivado a la Oficina`
- `Fecha y hora:`
- `Proyecto:`
- `Ingresar al Sistema DIRSU`
- `Este es un correo automático de notificación de derivación.`

### Variables
- `{{oficina_origen}}`
- `{{oficina_destino}}`
- `{{fecha_hora}}`
- `{{proyecto_linea}}`

---

## 3) Correo de Subsanación Enviada

### Metadatos
- Remitente fijo: `Sistema DIRSU <proyectosdirsu@unitru.edu.pe>`
- Asunto oficial observado: `Subsanación enviada — Tienes un proyecto por revisar — PROYECTOS DIRSU`

### Destinatario esperado
- Evaluador(es) de la oficina que generó la observación.

### Plantilla oficial

```txt
Hola,

El proyecto con título: “{{proyecto_titulo}}” del coordinador {{coordinador_nombre}} que pertenece a la facultad {{facultad_nombre}} y departamento {{departamento_nombre}} ha registrado una subsanación de las observaciones hechas por tu oficina ({{oficina_origen}}).

El siguiente paso es ingresar a la plataforma y volver a revisar el proyecto para aprobarlo si las subsanaciones satisfacen lo requerido.

Ingresar al Sistema DIRSU

Este mensaje se envió automáticamente al/los evaluador(es) de la oficina correspondiente.
```

### Texto fijo
- `Hola,`
- `El proyecto con título:`
- `ha registrado una subsanación de las observaciones hechas por tu oficina`
- `El siguiente paso es ingresar a la plataforma y volver a revisar el proyecto para aprobarlo si las subsanaciones satisfacen lo requerido.`
- `Ingresar al Sistema DIRSU`
- `Este mensaje se envió automáticamente al/los evaluador(es) de la oficina correspondiente.`

### Variables
- `{{proyecto_titulo}}`
- `{{coordinador_nombre}}`
- `{{facultad_nombre}}`
- `{{departamento_nombre}}`
- `{{oficina_origen}}`

---

## 4) Correo de Observación por Cotejo

### Metadatos
- Remitente fijo: `Sistema DIRSU <proyectosdirsu@unitru.edu.pe>`
- Asunto oficial observado: `Recibiste una Observación en {{oficina_origen}} - Sistema DIRSU`

### Destinatario esperado
- Coordinador/a o responsable del proyecto (para subsanar).

### Plantilla oficial

```txt
Recibiste una observación.

Proyecto: {{proyecto_linea}}

Oficina: {{oficina_origen}} ({{oficina_sigla}})  |  Tipo: Cotejo  |  Fecha: {{fecha_hora}}

Observación:
{{observacion_texto}}

Fecha máxima de subsanación: {{fecha_max_subsanacion}} ({{dias_restantes}} día(s) restantes)

Presiona para ir al Sistema DIRSU y subsanar.
```

### Texto fijo
- `Recibiste una observación.`
- `Proyecto:`
- `Oficina:`
- `|  Tipo: Cotejo  |  Fecha:`
- `Observación:`
- `Fecha máxima de subsanación:`
- `Presiona para ir al Sistema DIRSU y subsanar.`

### Variables
- `{{proyecto_linea}}`
- `{{oficina_origen}}`
- `{{oficina_sigla}}`
- `{{fecha_hora}}`
- `{{observacion_texto}}`
- `{{fecha_max_subsanacion}}`
- `{{dias_restantes}}`

---

## 5) Correo de Observación por Rúbrica

### Metadatos
- Remitente fijo: `Sistema DIRSU <proyectosdirsu@unitru.edu.pe>`
- Asunto oficial observado: `Recibiste una Observación en {{oficina_origen}} - Sistema DIRSU`

### Destinatario esperado
- Coordinador/a o responsable del proyecto (para subsanar).

### Plantilla oficial

```txt
Recibiste una observación.

Proyecto: {{proyecto_linea}}

Oficina: {{oficina_origen}} ({{oficina_sigla}})  |  Tipo: Rúbrica  |  Fecha: {{fecha_hora}}

Aspecto	Nota	Observación
{{rubrica_detalle_filas}}
Puntaje total: {{puntaje_total}} / {{puntaje_maximo}}

Fecha máxima de subsanación: {{fecha_max_subsanacion}} ({{dias_restantes}} día(s) restantes)

Presiona para ir al Sistema DIRSU y subsanar.

Si tienes dudas, contacta a {{oficina_origen}} o responde este correo.
```

### Texto fijo
- `Recibiste una observación.`
- `Proyecto:`
- `Oficina:`
- `|  Tipo: Rúbrica  |  Fecha:`
- `Aspecto	Nota	Observación`
- `Puntaje total:`
- `Fecha máxima de subsanación:`
- `Presiona para ir al Sistema DIRSU y subsanar.`
- `Si tienes dudas, contacta a`

### Variables
- `{{proyecto_linea}}`
- `{{oficina_origen}}`
- `{{oficina_sigla}}`
- `{{fecha_hora}}`
- `{{rubrica_detalle_filas}}` (tabla de filas por criterio)
- `{{puntaje_total}}`
- `{{puntaje_maximo}}`
- `{{fecha_max_subsanacion}}`
- `{{dias_restantes}}`

---

## Recomendaciones de implementación (consistencia)
- Respetar exactamente mayúsculas/minúsculas y signos de puntuación del texto fijo.
- Mantener formato de fecha/hora `dd/mm/yyyy HH:MM` en todos los escenarios.
- Construir `{{proyecto_linea}}` de forma uniforme para todos los correos.
- Mantener el CTA textual `Ingresar al Sistema DIRSU` o `Presiona para ir al Sistema DIRSU y subsanar.` según corresponda.

---

## Control de cambios
- Documento inicial generado para reconstrucción de formatos oficiales post-borrado de archivos de evaluación/mensajería.