<?php
/*-------------------------------------------------------------------------
 |  CARD + BACKEND: Crear formulario
 |  Ruta  : direccion_rsu/funciones/card_crear_formulario.php
 |  Reqs  : jQuery 3.6+, Bootstrap 4, AdminLTE, mysqli $conexion
 *------------------------------------------------------------------------*/

if (session_status() === PHP_SESSION_NONE) { @session_start(); } // sin validar sesión

// DB: usa la conexión existente si ya fue incluida en control_proyectos.php
if (!isset($conexion) || !$conexion) {
    // Ajusta la ruta si fuera necesario en tu estructura real
    require_once '../../componentes/db.php';
}

mysqli_set_charset($conexion, 'utf8mb4');

date_default_timezone_set('America/Lima');

function tipo_nombre_php(int $t): string {
    return match ($t) {
        1 => 'Presentación de Proyecto',
        2 => 'Informe Semestral',
        default => 'Otros'
    };
}

function h($s) { return htmlspecialchars((string)$s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }

function now_lima(): string { return date('Y-m-d H:i:s'); }

/*=============================== AJAX ==================================*/
if (isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    $action = $_POST['action'];

    try {
        /* ---------- CRONOGRAMAS ACTIVOS (para selects) ---------- */
        if ($action === 'cronogramas_activos') {
            $sql = "SELECT c.id,
                           p.nombre AS periodo,
                           c.tipo,
                           DATE_FORMAT(c.apertura,'%Y-%m-%d %H:%i') AS apertura,
                           DATE_FORMAT(c.cierre  ,'%Y-%m-%d %H:%i') AS cierre
                    FROM sm_cronogramas c
                    JOIN periodos p ON p.id = c.id_periodo
                    WHERE c.activo = 1
                    ORDER BY p.nombre DESC, c.apertura DESC";
            $res = mysqli_query($conexion, $sql);
            $out = [];
            while ($r = mysqli_fetch_assoc($res)) {
                $r['tipo_nombre'] = tipo_nombre_php((int)$r['tipo']);
                $out[] = $r;
            }
            echo json_encode(['success'=>true,'data'=>$out]);
            exit;
        }

        /* ---------- LISTAR FORMULARIOS con paginación 5 ---------- */
        if ($action === 'listar') {
            $page = max(1, (int)($_POST['page'] ?? 1));
            $per  = 5;
            $off  = ($page-1) * $per;

            $sqlc = "SELECT COUNT(*) AS total FROM sm_formularios";
            $resc = mysqli_query($conexion, $sqlc);
            $total = (int)mysqli_fetch_assoc($resc)['total'];

            $sql = "SELECT f.id, f.nombre, f.descripcion, f.id_cronograma,
                           DATE_FORMAT(f.fecha_actualizacion,'%Y-%m-%d %H:%i') AS fecha_actualizacion,
                           c.tipo, DATE_FORMAT(c.apertura,'%Y-%m-%d %H:%i') AS apertura,
                           DATE_FORMAT(c.cierre,'%Y-%m-%d %H:%i') AS cierre,
                           p.nombre AS periodo
                    FROM sm_formularios f
                    LEFT JOIN sm_cronogramas c ON c.id = f.id_cronograma
                    LEFT JOIN periodos p ON p.id = c.id_periodo
                    ORDER BY f.id DESC
                    LIMIT ? OFFSET ?";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'ii', $per, $off);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);

            $rows = [];
            while ($r = mysqli_fetch_assoc($res)) {
                $tipo = isset($r['tipo']) ? (int)$r['tipo'] : null;
                $r['tipo_nombre'] = $tipo ? tipo_nombre_php($tipo) : null;
                $rows[] = $r;
            }

            echo json_encode([
                'success'=>true,
                'data'=>$rows,
                'pagination'=>[
                    'page'=>$page, 'per'=>$per, 'total'=>$total,
                    'pages'=> max(1, (int)ceil($total/$per))
                ]
            ]);
            exit;
        }

        /* ---------- VALIDAR EXISTENTE POR CRONOGRAMA (interno) ---------- */
        // retorna el 1er formulario que usa ese cronograma (independiente de activo)
        function buscar_form_por_cronograma(mysqli $cx, int $id_crono): ?array {
            $sql = "SELECT f.id, f.nombre,
                           DATE_FORMAT(f.fecha_actualizacion,'%Y-%m-%d %H:%i') AS fecha_actualizacion,
                           f.id_cronograma
                    FROM sm_formularios f
                    WHERE f.id_cronograma = ?
                    ORDER BY f.id DESC LIMIT 1";
            $st = mysqli_prepare($cx, $sql);
            mysqli_stmt_bind_param($st, 'i', $id_crono);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);
            $row = mysqli_fetch_assoc($res);
            return $row ?: null;
        }

        function cargar_cronograma(mysqli $cx, int $id_crono): ?array {
            $sql = "SELECT c.id,
                           p.nombre AS periodo,
                           c.tipo,
                           DATE_FORMAT(c.apertura,'%Y-%m-%d %H:%i') AS apertura,
                           DATE_FORMAT(c.cierre  ,'%Y-%m-%d %H:%i') AS cierre
                    FROM sm_cronogramas c
                    JOIN periodos p ON p.id = c.id_periodo
                    WHERE c.id = ?
                    LIMIT 1";
            $st = mysqli_prepare($cx, $sql);
            mysqli_stmt_bind_param($st, 'i', $id_crono);
            mysqli_stmt_execute($st);
            $res = mysqli_stmt_get_result($st);
            $row = mysqli_fetch_assoc($res);
            if ($row) { $row['tipo_nombre'] = tipo_nombre_php((int)$row['tipo']); }
            return $row ?: null;
        }

        /* ---------- CREAR (fase 1: intenta, si hay conflicto => avisa) ---------- */
        if ($action === 'crear') {
            $id_crono = (int)($_POST['id_cronograma'] ?? 0);
            $nombre   = trim((string)($_POST['nombre'] ?? ''));
            $desc     = trim((string)($_POST['descripcion'] ?? ''));

            if ($nombre === '' || mb_strlen($nombre) > 200) {
                throw new Exception('El nombre es obligatorio y debe tener ≤ 200 caracteres.');
            }
            if (mb_strlen($desc) > 1000) {
                throw new Exception('La descripción debe tener ≤ 1000 caracteres.');
            }

            if ($id_crono > 0) {
                $exist = buscar_form_por_cronograma($conexion, $id_crono);
                if ($exist) {
                    $cr = cargar_cronograma($conexion, $id_crono);
                    echo json_encode([
                        'success'=>false,
                        'conflict'=>true,
                        'existing'=>$exist,
                        'cronograma'=>$cr
                    ]);
                    exit;
                }
            }

            // Insert directo (sin conflicto)
            $now = now_lima();
            if ($id_crono === 0) { $id_crono = null; }

            $sql = "INSERT INTO sm_formularios (id_cronograma, nombre, descripcion, activo, fecha_creacion, fecha_actualizacion)
                    VALUES (?, ?, ?, 1, ?, ?)";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param(
                $st,
                'issss',
                $id_crono,
                $nombre,
                $desc,
                $now,
                $now
            );
            mysqli_stmt_execute($st);

            echo json_encode(['success'=>true, 'id'=>mysqli_insert_id($conexion)]);
            exit;
        }

        /* ---------- CONFIRM REPLACE (borra el anterior y crea el nuevo) ---------- */
        if ($action === 'confirm_replace') {
            $old_id    = (int)($_POST['old_id'] ?? 0);
            $id_crono  = (int)($_POST['id_cronograma'] ?? 0);
            $nombre    = trim((string)($_POST['nombre'] ?? ''));
            $desc      = trim((string)($_POST['descripcion'] ?? ''));

            if (!$old_id || $id_crono <= 0) { throw new Exception('Datos inválidos.'); }
            if ($nombre === '' || mb_strlen($nombre) > 200) {
                throw new Exception('El nombre es obligatorio y ≤ 200 caracteres.');
            }
            if (mb_strlen($desc) > 1000) {
                throw new Exception('La descripción debe tener ≤ 1000 caracteres.');
            }

            mysqli_begin_transaction($conexion);

            // Borra físico el anterior
            $sqld = "DELETE FROM sm_formularios WHERE id = ?";
            $std  = mysqli_prepare($conexion, $sqld);
            mysqli_stmt_bind_param($std, 'i', $old_id);
            mysqli_stmt_execute($std);

            // Inserta el nuevo
            $now = now_lima();
            $sql = "INSERT INTO sm_formularios (id_cronograma, nombre, descripcion, activo, fecha_creacion, fecha_actualizacion)
                    VALUES (?, ?, ?, 1, ?, ?)";
            $st = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'issss', $id_crono, $nombre, $desc, $now, $now);
            mysqli_stmt_execute($st);

            mysqli_commit($conexion);

            echo json_encode(['success'=>true, 'id'=>mysqli_insert_id($conexion)]);
            exit;
        }

        /* ---------- ACTUALIZAR cronograma (solo cambia el vínculo) ---------- */
        if ($action === 'actualizar_cronograma') {
            $id_form  = (int)($_POST['id'] ?? 0);
            $id_crono = (int)($_POST['id_cronograma'] ?? 0);

            if (!$id_form) { throw new Exception('ID inválido.'); }

            // Si quiere vincular a un cronograma (>0), validar que no haya otro formulario con ese cronograma
            if ($id_crono > 0) {
                $sql = "SELECT id FROM sm_formularios WHERE id_cronograma = ? AND id <> ? LIMIT 1";
                $st  = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($st, 'ii', $id_crono, $id_form);
                mysqli_stmt_execute($st);
                $res = mysqli_stmt_get_result($st);
                if (mysqli_fetch_assoc($res)) {
                    echo json_encode([
                        'success'=>false,
                        'msg'=>'Ya existe un formulario vinculado a ese cronograma.'
                    ]);
                    exit;
                }
            }

            $now = now_lima();
            if ($id_crono === 0) { // desvincular => NULL
                $sql = "UPDATE sm_formularios SET id_cronograma = NULL, fecha_actualizacion = ? WHERE id = ?";
                $st  = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($st, 'si', $now, $id_form);
            } else {
                $sql = "UPDATE sm_formularios SET id_cronograma = ?, fecha_actualizacion = ? WHERE id = ?";
                $st  = mysqli_prepare($conexion, $sql);
                mysqli_stmt_bind_param($st, 'isi', $id_crono, $now, $id_form);
            }
            mysqli_stmt_execute($st);

            echo json_encode(['success'=>true]);
            exit;
        }

        /* ---------- ELIMINAR (borrado físico) ---------- */
        if ($action === 'eliminar') {
            $id = (int)($_POST['id'] ?? 0);
            if (!$id) { throw new Exception('ID inválido.'); }
            $sql = "DELETE FROM sm_formularios WHERE id = ?";
            $st  = mysqli_prepare($conexion, $sql);
            mysqli_stmt_bind_param($st, 'i', $id);
            mysqli_stmt_execute($st);
            echo json_encode(['success'=>true]);
            exit;
        }

        echo json_encode(['success'=>false,'msg'=>'Acción no reconocida']);
        exit;

    } catch (Throwable $e) {
        echo json_encode(['success'=>false, 'msg'=>$e->getMessage()]);
        exit;
    }
}
?>
<!-- ════════ Card único: Administración de formularios ════════ -->
<div class="card shadow-sm border-primary mb-4">
  <div class="card-header bg-primary text-white">
    <h5 class="m-0">Administración de formularios</h5>
  </div>

  <div class="card-body">
    <div class="row">

      <!-- ─────────────── Formulario (4) ─────────────── -->
      <div class="col-lg-4 mb-4 mb-lg-0"><!-- quitamos card interno -->
        <form id="formCrearFormulario" autocomplete="off">
          <div class="form-group">
            <label for="selCronograma">Cronograma</label>
            <select id="selCronograma" class="form-control">
              <option value="0">Sin cronograma</option>
            </select>
            <small class="form-text text-muted">
              Puede crear un formulario sin vincularlo a un cronograma activo.
            </small>
          </div>

          <div class="form-group">
            <label for="txtNombre">Nombre <span class="text-danger">*</span></label>
            <input type="text" id="txtNombre" class="form-control" maxlength="200" required>
            <small class="form-text text-muted">Máximo 200 caracteres.</small>
          </div>

          <div class="form-group">
            <label for="txtDescripcion">Descripción (opcional)</label>
            <textarea id="txtDescripcion" class="form-control" rows="2" maxlength="1000"></textarea>
            <small class="form-text text-muted">Máximo 1000 caracteres.</small>
          </div>

          <button type="button" id="btnCrearFormulario" class="btn btn-success btn-block">
            <i class="fas fa-plus-circle"></i> Crear
          </button>
        </form>
      </div><!-- /col-4 -->

      <!-- ─────────────── Tabla + filtro (8) ─────────────── -->
      <div class="col-lg-8"><!-- quitamos card interno -->
        <div class="table-responsive">
          <table id="tablaFormularios" class="table table-bordered table-hover table-sm">
            <thead class="thead-light">
              <tr>
                <th style="width:60px;">#</th>
                <th>Nombre</th>
                <th>Tipo</th>
                <th>Periodo</th>
                <th>Apertura</th>
                <th>Cierre</th>
                <th style="width:120px;">Acciones</th>
              </tr>
            </thead>
            <tbody></tbody>
          </table>
        </div>

        <nav id="paginadorFormularios" class="mt-3"></nav>
      </div><!-- /col-8 -->

    </div><!-- /row -->
  </div><!-- /card-body -->
