<?php
require_once __DIR__ . '/includes/rsu_diag.php';
rsu_diag_context('entry_point', 'login.php');
rsu_diag_context('evt_file_exists', file_exists(__DIR__ . '/includes/evt_mantenimiento.php') ? 'yes' : 'no');
rsu_diag_context('db_connection_file_exists', file_exists(__DIR__ . '/includes/db_connection.php') ? 'yes' : 'no');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/includes/evt_mantenimiento.php';

$evtMtoState = evt_mto_fetch_state();
$evtMtoEnabled = ((int)$evtMtoState['sistema_activo'] === 0);
$evtMtoBypass = evt_mto_has_bypass_session();
$evtLoginBlocked = ($evtMtoEnabled && !$evtMtoBypass);
$evtUnlockCsrf = evt_mto_get_csrf_token('evt_mantenimiento_unlock_csrf');

$evtTitulo = trim(isset($evtMtoState['titulo']) ? (string)$evtMtoState['titulo'] : '');
if ($evtTitulo === '') {
    $evtTitulo = evt_mto_default_title();
}

$evtMensaje = trim(isset($evtMtoState['mensaje']) ? (string)$evtMtoState['mensaje'] : '');
if ($evtMensaje === '') {
    $evtMensaje = evt_mto_default_message();
}

