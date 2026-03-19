<?php
// Verificar que el usuario esté autenticado y que la sesión esté activa
if (!isset($_SESSION['usuario'])) {
    echo "<script>alert('Primero debes iniciar sesión.');</script>";
    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
    exit();
}

// Consulta para obtener los datos de todas las filas de la tabla proyectos

//AQUÍ MODIFICAR ENTRADAS PARA TABLA VER PROYECTOS
$sql = "SELECT id, p2, departamento_academico, coordinador, estado FROM proyectos ORDER BY id ASC";
$stmt = $conexion->prepare($sql);
$stmt->execute();
$result = $stmt->get_result();

// Crear un array para almacenar los proyectos
$proyectos = [];

// Iterar sobre todas las filas obtenidas
if ($result->num_rows > 0) {
    while ($fila = $result->fetch_assoc()) {
        // Almacenar los datos p1, departamento_academico, coordinador y estado de cada fila
        //AQUÍ TAMBIÉN MODIFICAR ENTRADAS PARA TABLA VER PROYECTOS
        $proyectos[] = [
            'id' => (int) $fila['id'],
            'p2' => $fila['p2'],
            'departamento_academico' => $fila['departamento_academico'],
            'coordinador' => $fila['coordinador'],
            'estado' => (int) $fila['estado'] // estado es un entero, por lo que lo convertimos a int
        ];
    }
} else {
    echo "No se encontraron proyectos.";
}

// Cerrar la conexión
$stmt->close();
?>