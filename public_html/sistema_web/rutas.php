<?php
function escanearDirectorio($ruta, $nivel = 0) {
    $estructura = '';
    $elementos = scandir($ruta);
    $elementos = array_diff($elementos, ['.', '..']);
    sort($elementos);

    foreach ($elementos as $elemento) {
        $rutaCompleta = $ruta . DIRECTORY_SEPARATOR . $elemento;
        $indentacion = str_repeat('    ', $nivel);

        if (is_dir($rutaCompleta)) {
            $estructura .= "{$indentacion}📁 $elemento\n";

            // 🚫 Excluir contenido de componentes/archivo
            $rutaRelativa = str_replace('\\', '/', $rutaCompleta);
            if (preg_match('#(^|/)componentes/archivo$#', $rutaRelativa)) {
                continue;
            }

            $estructura .= escanearDirectorio($rutaCompleta, $nivel + 1);
        } else {
            $estructura .= "{$indentacion}📄 $elemento\n";
        }
    }

    return $estructura;
}

// Datos de conexión
$direccionservidor = "localhost";
$baseDatos = "rsudb";
$usuarioBD = "au_rsu";
$contraseniaBD = "_BrHJMGO3U3(9v.c";

$estructura = '';
$tablas = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['generar'])) {
        $estructura = escanearDirectorio('./');
    }

    if (isset($_POST['mostrar_tablas'])) {
        $conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
        if ($conexion) {
            $resultado = mysqli_query($conexion, "SHOW TABLES");
            if ($resultado) {
                while ($fila = mysqli_fetch_row($resultado)) {
                    $nombreTabla = $fila[0];
                    $resCreate = mysqli_query($conexion, "SHOW CREATE TABLE `$nombreTabla`");
                    if ($resCreate) {
                        $filaCreate = mysqli_fetch_assoc($resCreate);
                        $estructura .= "-- Tabla: $nombreTabla\n" . $filaCreate['Create Table'] . ";\n\n";
                    }
                }
            }
            mysqli_close($conexion);
        } else {
            $estructura .= "❌ Error al conectar a la base de datos: " . mysqli_connect_error();
        }
    }    
}
?>


<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Estructura del Sistema</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
        }

        textarea {
            font-family: monospace;
            font-size: 14px;
            height: 80vh;
            background-color: #1e1e1e;
            color: #dcdcdc;
            border: 1px solid #444;
            padding: 10px;
            width: 100%;
            resize: none;
        }

        .btn-copiar {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
            border-radius: 50%;
            padding: 15px 18px;
            font-size: 20px;
            background-color: #0d6efd;
            color: white;
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
            transition: background 0.3s ease;
        }

        .btn-copiar:hover {
            background-color: #084298;
        }

        .copiado-alerta {
            position: fixed;
            bottom: 100px;
            right: 30px;
            z-index: 1001;
            background: #28a745;
            color: white;
            padding: 10px 20px;
            border-radius: 5px;
            display: none;
        }
    </style>
</head>
<body>
<div class="container py-5">
    <h2 class="mb-4">🧭 Generar estructura de carpetas y archivos</h2>

    <form method="post" class="d-flex gap-3 mb-4 flex-wrap">
        <button type="submit" name="generar" class="btn btn-primary">
            📋 Generar Estructura
        </button>

        <button type="submit" name="mostrar_tablas" class="btn btn-success">
            🧩 Mostrar Tablas BD
        </button>
    </form>

    <?php if (isset($estructura)): ?>
        <label class="form-label">Resultado:</label>
        <textarea id="estructuraTexto" readonly><?= htmlspecialchars($estructura) ?></textarea>
    <?php endif; ?>

    <?php if (!empty($tablas)): ?>
        <h4 class="mt-4">🗄️ Tablas en la base de datos <code><?= $baseDatos ?></code></h4>
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-sm">
                <thead class="table-light">
                    <tr>
                        <th>Nombre de la Tabla</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tablas as $tabla): ?>
                        <tr><td><?= htmlspecialchars($tabla) ?></td></tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
<!-- Botón flotante para copiar -->
<button class="btn btn-copiar" onclick="copiarTexto()" title="Copiar estructura">
    📄
</button>

<!-- Alerta -->
<div class="copiado-alerta" id="alertaCopiado">¡Copiado al portapapeles!</div>

<script>
    function copiarTexto() {
        var textarea = document.getElementById("estructuraTexto");
        if (!textarea) return;

        textarea.select();
        textarea.setSelectionRange(0, 99999); // para móviles
        document.execCommand("copy");

        let alerta = document.getElementById("alertaCopiado");
        alerta.style.display = "block";
        setTimeout(() => alerta.style.display = "none", 2000);
    }
</script>
</body>
</html>
