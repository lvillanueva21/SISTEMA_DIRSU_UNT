<?php
// presentacion/crear_respuesta.php  — controlador público del POST
declare(strict_types=1);
date_default_timezone_set('America/Lima');
if (session_status() !== PHP_SESSION_ACTIVE) session_start();

// NO hagas HTML aquí. Solo delega al script real en /logica.
require_once __DIR__ . '/../componentes/db.php';
require_once __DIR__ . '/logica/funciones.php';
// Redirección portable al index del módulo semestral.
define('SM_SEMESTRAL_INDEX_REL', 'index.php');
require_once __DIR__ . '/logica/crear_respuesta.php';
