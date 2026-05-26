<?php
declare(strict_types=1);

include_once __DIR__ . '/../configSesion.php';
include_once __DIR__ . '/../../includes/api_dirsu/semester_audit_service.php';

date_default_timezone_set('America/Lima');

function cp_set_flash(string $type, string $msg): void
{
    $_SESSION['crear_proyecto_msg_type'] = $type;
    $_SESSION['crear_proyecto_msg'] = $msg;
}

function cp_redirect_datos_principales(): void
{
    $target = '../../vistas/datos_principales.php';
    if (!headers_sent()) {
        header('Location: ' . $target);
    } else {
        echo "<script>location.assign(" . json_encode($target) . ");</script>";
    }
    exit;
}

function cp_now_lima(): DateTimeImmutable
{
    return new DateTimeImmutable('now', new DateTimeZone('America/Lima'));
}

function cp_datetime_parse(?string $value): ?DateTimeImmutable
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    try {
        return new DateTimeImmutable($value, new DateTimeZone('America/Lima'));
    } catch (Throwable $e) {
        return null;
    }
}

function cp_valid_ymd(string $value): ?DateTimeImmutable
{
    $value = trim((string)$value);
    if ($value === '') {
        return null;
    }

    $dt = DateTimeImmutable::createFromFormat('Y-m-d', $value, new DateTimeZone('America/Lima'));
    if (!($dt instanceof DateTimeImmutable)) {
        return null;
    }
    if ($dt->format('Y-m-d') !== $value) {
        return null;
    }

    return $dt;
}

function cp_build_in_clause(array $ids): string
{
    $clean = array();
    $source = is_array($ids) ? $ids : array();
    $i = 0;
    for ($i = 0; $i < count($source); $i++) {
        $id = (int)$source[$i];
        if ($id > 0) {
            $clean[$id] = $id;
        }
    }

    if (empty($clean)) {
        return '';
    }

    return implode(',', array_values($clean));
}

function cp_fetch_active_presentation_context(mysqli $conexion): ?array
{
    $sql = "
        SELECT
            c.id AS cronograma_id,
            c.id_periodo,
            p.nombre AS periodo_nombre,
            c.apertura,
            c.cierre,
            f.id AS formulario_id,
            f.nombre AS formulario_nombre,
            rg.id AS regla_id,
            rg.inicio AS regla_inicio,
            rg.fin AS regla_fin
        FROM sm_cronogramas c
        INNER JOIN periodos p
            ON p.id = c.id_periodo
        LEFT JOIN (
            SELECT sf1.id, sf1.id_cronograma, sf1.nombre
            FROM sm_formularios sf1
            INNER JOIN (
                SELECT id_cronograma, MAX(id) AS max_id
                FROM sm_formularios
                WHERE activo = 1
                GROUP BY id_cronograma
            ) sfmax
                ON sfmax.id_cronograma = sf1.id_cronograma
               AND sfmax.max_id = sf1.id
        ) f
            ON f.id_cronograma = c.id
        LEFT JOIN (
            SELECT cg1.id, cg1.periodo, cg1.codigo, cg1.inicio, cg1.fin
            FROM cronogramas cg1
            INNER JOIN (
                SELECT periodo, codigo, MAX(id) AS max_id
                FROM cronogramas
                WHERE estado = 1
                  AND codigo = 'F1-GENERALIDADES'
                GROUP BY periodo, codigo
            ) cgmax
                ON cgmax.periodo = cg1.periodo
               AND cgmax.codigo = cg1.codigo
               AND cgmax.max_id = cg1.id
        ) rg
            ON rg.periodo = p.nombre
        WHERE c.activo = 1
          AND c.tipo = 1
          AND p.activo = 1
        ORDER BY c.apertura DESC, c.id DESC
        LIMIT 1
    ";

    $rs = mysqli_query($conexion, $sql);
    if (!($rs instanceof mysqli_result)) {
        return null;
    }
    $row = mysqli_fetch_assoc($rs) ?: null;
    mysqli_free_result($rs);
    return $row;
}

