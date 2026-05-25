<?php
$crearMsg = isset($_SESSION['crear_proyecto_msg']) ? trim((string)$_SESSION['crear_proyecto_msg']) : '';
$crearMsgType = isset($_SESSION['crear_proyecto_msg_type']) ? trim((string)$_SESSION['crear_proyecto_msg_type']) : 'info';
if ($crearMsgType === '') {
    $crearMsgType = 'info';
}

$prefillEmail = '';
$prefillTel = '';
$foundContact = false;
$usuarioSesion = isset($_SESSION['usuario']) ? trim((string)$_SESSION['usuario']) : '';
$nombresSesion = isset($_SESSION['nombres']) ? trim((string)$_SESSION['nombres']) : '';
$apellidosSesion = isset($_SESSION['apellidos']) ? trim((string)$_SESSION['apellidos']) : '';
$nombreCompromiso = trim($nombresSesion . ' ' . $apellidosSesion);
if ($nombreCompromiso === '') {
    $nombreCompromiso = $usuarioSesion;
}

$periodoCronograma = '';
if (isset($conexion) && $conexion instanceof mysqli) {
    $chk = $conexion->query("SHOW TABLES LIKE 'usuario_contactos'");
    if ($usuarioSesion !== '' && $chk instanceof mysqli_result && $chk->num_rows > 0) {
        $st = $conexion->prepare("SELECT email, telefono FROM usuario_contactos WHERE usuario = ? LIMIT 1");
        if ($st) {
            $st->bind_param('s', $usuarioSesion);
            $st->execute();
            $res = $st->get_result()->fetch_assoc();
            if ($res) {
                $prefillEmail = (string)($res['email'] ?? '');
                $prefillTel = (string)($res['telefono'] ?? '');
                $foundContact = ($prefillEmail !== '' || $prefillTel !== '');
            }
            $st->close();
        }
    }

    $stPer = $conexion->prepare("
      SELECT p.nombre
      FROM sm_cronogramas c
      INNER JOIN periodos p ON p.id = c.id_periodo
      WHERE c.activo = 1
        AND c.tipo = 1
        AND p.activo = 1
      ORDER BY c.apertura DESC, c.id DESC
      LIMIT 1
    ");
    if ($stPer) {
        $stPer->execute();
        $stPer->bind_result($periodoTmp);
        if ($stPer->fetch()) {
            $periodoCronograma = trim((string)$periodoTmp);
        }
        $stPer->close();
    }
}

if ($periodoCronograma === '') {
    $periodoCronograma = '-';
}
?>

<?php if ($crearMsg !== ''): ?>
    <div class="alert alert-<?php echo htmlspecialchars($crearMsgType, ENT_QUOTES, 'UTF-8'); ?> mb-3" role="alert">
        <?php echo htmlspecialchars($crearMsg, ENT_QUOTES, 'UTF-8'); ?>
    </div>
<?php endif; ?>
<?php
unset($_SESSION['crear_proyecto_msg'], $_SESSION['crear_proyecto_msg_type']);
?>

<style>
  .rp-sem-grid { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .6rem; }
  .rp-sem-item { border: 1px solid #d1d5db; border-radius: .5rem; padding: .4rem .55rem; background: #f9fafb; min-width: 140px; }
  .rp-sem-periodo { font-weight: 700; font-size: .84rem; color: #111827; }
  .rp-sem-task { font-size: .78rem; color: #334155; margin-top: .2rem; line-height: 1.25; }
  .rp-sem-state { font-size: .82rem; font-weight: 600; margin-top: .6rem; }
  .rp-sem-state.ok { color: #166534; }
  .rp-sem-state.err { color: #b91c1c; }
  .rp-commit-list { max-height: 240px; overflow: auto; border: 1px solid #e5e7eb; border-radius: .5rem; padding: .5rem .65rem; background: #f8fafc; }
</style>

<div class="card card-solid">
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-sm-6">
                <div class="col-12">
                    <img src="../imagenes/registrar_proyecto.jpg" class="product-image" alt="Registro de proyecto">
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <div class="text-center text-sm-left my-4">
                    <h3 class="mb-3">Es hora de comenzar tu primer proyecto</h3>
                    <p class="mb-4">No encontramos proyectos vinculados a tu cuenta. Presiona "Registrar nuevo proyecto" para iniciar la formulación.</p>
                </div>

                <div class="d-flex justify-content-center justify-content-sm-start mt-4">
                    <div class="mr-2">
                        <button type="button" class="btn btn-primary btn-lg btn-flat" data-toggle="modal" data-target="#crearProyectoModal">
                            <i class="fas fa-folder-plus fa-lg mr-2"></i>
                            Registrar nuevo proyecto
                        </button>
                    </div>

                    <div>
                        <button type="button" class="btn btn-default btn-lg btn-flat" data-toggle="modal" data-target="#infoRegistroModal">
                            <i class="fas fa-info-circle fa-lg mr-2"></i>
                            Más información
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="infoRegistroModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary py-2">
                <h5 class="modal-title mb-0">Importante</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <ul class="mb-0">
                    <li>Al registrar el proyecto podrás completar: <b>Generalidades</b>, <b>Plan de proyecto</b> y <b>Anexos</b>.</li>
                    <li>Podrás actualizar la información del proyecto mientras la interfaz esté habilitada.</li>
                    <li>Cuando termines la carga, deberás solicitar revisión desde el flujo correspondiente.</li>
                    <li>La fecha límite de presentación depende del cronograma vigente configurado por DIRSU.</li>
                </ul>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="crearProyectoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Registro inicial de proyecto</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formCrearProyecto" method="post" action="../componentes/proyecto/crear_proyecto.php" novalidate>
                <div class="modal-body">
                    <p class="mb-2">
                        Registra tus datos de contacto y los datos iniciales del proyecto para empezar la formulación.
                    </p>

                    <input type="hidden" name="crear_proyecto" value="1">
                    <input type="hidden" name="acepto_compromiso" id="acepto_compromiso" value="0">

                    <div class="form-group mb-2">
                        <label for="contact_email_project" class="mb-0">Correo institucional (@unitru.edu.pe)</label>
                        <input
                            type="email"
                            class="form-control"
                            id="contact_email_project"
                            name="email"
                            value="<?php echo htmlspecialchars($prefillEmail, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="tucuenta@unitru.edu.pe"
                            required
                            autocomplete="email"
                            inputmode="email">
                        <?php if ($foundContact): ?>
                            <small class="text-info">Se encontró contacto previo, ¿actualizar?</small>
                        <?php endif; ?>
                        <div class="invalid-feedback">
                            Ingresa un correo válido con dominio @unitru.edu.pe.
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="contact_tel_project" class="mb-0">Teléfono (9 dígitos, inicia con 9)</label>
                        <input
                            type="tel"
                            class="form-control"
                            id="contact_tel_project"
                            name="telefono"
                            value="<?php echo htmlspecialchars($prefillTel, ENT_QUOTES, 'UTF-8'); ?>"
                            placeholder="9XXXXXXXX"
                            required
                            minlength="9"
                            maxlength="9"
                            pattern="^9\d{8}$"
                            inputmode="numeric">
                        <div class="invalid-feedback">
                            El teléfono debe tener 9 dígitos y empezar con 9.
                        </div>
                    </div>

                    <div class="form-group mb-2">
                        <label for="p2_project" class="mb-0">2. Título del Proyecto</label>
                        <input
                            type="text"
                            class="form-control"
                            id="p2_project"
                            name="p2"
                            maxlength="300"
                            required>
                        <small class="text-muted">Puedes cambiar el título más adelante.</small>
                        <div class="invalid-feedback">El título del proyecto es obligatorio.</div>
                    </div>

                    <div class="form-group mb-0">
                        <label class="mb-1">9. Duración del proyecto (de 2 a 5 años)</label>
                        <div class="row">
                            <div class="col-md-6 mb-2">
                                <label for="fecha_inicio_project" class="mb-0 small">Fecha de inicio</label>
                                <input type="date" class="form-control" id="fecha_inicio_project" name="fecha_inicio" required>
                            </div>
                            <div class="col-md-6 mb-2">
                                <label for="fecha_fin_project" class="mb-0 small">Fecha de fin</label>
                                <input type="date" class="form-control" id="fecha_fin_project" name="fecha_fin" required>
                            </div>
                        </div>
                        <small class="text-muted">Se puede solicitar un cambio de fechas del proyecto previa coordinación con la Dirección de RSU: proyectosdirsu@unitru.edu.pe.</small>
                    </div>

                    <div id="rpSemState" class="rp-sem-state mt-2"></div>
                    <div id="rpSemResumen" class="small text-muted mt-1"></div>
                    <div id="rpSemGrid" class="rp-sem-grid"></div>
                </div>

                <div class="modal-footer py-2">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                    <button type="button" class="btn btn-primary btn-sm" id="btnOpenCompromiso">Guardar y revisar compromiso</button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="modal fade" id="compromisoProyectoModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Confirmación de compromiso</h6>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <p id="rpCompromisoTexto" class="mb-2"></p>
                <div class="rp-commit-list">
                    <ul id="rpCompromisoLista" class="mb-0"></ul>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-success btn-sm" id="btnAceptarCompromiso">Acepto y me comprometo a cumplir con las fechas de entrega</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('formCrearProyecto');
    var email = document.getElementById('contact_email_project');
    var tel = document.getElementById('contact_tel_project');
    var inTitle = document.getElementById('p2_project');
    var inStart = document.getElementById('fecha_inicio_project');
    var inEnd = document.getElementById('fecha_fin_project');
    var semState = document.getElementById('rpSemState');
    var semResumen = document.getElementById('rpSemResumen');
    var semGrid = document.getElementById('rpSemGrid');
    var btnOpenCompromiso = document.getElementById('btnOpenCompromiso');
    var btnAceptarCompromiso = document.getElementById('btnAceptarCompromiso');
    var inAcepto = document.getElementById('acepto_compromiso');
    var compromisoTexto = document.getElementById('rpCompromisoTexto');
    var compromisoLista = document.getElementById('rpCompromisoLista');

    if (!form || !email || !tel || !inTitle || !inStart || !inEnd || !semState || !semResumen || !semGrid || !btnOpenCompromiso || !btnAceptarCompromiso || !inAcepto || !compromisoTexto || !compromisoLista) {
        return;
    }

    var periodoCronograma = <?php echo json_encode($periodoCronograma); ?>;
    var nombreCompromiso = <?php echo json_encode($nombreCompromiso); ?>;
    var apiPreviewUrl = '../includes/api_dirsu/api.php?action=project.semesters.preview';
    var lastRows = [];
    var totalSemestres = 0;
    var rangeOk = false;
    var lastRequestToken = 0;

    function toText(v) {
        return String(v == null ? '' : v).trim();
    }

    function resetCommitment() {
        inAcepto.value = '0';
    }

    function sanitizeTel() {
        tel.value = toText(tel.value).replace(/\D/g, '').slice(0, 9);
    }

    function validateEmailDomain() {
        var v = toText(email.value).toLowerCase();
        email.value = v;
        if (v === '' || !v.endsWith('@unitru.edu.pe')) {
            email.setCustomValidity('Dominio no permitido');
        } else {
            email.setCustomValidity('');
        }
    }

    function isValidRange(s, e) {
        if (!s || !e) return { ok: false, msg: 'Selecciona fecha de inicio y fecha de fin.' };
        if (s === e) return { ok: false, msg: 'Las fechas no pueden ser iguales.' };
        if (e < s) return { ok: false, msg: 'La fecha de fin debe ser posterior a la fecha de inicio.' };
        return { ok: true, msg: '' };
    }

    function setRangeState(msg, ok) {
        semState.className = 'rp-sem-state mt-2 ' + (ok ? 'ok' : 'err');
        semState.textContent = msg;
        rangeOk = !!ok;
    }

    function escapeHtml(v) {
        return String(v).replace(/[&<>"']/g, function (ch) {
            return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[ch];
        });
    }

    function normalizeSemesters(rows) {
        var map = {};
        for (var i = 0; i < rows.length; i++) {
            var r = rows[i] || {};
            var anio = Number(r.anio || 0);
            var periodo = toText(r.periodo).toUpperCase();
            if (!anio || (periodo !== 'I' && periodo !== 'II')) continue;
            var key = String(anio) + '-' + periodo;
            if (!map[key]) {
                map[key] = { periodo: key, hasPresentacion: false, hasFinal: false };
            }
            if (toText(r.tipo) === 'presentacion') {
                map[key].hasPresentacion = true;
            }
            if (toText(r.tipo) === 'semestral' && Number(r.final || 0) === 1) {
                map[key].hasFinal = true;
            }
        }

        var list = [];
        for (var k in map) {
            if (Object.prototype.hasOwnProperty.call(map, k)) {
                list.push(map[k]);
            }
        }
        list.sort(function (a, b) {
            return a.periodo < b.periodo ? -1 : (a.periodo > b.periodo ? 1 : 0);
        });
        return list;
    }

    function deliverableFor(index, total, sem) {
        var first = index === 0;
        var last = index === total - 1;
        if (first && last) {
            return 'Presentación de proyecto (Generalidades, Plan de proyecto y Anexos) + Informe final';
        }
        if (first) {
            return 'Presentación de proyecto (Generalidades, Plan de proyecto y Anexos) + Informe semestral';
        }
        if (last || sem.hasFinal) {
            return 'Informe final';
        }
        return 'Informe semestral';
    }

    function renderPreview(semesters) {
        semGrid.innerHTML = '';
        for (var i = 0; i < semesters.length; i++) {
            var sem = semesters[i];
            var task = deliverableFor(i, semesters.length, sem);
            var box = document.createElement('div');
            box.className = 'rp-sem-item';
            box.innerHTML = '<div class="rp-sem-periodo">' + escapeHtml(sem.periodo) + '</div><div class="rp-sem-task">' + escapeHtml(task) + '</div>';
            semGrid.appendChild(box);
        }
    }

    function previewSemesters() {
        var s = toText(inStart.value);
        var e = toText(inEnd.value);
        var range = isValidRange(s, e);

        resetCommitment();
        if (!range.ok) {
            setRangeState(range.msg, false);
            semResumen.textContent = '';
            semGrid.innerHTML = '';
            lastRows = [];
            totalSemestres = 0;
            return;
        }

        var token = ++lastRequestToken;
        semResumen.textContent = 'Calculando semestres...';
        fetch(apiPreviewUrl + '&fecha_inicio=' + encodeURIComponent(s) + '&fecha_fin=' + encodeURIComponent(e), { credentials: 'same-origin' })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (token !== lastRequestToken) return;

                if (!json || json.ok !== true || !json.data || !Array.isArray(json.data.rows)) {
                    setRangeState('No se pudo calcular los semestres para este rango.', false);
                    semResumen.textContent = '';
                    semGrid.innerHTML = '';
                    lastRows = [];
                    totalSemestres = 0;
                    return;
                }

                var rows = json.data.rows;
                var semesters = normalizeSemesters(rows);
                totalSemestres = semesters.length;
                lastRows = semesters;
                renderPreview(semesters);

                semResumen.textContent = 'Semestres tentativos del proyecto: ' + totalSemestres + '.';
                if (totalSemestres < 4) {
                    setRangeState('Rango no válido: el proyecto debe tener al menos 4 semestres (2 años).', false);
                } else if (totalSemestres > 10) {
                    setRangeState('Rango no válido: el proyecto no puede superar 10 semestres (5 años).', false);
                } else {
                    setRangeState('Rango válido: puedes continuar con este proyecto.', true);
                }
            })
            .catch(function () {
                if (token !== lastRequestToken) return;
                setRangeState('No se pudo calcular los semestres para este rango.', false);
                semResumen.textContent = '';
                semGrid.innerHTML = '';
                lastRows = [];
                totalSemestres = 0;
            });
    }

    function validateForm() {
        sanitizeTel();
        validateEmailDomain();

        var title = toText(inTitle.value);
        if (title === '') {
            inTitle.setCustomValidity('Título obligatorio');
        } else {
            inTitle.setCustomValidity('');
        }

        var telOk = /^9\d{8}$/.test(tel.value);
        tel.setCustomValidity(telOk ? '' : 'Teléfono inválido');

        var s = toText(inStart.value);
        var e = toText(inEnd.value);
        var range = isValidRange(s, e);
        if (!range.ok || !rangeOk) {
            setRangeState(range.ok ? 'Selecciona un rango válido entre 4 y 10 semestres.' : range.msg, false);
        }

        form.classList.add('was-validated');
        return form.checkValidity() && rangeOk;
    }

    function formatLimaNow() {
        try {
            return new Intl.DateTimeFormat('es-PE', {
                timeZone: 'America/Lima',
                year: 'numeric',
                month: '2-digit',
                day: '2-digit',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit',
                hour12: false
            }).format(new Date());
        } catch (e) {
            return new Date().toLocaleString('es-PE');
        }
    }

    function buildCompromiso() {
        var titulo = toText(inTitle.value);
        var fIni = toText(inStart.value);
        var fFin = toText(inEnd.value);
        var fechaAct = formatLimaNow();
        var nombre = toText(nombreCompromiso);
        if (nombre === '') nombre = toText(<?php echo json_encode($usuarioSesion); ?>);

        compromisoTexto.textContent =
            'Yo ' + nombre +
            ' decido registrar en la fecha ' + fechaAct +
            ' el proyecto con el título "' + titulo +
            '" creado en el semestre ' + periodoCronograma +
            ' y que comprende fecha de inicio: ' + fIni +
            ' y fecha de fin: ' + fFin + '.';

        compromisoLista.innerHTML = '';
        for (var i = 0; i < lastRows.length; i++) {
            var sem = lastRows[i];
            var task = deliverableFor(i, lastRows.length, sem);
            var li = document.createElement('li');
            li.textContent = sem.periodo + ': ' + task + '.';
            compromisoLista.appendChild(li);
        }
    }

    function openCompromisoModal() {
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery('#compromisoProyectoModal').modal('show');
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            (new window.bootstrap.Modal(document.getElementById('compromisoProyectoModal'))).show();
        }
    }

    function closeCompromisoModal() {
        if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
            window.jQuery('#compromisoProyectoModal').modal('hide');
            return;
        }
        if (window.bootstrap && window.bootstrap.Modal) {
            var el = document.getElementById('compromisoProyectoModal');
            var instance = window.bootstrap.Modal.getInstance(el);
            if (instance) {
                instance.hide();
            }
        }
    }

    email.addEventListener('input', validateEmailDomain);
    tel.addEventListener('input', sanitizeTel);
    inTitle.addEventListener('input', resetCommitment);
    inStart.addEventListener('change', previewSemesters);
    inEnd.addEventListener('change', previewSemesters);

    btnOpenCompromiso.addEventListener('click', function () {
        if (!validateForm()) return;
        buildCompromiso();
        openCompromisoModal();
    });

    btnAceptarCompromiso.addEventListener('click', function () {
        inAcepto.value = '1';
        closeCompromisoModal();
        form.submit();
    });

    form.addEventListener('submit', function (ev) {
        if (inAcepto.value !== '1') {
            ev.preventDefault();
            ev.stopPropagation();
            return;
        }
        if (!validateForm()) {
            ev.preventDefault();
            ev.stopPropagation();
        }
    });

    if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
        window.jQuery('#crearProyectoModal').on('hidden.bs.modal', resetCommitment);
        window.jQuery('#compromisoProyectoModal').on('hidden.bs.modal', function () {
            if (inAcepto.value !== '1') {
                resetCommitment();
            }
        });
    }

    previewSemesters();
})();
</script>
