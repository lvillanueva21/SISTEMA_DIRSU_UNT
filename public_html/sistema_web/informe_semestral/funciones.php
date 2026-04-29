<?php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../includes/db_connection.php';
include_once __DIR__ . '/control_oficinas.php';

function testeo()
{
    global $conexion;

    $id_rol     = isset($_SESSION['id_rol']) ? (int) $_SESSION['id_rol'] : 0;
    $usuario    = isset($_SESSION['usuario']) ? $_SESSION['usuario'] : '';
    $id_escuela = isset($_SESSION['id_escuela']) ? (int) $_SESSION['id_escuela'] : 0;
    $id_depa    = isset($_SESSION['id_depa']) ? (int) $_SESSION['id_depa'] : 0;

    $rol_nombre = 'Rol no identificado';

    $sql = "SELECT nombre FROM rol WHERE id = $id_rol LIMIT 1";
    if ($rs = mysqli_query($conexion, $sql)) {
        if ($fila = mysqli_fetch_assoc($rs)) {
            $rol_nombre = $fila['nombre'];
        }
        mysqli_free_result($rs);
    }

    return [
        'rol'        => $rol_nombre,
        'usuario'    => $usuario,
        'id_rol'     => $id_rol,
        'id_escuela' => $id_escuela,
        'id_depa'    => $id_depa,
    ];
}

/* ===================== CATÃLOGOS PARA LOS FILTROS ===================== */

function obtenerFacultades(): array
{
    global $conexion;
    $out = [];
    $rs = mysqli_query($conexion, "SELECT id, nombre FROM facultades WHERE id <> 0 ORDER BY nombre ASC");
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) $out[(int)$r['id']] = $r['nombre'];
        mysqli_free_result($rs);
    }
    return $out;
}

function obtenerDepartamentos(int $id_facultad = 0): array
{
    global $conexion;
    $out = [];
    $id_facultad = (int)$id_facultad;
    $sql = "SELECT id, nombre FROM departamentos";
    if ($id_facultad > 0) $sql .= " WHERE id_facultad = $id_facultad";
    $sql .= " ORDER BY nombre ASC";
    $rs = mysqli_query($conexion, $sql);
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) $out[(int)$r['id']] = $r['nombre'];
        mysqli_free_result($rs);
    }
    return $out;
}

function obtenerPeriodos(): array
{
    global $conexion;
    $out = [];
    $rs = mysqli_query($conexion, "SELECT id, nombre FROM periodos ORDER BY nombre DESC, id DESC");
    if ($rs) {
        while ($r = mysqli_fetch_assoc($rs)) $out[(int)$r['id']] = $r['nombre'];
        mysqli_free_result($rs);
    }
    return $out;
}

function periodoNombrePorId(int $id_periodo): string
{
    global $conexion;
    if ($id_periodo <= 0) return '';

    $sql = "SELECT nombre FROM periodos WHERE id = $id_periodo LIMIT 1";
    $rs = mysqli_query($conexion, $sql);
    if ($rs && ($row = mysqli_fetch_assoc($rs))) {
        $nom = (string)($row['nombre'] ?? '');
        mysqli_free_result($rs);
        return $nom;
    }
    if ($rs) mysqli_free_result($rs);
    return '';
}

function sqlFiltroPeriodoSemestral(int $id_periodo, string $sem_alias = 's'): string
{
    if ($id_periodo <= 0) return '';
    return " AND EXISTS (
                SELECT 1
                FROM periodos prf
                WHERE prf.id = $id_periodo
                  AND prf.nombre = CONCAT($sem_alias.anio, '-', $sem_alias.periodo)
            ) ";
}

function sqlUltimaRespuestaSemestralPorProyecto(int $id_periodo = 0): string
{
    $extra = sqlFiltroPeriodoSemestral($id_periodo, 'ss');
    return "(
              SELECT rr.id
              FROM sm_respuestas rr
              JOIN sm_proyecto_semestres ss
                ON ss.id = rr.id_semestre
               AND ss.tipo = 'semestral'
               AND COALESCE(ss.vigente, 1) = 1
              WHERE rr.id_py = p.id
              $extra
              ORDER BY rr.actualizado_at DESC, rr.id DESC
              LIMIT 1
            )";
}