function cp_is_creation_allowed_for_user(mysqli $conexion, int $idUsuario): array
{
    $allowed = true;
    $pendingTitles = array();
    $projectIds = array();
    $projectNames = array();

    $sqlProjects = "
        SELECT p.id, COALESCE(NULLIF(TRIM(p.p2), ''), CONCAT('Proyecto ID ', p.id)) AS titulo
        FROM usuarios_proyectos up
        INNER JOIN proyectos p ON p.id = up.id_proyecto
        WHERE up.id_usuario = ?
          AND up.activo = 1
    ";
    $stmtProjects = $conexion->prepare($sqlProjects);
    if (!$stmtProjects) {
        return array(
            'ok' => false,
            'allowed' => false,
            'pending_titles' => array(),
            'error' => 'No se pudo validar proyectos del coordinador.'
        );
    }

    $stmtProjects->bind_param('i', $idUsuario);
    $stmtProjects->execute();
    $resProjects = $stmtProjects->get_result();
    if ($resProjects instanceof mysqli_result) {
        while ($row = $resProjects->fetch_assoc()) {
            $idp = isset($row['id']) ? (int)$row['id'] : 0;
            if ($idp <= 0) {
                continue;
            }
            $projectIds[$idp] = $idp;
            $projectNames[$idp] = (string)$row['titulo'];
        }
    }
    $stmtProjects->close();

    if (empty($projectIds)) {
        return array(
            'ok' => true,
            'allowed' => true,
            'pending_titles' => array(),
            'error' => ''
        );
    }

    $inClause = cp_build_in_clause(array_values($projectIds));
    if ($inClause === '') {
        return array(
            'ok' => true,
            'allowed' => true,
            'pending_titles' => array(),
            'error' => ''
        );
    }

    $approvalMap = array();
    $sqlFinal = "
        SELECT
            s.id_py,
            MAX(1) AS has_final_semester,
            MAX(CASE WHEN e.situacion = 'aprobado' THEN 1 ELSE 0 END) AS has_final_approved
        FROM sm_proyecto_semestres s
        LEFT JOIN sm_respuestas r
            ON r.id_semestre = s.id
        LEFT JOIN eva_evaluaciones e
            ON e.id_respuesta = r.id
        WHERE s.id_py IN (" . $inClause . ")
          AND s.tipo = 'semestral'
          AND COALESCE(s.vigente, 1) = 1
          AND COALESCE(s.final, 0) = 1
        GROUP BY s.id_py
    ";
    $rsFinal = mysqli_query($conexion, $sqlFinal);
    if ($rsFinal instanceof mysqli_result) {
        while ($row = mysqli_fetch_assoc($rsFinal)) {
            $idp = isset($row['id_py']) ? (int)$row['id_py'] : 0;
            if ($idp <= 0) {
                continue;
            }
            $approvalMap[$idp] = array(
                'has_final_semester' => ((int)($row['has_final_semester'] ?? 0) === 1),
                'has_final_approved' => ((int)($row['has_final_approved'] ?? 0) === 1)
            );
        }
        mysqli_free_result($rsFinal);
    }

    foreach ($projectIds as $idp) {
        $info = $approvalMap[$idp] ?? null;
        $isApproved = is_array($info)
            && !empty($info['has_final_semester'])
            && !empty($info['has_final_approved']);
        if (!$isApproved) {
            $allowed = false;
            $pendingTitles[] = $projectNames[$idp] ?? ('Proyecto ID ' . $idp);
        }
    }

    return array(
        'ok' => true,
        'allowed' => $allowed,
        'pending_titles' => $pendingTitles,
        'error' => ''
    );
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['crear_proyecto'])) {
    cp_redirect_datos_principales();
}

if (!($conexion instanceof mysqli)) {
    cp_set_flash('danger', 'No se pudo conectar con el sistema en este momento.');
    cp_redirect_datos_principales();
}

$idRol = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$usuario = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
if ($idRol !== 2 || $usuario === '') {
    cp_set_flash('danger', 'Solo coordinadores de proyecto pueden registrar nuevos proyectos.');
    cp_redirect_datos_principales();
}

