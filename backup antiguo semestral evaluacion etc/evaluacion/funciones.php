<?php
include_once __DIR__ . '/../componentes/configSesion.php';
include_once __DIR__ . '/../componentes/db.php';
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
 * NOTA: Conserva el filtro "Revisión" para segmentar por sm_respuestas (estado del Informe),
 * NO tiene relación con la evaluación V3.
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
        // Último estado en sm_respuestas (0:En proceso, 1:En Revisión, 2:Aprobado, 3:Observado)
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

    // Lista básica de proyectos SIN joins a sm_respuestas (no mostramos revisión/estado)
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

/* ===================== ACCIONES / BOTONES ===================== */
/* Mantenemos los botones visuales; solo “Ver Inf. Semestral” funciona.
   Los demás están DESHABILITADOS y no ejecutan nada. */
function accionesPorRol(int $id_rol, string $rol_nombre): array
{
    // Siempre mostrar "Ver Inf. Semestral"
    $vis = accionesVisiblesPorRol($id_rol); // PCF/RSU: cotejo/rubrica | DD/DF: vb | Admin: todo | Coord: nada
    return array_merge(['ver'], $vis);
}

function renderBotonesAccion(array $acciones, int $id_py): string
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
        'rubrica' => 'Calificar Rúbrica',
        'vb'      => 'Visto Bueno',
    ];

    $out = '';
    foreach ($acciones as $a) {
        if (!isset($clases[$a])) continue;
        $style = $styles[$a] ?? '';

        if ($a === 'ver') {
            // Botón siempre habilitado
            $out .= '<button type="button" data-id_py="' . (int)$id_py . '"'
                 .  ' class="' . $clases[$a] . '" style="' . $style . '"'
                 .  ' title="📄 Ver el informe semestral">'
                 .    $iconos[$a] . ' ' . $labels[$a]
                 .  '</button>';
            continue;
        }

        // Evaluación: decidir habilitado/bloqueado + mensaje humano
        $perm = puedeClickearAccion($id_rol, $a, $id_py);
        $st   = $perm['state'] ?? [];
        $ofNom = $st['oficina_nom'] ?? ($st['oficina_cod'] ?? 'otra oficina');
        $inst  = $st['instancia_estado'] ?? '';
        $sit   = $st['situacion'] ?? '';
        $enabled = (bool)$perm['enabled'];

        if ($enabled) {
            $title = '✅ Califica ahora este proyecto.';
            $msg   = $title;
        } else {
            // Priorizar razones específicas
            if ($sit === 'aprobado' || stripos((string)$perm['why'], 'aprobado') !== false) {
                $msg = '🚫 No puedes calificar. El proyecto ha recibido la aprobación total.';
            } elseif ($inst === 'observado' || stripos((string)$perm['why'], 'observado') !== false) {
                $msg = '🚫 No puedes calificar, el proyecto necesita ser subsanado por coordinador.';
            } elseif (stripos((string)$perm['why'], 'no inició') !== false) {
                $msg = '🚫 No puedes calificar. Aún no inicia la ruta de evaluación.';
            } elseif (stripos((string)$perm['why'], 'espera') !== false) {
                $msg = '🚫 No puedes calificar. La oficina no está en espera de revisión.';
            } elseif (stripos((string)$perm['why'], 'oficina') !== false || $ofNom) {
                $msg = '🚫 No puede calificar, el proyecto se encuentra en la Oficina de ' . htmlspecialchars($ofNom);
            } else {
                $msg = '🚫 No disponible para calificar en este momento.';
            }
            $title = $msg; // el title muestra el porqué en hover
        }

        $disabledAttr = $enabled ? '' : ' disabled aria-disabled="true"';
        $styleExtra   = $enabled ? '' : 'opacity:.6;'; // SIN pointer-events:none para permitir hover 🚫
        $toggleId     = 'why-' . $a . '-' . (int)$id_py;

        // Botón
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
