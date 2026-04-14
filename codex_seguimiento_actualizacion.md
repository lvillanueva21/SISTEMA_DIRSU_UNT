# Seguimiento actualización servidor real

## Objetivo
Comparar `public_html` (versión final funcional) vs `public_html_server_real_por_actualizar` (versión antigua del servidor real) para identificar archivos de código/configuración a copiar manualmente.

## Criterios usados
- Comparación directa por ruta relativa y contenido (SHA-256).
- Inclusión principal: `.php`, `.js`, `.css`, `.html`, `.json`.
- Inclusión adicional relevante: `.htaccess`.
- Exclusión: multimedia, documentos, backups, logs, caché, temporales y archivos no funcionales.
- Verificación adicional de cambios no funcionales: normalización de fin de línea (CRLF/LF).

## Hallazgos confirmados
- Archivos funcionales en versión final: **2095**.
- Archivos funcionales en versión antigua: **2062**.
- Diferencias por hash:
  - Modificados: **1670**
  - Nuevos: **34**
  - Eliminados: **1**
- Refinamiento por contenido (ignorando solo fin de línea):
  - Modificados reales: **94**
  - Modificados solo EOL (sin cambio funcional): **1576**

## Archivos modificados
- Lista exacta (94 con cambio real):
  - `codex_archivos_modificados_cambio_real.txt`
  - versión agrupada: `codex_archivos_modificados_cambio_real_agrupado.md`
- Lista completa por hash (incluye solo-EOL):
  - `codex_archivos_modificados.txt`
- Lista solo EOL:
  - `codex_archivos_modificados_solo_eol.txt`

## Archivos nuevos
- Lista exacta (34):
  - `codex_archivos_nuevos.txt`
  - versión agrupada: `codex_archivos_nuevos_agrupado.md`

## Archivos con configuración sensible
- `/sistema_web/includes/config.php` (revisar credenciales DB, `base_url`, entorno `app_env/session_mode`)
- `/sistema_web/includes/db_connection.php` (revisar conexión/charset/timezone en función de servidor real)
- `/sistema_web/login_mantenimiento.php` (tiene `ADMIN_KEY` hardcodeada; cambiar o desactivar)
- `/sistema_web/semestral/logica/notificaciones_subsanacion_autoridades.php` (revisar credenciales SMTP/correo remitente)
- `/sistema_web/includes/.htaccess` (validar compatibilidad Apache 2.2/2.4 en servidor real)
- `/sistema_web/includes/api_dirsu/.htaccess` (validar reglas de acceso según hosting Apache)
- `/sistema_web/includes/api_dirsu/guard.php` (revisar modo `development/production`)
- `/sistema_web/includes/menu_matrix.php` (revisa ítems `dev_only` según `session_mode`)
- `/sistema_web/includes/sidebar.php` (rutas relativas dinámicas; validar subruta real)

## Dudas pendientes
- Si deseas copiar también archivos con cambio solo EOL (no funcional), técnicamente no es necesario.
- Revisar si archivos de prueba/legacy deben quedar en producción:
  - `/sistema_web/direccion_rsu/prueba.php`
  - `/sistema_web/direccion_rsu/prueba2.php`
  - `/sistema_web/direccion_rsu/prueba3.php`
  - `/sistema_web/direccion_rsu/prueba4.php`
  - `/sistema_web/direccion_rsu/console.php`
  - `/sistema_web/comite_facultad/inicio_antiguo.php`
  - `/sistema_web/decanato_facultad/inicio_antiguo.php`
  - `/sistema_web/direccion_rsu/inicio_antiguo.php`
  - `/sistema_web/director_departamento/inicio_antiguo.php`
  - `/sistema_web/includes/api_dirsu/mock.php`

## Decisiones tomadas
- Para actualización manual segura, tomar como base:
  - **94 archivos modificados reales** + **34 archivos nuevos**.
- Los **1576 archivos solo-EOL** se separan para no copiar de más.

## Estado actual del análisis
- **Completado**.
- Archivo eliminado detectado: `/sistema_web/login_real_retornar_luego.php`.
- Posibles renombres por hash idéntico: **0**.

---

## Análisis estructura BD (2026-04-14)

### Tablas revisadas
- Total tablas en backup final (prueba): 76
- Total tablas en backup antiguo (real): 76
- Tablas comparadas estructuralmente (CREATE TABLE): 76

