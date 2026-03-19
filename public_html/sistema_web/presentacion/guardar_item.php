<?php
// presentacion/guardar_item.php — controlador público del POST de ítems
declare(strict_types=1);
date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

require_once __DIR__ . '/../componentes/db.php';
require_once __DIR__ . '/logica/funciones.php';
require_once __DIR__ . '/logica/guardar_item.php';
