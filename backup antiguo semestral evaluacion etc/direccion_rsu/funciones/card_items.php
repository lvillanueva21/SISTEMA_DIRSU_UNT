<?php if (session_status() === PHP_SESSION_NONE) { session_start(); } ?>
<!-- ════════ Card: Ítems de formulario ════════ -->
<div class="card shadow-sm border-secondary">
  <div class="card-header bg-secondary text-white py-2">
    <h5 class="m-0"><i class="fas fa-list-alt"></i> Ítems de formulario</h5>
  </div>

  <div class="card-body">
    <div class="row">

      <!-- ───── Col IZQUIERDA ───── -->
      <div class="col-lg-7 mb-3">
        <!-- 0. Selección del formulario -->
        <div class="form-group">
          <label class="mb-0"><strong>Formulario <span class="text-danger">*</span></strong></label>
          <select id="selFormulario" class="form-control" required>
            <option value="" disabled selected>Cargando…</option>
          </select>
        </div>

        <!-- Tabla de ítems -->
        <div class="table-responsive">
          <table id="tblItems" class="table table-bordered table-hover table-sm mb-0">
            <thead class="thead-light">
              <tr>
                <th style="width:55px;">#</th>
                <th>Nombre / Detalle</th>
                <th style="width:90px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>
      </div><!-- /.col-left -->

      <!-- ───── Col DERECHA (alta/edición) ───── -->
      <div class="col-lg-5">
        <form id="frmNuevoItem" autocomplete="off">
          <div class="form-group">
            <label class="mb-0"><strong>1. Nombre de ítem <span class="text-danger">*</span></strong></label>
            <input type="text" id="itNombre" class="form-control" maxlength="100" required>
          </div>

          <div class="form-group">
            <label class="mb-0"><strong>2. Descripción (opcional)</strong></label>
            <textarea id="itDescripcion" class="form-control" rows="3" maxlength="1000"></textarea>
          </div>

          <div class="form-group">
            <label class="mb-0"><strong>3. Tipo de dato <span class="text-danger">*</span></strong></label>
            <select id="itTipo" class="form-control" required>
              <option value="" disabled selected>Selecciona un tipo</option>
              <!-- ===== Tipos existentes ===== -->
              <option value="varchar">varchar – texto corto</option>
              <option value="longtext">longtext – párrafo</option>
              <option value="tinyint">tinyint – opción única</option>
              <option value="int">int – número entero</option>
              <option value="datetime">datetime – fecha y hora</option>
              <option value="date">date – fecha</option>
              <option value="boolean">boolean – sí / no</option>
              <option value="decimal">decimal – 2 decimales</option>
              <!-- ===== NUEVOS tipos (se almacenan como varchar) ===== -->
              <option value="ubicacion">Ubicación — (varchar)</option>
              <option value="ods">ODS — (varchar)</option>
              <option value="programa_ods">Programa - ODS — (varchar)</option>
              <option value="pdf">PDF — (ruta en varchar)</option>
              <option value="excel">Excel — (ruta en varchar)</option>
              <option value="word">Word — (ruta en varchar)</option>
            </select>
          </div>

          <div class="form-group col-md-6 px-0">
            <label class="mb-0"><strong>4. Orden <span class="text-danger">*</span></strong></label>
            <input type="number" id="itOrden" class="form-control text-center"
                   min="1" max="100" value="1" required>
          </div>

          <!-- 5. Imagen de ejemplo -->
          <div class="form-group">
            <label><strong>5. Imagen de ejemplo (opcional)</strong></label>
            <input type="file" id="fileImg" class="form-control-file" accept="image/*">
            <table class="table table-sm mt-2" id="tblImgState">
              <tbody>
                <tr class="rowEmptyImg">
                  <td class="text-muted">Vacío</td><td class="text-right"></td>
                </tr>
                <tr class="rowFileImg" style="display:none;">
                  <td>
                    <span class="badge badge-success">Imagen cargada</span>
                    <span class="text-monospace small ml-2 fileNameImg"></span>
                  </td>
                  <td class="text-right">
                    <button type="button" class="btn btn-info btn-xs btnVerImg"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-danger btn-xs btnBorrarImg"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- 6. PDF -->
          <div class="form-group">
            <label><strong>6. PDF (opcional)</strong></label>
            <input type="file" id="filePdf" class="form-control-file" accept="application/pdf">
            <table class="table table-sm mt-2" id="tblPdfState">
              <tbody>
                <tr class="rowEmptyPdf">
                  <td class="text-muted">Vacío</td><td class="text-right"></td>
                </tr>
                <tr class="rowFilePdf" style="display:none;">
                  <td>
                    <span class="badge badge-success">PDF cargado</span>
                    <span class="text-monospace small ml-2 fileNamePdf"></span>
                  </td>
                  <td class="text-right">
                    <button type="button" class="btn btn-info btn-xs btnVerPdf"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-danger btn-xs btnBorrarPdf"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- 7. Formato (Word/Excel) -->
          <div class="form-group">
            <label><strong>7. Formato (Word/Excel) (opcional)</strong></label>
            <input type="file" id="fileFormato" class="form-control-file"
                   accept=".doc,.docx,.xls,.xlsx,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document,application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
            <table class="table table-sm mt-2" id="tblFormatoState">
              <tbody>
                <tr class="rowEmptyFormato">
                  <td class="text-muted">Vacío</td><td class="text-right"></td>
                </tr>
                <tr class="rowFileFormato" style="display:none;">
                  <td>
                    <span class="badge badge-success">Formato cargado</span>
                    <span class="text-monospace small ml-2 fileNameFormato"></span>
                  </td>
                  <td class="text-right">
                    <button type="button" class="btn btn-info btn-xs btnVerFormato"><i class="fas fa-eye"></i></button>
                    <button type="button" class="btn btn-danger btn-xs btnBorrarFormato"><i class="fas fa-trash"></i></button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- 8. Link (opcional) -->
          <div class="form-group">
            <label class="mb-0"><strong>8. Link (opcional)</strong></label>
            <input type="url" id="itLink" class="form-control" placeholder="https://ejemplo.com/recurso">
          </div>

          <!-- 9. Video (opcional) -->
          <div class="form-group">
            <label class="mb-0"><strong>9. Video (opcional)</strong></label>
            <input type="url" id="itVideo" class="form-control" placeholder="https://www.youtube.com/watch?v=...">
          </div>

          <button type="submit" class="btn btn-success btn-block" id="btnSubmitItem">
            <i class="fas fa-plus-circle"></i> Añadir ítem
          </button>
          <button type="button" id="btnCancelEdit" class="btn btn-secondary btn-block mt-2" style="display:none;">
            Cancelar
          </button>
        </form>
      </div><!-- /.col-right -->
    </div><!-- /.row -->
  </div><!-- /.card-body -->
