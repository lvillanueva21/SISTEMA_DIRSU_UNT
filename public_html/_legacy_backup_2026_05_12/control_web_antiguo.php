<?php require_once __DIR__.'/cw_config.php'; ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Panel de Control Web</title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<style>
  .fila        { display:flex; align-items:center; gap:.5rem; }
  .handle      { cursor:move; }
  .sortable-placeholder{ height:40px; border:2px dashed #0d6efd; margin:.25rem 0 }
</style>
</head>

<body class="bg-light">

<div class="container py-4">
<h2 class="mb-4">Panel de Control Web</h2>

<div class="card shadow">
  <div class="card-header bg-primary text-white">Menú dinámico</div>
  <div class="card-body">

    <button id="btnNuevo" class="btn btn-success btn-sm mb-3">+ Nuevo ítem</button>

    <!-- ───────────── LISTA PRINCIPAL ───────────── -->
    <ul id="listaMenu" class="list-group sortable">
    <?php
      /* obtiene menú ordenado */
      $q = $mysqli->query("SELECT * FROM cw_opciones_menu ORDER BY COALESCE(parent_id,id), orden");
      $rows = [];
      while($r=$q->fetch_assoc()) $rows[]=$r;

      /* pinta <li><div class="fila">…</div><ul>… */
      function pinta($parent,&$rows){
        foreach($rows as $r) if($r['parent_id']===$parent){
          echo '<li class="list-group-item p-0" data-id="'.$r['id'].'">';
          echo '<div class="fila p-2">
                  <span class="handle">☰</span>
                  <input class="form-check-input visibleChk" type="checkbox" '.($r['visible']?'checked':'').'>
                  <input class="form-control form-control-sm w-25 textoInp" value="'.htmlspecialchars($r['texto']).'">
                  <input class="form-control form-control-sm urlInp" value="'.htmlspecialchars($r['url']).'">
                  <button class="btn btn-sm btn-secondary addSub">Sub+</button>
                  <button class="btn btn-sm btn-danger delItem">✕</button>
                </div>';
          echo   '<ul class="list-group ms-4 sortable">';
          pinta($r['id'],$rows);
          echo   '</ul></li>';
        }
      }
      pinta(null,$rows);
    ?>
    </ul>

    <button id="btnGuardar" class="btn btn-primary mt-3">Guardar cambios</button>
  </div>
</div>
</div>

<!-- card2 -->
 <div class="card shadow mt-5">
  <div class="card-header bg-primary text-white">Carrusel dinámico</div>
  <div class="card-body">
    <button id="btnNuevoSlide" class="btn btn-success btn-sm mb-3">+ Nuevo Slide</button>
    <ul id="listaCarrusel" class="list-group sortable">
    <?php
      $q = $mysqli->query("SELECT * FROM cw_carrusel ORDER BY orden");
      while($r = $q->fetch_assoc()){
echo '<li class="list-group-item p-0" data-id="'.$r['id'].'">
  <div class="fila p-2 flex-column align-items-start gap-2">
    <div class="d-flex w-100 align-items-center gap-2">
      <span class="handle">☰</span>
      <input class="form-check-input visibleChk" type="checkbox" '.($r['visible']?'checked':'').'> Mostrar slide
    </div>

    <input class="form-control form-control-sm tituloInp" placeholder="Título" value="'.htmlspecialchars($r['titulo']).'">
    <div class="form-check mb-1">
      <input class="form-check-input mostrarTituloChk" type="checkbox" '.($r['mostrar_titulo']?'checked':'').'> Mostrar título
    </div>

    <input class="form-control form-control-sm subtituloInp" placeholder="Subtítulo" value="'.htmlspecialchars($r['subtitulo']).'">
    <div class="form-check mb-1">
      <input class="form-check-input mostrarSubtituloChk" type="checkbox" '.($r['mostrar_subtitulo']?'checked':'').'> Mostrar subtítulo
    </div>

    <input type="file" class="form-control form-control-sm fileInp" accept="image/*">
    <img class="previewImg mt-2" src="'.htmlspecialchars($r['imagen']).'" style="max-height:80px;">
    <input type="hidden" class="urlInp" value="'.htmlspecialchars($r['imagen']).'">

    <button class="btn btn-sm btn-danger delItem mt-2">✕ Eliminar</button>
  </div>
</li>';
      }
    ?>
    </ul>
    <button id="btnGuardarCarrusel" class="btn btn-primary mt-3">Guardar Carrusel</button>
  </div>
</div>

 <!-- .card2 -->

<!-- Modal -->
<div class="modal fade" id="msgModal" tabindex="-1"><div class="modal-dialog">
  <div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"></h5>
      <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
    </div>
    <div class="modal-body"></div>
    <div class="modal-footer"><button class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button></div>
  </div></div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.2/jquery-ui.min.js"></script>

<script>
const uuid = ()=> crypto.randomUUID();

/*──────────── Sortable para TODAS las UL anidadas ────────────*/
function activarSortable(){
  $(".sortable").sortable({
    items       :"> li",
    handle      :".handle",
    placeholder :"sortable-placeholder",
    connectWith :".sortable",
    tolerance   :"pointer"
  }).disableSelection();
}
activarSortable();

/*──────────── Nuevo ítem de primer nivel ────────────*/
$("#btnNuevo").on("click", ()=>{
  const id = uuid();
  $("#listaMenu").append(`
    <li class="list-group-item p-0" data-id="${id}">
      <div class="fila p-2">
        <span class="handle">☰</span>
        <input class="form-check-input visibleChk" type="checkbox" checked>
        <input class="form-control form-control-sm w-25 textoInp" placeholder="Texto">
        <input class="form-control form-control-sm urlInp" placeholder="URL">
        <button class="btn btn-sm btn-secondary addSub">Sub+</button>
        <button class="btn btn-sm btn-danger delItem">✕</button>
      </div>
      <ul class="list-group ms-4 sortable"></ul>
    </li>`);
  activarSortable();
});

/*──────────── Añadir sub-menú ────────────*/
$(document).on("click",".addSub", function(){
  const $ul = $(this).closest("li").children("ul.sortable");
  const id  = uuid();
  $ul.append(`
    <li class="list-group-item p-0" data-id="${id}">
      <div class="fila p-2">
        <span class="handle">☰</span>
        <input class="form-check-input visibleChk" type="checkbox" checked>
        <input class="form-control form-control-sm w-25 textoInp" placeholder="Texto">
        <input class="form-control form-control-sm urlInp" placeholder="URL">
        <button class="btn btn-sm btn-danger delItem">✕</button>
      </div>
      <ul class="list-group ms-4 sortable"></ul>
    </li>`);
  activarSortable();
});

/*──────────── Eliminar ítem ────────────*/
$(document).on("click",".delItem", function(){
  $(this).closest("li").remove();
});

/*──────────── Guardar ────────────*/
$("#btnGuardar").on("click", ()=>{
  const datos = [], err = {flag:false};

  function recorrer($ul,parent){
    $ul.children("li").each(function(i){
      const $li   = $(this);
      const txt   = $li.find(".textoInp").val().trim();
      const url   = $li.find(".urlInp").val().trim();
      if(!txt||!url) { err.flag=true; return false; }

      datos.push({
        id        : $li.data("id"),
        parent_id : parent,
        texto     : txt,
        url       : url,
        visible   : $li.find(".visibleChk").is(":checked")?1:0,
        orden     : i
      });
      recorrer($li.children("ul.sortable"), $li.data("id"));
    });
  }
  recorrer($("#listaMenu"), null);

  if(err.flag){ modal('Error','Todos los campos Texto y URL son obligatorios','bg-danger'); return; }

  $.post("save_menu.php",{data:JSON.stringify(datos)}, r=>{
     const res = JSON.parse(r);
     if(res.status==='ok') modal('Éxito',res.msg,'bg-success');
     else                  modal('Error',res.msg,'bg-danger');
  }).fail(xhr=>{
     let m='Error inesperado';
     try{m=JSON.parse(xhr.responseText).msg}catch(e){}
     modal('Error',m,'bg-danger');
  });
});

/*──────────── Modal helper ────────────*/
function modal(title,body,cls){
  const $m = $("#msgModal");
  $m.find(".modal-title").text(title);
  $m.find(".modal-header").removeClass("bg-success bg-danger").addClass(cls||'');
  $m.find(".modal-body").html(body);
  bootstrap.Modal.getOrCreateInstance($m[0]).show();
}
</script>
<script>
$("#btnNuevoSlide").on("click", ()=>{
  const id = uuid();
  $("#listaCarrusel").append(`
    <li class="list-group-item p-0" data-id="${id}">
      <div class="fila p-2 flex-column align-items-start gap-2">
        <div class="d-flex w-100 align-items-center gap-2">
          <span class="handle">☰</span>
          <input class="form-check-input visibleChk" type="checkbox" checked> Mostrar slide
        </div>

        <input class="form-control form-control-sm tituloInp" placeholder="Título">
        <div class="form-check mb-1">
          <input class="form-check-input mostrarTituloChk" type="checkbox" checked> Mostrar título
        </div>

        <input class="form-control form-control-sm subtituloInp" placeholder="Subtítulo">
        <div class="form-check mb-1">
          <input class="form-check-input mostrarSubtituloChk" type="checkbox" checked> Mostrar subtítulo
        </div>

        <input type="file" class="form-control form-control-sm fileInp" accept="image/*">
        <img class="previewImg mt-2" src="" style="max-height:80px; display:none;">
        <input type="hidden" class="urlInp" value="">

        <button class="btn btn-sm btn-danger delItem mt-2">✕ Eliminar</button>
      </div>
    </li>`);
  activarSortable();
});

$("#btnGuardarCarrusel").on("click", ()=>{
  const datos = [];
  let error = false;
  $("#listaCarrusel li").each(function(i){
    const $li = $(this);
    const imagen = $li.find(".urlInp").val().trim();
    if(!imagen){ error = true; return false; }

    datos.push({
      id: $li.data("id"),
      imagen,
      orden: i,
      visible: $li.find(".visibleChk").is(":checked") ? 1 : 0,
      titulo: $li.find(".tituloInp").val().trim(),
      mostrar_titulo: $li.find(".mostrarTituloChk").is(":checked") ? 1 : 0,
      subtitulo: $li.find(".subtituloInp").val().trim(),
      mostrar_subtitulo: $li.find(".mostrarSubtituloChk").is(":checked") ? 1 : 0
    });
  });
  if(error) return modal('Error','Todos los slides deben tener imagen','bg-danger');

$.post("pagina_web/cw_save_carrusel.php", {data: JSON.stringify(datos)})
  .done(res=>{
      const r = JSON.parse(res);
      if (r.status==='ok') modal('Éxito', r.msg, 'bg-success');
      else                  modal('Error', r.msg, 'bg-danger');
  })
  .fail(xhr=>{
      modal('Error', 'Error ' + xhr.status + ': ' + xhr.statusText, 'bg-danger');
  });

});
</script>
<script>
$(document).on("change", ".fileInp", function(){
  const file = this.files[0];
  const $li = $(this).closest("li");
  const $preview = $li.find(".previewImg");
  const $hidden = $li.find(".urlInp");

  if (!file) return;

  const formData = new FormData();
  formData.append("file", file);

  fetch("pagina_web/cw_upload_carrusel.php", {
    method: "POST",
    body: formData
  })
  .then(r => r.json())
  .then(res => {
    if(res.file){
      $hidden.val(res.file);
      $preview.attr("src", res.file);
    } else {
      alert("Error: " + res.error);
    }
  })
  .catch(err => alert("Error al subir imagen"));
});
</script>
</body>
</html>
