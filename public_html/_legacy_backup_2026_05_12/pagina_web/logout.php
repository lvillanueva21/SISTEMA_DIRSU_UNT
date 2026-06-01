<?php
require __DIR__.'/../inc/app_boot.php';
cerrar_sesion();
header('Location: '.url('index.php'));
exit;
