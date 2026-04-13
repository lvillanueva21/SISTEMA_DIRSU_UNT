<?php
/**
 * Convierte 'd/m/Y' -> DateTime (Lima, 00:00:00). Lanza excepción si no válida.
 */
function parseDMY_orThrow(?string $dmy): DateTime {
    $dmy = trim((string)$dmy);
    $dt = DateTime::createFromFormat('d/m/Y', $dmy, new DateTimeZone('America/Lima'));
    if (!$dt) throw new InvalidArgumentException("Fecha inválida: $dmy");
    $dt->setTime(0,0,0);
    return $dt;
}

/** Devuelve límites naturales de semestre (inicio/fin) para un año y periodo. */
function limitesSemestreNat(int $anio, string $periodo): array {
    if ($periodo === 'I') {
        return [new DateTime("$anio-01-01"), new DateTime("$anio-06-30")];
    } else {
        return [new DateTime("$anio-07-01"), new DateTime("$anio-12-31")];
    }
}

/** A partir de una fecha retorna [anio, 'I'|'II'] del semestre natural. */
function semestreDeFecha(DateTime $dt): array {
    $anio = (int)$dt->format('Y');
    $m = (int)$dt->format('n');
    $p = ($m <= 6) ? 'I' : 'II';
    return [$anio, $p];
}

/** Avanza al inicio del siguiente semestre natural dada pareja [anio, periodo]. */
function siguienteSem(int $anio, string $periodo): array {
    if ($periodo === 'I') return [$anio, 'II'];
    return [$anio+1, 'I'];
}

/**
 * Construye el "plan" de filas que DEBEN existir en sm_proyecto_semestres
 */
function planSemestres(DateTime $fi, DateTime $ff): array {
    if ($fi > $ff) return [];

    [$ai, $pi] = semestreDeFecha($fi);
    [$af, $pf] = semestreDeFecha($ff);

    $cursorA = $ai; $cursorP = $pi;
    $listaSem = [];
    while (true) {
        $listaSem[] = [$cursorA, $cursorP];
        if ($cursorA === $af && $cursorP === $pf) break;
        [$cursorA, $cursorP] = siguienteSem($cursorA, $cursorP);
    }

    $rows = [];
    $num = 1;
    foreach ($listaSem as $idx => [$anio, $per]) {
        [$natIni, $natFin] = limitesSemestreNat($anio, $per);
        $ri = max($natIni->getTimestamp(), $fi->getTimestamp());
        $rf = min($natFin->getTimestamp(), $ff->getTimestamp());
        $fecha_inicio = (new DateTime())->setTimestamp($ri)->format('Y-m-d');
        $fecha_fin    = (new DateTime())->setTimestamp($rf)->format('Y-m-d');
        $esPrimero = ($idx === 0);
        $esUltimo  = ($idx === count($listaSem) - 1);

        if ($esPrimero) {
            $rows[] = [
                'anio'=>$anio,'periodo'=>$per,'tipo'=>'presentacion','numero'=>null,
                'final'=>0,'fecha_inicio'=>$fecha_inicio,'fecha_fin'=>$fecha_fin,
                'titulo'=>'Presentación de proyecto'
            ];
            $rows[] = [
                'anio'=>$anio,'periodo'=>$per,'tipo'=>'semestral','numero'=>$num,
                'final'=>$esUltimo?1:0,'fecha_inicio'=>$fecha_inicio,'fecha_fin'=>$fecha_fin,
                'titulo'=> $esUltimo ? sprintf('Informe Semestral %02d (Informe Final)',$num)
                                     : sprintf('Informe Semestral %02d',$num)
            ];
            $num++;
        } else {
            $rows[] = [
                'anio'=>$anio,'periodo'=>$per,'tipo'=>'semestral','numero'=>$num,
                'final'=>$esUltimo?1:0,'fecha_inicio'=>$fecha_inicio,'fecha_fin'=>$fecha_fin,
                'titulo'=> $esUltimo ? sprintf('Informe Semestral %02d (Informe Final)',$num)
                                     : sprintf('Informe Semestral %02d',$num)
            ];
            $num++;
        }
    }
    return $rows;
}

/**
 * Sincroniza la tabla sm_proyecto_semestres (usa soft-delete con vigente=0)
 */
