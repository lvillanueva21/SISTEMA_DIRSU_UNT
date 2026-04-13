<?php
/*********************************************************************
 *  control_usuarios.php  –  Filtro + tabla + paginación (sin AJAX)
 *--------------------------------------------------------------------
 *  · Carpeta : reportes/
 *  · Inclúyelo con  include("reportes/control_usuarios.php");
 *  · Requiere : ../../componentes/configSesion.php  y  ../../componentes/db.php
 *********************************************************************/
include "../../componentes/configSesion.php";
include "../../componentes/db.php";

/*════════════ 1.  PARÁMETROS GET ════════════*/
$f_rol = $_GET['f_rol']         ?? 'all';           // 0,1,2,2d,3,4,5,all
$f_fac = $_GET['f_facultad']    ?? '';              // id facultad
$f_dep = $_GET['f_departamento']?? '';              // id departamento
$f_txt = trim($_GET['f_texto']  ?? '');             // búsqueda
$len   = $_GET['len']           ?? 20;               // 5,10,50,all
$pag   = max(1, (int)($_GET['pag'] ?? 1));

$limit = ($len==='all') ? 50000 : (int)$len;

/*════════════ 2.  WHERE DINÁMICO ════════════*/
$where = [];  $bind = [];  $types = '';

# ░░ Rol
if ($f_rol!=='all'){
    if ($f_rol==='2d'){                         // Descontinuado
        $where[]="(u.id_rol=2 AND CHAR_LENGTH(u.usuario)<>4)";
    }elseif($f_rol==='2'){                      // Coordinador
        $where[]="(u.id_rol=2 AND CHAR_LENGTH(u.usuario)=4)";
    }else{
        $where[]="u.id_rol=?";
        $bind[]=$f_rol;  $types.='i';
    }
}

# ░░ Departamento
if ($f_dep!==''){
    $where[]="u.id_depa=?";
    $bind[]=$f_dep;  $types.='i';
}

# ░░ Facultad  (según rol)
if ($f_fac!==''){
    $where[]="
        (
          (u.id_rol IN (3,5) AND u.id_escuela=?)
          OR (u.id_rol IN (2,4) AND EXISTS
                (SELECT 1 FROM departamentos d2
                  WHERE d2.id=u.id_depa AND d2.id_facultad=?)
             )
        )";
    $bind[]=$f_fac; $bind[]=$f_fac;  $types.='ii';
}

# ░░ Texto
if ($f_txt!==''){
    $pat='%'.$f_txt.'%';
    $where[]="(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)";
    array_push($bind,$pat,$pat,$pat);
    $types.='sss';
}

$where_sql = $where ? 'WHERE '.implode(' AND ',$where) : '';

/*════════════ 3.  CONTEO TOTAL ════════════*/
$sqlCnt = "SELECT COUNT(*) FROM usuarios u $where_sql";
$stmt   = mysqli_prepare($conexion,$sqlCnt);
if($bind) mysqli_stmt_bind_param($stmt,$types,...$bind);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt,$total);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

$paginas = $limit ? max(1,ceil($total/$limit)) : 1;
if ($pag > $paginas) $pag = $paginas;
$offset = ($pag-1)*$limit;

/*════════════ 4.  SELECT PRINCIPAL ════════════*/
$sql = "
SELECT u.*,
       d.nombre         AS dep,
       f.nombre         AS fac,
       hu.adicional     AS clave
FROM usuarios u
LEFT JOIN departamentos d ON d.id = u.id_depa
LEFT JOIN facultades   f ON (
      (u.id_rol IN (3,5) AND f.id=u.id_escuela)
   OR (u.id_rol IN (2,4) AND f.id=d.id_facultad)
)
LEFT JOIN (
  SELECT h1.id_usuario, h1.adicional
    FROM historial_usuarios h1
    INNER JOIN (
        SELECT id_usuario, MAX(id) maxid
          FROM historial_usuarios
      GROUP BY id_usuario
    ) h2 ON h2.id_usuario=h1.id_usuario AND h2.maxid=h1.id
) hu ON hu.id_usuario=u.id
$where_sql
ORDER BY u.id DESC
LIMIT ?,?";
$bind2=$bind; $types2=$types.'ii';
$bind2[]=$offset; $bind2[]=$limit;

$stmt = mysqli_prepare($conexion,$sql);
if($bind2) mysqli_stmt_bind_param($stmt,$types2,...$bind2);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

