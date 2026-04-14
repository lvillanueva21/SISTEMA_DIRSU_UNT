<?php
/*********************************************************************
 *  control_usuarios.php  -  Filtro + tabla + paginacion (sin AJAX)
 *--------------------------------------------------------------------
 *  - Carpeta : reportes/
 *  - Incluyelo con  include("reportes/control_usuarios.php");
 *  - Requiere : ../../componentes/configSesion.php  y  ../../componentes/db.php
 *********************************************************************/
include "../../componentes/configSesion.php";
include "../../componentes/db.php";

/*============ 1.  PARAMETROS GET ============*/
$f_rol = $_GET['f_rol']         ?? 'all';           // 0,1,2,2d,3,4,5,all
$f_fac = $_GET['f_facultad']    ?? '';              // id facultad
$f_dep = $_GET['f_departamento']?? '';              // id departamento
$f_txt = trim($_GET['f_texto']  ?? '');             // busqueda
$len   = $_GET['len']           ?? 20;               // 5,10,50,all
$pag   = max(1, (int)($_GET['pag'] ?? 1));

$limit = ($len==='all') ? 50000 : (int)$len;

/*============ 2.  WHERE DINAMICO ============*/
$where = [];  $bind = [];  $types = '';

# -- Rol
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

# -- Departamento
if ($f_dep!==''){
    $where[]="u.id_depa=?";
    $bind[]=$f_dep;  $types.='i';
}

# -- Facultad  (segun rol)
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

# -- Texto
if ($f_txt!==''){
    $pat='%'.$f_txt.'%';
    $where[]="(u.usuario LIKE ? OR u.nombres LIKE ? OR u.apellidos LIKE ?)";
    array_push($bind,$pat,$pat,$pat);
    $types.='sss';
}

$where_sql = $where ? 'WHERE '.implode(' AND ',$where) : '';

/*============ 3.  CONTEO TOTAL ============*/
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

/*============ 4.  SELECT PRINCIPAL ============*/
$sql = "
SELECT u.*,
       d.nombre         AS dep,
       f.nombre         AS fac,
       hu.adicional     AS clave,
       hs.descripcion   AS estado_logico_desc
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
         WHERE adicional IS NOT NULL AND TRIM(adicional) <> ''
      GROUP BY id_usuario
    ) h2 ON h2.id_usuario=h1.id_usuario AND h2.maxid=h1.id
) hu ON hu.id_usuario=u.id
LEFT JOIN (
  SELECT h3.id_usuario, h3.descripcion
    FROM historial_usuarios h3
    INNER JOIN (
        SELECT id_usuario, MAX(id) maxid
          FROM historial_usuarios
         WHERE descripcion LIKE 'Estado de usuario:%'
      GROUP BY id_usuario
    ) h4 ON h4.id_usuario=h3.id_usuario AND h4.maxid=h3.id
) hs ON hs.id_usuario=u.id
$where_sql
ORDER BY u.id DESC
LIMIT ?,?";
$bind2=$bind; $types2=$types.'ii';
$bind2[]=$offset; $bind2[]=$limit;

$stmt = mysqli_prepare($conexion,$sql);
if($bind2) mysqli_stmt_bind_param($stmt,$types2,...$bind2);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

/*============ 5.  HELPERS ============*/
function etiquetaRol($id,$user){
    return [
        0=>'Administrador',
        1=>'Direccion RSU',
        2=>(strlen($user)==4?'Coordinador Proyecto':'Descontinuado'),
        3=>'Decanato Facultad',
        4=>'Direccion Departamento',
        5=>'Presidente Comit?'
    ][$id] ?? '-';
}

function usuarioActivoDesdeHistorial($descripcion)
{
    $descripcion = strtolower(trim((string)$descripcion));
    if ($descripcion === '') {
        return true;
    }
    return strpos($descripcion, 'desactivado') === false;
}
$n_inicio = $offset+1;
$optsLen  = [20, 50, 100, 'all'];

$facultades = [];
$rsFac = mysqli_query($conexion, "SELECT id, nombre FROM facultades WHERE id<>0 ORDER BY nombre ASC");
if ($rsFac) {
    while ($rowFac = mysqli_fetch_assoc($rsFac)) {
        $facultades[] = $rowFac;
    }
}

