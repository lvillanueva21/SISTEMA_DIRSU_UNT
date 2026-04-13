# Manual rapido - Control de Proyectos

Ruta de interfaz: `sistema_web/direccion_rsu/control_proyectos.php`

Este modulo fue pensado para este flujo:

1. Gestionar periodos
2. Crear cronogramas
3. Crear formularios
4. Cargar items del formulario

---

## 1) Card: Gestion de periodos de trabajo

### Proposito
Crear y mantener los periodos base que luego usan los cronogramas.

### Tablas vinculadas
- `periodos`

### Campos que se llenan
- `nombre`
- `fecha_inicio`
- `fecha_fin`
- `activo` (1 activo / 0 inactivo)

### Como usarlo
1. Escribe nombre del periodo (ej: `2026-I`).
2. Ingresa fecha inicio y fecha fin.
3. Elige estado (activo o inactivo).
4. Click en `Guardar periodo`.
5. Para editar: boton lapiz en la tabla.
6. Para eliminar: boton papelera.

### Que evitar
- Fechas invertidas (`inicio > fin`).
- Nombres ambiguos o poco claros.
- Eliminar periodos que ya tienen cronogramas en uso (puede fallar por relacion en BD).

---

## 2) Card: Control de cronogramas de Presentacion y Revision

### Proposito
Definir ventanas de apertura/cierre para cada tipo de proceso por periodo.

### Tablas vinculadas
- `sm_cronogramas`
- `periodos`

### Campos que se llenan
- `tipo` (1 Presentacion, 2 Informe Semestral, 3 Otros)
- `id_periodo`
- `apertura` (datetime)
- `cierre` (datetime)
- `activo` (checkbox)

### Como usarlo
1. Selecciona tipo de cronograma.
2. Selecciona periodo.
3. Define apertura y cierre.
4. Marca `Activo` si debe quedar vigente.
5. Click en `Anadir`.
6. Usa filtro por periodo para revisar registros.
7. Edita desde la tabla (lapiz) y guarda.
8. Elimina desde la tabla (papelera) con confirmacion.

### Reglas importantes
- `apertura` debe ser menor que `cierre`.
- Si guardas uno como activo, el sistema desactiva otros del mismo `periodo + tipo`.
- El listado actual muestra los mas recientes (limitado en backend).

### Que evitar
- Crear cronogramas sin periodo.
- Dejar activos multiples para el mismo periodo y tipo.
- Usar fechas fuera de logica operativa del proceso.

---

## 3) Card: Administracion de formularios

### Proposito
Crear formularios que luego seran completados con items y usados en evaluacion/captura.

### Tablas vinculadas
- `sm_formularios`
- Relacion de consulta con `sm_cronogramas` y `periodos`

### Campos que se llenan
- `id_cronograma` (opcional, puede ser `Sin cronograma`)
- `nombre` (max 200)
- `descripcion` (opcional, max 1000)

### Como usarlo
1. (Opcional) elige cronograma activo.
2. Ingresa nombre del formulario.
3. Ingresa descripcion si aplica.
4. Click en `Crear`.
5. Si hay conflicto de cronograma repetido, el sistema muestra modal para reemplazar.
6. Desde tabla puedes:
   - Reasignar cronograma (editar)
   - Eliminar formulario

### Reglas importantes
- Se controla 1 formulario por cronograma (si ya existe, propone reemplazo).
- Permite formularios sin cronograma (vinculo NULL).

### Que evitar
- Nombres genericos (ej: "Formulario 1").
- Reemplazar formularios sin validar impacto en datos relacionados.
- Borrar formularios en uso sin respaldo previo.

---

## 4) Card: Items de formulario

### Proposito
Definir estructura de captura del formulario: campos, orden, tipo y adjuntos de apoyo.

### Tablas vinculadas
- `sm_items` (catalogo de item)
- `sm_formulario_items` (vinculo formulario-item y orden)
- `sm_formularios` (fuente del selector)

### Campos que se llenan
- `nombre` (item)
- `descripcion` (se guarda en `ejemplo`)
- `tipo` (varchar, longtext, tinyint, int, datetime, date, boolean, decimal, etc.)
- `orden`
- `link` (opcional)
- `video` (opcional)
- Archivos opcionales:
  - Imagen (`img_ruta`)
  - PDF (`pdf_ruta`)
  - Formato Word/Excel (`formato`)

### Como usarlo
1. Selecciona formulario.
2. Llena nombre, tipo y orden (obligatorios).
3. Completa descripcion/link/video si aplica.
4. Click `Anadir item`.
5. Para editar: boton lapiz en la tabla de items.
6. Para eliminar: boton papelera (desactiva el vinculo del item en ese formulario).
7. Para subir archivos:
   - Primero guarda el item
   - Luego sube imagen/pdf/formato

### Reglas importantes
- No permite dos items activos con el mismo orden dentro del mismo formulario.
- Tipos de archivo permitidos:
  - Imagen: `jpg`, `jpeg`, `png`, `gif`, `webp`
  - PDF: `pdf`
  - Formato: `doc`, `docx`, `xls`, `xlsx`
- Tamano maximo depende de `upload_max_filesize` y `post_max_size`.

### Que evitar
- Repetir orden de item.
- Subir archivos no permitidos o muy pesados.
- Subir archivo antes de guardar el item (el sistema lo bloquea).
- Usar links sin protocolo claro (preferir `https://`).

---

## Buenas practicas operativas

1. Crear primero periodos, luego cronogramas.
2. Crear formularios vinculados al cronograma activo correcto.
3. Definir items con orden consistente (1,2,3...).
4. Probar con un formulario de ensayo antes de pasar a produccion.
5. Antes de eliminar, validar si el dato ya esta siendo usado por otras vistas.
