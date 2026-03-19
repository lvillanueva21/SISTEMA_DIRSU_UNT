<?php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../componentes/db.php';

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

/* ===================== CATÁLOGOS PARA LOS FILTROS ===================== */

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

/* ===================== WHERE POR ROL + FILTROS ===================== */

function whereFiltroPorRol(array $usr)
{
    global $conexion;

    $id_rol = (int) $usr['id_rol'];

    // Admin (0) y RSU (1): sin filtro
    if ($id_rol === 0 || $id_rol === 1) {
        return '';
    }

    // Decanato (3) y Comité (5): facultad = id_escuela del usuario
    if ($id_rol === 3 || $id_rol === 5) {
        $fac = (int) $usr['id_escuela'];
        return ($fac > 0) ? " AND f.id = $fac " : " AND 1=0 ";
    }

    // Dirección de Departamento (4): departamento = id_depa del usuario
    if ($id_rol === 4) {
        $depa = (int) $usr['id_depa'];
        return ($depa > 0) ? " AND d.id = $depa " : " AND 1=0 ";
    }

    // Coordinador (2): solo sus proyectos (por código docente u.usuario)
    if ($id_rol === 2) {
        $cod = isset($usr['usuario']) ? mysqli_real_escape_string($conexion, $usr['usuario']) : '';
        return ($cod !== '') ? " AND u.usuario = '$cod' " : " AND 1=0 ";
    }

    return '';
}

/**
 * WHERE adicional por filtros de UI.
 * $filtros: ['facultad','departamento','periodo','revision','q']
 * NOTA: Conserva el filtro "Revisión" para seguir segmentando por sm_respuestas,
 * aunque en la tabla no se muestra el estado.
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
    if ($per > 0) $w .= " AND pr.id = $per ";

    $rev = isset($filtros['revision']) ? trim((string)$filtros['revision']) : '';
    if ($rev === 'sin') {
        // Proyectos sin ningún registro en sm_respuestas
        $w .= " AND NOT EXISTS (
                    SELECT 1 FROM sm_respuestas rx
                    WHERE rx.id_py = p.id
                ) ";
    } elseif ($rev !== '') {
        // Filtrado por estado "vigente" usando el ÚLTIMO registro de sm_respuestas del proyecto
        // Map: 0=En proceso, 1=En Revisión, 2=Aprobado, 3=Observado
        $revInt = (int)$rev;
        $w .= " AND COALESCE((
                    SELECT r.estado
                    FROM sm_respuestas r
                    WHERE r.id_py = p.id
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

/* ===================== TOTAL Y LISTA (SIN LÓGICA DE EVALUACIÓN) ===================== */
/* Se mantiene el mismo origen que ya te funcionaba: revisiones_proyectos. */

function totalProyectos(array $usr, array $filtros = [])
{
    global $conexion;

    $whereRol    = whereFiltroPorRol($usr);
    $whereExtras = whereFiltros($filtros);

    $sql = " SELECT COUNT(DISTINCT p.id) AS total
             FROM revisiones_proyectos rp
             INNER JOIN usuarios_proyectos up ON up.id_proyecto = rp.id_py AND up.activo = 1
             INNER JOIN proyectos p           ON p.id = rp.id_py
             INNER JOIN usuarios u            ON u.id = up.id_usuario
             LEFT JOIN departamentos d        ON d.id = u.id_depa
             LEFT JOIN facultades f           ON f.id = d.id_facultad
             LEFT JOIN proyectos_periodo pp   ON pp.id_py = p.id
             LEFT JOIN periodos pr            ON pr.id = pp.id_periodo
             WHERE 1=1 $whereRol $whereExtras ";

    $res = mysqli_query($conexion, $sql);
    $row = $res ? mysqli_fetch_assoc($res) : ['total' => 0];

    return (int) ($row['total'] ?? 0);
}

