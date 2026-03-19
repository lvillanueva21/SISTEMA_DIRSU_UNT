<?php
include("../componentes/configSesion.php");
include("../componentes/db.php");

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $action = $_POST["action"] ?? "";
    $id = intval($_POST["id"] ?? 0);

    if ($action === "create" || $action === "update") {
        $id_rol = intval($_POST["id_rol"]);
        $titulo = mysqli_real_escape_string($conexion, $_POST["titulo"]);
        $mensaje = mysqli_real_escape_string($conexion, $_POST["mensaje"]);
        $fecha_inicio = $_POST["fecha_inicio"];
        $fecha_fin = $_POST["fecha_fin"];

        if ($action === "create") {
            $sql = "INSERT INTO deadlines (id_rol, titulo, mensaje, fecha_inicio, fecha_fin, activo)
                    VALUES ($id_rol, '$titulo', '$mensaje', '$fecha_inicio', '$fecha_fin', 1)";
        } else {
            $sql = "UPDATE deadlines
                    SET id_rol=$id_rol, titulo='$titulo', mensaje='$mensaje',
                        fecha_inicio='$fecha_inicio', fecha_fin='$fecha_fin'
                    WHERE id=$id";
        }

        mysqli_query($conexion, $sql);

    } elseif ($action === "toggle" && $id > 0) {
        $estado = mysqli_query($conexion, "SELECT activo FROM deadlines WHERE id=$id");
        if ($row = mysqli_fetch_assoc($estado)) {
            $nuevo_estado = $row['activo'] ? 0 : 1;
            mysqli_query($conexion, "UPDATE deadlines SET activo=$nuevo_estado WHERE id=$id");
        }

    } elseif ($action === "delete" && $id > 0) {
        mysqli_query($conexion, "DELETE FROM deadlines WHERE id=$id");
    }
}

// 🔁 Siempre redirigir de vuelta a control_eventos.php
header("Location: ../control_eventos.php");
exit;
