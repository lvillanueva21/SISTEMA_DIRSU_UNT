# Regla de Textos en Espanol (UTF-8)

Este documento es una regla obligatoria para revisar textos visibles del sistema.
Objetivo: evitar textos rotos como `pagina`, `sesion`, `Area` o simbolos extranos.

## Regla obligatoria en cada cambio

Antes de aprobar un cambio, siempre revisar:

1. Botones
2. Titulos y subtitulos
3. Mensajes de alerta, confirmacion y error
4. Footer y textos institucionales
5. Texto renderizado por AJAX/JSON

Si aparece texto roto, el cambio no se aprueba hasta corregirlo.

## Estandar tecnico minimo

1. Guardar archivos en `UTF-8` (preferible `UTF-8 sin BOM`).
2. Mantener `<meta charset="utf-8">` en vistas HTML.
3. En respuestas de backend usar:
- HTML: `header('Content-Type: text/html; charset=utf-8');`
- JSON: `header('Content-Type: application/json; charset=utf-8');`
4. En MySQL/MariaDB usar `utf8mb4` en conexion y tablas.
5. Evitar copiar texto desde fuentes que cambian encoding (Word, PDFs viejos, etc.) sin validar salida final.

## Textos de referencia

Si necesitas version segura en HTML ASCII, usa entidades:

- `Ir a p&aacute;gina DIRSU`
- `Cerrar sesi&oacute;n`
- `&copy; 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.`
- `Desarrollado por el &Aacute;rea inform&aacute;tica - DIRSU`

Si usas UTF-8 normal, revisar que se vea asi:

- `Ir a pagina DIRSU`
- `Cerrar sesion`
- `(c) 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.`
- `Desarrollado por el Area informatica - DIRSU`

## Checklist rapido de QA visual

1. Abrir la interfaz y navegar modulos principales.
2. Revisar navbar, sidebar, cards, modales y footer.
3. Ejecutar una accion AJAX y validar mensajes de respuesta.
4. Confirmar que no aparezcan patrones de mojibake en pantalla.
5. Si hay error, corregir encoding del archivo fuente y volver a probar.

## Nota para el equipo

Esta regla aplica a cualquier archivo nuevo o modificado del sistema en espanol.
No cerrar una tarea de interfaz sin pasar esta revision.