### Diferencias confirmadas
- Tablas nuevas en final: 0
- Tablas faltantes en final (sobrantes en antigua): 0
- Cambios en columnas (nombre/tipo/longitud/null/default): 0
- Cambios en PK: 0
- Cambios en índices (KEY/UNIQUE/FULLTEXT): 0
- Cambios detectados: 13 tablas
  - 10 con diferencias en `FOREIGN KEY` por cláusulas `RESTRICT` explícitas vs implícitas (misma semántica habitual).
  - 1 con `CHECK` expresado distinto (`regexp` vs `regexp_like`) en `l2601_usuarios`.
  - 3 con diferencia de collation de tabla (`utf8mb3_uca1400_ai_ci` en final).

### Dudas detectadas
- `CHECK chk_dni_8dig` en `l2601_usuarios`: sintaxis difiere por versión de motor/exportador; validar versión MySQL/MariaDB antes de forzar recreación.
- Collation `utf8mb3_uca1400_ai_ci` en tablas `ubigeo_*`: puede no existir en servidores más antiguos.

### SQL propuesto
- Archivo generado: `codex_sql_igualar_estructura_db.sql`
- Contiene:
  - `ALTER TABLE ... DEFAULT CHARACTER SET ... COLLATE ...` para 3 tablas `ubigeo_*`.
  - Bloque opcional de alineación estricta de constraints (`DROP/ADD FOREIGN KEY` y `DROP/ADD CHECK`).

### Riesgos o consideraciones antes de ejecutar en producción
- Recomendación operativa: ejecutar primero SOLO cambios de collation si el motor soporta `utf8mb3_uca1400_ai_ci`.
- La recreación de FKs para quitar `RESTRICT` explícito no es obligatoria para compatibilidad funcional.
- `DROP CHECK` puede fallar según versión (MySQL vs MariaDB). Validar en staging.
- Hacer respaldo previo y ejecutar en ventana de mantenimiento.

---

## Diagnóstico acceso `direccion_rsu` (2026-04-14)

### Síntoma reportado
- Login exitoso, pero redirección automática a `login.php` al abrir páginas de `direccion_rsu`.
- Excepción: `control_proyectos.php` y `panel.php` sí abren.

### Hallazgo técnico confirmado
- Se detectó BOM UTF-8 (`EF BB BF`) al inicio de múltiples archivos `direccion_rsu`.
- Las 2 páginas que sí abrían (`control_proyectos.php`, `panel.php`) **no** tenían BOM.
- Este patrón es compatible con fallo de `session_start()`/headers en `configSesion.php` (con `error_reporting(0)` el warning queda oculto), provocando rebote a login.

### Acción aplicada
- Se eliminó BOM en 21 archivos de `public_html/sistema_web/direccion_rsu`.
- Verificación posterior: `BOM_REMAINING = 0` en ese directorio.

### Archivos corregidos (BOM eliminado)
- `/sistema_web/direccion_rsu/codigos.php`
- `/sistema_web/direccion_rsu/console.php`
- `/sistema_web/direccion_rsu/control_eventos.php`
- `/sistema_web/direccion_rsu/cotejo.php`
- `/sistema_web/direccion_rsu/data.php`
- `/sistema_web/direccion_rsu/estadistica.php`
- `/sistema_web/direccion_rsu/evaluacion.php`
- `/sistema_web/direccion_rsu/general.php`
- `/sistema_web/direccion_rsu/general2.php`
- `/sistema_web/direccion_rsu/guia.php`
- `/sistema_web/direccion_rsu/inicio.php`
- `/sistema_web/direccion_rsu/inicio_antiguo.php`
- `/sistema_web/direccion_rsu/progreso_proyectos.php`
- `/sistema_web/direccion_rsu/prueba.php`
- `/sistema_web/direccion_rsu/prueba2.php`
- `/sistema_web/direccion_rsu/prueba3.php`
- `/sistema_web/direccion_rsu/prueba4.php`
- `/sistema_web/direccion_rsu/red.php`
- `/sistema_web/direccion_rsu/reportes/crear_autoridad_ajax.php`
- `/sistema_web/direccion_rsu/rubrica.php`
- `/sistema_web/direccion_rsu/usuarios.php`

### Consideraciones
- Las diferencias DB entre real/prueba (FK `RESTRICT` explícito/implícito y collations `ubigeo`) no explican este síntoma de redirección selectiva por página.
- Recomendado: desplegar estos archivos corregidos y validar navegación de rol 1.

---

## Diagnóstico de codificación (mojibake/tildes) - 2026-04-14

