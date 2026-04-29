# AGENTS.md

## Regla principal de codificación
- Todo archivo de código y texto del repo debe guardarse en `UTF-8` **sin BOM**.
- Está prohibido guardar archivos en `Windows-1252`, `ISO-8859-1` o similares.

## Regla de edición segura
- No usar herramientas que cambien codificación de forma implícita.
- Si se usa PowerShell para escribir archivos, forzar siempre UTF-8 sin BOM.
- No convertir texto por “prueba y error” de codificaciones.
- No borrar ni alterar texto intencional del usuario (incluye tildes, ñ y emojis).

## Checklist obligatorio antes de cerrar cambios
- Verificar que no exista mojibake en archivos tocados:
  - Buscar patrones: `Ã`, `Â`, `â€”`, `â€“`, `â€œ`, `â€`, `â€¢`, `ðŸ`.
- Verificar que los textos visibles en UI con acentos sigan correctos.
- Confirmar que no se reemplazaron emojis válidos por secuencias dañadas.

## Comando recomendado de verificación (Windows / PowerShell)
```powershell
rg -n 'Ã|Â|â€”|â€“|â€œ|â€|â€¢|ðŸ' public_html/sistema_web
```

## Si aparece mojibake
- Detener cambios funcionales.
- Corregir primero la codificación del/los archivo(s) afectados.
- Repetir la búsqueda de verificación hasta obtener cero coincidencias.
