# Paso 08 - Cierre de flujo coordinador en lista_proyectos

## Objetivo
Completar en `lista_proyectos` el flujo real de coordinador para informes semestrales/finales:
- Solicitar revisión
- Anular solicitud
- Enviar subsanación
- Ver observaciones activas (cotejo/rúbrica)

## Hallazgos clave
1. El backend de `semestral/logica/*` ya tenía reglas sólidas de transición.
2. `lista_proyectos` solo estaba mostrando estado y acciones de evaluador; faltaba conectar acciones de coordinador.
3. El correo de subsanación no incluía resumen de observaciones de rúbrica.

## Implementado
1. `lista_proyectos/assets/proyectos.js`
   - Nuevo bloque `Flujo del coordinador` en modal de evaluación.
   - Conexión AJAX real a:
     - `semestral/logica/solicitar_revision.php`
     - `semestral/logica/anular_revision.php`
     - `semestral/logica/enviar_subsanacion.php`
   - Nuevo modal de observaciones usando:
     - `evaluacion/api/observaciones_estado.php?id_py=...`
   - Render de tabla de aspectos de rúbrica en observaciones.

2. `semestral/logica/notificaciones_subsanacion_autoridades.php`
   - Se agregó `obtenerResumenRubricaSubsanacion(...)`.
   - Si existen observaciones de rúbrica, el correo ahora incluye:
     - Tabla HTML (aspecto, nota, observación)
     - Resumen en texto plano
     - Puntaje total cuando existe.

3. `direccion_rsu/control_eventos.php`
   - Se actualizaron etiquetas de mensajería (ya no “reservado” para solicitud/subsanación).
   - Se actualizaron plantillas de vista previa para reflejar flujo activo y ejemplo de resumen de rúbrica en subsanación.

## Riesgos y notas
1. Si el servidor no tiene sesión válida de coordinador, las acciones devolverán error de autorización.
2. Si no hay destinatarios válidos de correo, la acción funcional no debe bloquearse, pero quedará advertencia de mensajería.
3. El resumen de rúbrica se arma desde `eva_rubrica_aspectos`; si no hay observaciones de texto, no se inyecta tabla.

## Pruebas manuales recomendadas
1. Coordinador con informe en borrador observado:
   - Abrir `Evaluación` en `lista_proyectos`.
   - Ejecutar `Enviar subsanación`.
   - Verificar mensaje de éxito y recarga del estado.
2. Coordinador con informe en borrador:
   - Ejecutar `Solicitar revisión`.
3. Coordinador con informe solicitado y aún no tomado por oficina:
   - Ejecutar `Anular solicitud`.
4. Coordinador con informe observado:
   - Ejecutar `Ver observaciones` y confirmar tabla de rúbrica.
