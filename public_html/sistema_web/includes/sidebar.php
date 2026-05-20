<?php
/**
 * Renderizador de sidebar por rol (fase 1: Direccion RSU).
 * Uso esperado:
 *   include_once __DIR__ . '/../includes/sidebar.php';
 */

if (session_status() !== PHP_SESSION_ACTIVE) {
    @session_start();
}

include_once __DIR__ . '/config.php';
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

        if (isset($item['active_on_dynamic']) && is_array($item['active_on_dynamic'])) {
            $current_app_path = rsu_sidebar_current_app_path();
            return in_array($current_app_path, $item['active_on_dynamic']);
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

if (!function_exists('rsu_sidebar_is_development_mode')) {
    function rsu_sidebar_is_development_mode()
    {
        global $RSU_CONFIG;

        $mode = isset($RSU_CONFIG['session_mode']) ? (string)$RSU_CONFIG['session_mode'] : 'production';
        return strtolower(trim($mode)) === 'development';
    }
}

if (!function_exists('rsu_sidebar_item_is_visible')) {
    function rsu_sidebar_item_is_visible($item)
    {
        if (!is_array($item)) {
            return false;
        }

        if (isset($item['dev_only']) && $item['dev_only']) {
            return rsu_sidebar_is_development_mode();
        }

        return true;
    }
}

if (!function_exists('rsu_sidebar_current_app_path')) {
    function rsu_sidebar_current_app_path()
    {
        static $cached_path = null;
        if ($cached_path !== null) {
            return $cached_path;
        }

        $script_file = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $app_root = realpath(__DIR__ . '/..');
        $script_file_real = $script_file !== '' ? realpath($script_file) : false;

        if (!$app_root || !$script_file_real) {
            $cached_path = '';
            return $cached_path;
        }

        $app_root_norm = str_replace('\\', '/', rtrim($app_root, '\\/'));
        $script_norm = str_replace('\\', '/', $script_file_real);

        if (stripos($script_norm, $app_root_norm) !== 0) {
            $cached_path = '';
            return $cached_path;
        }

        $relative = ltrim(substr($script_norm, strlen($app_root_norm)), '/');
        $cached_path = str_replace('\\', '/', $relative);
        return $cached_path;
    }
}

if (!function_exists('rsu_sidebar_is_external_href')) {
    function rsu_sidebar_is_external_href($href)
    {
        $href = trim((string)$href);
        if ($href === '') {
            return false;
        }

        if (strpos($href, '#') === 0 || strpos($href, '/') === 0) {
            return true;
        }

        return (bool)preg_match('/^[a-z][a-z0-9+.-]*:/i', $href) || strpos($href, '//') === 0;
    }
}

if (!function_exists('rsu_sidebar_build_dynamic_href')) {
    function rsu_sidebar_build_dynamic_href($target_app_path)
    {
        $target_app_path = ltrim(str_replace('\\', '/', (string)$target_app_path), '/');
        if ($target_app_path === '') {
            return '#';
        }

        $script_file = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';
        $app_root = realpath(__DIR__ . '/..');
        $script_dir = $script_file !== '' ? realpath(dirname($script_file)) : false;

        if (!$app_root || !$script_dir) {
            return $target_app_path;
        }

        $app_root_norm = str_replace('\\', '/', rtrim($app_root, '\\/'));
        $script_dir_norm = str_replace('\\', '/', rtrim($script_dir, '\\/'));

        if (stripos($script_dir_norm, $app_root_norm) !== 0) {
            return $target_app_path;
        }

        $relative_dir = ltrim(substr($script_dir_norm, strlen($app_root_norm)), '/');
        $levels = $relative_dir === '' ? 0 : count(explode('/', $relative_dir));
        $prefix = str_repeat('../', $levels);

        return $prefix . $target_app_path;
    }
}

if (!function_exists('rsu_sidebar_normalize_dot_segments')) {
    function rsu_sidebar_normalize_dot_segments($path)
    {
        $path = str_replace('\\', '/', (string)$path);
        $segments = explode('/', $path);
        $result = array();

        foreach ($segments as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }

            if ($segment === '..') {
                if (!empty($result)) {
                    array_pop($result);
                }
                continue;
            }

            $result[] = $segment;
        }

        return implode('/', $result);
    }
}