</div><!-- /card -->

<!-- ====== MODAL: Confirmar reemplazo por cronograma duplicado ====== -->
<div class="modal fade" id="modalReemplazo" tabindex="-1" role="dialog" aria-labelledby="modalReemplazoLbl" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content border-warning">
      <div class="modal-header bg-warning text-white py-2">
        <h5 class="modal-title m-0" id="modalReemplazoLbl">Se encontró un formulario para ese cronograma</h5>
        <button class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body">
        <p class="mb-2"><strong>Formulario existente</strong></p>
        <ul class="mb-3" id="detalleFormExistente"></ul>

        <p class="mb-2"><strong>Cronograma</strong></p>
        <ul class="mb-0" id="detalleCronograma"></ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger" id="btnConfirmReemplazo"><i class="fas fa-exchange-alt"></i> Reemplazar y crear</button>
      </div>
    </div>
  </div>
</div>

<!-- ====== MODAL: Eliminar formulario ====== -->
<div class="modal fade" id="modalEliminarForm" tabindex="-1" role="dialog" aria-labelledby="modalEliminarLbl" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered" role="document">
    <div class="modal-content border-danger">
      <div class="modal-header bg-danger text-white py-2">
        <h5 class="modal-title m-0" id="modalEliminarLbl">Eliminar formulario</h5>
        <button class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="bodyEliminarForm"></div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
        <button class="btn btn-danger" id="btnConfirmEliminar"><i class="fas fa-trash"></i> Eliminar</button>
      </div>
    </div>
  </div>