function proyectosListado($pagina = 1, $por_pagina = 20, array $usr = [], array $filtros = [])
{
    global $conexion;

    $offset      = ($pagina - 1) * $por_pagina;
    $data        = [];
    $whereRol    = whereFiltroPorRol($usr);
    $whereExtras = whereFiltros($filtros);

    // Lista básica de proyectos (igual que tu versión inicial)
    $sql = " SELECT
                 p.id AS id_py,
                 p.p2 AS titulo,
                 u.nombres,
                 u.apellidos,
                 u.usuario AS cod_docente,
                 d.nombre AS nombre_departamento,
                 f.nombre AS nombre_facultad,
                 pr.nombre AS nombre_periodo
             FROM revisiones_proyectos rp
             INNER JOIN usuarios_proyectos up
                 ON up.id_proyecto = rp.id_py AND up.activo = 1
             INNER JOIN proyectos p
                 ON p.id = rp.id_py
             INNER JOIN usuarios u
                 ON u.id = up.id_usuario
             LEFT JOIN departamentos d
                 ON d.id = u.id_depa
             LEFT JOIN facultades f
                 ON f.id = d.id_facultad
             LEFT JOIN proyectos_periodo pp
                 ON pp.id_py = p.id
             LEFT JOIN periodos pr
                 ON pr.id = pp.id_periodo
             WHERE 1=1 $whereRol $whereExtras
             ORDER BY p.p2 ASC
             LIMIT $por_pagina OFFSET $offset ";

    if ($rs = mysqli_query($conexion, $sql)) {
        while ($row = mysqli_fetch_assoc($rs)) {
            $data[] = [
                'id_py'        => (int) $row['id_py'],
                'titulo'       => $row['titulo'] ?? '',
                'periodo'      => $row['nombre_periodo'] ?? 'No definido',
                'coordinador'  => trim(($row['nombres'] ?? '') . ' ' . ($row['apellidos'] ?? '')),
                'cod_docente'  => $row['cod_docente'] ?? 'Sin código',
                'facultad'     => $row['nombre_facultad'] ?? 'No registrada',
                'departamento' => $row['nombre_departamento'] ?? 'No registrado',
            ];
        }
        mysqli_free_result($rs);
    }

    return $data;
}

/* ===================== EVALUACIÓN: helpers de estado para la UI ===================== */

/** Catálogo de oficinas desde BD con fallback local (id => nom/bg/fg) */
function ev_catalogo_oficinas(): array {
    static $cache = null;
    if ($cache !== null) return $cache;

    $fallback = [
        1 => ['nom'=>'Comité de Facultad',        'bg'=>'#0275D8','fg'=>'#ffffff'],
        2 => ['nom'=>'Dirección de Departamento', 'bg'=>'#F0AD4E','fg'=>'#000000'],
        3 => ['nom'=>'Decanato de Facultad',      'bg'=>'#5BC0DE','fg'=>'#000000'],
        4 => ['nom'=>'Dirección RSU',             'bg'=>'#5CB85C','fg'=>'#ffffff'],
    ];

    global $conexion;
    $cache = $fallback;

    $rs = @mysqli_query($conexion, "SELECT office, nombre, badge_bg, text_color FROM ev_cat_oficinas");
    if ($rs) {
        $tmp = [];
        while ($r = mysqli_fetch_assoc($rs)) {
            $tmp[(int)$r['office']] = [
                'nom' => $r['nombre'],
                'bg'  => $r['badge_bg'],
                'fg'  => $r['text_color'],
            ];
        }
        mysqli_free_result($rs);
        if (!empty($tmp)) $cache = $tmp;
    }
    return $cache;
}
function ev_oficina_nom(?int $id): string {
    $cat = ev_catalogo_oficinas();
    return ($id && isset($cat[$id])) ? $cat[$id]['nom'] : 'Sin Oficina (no se encontró tracking/caso)';
}

/** Texto del estado de sm_respuestas (0..3) */
function ev_estado_sm_texto($est): string {
    $est = (int)$est;
    switch ($est) {
        case 0: return 'En desarrollo';
        case 1: return 'En revisión';
        case 2: return 'Aprobado';
        case 3: return 'Observado';
        default: return 'Sin Informe';
    }
}