if (!function_exists('rsu_sidebar_to_app_path')) {
    function rsu_sidebar_to_app_path($raw_path, $context_dir)
    {
        $raw_path = trim((string)$raw_path);
        $context_dir = trim(str_replace('\\', '/', (string)$context_dir), '/');

        if ($raw_path === '' || rsu_sidebar_is_external_href($raw_path)) {
            return '';
        }

        $raw_path = str_replace('\\', '/', $raw_path);

        // Si ya parece una ruta interna desde sistema_web, se respeta.
        if (
            strpos($raw_path, '../') !== 0
            && strpos($raw_path, './') !== 0
            && strpos($raw_path, '/') !== false
        ) {
            return rsu_sidebar_normalize_dot_segments($raw_path);
        }

        $base = $context_dir;
        $combined = ($base !== '') ? ($base . '/' . $raw_path) : $raw_path;
        return rsu_sidebar_normalize_dot_segments($combined);
    }
}

if (!function_exists('rsu_sidebar_resolve_map_value')) {
    function rsu_sidebar_resolve_map_value($data, $direct_key, $by_app_key, $by_page_key, $current_app_path, $current_file, $fallback = '', $ignore_by_page = false)
    {
        if (!is_array($data)) {
            return $fallback;
        }

        if (!$ignore_by_page && isset($data[$by_app_key]) && is_array($data[$by_app_key])) {
            if (isset($data[$by_app_key][$current_app_path])) {
                return (string)$data[$by_app_key][$current_app_path];
            }
        }

        if (!$ignore_by_page && isset($data[$by_page_key]) && is_array($data[$by_page_key])) {
            if (isset($data[$by_page_key][$current_file])) {
                return (string)$data[$by_page_key][$current_file];
            }
        }

        if (isset($data[$direct_key]) && $data[$direct_key] !== '') {
            return (string)$data[$direct_key];
        }

        return $fallback;
    }
}

if (!function_exists('rsu_sidebar_prefix_api_context_path')) {
    function rsu_sidebar_prefix_api_context_path($path, $context_dir)
    {
        $path = trim((string)$path);
        $context_dir = trim((string)$context_dir, '/');

        if ($path === '' || $context_dir === '' || rsu_sidebar_is_external_href($path)) {
            return $path;
        }

        return '../../' . $context_dir . '/' . ltrim($path, '/');
    }
}

if (!function_exists('rsu_sidebar_get_visible_children')) {
    function rsu_sidebar_get_visible_children($tree_item)
    {
        $visible_children = array();

        if (!isset($tree_item['children']) || !is_array($tree_item['children'])) {
            return $visible_children;
        }

        foreach ($tree_item['children'] as $child) {
            if (rsu_sidebar_item_is_visible($child)) {
                $visible_children[] = $child;
            }
        }

        return $visible_children;
    }
}

if (!function_exists('rsu_sidebar_tree_has_active_child')) {
    function rsu_sidebar_tree_has_active_child($tree_item, $current_file)
    {
        $visible_children = rsu_sidebar_get_visible_children($tree_item);
        foreach ($visible_children as $child) {
            if (rsu_sidebar_is_active($child, $current_file)) {
                return true;
            }
        }

        return false;
    }
}

if (!function_exists('rsu_sidebar_resolve_href')) {
    function rsu_sidebar_resolve_href($node, $current_file, $current_app_path, $context_dir, $fallback = '#', $ignore_by_page = false, $api_context_dir = '')
    {
        if (!is_array($node)) {
            return $fallback;
        }

        if (isset($node['href_dynamic']) && $node['href_dynamic'] !== '') {
            return rsu_sidebar_build_dynamic_href($node['href_dynamic']);
        }

        $value = rsu_sidebar_resolve_map_value(
            $node,
            'href',
            'href_by_app_path',
            'href_by_page',
            $current_app_path,
            $current_file,
            '',
            $ignore_by_page
        );

        if ($value !== '') {
            $value = rsu_sidebar_prefix_api_context_path($value, $api_context_dir);
            if ($api_context_dir !== '') {
                return $value;
            }

            $app_path = rsu_sidebar_to_app_path($value, $context_dir);
            if ($app_path !== '') {
                return rsu_sidebar_build_dynamic_href($app_path);
            }

            return $value;
        }

        return $fallback;
    }
}