</div>

<!-- ====== MODAL: Mensaje OK ====== -->
<div class="modal fade" id="modalOk" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-sm modal-dialog-centered" role="document">
    <div class="modal-content border-success">
      <div class="modal-header bg-success text-white py-2">
        <h6 class="modal-title m-0">Éxito</h6>
        <button class="close text-white" data-dismiss="modal">&times;</button>
      </div>
      <div class="modal-body" id="modalOkBody"></div>
    </div>
  </div>
</div>
<script>
$(function () {

  /* ═════════════ Helpers ═════════════ */
  const TIPO = t =>
    t == 1 ? "Presentación de Proyecto" :
    t == 2 ? "Informe Semestral"       :
             "Otros";

  const esc = s => String(s ?? "")
    .replace(/[&<>"']/g, m => (
      { "&":"&amp;", "<":"&lt;", ">":"&gt;", "\"":"&quot;", "'":"&#39;" }[m]
    ));

  /* ═════════════ AJAX wrapper ═════════════ */
  function ajax (action, data = {}, ok, err) {
    $.post(
      "funciones/card_crear_formulario.php",
      { action, ...data },
      r => {
        if (r?.success)             { ok  && ok(r); }
        else if (r?.conflict)       { ok  && ok(r); }   // flujo especial
        else                        { (err || alert)(r?.msg || "Error"); }
      },
      "json"
    ).fail(() => (err || alert)("Sin conexión con el servidor"));
  }

  /* ═════════════ Estado global ═════════════ */
  let cacheActivos   = [];   // cronogramas activos (selects)
  let pendingReplace = null; // datos en espera de confirmación
  let pagina         = 1;    // página actual (tabla)

  /* ═════════════ Select Cronogramas ═════════════ */
  function cargarActivosSelects () {
    const $sel   = $("#selCronograma");
    const previo = $sel.val();        // recordar selección
    ajax("cronogramas_activos", {}, r => {
      cacheActivos = r.data || [];
      $sel.empty().append(`<option value="0">Sin cronograma</option>`);
      cacheActivos.forEach(c => {
        $sel.append(`
          <option value="${c.id}">
            ${esc(c.periodo)} — ${esc(TIPO(c.tipo))}
            (${esc(c.apertura)} → ${esc(c.cierre)})
          </option>`);
      });
      if (previo != null) $sel.val(previo); // restaurar si aplica
    });
  }

  /* ═════════════ Tabla + paginación ═════════════ */
  function pintarPaginador (pages) {
    const $pag = $("#paginadorFormularios").empty();
    if (pages <= 1) return;

    let html = `<ul class="pagination pagination-sm mb-0">`,
        prev = pagina - 1,
        next = pagina + 1;

    html += `<li class="page-item${pagina <= 1 ? " disabled" : ""}">
               <a class="page-link" data-p="${prev}" href="#">&laquo;</a>
             </li>`;

    for (let p = 1; p <= pages; p++) {
      html += `<li class="page-item${p === pagina ? " active" : ""}">
                 <a class="page-link" data-p="${p}" href="#">${p}</a>
               </li>`;
    }

    html += `<li class="page-item${pagina >= pages ? " disabled" : ""}">
               <a class="page-link" data-p="${next}" href="#">&raquo;</a>
             </li></ul>`;

    $pag.html(html);
  }

  function cargarTabla (p = 1) {
    pagina = p;
    ajax("listar", { page: p }, r => {
      const $tb  = $("#tablaFormularios tbody").empty();
      const data = r.data || [];

      if (!data.length) {
        $tb.append(`<tr><td class="text-center text-muted" colspan="7">Sin registros</td></tr>`);
      } else {
        data.forEach((f, i) => {
          const idx   = (r.pagination.per * (r.pagination.page - 1)) + (i + 1);
          const crono = f.id_cronograma ? {
            ...f,
            id:       f.id_cronograma,
            periodo:  f.periodo,
            tipo:     f.tipo,
            apertura: f.apertura,
            cierre:   f.cierre
          } : null;

          $tb.append(`
            <tr data-id="${f.id}" data-id_crono="${f.id_cronograma || 0}">
              <td>${idx}</td>
              <td>${esc(f.nombre)}</td>
              <td>${crono ? esc(TIPO(crono.tipo)) : "Sin cronograma"}</td>
              <td>${crono ? esc(crono.periodo)    : "—"}</td>
              <td>${crono ? esc(crono.apertura)   : "—"}</td>
              <td>${crono ? esc(crono.cierre)     : "—"}</td>
              <td class="text-center">
                <button class="btn btn-sm btn-primary btnEditar"  title="Editar"><i class="fas fa-edit"></i></button>
                <button class="btn btn-sm btn-danger  btnEliminar" title="Eliminar"><i class="fas fa-trash"></i></button>
              </td>
            </tr>`);
        });
      }
      pintarPaginador(r.pagination.pages);
    });
  }

  /* ═════════════ Crear formulario ═════════════ */
  $("#btnCrearFormulario").on("click", () => {
    const id_cronograma = +$("#selCronograma").val() || 0,
          nombre        = $("#txtNombre").val().trim(),
          descripcion   = $("#txtDescripcion").val().trim();

    if (!nombre)                 return alert("El nombre es obligatorio");
    if (nombre.length > 200)     return alert("Máx 200 caracteres en nombre");
    if (descripcion.length > 1000) return alert("Máx 1000 caracteres en descripción");

    ajax("crear", { id_cronograma, nombre, descripcion }, r => {

      /* ——— Conflicto: cronograma ya tiene formulario ——— */
      if (r.conflict) {
        pendingReplace = { ...r.existing, id_cronograma, nombre, descripcion };
        $("#detalleFormExistente").html(`
          <li><b>ID:</b> ${esc(r.existing.id)}</li>
          <li><b>Nombre:</b> ${esc(r.existing.nombre)}</li>
          <li><b>Últ. actualización:</b> ${esc(r.existing.fecha_actualizacion)}</li>`);

        const c = r.cronograma;
        $("#detalleCronograma").html(`
          <li><b>Periodo:</b> ${esc(c.periodo)}</li>
          <li><b>Tipo:</b> ${esc(TIPO(c.tipo))}</li>
          <li><b>Apertura:</b> ${esc(c.apertura)}</li>
          <li><b>Cierre:</b> ${esc(c.cierre)}</li>`);

        $("#modalReemplazo").modal("show");
        return;
      }

      /* ——— Creación normal ——— */
      $("#modalOkBody").text("Formulario creado correctamente.");
      $("#modalOk").modal("show");
      $("#formCrearFormulario")[0].reset();
      cargarTabla(1);
      cargarActivosSelects();
    });
  });

  $("#btnConfirmReemplazo").on("click", () => {
    if (!pendingReplace) return;
    const { id: old_id, id_cronograma, nombre, descripcion } = pendingReplace;

    ajax("confirm_replace",
         { old_id, id_cronograma, nombre, descripcion },
         () => {
           $("#modalReemplazo").modal("hide");
           pendingReplace = null;
           $("#modalOkBody").text("Se reemplazó el formulario y se creó el nuevo.");
           $("#modalOk").modal("show");
           $("#formCrearFormulario")[0].reset();
           cargarTabla(1);
           cargarActivosSelects();
         });
  });

  /* ═════════════ Editar cronograma (en línea) ═════════════ */
  $("#tablaFormularios").on("click", ".btnEditar", function () {
    const $tr      = $(this).closest("tr");
    if ($tr.hasClass("editando")) return;
    $tr.addClass("editando");

    const idActual = +$tr.data("id_crono") || 0;

    /* Construir <select> dinámico */
    let sel = `<select class="form-control sel-crono-edit">
                 <option value="0">Sin cronograma</option>`;
    cacheActivos.forEach(c => {
      sel += `<option value="${c.id}">
                ${esc(c.periodo)} — ${esc(TIPO(c.tipo))}
                (${esc(c.apertura)} → ${esc(c.cierre)})
              </option>`;
    });
    if (idActual && !cacheActivos.find(c => +c.id === idActual)) {
      sel += `<option value="${idActual}" selected>(inactivo)</option>`;
    }
    sel += `</select>`;

    /* Reemplazar celdas */
    $tr.children().eq(2).html("—");
    $tr.children().eq(3).html(sel);
    $tr.children().eq(4).html("—");
    $tr.children().eq(5).html("—");

    /* Botones acción */
    $tr.children().eq(6).html(`
      <button class="btn btn-sm btn-success btnGuardar"><i class="fas fa-save"></i></button>
      <button class="btn btn-sm btn-secondary btnCancelar"><i class="fas fa-times"></i></button>`);
  });

  $("#tablaFormularios")
    .on("click", ".btnCancelar", ()  => cargarTabla(pagina))
    .on("click", ".btnGuardar", function () {
      const $tr          = $(this).closest("tr"),
            id           = +$tr.data("id"),
            id_cronograma = +$tr.find(".sel-crono-edit").val() || 0;

      ajax("actualizar_cronograma",
           { id, id_cronograma },
           () => {
             $("#modalOkBody").text("Formulario actualizado.");
             $("#modalOk").modal("show");
             cargarTabla(pagina);
             cargarActivosSelects();
           });
    });

  /* ═════════════ Eliminar ═════════════ */
  let idEliminar = null;

  $("#tablaFormularios").on("click", ".btnEliminar", function () {
    const $tr = $(this).closest("tr");
    idEliminar = +$tr.data("id");
    $("#bodyEliminarForm").html(`¿Eliminar el formulario <b>${esc($tr.children().eq(1).text())}</b>?`);
    $("#modalEliminarForm").modal("show");
  });

  $("#btnConfirmEliminar").on("click", () => {
    if (!idEliminar) return;
    ajax("eliminar", { id: idEliminar }, () => {
      $("#modalEliminarForm").modal("hide");
      idEliminar = null;
      cargarTabla(1);
      cargarActivosSelects();
    });
  });

  /* ═════════════ Paginador (delegado) ═════════════ */
  $("#paginadorFormularios").on("click", ".page-link", function (e) {
    e.preventDefault();
    const p = +$(this).data("p");
    if (p && p !== pagina) cargarTabla(p);
  });

  /* ═════════════ Recarga select al abrir ═════════════ */
  let selBusy = false;
  $("#selCronograma").on("focus.mref pointerdown.mref", function () {
    if (selBusy) return;
    selBusy = true;
    cargarActivosSelects();
    setTimeout(() => (selBusy = false), 300);
  });

  /* ═════════════ INIT ═════════════ */
  cargarActivosSelects();
  cargarTabla(1);

});
</script>
