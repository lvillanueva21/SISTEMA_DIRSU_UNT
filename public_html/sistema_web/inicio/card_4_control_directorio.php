<?php
// sistema_web/inicio/card_4_control_directorio.php
// Se incluye DENTRO del card 4. No dibuja otro card.

if (session_status() !== PHP_SESSION_ACTIVE) { session_start(); }
$idRol   = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : null;
if ($idRol !== 1) {
  echo '<div class="text-muted small">Acceso restringido.</div>';
  return;
}

// DB
if (!isset($conexion)) { include_once __DIR__ . '/../componentes/db.php'; }

// Mensajes flash
$dirMsg = $_SESSION['dir_msg']      ?? null;
$dirTyp = $_SESSION['dir_msg_type'] ?? null;
unset($_SESSION['dir_msg'], $_SESSION['dir_msg_type']);

// CSRF mínimo
if (empty($_SESSION['csrf_dir'])) {
  $_SESSION['csrf_dir'] = bin2hex(random_bytes(16));
}
$CSRF = $_SESSION['csrf_dir'];

// --- Helpers ---
function dir_flash($msg, $type='success'){
  $_SESSION['dir_msg'] = $msg;
  $_SESSION['dir_msg_type'] = $type;
}
function go_back(){
  $back = $_SERVER['HTTP_REFERER'] ?? '/sistema_web/direccion_rsu/inicio.php';
  header("Location: $back");
  exit;
}

// Normalizar búsqueda (tokens por espacio)
$q = trim((string)($_GET['dir_q'] ?? ''));
$tokens = array_values(array_filter(preg_split('/\s+/', $q)));
$limit = 100; // top N