$evtImagen = trim(isset($evtMtoState['imagen']) ? (string)$evtMtoState['imagen'] : '');
if ($evtImagen === '') {
    $evtImagen = evt_mto_default_image();
}
?>
<!doctype html>
<html lang="en">
<head>
    <title>Iniciar Sesi&oacute;n - Sistema DIRSU</title>
    <link href="imagenes/dirsu_128_128.ico" rel="icon">
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <link rel="stylesheet" href="css/style.css">
    <style>
        .mto-img {
            background-image: url('<?php echo htmlspecialchars($evtImagen, ENT_QUOTES, 'UTF-8'); ?>');
            background-size: contain;
            background-repeat: no-repeat;
            background-position: center;
            min-height: 320px;
            border-top-left-radius: .5rem;
            border-bottom-left-radius: .5rem;
        }
        .modal-content.rounded-lg { border-radius: .75rem; }
        #mtoUnlockFeedback { min-height: 20px; }
    </style>
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
                                <h3 class="text-center mb-4">Iniciar sesi&oacute;n</h3>
                            </div>
                        </div>

                        <?php
                        if (isset($_GET['error']) && $_GET['error'] == 1) {
                            echo "<p style='color:red'>Usuario y/o contrase&ntilde;a incorrectos ...</p>";
                        } elseif (isset($_GET['error']) && $_GET['error'] == 5) {
                            echo "<p style='color:#b36b00'>Este usuario est&aacute; desactivado. Solicita activaci&oacute;n al &aacute;rea DIRSU.</p>";
                        } elseif (isset($_GET['error']) && $_GET['error'] == 6) {
                            echo "<p style='color:#b36b00'>El sistema se encuentra en mantenimiento. Ingrese la clave secreta para continuar.</p>";
                        }
                        ?>

                        <form id="formAuthentication" class="signin-form" action="./componentes/sesion/validarSesion.php" method="POST">
                            <div class="form-group">
                                <label for="usuario" class="form-label fs-6">Usuario:
                                    <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Si eres coordinador de proyecto, tu usuario es tu C&Oacute;DIGO DOCENTE">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </label>
                                <input type="text" class="form-control" id="usuario" name="usuario" required <?php echo $evtLoginBlocked ? 'disabled' : ''; ?>>
                            </div>
                            <div class="form-group">
                                <label for="clave" class="form-label fs-6">Contrase&ntilde;a:
                                    <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Si olvidaste tu contrase&ntilde;a solicita una nueva en dirsu@unitru.edu.pe">
                                        <i class="fa fa-info-circle"></i>
                                    </button>
                                </label>
                                <div class="input-group">
                                    <input type="password" class="form-control" id="clave" name="clave" required <?php echo $evtLoginBlocked ? 'disabled' : ''; ?>>
                                    <div class="input-group-append">
                                        <button type="button" id="togglePassword" class="btn btn-outline-secondary" <?php echo $evtLoginBlocked ? 'disabled' : ''; ?>>
                                            <i class="fa fa-eye" id="toggleIcon"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <br>
                            <button type="submit" id="btnIngresar" class="form-control btn btn-primary rounded submit px-3" <?php echo $evtLoginBlocked ? 'disabled' : ''; ?>>Ingresar</button>
                        </form>

                        <div class="form-group d-md-flex"></div>
                        <div class="d-flex justify-content-center align-items-center">
                            <span class="mr-2">&iquest;No tienes una cuenta?</span>
                            <button type="button" class="btn btn-link btn-sm d-inline-flex align-items-center text-decoration-none p-0" data-toggle="modal" data-target="#registroModal">
                                <i class="fa fa-book mr-1"></i> Ver gu&iacute;a
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
    (function () {
        var loginBlocked = <?php echo $evtLoginBlocked ? 'true' : 'false'; ?>;
        var unlockUrl = './componentes/sesion/evt_mantenimiento_unlock.php';
        var unlockCsrf = <?php echo json_encode($evtUnlockCsrf); ?>;

        function setLoginLock(lock) {
            $('#usuario, #clave, #btnIngresar, #togglePassword').prop('disabled', !!lock);
        }

        function setUnlockLoading(loading) {
            $('#btnUnlockMto').prop('disabled', !!loading);
            $('#mtoUnlockKey').prop('disabled', !!loading);
        }

        document.getElementById('togglePassword').addEventListener('click', function () {
            var passwordInput = document.getElementById('clave');
            var toggleIcon = document.getElementById('toggleIcon');
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

        function unlockLogin() {
            var key = $.trim($('#mtoUnlockKey').val());
            $('#mtoUnlockFeedback').removeClass('text-success text-danger').text('');

            if (key === '') {
                $('#mtoUnlockFeedback').addClass('text-danger').text('Ingrese la clave secreta.');
                return;
            }

            setUnlockLoading(true);
            $.ajax({
                url: unlockUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    clave: key,
                    csrf_token: unlockCsrf
                }
            }).done(function (res) {
                if (!res || !res.success) {
                    $('#mtoUnlockFeedback').addClass('text-danger').text((res && res.msg) ? res.msg : 'No se pudo validar la clave.');
                    $('#mtoUnlockKey').val('').trigger('focus');
                    return;
                }

                $('#mtoUnlockFeedback').addClass('text-success').text('Clave valida. Ya puede iniciar sesion.');
                setLoginLock(false);
                $('#mantenimientoModal').modal('hide');
            }).fail(function () {
                $('#mtoUnlockFeedback').addClass('text-danger').text('Error de comunicacion con el servidor.');
            }).always(function () {
                setUnlockLoading(false);
            });
        }

        $(document).ready(function () {
            $('[data-toggle="tooltip"]').tooltip();
            setLoginLock(loginBlocked);

            if (loginBlocked) {
                $('#mantenimientoModal').modal({ backdrop: 'static', keyboard: false, show: true });
            }

            $('#btnUnlockMto').on('click', unlockLogin);
            $('#mtoUnlockKey').on('keypress', function (e) {
                if (e.which === 13) {
                    e.preventDefault();
                    unlockLogin();
                }
            });
        });
    })();
</script>

<div class="modal fade" id="registroModal" tabindex="-1" role="dialog" aria-labelledby="registroModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="registroModalLabel"><img src="imagenes/dirsu_128_128.ico" alt="Logo DIRSU" style="width: 24px; height: 24px;" class="mr-2"> Guia para acceder al Sistema DIRSU</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body text-justify">
                <p>
                    <strong>Ya tienes usuario, pero olvidaste tu contrase&ntilde;a?</strong><br>
                    Solicita una nueva escribiendo a
                    <span id="copiarCorreo" class="text-primary font-weight-bold" style="cursor: pointer;" title="Haz clic para copiar">dirsu@unitru.edu.pe</span>,
                    indicando tu <em>c&oacute;digo de docente</em> y <em>nombre completo</em>.
                </p>
                <hr>
                <p>
                    <strong>&iquest;A&uacute;n no tienes usuario y deseas registrar tu primer proyecto?</strong><br>
                    La creaci&oacute;n de usuarios se realiza en coordinaci&oacute;n con los <em>presidentes de comit&eacute; de facultad</em> durante fechas establecidas cada semestre por la DIRSU.<br>
                    Las facultades env&iacute;an a DIRSU los <em>c&oacute;digos de los docentes coordinadores</em> y nosotros generamos las credenciales.<br>
                    Cuando inicie la admisi&oacute;n de nuevos proyectos, se notificar&aacute; a las facultades por correo.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="mantenimientoModal" tabindex="-1" role="dialog" aria-labelledby="mantenimientoLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content rounded-lg shadow">
            <div class="modal-body p-0">
                <div class="row no-gutters">
                    <div class="col-md-6 d-none d-md-block">
                        <div class="mto-img w-100 h-100" role="img" aria-label="Mantenimiento"></div>
                    </div>
                    <div class="col-12 col-md-6 p-4">
                        <h5 class="mb-2 text-center" id="mantenimientoLabel"><?php echo htmlspecialchars($evtTitulo, ENT_QUOTES, 'UTF-8'); ?></h5>
                        <p class="text-justify mb-3" style="line-height:1.4;">
                            <?php echo nl2br(htmlspecialchars($evtMensaje, ENT_QUOTES, 'UTF-8')); ?>
                        </p>
                        <div class="d-block d-md-none text-center mb-3">
                            <img src="<?php echo htmlspecialchars($evtImagen, ENT_QUOTES, 'UTF-8'); ?>" alt="Mantenimiento" class="img-fluid" style="max-height:140px; object-fit:contain;">
                        </div>
                        <div class="form-group mb-2">
                            <label for="mtoUnlockKey" class="mb-1">Clave secreta de mantenimiento</label>
                            <div class="input-group">
                                <input type="password" id="mtoUnlockKey" class="form-control" placeholder="Ingrese la clave para continuar" autocomplete="off">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" id="btnUnlockMto" type="button">Desbloquear</button>
                                </div>
                            </div>
                            <small class="form-text text-muted">El desbloqueo dura para esta sesion actual del navegador.</small>
                            <div id="mtoUnlockFeedback" class="small mt-2"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    const correoSpan = document.getElementById('copiarCorreo');
    if (correoSpan) {
        correoSpan.addEventListener('click', function () {
            const correo = 'dirsu@unitru.edu.pe';
            navigator.clipboard.writeText(correo).then(() => {
                const originalText = correoSpan.innerText;
                correoSpan.innerText = 'Copiado';
                correoSpan.classList.add('text-success');
                setTimeout(() => {
                    correoSpan.innerText = originalText;
                    correoSpan.classList.remove('text-success');
                }, 2000);
            }).catch((err) => {
                console.error('Error al copiar el correo: ', err);
            });
        });
    }
</script>
</body>
</html>
