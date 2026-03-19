<?php
   // Incluir configSesion.php para verificar la sesión
   include "../componentes/configSesion.php";
   
   // Incluir la conexión a la base de datos
   include('../componentes/db.php');
   
   // Incluir el archivo que carga los datos del proyecto
   include('../componentes/proyecto/cargar_proyecto.php');
   
   // Incluir el archivo que carga los demás lugares de ejecución si existen
   include '../componentes/proyecto/mostrar_unidades.php';
   
   // Variables de sesión
   $id_proyecto = $_SESSION['id_py'];
   
    // Consultas para cargar los departamentos
    $query_facultades = "SELECT id, nombre FROM facultades";
    $result_facultades = mysqli_query($conexion, $query_facultades);
    
    $query_escuelas = "SELECT id_escuela, nombre_escuela FROM escuelas";
    $result_escuelas = mysqli_query($conexion, $query_escuelas);
    
    $query_departamentos = "SELECT id, nombre FROM departamentos ORDER BY nombre ASC";
    $result_departamentos = mysqli_query($conexion, $query_departamentos);

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['facultades'], $_POST['escuelas'], $_POST['departamentos'])) {
    $facultades_id = $_POST['facultades'];
    $escuelas_id = $_POST['escuelas'];
    $departamentos_id = $_POST['departamentos'];

    // Consultas para obtener los nombres de los departamentos, provincias y distritos
    $query_facultades = "SELECT nombre FROM facultades WHERE id = $facultades_id";
    $result_facultades = mysqli_query($conexion, $query_facultades);
    $facultades_nombre = mysqli_fetch_assoc($result_facultades)['nombre'];
    
    $query_escuelas = "SELECT nombre_escuela FROM escuelas WHERE id_escuela = $escuelas_id";
    $result_escuelas = mysqli_query($conexion, $query_escuelas);
    $escuelas_nombre = mysqli_fetch_assoc($result_escuelas)['nombre_escuela'];
    
    $query_departamentos = "SELECT nombre FROM departamentos WHERE id = $departamentos_id";
    $result_departamentos = mysqli_query($conexion, $query_departamentos);
    $departamentos_nombre = mysqli_fetch_assoc($result_departamentos)['nombre'];

    // Insertar en la base de datos con los nombres
    $query_insert = "INSERT INTO unidades_multi (id_py, facultad_um, escuela_um, departamento_um) 
                     VALUES ('$id_proyecto', '$facultades_nombre', '$escuelas_nombre', '$departamentos_nombre')";

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
      <title>Unidades ejecutoras - SISTEMA DIRSU</title>
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
                        <h6 class="alert alert-success">Añadir más unidades ejecutoras del proyecto<i class="fa fa-plus" style="float: right;"></i></h6>
                        <!-- Formulario para añadir lugares -->
                        <form method="POST">
                            <!-- Select Departamento -->
                            <div class="form-group">
                                <label for="facultades">Selecciona una Facultad</label>
                                <select id="facultades" name="facultades" class="form-control" required>
                                    <option value="">Selecciona una Facultad</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_facultades)) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="escuelas">Selecciona una Escuela</label>
                                <select id="escuelas" name="escuelas" class="form-control" required>
                                    <option value="">Selecciona una escuela</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_escuelas)) { ?>
                                        <option value="<?php echo $row['id_escuela']; ?>"><?php echo $row['nombre_escuela']; ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="departamentos">Selecciona un Departamento Académico</label>
                                <select id="departamentos" name="departamentos" class="form-control" required>
                                    <option value="">Selecciona un Departamento académico</option>
                                    <?php while ($row = mysqli_fetch_assoc($result_departamentos)) { ?>
                                        <option value="<?php echo $row['id']; ?>"><?php echo $row['nombre']; ?></option>
                                    <?php } ?>
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
                        <h6 class="alert alert-success">Mis unidades de ejecución adicionales<i class="fa fa-list" style="float: right;"></i></h6>
                        <?php
                        // Verificamos si hay registros y los mostramos en una tabla
                        if (mysqli_num_rows($result) > 0) {
                            echo '<table class="table table-bordered table-striped">';
                            echo '<thead class="thead-dark">';
                            echo '<tr>';
                            echo '<th>N°</th>';
                            echo '<th>Facultad</th>';
                            echo '<th>Escuela</th>';
                            echo '<th>Departamento Académico</th>';
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
                                echo "<td>" . $row['facultad_um'] . "</td>";
                                echo "<td>" . $row['escuela_um'] . "</td>";
                                echo "<td>" . $row['departamento_um'] . "</td>";
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
                                            <p>Aún no tienes unidades ejecutoras adicionales.</p>
                                        </td>
                                    </tr>
                                  </table>";
                        }
                        ?>
                    </div
                </div>
            </div>
         </section>
      </div>
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <script src="../plogins/moment/moment.min.js"></script>
      <script src="../plogins/inputmask/jquery.inputmask.min.js"></script>
      <script src="../dust/js/adminlte.min.js"></script>
   </body>
</html>