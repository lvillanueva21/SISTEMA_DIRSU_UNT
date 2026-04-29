<?php
// /sistema_web/informe_semestral/control_oficinas.php
// Reglas de visibilidad de botones y habilitaciÃ³n por oficina/estado.
include_once __DIR__ . '/../includes/db_connection.php';

/** Oficina asociada a un rol â†’ cÃ³digo corto de catÃ¡logo eva_oficinas */
function oficinaCodigoPorRol(int $id_rol): ?string {
    switch ($id_rol) {
        case 5: return 'PCF'; // ComitÃ© de Facultad
        case 4: return 'DD';  // DirecciÃ³n de Departamento
        case 3: return 'DF';  // Decanato de Facultad
        case 1: return 'RSU'; // DirecciÃ³n RSU
        // 0: Admin (sin oficina), 2: Coordinador (sin oficina)
        default: return null;
    }
}

/** QuÃ© botones â€œcalificarâ€ puede VER cada rol */
function accionesVisiblesPorRol(int $id_rol): array {
    if ($id_rol === 2) return []; // Coordinador: no ve calificar
    if ($id_rol === 5 || $id_rol === 1) return ['cotejo','rubrica']; // PCF y RSU
    if ($id_rol === 3 || $id_rol === 4) return ['vb'];               // DF y DD
    if ($id_rol === 0) return ['cotejo','rubrica','vb'];              // Admin
    return [];
}

/** Oficinas activas en orden de ruta */
function oficinasActivasOrdered(): array {
    global $conexion;
    $out = [];
    $sql = "SELECT id, codigo, nombre FROM eva_oficinas WHERE activo=1 ORDER BY orden ASC";
    if ($rs = $conexion->query($sql)) {
        while ($r = $rs->fetch_assoc()) {
            $out[] = ['id'=>(int)$r['id'], 'codigo'=>$r['codigo']??null, 'nombre'=>$r['nombre']??null];
        }
        $rs->free();
    }
    return $out;
}

/** Mapea rol -> posiciÃ³n en la ruta (por orden) y devuelve {id, codigo, nombre} de su oficina */
function oficinaIdNombrePorRol(int $id_rol): ?array {
    // Mapa por ORDINAL (asumiendo el orden de la ruta: 0:PCF, 1:DD, 2:DF, 3:RSU)
    $idxMap = [
        5 => 0, // ComitÃ© de Facultad
        4 => 1, // DirecciÃ³n de Departamento
        3 => 2, // Decanato
        1 => 3, // RSU
    ];
    if (!isset($idxMap[$id_rol])) return null; // Admin/Coordinador: sin oficina fija
    $ofs = oficinasActivasOrdered();
    $i = $idxMap[$id_rol];
    return isset($ofs[$i]) ? $ofs[$i] : null;
}

/** Estado actual de evaluaciÃ³n para un proyecto */
function estadoEvaluacionActualPorProyecto(int $id_py): ?array {
    global $conexion;
    // Tomamos la evaluaciÃ³n mÃ¡s reciente del proyecto y su oficina actual por ID
    $sql = "SELECT
                e.id                AS eval_id,
                e.situacion         AS situacion,
                e.id_oficina_actual AS oficina_id,
                o.codigo            AS oficina_cod,
                o.nombre            AS oficina_nom,
                (
                  SELECT oi.estado
                  FROM eva_oficina_instancias oi
                  WHERE oi.id_evaluacion = e.id
                    AND oi.id_oficina    = e.id_oficina_actual
                  ORDER BY oi.id DESC
                  LIMIT 1
                ) AS instancia_estado
            FROM eva_evaluaciones e
            INNER JOIN sm_respuestas r ON r.id = e.id_respuesta
            LEFT JOIN eva_oficinas o    ON o.id = e.id_oficina_actual
            WHERE r.id_py = ?
            ORDER BY e.id DESC
            LIMIT 1";
    if (!($stmt = $conexion->prepare($sql))) return null;
    $stmt->bind_param('i', $id_py);
    if (!$stmt->execute()) { $stmt->close(); return null; }
    $res = $stmt->get_result();
    $row = ($res && $res->num_rows) ? $res->fetch_assoc() : null;
    $stmt->close();
    if (!$row) return null;

    return [
        'eval_id'          => (int)$row['eval_id'],
        'situacion'        => $row['situacion'] ?: null,
        'oficina_id'       => $row['oficina_id'] ? (int)$row['oficina_id'] : null,
        'oficina_cod'      => $row['oficina_cod'] ?: null,
        'oficina_nom'      => $row['oficina_nom'] ?: null,
        'instancia_estado' => $row['instancia_estado'] ?: null,
    ];
}

/**
 * Â¿El botÃ³n se puede CLICKear?
 * Regla: proyecto debe estar en la oficina del rol (salvo admin), la instancia en 'en_espera',
 * y NO estar aprobado total ni observado.
 */
function puedeClickearAccion(int $id_rol, string $accion, int $id_py): array {
    $out = ['enabled' => false, 'why' => 'No autorizado', 'state' => null];

    // Coordinador nunca califica
    if ($id_rol === 2) { $out['why'] = 'El coordinador no califica'; return $out; }

    $visibles = accionesVisiblesPorRol($id_rol);
    if ($id_rol !== 0 && !in_array($accion, $visibles, true)) {
        $out['why'] = 'AcciÃ³n no corresponde a su oficina'; return $out;
    }

    $st = estadoEvaluacionActualPorProyecto($id_py);
    if (!$st) { $out['why'] = 'El proyecto aÃºn no iniciÃ³ su ruta'; return $out; }
    if (($st['situacion'] ?? '') === 'aprobado') { $out['why'] = 'Proyecto ya aprobado'; return $out; }

    // Admin (0) puede siempre que no estÃ© observado ni aprobado
    if ($id_rol !== 0) {
        $ofRol = oficinaIdNombrePorRol($id_rol); // {id,codigo,nombre}
        if (!$ofRol || !$st['oficina_id'] || (int)$st['oficina_id'] !== (int)$ofRol['id']) {
            $out['why'] = 'Proyecto no estÃ¡ en su oficina actual'; 
            $out['state'] = $st;
            return $out;
        }
    }

    $inst = $st['instancia_estado'] ?? '';
    if ($inst === 'observado') { $out['why'] = 'Proyecto observado; debe subsanar'; $out['state'] = $st; return $out; }
    if ($inst !== 'en_espera') { $out['why'] = 'La oficina no estÃ¡ en espera de revisiÃ³n'; $out['state'] = $st; return $out; }

    // OK
    $out['enabled'] = true; $out['why'] = ''; $out['state'] = $st;
    return $out;
}