function badgeClaseEstadoOficina(string $txt): string {
  $t = mb_strtolower(trim($txt), 'UTF-8');

  // Estados globales â€” se mantienen igual
  if ($t === 'aprobaciÃ³n total')      return 'badge badge-success bg-success';
  if ($t === 'sin informe semestral') return 'badge badge-secondary bg-secondary';
  if ($t === 'no solicitÃ³ revisiÃ³n')  return 'badge badge-warning bg-warning text-dark';
  if ($t === 'â€”' || $t === '')        return 'badge badge-light bg-light text-muted';

  // Oficinas â€” detecta por nombre/abreviatura, sin depender de un 2Âº parÃ¡metro
  if (preg_match('/\bpcf\b|comit[Ã©e]\s*de\s*facultad/i', $txt)) return 'badge badge-ofic-pcf';
  if (preg_match('/\bdd\b|departamento/i', $txt))                return 'badge badge-ofic-dd';
  if (preg_match('/\bdf\b|decan/i', $txt))                       return 'badge badge-ofic-df';
  if (preg_match('/\brsu\b/i', $txt))                            return 'badge badge-ofic-rsu';

  // Cualquier otra oficina (fallback neutro)
  return 'badge badge-info bg-info text-dark';
}

function badgeClaseSubEstado(string $txt): string {
  $t = mb_strtolower(trim($txt), 'UTF-8');
  if ($t === 'observado')  return 'badge badge-danger bg-danger';
  if ($t === 'en espera')  return 'badge badge-primary bg-primary text-white'; // <- aquÃ­
  return 'badge badge-light bg-light text-muted';
}

/** Rol â€œhumanoâ€ que debe calificar, segÃºn cÃ³digo de oficina */
function rolCalificadorPorCodigo(?string $cod): string {
  $c = strtoupper(trim((string)$cod));
  switch ($c) {
    case 'PCF': return 'Presidente de ComitÃ© de Facultad';
    case 'DD':  return 'Director de Departamento AcadÃ©mico';
    case 'DF':  return 'Decano de Facultad';
    case 'RSU': return 'Director de RSU';
    default:    return 'responsable';
  }
}

/* ===================== WHERE POR ROL + FILTROS ===================== */

function whereFiltroPorRol(array $usr)
{
    global $conexion;

    $id_rol = (int) $usr['id_rol'];

    // Admin (0) y RSU (1): sin filtro
    if ($id_rol === 0 || $id_rol === 1) {
        return '';
    }

    // Decanato (3) y ComitÃ© (5): facultad = id_escuela del usuario
    if ($id_rol === 3 || $id_rol === 5) {
        $fac = (int) $usr['id_escuela'];
        return ($fac > 0) ? " AND f.id = $fac " : " AND 1=0 ";
    }

    // DirecciÃ³n de Departamento (4): departamento = id_depa del usuario
    if ($id_rol === 4) {
        $depa = (int) $usr['id_depa'];
        return ($depa > 0) ? " AND d.id = $depa " : " AND 1=0 ";
    }

    // Coordinador (2): solo sus proyectos (por cÃ³digo docente u.usuario)
    if ($id_rol === 2) {
        $cod = isset($usr['usuario']) ? mysqli_real_escape_string($conexion, $usr['usuario']) : '';
        return ($cod !== '') ? " AND u.usuario = '$cod' " : " AND 1=0 ";
    }

    return '';
}

/**
 * WHERE adicional por filtros de UI.
 * $filtros: ['facultad','departamento','periodo','revision','q']
 * NOTA: Conserva el filtro "RevisiÃ³n" para segmentar por sm_respuestas (estado del Informe),
 * NO tiene relaciÃ³n con la evaluaciÃ³n V3.
 */