// Acciones POST: delete_one, delete_all, edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  $action = $_POST['action'] ?? '';
  $csrf   = $_POST['csrf'] ?? '';
  if (!hash_equals($_SESSION['csrf_dir'], (string)$csrf)) {
    dir_flash('Token inválido. Recarga la página e inténtalo otra vez.', 'danger');
    go_back();
  }

  if ($action === 'delete_one') {
    $id = (int)($_POST['id'] ?? 0);
    if ($id <= 0) { dir_flash('ID inválido.', 'danger'); go_back(); }
    if (!($st = $conexion->prepare("DELETE FROM directorio WHERE id=? LIMIT 1"))) {
      dir_flash('Error interno (prep delete).','danger'); go_back();
    }
    $st->bind_param("i", $id);
    if (!$st->execute()) { $st->close(); dir_flash('No se pudo borrar.','danger'); go_back(); }
    $st->close();
    dir_flash('Registro eliminado correctamente.');
    go_back();
  }

  if ($action === 'delete_all') {
    $qdel = trim((string)($_POST['q'] ?? ''));
    if ($qdel === '') {
      dir_flash('Para borrar en lote, especifica un criterio de búsqueda.', 'danger'); go_back();
    }
    // Construir WHERE por tokens (accent/case-insensitive vía collation)
    $toks = array_values(array_filter(preg_split('/\s+/', $qdel)));
    $w = []; $params = []; $types = '';
    foreach ($toks as $t) {
      $like = '%'.$t.'%';
      $w[] = "(nombres COLLATE utf8mb4_unicode_ci LIKE ? 
            OR apellidos COLLATE utf8mb4_unicode_ci LIKE ?
            OR email COLLATE utf8mb4_unicode_ci LIKE ?
            OR correo_asistente COLLATE utf8mb4_unicode_ci LIKE ?)";
      array_push($params, $like, $like, $like, $like);
      $types .= 'ssss';
    }
    $where = $w ? ('WHERE '.implode(' AND ', $w)) : '';
    $sql = "DELETE FROM directorio $where";
    if (!($st = $conexion->prepare($sql))) { dir_flash('Error interno (prep delete all).','danger'); go_back(); }
    $st->bind_param($types, ...$params);
    if (!$st->execute()) { $st->close(); dir_flash('No se pudo borrar en lote.','danger'); go_back(); }
    $affected = $st->affected_rows;
    $st->close();
    dir_flash("Se eliminaron $affected registros.");
    go_back();
  }

  if ($action === 'edit') {
    // Campos
    $id     = (int)($_POST['id'] ?? 0);
    $email  = strtolower(trim((string)($_POST['email'] ?? '')));
    $telefono = trim((string)($_POST['telefono'] ?? ''));
    $nombres  = trim((string)($_POST['nombres'] ?? ''));
    $apellidos= trim((string)($_POST['apellidos'] ?? ''));
    $telA     = trim((string)($_POST['telefono_asistente'] ?? ''));
    $mailA    = trim((string)($_POST['correo_asistente'] ?? ''));
    $idRolEd  = (int)($_POST['id_rol'] ?? 0);

    if ($id<=0) { dir_flash('ID inválido.','danger'); go_back(); }

    // Uppercase server-side
    if (function_exists('mb_strtoupper')) {
      $nombres   = mb_strtoupper($nombres, 'UTF-8');
      $apellidos = mb_strtoupper($apellidos,'UTF-8');
    } else { $nombres = strtoupper($nombres); $apellidos = strtoupper($apellidos); }

    // Validaciones básicas
    if (!preg_match('/^[A-ZÁÉÍÓÚÜÑ\'\.\-\s]{2,100}$/u',$nombres))   { dir_flash('Nombres inválidos.','danger'); go_back(); }
    if (!preg_match('/^[A-ZÁÉÍÓÚÜÑ\'\.\-\s]{2,100}$/u',$apellidos)) { dir_flash('Apellidos inválidos.','danger'); go_back(); }
    if (!preg_match('/^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i',$email)) { dir_flash('Correo debe ser @unitru.edu.pe','danger'); go_back(); }
    if (!preg_match('/^9\d{8}$/',$telefono))                        { dir_flash('Teléfono inválido.','danger'); go_back(); }
    if ($telA!=='' && !preg_match('/^9\d{8}$/',$telA))              { dir_flash('Tel. asistente inválido.','danger'); go_back(); }
    if ($mailA!=='' && !preg_match('/^[^\s@]+@[^\s@]+\.[^\s@]+$/',$mailA)) { dir_flash('Correo asistente inválido.','danger'); go_back(); }

    // Rol válido
    $okRol = false;
    if ($st = $conexion->prepare("SELECT 1 FROM rol WHERE id=? LIMIT 1")) {
      $st->bind_param("i", $idRolEd); $st->execute(); $okRol = (bool)$st->get_result()->fetch_row(); $st->close();
    }
    if (!$okRol){ dir_flash('Rol inválido.','danger'); go_back(); }

    // Update
    $sql = "UPDATE directorio
            SET id_rol=?, email=?, telefono=?, nombres=?, apellidos=?, 
                telefono_asistente=NULLIF(?,''), correo_asistente=NULLIF(?,''), updated_at=NOW()
            WHERE id=? LIMIT 1";
    if (!($st = $conexion->prepare($sql))) { dir_flash('Error interno (prep update).','danger'); go_back(); }
    $st->bind_param("issssssi", $idRolEd, $email, $telefono, $nombres, $apellidos, $telA, $mailA, $id);
    if (!$st->execute()) { $st->close(); dir_flash('No se pudo actualizar.','danger'); go_back(); }
    $st->close();
    dir_flash('Registro actualizado correctamente.');
    go_back();
  }

  // Si no fue ninguna acción válida:
  dir_flash('Acción no reconocida.','danger');
  go_back();
}

// --- Listado con búsqueda ---
$roles = [];
$resR = $conexion->query("SELECT id, nombre FROM rol ORDER BY id ASC");
if ($resR) { while($r = $resR->fetch_assoc()){ $roles[(int)$r['id']] = $r['nombre']; } }

