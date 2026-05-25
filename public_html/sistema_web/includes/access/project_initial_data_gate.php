<?php

if (!function_exists('rsu_initial_data_value')) {
    function rsu_initial_data_value($row, $key, $default = '')
    {
        if (!is_array($row) || !isset($row[$key])) {
            return $default;
        }
        return $row[$key];
    }
}

if (!function_exists('rsu_initial_data_get_flash')) {
    function rsu_initial_data_get_flash()
    {
        $msg = isset($_SESSION['rsu_initial_data_msg']) ? trim((string)$_SESSION['rsu_initial_data_msg']) : '';
        $type = isset($_SESSION['rsu_initial_data_msg_type']) ? trim((string)$_SESSION['rsu_initial_data_msg_type']) : 'info';
        if ($type === '') {
            $type = 'info';
        }

        unset($_SESSION['rsu_initial_data_msg'], $_SESSION['rsu_initial_data_msg_type']);
        return array('msg' => $msg, 'type' => $type);
    }
}

if (!function_exists('rsu_project_initial_data_get_status')) {
    function rsu_project_initial_data_get_status($conexion, $id_py)
    {
        $id_py = (int)$id_py;
        if ($id_py <= 0 || !($conexion instanceof mysqli)) {
            return array(
                'ok' => false,
                'id_py' => $id_py,
                'needs_block' => false
            );
        }

        $sql = "SELECT p2, fecha_inicio, fecha_fin FROM proyectos WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conexion, $sql);
        if (!$stmt) {
            return array(
                'ok' => false,
                'id_py' => $id_py,
                'needs_block' => false
            );
        }

        mysqli_stmt_bind_param($stmt, 'i', $id_py);
        mysqli_stmt_execute($stmt);
        $res = mysqli_stmt_get_result($stmt);
        $row = ($res instanceof mysqli_result) ? mysqli_fetch_assoc($res) : null;
        if ($res instanceof mysqli_result) {
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);

        if (!is_array($row)) {
            return array(
                'ok' => false,
                'id_py' => $id_py,
                'needs_block' => false
            );
        }

        $titulo = trim((string)rsu_initial_data_value($row, 'p2', ''));
        $fecha_inicio = trim((string)rsu_initial_data_value($row, 'fecha_inicio', ''));
        $fecha_fin = trim((string)rsu_initial_data_value($row, 'fecha_fin', ''));

        $title_missing = ($titulo === '');
        $dates_missing = ($fecha_inicio === '' || $fecha_fin === '');
        $needs_block = ($title_missing || $dates_missing);

        return array(
            'ok' => true,
            'id_py' => $id_py,
            'titulo' => $titulo,
            'fecha_inicio' => $fecha_inicio,
            'fecha_fin' => $fecha_fin,
            'title_missing' => $title_missing,
            'dates_missing' => $dates_missing,
            'needs_block' => $needs_block
        );
    }
}

if (!function_exists('rsu_initial_data_current_uri')) {
    function rsu_initial_data_current_uri($fallback)
    {
        $fallback = trim((string)$fallback);
        if ($fallback === '') {
            $fallback = '../inicio.php';
        }

        $uri = isset($_SERVER['REQUEST_URI']) ? trim((string)$_SERVER['REQUEST_URI']) : '';
        if ($uri === '' || strpos($uri, '://') !== false || strpos($uri, '..') !== false) {
            return $fallback;
        }
        if ($uri[0] !== '/') {
            return $fallback;
        }
        return $uri;
    }
}