function whereFiltros(array $filtros): string
{
    global $conexion;
    $w = '';

    $fac = isset($filtros['facultad']) ? (int)$filtros['facultad'] : 0;
    if ($fac > 0) $w .= " AND f.id = $fac ";

    $dep = isset($filtros['departamento']) ? (int)$filtros['departamento'] : 0;
    if ($dep > 0) $w .= " AND d.id = $dep ";

    $per = isset($filtros['periodo']) ? (int)$filtros['periodo'] : 0;
    if ($per > 0) {
        $w .= " AND EXISTS (
                    SELECT 1
                    FROM proyectos_periodo ppf
                    WHERE ppf.id_py = p.id
                      AND ppf.id_periodo = $per
                ) ";
    }

    $rev = isset($filtros['revision']) ? trim((string)$filtros['revision']) : '';
    if ($rev === 'sin') {
        $extraPer = sqlFiltroPeriodoSemestral($per, 'sx');
        // Proyectos sin informe semestral en el contexto (periodo o total)
        $w .= " AND NOT EXISTS (
                    SELECT 1
                    FROM sm_respuestas rx
                    JOIN sm_proyecto_semestres sx
                      ON sx.id = rx.id_semestre
                     AND sx.tipo = 'semestral'
                     AND COALESCE(sx.vigente, 1) = 1
                    WHERE rx.id_py = p.id
                    $extraPer
                ) ";
    } elseif ($rev !== '') {
        $revInt = (int)$rev;
        $extraPer = sqlFiltroPeriodoSemestral($per, 'sx');
        // Ãšltimo estado del informe semestral en contexto (periodo o total)
        $w .= " AND COALESCE((
                    SELECT r.estado
                    FROM sm_respuestas r
                    JOIN sm_proyecto_semestres sx
                      ON sx.id = r.id_semestre
                     AND sx.tipo = 'semestral'
                     AND COALESCE(sx.vigente, 1) = 1
                    WHERE r.id_py = p.id
                    $extraPer
                    ORDER BY r.actualizado_at DESC, r.id DESC
                    LIMIT 1
                ), -1) = $revInt ";
    }
    // Si $rev === '' => Todos

    $q = isset($filtros['q']) ? trim((string)$filtros['q']) : '';
    if ($q !== '') {
        $s = mysqli_real_escape_string($conexion, $q);
        $num = ctype_digit($q) ? (int)$q : 0;
        $w .= " AND (
            p.p2 LIKE '%$s%' COLLATE utf8mb4_general_ci
            OR CONCAT(u.nombres,' ',u.apellidos) LIKE '%$s%' COLLATE utf8mb4_general_ci
            OR u.usuario LIKE '%$s%' COLLATE utf8mb4_general_ci
            ".($num>0 ? " OR p.id = $num " : "")."
        ) ";
    }

    return $w;
}

function whereTieneInforme(array $filtros): string
{
    $tiene = isset($filtros['tiene_informe']) ? strtolower(trim((string)$filtros['tiene_informe'])) : '';
    $per   = isset($filtros['periodo']) ? (int)$filtros['periodo'] : 0;
    if ($tiene === '') return '';

    $extraPer = sqlFiltroPeriodoSemestral($per, 'sx');
    $existsSql = " EXISTS (
                      SELECT 1
                      FROM sm_respuestas rx
                      JOIN sm_proyecto_semestres sx
                        ON sx.id = rx.id_semestre
                       AND sx.tipo = 'semestral'
                       AND COALESCE(sx.vigente, 1) = 1
                      WHERE rx.id_py = p.id
                      $extraPer
                   ) ";

    if ($tiene === 'si' || $tiene === '1' || $tiene === 'yes') {
        return " AND $existsSql ";
    }
    if ($tiene === 'no' || $tiene === '0') {
        return " AND NOT ($existsSql) ";
    }

    return '';
}

/**
 * WHERE adicional por filtro "Estado / Oficina" (select 'oficina' en codigos.php)
 * Valores esperados:
 *  - 'PCF', 'DD', 'DF', 'RSU'      => oficina activa especÃ­fica (e/o join)
 *  - 'APROB'                       => AprobaciÃ³n Total
 *  - 'SIN'                         => Sin Estado / Oficina (sin informe / no solicitÃ³ / sin oficina activa)
 *  - ''                            => Todos
 *
 * IMPORTANTE:
 *  Este filtro se apoya en los alias de JOIN que ya existen en proyectosListado (r0, e, o).
 *  Para totalProyectos hemos aÃ±adido los mismos JOINs mÃ­nimos (r0, e, o) para que cuente bien.
 */