$email = strtolower(trim((string)($_POST['email'] ?? '')));
$telefono = trim((string)($_POST['telefono'] ?? ''));
$tituloProyecto = trim((string)($_POST['p2'] ?? ''));
$fechaInicioRaw = trim((string)($_POST['fecha_inicio'] ?? ''));
$fechaFinRaw = trim((string)($_POST['fecha_fin'] ?? ''));
$aceptoCompromiso = isset($_POST['acepto_compromiso']) ? (int)$_POST['acepto_compromiso'] : 0;

if (!preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i', $email)) {
    cp_set_flash('danger', 'El correo debe ser institucional con dominio @unitru.edu.pe.');
    cp_redirect_datos_principales();
}
if (!preg_match('/^9\d{8}$/', $telefono)) {
    cp_set_flash('danger', 'El teléfono debe tener 9 dígitos y empezar con 9.');
    cp_redirect_datos_principales();
}

if ($tituloProyecto === '') {
    cp_set_flash('danger', 'Debes completar el campo 2. Título del Proyecto.');
    cp_redirect_datos_principales();
}
$tituloLen = function_exists('mb_strlen') ? mb_strlen($tituloProyecto, 'UTF-8') : strlen($tituloProyecto);
if ($tituloLen > 300) {
    cp_set_flash('danger', 'El título del proyecto supera el máximo permitido de 300 caracteres.');
    cp_redirect_datos_principales();
}
$fechaInicio = cp_valid_ymd($fechaInicioRaw);
$fechaFin = cp_valid_ymd($fechaFinRaw);
if (!($fechaInicio instanceof DateTimeImmutable) || !($fechaFin instanceof DateTimeImmutable)) {
    cp_set_flash('danger', 'Debes registrar fechas válidas de inicio y fin del proyecto.');
    cp_redirect_datos_principales();
}
if ($fechaInicioRaw === $fechaFinRaw) {
    cp_set_flash('danger', 'Las fechas no pueden ser iguales.');
    cp_redirect_datos_principales();
}
if ($fechaFin <= $fechaInicio) {
    cp_set_flash('danger', 'La fecha de fin debe ser posterior a la fecha de inicio.');
    cp_redirect_datos_principales();
}
if ($aceptoCompromiso !== 1) {
    cp_set_flash('danger', 'Debes aceptar el compromiso para registrar el proyecto.');
    cp_redirect_datos_principales();
}

$previewSem = rsu_api_semester_audit_build_expected_rows($fechaInicioRaw, $fechaFinRaw);
if (!is_array($previewSem) || empty($previewSem['ok']) || !isset($previewSem['rows']) || !is_array($previewSem['rows'])) {
    cp_set_flash('danger', 'No fue posible calcular los semestres del proyecto para ese rango.');
    cp_redirect_datos_principales();
}
$totalSemestres = 0;
foreach ($previewSem['rows'] as $rowSem) {
    if (isset($rowSem['tipo']) && (string)$rowSem['tipo'] === 'semestral') {
        $totalSemestres++;
    }
}
if ($totalSemestres < 4 || $totalSemestres > 10) {
    cp_set_flash('danger', 'La duración del proyecto debe estar entre 4 y 10 semestres (de 2 a 5 años).');
    cp_redirect_datos_principales();
}

$sqlUser = "SELECT id FROM usuarios WHERE usuario = ? LIMIT 1";
$stmtUser = $conexion->prepare($sqlUser);
if (!$stmtUser) {
    cp_set_flash('danger', 'No se pudo validar la cuenta del coordinador.');
    cp_redirect_datos_principales();
}
$stmtUser->bind_param('s', $usuario);
$stmtUser->execute();
$stmtUser->bind_result($idUsuario);
$existsUser = $stmtUser->fetch();
$stmtUser->close();

if (!$existsUser || (int)$idUsuario <= 0) {
    cp_set_flash('danger', 'No se encontró al coordinador autenticado.');
    cp_redirect_datos_principales();
}
$idUsuario = (int)$idUsuario;

