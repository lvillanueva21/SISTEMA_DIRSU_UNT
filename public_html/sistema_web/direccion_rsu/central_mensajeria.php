<?php
include "../componentes/configSesion.php";
require_once "../includes/evt_mantenimiento.php";

if (!isset($_SESSION['id_rol']) || (int)$_SESSION['id_rol'] !== 1) {
    http_response_code(403);
    echo "Acceso denegado.";
    exit;
}

$centralMsgCsrf = evt_mto_get_csrf_token('central_mensajeria_csrf');
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Central de mensajes de evaluación</title>
    <link href="../imagenes/dirsu_128_128.ico" rel="icon">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
    <link rel="stylesheet" href="https://code.ionicframework.com/ionicons/2.0.1/css/ionicons.min.css">
    <link rel="stylesheet" href="../plogins/jqvmap/jqvmap.min.css">
    <link rel="stylesheet" href="../dust/css/adminlte.min.css">
    <link rel="stylesheet" href="../plogins/overlayScrollbars/css/OverlayScrollbars.min.css">
    <style>
        .cm-card {
            border: 1px solid #e9ecef;
            border-radius: 12px;
        }
        .cm-kpi {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            padding: .75rem;
            background: #fff;
        }
        .cm-kpi .num {
            font-size: 1.15rem;
            font-weight: 700;
            line-height: 1.1;
        }
        .cm-kpi .lbl {
            font-size: .78rem;
            color: #6c757d;
        }
        .cm-table td, .cm-table th {
            font-size: .86rem;
            vertical-align: top;
        }
        .cm-badge {
            font-size: .73rem;
            border-radius: 999px;
            padding: .26rem .55rem;
            font-weight: 700;
        }
        .cm-badge-ok { background: #d4edda; color: #155724; }
        .cm-badge-warn { background: #fff3cd; color: #856404; }
        .cm-badge-err { background: #f8d7da; color: #721c24; }
        .cm-mono {
            font-family: Consolas, "Courier New", monospace;
            font-size: .8rem;
        }
        .cm-pre {
            white-space: pre-wrap;
            word-break: break-word;
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: .65rem;
            max-height: 260px;
            overflow: auto;
            font-size: .82rem;
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
                    <div class="col-sm-8">
                        <h1>Central de mensajes de evaluación</h1>
                    </div>
                    <div class="col-sm-4 text-sm-right">
                        <button id="btnReloadAll" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-sync-alt"></i> Actualizar
                        </button>
                    </div>
                </div>
            </div>
        </section>

        <section class="content">
            <div class="container-fluid">
                <div id="cmAlert" class="alert d-none"></div>

                <div class="row mb-3" id="cmKpiRow">
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiTotal">0</div><div class="lbl">Total</div></div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiEnviado">0</div><div class="lbl">Enviado</div></div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiNoEnviado">0</div><div class="lbl">No enviado</div></div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiError">0</div><div class="lbl">Error</div></div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiHoy">0</div><div class="lbl">Hoy</div></div>
                    </div>
                    <div class="col-6 col-md-3 col-lg-2 mb-2">
                        <div class="cm-kpi"><div class="num" id="kpiEntrega">0%</div><div class="lbl">Tasa envío</div></div>
                    </div>
                </div>

                <div class="card cm-card">
                    <div class="card-header p-0 pt-1">
                        <ul class="nav nav-tabs" id="cmTabs" role="tablist">
                            <li class="nav-item">
                                <a class="nav-link active" id="tab-outbox" data-toggle="pill" href="#pane-outbox" role="tab">Bandeja outbox</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-eventos" data-toggle="pill" href="#pane-eventos" role="tab">Auditoría de eventos</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" id="tab-timeline" data-toggle="pill" href="#pane-timeline" role="tab">Timeline por respuesta</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="pane-outbox" role="tabpanel">
                                <div class="row">
                                    <div class="col-12 col-lg-2 mb-2"><input id="fDesde" type="date" class="form-control form-control-sm" placeholder="Desde"></div>
                                    <div class="col-12 col-lg-2 mb-2"><input id="fHasta" type="date" class="form-control form-control-sm" placeholder="Hasta"></div>
                                    <div class="col-12 col-lg-2 mb-2">
                                        <select id="fEstado" class="form-control form-control-sm">
                                            <option value="">Estado (todos)</option>
                                            <option value="enviado">Enviado</option>
                                            <option value="no_enviado">No enviado</option>
                                            <option value="error">Error</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-2 mb-2">
                                        <select id="fEvento" class="form-control form-control-sm">
                                            <option value="">Evento (todos)</option>
                                            <option value="MAIL_DERIVACION">MAIL_DERIVACION</option>
                                            <option value="MAIL_DERIVACION_OFICINA">MAIL_DERIVACION_OFICINA</option>
                                            <option value="MAIL_OBSERVACION">MAIL_OBSERVACION</option>
                                            <option value="MAIL_APROB_TOTAL">MAIL_APROB_TOTAL</option>
                                            <option value="MAIL_SOLICITUD_REVISION">MAIL_SOLICITUD_REVISION</option>
                                            <option value="MAIL_SUBSANACION">MAIL_SUBSANACION</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-2 mb-2">
                                        <select id="fTipoInforme" class="form-control form-control-sm">
                                            <option value="">Tipo informe (todos)</option>
                                            <option value="semestral">Informe semestral</option>
                                            <option value="final">Informe final</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-lg-2 mb-2"><input id="fIdRespuesta" type="number" class="form-control form-control-sm" placeholder="ID respuesta"></div>
                                </div>
                                <div class="row">
                                    <div class="col-12 col-lg-3 mb-2"><input id="fOficina" type="number" class="form-control form-control-sm" placeholder="ID oficina"></div>
                                    <div class="col-12 col-lg-3 mb-2"><input id="fMotivo" type="text" class="form-control form-control-sm" placeholder="Motivo/no enviado motivo"></div>
                                    <div class="col-12 col-lg-4 mb-2"><input id="fTexto" type="text" class="form-control form-control-sm" placeholder="Texto libre (asunto, destinatarios, error, proyecto)"></div>
                                    <div class="col-12 col-lg-2 mb-2 text-right">
                                        <button id="btnFiltrarOutbox" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnLimpiarOutbox" class="btn btn-secondary btn-sm">Limpiar</button>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover cm-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>Estado</th>
                                                <th>Evento</th>
                                                <th>Tipo informe</th>
                                                <th>Oficina</th>
                                                <th>ID respuesta</th>
                                                <th>Proyecto</th>
                                                <th>Asunto</th>
                                                <th>Destinatarios</th>
                                                <th>Motivo</th>
                                                <th>Acción</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbOutbox"></tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small id="outboxInfo" class="text-muted">-</small>
                                    <div>
                                        <button id="outboxPrev" class="btn btn-outline-secondary btn-sm">Anterior</button>
                                        <button id="outboxNext" class="btn btn-outline-secondary btn-sm">Siguiente</button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pane-eventos" role="tabpanel">
                                <div class="row">
                                    <div class="col-12 col-lg-2 mb-2"><input id="eDesde" type="date" class="form-control form-control-sm"></div>
                                    <div class="col-12 col-lg-2 mb-2"><input id="eHasta" type="date" class="form-control form-control-sm"></div>
                                    <div class="col-12 col-lg-3 mb-2"><input id="eEventCode" type="text" class="form-control form-control-sm" placeholder="event_code"></div>
                                    <div class="col-12 col-lg-2 mb-2"><input id="eIdRespuesta" type="number" class="form-control form-control-sm" placeholder="ID respuesta"></div>
                                    <div class="col-12 col-lg-3 mb-2 text-right">
                                        <button id="btnFiltrarEventos" class="btn btn-primary btn-sm">Filtrar</button>
                                        <button id="btnLimpiarEventos" class="btn btn-secondary btn-sm">Limpiar</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover cm-table">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Fecha</th>
                                                <th>event_code</th>
                                                <th>Oficina</th>
                                                <th>ID respuesta</th>
                                                <th>Usuario</th>
                                                <th>IP</th>
                                                <th>Detalle</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbEventos"></tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-between align-items-center">
                                    <small id="eventosInfo" class="text-muted">-</small>
                                    <div>
                                        <button id="eventosPrev" class="btn btn-outline-secondary btn-sm">Anterior</button>
                                        <button id="eventosNext" class="btn btn-outline-secondary btn-sm">Siguiente</button>
                                    </div>
                                </div>
                            </div>

                            <div class="tab-pane fade" id="pane-timeline" role="tabpanel">
                                <div class="row">
                                    <div class="col-12 col-lg-3 mb-2">
                                        <input id="tIdRespuesta" type="number" class="form-control form-control-sm" placeholder="ID respuesta (obligatorio)">
                                    </div>
                                    <div class="col-12 col-lg-9 mb-2 text-right">
                                        <button id="btnCargarTimeline" class="btn btn-primary btn-sm">Cargar timeline</button>
                                        <button id="btnLimpiarTimeline" class="btn btn-secondary btn-sm">Limpiar</button>
                                    </div>
                                </div>
                                <div class="table-responsive">
                                    <table class="table table-bordered table-hover cm-table">
                                        <thead>
                                            <tr>
                                                <th>Fecha</th>
                                                <th>Origen</th>
                                                <th>Código</th>
                                                <th>Estado/Detalle</th>
                                                <th>Referencia</th>
                                            </tr>
                                        </thead>
                                        <tbody id="tbTimeline"></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    </div>

    <footer class="main-footer">
        <strong>&copy; 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
        <div class="float-right d-none d-sm-inline-block">
            <p>Desarrollado por el <a href="#">Area informatica - DIRSU</a></p>
        </div>
    </footer>
</div>

<div class="modal fade" id="modalDetalleOutbox" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable" role="document">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">Detalle de mensaje</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="row mb-2">
                    <div class="col-md-4"><strong>ID:</strong> <span id="dId"></span></div>
                    <div class="col-md-4"><strong>Estado:</strong> <span id="dEstado"></span></div>
                    <div class="col-md-4"><strong>Evento:</strong> <span id="dEvento"></span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12"><strong>Asunto:</strong> <span id="dAsunto"></span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-12"><strong>Destinatarios:</strong> <span id="dDestinatarios" class="cm-mono"></span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Motivo:</strong> <span id="dMotivo"></span></div>
                    <div class="col-md-4"><strong>No enviado motivo:</strong> <span id="dNoEnv"></span></div>
                    <div class="col-md-4"><strong>Error:</strong> <span id="dError"></span></div>
                </div>
                <div class="row mb-2">
                    <div class="col-md-4"><strong>Creado por:</strong> <span id="dBy"></span></div>
                    <div class="col-md-4"><strong>IP:</strong> <span id="dIp"></span></div>
                    <div class="col-md-4"><strong>Enviado en:</strong> <span id="dEnviadoEn"></span></div>
                </div>
                <hr>
                <p class="mb-1"><strong>Cuerpo texto</strong></p>
                <div id="dTexto" class="cm-pre"></div>
                <p class="mt-3 mb-1"><strong>Cuerpo HTML</strong></p>
                <div id="dHtml" class="cm-pre"></div>
            </div>
            <div class="modal-footer">
                <button class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<script src="../plogins/jquery/jquery.min.js"></script>
<script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
<script src="../dust/js/adminlte.min.js"></script>
<script>
(function () {
    var apiUrl = 'funciones/central_mensajeria_api.php';
    var csrfToken = <?php echo json_encode($centralMsgCsrf); ?>;
    var outboxPage = 1;
    var eventosPage = 1;
    var perPage = 10;

    function esc(v) {
        return String(v == null ? '' : v)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }

    function showAlert(type, msg) {
        $('#cmAlert').removeClass('d-none alert-success alert-danger alert-warning alert-info')
            .addClass('alert-' + type).text(msg || '');
    }

    function clearAlert() {
        $('#cmAlert').addClass('d-none').removeClass('alert-success alert-danger alert-warning alert-info').text('');
    }

    function badgeEstado(estado) {
        if (estado === 'enviado') return '<span class="cm-badge cm-badge-ok">enviado</span>';
        if (estado === 'error') return '<span class="cm-badge cm-badge-err">error</span>';
        return '<span class="cm-badge cm-badge-warn">no_enviado</span>';
    }

    function outboxFilters() {
        return {
            desde: $('#fDesde').val(),
            hasta: $('#fHasta').val(),
            estado: $('#fEstado').val(),
            event_code: $('#fEvento').val(),
            tipo_informe: $('#fTipoInforme').val(),
            id_respuesta: $('#fIdRespuesta').val(),
            office: $('#fOficina').val(),
            motivo: $('#fMotivo').val(),
            q: $('#fTexto').val()
        };
    }

    function loadKpis() {
        $.post(apiUrl, { action: 'kpis', csrf_token: csrfToken }, function (res) {
            if (!res || !res.success) return;
            var d = res.data || {};
            $('#kpiTotal').text(d.total || 0);
            $('#kpiEnviado').text(d.enviado || 0);
            $('#kpiNoEnviado').text(d.no_enviado || 0);
            $('#kpiError').text(d.error || 0);
            $('#kpiHoy').text(d.hoy || 0);
            $('#kpiEntrega').text((d.tasa_envio || 0) + '%');
        }, 'json');
    }

    function loadOutbox() {
        clearAlert();
        var req = outboxFilters();
        req.action = 'list_outbox';
        req.csrf_token = csrfToken;
        req.page = outboxPage;
        req.per_page = perPage;
        $.post(apiUrl, req, function (res) {
            if (!res || !res.success) {
                showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo cargar outbox.');
                return;
            }
            var d = res.data || {};
            var rows = d.rows || [];
            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="12" class="text-center text-muted">Sin resultados.</td></tr>';
            } else {
                rows.forEach(function (r) {
                    html += '<tr>' +
                        '<td>' + esc(r.id) + '</td>' +
                        '<td>' + esc(r.created_at) + '</td>' +
                        '<td>' + badgeEstado(r.estado) + '</td>' +
                        '<td><span class="cm-mono">' + esc(r.event_code) + '</span></td>' +
                        '<td>' + esc(r.tipo_informe_label || 'Sin determinar') + '</td>' +
                        '<td>' + esc(r.oficina_label || '-') + '</td>' +
                        '<td>' + esc(r.id_respuesta) + '</td>' +
                        '<td>' + esc(r.proyecto_titulo || '-') + '</td>' +
                        '<td>' + esc(r.asunto || '-') + '</td>' +
                        '<td class="cm-mono">' + esc(r.destinatarios || '-') + '</td>' +
                        '<td>' + esc(r.motivo || r.no_enviado_motivo || '-') + '</td>' +
                        '<td>' +
                            '<button class="btn btn-sm btn-outline-primary btn-det mr-1" data-id="' + esc(r.id) + '">Ver</button>' +
                            '<button class="btn btn-sm btn-outline-success btn-reenviar" data-id="' + esc(r.id) + '">Reenviar</button>' +
                        '</td>' +
                    '</tr>';
                });
            }
            $('#tbOutbox').html(html);
            $('#outboxInfo').text('Página ' + (d.page || 1) + ' de ' + (d.total_pages || 1) + ' — Total: ' + (d.total || 0));
            $('#outboxPrev').prop('disabled', (d.page || 1) <= 1);
            $('#outboxNext').prop('disabled', (d.page || 1) >= (d.total_pages || 1));
        }, 'json').fail(function () {
            showAlert('danger', 'Error de comunicación al cargar outbox.');
        });
    }

    function loadDetalleOutbox(id) {
        $.post(apiUrl, { action: 'get_outbox_detail', id: id, csrf_token: csrfToken }, function (res) {
            if (!res || !res.success) {
                showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo cargar detalle.');
                return;
            }
            var r = res.data || {};
            $('#dId').text(r.id || '');
            $('#dEstado').text(r.estado || '');
            $('#dEvento').text(r.event_code || '');
            $('#dAsunto').text(r.asunto || '');
            $('#dDestinatarios').text(r.destinatarios || '');
            $('#dMotivo').text(r.motivo || '');
            $('#dNoEnv').text(r.no_enviado_motivo || '');
            $('#dError').text(r.error_detalle || '');
            $('#dBy').text(r.created_by || '-');
            $('#dIp').text(r.ip || '-');
            $('#dEnviadoEn').text(r.enviado_en || '-');
            $('#dTexto').text(r.cuerpo_texto || '');
            $('#dHtml').text(r.cuerpo_html || '');
            $('#modalDetalleOutbox').modal('show');
        }, 'json');
    }

    function reenviarOutbox(id) {
        if (!id) return;
        if (!confirm('Se reenviará este correo y se registrará un nuevo evento en outbox. ¿Deseas continuar?')) {
            return;
        }
        clearAlert();
        $.post(apiUrl, { action: 'reenviar_outbox', id: id, csrf_token: csrfToken }, function (res) {
            if (!res || !res.success) {
                showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo reenviar el correo.');
                return;
            }
            showAlert('success', (res.msg || 'Reenvío ejecutado correctamente.') + (res.data && res.data.nuevo_outbox_id ? (' Nuevo ID outbox: ' + res.data.nuevo_outbox_id) : ''));
            loadKpis();
            loadOutbox();
            loadEventos();
        }, 'json').fail(function (xhr) {
            var msg = 'Error de comunicación al reenviar correo.';
            if (xhr && xhr.responseJSON && xhr.responseJSON.msg) {
                msg = xhr.responseJSON.msg;
            }
            showAlert('danger', msg);
        });
    }

    function eventosFilters() {
        return {
            desde: $('#eDesde').val(),
            hasta: $('#eHasta').val(),
            event_code: $('#eEventCode').val(),
            id_respuesta: $('#eIdRespuesta').val()
        };
    }

    function loadEventos() {
        clearAlert();
        var req = eventosFilters();
        req.action = 'list_eventos';
        req.csrf_token = csrfToken;
        req.page = eventosPage;
        req.per_page = perPage;
        $.post(apiUrl, req, function (res) {
            if (!res || !res.success) {
                showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo cargar eventos.');
                return;
            }
            var d = res.data || {};
            var rows = d.rows || [];
            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="8" class="text-center text-muted">Sin resultados.</td></tr>';
            } else {
                rows.forEach(function (r) {
                    html += '<tr>' +
                        '<td>' + esc(r.id) + '</td>' +
                        '<td>' + esc(r.created_at) + '</td>' +
                        '<td><span class="cm-mono">' + esc(r.event_code) + '</span></td>' +
                        '<td>' + esc(r.office || '-') + '</td>' +
                        '<td>' + esc(r.id_respuesta) + '</td>' +
                        '<td>' + esc(r.created_by || '-') + '</td>' +
                        '<td class="cm-mono">' + esc(r.ip || '-') + '</td>' +
                        '<td><div class="cm-pre" style="max-height:120px;">' + esc(r.detalle || '') + '</div></td>' +
                    '</tr>';
                });
            }
            $('#tbEventos').html(html);
            $('#eventosInfo').text('Página ' + (d.page || 1) + ' de ' + (d.total_pages || 1) + ' — Total: ' + (d.total || 0));
            $('#eventosPrev').prop('disabled', (d.page || 1) <= 1);
            $('#eventosNext').prop('disabled', (d.page || 1) >= (d.total_pages || 1));
        }, 'json').fail(function () {
            showAlert('danger', 'Error de comunicación al cargar eventos.');
        });
    }

    function loadTimeline() {
        clearAlert();
        var idr = $('#tIdRespuesta').val();
        if (!idr) {
            showAlert('warning', 'Ingresa un ID de respuesta para cargar el timeline.');
            return;
        }
        $.post(apiUrl, { action: 'timeline_respuesta', csrf_token: csrfToken, id_respuesta: idr }, function (res) {
            if (!res || !res.success) {
                showAlert('danger', (res && res.msg) ? res.msg : 'No se pudo cargar timeline.');
                return;
            }
            var rows = (res.data && res.data.rows) ? res.data.rows : [];
            var html = '';
            if (!rows.length) {
                html = '<tr><td colspan="5" class="text-center text-muted">Sin movimientos para esa respuesta.</td></tr>';
            } else {
                rows.forEach(function (r) {
                    html += '<tr>' +
                        '<td>' + esc(r.fecha) + '</td>' +
                        '<td>' + esc(r.origen) + '</td>' +
                        '<td><span class="cm-mono">' + esc(r.codigo) + '</span></td>' +
                        '<td>' + esc(r.estado_detalle || '-') + '</td>' +
                        '<td>' + esc(r.referencia || '-') + '</td>' +
                    '</tr>';
                });
            }
            $('#tbTimeline').html(html);
        }, 'json').fail(function () {
            showAlert('danger', 'Error de comunicación al cargar timeline.');
        });
    }

    $(function () {
        loadKpis();
        loadOutbox();
        loadEventos();

        $('#btnReloadAll').on('click', function () {
            loadKpis();
            loadOutbox();
            loadEventos();
        });

        $('#btnFiltrarOutbox').on('click', function () { outboxPage = 1; loadOutbox(); });
        $('#btnLimpiarOutbox').on('click', function () {
            $('#fDesde,#fHasta,#fEstado,#fEvento,#fTipoInforme,#fIdRespuesta,#fOficina,#fMotivo,#fTexto').val('');
            outboxPage = 1;
            loadOutbox();
        });
        $('#outboxPrev').on('click', function () { if (outboxPage > 1) { outboxPage--; loadOutbox(); } });
        $('#outboxNext').on('click', function () { outboxPage++; loadOutbox(); });

        $('#btnFiltrarEventos').on('click', function () { eventosPage = 1; loadEventos(); });
        $('#btnLimpiarEventos').on('click', function () {
            $('#eDesde,#eHasta,#eEventCode,#eIdRespuesta').val('');
            eventosPage = 1;
            loadEventos();
        });
        $('#eventosPrev').on('click', function () { if (eventosPage > 1) { eventosPage--; loadEventos(); } });
        $('#eventosNext').on('click', function () { eventosPage++; loadEventos(); });

        $('#btnCargarTimeline').on('click', loadTimeline);
        $('#btnLimpiarTimeline').on('click', function () {
            $('#tIdRespuesta').val('');
            $('#tbTimeline').html('');
            clearAlert();
        });

        $(document).on('click', '.btn-det', function () {
            var id = $(this).data('id');
            loadDetalleOutbox(id);
        });

        $(document).on('click', '.btn-reenviar', function () {
            var id = $(this).data('id');
            reenviarOutbox(id);
        });
    });
})();
</script>
</body>
</html>
