<?php if (!isset($mysqli)) require_once __DIR__.'/app_boot.php';

$sql = "SELECT id, texto, url, parent_id, visible, orden
        FROM cw_opciones_menu
        WHERE visible = 1
        ORDER BY parent_id, orden";
$res = $mysqli->query($sql);

$padres=[]; $pend=[];
while($row=$res->fetch_assoc()){
  $row['url'] = (strpos($row['url'],'/')===0) ? $row['url'] : url($row['url']);
  if ($row['parent_id']===null) { $row['children']=[]; $padres[$row['id']]=$row; }
  else { $pend[]=$row; }
}
foreach($pend as $h){
  if(isset($padres[$h['parent_id']])){
    $padres[$h['parent_id']]['children'][]=$h;
  }
}
function parent_active($p){ if(is_active($p['url']))return true; foreach($p['children'] as $c){ if(is_active($c['url'])) return true; } return false; }
?>
<nav class="navbar navbar-expand-lg bg-white navbar-light sticky-top p-0">
  <a href="<?= url('index.php') ?>" class="navbar-brand d-flex align-items-center px-4 px-lg-5"><h1 class="m-0">DIRSU</h1></a>
  <button class="navbar-toggler me-4" type="button" data-bs-toggle="collapse" data-bs-target="#navbarCollapse"><span class="navbar-toggler-icon"></span></button>
  <div class="collapse navbar-collapse" id="navbarCollapse">
    <div class="navbar-nav ms-auto p-4 p-lg-0">
      <?php foreach ($padres as $m): ?>
        <?php if (empty($m['children'])): ?>
          <a class="nav-item nav-link<?= is_active($m['url'])?' active':'' ?>" href="<?= htmlspecialchars($m['url']) ?>"><?= htmlspecialchars($m['texto']) ?></a>
        <?php else: ?>
          <div class="nav-item dropdown">
            <a href="#" class="nav-link dropdown-toggle<?= parent_active($m)?' active':'' ?>" data-bs-toggle="dropdown"><?= htmlspecialchars($m['texto']) ?></a>
            <div class="dropdown-menu bg-light m-0">
              <?php foreach ($m['children'] as $c): ?>
                <a class="dropdown-item<?= is_active($c['url'])?' active':'' ?>" href="<?= htmlspecialchars($c['url']) ?>"><?= htmlspecialchars($c['texto']) ?></a>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      <?php endforeach; ?>
    </div>
    <a href="https://rsu.unitru.edu.pe/sistema_web/" class="btn btn-primary py-4 px-lg-4 rounded-0 d-none d-lg-block">
      Ir a Sistema DIRSU<i class="fa fa-arrow-right ms-3"></i>
    </a>
  </div>
</nav>
