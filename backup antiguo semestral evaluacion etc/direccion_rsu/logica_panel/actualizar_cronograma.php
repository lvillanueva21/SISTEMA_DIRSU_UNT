<?php 
// Incluir la conexión a la base de datos
include('../../componentes/db.php');

// Procesamiento: Actualizar registros existentes o insertar nuevos
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accion']) && $_POST['accion'] == 'actualizar') {
    foreach ($_POST['periodo'] as $key => $periodo) {
        $codigo      = $_POST['codigo'][$key];
        $descripcion = $_POST['descripcion'][$key];
        $inicio      = $_POST['inicio'][$key];
        $fin         = $_POST['fin'][$key];
        $estado      = $_POST['estado'][$key];
        
        // Si la clave es numérica, se trata de un registro existente: actualizar
        if (is_numeric($key)) {
            $sql = "UPDATE cronogramas SET periodo = ?, codigo = ?, descripcion = ?, inicio = ?, fin = ?, estado = ? WHERE id = ?";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssssii", $periodo, $codigo, $descripcion, $inicio, $fin, $estado, $key);
            $stmt->execute();
        } else {
            // Claves no numéricas: se insertan como nuevos registros
            $sql = "INSERT INTO cronogramas (periodo, codigo, descripcion, inicio, fin, estado) VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conexion->prepare($sql);
            $stmt->bind_param("sssssi", $periodo, $codigo, $descripcion, $inicio, $fin, $estado);
            $stmt->execute();
        }
    }
    echo "Cronogramas actualizados correctamente";
    exit();
}

// Consulta para obtener todos los cronogramas ordenados por la fecha de inicio
$sql = "SELECT * FROM cronogramas ORDER BY inicio ASC";
$result = mysqli_query($conexion, $sql);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Actualizar Cronogramas</title>
    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="../../plogins/bootstrap/css/bootstrap.min.css">
    <!-- Font Awesome para iconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body { padding: 20px; }
        .table-container { margin-bottom: 30px; }
        table, th, td { font-size: 0.85rem; padding: 4px 8px; }
        .activo { background-color: #d4edda !important; } /* Verde pastel */
        .inactivo { background-color: #f8d7da !important; } /* Rojo pastel */
        /* Para que las textareas sean de altura reducida */
        textarea.form-control { height: 40px; resize: none; }
    </style>
</head>
<body>
    <div class="container">
        <!-- Encabezado: Botones de actualizar e insertar -->
        <div class="d-flex justify-content-end align-items-center mb-4">
            <button id="btnInsertar" class="btn btn-success mr-2" title="Insertar registro">
                <i class="fa fa-plus"></i>
            </button>
            <button id="btnActualizar" class="btn btn-primary" title="Actualizar cronogramas">
                <i class="fa fa-save"></i>
            </button>
        </div>
        
        <!-- Formulario que envuelve la tabla con los datos editables -->
        <form id="formCronogramas">
            <input type="hidden" name="accion" value="actualizar">
            <div class="table-responsive table-container">
                <table class="table table-bordered table-sm">
                    <thead class="bg-success text-white">
                        <tr>
                            <th>Período</th>
                            <th>Código</th>
                            <th>Descripción</th>
                            <th>Inicio</th>
                            <th>Fin</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody id="tablaCronogramas">
                        <?php while ($row = mysqli_fetch_assoc($result)) { 
                            $inicioVal = date("Y-m-d\\TH:i", strtotime($row['inicio']));
                            $finVal = date("Y-m-d\\TH:i", strtotime($row['fin']));
                            $filaClase = ($row['estado'] == 1) ? 'activo' : 'inactivo';
                        ?>
                        <tr class="<?php echo $filaClase; ?>" data-id="<?php echo $row['id']; ?>">
                            <td>
                                <textarea class="form-control" name="periodo[<?php echo $row['id']; ?>]"><?php echo htmlspecialchars($row['periodo']); ?></textarea>
                            </td>
                            <td>
                                <select class="form-control" name="codigo[<?php echo $row['id']; ?>]">
                                    <?php 
                                    $opciones = ["F1-GENERALIDADES", "F1-PLAN", "F1-ANEXOS", "F3-SEMESTRAL", "EV-PCF", "EV-DD", "EV-DF", "EV-RSU"];
                                    foreach ($opciones as $opcion) {
                                        $selected = ($row['codigo'] == $opcion) ? 'selected' : '';
                                        echo "<option value='$opcion' $selected>$opcion</option>";
                                    }
                                    ?>
                                </select>
                            </td>
                            <td>
                            <textarea class="form-control" name="descripcion[<?php echo $row['id']; ?>]" style="height:80px;"><?php echo htmlspecialchars($row['descripcion']); ?></textarea>
                            </td>
                            <td>
                                <input type="datetime-local" class="form-control" name="inicio[<?php echo $row['id']; ?>]" value="<?php echo $inicioVal; ?>">
                            </td>
                            <td>
                                <input type="datetime-local" class="form-control" name="fin[<?php echo $row['id']; ?>]" value="<?php echo $finVal; ?>">
                            </td>
                            <td>
                                <select name="estado[<?php echo $row['id']; ?>]" class="form-control">
                                    <option value="1" <?php echo ($row['estado'] == 1) ? 'selected' : ''; ?>>Activo</option>
                                    <option value="0" <?php echo ($row['estado'] == 0) ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </td>
                        </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
        </form>
        <script>
        let nuevoContador = 1;
        $(document).ready(function() {
            $("#btnActualizar").click(function(e) {
                e.preventDefault();
                $.post('logica_panel/actualizar_cronograma.php', $("#formCronogramas").serialize(), function(response) {
                    alert(response);
                    location.reload();
                });
            });
            
            $("#btnInsertar").click(function(e) {
                e.preventDefault();
                let newIndex = "new_" + nuevoContador++;
                let nuevaFila = `<tr class="activo" data-id="${newIndex}">
                    <td>
                        <textarea class="form-control" name="periodo[${newIndex}]" placeholder="Ej. 2025-I"></textarea>
                    </td>
                    <td>
                        <select class="form-control" name="codigo[${newIndex}]">
                            <option value="F1-GENERALIDADES">F1-GENERALIDADES</option>
                            <option value="F1-PLAN">F1-PLAN</option>
                            <option value="F1-ANEXOS">F1-ANEXOS</option>
                            <option value="F3-SEMESTRAL">F3-SEMESTRAL</option>
                            <option value="EV-PCF">EV-PCF</option>
                            <option value="EV-DD">EV-DD</option>
                            <option value="EV-DF">EV-DF</option>
                            <option value="EV-RSU">EV-RSU</option>
                        </select>
                    </td>
                    <td>
                        <textarea class="form-control" name="descripcion[${newIndex}]" placeholder=\"Descripción\"></textarea>
                    </td>
                    <td>
                        <input type="datetime-local" class="form-control" name="inicio[${newIndex}]" value="">
                    </td>
                    <td>
                        <input type="datetime-local" class="form-control" name="fin[${newIndex}]" value="">
                    </td>
                    <td>
                        <select name="estado[${newIndex}]" class="form-control">
                            <option value="1" selected>Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </td>
                </tr>`;
                $("#tablaCronogramas").append(nuevaFila);
            });
        });
    </script>
    </div>
</body>
</html>