</div><!-- /.card -->

<!-- Modal: vista previa de imagen -->
<div class="modal fade" id="modalImg" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body p-0">
        <img id="imgPreview" src="" alt="Imagen" class="img-fluid w-100">
      </div>
      <div class="modal-footer py-2">
        <button class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: confirmación/éxito (con resumen) -->
<div class="modal fade" id="modalOk" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body">
        <div class="d-flex align-items-center mb-2">
          <i class="fas fa-check-circle text-success fa-2x mr-2"></i>
          <div id="okMsg" class="font-weight-bold mb-0">Guardado correctamente</div>
        </div>
        <div id="okSummary" class="border rounded p-2 bg-light">
          <!-- aquí se inyecta el resumen del ítem -->
        </div>
      </div>
      <div class="modal-footer py-2 justify-content-center">
        <button type="button" class="btn btn-primary btn-sm" data-dismiss="modal">OK</button>
      </div>
    </div>
  </div>
</div>

<!-- Modal: error -->
<div class="modal fade" id="modalError" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-body text-center">
        <i class="fas fa-times-circle text-danger fa-2x mb-2"></i>
        <div id="errMsg" class="font-weight-bold">Ocurrió un error</div>
        <code id="errDetail" class="small d-block mt-2 text-monospace"></code>
      </div>
      <div class="modal-footer py-2 justify-content-center">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<script>