function whereOficina(array $filtros): string
{
    global $conexion;
    $w  = '';
    $of = isset($filtros['oficina']) ? trim((string)$filtros['oficina']) : '';

    if ($of === '') return $w;

    if ($of === 'APROB') {
        // AprobaciÃ³n Total
        $w .= " AND e.situacion = 'aprobado' ";
        return $w;
    }

    if ($of === 'SIN') {
        // Sin estado/oficina (coincide con la lÃ³gica de la CASE de estado_oficina en proyectosListado)
        $w .= " AND (
                   r0.id IS NULL
                OR (e.id IS NULL AND COALESCE(r0.estado,0)=0)
                OR (e.id_oficina_actual IS NULL AND (e.situacion IS NULL OR e.situacion <> 'aprobado'))
            ) ";
        return $w;
    }

    // Oficinas especÃ­ficas por cÃ³digo (PCF, DD, DF, RSU)
    $safe = mysqli_real_escape_string($conexion, $of);
    $w   .= " AND e.id_oficina_actual IS NOT NULL AND o.codigo = '{$safe}' ";
    return $w;
}

/* ===================== TOTAL Y LISTA (SIN LÃ“GICA DE EVALUACIÃ“N) ===================== */

function totalProyectos(array $usr, array $filtros = [])
{
    global $conexion;

    $whereRol      = whereFiltroPorRol($usr);
    $whereExtras   = whereFiltros($filtros);
    $whereTiene    = whereTieneInforme($filtros);
    $whereOficina  = whereOficina($filtros);
    $per           = isset($filtros['periodo']) ? (int)$filtros['periodo'] : 0;
    $latestRespSql = sqlUltimaRespuestaSemestralPorProyecto($per);

    $sql = " SELECT COUNT(DISTINCT p.id) AS total
             FROM proyectos p
             LEFT JOIN usuarios_proyectos up
                    ON up.id_proyecto = p.id
                   AND up.activo = 1
             LEFT JOIN usuarios u
                    ON u.id = up.id_usuario
                   AND u.id_rol = 2
             LEFT JOIN departamentos d
                    ON d.id = u.id_depa
             LEFT JOIN facultades f
                    ON f.id = d.id_facultad

             -- Ãšltima respuesta del proyecto (r0)
             LEFT JOIN sm_respuestas r0
                 ON r0.id = $latestRespSql

             -- EvaluaciÃ³n + oficina actual (e/o)
             LEFT JOIN eva_evaluaciones e
                 ON e.id_respuesta = r0.id
             LEFT JOIN eva_oficinas o
                 ON o.id = e.id_oficina_actual

             WHERE 1=1 $whereRol $whereExtras $whereTiene $whereOficina ";

    $res = mysqli_query($conexion, $sql);
    if (!$res) return 0;

    $row = mysqli_fetch_assoc($res);
    mysqli_free_result($res);
    return (int)($row['total'] ?? 0);
}

