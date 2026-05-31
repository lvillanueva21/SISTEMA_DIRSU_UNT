<?php
include "../componentes/configSesion.php";

$modo = isset($_GET['modo']) ? trim((string)$_GET['modo']) : 'seguro';
$modo = ($modo === 'completo') ? 'completo' : 'seguro';

$modSolicitado = isset($_GET['mod']) ? strtolower(trim((string)$_GET['mod'])) : 'ninguno';
$modulosValidos = array('ninguno', 'panel', 'semestres', 'db', 'inicio', 'estado');
if (!in_array($modSolicitado, $modulosValidos, true)) {
    $modSolicitado = 'ninguno';
}

$activar = array(
    'panel' => ($modSolicitado === 'panel'),
    'semestres' => ($modSolicitado === 'semestres'),
    'db' => ($modSolicitado === 'db'),
    'inicio' => ($modSolicitado === 'inicio'),
    'estado' => ($modSolicitado === 'estado')
);

$evtMtoCsrf = function_exists('evt_mto_get_csrf_token')
    ? evt_mto_get_csrf_token('evt_mantenimiento_admin_csrf')
    : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Panel de eventos - Diagnóstico</title>
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <style>
        .evt-card { border: 1px solid #e9ecef; border-radius: 12px; }
        .evt-muted { color: #6c757d; font-size: .92rem; }
        .evt-chip { display: inline-block; padding: .2rem .5rem; border-radius: .5rem; font-size: .8rem; }
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
                    <div class="col-sm-8">
                        <h1>Panel de diagnóstico por módulo</h1>
                    </div>
                    <div class="col-sm-4 text-sm-right">
                        <span class="badge badge-warning">Modo: <?php echo htmlspecialchars($modo, ENT_QUOTES, 'UTF-8'); ?></span>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div class="alert alert-info">
                    Esta página no ejecuta pruebas automáticamente. Activa solo un módulo por vez usando los botones.
                    Módulo actual: <strong><?php echo htmlspecialchars($modSolicitado, ENT_QUOTES, 'UTF-8'); ?></strong>
                </div>

                <div class="card card-outline card-primary mb-3">
                    <div class="card-body py-2">
                        <div class="d-flex flex-wrap align-items-center justify-content-between">
                            <div>
                                <strong>Selecciona un módulo para aislar el bloqueo</strong>
                                <div class="evt-muted">Carga una sola variante y prueba. Si una variante da Forbidden, ahí está el sospechoso.</div>
                            </div>
                            <div class="mt-2 mt-md-0">
                                <a class="btn btn-sm btn-outline-secondary" href="eventos_panel.php?mod=ninguno">Sin módulo</a>
                                <a class="btn btn-sm btn-outline-warning" href="eventos_panel.php?mod=panel">Panel</a>
                                <a class="btn btn-sm btn-outline-success" href="eventos_panel.php?mod=semestres">Semestres</a>
                                <a class="btn btn-sm btn-outline-info" href="eventos_panel.php?mod=db">Gestor DB</a>
                                <a class="btn btn-sm btn-outline-danger" href="eventos_panel.php?mod=inicio">Fecha inicio</a>
                                <a class="btn btn-sm btn-outline-dark" href="eventos_panel.php?mod=estado">Estado</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 col-md-6 mb-3">
                        <div class="p-3 evt-card h-100">
                            <h5 class="mb-2"><i class="fas fa-tools mr-2 text-warning"></i>Prueba de panel original</h5>
                            <span class="evt-chip <?php echo $activar['panel'] ? 'bg-success' : 'bg-secondary'; ?> text-white"><?php echo $activar['panel'] ? 'Activo' : 'Desactivado'; ?></span>
                            <p class="evt-muted mt-2 mb-3">Abre la ruta original solo cuando este módulo está activo.</p>
                            <button type="button" id="btnOpenOriginalPanel" class="btn btn-warning btn-sm" <?php echo $activar['panel'] ? '' : 'disabled'; ?>>Abrir panel original</button>
                            <pre id="panelOut" class="mt-2 p-2 border rounded bg-light small mb-0" style="white-space: pre-wrap;">Sin prueba aún.</pre>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <div class="p-3 evt-card h-100">
                            <h5 class="mb-2"><i class="fas fa-calendar-check mr-2 text-success"></i>Cálculo de semestres</h5>
                            <span class="evt-chip <?php echo $activar['semestres'] ? 'bg-success' : 'bg-secondary'; ?> text-white"><?php echo $activar['semestres'] ? 'Activo' : 'Desactivado'; ?></span>
                            <div class="mb-2 mt-2">
                                <button type="button" id="btnSemStatus" class="btn btn-outline-success btn-sm" <?php echo $activar['semestres'] ? '' : 'disabled'; ?>>Ver estado</button>
                            </div>
                            <pre id="semStatusOut" class="mt-2 p-2 border rounded bg-light small mb-0" style="white-space: pre-wrap;">Sin prueba aún.</pre>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <div class="p-3 evt-card h-100">
                            <h5 class="mb-2"><i class="fas fa-database mr-2 text-info"></i>Acceso gestor DB</h5>
                            <span class="evt-chip <?php echo $activar['db'] ? 'bg-success' : 'bg-secondary'; ?> text-white"><?php echo $activar['db'] ? 'Activo' : 'Desactivado'; ?></span>
                            <div class="custom-control custom-switch mb-2 mt-2">
                                <input type="checkbox" class="custom-control-input" id="dbAccessEstado" <?php echo $activar['db'] ? '' : 'disabled'; ?>>
                                <label class="custom-control-label" for="dbAccessEstado">Habilitar acceso a consultas</label>
                            </div>
                            <button type="button" id="btnDbAccessSave" class="btn btn-info btn-sm" <?php echo $activar['db'] ? '' : 'disabled'; ?>>Guardar acceso DB</button>
                            <pre id="dbAccessOut" class="mt-2 p-2 border rounded bg-light small mb-0" style="white-space: pre-wrap;">Sin prueba aún.</pre>
                        </div>
                    </div>

                    <div class="col-12 col-md-6 mb-3">
                        <div class="p-3 evt-card h-100">
                            <h5 class="mb-2"><i class="fas fa-hourglass-half mr-2 text-danger"></i>Fecha límite en inicio</h5>
                            <span class="evt-chip <?php echo $activar['inicio'] ? 'bg-success' : 'bg-secondary'; ?> text-white"><?php echo $activar['inicio'] ? 'Activo' : 'Desactivado'; ?></span>
                            <div class="custom-control custom-switch mb-2 mt-2">
                                <input type="checkbox" class="custom-control-input" id="inicioVisible" <?php echo $activar['inicio'] ? '' : 'disabled'; ?>>
                                <label class="custom-control-label" for="inicioVisible">Mostrar bloque en inicio</label>
                            </div>
                            <div class="form-group mb-2">
                                <label class="mb-1" for="inicioTitulo">Título</label>
                                <input type="text" class="form-control form-control-sm" id="inicioTitulo" maxlength="120" <?php echo $activar['inicio'] ? '' : 'disabled'; ?>>
                            </div>
                            <div class="form-group mb-2">
                                <label class="mb-1" for="inicioMensaje">Mensaje</label>
                                <textarea class="form-control form-control-sm" id="inicioMensaje" rows="2" maxlength="300" <?php echo $activar['inicio'] ? '' : 'disabled'; ?>></textarea>
                            </div>
                            <div class="form-group mb-2">
                                <label class="mb-1" for="inicioDeadline">Fecha y hora límite</label>
                                <input type="datetime-local" class="form-control form-control-sm" id="inicioDeadline" <?php echo $activar['inicio'] ? '' : 'disabled'; ?>>
                            </div>
                            <button type="button" id="btnInicioSave" class="btn btn-danger btn-sm" <?php echo $activar['inicio'] ? '' : 'disabled'; ?>>Guardar fecha límite</button>
                            <pre id="inicioOut" class="mt-2 p-2 border rounded bg-light small mb-0" style="white-space: pre-wrap;">Sin prueba aún.</pre>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12 mb-3">
                        <div class="p-3 evt-card">
                            <h5 class="mb-2"><i class="fas fa-cogs mr-2 text-secondary"></i>Estado general</h5>
                            <span class="evt-chip <?php echo $activar['estado'] ? 'bg-success' : 'bg-secondary'; ?> text-white"><?php echo $activar['estado'] ? 'Activo' : 'Desactivado'; ?></span>
                            <div class="mt-2">
                                <button type="button" id="btnEvtState" class="btn btn-outline-secondary btn-sm" <?php echo $activar['estado'] ? '' : 'disabled'; ?>>Cargar estado general</button>
                            </div>
                            <pre id="evtStateOut" class="mt-2 p-2 border rounded bg-light small mb-0" style="white-space: pre-wrap;">Sin prueba aún.</pre>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
    </footer>
</div>

<script src="../plogins/jquery/jquery.min.js"></script>
<script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dust/js/adminlte.js"></script>
<script>
    (function () {
        var csrfToken = <?php echo json_encode($evtMtoCsrf); ?>;
        var modulo = <?php echo json_encode($modSolicitado); ?>;
        var semApiUrl = 'funciones/evt_semestres_api.php';
        var evtApiUrl = 'funciones/evt_mantenimiento_api.php';

        function printTo($target, obj) {
            $target.text(JSON.stringify(obj, null, 2));
        }

        function failTo($target, xhr) {
            var txt = 'Error HTTP ' + (xhr && xhr.status ? xhr.status : '0');
            if (xhr && xhr.responseText) {
                txt += '\n' + xhr.responseText;
            }
            $target.text(txt);
        }

        function callApi(url, action, extraData) {
            var payload = { action: action, csrf_token: csrfToken };
            if (extraData && typeof extraData === 'object') {
                Object.keys(extraData).forEach(function (k) { payload[k] = extraData[k]; });
            }
            return $.ajax({ url: url, method: 'POST', dataType: 'json', data: payload });
        }

        function toDatetimeLocal(value) {
            var raw = String(value || '').trim();
            if (raw === '') return '';
            return raw.replace(' ', 'T').slice(0, 16);
        }

        function hydrateGeneralState(resp) {
            if (!resp || !resp.success || !resp.data) return;
            var data = resp.data || {};
            var db = data.db_manager_access || {};
            var ini = data.inicio_deadline || {};
            $('#dbAccessEstado').prop('checked', Number(db.estado || 0) === 1);
            $('#inicioVisible').prop('checked', Number(ini.visible || 0) === 1);
            $('#inicioTitulo').val(ini.titulo || '');
            $('#inicioMensaje').val(ini.mensaje || '');
            $('#inicioDeadline').val(toDatetimeLocal(ini.deadline || ''));
        }

        if (modulo === 'panel') {
            $('#btnOpenOriginalPanel').on('click', function () {
                window.open('control_eventos.php', '_blank');
                $('#panelOut').text('Intento de apertura ejecutado.');
            });
        }

        if (modulo === 'semestres') {
            $('#btnSemStatus').on('click', function () {
                var $out = $('#semStatusOut');
                $out.text('Consultando estado...');
                callApi(semApiUrl, 'get_status').done(function (resp) {
                    printTo($out, resp);
                }).fail(function (xhr) {
                    failTo($out, xhr);
                });
            });
        }

        if (modulo === 'db') {
            $('#btnDbAccessSave').on('click', function () {
                var $out = $('#dbAccessOut');
                $out.text('Guardando...');
                callApi(evtApiUrl, 'save_db_manager_access', {
                    estado: $('#dbAccessEstado').is(':checked') ? 1 : 0
                }).done(function (resp) {
                    printTo($out, resp);
                }).fail(function (xhr) {
                    failTo($out, xhr);
                });
            });
        }

        if (modulo === 'inicio') {
            $('#btnInicioSave').on('click', function () {
                var $out = $('#inicioOut');
                $out.text('Guardando...');
                callApi(evtApiUrl, 'save_inicio_deadline', {
                    visible: $('#inicioVisible').is(':checked') ? 1 : 0,
                    titulo: $.trim($('#inicioTitulo').val() || ''),
                    mensaje: $.trim($('#inicioMensaje').val() || ''),
                    deadline: $.trim($('#inicioDeadline').val() || '')
                }).done(function (resp) {
                    printTo($out, resp);
                }).fail(function (xhr) {
                    failTo($out, xhr);
                });
            });
        }

        if (modulo === 'estado') {
            $('#btnEvtState').on('click', function () {
                var $out = $('#evtStateOut');
                $out.text('Consultando estado...');
                callApi(evtApiUrl, 'get_state').done(function (resp) {
                    printTo($out, resp);
                    hydrateGeneralState(resp);
                }).fail(function (xhr) {
                    failTo($out, xhr);
                });
            });
        }
    })();
</script>
</body>
</html>
