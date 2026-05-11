(function () {
  var detailPlaceholder = document.getElementById('opesp-detail-placeholder');
  var detailContainer = document.getElementById('opesp-informe-detalle');
  var resizeTimer = null;
  var mutationObserver = null;

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

    mutationObserver.observe(detailContainer, {
      childList: true,
      subtree: true
    });
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

  function renderAttachments(idPy) {
    if (!detailContainer) {
      return;
    }

    var cont = detailContainer.querySelector('#contenedor-archivos');
    if (!cont) {
      return;
    }

    cont.innerHTML = '<div class="text-muted">Cargando anexos del proyecto...</div>';

    fetch('../comite_facultad/calificacion/gestion_archivos.php?id_py=' + encodeURIComponent(idPy))
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

        cont.innerHTML = '';

        Object.keys(label).forEach(function (cat) {
          var files = data && data[cat] ? data[cat] : null;
          var sec = document.createElement('div');
          sec.className = 'mb-3';
          sec.id = navId[cat] || '';

          var title = document.createElement('strong');
          title.textContent = label[cat];
          sec.appendChild(title);
          sec.appendChild(document.createElement('br'));

          if (!files || !files.length) {
            var emptyText = document.createElement('span');
            emptyText.className = 'text-danger';
            emptyText.textContent = 'No hay archivo';
            sec.appendChild(emptyText);
          } else {
            files.forEach(function (fileName) {
              var ext = String(fileName).split('.').pop().toLowerCase();
              var isPdf = ext === 'pdf';
              var isXls = ext === 'xls' || ext === 'xlsx';
              var icon = isPdf ? 'file-pdf text-danger' : (isXls ? 'file-excel text-success' : 'file-alt text-secondary');
              var btnClass = isPdf ? 'btn-outline-danger' : (isXls ? 'btn-outline-success' : 'btn-outline-secondary');
              var url = '../comite_facultad/calificacion/descarga_archivos.php?categoria=' + encodeURIComponent(cat)
                + '&id_py=' + encodeURIComponent(idPy)
                + '&archivo=' + encodeURIComponent(fileName)
                + (isPdf ? '&ver=1' : '');

              var card = document.createElement('div');
              card.className = 'archivo-card d-flex align-items-center justify-content-between p-3 mb-2 border rounded shadow-sm bg-white';

              var left = document.createElement('div');
              left.className = 'd-flex align-items-center';
              left.style.gap = '10px';

              var iconEl = document.createElement('i');
              iconEl.className = 'fas fa-' + icon;
              iconEl.style.fontSize = '1.5rem';
              left.appendChild(iconEl);

              var fileLabel = document.createElement('div');
              fileLabel.title = fileName;
              fileLabel.style.maxWidth = '300px';
              fileLabel.style.whiteSpace = 'nowrap';
              fileLabel.style.overflow = 'hidden';
              fileLabel.style.textOverflow = 'ellipsis';
              fileLabel.textContent = fileName;
              left.appendChild(fileLabel);

              var link = document.createElement('a');
              link.href = url;
              link.target = '_blank';
              link.className = 'btn ' + btnClass + ' btn-sm';
              link.textContent = 'Descargar';

              card.appendChild(left);
              card.appendChild(link);
              sec.appendChild(card);
            });
          }

          cont.appendChild(sec);
        });
      })
      .catch(function () {
        cont.innerHTML = '<div class="text-danger">Error al cargar archivos.</div>';
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
      renderAttachments(projectId);
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
      detailContainer.innerHTML = '<div class="alert alert-warning mb-0">No se pudo cargar la informacion del proyecto.</div>';
      clearActiveButtons();
    }

    if (window.jQuery) {
      jQuery.get('../comite_facultad/calificacion/presentacion.php', { id_py: projectId }, function (html) {
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

    var params = {
      id: projectId,
      id_respuesta: responseId
    };

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
      jQuery.get('../informe_semestral/ver_informe.php', params, function (html) {
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
      jQuery.get('../comite_facultad/calificacion/semestral.php', { id_py: projectId }, function (html) {
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
      if (actionType === 'proyecto') {
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
    } else {
      scheduleStabilizeLayout();
    }
  }
})();