function proyectosListado($pagina = 1, $por_pagina = 20, array $usr = [], array $filtros = [])
{
    global $conexion;

    $offset        = ($pagina - 1) * $por_pagina;
    $data          = [];
    $whereRol      = whereFiltroPorRol($usr);
    $whereExtras   = whereFiltros($filtros);
    $whereTiene    = whereTieneInforme($filtros);
    $whereOficina  = whereOficina($filtros);
    $per           = isset($filtros['periodo']) ? (int)$filtros['periodo'] : 0;
    $latestRespSql = sqlUltimaRespuestaSemestralPorProyecto($per);
    $periodoFiltroNombre = periodoNombrePorId($per);

    $sql = " SELECT
                 p.id AS id_py,
                 p.p2 AS titulo,
                 u.nombres,
                 u.apellidos,
                 u.usuario  AS cod_docente,
                 d.nombre   AS nombre_departamento,
                 f.nombre   AS nombre_facultad,
                 COALESCE(
                    CONCAT(s0.anio, '-', s0.periodo),
                    " . ($periodoFiltroNombre !== '' ? ("'" . mysqli_real_escape_string($conexion, $periodoFiltroNombre) . "'") : "NULL") . ",
                    (
                      SELECT prx.nombre
                      FROM proyectos_periodo ppx
                      JOIN periodos prx ON prx.id = ppx.id_periodo
                      WHERE ppx.id_py = p.id
                      ORDER BY prx.nombre DESC, prx.id DESC
                      LIMIT 1
                    ),
                    'No definido'
                 ) AS nombre_periodo,

                 -- Ãšltima respuesta del proyecto
                 r0.id      AS resp_id,
                 r0.estado  AS resp_estado,

                 -- EvaluaciÃ³n (si iniciÃ³ ruta) + oficina actual
                 e.situacion,
                 o.codigo   AS oficina_cod,
                 o.nombre   AS oficina_nom,

                 -- Ãšltima instancia de esa oficina para la evaluaciÃ³n actual
                 oi.estado  AS instancia_estado,
                 oi.llegada AS instancia_llegada,
                 oi.salida  AS instancia_salida,

                 -- Estados de calificaciones en la oficina actual (con fechas)
                 cj.estado  AS cotejo_estado,
                 cj.actualizado_at AS cotejo_at,

                 rb.estado  AS rubrica_estado,
                 rb.actualizado_at AS rubrica_at,

                 vb.estado  AS vb_estado,
                 vb.actualizado_at AS vb_at,

                 -- Label principal (columna 'Estado / Oficina')
                 CASE
                   WHEN e.situacion = 'aprobado'                   THEN 'AprobaciÃ³n Total'
                   WHEN e.id_oficina_actual IS NOT NULL            THEN o.nombre
                   WHEN r0.id IS NULL                              THEN 'Sin Informe Semestral'
                   WHEN e.id IS NULL AND COALESCE(r0.estado,0)=0   THEN 'No solicitÃ³ RevisiÃ³n'
                   ELSE 'â€”'
                 END AS estado_oficina,

                 -- Sub-estado visible como segundo label (si corresponde)
                 CASE
                   WHEN e.situacion='aprobado' THEN NULL
                   WHEN e.id_oficina_actual IS NOT NULL THEN
                     CASE oi.estado
                       WHEN 'observado'  THEN 'Observado'
                       WHEN 'en_espera'  THEN 'En Espera'
                       WHEN 'aprobado'   THEN NULL
                       ELSE NULL
                     END
                   ELSE NULL
                 END AS estado_sub,

                 -- Fecha/hora a mostrar bajo el label (regla: calificaciÃ³n > instancia)
                 CASE
                   WHEN e.situacion='aprobado' THEN e.actualizado_at
                   WHEN e.id_oficina_actual IS NOT NULL THEN
                     CASE oi.estado
                       WHEN 'observado' THEN COALESCE(
                         CASE WHEN cj.estado='observado' THEN cj.actualizado_at END,
                         CASE WHEN rb.estado='observado' THEN rb.actualizado_at END,
                         CASE WHEN vb.estado='observado' THEN vb.actualizado_at END,
                         oi.ultima_observacion_at
                       )
                       WHEN 'en_espera' THEN oi.llegada
                       WHEN 'aprobado'  THEN oi.salida
                       ELSE NULL
                     END
                   ELSE NULL
                 END AS estado_dt

             FROM proyectos p
             LEFT JOIN usuarios_proyectos up
                 ON up.id_proyecto = p.id
                AND up.activo = 1
             LEFT JOIN usuarios u
                 ON u.id = up.id_usuario
                AND u.id_rol = 2
             LEFT JOIN departamentos d
                 ON d.id = u.id_depa
             LEFT JOIN facultades f
                 ON f.id = d.id_facultad

             -- Ãšltima respuesta del proyecto
             LEFT JOIN sm_respuestas r0
                 ON r0.id = $latestRespSql
             LEFT JOIN sm_proyecto_semestres s0
                 ON s0.id = r0.id_semestre

             -- EvaluaciÃ³n (si iniciÃ³ ruta) + oficina actual
             LEFT JOIN eva_evaluaciones e
                 ON e.id_respuesta = r0.id
             LEFT JOIN eva_oficinas o
                 ON o.id = e.id_oficina_actual

             -- Ãšltima instancia de esa oficina para la evaluaciÃ³n actual
             LEFT JOIN (
                 SELECT id_evaluacion, id_oficina, MAX(id) AS last_id
                 FROM eva_oficina_instancias
                 GROUP BY id_evaluacion, id_oficina
             ) lastoi
               ON lastoi.id_evaluacion = e.id
              AND lastoi.id_oficina    = e.id_oficina_actual
             LEFT JOIN eva_oficina_instancias oi
               ON oi.id = lastoi.last_id

             -- Calificaciones en oficina actual (cotejo / rÃºbrica / visto bueno)
             LEFT JOIN eva_calificaciones cj
               ON cj.id_evaluacion = e.id AND cj.id_oficina = e.id_oficina_actual AND cj.tipo='cotejo'
             LEFT JOIN eva_calificaciones rb
               ON rb.id_evaluacion = e.id AND rb.id_oficina = e.id_oficina_actual AND rb.tipo='rubrica'
             LEFT JOIN eva_calificaciones vb
               ON vb.id_evaluacion = e.id AND vb.id_oficina = e.id_oficina_actual AND vb.tipo='vistobueno'

             WHERE 1=1 $whereRol $whereExtras $whereTiene $whereOficina
             ORDER BY p.p2 ASC
             LIMIT $por_pagina OFFSET $offset ";

    $rs = mysqli_query($conexion, $sql);
    if (!$rs) {
        return $data;
    }

    while ($row = mysqli_fetch_assoc($rs)) {
        $data[] = [
            'id_py'            => (int)$row['id_py'],
            'titulo'           => $row['titulo'] ?? '',
            'periodo'          => $row['nombre_periodo'] ?? 'No definido',
            'coordinador'      => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
            'cod_docente'      => $row['cod_docente'] ?? 'Sin cÃ³digo',
            'facultad'         => $row['nombre_facultad'] ?? 'No registrada',
            'departamento'     => $row['nombre_departamento'] ?? 'No registrado',

            // sm_respuestas
            'resp_id'          => isset($row['resp_id']) ? (int)$row['resp_id'] : null,
            'resp_estado'      => isset($row['resp_estado']) ? (int)$row['resp_estado'] : null,

            // evaluaciÃ³n/oficina
            'situacion'        => $row['situacion'] ?? null,
            'oficina_cod'      => $row['oficina_cod'] ?? null,
            'oficina_nom'      => $row['oficina_nom'] ?? null,
            'instancia_estado' => $row['instancia_estado'] ?? null,
            'instancia_llegada'=> $row['instancia_llegada'] ?? null,
            'instancia_salida' => $row['instancia_salida'] ?? null,

            // calificaciones por tipo
            'cotejo_estado'    => $row['cotejo_estado'] ?? null,
            'cotejo_at'        => $row['cotejo_at'] ?? null,
            'rubrica_estado'   => $row['rubrica_estado'] ?? null,
            'rubrica_at'       => $row['rubrica_at'] ?? null,
            'vb_estado'        => $row['vb_estado'] ?? null,
            'vb_at'            => $row['vb_at'] ?? null,

            // columna Estado/Oficina
            'estado_oficina'   => $row['estado_oficina'] ?? 'â€”',
            'estado_sub'       => $row['estado_sub']     ?? null,
            'estado_dt'        => $row['estado_dt']      ?? null,
        ];
    }
    mysqli_free_result($rs);
    return $data;
}

