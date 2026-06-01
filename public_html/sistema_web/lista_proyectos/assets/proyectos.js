(function () {
  var currentEvalContext = {
    responseId: null,
    projectId: null,
    formName: '',
    actionsState: {},
    ui: {},
    observation: null,
    coordinatorStatus: null,
    tipoInforme: '',
    periodo: ''
  };

  function hasJquery() {
    return !!(window.jQuery && window.jQuery.fn);
  }

  function isEvaluadorPage() {
    var p = String((window.location && window.location.pathname) || '').toLowerCase();
    return p.indexOf('/lista_proyectos/evaluador.php') !== -1;
  }

  function isCoordinadorPage() {
    var p = String((window.location && window.location.pathname) || '').toLowerCase();
    return p.indexOf('/lista_proyectos/coordinador.php') !== -1;
  }

  function esc(value) {
    var str = String(value == null ? '' : value);
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function closeAllDetails() {
    var rows = document.querySelectorAll('.prj-detail-row');
    for (var i = 0; i < rows.length; i++) {
      rows[i].style.display = 'none';
    }
  }

  function bindRowToggle() {
    var rows = document.querySelectorAll('.prj-row-toggle');
    for (var i = 0; i < rows.length; i++) {
      rows[i].addEventListener('click', function (event) {
        var target = event.target || event.srcElement;
        if (target) {
          var node = target;
          while (node && node !== this) {
            if (
              node.classList && (
                node.classList.contains('prj-deliver-btn') ||
                node.classList.contains('prj-eval-btn') ||
                node.classList.contains('prj-btn-informe') ||
                node.classList.contains('prj-btn-evaluacion') ||
                node.classList.contains('prj-btn-presentacion')
              )
            ) {
              return;
            }
            node = node.parentNode;
          }
        }

        var targetId = this.getAttribute('data-target');
        if (!targetId) return;

        var detail = document.getElementById(targetId);
        if (!detail) return;

        var isOpen = detail.style.display !== 'none';
        closeAllDetails();
        detail.style.display = isOpen ? 'none' : '';
      });
    }
  }

  function syncProgressStatusHeights() {
    var rows = document.querySelectorAll('tr.prj-row-toggle');
    for (var i = 0; i < rows.length; i++) {
      var row = rows[i];
      var progressWrap = row.querySelector('.prj-progress-wrap');
      var statusWrap = row.querySelector('.prj-status-wrap');
      if (!progressWrap || !statusWrap) continue;

      var progressLines = progressWrap.querySelectorAll('.prj-progress-line');
      var statusLines = statusWrap.querySelectorAll('.prj-status-line');
      if (!progressLines.length || !statusLines.length) continue;

      for (var p = 0; p < progressLines.length; p++) progressLines[p].style.minHeight = '';
      for (var s = 0; s < statusLines.length; s++) statusLines[s].style.minHeight = '';

      var statusByKey = {};
      for (var k1 = 0; k1 < statusLines.length; k1++) {
        statusByKey[statusLines[k1].getAttribute('data-line-key') || ('idx_' + k1)] = statusLines[k1];
      }

      for (var k2 = 0; k2 < progressLines.length; k2++) {
        var lineP = progressLines[k2];
        var keyP = lineP.getAttribute('data-line-key') || ('idx_' + k2);
        var lineS = statusByKey[keyP] || null;
        if (!lineS) continue;
        var maxH = Math.max(lineP.offsetHeight || 0, lineS.offsetHeight || 0);
        if (maxH > 0) {
          lineP.style.minHeight = maxH + 'px';
          lineS.style.minHeight = maxH + 'px';
        }
      }
    }
  }

  function showEvalAlert(type, message) {
    var alert = document.getElementById('prjEvalAlert');
    if (!alert) return;
    alert.className = 'alert alert-' + type;
    alert.textContent = message || '';
    alert.classList.remove('d-none');
  }

  function clearEvalAlert() {
    var alert = document.getElementById('prjEvalAlert');
    if (!alert) return;
    alert.className = 'alert d-none';
    alert.textContent = '';
  }

  function setRoleSections(ui) {
    var coordSection = document.getElementById('prjCoordSection');
    var evalSection = document.getElementById('prjEvalSection');
    var showCoord = !!(ui && ui.show_coordinator_flow);
    var showEval = !!(ui && ui.show_eval_actions);
    if (coordSection) coordSection.classList.toggle('d-none', !showCoord);
    if (evalSection) evalSection.classList.toggle('d-none', !showEval);
  }

  function setPendingEvalCount(count) {
    var el = document.getElementById('prjEvalPendingText');
    if (!el) return;
    var n = Number(count || 0);
    if (!isFinite(n) || n < 0) n = 0;
    var txt = n < 10 ? ('0' + n) : String(n);
    el.textContent = 'Evaluaciones pendientes: ' + txt;
  }

  function setEvalTabLabel(id, text) {
    var el = document.getElementById(id);
    if (!el) return;
    el.textContent = text;
  }

  function scrollEvalSectionToBottom(actionPaneId) {
    var modalBody = document.querySelector('#modalEvaluacionDetalle .modal-body');
    var pane = document.getElementById(actionPaneId || '');
    if (!modalBody || !pane) return;
    var bodyRect = modalBody.getBoundingClientRect();
    var paneRect = pane.getBoundingClientRect();
    var to = modalBody.scrollTop + (paneRect.top - bodyRect.top) - 10;
    if (to < 0) to = 0;
    modalBody.scrollTo({ top: to, behavior: 'smooth' });
  }

  function buildIndicacionesHtml(roleId, hasVistoBuenoOnly) {
    var isVb = !!hasVistoBuenoOnly;
    var html = '<div class="prj-indicaciones-text">';
    if (!isVb) {
      if (roleId === 5) {
        html += '<p>Eres el evaluador <strong>Presidente de comité de tu facultad</strong>, perteneces a la primera oficina de la <strong>ruta de evaluación</strong>, te corresponde <strong>Calificar por Cotejo</strong> y <strong>Calificar por Rúbrica</strong> a los informes semestrales o finales.</p>';
      } else {
        html += '<p>Eres el evaluador <strong>Director de RSU</strong>, perteneces a la cuarta y última oficina de la <strong>ruta de evaluación</strong>, te corresponde <strong>Calificar por Cotejo</strong> y <strong>Calificar por Rúbrica</strong> a los informes semestrales o finales.</p>';
      }
      html += '<p>1. Para calificar por <strong>Cotejo</strong>, deberás revisar el ANEXO 9_LISTA DE COTEJO PARA EVALUAR EL ESQUEMA DE INFORME DEL PROYECTO DE RSU.</p>';
      html += '<p>2. Para calificar por <strong>Rúbrica</strong>, deberás revisar el ANEXO 10_RÚBRICA DE CALIFICACIÓN PARA EL INFORME DEL PROYECTO DE RSU.</p>';
      if (roleId === 5) {
        html += '<p>Para que el informe pase a la siguiente oficina es fundamental darle la Aprobación en la Calificación de Cotejo y en la Calificación por Rúbrica.</p>';
      } else {
        html += '<p>Para que el informe reciba la aprobación total es fundamental darle la Aprobación en la Calificación de Cotejo y en la Calificación por Rúbrica.</p>';
      }
      html += '</div>';
      html += '<div class="prj-indicaciones-actions">';
      html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-blue prj-btn-recurso-rsu" data-resource="pdf_cotejo" data-resource-kind="pdf">Ver Anexo 9 - Lista de Cotejo</button>';
      html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-blue prj-btn-recurso-rsu" data-resource="pdf_rubrica" data-resource-kind="pdf">Ver Anexo 10 - Rúbrica</button>';
      html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_calificar" data-resource-kind="video">Video - Calificar por cotejo y rúbrica</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_coord_capacitacion_completa_22052026" data-resource-kind="video">Capacitación completa - 22-05-2026</button>';
    html += '</div>';
      return html;
    }

    if (roleId === 4) {
      html += '<p>Eres el evaluador <strong>Director de Departamento</strong>, perteneces a la segunda oficina de la <strong>ruta de evaluación</strong>, te corresponde <strong>otorgar el Visto Bueno</strong> a los informes semestrales o finales, antes de pasar al Decanato de Facultad.</p>';
    } else {
      html += '<p>Eres el evaluador <strong>Decano de Facultad</strong>, perteneces a la tercera oficina de la <strong>ruta de evaluación</strong>, te corresponde <strong>otorgar el Visto Bueno</strong> a los informes semestrales o finales, antes de pasar a la Dirección de RSU.</p>';
    }
    html += '<p>1. Para otorgar el <strong>Visto Bueno</strong>, deberás ir a la pestaña 1. Otorgar Visto Bueno.</p>';
    html += '<p>Para que el informe pase a la siguiente oficina es fundamental darle el Visto Bueno.</p>';
    html += '</div>';
    html += '<div class="prj-indicaciones-actions">';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_calificar" data-resource-kind="video">Video sobre cómo otorgar el visto bueno</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_coord_capacitacion_completa_22052026" data-resource-kind="video">Capacitación completa - 22-05-2026</button>';
    html += '</div>';
    return html;
  }

  function buildCoordinatorIndicacionesHtml() {
    var html = '<div class="prj-indicaciones-text">';
    html += '<p>Eres coordinador de proyecto y responsable de subir la información correspondiente: presentación del proyecto, informes semestrales e informe final.</p>';
    html += '<p>El proyecto debe durar como mínimo 2 años, equivalente a 4 semestres, y como máximo 5 años, equivalente a 10 semestres.</p>';
    html += '<p>En el primer semestre deberás subir la presentación del proyecto y el primer informe semestral. Luego, en cada semestre, subirás un informe semestral, excepto en el último, donde corresponde presentar el informe final.</p>';
    html += '<p>Los informes semestrales y finales serán evaluados mediante la Ruta de Evaluación, que incluye: Comité de Facultad, Dirección de Departamento, Decanato de Facultad y Dirección de RSU. El Comité de Facultad y la Dirección de RSU evaluarán mediante Lista de Cotejo y Rúbrica. La Dirección de Departamento y el Decanato otorgarán sus respectivos vistos buenos.</p>';
    html += '<p>Tu proyecto puede recibir observaciones en el Comité de Facultad o en la Dirección de RSU. En ese caso, podrás subsanarlas en un plazo de 1 o 2 días, según criterio del evaluador. La notificación llegará a tu correo registrado en el sistema. Al subsanar la observación, el evaluador será notificado para revisarla y aprobarla.</p>';
    html += '<p>Cada paso de la evaluación, subsanación y aprobación se realiza dentro del sistema. Además, cada avance será notificado a tu correo UNITRU.</p>';
    html += '</div>';
    html += '<div class="prj-indicaciones-actions">';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-blue prj-btn-recurso-rsu" data-resource="pdf_cotejo" data-resource-kind="pdf">Ver Anexo 9 - Lista de Cotejo</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-blue prj-btn-recurso-rsu" data-resource="doc_anexo08" data-resource-kind="download">Ver Anexo 08 - Esquema de Informe Semestral y Final</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-blue prj-btn-recurso-rsu" data-resource="pdf_rubrica" data-resource-kind="pdf">Ver Anexo 10 - Rúbrica</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_coord_revision_subsanacion" data-resource-kind="video">Video - Solicitar revisión y subsanar observación.</button>';
    html += '  <button type="button" class="btn prj-indicaciones-btn prj-indicaciones-btn-red prj-btn-recurso-rsu" data-resource="video_coord_capacitacion_completa_22052026" data-resource-kind="video">Capacitación completa - 22-05-2026</button>';
    html += '</div>';
    return html;
  }

  function renderCoordinatorPendientes(status, observation, tipoInforme) {
    var wrap = document.getElementById('prjEvalActionsCotejo');
    if (!wrap) return;

    var st = status || {};
    var obs = observation || {};
    var tipoTxt = String(tipoInforme || 'Informe');
    var isFinal = tipoTxt.toLowerCase().indexOf('final') !== -1;
    var lower = isFinal ? 'final' : 'semestral';
    var btnTxt = isFinal ? 'Ir a Informe Final' : 'Ir a Informe Semestral';
    var btnCls = isFinal ? 'btn btn-dark btn-lg prj-coord-pending-btn' : 'btn btn-success btn-lg prj-coord-pending-btn';
    var gotoHref = '../semestral/index.php';

    var responseState = Number(st.response_state || 0);
    var evalId = Number(st.eval_id || 0);
    var situacion = String(st.situacion || '').toLowerCase();
    var oficinaNom = String(st.oficina_actual_nombre || '').trim();
    var hasObs = !!obs.has_observation;

    var html = '<div class="prj-coord-pending-wrap">';
    if (hasObs) {
      html += '<p>Tu informe ' + lower + ' está observado, presiona el botón y ve al formulario para subsanar las observaciones y solicitar una nueva revisión a tu evaluador.</p>';
      html += '<a href="' + esc(gotoHref) + '" class="' + esc(btnCls) + '">' + esc(btnTxt) + '</a>';
      html += '</div>';
      wrap.innerHTML = html;
      return;
    }

    if (situacion === 'aprobado' || responseState === 2) {
      html += '<p>Tu informe ' + lower + ' fue APROBADO TOTALMENTE, no quedan tareas pendientes.</p>';
      html += '</div>';
      wrap.innerHTML = html;
      return;
    }

    if (responseState === 1) {
      if (oficinaNom !== '') {
        html += '<p>Tu informe ' + lower + ' está en revisión en <strong>' + esc(oficinaNom) + '</strong>. No tienes tareas pendientes por ahora; espera la notificación de avance en tu correo UNITRU.</p>';
      } else {
        html += '<p>Tu informe ' + lower + ' ya fue enviado a revisión. No tienes tareas pendientes por ahora; espera la notificación de avance en tu correo UNITRU.</p>';
      }
      html += '</div>';
      wrap.innerHTML = html;
      return;
    }

    if (evalId <= 0 || responseState === 0 || responseState === 3) {
      html += '<p>Aún no has creado tu informe ' + lower + ' correspondiente a este semestre, puedes crearlo presionando el botón.</p>';
      html += '<a href="' + esc(gotoHref) + '" class="' + esc(btnCls) + '">Crear Informe ' + (isFinal ? 'Final' : 'Semestral') + '</a>';
      html += '</div>';
      wrap.innerHTML = html;
      return;
    }

    if (oficinaNom !== '') {
      html += '<p>Tu informe ' + lower + ' se encuentra actualmente en <strong>' + esc(oficinaNom) + '</strong>. No tienes tareas pendientes en este momento.</p>';
    } else {
      html += '<p>No se detectan tareas pendientes para tu informe ' + lower + ' en este momento.</p>';
    }
    html += '</div>';
    wrap.innerHTML = html;
  }

  function setupIndicacionesTab(actions, ui) {
    var item = document.getElementById('prjTabIndicacionesItem');
    var pane = document.getElementById('prjTabIndicaciones');
    var link = document.getElementById('prjTabIndicacionesLink');
    var body = document.getElementById('prjEvalIndicacionesBody');
    var tabRItem = document.getElementById('prjTabRubricaLink');
    var tabRLi = tabRItem && tabRItem.parentNode ? tabRItem.parentNode : null;
    var tabRPane = document.getElementById('prjTabRubrica');
    var pendingText = document.getElementById('prjEvalPendingText');
    if (!item || !pane || !link || !body) return;

    var coordinadorVista = isCoordinadorPage();
    var evaluadorVista = isEvaluadorPage();
    var showEval = !!(ui && ui.show_eval_actions);
    if ((!evaluadorVista && !coordinadorVista) || !showEval) {
      item.classList.add('d-none');
      pane.classList.remove('show', 'active');
      if (tabRLi) tabRLi.classList.remove('d-none');
      if (tabRPane) tabRPane.classList.remove('d-none');
      if (pendingText) pendingText.classList.remove('d-none');
      setEvalTabLabel('prjTabObsLink', 'Ver observación');
      setEvalTabLabel('prjTabCotejoLink', 'Calificar Cotejo');
      setEvalTabLabel('prjTabRubricaLink', 'Calificar Rúbrica');
      return;
    }

    if (coordinadorVista && (ui && ui.mode === 'coordinador')) {
      item.classList.remove('d-none');
      if (tabRLi) tabRLi.classList.add('d-none');
      if (tabRPane) tabRPane.classList.add('d-none');
      if (pendingText) pendingText.classList.add('d-none');

      setEvalTabLabel('prjTabObsLink', 'Ver observación');
      setEvalTabLabel('prjTabCotejoLink', 'Mis pendientes');
      body.innerHTML = buildCoordinatorIndicacionesHtml();
      renderCoordinatorPendientes(currentEvalContext.coordinatorStatus, currentEvalContext.observation, currentEvalContext.tipoInforme);

      if (hasJquery()) {
        window.jQuery('#prjTabIndicacionesLink').tab('show');
      }
      return;
    }

    var map = {};
    if (Array.isArray(actions)) {
      for (var i = 0; i < actions.length; i++) {
        var ac = actions[i] || {};
        if (ac.key) map[ac.key] = ac;
      }
    }
    var hasC = !!map.cotejo;
    var hasR = !!map.rubrica;
    var hasVb = !!map.vb;
    var roleId = Number((ui && ui.role_id) || 0);
    var forceVbByRole = (roleId === 3 || roleId === 4);
    var vbOnly = forceVbByRole || (hasVb && !hasC && !hasR);

    item.classList.remove('d-none');
    if (vbOnly) {
      if (tabRLi) tabRLi.classList.add('d-none');
      if (tabRPane) tabRPane.classList.add('d-none');
    } else {
      if (tabRLi) tabRLi.classList.remove('d-none');
      if (tabRPane) tabRPane.classList.remove('d-none');
    }
    if (pendingText) pendingText.classList.remove('d-none');
    setEvalTabLabel('prjTabObsLink', 'Ver observación');
    if (vbOnly) {
      setEvalTabLabel('prjTabCotejoLink', '1. Otorgar Visto Bueno');
    } else {
      setEvalTabLabel('prjTabCotejoLink', '1. Calificar cotejo');
      setEvalTabLabel('prjTabRubricaLink', '2. Calificar rúbrica');
    }

    body.innerHTML = buildIndicacionesHtml(roleId, vbOnly);

    if (hasJquery()) {
      window.jQuery('#prjTabIndicacionesLink').tab('show');
    }
  }

  function showRsuRecursoInfo(msg) {
    var txt = String(msg || 'No se encontró el recurso consulte a RSU');
    var body = document.getElementById('prjRsuRecursoInfoBody');
    if (body) body.textContent = txt;
    if (hasJquery()) window.jQuery('#modalRsuRecursoInfo').modal('show');
    else window.alert(txt);
  }

  function getCurrentRoleIdForResource() {
    var fromEval = Number((currentEvalContext.ui && currentEvalContext.ui.role_id) || 0);
    if (fromEval > 0) return fromEval;
    var fromPage = Number(window.PRJ_PAGE_ROLE_ID || 0);
    return (fromPage > 0) ? fromPage : 0;
  }

  function resolveYoutubeVideoUrl(resourceKey) {
    var map = window.PRJ_YOUTUBE_VIDEO_MAP || {};
    var byResource = map ? map[resourceKey] : null;
    if (!byResource || typeof byResource !== 'object') return '';

    var roleId = getCurrentRoleIdForResource();
    if (roleId <= 0) return '';

    var key = String(roleId);
    var url = byResource[key];
    return String(url || '').trim();
  }

  function openRsuVideoModal(url) {
    var frame = document.getElementById('prjRsuVideoFrame');
    if (!frame) return;
    frame.src = '';
    frame.src = String(url || '').trim();
    if (hasJquery()) window.jQuery('#modalRsuVideoRecurso').modal('show');
  }

  function closeRsuVideoModalAndStop() {
    var frame = document.getElementById('prjRsuVideoFrame');
    if (!frame) return;
    frame.src = '';
  }

  function openRsuResource(resourceKey, kind) {
    if (!resourceKey) return;
    if (kind === 'video') {
      var ytUrl = resolveYoutubeVideoUrl(resourceKey);
      if (!ytUrl) {
        showRsuRecursoInfo('No se encontró el recurso de video para tu rol. Consulte a RSU.');
        return;
      }
      openRsuVideoModal(ytUrl);
      return;
    }
    if (!(window.jQuery && window.jQuery.ajax)) {
      showRsuRecursoInfo('No hay motor AJAX disponible.');
      return;
    }
    window.jQuery.ajax({
      url: '../lista_proyectos/api_recursos_rsu.php',
      method: 'GET',
      dataType: 'json',
      cache: false,
      data: {
        action: 'get',
        resource: resourceKey
      }
    }).done(function (json) {
      if (!json || !json.ok || !json.data || !json.data.url) {
        showRsuRecursoInfo((json && json.msg) ? json.msg : 'No se encontró el recurso consulte a RSU');
        return;
      }
      var url = String(json.data.url || '');
      if (url === '') {
        showRsuRecursoInfo('No se encontró el recurso consulte a RSU');
        return;
      }
      if (kind === 'download') {
        window.location.href = url;
      } else {
        window.open(url, '_blank', 'noopener');
      }
    }).fail(function () {
      showRsuRecursoInfo('No se encontró el recurso consulte a RSU');
    });
  }

  function updateEvalModalTitle(tipoInforme, periodo) {
    var t = document.getElementById('prjModalEvalTitle');
    if (!t) return;
    var tipo = String(tipoInforme || '').trim() || 'Informe';
    var per = String(periodo || '').trim() || 'Sin período';
    t.textContent = 'Ruta de evaluación de ' + tipo + ' - ' + per;
  }

  function formatDateText(v) {
    var txt = String(v || '').trim();
    return txt === '' ? '-' : txt;
  }

  function renderEvalTimeline(items) {
    var wrap = document.getElementById('prjEvalTimeline');
    if (!wrap) return;
    if (!Array.isArray(items) || items.length === 0) {
      wrap.innerHTML = '<div class="text-muted">Sin ruta de evaluación.</div>';
      return;
    }

    var html = '';
    for (var i = 0; i < items.length; i++) {
      var it = items[i] || {};
      var estado = String(it.estado || 'pendiente');
      var badgeClass = 'badge-secondary';
      var badgeText = 'Pendiente';
      if (estado === 'en_espera') { badgeClass = 'badge-primary'; badgeText = 'En espera'; }
      else if (estado === 'observado') { badgeClass = 'badge-danger'; badgeText = 'Observado'; }
      else if (estado === 'aprobado' || estado === 'cerrado') { badgeClass = 'badge-success'; badgeText = estado === 'aprobado' ? 'Aprobado' : 'Cerrado'; }

      var califs = it.calificaciones || {};
      var chips = [];
      if (califs.cotejo && califs.cotejo.estado) chips.push('Cotejo: ' + califs.cotejo.estado);
      if (califs.rubrica && califs.rubrica.estado) chips.push('Rúbrica: ' + califs.rubrica.estado);
      if (califs.vistobueno && califs.vistobueno.estado) chips.push('VB: ' + califs.vistobueno.estado);

      html += ''
        + '<div class="prj-eval-step">'
        + '  <div class="prj-eval-step-title">' + esc(it.nombre || it.codigo || 'Oficina') + '</div>'
        + '  <div><span class="badge ' + badgeClass + '">' + esc(badgeText) + '</span></div>'
        + '  <div class="prj-eval-step-meta mt-1">Llegada: ' + esc(formatDateText(it.llegada)) + '</div>'
        + '  <div class="prj-eval-step-meta">Salida: ' + esc(formatDateText(it.salida)) + '</div>'
        + '  <div class="prj-eval-step-meta">Obs.: ' + esc(formatDateText(it.obs_at)) + '</div>'
        + '  <div class="prj-eval-step-meta">Rev.: ' + esc(formatDateText(it.rev_at)) + '</div>'
        + (chips.length ? ('<div class="prj-eval-step-meta mt-1"><strong>' + esc(chips.join(' | ')) + '</strong></div>') : '')
        + '</div>';
    }
    wrap.innerHTML = html;
  }

  function buildDisabledActionHtml(reason) {
    var msg = String(reason || 'Aún no te corresponde evaluar, el informe está en otra oficina.');
    var cls = 'prj-pending-soft-alert mb-0';
    return '<div class="' + cls + '">' + esc(msg) + '</div>';
  }

  function renderEvalActions(actions) {
    var wrapC = document.getElementById('prjEvalActionsCotejo');
    var wrapR = document.getElementById('prjEvalActionsRubrica');
    if (!wrapC || !wrapR) return;

    var tabCLink = document.getElementById('prjTabCotejoLink');
    var tabRLink = document.getElementById('prjTabRubricaLink');
    var tabRItem = tabRLink && tabRLink.parentNode ? tabRLink.parentNode : null;
    var tabRPane = document.getElementById('prjTabRubrica');

    wrapC.setAttribute('data-form-loaded', '0');
    wrapR.setAttribute('data-form-loaded', '0');
    wrapC.setAttribute('data-action-key', 'cotejo');
    wrapR.setAttribute('data-action-key', 'rubrica');
    if (tabCLink) tabCLink.textContent = 'Calificar Cotejo';
    if (tabRLink) tabRLink.textContent = 'Calificar Rúbrica';
    if (tabRItem) tabRItem.classList.remove('d-none');
    if (tabRPane) tabRPane.classList.remove('d-none');

    if (!Array.isArray(actions) || actions.length === 0) {
      wrapC.innerHTML = '<div class="text-muted">Este rol no tiene acciones de evaluación.</div>';
      wrapR.innerHTML = '<div class="text-muted">Este rol no tiene acciones de evaluación.</div>';
      return;
    }

    var map = {};
    for (var i = 0; i < actions.length; i++) {
      var a = actions[i] || {};
      if (a.key) map[a.key] = a;
    }

    function renderActionPane(wrap, action, actionKey, noAplicaMsg) {
      wrap.setAttribute('data-action-key', actionKey || '');
      wrap.setAttribute('data-form-loaded', '0');
      if (!action || !action.key) {
        wrap.innerHTML = '<div class="text-muted">' + esc(noAplicaMsg || 'No aplica este tipo de evaluación para tu rol.') + '</div>';
        return;
      }
      if (!action.enabled) {
        wrap.innerHTML = buildDisabledActionHtml(action.reason || 'Aún no te corresponde evaluar, el informe está en otra oficina.');
        return;
      }
      wrap.innerHTML = '<div class="text-muted">Cargando formulario...</div>';
    }

    var hasC = !!map.cotejo;
    var hasR = !!map.rubrica;
    var hasVb = !!map.vb;
    var roleId = Number((currentEvalContext.ui && currentEvalContext.ui.role_id) || 0);
    var forceVbByRole = (roleId === 3 || roleId === 4);
    if (forceVbByRole || (hasVb && !hasC && !hasR)) {
      if (tabCLink) tabCLink.textContent = '1. Otorgar Visto Bueno';
      if (tabRItem) tabRItem.classList.add('d-none');
      if (tabRPane) tabRPane.classList.add('d-none');
      var vbAction = map.vb || null;
      renderActionPane(wrapC, vbAction, 'vb', 'No aplica este tipo de evaluación para tu rol.');
      wrapR.innerHTML = '';
      return;
    }

    renderActionPane(wrapC, hasC ? map.cotejo : null, 'cotejo', 'No aplica este tipo de evaluación para tu rol.');
    renderActionPane(wrapR, hasR ? map.rubrica : null, 'rubrica', 'No aplica este tipo de evaluación para tu rol.');
  }

  function injectHtmlWithScripts(targetEl, html) {
    if (!targetEl) return;
    var container = document.createElement('div');
    container.innerHTML = html;
    var scripts = container.querySelectorAll('script');
    var scriptList = [];
    for (var i = 0; i < scripts.length; i++) {
      scriptList.push(scripts[i]);
      scripts[i].parentNode.removeChild(scripts[i]);
    }
    targetEl.innerHTML = container.innerHTML;
    for (var j = 0; j < scriptList.length; j++) {
      var oldScript = scriptList[j];
      var newScript = document.createElement('script');
      if (oldScript.src) newScript.src = oldScript.src;
      else newScript.text = oldScript.text || oldScript.textContent || oldScript.innerHTML || '';
      document.body.appendChild(newScript);
      document.body.removeChild(newScript);
    }
  }

  function loadEvalActionForm(actionKey) {
    var wrap = (actionKey === 'rubrica') ? document.getElementById('prjEvalActionsRubrica') : document.getElementById('prjEvalActionsCotejo');
    if (!wrap || !actionKey || !currentEvalContext.responseId) return;

    wrap.setAttribute('data-form-loaded', '0');
    wrap.innerHTML = '<div id="contenidoEval"><div class="text-muted">Cargando formulario...</div></div>';

    if (window.jQuery && window.jQuery.ajax) {
      window.jQuery.ajax({
        url: '../informe_semestral/modales/evaluacion_msg.php',
        method: 'GET',
        data: {
          accion: actionKey,
          id: currentEvalContext.projectId || '',
          id_respuesta: currentEvalContext.responseId
        },
        cache: false
      }).done(function (html) {
        injectHtmlWithScripts(wrap, '<div id="contenidoEval">' + html + '</div>');
        wrap.setAttribute('data-form-loaded', '1');
        if (isEvaluadorPage()) {
          scrollEvalSectionToBottom(actionKey === 'rubrica' ? 'prjTabRubrica' : 'prjTabCotejo');
        }
      }).fail(function () {
        wrap.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el formulario de calificación.</div>';
      });
      return;
    }
    wrap.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
  }

  function renderCoordinatorActions(flow) {
    var wrap = document.getElementById('prjCoordActions');
    if (!wrap) return;
    if (!flow || !flow.visible) {
      wrap.innerHTML = '<div class="text-muted">Este rol no tiene acciones de coordinador en esta vista.</div>';
      return;
    }
    var html = '';
    var hasMain = !!flow.action;
    if (hasMain) {
      var btnClass = flow.enabled ? 'btn btn-primary btn-sm prj-btn-coord-action' : 'btn btn-secondary btn-sm prj-btn-coord-action prj-eval-action-disabled';
      html += '<button type="button" class="' + btnClass + '" data-action="' + esc(flow.action) + '" data-enabled="' + (flow.enabled ? '1' : '0') + '" title="' + esc(flow.reason || '') + '">'
        + esc(flow.label || 'Acción de coordinador') + '</button> ';
    } else {
      html += '<span class="badge badge-secondary">' + esc(flow.label || 'Sin acción') + '</span> ';
    }
    if (flow.obs_enabled) {
      html += '<button type="button" class="btn btn-outline-danger btn-sm prj-btn-show-obs" title="' + esc(flow.obs_reason || 'Ver observaciones') + '">Ver observaciones</button>';
    } else {
      html += '<button type="button" class="btn btn-outline-secondary btn-sm prj-btn-show-obs prj-eval-action-disabled" data-enabled="0" title="' + esc(flow.obs_reason || 'No hay observaciones activas') + '">Ver observaciones</button>';
    }
    if (flow.reason) html += '<div class="prj-coord-note mt-2">' + esc(flow.reason) + '</div>';
    wrap.innerHTML = html;
  }

  function renderObsDetailBlock(title, detail, includeTable) {
    var html = '<div class="card mb-2"><div class="card-body p-2">';
    html += '<h6 class="mb-2">' + esc(title) + '</h6>';
    html += '<div><strong>Oficina:</strong> ' + esc(detail.oficina_nom || '-') + ' (' + esc(detail.oficina_cod || '-') + ')</div>';
    html += '<div><strong>Fecha de observación:</strong> ' + esc(detail.obs_at_fmt || detail.obs_at || '-') + '</div>';
    html += '<div><strong>Fecha máxima de subsanación:</strong> ' + esc(detail.fecha_limite_fmt || '-') + '</div>';
    html += '<div><strong>Días de subsanación:</strong> ' + esc(detail.dias_subsanacion || '-') + ' (días laborables)</div>';
    if (includeTable && Array.isArray(detail.aspectos) && detail.aspectos.length > 0) {
      html += '<div class="table-responsive mt-2"><table class="table table-sm table-bordered prj-obs-inline-table mb-0">';
      html += '<thead><tr><th>Aspecto</th><th>Nota</th><th>Observación</th></tr></thead><tbody>';
      for (var i = 0; i < detail.aspectos.length; i++) {
        var ax = detail.aspectos[i] || {};
        html += '<tr><td>' + esc(ax.aspecto || '-') + '</td><td class="text-center">' + esc(ax.notaTx || ax.nota || '-') + '</td><td><em>' + esc(ax.obs || 'Sin observación') + '</em></td></tr>';
      }
      html += '</tbody></table></div>';
      if (typeof detail.total !== 'undefined' && detail.total !== null) {
        html += '<div class="mt-2"><strong>Puntaje total:</strong> ' + esc(detail.total) + ' / 20</div>';
      }
    } else {
      html += '<div class="mt-2"><strong>Observación:</strong> <em>' + esc(detail.obs_text || 'Sin observación') + '</em></div>';
    }
    html += '</div></div>';
    return html;
  }

  function loadInlineObservation() {
    var body = document.getElementById('prjEvalObsInlineBody');
    if (!body || !currentEvalContext.responseId) return;
    body.innerHTML = '<div class="text-muted">Cargando observación...</div>';
    if (!(window.jQuery && window.jQuery.ajax)) {
      body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
      return;
    }
    window.jQuery.ajax({
      url: '../lista_proyectos/api_evaluacion_observacion.php',
      method: 'GET',
      dataType: 'json',
      data: { response_id: currentEvalContext.responseId },
      cache: false
    }).done(function (json) {
      if (!json || !json.ok || !json.data) {
        body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el detalle de observación.</div>';
        return;
      }
      var data = json.data || {};
      if (!data.has_observation) {
        body.innerHTML = isEvaluadorPage()
          ? '<div class="prj-pending-soft-alert mb-0">No hay observaciones activas para este informe.</div>'
          : '<div class="alert alert-warning mb-0">No hay observaciones activas para este informe.</div>';
        return;
      }
      var html = '';
      if (data.cotejo) html += renderObsDetailBlock('Observación por lista de cotejo', data.cotejo, false);
      if (data.rubrica) html += renderObsDetailBlock('Observación por evaluación de rúbrica', data.rubrica, true);
      body.innerHTML = html || (isEvaluadorPage()
        ? '<div class="prj-pending-soft-alert mb-0">No hay observaciones activas para este informe.</div>'
        : '<div class="alert alert-warning mb-0">No hay observaciones activas para este informe.</div>');
    }).fail(function () {
      body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el detalle de observación.</div>';
    });
  }

  function renderInlineObservationToggle(obsInfo) {
    var body = document.getElementById('prjEvalObsInlineBody');
    if (!body) return;
    if (!obsInfo || !obsInfo.has_observation) {
      body.innerHTML = isEvaluadorPage()
        ? '<div class="prj-pending-soft-alert mb-0">No hay observaciones activas para este informe.</div>'
        : '<div class="prj-eval-inline-help">No hay observaciones activas para este informe.</div>';
      return;
    }
    loadInlineObservation();
  }

  function openEvaluacionModal(responseId, projectId) {
    clearEvalAlert();
    setRoleSections({ show_coordinator_flow: false, show_eval_actions: false });
    setPendingEvalCount(0);
    var resumen = document.getElementById('prjEvalResumen');
    var timeline = document.getElementById('prjEvalTimeline');
    var actionsC = document.getElementById('prjEvalActionsCotejo');
    var actionsR = document.getElementById('prjEvalActionsRubrica');
    var coord = document.getElementById('prjCoordActions');
    var obs = document.getElementById('prjEvalObsInlineBody');
    var indic = document.getElementById('prjEvalIndicacionesBody');
    if (resumen) resumen.innerHTML = '<span class="text-muted">Cargando...</span>';
    if (timeline) timeline.innerHTML = '';
    if (actionsC) actionsC.innerHTML = '';
    if (actionsR) actionsR.innerHTML = '';
    if (coord) coord.innerHTML = '';
    if (obs) obs.innerHTML = 'Sin detalle cargado.';
    if (indic) indic.innerHTML = '<p class="mb-0 text-muted">Cargando indicaciones...</p>';
    currentEvalContext.coordinatorStatus = null;

    if (window.jQuery && window.jQuery.fn) window.jQuery('#modalEvaluacionDetalle').modal('show');
    if (!(window.jQuery && window.jQuery.ajax)) {
      showEvalAlert('danger', 'No hay motor AJAX disponible.');
      return;
    }

    window.jQuery.ajax({
      url: '../lista_proyectos/api_evaluacion_detalle.php',
      method: 'GET',
      dataType: 'json',
      data: { response_id: responseId, project_id: projectId || '' },
      cache: false
    }).done(function (json) {
      if (!json || !json.ok) {
        showEvalAlert('danger', (json && json.msg) ? json.msg : 'No se pudo cargar evaluación.');
        return;
      }
      var data = json.data || {};
      currentEvalContext.responseId = data.response_id || responseId || null;
      currentEvalContext.projectId = data.id_py || projectId || null;
      currentEvalContext.formName = data.formulario_nombre || '';
      currentEvalContext.actionsState = {};
      currentEvalContext.ui = data.ui || {};
      currentEvalContext.observation = data.observation || null;
      currentEvalContext.coordinatorStatus = data.coordinator_status || null;
      currentEvalContext.tipoInforme = data.tipo_informe || '';
      currentEvalContext.periodo = data.periodo || '';
      if (Array.isArray(data.actions)) {
        for (var i = 0; i < data.actions.length; i++) {
          var a = data.actions[i] || {};
          if (a.key) currentEvalContext.actionsState[a.key] = { enabled: !!a.enabled, reason: a.reason || '' };
        }
      }
      setRoleSections(currentEvalContext.ui);
      setPendingEvalCount(data.actions_enabled_count || 0);
      updateEvalModalTitle(currentEvalContext.tipoInforme, currentEvalContext.periodo);

      if (resumen) {
        var badgeClass = (data.eval_badge && data.eval_badge.class) ? data.eval_badge.class : 'badge badge-secondary';
        var badgeText = (data.eval_badge && data.eval_badge.text) ? data.eval_badge.text : 'Sin ruta';
        resumen.innerHTML = '<div><strong>Proyecto:</strong> ' + esc(data.titulo_proyecto || 'Sin título') + '</div>'
          + '<div><strong>Periodo:</strong> ' + esc(data.periodo || 'No definido')
          + ' | <strong>Tipo:</strong> ' + esc(data.tipo_informe || 'Informe')
          + ' | <span class=\"' + esc(badgeClass) + '\">' + esc(badgeText) + '</span></div>'
          + (data.coordinador ? ('<div><strong>Coordinador:</strong> ' + esc(data.coordinador) + '</div>') : '');
      }

      renderEvalTimeline(data.timeline || []);
      renderInlineObservationToggle(currentEvalContext.observation);
      renderEvalActions(data.actions || []);
      setupIndicacionesTab(data.actions || [], currentEvalContext.ui || {});
      renderCoordinatorActions(data.coordinator_flow || null);
    }).fail(function () {
      showEvalAlert('danger', 'No se pudo cargar evaluación.');
    });
  }

  function syncModalInformeLayout() {
    var body = document.getElementById('prjInformeDetalleBody');
    if (!body) return;
    var bodyRect = body.getBoundingClientRect();
    var rows = body.querySelectorAll('.rsu-split-row');
    for (var i = 0; i < rows.length; i++) {
      rows[i].style.display = 'flex';
      var left = rows[i].querySelector('.rsu-left');
      var right = rows[i].querySelector('.rsu-right');
      var rowRect = rows[i].getBoundingClientRect();
      var usedTop = Math.max(0, rowRect.top - bodyRect.top);
      var available = Math.max(220, (body.clientHeight || 0) - usedTop - 12);
      if (left) {
        left.style.height = available + 'px';
        left.style.maxHeight = available + 'px';
      }
      if (right) {
        right.style.height = available + 'px';
        right.style.maxHeight = available + 'px';
      }
    }
    bindInformeScrollGuards(body);
    bindInformeNavLinks(body);
  }

  function bindInformeScrollGuards(root) {
    if (!root) return;
    var panes = root.querySelectorAll('.rsu-left, .rsu-right');
    for (var i = 0; i < panes.length; i++) {
      var pane = panes[i];
      if (pane.getAttribute('data-wheel-guard') === '1') continue;
      pane.setAttribute('data-wheel-guard', '1');
      pane.addEventListener('wheel', function (ev) {
        var delta = ev.deltaY || 0;
        if (!delta) return;
        var top = this.scrollTop;
        var max = this.scrollHeight - this.clientHeight;
        var goingDown = delta > 0;
        var canScrollDown = top < max;
        var canScrollUp = top > 0;
        if ((goingDown && canScrollDown) || (!goingDown && canScrollUp)) {
          ev.stopPropagation();
        }
      }, { passive: true });
      pane.addEventListener('touchmove', function (ev) {
        ev.stopPropagation();
      }, { passive: true });
    }
  }

  function bindInformeNavLinks(root) {
    if (!root) return;
    var anchors = root.querySelectorAll('.rsu-left a[href^="#"]');
    for (var i = 0; i < anchors.length; i++) {
      var a = anchors[i];
      if (a.getAttribute('data-nav-bound') === '1') continue;
      a.setAttribute('data-nav-bound', '1');
      a.addEventListener('click', function (ev) {
        ev.preventDefault();
        var hash = this.getAttribute('href') || '';
        if (!hash || hash.charAt(0) !== '#') return;
        var split = this.closest('.rsu-split-row');
        if (!split) return;
        var right = split.querySelector('.rsu-right');
        if (!right) return;
        var target = right.querySelector(hash);
        if (!target) target = split.querySelector(hash);
        if (!target) return;
        var offset = target.offsetTop - right.offsetTop - 8;
        right.scrollTo({ top: Math.max(0, offset), behavior: 'smooth' });
      });
    }
  }

  function setInformeModalHeader(tipoInforme) {
    var header = document.getElementById('prjInformeHeader');
    var icon = document.getElementById('prjInformeModalIcon');
    var titleText = document.getElementById('prjInformeModalTitleText');
    if (!header || !icon || !titleText) return;

    var tipo = String(tipoInforme || '').toLowerCase();
    var isFinal = (tipo === 'final');
    header.classList.remove('prj-informe-header-semestral', 'prj-informe-header-final');
    header.classList.add(isFinal ? 'prj-informe-header-final' : 'prj-informe-header-semestral');
    icon.className = isFinal ? 'fas fa-flag-checkered mr-2' : 'fas fa-file-alt mr-2';
    titleText.textContent = isFinal ? 'Informe final' : 'Informe semestral';
  }

  function openInformeModal(projectId, responseId, tipoInforme) {
    var body = document.getElementById('prjInformeDetalleBody');
    if (!body) return;
    setInformeModalHeader(tipoInforme);
    body.innerHTML = '<div class="text-muted">Cargando...</div>';
    if (window.jQuery && window.jQuery.fn) window.jQuery('#modalInformeDetalle').modal('show');
    if (!(window.jQuery && window.jQuery.get)) {
      body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
      return;
    }
    window.jQuery.get('../informe_semestral/ver_informe.php', { id: projectId, id_respuesta: responseId || '' }, function (html) {
      body.innerHTML = html;
      syncModalInformeLayout();
      if (window.requestAnimationFrame) window.requestAnimationFrame(syncModalInformeLayout);
    }, 'html').fail(function () {
      body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el informe.</div>';
    });
  }

  function openPresentacionModal(projectId) {
    var body = document.getElementById('prjPresentacionDetalleBody');
    if (!body) return;
    body.innerHTML = '<p class="text-center text-muted">Cargando datos del proyecto...</p>';
    if (window.jQuery && window.jQuery.fn) window.jQuery('#modalPresentacionDetalle').modal('show');
    if (!(window.jQuery && window.jQuery.get)) {
      body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
      return;
    }
    window.jQuery.get('../comite_facultad/calificacion/presentacion.php', { id_py: projectId }, function (html) {
      body.innerHTML = html;
      loadPresentacionAdjuntos(projectId, body);
    }, 'html').fail(function () {
      body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar la presentación del proyecto.</div>';
    });
  }

  function loadPresentacionAdjuntos(projectId, root) {
    if (!projectId || !root) return;
    var cont = root.querySelector('#contenedor-archivos');
    if (!cont) return;
    fetch('../comite_facultad/calificacion/gestion_archivos.php?id_py=' + encodeURIComponent(projectId), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var label = {
          lista_docentes: '1. Lista de Docentes',
          lista_alumnos: '2. Lista de Alumnos',
          diagrama: '3. Diagrama',
          compromiso: '4. Compromiso Etico',
          carta: '5. Carta de Intencion'
        };
        var navId = {
          lista_docentes: 'anitem1',
          lista_alumnos: 'anitem2',
          diagrama: 'anitem3',
          compromiso: 'anitem4',
          carta: 'anitem5'
        };
        for (var cat in data) {
          if (!Object.prototype.hasOwnProperty.call(data, cat)) continue;
          var files = data[cat];
          var sec = document.createElement('div');
          sec.className = 'mb-3';
          sec.id = navId[cat] || '';
          var titulo = '<strong>' + esc(label[cat] || cat) + '</strong><br>';
          if (!Array.isArray(files) || !files.length) {
            sec.innerHTML = titulo + '<span class="text-danger">No hay archivo</span>';
          } else {
            var cards = files.map(function (f) {
              var name = String(f || '');
              var ext = name.split('.').pop().toLowerCase();
              var isPdf = ext === 'pdf';
              var isXls = (ext === 'xls' || ext === 'xlsx');
              var icon = isPdf ? 'file-pdf text-danger' : (isXls ? 'file-excel text-success' : 'file-alt text-secondary');
              var btn = isPdf ? 'btn-outline-danger' : (isXls ? 'btn-outline-success' : 'btn-outline-secondary');
              var url = '../comite_facultad/calificacion/descarga_archivos.php?categoria='
                + encodeURIComponent(cat)
                + '&id_py=' + encodeURIComponent(projectId)
                + '&archivo=' + encodeURIComponent(name)
                + (isPdf ? '&ver=1' : '');
              return ''
                + '<div class="archivo-card d-flex align-items-center justify-content-between p-3 mb-2 border rounded shadow-sm bg-white">'
                + '  <div class="d-flex align-items-center" style="gap:10px;">'
                + '    <i class="fas fa-' + icon + '" style="font-size:1.5rem;"></i>'
                + '    <div title="' + esc(name) + '" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">'
                +        esc(name)
                + '    </div>'
                + '  </div>'
                + '  <a href="' + url + '" target="_blank" rel="noopener" class="btn ' + btn + ' btn-sm">Descargar</a>'
                + '</div>';
            }).join('');
            sec.innerHTML = titulo + cards;
          }
          cont.appendChild(sec);
        }
      })
      .catch(function () {
        cont.innerHTML = '<div class="text-danger">Error al cargar archivos.</div>';
      });
  }

  function postCoordinatorAction(actionKey) {
    var responseId = currentEvalContext.responseId;
    var projectId = currentEvalContext.projectId;
    if (!actionKey || !responseId) return;

    var url = '';
    var payload = { id_respuesta: responseId };
    var confirmText = '';
    if (actionKey === 'solicitar') {
      url = '../semestral/logica/solicitar_revision.php';
      payload.proy_titulo = '';
      payload.form_nombre = currentEvalContext.formName || '';
      confirmText = '¿Confirmas solicitar revisión del informe?';
    } else if (actionKey === 'anular') {
      url = '../semestral/logica/anular_revision.php';
      confirmText = '¿Confirmas anular la solicitud y volver a borrador?';
    } else if (actionKey === 'subsanar') {
      url = '../semestral/logica/enviar_subsanacion.php';
      confirmText = '¿Confirmas enviar la subsanación a la oficina actual?';
    } else {
      showEvalAlert('warning', 'Acción de coordinador no reconocida.');
      return;
    }
    if (window.confirm && !window.confirm(confirmText)) return;
    showEvalAlert('info', 'Procesando acción...');

    window.jQuery.ajax({
      url: url,
      method: 'POST',
      dataType: 'json',
      data: payload,
      cache: false
    }).done(function (json) {
      var ok = !!json && (json.status === 'ok' || json.ok === true);
      if (!ok) {
        var msgErr = (json && (json.msg || json.error)) ? (json.msg || json.error) : 'No se pudo completar la acción.';
        showEvalAlert('danger', msgErr);
        return;
      }
      var msg = (json && json.msg) ? json.msg : 'Acción realizada correctamente.';
      if (json && json.mail_ok === false && json.mail_msg) {
        msg += ' ' + json.mail_msg;
        showEvalAlert('warning', msg);
      } else {
        showEvalAlert('success', msg);
      }
      window.setTimeout(function () { openEvaluacionModal(responseId, projectId); }, 450);
    }).fail(function (xhr) {
      var msgFail = 'No se pudo completar la acción.';
      if (xhr && xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.error)) msgFail = xhr.responseJSON.msg || xhr.responseJSON.error;
      showEvalAlert('danger', msgFail);
    });
  }

  function bindProgressButtonsJquery() {
    if (!hasJquery()) return false;
    var $doc = window.jQuery(document);

    $doc.off('click.prjInforme').on('click.prjInforme', '.prj-btn-informe', function (event) {
      event.preventDefault(); event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      var projectId = window.jQuery(this).attr('data-project-id');
      var tipoInforme = window.jQuery(this).attr('data-informe-tipo') || '';
      if (responseId && projectId) openInformeModal(projectId, responseId, tipoInforme);
    });
    $doc.off('click.prjPresentacion').on('click.prjPresentacion', '.prj-btn-presentacion', function (event) {
      event.preventDefault(); event.stopPropagation();
      var projectId = window.jQuery(this).attr('data-project-id');
      if (projectId) openPresentacionModal(projectId);
    });
    $doc.off('click.prjEval').on('click.prjEval', '.prj-btn-evaluacion', function (event) {
      event.preventDefault(); event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      var projectId = window.jQuery(this).attr('data-project-id');
      if (responseId) openEvaluacionModal(responseId, projectId);
    });
    $doc.off('click.prjCoordAction').on('click.prjCoordAction', '.prj-btn-coord-action', function (event) {
      event.preventDefault(); event.stopPropagation();
      var enabled = window.jQuery(this).attr('data-enabled') === '1';
      var reason = window.jQuery(this).attr('title') || '';
      var actionKey = window.jQuery(this).attr('data-action');
      if (!actionKey) return;
      if (!enabled) { showEvalAlert('warning', reason || 'Acción no disponible en este estado.'); return; }
      postCoordinatorAction(actionKey);
    });
    $doc.off('click.prjShowObs').on('click.prjShowObs', '.prj-btn-show-obs', function (event) {
      event.preventDefault(); event.stopPropagation();
      var enabled = window.jQuery(this).attr('data-enabled');
      if (enabled === '0') { showEvalAlert('warning', window.jQuery(this).attr('title') || 'No hay observaciones activas.'); return; }
      loadInlineObservation();
      window.jQuery('#prjTabObsLink').tab('show');
    });
    $doc.off('click.prjRsuRecurso').on('click.prjRsuRecurso', '.prj-btn-recurso-rsu', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var resource = window.jQuery(this).attr('data-resource') || '';
      var kind = window.jQuery(this).attr('data-resource-kind') || '';
      openRsuResource(resource, kind);
    });
    $doc.off('shown.bs.tab.prjEvalTabs').on('shown.bs.tab.prjEvalTabs', '#prjTabCotejoLink, #prjTabRubricaLink', function (event) {
      if (currentEvalContext.ui && currentEvalContext.ui.mode === 'coordinador') {
        return;
      }
      var targetId = window.jQuery(event.target).attr('id') || '';
      if (targetId === 'prjTabCotejoLink') {
        var keyC = window.jQuery('#prjEvalActionsCotejo').attr('data-action-key') || 'cotejo';
        if (isEvaluadorPage()) scrollEvalSectionToBottom('prjTabCotejo');
        if (currentEvalContext.actionsState[keyC] && currentEvalContext.actionsState[keyC].enabled) loadEvalActionForm(keyC);
      } else if (targetId === 'prjTabRubricaLink') {
        var keyR = window.jQuery('#prjEvalActionsRubrica').attr('data-action-key') || 'rubrica';
        if (isEvaluadorPage()) scrollEvalSectionToBottom('prjTabRubrica');
        if (currentEvalContext.actionsState[keyR] && currentEvalContext.actionsState[keyR].enabled) loadEvalActionForm(keyR);
      }
    });
    if (hasJquery()) {
      window.jQuery('#modalRsuVideoRecurso')
        .off('hidden.bs.modal.prjRsuVideo')
        .on('hidden.bs.modal.prjRsuVideo', closeRsuVideoModalAndStop);
    }
    return true;
  }

  function bindFilters() {
    var form = document.getElementById('prjFiltersForm');
    if (!form) return;
    var fac = document.getElementById('prjFacultad');
    var dep = document.getElementById('prjDepartamento');
    var cre = document.getElementById('prjCreacion');
    function submitForm() { if (form.requestSubmit) form.requestSubmit(); else form.submit(); }
    if (fac) fac.addEventListener('change', function () { if (dep) dep.value = '0'; submitForm(); });
    if (dep) dep.addEventListener('change', submitForm);
    if (cre) cre.addEventListener('change', submitForm);
  }

  function init() {
    bindRowToggle();
    bindProgressButtonsJquery();
    bindFilters();
    syncProgressStatusHeights();
    window.addEventListener('resize', syncProgressStatusHeights);
    window.addEventListener('load', syncProgressStatusHeights);
    window.setTimeout(syncProgressStatusHeights, 120);
    window.setTimeout(syncProgressStatusHeights, 450);
  }

  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init);
  else init();
})();
