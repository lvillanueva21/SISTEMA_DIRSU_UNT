<?php
/**
 * Renderizador de sidebar por rol (fase 1: Direccion RSU).
 * Uso esperado:
 *   include_once __DIR__ . '/../includes/sidebar.php';
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

include_once __DIR__ . '/menu_matrix.php';

if (!function_exists('rsu_sidebar_escape')) {
    function rsu_sidebar_escape($value)
    {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('rsu_sidebar_display_name')) {
    function rsu_sidebar_display_name($nombres, $apellidos)
    {
        $nombres = trim((string)$nombres);
        $apellidos = trim((string)$apellidos);
        $primer_apellido = '';

        if ($apellidos !== '') {
            $partes = explode(' ', $apellidos);
            $primer_apellido = isset($partes[0]) ? $partes[0] : '';
        }

        if (mb_strlen($nombres, 'UTF-8') > 22) {
            return $nombres;
        }

        $completo = trim($nombres . ' ' . $primer_apellido);
        if (mb_strlen($completo, 'UTF-8') <= 23) {
            return $completo;
        }

        return mb_substr($nombres, 0, 23, 'UTF-8');
    }
}

if (!function_exists('rsu_sidebar_is_active')) {
    function rsu_sidebar_is_active($item, $current_file)
    {
        if (!is_array($item)) {
            return false;
        }

        if (isset($item['active_on']) && is_array($item['active_on'])) {
            return in_array($current_file, $item['active_on']);
        }

        if (!isset($item['href'])) {
            return false;
        }

        return basename((string)$item['href']) === $current_file;
    }
}

if (!function_exists('rsu_sidebar_tree_has_active_child')) {
    function rsu_sidebar_tree_has_active_child($tree_item, $current_file)
    {
        if (!isset($tree_item['children']) || !is_array($tree_item['children'])) {
            return false;
        }

        foreach ($tree_item['children'] as $child) {
            if (rsu_sidebar_is_active($child, $current_file)) {
                return true;
            }
        }

        return false;
    }
}

$sidebar_role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$sidebar_config = rsu_get_menu_by_role($sidebar_role_id);

if (!$sidebar_config) {
    // No rompe flujo si el rol aun no esta migrado a menu_matrix.
    return;
}

$sidebar_current_file = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
$sidebar_brand = isset($sidebar_config['brand']) ? $sidebar_config['brand'] : array();
$sidebar_items = isset($sidebar_config['items']) ? $sidebar_config['items'] : array();
$sidebar_avatar = isset($sidebar_config['avatar']) ? $sidebar_config['avatar'] : '../dust/img/avatar.png';

$sidebar_nombres = isset($nombres) ? $nombres : (isset($_SESSION['nombres']) ? $_SESSION['nombres'] : '');
$sidebar_apellidos = isset($apellidos) ? $apellidos : (isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '');
$sidebar_user_text = rsu_sidebar_display_name($sidebar_nombres, $sidebar_apellidos);
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="<?php echo rsu_sidebar_escape(isset($sidebar_brand['href']) ? $sidebar_brand['href'] : 'inicio.php'); ?>" class="brand-link">
    <img src="<?php echo rsu_sidebar_escape(isset($sidebar_brand['logo']) ? $sidebar_brand['logo'] : '../dust/img/dirsu_logo_128_128.png'); ?>"
         alt="Logo"
         class="brand-image img-circle elevation-3"
         style="opacity:.8">
    <span class="brand-text font-weight-light"><?php echo rsu_sidebar_escape(isset($sidebar_brand['name']) ? $sidebar_brand['name'] : 'Sistema'); ?></span>
  </a>

  <div class="sidebar">
    <div class="user-panel mt-3 pb-3 mb-3 d-flex">
      <div class="image">
        <img src="<?php echo rsu_sidebar_escape($sidebar_avatar); ?>" class="img-circle elevation-2" alt="User Image">
      </div>
      <div class="info">
        <a href="inicio.php" class="d-block"><?php echo rsu_sidebar_escape($sidebar_user_text); ?></a>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php foreach ($sidebar_items as $item): ?>
          <?php
          $type = isset($item['type']) ? $item['type'] : 'item';
          if ($type === 'header'):
          ?>
            <li class="nav-header"><?php echo rsu_sidebar_escape(isset($item['label']) ? $item['label'] : ''); ?></li>
          <?php elseif ($type === 'tree'): ?>
            <?php $tree_open = rsu_sidebar_tree_has_active_child($item, $sidebar_current_file); ?>
            <li class="nav-item menu <?php echo $tree_open ? 'menu-open' : ''; ?>">
              <a href="#" class="nav-link <?php echo $tree_open ? 'active' : ''; ?>">
                <span class="badge nav-icon"><i class="<?php echo rsu_sidebar_escape(isset($item['icon']) ? $item['icon'] : 'fas fa-circle'); ?>"></i></span>
                <p><?php echo rsu_sidebar_escape(isset($item['label']) ? $item['label'] : ''); ?></p>
              </a>
              <ul class="nav nav-treeview">
                <?php if (isset($item['children']) && is_array($item['children'])): ?>
                  <?php foreach ($item['children'] as $child): ?>
                    <?php $child_active = rsu_sidebar_is_active($child, $sidebar_current_file); ?>
                    <li class="nav-item">
                      <a href="<?php echo rsu_sidebar_escape(isset($child['href']) ? $child['href'] : '#'); ?>"
                         class="nav-link <?php echo $child_active ? 'active' : ''; ?>">
                        <i class="<?php echo rsu_sidebar_escape(isset($child['icon']) ? $child['icon'] : 'fas fa-circle nav-icon'); ?> nav-icon"></i>
                        <p><?php echo rsu_sidebar_escape(isset($child['label']) ? $child['label'] : ''); ?></p>
                      </a>
                    </li>
                  <?php endforeach; ?>
                <?php endif; ?>
              </ul>
            </li>
          <?php else: ?>
            <?php $item_active = rsu_sidebar_is_active($item, $sidebar_current_file); ?>
            <li class="nav-item">
              <a href="<?php echo rsu_sidebar_escape(isset($item['href']) ? $item['href'] : '#'); ?>"
                 class="nav-link <?php echo $item_active ? 'active' : ''; ?>">
                <i class="<?php echo rsu_sidebar_escape(isset($item['icon']) ? $item['icon'] : 'fas fa-circle nav-icon'); ?> nav-icon"></i>
                <p><?php echo rsu_sidebar_escape(isset($item['label']) ? $item['label'] : ''); ?></p>
              </a>
            </li>
          <?php endif; ?>
        <?php endforeach; ?>
      </ul>
    </nav>
  </div>
</aside>