/* ===================== ACCIONES / BOTONES ===================== */
/* Mantenemos los botones visuales; solo â€œVer Inf. Semestralâ€ funciona.
   Los demÃ¡s estÃ¡n DESHABILITADOS y no ejecutan nada. */
function accionesPorRol(int $id_rol, string $rol_nombre): array
{
    // Siempre mostrar "Ver Inf. Semestral"
    $vis = accionesVisiblesPorRol($id_rol); // PCF/RSU: cotejo/rubrica | DD/DF: vb | Admin: todo | Coord: nada
    return array_merge(['ver'], $vis);
}

function renderBotonesAccion(array $acciones, int $id_py, ?int $id_respuesta = null): string
{
    $usr    = testeo();
    $id_rol = (int)$usr['id_rol'];

    $styles = [
        'ver'     => 'background-color:#28a745;border-color:#28a745;color:#ffffff;',
        'cotejo'  => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
        'rubrica' => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
        'vb'      => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
    ];
    $clases = [
        'ver'     => 'btn btn-sm mb-1 w-100 btn-ver-informe',
        'cotejo'  => 'btn btn-sm mb-1 w-100 btn-eval',
        'rubrica' => 'btn btn-sm mb-1 w-100 btn-eval',
        'vb'      => 'btn btn-sm mb-1 w-100 btn-eval',
    ];
    $iconos = [
        'ver'     => '<i class="fas fa-calendar-alt"></i>',
        'cotejo'  => '<i class="fas fa-list"></i>',
        'rubrica' => '<i class="fas fa-th"></i>',
        'vb'      => '<i class="fas fa-eye"></i>',
    ];
    $labels = [
        'ver'     => 'Ver Inf. Semestral',
        'cotejo'  => 'Calificar Cotejo',
        'rubrica' => 'Calificar RÃºbrica',
        'vb'      => 'Visto Bueno',
    ];

    $out = '';
    foreach ($acciones as $a) {
        if (!isset($clases[$a])) continue;
        $style = $styles[$a] ?? '';

        if ($a === 'ver') {
            // BotÃ³n siempre habilitado
            $out .= '<button type="button" data-id_py="' . (int)$id_py . '"'
                 .  (($id_respuesta !== null && $id_respuesta > 0) ? (' data-id_respuesta="' . (int)$id_respuesta . '"') : '')
                 .  ' class="' . $clases[$a] . '" style="' . $style . '"'
                 .  ' title="ðŸ“„ Ver el informe semestral">'
                 .    $iconos[$a] . ' ' . $labels[$a]
                 .  '</button>';
            continue;
        }

        // EvaluaciÃ³n: decidir habilitado/bloqueado + mensaje humano
        $perm = puedeClickearAccion($id_rol, $a, $id_py);
        $st   = $perm['state'] ?? [];
        $ofNom = $st['oficina_nom'] ?? ($st['oficina_cod'] ?? 'otra oficina');
        $inst  = $st['instancia_estado'] ?? '';
        $sit   = $st['situacion'] ?? '';
        $enabled = (bool)$perm['enabled'];

        if ($enabled) {
            $title = 'âœ… Califica ahora este proyecto.';
            $msg   = $title;
        } else {
            // Priorizar razones especÃ­ficas
            if ($sit === 'aprobado' || stripos((string)$perm['why'], 'aprobado') !== false) {
                $msg = 'ðŸš« No puedes calificar. El proyecto ha recibido la aprobaciÃ³n total.';
            } elseif ($inst === 'observado' || stripos((string)$perm['why'], 'observado') !== false) {
                $msg = 'ðŸš« No puedes calificar, el proyecto necesita ser subsanado por coordinador.';
            } elseif (stripos((string)$perm['why'], 'no iniciÃ³') !== false) {
                $msg = 'ðŸš« No puedes calificar. AÃºn no inicia la ruta de evaluaciÃ³n.';
            } elseif (stripos((string)$perm['why'], 'espera') !== false) {
                $msg = 'ðŸš« No puedes calificar. La oficina no estÃ¡ en espera de revisiÃ³n.';
            } elseif (stripos((string)$perm['why'], 'oficina') !== false || $ofNom) {
                $msg = 'ðŸš« No puede calificar, el proyecto se encuentra en la Oficina de ' . htmlspecialchars($ofNom);
            } else {
                $msg = 'ðŸš« No disponible para calificar en este momento.';
            }
            $title = $msg; // el title muestra el porquÃ© en hover
        }

        $disabledAttr = $enabled ? '' : ' disabled aria-disabled="true"';
        $styleExtra   = $enabled ? '' : 'opacity:.6;'; // SIN pointer-events:none para permitir hover ðŸš«
        $toggleId     = 'why-' . $a . '-' . (int)$id_py;

        // BotÃ³n
        $out .= '<button type="button"'
             .  ' class="' . $clases[$a] . '"'
             .  ' style="' . $style . $styleExtra . '"'
             .  ' data-accion="' . htmlspecialchars($a) . '"'
             .  ' data-id_py="' . (int)$id_py . '"'
             .  ' title="' . htmlspecialchars($title) . '"'
             .   $disabledAttr
             .  '>'
             .    $iconos[$a] . ' ' . $labels[$a]
             .  '</button>';
    }
    return $out;
}