$where = ''; $params=[]; $types='';
if ($tokens) {
  $w = [];
  foreach ($tokens as $t) {
    $like = '%'.$t.'%';
    $w[] = "(nombres  COLLATE utf8mb4_unicode_ci LIKE ?
          OR apellidos COLLATE utf8mb4_unicode_ci LIKE ?
          OR email     COLLATE utf8mb4_unicode_ci LIKE ?
          OR correo_asistente COLLATE utf8mb4_unicode_ci LIKE ?)";
    array_push($params, $like,$like,$like,$like);
    $types .= 'ssss';
  }
  $where = 'WHERE '.implode(' AND ', $w);
}
$sql = "SELECT d.*, r.nombre AS rol_nombre
        FROM directorio d
        LEFT JOIN rol r ON r.id = d.id_rol
        $where
        ORDER BY d.updated_at DESC
        LIMIT ?";
$params[] = $limit; $types .= 'i';

$st = $conexion->prepare($sql);
$st->bind_param($types, ...$params);
$st->execute();
$res = $st->get_result();
$rows = $res ? $res->fetch_all(MYSQLI_ASSOC) : [];
$st->close();

$count = count($rows);
?>

<?php if ($dirMsg): ?>
  <div class="alert alert-<?= $dirTyp==='danger'?'danger':'success' ?> py-2 mb-2"><?= htmlspecialchars($dirMsg) ?></div>
<?php endif; ?>

<form class="form-inline mb-2" method="get" action="">
  <div class="input-group input-group-sm mr-2" style="flex:1;">
    <input type="text" class="form-control" name="dir_q" value="<?= htmlspecialchars($q) ?>"
           placeholder="Buscar por nombres, apellidos o correos…">
    <div class="input-group-append">
      <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-search"></i></button>
      <a class="btn btn-outline-secondary" href="<?= htmlspecialchars(preg_replace('/\?.*/','', $_SERVER['REQUEST_URI'])) ?>"><i class="fas fa-times"></i></a>
    </div>
  </div>

  <div class="btn-group btn-group-sm">
    <button type="button" class="btn btn-danger"
            <?= $q==='' || $count===0 ? 'disabled' : '' ?>
            onclick="if(confirm('¿Eliminar TODOS los resultados actuales? Esta acción no se puede deshacer.')){ document.getElementById('delAllForm').submit(); }">
      <i class="fas fa-trash-alt"></i> Eliminar todos (<?= (int)$count ?>)
    </button>
  </div>
</form>

<form id="delAllForm" method="post" action="">
  <input type="hidden" name="action" value="delete_all">
  <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
  <input type="hidden" name="q" value="<?= htmlspecialchars($q) ?>">
</form>

<div class="table-responsive">
  <table class="table table-sm table-hover mb-2">
    <thead class="thead-light">
      <tr>
        <th style="min-width:160px;">Nombre</th>
        <th>Email</th>
        <th>Teléfono</th>
        <th>Asistente</th>
        <th>Rol</th>
        <th class="text-right" style="width:120px;">Acciones</th>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td colspan="6" class="text-muted small">No hay resultados.</td></tr>
      <?php else: foreach ($rows as $r): ?>
        <tr>
          <td><?= htmlspecialchars(trim(($r['nombres']??'').' '.($r['apellidos']??''))) ?></td>
          <td><?= htmlspecialchars($r['email'] ?? '') ?></td>
          <td><?= htmlspecialchars($r['telefono'] ?? '') ?></td>
          <td class="small">
            <div><?= htmlspecialchars($r['telefono_asistente'] ?: '—') ?></div>
            <div><?= htmlspecialchars($r['correo_asistente'] ?: '—') ?></div>
          </td>
          <td class="small"><?= htmlspecialchars($r['rol_nombre'] ?? ('ID '.$r['id_rol'])) ?></td>
          <td class="text-right">
            <div class="btn-group btn-group-sm">
              <button class="btn btn-outline-primary"
                data-toggle="modal" data-target="#editDirModal"
                data-id="<?= (int)$r['id'] ?>"
                data-nombres="<?= htmlspecialchars($r['nombres']) ?>"
                data-apellidos="<?= htmlspecialchars($r['apellidos']) ?>"
                data-email="<?= htmlspecialchars($r['email']) ?>"
                data-telefono="<?= htmlspecialchars($r['telefono']) ?>"
                data-tela="<?= htmlspecialchars($r['telefono_asistente'] ?? '') ?>"
                data-maila="<?= htmlspecialchars($r['correo_asistente'] ?? '') ?>"
                data-idrol="<?= (int)$r['id_rol'] ?>">
                <i class="fas fa-edit"></i>
              </button>
              <form method="post" action="" onsubmit="return confirm('¿Eliminar este registro?');">
                <input type="hidden" name="action" value="delete_one">
                <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button type="submit" class="btn btn-outline-danger"><i class="fas fa-trash"></i></button>
              </form>
            </div>
          </td>
        </tr>
      <?php endforeach; endif; ?>
    </tbody>
  </table>