if (!function_exists('rsu_sidebar_resolve_value_by_page')) {
    function rsu_sidebar_resolve_value_by_page($data, $value_key, $map_key, $current_file, $fallback = '', $ignore_by_page = false, $current_app_path = '')
    {
        if (!is_array($data)) {
            return $fallback;
        }

        $map_by_app_key = '';
        if ($map_key !== '') {
            $map_by_app_key = str_replace('_by_page', '_by_app_path', (string)$map_key);
        }

        if (!$ignore_by_page && $map_by_app_key !== '' && isset($data[$map_by_app_key]) && is_array($data[$map_by_app_key])) {
            if (isset($data[$map_by_app_key][$current_app_path])) {
                return (string)$data[$map_by_app_key][$current_app_path];
            }
        }

        if (!$ignore_by_page && isset($data[$map_key]) && is_array($data[$map_key])) {
            if (isset($data[$map_key][$current_file])) {
                return (string)$data[$map_key][$current_file];
            }
        }

        if (isset($data[$value_key]) && $data[$value_key] !== '') {
            return (string)$data[$value_key];
        }

        return $fallback;
    }
}

$sidebar_role_id = isset($_SESSION['id_rol']) ? (int)$_SESSION['id_rol'] : 0;
$sidebar_config = rsu_get_menu_by_role($sidebar_role_id);

if (!$sidebar_config) {
    // No rompe flujo si el rol aun no esta migrado a menu_matrix.
    return;
}

$sidebar_current_file = basename(isset($_SERVER['PHP_SELF']) ? $_SERVER['PHP_SELF'] : '');
$sidebar_current_app_path = rsu_sidebar_current_app_path();
$sidebar_is_api_dirsu_context = ($sidebar_current_app_path === 'includes/api_dirsu/index.php');
$sidebar_brand = isset($sidebar_config['brand']) ? $sidebar_config['brand'] : array();
$sidebar_items = isset($sidebar_config['items']) ? $sidebar_config['items'] : array();
$sidebar_context_dir = isset($sidebar_config['menu_context_dir']) ? (string)$sidebar_config['menu_context_dir'] : '';
$sidebar_api_prefix_context = $sidebar_is_api_dirsu_context ? $sidebar_context_dir : '';
$sidebar_brand_href = rsu_sidebar_resolve_value_by_page($sidebar_brand, 'href', 'href_by_page', $sidebar_current_file, 'inicio.php', $sidebar_is_api_dirsu_context, $sidebar_current_app_path);
$sidebar_brand_logo = rsu_sidebar_resolve_value_by_page($sidebar_brand, 'logo', 'logo_by_page', $sidebar_current_file, '../dust/img/dirsu_logo_128_128.png', $sidebar_is_api_dirsu_context, $sidebar_current_app_path);
$sidebar_avatar = rsu_sidebar_resolve_value_by_page($sidebar_config, 'avatar', 'avatar_by_page', $sidebar_current_file, '../dust/img/avatar.png', $sidebar_is_api_dirsu_context, $sidebar_current_app_path);
$sidebar_user_href = isset($sidebar_config['user_home']) ? (string)$sidebar_config['user_home'] : 'inicio.php';
$sidebar_user_link_class = rsu_sidebar_resolve_value_by_page($sidebar_config, 'user_link_class', 'user_link_class_by_page', $sidebar_current_file, 'd-block', $sidebar_is_api_dirsu_context, $sidebar_current_app_path);
$sidebar_user_link_style = rsu_sidebar_resolve_value_by_page($sidebar_config, 'user_link_style', 'user_link_style_by_page', $sidebar_current_file, '', $sidebar_is_api_dirsu_context, $sidebar_current_app_path);

$sidebar_user_href = rsu_sidebar_resolve_map_value(
    $sidebar_config,
    'user_home',
    'user_home_by_app_path',
    'user_home_by_page',
    $sidebar_current_app_path,
    $sidebar_current_file,
    $sidebar_user_href,
    $sidebar_is_api_dirsu_context
);

$sidebar_brand_href = rsu_sidebar_prefix_api_context_path($sidebar_brand_href, $sidebar_api_prefix_context);
$sidebar_brand_logo = rsu_sidebar_prefix_api_context_path($sidebar_brand_logo, $sidebar_api_prefix_context);
$sidebar_avatar = rsu_sidebar_prefix_api_context_path($sidebar_avatar, $sidebar_api_prefix_context);
$sidebar_user_href = rsu_sidebar_prefix_api_context_path($sidebar_user_href, $sidebar_api_prefix_context);

if (!$sidebar_is_api_dirsu_context) {
    $brand_app_path = rsu_sidebar_to_app_path($sidebar_brand_href, $sidebar_context_dir);
    if ($brand_app_path !== '') {
        $sidebar_brand_href = rsu_sidebar_build_dynamic_href($brand_app_path);
    }

    $user_app_path = rsu_sidebar_to_app_path($sidebar_user_href, $sidebar_context_dir);
    if ($user_app_path !== '') {
        $sidebar_user_href = rsu_sidebar_build_dynamic_href($user_app_path);
    }
}

