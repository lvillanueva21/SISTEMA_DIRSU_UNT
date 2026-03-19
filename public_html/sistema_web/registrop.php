<!doctype html>
<html lang="en">
   <head>
      <title>Registrar usuario - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="imagenes/dirsu_128_128.ico" rel="icon">
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
      <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
      <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
      <link rel="stylesheet" href="css/style.css">
   </head>
   <body>
      <section class="ftco-section">
         <div class="container">
            <div class="row justify-content-center">
               <div class="col-md-7 col-lg-6">
                  <div class="wrap">
                     <div class="img" style="background-image: url(imagenes/login_portada_3.webp);"></div>
                     <div class="login-wrap p-4 p-md-5">
                        <div class="d-flex">
                           <div class="w-100">
                              <h3 class="text-center mb-4">Registrate en el Sistema DIRSU</h3>
                              <p class="text-center">Llena todos los campos del formulario</p>
                           </div>
                        </div>
                        <!-- INICIO DE FORMULARIO -->
                        <!-- Se envía el formulario por método POST -->
                        <?php
                           if (isset($_GET['alert'])) {
                               if ($_GET['alert'] == 1) {
                                   echo "<div class='alert alert-warning' role='alert'>El usuario ingresado ya existe, inicie sesión o registre otro.</div>";
                               } elseif ($_GET['alert'] == 2) {
                                   echo "<div class='alert alert-success text-center' role='alert'>Registro exitoso, haz clic para  <a href='/sistema_web/login.php'> Iniciar sesión</a></div>";
                               } elseif ($_GET['alert'] == 3) {
                                   echo "<div class='alert alert-danger' role='alert'>Error al registrar, intente nuevamente</div>";
                               } elseif ($_GET['alert'] == 4) {
                                   echo "<div class='alert alert-info' role='alert'>Las contraseñas ingresadas no coinciden</div>";
                               }
                           }
                        ?>
                        <form id="formAuthentication" class="signin-form" action="./componentes/sesion/validarRegistro.php" method="POST">
                           <div class="row">
                              <div class="form-group col-md-12">
                                 <label for="departamento_academico" class="form-label fs-6">Departamento Académico:</label>
                                 <select class="form-control" id="id_depa" name="id_depa" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="1">Agronomía y Zootecnia</option>
                                    <option value="2">Ciencias Agroindustriales</option>
                                    <option value="3">Ciencias Biológicas</option>
                                    <option value="4">Microbiología y Parasitología</option>
                                    <option value="5">Pesquería</option>
                                    <option value="6">Química Biológica y Fisiología Animal</option>
                                    <option value="7">Administración</option>
                                    <option value="8">Contabilidad y Finanzas</option>
                                    <option value="9">Economía</option>
                                    <option value="10">Ciencias Básicas Estomatológicas</option>
                                    <option value="11">Estomatología</option>
                                    <option value="12">Estadística</option>
                                    <option value="13">Física</option>
                                    <option value="14">Informática</option>
                                    <option value="15">Matemáticas</option>
                                    <option value="16">Arqueología y Antropología</option>
                                    <option value="17">Ciencias Sociales</option>
                                    <option value="18">Ciencias Jurídicas Públicas y Políticas</option>
                                    <option value="19">Ciencias Jurídicas Privadas y Sociales</option>
                                    <option value="20">Ciencia Política y Gobernabilidad</option>
                                    <option value="21">Ciencias de la Educación</option>
                                    <option value="22">Ciencias Psicológicas</option>
                                    <option value="23">Comunicación Social</option>
                                    <option value="24">Filosofía y Arte</option>
                                    <option value="25">Historia y Geografía</option>
                                    <option value="26">Idiomas y Lingüística</option>
                                    <option value="27">Lengua Nacional y Literatura</option>
                                    <option value="28">Enfermería de la Mujer, Niño y Adolescente</option>
                                    <option value="29">Salud del Adulto</option>
                                    <option value="30">Salud Familiar y Comunitaria</option>
                                    <option value="31">Farmacotecnia</option>
                                    <option value="32">Farmacología</option>
                                    <option value="33">Bioquímica</option>
                                    <option value="34">Ingeniería Civil, Arquitectura y Urbanismo</option>
                                    <option value="35">Ingeniería Industrial</option>
                                    <option value="36">Ingeniería de Materiales</option>
                                    <option value="37">Mecánica y Energía</option>
                                    <option value="38">Ingeniería Metalúrgica</option>
                                    <option value="39">Ingeniería de Minas</option>
                                    <option value="40">Ingeniería de Sistemas</option>
                                    <option value="41">Ingeniería Química</option>
                                    <option value="42">Ingeniería Ambiental</option>
                                    <option value="43">Química</option>
                                    <option value="44">Ciencias Básicas Médicas</option>
                                    <option value="45">Cirugía</option>
                                    <option value="46">Fisiología Humana</option>
                                    <option value="47">Ginecología-Obstetricia</option>
                                    <option value="48">Medicina</option>
                                    <option value="49">Medicina Preventiva y Salud Pública</option>
                                    <option value="50">Morfología Humana</option>
                                    <option value="51">Pediatría</option>
                                 </select>
                              </div>
                           </div>
                           <div class="row">
                              <div class="form-group col-md-12">
                                 <label for="sede" class="form-label fs-6">Sede:</label>
                                 <select class="form-control" id="id_sede" name="id_sede" required>
                                    <option value="">Seleccione una opción</option>
                                    <option value="1">Trujillo</option>
                                    <option value="2">Jequetepeque</option>
                                    <option value="3">Huamachuco</option>
                                    <option value="4">Santiago de Chuco</option>
                                 </select>
                              </div>
                           </div>
                           <div class="row">
                              <div class="form-group col-md-5">
                                 <label for="nombres" class="form-label fs-6">Nombres:</label>
                                 <input type="text" class="form-control" id="nombres" name="nombres" required>
                              </div>
                              <div class="form-group col-md-7">
                                 <label for="apellidos" class="form-label fs-6">Apellidos:</label>
                                 <input type="text" class="form-control" id="apellidos" name="apellidos" required>
                              </div>
                           </div>
                           <div class="row">
                              <div class="form-group col-md-12">
    <label for="usuario" class="form-label fs-6">Código docente:
        <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Tu CÓDIGO DOCENTE será tu usuario para iniciar sesión.">
            <i class="fa fa-info-circle"></i>
        </button>
    </label>
    <input 
        type="text" 
        class="form-control" 
        id="usuario" 
        name="usuario" 
        required 
        minlength="4"
        maxlength="8"
        pattern="\d{4,8}" 
        title="Debe ingresar entre 4 y 8 dígitos numéricos.">
