<?php
// acciones/registro_usuario.php
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/conexion.php';
require_once __DIR__ . '/../includes/gestion_archivos.php';

function json_out($ok, $msg, $extra = array(), $http = 200) {
    http_response_code($http);
    $out = array('ok' => (bool)$ok, 'msg' => (string)$msg);
    foreach ($extra as $k => $v) { $out[$k] = $v; }
    echo json_encode($out);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_out(false, 'Método no permitido.', array(), 405);
}

if (!csrf_validate(isset($_POST['csrf']) ? $_POST['csrf'] : null)) {
    json_out(false, 'CSRF inválido. Recarga la página.', array(), 400);
}

try {
    $nombres   = isset($_POST['nombres']) ? trim($_POST['nombres']) : '';
    $apellidos = isset($_POST['apellidos']) ? trim($_POST['apellidos']) : '';
    $dni       = isset($_POST['dni']) ? trim($_POST['dni']) : '';
    $pass      = isset($_POST['password']) ? (string)$_POST['password'] : '';
    $rol_id    = isset($_POST['rol_id']) ? (int)$_POST['rol_id'] : 0;

    if ($nombres === '' || $apellidos === '' || $pass === '') {
        json_out(false, 'Completa nombres, apellidos y contraseña.', array(), 400);
    }

    if (!preg_match('/^\d{8}$/', $dni)) {
        json_out(false, 'DNI inválido. Debe tener 8 números.', array(), 400);
    }

    if ($rol_id <= 0) {
        json_out(false, 'Selecciona un rol.', array(), 400);
    }

    $mysqli = db();

    // Validar rol
    $stRol = $mysqli->prepare("SELECT id FROM l2601_roles WHERE id=? LIMIT 1");
    $stRol->bind_param("i", $rol_id);
    $stRol->execute();
    $stRol->store_result();
    if ($stRol->num_rows !== 1) {
        $stRol->close();
        json_out(false, 'Rol inválido.', array(), 400);
    }
    $stRol->close();

    // Verificar DNI no exista
    $st = $mysqli->prepare("SELECT id FROM l2601_usuarios WHERE dni=? LIMIT 1");
    $st->bind_param("s", $dni);
    $st->execute();
    $st->store_result();
    if ($st->num_rows > 0) {
        $st->close();
        json_out(false, 'Ese DNI ya está registrado.', array(), 409);
    }
    $st->close();

    // Foto opcional
    $fotoRel = null;
    if (isset($_FILES['foto_perfil']) && !empty($_FILES['foto_perfil']['name'])) {

        $maxBytes = 2 * 1024 * 1024; // 2MB
        $size = isset($_FILES['foto_perfil']['size']) ? (int)$_FILES['foto_perfil']['size'] : 0;
        if ($size > $maxBytes) {
            json_out(false, 'La foto supera 2MB.', array(), 400);
        }

        $ext = strtolower(pathinfo((string)$_FILES['foto_perfil']['name'], PATHINFO_EXTENSION));
        $permitidas = array('jpg','jpeg','png','webp');
        if (!in_array($ext, $permitidas, true)) {
            json_out(false, 'Formato no permitido. Usa jpg, jpeg, png o webp.', array(), 400);
        }

        // Guarda en almacen/.../foto_perfil/
        $up = ga_save_upload($mysqli, $_FILES['foto_perfil'], 'foto_perfil', 'perfil', 'usuarios', 'usuario', $dni);
        $fotoRel = $up['ruta_relativa'];
    }

    $hash = password_hash($pass, PASSWORD_DEFAULT);

    $sql = "INSERT INTO l2601_usuarios (nombres, apellidos, dni, clave_hash, rol_id, foto_perfil)
            VALUES (?,?,?,?,?,?)";
    $st2 = $mysqli->prepare($sql);
    $st2->bind_param("ssssis", $nombres, $apellidos, $dni, $hash, $rol_id, $fotoRel);
    $st2->execute();
    $st2->close();

    json_out(true, 'Usuario creado correctamente.');
} catch (Exception $e) {
    // Respuesta JSON aunque explote
    json_out(false, 'Error interno del servidor.', array('detalle' => $e->getMessage()), 500);
}