</div>

<!-- Modal para EDITAR -->
<div class="modal fade" id="editDirModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document"><div class="modal-content">
    <div class="modal-header py-2">
      <h6 class="modal-title mb-0"><i class="fas fa-user-edit"></i> Editar contacto</h6>
      <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
    </div>
    <form method="post" action="" id="editDirForm" novalidate>
      <div class="modal-body">
        <input type="hidden" name="action" value="edit">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars($CSRF) ?>">
        <input type="hidden" name="id" id="ed_id">

        <div class="form-row">
          <div class="form-group col-md-6">
            <label class="mb-0">Nombres (MAYÚSCULA)</label>
            <input type="text" class="form-control" name="nombres" id="ed_nombres" required minlength="2" maxlength="100">
          </div>
          <div class="form-group col-md-6">
            <label class="mb-0">Apellidos (MAYÚSCULA)</label>
            <input type="text" class="form-control" name="apellidos" id="ed_apellidos" required minlength="2" maxlength="100">
          </div>
          <div class="form-group col-md-6">
            <label class="mb-0">Correo institucional</label>
            <input type="email" class="form-control" name="email" id="ed_email" required>
          </div>
          <div class="form-group col-md-6">
            <label class="mb-0">Teléfono</label>
            <input type="tel" class="form-control" name="telefono" id="ed_tel" required minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
          </div>
          <div class="form-group col-md-6">
            <label class="mb-0">Tel. asistente <small class="text-muted">(opcional)</small></label>
            <input type="tel" class="form-control" name="telefono_asistente" id="ed_tela" minlength="9" maxlength="9" pattern="^9\d{8}$" inputmode="numeric">
          </div>
          <div class="form-group col-md-6">
            <label class="mb-0">Correo asistente <small class="text-muted">(opcional)</small></label>
            <input type="email" class="form-control" name="correo_asistente" id="ed_maila">
          </div>
          <div class="form-group col-12">
            <label class="mb-0">Rol</label>
            <select class="form-control" name="id_rol" id="ed_idrol" required>
              <?php foreach ($roles as $rid=>$rname): ?>
                <option value="<?= (int)$rid ?>"><?= htmlspecialchars($rname) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>

      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="submit" class="btn btn-primary btn-sm">Guardar cambios</button>
      </div>
    </form>
  </div></div>
</div>

<script>
(function(){
  // Rellenar modal de edición
  $('#editDirModal').on('show.bs.modal', function (e) {
    var btn = $(e.relatedTarget);
    $('#ed_id').val(btn.data('id'));
    $('#ed_nombres').val((btn.data('nombres')||'').toString().toUpperCase());
    $('#ed_apellidos').val((btn.data('apellidos')||'').toString().toUpperCase());
    $('#ed_email').val(btn.data('email')||'');
    $('#ed_tel').val(btn.data('telefono')||'');
    $('#ed_tela').val(btn.data('tela')||'');
    $('#ed_maila').val(btn.data('maila')||'');
    $('#ed_idrol').val(btn.data('idrol')||'');
  });

  // Forzar mayúsculas y sanitizar teléfonos
  $('#ed_nombres, #ed_apellidos').on('input', function(){ this.value = (this.value||'').toUpperCase(); });
  $('#ed_tel, #ed_tela').on('input', function(){ this.value = (this.value||'').replace(/\D/g,'').slice(0,9); });
})();
</script>
