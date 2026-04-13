<?php
// Incluir el archivo de conexión a la base de datos
include_once('../bd.php'); // Asegúrate de que la ruta sea correcta según tu estructura

// Obtener facultades
$queryFacultad = "SELECT id, nombre FROM facultades ORDER BY nombre ASC";
$resultFacultad = mysqli_query($conexion, $queryFacultad);

// Obtener departamentos
$queryDepartamento = "SELECT id, nombre FROM departamentos ORDER BY nombre ASC"; // Cambio aquí a "departamentos"
$resultDepartamento = mysqli_query($conexion, $queryDepartamento);
?>

<form action="logica_panel/validar_crear_autoridad.php" method="POST">
    <div class="row">
        <!-- Nombres -->
        <div class="form-group col-md-6">
            <label for="nombres" class="form-label">Nombres</label>
            <input type="text" class="form-control" id="nombres" name="nombres" required>
        </div>
        <!-- Apellidos -->
        <div class="form-group col-md-6">
            <label for="apellidos" class="form-label">Apellidos</label>
            <input type="text" class="form-control" id="apellidos" name="apellidos" required>
        </div>
    </div>
    <div class="row">
        <!-- Código o DNI -->
        <div class="form-group col-md-6">
            <label for="usuario" class="form-label">Código o DNI</label>
            <input type="text" class="form-control" id="usuario" name="usuario" required minlength="4" maxlength="8">
        </div>
        <!-- Sede -->
        <div class="form-group col-md-6">
            <label for="id_sede" class="form-label">Sede</label><br>
            <select class="form-control" id="id_sede" name="id_sede" required>
                <option value="1">Trujillo</option>
                <option value="2">Jequetepeque</option>
                <option value="3">Huamachuco</option>
                <option value="4">Santiago de Chuco</option>
            </select>
        </div>
    </div> 
    <div class="row">
        <!-- Contraseña -->
        <div class="form-group col-md-6">
            <label for="clave" class="form-label">Contraseña</label>
            <div class="input-group">
                <input type="password" class="form-control" id="clave" name="clave" required>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clave')">
                        <i class="fa fa-eye" id="toggleClaveIcon"></i>
                    </button>
                </div>
            </div>
        </div>
        <!-- Confirmar Contraseña -->
        <div class="form-group col-md-6">
            <label for="clave2" class="form-label">Confirmar Contraseña</label>
            <div class="input-group">
                <input type="password" class="form-control" id="clave2" name="clave2" required>
                <div class="input-group-append">
                    <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clave2')">
                        <i class="fa fa-eye" id="toggleClave2Icon"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <!-- Tipo de Usuario -->
        <div class="form-group col-md-12">
            <label for="id_rol" class="form-label">Tipo de Usuario</label><br>
            <select class="form-control" id="id_rol" name="id_rol" required>
                <option value="1">Director de DIRSU</option>
                <option value="3">Decano de la Facultad</option>
                <option value="4">Director de Departamento</option>
                <option value="5">Presidente de Comité de RS de Facultad</option>
            </select>
        </div>
    </div>

    <!-- Facultad (oculto por defecto) -->
    <div class="row" id="facultad-container" style="display: none;">
        <div class="form-group col-md-12">
            <label for="facultad" class="form-label">Facultad</label><br>
            <select class="form-control" id="facultad" name="id_escuela">
            <option value="0" selected>Sin Facultad</option>
                <?php
                // Verificar si hay resultados para facultades
                if (mysqli_num_rows($resultFacultad) > 0) {
                    while ($facultad = mysqli_fetch_assoc($resultFacultad)) {
                        echo "<option value='" . $facultad['id'] . "'>" . $facultad['nombre'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay facultades disponibles</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <!-- Departamento (oculto por defecto) -->
    <div class="row" id="departamento-container" style="display: none;">
        <div class="form-group col-md-12">
            <label for="departamento" class="form-label">Departamento</label><br>
            <select class="form-control" id="departamento" name="id_depa">
            <option value="0" selected>Sin Departamento Académico</option>
                <?php
                // Verificar si hay resultados para departamentos
                if (mysqli_num_rows($resultDepartamento) > 0) {
                    while ($departamento = mysqli_fetch_assoc($resultDepartamento)) {
                        echo "<option value='" . $departamento['id'] . "'>" . $departamento['nombre'] . "</option>";
                    }
                } else {
                    echo "<option value=''>No hay departamentos disponibles</option>";
                }
                ?>
            </select>
        </div>
    </div>

    <div class="row">
        <div class="form-group col-md-12 text-center">
            <button type="submit" class="btn btn-primary">Registrar usuario</button>
        </div>
    </div>
</form>

<script>
    // Función para mostrar/ocultar los selects según el Tipo de Usuario seleccionado
document.getElementById('id_rol').addEventListener('change', function() {
    var tipoUsuario = this.value;

    // Ocultar todos los select nuevos por defecto
    document.getElementById('facultad-container').style.display = 'none';
    document.getElementById('departamento-container').style.display = 'none';

    // Mostrar los select de acuerdo con el Tipo de Usuario seleccionado
    if (tipoUsuario == "3" || tipoUsuario == "5") {
        // Mostrar solo Facultad para "Decano de la Facultad" y "Presidente de Comité de RS"
        document.getElementById('facultad-container').style.display = 'block';
    }

    if (tipoUsuario == "4") {
        // Mostrar solo Departamento para "Director de Departamento"
        document.getElementById('departamento-container').style.display = 'block';
    }
});

// Ejecutar el cambio al cargar la página para que se configure la visibilidad inicial
document.getElementById('id_rol').dispatchEvent(new Event('change'));


    // Validación para el campo de código (solo números)
    document.getElementById('usuario').addEventListener('input', function (e) {
        var value = e.target.value;
        if (!/^\d{0,8}$/.test(value)) {
            e.target.value = value.replace(/[^\d]/g, ''); // Elimina caracteres no numéricos
        }
    });

    $(document).ready(function(){
        $('[data-toggle="tooltip"]').tooltip(); 
    });

    // Función para mostrar/ocultar la contraseña
    function togglePassword(id) {
        var input = document.getElementById(id);
        var icon = document.getElementById('toggle' + id.charAt(0).toUpperCase() + id.slice(1) + 'Icon');
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = "password";
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
