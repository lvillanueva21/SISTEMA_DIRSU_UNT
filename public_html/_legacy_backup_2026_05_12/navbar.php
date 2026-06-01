<?php
require_once __DIR__.'/cw_config.php';

/* 1) Leemos TODO el menú en un solo query.
   - NULL = elemento de primer nivel
   - Se ordena por parent_id (NULL primero) y luego por orden */
$sql = "SELECT id, texto, url, parent_id, visible, orden
        FROM cw_opciones_menu
        WHERE visible = 1
        ORDER BY parent_id, orden";

$res = $mysqli->query($sql);

/* 2) Separamos padres e hijos en dos arrays y luego los unimos.
      Así evitamos el problema de que un hijo llegue antes que su padre */
$padres   = [];   // id => datos + children
$pendientes = []; // lista de hijos mientras no conozcamos al padre

while ($row = $res->fetch_assoc()) {
    if ($row['parent_id'] === null) {
        $row['children'] = [];
        $padres[$row['id']] = $row;
    } else {
        $pendientes[] = $row;
    }
}

/* 3) Ahora sí añadimos cada hijo al padre que le corresponde */
foreach ($pendientes as $hijo) {
    if (isset($padres[$hijo['parent_id']])) {
        $padres[$hijo['parent_id']]['children'][] = $hijo;
    }
}

/* 4) Para la vista usamos $padres (ya tiene sus children) */
?>
<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
  <a href="index.php" class="navbar-brand d-flex align-items-center px-4 px-lg-5">
      <h1 class="m-0">DIRSU</h1>
  </a>

  <button class="navbar-toggler me-4" type="button"
          data-bs-toggle="collapse" data-bs-target="#navbarCollapse">
      <span class="navbar-toggler-icon"></span>
  </button>

  <div class="collapse navbar-collapse" id="navbarCollapse">
    <div class="navbar-nav ms-auto p-4 p-lg-0">
      <?php foreach ($padres as $m): ?>
        <?php if (empty($m['children'])): ?>
          <a class="nav-item nav-link"
             href="<?= htmlspecialchars($m['url']) ?>">
             <?= htmlspecialchars($m['texto']) ?>
          </a>
        <?php else: ?>
          <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle"
               data-bs-toggle="dropdown">
               <?= htmlspecialchars($m['texto']) ?>
            </a>
            <div class="dropdown-menu bg-light m-0">
              <?php foreach ($m['children'] as $c): ?>
                <a class="dropdown-item"
                   href="<?= htmlspecialchars($c['url']) ?>">
                   <?= htmlspecialchars($c['texto']) ?>
                </a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>

    <a href="https://rsu.unitru.edu.pe/sistema_web/"
       class="btn btn-primary py-4 px-lg-4 rounded-0 d-none d-lg-block">
       Ir a Sistema DIRSU<i class="fa fa-arrow-right ms-3"></i>
    </a>
  </div>
</nav>
