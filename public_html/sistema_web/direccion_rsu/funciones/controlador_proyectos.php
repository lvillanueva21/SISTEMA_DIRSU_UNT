<?php
/*-------------------------------------------------------------------------
 |  VISTA: Control de cronogramas
 |  Ruta  : direccion_rsu/funciones/controlador_proyectos.php
 |  Dep.  : jQuery 3.6+, Bootstrap 4, AdminLTE
 *------------------------------------------------------------------------*/
?>

<!-- ─────── Tarjeta principal ─────── -->
<div class="card border-primary">
  <div class="card-header bg-primary text-white">
    <h5 class="card-title mb-0">Control de cronogramas de Presentación y Revisión</h5>
  </div>

  <div class="card-body">
    <div class="row">
      <!-- ╭────────── col-3  = formulario ──────────╮ -->
      <div class="col-lg-3 mb-3">
        <form id="formCronograma" autocomplete="off">
          <div class="form-row align-items-end">

            <div class="form-group col-md-12">
              <label for="tipo">Tipo de cronograma</label>
              <select id="tipo" name="tipo" class="form-control" required>
                <option value="" disabled selected>Seleccione...</option>
                <option value="1">Presentación de Proyecto</option>
                <option value="2">Informe Semestral</option>
                <option value="3">Otros</option>
              </select>
            </div>

            <div class="form-group col-md-12">
              <label for="id_periodo">Periodo</label>
              <select id="id_periodo" name="id_periodo" class="form-control"></select>
            </div>

            <div class="form-group col-md-12">
              <label for="apertura">Apertura</label>
              <input type="datetime-local" id="apertura" name="apertura" class="form-control" required>
            </div>

            <div class="form-group col-md-12">
              <label for="cierre">Cierre</label>
              <input type="datetime-local" id="cierre" name="cierre" class="form-control" required>
            </div>

            <div class="form-group col-md-4 text-center">
              <label for="activo">Activo</label><br>
              <input type="checkbox" id="activo" name="activo" class="form-control">
            </div>

            <div class="form-group col-md-8">
              <button type="submit" class="btn btn-success btn-block">
                <i class="fas fa-plus-circle"></i> Añadir
              </button>
            </div>

          </div><!-- /form-row -->
        </form>
      </div><!-- /col-3 -->

      <!-- ╭────────── col-9  = filtro + tabla ──────────╮ -->
      <div class="col-lg-9">
        <!-- Filtro de periodo -->
        <div class="form-group row">
          <label for="filtroPeriodo" class="col-sm-4 col-form-label">Filtrar por periodo</label>
          <div class="col-sm-8">
            <select id="filtroPeriodo" class="form-control"></select>
          </div>
        </div>

        <!-- Tabla -->
        <div class="table-responsive">
          <table id="tablaCronogramas" class="table table-bordered table-hover">
            <thead class="thead-light">
              <tr>
                <th>#</th><th>Periodo</th><th>Tipo</th><th>Apertura</th>
                <th>Cierre</th><th>Estado</th><th>Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div><!-- /col-9 -->
    </div><!-- /row -->
  </div><!-- /card-body -->
</div><!-- /card -->
<!-- ===== Modal Éxito ===== -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog"
     aria-labelledby="successModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="successModalLabel">✅ Cronograma guardado</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="successBody"></div>
    </div>
  </div>
</div>

<!-- ===== Modal Eliminar ===== -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog"
     aria-labelledby="deleteModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white">
        <h5 class="modal-title" id="deleteModalLabel">Eliminar cronograma</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <pre class="mb-0" id="deleteBody" style="white-space:pre-wrap;"></pre>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger" id="btnConfirmDelete">Sí, eliminar</button>
      </div>
    </div>
  </div>
