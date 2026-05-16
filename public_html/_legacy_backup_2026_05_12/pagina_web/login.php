<?php
require __DIR__.'/../inc/app_boot.php';

$err = '';
if ($_SERVER['REQUEST_METHOD']==='POST') {
  $user = trim($_POST['usuario'] ?? '');
  $pass = $_POST['clave'] ?? '';
  if ($user==='' || $pass==='') {
    $err = 'Complete usuario y contraseña.';
  } else {
    if (iniciar_sesion($mysqli, $user, $pass)) {
      $ret = isset($_GET['r']) ? $_GET['r'] : url('index.php');
      header('Location: '.$ret);
      exit;
    } else {
      $err = 'Usuario o contraseña incorrectos.';
    }
  }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Iniciar sesión</title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
.login-card{max-width:420px;margin:8vh auto;}
.eye{cursor:pointer; position:absolute; right:12px; top:9px; color:#666;}
</style>
</head>
<body class="bg-light">
<div class="container">
  <div class="card login-card shadow">
    <div class="card-header bg-success text-white">Iniciar sesión</div>
    <div class="card-body">
      <?php if($err): ?><div class="alert alert-danger"><?= h($err) ?></div><?php endif; ?>
      <form method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Usuario</label>
          <input type="text" name="usuario" class="form-control" maxlength="60" required>
        </div>
        <div class="mb-3 position-relative">
          <label class="form-label">Contraseña</label>
          <input type="password" name="clave" id="clave" class="form-control" required>
          <span class="eye" onclick="toggleClave()"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" fill="currentColor" viewBox="0 0 16 16"><path d="M16 8s-3-5.5-8-5.5S0 8 0 8s3 5.5 8 5.5S16 8 16 8z"/><path d="M8 5a3 3 0 1 0 0 6 3 3 0 0 0 0-6"/></svg></span>
        </div>
        <button class="btn btn-success w-100">Entrar</button>
      </form>
      <div class="text-center mt-3"><a href="<?= h(url('index.php')) ?>">← Volver</a></div>
    </div>
  </div>
</div>
<script>
function toggleClave(){
  const i = document.getElementById('clave');
  i.type = (i.type==='password') ? 'text' : 'password';
}
</script>
</body>
</html>