</div>
                              
                           </div>
                           <div class="row">
                               <div class="form-group col-md-12">
                                 <label for="clave" class="form-label fs-6">Contraseña:</label>
                                 <div class="input-group">
                                    <input type="password" class="form-control" id="clave" name="clave" required>
                                    <div class="input-group-append">
                                       <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('clave')">
                                       <i class="fa fa-eye" id="toggleClaveIcon"></i>
                                       </button>
                                    </div>
                                 </div>
                              </div>
                           </div>
                           <div class="row">
                              <div class="form-group col-md-12">
                                 <label for="clave2" class="form-label fs-6">Confirmar contraseña:</label>
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
                           <br>
                           <button type="submit" class="form-control btn btn-primary rounded submit px-3">Crear cuenta</button>
                        </form>
                        <div class="form-group d-md-flex">
                        </div>
                        <p class="text-center">Tienes una cuenta? <a href="/sistema_web/login.php">Iniciar sesión</a></p>
                     </div>
                  </div>
               </div>
            </div>
         </div>
      </section>
      <script src="js/jquery.min.js"></script>
      <script src="js/popper.js"></script>
      <script src="js/bootstrap.min.js"></script>
      <script src="js/main.js"></script>
      <script>
        document.getElementById('usuario').addEventListener('input', function (e) {
        var value = e.target.value;
        if (!/^\d{0,8}$/.test(value)) {
        e.target.value = value.replace(/[^\d]/g, ''); // Elimina caracteres no numéricos
       }
       });
         $(document).ready(function(){
           $('[data-toggle="tooltip"]').tooltip(); 
         });
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
   </body>
</html>