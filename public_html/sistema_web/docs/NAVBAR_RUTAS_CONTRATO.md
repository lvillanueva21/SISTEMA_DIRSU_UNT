# Contrato de Rutas del Navbar (Definitivo)

Este documento define la regla única para evitar roturas de navegación al mover `sistema_web` entre dominios, subdominios o subcarpetas.

## Principio base

- El navegador siempre resuelve `href` según la URL actual.
- Por eso, no se deben dejar enlaces ambiguos dependientes de la carpeta activa.
- El sidebar transforma cada enlace del menú a una ruta canónica interna de `sistema_web` y luego la vuelve relativa al archivo actual.

## Reglas obligatorias para `menu_matrix.php`

1. Usar `href_dynamic` cuando el destino ya se conoce como ruta interna canónica.
2. Si se usa `href`, entender que se interpreta respecto a `menu_context_dir` del rol.
3. Cuando exista un caso especial por página concreta, usar:
   - `href_by_app_path` (preferido), por ejemplo: `semestral/index.php`.
4. Dejar `href_by_page` solo para compatibilidad legacy.
5. Evitar depender de claves ambiguas como `index.php` si hay múltiples `index.php` en el sistema.

## Orden de resolución (sidebar)

1. `href_dynamic`
2. `href_by_app_path`
3. `href_by_page`
4. `href`

Luego:

1. Se convierte a ruta canónica interna (`app_path`).
2. Se genera un `href` relativo correcto desde la página actual.

## Política de portabilidad

1. Prohibido hardcodear dominio (`https://...`) para navegación interna.
2. Prohibido usar rutas absolutas desde raíz (`/sistema_web/...`) como regla general.
3. Toda navegación interna debe sobrevivir a despliegues en:
   - `dominio.pe/`
   - `sub.dominio.pe/`
   - `dominio.pe/carpeta/`

## Checklist antes de agregar un ítem de menú

1. Definir `menu_context_dir` correcto del rol.
2. Probar el enlace desde:
   - la página principal del rol
   - `lista_proyectos/coordinador.php` (rol 2)
   - `lista_proyectos/evaluador.php` (roles evaluadores)
3. Si falla en una ubicación concreta, agregar `href_by_app_path` (no `href_by_page` ambiguo).
4. Verificar submenús (`tree`) del rol.

## Nota de mantenimiento

- Si se crea una nueva interfaz con nombre repetido (`index.php`, `principal.php`, etc.), no usar solo basename para casos especiales.
- Preferir siempre `app_path` completo para desambiguar.
