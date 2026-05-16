<?php
declare(strict_types=1);

// ====== RUTAS BASE ======
$modulesDir = __DIR__ . '/modules';

// ====== MAPA DE RUTAS (?p=...) ======
$routes = [
    // Home
    'inicio' => [
        'title' => 'Inicio',
        'active' => 'home',
        'module' => 'home.php',
        'header' => false,
    ],

    // Areas
    'areas_proyectos' => [
        'title' => 'Areas - Proyectos',
        'active' => 'areas',
        'module' => 'areas/proyectos.php',
        'header' => true,
        'headerTitle' => 'Proyectos',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Areas', null], ['Proyectos', null]],
    ],
    'areas_ambiental' => [
        'title' => 'Areas - Ambiental',
        'active' => 'areas',
        'module' => 'areas/ambiental.php',
        'header' => true,
        'headerTitle' => 'Ambiental',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Areas', null], ['Ambiental', null]],
    ],

    // Voluntariado UNT
    'vol_cdn' => [
        'title' => 'Voluntariado UNT - CDN',
        'active' => 'voluntariado',
        'module' => 'voluntariado/cdn.php',
        'header' => true,
        'headerTitle' => 'CDN',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['CDN', null]],
    ],
    'vol_cvgen' => [
        'title' => 'Voluntariado UNT - CVGEN',
        'active' => 'voluntariado',
        'module' => 'voluntariado/cvgen.php',
        'header' => true,
        'headerTitle' => 'CVGEN',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['CVGEN', null]],
    ],
    'vol_grd' => [
        'title' => 'Voluntariado UNT - GRD',
        'active' => 'voluntariado',
        'module' => 'voluntariado/grd.php',
        'header' => true,
        'headerTitle' => 'GRD',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['GRD', null]],
    ],
    'vol_promam' => [
        'title' => 'Voluntariado UNT - PROMAM',
        'active' => 'voluntariado',
        'module' => 'voluntariado/promam.php',
        'header' => true,
        'headerTitle' => 'PROMAM',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['PROMAM', null]],
    ],
    'vol_sbc' => [
        'title' => 'Voluntariado UNT - SBC',
        'active' => 'voluntariado',
        'module' => 'voluntariado/sbc.php',
        'header' => true,
        'headerTitle' => 'SBC',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['SBC', null]],
    ],
    'vol_unippets' => [
        'title' => 'Voluntariado UNT - UNIPPETS',
        'active' => 'voluntariado',
        'module' => 'voluntariado/unippets.php',
        'header' => true,
        'headerTitle' => 'UNIPPETS',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['Voluntariado UNT', null], ['UNIPPETS', null]],
    ],

    // CECUNT
    'cec_teatro' => [
        'title' => 'CECUNT - Teatro Universitario',
        'active' => 'cecunt',
        'module' => 'cecunt/teatro.php',
        'header' => true,
        'headerTitle' => 'Teatro Universitario',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['CECUNT', null], ['Teatro Universitario', null]],
    ],
    'cec_orfeon' => [
        'title' => 'CECUNT - Orfeon Universitario',
        'active' => 'cecunt',
        'module' => 'cecunt/orfeon.php',
        'header' => true,
        'headerTitle' => 'Orfeon Universitario',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['CECUNT', null], ['Orfeon Universitario', null]],
    ],
    'cec_danza' => [
        'title' => 'CECUNT - Grupo de Danza',
        'active' => 'cecunt',
        'module' => 'cecunt/danza.php',
        'header' => true,
        'headerTitle' => 'Grupo de Danza',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['CECUNT', null], ['Grupo de Danza', null]],
    ],
    'cec_banda' => [
        'title' => 'CECUNT - Banda de Musica',
        'active' => 'cecunt',
        'module' => 'cecunt/banda.php',
        'header' => true,
        'headerTitle' => 'Banda de Musica',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['CECUNT', null], ['Banda de Musica', null]],
    ],
    'cec_musica' => [
        'title' => 'CECUNT - Grupo de Musica',
        'active' => 'cecunt',
        'module' => 'cecunt/musica.php',
        'header' => true,
        'headerTitle' => 'Grupo de Musica',
        'crumbs' => [['Inicio','index.php?p=inicio'], ['CECUNT', null], ['Grupo de Musica', null]],
    ],
];

// Default route
$p = $_GET['p'] ?? 'inicio';
$p = is_string($p) ? $p : 'inicio';
$pageKey = $p;

$route = $routes[$p] ?? $routes['inicio'];

// Variables consumidas por includes/header.php y includes/navbar.php
$pageTitle  = $route['title'];
$activePage = $route['active'];

// Page header
$showPageHeader  = (bool)($route['header'] ?? false);
$pageHeaderTitle = $route['headerTitle'] ?? '';
$pageHeaderCrumbs = $route['crumbs'] ?? [];
$pageHeaderConfigurable = ($showPageHeader && isset($routes[$p]) && $p !== 'inicio');

// Resolver modulo
$moduleRel = $route['module'];
$modulePath = $modulesDir . '/' . $moduleRel;

// Seguridad basica
if (str_contains($moduleRel, '..') || !is_file($modulePath)) {
    http_response_code(404);
    $pageTitle = '404';
    $activePage = '';
    $showPageHeader = true;
    $pageHeaderTitle = 'Pagina no encontrada';
    $pageHeaderCrumbs = [['Inicio','index.php?p=inicio'], ['404', null]];
    $pageHeaderConfigurable = false;
    $modulePath = $modulesDir . '/404.php';
}

// Render base
require __DIR__ . '/includes/base.php';