### Objetivo
Identificar archivos en `public_html/sistema_web` con texto dañado (mojibake) y textos de UI sin tilde.

### Resultado técnico
- Detección global inicial de mojibake en `sistema_web`: 20,896 líneas / 1,234 archivos (incluye librerías).
- Detección enfocada (sin vendor como `plogins`, `dust`, `recursos`, etc.): 6,912 líneas / 355 archivos.
- Archivos foco UI (módulos funcionales): 254 archivos con coincidencias.

### Evidencia de UI afectada (ejemplos)
- `direccion_rsu/inicio.php`: `sesiÃ³n`, `pÃ¡gina`, `Ãrea`, `Â©`
- `direccion_rsu/general.php`: `sesiÃ³n`, `pÃ¡gina`, `Ãrea`, `Â©`
- `direccion_rsu/red.php`: `sesiÃ³n`, `pÃ¡gina`, `Ãrea`, `Â©`
- `direccion_rsu/control_proyectos.php`: textos sin tilde (`sesion`, `pagina`, `Administracion`, `Gestion`)

### Archivos auxiliares generados
- `codex_mojibake_hits_sistema_web.json` (líneas exactas detectadas)
- `codex_no_tilde_suspects_sistema_web.json` (sospechosos de tilde faltante)
- `codex_mojibake_files_foco_conteo.txt` (rutas + conteo por archivo)
- `codex_mojibake_ui_confirmados.txt` (rutas de UI con mojibake confirmado)
- `codex_sin_tilde_ui_sospechosos.txt` (rutas UI sin tilde probable)

### Consideración
Conviene corregir primero `direccion_rsu` + `includes` + `inicio` y luego extender a `vistas`, `evaluacion`, `semestral`, `presentacion`.

---

## Corrección final de mojibake/tildes aplicada (2026-04-14)

### Objetivo
Dejar los archivos detectados sin mojibake ni signos `?` en textos de interfaz para despliegue en prueba y producción.

### Hallazgos corregidos (confirmados)
- Se corrigieron textos con `?` en UI/API en 7 archivos clave.
- Se normalizaron comentarios con símbolos extraños en `estadistica.php`.
- Se detectó y revirtió un riesgo en librerías de terceros (`plogins`) donde expresiones regex habían quedado alteradas.

### Archivos corregidos directamente
- `/sistema_web/direccion_rsu/control_proyectos.php`
- `/sistema_web/includes/api_dirsu/index.php`
- `/sistema_web/includes/api_dirsu/guard_api.php`
- `/sistema_web/direccion_rsu/panel.php`
- `/sistema_web/direccion_rsu/funciones/card_gestion_periodos.php`
- `/sistema_web/direccion_rsu/estadistica.php`
- `/sistema_web/direccion_rsu/console.php`

### Librerías restauradas (para evitar regresiones)
- `/sistema_web/plogins/codemirror/mode/clike/clike.js`
- `/sistema_web/plogins/codemirror/mode/handlebars/handlebars.js`
- `/sistema_web/plogins/codemirror/mode/rst/rst.js`
- `/sistema_web/plogins/dropzone/min/dropzone-amd-module.min.js`
- `/sistema_web/plogins/dropzone/min/dropzone.min.js`
- `/sistema_web/plogins/jquery-validation/additional-methods.js`
- `/sistema_web/plogins/jquery-validation/additional-methods.min.js`
- `/sistema_web/plogins/pdfmake/pdfmake.min.js`

### Verificación técnica
- Patrón mojibake (`Ã`, `Â`, `�`) en rutas objetivo: **0 hallazgos**.
- Patrón de tildes rotas con `?` en rutas objetivo: **0 hallazgos**.
- Validación `php -l`: **no ejecutable en este entorno local** (comando `php` no disponible en PATH).

### Estado actual
- Corrección de codificación y tildes en rutas detectadas: **completada**.
- Listo para subir archivos corregidos a servidor de prueba y servidor real.

### Ajuste puntual de menús/submenús (2026-04-14)
- Archivo corregido: `/sistema_web/includes/menu_matrix.php`
- Se normalizaron labels visibles con tildes y redacción en español:
  - `Evaluación`, `Información`, `Guía`, `Formulación`, `presentación`, `Ejecución`, `Revisión`, `Rúbrica`, `Códigos`, `Analítica`.
- También se corrigió texto de encabezado/comentarios (`menús`, `Dirección`, `Comité`) y `API DIRSU`.
- Verificación: archivo sin BOM (`BOM_OK`).