/*════════════ 5.  HELPERS ════════════*/
function etiquetaRol($id,$user){
    return [
        0=>'Administrador',
        1=>'Dirección RSU',
        2=>(strlen($user)==4?'Coordinador Proyecto':'Descontinuado'),
        3=>'Decanato Facultad',
        4=>'Dirección Departamento',
        5=>'Presidente Comité'
    ][$id] ?? '—';
}
$n_inicio = $offset+1;
$optsLen  = [20, 50, 100, 'all'];

/*════════════ 6.  ESTILOS ════════════*/ ?>
<style>
.lbl-fac{background:#d1c4e9;color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.lbl-dep{background:rgb(250,238,189);color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.lbl-rsu{background:#99ff99;color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.btn-editar{background:#0050EF;color:#fff;}
.btn-py{background:#FFDD00;color:#000;}
.btn-copiar{background:#647687;color:#fff;}
thead th{background:#b2fab4;text-align:center;}
tbody td{vertical-align:middle;}
</style>

<!--════════════ 7.  FORMULARIO DE FILTRO ════════════-->
<form class="mb-3" method="get">
  <div class="form-row align-items-end">

    <div class="col-md-2">
      <label>Rol</label>
      <select name="f_rol" class="form-control">
        <?php
          $rolesSel = [
            'all'=>'Todos','0'=>'Administrador','1'=>'Dirección RSU',
            '2'=>'Coordinador Proyecto','2d'=>'Descontinuado',
            '3'=>'Decanato Facultad','4'=>'Dirección Departamento','5'=>'Presidente Comité'
          ];
          foreach($rolesSel as $v=>$t){
              $sel = ($f_rol==$v)?'selected':'';
              echo "<option value=\"$v\" $sel>$t</option>";
          }
        ?>
      </select>
    </div>

    <div class="col-md-3">
      <label>Facultad</label>
      <select name="f_facultad" id="f_facultad" class="form-control">
        <option value="">Todos</option>
        <?php
          $q="SELECT id,nombre FROM facultades WHERE id<>0 ORDER BY nombre";
          $rs=mysqli_query($conexion,$q);
          while($f=mysqli_fetch_assoc($rs)){
              $sel=($f_fac==$f['id'])?'selected':'';
              echo "<option value=\"{$f['id']}\" $sel>".htmlspecialchars($f['nombre'])."</option>";
          }
        ?>
      </select>
    </div>

    <div class="col-md-3">
      <label>Departamento</label>
      <select name="f_departamento" id="f_departamento" class="form-control">
        <option value="">Todos</option>
        <?php
$q="SELECT d.id,d.nombre,f.id fid,f.nombre fac
      FROM departamentos d
      JOIN facultades f ON f.id=d.id_facultad
     WHERE d.id<>0
  ORDER BY d.nombre ASC";
          $rs=mysqli_query($conexion,$q);
          while($d=mysqli_fetch_assoc($rs)){
              $sel=($f_dep==$d['id'])?'selected':'';
              echo "<option value=\"{$d['id']}\" data-fac=\"{$d['fid']}\" $sel>"
                  .htmlspecialchars($d['nombre'])."</option>";
          }
        ?>
      </select>
    </div>

    <div class="col-md-3">
      <label>Texto (código, nombres, apellidos)</label>
      <input type="text" name="f_texto" class="form-control" value="<?= htmlspecialchars($f_txt) ?>">
    </div>

    <div class="col-md-1">
      <button class="btn btn-primary btn-block"><i class="fas fa-filter"></i></button>
    </div>
  </div>
</form>

<!-- Selector de cantidad + Botón limpiar -->
<div class="mb-2 d-flex justify-content-between">
  <div>
    <span class="mr-2 font-weight-bold">Cantidad:</span>
    <?php
      foreach($optsLen as $o){
          $class=($o==$len || ($o==='all' && $len==='all'))?'btn-primary':'btn-outline-primary';
          $link=array_merge($_GET,['len'=>$o,'pag'=>1]);
          echo '<a class="btn '.$class.' btn-sm" href="?'.http_build_query($link).'">'
              .($o==='all'?'TODOS':$o).'</a> ';
      }
    ?>
  </div>
  <div>
    <a href="?len=20&pag=1" class="btn btn-secondary btn-sm">
      <i class="fas fa-broom"></i> Limpiar
    </a>
  </div>
</div>

<!--════════════ 8.  TABLA ════════════-->
<div class="table-responsive">
<table class="table table-bordered table-sm">
  <thead>
    <tr>
      <th style="width:55px">#</th>
      <th>ID</th>
      <th>USUARIO</th>
      <th>NOMBRE</th>
      <th>CLAVE</th>
      <th>ROL</th>
      <th style="width:180px">ACCIONES</th>
    </tr>
  </thead>
  <tbody>
  <?php while($u=mysqli_fetch_assoc($res)): ?>
    <tr>
      <td class="text-right"><?= $n_inicio++ ?></td>
      <td class="text-right"><?= $u['id'] ?></td>
      <td><?= htmlspecialchars($u['usuario']) ?></td>
      <td>
          <?= htmlspecialchars($u['nombres'].' '.$u['apellidos']) ?><br>
          <?php if(in_array($u['id_rol'],[0,1])): ?>
              <span class="lbl-rsu">RSU</span>
          <?php else: ?>
              <?php if($u['fac']): ?><span class="lbl-fac"><?= htmlspecialchars($u['fac']) ?></span><?php endif; ?>
              <?php if($u['dep']): ?><span class="lbl-dep"><?= htmlspecialchars($u['dep']) ?></span><?php endif; ?>
          <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($u['clave'] ?? '—') ?></td>
      <td><?= etiquetaRol($u['id_rol'],$u['usuario']) ?></td>
      <td class="text-center">
<button
  class="btn btn-sm btn-editar"
  data-id="<?= $u['id'] ?>"
  data-usuario="<?= htmlspecialchars($u['usuario']) ?>"
  data-nombres="<?= htmlspecialchars($u['nombres']) ?>"
  data-apellidos="<?= htmlspecialchars($u['apellidos']) ?>"
  data-depa="<?= $u['id_depa'] ?>"
  data-rol="<?= $u['id_rol'] ?>"
  data-clave="<?= htmlspecialchars($u['clave']) ?>"
>
  Editar
</button>
        <button class="btn btn-sm btn-py">Py</button>
        <button class="btn btn-sm btn-copiar">Copiar</button>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>

<!--════════════ 9.  PAGINACIÓN ════════════-->
<?php
$qBase=$_GET; unset($qBase['pag']);
?>
<?php
function pagLink($i,$pag,$qBase){
    $qBase['pag']=$i; $link='?'.http_build_query($qBase);
    $active=($i==$pag)?'active':'';
    return "<li class='page-item $active'><a class='page-link' href='$link'>$i</a></li>";
}
?>
<nav>
  <ul class="pagination pagination-sm justify-content-center">
    <?php
    if($paginas>1){
        echo pagLink(1,$pag,$qBase);
        if($pag>4) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
        for($i=max(2,$pag-2); $i<=min($paginas-1,$pag+2); $i++){
            echo pagLink($i,$pag,$qBase);
        }
        if($pag<$paginas-3) echo "<li class='page-item disabled'><span class='page-link'>…</span></li>";
        if($paginas>1) echo pagLink($paginas,$pag,$qBase);
    }
    ?>
  </ul>
</nav>
<!-- ════════════ MODAL – DETALLES DEL USUARIO ════════════ -->
<div class="modal fade" id="modalEditUser" tabindex="-1" role="dialog">
 <div class="modal-dialog modal-lg" role="document">
  <div class="modal-content">
   <div class="modal-header py-2">
     <h5 class="modal-title mb-0">Detalles del usuario</h5>
     <button type="button" class="close" data-dismiss="modal">&times;</button>
   </div>

   <form id="formEditUser">
    <div class="modal-body">

     <div class="form-row">
       <div class="form-group col-md-6">
         <label>Rol:</label>
         <input class="form-control" id="euRol" readonly>
       </div>
       <div class="form-group col-md-6">
         <label>Id:</label>
         <input class="form-control" id="euId" readonly>
       </div>
     </div>

     <div class="form-row">
       <div class="form-group col-md-6">
         <label>Nombres:</label>
         <input type="text" class="form-control" name="nombres" id="euNombres" required>
       </div>
       <div class="form-group col-md-6">
         <label>Apellidos:</label>
         <input type="text" class="form-control" name="apellidos" id="euApellidos" required>
       </div>
     </div>

     <div class="form-row">
       <div class="form-group col-md-6">
         <label>Usuario:</label>
         <input type="text" class="form-control" name="usuario" id="euUsuario" required>
       </div>
       <div class="form-group col-md-6">
         <label>Clave:</label>
         <input type="password" class="form-control" name="clave" id="euClave"
                placeholder="Ingrese una nueva clave …">
       </div>
     </div>

     <div class="form-row">
       <div class="form-group col-md-6">
         <label>Facultad:</label>
         <input class="form-control" id="euFacultad" readonly>
       </div>
       <div class="form-group col-md-6">
         <label>Departamento:</label>
         <select class="form-control" name="id_depa" id="euDepa" required></select>
       </div>
     </div>

     <div id="euMsg" class="alert alert-danger d-none"></div>
    </div>

    <div class="modal-footer">
      <button type="submit" class="btn btn-primary btn-block">Actualizar</button>
    </div>
   </form>
  </div>
 </div>
</div>
<!-- ════════════ /MODAL ════════════ -->

<!--════════════ 10.  JS SINCRONIZACIÓN FAC ↔ DEP ════════════-->
<!--════════════ 10.  JS FACULTAD ↔ DEPARTAMENTO ════════════-->
<script>
$(function(){

  const $fac = $('#f_facultad');
  const $dep = $('#f_departamento');

  /* 1)  Oculta / muestra departamentos según la facultad escogida  */
  function filtrarDepartamentos(){
      const idFac = $fac.val();          // '' = Todas las facultades
      $dep.find('option').each(function(){
          const depFac = $(this).data('fac');   // undefined para «Todos»
          /* Mostrar si:  – no hay facultad elegida
                           – o el depto pertenece a la facultad */
          const visible = (idFac === '' || depFac == idFac || $(this).val()==='');
          $(this).toggle(visible);
      });
// Solo limpiar si hay facultad seleccionada y el departamento no pertenece a esa facultad
if (idFac !== '' && $dep.find('option:selected').data('fac') != idFac) {
    $dep.val('');
}
  }

  /* 2)  Cuando cambie FACULTAD filtramos departamentos         */
  $fac.on('change', filtrarDepartamentos);

  /* 3)  Cuando cambie DEPARTAMENTO sincronizamos FACULTAD      */
$dep.on('change', function(){
    const facDeDep = $dep.find('option:selected').data('fac');
    if($(this).val() !== '' && facDeDep){          
        $fac.val(facDeDep);   // Solo sincronizamos la facultad
        // ❌ No llamamos de nuevo a filtrarDepartamentos()
    }
});

  /* 4)  Ejecutar al cargar la página (por URL con filtros)     */
  filtrarDepartamentos();

});
</script>
<script>
$(function () {

  /* 1.  Obtener departamentos vía AJAX (una sola vez) */
  let DEPS = [];
  $.getJSON('../componentes/usuarios/listar_departamentos.php', d => { DEPS = d; });

  /* 2.  Cargar datos en el modal al hacer clic en Editar */
  $(document).on('click', '.btn-editar', function () {
      const $b = $(this);

      $('#euId').val($b.data('id'));
// ——— mapa de roles (igual que la función etiquetaRol() en PHP) ———
const ROLES = {
    0 : 'Administrador',
    1 : 'Dirección RSU',
    2 : 'Coordinador de proyecto',
    3 : 'Decanato de Facultad',
    4 : 'Dirección de Departamento',
    5 : 'Comité RS de Facultad'
};
$('#euRol').val( ROLES[$b.data('rol')] ?? '—' );
      $('#euNombres').val($b.data('nombres'));
      $('#euApellidos').val($b.data('apellidos'));
      $('#euUsuario').val($b.data('usuario'));
      $('#euClave').val('');

      // Renderizar select de departamentos
const idDepSel = $b.data('depa');
const $sel = $('#euDepa').empty();

// Primera opción: sin departamento
$sel.append(
    $('<option>', { value: '', text: 'Sin Departamento Académico' })
);

DEPS.forEach(d => {
    const $o = $('<option>', {
        value: d.id,
        text : d.nombre,
        'data-fac': d.facultad
    });
    if (d.id == idDepSel) $o.attr('selected', true);
    $sel.append($o);
});

// Facultad
$('#euFacultad').val(
    DEPS.find(x => x.id == idDepSel)?.facultad || 'Sin Facultad'
);

      $('#euMsg').addClass('d-none');         // Limpia mensaje de error
      $('#modalEditUser').modal('show');
  });

  /* 3.  Cuando cambia el departamento dentro del modal */
  $('#euDepa').on('change', function () {
      const fac = $('#euDepa option:selected').data('fac') || '';
      $('#euFacultad').val(fac);
  });

  /* 4.  Envío del formulario */
  $('#formEditUser').on('submit', function (e) {
      e.preventDefault();
      const data = $(this).serialize() + '&id=' + $('#euId').val();

      $.post('../componentes/usuarios/actualizar_usuario.php', data, resp => {
          if (resp.ok) {
              location.reload();
          } else {
              $('#euMsg').removeClass('d-none').text(resp.msg);
          }
      }, 'json')
      .fail(() => $('#euMsg').removeClass('d-none')
                             .text('Error inesperado. Intente nuevamente'));
  });

});
</script>


