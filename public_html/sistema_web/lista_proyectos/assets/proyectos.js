(function () {
  function hasJquery() {
    return !!(window.jQuery && window.jQuery.fn);
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
              node.classList &&
              (
                node.classList.contains('prj-deliver-btn') ||
                node.classList.contains('prj-eval-btn') ||
                node.classList.contains('prj-btn-informe') ||
                node.classList.contains('prj-btn-evaluacion')
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

  function esc(value) {
    var str = String(value == null ? '' : value);
    return str
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function formatDateText(v) {
    var txt = String(v || '').trim();
    return txt === '' ? '—' : txt;
  }

  function renderEvalTimeline(items) {
    var wrap = document.getElementById('prjEvalTimeline');
    if (!wrap) return;

    if (!Array.isArray(items) || items.length === 0) {
      wrap.innerHTML = '<div class="text-muted">Sin timeline de evaluación.</div>';
      return;
    }

    var html = '';
    for (var i = 0; i < items.length; i++) {
      var it = items[i] || {};
      var estado = String(it.estado || 'pendiente');
      var badgeClass = 'badge-secondary';
      var badgeText = 'Pendiente';
      if (estado === 'en_espera') {
        badgeClass = 'badge-primary';
        badgeText = 'En Espera';
      } else if (estado === 'observado') {
        badgeClass = 'badge-danger';
        badgeText = 'Observado';
      } else if (estado === 'aprobado') {
        badgeClass = 'badge-success';
        badgeText = 'Aprobado';
      } else if (estado === 'cerrado') {
        badgeClass = 'badge-success';
        badgeText = 'Cerrado';
      }

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

  function renderEvalActions(actions) {
    var wrap = document.getElementById('prjEvalActions');
    if (!wrap) return;

    if (!Array.isArray(actions) || actions.length === 0) {
      wrap.innerHTML = '<div class="text-muted">Este rol no tiene acciones de calificación.</div>';
      return;
    }

    var html = '';
    for (var i = 0; i < actions.length; i++) {
      var a = actions[i] || {};
      var disabled = a.enabled ? '' : ' disabled';
      var title = esc(a.reason || '');
      html += '<button type="button" class="btn btn-warning btn-sm" title="' + title + '"' + disabled + '>'
        + esc(a.label || 'Acción') + '</button>';
    }
    wrap.innerHTML = html;
  }

  function openInformeModal(responseId) {
    var body = document.getElementById('prjInformeDetalleBody');
    if (!body) return;
    body.innerHTML = '<div class="text-muted">Cargando...</div>';
    if (window.jQuery && window.jQuery.fn) {
      jQuery('#modalInformeDetalle').modal('show');
    }
    if (window.jQuery && window.jQuery.ajax) {
      window.jQuery.ajax({
        url: '../lista_proyectos/api_informe_detalle.php',
        method: 'GET',
        data: { response_id: responseId },
        cache: false
      }).done(function (html) {
        body.innerHTML = html;
      }).fail(function () {
        body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el informe.</div>';
      });
      return;
    }
    body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
  }

  function openEvaluacionModal(responseId) {
    clearEvalAlert();
    var resumen = document.getElementById('prjEvalResumen');
    var timeline = document.getElementById('prjEvalTimeline');
    var actions = document.getElementById('prjEvalActions');
    if (resumen) resumen.innerHTML = '<span class="text-muted">Cargando...</span>';
    if (timeline) timeline.innerHTML = '';
    if (actions) actions.innerHTML = '';

    if (window.jQuery && window.jQuery.fn) {
      jQuery('#modalEvaluacionDetalle').modal('show');
    }
    if (window.jQuery && window.jQuery.ajax) {
      window.jQuery.ajax({
        url: '../lista_proyectos/api_evaluacion_detalle.php',
        method: 'GET',
        dataType: 'json',
        data: { response_id: responseId },
        cache: false
      }).done(function (json) {
        if (!json || !json.ok) {
          showEvalAlert('danger', (json && json.msg) ? json.msg : 'No se pudo cargar evaluación.');
          return;
        }
        var data = json.data || {};
        if (resumen) {
          var badgeClass = (data.eval_badge && data.eval_badge.class) ? data.eval_badge.class : 'badge badge-secondary';
          var badgeText = (data.eval_badge && data.eval_badge.text) ? data.eval_badge.text : 'Sin ruta';
          resumen.innerHTML = ''
            + '<div><strong>Proyecto:</strong> ' + esc(data.titulo_proyecto || 'Sin título') + '</div>'
            + '<div><strong>Periodo:</strong> ' + esc(data.periodo || 'No definido')
            + ' | <strong>Tipo:</strong> ' + esc(data.tipo_informe || 'Informe')
            + ' | <span class="' + esc(badgeClass) + '">' + esc(badgeText) + '</span></div>';
        }
        renderEvalTimeline(data.timeline || []);
        renderEvalActions(data.actions || []);
      }).fail(function () {
        showEvalAlert('danger', 'No se pudo cargar evaluación.');
      });
      return;
    }
    showEvalAlert('danger', 'No hay motor AJAX disponible.');
  }

  function bindProgressButtonsJquery() {
    if (!hasJquery()) {
      return false;
    }
    var $doc = window.jQuery(document);
    $doc.off('click.prjInforme').on('click.prjInforme', '.prj-btn-informe', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      if (responseId) {
        openInformeModal(responseId);
      }
    });

    $doc.off('click.prjEval').on('click.prjEval', '.prj-btn-evaluacion', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      if (responseId) {
        openEvaluacionModal(responseId);
      }
    });
    return true;
  }

  function bindProgressButtonsNativeFallback() {
    document.addEventListener('click', function (event) {
      var target = event.target || event.srcElement;
      if (!target) return;

      var btnInfo = target;
      while (btnInfo && btnInfo.nodeType === 1 && !btnInfo.classList.contains('prj-btn-informe')) {
        btnInfo = btnInfo.parentNode;
      }
      if (btnInfo && btnInfo.classList && btnInfo.classList.contains('prj-btn-informe')) {
        event.preventDefault();
        event.stopPropagation();
        var responseId = btnInfo.getAttribute('data-response-id');
        if (responseId) {
          openInformeModal(responseId);
        }
        return;
      }

      var btnEval = target;
      while (btnEval && btnEval.nodeType === 1 && !btnEval.classList.contains('prj-btn-evaluacion')) {
        btnEval = btnEval.parentNode;
      }
      if (btnEval && btnEval.classList && btnEval.classList.contains('prj-btn-evaluacion')) {
        event.preventDefault();
        event.stopPropagation();
        var responseEvalId = btnEval.getAttribute('data-response-id');
        if (responseEvalId) {
          openEvaluacionModal(responseEvalId);
        }
      }
    }, false);
  }

  function bindProgressButtons() {
    if (!bindProgressButtonsJquery()) {
      bindProgressButtonsNativeFallback();
    }
  }

  function init() {
    bindRowToggle();
    bindProgressButtons();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
