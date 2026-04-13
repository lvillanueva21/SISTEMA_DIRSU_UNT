<?php
// /sistema_web/evaluacion/modales/evaluacion_msg.php
header('Content-Type: text/html; charset=utf-8');

include_once __DIR__ . '/../funciones.php'; // testeo(), whereFiltroPorRol(), $conexion

$usr        = testeo();
$id_rol     = (int)$usr['id_rol'];
$rol_nombre = $usr['rol'] ?? 'Rol no identificado';

$accion = isset($_GET['accion']) ? trim((string)$_GET['accion']) : '';
$id_py  = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$labels = [
  'cotejo'  => 'Calificar Cotejo',
  'rubrica' => 'Calificar Rúbrica',
  'vb'      => 'Visto Bueno',
];
$label = $labels[$accion] ?? 'Acción';

$coordinador = '';
$titulo      = '';
$formulario  = '';

if ($id_py > 0) {
    // La autorización ya se controló al habilitar el botón (control_oficinas.php).
    // Evitamos filtros por rol aquí para no dejar el modal sin datos.
    $sql = "
      SELECT
        p.p2 AS titulo,
        TRIM(CONCAT(u.nombres,' ',u.apellidos)) AS coordinador,
        f.nombre AS formulario
      FROM proyectos p
      LEFT JOIN usuarios_proyectos up
             ON up.id_proyecto = p.id AND up.activo = 1
      LEFT JOIN usuarios u
             ON u.id = up.id_usuario
      LEFT JOIN (
        SELECT r.id_py, r.id_formulario
        FROM sm_respuestas r
        WHERE r.id_py = ?
        ORDER BY r.actualizado_at DESC, r.id DESC
        LIMIT 1
      ) r ON r.id_py = p.id
      LEFT JOIN sm_formularios f ON f.id = r.id_formulario
      WHERE p.id = ?
      LIMIT 1
    ";
    if ($stmt = mysqli_prepare($conexion, $sql)) {
        mysqli_stmt_bind_param($stmt, 'ii', $id_py, $id_py);
        if (mysqli_stmt_execute($stmt)) {
            $res = mysqli_stmt_get_result($stmt);
            if ($res && ($row = mysqli_fetch_assoc($res))) {
                $coordinador = $row['coordinador'] ?? '';
                $titulo      = $row['titulo'] ?? '';
                $formulario  = $row['formulario'] ?? '';
            }
            mysqli_free_result($res);
        }
        mysqli_stmt_close($stmt);
    }
}
?>
<style>
  /* Solo separación segura entre botones (BS4/BS5) */
  #modalEval .ev-actions .btn + .btn{ margin-left:12px; }
</style>

<div class="container-fluid py-2">
<?php if (!$id_py || $titulo === ''): ?>
  <div class="alert alert-danger mb-0">No se pudo obtener el proyecto (ID: <?= (int)$id_py ?>) o no tienes acceso.</div>
