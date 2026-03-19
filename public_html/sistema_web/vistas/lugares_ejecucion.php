<?php
// Incluir configSesion.php para verificar la sesión
include "../componentes/configSesion.php";

// Incluir la conexión a la base de datos
include('../componentes/db.php');

// Incluir el archivo que carga los datos del proyecto
include('../componentes/proyecto/cargar_proyecto.php');

// Incluir el archivo que carga los demás lugares de ejecución si existen
include '../componentes/proyecto/mostrar_lugares.php';

// Variables de sesión
$id_proyecto = $_SESSION['id_py'];

// Consultas para cargar los departamentos
$query_departamentos = "SELECT id, name FROM ubigeo_peru_departments";
$result_departamentos = mysqli_query($conexion, $query_departamentos);

// Consultas para cargar las provincias y distritos si se han seleccionado
$provincias = [];
$distritos = [];

if (isset($_POST['departamento_id'])) {
    $departamento_id = $_POST['departamento_id'];
    $query_provincias = "SELECT id, name FROM ubigeo_peru_provinces WHERE department_id = $departamento_id";
    $result_provincias = mysqli_query($conexion, $query_provincias);
    if (mysqli_num_rows($result_provincias) > 0) {
        while ($row = mysqli_fetch_assoc($result_provincias)) {
            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
    } else {
        echo '<option value="">No hay provincias disponibles</option>';
    }
    exit;  // Finaliza el script aquí para evitar otros outputs
}

if (isset($_POST['provincia_id'])) {
    $provincia_id = $_POST['provincia_id'];
    $query_distritos = "SELECT id, name FROM ubigeo_peru_districts WHERE province_id = $provincia_id";
    $result_distritos = mysqli_query($conexion, $query_distritos);
    if (mysqli_num_rows($result_distritos) > 0) {
        while ($row = mysqli_fetch_assoc($result_distritos)) {
            echo '<option value="' . $row['id'] . '">' . $row['name'] . '</option>';
        }
    } else {
        echo '<option value="">No hay distritos disponibles</option>';
    }
    exit;  // Finaliza el script aquí para evitar otros outputs
}

// Procesar el formulario
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['sector'], $_POST['caserio'], $_POST['departamento'], $_POST['provincia'], $_POST['distrito'])) {
    $sector = $_POST['sector'] ?? null;
    $caserio = $_POST['caserio'] ?? null;
    $departamento_id = $_POST['departamento'];
    $provincia_id = $_POST['provincia'];
    $distrito_id = $_POST['distrito'];

    // Consultas para obtener los nombres de los departamentos, provincias y distritos
    $query_departamento = "SELECT name FROM ubigeo_peru_departments WHERE id = $departamento_id";
    $result_departamento = mysqli_query($conexion, $query_departamento);
    $departamento_name = mysqli_fetch_assoc($result_departamento)['name'];

    $query_provincia = "SELECT name FROM ubigeo_peru_provinces WHERE id = $provincia_id";
    $result_provincia = mysqli_query($conexion, $query_provincia);
    $provincia_name = mysqli_fetch_assoc($result_provincia)['name'];

    $query_distrito = "SELECT name FROM ubigeo_peru_districts WHERE id = $distrito_id";
    $result_distrito = mysqli_query($conexion, $query_distrito);
    $distrito_name = mysqli_fetch_assoc($result_distrito)['name'];

    // Insertar en la base de datos con los nombres
    $query_insert = "INSERT INTO lugares_multi (id_py, sector, caserio, distrito_em, provincia_em, departamento_em) 
                     VALUES ('$id_proyecto', '$sector', '$caserio', '$distrito_name', '$provincia_name', '$departamento_name')";

    if (mysqli_query($conexion, $query_insert)) {
        // Redireccionar después de guardar los datos
        header("Location: " . $_SERVER['PHP_SELF']); // Redirige a la misma página
        exit;  // Evitar que el resto del código se ejecute después de la redirección
    } else {
        echo "<script>alert('Error al guardar los datos: " . mysqli_error($conexion) . "');</script>";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Lugares de Ejecución - SISTEMA DIRSU</title>
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <script src="../plogins/jquery/jquery.min.js"></script>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
    <div class="wrapper">
        <section class="content">
            <div class="container-fluid">
                <!-- Formulario y lista en un contenedor fluido -->
                <div class="row">
                    <!-- Primer div (Formulario de Registro) -->
                    <div class="col-md-5">
                        <h6 class="alert alert-success">Añadir más lugares de ejecución de proyecto<i class="fa fa-plus" style="float: right;"></i></h6>
                        <!-- Formulario para añadir lugares -->
                        <form method="POST">
                            <!-- Input Sector -->
                            <div class="form-group">
                                <label for="sector">Ingresa un sector</label>
                                <input type="text" id="sector" name="sector" class="form-control" maxlength="100" placeholder="Ingresa un sector">
                            </div>
                            <!-- Input Caserío -->
                            <div class="form-group">
                                <label for="caserio">Ingresa un caserío</label>
                                <input type="text" id="caserio" name="caserio" class="form-control" maxlength="100" placeholder="Ingresa un caserío">
                            </div>
                            <!-- Select Departamento -->
                            <div class="form-group">
                                <label for="departamento">Selecciona un departamento</label>
                                <select id="departamento" name="departamento" class="form-control" required>
                                    <option value="">Selecciona un departamento</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_departamentos)) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['name']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <!-- Select Provincia -->
                            <div class="form-group">
                                <label for="provincia">Selecciona una provincia</label>
                                <select id="provincia" name="provincia" class="form-control" required>
                                    <option value="">Selecciona una provincia</option>
                                </select>
                            </div>
                            <!-- Select Distrito -->
                            <div class="form-group">
                                <label for="distrito">Selecciona un distrito</label>
                                <select id="distrito" name="distrito" class="form-control" required>
                                    <option value="">Selecciona un distrito</option>
                                </select>
                            </div>

                            <!-- Botón Guardar centrado -->
                            <div class="text-center">
                                <button type="submit" class="btn btn-primary">Añadir</button>
                            </div>
                        </form>
                    </div>

                    <!-- Segundo div (Lista de Lugares de Ejecución) -->
                    <div class="col-md-7">
                        <h6 class="alert alert-success">Mis lugares de ejecución adicionales<i class="fa fa-list" style="float: right;"></i></h6>
                        <?php
                        // Verificamos si hay registros y los mostramos en una tabla
                        if (mysqli_num_rows($result) > 0) {
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead class="thead-dark">';
                            echo '<tr>';
                            echo '<th>N°</th>';
                            echo '<th>Sector</th>';
                            echo '<th>Caserío</th>';
                            echo '<th>Departamento</th>';
                            echo '<th>Provincia</th>';
                            echo '<th>Distrito</th>';
                            echo '<th>Acciones</th>';
                            echo '</tr>';
                            echo '</thead>';
                            echo '<tbody>';

                            // Inicializar el contador de filas
                            $contador = 1;

                            // Mostrar los registros en la tabla
                            while ($row = mysqli_fetch_assoc($result)) {
                                echo "<tr>";
                                echo "<td>" . $contador . "</td>";
                                echo "<td>" . $row['sector'] . "</td>";
                                echo "<td>" . $row['caserio'] . "</td>";
                                echo "<td>" . $row['departamento_em'] . "</td>";
                                echo "<td>" . $row['provincia_em'] . "</td>";
                                echo "<td>" . $row['distrito_em'] . "</td>";
                                echo "<td><a href='?eliminar=" . $row['id'] . "' class='btn btn-danger' onclick='return confirm(\"¿Estás seguro de eliminar este registro?\");'><i class='fas fa-trash'></i></a></td>";
                                echo "</tr>";

                                $contador++;
                            }

                            echo '</tbody>';
                            echo '</table>';
                        } else {
                            echo "<table class='table table-striped' style='border-radius: 0.5rem; overflow: hidden;'>
                                    <tr>
                                        <td style='text-align: center; vertical-align: middle;'>
                                            <p>Aún no tienes lugares de ejecución adicionales.</p>
                                        </td>
                                    </tr>
                                  </table>";
                        }
                        ?>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="../plogins/moment/moment.min.js"></script>
    <script src="../plogins/inputmask/jquery.inputmask.min.js"></script>
    <script src="../dust/js/adminlte.min.js"></script>

    <script>
        $(document).ready(function() {
            // Cargar provincias y distritos cuando se seleccione un departamento
            $('#departamento').on('change', function() {
                var departamentoId = $(this).val();
                if (departamentoId) {
                    $('#provincia').html('<option value="">Selecciona una provincia</option>');
                    $('#distrito').html('<option value="">Selecciona un distrito</option>');

                    $.ajax({
                        type: 'POST',
                        url: 'lugares_ejecucion.php',
                        data: { departamento_id: departamentoId },
                        success: function(data) {
                            $('#provincia').html(data);

                            var provinciaId = $('#provincia').val();
                            if (provinciaId) {
                                $.ajax({
                                    type: 'POST',
                                    url: 'lugares_ejecucion.php',
                                    data: { provincia_id: provinciaId },
                                    success: function(data) {
                                        $('#distrito').html(data);
                                    }
                                });
                            }
                        }
                    });
                } else {
                    $('#provincia').html('<option value="">Selecciona una provincia</option>');
                    $('#distrito').html('<option value="">Selecciona un distrito</option>');
                }
            });

            // Cargar distritos cuando se seleccione una provincia
            $('#provincia').on('change', function() {
                var provinciaId = $(this).val();
                if (provinciaId) {
                    $.ajax({
                        type: 'POST',
                        url: 'lugares_ejecucion.php',
                        data: { provincia_id: provinciaId },
                        success: function(data) {
                            $('#distrito').html(data);
                        }
                    });
                } else {
                    $('#distrito').html('<option value="">Selecciona un distrito</option>');
                }
            });
        });
    </script>
</body>
</html>
