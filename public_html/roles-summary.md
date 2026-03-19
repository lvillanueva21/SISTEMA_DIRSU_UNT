# Roles y login del sistema DIRSU

## Tablas clave (fuente: `backup_rsudb_20260319_223831.sql`)
- `rol` (p. ej. línea ~6745): almacena los tipos de acceso con `id` entero y `nombre`. Los valores cargados en la copia son:
  - `0`: Administrador
  - `1`: Dirección de Responsabilidad Social y Extensión Cultural Universitaria
  - `2`: Coordinador de Proyecto
  - `3`: Decanato de la Facultad
  - `4`: Dirección de Departamento
  - `5`: Comité de Responsabilidad Social de Facultad
- `usuarios` (p. ej. línea ~12156): contiene credenciales (`usuario`, `clave` en hash), datos personales y referencias (`id_rol`, `id_escuela`, `id_py`, `id_sede`, `id_depa`). La relación `fk_usuarios_rol` asegura que cada usuario apunte a uno de los tipos anteriores y existen registros con `id_rol` entre 1 y 5.

## Flujo de inicio de sesión y rutas por rol
- El formulario en `public_html/sistema_web/login.php` publica `usuario` y `clave` a `public_html/sistema_web/componentes/sesion/validarSesion.php`.
- `validarSesion.php` (líneas ~1-70) consulta la tabla `usuarios`, usa `password_verify()` contra el `hash` almacenado y guarda `id_rol`, nombres, apellidos, escuela/proyecto/sede/departamento en `$_SESSION`.
- Luego redirige según `id_rol`:
  - `1` ? `public_html/sistema_web/direccion_rsu/inicio.php`
  - `2` ? `public_html/sistema_web/inicio.php`
  - `3` ? `public_html/sistema_web/decanato_facultad/inicio.php`
  - `4` ? `public_html/sistema_web/director_departamento/inicio.php`
  - `5` ? `public_html/sistema_web/comite_facultad/inicio.php`
  - Roles sin regla (como `0` o nuevos) reciben `error=2` y vuelven a `login.php`.

Estas rutas definen la experiencia que cada rol tiene después de autenticarse.
