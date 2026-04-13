<?php
/*-------------------------------------------------------------------------
 |  VISTA: Gestion de periodos
 |  Ruta  : direccion_rsu/funciones/card_gestion_periodos.php
 |  Nota  : Version optimizada para control_proyectos (sin recarga de pagina)
 *------------------------------------------------------------------------*/
?>
<div class="card border-success">
  <div class="card-header bg-success text-white">
    <h5 class="card-title mb-0">Gestion de periodos de trabajo</h5>
  </div>
  <div class="card-body">
    <div class="row">
      <div class="col-lg-4 mb-3">
        <form id="gpFormPeriodo" autocomplete="off">
          <input type="hidden" id="gpIdPeriodo" value="">

          <div class="form-group">
            <label for="gpNombre">Nombre del periodo <span class="text-danger">*</span></label>
            <input type="text" id="gpNombre" class="form-control" maxlength="120" required>
            <small class="form-text text-muted">
              Ejemplo: 2026-I, 2026-II, 2026 Extraordinario.
            </small>
          </div>

          <div class="form-group">
            <label for="gpFechaInicio">Fecha de inicio <span class="text-danger">*</span></label>
            <input type="date" id="gpFechaInicio" class="form-control" required>
            <small class="form-text text-muted">
              Desde esta fecha inicia la vigencia del periodo.
            </small>
          </div>

          <div class="form-group">
            <label for="gpFechaFin">Fecha de fin <span class="text-danger">*</span></label>
            <input type="date" id="gpFechaFin" class="form-control" required>
            <small class="form-text text-muted">
              Debe ser mayor o igual a la fecha de inicio.
            </small>
          </div>

          <div class="form-group">
            <label for="gpActivo">Estado del periodo</label>
            <select id="gpActivo" class="form-control">
              <option value="1">Activo</option>
              <option value="0">Inactivo</option>
            </select>
            <small class="form-text text-muted">
              El estado define si el periodo aparece como habilitado.
            </small>
          </div>

          <button type="submit" class="btn btn-success btn-block" id="gpBtnGuardar">
            <i class="fas fa-save"></i> Guardar periodo
          </button>
          <button type="button" class="btn btn-secondary btn-block mt-2" id="gpBtnCancelar" style="display:none;">
            Cancelar edicion
          </button>
        </form>
      </div>

      <div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h6 class="mb-0">Periodos registrados</h6>
          <small class="text-muted" id="gpInfoEstado">Cargando...</small>
        </div>
        <div class="table-responsive">
          <table class="table table-bordered table-hover table-sm" id="gpTablaPeriodos">
            <thead class="thead-light">
              <tr>
                <th style="width: 60px;">#</th>
                <th>Nombre</th>
                <th style="width: 130px;">Inicio</th>
                <th style="width: 130px;">Fin</th>
                <th style="width: 90px;">Estado</th>
                <th style="width: 120px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