$creationGate = cp_is_creation_allowed_for_user($conexion, $idUsuario);
if (empty($creationGate['ok'])) {
    cp_set_flash('danger', (string)$creationGate['error']);
    cp_redirect_datos_principales();
}
if (empty($creationGate['allowed'])) {
    $pending = isset($creationGate['pending_titles']) && is_array($creationGate['pending_titles'])
        ? $creationGate['pending_titles']
        : array();
    $msg = 'La creación de proyecto está bloqueada hasta completar informes finales pendientes.';
    if (!empty($pending)) {
        $msg .= ' Pendientes: ' . implode(' | ', $pending) . '.';
    }
    cp_set_flash('warning', $msg);
    cp_redirect_datos_principales();
}

$ctx = cp_fetch_active_presentation_context($conexion);
if (!is_array($ctx)) {
    cp_set_flash('danger', 'No hay cronograma activo de Presentación de proyecto configurado.');
    cp_redirect_datos_principales();
}

$cronogramaId = isset($ctx['cronograma_id']) ? (int)$ctx['cronograma_id'] : 0;
$periodoId = isset($ctx['id_periodo']) ? (int)$ctx['id_periodo'] : 0;
$periodoNombre = trim((string)($ctx['periodo_nombre'] ?? ''));
$formularioId = isset($ctx['formulario_id']) ? (int)$ctx['formulario_id'] : 0;
$formularioNombre = trim((string)($ctx['formulario_nombre'] ?? ''));

if ($cronogramaId <= 0 || $periodoId <= 0 || $periodoNombre === '') {
    cp_set_flash('danger', 'La configuración del cronograma de presentación es inválida.');
    cp_redirect_datos_principales();
}
if ($formularioId <= 0 || $formularioNombre === '') {
    cp_set_flash('danger', 'No hay formulario activo vinculado al cronograma de presentación.');
    cp_redirect_datos_principales();
}

$now = cp_now_lima();
$apertura = cp_datetime_parse((string)($ctx['apertura'] ?? ''));
$cierre = cp_datetime_parse((string)($ctx['cierre'] ?? ''));
if (!($apertura instanceof DateTimeImmutable) || !($cierre instanceof DateTimeImmutable)) {
    cp_set_flash('danger', 'El cronograma de presentación no tiene fechas válidas.');
    cp_redirect_datos_principales();
}
if ($now < $apertura || $now > $cierre) {
    cp_set_flash('warning', 'La convocatoria de Presentación de proyecto no está abierta en este momento.');
    cp_redirect_datos_principales();
}

$reglaId = isset($ctx['regla_id']) ? (int)$ctx['regla_id'] : 0;
$reglaInicio = cp_datetime_parse((string)($ctx['regla_inicio'] ?? ''));
$reglaFin = cp_datetime_parse((string)($ctx['regla_fin'] ?? ''));
if ($reglaId <= 0 || !($reglaInicio instanceof DateTimeImmutable) || !($reglaFin instanceof DateTimeImmutable)) {
    cp_set_flash('danger', 'No existe una regla activa para F1-GENERALIDADES en este período.');
    cp_redirect_datos_principales();
}
if ($now < $reglaInicio || $now > $reglaFin) {
    cp_set_flash('warning', 'La interfaz de Generalidades no está habilitada en este momento.');
    cp_redirect_datos_principales();
}