if (!function_exists('rsu_project_initial_data_render_modal')) {
    function rsu_project_initial_data_render_modal($status, $opts = array())
    {
        if (!is_array($status) || empty($status['needs_block'])) {
            return;
        }

        $opts = is_array($opts) ? $opts : array();
        $modal_id = isset($opts['modal_id']) ? trim((string)$opts['modal_id']) : 'rsuModalDatosIniciales';
        if ($modal_id === '') {
            $modal_id = 'rsuModalDatosIniciales';
        }

        $save_url = isset($opts['save_url']) ? trim((string)$opts['save_url']) : '../componentes/proyecto/guardar_datos_iniciales.php';
        $preview_api_url = isset($opts['preview_api_url']) ? trim((string)$opts['preview_api_url']) : '../includes/api_dirsu/api.php';
        $fallback_return = isset($opts['fallback_return']) ? trim((string)$opts['fallback_return']) : '../inicio.php';
        $return_to = rsu_initial_data_current_uri($fallback_return);
        $flash = rsu_initial_data_get_flash();

        $title_value = isset($status['titulo']) ? (string)$status['titulo'] : '';
        $start_value = isset($status['fecha_inicio']) ? (string)$status['fecha_inicio'] : '';
        $end_value = isset($status['fecha_fin']) ? (string)$status['fecha_fin'] : '';
        ?>
        <style>
          .rsu-init-wrap { border: 1px solid #e5e7eb; border-radius: .7rem; padding: 1rem; background: #ffffff; }
          .rsu-init-wrap h5 { margin-bottom: .45rem; }
          .rsu-init-note { color: #6b7280; margin-bottom: 0; }
          .rsu-init-sem-grid { display: flex; flex-wrap: wrap; gap: .45rem; margin-top: .65rem; }
          .rsu-init-sem-item { border: 1px solid #d1d5db; border-radius: .6rem; padding: .4rem .55rem; background: #f9fafb; min-width: 130px; }
          .rsu-init-sem-periodo { font-weight: 700; font-size: .86rem; color: #111827; }
          .rsu-init-sem-task { font-size: .79rem; color: #374151; margin-top: .2rem; line-height: 1.25; }
          .rsu-init-sem-state { font-size: .82rem; font-weight: 600; margin-top: .55rem; }
          .rsu-init-sem-state.ok { color: #166534; }
          .rsu-init-sem-state.err { color: #b91c1c; }
        </style>

        <?php if (!empty($flash['msg'])): ?>
          <div class="alert alert-<?php echo htmlspecialchars((string)$flash['type'], ENT_QUOTES, 'UTF-8'); ?> mb-3">
            <?php echo htmlspecialchars((string)$flash['msg'], ENT_QUOTES, 'UTF-8'); ?>
          </div>
        <?php endif; ?>

        <div class="rsu-init-wrap mb-3">
          <h5 class="mb-2">Completa los datos iniciales para comenzar</h5>
          <p class="rsu-init-note">Para seguir, primero registra el titulo y la duracion de tu proyecto.</p>
        </div>

        <div class="modal fade" id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>" tabindex="-1" role="dialog" aria-hidden="true" data-backdrop="static" data-keyboard="false">
          <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
            <div class="modal-content">
              <div class="modal-header py-2">
                <h6 class="modal-title mb-0">Registra los datos iniciales para empezar a llenar el contenido</h6>
              </div>
              <form id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Form" method="post" action="<?php echo htmlspecialchars($save_url, ENT_QUOTES, 'UTF-8'); ?>" novalidate>
                <div class="modal-body">
                  <input type="hidden" name="return_to" value="<?php echo htmlspecialchars($return_to, ENT_QUOTES, 'UTF-8'); ?>">

                  <div class="form-group mb-2">
                    <label for="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Titulo" class="mb-0">2. Titulo del Proyecto</label>
                    <input
                      type="text"
                      class="form-control"
                      id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Titulo"
                      name="p2"
                      value="<?php echo htmlspecialchars($title_value, ENT_QUOTES, 'UTF-8'); ?>"
                      maxlength="300"
                      required>
                    <small class="text-muted">Puedes cambiar el titulo mas adelante.</small>
                    <div class="invalid-feedback">El titulo del proyecto es obligatorio.</div>
                  </div>

                  <div class="form-group mb-0">
                    <label class="mb-1">9. Duracion del proyecto (de 2 a 5 anos)</label>
                    <div class="row">
                      <div class="col-md-6 mb-2">
                        <label for="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Inicio" class="mb-0 small">Fecha de inicio</label>
                        <input
                          type="date"
                          class="form-control"
                          id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Inicio"
                          name="fecha_inicio"
                          value="<?php echo htmlspecialchars($start_value, ENT_QUOTES, 'UTF-8'); ?>"
                          required>
                      </div>
                      <div class="col-md-6 mb-2">
                        <label for="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Fin" class="mb-0 small">Fecha de fin</label>
                        <input
                          type="date"
                          class="form-control"
                          id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>Fin"
                          name="fecha_fin"
                          value="<?php echo htmlspecialchars($end_value, ENT_QUOTES, 'UTF-8'); ?>"
                          required>
                      </div>
                    </div>
                    <small class="text-muted">Se puede solicitar un cambio de fechas del proyecto previa coordinacion con la Direccion de RSU: proyectosdirsu@unitru.edu.pe.</small>
                  </div>

                  <div id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>SemState" class="rsu-init-sem-state mt-2"></div>
                  <div id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>SemResumen" class="small text-muted mt-1"></div>
                  <div id="<?php echo htmlspecialchars($modal_id, ENT_QUOTES, 'UTF-8'); ?>SemGrid" class="rsu-init-sem-grid"></div>
                </div>
                <div class="modal-footer py-2">
                  <button type="submit" class="btn btn-primary btn-sm">Guardar y continuar</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <script>
        (function () {
          var modalId = <?php echo json_encode($modal_id); ?>;
          var apiUrl = <?php echo json_encode($preview_api_url); ?>;
          var form = document.getElementById(modalId + 'Form');
          var inStart = document.getElementById(modalId + 'Inicio');
          var inEnd = document.getElementById(modalId + 'Fin');
          var inTitle = document.getElementById(modalId + 'Titulo');
          var semState = document.getElementById(modalId + 'SemState');
          var semResumen = document.getElementById(modalId + 'SemResumen');
          var semGrid = document.getElementById(modalId + 'SemGrid');
          var modalEl = document.getElementById(modalId);
          var lastRequestToken = 0;
          var semesterRangeValid = false;

          if (!form || !inStart || !inEnd || !inTitle || !semState || !semResumen || !semGrid || !modalEl) return;

          function showModalNow() {
            if (window.jQuery && window.jQuery.fn && typeof window.jQuery.fn.modal === 'function') {
              window.jQuery('#' + modalId).modal('show');
              return;
            }
            if (window.bootstrap && window.bootstrap.Modal) {
              (new window.bootstrap.Modal(modalEl)).show();
              return;
            }
            setTimeout(showModalNow, 50);
          }

          function normalizeRows(rows) {
            var bySem = {};
            for (var i = 0; i < rows.length; i++) {
              var r = rows[i] || {};
              var key = String(r.anio || '') + '-' + String(r.periodo || '');
              if (!bySem[key]) bySem[key] = { periodo: key, final: false };
              if (String(r.tipo || '') === 'semestral' && Number(r.final || 0) === 1) {
                bySem[key].final = true;
              }
            }

            var list = [];
            for (var k in bySem) {
              if (Object.prototype.hasOwnProperty.call(bySem, k)) list.push(bySem[k]);
            }
            list.sort(function (a, b) {
              return a.periodo < b.periodo ? -1 : (a.periodo > b.periodo ? 1 : 0);
            });
            return list;
          }

          function renderSemestres(list) {
            semGrid.innerHTML = '';
            for (var i = 0; i < list.length; i++) {
              var item = list[i];
              var task = 'Informe semestral';
              if (i === 0) task = 'Presentacion de proyecto + Informe semestral';
              if (item.final) task = 'Informe final';

              var node = document.createElement('div');
              node.className = 'rsu-init-sem-item';
              node.innerHTML = '<div class="rsu-init-sem-periodo">' + item.periodo + '</div><div class="rsu-init-sem-task">' + task + '</div>';
              semGrid.appendChild(node);
            }
          }

          function setState(msg, ok) {
            semState.className = 'rsu-init-sem-state mt-2 ' + (ok ? 'ok' : 'err');
            semState.textContent = msg;
            semesterRangeValid = !!ok;
          }

          function resetPreview(msg) {
            semGrid.innerHTML = '';
            semResumen.textContent = msg || '';
          }

          function validateRange(s, e) {
            if (!s || !e) return { ok: false, msg: 'Selecciona fecha de inicio y fecha de fin.' };
            if (s === e) return { ok: false, msg: 'Las fechas no pueden ser iguales.' };
            if (e < s) return { ok: false, msg: 'La fecha fin debe ser posterior a la fecha de inicio.' };
            return { ok: true, msg: '' };
          }

          function loadPreview() {
            var s = String(inStart.value || '').trim();
            var e = String(inEnd.value || '').trim();
            var range = validateRange(s, e);
            if (!range.ok) {
              setState(range.msg, false);
              resetPreview('');
              return;
            }

            var token = ++lastRequestToken;
            semResumen.textContent = 'Calculando semestres...';
            var url = apiUrl + '?action=project.semesters.preview&fecha_inicio=' + encodeURIComponent(s) + '&fecha_fin=' + encodeURIComponent(e);
            fetch(url, { credentials: 'same-origin' })
              .then(function (res) { return res.json(); })
              .then(function (json) {
                if (token !== lastRequestToken) return;
                if (!json || json.ok !== true || !json.data || !Array.isArray(json.data.rows)) {
                  setState('No se pudo calcular los semestres para este rango.', false);
                  resetPreview('');
                  return;
                }

                var semestres = normalizeRows(json.data.rows);
                var total = semestres.length;
                renderSemestres(semestres);
                semResumen.textContent = 'Semestres tentativos del proyecto: ' + total + '.';
                if (total < 4) {
                  setState('Rango invalido: el proyecto debe tener al menos 4 semestres (2 anos).', false);
                } else if (total > 10) {
                  setState('Rango invalido: el proyecto no puede superar 10 semestres (5 anos).', false);
                } else {
                  setState('Rango valido: puedes continuar con este proyecto.', true);
                }
              })
              .catch(function () {
                if (token !== lastRequestToken) return;
                setState('No se pudo calcular los semestres para este rango.', false);
                resetPreview('');
              });
          }

          inStart.addEventListener('change', loadPreview);
          inEnd.addEventListener('change', loadPreview);

          form.addEventListener('submit', function (ev) {
            var title = String(inTitle.value || '').trim();
            if (title === '') {
              inTitle.setCustomValidity('El titulo es obligatorio.');
            } else {
              inTitle.setCustomValidity('');
            }

            var s = String(inStart.value || '').trim();
            var e = String(inEnd.value || '').trim();
            var range = validateRange(s, e);

            if (!range.ok || !semesterRangeValid || !form.checkValidity()) {
              ev.preventDefault();
              ev.stopPropagation();
            }
            form.classList.add('was-validated');
          });

          loadPreview();
          showModalNow();
        })();
        </script>
        <?php
    }
}