$sidebar_nombres = isset($nombres) ? $nombres : (isset($_SESSION['nombres']) ? $_SESSION['nombres'] : '');
$sidebar_apellidos = isset($apellidos) ? $apellidos : (isset($_SESSION['apellidos']) ? $_SESSION['apellidos'] : '');
$sidebar_user_text = rsu_sidebar_display_name($sidebar_nombres, $sidebar_apellidos);
?>
<aside class="main-sidebar sidebar-dark-primary elevation-4">
  <a href="<?php echo rsu_sidebar_escape($sidebar_brand_href); ?>" class="brand-link">
    <img src="<?php echo rsu_sidebar_escape($sidebar_brand_logo); ?>"
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
        <a href="<?php echo rsu_sidebar_escape($sidebar_user_href); ?>"
           class="<?php echo rsu_sidebar_escape($sidebar_user_link_class); ?>"
           <?php if ($sidebar_user_link_style !== ''): ?>style="<?php echo rsu_sidebar_escape($sidebar_user_link_style); ?>"<?php endif; ?>><?php echo rsu_sidebar_escape($sidebar_user_text); ?></a>
      </div>
    </div>

    <nav class="mt-2">
      <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
        <?php foreach ($sidebar_items as $item): ?>
          <?php
          if (!rsu_sidebar_item_is_visible($item)) {
              continue;
          }

          $type = isset($item['type']) ? $item['type'] : 'item';
          if ($type === 'header'):
          ?>
            <li class="nav-header"><?php echo rsu_sidebar_escape(isset($item['label']) ? $item['label'] : ''); ?></li>
          <?php elseif ($type === 'tree'): ?>
            <?php $visible_children = rsu_sidebar_get_visible_children($item); ?>
            <?php if (count($visible_children) === 0) { continue; } ?>
            <?php $tree_open = rsu_sidebar_tree_has_active_child($item, $sidebar_current_file); ?>
            <li class="nav-item menu <?php echo $tree_open ? 'menu-open' : ''; ?>">
              <a href="#" class="nav-link <?php echo $tree_open ? 'active' : ''; ?>">
                <?php if (isset($item['icon_badge']) && $item['icon_badge'] !== ''): ?>
                  <span class="badge nav-icon"><?php echo rsu_sidebar_escape($item['icon_badge']); ?></span>
                <?php else: ?>
                  <span class="badge nav-icon"><i class="<?php echo rsu_sidebar_escape(isset($item['icon']) ? $item['icon'] : 'fas fa-circle'); ?>"></i></span>
                <?php endif; ?>
                <p><?php echo rsu_sidebar_escape(isset($item['label']) ? $item['label'] : ''); ?></p>
              </a>
              <ul class="nav nav-treeview">
                <?php foreach ($visible_children as $child): ?>
                    <?php $child_active = rsu_sidebar_is_active($child, $sidebar_current_file); ?>
                    <?php $child_icon = isset($child['icon']) ? trim((string)$child['icon']) : ''; ?>
                    <li class="nav-item">
                      <a href="<?php echo rsu_sidebar_escape(rsu_sidebar_resolve_href($child, $sidebar_current_file, $sidebar_current_app_path, $sidebar_context_dir, '#', $sidebar_is_api_dirsu_context, $sidebar_api_prefix_context)); ?>"
                         class="nav-link <?php echo $child_active ? 'active' : ''; ?>">
                        <?php if ($child_icon !== ''): ?>
                          <i class="<?php echo rsu_sidebar_escape($child_icon); ?> nav-icon"></i>
                        <?php endif; ?>
                        <p><?php echo rsu_sidebar_escape(isset($child['label']) ? $child['label'] : ''); ?></p>
                      </a>
                    </li>
                <?php endforeach; ?>
              </ul>
            </li>
          <?php else: ?>
            <?php $item_active = rsu_sidebar_is_active($item, $sidebar_current_file); ?>
            <li class="nav-item">
              <a href="<?php echo rsu_sidebar_escape(rsu_sidebar_resolve_href($item, $sidebar_current_file, $sidebar_current_app_path, $sidebar_context_dir, '#', $sidebar_is_api_dirsu_context, $sidebar_api_prefix_context)); ?>"
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