function syncProyectoSemestres(mysqli $cx, int $id_py, DateTime $fi, DateTime $ff): array {
    $plan = planSemestres($fi, $ff);

    $exist = [];
    $res = $cx->prepare("
        SELECT id, anio, periodo, tipo, numero, fecha_inicio, fecha_fin, final, titulo, vigente
        FROM sm_proyecto_semestres
        WHERE id_py=?
    ");
    $res->bind_param("i",$id_py);
    $res->execute();
    $rs = $res->get_result();
    while ($row = $rs->fetch_assoc()) {
        $k = $row['anio'].'|'.$row['periodo'].'|'.$row['tipo'];
        $exist[$k] = [
            'id' => (int)$row['id'],
            'numero' => $row['numero'] === null ? null : (int)$row['numero'],
            'fecha_inicio' => (string)$row['fecha_inicio'],
            'fecha_fin' => (string)$row['fecha_fin'],
            'final' => (int)$row['final'],
            'titulo' => (string)$row['titulo'],
            'vigente' => (int)$row['vigente']
        ];
    }
    $res->close();

    $cx->begin_transaction();
    $cre=0;$upd=0;$desact=0;

    try {
        $ins = $cx->prepare("
            INSERT INTO sm_proyecto_semestres
              (id_py, anio, periodo, fecha_inicio, fecha_fin, tipo, numero, final, titulo, vigente)
            VALUES (?,?,?,?,?,?,?,?,?,1)
        ");
        if (!$ins) throw new RuntimeException("Error prepare insert sm_proyecto_semestres: ".$cx->error);
        $up = $cx->prepare("
            UPDATE sm_proyecto_semestres
            SET fecha_inicio=?, fecha_fin=?, numero=?, final=?, titulo=?, vigente=?
            WHERE id=?
        ");
        if (!$up) throw new RuntimeException("Error prepare update sm_proyecto_semestres: ".$cx->error);

        foreach ($plan as $row) {
            $k = $row['anio'].'|'.$row['periodo'].'|'.$row['tipo'];
            $targetNumero = $row['numero'] === null ? null : (int)$row['numero'];
            $targetFinal = (int)$row['final'];
            $targetTitulo = (string)$row['titulo'];

            if (isset($exist[$k])) {
                $cur = $exist[$k];
                $cambio =
                    ((string)$cur['fecha_inicio'] !== (string)$row['fecha_inicio']) ||
                    ((string)$cur['fecha_fin'] !== (string)$row['fecha_fin']) ||
                    ($cur['numero'] !== $targetNumero) ||
                    ((int)$cur['final'] !== $targetFinal) ||
                    ((string)$cur['titulo'] !== $targetTitulo) ||
                    ((int)$cur['vigente'] !== 1);

                if ($cambio) {
                    $vigente = 1;
                    $idExist = (int)$cur['id'];
                    $up->bind_param(
                        "ssiisii",
                        $row['fecha_inicio'],
                        $row['fecha_fin'],
                        $targetNumero,
                        $targetFinal,
                        $targetTitulo,
                        $vigente,
                        $idExist
                    );
                    $okUp = $up->execute();
                    if (!$okUp) throw new RuntimeException("Error update sm_proyecto_semestres: ".$up->error);
                    $upd++;
                }

                unset($exist[$k]);
                continue;
            }

            $ins->bind_param(
                "iissssiis",
                $id_py,
                $row['anio'],
                $row['periodo'],
                $row['fecha_inicio'],
                $row['fecha_fin'],
                $row['tipo'],
                $targetNumero,
                $targetFinal,
                $targetTitulo
            );
            $okIns = $ins->execute();
            if (!$okIns) throw new RuntimeException("Error insert sm_proyecto_semestres: ".$ins->error);
            $cre++;
        }
        $ins->close();
        $up->close();

        if (!empty($exist)) {
            $idsDesactivar = [];
            foreach ($exist as $filaRestante) {
                if ((int)$filaRestante['vigente'] === 1) {
                    $idsDesactivar[] = (int)$filaRestante['id'];
                }
            }

            if (!empty($idsDesactivar)) {
                $place = implode(',', array_fill(0, count($idsDesactivar), '?'));
                $types = str_repeat('i', count($idsDesactivar));
                $delStmt = $cx->prepare("UPDATE sm_proyecto_semestres SET vigente=0 WHERE id IN ($place)");
                $delStmt->bind_param($types, ...$idsDesactivar);
                $delStmt->execute();
                $desact += $delStmt->affected_rows;
                $delStmt->close();
            }
        }

        $cx->commit();
    } catch (Throwable $e) {
        $cx->rollback();
        throw $e;
    }

    return ['creados'=>$cre,'actualizados'=>$upd,'desactivados'=>$desact,'total'=>count($plan)];
}

/* ===================== HELPERS Y CONSULTAS ===================== */

function parsePeriodoNombre(string $nombre): array {
    $nombre = trim($nombre);
    [$anio, $per] = explode('-', $nombre, 2);
    $anio = (int)trim($anio);
    $per  = strtoupper(trim($per));
    if (!in_array($per, ['I','II'], true) || $anio < 1900) {
        throw new InvalidArgumentException("Periodo inválido: $nombre");
    }
    return [$anio, $per];
}

function obtenerCronogramaActivoTipo2(mysqli $cx): ?array {
    $sql = "SELECT c.id, c.id_periodo, p.nombre AS periodo_nombre, c.apertura, c.cierre
            FROM sm_cronogramas c
            JOIN periodos p ON p.id = c.id_periodo
            WHERE c.activo = 1 AND c.tipo = 2
            ORDER BY c.apertura DESC
            LIMIT 1";
    $st = $cx->prepare($sql);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function obtenerFormularioDeCronograma(mysqli $cx, int $id_cronograma): ?array {
    $st = $cx->prepare("SELECT id, nombre FROM sm_formularios WHERE id_cronograma=? AND activo=1 LIMIT 1");
    $st->bind_param("i", $id_cronograma);
    $st->execute();
    $rs = $st->get_result();
    $row = $rs->fetch_assoc();
    $st->close();
    return $row ?: null;
}

function tipoSemestreParaFormulario(string $nombreFormulario): string {
    return (stripos($nombreFormulario, 'presentación') !== false) ? 'presentacion' : 'semestral';
}

function encontrarSemestreObjetivo(mysqli $cx, int $id_py, int $anio, string $periodo, string $tipo): ?int {
    $st = $cx->prepare("
        SELECT id FROM sm_proyecto_semestres
        WHERE id_py=? AND anio=? AND periodo=? AND tipo=? AND vigente=1
        LIMIT 1
    ");
    $st->bind_param("iiss", $id_py, $anio, $periodo, $tipo);
    $st->execute();
    $st->bind_result($id);
    $ok = $st->fetch();
    $st->close();
    return $ok ? (int)$id : null;
}

function encontrarRespuestaExistente(mysqli $cx, int $id_py, int $id_formulario, int $id_semestre): ?int {
    $st = $cx->prepare("
        SELECT id FROM sm_respuestas
        WHERE id_py=? AND id_formulario=? AND id_semestre=?
        LIMIT 1
    ");
    $st->bind_param("iii", $id_py, $id_formulario, $id_semestre);
    $st->execute();
    $st->bind_result($id);
    $ok = $st->fetch();
    $st->close();
    return $ok ? (int)$id : null;
}

/* ===================== FUNCIÓN PRINCIPAL ===================== */
function obtenerInfoSemestral(mysqli $conexion, int $id_py): array
{
    // 1) Fechas del proyecto
    $sqlProj = "SELECT fecha_inicio, fecha_fin FROM proyectos WHERE id = ?";
    $stmtProj = $conexion->prepare($sqlProj);
    if (!$stmtProj) return ['error' => 'Error al preparar la consulta de proyecto.'];
    $stmtProj->bind_param("i", $id_py);
    $stmtProj->execute();
    $stmtProj->bind_result($fecha_inicio_txt, $fecha_fin_txt);
    $tieneFila = $stmtProj->fetch();
    $stmtProj->close();
    if (!$tieneFila) return ['error' => 'Proyecto no encontrado.'];

    // 2) Validación de fechas texto d/m/Y
    $formatoFecha = '/^(0[1-9]|[12][0-9]|3[01])\/(0[1-9]|1[0-2])\/(19|20)\d{2}$/';
    $fechaValidaInicio = preg_match($formatoFecha, trim((string)$fecha_inicio_txt));
    $fechaValidaFin    = preg_match($formatoFecha, trim((string)$fecha_fin_txt));

    if (!$fechaValidaInicio || !$fechaValidaFin) {
        return [
            'inicio'     => $fechaValidaInicio ? $fecha_inicio_txt : "No se encontró la fecha de inicio",
            'fin'        => $fechaValidaFin    ? $fecha_fin_txt    : "No se encontró la fecha de fin",
            'cantidad'   => 0,
            'semestres'  => [],
            'interfaz'   => 3, // sinFechas
            'ahora_lima' => (new DateTime('now', new DateTimeZone('America/Lima')))->format('Y-m-d H:i:s'),
            'apertura'   => null,
            'cierre'     => null,
            'motivo'     => 'sin_fechas'
        ];
    }

    // 3) Normaliza a DateTime
    try {
        $fi = parseDMY_orThrow($fecha_inicio_txt);
        $ff = parseDMY_orThrow($fecha_fin_txt);
    } catch (Throwable $e) {
        return ['error'=>"Fechas inválidas: ".$e->getMessage()];
    }
    if ($fi > $ff) {
        return ['error'=>"La fecha de inicio es mayor que la fecha fin."];
    }

    // 4) Sincroniza semestres
    $syncResumen = syncProyectoSemestres($conexion, $id_py, $fi, $ff);

    // 5) Chips
    $stmt = $conexion->prepare("
        SELECT anio, periodo, tipo, numero, final
        FROM sm_proyecto_semestres
        WHERE id_py=? AND vigente=1
        ORDER BY anio, FIELD(periodo,'I','II'),
                 CASE tipo WHEN 'presentacion' THEN 0 ELSE 1 END,
                 COALESCE(numero,0)
    ");
    $stmt->bind_param("i",$id_py);
    $stmt->execute();
    $res = $stmt->get_result();
    $chips = []; $vistoSem = [];
    while ($r = $res->fetch_assoc()) {
        $chip = $r['anio'].'-'.$r['periodo'];
        if (!isset($vistoSem[$chip])) { $chips[] = $chip; $vistoSem[$chip]=true; }
    }
    $stmt->close();
    $cantidad = count($chips);

    // 6) Cronograma
    $tz = new DateTimeZone('America/Lima');
    $ahora = new DateTime('now', $tz);

    $cron = obtenerCronogramaActivoTipo2($conexion);
    if (!$cron) {
        return [
            'inicio'     => $fecha_inicio_txt,
            'fin'        => $fecha_fin_txt,
            'cantidad'   => $cantidad,
            'semestres'  => $chips,
            'interfaz'   => 0,
            'ahora_lima' => $ahora->format('Y-m-d H:i:s'),
            'apertura'   => null,
            'cierre'     => null,
            'motivo'     => 'sin_activo_tipo2',
            'form_activo' => null,
            'periodo_activo' => null,
            'semestre_objetivo_id' => null,
            'respuesta_id' => null,
        ];
    }

    $apertura = new DateTime($cron['apertura'], $tz);
    $cierre   = new DateTime($cron['cierre'],   $tz);
    $interfaz = ($ahora >= $apertura && $ahora <= $cierre) ? 2 : 1;
    $motivo      = ($interfaz === 2 ? 'en_rango' : 'fuera_rango');

    // 7) Formulario
    $form = obtenerFormularioDeCronograma($conexion, (int)$cron['id']);
    $formPayload = $form ? ['id'=>(int)$form['id'], 'nombre'=>$form['nombre']] : null;

    // 8) Periodo → semestre objetivo
    $semestreObjetivoId = null;
    $periodoPayload = null;
    try {
        [$pAnio, $pPer] = parsePeriodoNombre($cron['periodo_nombre']);
        $periodoPayload = ['nombre'=>$cron['periodo_nombre'], 'anio'=>$pAnio, 'periodo'=>$pPer];
        if ($formPayload) {
            $tipoSem = tipoSemestreParaFormulario($formPayload['nombre']);
            $semestreObjetivoId = encontrarSemestreObjetivo($conexion, $id_py, $pAnio, $pPer, $tipoSem);
        }
    } catch (Throwable $e) {
        $periodoPayload = ['nombre'=>$cron['periodo_nombre'], 'anio'=>null, 'periodo'=>null];
    }

    // 9) Respuesta existente
    $respuestaId = null;
    if ($formPayload && $semestreObjetivoId) {
        $respuestaId = encontrarRespuestaExistente($conexion, $id_py, (int)$formPayload['id'], (int)$semestreObjetivoId);
    }

    return [
        'inicio'     => $fecha_inicio_txt,
        'fin'        => $fecha_fin_txt,
        'cantidad'   => $cantidad,
        'semestres'  => $chips,
        'interfaz'   => $interfaz,
        'ahora_lima' => $ahora->format('Y-m-d H:i:s'),
        'apertura'   => $apertura->format('Y-m-d H:i:s'),
        'cierre'     => $cierre->format('Y-m-d H:i:s'),
        'motivo'     => $motivo,
        'sync'       => $syncResumen,
        'form_activo'          => $formPayload,
        'periodo_activo'       => $periodoPayload,
        'semestre_objetivo_id' => $semestreObjetivoId,
        'respuesta_id'         => $respuestaId,
        'cronograma_id'        => (int)$cron['id'],
    ];
}
