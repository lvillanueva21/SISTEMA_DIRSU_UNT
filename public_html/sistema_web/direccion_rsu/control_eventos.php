<?php
include "../componentes/configSesion.php";
require_once "../includes/evt_mantenimiento.php";

$evtMtoCsrf = evt_mto_get_csrf_token('evt_mantenimiento_admin_csrf');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Control de eventos</title>
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <style>
        .evt-card-action {
            border: 1px solid #e9ecef;
            border-radius: 12px;
            transition: all .2s ease;
        }
        .evt-card-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0, 0, 0, .08);
        }
        .evt-status-pill {
            font-size: .78rem;
            font-weight: 600;
            padding: .35rem .6rem;
            border-radius: 999px;
            display: inline-block;
        }
        .btn-icon {
            min-width: 40px;
        }
    </style>
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">
    <nav class="main-header navbar navbar-expand navbar-white navbar-light">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                <a href="https://rsu.unitru.edu.pe" class="nav-link" target="_blank">
                    <p style="color: white;">Ir a página DIRSU</p>
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesión</a>
            </li>
        </ul>
    </nav>

    <?php include_once "../includes/sidebar.php"; ?>

    <div class="content-wrapper">
        <section class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-6">
                        <h1>Control de eventos</h1>
                    </div>
                    <div class="col-sm-6 text-sm-right">
                        <span id="evtMtoHeaderState" class="evt-status-pill bg-success">Sistema activo</span>
                    </div>
                </div>
            </div>
        </section>

        <section class="content" style="min-height: 400px;">
            <div class="container-fluid">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">Botonera de eventos</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-12 col-md-6 col-lg-4">
                                <div class="p-3 evt-card-action h-100">
                                    <div class="d-flex align-items-center mb-2">
                                        <div class="mr-3">
                                            <span class="btn btn-warning btn-sm disabled"><i class="fas fa-tools"></i></span>
                                        </div>
                                        <div>
                                            <h5 class="mb-1">Mantenimiento del sistema</h5>
                                            <small class="text-muted">Controla disponibilidad de login y acceso general.</small>
                                        </div>
                                    </div>
                                    <button type="button" id="btnOpenMantenimiento" class="btn btn-primary btn-block mt-3">
                                        Configurar mantenimiento
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-light border mt-4 mb-0">
                            Esta vista está preparada para agregar más controles en siguientes iteraciones.
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
        <div class="float-right d-none d-sm-inline-block">
            <p>Desarrollado por el <a href="#">Área informática - DIRSU</a></p>
        </div>
    </footer>
</div>

<div class="modal fade" id="modalMantenimiento" tabindex="-1" role="dialog" aria-labelledby="modalMantenimientoLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title" id="modalMantenimientoLabel">
                    <i class="fas fa-tools mr-2 text-warning"></i>Mantenimiento del sistema
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div id="evtMtoAlert" class="alert d-none"></div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <strong>Estado actual</strong>
                            <div id="evtMtoEstadoTexto" class="text-muted">Cargando...</div>
                        </div>
                        <span id="evtMtoEstadoBadge" class="badge badge-success">Activo</span>
                    </div>
                </div>

                <div class="custom-control custom-switch mb-3">
                    <input type="checkbox" class="custom-control-input" id="evtMtoSwitchActivo" checked>
                    <label class="custom-control-label" for="evtMtoSwitchActivo">Sistema activo</label>
                </div>

                <div class="alert alert-warning mb-3">
                    Al cambiar el estado se bloquearán o habilitarán accesos de forma inmediata.
                </div>

                <div class="form-group">
                    <label for="evtMtoClaveNueva">Crear o actualizar clave secreta</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="evtMtoClaveNueva" autocomplete="new-password" placeholder="Dejar en blanco para mantener la clave actual">
                        <div class="input-group-append">
                            <button class="btn btn-outline-secondary btn-icon" id="btnEvtMtoToggle" type="button" title="Mostrar u ocultar clave">
                                <i class="fa fa-eye" id="evtMtoEyeIcon"></i>
                            </button>
                            <button class="btn btn-outline-secondary btn-icon" id="btnEvtMtoCopy" type="button" title="Copiar clave escrita">
                                <i class="fa fa-copy"></i>
                            </button>
                        </div>
                    </div>
                    <small class="form-text text-muted">Mínimo 8 caracteres. No se mostrará ni se guardará en texto plano.</small>
                </div>

                <div class="mb-3">
                    <span id="evtMtoSecretState" class="badge badge-secondary">Clave no configurada</span>
                </div>

                <div class="form-group">
                    <label for="evtMtoTitulo">Título del mensaje de mantenimiento</label>
                    <input type="text" class="form-control" id="evtMtoTitulo" maxlength="180">
                </div>

                <div class="form-group">
                    <label for="evtMtoMensaje">Mensaje del mantenimiento</label>
                    <textarea id="evtMtoMensaje" class="form-control" rows="5" maxlength="5000"></textarea>
                </div>

                <div class="text-muted small">
                    Última actualización: <span id="evtMtoUpdatedAt">-</span>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
                <button type="button" id="btnGuardarMantenimiento" class="btn btn-primary">
                    <span class="normal-label">Guardar</span>
                    <span class="loading-label d-none"><i class="fas fa-spinner fa-spin mr-1"></i>Guardando...</span>
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="modalEvtMtoConfirm" tabindex="-1" role="dialog" aria-labelledby="modalEvtMtoConfirmLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title" id="modalEvtMtoConfirmLabel">Confirmar acción</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p class="mb-0" id="evtMtoConfirmText">¿Estás seguro?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-primary" id="btnEvtMtoConfirmAction">Confirmar</button>
            </div>
        </div>
    </div>
