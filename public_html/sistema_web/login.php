<!doctype html>
<html lang="en">
<head>
    <title>Iniciar Sesión - Sistema DIRSU</title>
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
            <div class="col-md-7 col-lg-5">
                <div class="wrap">
                    <div class="img" style="background-image: url(imagenes/login_portada_3.webp);"></div>
                    <div class="login-wrap p-4 p-md-5">
                        <div class="d-flex">
                            <div class="w-100">
                                <h3 class="text-center mb-4"> Iniciar sesión</h3>
                            </div>
                        </div>

                        <?php
                        if (isset($_GET['error']) && $_GET['error'] == 1) {
                            echo "<p style='color:red'> Usuario y/o contraseña incorrectos ...</p>";
                        } elseif (isset($_GET['error']) && $_GET['error'] == 5) {
                            echo "<p style='color:#b36b00'> Este usuario est&aacute; desactivado. Solicita activaci&oacute;n al &aacute;rea DIRSU.</p>";
                        }
                        ?>
                        <!-- INICIO DE FORMULARIO -->
                        <form id="formAuthentication" class="signin-form" action="./componentes/sesion/validarSesion.php" method="POST">
                            <div class="form-group">
                                <label for="usuario" class="form-label fs-6">Usuario: <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Si eres coordinador de proyecto, tu usuario es tu CÓDIGO DOCENTE">
            <i class="fa fa-info-circle"></i>
        </button></label>
                                <input type="text" class="form-control" id="usuario" name="usuario" required>
                            </div>
                            <div class="form-group">
                                <label for="clave" class="form-label fs-6">Contraseña: <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Si olvidaste tu contraseña solicita una nueva en dirsu@unitru.edu.pe ✉️">
            <i class="fa fa-info-circle"></i>
        </button></label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="clave" name="clave" required>
                                    <div class="input-group-append">
                                        <button type="button" id="togglePassword" class="btn btn-outline-secondary">
                                            <i class="fa fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <button type="submit" class="form-control btn btn-primary rounded submit px-3">Ingresar</button>
                        </form>
                        <div class="form-group d-md-flex">
                            <!-- <div class="w-50 text-left">
                                <label class="checkbox-wrap checkbox-primary mb-0">Recordar
                                    <input type="checkbox" checked>
                                    <span class="checkmark"></span>
                                </label>
                            </div> -->
                            <!-- <div class="w-50 text-md-right">
                                <a href="#">¿Olvidaste tu contraseña?</a>
                            </div> -->
                        </div>
                        <!-- FIN DE FORMULARIO -->
<div class="d-flex justify-content-center align-items-center">
  <span class="mr-2">¿No tienes una cuenta?</span>
  <button type="button" class="btn btn-link btn-sm d-inline-flex align-items-center text-decoration-none p-0" data-toggle="modal" data-target="#registroModal">
    <i class="fa fa-book mr-1"></i> Ver guía
  </button>
</div>
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
    document.getElementById('togglePassword').addEventListener('click', function () {
        const passwordInput = document.getElementById('clave');
        const toggleIcon = document.getElementById('toggleIcon');
        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            toggleIcon.classList.remove('fa-eye');
            toggleIcon.classList.add('fa-eye-slash');
        } else {
            passwordInput.type = 'password';
            toggleIcon.classList.remove('fa-eye-slash');
            toggleIcon.classList.add('fa-eye');
        }
    });
    $(document).ready(function(){
           $('[data-toggle="tooltip"]').tooltip(); 
         });
</script>
<!-- Modal -->
<div class="modal fade" id="registroModal" tabindex="-1" role="dialog" aria-labelledby="registroModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="registroModalLabel"><img src="imagenes/dirsu_128_128.ico" alt="Logo DIRSU" style="width: 24px; height: 24px;" class="mr-2"> Guía para acceder al Sistema DIRSU</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
<div class="modal-body text-justify">
  <p>
    <strong>🔒 ¿Ya tienes usuario, pero olvidaste tu contraseña?</strong><br>
    Solicita una nueva escribiendo a 
<span id="copiarCorreo" class="text-primary font-weight-bold" style="cursor: pointer;" title="Haz clic para copiar">dirsu@unitru.edu.pe</span>, 
    indicando tu <em>código de docente</em> y <em>nombre completo</em>.
  </p>
  <hr>
  <p>
    <strong>📝 ¿Aún no tienes usuario y deseas registrar tu primer proyecto?</strong><br>
    La creación de usuarios se realiza en coordinación con los <em>presidentes de comité de facultad</em> durante fechas establecidas cada semestre por la DIRSU.<br>
    Las facultades envían a DIRSU los <em>códigos de los docentes coordinadores</em> y nosotros generamos las credenciales.<br>
    Cuando inicie la admisión de nuevos proyectos, se notificará a las facultades por correo.
  </p>
</div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>
<script>
  const correoSpan = document.getElementById('copiarCorreo');
  correoSpan.addEventListener('click', function () {
    const correo = 'dirsu@unitru.edu.pe';
    navigator.clipboard.writeText(correo).then(() => {
      // Guardar texto original
      const originalText = correoSpan.innerText;

      // Cambiar temporalmente el contenido
      correoSpan.innerText = '¡Copiado!';
      correoSpan.classList.add('text-success');

      // Restaurar después de 2 segundos
      setTimeout(() => {
        correoSpan.innerText = originalText;
        correoSpan.classList.remove('text-success');
      }, 2000);
    }).catch((err) => {
      console.error('Error al copiar el correo: ', err);
    });
  });
</script>
</body>
</html>