$(function () {
  var gpRows = [];
  var gpEditing = false;

  var $form = $('#gpFormPeriodo');
  var $id = $('#gpIdPeriodo');
  var $nombre = $('#gpNombre');
  var $inicio = $('#gpFechaInicio');
  var $fin = $('#gpFechaFin');
  var $activo = $('#gpActivo');
  var $btnGuardar = $('#gpBtnGuardar');
  var $btnCancelar = $('#gpBtnCancelar');
  var $tabla = $('#gpTablaPeriodos tbody');
  var $info = $('#gpInfoEstado');

  function gpEscape(text) {
    return String(text || '')
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#39;');
  }

  function gpResetForm() {
    gpEditing = false;
    $id.val('');
    $form[0].reset();
    $activo.val('1');
    $btnGuardar.html('<i class="fas fa-save"></i> Guardar periodo')
              .removeClass('btn-primary')
              .addClass('btn-success');
    $btnCancelar.hide();
  }

  function gpSetEditMode(row) {
    gpEditing = true;
    $id.val(row.id);
    $nombre.val(row.nombre);
    $inicio.val(row.fecha_inicio);
    $fin.val(row.fecha_fin);
    $activo.val(String(row.activo));
    $btnGuardar.html('<i class="fas fa-edit"></i> Actualizar periodo')
              .removeClass('btn-success')
              .addClass('btn-primary');
    $btnCancelar.show();
    $nombre.focus();
  }

  function gpRenderTable() {
    $tabla.empty();

    if (!gpRows.length) {
      $tabla.append('<tr><td colspan="6" class="text-center text-muted">No hay periodos registrados.</td></tr>');
      return;
    }

    var i;
    for (i = 0; i < gpRows.length; i++) {
      var row = gpRows[i];
      var estadoBadge = parseInt(row.activo, 10) === 1
        ? '<span class="badge badge-success">Activo</span>'
        : '<span class="badge badge-secondary">Inactivo</span>';

      $tabla.append(
        '<tr data-id="' + gpEscape(row.id) + '">' +
          '<td>' + (i + 1) + '</td>' +
          '<td>' + gpEscape(row.nombre) + '</td>' +
          '<td>' + gpEscape(row.fecha_inicio) + '</td>' +
          '<td>' + gpEscape(row.fecha_fin) + '</td>' +
          '<td class="text-center">' + estadoBadge + '</td>' +
          '<td class="text-center">' +
            '<button type="button" class="btn btn-xs btn-primary gpBtnEdit" data-id="' + gpEscape(row.id) + '" title="Editar"><i class="fas fa-edit"></i></button> ' +
            '<button type="button" class="btn btn-xs btn-danger gpBtnDelete" data-id="' + gpEscape(row.id) + '" title="Eliminar"><i class="fas fa-trash"></i></button>' +
          '</td>' +
        '</tr>'
      );
    }
  }

  function gpAjax(action, data, onOk) {
    $.post('funciones/logica_periodos.php', $.extend({ action: action }, data || {}), function (r) {
      if (r && r.success) {
        if (typeof onOk === 'function') {
          onOk(r);
        }
      } else {
        alert((r && r.msg) ? r.msg : 'No se pudo procesar la accion.');
      }
    }, 'json').fail(function () {
      alert('No se pudo conectar con el servidor.');
    });
  }

  function gpLoadRows() {
    $info.text('Actualizando...');
    gpAjax('list', {}, function (r) {
      gpRows = r.data || [];
      gpRenderTable();
      $info.text('Total: ' + gpRows.length + ' periodo(s)');
    });
  }

  function gpFindRow(id) {
    var i;
    for (i = 0; i < gpRows.length; i++) {
      if (parseInt(gpRows[i].id, 10) === parseInt(id, 10)) {
        return gpRows[i];
      }
    }
    return null;
  }

  $form.on('submit', function (e) {
    e.preventDefault();

    var id = $id.val();
    var nombre = $.trim($nombre.val());
    var fechaInicio = $inicio.val();
    var fechaFin = $fin.val();
    var activo = $activo.val() === '1' ? 1 : 0;

    if (!nombre) {
      alert('El nombre del periodo es obligatorio.');
      return;
    }
    if (!fechaInicio || !fechaFin) {
      alert('Debe ingresar fecha de inicio y fecha de fin.');
      return;
    }
    if (fechaInicio > fechaFin) {
      alert('La fecha de inicio no puede ser mayor que la fecha de fin.');
      return;
    }

    if (id) {
      gpAjax('update', {
        id: id,
        nombre: nombre,
        fecha_inicio: fechaInicio,
        fecha_fin: fechaFin,
        activo: activo
      }, function () {
        gpResetForm();
        gpLoadRows();
      });
      return;
    }

    gpAjax('create', {
      nombre: nombre,
      fecha_inicio: fechaInicio,
      fecha_fin: fechaFin,
      activo: activo
    }, function () {
      gpResetForm();
      gpLoadRows();
    });
  });

  $btnCancelar.on('click', function () {
    gpResetForm();
  });

  $tabla.on('click', '.gpBtnEdit', function () {
    var id = $(this).data('id');
    var row = gpFindRow(id);
    if (row) {
      gpSetEditMode(row);
    }
  });

  $tabla.on('click', '.gpBtnDelete', function () {
    var id = $(this).data('id');
    var row = gpFindRow(id);
    var nombre = row ? row.nombre : '';

    if (!confirm('¿Eliminar el periodo "' + nombre + '"?')) {
      return;
    }

    gpAjax('delete', { id: id }, function () {
      if (gpEditing && parseInt($id.val(), 10) === parseInt(id, 10)) {
        gpResetForm();
      }
      gpLoadRows();
    });
  });

  gpResetForm();
  gpLoadRows();
});
</script>