</div>

<script src="../plogins/jquery/jquery.min.js"></script>
<script src="../plogins/jquery-ui/jquery-ui.min.js"></script>
<script>$.widget.bridge('uibutton', $.ui.button);</script>
<script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../plogins/sparklines/sparkline.js"></script>
<script src="../plogins/jqvmap/jquery.vmap.min.js"></script>
<script src="../plogins/jqvmap/maps/jquery.vmap.usa.js"></script>
<script src="../plogins/jquery-knob/jquery.knob.min.js"></script>
<script src="../dust/js/adminlte.js"></script>
<script src="../dust/js/demo.js"></script>
<script src="../dust/js/pages/dashboard.js"></script>
<script>
    (function () {
        var apiUrl = 'funciones/evt_mantenimiento_api.php';
        var csrfToken = <?php echo json_encode($evtMtoCsrf); ?>;
        var currentState = null;
        var pendingPayload = null;

        function escapeHtml(value) {
            return String(value || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function showAlert(type, message) {
            var $alert = $('#evtMtoAlert');
            $alert.removeClass('d-none alert-success alert-danger alert-warning alert-info')
                .addClass('alert-' + type)
                .html(escapeHtml(message));
        }

        function setSaving(isSaving) {
            var $btn = $('#btnGuardarMantenimiento');
            $btn.prop('disabled', isSaving);
            $btn.find('.normal-label').toggleClass('d-none', isSaving);
            $btn.find('.loading-label').toggleClass('d-none', !isSaving);
        }

        function fallbackCopyText(text) {
            var aux = document.createElement('textarea');
            aux.value = text;
            aux.setAttribute('readonly', '');
            aux.style.position = 'absolute';
            aux.style.left = '-9999px';
            document.body.appendChild(aux);
            aux.select();
            aux.setSelectionRange(0, 99999);
            var ok = false;
            try {
                ok = document.execCommand('copy');
            } catch (e) {
                ok = false;
            }
            document.body.removeChild(aux);
            return ok;
        }

        function copySecretInput() {
            var value = $.trim($('#evtMtoClaveNueva').val());
            if (value === '') {
                showAlert('warning', 'Primero escribe una clave para poder copiarla.');
                return;
            }

            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(value).then(function () {
                    showAlert('success', 'Clave copiada al portapapeles.');
                }).catch(function () {
                    if (fallbackCopyText(value)) {
                        showAlert('success', 'Clave copiada al portapapeles.');
                    } else {
                        showAlert('danger', 'No se pudo copiar la clave.');
                    }
                });
                return;
            }

            if (fallbackCopyText(value)) {
                showAlert('success', 'Clave copiada al portapapeles.');
            } else {
                showAlert('danger', 'No se pudo copiar la clave.');
            }
        }

        function toggleSecretInput() {
            var input = document.getElementById('evtMtoClaveNueva');
            var icon = document.getElementById('evtMtoEyeIcon');
            if (!input || !icon) {
                return;
            }
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        function renderState(state) {
            currentState = state || {};
            var isActive = Number(currentState.sistema_activo) === 1;
            var hasSecret = !!currentState.has_secret;

            $('#evtMtoSwitchActivo').prop('checked', isActive);
            $('#evtMtoTitulo').val(currentState.titulo || '');
            $('#evtMtoMensaje').val(currentState.mensaje || '');
            $('#evtMtoClaveNueva').val('');
            $('#evtMtoClaveNueva').attr('type', 'password');
            $('#evtMtoEyeIcon').removeClass('fa-eye-slash').addClass('fa-eye');

            $('#evtMtoEstadoTexto').text(isActive ? 'Sistema activo. Los usuarios pueden iniciar sesión.' : 'Sistema en mantenimiento. Login bloqueado sin clave secreta.');
            $('#evtMtoEstadoBadge')
                .removeClass('badge-success badge-danger')
                .addClass(isActive ? 'badge-success' : 'badge-danger')
                .text(isActive ? 'Activo' : 'Mantenimiento');
            $('#evtMtoHeaderState')
                .removeClass('bg-success bg-danger')
                .addClass(isActive ? 'bg-success' : 'bg-danger')
                .text(isActive ? 'Sistema activo' : 'Sistema en mantenimiento');

            $('#evtMtoSecretState')
                .removeClass('badge-success badge-secondary')
                .addClass(hasSecret ? 'badge-success' : 'badge-secondary')
                .text(hasSecret ? 'Clave configurada' : 'Clave no configurada');

            $('#evtMtoUpdatedAt').text(currentState.actualizado_en ? currentState.actualizado_en : '-');
        }

        function loadState(showModal) {
            $.ajax({
                url: apiUrl,
                method: 'POST',
                dataType: 'json',
                data: {
                    action: 'get_state',
                    csrf_token: csrfToken
                }
            }).done(function (res) {
                if (!res || !res.success) {
                    showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo cargar la configuración.');
                    return;
                }
                renderState(res.data || {});
                $('#evtMtoAlert').addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info').text('');
                if (showModal) {
                    $('#modalMantenimiento').modal('show');
                }
            }).fail(function () {
                showAlert('danger', 'Error de comunicación con el servidor.');
            });
        }

        function buildPayload() {
            if (!currentState) {
                showAlert('warning', 'Primero cargue el estado del sistema.');
                return null;
            }

            var nextActive = $('#evtMtoSwitchActivo').is(':checked') ? 1 : 0;
            var keyValue = $.trim($('#evtMtoClaveNueva').val());

            if (nextActive === 0 && !currentState.has_secret && keyValue === '') {
                showAlert('danger', 'Para apagar el sistema debe configurar primero una clave secreta.');
                return null;
            }

            return {
                action: 'save_config',
                csrf_token: csrfToken,
                sistema_activo: nextActive,
                clave_nueva: keyValue,
                titulo: $('#evtMtoTitulo').val(),
                mensaje: $('#evtMtoMensaje').val()
            };
        }

        function openConfirmForSave(payload) {
            var prevActive = Number(currentState.sistema_activo) === 1 ? 1 : 0;
            var nextActive = Number(payload.sistema_activo) === 1 ? 1 : 0;
            var text = '¿Estás seguro de guardar los cambios?';
            if (prevActive !== nextActive) {
                text = nextActive === 0
                    ? '¿Estás seguro de apagar el sistema y activar mantenimiento?'
                    : '¿Estás seguro de prender el sistema y desactivar mantenimiento?';
            }
            $('#evtMtoConfirmText').text(text);
            $('#modalEvtMtoConfirm').modal('show');
        }

        function executeSave(payload) {
            setSaving(true);
            $.ajax({
                url: apiUrl,
                method: 'POST',
                dataType: 'json',
                data: payload
            }).done(function (res) {
                if (!res || !res.success) {
                    showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo guardar.');
                    return;
                }
                renderState(res.data || {});
                showAlert('success', res.msg || 'Configuración guardada.');
            }).fail(function () {
                showAlert('danger', 'Error de comunicación con el servidor.');
            }).always(function () {
                setSaving(false);
                pendingPayload = null;
            });
        }

        function requestSave() {
            var payload = buildPayload();
            if (!payload) {
                return;
            }
            pendingPayload = payload;
            openConfirmForSave(payload);
        }

        $(function () {
            loadState(false);
            $('#btnOpenMantenimiento').on('click', function () {
                loadState(true);
            });
            $('#btnGuardarMantenimiento').on('click', requestSave);
            $('#btnEvtMtoConfirmAction').on('click', function () {
                if (!pendingPayload) {
                    $('#modalEvtMtoConfirm').modal('hide');
                    return;
                }
                $('#modalEvtMtoConfirm').modal('hide');
                executeSave(pendingPayload);
            });
            $('#btnEvtMtoToggle').on('click', toggleSecretInput);
            $('#btnEvtMtoCopy').on('click', copySecretInput);
        });
    })();
</script>
</body>
</html>