<?php else: ?>

  <!-- ——— Encabezado compacto: Proyecto / Revisión / Coordinador ——— -->
  <div class="border rounded-3 p-3 mb-3 bg-white">
    <div class="mb-2">
      <strong>Proyecto:</strong>
      <span class="ms-1"><?= htmlspecialchars($titulo) ?></span>
    </div>
    <div class="d-flex flex-column flex-md-row gap-3">
      <div>
        <strong>Revisión de:</strong>
        <span class="ms-1"><?= htmlspecialchars($formulario ?: 'Formulario no identificado') ?></span>
      </div>
      <div class="ms-md-auto">
        <strong>Coordinador:</strong>
        <span class="ms-1"><?= htmlspecialchars($coordinador) ?></span>
      </div>
    </div>
  </div>

  <?php if ($accion === 'cotejo'): ?>
    <!-- ========== COTEJO ========== -->
    <div class="row g-3" id="cjRow">
      <!-- COLUMNA IZQUIERDA (se hace full-width cuando NO es “observado”) -->
      <div id="cjColLeft" class="col-12 col-md-6">
        <!-- Calificación -->
        <div class="card mb-3">
          <div class="card-body">
            <label for="evCalificacion" class="form-label fw-semibold">Calificación</label>
            <select id="evCalificacion" class="form-select form-control">
              <option value="" selected>Seleccionar</option>
              <option value="aprobado">✅ Aprobado</option>
              <option value="observado">⚠️ Observado</option>
              <option value="espera">⏳ En espera</option>
            </select>
          </div>
        </div>

        <!-- Días + Fecha (se oculta cuando NO es “observado”) -->
        <div class="card" id="cjDiasCard">
          <div class="card-body">
            <div class="row g-3 align-items-start">
              <div class="col-sm-6">
                <label for="evDias" class="form-label fw-semibold">Días para subsanar</label>
                <select id="evDias" class="form-select form-control">
                  <option value="">Seleccionar</option>
                  <option value="1">1 día</option>
                  <option value="2">2 días</option>
                </select>
                <div class="form-text">Se calcula con fecha y hora actuales (Lima, Perú).</div>
              </div>
              <div class="col-sm-6">
                <label class="form-label fw-semibold">Fecha límite</label>
                <div id="evFecha" class="alert alert-primary text-center fw-semibold py-2 mb-0" aria-live="polite">—</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- COLUMNA DERECHA: Observación (se oculta cuando NO es “observado”) -->
      <div id="cjColObs" class="col-12 col-md-6">
        <div class="card h-100">
          <div class="card-body">
            <label for="evObs" class="form-label fw-semibold">Observación</label>
            <textarea id="evObs" class="form-control" maxlength="3000" rows="6"
              placeholder="Escribe tus observaciones (máx. 3000 caracteres)">No necesita observación</textarea>
            <div class="form-text"><span id="evCont">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>
    </div>

  <?php elseif ($accion === 'vb'): ?>
    <!-- ========== VISTO BUENO ========== -->
    <div class="card">
      <div class="card-body">
        <label for="vbCalificacion" class="form-label fw-semibold">Calificación</label>
        <select id="vbCalificacion" class="form-select form-control">
          <option value="" selected>Seleccionar</option>
          <option value="aprobado">✅ Aprobado</option>
          <option value="espera">⏳ En espera</option>
        </select>
        <div class="form-text">Esta acción no guarda datos todavía. Se conectará a Evaluación V4.</div>
      </div>
    </div>

  <?php elseif ($accion === 'rubrica'): ?>
    <!-- ========== RÚBRICA ========== -->
    <div class="card mb-3">
      <div class="card-body d-flex flex-wrap justify-content-between align-items-center">
        <div class="fw-semibold">Puntaje Total: <span id="rbTotal">0</span> / 20</div>
        <div class="fw-semibold">Estado: <span id="rbEstado" class="badge bg-secondary">En espera</span></div>
      </div>
    </div>

    <div class="row g-3">
      <div class="col-12 col-md-6">
        <!-- A1 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 1: Estructura</div>
          <select class="form-select form-control rb-sel" id="rbSel1" data-asp="1">
            <option value="0" selected>0 - En espera</option>
            <option value="1">1 - Insuficiente</option>
            <option value="2">2 - Mejorable</option>
            <option value="3">3 - Satisfactorio</option>
            <option value="4">4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox1">
            <label class="form-label fw-semibold" id="rbObsLabel1">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs1" maxlength="3000" rows="4"></textarea>
            <div class="form-text"><span id="rbCount1">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A3 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 3: Redacción</div>
          <select class="form-select form-control rb-sel" id="rbSel3" data-asp="3">
            <option value="0" selected>0 - En espera</option>
            <option value="1">1 - Insuficiente</option>
            <option value="2">2 - Mejorable</option>
            <option value="3">3 - Satisfactorio</option>
            <option value="4">4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox3">
            <label class="form-label fw-semibold" id="rbObsLabel3">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs3" maxlength="3000" rows="4"></textarea>
            <div class="form-text"><span id="rbCount3">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A5 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 5: Propuesta de Mejora</div>
          <select class="form-select form-control rb-sel" id="rbSel5" data-asp="5">
            <option value="0" selected>0 - En espera</option>
            <option value="1">1 - Insuficiente</option>
            <option value="2">2 - Mejorable</option>
            <option value="3">3 - Satisfactorio</option>
            <option value="4">4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox5">
            <label class="form-label fw-semibold" id="rbObsLabel5">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs5" maxlength="3000" rows="4"></textarea>
            <div class="form-text"><span id="rbCount5">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>

      <div class="col-12 col-md-6">
        <!-- A2 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 2: Contenido</div>
          <select class="form-select form-control rb-sel" id="rbSel2" data-asp="2">
            <option value="0" selected>0 - En espera</option>
            <option value="1">1 - Insuficiente</option>
            <option value="2">2 - Mejorable</option>
            <option value="3">3 - Satisfactorio</option>
            <option value="4">4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox2">
            <label class="form-label fw-semibold" id="rbObsLabel2">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs2" maxlength="3000" rows="4"></textarea>
            <div class="form-text"><span id="rbCount2">0</span> de 3000 caracteres.</div>
          </div>
        </div>
        <!-- A4 -->
        <div class="mb-3">
          <div class="fw-semibold mb-1">Aspecto 4: Calidad de información</div>
          <select class="form-select form-control rb-sel" id="rbSel4" data-asp="4">
            <option value="0" selected>0 - En espera</option>
            <option value="1">1 - Insuficiente</option>
            <option value="2">2 - Mejorable</option>
            <option value="3">3 - Satisfactorio</option>
            <option value="4">4 - Excelente</option>
          </select>
          <div class="mt-2 rb-obs d-none" id="rbObsBox4">
            <label class="form-label fw-semibold" id="rbObsLabel4">Observación</label>
            <textarea class="form-control rb-obs-ta" id="rbObs4" maxlength="3000" rows="4"></textarea>
            <div class="form-text"><span id="rbCount4">0</span> de 3000 caracteres.</div>
          </div>
        </div>
      </div>
    </div>

    <!-- Días / Fecha (sólo si Observado) -->
    <div id="rbCorrBox" class="card d-none">
      <div class="card-body">
        <div class="row g-3 align-items-start">
          <div class="col-sm-6">
            <label for="rbDias" class="form-label fw-semibold">Días para subsanar</label>
            <select id="rbDias" class="form-select form-control">
              <option value="">Seleccionar</option>
              <option value="1">1 día</option>
              <option value="2">2 días</option>
            </select>
            <div class="form-text">Se calcula con fecha y hora actuales (Lima, Perú).</div>
          </div>
          <div class="col-sm-6">
            <label class="form-label fw-semibold">Fecha límite</label>
            <div id="rbFecha" class="alert alert-primary text-center fw-semibold py-2 mb-0" aria-live="polite">—</div>
          </div>
        </div>
      </div>
    </div>

  <?php else: ?>
    <div class="alert alert-secondary">Acción no reconocida.</div>
  <?php endif; ?>

  <!-- Footer -->
  <div class="d-flex justify-content-center mt-3 ev-actions">
    <button type="button" class="btn btn-success disabled" aria-disabled="true" title="Deshabilitado por ahora">
      Calificar y notificar al correo
    </button>
    <button type="button" class="btn btn-secondary" data-dismiss="modal" data-bs-dismiss="modal">
      Cancelar
    </button>
  </div>

  <?php if ($accion === 'cotejo'): ?>
  <script>
  (function(){
    const ZT='America/Lima', DEFAULT_OBS='No necesita observación';
    // Utilidades fecha/hora Lima
    function nowLimaParts(){
      const fmt=new Intl.DateTimeFormat('en-CA',{timeZone:ZT,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false});
      const p=fmt.formatToParts(new Date()); const g=t=>p.find(x=>x.type===t)?.value||'';
      return {y:+g('year'),mo:+g('month'),d:+g('day'),h:+g('hour'),mi:+g('minute')};
    }
    function daysInMonthUTC(y,m){return new Date(Date.UTC(y,m,0)).getUTCDate();}
    function addDaysCalendar(b,n){let {y,mo,d,h,mi}=b,l=parseInt(n,10)||0;while(l>0){const dim=daysInMonthUTC(y,mo),rem=dim-d;if(l<=rem){d+=l;l=0}else{l-=(rem+1);d=1;mo++;if(mo>12){mo=1;y++}}}return{y,mo,d,h,mi}}
    function fmt(o){return `${String(o.d).padStart(2,'0')}/${String(o.mo).padStart(2,'0')}/${o.y} a las ${String(o.h).padStart(2,'0')}:${String(o.mi).padStart(2,'0')} hrs`}

    // DOM
    const $cal   = document.getElementById('evCalificacion');
    const $dias  = document.getElementById('evDias');
    const $fecha = document.getElementById('evFecha');
    const $obs   = document.getElementById('evObs');
    const $cont  = document.getElementById('evCont');
    const colLeft = document.getElementById('cjColLeft');
    const diasCard = document.getElementById('cjDiasCard');
    const colObs = document.getElementById('cjColObs');

    function setObservedMode(on){
      // Mostrar/ocultar extras
      diasCard.classList.toggle('d-none', !on);
      colObs.classList.toggle('d-none', !on);

      // Ancho de la columna “Calificación”
      if (on) {
        if (!colLeft.classList.contains('col-md-6')) colLeft.classList.add('col-md-6');
      } else {
        colLeft.classList.remove('col-md-6'); // full width
      }

      // Bloqueos y reseteos
      $dias.disabled = !on;
      if (!on) { $dias.value=''; $fecha.textContent='—'; }

      if (on){
        $obs.disabled=false;
        if ($obs.value.trim()===DEFAULT_OBS){ $obs.value=''; }
        $obs.placeholder='Escribe tus observaciones (máx. 3000 caracteres)';
      } else {
        $obs.value = DEFAULT_OBS;
        $obs.disabled = true;
      }
      updateCounter();
    }

    function onCal(){ setObservedMode(($cal.value||'').toLowerCase()==='observado'); }
    function onDias(){ const v=parseInt($dias.value||'0',10); if(!v||$dias.disabled){$fecha.textContent='—';return;} const dest=addDaysCalendar(nowLimaParts(),v); $fecha.textContent=fmt(dest); }
    function updateCounter(){ if(!$cont)return; $cont.textContent= $obs.disabled ? '0' : String($obs.value.length); }

    // Estado inicial: ocultar extras hasta que sea “Observado”
    setObservedMode(false);
    $cal.addEventListener('change',onCal);
    $dias.addEventListener('change',onDias);
    $obs.addEventListener('input',updateCounter);
    updateCounter();
  })();
  </script>
  <?php elseif ($accion === 'rubrica'): ?>
  <script>
  (function(){
    // ========= utilidades tiempo Lima =========
    const ZT='America/Lima';
    function nowLimaParts(){const fmt=new Intl.DateTimeFormat('en-CA',{timeZone:ZT,year:'numeric',month:'2-digit',day:'2-digit',hour:'2-digit',minute:'2-digit',hour12:false});const p=fmt.formatToParts(new Date());const g=t=>p.find(x=>x.type===t)?.value||'';return{y:+g('year'),mo:+g('month'),d:+g('day'),h:+g('hour'),mi:+g('minute')}}
    function daysInMonthUTC(y,m){return new Date(Date.UTC(y,m,0)).getUTCDate();}
    function addDaysCalendar(b,n){let{y,mo,d,h,mi}=b,l=parseInt(n,10)||0;while(l>0){const dim=daysInMonthUTC(y,mo),rem=dim-d;if(l<=rem){d+=l;l=0}else{l-=(rem+1);d=1;mo++;if(mo>12){mo=1;y++}}}return{y,mo,d,h,mi}}
    function fmt(o){return `${String(o.d).padStart(2,'0')}/${String(o.mo).padStart(2,'0')}/${o.y} a las ${String(o.h).padStart(2,'0')}:${String(o.mi).padStart(2,'0')} hrs`}

    const sels=[1,2,3,4,5].map(i=>document.getElementById('rbSel'+i));
    const obsBoxes=[1,2,3,4,5].map(i=>document.getElementById('rbObsBox'+i));
    const obsLabels=[1,2,3,4,5].map(i=>document.getElementById('rbObsLabel'+i));
    const obsAreas=[1,2,3,4,5].map(i=>document.getElementById('rbObs'+i));
    const counts=[1,2,3,4,5].map(i=>document.getElementById('rbCount'+i));
    const totalEl=document.getElementById('rbTotal');
    const estadoEl=document.getElementById('rbEstado');
    const boxCorreccion=document.getElementById('rbCorrBox');
    const diasSel=document.getElementById('rbDias');
    const fechaLbl=document.getElementById('rbFecha');

    function setEstadoBadge(estado){
      let cls='bg-secondary', txt='En espera';
      if(estado==='observado'){cls='bg-warning text-dark';txt='Observado';}
      else if(estado==='aprobado'){cls='bg-success';txt='Aprobado';}
      estadoEl.className='badge '+cls; estadoEl.textContent=txt;
    }

    function updateAspectObs(idx, val, labelText, labelClass){
      const show=(val==='1'||val==='2');
      const box=obsBoxes[idx], lbl=obsLabels[idx];
      if(show){
        box.classList.remove('d-none');
        lbl.textContent=labelText;
        lbl.classList.remove('text-success','text-danger');
        if(labelClass) lbl.classList.add(labelClass);
      }else{
        box.classList.add('d-none');
        obsAreas[idx].value=''; counts[idx].textContent='0';
        lbl.classList.remove('text-success','text-danger');
      }
    }

    function resetAllToZero(){
      sels.forEach(s=>s.value='0');
      totalEl.textContent='0';
      setEstadoBadge('espera');
      boxCorreccion.classList.add('d-none');
      diasSel.value=''; fechaLbl.textContent='—';
      obsBoxes.forEach((_,i)=>updateAspectObs(i,'0','Observación','text-danger'));
    }

    function recalc(triggerSel=null){
      if(triggerSel && triggerSel.value==='0'){ resetAllToZero(); return; }

      let total=0, allZero=true;
      sels.forEach(s=>{const v=parseInt(s.value||'0',10); total+=v; if(v!==0) allZero=false;});
      totalEl.textContent=String(total);

      let estado='observado';
      if(allZero) estado='espera';
      else if(total>=14) estado='aprobado';
      setEstadoBadge(estado);

      if(estado==='observado'){ boxCorreccion.classList.remove('d-none'); }
      else { boxCorreccion.classList.add('d-none'); diasSel.value=''; fechaLbl.textContent='—'; }

      const labelText=(estado==='aprobado')?'Recomendación':'Observación';
      const labelClass=(estado==='aprobado')?'text-success':'text-danger';
      sels.forEach((s,idx)=>updateAspectObs(idx,s.value,labelText,labelClass));
    }

    sels.forEach(s=>{ s.addEventListener('change', (e)=>recalc(e.target)); });
    obsAreas.forEach((ta,idx)=>{ ta.addEventListener('input', ()=>{ counts[idx].textContent=String(ta.value.length); }); });

    diasSel.addEventListener('change', function(){
      const v=parseInt(this.value||'0',10);
      if(!v){ fechaLbl.textContent='—'; return; }
      const dest=addDaysCalendar(nowLimaParts(), v);
      fechaLbl.textContent = fmt(dest);
    });

    recalc(null); // estado inicial: En espera
  })();
  </script>
  <?php endif; ?>

<?php endif; ?>
</div>