$conexion->begin_transaction();
try {
    $sqlContacto = "
      INSERT INTO usuario_contactos (usuario, email, telefono, created_at, updated_at)
      VALUES (?, ?, ?, NOW(), NOW())
      ON DUPLICATE KEY UPDATE
        email = VALUES(email),
        telefono = VALUES(telefono),
        updated_at = NOW()
    ";
    $stmtContacto = $conexion->prepare($sqlContacto);
    if (!$stmtContacto) {
        throw new RuntimeException('No se pudo preparar la actualización de contacto.');
    }
    $stmtContacto->bind_param('sss', $usuario, $email, $telefono);
    if (!$stmtContacto->execute()) {
        throw new RuntimeException('No se pudo registrar/actualizar contacto.');
    }
    $stmtContacto->close();

    $sqlInsertProyecto = "INSERT INTO proyectos (p2, fecha_inicio, fecha_fin) VALUES (?, ?, ?)";
    $stmtProyecto = $conexion->prepare($sqlInsertProyecto);
    if (!$stmtProyecto) {
        throw new RuntimeException('No se pudo preparar el registro del proyecto.');
    }
    $stmtProyecto->bind_param('sss', $tituloProyecto, $fechaInicioRaw, $fechaFinRaw);
    if (!$stmtProyecto->execute()) {
        $stmtProyecto->close();
        throw new RuntimeException('No se pudo crear el proyecto.');
    }
    $stmtProyecto->close();
    $nuevoIdProyecto = (int)$conexion->insert_id;
    if ($nuevoIdProyecto <= 0) {
        throw new RuntimeException('No se obtuvo el identificador del proyecto creado.');
    }

    $stmtUpdateUsuario = $conexion->prepare("UPDATE usuarios SET id_py = ? WHERE id = ?");
    if (!$stmtUpdateUsuario) {
        throw new RuntimeException('No se pudo actualizar el proyecto activo del usuario.');
    }
    $stmtUpdateUsuario->bind_param('ii', $nuevoIdProyecto, $idUsuario);
    if (!$stmtUpdateUsuario->execute()) {
        throw new RuntimeException('No se pudo asociar el proyecto activo al coordinador.');
    }
    $stmtUpdateUsuario->close();

    $stmtUP = $conexion->prepare("INSERT INTO usuarios_proyectos (id_usuario, id_proyecto) VALUES (?, ?)");
    if (!$stmtUP) {
        throw new RuntimeException('No se pudo preparar la relación coordinador-proyecto.');
    }
    $stmtUP->bind_param('ii', $idUsuario, $nuevoIdProyecto);
    if (!$stmtUP->execute()) {
        throw new RuntimeException('No se pudo crear la relación coordinador-proyecto.');
    }
    $stmtUP->close();

    $stmtPP = $conexion->prepare("INSERT INTO proyectos_periodo (id_py, id_periodo) VALUES (?, ?)");
    if (!$stmtPP) {
        throw new RuntimeException('No se pudo preparar la relación proyecto-período.');
    }
    $stmtPP->bind_param('ii', $nuevoIdProyecto, $periodoId);
    if (!$stmtPP->execute()) {
        throw new RuntimeException('No se pudo vincular el proyecto al período del cronograma.');
    }
    $stmtPP->close();

    $descripcion = 'Se creó el proyecto con ID: ' . $nuevoIdProyecto
        . ' para el período: ' . $periodoNombre
        . '. Título: ' . $tituloProyecto
        . '. Inicio: ' . $fechaInicioRaw
        . '. Fin: ' . $fechaFinRaw
        . ' (Cronograma presentación #' . $cronogramaId . ', Formulario #' . $formularioId . ').';
    $fechaActual = $now->format('Y-m-d H:i:s');
    $stmtHist = $conexion->prepare("INSERT INTO historial_proyectos (descripcion, fecha, id_py) VALUES (?, ?, ?)");
    if (!$stmtHist) {
        throw new RuntimeException('No se pudo preparar el historial del proyecto.');
    }
    $stmtHist->bind_param('ssi', $descripcion, $fechaActual, $nuevoIdProyecto);
    if (!$stmtHist->execute()) {
        throw new RuntimeException('No se pudo registrar el historial de creación del proyecto.');
    }
    $stmtHist->close();

    $_SESSION['id_py'] = $nuevoIdProyecto;

    if (!$conexion->commit()) {
        throw new RuntimeException('No se pudo confirmar la creación del proyecto.');
    }

    unset($_SESSION['crear_proyecto_msg'], $_SESSION['crear_proyecto_msg_type']);
    cp_redirect_datos_principales();
} catch (Throwable $e) {
    $conexion->rollback();
    cp_set_flash('danger', 'No se pudo crear el proyecto: ' . $e->getMessage());
    cp_redirect_datos_principales();
}
