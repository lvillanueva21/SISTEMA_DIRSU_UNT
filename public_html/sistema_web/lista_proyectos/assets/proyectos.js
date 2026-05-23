(function () {
  var currentEvalContext = { responseId: null, projectId: null, formName: '' };

  function hasJquery() {
    return !!(window.jQuery && window.jQuery.fn);
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

  function formatDateText(v) {
    var txt = String(v || '').trim();
    return txt === '' ? '-' : txt;
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
        badgeText = 'En espera';
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
      var enabled = !!a.enabled;
      var title = esc(a.reason || '');
      var classes = 'btn btn-warning btn-sm prj-btn-eval-action';
      if (!enabled) {
        classes += ' prj-eval-action-disabled';
      }

      html += '<button type="button" class="' + classes + '" data-action="' + esc(a.key || '') + '" data-enabled="' + (enabled ? '1' : '0') + '" title="' + title + '">'
        + esc(a.label || 'Acción') + '</button>';
    }
    wrap.innerHTML = html;
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
      html += '<button type="button" class="btn btn-outline-danger btn-sm prj-btn-show-obs" title="' + esc(flow.obs_reason || 'Ver observaciones') + '">'
        + 'Ver observaciones</button>';
    } else {
      html += '<button type="button" class="btn btn-outline-secondary btn-sm prj-btn-show-obs prj-eval-action-disabled" data-enabled="0" title="' + esc(flow.obs_reason || 'No hay observaciones activas') + '">'
        + 'Ver observaciones</button>';
    }

    if (flow.reason) {
      html += '<div class="prj-coord-note mt-2">' + esc(flow.reason) + '</div>';
    }

    wrap.innerHTML = html;
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
      if (oldScript.src) {
        newScript.src = oldScript.src;
      } else {
        newScript.text = oldScript.text || oldScript.textContent || oldScript.innerHTML || '';
      }
      document.body.appendChild(newScript);
      document.body.removeChild(newScript);
    }
  }

  function loadEvalActionForm(actionKey) {
    var wrap = document.getElementById('prjEvalActions');
    if (!wrap || !actionKey || !currentEvalContext.responseId) return;

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
      }).fail(function () {
        wrap.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el formulario de calificación.</div>';
      });
      return;
    }

    wrap.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
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

    if (window.confirm && !window.confirm(confirmText)) {
      return;
    }

    showEvalAlert('info', 'Procesando acción...');

    if (window.jQuery && window.jQuery.ajax) {
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

        window.setTimeout(function () {
          openEvaluacionModal(responseId, projectId);
        }, 450);
      }).fail(function (xhr) {
        var msgFail = 'No se pudo completar la acción.';
        if (xhr && xhr.responseJSON && (xhr.responseJSON.msg || xhr.responseJSON.error)) {
          msgFail = xhr.responseJSON.msg || xhr.responseJSON.error;
        }
        showEvalAlert('danger', msgFail);
      });
      return;
    }

    showEvalAlert('danger', 'No hay motor AJAX disponible.');
  }

  function renderObsDetailBlock(title, detail, includeTable) {
    var html = '<div class="card mb-2"><div class="card-body p-2">';
    html += '<h6 class="mb-2">' + esc(title) + '</h6>';
    html += '<div><strong>Oficina:</strong> ' + esc(detail.oficina_nom || '-') + ' (' + esc(detail.oficina_cod || '-') + ')</div>';
    html += '<div><strong>Observado:</strong> ' + esc(detail.obs_at || '-') + '</div>';
    html += '<div><strong>Fecha máxima de subsanación:</strong> ' + esc(detail.limite || '-') + '</div>';
    html += '<div><strong>Días:</strong> ' + esc(detail.dias || '-') + '</div>';

    if (includeTable && Array.isArray(detail.aspectos) && detail.aspectos.length > 0) {
      html += '<div class="table-responsive mt-2"><table class="table table-sm table-bordered prj-obs-table mb-0">';
      html += '<thead><tr><th>Aspecto</th><th>Nota</th><th>Observación</th></tr></thead><tbody>';
      for (var i = 0; i < detail.aspectos.length; i++) {
        var ax = detail.aspectos[i] || {};
        html += '<tr>'
          + '<td>' + esc(ax.aspecto || '-') + '</td>'
          + '<td class="text-center">' + esc(ax.notaTx || ax.nota || '-') + '</td>'
          + '<td>' + esc(ax.obs || 'Sin observación') + '</td>'
          + '</tr>';
      }
      html += '</tbody></table></div>';
      if (typeof detail.total !== 'undefined' && detail.total !== null) {
        html += '<div class="mt-2"><strong>Puntaje total:</strong> ' + esc(detail.total) + ' / 20</div>';
      }
    } else {
      html += '<div class="mt-2"><strong>Observación:</strong> ' + esc(detail.obs_text || 'Sin observación') + '</div>';
    }

    html += '</div></div>';
    return html;
  }

  function openObservacionesModal(projectId) {
    var body = document.getElementById('prjObsDetalleBody');
    if (!body || !projectId) return;
    body.innerHTML = '<div class="text-muted">Cargando observaciones...</div>';

    if (window.jQuery && window.jQuery.fn) {
      window.jQuery('#modalObservacionesDetalle').modal('show');
    }

    if (!(window.jQuery && window.jQuery.ajax)) {
      body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
      return;
    }

    window.jQuery.ajax({
      url: '../evaluacion/api/observaciones_estado.php',
      method: 'GET',
      dataType: 'json',
      data: { id_py: projectId },
      cache: false
    }).done(function (json) {
      if (!json || json.ok !== true) {
        body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar observaciones.</div>';
        return;
      }

      var hasC = !!json.cotejo;
      var hasR = !!json.rubrica;
      if (!hasC && !hasR) {
        body.innerHTML = '<div class="alert alert-warning mb-0">No has recibido observaciones por el momento.</div>';
        return;
      }

      var html = '';
      if (hasC) {
        html += renderObsDetailBlock('Observación de cotejo', json.cotejo, false);
      }
      if (hasR) {
        html += renderObsDetailBlock('Observación de rúbrica', json.rubrica, true);
      }
      body.innerHTML = html;
    }).fail(function () {
      body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar observaciones.</div>';
    });
  }

  function syncModalInformeLayout() {
    var body = document.getElementById('prjInformeDetalleBody');
    if (!body) return;
    var rows = body.querySelectorAll('.rsu-split-row');
    for (var i = 0; i < rows.length; i++) {
      rows[i].style.display = 'flex';
    }
  }

  function openInformeModal(projectId, responseId) {
    var body = document.getElementById('prjInformeDetalleBody');
    if (!body) return;
    body.innerHTML = '<div class="text-muted">Cargando...</div>';

    if (window.jQuery && window.jQuery.fn) {
      window.jQuery('#modalInformeDetalle').modal('show');
    }

    if (window.jQuery && window.jQuery.get) {
      window.jQuery.get('../informe_semestral/ver_informe.php', {
        id: projectId,
        id_respuesta: responseId || ''
      }, function (html) {
        body.innerHTML = html;
        syncModalInformeLayout();
        if (window.requestAnimationFrame) {
          window.requestAnimationFrame(syncModalInformeLayout);
        }
      }, 'html').fail(function () {
        body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar el informe.</div>';
      });
      return;
    }

    body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
  }

  function attachPresentacionFiles(projectId) {
    var contenedor = document.getElementById('contenedor-archivos');
    if (!contenedor || !window.fetch) return;

    fetch('../includes/api_dirsu/presentacion_archivos_api.php?id_py=' + encodeURIComponent(projectId))
      .then(function (response) { return response.json(); })
      .then(function (data) {
        var nombres = {
          lista_docentes: '1. Lista de Docentes',
          lista_alumnos: '2. Lista de Alumnos',
          diagrama: '3. Diagrama',
          compromiso: '4. Compromiso Etico',
          carta: '5. Carta de Intencion'
        };

        var idsNavegacion = {
          lista_docentes: 'anitem1',
          lista_alumnos: 'anitem2',
          diagrama: 'anitem3',
          compromiso: 'anitem4',
          carta: 'anitem5'
        };

        for (var clave in data) {
          if (!Object.prototype.hasOwnProperty.call(data, clave)) continue;

          var archivos = data[clave];
          var seccion = document.createElement('div');
          seccion.className = 'mb-3';
          seccion.id = idsNavegacion[clave] || '';

          var titulo = '<strong>' + esc(nombres[clave] || clave) + '</strong><br>';

          if (!archivos || !archivos.length) {
            seccion.innerHTML = titulo + '<span class="text-danger">No hay archivo</span>';
          } else {
            var lista = '';
            for (var i = 0; i < archivos.length; i++) {
              var nombre = String(archivos[i] || '');
              var partes = nombre.split('.');
              var ext = (partes.length > 1 ? partes[partes.length - 1] : '').toLowerCase();
              var isPDF = ext === 'pdf';
              var isExcel = ext === 'xls' || ext === 'xlsx';
              var iconClass = isPDF ? 'fas fa-file-pdf text-danger' : (isExcel ? 'fas fa-file-excel text-success' : 'fas fa-file-alt text-secondary');
              var claseBoton = isPDF ? 'btn-outline-danger' : (isExcel ? 'btn-outline-success' : 'btn-outline-secondary');
              var url = '../includes/api_dirsu/presentacion_descarga_api.php?categoria=' + encodeURIComponent(clave) + '&id_py=' + encodeURIComponent(projectId) + '&archivo=' + encodeURIComponent(nombre) + (isPDF ? '&ver=1' : '');

              lista += ''
                + '<div class="archivo-card d-flex align-items-center justify-content-between p-3 mb-2 border rounded shadow-sm bg-white">'
                + '  <div class="d-flex align-items-center" style="gap: 10px;">'
                + '    <i class="' + iconClass + '" style="font-size: 1.5rem;"></i>'
                + '    <div title="' + esc(nombre) + '" style="max-width: 300px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">' + esc(nombre) + '</div>'
                + '  </div>'
                + '  <a href="' + url + '" target="_blank" class="btn ' + claseBoton + ' btn-sm">Descargar</a>'
                + '</div>';
            }
            seccion.innerHTML = titulo + lista;
          }

          contenedor.appendChild(seccion);
        }
      })
      .catch(function () {
        contenedor.innerHTML = "<div class='text-danger'>Error al cargar archivos.</div>";
      });
  }

  function openPresentacionModal(projectId) {
    var body = document.getElementById('prjPresentacionDetalleBody');
    if (!body) return;
    body.innerHTML = '<p class="text-center text-muted">Cargando datos del proyecto...</p>';

    if (window.jQuery && window.jQuery.fn) {
      window.jQuery('#modalPresentacionDetalle').modal('show');
    }

    if (window.jQuery && window.jQuery.get) {
      window.jQuery.get('../includes/api_dirsu/presentacion_modal_api.php', { id_py: projectId }, function (html) {
        body.innerHTML = html;
        attachPresentacionFiles(projectId);
      }, 'html').fail(function () {
        body.innerHTML = '<div class="alert alert-danger mb-0">No se pudo cargar la presentación del proyecto.</div>';
      });
      return;
    }

    body.innerHTML = '<div class="alert alert-danger mb-0">No hay motor AJAX disponible.</div>';
  }

  function openEvaluacionModal(responseId, projectId) {
    clearEvalAlert();
    var resumen = document.getElementById('prjEvalResumen');
    var timeline = document.getElementById('prjEvalTimeline');
    var actions = document.getElementById('prjEvalActions');
    var coord = document.getElementById('prjCoordActions');
    if (resumen) resumen.innerHTML = '<span class="text-muted">Cargando...</span>';
    if (timeline) timeline.innerHTML = '';
    if (actions) actions.innerHTML = '';
    if (coord) coord.innerHTML = '';

    if (window.jQuery && window.jQuery.fn) {
      window.jQuery('#modalEvaluacionDetalle').modal('show');
    }

    if (window.jQuery && window.jQuery.ajax) {
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
        renderCoordinatorActions(data.coordinator_flow || null);
      }).fail(function () {
        showEvalAlert('danger', 'No se pudo cargar evaluación.');
      });
      return;
    }

    showEvalAlert('danger', 'No hay motor AJAX disponible.');
  }

  function bindProgressButtonsJquery() {
    if (!hasJquery()) return false;

    var $doc = window.jQuery(document);

    $doc.off('click.prjInforme').on('click.prjInforme', '.prj-btn-informe', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      var projectId = window.jQuery(this).attr('data-project-id');
      if (responseId && projectId) {
        openInformeModal(projectId, responseId);
      }
    });

    $doc.off('click.prjPresentacion').on('click.prjPresentacion', '.prj-btn-presentacion', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var projectId = window.jQuery(this).attr('data-project-id');
      if (projectId) {
        openPresentacionModal(projectId);
      }
    });

    $doc.off('click.prjEval').on('click.prjEval', '.prj-btn-evaluacion', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var responseId = window.jQuery(this).attr('data-response-id');
      var projectId = window.jQuery(this).attr('data-project-id');
      if (responseId) {
        openEvaluacionModal(responseId, projectId);
      }
    });

    $doc.off('click.prjEvalPres').on('click.prjEvalPres', '.prj-eval-btn-pres', function (event) {
      event.preventDefault();
      event.stopPropagation();
      if (window.alert) {
        window.alert('La evaluación de presentación se habilitará en el siguiente sprint.');
      }
    });

    $doc.off('click.prjEvalAction').on('click.prjEvalAction', '.prj-btn-eval-action', function (event) {
      event.preventDefault();
      event.stopPropagation();

      var enabled = window.jQuery(this).attr('data-enabled') === '1';
      var reason = window.jQuery(this).attr('title') || '';
      var actionKey = window.jQuery(this).attr('data-action');
      if (actionKey) {
        if (!enabled) {
          showEvalAlert('warning', reason || 'Acción no disponible en este estado.');
          return;
        }
        loadEvalActionForm(actionKey);
      }
    });

    $doc.off('click.prjCoordAction').on('click.prjCoordAction', '.prj-btn-coord-action', function (event) {
      event.preventDefault();
      event.stopPropagation();

      var enabled = window.jQuery(this).attr('data-enabled') === '1';
      var reason = window.jQuery(this).attr('title') || '';
      var actionKey = window.jQuery(this).attr('data-action');
      if (!actionKey) return;
      if (!enabled) {
        showEvalAlert('warning', reason || 'Acción no disponible en este estado.');
        return;
      }
      postCoordinatorAction(actionKey);
    });

    $doc.off('click.prjShowObs').on('click.prjShowObs', '.prj-btn-show-obs', function (event) {
      event.preventDefault();
      event.stopPropagation();
      var enabled = window.jQuery(this).attr('data-enabled');
      if (enabled === '0') {
        showEvalAlert('warning', window.jQuery(this).attr('title') || 'No hay observaciones activas.');
        return;
      }
      if (currentEvalContext.projectId) {
        openObservacionesModal(currentEvalContext.projectId);
      }
    });

    return true;
  }

  function bindFilters() {
    var form = document.getElementById('prjFiltersForm');
    if (!form) return;

    var fac = document.getElementById('prjFacultad');
    var dep = document.getElementById('prjDepartamento');
    var cre = document.getElementById('prjCreacion');

    function submitForm() {
      if (form.requestSubmit) {
        form.requestSubmit();
      } else {
        form.submit();
      }
    }

    if (fac) {
      fac.addEventListener('change', function () {
        if (dep) {
          dep.value = '0';
        }
        submitForm();
      });
    }

    if (dep) {
      dep.addEventListener('change', function () {
        submitForm();
      });
    }

    if (cre) {
      cre.addEventListener('change', function () {
        submitForm();
      });
    }
  }

  function init() {
    bindRowToggle();
    bindProgressButtonsJquery();
    bindFilters();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', init);
  } else {
    init();
  }
})();
