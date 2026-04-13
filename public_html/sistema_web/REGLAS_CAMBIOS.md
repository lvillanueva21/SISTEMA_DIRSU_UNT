# Reglas de Cambios: Rutas y UTF-8

## 1) Problema: rutas absolutas a la raíz

### Qué falla
Usar rutas como:
- `/sistema_web/...`
- `https://dominio-fijo/sistema_web/...`

rompe cuando el sistema se despliega en otra base, por ejemplo:
- `https://ejemplo.pe/sistema_web/...`
- `https://ejemplo.pe/ventas/sistema_web/...`
- `https://subdominio.ejemplo.pe/rsu/sistema_web/...`

Síntoma típico:
- Botones que solo agregan `#`
- JS/CSS/API con 404
- Redirecciones a URL incorrecta

### Regla
`Prohibido usar rutas relativas a la raíz` para recursos internos del sistema.

### Estrategia recomendada
1. Usar rutas relativas al archivo actual:
- `../evaluacion/js/observaciones_ui.js`
- `../index.php`
- `../../componentes/db.php`

2. Para enlaces en correos/notificaciones:
- Construir URL dinámica desde `HTTP_HOST` + `SCRIPT_NAME` y detectar base `.../sistema_web`.
- Evitar dominio hardcodeado.

3. Para rutas de filesystem:
- Evitar rutas fijas tipo `/var/www/.../sistema_web`.
- Usar `realpath(__DIR__ . '/../../')` y derivar desde ahí.

### Ejemplos (patrón)
```php
// Redirección portable
header('Location: ../index.php', true, 303);

// Base FS portable
$FS_BASE = realpath(__DIR__ . '/../../') ?: dirname(__DIR__, 2);
```

```js
// API portable desde /semestral/index.php
const API = (id) => `../evaluacion/api/observaciones_estado.php?id_py=${encodeURIComponent(id)}`;
```

---

## 2) Problema: mojibake (caracteres corruptos)

### Qué es
Texto en español dañado por mezcla de codificaciones (UTF-8 vs ANSI/ISO-8859-1).

### Causas comunes
- Archivo guardado con codificación distinta a UTF-8.
- Copiar/pegar desde fuente con encoding diferente.
- Edición masiva automática de caracteres.

### Regla
Todos los archivos editados deben quedar en `UTF-8 sin BOM`.

### Prevención
1. No hacer reemplazos globales “de limpieza”.
2. Editar solo líneas necesarias.
3. Si hay riesgo en texto visible HTML, usar entidades:
- `&aacute;`, `&eacute;`, `&iacute;`, `&oacute;`, `&uacute;`, `&ntilde;`, `&copy;`

### Verificación obligatoria tras cambios
```bash
# Usa aqui tu patron estandar de deteccion de mojibake
rg -n "<PATRON_MOJIBAKE>" <archivos_modificados>
```

Si aparece algo:
1. Corregir manualmente solo esas líneas.
2. Repetir el escaneo hasta quedar limpio.

---

## 3) Checklist rápido antes de cerrar una tarea

1. No hay rutas hardcodeadas a `/sistema_web/...` en código ejecutable.
2. No hay dominios fijos para enlaces internos.
3. No hay rutas físicas de servidor hardcodeadas.
4. Escaneo de mojibake limpio.
5. Sintaxis PHP intacta (`<?php`, `?>`, `??`, `?:`, `=>`).

---

## 4) Cómo invocar esta guía

Cuando pidas cambios, puedes escribir:

`Aplica cambios siguiendo REGLAS_CAMBIOS.md`

o

`Revisa portabilidad de rutas y UTF-8 según REGLAS_CAMBIOS.md`