$(function(){

  /* ====== LÍMITES DEL SERVIDOR Y HELPERS ====== */
  const LIMITS = {
    uploadMax: '<?php echo ini_get("upload_max_filesize"); ?>',
    postMax:   '<?php echo ini_get("post_max_size"); ?>'
  };

  function parseSize(s){ // "2M" -> bytes
    if (!s) return 0;
    s = String(s).trim().toUpperCase();
    let mult = 1, unit = s.slice(-1);
    let num = parseFloat(s);
    if (unit === 'K') mult = 1024;
    else if (unit === 'M') mult = 1024**2;
    else if (unit === 'G') mult = 1024**3;
    else if (/\D$/.test(s)) { mult = 1; num = parseFloat(s); }
    return Math.floor(num * mult);
  }
  function fmtBytes(b){
    if (!b) return 'desconocido';
    const u = ['B','KB','MB','GB','TB'];
    let i=0, n=Number(b);
    while(n>=1024 && i<u.length-1){ n/=1024; i++; }
    return n.toFixed(n<10?2:1)+' '+u[i];
  }
  function showErrorModal(msg, detail){
    $('#errMsg').text(msg || 'Ocurrió un error');
    $('#errDetail').text(detail || '');
    $('#modalError').modal('show');
  }

  // Límite efectivo del servidor (más estricto)
  const MAX_SERVER = Math.min(
    parseSize(LIMITS.uploadMax) || Infinity,
    parseSize(LIMITS.postMax)   || Infinity
  );

  // Manejo centralizado de fallos Ajax
  function handleAjaxFail(jq, defaultMsg){
    let msg = defaultMsg || 'Error de conexión';
    let detail = '';
    if (jq && jq.responseJSON && jq.responseJSON.msg){
      msg = jq.responseJSON.msg;
      detail = jq.responseJSON.code || '';
    } else if (jq && jq.status === 413){
      msg = 'La petición excede el límite del servidor (HTTP 413).';
    }
    showError(msg);
    showErrorModal(msg, detail);
  }

  /* ====== RESTO DE LÓGICA ====== */

  const URL = 'funciones/card_items_srv.php';

  const esc = s => String(s??'')
      .replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m]));

  // feedback en tabla (3 columnas)
  function showError(msg){
    $('#tblItems tbody').html(
      `<tr><td colspan="3">
         <div class="alert alert-danger mb-0 p-2">${esc(msg)}</div>
       </td></tr>`);
  }

  // modal OK (simple, sin resumen)
  function showOkModal(msg){
    $('#okMsg').text(msg || 'Operación exitosa');
    $('#okSummary').html(''); // limpia resumen
    $('#modalOk').modal('show');
  }

  // ===== Render amigable del ítem en el modal de éxito =====
  function badge(label, on, clsOn='success', clsOff='secondary'){
    const cls = on ? clsOn : clsOff;
    return `<span class="badge badge-${cls} mr-1 mb-1">${label} ${on ? 'SI' : 'NO'}</span>`;
  }

  function renderItemSummary(it){
    const title = `<div class="mb-1"><strong>${esc(it.nombre || '')}</strong></div>`;
    const desc  = it.descripcion ? `<small class="text-muted d-block mb-2">${esc(it.descripcion)}</small>` : '';
    const badges = [
      `<span class="badge badge-info mr-1 mb-1">Tipo: ${esc(it.tipo || '')}</span>`,
      `<span class="badge badge-dark mr-1 mb-1">Orden: ${it.orden ?? ''}</span>`,
      badge('Ejemplo', it.hasEjemplo),
      badge('Img',     it.hasImg),
      badge('PDF',     it.hasPdf),
      badge('Link',    it.hasLink),
      badge('Formato', it.hasFormato),
      badge('Video',   it.hasVideo),
      badge('Archivo', it.hasArchivo),
    ].join(' ');
    return `${title}${desc}<div class="d-flex flex-wrap">${badges}</div>`;
  }

  // Trae datos del ítem y muestra modal de éxito con resumen
  function showOkItem(idItem){
    if (!idItem) { showOkModal('Guardado correctamente'); return; } // guardia extra
    $.post(URL, { action:'detalle_item', id_item:idItem }, r=>{
      if (r && r.success && r.data){
        $('#okMsg').text('Guardado correctamente');
        $('#okSummary').html(renderItemSummary(r.data));
        $('#modalOk').modal('show');
      } else {
        showOkModal('Guardado correctamente');
      }
    }, 'json').fail(()=> showOkModal('Guardado correctamente'));
  }

  /* ===== mini-tablas de estado IMG/PDF/FORMATO ===== */
  function renderAssetState(kind, url) {
    let $tbl, clsEmpty, clsFile, clsName;
    if (kind === 'img') {
      $tbl = $('#tblImgState'); clsEmpty='.rowEmptyImg'; clsFile='.rowFileImg'; clsName='.fileNameImg';
    } else if (kind === 'pdf') {
      $tbl = $('#tblPdfState'); clsEmpty='.rowEmptyPdf'; clsFile='.rowFilePdf'; clsName='.fileNamePdf';
    } else if (kind === 'formato') {
      $tbl = $('#tblFormatoState'); clsEmpty='.rowEmptyFormato'; clsFile='.rowFileFormato'; clsName='.fileNameFormato';
    } else { return; }

    $tbl.data('url', url || '');
    const fileName = url ? url.split('/').pop() : '';
    if (url) {
      $tbl.find(clsName).text(fileName);
      $tbl.find(clsEmpty).hide(); $tbl.find(clsFile).show();
    } else {
      $tbl.find(clsFile).hide();  $tbl.find(clsEmpty).show();
    }
  }

  /* ===== cargar formularios (con refresco al abrir) ===== */
  let formActual = null;
  function cargarFormularios () {
    const seleccion = formActual;
    $.post(URL, { action: 'listar_forms' }, r => {
      const $sel = $('#selFormulario')
        .empty()
        .append('<option value="" disabled selected>Selecciona un formulario</option>');
      if (r.success && r.data.length) {
        r.data.forEach(f => $sel.append(`<option value="${f.id}">${esc(f.nombre)}</option>`));
        if (seleccion) $sel.val(seleccion);
      } else {
        $sel.append('<option value="">(sin formularios)</option>');
        showError('No hay formularios activos');
      }
    }, 'json')
    .fail(() => showError('Error al cargar formularios'));
  }

  let busyReload = false;
  $('#selFormulario').on('focus.reload pointerdown.reload mousedown.reload', function () {
    if (busyReload) return;
    busyReload = true;
    cargarFormularios();
    setTimeout(()=> busyReload=false, 300);
  });

  /* ===== estado de edición ===== */
  let editMode   = false;
  let editIdItem = null;

  function resetForm(){
    editMode=false; editIdItem=null;
    $('#frmNuevoItem')[0].reset();
    $('#itOrden').val(1);
    $('#btnSubmitItem')
      .removeClass('btn-info').addClass('btn-success')
      .html('<i class="fas fa-plus-circle"></i> Añadir ítem');
    $('#btnCancelEdit').hide();
    renderAssetState('img', null);
    renderAssetState('pdf', null);
    renderAssetState('formato', null);
  }

  function fillForm(it){
    $('#itNombre').val(it.nombre);
    $('#itDescripcion').val(it.descripcion);
    $('#itTipo').val(it.tipo);
    $('#itOrden').val(it.orden);
    $('#itLink').val(it.link || '');
    $('#itVideo').val(it.video || '');
    renderAssetState('img', it.img_url || null);
    renderAssetState('pdf', it.pdf_url || null);
    renderAssetState('formato', it.formato_url || null);
    $('#btnSubmitItem')
      .removeClass('btn-success').addClass('btn-info')
      .html('<i class="fas fa-save"></i> Guardar cambios');
    $('#btnCancelEdit').show();
  }

  /* ===== listar ítems ===== */
  function listarItems(){
    if(!formActual){ $('#tblItems tbody').html(''); return; }
    $.post(URL,{action:'listar_items',id_formulario:formActual},r=>{
      const $tb=$('#tblItems tbody').empty();
      if(!(r.success&&r.data.length)){
        showError(r.msg||'Sin ítems'); return;
      }
      r.data.forEach((it,i)=>{
        $tb.append(`
          <tr data-id="${it.id}" data-orden="${it.orden}">
            <td class="text-center align-middle">${i+1}</td>
            <td class="align-middle">
              <div><strong>${esc(it.nombre)}</strong></div>
              <small class="text-muted d-block mb-1">${esc(it.descripcion)}</small>
              <span class="badge badge-info">Tipo: ${esc(it.tipo)}</span>
              <span class="badge badge-dark">Orden: ${it.orden}</span>
              <span class="badge badge-${it.hasEjemplo?'success':'secondary'}">Ejemplo ${it.hasEjemplo?'SI':'NO'}</span>
              <span class="badge badge-${it.hasImg?'success':'secondary'}">Img ${it.hasImg?'SI':'NO'}</span>
              <span class="badge badge-${it.hasPdf?'success':'secondary'}">PDF ${it.hasPdf?'SI':'NO'}</span>
              <span class="badge badge-${it.hasLink?'success':'secondary'}">Link ${it.hasLink?'SI':'NO'}</span>
              <span class="badge badge-${it.hasFormato?'success':'secondary'}">Formato ${it.hasFormato?'SI':'NO'}</span>
              <span class="badge badge-${it.hasVideo?'success':'secondary'}">Video ${it.hasVideo?'SI':'NO'}</span>
              <span class="badge badge-${it.hasArchivo?'success':'secondary'}">Archivo ${it.hasArchivo?'SI':'NO'}</span>
            </td>
            <td class="text-center align-middle">
              <button class="btn btn-xs btn-primary btnEdit"><i class="fas fa-edit"></i></button>
              <button class="btn btn-xs btn-danger  btnDel"><i class="fas fa-trash"></i></button>
            </td>
          </tr>`);
      });
    },'json').fail(()=>showError('Error al listar ítems'));
  }

  /* ===== cambio de formulario ===== */
  $('#selFormulario').on('change',function(){
    formActual=$(this).val();
    resetForm();
    listarItems();
  });

  /* ===== alta / edición ===== */
  $('#frmNuevoItem').on('submit',function(e){
    e.preventDefault();
    if(!formActual){ alert('Elige un formulario'); return; }

    const payload = {
      id_formulario: formActual,
      nombre: $('#itNombre').val().trim(),
      descripcion: $('#itDescripcion').val().trim(),
      tipo: $('#itTipo').val(),
      orden: +$('#itOrden').val(),
      link:  $('#itLink').val().trim(),
      video: $('#itVideo').val().trim()
    };
    if(!payload.nombre||!payload.tipo||!payload.orden) return;

    if(editMode){
      $.post(URL,{action:'actualizar_item',id_item:editIdItem,...payload},r=>{
        if(r.success){ listarItems(); showOkItem(editIdItem); }
        else showError(r.msg||'Error al actualizar');
      },'json').fail(()=>showError('Error de conexión'));
    }else{
      $.post(URL,{action:'crear_item',...payload},r=>{
        if(r.success){
          if (r.id) {
            editMode = true; editIdItem = r.id; $('#btnCancelEdit').show();
            $('#btnSubmitItem').removeClass('btn-success').addClass('btn-info')
              .html('<i class="fas fa-save"></i> Guardar cambios');
          } else { resetForm(); }
          listarItems();
          if (r && r.id) showOkItem(r.id); else showOkModal('Ítem creado correctamente');
        } else showError(r.msg||'Error al crear');
      },'json').fail(()=>showError('Error de conexión'));
    }
  });

  $('#btnCancelEdit').on('click', resetForm);

  /* ===== acciones de fila ===== */
  $('#tblItems').on('click','.btnDel',function(){
    const id=$(this).closest('tr').data('id');
    if(!confirm('¿Eliminar ítem?')) return;
    $.post(URL,{action:'eliminar_item',id_formulario:formActual,id_item:id},r=>{
      if(r.success){ listarItems(); if (editIdItem===id) resetForm(); showOkModal('Ítem eliminado'); }
      else showError(r.msg||'Error al eliminar');
    },'json').fail(()=>showError('Error de conexión'));
  });

  $('#tblItems').on('click','.btnEdit',function(){
    const id=$(this).closest('tr').data('id');
    $.post(URL,{action:'detalle_item',id_item:id},r=>{
      if(r.success){
        editMode=true; editIdItem=id;
        fillForm(r.data);
      }else showError(r.msg||'No se pudo obtener el ítem');
    },'json').fail(()=>showError('Error de conexión'));
  });

  /* ===== Subida / gestión de archivos ===== */
  $('#fileImg').on('change', function(){
    if(!editMode || !editIdItem){ alert('Guarda primero el ítem y luego sube la imagen'); this.value=''; return; }
    const f = this.files[0]; if(!f) return;

    if (f.size > MAX_SERVER) {
      showErrorModal(`El archivo (${fmtBytes(f.size)}) excede el límite del servidor (${LIMITS.uploadMax} / post ${LIMITS.postMax}).`, 'CLIENT_PRECHECK');
      this.value = '';
      return;
    }

    const fd = new FormData();
    fd.append('action','subir_archivo');
    fd.append('id_item',editIdItem);
    fd.append('tipo','img');
    fd.append('file',f);

    $.ajax({url:URL,type:'POST',data:fd,processData:false,contentType:false,dataType:'json'})
      .done(r=>{
        if(r.success){
          renderAssetState('img', r.url || null);
          listarItems();
          showOkItem(editIdItem);
        } else {
          showError(r.msg || 'Error al subir imagen');
          showErrorModal(r.msg || 'Error al subir imagen', r.code || '');
        }
      })
      .fail(jq=> handleAjaxFail(jq, 'Fallo al subir imagen'));
  });

  $('#tblImgState').on('click','.btnVerImg',function(){
    const url = $('#tblImgState').data('url');
    if(!url) return;
    $('#imgPreview').attr('src', url);
    $('#modalImg').modal('show');
  });

  $('#tblImgState').on('click','.btnBorrarImg',function(){
    if(!editIdItem) return;
    if(!confirm('¿Eliminar imagen del ítem?')) return;
    $.post(URL,{action:'borrar_archivo',id_item:editIdItem,tipo:'img'},r=>{
      if(r.success){ renderAssetState('img', null); listarItems(); showOkModal('Imagen eliminada'); }
      else showError(r.msg||'No se pudo borrar la imagen');
    },'json').fail(()=>showError('Error de conexión'));
  });

  $('#filePdf').on('change', function(){
    if(!editMode || !editIdItem){ alert('Guarda primero el ítem y luego sube el PDF'); this.value=''; return; }
    const f = this.files[0]; if(!f) return;

    if (f.size > MAX_SERVER) {
      showErrorModal(`El archivo (${fmtBytes(f.size)}) excede el límite del servidor (${LIMITS.uploadMax} / post ${LIMITS.postMax}).`, 'CLIENT_PRECHECK');
      this.value = '';
      return;
    }

    const fd = new FormData();
    fd.append('action','subir_archivo');
    fd.append('id_item',editIdItem);
    fd.append('tipo','pdf');
    fd.append('file',f);

    $.ajax({url:URL,type:'POST',data:fd,processData:false,contentType:false,dataType:'json'})
      .done(r=>{
        if(r.success){
          renderAssetState('pdf', r.url || null);
          listarItems();
          showOkItem(editIdItem);
        } else {
          showError(r.msg || 'Error al subir PDF');
          showErrorModal(r.msg || 'Error al subir PDF', r.code || '');
        }
      })
      .fail(jq=> handleAjaxFail(jq, 'Fallo al subir PDF'));
  });

  $('#tblPdfState').on('click','.btnVerPdf',function(){
    const url = $('#tblPdfState').data('url');
    if(url) window.open(url,'_blank');
  });

  $('#tblPdfState').on('click','.btnBorrarPdf',function(){
    if(!editIdItem) return;
    if(!confirm('¿Eliminar PDF del ítem?')) return;
    $.post(URL,{action:'borrar_archivo',id_item:editIdItem,tipo:'pdf'},r=>{
      if(r.success){ renderAssetState('pdf', null); listarItems(); showOkModal('PDF eliminado'); }
      else showError(r.msg||'No se pudo borrar el PDF');
    },'json').fail(()=>showError('Error de conexión'));
  });

  // ===== Formato (Word/Excel) =====
  $('#fileFormato').on('change', function(){
    if(!editMode || !editIdItem){ alert('Guarda primero el ítem y luego sube el Formato'); this.value=''; return; }
    const f = this.files[0]; if(!f) return;

    if (f.size > MAX_SERVER) {
      showErrorModal(`El archivo (${fmtBytes(f.size)}) excede el límite del servidor (${LIMITS.uploadMax} / post ${LIMITS.postMax}).`, 'CLIENT_PRECHECK');
      this.value = '';
      return;
    }
    const okExt = /\.(doc|docx|xls|xlsx)$/i.test(f.name);
    if (!okExt) {
      showErrorModal('Solo se permite Word/Excel (.doc, .docx, .xls, .xlsx)', 'CLIENT_MIME');
      this.value = '';
      return;
    }

    const fd = new FormData();
    fd.append('action','subir_archivo');
    fd.append('id_item',editIdItem);
    fd.append('tipo','formato');
    fd.append('file',f);

    $.ajax({url:URL,type:'POST',data:fd,processData:false,contentType:false,dataType:'json'})
      .done(r=>{
        if(r.success){
          renderAssetState('formato', r.url || null);
          listarItems();
          showOkItem(editIdItem);
        } else {
          showError(r.msg || 'Error al subir Formato');
          showErrorModal(r.msg || 'Error al subir Formato', r.code || '');
        }
      })
      .fail(jq=> handleAjaxFail(jq, 'Fallo al subir Formato'));
  });

  $('#tblFormatoState').on('click','.btnVerFormato',function(){
    const url = $('#tblFormatoState').data('url');
    if(url) window.open(url,'_blank');
  });

  $('#tblFormatoState').on('click','.btnBorrarFormato',function(){
    if(!editIdItem) return;
    if(!confirm('¿Eliminar Formato del ítem?')) return;
    $.post(URL,{action:'borrar_archivo',id_item:editIdItem,tipo:'formato'},r=>{
      if(r.success){ renderAssetState('formato', null); listarItems(); showOkModal('Formato eliminado'); }
      else showError(r.msg||'No se pudo borrar el Formato');
    },'json').fail(()=>showError('Error de conexión'));
  });

  /* ===== INIT ===== */
  cargarFormularios();
});
</script>
