<?php
/* analitics.php — Dashboard público (demo) con KPIs + gráficos + tabla
   Requiere: Bootstrap + FontAwesome en el layout padre (como tus otras páginas).
   Carga Chart.js por CDN en este mismo archivo.
*/
include_once __DIR__ . '/funciones.php';

/* ===== Catálogos (si existen en tu entorno) ===== */
$usr         = function_exists('testeo') ? testeo() : ['rol'=>'Público','usuario'=>'demo'];
$facultades  = function_exists('obtenerFacultades') ? obtenerFacultades() : [0=>'Sin Facultad',1=>'Ciencias',2=>'Educación',3=>'Medicina',4=>'Agrarias',5=>'Ambiental',6=>'Humanidades',7=>'Ingeniería',8=>'Enfermería',9=>'Arte'];
$periodos    = function_exists('obtenerPeriodos') ? obtenerPeriodos() : [1=>'2025-I', 2=>'2024-II', 3=>'2024-I'];

/* Normalizar para JS (asegurar un array asociativo simple id=>nombre) */
$fac_js = [];
foreach ($facultades as $id=>$nom) {
  if ((int)$id===0) { $fac_js[(int)$id] = 'Sin Facultad'; continue; }
  $fac_js[(int)$id] = (string)$nom;
}
$per_js = [];
foreach ($periodos as $id=>$nom) { $per_js[(int)$id] = (string)$nom; }
?>
<style>
:root{
  --night-900:#0a1633;
  --night-800:#0f244d;
  --night-700:#173568;
  --accent:#0d6efd;
  --soft:#eff3fa;
  --green:#20c997;
  --orange:#ff7f32;
  --pink:#ff4fa6;
  --violet:#8a5cf6;
}
.rsu-ana .title{ color:#0f244d; font-weight:800; font-size:1.15rem; }
.rsu-ana .subtle{ color:#6b7a99; font-size:.9rem; }

/* KPIs */
.rsu-ana .kpi{
  border:0; border-radius:16px; color:#fff; padding:14px; height:100%;
  box-shadow:0 8px 24px rgba(10,22,51,.20); display:flex; align-items:center; justify-content:space-between;
}
.rsu-ana .kpi .num{ font-weight:800; font-size:1.9rem; line-height:1; letter-spacing:.2px; }
.rsu-ana .kpi .lbl{ font-size:.95rem; opacity:.95; }
.rsu-ana .kpi i{ opacity:.9; }

/* Gradientes */
.grad-navy   { background: linear-gradient(135deg,var(--night-800),var(--night-700)); }
.grad-blue   { background: linear-gradient(135deg,#1b3b8b,#0d6efd); }
.grad-green  { background: linear-gradient(135deg,#136a3a,#20c997); }
.grad-orange { background: linear-gradient(135deg,#a24b1a,#ff7f32); }
.grad-pink   { background: linear-gradient(135deg,#8d225a,#ff4fa6); }
.grad-violet { background: linear-gradient(135deg,#4b2c82,#8a5cf6); }

/* Cards base */
.rsu-ana .cardy{
  background:#fff; border:1px solid #e7ecf5; border-radius:14px; box-shadow:0 8px 22px rgba(15,36,77,.06);
  overflow:hidden;
}
.rsu-ana .cardy-hd{
  display:flex; align-items:center; gap:.5rem; padding:10px 12px; color:#fff; font-weight:700; font-size:.95rem;
}
.rsu-ana .cardy-bd{ padding:12px; }

/* Layout responsive: top (filtros), KPIs, contenido 3 columnas */
.rsu-ana .layout{
  display:grid; grid-template-columns: 1fr; gap:14px;
}
@media (min-width: 1200px){
  .rsu-ana .layout{ grid-template-columns: 1fr 1fr 1fr; }
}
@media (min-width: 1400px){
  .rsu-ana .layout{ grid-template-columns: 1.25fr 1fr 0.9fr; }
}

/* Contenedor charts (altura fija) */
.rsu-ana .chart-wrap{ height: 280px; }
.rsu-ana .chart-wrap.sm{ height: 220px; }

/* Chips */
.rsu-ana .chip{
  display:inline-flex; align-items:center; gap:6px; padding:.25rem .6rem; background:#e8eefb;
  color:#1c3f7a; border-radius:999px; font-size:.78rem; margin:0 6px 6px 0;
}
.rsu-ana .badge-soft{
  background:#eff3fa; color:#0f244d; border-radius:8px; padding:.15rem .4rem; font-size:.75rem;
}

/* Tabla compacta */
.rsu-ana table.table{ margin:0; }
.rsu-ana table.table thead th{ background:#f7f9fe; border-color:#edf1fb; font-size:.85rem; }
.rsu-ana table.table td, .rsu-ana table.table th{ padding:.45rem .6rem; vertical-align:middle; }
.rsu-ana .ods-dot{
  width:10px; height:10px; border-radius:50%; display:inline-block; margin-right:6px;
}

/* Aside: mini listas */
.rsu-ana .mini-list{ list-style:none; margin:0; padding:0; }
.rsu-ana .mini-list li{
  display:flex; align-items:center; gap:10px; padding:8px 0; border-bottom:1px dashed #e6ebf7;
}
.rsu-ana .mini-list li:last-child{ border-bottom:none; }
.rsu-ana .mini-thumb{ width:40px; height:40px; border-radius:10px; object-fit:cover; border:1px solid #eef2fb; }

/* Filtros */
.rsu-ana .filters .form-control, .rsu-ana .filters .custom-select{
  height:34px; font-size:.9rem;
}
</style>

<div class="rsu-ana">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="title"><i class="fas fa-chart-line"></i> Dashboard público de Proyectos RSU
      <span class="subtle">· Demo con datos genéricos</span>
    </div>
    <div class="subtle">Rol: <?= htmlspecialchars($usr['rol']) ?> · Usuario: <?= htmlspecialchars($usr['usuario']) ?></div>
  </div>

  <!-- FILTROS -->
  <div class="cardy mb-2">
    <div class="cardy-bd filters">
      <form id="frmAna" class="mb-0">
        <div class="form-row align-items-end">
          <div class="col-12 col-md-3 mb-2">
            <label class="mb-1 small text-muted">Período</label>
            <select id="f_periodo" class="form-control">
              <?php foreach ($per_js as $id=>$nom): ?>
                <option value="<?= (int)$id ?>"><?= htmlspecialchars($nom) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 mb-2">
            <label class="mb-1 small text-muted">Facultad</label>
            <select id="f_facultad" class="form-control">
              <option value="0">Todas</option>
              <?php foreach ($fac_js as $id=>$nom): ?>
                <option value="<?= (int)$id ?>"><?= htmlspecialchars($nom) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-3 mb-2">
            <label class="mb-1 small text-muted">Oficina / Estado</label>
            <select id="f_oficina" class="form-control">
              <option value="">Todas</option>
              <option value="PCF">Comité de Facultad</option>
              <option value="DD">Dirección de Departamento</option>
              <option value="DF">Decanato de Facultad</option>
              <option value="RSU">Dirección RSU</option>
              <option value="APROB">Aprobación Total</option>
              <option value="SIN">Sin estado</option>
            </select>
          </div>
          <div class="col-12 col-md-2 mb-2 text-right">
            <button type="button" id="btnReset" class="btn btn-sm btn-outline-secondary"><i class="fas fa-broom"></i> Limpiar</button>
          </div>
        </div>
      </form>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-2">
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-navy">
        <div><div class="num" id="k_total">0</div><div class="lbl">Proyectos activos</div></div>
        <i class="fas fa-diagram-project fa-2x"></i>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-blue">
        <div><div class="num" id="k_docentes">0</div><div class="lbl">Docentes involucrados</div></div>
        <i class="fas fa-user-tie fa-2x"></i>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-green">
        <div><div class="num" id="k_estudiantes">0</div><div class="lbl">Estudiantes participantes</div></div>
        <i class="fas fa-user-graduate fa-2x"></i>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-orange">
        <div><div class="num" id="k_horas">0</div><div class="lbl">Horas RS acumuladas</div></div>
        <i class="far fa-clock fa-2x"></i>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-pink">
        <div><div class="num" id="k_evidencias">0%</div><div class="lbl">% con carpeta de evidencias</div></div>
        <i class="fas fa-folder-open fa-2x"></i>
      </div>
    </div>
    <div class="col-12 col-md-6 col-lg-4 mb-2">
      <div class="kpi grad-violet">
        <div><div class="num" id="k_silabo">0%</div><div class="lbl">% con sílabo adjunto</div></div>
        <i class="far fa-file-pdf fa-2x"></i>
      </div>
    </div>
  </div>

  <!-- CONTENIDO: 3 columnas -->
  <div class="layout mt-2">
    <!-- Columna A: gráficos principales -->
    <div>
      <div class="cardy mb-2">
        <div class="cardy-hd grad-blue"><i class="fas fa-building"></i> Proyectos por Facultad</div>
        <div class="cardy-bd"><div class="chart-wrap"><canvas id="chFac"></canvas></div></div>
      </div>

      <div class="cardy mb-2">
        <div class="cardy-hd grad-pink"><i class="fas fa-calendar-week"></i> Proyectos creados por semana</div>
        <div class="cardy-bd"><div class="chart-wrap"><canvas id="chSemana"></canvas></div></div>
      </div>
    </div>

    <!-- Columna B: gráficos complementarios -->
    <div>
      <div class="cardy mb-2">
        <div class="cardy-hd grad-green"><i class="fas fa-sitemap"></i> Distribución por Oficina / Estado</div>
        <div class="cardy-bd"><div class="chart-wrap sm"><canvas id="chOfic"></canvas></div></div>
      </div>

      <div class="cardy mb-2">
        <div class="cardy-hd grad-violet"><i class="fas fa-bullseye"></i> ODS más frecuentes</div>
        <div class="cardy-bd"><div class="chart-wrap"><canvas id="chODS"></canvas></div></div>
      </div>
    </div>

    <!-- Columna C: módulos compactos -->
    <div>
      <div class="cardy mb-2">
        <div class="cardy-hd grad-navy"><i class="fas fa-list-ol"></i> Top ODS (sobre filtros)</div>
        <div class="cardy-bd" id="odsTopList">
          <!-- Se llena por JS -->
        </div>
      </div>

      <div class="cardy mb-2">
        <div class="cardy-hd grad-orange"><i class="fas fa-file-circle-check"></i> Tipos de archivo subidos</div>
        <div class="cardy-bd">
          <ul class="mini-list" id="fileTypesList">
            <!-- Se llena por JS -->
          </ul>
        </div>
      </div>

      <div class="cardy mb-2">
        <div class="cardy-hd grad-green"><i class="fas fa-user-star"></i> Top coordinadores</div>
        <div class="cardy-bd">
          <ul class="mini-list" id="topCoordList">
            <!-- Se llena por JS -->
          </ul>
        </div>
      </div>
    </div>
  </div>

  <!-- TABLA -->
  <div class="cardy mt-2">
    <div class="cardy-hd grad-blue"><i class="fas fa-table"></i> Últimos proyectos</div>
    <div class="cardy-bd p-0">
      <div class="table-responsive">
        <table class="table table-sm table-hover mb-0">
          <thead>
            <tr>
              <th style="width: 8%;">Código</th>
              <th style="width: 28%;">Título</th>
              <th style="width: 18%;">Facultad</th>
              <th style="width: 18%;">Coordinador</th>
              <th style="width: 18%;">ODS</th>
              <th style="width: 10%;">Oficina</th>
            </tr>
          </thead>
          <tbody id="tblBody">
            <!-- Se llena por JS -->
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script>
/* =========================
   DEMO DATA (cliente)
   ========================= */
const FACULTADES = <?=
  json_encode($fac_js, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
?>;
const PERIODOS = <?=
  json_encode($per_js, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES)
?>;

/* Programas priorizados (dummy) */
const PROGRAMAS = [
  'Salud comunitaria','Educación y lectura','Ambiente y sostenibilidad',
  'Innovación y tecnología','Cultura y artes','Desarrollo productivo'
];

/* Paleta para ODS 1..17 (colores de referencia, compactos) */
const ODS_COLORS = [
  '#e5243b','#dda63a','#4c9f38','#c5192d','#ff3a21','#26bde2','#fcc30b','#a21942',
  '#fd6925','#dd1367','#fd9d24','#bf8b2e','#3f7e44','#0a97d9','#56c02b','#00689d','#19486a'
];

/* Utilidades aleatorias controladas */
function rnd(min,max){ return Math.floor(Math.random()*(max-min+1))+min; }
function sample(arr){ return arr[rnd(0,arr.length-1)]; }
function pickMany(arr,k){
  const a=[...arr], out=[]; k=Math.min(k,a.length);
  for(let i=0;i<k;i++){ const j=rnd(0,a.length-1); out.push(a.splice(j,1)[0]); }
  return out;
}

/* Generar proyectos demo (36) */
const NOW = new Date();
const proyectos = [];
const facIds = Object.keys(FACULTADES).map(Number).filter(id=>!isNaN(id));
const perIds = Object.keys(PERIODOS).map(Number).filter(id=>!isNaN(id));
const oficinas = ['PCF','DD','DF','RSU','APROB','SIN'];
const estados  = ['en_espera','observado','aprobado'];

for (let i=1;i<=36;i++){
  const fid = sample(facIds);
  const pid = sample(perIds);
  const prog = sample(PROGRAMAS);
  const odsCount = rnd(1,4);
  const ods = pickMany([1,2,3,4,5,6,7,8,9,10,11,12,13,14,15,16,17], odsCount);
  const docentes = rnd(1,8);
  const estudiantes = rnd(5,60);
  const horas = rnd(20,220);
  const oficina = sample(oficinas);
  const estado  = oficina==='APROB' ? 'aprobado' : sample(estados);
  const has_pdf = Math.random()<0.7;
  const has_excel_doc = Math.random()<0.65;
  const has_excel_est = Math.random()<0.7;
  const has_drive = Math.random()<0.75;
  const has_silabo = has_pdf && Math.random()<0.8;
  const weeksAgo = rnd(0,12);
  const created = new Date(NOW.getFullYear(), NOW.getMonth(), NOW.getDate()-weeksAgo*7 - rnd(0,6), rnd(8,18), rnd(0,59), 0);

  proyectos.push({
    id_py: 100+i,
    codigo: `${['ENF','MED','EDU','AMB','INF','SOC','ART','AGR'][rnd(0,7)]}-${String(rnd(1,60)).padStart(3,'0')}-${NOW.getFullYear()}`,
    titulo: `${prog} — iniciativa ${i}`,
    facultad_id: fid,
    periodo_id: pid,
    programa: prog,
    ods: ods,
    docentes: docentes,
    estudiantes: estudiantes,
    horas_rs: horas,
    oficina: oficina,
    estado: estado,
    has_pdf: has_pdf,
    has_excel_doc: has_excel_doc,
    has_excel_est: has_excel_est,
    has_drive: has_drive,
    has_silabo: has_silabo,
    coordinador: ['Eliana','Luis','Karla','Jorge','María','Sonia','Carlos','Gabriela','Pedro','Elena'][rnd(0,9)] + ' ' +
                 ['P.','R.','V.','E.','L.','A.','C.','M.','D.','N.'][rnd(0,9)] + ' ' +
                 ['Sandoval','Medina','Torres','Salazar','Campos','Herrera','Núñez','Rojas','Yupanqui','Paredes'][rnd(0,9)],
    created_ts: created.getTime()
  });
}

/* =========================
   FILTROS + AGREGACIONES
   ========================= */
const $periodo = document.getElementById('f_periodo');
const $facultad = document.getElementById('f_facultad');
const $oficina  = document.getElementById('f_oficina');
const $btnReset = document.getElementById('btnReset');

function applyFilters(){
  const pid = parseInt($periodo.value,10);
  const fid = parseInt($facultad.value,10);
  const ofi = $oficina.value;

  return proyectos.filter(p=>{
    if (!isNaN(pid) && pid>0 && p.periodo_id!==pid) return false;
    if (!isNaN(fid) && fid>0 && p.facultad_id!==fid) return false;

    if (ofi!==''){
      if (ofi==='APROB' && p.estado!=='aprobado') return false;
      else if (['PCF','DD','DF','RSU'].includes(ofi) && p.oficina!==ofi) return false;
      else if (ofi==='SIN' && p.oficina!=='SIN') return false;
    }
    return true;
  });
}

/* KPIs */
const fmt = new Intl.NumberFormat('es-PE');
function updateKPIs(rows){
  const k_total = rows.length;
  const k_doc = rows.reduce((a,b)=>a+b.docentes,0);
  const k_est = rows.reduce((a,b)=>a+b.estudiantes,0);
  const k_h   = rows.reduce((a,b)=>a+b.horas_rs,0);
  const pct_ev = k_total>0 ? Math.round(100*rows.filter(r=>r.has_drive).length/k_total) : 0;
  const pct_sil = k_total>0 ? Math.round(100*rows.filter(r=>r.has_silabo).length/k_total) : 0;

  document.getElementById('k_total').textContent = fmt.format(k_total);
  document.getElementById('k_docentes').textContent = fmt.format(k_doc);
  document.getElementById('k_estudiantes').textContent = fmt.format(k_est);
  document.getElementById('k_horas').textContent = fmt.format(k_h);
  document.getElementById('k_evidencias').textContent = pct_ev + '%';
  document.getElementById('k_silabo').textContent = pct_sil + '%';
}

/* Conteos */
function groupByFac(rows){
  const m = {};
  rows.forEach(r=>{ m[r.facultad_id] = (m[r.facultad_id]||0)+1; });
  const labels = Object.keys(FACULTADES).map(Number).filter(id=>!isNaN(id));
  return {
    labels: labels.map(id=>FACULTADES[id]),
    data: labels.map(id=>m[id]||0)
  };
}
function groupByOficina(rows){
  const keys = ['PCF','DD','DF','RSU','APROB','SIN'];
  const m = {PCF:0,DD:0,DF:0,RSU:0,APROB:0,SIN:0};
  rows.forEach(r=>{
    if (r.estado==='aprobado') m.APROB++;
    else if (keys.includes(r.oficina)) m[r.oficina]++;
    else m.SIN++;
  });
  return { labels:['PCF','DD','DF','RSU','APROB','SIN'], data:[m.PCF,m.DD,m.DF,m.RSU,m.APROB,m.SIN] };
}
function groupODS(rows){
  const m = Array(18).fill(0);
  rows.forEach(r=>r.ods.forEach(id=>m[id]++));
  // top 8
  const arr = [];
  for(let i=1;i<=17;i++) arr.push({id:i, c:m[i]});
  arr.sort((a,b)=>b.c-a.c);
  const top = arr.slice(0,8);
  return { labels: top.map(x=>'ODS '+x.id), data: top.map(x=>x.c), ids: top.map(x=>x.id) };
}
function byWeek(rows){
  // últimas 12 semanas
  const weeks = [];
  for(let i=11;i>=0;i--){
    const d = new Date(); d.setDate(d.getDate() - i*7);
    const label = d.toLocaleDateString('es-PE',{day:'2-digit',month:'2-digit'});
    weeks.push({label, ts: d.getTime(), c:0});
  }
  rows.forEach(r=>{
    // asignar a la semana más cercana anterior
    for(let i=0;i<weeks.length;i++){
      if (r.created_ts <= (weeks[i].ts + 6*86400000)) { weeks[i].c++; break; }
    }
  });
  return { labels: weeks.map(w=>w.label), data: weeks.map(w=>w.c) };
}

/* Aside: Top ODS listado */
function renderODSTop(rows){
  const c = Array(18).fill(0);
  rows.forEach(r=>r.ods.forEach(id=>c[id]++));
  const arr=[]; for(let i=1;i<=17;i++) arr.push({id:i, c:c[i]});
  arr.sort((a,b)=>b.c-a.c);
  const top = arr.slice(0,10);

  const cont = document.getElementById('odsTopList');
  cont.innerHTML = '';
  if (top.length===0){ cont.innerHTML = '<span class="subtle">Sin datos</span>'; return; }

  top.forEach(x=>{
    const row = document.createElement('div');
    row.className = 'd-flex align-items-center mb-1';
    row.innerHTML = `
      <span class="ods-dot" style="background:${ODS_COLORS[x.id-1]};"></span>
      <div class="mr-auto">ODS ${x.id}</div>
      <span class="badge-soft">${x.c}</span>
    `;
    cont.appendChild(row);
  });
}

/* Aside: tipos de archivo */
function renderFiles(rows){
  const pdf = rows.filter(r=>r.has_pdf).length;
  const sx  = rows.filter(r=>r.has_excel_doc || r.has_excel_est).length;
  const drv = rows.filter(r=>r.has_drive).length;
  const sil = rows.filter(r=>r.has_silabo).length;

  const el = document.getElementById('fileTypesList');
  el.innerHTML = `
    <li><i class="far fa-file-pdf text-danger"></i><div>PDF / informes</div><div class="ml-auto badge-soft">${pdf}</div></li>
    <li><i class="far fa-file-excel text-success"></i><div>Excel (docentes/estudiantes)</div><div class="ml-auto badge-soft">${sx}</div></li>
    <li><i class="fas fa-folder-open text-primary"></i><div>Carpeta Drive</div><div class="ml-auto badge-soft">${drv}</div></li>
    <li><i class="far fa-file-alt text-warning"></i><div>Sílabo adjunto</div><div class="ml-auto badge-soft">${sil}</div></li>
  `;
}

/* Aside: top coordinadores */
function renderCoordinadores(rows){
  const m = {};
  rows.forEach(r=>{
    m[r.coordinador] = (m[r.coordinador]||0) + 1;
  });
  const arr = Object.entries(m).map(([k,v])=>({name:k, c:v}));
  arr.sort((a,b)=>b.c-a.c);
  const top = arr.slice(0,6);

  const el = document.getElementById('topCoordList');
  el.innerHTML = '';
  if (top.length===0){ el.innerHTML='<span class="subtle">Sin datos</span>'; return; }
  top.forEach(t=>{
    const li = document.createElement('li');
    li.innerHTML = `
      <img class="mini-thumb" src="https://i.pravatar.cc/80?u=${encodeURIComponent(t.name)}" alt="avatar">
      <div>
        <div class="font-weight-bold" style="line-height:1.15">${t.name}</div>
        <div class="subtle">${t.c} proyecto(s)</div>
      </div>
    `;
    el.appendChild(li);
  });
}

/* Tabla de últimos proyectos */
function renderTable(rows){
  const tb = document.getElementById('tblBody');
  tb.innerHTML = '';
  const sorted = [...rows].sort((a,b)=>b.created_ts - a.created_ts).slice(0,10);
  const oficinaAlias = (p)=>{
    if (p.estado==='aprobado') return 'APROB';
    return p.oficina || 'SIN';
  };
  sorted.forEach(p=>{
    const tr = document.createElement('tr');
    const odsChips = p.ods.slice(0,4).map(id=>`<span class="chip"><span class="ods-dot" style="background:${ODS_COLORS[id-1]}"></span>ODS ${id}</span>`).join(' ');
    tr.innerHTML = `
      <td><span class="badge-soft">${p.codigo}</span></td>
      <td><span class="font-weight-bold">${escapeHtml(p.titulo)}</span></td>
      <td>${escapeHtml(FACULTADES[p.facultad_id] || '—')}</td>
      <td>${escapeHtml(p.coordinador)}</td>
      <td>${odsChips}</td>
      <td>${oficinaAlias(p)}</td>
    `;
    tb.appendChild(tr);
  });
}

function escapeHtml(s){ return String(s).replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;' })[m]); }

/* =========================
   CHARTS
   ========================= */
let chFac=null, chOfic=null, chODS=null, chSemana=null;

function buildCharts(){
  const c1 = document.getElementById('chFac');
  const c2 = document.getElementById('chOfic');
  const c3 = document.getElementById('chODS');
  const c4 = document.getElementById('chSemana');

  // Common options
  const ticks = { color:'#506082', font:{ size:12 } };
  const grid  = { color:'#edf1fb' };

  chFac = new Chart(c1, {
    type:'bar',
    data:{ labels:[], datasets:[{ label:'Proyectos', data:[], borderWidth:1 }] },
    options:{
      responsive:true, maintainAspectRatio:false,
      scales:{ x:{ ticks, grid }, y:{ ticks, grid, beginAtZero:true, suggestedMax:6 } },
      plugins:{ legend:{display:false}, tooltip:{mode:'index'} }
    }
  });

  chOfic = new Chart(c2, {
    type:'doughnut',
    data:{ labels:[], datasets:[{ data:[], borderWidth:0, hoverOffset:6, backgroundColor:['#2f7df6','#74a0f7','#99b6fb','#bcd0ff','#20c997','#98a2b3'] }] },
    options:{ responsive:true, maintainAspectRatio:false, plugins:{ legend:{position:'bottom'} } }
  });

  chODS = new Chart(c3, {
    type:'bar',
    data:{ labels:[], datasets:[{ data:[], borderWidth:0, backgroundColor:[] }] },
    options:{
      indexAxis:'y',
      responsive:true, maintainAspectRatio:false,
      scales:{ x:{ ticks, grid, beginAtZero:true, suggestedMax:8 }, y:{ ticks, grid } },
      plugins:{ legend:{display:false} }
    }
  });

  chSemana = new Chart(c4, {
    type:'line',
    data:{ labels:[], datasets:[{ label:'Proyectos/semana', data:[], tension:.3, fill:false, pointRadius:3, borderWidth:2, borderColor:'#0d6efd' }] },
    options:{
      responsive:true, maintainAspectRatio:false,
      scales:{ x:{ ticks, grid }, y:{ ticks, grid, beginAtZero:true, suggestedMax:6 } },
      plugins:{ legend:{display:false} }
    }
  });
}

function updateCharts(rows){
  // Facultad
  const gf = groupByFac(rows);
  chFac.data.labels = gf.labels;
  chFac.data.datasets[0].data = gf.data;
  chFac.update();

  // Oficina
  const go = groupByOficina(rows);
  chOfic.data.labels = go.labels;
  chOfic.data.datasets[0].data = go.data;
  chOfic.update();

  // ODS
  const gd = groupODS(rows);
  chODS.data.labels = gd.labels;
  chODS.data.datasets[0].data = gd.data;
  chODS.data.datasets[0].backgroundColor = gd.ids.map(id=>ODS_COLORS[id-1]);
  chODS.update();

  // Semanas
  const gw = byWeek(rows);
  chSemana.data.labels = gw.labels;
  chSemana.data.datasets[0].data = gw.data;
  chSemana.update();
}

/* =========================
   INIT + EVENTOS
   ========================= */
function refreshAll(){
  const rows = applyFilters();
  updateKPIs(rows);
  updateCharts(rows);
  renderODSTop(rows);
  renderFiles(rows);
  renderCoordinadores(rows);
  renderTable(rows);
}

document.addEventListener('DOMContentLoaded', ()=>{
  // seleccionar primer periodo por defecto (si existe)
  const perKeys = Object.keys(PERIODOS);
  if (perKeys.length) { document.getElementById('f_periodo').value = perKeys[0]; }
  buildCharts();
  refreshAll();

  [$periodo,$facultad,$oficina].forEach(el=> el.addEventListener('change', refreshAll));
  $btnReset.addEventListener('click', ()=>{
    $periodo.selectedIndex = 0;
    $facultad.value = '0';
    $oficina.value = '';
    refreshAll();
  });
});
</script>
