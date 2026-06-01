<?php
/* cw_config.php  ▸  puente hacia tu conexión existente ------------------ */
require_once __DIR__ . '/sistema_web/componentes/db.php';   // ← ruta que ya tienes

/* la variable $conexion que devuelve db.php ES un objeto mysqli,  
   sólo le ponemos un alias para reutilizar el código que te pasé */
$mysqli = $conexion;          // alias
$mysqli->set_charset('utf8mb4');
?>