</div>
<!-- ===== Modal Control de Visibilidad F1 ===== -->
<div class="modal fade" id="visibilidadF1Modal" tabindex="-1" role="dialog"
     aria-labelledby="visibilidadF1ModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-secondary">
      <div class="modal-header bg-secondary text-white">
        <h5 class="modal-title" id="visibilidadF1ModalLabel">Control de Visibilidad de Interfaces</h5>
        <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <div class="alert alert-light border mb-2">
          <strong>Periodo:</strong> <span id="visibilidadF1Periodo">-</span>
        </div>
        <form id="formVisibilidadF1">
          <input type="hidden" id="visibilidadF1CronogramaId" name="id_cronograma" value="">
          <div class="table-responsive">
            <table class="table table-sm table-bordered mb-0">
              <thead class="thead-light">
                <tr>
                  <th>Interfaz</th>
                  <th>Descripcion</th>
                  <th>Inicio</th>
                  <th>Fin</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody id="visibilidadF1Body"></tbody>
            </table>
          </div>
        </form>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-primary" id="btnGuardarVisibilidadF1">Guardar cambios</button>
      </div>
    </div>
  </div>
</div>
<!-- ============= SCRIPTS ESPECÍFICOS ============== -->
<script>
$(function(){

  /*--- Cargar periodos ---*/
  function cargarPeriodos(){
    $.post('funciones/logica_cronogramas.php',{action:'periodos'},function(r){
      const $selForm=$('#id_periodo'), $selFiltro=$('#filtroPeriodo');
      $selForm.empty(); $selFiltro.empty();
      if(!r.length){
        $selForm.append('<option value="">Sin periodos</option>').prop('disabled',true);
        $selFiltro.append('<option value="todos">Todos</option>').prop('disabled',true);
        return;
      }
      $selForm.append('<option value="" disabled selected>Seleccione...</option>');
      $selFiltro.append('<option value="todos">Todos</option>');
      r.forEach(p=>{
        $selForm.append(`<option value="${p.id}">${p.nombre}</option>`);
        $selFiltro.append(`<option value="${p.id}">${p.nombre}</option>`);
      });
    },'json');
  }

  /*--- Listar cronogramas ---*/
  function listarCronogramas(){
    const filtro=$('#filtroPeriodo').val()||'todos';
    $.post('funciones/logica_cronogramas.php',{action:'list',filtro},function(r){
      const $tb=$('#tablaCronogramas tbody').empty();
      if(!r.length){
        $tb.append('<tr><td colspan="7" class="text-center text-muted">Sin registros</td></tr>');
        return;
      }
      r.forEach((c,i)=>{
        const estado=parseInt(c.activo)===1?'✅':'⛔';
        const btnVisibilidad = parseInt(c.tipo,10)===1
          ? '<button class="btn btn-sm btn-secondary btnVisibilidad" title="Control de visibilidad"><i class="fas fa-sliders-h"></i></button>'
          : '';
        $tb.append(`
          <tr data-id="${c.id}" data-id_periodo="${c.id_periodo}" data-tipo="${c.tipo}" data-activo="${parseInt(c.activo,10)===1 ? 1 : 0}">
            <td>${i+1}</td><td>${c.periodo}</td><td>${c.tipo_nombre}</td>
            <td>${c.apertura}</td><td>${c.cierre}</td>
            <td class="text-center">${estado}</td>
            <td class="text-center">
              ${btnVisibilidad}
              <button class="btn btn-sm btn-primary btnEditar"><i class="fas fa-edit"></i></button>
              <button class="btn btn-sm btn-danger btnBorrar"><i class="fas fa-trash"></i></button>
            </td>
          </tr>`);
      });
    },'json');
  }

  /*--- Alta ---*/
  $('#formCronograma').on('submit',function(e){
    e.preventDefault();
    const ap=$('#apertura').val(), ci=$('#cierre').val();
    if(!ap||!ci) return alert('Complete fechas.');
    if(new Date(ap)>=new Date(ci)) return alert('La fecha de apertura debe ser menor que la de cierre.');

    $.post('funciones/logica_cronogramas.php',$(this).serialize()+'&action=create',function(r){
      if(r.success){
        $('#successBody').html(
          `📅 <b>Periodo:</b> ${r.data.periodo}<br>`+
          `📝 <b>Tipo:</b> ${r.data.tipo_nombre}<br>`+
          `⏰ <b>Apertura:</b> ${r.data.apertura}<br>`+
          `⏳ <b>Cierre:</b> ${r.data.cierre}<br>`+
          `🔄 <b>Estado:</b> ${r.data.activo==1?'Activo':'Inactivo'}`
        );
        $('#successModal').modal('show');

        /*-- ✅ RESET y refresco seguros --*/
        $('#formCronograma')[0].reset(); // antes fallaba con this.reset()
        listarCronogramas();

      }else alert(r.msg||'Error');
    },'json');
  });

  $('#filtroPeriodo').on('change',listarCronogramas);

  /*--- Borrar (abre modal) ---*/
  let idEliminar=null;
  $('#tablaCronogramas').on('click','.btnBorrar',function(){
    idEliminar=$(this).closest('tr').data('id');
    $.post('funciones/logica_cronogramas.php',{action:'info',id:idEliminar},function(r){
      $('#deleteBody').text(
`¿Confirmas que deseas eliminar el cronograma?
📅 Periodo: ${r.periodo}
⏰ Apertura: ${r.apertura}
⏳ Cierre: ${r.cierre}
🔄 Estado: ${r.activo==1?'Activo':'Inactivo'}`);
      $('#deleteModal').modal('show');
    },'json');
  });

  /*--- Confirmar borrado ---*/
  $(document).on('click','#btnConfirmDelete',function(){
    if(!idEliminar) return;
    $.post('funciones/logica_cronogramas.php',{action:'delete',id:idEliminar},function(r){
      if(r.success){ listarCronogramas(); $('#deleteModal').modal('hide'); }
      else alert(r.msg||'No se pudo eliminar');
      idEliminar=null;
    },'json');
  });

  /*--- Editar en línea (igual que antes) ---*/
  $('#tablaCronogramas')
    .off('click','.btnEditar')
    .on('click','.btnEditar',function(){
      const $tr=$(this).closest('tr');
      if($tr.hasClass('editando')) return;
      $tr.addClass('editando');

      const idPer=String($tr.data('id_periodo'));
      const perTx=$tr.children().eq(1).text();
      const tipo=parseInt($tr.data('tipo'),10)||1;
      const ap=$tr.children().eq(3).text().replace(' ','T');
      const ci=$tr.children().eq(4).text().replace(' ','T');
      const act=parseInt($tr.data('activo'),10)===1;

      cargarSelectPeriodo(html=>{
        $tr.children().eq(1).html(html);
        const $sel=$tr.find('select.sel-periodo');
        idPer? $sel.val(idPer) : $sel.val($sel.find(`option:contains("${perTx}")`).val());
      });

      $tr.children().eq(2).html(`
        <select class="form-control sel-tipo">
          <option value="1">Presentación de Proyecto</option>
          <option value="2">Informe Semestral</option>
          <option value="3">Otros</option>
        </select>`).find('select.sel-tipo').val(tipo);

      $tr.children().eq(3).html(`<input type="datetime-local" class="form-control" value="${ap}">`);
      $tr.children().eq(4).html(`<input type="datetime-local" class="form-control" value="${ci}">`);
      $tr.children().eq(5).html(`<input type="checkbox" class="form-control" ${act?'checked':''}>`);

      $(this).removeClass('btn-primary btnEditar')
             .addClass('btn-success btnGuardar')
             .html('<i class="fas fa-save"></i>');
    });

  function cargarSelectPeriodo(cb){
    $.post('funciones/logica_cronogramas.php',{action:'periodos'},function(p){
      let html='<select class="form-control sel-periodo">';
      p.forEach(pp=> html+=`<option value="${pp.id}">${pp.nombre}</option>`);
      html+='</select>'; cb(html);
    },'json');
  }

  /*--- Guardar edición ---*/
  $('#tablaCronogramas')
    .off('click','.btnGuardar')
    .on('click','.btnGuardar',function(){
      const $tr=$(this).closest('tr');
      const data={
        action:'update',
        id:$tr.data('id'),
        id_periodo:$tr.find('select.sel-periodo').val(),
        tipo:parseInt($tr.find('select.sel-tipo').val(),10),
        apertura:$tr.find('input[type="datetime-local"]').eq(0).val(),
        cierre:$tr.find('input[type="datetime-local"]').eq(1).val(),
        activo:$tr.find('input[type="checkbox"]').is(':checked')?1:0
      };
      if(new Date(data.apertura)>=new Date(data.cierre))
        return alert('Apertura debe ser menor que cierre');

      $.post('funciones/logica_cronogramas.php',data,function(r){
        if(r.success) listarCronogramas();
        else alert(r.msg||'Error al actualizar');
      },'json');
    });

  function escHtml(v){
    return String(v === null || v === undefined ? '' : v)
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  function renderVisibilidadRows(rows){
    const $body = $('#visibilidadF1Body').empty();
    if(!rows || !rows.length){
      $body.append('<tr><td colspan="5" class="text-center text-muted">Sin configuracion disponible.</td></tr>');
      return;
    }

    rows.forEach(function(row){
      const codigo = String(row.codigo || '').trim();
      const nombre = escHtml(row.nombre || codigo || 'Interfaz');
      const descripcion = escHtml(row.descripcion || '');
      const inicio = escHtml(row.inicio || '');
      const fin = escHtml(row.fin || '');
      const estado = parseInt(row.estado,10) === 1 ? 1 : 0;

      $body.append(
        '<tr>' +
          '<td><strong>' + nombre + '</strong><div class="small text-muted">' + escHtml(codigo) + '</div></td>' +
          '<td><input type="text" class="form-control form-control-sm" name="rows[' + codigo + '][descripcion]" value="' + descripcion + '"></td>' +
          '<td><input type="datetime-local" class="form-control form-control-sm" name="rows[' + codigo + '][inicio]" value="' + inicio + '"></td>' +
          '<td><input type="datetime-local" class="form-control form-control-sm" name="rows[' + codigo + '][fin]" value="' + fin + '"></td>' +
          '<td><select class="form-control form-control-sm" name="rows[' + codigo + '][estado]">' +
            '<option value="1"' + (estado===1 ? ' selected' : '') + '>Activo</option>' +
            '<option value="0"' + (estado===0 ? ' selected' : '') + '>Inactivo</option>' +
          '</select></td>' +
        '</tr>'
      );
    });
  }

  $('#tablaCronogramas').on('click', '.btnVisibilidad', function(){
    const idCronograma = parseInt($(this).closest('tr').data('id'), 10) || 0;
    if(!idCronograma){
      alert('No se pudo identificar el cronograma.');
      return;
    }

    $.post('funciones/logica_cronogramas.php', { action:'get_visibilidad_f1', id_cronograma:idCronograma }, function(r){
      if(!r || !r.success || !r.data){
        alert((r && r.msg) ? r.msg : 'No se pudo cargar la configuracion de visibilidad.');
        return;
      }

      $('#visibilidadF1CronogramaId').val(r.data.id_cronograma || idCronograma);
      $('#visibilidadF1Periodo').text(r.data.periodo || '-');
      renderVisibilidadRows(r.data.rows || []);
      $('#visibilidadF1Modal').modal('show');
    }, 'json');
  });

  $('#btnGuardarVisibilidadF1').on('click', function(){
    const idCronograma = parseInt($('#visibilidadF1CronogramaId').val(), 10) || 0;
    if(!idCronograma){
      alert('No hay cronograma seleccionado.');
      return;
    }

    const payload = $('#formVisibilidadF1').serialize() + '&action=save_visibilidad_f1&id_cronograma=' + idCronograma;
    $.post('funciones/logica_cronogramas.php', payload, function(r){
      if(r && r.success){
        alert(r.msg || 'Visibilidad guardada correctamente.');
        $('#visibilidadF1Modal').modal('hide');
        return;
      }
      alert((r && r.msg) ? r.msg : 'No se pudo guardar la configuracion.');
    }, 'json');
  });

  /*--- Init ---*/
  cargarPeriodos(); listarCronogramas();
});
</script>
