(function () {
  var detailPlaceholder = document.getElementById('opesp-detail-placeholder');
  var detailContainer = document.getElementById('opesp-informe-detalle');
  var resizeTimer = null;
  var mutationObserver = null;

  var selectorModal = document.getElementById('opesp-form-selector-modal');
  var selectorSelect = document.getElementById('opesp-form-selector');
  var selectorApplyBtn = document.getElementById('opesp-form-selector-apply');
  var runModal = document.getElementById('opesp-migration-run-modal');
  var progressBar = document.getElementById('opesp-migration-progress-bar');
  var progressStatus = document.getElementById('opesp-migration-live-status');
  var progressLog = document.getElementById('opesp-migration-log');
  var progressCloseBtn = document.getElementById('opesp-migration-close-btn');
  var confirmModal = document.getElementById('opesp-migration-confirm-modal');
  var confirmBtn = document.getElementById('opesp-migration-confirm-btn');

  var pendingFormSelectProject = 0;
  var pendingExecutePayload = null;
  var progressTimers = [];

  function clearActiveButtons() {
    var buttons = document.querySelectorAll('.opesp-btn-formulario');
    buttons.forEach(function (btn) {
      btn.classList.remove('active');
    });
  }

  function setActiveProjectButton(projectId) {
    clearActiveButtons();
    var target = document.querySelector('.opesp-btn-proyecto-base[data-id-py="' + projectId + '"]');
    if (target) {
      target.classList.add('active');
    }
  }

  function setActiveSemestralLegacyButton(projectId) {
    clearActiveButtons();
    var target = document.querySelector('.opesp-btn-semestral-legacy[data-id-py="' + projectId + '"]');
    if (target) {
      target.classList.add('active');
    }
  }

  function setActiveInformeButton(responseId) {
    clearActiveButtons();
    var target = document.querySelector('.opesp-btn-formulario[data-action="informe"][data-id-respuesta="' + responseId + '"]');
    if (target) {
      target.classList.add('active');
    }
  }

  function setActiveMigrationButton(projectId) {
    clearActiveButtons();
    var target = document.querySelector('.opesp-btn-migracion[data-id-py="' + projectId + '"]');
    if (target) {
      target.classList.add('active');
    }
  }

  function escapeHtml(value) {
    return String(value == null ? '' : value)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function textToHtml(value) {
    return escapeHtml(value).replace(/\n/g, '<br>');
  }

  function updateMigrationSummary(summary) {
    if (!summary) {
      return;
    }
    var elTotal = document.getElementById('opesp-summary-migrables');
    var elMigrados = document.getElementById('opesp-summary-migrados');
    var elPendientes = document.getElementById('opesp-summary-pendientes');

    if (elTotal && summary.total_migrables != null) {
      elTotal.textContent = Number(summary.total_migrables).toLocaleString('es-PE');
    }
    if (elMigrados && summary.total_migrados != null) {
      elMigrados.textContent = Number(summary.total_migrados).toLocaleString('es-PE');
    }
    if (elPendientes && summary.total_pendientes != null) {
      elPendientes.textContent = Number(summary.total_pendientes).toLocaleString('es-PE');
    }
  }

  function stabilizeDetailLayout() {
    if (!detailContainer) {
      return;
    }

    var isMobile = window.matchMedia('(max-width: 991.98px)').matches;
    var splitBlocks = detailContainer.querySelectorAll('.contenedor-scroll, .rsu-split-row');
    splitBlocks.forEach(function (split) {
      split.style.display = 'flex';
      split.style.flexDirection = isMobile ? 'column' : 'row';
      split.style.alignItems = 'stretch';
      split.style.gap = '1rem';
      split.style.minHeight = '0';
      split.style.height = 'auto';
      split.style.overflow = 'visible';
    });

    var navBlocks = detailContainer.querySelectorAll('.navegacion-lateral, .rsu-left');
    navBlocks.forEach(function (nav) {
      nav.style.position = 'static';
      nav.style.top = 'auto';
      nav.style.height = 'auto';
      nav.style.maxHeight = 'none';
      nav.style.overflowY = 'visible';
      nav.style.overflowX = 'hidden';
      nav.style.minHeight = '0';
      nav.style.alignSelf = 'stretch';
      if (isMobile) {
        nav.style.minWidth = '0';
        nav.style.maxWidth = '100%';
        nav.style.flex = '1 1 auto';
      } else {
        nav.style.minWidth = '180px';
        nav.style.maxWidth = '260px';
        nav.style.flex = '0 0 230px';
      }
    });

    var contentBlocks = detailContainer.querySelectorAll('.flex-grow-1, .rsu-right');
    contentBlocks.forEach(function (content) {
      content.style.minWidth = '0';
      content.style.minHeight = '0';
      content.style.flex = '1 1 auto';
      content.style.overflow = 'visible';
    });

    var fixedHeightBlocks = detailContainer.querySelectorAll('.form-body, .modal-body');
    fixedHeightBlocks.forEach(function (block) {
      block.style.height = 'auto';
      block.style.maxHeight = 'none';
      block.style.minHeight = '0';
      block.style.overflow = 'visible';
    });
  }

  function scheduleStabilizeLayout() {
    stabilizeDetailLayout();
    setTimeout(stabilizeDetailLayout, 80);
    setTimeout(stabilizeDetailLayout, 240);
    setTimeout(stabilizeDetailLayout, 600);
  }

  function attachMutationStabilizer() {
    if (!detailContainer || !window.MutationObserver) {
      return;
    }
    if (mutationObserver) {
      mutationObserver.disconnect();
    }
    mutationObserver = new MutationObserver(function () {
      scheduleStabilizeLayout();
    });
    mutationObserver.observe(detailContainer, { childList: true, subtree: true });
  }

  function findScrollableAncestor(node) {
    var current = node ? node.parentElement : null;
    while (current && current !== document.body) {
      var style = window.getComputedStyle(current);
      var overflowY = style.overflowY;
      var isScrollable = (overflowY === 'auto' || overflowY === 'scroll') && current.scrollHeight > current.clientHeight;
      if (isScrollable) {
        return current;
      }
      current = current.parentElement;
    }
    var fallback = detailContainer ? detailContainer.closest('.opesp-scroll-body') : null;
    return fallback || document.scrollingElement || document.documentElement;
  }

  function showModal(modalEl) {
    if (!modalEl) {
      return;
    }
    if (window.jQuery) {
      window.jQuery(modalEl).modal('show');
      return;
    }
    modalEl.style.display = 'block';
    modalEl.classList.add('show');
    modalEl.removeAttribute('aria-hidden');
  }

  function hideModal(modalEl) {
    if (!modalEl) {
      return;
    }
    if (window.jQuery) {
      window.jQuery(modalEl).modal('hide');
      return;
    }
    modalEl.style.display = 'none';
    modalEl.classList.remove('show');
    modalEl.setAttribute('aria-hidden', 'true');
  }

  function appendProgressLine(text, cssClass) {
    if (!progressLog) {
      return;
    }
    var div = document.createElement('div');
    div.className = 'line' + (cssClass ? ' ' + cssClass : '');
    div.textContent = text;
    progressLog.appendChild(div);
    progressLog.scrollTop = progressLog.scrollHeight;
  }

  function resetProgressModal() {
    progressTimers.forEach(function (id) { clearTimeout(id); });
    progressTimers = [];
    if (progressBar) {
      progressBar.style.width = '0%';
      progressBar.textContent = '0%';
      progressBar.classList.add('progress-bar-animated');
    }
    if (progressStatus) {
      progressStatus.textContent = 'Preparando migración...';
    }
    if (progressLog) {
      progressLog.innerHTML = '';
    }
    if (progressCloseBtn) {
      progressCloseBtn.disabled = true;
    }
  }

  function setProgress(percent, statusText) {
    var pct = Math.max(0, Math.min(100, Number(percent || 0)));
    if (progressBar) {
      progressBar.style.width = pct + '%';
      progressBar.textContent = pct + '%';
    }
    if (progressStatus && statusText) {
      progressStatus.textContent = statusText;
    }
  }

  function simulateProgressWhileRunning() {
    var checkpoints = [
      { at: 150, pct: 8, text: 'Validando proyecto y coordinador...' },
      { at: 650, pct: 16, text: 'Resolviendo formulario semestral 2024-II...' },
      { at: 1300, pct: 24, text: 'Verificando semestre objetivo...' },
      { at: 2200, pct: 32, text: 'Preparando respuesta destino...' },
      { at: 3200, pct: 45, text: 'Migrando ítems del informe antiguo...' },
      { at: 4600, pct: 62, text: 'Construyendo ruta de evaluación...' },
      { at: 6000, pct: 78, text: 'Aplicando aprobaciones por oficina...' },
      { at: 7600, pct: 88, text: 'Registrando historial de migración...' }
    ];

    checkpoints.forEach(function (cp) {
      var timerId = setTimeout(function () {
        setProgress(cp.pct, cp.text);
        appendProgressLine(cp.text);
      }, cp.at);
      progressTimers.push(timerId);
    });
  }

  function requestJson(url, options) {
    return fetch(url, options).then(function (res) {
      return res.json().catch(function () {
        return { ok: false, message: 'Respuesta inválida del servidor.' };
      });
    });
  }

  function replaceSectionFromDoc(sectionId, remoteDoc) {
    var currentNode = document.getElementById(sectionId);
    var incomingNode = remoteDoc.getElementById(sectionId);
    if (!currentNode || !incomingNode) {
      return false;
    }
    currentNode.outerHTML = incomingNode.outerHTML;
    return true;
  }

  function refreshTableDataAfterMigration(idPy) {
    var targetIdPy = parseInt(idPy, 10) || 0;
    return fetch(window.location.href, { cache: 'no-store' })
      .then(function (response) { return response.text(); })
      .then(function (html) {
        var parser = new DOMParser();
        var remoteDoc = parser.parseFromString(html, 'text/html');

        replaceSectionFromDoc('opesp-results-meta', remoteDoc);
        replaceSectionFromDoc('opesp-table-wrap', remoteDoc);
        replaceSectionFromDoc('opesp-pagination-wrap', remoteDoc);

        var remoteSummaryMigrables = remoteDoc.getElementById('opesp-summary-migrables');
        var remoteSummaryMigrados = remoteDoc.getElementById('opesp-summary-migrados');
        var remoteSummaryPendientes = remoteDoc.getElementById('opesp-summary-pendientes');

        if (remoteSummaryMigrables) {
          var localMigrables = document.getElementById('opesp-summary-migrables');
          if (localMigrables) {
            localMigrables.textContent = remoteSummaryMigrables.textContent;
          }
        }
        if (remoteSummaryMigrados) {
          var localMigrados = document.getElementById('opesp-summary-migrados');
          if (localMigrados) {
            localMigrados.textContent = remoteSummaryMigrados.textContent;
          }
        }
        if (remoteSummaryPendientes) {
          var localPendientes = document.getElementById('opesp-summary-pendientes');
          if (localPendientes) {
            localPendientes.textContent = remoteSummaryPendientes.textContent;
          }
        }

        if (targetIdPy > 0) {
          setActiveMigrationButton(targetIdPy);
        }
      })
      .catch(function () {
        // Mantener la pantalla util incluso si falla el refresco parcial.
      });
  }

  function renderMigrationPreview(data, pushUrl) {
    if (!detailContainer) {
      return;
    }

    if (detailPlaceholder) {
      detailPlaceholder.classList.add('d-none');
    }
    detailContainer.classList.remove('d-none');

    var form = data.form || {};
    var existing = data.existing_response || {};
    var legacyItems = Array.isArray(data.legacy_items) ? data.legacy_items : [];
    var targetItems = Array.isArray(data.target_items) ? data.target_items : [];
    var coordinadores = Array.isArray(data.coordinadores) ? data.coordinadores : [];

    var coordWarn = '';
    if (Number(data.coordinador_count || 0) > 1) {
      coordWarn = '<div class="alert alert-warning py-2 mb-2">Se detectaron múltiples coordinadores activos reales. Se usará el primero para la migración automática.</div>';
    }

    var existingWarn = '';
    if (Number(existing.exists || 0) === 1) {
      existingWarn = '<div class="alert alert-danger py-2 mb-2 mb-md-0">Este formulario ya tiene respuesta previa. Al migrar se hará reemplazo completo.</div>';
    }

    var headerInfo = '';
    if (coordinadores.length) {
      var c0 = coordinadores[0];
      headerInfo = '<div class="small text-muted">Coordinador base: <strong>' + escapeHtml((c0.nombres || '') + ' ' + (c0.apellidos || '')) +
        '</strong> (' + escapeHtml(c0.usuario || '') + ')</div>';
    }

    var html = '';
    html += '<div class="opesp-migracion-actions mb-2">';
    html += '<button type="button" class="btn btn-info btn-sm" id="opesp-run-migration-btn"';
    html += ' data-id-py="' + escapeHtml(data.id_py || 0) + '"';
    html += ' data-id-formulario="' + escapeHtml(form.id_formulario || 0) + '"';
    html += ' data-has-existing="' + escapeHtml(existing.exists || 0) + '">';
    html += '<i class="fas fa-random"></i> Migrar ahora';
    html += '</button>';
    html += '<span class="badge badge-light border p-2">Formulario destino: ' + escapeHtml(form.nombre_formulario || 'Sin nombre') + '</span>';
    html += '<span class="badge badge-light border p-2">ID Form: ' + escapeHtml(form.id_formulario || 0) + '</span>';
    html += '</div>';
    html += coordWarn;
    html += existingWarn;
    html += headerInfo;

    html += '<div class="opesp-migracion-layout mt-2">';
    html += '<div class="opesp-migracion-col">';
    html += '<div class="opesp-migracion-col-head"><strong>Origen: Informe semestral antiguo</strong></div>';
    html += '<div class="opesp-migracion-col-body">';
    if (!legacyItems.length) {
      html += '<div class="text-muted">No hay datos de origen.</div>';
    } else {
      legacyItems.forEach(function (item) {
        html += '<div class="opesp-migracion-item">';
        html += '<div class="opesp-migracion-item-title">' + escapeHtml(item.label || '') + '</div>';
        html += '<div class="opesp-migracion-item-value">' + textToHtml(item.value || '') + '</div>';
        html += '</div>';
      });
    }
    html += '</div></div>';

    html += '<div class="opesp-migracion-col">';
    html += '<div class="opesp-migracion-col-head"><strong>Destino: Formulario semestral 2024-II</strong></div>';
    html += '<div class="opesp-migracion-col-body">';
    if (!targetItems.length) {
      html += '<div class="text-muted">No hay ítems destino.</div>';
    } else {
      targetItems.forEach(function (item) {
        html += '<div class="opesp-migracion-item">';
        html += '<div class="opesp-migracion-item-title">' + escapeHtml((item.orden || 0) + '. ' + (item.nombre || '')) + '</div>';
        html += '<div class="opesp-migracion-item-meta">Tipo: ' + escapeHtml(item.tipo || '') +
          ' | Fuente: ' + escapeHtml(item.legacy_field || 'sin mapeo') + '</div>';
        html += '<div class="opesp-migracion-item-value">' + textToHtml(item.valor || '') + '</div>';
        html += '</div>';
      });
    }
    html += '</div></div>';
    html += '</div>';

    detailContainer.innerHTML = html;
    scheduleStabilizeLayout();
    setActiveMigrationButton(data.id_py || 0);
    updateMigrationSummary(data.summary || null);

    if (pushUrl) {
      var current = new URL(window.location.href);
      current.searchParams.set('id_py', String(data.id_py || 0));
      current.searchParams.set('tipo_resp', 'migracion_2024ii');
      current.searchParams.delete('id_respuesta');
      current.searchParams.delete('id_periodo');
      window.history.replaceState({}, '', current.toString());
    }
  }

  function openFormSelection(forms, idPy) {
    if (!selectorSelect || !selectorApplyBtn) {
      return;
    }
    pendingFormSelectProject = Number(idPy || 0);
    selectorSelect.innerHTML = '<option value="">Seleccione...</option>';
    forms.forEach(function (f) {
      var opt = document.createElement('option');
      opt.value = String(f.id_formulario || 0);
      var suffix = [];
      if (Number(f.formulario_activo || 0) === 1) suffix.push('Formulario activo');
      if (Number(f.cronograma_activo || 0) === 1) suffix.push('Cronograma activo');
      var info = suffix.length ? ' (' + suffix.join(', ') + ')' : '';
      opt.textContent = (f.nombre_formulario || 'Formulario') + ' [ID ' + (f.id_formulario || 0) + ']' + info;
      selectorSelect.appendChild(opt);
    });
    showModal(selectorModal);
  }

  function openMigrationPreview(idPy, formId, pushUrl) {
    var projectId = parseInt(idPy, 10);
    if (!projectId || projectId <= 0 || !detailContainer) {
      return;
    }

    if (detailPlaceholder) {
      detailPlaceholder.classList.add('d-none');
    }
    detailContainer.classList.remove('d-none');
    detailContainer.innerHTML = '<p class="text-center text-muted my-4">Cargando vista de migración 2024-II...</p>';

    var url = '../op_especiales/migracion_2024ii.php?action=preview&id_py=' + encodeURIComponent(projectId);
    if (formId && Number(formId) > 0) {
      url += '&id_formulario=' + encodeURIComponent(formId);
    }

    requestJson(url).then(function (data) {
      if (!data || data.ok !== true) {
        detailContainer.innerHTML = '<div class="alert alert-warning mb-0">' + escapeHtml((data && data.message) || 'No se pudo cargar la migración.') + '</div>';
        updateMigrationSummary(data && data.summary ? data.summary : null);
        return;
      }

      if (Number(data.requires_form_selection || 0) === 1) {
        detailContainer.innerHTML = '<div class="alert alert-info mb-0">Seleccione el formulario destino para continuar con la migración.</div>';
        openFormSelection(Array.isArray(data.forms) ? data.forms : [], projectId);
        updateMigrationSummary(data.summary || null);
        return;
      }

      renderMigrationPreview(data, pushUrl);
    }).catch(function () {
      detailContainer.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar la vista de migración.</div>';
    });
  }

  function finalizeProgress(success, message, steps, summary) {
    progressTimers.forEach(function (id) { clearTimeout(id); });
    progressTimers = [];
    if (Array.isArray(steps)) {
      steps.forEach(function (line) {
        appendProgressLine(line, success ? 'ok' : '');
      });
    }
    if (success) {
      setProgress(100, message || 'Migración completada.');
      appendProgressLine('Migración completada con aprobación total.', 'ok');
      if (progressBar) {
        progressBar.classList.remove('progress-bar-animated');
      }
    } else {
      setProgress(100, message || 'Migración cancelada por error.');
      appendProgressLine(message || 'Migración cancelada por error.', 'error');
      if (progressBar) {
        progressBar.classList.remove('progress-bar-animated');
        progressBar.classList.add('bg-danger');
      }
    }
    if (progressCloseBtn) {
      progressCloseBtn.disabled = false;
    }
    updateMigrationSummary(summary || null);
  }

  function executeMigration(payload) {
    if (!payload) {
      return;
    }

    resetProgressModal();
    if (progressBar) {
      progressBar.classList.remove('bg-danger');
    }
    showModal(runModal);
    appendProgressLine('Iniciando migración para el proyecto ' + payload.id_py + '...');
    setProgress(3, 'Iniciando...');
    simulateProgressWhileRunning();

    var body = new URLSearchParams();
    body.set('action', 'execute');
    body.set('id_py', String(payload.id_py));
    body.set('id_formulario', String(payload.id_formulario));
    body.set('force_replace', String(payload.force_replace ? 1 : 0));

    requestJson('../op_especiales/migracion_2024ii.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
      body: body.toString()
    }).then(function (data) {
      if (!data || data.ok !== true) {
        finalizeProgress(false, (data && data.message) || 'No se pudo completar la migración.', data && data.steps ? data.steps : null, data && data.summary ? data.summary : null);
        return;
      }
      finalizeProgress(true, data.message || 'Migración finalizada.', data.steps || [], data.summary || null);
      openMigrationPreview(payload.id_py, payload.id_formulario, false);
      refreshTableDataAfterMigration(payload.id_py);
    }).catch(function () {
      finalizeProgress(false, 'Error de red o respuesta inválida durante la migración.');
    });
  }

  function loadProyecto(idPy, pushUrl) {
    var projectId = parseInt(idPy, 10);
    if (!projectId || projectId <= 0 || !detailContainer) {
      return;
    }
    if (detailPlaceholder) {
      detailPlaceholder.classList.add('d-none');
    }
    detailContainer.classList.remove('d-none');
    detailContainer.innerHTML = '<p class="text-center text-muted my-4">Cargando datos del proyecto...</p>';

    function handleLoaded(html) {
      detailContainer.innerHTML = html;
      scheduleStabilizeLayout();
      setActiveProjectButton(projectId);
      if (pushUrl) {
        var current = new URL(window.location.href);
        current.searchParams.set('id_py', String(projectId));
        current.searchParams.set('tipo_resp', 'proyecto');
        current.searchParams.delete('id_respuesta');
        current.searchParams.delete('id_periodo');
        window.history.replaceState({}, '', current.toString());
      }
    }

    function handleError() {
      detailContainer.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar la información del proyecto.</div>';
      clearActiveButtons();
    }

    if (window.jQuery) {
      window.jQuery.get('../comite_facultad/calificacion/presentacion.php', { id_py: projectId }, function (html) {
        handleLoaded(html);
      }, 'html').fail(handleError);
      return;
    }

    fetch('../comite_facultad/calificacion/presentacion.php?id_py=' + encodeURIComponent(projectId))
      .then(function (r) { return r.text(); })
      .then(function (html) { handleLoaded(html); })
      .catch(handleError);
  }

  function loadInforme(idPy, idRespuesta, idPeriodo, tipo, pushUrl) {
    var projectId = parseInt(idPy, 10);
    var responseId = parseInt(idRespuesta, 10);
    var periodId = parseInt(idPeriodo, 10);
    var responseType = (tipo || '').toLowerCase();

    if (!projectId || projectId <= 0 || !responseId || responseId <= 0 || !detailContainer) {
      return;
    }
    if (detailPlaceholder) {
      detailPlaceholder.classList.add('d-none');
    }
    detailContainer.classList.remove('d-none');
    detailContainer.innerHTML = '<p class="text-center text-muted my-4">Cargando informe...</p>';

    var params = { id: projectId, id_respuesta: responseId };
    if (responseType === 'semestral' && periodId > 0) {
      params.semestral = periodId;
    }

    function handleLoadedHtml(html) {
      detailContainer.innerHTML = html;
      scheduleStabilizeLayout();
      setActiveInformeButton(responseId);
      if (pushUrl) {
        var current = new URL(window.location.href);
        current.searchParams.set('id_py', String(projectId));
        current.searchParams.set('id_respuesta', String(responseId));
        current.searchParams.set('tipo_resp', responseType || 'otros');
        if (responseType === 'semestral' && periodId > 0) {
          current.searchParams.set('id_periodo', String(periodId));
        } else {
          current.searchParams.delete('id_periodo');
        }
        window.history.replaceState({}, '', current.toString());
      }
    }

    function handleError() {
      detailContainer.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar el informe.</div>';
      clearActiveButtons();
    }

    if (window.jQuery) {
      window.jQuery.get('../informe_semestral/ver_informe.php', params, function (html) {
        handleLoadedHtml(html);
      }, 'html').fail(handleError);
      return;
    }

    var query = new URLSearchParams(params).toString();
    fetch('../informe_semestral/ver_informe.php?' + query)
      .then(function (r) { return r.text(); })
      .then(function (html) { handleLoadedHtml(html); })
      .catch(handleError);
  }

  function loadSemestralLegacy(idPy, pushUrl) {
    var projectId = parseInt(idPy, 10);
    if (!projectId || projectId <= 0 || !detailContainer) {
      return;
    }
    if (detailPlaceholder) {
      detailPlaceholder.classList.add('d-none');
    }
    detailContainer.classList.remove('d-none');
    detailContainer.innerHTML = '<p class="text-center text-muted my-4">Cargando informe semestral legacy...</p>';

    function handleLoaded(html) {
      detailContainer.innerHTML = html;
      scheduleStabilizeLayout();
      setActiveSemestralLegacyButton(projectId);
      if (pushUrl) {
        var current = new URL(window.location.href);
        current.searchParams.set('id_py', String(projectId));
        current.searchParams.set('tipo_resp', 'semestral_legacy');
        current.searchParams.delete('id_respuesta');
        current.searchParams.delete('id_periodo');
        window.history.replaceState({}, '', current.toString());
      }
    }

    function handleError() {
      detailContainer.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar el informe semestral legacy.</div>';
      clearActiveButtons();
    }

    if (window.jQuery) {
      window.jQuery.get('../comite_facultad/calificacion/semestral.php', { id_py: projectId }, function (html) {
        handleLoaded(html);
      }, 'html').fail(handleError);
      return;
    }

    fetch('../comite_facultad/calificacion/semestral.php?id_py=' + encodeURIComponent(projectId))
      .then(function (r) { return r.text(); })
      .then(function (html) { handleLoaded(html); })
      .catch(handleError);
  }

  document.addEventListener('click', function (e) {
    var actionBtn = e.target.closest('.opesp-btn-formulario');
    if (actionBtn) {
      e.preventDefault();
      var actionType = actionBtn.getAttribute('data-action') || 'informe';
      if (actionType === 'migracion_2024ii') {
        openMigrationPreview(actionBtn.getAttribute('data-id-py'), 0, true);
      } else if (actionType === 'proyecto') {
        loadProyecto(actionBtn.getAttribute('data-id-py'), true);
      } else if (actionType === 'semestral_legacy') {
        loadSemestralLegacy(actionBtn.getAttribute('data-id-py'), true);
      } else {
        loadInforme(
          actionBtn.getAttribute('data-id-py'),
          actionBtn.getAttribute('data-id-respuesta'),
          actionBtn.getAttribute('data-id-periodo'),
          actionBtn.getAttribute('data-tipo'),
          true
        );
      }
      return;
    }

    var runBtn = e.target.closest('#opesp-run-migration-btn');
    if (runBtn) {
      e.preventDefault();
      var payload = {
        id_py: parseInt(runBtn.getAttribute('data-id-py') || '0', 10),
        id_formulario: parseInt(runBtn.getAttribute('data-id-formulario') || '0', 10),
        force_replace: 0
      };
      var hasExisting = parseInt(runBtn.getAttribute('data-has-existing') || '0', 10) === 1;
      if (hasExisting) {
        pendingExecutePayload = payload;
        showModal(confirmModal);
      } else {
        executeMigration(payload);
      }
      return;
    }

    var row = e.target.closest('.opesp-fila-toggle');
    if (!row || e.target.closest('a, button, .btn')) {
      return;
    }
    var id = row.getAttribute('data-id');
    if (!id) {
      return;
    }
    var detailRow = document.querySelector('.opesp-fila-extra-' + id);
    if (!detailRow) {
      return;
    }
    var shouldShow = detailRow.style.display === 'none' || detailRow.style.display === '';
    detailRow.style.display = shouldShow ? 'table-row' : 'none';
  });

  if (confirmBtn) {
    confirmBtn.addEventListener('click', function () {
      hideModal(confirmModal);
      if (pendingExecutePayload) {
        pendingExecutePayload.force_replace = 1;
        executeMigration(pendingExecutePayload);
      }
      pendingExecutePayload = null;
    });
  }

  if (selectorApplyBtn) {
    selectorApplyBtn.addEventListener('click', function () {
      if (!selectorSelect) {
        return;
      }
      var formId = parseInt(selectorSelect.value || '0', 10);
      if (!formId || formId <= 0) {
        return;
      }
      hideModal(selectorModal);
      openMigrationPreview(pendingFormSelectProject, formId, true);
    });
  }

  if (progressCloseBtn) {
    progressCloseBtn.addEventListener('click', function () {
      hideModal(runModal);
    });
  }

  document.addEventListener('click', function (e) {
    var navLink = e.target.closest('#opesp-informe-detalle a[href^="#"]');
    if (!navLink || !detailContainer) {
      return;
    }

    var targetId = navLink.getAttribute('href');
    if (!targetId || targetId === '#') {
      return;
    }

    var target = detailContainer.querySelector(targetId);
    if (!target) {
      return;
    }

    e.preventDefault();
    var scrollRoot = findScrollableAncestor(target);
    var rootRect = scrollRoot.getBoundingClientRect ? scrollRoot.getBoundingClientRect() : { top: 0 };
    var targetRect = target.getBoundingClientRect ? target.getBoundingClientRect() : { top: 0 };
    var nextTop = (scrollRoot.scrollTop || 0) + (targetRect.top - rootRect.top) - 10;

    if (typeof scrollRoot.scrollTo === 'function') {
      scrollRoot.scrollTo({ top: nextTop, left: 0, behavior: 'smooth' });
    } else {
      scrollRoot.scrollTop = nextTop;
    }
  });

  window.addEventListener('resize', function () {
    clearTimeout(resizeTimer);
    resizeTimer = setTimeout(scheduleStabilizeLayout, 150);
  });

  if (detailContainer) {
    attachMutationStabilizer();

    var initialPy = parseInt(detailContainer.getAttribute('data-selected-id-py') || '0', 10);
    var initialResp = parseInt(detailContainer.getAttribute('data-selected-id-respuesta') || '0', 10);
    var initialPeriodo = parseInt(detailContainer.getAttribute('data-selected-id-periodo') || '0', 10);
    var initialTipo = (detailContainer.getAttribute('data-selected-tipo') || '').toLowerCase();

    if (initialPy > 0 && initialResp > 0) {
      loadInforme(initialPy, initialResp, initialPeriodo, initialTipo, false);
    } else if (initialPy > 0 && initialTipo === 'proyecto') {
      loadProyecto(initialPy, false);
    } else if (initialPy > 0 && initialTipo === 'semestral_legacy') {
      loadSemestralLegacy(initialPy, false);
    } else if (initialPy > 0 && initialTipo === 'migracion_2024ii') {
      openMigrationPreview(initialPy, 0, false);
    } else {
      scheduleStabilizeLayout();
    }
  }
})();
