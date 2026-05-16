<?php
// includes/base.php
declare(strict_types=1);

// Variables esperadas desde index.php:
// $pageTitle, $activePage, $modulePath (ruta absoluta al archivo del módulo)
// opcional: $showPageHeader, $pageHeaderTitle, $pageHeaderCrumbs

require __DIR__ . '/header.php';
require __DIR__ . '/navbar.php';

$showPageHeader = $showPageHeader ?? false;
if ($showPageHeader) {
    require __DIR__ . '/page_header.php';
}

require $modulePath;

require __DIR__ . '/footer.php';
