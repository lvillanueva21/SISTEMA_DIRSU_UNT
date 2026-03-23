# API DIRSU - Estructura y Guia de Implementacion

Este documento describe como esta organizada la carpeta `includes/api_dirsu/`, como funciona la API actual de usuarios y como crear futuras APIs manteniendo el mismo orden.

## 1) Objetivo de `api_dirsu`

- Centralizar endpoints API reutilizables para todo el sistema.
- Mantener una interfaz de pruebas (solo development) para validar endpoints sin recargar pagina.
- Estandarizar respuestas JSON, validaciones, seguridad y estructura de codigo.

## 2) Estructura actual de archivos

- `index.php`
  - Interfaz visual de pruebas de APIs.
  - Usa AJAX para invocar `api.php`.
  - Visible solo en `session_mode=development` (por `guard.php`).

- `api.php`
  - Router principal de acciones API (ejemplo: `action=user.get`).
  - Valida metodo HTTP, parametros y devuelve JSON estandar.
  - Usa `guard_api.php` y servicios (ejemplo: `user_service.php`).

- `user_service.php`
  - Logica de negocio y consultas para la API de usuarios.
  - No imprime HTML; retorna arreglos con `ok`, `data`, `meta` o error.

- `guard.php`
  - Guard para la interfaz visual (`index.php`).
  - Exige sesion valida y `session_mode=development`.

- `guard_api.php`
  - Guard de endpoints API (`api.php`).
  - Exige sesion valida y permite controlar roles autorizados.
  - No depende de `session_mode` (la API se puede usar en produccion).

- `json_response.php`
  - Helpers unificados para responder JSON:
    - `rsu_api_json_ok(...)`
    - `rsu_api_json_error(...)`

- `url_helper.php`
  - Construccion de rutas relativas para `api.php`.
  - Evita rutas absolutas a raiz (`/`) para facilitar migraciones de dominio/subruta.

- `.htaccess`
  - Bloquea acceso directo a toda la carpeta por defecto.
  - Habilita solo `index.php` y `api.php`.

## 3) Flujo de funcionamiento (actual)

1. Usuario abre `includes/api_dirsu/index.php`.
2. `guard.php` valida sesion + modo development.
3. Interfaz ejecuta AJAX contra `api.php?action=user.get&...`.
4. `api.php` valida:
   - sesion y rol (`guard_api.php`)
   - metodo (`GET`)
   - accion y parametros
5. `api.php` llama a `user_service.php`.
6. `user_service.php` consulta BD (mysqli), arma resultado y lo retorna.
7. `api.php` responde JSON estandar con `json_response.php`.

## 4) Estandar de respuesta JSON

### Exito

```json
{
  "ok": true,
  "message": "Consulta de usuario completada.",
  "data": {},
  "meta": {}
}
```

### Error

```json
{
  "ok": false,
  "code": "not_found",
  "message": "No se encontro ...",
  "errors": [],
  "data": null
}
```

## 5) Regla de orden para futuras APIs

Cada nueva API debe separar:

1. **Router/entrada** en `api.php`
2. **Logica de negocio** en un archivo `*_service.php`
3. **Salida JSON** por `json_response.php`
4. **Seguridad** por `guard_api.php`

No mezclar:
- HTML en servicios
- SQL directo dentro de `index.php`
- `echo` de texto libre en endpoints API

## 6) Como crear una nueva API (paso a paso)

Ejemplo: `project.get`

1. Crear servicio:
   - Archivo: `project_service.php`
   - Funcion sugerida: `rsu_api_project_get($id)`
   - Debe retornar:
     - exito: `array('ok' => true, 'data' => ..., 'meta' => ...)`
     - error: `array('ok' => false, 'error_code' => '...', 'error_message' => '...')`

2. Registrar include en `api.php`:
   - `include_once __DIR__ . '/project_service.php';`

3. Agregar bloque de accion en `api.php`:
   - `if ($action === 'project.get') { ... }`
   - Validar parametros.
   - Llamar servicio.
   - Responder con `rsu_api_json_ok` o `rsu_api_json_error`.

4. (Opcional) Exponer en interfaz de pruebas `index.php`:
   - Agregar item en `$rsu_api_dirsu_data`.
   - Si tiene backend activo, poner `soporte_live => 1`.
   - Si requiere alias nuevos, actualizar `getAliasForField(...)`.

5. Probar:
   - Desde `index.php` en development.
   - Validar casos de error: faltan parametros, no encontrado, sin permisos.

## 7) Convenciones recomendadas

- Nombres de accion: `modulo.operacion` (ejemplo: `user.get`, `project.list`).
- Servicios: `*_service.php`.
- Funciones: prefijo `rsu_api_...`.
- SQL con `mysqli_prepare` y parametros enlazados.
- Compatibilidad: evitar funciones exclusivas de versiones nuevas.

## 8) Seguridad y compatibilidad

- API protegida por sesion activa.
- Control de roles centralizable en `guard_api.php`.
- `.htaccess` minimiza exposicion de archivos internos.
- Rutas relativas para soportar:
  - `https://dominio/sistema_web/`
  - `https://dominio/subruta/sistema_web/`

## 9) Notas para mantener consistencia

- La interfaz (`index.php`) es laboratorio; los endpoints son el componente reutilizable.
- Si un endpoint sera consumido por varias pantallas:
  - mantener contrato JSON estable
  - versionar cambios grandes (por ejemplo `user.get.v2`)
- Si agregas nuevos campos de respuesta:
  - actualizar alias amigables en la tabla de `index.php` para facilitar QA/soporte.