$departamentos = [];
$rsDep = mysqli_query($conexion, "SELECT d.id, d.nombre, f.id AS id_facultad, f.nombre AS facultad
                                  FROM departamentos d
                                  JOIN facultades f ON f.id=d.id_facultad
                                  WHERE d.id<>0
                                  ORDER BY d.nombre ASC");
if ($rsDep) {
    while ($rowDep = mysqli_fetch_assoc($rsDep)) {
        $departamentos[] = $rowDep;
    }
}

/*============ 6.  ESTILOS ============*/ ?>
<style>
.lbl-fac{background:#d1c4e9;color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.lbl-dep{background:rgb(250,238,189);color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.lbl-rsu{background:#99ff99;color:#000;padding:2px 6px;border-radius:4px;font-size:.75rem;}
.btn-editar{background:#0050EF;color:#fff;}
.btn-contacto{background:#FFDD00;color:#000;}
.btn-copiar{background:#647687;color:#fff;}
.btn-toggle-user.active{background:#2e7d32;color:#fff;}
.btn-toggle-user.inactive{background:#7f8c8d;color:#fff;}
.btn-trash-user{background:#c62828;color:#fff;}
.user-inactive-row{background:#f9f1f1;}
.user-inactive-row td{color:#7a7a7a;}
.user-notify-wrap{position:fixed;top:75px;right:20px;z-index:1061;min-width:320px;max-width:420px;}
.action-icon-btn{width:32px;height:30px;padding:0;display:inline-flex;align-items:center;justify-content:center;}
.contact-inline-chip{display:inline-block;padding:2px 8px;border-radius:12px;background:#e9ecef;color:#495057;font-size:.78rem;}
thead th{background:#b2fab4;text-align:center;}
tbody td{vertical-align:middle;}
.toolbar-actions .btn{margin-left:4px;margin-bottom:4px;}
</style>

<!--============ 7.  FORMULARIO DE FILTRO ============-->
<form class="mb-3" method="get">
  <input type="hidden" name="len" value="<?= htmlspecialchars((string)$len) ?>">
  <input type="hidden" name="pag" value="1">

  <div class="form-row align-items-end">
    <div class="col-md-2 mb-2">
      <label>Rol</label>
      <select name="f_rol" class="form-control">
        <?php
          $rolesSel = [
            'all'=>'Todos','0'=>'Administrador','1'=>'Direccion RSU',
            '2'=>'Coordinador Proyecto','2d'=>'Descontinuado',
            '3'=>'Decanato Facultad','4'=>'Direccion Departamento','5'=>'Presidente Comit?'
          ];
          foreach($rolesSel as $v=>$t){
              $sel = ($f_rol==$v)?'selected':'';
              echo "<option value=\"$v\" $sel>$t</option>";
          }
        ?>
      </select>
    </div>

    <div class="col-md-3 mb-2">
      <label>Facultad</label>
      <select name="f_facultad" id="f_facultad" class="form-control">
        <option value="">Todos</option>
        <?php foreach($facultades as $facItem): ?>
          <option value="<?= (int)$facItem['id'] ?>" <?= ((string)$f_fac === (string)$facItem['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($facItem['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-3 mb-2">
      <label>Departamento</label>
      <select name="f_departamento" id="f_departamento" class="form-control">
        <option value="">Todos</option>
        <?php foreach($departamentos as $depItem): ?>
          <option value="<?= (int)$depItem['id'] ?>"
                  data-fac="<?= (int)$depItem['id_facultad'] ?>"
                  data-fac-name="<?= htmlspecialchars($depItem['facultad']) ?>"
                  <?= ((string)$f_dep === (string)$depItem['id']) ? 'selected' : '' ?>>
            <?= htmlspecialchars($depItem['nombre']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="col-md-4 mb-2">
      <label>Texto (codigo, nombres, apellidos)</label>
      <input type="text" name="f_texto" class="form-control" value="<?= htmlspecialchars($f_txt) ?>">
    </div>
  </div>

  <div class="form-row align-items-center">
    <div class="col-md-6 mb-2">
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
    <div class="col-md-6 mb-2 text-md-right toolbar-actions">
      <button class="btn btn-primary btn-sm" type="submit">
        <i class="fas fa-filter"></i> Filtrar
      </button>
      <a href="?len=20&pag=1" class="btn btn-secondary btn-sm">
        <i class="fas fa-broom"></i> Limpiar
      </a>
      <button type="button" id="btnExportPdf" class="btn btn-outline-danger btn-sm">
        <i class="far fa-file-pdf"></i> PDF
      </button>
      <button type="button" id="btnExportExcel" class="btn btn-outline-success btn-sm">
        <i class="far fa-file-excel"></i> EXCEL
      </button>
      <button type="button" id="btnOpenCreateUser" class="btn btn-success btn-sm" data-toggle="modal" data-target="#modalCreateUser">
        <i class="fas fa-user-plus"></i> Crear
      </button>
    </div>
  </div>
</form>

<!--============ 8.  TABLA ============-->
<div class="table-responsive">
<table class="table table-bordered table-sm" id="tablaUsuariosGeneral">
  <thead>
    <tr>
      <th style="width:55px">#</th>
      <th>ID</th>
      <th>USUARIO</th>
      <th>NOMBRE</th>
      <th>CLAVE</th>
      <th>ROL</th>
      <th style="width:240px">ACCIONES</th>
    </tr>
  </thead>
  <tbody>
  <?php while($u=mysqli_fetch_assoc($res)): ?>
    <?php
      $rolTexto = etiquetaRol($u['id_rol'],$u['usuario']);
      $claveTexto = trim((string)($u['clave'] ?? '')) !== '' ? (string)$u['clave'] : '-';
      $usuarioActivo = usuarioActivoDesdeHistorial($u['estado_logico_desc'] ?? '');
      $estadoTextoBtn = $usuarioActivo ? 'Desactivar usuario' : 'Activar usuario';
      $iconoEstado = $usuarioActivo ? 'fa-toggle-on' : 'fa-toggle-off';
      $claseEstado = $usuarioActivo ? 'active' : 'inactive';
    ?>
    <tr
      class="js-user-row<?= $usuarioActivo ? '' : ' user-inactive-row' ?>"
      data-id="<?= (int)$u['id'] ?>"
      data-usuario="<?= htmlspecialchars((string)$u['usuario'], ENT_QUOTES, 'UTF-8') ?>"
      data-nombres="<?= htmlspecialchars((string)$u['nombres'], ENT_QUOTES, 'UTF-8') ?>"
      data-apellidos="<?= htmlspecialchars((string)$u['apellidos'], ENT_QUOTES, 'UTF-8') ?>"
      data-rol="<?= (int)$u['id_rol'] ?>"
      data-rol-texto="<?= htmlspecialchars((string)$rolTexto, ENT_QUOTES, 'UTF-8') ?>"
      data-depa="<?= (int)$u['id_depa'] ?>"
      data-dep="<?= htmlspecialchars((string)($u['dep'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      data-fac="<?= htmlspecialchars((string)($u['fac'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
      data-clave="<?= htmlspecialchars((string)$claveTexto, ENT_QUOTES, 'UTF-8') ?>"
      data-activo="<?= $usuarioActivo ? '1' : '0' ?>"
      data-id-py="<?= (int)($u['id_py'] ?? 0) ?>"
    >
      <td class="text-right"><?= $n_inicio++ ?></td>
      <td class="text-right"><?= (int)$u['id'] ?></td>
      <td><?= htmlspecialchars((string)$u['usuario']) ?></td>
      <td>
          <?= htmlspecialchars((string)$u['nombres'].' '.(string)$u['apellidos']) ?><br>
          <?php if(in_array((int)$u['id_rol'], [0,1], true)): ?>
              <span class="lbl-rsu">RSU</span>
          <?php else: ?>
              <?php if(!empty($u['fac'])): ?><span class="lbl-fac"><?= htmlspecialchars((string)$u['fac']) ?></span><?php endif; ?>
              <?php if(!empty($u['dep'])): ?><span class="lbl-dep"><?= htmlspecialchars((string)$u['dep']) ?></span><?php endif; ?>
          <?php endif; ?>
      </td>
      <td><?= htmlspecialchars($claveTexto) ?></td>
      <td><?= htmlspecialchars($rolTexto) ?></td>
      <td class="text-center">
        <button
          type="button"
          class="btn btn-sm btn-editar action-icon-btn"
          title="Editar usuario"
          aria-label="Editar usuario"
        >
          <i class="fas fa-pencil-alt"></i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-contacto action-icon-btn"
          title="Ver informacion de contacto"
          aria-label="Ver informacion de contacto"
        >
          <i class="fas fa-phone"></i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-copiar action-icon-btn"
          title="Copiar acceso"
          aria-label="Copiar acceso"
        >
          <i class="far fa-copy"></i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-toggle-user action-icon-btn <?= $claseEstado ?>"
          title="<?= $estadoTextoBtn ?>"
          aria-label="<?= $estadoTextoBtn ?>"
        >
          <i class="fas <?= $iconoEstado ?>"></i>
        </button>
        <button
          type="button"
          class="btn btn-sm btn-trash-user action-icon-btn"
          title="Eliminar usuario"
          aria-label="Eliminar usuario"
        >
          <i class="fas fa-trash"></i>
        </button>
      </td>
    </tr>
  <?php endwhile; ?>
  </tbody>
</table>
</div>

<!--============ 9.  PAGINACION ============-->
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
        if($pag>4) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        for($i=max(2,$pag-2); $i<=min($paginas-1,$pag+2); $i++){
            echo pagLink($i,$pag,$qBase);
        }
        if($pag<$paginas-3) echo "<li class='page-item disabled'><span class='page-link'>...</span></li>";
        if($paginas>1) echo pagLink($paginas,$pag,$qBase);
    }
    ?>
  </ul>
</nav>

<div id="userNotifyWrap" class="user-notify-wrap"></div>

<div class="modal fade" id="modalUserConfirm" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-warning py-2">
        <h5 class="modal-title mb-0" id="userConfirmTitle">Confirmar accion</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body" id="userConfirmBody"></div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-danger btn-sm" id="btnConfirmUserAction">Si, continuar</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="modalUserContacto" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white py-2">
        <h5 class="modal-title mb-0">
          <i class="fas fa-address-card"></i> Informacion de contacto del usuario
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <span class="contact-inline-chip" id="contactOrigen">Origen: -</span>
          <small class="text-muted" id="contactEstadoCarga">Listo</small>
        </div>

        <div class="border rounded p-3 mb-2">
          <h6 class="mb-2">Datos principales</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="contactNombres" class="mb-1">Nombres</label>
              <input type="text" class="form-control form-control-sm" id="contactNombres" autocomplete="off">
            </div>
            <div class="form-group col-md-6">
              <label for="contactApellidos" class="mb-1">Apellidos</label>
              <input type="text" class="form-control form-control-sm" id="contactApellidos" autocomplete="off">
            </div>
            <div class="form-group col-md-6">
              <label for="contactRol" class="mb-1">Rol</label>
              <input type="text" class="form-control form-control-sm" id="contactRol" readonly>
            </div>
            <div class="form-group col-md-6 mb-0">
              <label for="contactUsuario" class="mb-1">Usuario</label>
              <input type="text" class="form-control form-control-sm" id="contactUsuario" readonly>
            </div>
          </div>
        </div>

        <div class="border rounded p-3 mb-2">
          <h6 class="mb-2">Datos de contacto</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="contactCorreo" class="mb-1">Correo institucional (@unitru.edu.pe)</label>
              <input type="email" class="form-control form-control-sm" id="contactCorreo" autocomplete="off">
            </div>
            <div class="form-group col-md-6 mb-0">
              <label for="contactTelefono" class="mb-1">Telefono (9 digitos, inicia con 9)</label>
              <input type="tel" class="form-control form-control-sm" id="contactTelefono" maxlength="9" inputmode="numeric" autocomplete="off">
            </div>
          </div>
        </div>

        <div class="border rounded p-3 d-none" id="contactAsistenteWrap">
          <h6 class="mb-2">Datos de asistente</h6>
          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="contactTelefonoAsistente" class="mb-1">Telefono asistente (opcional)</label>
              <input type="tel" class="form-control form-control-sm" id="contactTelefonoAsistente" maxlength="9" inputmode="numeric" autocomplete="off">
            </div>
            <div class="form-group col-md-6 mb-0">
              <label for="contactCorreoAsistente" class="mb-1">Correo asistente (opcional)</label>
              <input type="email" class="form-control form-control-sm" id="contactCorreoAsistente" autocomplete="off">
            </div>
          </div>
        </div>
        <div class="small text-muted mt-2" id="contactInlineMsg"></div>
      </div>
      <div class="modal-footer py-2">
        <button type="button" class="btn btn-primary btn-sm" id="btnSaveContactInfo">
          <i class="fas fa-save"></i> Guardar
        </button>
        <button type="button" class="btn btn-outline-danger btn-sm" id="btnDeleteContactInfo">
          <i class="fas fa-trash"></i> Eliminar
        </button>
        <button type="button" class="btn btn-outline-primary btn-sm" id="btnCopyContactInfo">
          <i class="far fa-copy"></i> Copiar
        </button>
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cerrar</button>
      </div>
    </div>
  </div>
</div>

<!-- ============ MODAL - CREAR USUARIO DE AUTORIDAD ============ -->
<div class="modal fade" id="modalCreateUser" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white py-2">
        <h5 class="modal-title mb-0">Crear usuario del sistema</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formCreateUser" autocomplete="off">
        <div class="modal-body">
          <div id="cuMsg" class="alert d-none mb-3"></div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="cuNombres">Nombres</label>
              <input type="text" class="form-control" id="cuNombres" name="nombres" required>
            </div>
            <div class="form-group col-md-6">
              <label for="cuApellidos">Apellidos</label>
              <input type="text" class="form-control" id="cuApellidos" name="apellidos" required>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="cuUsuario" id="cuUsuarioLabel">Codigo docente</label>
              <input type="text" class="form-control" id="cuUsuario" name="usuario" required minlength="4" maxlength="4" placeholder="Solo numeros">
              <small class="form-text text-muted" id="cuUsuarioHint">Ingrese codigo docente (4 digitos).</small>
              <small class="form-text text-muted" id="cuUsuarioCounter">0/4 caracteres</small>
            </div>
            <div class="form-group col-md-6">
              <label for="cuSede">Sede</label>
              <select class="form-control" id="cuSede" name="id_sede" required>
                <option value="1">Trujillo</option>
                <option value="2">Jequetepeque</option>
                <option value="3">Huamachuco</option>
                <option value="4">Santiago de Chuco</option>
              </select>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-6">
              <label for="cuClave">Contrasena</label>
              <div class="input-group">
                <input type="password" class="form-control" id="cuClave" name="clave" required>
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary cuTogglePass" type="button" data-target="#cuClave">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
            <div class="form-group col-md-6">
              <label for="cuClave2">Confirmar contrasena</label>
              <div class="input-group">
                <input type="password" class="form-control" id="cuClave2" name="clave2" required>
                <div class="input-group-append">
                  <button class="btn btn-outline-secondary cuTogglePass" type="button" data-target="#cuClave2">
                    <i class="fa fa-eye"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>

          <div class="form-row">
            <div class="form-group col-md-12">
              <label for="cuRol">Tipo de usuario</label>
              <select class="form-control" id="cuRol" name="id_rol" required>
                <option value="2">Coordinador de Proyecto</option>
                <option value="1">Director de DIRSU</option>
                <option value="3">Decano de la Facultad</option>
                <option value="4">Director de Departamento</option>
                <option value="5">Presidente de Comit? de RS de Facultad</option>
              </select>
              <small class="form-text text-muted" id="cuRolHint">Selecciona el tipo de usuario para activar reglas y campos obligatorios.</small>
              <small class="form-text text-info" id="cuRolImplication"></small>
            </div>
          </div>

          <div class="form-row d-none" id="cuFacultadWrap">
            <div class="form-group col-md-12">
              <label for="cuFacultad">Facultad</label>
              <select class="form-control" id="cuFacultad" name="id_escuela">
                <option value="0">Sin Facultad</option>
                <?php foreach($facultades as $facItem): ?>
                  <option value="<?= (int)$facItem['id'] ?>"><?= htmlspecialchars($facItem['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
          </div>

          <div class="form-row d-none" id="cuDepaWrap">
            <div class="form-group col-md-12">
              <label for="cuDepartamento">Departamento</label>
              <select class="form-control" id="cuDepartamento" name="id_depa">
                <option value="0">Sin Departamento Academico</option>
                <?php foreach($departamentos as $depItem): ?>
                  <option value="<?= (int)$depItem['id'] ?>"
                          data-fac="<?= (int)$depItem['id_facultad'] ?>"
                          data-fac-name="<?= htmlspecialchars($depItem['facultad']) ?>">
                    <?= htmlspecialchars($depItem['nombre']) ?>
                  </option>
                <?php endforeach; ?>
              </select>
              <small class="form-text text-muted">Para coordinador y director de departamento, el campo departamento es obligatorio.</small>
            </div>
          </div>

        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
          <button type="submit" id="cuBtnSubmit" class="btn btn-success">
            <i class="fas fa-save"></i> Registrar usuario
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- ============ MODAL - DETALLES DEL USUARIO ============ -->
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
                placeholder="Ingrese una nueva clave ...">
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
<!-- ============ /MODAL ============ -->

<!--============ 10.  JS SINCRONIZACION FAC <-> DEP ============-->
<!--============ 10.  JS FACULTAD <-> DEPARTAMENTO ============-->
<script>
$(function(){

  const $fac = $('#f_facultad');
  const $dep = $('#f_departamento');

  /* 1)  Oculta / muestra departamentos segun la facultad escogida  */
  function filtrarDepartamentos(){
      const idFac = $fac.val();          // '' = Todas las facultades
      $dep.find('option').each(function(){
          const depFac = $(this).data('fac');   // undefined para "Todos"
          /* Mostrar si:  - no hay facultad elegida
                           - o el depto pertenece a la facultad */
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
        // [x] No llamamos de nuevo a filtrarDepartamentos()
    }
});

  /* 4)  Ejecutar al cargar la pagina (por URL con filtros)     */
  filtrarDepartamentos();

});
</script>
<script>
$(function () {
  var DEPS = [];
  var currentEditRow = null;
  var pendingConfirmFn = null;

  var $notifyWrap = $('#userNotifyWrap');
  var $confirmModal = $('#modalUserConfirm');
  var $confirmTitle = $('#userConfirmTitle');
  var $confirmBody = $('#userConfirmBody');
  var $confirmBtn = $('#btnConfirmUserAction');
  var $contactModal = $('#modalUserContacto');
  var $contactNombres = $('#contactNombres');
  var $contactApellidos = $('#contactApellidos');
  var $contactRol = $('#contactRol');
  var $contactUsuario = $('#contactUsuario');
  var $contactCorreo = $('#contactCorreo');
  var $contactTelefono = $('#contactTelefono');
  var $contactAsistenteWrap = $('#contactAsistenteWrap');
  var $contactTelefonoAsistente = $('#contactTelefonoAsistente');
  var $contactCorreoAsistente = $('#contactCorreoAsistente');
  var $contactOrigen = $('#contactOrigen');
  var $contactEstadoCarga = $('#contactEstadoCarga');
  var $contactInlineMsg = $('#contactInlineMsg');
  var $btnCopyContactInfo = $('#btnCopyContactInfo');
  var $btnSaveContactInfo = $('#btnSaveContactInfo');
  var $btnDeleteContactInfo = $('#btnDeleteContactInfo');
  var currentContactPayload = null;
  var currentContactRowData = null;
  var currentContactCanManage = false;

  function escapeHtml(text) {
    return $('<div>').text(String(text || '')).html();
  }

  function loginLinkSistema() {
    var path = String(window.location.pathname || '');
    return window.location.origin + path.replace(/direccion_rsu\/usuarios\.php.*$/i, '') + 'login.php';
  }

  function notificar(tipo, texto, autoHideMs) {
    var cls = 'alert-info';
    if (tipo === 'success') cls = 'alert-success';
    if (tipo === 'danger') cls = 'alert-danger';
    if (tipo === 'warning') cls = 'alert-warning';

    var $alert = $('<div class="alert ' + cls + ' alert-dismissible fade show shadow-sm mb-2" role="alert"></div>');
    $alert.append($('<span>').text(texto));
    $alert.append('<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">&times;</span></button>');
    $notifyWrap.append($alert);

    var delay = typeof autoHideMs === 'number' ? autoHideMs : 2600;
    setTimeout(function () {
      $alert.alert('close');
    }, delay);
  }

  function confirmarAccion(titulo, html, claseBtn, callback) {
    pendingConfirmFn = callback;
    $confirmTitle.text(titulo || 'Confirmar accion');
    $confirmBody.html(html || '');
    $confirmBtn.removeClass('btn-danger btn-warning btn-primary btn-success').addClass(claseBtn || 'btn-danger');
    $confirmModal.modal('show');
  }

  $confirmBtn.on('click', function () {
    $confirmModal.modal('hide');
    if (typeof pendingConfirmFn === 'function') {
      var fn = pendingConfirmFn;
      pendingConfirmFn = null;
      fn();
    }
  });

  function getUserDataFromRow($row) {
    return {
      id: parseInt($row.attr('data-id') || '0', 10),
      usuario: String($row.attr('data-usuario') || ''),
      nombres: String($row.attr('data-nombres') || ''),
      apellidos: String($row.attr('data-apellidos') || ''),
      idRol: parseInt($row.attr('data-rol') || '0', 10),
      rolTexto: String($row.attr('data-rol-texto') || '-'),
      idDepa: parseInt($row.attr('data-depa') || '0', 10),
      dep: String($row.attr('data-dep') || ''),
      fac: String($row.attr('data-fac') || ''),
      clave: String($row.attr('data-clave') || '-'),
      activo: String($row.attr('data-activo') || '1') === '1',
      idPy: parseInt($row.attr('data-id-py') || '0', 10)
    };
  }

  function setUserDataToRow($row, data) {
    $row.attr('data-usuario', data.usuario);
    $row.attr('data-nombres', data.nombres);
    $row.attr('data-apellidos', data.apellidos);
    $row.attr('data-depa', data.idDepa);
    $row.attr('data-dep', data.dep);
    $row.attr('data-fac', data.fac);
    $row.attr('data-clave', data.clave);
    $row.attr('data-activo', data.activo ? '1' : '0');
  }

  function construirBadges(data) {
    if (data.idRol === 1 || data.idRol === 0) {
      return '<span class="lbl-rsu">RSU</span>';
    }

    var html = '';
    if (data.fac) {
      html += '<span class="lbl-fac">' + escapeHtml(data.fac) + '</span> ';
    }
    if (data.dep) {
      html += '<span class="lbl-dep">' + escapeHtml(data.dep) + '</span>';
    }
    return html;
  }

  function refrescarEstadoVisual($row, activo) {
    var $btn = $row.find('.btn-toggle-user');
    var titulo = activo ? 'Desactivar usuario' : 'Activar usuario';

    $row.attr('data-activo', activo ? '1' : '0');
    $row.toggleClass('user-inactive-row', !activo);
    $btn.attr('title', titulo).attr('aria-label', titulo);
    $btn.toggleClass('active', !!activo).toggleClass('inactive', !activo);

    var $icon = $btn.find('i');
    $icon.toggleClass('fa-toggle-on', !!activo).toggleClass('fa-toggle-off', !activo);
  }

  function actualizarFilaEditar($row, payload) {
    var data = getUserDataFromRow($row);
    data.usuario = payload.usuario;
    data.nombres = payload.nombres;
    data.apellidos = payload.apellidos;
    data.idDepa = payload.idDepa;
    data.dep = payload.dep;
    data.fac = payload.fac;
    if (payload.claveNueva) {
      data.clave = payload.claveNueva;
    }

    setUserDataToRow($row, data);

    var $cells = $row.find('td');
    $cells.eq(2).text(data.usuario);
    $cells.eq(3).html(escapeHtml(data.nombres + ' ' + data.apellidos) + '<br>' + construirBadges(data));
    $cells.eq(4).text(data.clave || '-');
  }

  function copiarTexto(texto) {
    if (navigator.clipboard && window.isSecureContext) {
      return navigator.clipboard.writeText(texto);
    }

    return new Promise(function (resolve, reject) {
      var ta = document.createElement('textarea');
      ta.value = texto;
      ta.style.position = 'fixed';
      ta.style.left = '-9999px';
      document.body.appendChild(ta);
      ta.select();
      try {
        document.execCommand('copy');
        document.body.removeChild(ta);
        resolve();
      } catch (err) {
        document.body.removeChild(ta);
        reject(err);
      }
    });
  }

  function construirMensajeAcceso(data) {
    var idRol = parseInt(data.idRol || 0, 10);
    var mostrarFacultad = true;
    var mostrarDepartamento = true;

    if (idRol === 1) {
      mostrarFacultad = false;
      mostrarDepartamento = false;
    }
    if (idRol === 3 || idRol === 5) {
      mostrarDepartamento = false;
    }

    var lineas = [
      'ACCESO AL SISTEMA DIRSU',
      'Hola, compartimos tus datos de acceso:',
      'Rol: ' + (data.rolTexto || '-')
    ];

    if (mostrarFacultad && data.fac) {
      lineas.push('Facultad: ' + data.fac);
    }
    if (mostrarDepartamento && data.dep) {
      lineas.push('Departamento Academico: ' + data.dep);
    }

    lineas.push('Usuario: ' + (data.usuario || '-'));
    lineas.push('Clave: ' + ((data.clave && data.clave !== '-') ? data.clave : 'Solicitar restablecimiento'));
    lineas.push('Link del sistema: ' + loginLinkSistema());
    lineas.push('Si no puedes ingresar, solicita apoyo al area de Informatica DIRSU.');

    return lineas.join('\n');
  }

  function rolPermiteAsistente(idRol) {
    return idRol === 1 || idRol === 3 || idRol === 4 || idRol === 5;
  }

  function rolPermiteEditarDatosPersonales(idRol) {
    return idRol === 1 || idRol === 3 || idRol === 4 || idRol === 5;
  }

  function limpiarSoloDigitos(valor, maxLen) {
    var out = String(valor || '').replace(/\D/g, '');
    if (typeof maxLen === 'number' && maxLen > 0) {
      out = out.slice(0, maxLen);
    }
    return out;
  }

  function emailUnitruValido(valor) {
    return /^[a-z0-9._%+\-]+@unitru\.edu\.pe$/i.test(String(valor || '').trim());
  }

  function emailSimpleValido(valor) {
    if (String(valor || '').trim() === '') {
      return true;
    }
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(String(valor || '').trim());
  }

  function textoConFallback(valor, fallback) {
    var limpio = String(valor === null || valor === undefined ? '' : valor).trim();
    if (limpio === '') {
      return String(fallback || '-');
    }
    return limpio;
  }

  function normalizarOrigenContacto(origen) {
    var val = String(origen || '').toLowerCase();
    if (val === 'directorio') return 'Origen: directorio';
    if (val === 'usuario_contactos') return 'Origen: usuario_contactos';
    if (val === 'directorio+usuario_contactos') return 'Origen: directorio + usuario_contactos';
    if (val === 'directorio+fallback_usuario_contactos') return 'Origen: directorio + usuario_contactos';
    return 'Origen: no definido';
  }

  function resetModalContacto(cargando) {
    currentContactPayload = null;
    currentContactCanManage = false;
    $contactNombres.val('');
    $contactApellidos.val('');
    $contactRol.val('');
    $contactUsuario.val('');
    $contactCorreo.val('');
    $contactTelefono.val('');
    $contactTelefonoAsistente.val('');
    $contactCorreoAsistente.val('');
    $contactInlineMsg.removeClass('text-danger text-success').addClass('text-muted').text('');
    $contactAsistenteWrap.addClass('d-none');
    $contactOrigen.text('Origen: -');
    $contactEstadoCarga.text(cargando ? 'Cargando...' : 'Listo');
    $contactNombres.prop('readonly', true);
    $contactApellidos.prop('readonly', true);
    $contactCorreo.prop('readonly', true);
    $contactTelefono.prop('readonly', true);
    $contactTelefonoAsistente.prop('readonly', true);
    $contactCorreoAsistente.prop('readonly', true);
    $btnCopyContactInfo.prop('disabled', !!cargando);
    $btnSaveContactInfo.prop('disabled', !!cargando);
    $btnDeleteContactInfo.prop('disabled', !!cargando);
  }

  function construirTextoContacto(payload) {
    var lineas = [];
    var rolId = parseInt((payload && payload.rol && payload.rol.id) || 0, 10);
    var nombre = textoConFallback(payload ? payload.nombres_completos : '', '-');
    var rolNombre = textoConFallback(payload && payload.rol ? payload.rol.nombre : '', '-');
    var usuario = textoConFallback(payload ? payload.usuario : '', '-');
    var correo = textoConFallback(payload && payload.contacto ? payload.contacto.email : '', 'No registrado');
    var telefono = textoConFallback(payload && payload.contacto ? payload.contacto.telefono : '', 'No registrado');

    lineas.push('INFORMACION DE CONTACTO');
    lineas.push('Nombre: ' + nombre);
    lineas.push('Rol: ' + rolNombre);
    lineas.push('Usuario: ' + usuario);
    lineas.push('Correo: ' + correo);
    lineas.push('Telefono: ' + telefono);

    if (rolPermiteAsistente(rolId)) {
      lineas.push('Telefono asistente: ' + textoConFallback(payload && payload.contacto ? payload.contacto.telefono_asistente : '', 'No registrado'));
      lineas.push('Correo asistente: ' + textoConFallback(payload && payload.contacto ? payload.contacto.correo_asistente : '', 'No registrado'));
    }

    lineas.push(normalizarOrigenContacto(payload && payload.contacto ? payload.contacto.origen : ''));
    return lineas.join('\n');
  }

  function aplicarDatosContacto(payload, rowData) {
    var rolId = parseInt((payload && payload.rol && payload.rol.id) || rowData.idRol || 0, 10);
    var nombres = textoConFallback(payload ? payload.nombres : '', rowData.nombres);
    var apellidos = textoConFallback(payload ? payload.apellidos : '', rowData.apellidos);
    var rolNombre = textoConFallback(payload && payload.rol ? payload.rol.nombre : '', rowData.rolTexto);
    var usuario = textoConFallback(payload ? payload.usuario : '', rowData.usuario);
    var correo = String((payload && payload.contacto && payload.contacto.email) ? payload.contacto.email : '').trim().toLowerCase();
    var telefono = limpiarSoloDigitos(payload && payload.contacto ? payload.contacto.telefono : '', 9);
    var telefonoAsistente = limpiarSoloDigitos(payload && payload.contacto ? payload.contacto.telefono_asistente : '', 9);
    var correoAsistente = String((payload && payload.contacto && payload.contacto.correo_asistente) ? payload.contacto.correo_asistente : '').trim().toLowerCase();
    var esEvaluador = rolPermiteEditarDatosPersonales(rolId);
    var esCoordinador = (rolId === 2);
    var editable = esEvaluador || esCoordinador;

    $contactNombres.val(nombres === '-' ? '' : nombres);
    $contactApellidos.val(apellidos === '-' ? '' : apellidos);
    $contactRol.val(rolNombre === '-' ? '' : rolNombre);
    $contactUsuario.val(usuario === '-' ? '' : usuario);
    $contactCorreo.val(correo);
    $contactTelefono.val(telefono);
    $contactOrigen.text(normalizarOrigenContacto(payload && payload.contacto ? payload.contacto.origen : ''));
    $contactNombres.prop('readonly', !esEvaluador);
    $contactApellidos.prop('readonly', !esEvaluador);
    $contactCorreo.prop('readonly', !editable);
    $contactTelefono.prop('readonly', !editable);
    $btnSaveContactInfo.prop('disabled', !editable);
    $btnDeleteContactInfo.prop('disabled', !editable);
    currentContactCanManage = editable;
    $contactInlineMsg.removeClass('text-danger text-success').addClass('text-muted').text('');

    if (esEvaluador) {
      $contactTelefonoAsistente.val(telefonoAsistente);
      $contactCorreoAsistente.val(correoAsistente);
      $contactTelefonoAsistente.prop('readonly', false);
      $contactCorreoAsistente.prop('readonly', false);
      $contactAsistenteWrap.removeClass('d-none');
    } else {
      $contactTelefonoAsistente.val('');
      $contactCorreoAsistente.val('');
      $contactTelefonoAsistente.prop('readonly', true);
      $contactCorreoAsistente.prop('readonly', true);
      $contactAsistenteWrap.addClass('d-none');
    }
  }

  function actualizarFilaTrasEditarContacto(userId, payloadContacto) {
    var $row = $('#tablaUsuariosGeneral tbody tr[data-id="' + userId + '"]');
    if (!$row.length || !payloadContacto) {
      return;
    }

    if (payloadContacto.nombres && payloadContacto.apellidos) {
      var data = getUserDataFromRow($row);
      data.nombres = String(payloadContacto.nombres);
      data.apellidos = String(payloadContacto.apellidos);
      setUserDataToRow($row, data);
      var $cells = $row.find('td');
      $cells.eq(3).html(escapeHtml(data.nombres + ' ' + data.apellidos) + '<br>' + construirBadges(data));
    }
  }

  function cargarContactoUsuario(idUsuario, done) {
    $.getJSON('../includes/api_dirsu/api.php', { action: 'user.get', id: idUsuario }, function (resp) {
      if (typeof done === 'function') {
        done(resp || null, null);
      }
    }).fail(function (xhr) {
      if (typeof done === 'function') {
        done(null, xhr || null);
      }
    });
  }

  function alternarIconosContacto() {
    $('.btn-contacto i').each(function () {
      var $icon = $(this);
      var state = String($icon.attr('data-ico') || 'phone');
      if (state === 'phone') {
        $icon.removeClass('fa-phone').addClass('fa-envelope').attr('data-ico', 'mail');
      } else {
        $icon.removeClass('fa-envelope').addClass('fa-phone').attr('data-ico', 'phone');
      }
    });
  }

  function renumerarTablaUsuariosLocal() {
    $('#tablaUsuariosGeneral tbody tr').each(function (idx) {
      $(this).find('td:first').text(idx + 1);
    });
  }

  function requestUserAction(payload, onDone) {
    $.post('reportes/usuario_acciones_ajax.php', payload, function (resp) {
      if (typeof onDone === 'function') onDone(resp || null, null);
    }, 'json').fail(function (xhr) {
      if (typeof onDone === 'function') onDone(null, xhr);
    });
  }

  $.getJSON('../componentes/usuarios/listar_departamentos.php', function (d) {
    DEPS = d || [];
  });

  $(document).on('click', '.btn-editar', function () {
    var $row = $(this).closest('tr');
    var rowData = getUserDataFromRow($row);
    currentEditRow = $row;

    $('#euId').val(rowData.id);
    $('#euRol').val(rowData.rolTexto || '-');
    $('#euNombres').val(rowData.nombres);
    $('#euApellidos').val(rowData.apellidos);
    $('#euUsuario').val(rowData.usuario);
    $('#euClave').val('');

    var $sel = $('#euDepa').empty();
    $sel.append($('<option>', { value: '', text: 'Sin Departamento Academico' }));

    DEPS.forEach(function (d) {
      var $o = $('<option>', { value: d.id, text: d.nombre, 'data-fac': d.facultad });
      if (String(d.id) === String(rowData.idDepa)) {
        $o.attr('selected', true);
      }
      $sel.append($o);
    });

    var fac = rowData.fac || ((DEPS.find(function (x) { return String(x.id) === String(rowData.idDepa); }) || {}).facultad || 'Sin Facultad');
    $('#euFacultad').val(fac);
    $('#euMsg').addClass('d-none').text('');
    $('#modalEditUser').modal('show');
  });

  $('#euDepa').on('change', function () {
    var fac = $('#euDepa option:selected').data('fac') || '';
    $('#euFacultad').val(fac);
  });

  $('#formEditUser').on('submit', function (e) {
    e.preventDefault();
    var data = $(this).serialize() + '&id=' + $('#euId').val();
    var claveNueva = String($('#euClave').val() || '').trim();

    $.post('../componentes/usuarios/actualizar_usuario.php', data, function (resp) {
      if (resp && resp.ok) {
        if (currentEditRow && currentEditRow.length) {
          var depId = parseInt($('#euDepa').val() || '0', 10);
          var depText = $('#euDepa option:selected').text() || '';
          if (depId <= 0) depText = '';

          actualizarFilaEditar(currentEditRow, {
            usuario: String($('#euUsuario').val() || ''),
            nombres: String($('#euNombres').val() || ''),
            apellidos: String($('#euApellidos').val() || ''),
            idDepa: depId,
            dep: depText,
            fac: String($('#euFacultad').val() || ''),
            claveNueva: claveNueva
          });
        }
        $('#modalEditUser').modal('hide');
        notificar('success', 'Usuario actualizado correctamente.');
      } else {
        $('#euMsg').removeClass('d-none').text((resp && resp.msg) ? resp.msg : 'No se pudo actualizar el usuario.');
      }
    }, 'json').fail(function () {
      $('#euMsg').removeClass('d-none').text('Error inesperado. Intente nuevamente.');
    });
  });

  $(document).on('click', '.btn-copiar', function () {
    var $row = $(this).closest('tr');
    var data = getUserDataFromRow($row);
    var texto = construirMensajeAcceso(data);

    copiarTexto(texto).then(function () {
      notificar('success', 'Acceso copiado para ' + (data.nombres + ' ' + data.apellidos).trim() + '.');
    }).catch(function () {
      notificar('danger', 'No se pudo copiar el acceso. Intenta nuevamente.');
    });
  });

  $(document).on('click', '.btn-contacto', function () {
    var $row = $(this).closest('tr');
    var rowData = getUserDataFromRow($row);
    resetModalContacto(true);
    currentContactRowData = rowData;
    $contactModal.modal('show');

    cargarContactoUsuario(rowData.id, function (resp) {
      if (!resp || !resp.ok || !resp.data) {
        $contactEstadoCarga.text('Error al cargar.');
        notificar('warning', 'No se pudo obtener la informacion de contacto.');
        return;
      }

      currentContactPayload = resp.data;
      aplicarDatosContacto(resp.data, rowData);
      var rolApi = parseInt((resp.data.rol && resp.data.rol.id) || 0, 10);
      currentContactCanManage = (rolApi === 2 || rolPermiteEditarDatosPersonales(rolApi));
      $contactEstadoCarga.text('Listo');
      $btnCopyContactInfo.prop('disabled', false);
      $btnSaveContactInfo.prop('disabled', !currentContactCanManage);
      $btnDeleteContactInfo.prop('disabled', !currentContactCanManage);
      if (!currentContactCanManage) {
        notificar('info', 'Para este rol solo esta disponible la visualizacion y copia del contacto.');
      }
    });
  });

  $btnCopyContactInfo.on('click', function () {
    if (!currentContactPayload) {
      notificar('warning', 'No hay informacion cargada para copiar.');
      return;
    }
    copiarTexto(construirTextoContacto(currentContactPayload)).then(function () {
      notificar('success', 'Informacion de contacto copiada.');
    }).catch(function () {
      notificar('danger', 'No se pudo copiar la informacion de contacto.');
    });
  });

  $contactNombres.on('input', function () {
    this.value = String(this.value || '').toUpperCase();
  });

  $contactApellidos.on('input', function () {
    this.value = String(this.value || '').toUpperCase();
  });

  $contactTelefono.on('input', function () {
    this.value = limpiarSoloDigitos(this.value, 9);
  });

  $contactTelefonoAsistente.on('input', function () {
    this.value = limpiarSoloDigitos(this.value, 9);
  });

  $contactCorreo.on('input', function () {
    this.value = String(this.value || '').trim().toLowerCase();
  });

  $contactCorreoAsistente.on('input', function () {
    this.value = String(this.value || '').trim().toLowerCase();
  });

  $btnSaveContactInfo.on('click', function () {
    if (!currentContactPayload || !currentContactRowData) {
      notificar('warning', 'Primero carga la informacion de contacto.');
      return;
    }
    if (!currentContactCanManage) {
      notificar('warning', 'Este usuario no tiene edicion de contacto habilitada en este modulo.');
      return;
    }

    var rolId = parseInt((currentContactPayload.rol && currentContactPayload.rol.id) || currentContactRowData.idRol || 0, 10);
    var esEvaluador = rolPermiteEditarDatosPersonales(rolId);
    var nombres = String($contactNombres.val() || '').trim().toUpperCase();
    var apellidos = String($contactApellidos.val() || '').trim().toUpperCase();
    var email = String($contactCorreo.val() || '').trim().toLowerCase();
    var telefono = limpiarSoloDigitos($contactTelefono.val(), 9);
    var telAsis = limpiarSoloDigitos($contactTelefonoAsistente.val(), 9);
    var correoAsis = String($contactCorreoAsistente.val() || '').trim().toLowerCase();

    $contactNombres.val(nombres);
    $contactApellidos.val(apellidos);
    $contactCorreo.val(email);
    $contactTelefono.val(telefono);
    $contactTelefonoAsistente.val(telAsis);
    $contactCorreoAsistente.val(correoAsis);

    if (!emailUnitruValido(email)) {
      $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger').text('El correo debe ser institucional (@unitru.edu.pe).');
      return;
    }
    if (!/^9\d{8}$/.test(telefono)) {
      $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger').text('El telefono debe tener 9 digitos e iniciar con 9.');
      return;
    }
    if (esEvaluador) {
      if (nombres.length < 2 || apellidos.length < 2) {
        $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger').text('Nombres y apellidos son obligatorios para este rol.');
        return;
      }
      if (telAsis !== '' && !/^9\d{8}$/.test(telAsis)) {
        $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger').text('El telefono de asistente debe iniciar con 9 y tener 9 digitos.');
        return;
      }
      if (!emailSimpleValido(correoAsis)) {
        $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger').text('Correo de asistente invalido.');
        return;
      }
    } else {
      telAsis = '';
      correoAsis = '';
      $contactTelefonoAsistente.val('');
      $contactCorreoAsistente.val('');
    }

    var payload = {
      action: 'update_contact',
      id: currentContactRowData.id,
      nombres: nombres,
      apellidos: apellidos,
      email: email,
      telefono: telefono,
      telefono_asistente: telAsis,
      correo_asistente: correoAsis
    };

    $btnSaveContactInfo.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');
    $contactInlineMsg.removeClass('text-danger text-success').addClass('text-muted').text('Guardando cambios...');

    requestUserAction(payload, function (resp) {
      $btnSaveContactInfo.prop('disabled', false).html('<i class="fas fa-save"></i> Guardar');

      if (!resp || !resp.ok) {
        $contactInlineMsg.removeClass('text-muted text-success').addClass('text-danger')
          .text((resp && resp.msg) ? resp.msg : 'No se pudo actualizar la informacion de contacto.');
        return;
      }

      $contactInlineMsg.removeClass('text-muted text-danger').addClass('text-success')
        .text(resp.msg || 'Informacion de contacto actualizada.');
      notificar('success', resp.msg || 'Informacion de contacto actualizada.');

      if (resp.data && resp.data.nombres && resp.data.apellidos) {
        actualizarFilaTrasEditarContacto(payload.id, resp.data);
        currentContactRowData.nombres = resp.data.nombres;
        currentContactRowData.apellidos = resp.data.apellidos;
      }

      cargarContactoUsuario(payload.id, function (reloadResp) {
        if (reloadResp && reloadResp.ok && reloadResp.data && currentContactRowData) {
          currentContactPayload = reloadResp.data;
          aplicarDatosContacto(reloadResp.data, currentContactRowData);
          $contactEstadoCarga.text('Listo');
          $contactInlineMsg.removeClass('text-muted text-danger').addClass('text-success')
            .text(resp.msg || 'Informacion de contacto actualizada.');
        }
      });
    });
  });

  $btnDeleteContactInfo.on('click', function () {
    if (!currentContactRowData) {
      notificar('warning', 'No hay un usuario seleccionado para eliminar contacto.');
      return;
    }

    var data = currentContactRowData;
    confirmarAccion(
      'Eliminar contacto',
      '<p class="mb-1">Se eliminara la informacion de contacto de:</p><p class="mb-0"><b>' + escapeHtml((data.nombres + ' ' + data.apellidos).trim()) + '</b> (' + escapeHtml(data.usuario) + ')</p>',
      'btn-danger',
      function () {
        requestUserAction({ action: 'delete_contact', id: data.id }, function (resp) {
          if (resp && resp.ok) {
            notificar('success', resp.msg || 'Contacto eliminado correctamente.');
            cargarContactoUsuario(data.id, function (reloadResp) {
              if (reloadResp && reloadResp.ok && reloadResp.data) {
                currentContactPayload = reloadResp.data;
                aplicarDatosContacto(reloadResp.data, data);
                $contactEstadoCarga.text('Listo');
              } else {
                resetModalContacto(false);
                $contactEstadoCarga.text('Sin datos.');
              }
            });
            return;
          }
          notificar('danger', (resp && resp.msg) ? resp.msg : 'No se pudo eliminar el contacto.');
        });
      }
    );
  });

  $contactModal.on('hidden.bs.modal', function () {
    currentContactPayload = null;
    currentContactRowData = null;
    currentContactCanManage = false;
    resetModalContacto(false);
  });

  setInterval(alternarIconosContacto, 3500);

  $(document).on('click', '.btn-toggle-user', function () {
    var $row = $(this).closest('tr');
    var data = getUserDataFromRow($row);
    var accionTxt = data.activo ? 'desactivar' : 'activar';

    confirmarAccion(
      'Confirmar estado de usuario',
      '<p class="mb-1">Vas a <b>' + accionTxt + '</b> al usuario:</p><p class="mb-0"><b>' + escapeHtml(data.nombres + ' ' + data.apellidos) + '</b> (' + escapeHtml(data.usuario) + ')</p>',
      'btn-warning',
      function () {
        notificar('info', 'Procesando cambio de estado...');
        requestUserAction({ action: 'toggle_state', id: data.id }, function (resp) {
          if (resp && resp.ok) {
            var activoNuevo = !!resp.data.activo;
            refrescarEstadoVisual($row, activoNuevo);
            notificar('success', resp.msg || 'Estado de usuario actualizado.');
            return;
          }
          notificar('danger', (resp && resp.msg) ? resp.msg : 'No se pudo cambiar el estado del usuario.');
        });
      }
    );
  });

  $(document).on('click', '.btn-trash-user', function () {
    var $row = $(this).closest('tr');
    var data = getUserDataFromRow($row);
    confirmarAccion(
      'Eliminar usuario',
      '<p class="mb-1">Esta accion elimina el usuario de forma permanente.</p><p class="mb-0"><b>' + escapeHtml(data.nombres + ' ' + data.apellidos) + '</b> (' + escapeHtml(data.usuario) + ')</p>',
      'btn-danger',
      function () {
        notificar('warning', 'Eliminando usuario, espera un momento...');
        requestUserAction({ action: 'delete_physical', id: data.id }, function (resp) {
          if (resp && resp.ok) {
            $row.remove();
            renumerarTablaUsuariosLocal();
            notificar('success', resp.msg || 'Usuario eliminado correctamente.');
            return;
          }
          notificar('danger', (resp && resp.msg) ? resp.msg : 'No se pudo eliminar el usuario.');
        });
      }
    );
  });
});
</script>
<script>
$(function () {
  var $modalCreate = $('#modalCreateUser');
  var $formCreate = $('#formCreateUser');
  var $msgCreate = $('#cuMsg');
  var $rol = $('#cuRol');
  var $facWrap = $('#cuFacultadWrap');
  var $depWrap = $('#cuDepaWrap');
  var $facSel = $('#cuFacultad');
  var $depSel = $('#cuDepartamento');
  var $usuarioInput = $('#cuUsuario');
  var $usuarioLabel = $('#cuUsuarioLabel');
  var $usuarioHint = $('#cuUsuarioHint');
  var $usuarioCounter = $('#cuUsuarioCounter');
  var $rolImplication = $('#cuRolImplication');
  var $btnSubmit = $('#cuBtnSubmit');
  var $tablaUsuarios = $('#tablaUsuariosGeneral tbody');
  var canAppendCreatedRow = <?= ($pag == 1 && $f_rol === 'all' && $f_fac === '' && $f_dep === '' && $f_txt === '') ? 'true' : 'false' ?>;

  function mostrarMensajeCrear(tipo, texto) {
    $msgCreate.removeClass('d-none alert-success alert-danger alert-info')
              .addClass('alert-' + tipo)
              .text(texto);
  }

  function limpiarMensajeCrear() {
    $msgCreate.addClass('d-none').removeClass('alert-success alert-danger alert-info').text('');
  }

  function obtenerReglaUsuario() {
    var rol = String($rol.val() || '');
    if (rol === '2') {
      return {
        min: 4,
        max: 4,
        label: 'Codigo docente',
        texto: 'Ingrese codigo docente (4 digitos).',
        placeholder: 'Ejemplo: 6407'
      };
    }
    if (rol === '1') {
      return {
        min: 8,
        max: 8,
        label: 'DNI',
        texto: 'Ingrese DNI (8 digitos).',
        placeholder: 'Ejemplo: 71114368'
      };
    }
    if (rol === '3' || rol === '4' || rol === '5') {
      return {
        min: 5,
        max: 5,
        label: 'Codigo de usuario',
        texto: 'Ingrese codigo de usuario (5 digitos).',
        placeholder: 'Ejemplo: 50009'
      };
    }

    return {
      min: 4,
      max: 4,
      label: 'Usuario',
      texto: 'Seleccione rol para aplicar la validacion.',
      placeholder: 'Solo numeros'
    };
  }

  function normalizarUsuarioInput() {
    var regla = obtenerReglaUsuario();
    var valor = String($usuarioInput.val() || '').replace(/[^\d]/g, '');
    if (valor.length > regla.max) {
      valor = valor.substring(0, regla.max);
    }
    $usuarioInput.val(valor);
  }

  function actualizarAyudaUsuario() {
    var regla = obtenerReglaUsuario();
    var valor = String($usuarioInput.val() || '');

    $usuarioInput.attr('maxlength', regla.max);
    $usuarioInput.attr('minlength', regla.min);
    $usuarioInput.attr('placeholder', regla.placeholder);
    $usuarioLabel.text(regla.label);

    $usuarioHint.text(regla.texto);
    $usuarioCounter.text(valor.length + '/' + regla.max + ' caracteres');
    if (valor.length < regla.min) {
      $usuarioCounter.addClass('text-danger');
    } else {
      $usuarioCounter.removeClass('text-danger');
    }
  }

  function obtenerTextoImpactoRol(rol) {
    if (rol === '2') {
      return 'Coordinador: se crea con id_py=0 (sin proyecto).';
    }
    if (rol === '1') {
      return 'Direccion RSU: no requiere facultad ni departamento.';
    }
    if (rol === '3') {
      return 'Decano: requiere facultad.';
    }
    if (rol === '4') {
      return 'Director de departamento: requiere departamento.';
    }
    if (rol === '5') {
      return 'Presidente de comite: requiere facultad.';
    }
    return '';
  }

  function actualizarCamposPorRol() {
    var rol = String($rol.val() || '');

    $rolImplication.text(obtenerTextoImpactoRol(rol));

    $facWrap.addClass('d-none');
    $depWrap.addClass('d-none');
    $facSel.prop('required', false);
    $depSel.prop('required', false);
    $facSel.val('0');
    $depSel.val('0');

    if (rol === '3' || rol === '5') {
      $facWrap.removeClass('d-none');
      $facSel.prop('required', true);
      normalizarUsuarioInput();
      actualizarAyudaUsuario();
      return;
    }
    if (rol === '4' || rol === '2') {
      $depWrap.removeClass('d-none');
      $depSel.prop('required', true);
    }

    normalizarUsuarioInput();
    actualizarAyudaUsuario();
  }

  function resetModalCrear() {
    $formCreate[0].reset();
    limpiarMensajeCrear();
    $btnSubmit.prop('disabled', false).html('<i class="fas fa-save"></i> Registrar usuario');
    actualizarCamposPorRol();
  }

  function rolTexto(idRol, usuario) {
    var map = {
      0: 'Administrador',
      1: 'Direccion RSU',
      2: (String(usuario || '').length === 4 ? 'Coordinador Proyecto' : 'Descontinuado'),
      3: 'Decanato Facultad',
      4: 'Direccion Departamento',
      5: 'Presidente Comit?'
    };
    return map[idRol] || '-';
  }

  function construirBadges(data) {
    if (parseInt(data.id_rol, 10) === 1 || parseInt(data.id_rol, 10) === 0) {
      return '<span class="lbl-rsu">RSU</span>';
    }

    var html = '';
    if (data.fac) {
      html += '<span class="lbl-fac">' + $('<div>').text(data.fac).html() + '</span> ';
    }
    if (data.dep) {
      html += '<span class="lbl-dep">' + $('<div>').text(data.dep).html() + '</span>';
    }
    return html;
  }

  function renumerarTablaUsuarios() {
    $tablaUsuarios.find('tr').each(function (idx) {
      $(this).find('td:first').text(idx + 1);
    });
  }

  function appendUsuarioEnTabla(data) {
    var rolText = rolTexto(parseInt(data.id_rol, 10), data.usuario);
    var depText = data.dep ? String(data.dep) : '';
    var facText = data.fac ? String(data.fac) : '';
    var claveVisible = data.clave_visible ? String(data.clave_visible) : '-';

    var rowHtml =
      '<tr class="js-user-row" ' +
        'data-id="' + data.id + '" ' +
        'data-usuario="' + $('<div>').text(data.usuario).html() + '" ' +
        'data-nombres="' + $('<div>').text(data.nombres).html() + '" ' +
        'data-apellidos="' + $('<div>').text(data.apellidos).html() + '" ' +
        'data-rol="' + data.id_rol + '" ' +
        'data-rol-texto="' + $('<div>').text(rolText).html() + '" ' +
        'data-depa="' + (data.id_depa || 0) + '" ' +
        'data-dep="' + $('<div>').text(depText).html() + '" ' +
        'data-fac="' + $('<div>').text(facText).html() + '" ' +
        'data-clave="' + $('<div>').text(claveVisible).html() + '" ' +
        'data-activo="1" ' +
        'data-id-py="' + (data.id_py || 0) + '">' +
        '<td class="text-right">1</td>' +
        '<td class="text-right">' + data.id + '</td>' +
        '<td>' + $('<div>').text(data.usuario).html() + '</td>' +
        '<td>' +
          $('<div>').text(data.nombres + ' ' + data.apellidos).html() + '<br>' +
          construirBadges(data) +
        '</td>' +
        '<td>' + $('<div>').text(claveVisible).html() + '</td>' +
        '<td>' + $('<div>').text(rolText).html() + '</td>' +
        '<td class="text-center">' +
          '<button type="button" class="btn btn-sm btn-editar action-icon-btn" title="Editar usuario" aria-label="Editar usuario">' +
            '<i class="fas fa-pencil-alt"></i>' +
          '</button> ' +
          '<button type="button" class="btn btn-sm btn-contacto action-icon-btn" title="Ver informacion de contacto" aria-label="Ver informacion de contacto"><i class="fas fa-phone"></i></button> ' +
          '<button type="button" class="btn btn-sm btn-copiar action-icon-btn" title="Copiar acceso" aria-label="Copiar acceso">' +
            '<i class="far fa-copy"></i>' +
          '</button> ' +
          '<button type="button" class="btn btn-sm btn-toggle-user action-icon-btn active" title="Desactivar usuario" aria-label="Desactivar usuario">' +
            '<i class="fas fa-toggle-on"></i>' +
          '</button> ' +
          '<button type="button" class="btn btn-sm btn-trash-user action-icon-btn" title="Eliminar usuario" aria-label="Eliminar usuario">' +
            '<i class="fas fa-trash"></i>' +
          '</button>' +
        '</td>' +
      '</tr>';

    $tablaUsuarios.prepend(rowHtml);
    renumerarTablaUsuarios();
  }

  $('#btnExportPdf').on('click', function () {
    alert('Exportacion PDF pendiente de implementacion.');
  });

  $('#btnExportExcel').on('click', function () {
    alert('Exportacion EXCEL pendiente de implementacion.');
  });

  $modalCreate.on('show.bs.modal', function () {
    resetModalCrear();
  });

  $rol.on('change', actualizarCamposPorRol);

  $usuarioInput.on('input', function () {
    normalizarUsuarioInput();
    actualizarAyudaUsuario();
  });

  $('.cuTogglePass').on('click', function () {
    var target = $(this).data('target');
    var $input = $(target);
    var $icon = $(this).find('i');

    if ($input.attr('type') === 'password') {
      $input.attr('type', 'text');
      $icon.removeClass('fa-eye').addClass('fa-eye-slash');
    } else {
      $input.attr('type', 'password');
      $icon.removeClass('fa-eye-slash').addClass('fa-eye');
    }
  });

  $formCreate.on('submit', function (e) {
    var rolActual = String($rol.val() || '');

    normalizarUsuarioInput();
    actualizarAyudaUsuario();
    var usuarioActual = String($usuarioInput.val() || '');

    e.preventDefault();
    limpiarMensajeCrear();

    if (rolActual === '2' && !/^\d{4}$/.test(usuarioActual)) {
      mostrarMensajeCrear('danger', 'Para coordinador, el usuario debe tener exactamente 4 digitos numericos.');
      return;
    }

    if (rolActual === '1' && !/^\d{8}$/.test(usuarioActual)) {
      mostrarMensajeCrear('danger', 'Para Direccion RSU, el usuario debe tener exactamente 8 digitos numericos.');
      return;
    }

    if ((rolActual === '3' || rolActual === '4' || rolActual === '5') && !/^\d{5}$/.test(usuarioActual)) {
      mostrarMensajeCrear('danger', 'Para este rol, el usuario debe tener exactamente 5 digitos numericos.');
      return;
    }

    if ((rolActual === '2' || rolActual === '4') && parseInt($depSel.val(), 10) <= 0) {
      mostrarMensajeCrear('danger', 'Debes seleccionar un departamento para el rol elegido.');
      return;
    }

    if ((rolActual === '3' || rolActual === '5') && parseInt($facSel.val(), 10) <= 0) {
      mostrarMensajeCrear('danger', 'Debes seleccionar una facultad para el rol elegido.');
      return;
    }

    if (String($('#cuClave').val() || '') !== String($('#cuClave2').val() || '')) {
      mostrarMensajeCrear('danger', 'Las contrasenas no coinciden.');
      return;
    }

    $btnSubmit.prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i> Guardando...');

    $.post('reportes/crear_autoridad_ajax.php', $formCreate.serialize(), function (resp) {
      if (resp && resp.ok) {
        mostrarMensajeCrear('success', resp.msg || 'Usuario creado correctamente.');
        if (resp.data && canAppendCreatedRow) {
          appendUsuarioEnTabla(resp.data);
        }
        setTimeout(function () {
          $modalCreate.modal('hide');
        }, 1200);
        return;
      }

      mostrarMensajeCrear('danger', (resp && resp.msg) ? resp.msg : 'No se pudo crear el usuario.');
    }, 'json').fail(function () {
      mostrarMensajeCrear('danger', 'Error inesperado al crear el usuario.');
    }).always(function () {
      $btnSubmit.prop('disabled', false).html('<i class="fas fa-save"></i> Registrar usuario');
    });
  });

  actualizarCamposPorRol();
  actualizarAyudaUsuario();
});
</script>