/**
 * Devuelve un paquete de estado para la UI:
 * - Última sm_respuesta (si no hay: tiene_respuesta=false)
 * - Oficina actual (tracking abierto > ev_casos.current_office)
 * - Observaciones (banderas cotejo/rúbrica)
 * Nunca rompe el listado: valores por defecto si algo falta.
 */
function ev_estado_para_ui(int $id_py): array {
    global $conexion;

    $out = [
        'tiene_respuesta' => false,
        'respuesta_id'    => null,
        'estado'          => null,
        'estado_txt'      => 'Sin Informe',
        'oficina_id'      => 0,
        'oficina_txt'     => 'Sin Oficina (no se encontró tracking/caso)',
        'desde'           => null,
        'observaciones'   => ['cotejo'=>false,'rubrica'=>false],
    ];

    /* 1) última sm_respuesta del proyecto */
    $rid = 0;
    if ($st = @mysqli_prepare($conexion, "SELECT id, estado, creado_at, actualizado_at
                                          FROM sm_respuestas
                                          WHERE id_py=? ORDER BY actualizado_at DESC, id DESC LIMIT 1")) {
        mysqli_stmt_bind_param($st, "i", $id_py);
        mysqli_stmt_execute($st);
        $res = mysqli_stmt_get_result($st);
        if ($r = mysqli_fetch_assoc($res)) {
            $out['tiene_respuesta'] = true;
            $out['respuesta_id']    = (int)$r['id'];
            $out['estado']          = (int)$r['estado'];
            $out['estado_txt']      = ev_estado_sm_texto($r['estado']);
            $out['desde']           = $r['actualizado_at'] ?: $r['creado_at'];
            $rid = (int)$r['id'];
        }
        mysqli_stmt_close($st);
    }
    if ($rid <= 0) return $out; // Sin informe → devolvemos defaults

    /* 2) oficina actual según estado */
    $estado = (int)$out['estado'];

    if ($estado === 1) {
        // En revisión → primero tracking abierto
        if ($st = @mysqli_prepare($conexion, "SELECT office, arrived_at
                                              FROM ev_oficinas_tracking
                                              WHERE id_respuesta=? AND left_at IS NULL
                                              ORDER BY arrived_at DESC, id DESC LIMIT 1")) {
            mysqli_stmt_bind_param($st, "i", $rid);
            mysqli_stmt_execute($st);
            $t = mysqli_stmt_get_result($st);
            if ($row = mysqli_fetch_assoc($t)) {
                $out['oficina_id']  = (int)$row['office'];
                $out['oficina_txt'] = ev_oficina_nom($out['oficina_id']);
                $out['desde']       = $row['arrived_at'] ?: $out['desde'];
            } else {
                // Fallback a ev_casos.current_office
                if ($st2 = @mysqli_prepare($conexion, "SELECT current_office, fecha_solicitud
                                                       FROM ev_casos WHERE id_respuesta=? LIMIT 1")) {
                    mysqli_stmt_bind_param($st2, "i", $rid);
                    mysqli_stmt_execute($st2);
                    $c = mysqli_stmt_get_result($st2);
                    if ($cc = mysqli_fetch_assoc($c)) {
                        $out['oficina_id']  = (int)$cc['current_office'];
                        $out['oficina_txt'] = ev_oficina_nom($out['oficina_id']);
                        if (!empty($cc['fecha_solicitud'])) $out['desde'] = $cc['fecha_solicitud'];
                    }
                    mysqli_stmt_close($st2);
                }
            }
            mysqli_stmt_close($st);
        }
    } elseif ($estado === 2) {
        // Aprobado → RSU como referencia de cierre
        $out['oficina_id']  = 4;
        $out['oficina_txt'] = ev_oficina_nom(4);
    } elseif ($estado === 3) {
        // Observado → última oficina que observó
        if ($st = @mysqli_prepare($conexion, "SELECT office, COALESCE(MAX(COALESCE(closed_at, observed_at, created_at)), NOW()) AS f
                                              FROM ev_intentos
                                              WHERE id_respuesta=? AND estado=2
                                              GROUP BY office ORDER BY f DESC LIMIT 1")) {
            mysqli_stmt_bind_param($st, "i", $rid);
            mysqli_stmt_execute($st);
            $rs = mysqli_stmt_get_result($st);
            if ($row = mysqli_fetch_assoc($rs)) {
                $out['oficina_id']  = (int)$row['office'];
                $out['oficina_txt'] = ev_oficina_nom($out['oficina_id']);
                if (!empty($row['f'])) $out['desde'] = $row['f'];
            }
            mysqli_stmt_close($st);
        }
    }
    // estado 0 → queda “Sin Oficina …” (borrador)

    /* 3) observaciones (banderas) */
    // Cotejo → último intento tipo=1 observado o con obs_general
    if ($st = @mysqli_prepare($conexion, "SELECT estado, COALESCE(obs_general,'') AS og
                                          FROM ev_intentos
                                          WHERE id_respuesta=? AND tipo=1
                                          ORDER BY id DESC LIMIT 1")) {
        mysqli_stmt_bind_param($st, "i", $rid);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        if ($row = mysqli_fetch_assoc($rs)) {
            $out['observaciones']['cotejo'] = ((int)$row['estado'] === 2) || (trim((string)$row['og']) !== '');
        }
        mysqli_stmt_close($st);
    }

    // Rúbrica → intento tipo=2 observado o aspectos con nota<=2/observación
    $lastRbId = 0; $lastRbEstado = 0;
    if ($st = @mysqli_prepare($conexion, "SELECT id, estado
                                          FROM ev_intentos
                                          WHERE id_respuesta=? AND tipo=2
                                          ORDER BY id DESC LIMIT 1")) {
        mysqli_stmt_bind_param($st, "i", $rid);
        mysqli_stmt_execute($st);
        $rs = mysqli_stmt_get_result($st);
        if ($row = mysqli_fetch_assoc($rs)) {
            $lastRbId = (int)$row['id'];
            $lastRbEstado = (int)$row['estado'];
        }
        mysqli_stmt_close($st);
    }
    if ($lastRbId > 0) {
        if ($lastRbEstado === 2) {
            $out['observaciones']['rubrica'] = true;
        } else {
            if ($st = @mysqli_prepare($conexion, "SELECT COUNT(*) AS c
                                                  FROM ev_rubrica_aspectos
                                                  WHERE intento_id=? AND (nota<=2 OR (observacion IS NOT NULL AND TRIM(observacion)<>''))")) {
                mysqli_stmt_bind_param($st, "i", $lastRbId);
                mysqli_stmt_execute($st);
                $rs = mysqli_stmt_get_result($st);
                $c = ($row = mysqli_fetch_assoc($rs)) ? (int)$row['c'] : 0;
                $out['observaciones']['rubrica'] = ($c > 0);
                mysqli_stmt_close($st);
            }
        }
    }

    return $out;
}

/** Mapeo de rol → oficina “propia” (0=admin: cualquiera) */
function ev_rol_oficina(int $id_rol): ?int {
    switch ((int)$id_rol) {
        case 5: return 1; // Comité de Facultad
        case 4: return 2; // Dirección de Departamento
        case 3: return 3; // Decanato
        case 1: return 4; // RSU
        case 0: return 0; // Admin (todas)
        default: return null; // otros (coordinador, etc.)
    }
}

/** Oficinas que aplican para cada acción */
function ev_accion_oficinas(string $accion): array {
    switch ($accion) {
        case 'cotejo':  return [1,4]; // PCF y RSU
        case 'rubrica': return [1,4]; // PCF y RSU
        case 'vb':      return [2,3]; // DD y DF
        default:        return [];    // ver => libre
    }
}

/** Acciones visibles por rol (solo nombres) */
function accionesPorRol(int $id_rol, string $rol_nombre): array {
    switch ((int)$id_rol) {
        case 0: return ['ver','cotejo','rubrica','vb']; // Admin
        case 1: return ['ver','cotejo','rubrica'];      // RSU
        case 5: return ['ver','cotejo','rubrica'];      // Comité
        case 4: return ['ver','vb'];                    // Dirección de Departamento
        case 3: return ['ver','vb'];                    // Decanato
        default: return ['ver'];                        // Coordinador u otros
    }
}

/** Si la acción está bloqueada, devuelve el motivo (string); si NO, devuelve null */
function ev_motivo_bloqueo(string $accion, array $ev, array $usr): ?string {
    if ($accion === 'ver') return null;

    if (empty($ev['tiene_respuesta'])) return 'No tiene Informe Semestral';
    $estado = isset($ev['estado']) ? (int)$ev['estado'] : -1;

    if ($estado === 0) return 'Debe solicitar revisión';
    if ($estado === 2) return 'Informe aprobado';
    if ($estado === 3) return 'Proyecto observado — esperar subsanación';
    if ($estado !== 1) return 'Estado no elegible';

    // En revisión → validar oficina actual y rol
    $oficinaActual = isset($ev['oficina_id']) ? (int)$ev['oficina_id'] : 0;
    $oficinasDeAccion = ev_accion_oficinas($accion);
    if (!in_array($oficinaActual, $oficinasDeAccion, true)) {
        $txt = !empty($ev['oficina_txt']) ? $ev['oficina_txt'] : 'oficina no determinada';
        return 'No puede revisar porque el proyecto se encuentra en '.$txt;
    }

    $rolOficina = ev_rol_oficina((int)$usr['id_rol']);
    if ($rolOficina && $rolOficina !== $oficinaActual && (int)$usr['id_rol'] !== 0) {
        $txt = !empty($ev['oficina_txt']) ? $ev['oficina_txt'] : 'oficina actual';
        return 'No pertenece a la oficina actual ('.$txt.')';
    }

    return null; // permitido
}

/** Render de botones con bloqueo + tooltip/mensaje */
function renderBotonesAccion(array $acciones, int $id_py, array $ev, array $usr): string {
    $styles = [
        'ver'     => 'background-color:#28a745;border-color:#28a745;color:#ffffff;',
        'cotejo'  => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
        'rubrica' => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
        'vb'      => 'background-color:#ffc107;border-color:#ffc107;color:#212529;',
    ];
    $clases = [
        'ver'     => 'btn btn-sm mb-1 w-100 btn-ver-informe',
        // ⚠️ estos llevan .btn-evaluar + data-accion
        'cotejo'  => 'btn btn-sm mb-1 w-100 btn-evaluar',
        'rubrica' => 'btn btn-sm mb-1 w-100 btn-evaluar',
        'vb'      => 'btn btn-sm mb-1 w-100 btn-evaluar',
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
        'rubrica' => 'Calificar Rúbrica',
        'vb'      => 'Visto Bueno',
    ];

    $out = '';
    $todosBloqueados = true;
    $motivoGeneral   = '';

    foreach ($acciones as $a) {
        if (!isset($clases[$a])) continue;
        $motivo = ev_motivo_bloqueo($a, $ev, $usr);
        $disabled = $motivo ? 'disabled' : '';
        if (!$motivo) $todosBloqueados = false;
        elseif ($motivoGeneral === '' && $a !== 'ver') $motivoGeneral = $motivo;

        if ($a === 'ver') {
            $out .= '<button type="button" data-id_py="' . (int)$id_py . '"'
                 .  ' class="' . $clases[$a] . '" style="' . $styles[$a] . '">'
                 .    $iconos[$a] . ' ' . $labels[$a]
                 .  '</button>';
        } else {
            $title = $motivo ? ' title="'.htmlspecialchars($motivo,ENT_QUOTES,'UTF-8').'" ' : '';
            $out .= '<button type="button" '.$disabled.$title
                 .  ' data-id_py="'.(int)$id_py.'" data-accion="'.htmlspecialchars($a,ENT_QUOTES,'UTF-8').'"'
                 .  ' class="' . $clases[$a] . '" style="' . $styles[$a] . '">'
                 .    $iconos[$a] . ' ' . $labels[$a]
                 .  '</button>';
        }
    }

    if ($todosBloqueados && $motivoGeneral !== '') {
        $out .= '<div class="text-muted small mt-1">'
             .   htmlspecialchars($motivoGeneral, ENT_QUOTES, 'UTF-8')
             . '</div>';
    }
    return $out;
}
