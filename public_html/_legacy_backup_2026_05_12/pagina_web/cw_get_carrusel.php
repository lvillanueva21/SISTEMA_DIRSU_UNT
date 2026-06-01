<?php
require_once __DIR__.'/../cw_config.php';
$q = $mysqli->query("SELECT * FROM cw_carrusel WHERE visible=1 ORDER BY orden");
echo json_encode($q->fetch_all(MYSQLI_ASSOC));
?>
