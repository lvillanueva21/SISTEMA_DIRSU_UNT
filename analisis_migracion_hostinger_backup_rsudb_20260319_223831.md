# Analisis de migracion: MySQL 8.0.43 -> MariaDB 11.8.3 (Hostinger)

Fecha del analisis: 2026-03-21
Archivo analizado: `backup_rsudb_20260319_223831.sql`
Ubicacion: `E:\GITHUB CLONES\LIVP_RSU_UNT`

## Resumen ejecutivo

Resultado: **la migracion del dump actual no termina completa en Hostinger**.

Bloqueos confirmados:

1. Incompatibilidad SQL entre MySQL 8 y MariaDB en `CHECK` de `l2601_usuarios` por uso de `regexp_like()`.
2. Error de exportacion en `v_estado_proyecto`: el dump elimina el objeto, pero no incluye `CREATE VIEW` ni `CREATE TABLE`, y luego intenta insertar datos.

Conclusion rapida:

- El problema principal es de compatibilidad SQL y del exportador, no de PHP.
- En hosting compartido no puedes convertir MariaDB 11.8 en MySQL 8 "por dominio".
- Si corriges 2 puntos del dump, el cambio en codigo PHP puede ser minimo o nulo.

---

## Hallazgos confirmados en el dump

## 1) Falla fatal #1 (confirmada): `regexp_like()` dentro de CHECK

Referencia en dump:

- Linea `5221`: inicio de `CREATE TABLE l2601_usuarios`
- Linea `5237`: `CONSTRAINT chk_dni_8dig CHECK (regexp_like(...))`

Fragmento conflictivo:

```sql
CONSTRAINT `chk_dni_8dig` CHECK (regexp_like(`dni`,_utf8mb4'^[0-9]{8}$'))
```

En MariaDB 11.8.3 (tu Hostinger) `REGEXP_LIKE()` no existe, por eso aparece `#1901`.

Adaptacion compatible minima:

```sql
CONSTRAINT `chk_dni_8dig` CHECK (`dni` REGEXP '^[0-9]{8}$')
```

---

## 2) Falla fatal #2 (confirmada): objeto `v_estado_proyecto` exportado de forma incompleta

Referencia en dump:

- Linea `12610`: `DROP TABLE IF EXISTS v_estado_proyecto;`
- Linea `12611`: solo `;` (no hay `CREATE TABLE` ni `CREATE VIEW`)
- Linea `12614+`: `INSERT INTO v_estado_proyecto ...`

Esto produce error posterior (tipicamente `#1146 Table ... doesn't exist`) cuando el importador llega a esas lineas.

Impacto:

- Aunque soluciones `l2601_usuarios`, el dump volvera a fallar al final por `v_estado_proyecto`.
- Ademas, el sistema usa `v_estado_proyecto` en codigo (ejemplo: `public_html/sistema_web/modulos/Merp/MerpEngine.php`, linea 59).

---

## Hasta donde avanza la importacion con el dump actual

Orden detectado de objetos `CREATE TABLE`: 76.

Punto de corte principal:

- Tabla #41: `l2601_usuarios` (linea 5221)
- Si el importador se detiene al primer error (comportamiento normal), quedaran importadas solo las 40 tablas anteriores (hasta `l2601_roles`).

Si corriges ese punto y vuelves a importar:

- El siguiente corte probable es al llegar a `INSERT INTO v_estado_proyecto` (linea 12614).

---

## Compatibilidad general detectada (ademas de lo anterior)

## Compatible en tu destino (segun pruebas y dump)

- `CHECK` constraints: si, con expresiones compatibles de MariaDB.
- `REGEXP` clasico: si.
- Collations usadas en dump: `utf8mb4_unicode_ci`, `utf8mb4_spanish_ci`, `utf8mb4_0900_ai_ci` (reportadas disponibles en tu host).
- Engine principal: InnoDB.

## No compatible en tu destino

- `REGEXP_LIKE()`.

## No se detectaron en este dump

- Triggers
- Procedures / Functions
- `CREATE VIEW` valido
- Clausulas `DEFINER` (en el dump actual)

---

## Se puede "adaptar" Hostinger para comportarse como tu servidor MySQL 8?

Respuesta corta: **no de forma real en hosting compartido**.

Limitaciones practicas:

- El motor ya esta fijado a MariaDB 11.8 por proveedor.
- No tienes control root para cambiar a MySQL 8 por cuenta o por dominio.
- Funciones no existentes (`REGEXP_LIKE`) no se habilitan con `sql_mode`.

Alternativas reales:

1. Adaptar dump para MariaDB (recomendado para tu caso, cambio minimo).
2. Migrar a VPS donde instales MySQL 8.0.x si necesitas paridad 1:1 con origen.

---

## Correccion minima recomendada (sin rehacer codigo del sistema)

1. Reemplazar en dump:

```sql
CHECK (regexp_like(`dni`,_utf8mb4'^[0-9]{8}$'))
```

por

```sql
CHECK (`dni` REGEXP '^[0-9]{8}$')
```

2. Corregir el bloque de `v_estado_proyecto`:

- No insertar datos en una vista.
- Exportar su definicion real con `SHOW CREATE VIEW v_estado_proyecto` desde origen.
- En dump destino usar `DROP VIEW IF EXISTS` + `CREATE VIEW ...`.

3. Ajustar el exportador `consultas.php` para futuras copias:

- No usar solo `SHOW TABLES`; usar `SHOW FULL TABLES` para distinguir BASE TABLE vs VIEW.
- Para BASE TABLE: `SHOW CREATE TABLE` + (opcional) INSERTs.
- Para VIEW: `SHOW CREATE VIEW`; no exportar INSERTs de la vista.

---

## Diagnostico final

Tu preocupacion es valida, pero el escenario tiene solucion con cambios puntuales:

- No necesitas rehacer "muchas lineas" de tu app PHP.
- Necesitas corregir el SQL de exportacion/importacion en 2 frentes puntuales.
- El bloqueo no se debe a configuracion de PHP.

Con esos ajustes, la migracion completa es factible en MariaDB 11.8 de Hostinger.
