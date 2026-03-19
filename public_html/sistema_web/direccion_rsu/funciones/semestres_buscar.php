<?php
/* -----------------------------------------------------------
 |  AJAX: Buscar proyectos por id_py, usuario, nombres, apellidos
 |  Devuelve JSON con los campos mínimos para la calculadora
 * ----------------------------------------------------------*/
header('Content-Type: application/json; charset=utf-8');
require_once '../../componentes/db.php';

$q = trim($_POST['q'] ?? '');
if ($q === '') { echo json_encode([]); exit; }

/* Buscar en: id_py, usuario, nombres, apellidos (todo como texto) */
$where = "(CAST(p.id AS CHAR) LIKE CONCAT('%',?,'%')
        OR u.usuario   LIKE CONCAT('%',?,'%')
        OR u.nombres   LIKE CONCAT('%',?,'%')
        OR u.apellidos LIKE CONCAT('%',?,'%'))";

/* --- SELECT recortado --- */
$sql = "
SELECT
  p.id                     AS id_py,
  u.usuario                AS codigo,
  CONCAT(u.nombres,' ',u.apellidos) AS coordinador,
  f.nombre                 AS facultad,
  d.nombre                 AS departamento,
  p.p2                     AS titulo,
  per.nombre               AS periodo,
  p.estado                 AS estado
FROM proyectos            p
JOIN usuarios_proyectos   up  ON up.id_proyecto = p.id
JOIN usuarios             u   ON u.id          = up.id_usuario
JOIN departamentos        d   ON d.id          = u.id_depa
JOIN facultades           f   ON f.id          = d.id_facultad
JOIN proyectos_periodo    pp  ON pp.id_py      = p.id
JOIN periodos             per ON per.id        = pp.id_periodo
WHERE $where
ORDER BY p.id DESC
LIMIT 50
";

$stmt = mysqli_prepare($conexion,$sql);
mysqli_stmt_bind_param($stmt,'ssss', $q, $q, $q, $q);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

echo json_encode(mysqli_fetch_all($res,MYSQLI_ASSOC));
