<?php
// panel_prueba.php (maqueta sin BD)
// Simula sesión iniciada
$user_name = 'Maria Elisa';
$user_role = 'Editor';
?><!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <title>Panel DIRSU (maqueta) — Eco UI</title>
  <meta name="viewport" content="width=device-width, initial-scale=1" />

  <!-- Fuentes e iconos -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Jost:wght@500;600;700&family=Open+Sans:wght@400;500&display=swap" rel="stylesheet" />
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css" rel="stylesheet" />

  <style>
    :root{
      --primary:#2E7D32; /* verde hoja */
      --secondary:#00A86B; /* verde selva */
      --accent1:#FFD166; /* amarillo cálido */
      --accent2:#118AB2; /* azul-teal */
      --bg:#F3F6F9;
      --text:#0B132B;
      --muted:#6B7280;
      --white:#FFFFFF;
      --card:#FFFFFF;
      --shadow:0 8px 24px rgba(0,0,0,.08);
      --radius:16px;
    }
    *{box-sizing:border-box}
    html,body{height:100%}
    body{
      margin:0;
      color:var(--text);
      background:linear-gradient(180deg, #f5faf7 0%, #f3f6f9 100%);
      font-family: "Open Sans", system-ui, -apple-system, Segoe UI, Roboto, "Helvetica Neue", Arial;
    }
    .app{
      display:grid;
      grid-template-columns:280px 1fr;
      grid-template-rows:auto 1fr;
      min-height:100vh;
    }
    /* Sidebar */
    .sidebar{
      grid-row:1 / span 2;
      background:linear-gradient(180deg, #2E7D32 0%, #00A86B 100%);
      color:#E6FFEF;
      padding:20px 16px;
      position:sticky; top:0; height:100vh; overflow:auto;
    }
    .brand{
      display:flex; align-items:center; gap:10px;
      padding:10px 12px; border-radius:12px;
      background: rgba(255,255,255,.08);
    }
    .brand i{font-size:22px}
    .brand span{
      font-family: "Jost", sans-serif;
      font-weight:700; letter-spacing:.5px;
    }
    .userbox{
      margin:14px 0 18px 0; padding:12px;
      background: rgba(255,255,255,.06); border-radius:12px;
      display:flex; align-items:center; gap:10px;
    }
    .avatar{
      width:36px; height:36px; border-radius:50%;
      background:#fff; color:var(--primary);
      display:flex; align-items:center; justify-content:center;
      font-weight:700;
    }
    .userbox small{opacity:.85; display:block; margin-top:2px}

    .side-group{margin:16px 4px}
    .side-group h5{
      margin:10px 10px; font-size:12px; text-transform:uppercase; letter-spacing:.08em; opacity:.85
    }
    .side-link{
      display:flex; align-items:center; gap:10px;
      padding:10px 12px; border-radius:10px;
      color:#E6FFEF; text-decoration:none; margin:6px 0;
    }
    .side-link:hover{background:rgba(255,255,255,.12)}
    .side-link.active{background:#fff; color:var(--primary); box-shadow:var(--shadow)}
    .subpages{margin-left:38px}
    .subpages .side-link{opacity:.95}

    /* Header */
    .header{
      background:var(--white);
      box-shadow:var(--shadow);
      border-radius:0 0 var(--radius) var(--radius);
      padding:14px 18px;
      display:flex; align-items:center; justify-content:space-between;
      position:sticky; top:0; z-index:5;
    }
    .breadcrumbs{font-size:13px; color:var(--muted)}
    .breadcrumbs b{color:var(--text)}
    .header-actions{display:flex; gap:10px; align-items:center}
    .btn{
      border:none; border-radius:10px; padding:10px 14px;
      font-weight:600; cursor:pointer;
      background:#EEF3F6; color:var(--text);
    }
    .btn.icon{display:inline-flex; gap:8px; align-items:center}
    .btn.primary{background:var(--primary); color:#fff}
    .btn.secondary{background:var(--secondary); color:#fff}
    .btn.accent{background:var(--accent2); color:#fff}
    .btn.warning{background:var(--accent1); color:#7A4D00}
    .btn.ghost{background:transparent; border:1px solid #e5e7eb}
    .btn.small{padding:6px 10px; font-size:13px; border-radius:8px}

    /* Content */
    .content{
      padding:22px; max-width:1400px; margin:0 auto; width:100%;
    }
    .panel{
      background:var(--card); border-radius:var(--radius); box-shadow:var(--shadow);
      padding:18px; margin-bottom:18px;
    }
    .panel-title{
      display:flex; align-items:center; justify-content:space-between; gap:10px;
      margin-bottom:10px;
    }
    .title{
      display:flex; align-items:center; gap:10px; font-family:"Jost",sans-serif
    }
    .title i{color:var(--secondary)}
    .muted{color:var(--muted)}
    .grid{
      display:grid; gap:14px;
    }
    @media (min-width: 900px){
      .grid.cols-2{grid-template-columns:1fr 1fr}
      .grid.cols-3{grid-template-columns:repeat(3, 1fr)}
      .grid.cols-4{grid-template-columns:repeat(4, 1fr)}
    }

    .module-card{
      background:#fff; border:1px solid #eef1f4; border-radius:14px;
      box-shadow:0 6px 18px rgba(0,0,0,.05);
      padding:14px; display:flex; flex-direction:column; gap:10px;
    }
    .module-card[draggable="true"]{cursor:grab}
    .module-card.dragging{opacity:.6}
    .module-card.drop-target{outline:2px dashed var(--accent2)}

    .module-head{
      display:flex; align-items:center; justify-content:space-between;
    }
    .module-head .tags{display:flex; gap:6px; flex-wrap:wrap}
    .tag{
      border-radius:999px; padding:4px 8px; font-size:12px; font-weight:700; letter-spacing:.03em;
      display:inline-flex; align-items:center; gap:6px;
    }
    .tag.green{background:#E6F6EE; color:#0E6C3A}
    .tag.blue{background:#E6F3FA; color:#0A5C74}
    .tag.yellow{background:#FFF2CC; color:#7A4D00}
    .tag.gray{background:#F1F5F9; color:#475569}
    .module-actions{display:flex; gap:8px; align-items:center}
    .module-body{border-top:1px dashed #e5e7eb; padding-top:10px; color:#374151; font-size:14px}
    .module-footer{display:flex; justify-content:flex-end; gap:8px; padding-top:6px}

    .preview-carousel{
      display:grid; grid-template-columns:repeat(3,1fr); gap:10px;
    }
    .slide{
      background:#f8fafb; border:1px solid #e5e7eb; border-radius:12px; padding:8px;
    }
    .slide .thumb{
      height:110px; border-radius:10px; background:#eef2f7; display:flex; align-items:center; justify-content:center; color:#94a3b8;
      margin-bottom:8px; overflow:hidden;
    }
    .slide .thumb img{width:100%; height:100%; object-fit:cover}

    .kv{display:grid; grid-template-columns:120px 1fr; gap:8px; align-items:center}

    .media-grid{
      display:grid; grid-template-columns:repeat(auto-fill,minmax(140px,1fr)); gap:12px;
    }
    .media-item{
      background:#fff; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; box-shadow:var(--shadow);
    }
    .media-thumb{height:100px; background:#f1f5f9; display:flex; align-items:center; justify-content:center; color:#64748b}
    .media-thumb img{width:100%; height:100%; object-fit:cover}
    .media-meta{padding:8px; font-size:12px; color:#475569}

    /* Modal */
    .modal{
      position:fixed; inset:0; display:none; align-items:center; justify-content:center; background:rgba(0,0,0,.3); z-index:50;
    }
    .modal.open{display:flex;}
    .dialog{
      background:#fff; border-radius:16px; width:min(920px, 92vw); max-height:88vh; overflow:auto; box-shadow:var(--shadow);
    }
    .dialog header{
      padding:14px 16px; border-bottom:1px solid #eef1f4; display:flex; align-items:center; justify-content:space-between;
    }
    .dialog .body{padding:16px}
    .form-grid{display:grid; gap:12px}
    @media(min-width:860px){ .form-grid.cols-2{grid-template-columns:1fr 1fr} }
    label{font-weight:600; font-size:13px; color:#1f2937}
    input[type="text"], input[type="url"], input[type="number"], textarea, select{
      width:100%; padding:10px 12px; border-radius:10px; border:1px solid #e5e7eb; background:#fbfdff; font:inherit;
    }
    textarea{min-height:110px}
    .help{font-size:12px; color:#64748b}

    /* Toasts */
    .toasts{position:fixed; right:18px; bottom:18px; display:flex; flex-direction:column; gap:10px; z-index:99}
    .toast{
      background:#111827; color:#fff; padding:10px 14px; border-radius:12px; box-shadow:var(--shadow); display:flex; gap:8px; align-items:center;
    }
    .toast.success{background:#065f46}
    .toast.warn{background:#92400e}
    .toast.info{background:#1f2937}

    /* Pills y chips */
    .chip{
      display:inline-flex; gap:8px; align-items:center; padding:6px 10px; background:#E8F5E9; color:#0E6C3A; border-radius:999px; font-size:12px; font-weight:700;
    }
    .divider{height:1px; background:#eef1f4; margin:12px 0}

    .empty{
      padding:20px; text-align:center; color:#6b7280; background:#fff; border:1px dashed #d1d5db; border-radius:12px;
    }

    .eco-badge{
      display:inline-flex; align-items:center; gap:8px; background:var(--accent1); color:#7A4D00; border-radius:999px; padding:6px 10px; font-weight:800;
    }
    .preview-badges{display:flex; gap:8px; flex-wrap:wrap}

    /* PREVIEW page */
    .preview-wrap{background:#fff; border-radius:16px; overflow:auto; max-height:80vh; box-shadow:var(--shadow)}
    .preview-head{background:linear-gradient(90deg, var(--secondary), var(--primary)); color:#fff; padding:20px}
    .preview-container{padding:16px}
    .hero{position:relative; border-radius:14px; overflow:hidden; background:#f1f5f9}
    .hero img{width:100%; height:260px; object-fit:cover; display:block}
    .hero .hero-caption{position:absolute; left:16px; bottom:16px; background:rgba(0,0,0,.45); color:#fff; padding:8px 10px; border-radius:8px}

  </style>
</head>
<body>
  <div class="app">
    <!-- SIDEBAR -->
    <aside class="sidebar">
      <div class="brand"><i class="fa-solid fa-seedling"></i> <span>DIRSU • Panel</span></div>

      <div class="userbox">
        <div class="avatar"><?php echo strtoupper(substr($user_name,0,1)); ?></div>
        <div>
          <strong><?php echo htmlspecialchars($user_name); ?></strong>
          <small>Rol: <?php echo htmlspecialchars($user_role); ?></small>
        </div>
      </div>

      <div class="side-group">
        <h5>Gestión</h5>
        <a href="#" class="side-link active" data-section="paginas"><i class="fa-solid fa-browser"></i> <span>Páginas</span></a>
        <a href="#" class="side-link" data-section="medios"><i class="fa-solid fa-photo-film"></i> <span>Medios</span></a>
        <a href="#" class="side-link" data-section="menu"><i class="fa-solid fa-list"></i> <span>Menú</span></a>
        <a href="#" class="side-link" data-section="noticias"><i class="fa-solid fa-newspaper"></i> <span>Noticias</span></a>
        <a href="#" class="side-link" data-section="eventos"><i class="fa-solid fa-calendar-days"></i> <span>Eventos</span></a>
        <a href="#" class="side-link" data-section="ajustes"><i class="fa-solid fa-gear"></i> <span>Ajustes</span></a>
      </div>

      <div class="side-group">
        <h5>Páginas del sitio</h5>
        <a href="#" class="side-link subpage-link" data-page="inicio"><i class="fa-solid fa-home"></i> <span>Inicio</span></a>
        <a href="#" class="side-link subpage-link" data-page="nosotros"><i class="fa-solid fa-people-group"></i> <span>Nosotros</span></a>

        <div class="subpages">
          <h5>Áreas</h5>
          <a href="#" class="side-link subpage-link" data-page="areas_proyectos"><i class="fa-solid fa-diagram-project"></i> <span>Proyectos</span></a>
          <a href="#" class="side-link subpage-link" data-page="areas_ambiental"><i class="fa-solid fa-leaf"></i> <span>Ambiental</span></a>
        </div>

        <div class="subpages">
          <h5>VUNT</h5>
          <a href="#" class="side-link subpage-link" data-page="vunt_cdn"><i class="fa-solid fa-handshake-angle"></i> <span>CDN</span></a>
          <a href="#" class="side-link subpage-link" data-page="vunt_cvgen"><i class="fa-solid fa-venus-mars"></i> <span>CVGÉN</span></a>
          <a href="#" class="side-link subpage-link" data-page="vunt_promam"><i class="fa-solid fa-earth-americas"></i> <span>PROMAM</span></a>
        </div>

        <div class="subpages">
          <h5>CECUNT</h5>
          <a href="#" class="side-link subpage-link" data-page="cecunt_teatro"><i class="fa-solid fa-masks-theater"></i> <span>Teatro</span></a>
          <a href="#" class="side-link subpage-link" data-page="cecunt_orfeon"><i class="fa-solid fa-microphone-lines"></i> <span>Orfeón</span></a>
          <a href="#" class="side-link subpage-link" data-page="cecunt_danza"><i class="fa-solid fa-person-running"></i> <span>Danza</span></a>
          <a href="#" class="side-link subpage-link" data-page="cecunt_banda"><i class="fa-solid fa-drum"></i> <span>Banda</span></a>
          <a href="#" class="side-link subpage-link" data-page="cecunt_grupo_musica"><i class="fa-solid fa-music"></i> <span>Grupo de Música</span></a>
        </div>
      </div>

      <div class="panel" style="background:rgba(255,255,255,.1); color:#fff;">
        <div class="title"><i class="fa-solid fa-circle-info"></i><strong>Demo</strong></div>
        <p style="font-size:13px; margin:8px 0 0 0; opacity:.95">
          Esta es una <b>maqueta</b>: no guarda en servidor. Usa <code>localStorage</code> y datos integrados (con imágenes embebidas).
        </p>
      </div>
    </aside>

    <!-- HEADER -->
    <header class="header">
      <div class="breadcrumbs"><span id="bc-section">Páginas</span> / <b id="bc-current">Inicio</b></div>
      <div class="header-actions">
        <button class="btn icon" id="btn-export"><i class="fa-solid fa-file-export"></i> Exportar JSON</button>
        <button class="btn ghost icon" id="btn-preview"><i class="fa-solid fa-eye"></i> Previsualizar</button>
        <button class="btn" id="btn-draft"><i class="fa-regular fa-floppy-disk"></i> Guardar borrador</button>
        <button class="btn primary" id="btn-publish"><i class="fa-solid fa-paper-plane"></i> Publicar</button>
      </div>
    </header>

    <!-- CONTENT -->
    <main class="content" id="main">
      <!-- Se rellena por JS según sección/página -->
    </main>
  </div>

  <!-- MODAL: NUEVO MÓDULO -->
  <div class="modal" id="modalPicker">
    <div class="dialog">
      <header>
        <div class="title"><i class="fa-solid fa-layer-group"></i> <strong>Agregar módulo</strong></div>
        <button class="btn small ghost" onclick="closeModal('modalPicker')"><i class="fa-solid fa-xmark"></i></button>
      </header>
      <div class="body">
        <div class="grid cols-3" id="pickerGrid">
          <!-- Opciones de módulos (JS) -->
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: EDITOR GENÉRICO -->
  <div class="modal" id="modalEditor">
    <div class="dialog">
      <header>
        <div class="title"><i class="fa-solid fa-pen-to-square"></i> <strong id="editTitle">Editar módulo</strong></div>
        <div style="display:flex; gap:8px; align-items:center">
          <button class="btn small" onclick="toggleEditTab('form')">Formulario</button>
          <button class="btn small" onclick="toggleEditTab('json')">JSON</button>
          <button class="btn small ghost" onclick="closeModal('modalEditor')"><i class="fa-solid fa-xmark"></i></button>
        </div>
      </header>
      <div class="body">
        <div id="formEditor" class="form-grid cols-2">
          <!-- Formulario dinámico según módulo -->
        </div>
        <div id="jsonEditor" style="display:none">
          <label for="jsonArea">Edición avanzada (JSON)</label>
          <textarea id="jsonArea" spellcheck="false" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas,'Liberation Mono','Courier New',monospace; min-height:300px;"></textarea>
          <div class="help">Consejo: usa comillas dobles y estructura válida. Se valida al guardar.</div>
        </div>
        <div style="display:flex; justify-content:flex-end; gap:8px; margin-top:12px">
          <button class="btn" onclick="closeModal('modalEditor')">Cancelar</button>
          <button class="btn primary" onclick="saveEditor()">Guardar cambios</button>
        </div>
      </div>
    </div>
  </div>

  <!-- MODAL: PREVIEW -->
  <div class="modal" id="modalPreview">
    <div class="dialog" style="width:min(1100px,94vw)">
      <header>
        <div class="title"><i class="fa-solid fa-eye"></i> <strong id="pvTitle">Previsualización</strong></div>
        <button class="btn small ghost" onclick="closeModal('modalPreview')"><i class="fa-solid fa-xmark"></i></button>
      </header>
      <div class="body">
        <div class="preview-wrap" id="previewWrap"></div>
      </div>
    </div>
  </div>

  <!-- TOASTS -->
  <div class="toasts" id="toasts"></div>

  <script>
    // ----------- Estado (localStorage) -----------
    const LS_KEY = 'dirsu_panel_dummy_state_v2';
    const LS_MEDIA = 'dirsu_panel_dummy_media_v1';

    const DEFAULT_STATE = {
      selectedSection: 'paginas',
      selectedPage: 'inicio',
      pages: {
        inicio: {
          title: 'Inicio',
          modules: [
            { type:'carousel', visible:true, data:{
              slides:[
                {image: dummyImg('green','Comunidad'), title:'Compromiso con la comunidad', subtitle:'Proyectos de RSU con impacto real', alt:'Slide 1'},
                {image: dummyImg('blue','Sostenibilidad'), title:'Sostenibilidad ambiental', subtitle:'Campañas UNT libre de plástico', alt:'Slide 2'},
                {image: dummyImg('yellow','Voluntariado'), title:'Voluntariado universitario', subtitle:'Acción solidaria y formación integral', alt:'Slide 3'},
              ]
            }},
            { type:'top_features', visible:true, data:{
              items:[
                {icon:'fa-leaf', title:'Desarrollo Sostenible', text:'Educación ambiental, manejo de residuos.'},
                {icon:'fa-clipboard-check', title:'Proyectos RSU', text:'Asesoría, monitoreo y calidad institucional.'},
                {icon:'fa-hands-helping', title:'Voluntariado', text:'Salud, ciudadanía y ambiente.'},
              ]
            }},
            { type:'about_metrics', visible:true, data:{
              about:'La DIRSU impulsa el impacto positivo de la UNT a nivel local, regional y global.',
              metrics:[
                {value: '1234', label:'Estudiantes beneficiados'},
                {value: '1234', label:'Pobladores alcanzados'},
                {value: '1234', label:'Voluntarios dedicados'},
                {value: '1234', label:'Proyectos desarrollados'},
              ]
            }},
            { type:'convocatoria', visible:true, data:{ activa:false, titulo:'Regístrate en la convocatoria', descripcion:'Se habilitará el formulario al activar.', campos:['Nombre','Correo','Celular','Escuela','Ciclo'] }},
            { type:'team', visible:true, data:{
              people:[
                {name:'Dr. Lourdes Tuesta Collantes', role:'Directora DIRSU', bio:'Estrategia institucional y articulación RSU.', photo: avatar('LT')},
                {name:'Ysmael Linares Neyra', role:'Administrador DIRSU', bio:'Gestión administrativa y soporte a proyectos.', photo: avatar('YL')}
              ]
            }},
          ]
        },
        nosotros: {
          title:'Nosotros',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'Bienvenidos a la Dirección de Responsabilidad Social Universitaria. Promovemos sostenibilidad, ética y desarrollo comunitario.'}},
            { type:'about_metrics', visible:true, data:{
              about:'RSU articulada a formación, investigación y extensión.',
              metrics:[
                {value:'+15', label:'Años de compromiso'},
                {value:'ISO', label:'Enfoque 26000'},
              ]
            }},
            { type:'gallery', visible:true, data:{
              images:[
                {image: dummyImg('green','RAEEcicla'), caption:'Campaña RAEEcicla'},
                {image: dummyImg('blue','Voluntariado'), caption:'Voluntariado en acción'},
                {image: dummyImg('yellow','ODS'), caption:'Taller de ODS'}
              ]
            }},
            { type:'team', visible:true, data:{
              people:[
                {name:'Nombre 1', role:'Coordinación', bio:'Bio breve.', photo: avatar('N1')},
                {name:'Nombre 2', role:'Asistente', bio:'Bio breve.', photo: avatar('N2')}
              ]
            }}
          ]
        },
        areas_proyectos:{
          title:'Áreas / Proyectos',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'Asesoramos y monitoreamos iniciativas de RSU.'}},
            { type:'news', visible:true, data:{
              posts:[
                {title:'Nuevo proyecto en comunidad rural', slug:'proyecto-comunidad', excerpt:'Enfoque participativo y sostenible.', date:'2025-01-07', author:'DIRSU', tags:['RSU','Proyectos'], cover: dummyImg('blue','Proyecto'), status:'Publicado'}
              ]
            }},
          ]
        },
        areas_ambiental:{
          title:'Áreas / Ambiental',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'Sostenibilidad ambiental, equidad e inclusión.'}},
            { type:'badges', visible:true, data:{
              items:[
                {text:'UNT Libre de Plástico', bg:'#E6F6EE', color:'#0E6C3A', icon:'fa-leaf'},
                {text:'RAEEcicla', bg:'#E6F3FA', color:'#0A5C74', icon:'fa-recycle'}
              ]
            }}
          ]
        },
        vunt_cdn:{
          title:'VUNT / CDN',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'CDN - Programa de Ciudadanía del Voluntariado UNT.'}},
            { type:'facts', visible:true, data:{ items:[
              {value:'120', label:'Actividades cívicas'}, {value:'45', label:'Aliados'}, {value:'350', label:'Voluntarios'}
            ]}}
          ]
        },
        vunt_cvgen:{
          title:'VUNT / CVGÉN',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'Programa contra la Violencia de Género.'}},
            { type:'news', visible:true, data:{
              posts:[
                {title:'Jornada de sensibilización', slug:'jornada-sensibilizacion', excerpt:'Acciones para erradicar la violencia.', date:'2025-02-02', author:'Equipo CVGÉN', tags:['CVGÉN','Comunidad'], cover: dummyImg('yellow','CVGÉN'), status:'Borrador'}
              ]
            }}
          ]
        },
        vunt_promam:{
          title:'VUNT / PROMAM',
          modules:[
            { type:'paragraph', visible:true, data:{ html:'Programa del Cuidado del Medio Ambiente.'}},
            { type:'events', visible:true, data:{
              items:[
                {title:'Campaña de Arborización', date:'2025-03-20', place:'Campus UNT', link:'#', featured:true},
                {title:'Limpieza de playas', date:'2025-04-05', place:'Huanchaco', link:'#', featured:false},
              ]
            }}
          ]
        },
        cecunt_teatro:{ title:'CECUNT / Teatro', modules:[{type:'paragraph', visible:true, data:{html:'Teatro Universitario: formación y arte.'}}] },
        cecunt_orfeon:{ title:'CECUNT / Orfeón', modules:[{type:'paragraph', visible:true, data:{html:'Orfeón Universitario: música coral.'}}] },
        cecunt_danza:{ title:'CECUNT / Danza', modules:[{type:'paragraph', visible:true, data:{html:'Grupo de Danza Universitario.'}}] },
        cecunt_banda:{ title:'CECUNT / Banda', modules:[{type:'paragraph', visible:true, data:{html:'Banda de Música Universitaria.'}}] },
        cecunt_grupo_musica:{ title:'CECUNT / Grupo de Música', modules:[{type:'paragraph', visible:true, data:{html:'Conjunto musical universitario.'}}] },
      },
      news:[
        {title:'DIRSU en comunidad rural', slug:'dirsu-comunidad', excerpt:'Impacto local y sostenible.', date:'2025-01-05', author:'DIRSU', tags:['RSU'], cover: dummyImg('green','Comunidad'), status:'Publicado'},
        {title:'Alianza con municipio', slug:'alianza-municipio', excerpt:'Trabajo articulado.', date:'2025-01-12', author:'DIRSU', tags:['Alianzas'], cover: dummyImg('blue','Alianza'), status:'Publicado'},
      ],
      events:[
        {title:'Feria ODS', date:'2025-02-10', place:'Plaza Mayor', link:'#', featured:true},
        {title:'Seminario RSU', date:'2025-02-28', place:'Auditorio UNT', link:'#', featured:false},
      ],
      menu:[
        {text:'Inicio', url:'/index.php'},
        {text:'Nosotros', url:'/pagina_web/nosotros.php'},
        {text:'Áreas', url:'#', children:[
          {text:'Proyectos', url:'/pagina_web/areas/proyectos.php'},
          {text:'Ambiental', url:'/pagina_web/areas/ambiental.php'}
        ]},
        {text:'VUNT', url:'#', children:[
          {text:'CDN', url:'/pagina_web/vunt/cdn.php'},
          {text:'CVGÉN', url:'/pagina_web/vunt/cvgen.php'},
          {text:'PROMAM', url:'/pagina_web/vunt/promam.php'},
        ]},
        {text:'CECUNT', url:'#', children:[
          {text:'Teatro', url:'/pagina_web/cecunt/teatro.php'},
          {text:'Orfeón', url:'/pagina_web/cecunt/orfeon.php'},
          {text:'Danza', url:'/pagina_web/cecunt/danza.php'},
          {text:'Banda', url:'/pagina_web/cecunt/banda_musica.php'},
          {text:'Grupo de Música', url:'/pagina_web/cecunt/grupo_musica.php'},
        ]},
      ],
      media: []
    };

    // ----------- Imágenes offline (data-URI SVG) -----------
    function dummyImg(tone='green', text='DIRSU'){
      const palettes = {
        green:['#E6F6EE','#BDEED3','#8FDDB5','#5BCB95'],
        blue:['#E6F3FA','#BFE3F6','#95D1F0','#68BDE8'],
        yellow:['#FFF2CC','#FFE08A','#FFD166','#E0A800']
      };
      const p = palettes[tone] || palettes.green;
      const svg = `
        <svg xmlns='http://www.w3.org/2000/svg' width='800' height='450'>
          <defs>
            <linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>
              <stop offset='0%' stop-color='${p[0]}'/>
              <stop offset='100%' stop-color='${p[2]}'/>
            </linearGradient>
          </defs>
          <rect width='100%' height='100%' fill='url(#g)'/>
          <circle cx='120' cy='100' r='60' fill='${p[1]}' opacity='0.6'/>
          <circle cx='700' cy='360' r='90' fill='${p[3]}' opacity='0.35'/>
          <text x='50%' y='52%' dominant-baseline='middle' text-anchor='middle'
            font-family='Jost, Arial, sans-serif' font-size='44' font-weight='700' fill='#0B132B'
            >${escapeHtml(text)}</text>
          <text x='50%' y='68%' dominant-baseline='middle' text-anchor='middle'
            font-family='Open Sans, Arial' font-size='18' fill='#0B132B' opacity='.7'
            >Maqueta DIRSU</text>
        </svg>`;
      return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
    }
    function avatar(seed='A'){
      const bg = '#E6F6EE', fg = '#0E6C3A';
      const svg = `
      <svg xmlns='http://www.w3.org/2000/svg' width='128' height='128'>
        <rect width='100%' height='100%' rx='18' ry='18' fill='${bg}'/>
        <text x='50%' y='56%' dominant-baseline='middle' text-anchor='middle'
          font-family='Jost, Arial' font-size='54' font-weight='800' fill='${fg}'>${escapeHtml(seed.slice(0,2).toUpperCase())}</text>
      </svg>`;
      return 'data:image/svg+xml;base64,' + btoa(unescape(encodeURIComponent(svg)));
    }

    let STATE = loadState();

    function loadState(){
      try{
        const s = localStorage.getItem(LS_KEY);
        if(s){ return JSON.parse(s); }
      }catch(e){}
      return JSON.parse(JSON.stringify(DEFAULT_STATE));
    }
    function saveState(){ localStorage.setItem(LS_KEY, JSON.stringify(STATE)); }

    // ----------- UI helpers -----------
    function qs(sel,ctx=document){ return ctx.querySelector(sel); }
    function qsa(sel,ctx=document){ return Array.from(ctx.querySelectorAll(sel)); }

    function toast(msg, type='info'){
      const box = qs('#toasts');
      const t = document.createElement('div');
      t.className = 'toast ' + (type==='success'?'success': type==='warn'?'warn':'info');
      t.innerHTML = `<i class="fa-solid ${type==='success'?'fa-circle-check': type==='warn'?'fa-triangle-exclamation':'fa-circle-info'}"></i> <span>${msg}</span>`;
      box.appendChild(t);
      setTimeout(()=>{ t.style.opacity=.9; }, 30);
      setTimeout(()=>{ t.style.opacity=.0; t.style.transform='translateY(6px)'; }, 2600);
      setTimeout(()=>{ box.removeChild(t); }, 3000);
    }

    function openModal(id){ qs('#'+id).classList.add('open'); }
    function closeModal(id){ qs('#'+id).classList.remove('open'); }

    // ----------- Render principal -----------
    const main = qs('#main');
    const bcSection = qs('#bc-section');
    const bcCurrent = qs('#bc-current');

    function setSection(sec){
      STATE.selectedSection = sec; saveState();
      qsa('.side-link[data-section]').forEach(a=>{
        a.classList.toggle('active', a.getAttribute('data-section')===sec);
      });
      bcSection.textContent = sec.charAt(0).toUpperCase() + sec.slice(1);
      if(sec!=='paginas'){ bcCurrent.textContent = ''; }
      render();
    }

    function setPage(page){
      STATE.selectedSection = 'paginas';
      STATE.selectedPage = page; saveState();
      qsa('.subpage-link').forEach(a=>{
        a.classList.toggle('active', a.getAttribute('data-page')===page);
      });
      bcSection.textContent = 'Páginas';
      bcCurrent.textContent = STATE.pages[page]?.title || 'Página';
      render();
    }

    // Eventos sidebar
    qsa('.side-link[data-section]').forEach(a=>{
      a.addEventListener('click', (e)=>{ e.preventDefault(); setSection(a.getAttribute('data-section')); });
    });
    qsa('.subpage-link').forEach(a=>{
      a.addEventListener('click', (e)=>{ e.preventDefault(); setPage(a.getAttribute('data-page')); });
    });

    // ----------- Renderizadores por sección -----------
    function render(){
      const sec = STATE.selectedSection;
      if(sec==='paginas'){ renderPaginas(); }
      else if(sec==='medios'){ renderMedios(); }
      else if(sec==='menu'){ renderMenu(); }
      else if(sec==='noticias'){ renderNoticias(); }
      else if(sec==='eventos'){ renderEventos(); }
      else if(sec==='ajustes'){ renderAjustes(); }
    }

    // UI páginas y módulos
    function renderPaginas(){
      const pid = STATE.selectedPage || 'inicio';
      const page = STATE.pages[pid];
      if(!page){ main.innerHTML = `<div class="empty">Página no encontrada.</div>`; return; }
      bcCurrent.textContent = page.title;

      let html = `
        <div class="panel">
          <div class="panel-title">
            <div class="title"><i class="fa-solid fa-file-lines"></i><h2 style="margin:0">${page.title}</h2></div>
            <div style="display:flex; gap:8px">
              <span class="eco-badge"><i class="fa-solid fa-leaf"></i> Paleta ECO</span>
              <button class="btn accent icon" onclick="openModulePicker()"><i class="fa-solid fa-plus"></i> Agregar módulo</button>
            </div>
          </div>
          <div class="muted">Agrega, edita, reordena o elimina módulos. (Maqueta; se guarda en localStorage) • Consejo: arrastra las tarjetas para reordenarlas.</div>
        </div>
      `;

      // Lista de módulos
      html += `<div class="grid" id="modulesGrid" style="gap:16px">`;
      page.modules.forEach((mod, idx)=>{
        html += moduleCard(pid, idx, mod);
      });
      html += `</div>`;

      main.innerHTML = html;
      initDnD('#modulesGrid'); // ← habilita arrastrar/soltar
    }

    function moduleCard(pid, index, mod){
      const visTag = `<span class="tag ${mod.visible?'green':'gray'}"><i class="fa-solid ${mod.visible?'fa-eye':'fa-eye-slash'}"></i>${mod.visible?'Visible':'Oculto'}</span>`;
      const typeTag = `<span class="tag blue"><i class="fa-solid fa-layer-group"></i>${mod.type}</span>`;
      return `
      <div class="module-card" draggable="true" data-pid="${pid}" data-index="${index}">
        <div class="module-head">
          <div class="tags">${typeTag} ${visTag}</div>
          <div class="module-actions">
            <button class="btn small ghost" title="Subir" onclick="moveModule('${pid}', ${index}, -1)"><i class="fa-solid fa-arrow-up"></i></button>
            <button class="btn small ghost" title="Bajar" onclick="moveModule('${pid}', ${index}, 1)"><i class="fa-solid fa-arrow-down"></i></button>
            <button class="btn small" onclick="toggleVisible('${pid}', ${index})">${mod.visible?'Ocultar':'Mostrar'}</button>
            <button class="btn small" onclick="openEditor('${pid}', ${index})"><i class="fa-solid fa-pen-to-square"></i> Editar</button>
            <button class="btn small" onclick="duplicateModule('${pid}', ${index})"><i class="fa-regular fa-clone"></i></button>
            <button class="btn small warning" onclick="deleteModule('${pid}', ${index})"><i class="fa-solid fa-trash"></i></button>
          </div>
        </div>
        <div class="module-body">
          ${modulePreview(mod)}
        </div>
        <div class="module-footer">
          <span class="muted">Tipo: ${mod.type}</span>
        </div>
      </div>`;
    }

    function modulePreview(mod){
      try{
        switch(mod.type){
          case 'carousel':
            return `<div class="preview-carousel">` + mod.data.slides.slice(0,3).map(s=>`
              <div class="slide">
                <div class="thumb">${s.image?`<img src="${s.image}" alt="">`:`<i class="fa-regular fa-image"></i>`}</div>
                <div><strong>${escapeHtml(s.title||'Slide')}</strong><div class="muted" style="font-size:12px">${escapeHtml(s.subtitle||'Subtítulo')}</div></div>
              </div>`).join('') + `</div>`;
          case 'top_features':
            return `<div class="grid cols-3">` + (mod.data.items||[]).slice(0,3).map(it=>`
              <div class="kv">
                <div style="display:flex; align-items:center; justify-content:center; height:66px; width:100%; background:#f3faf4; border-radius:12px; color:var(--primary)">
                  <i class="fa-solid ${it.icon||'fa-leaf'}"></i>
                </div>
                <div><strong>${escapeHtml(it.title||'Título')}</strong><div class="muted" style="font-size:12px">${escapeHtml(it.text||'Descripción breve')}</div></div>
              </div>`).join('') + `</div>`;
          case 'about_metrics':
            return `<div class="grid cols-2">
              <div>${escapeHtml(mod.data.about||'Descripción')}</div>
              <div class="grid cols-2">` + (mod.data.metrics||[]).slice(0,4).map(m=>`
                <div class="kv">
                  <div style="font-size:26px; font-weight:800; color:var(--secondary)">${escapeHtml(m.value||'0')}</div>
                  <div class="muted">${escapeHtml(m.label||'Métrica')}</div>
                </div>`).join('') + `</div>
            </div>`;
          case 'convocatoria':
            return `<div class="grid cols-2">
              <div><strong>${escapeHtml(mod.data.titulo||'Convocatoria')}</strong><div class="muted">${escapeHtml(mod.data.descripcion||'Descripción')}</div></div>
              <div><span class="chip"><i class="fa-solid ${mod.data.activa?'fa-toggle-on':'fa-toggle-off'}"></i> ${mod.data.activa?'Activa':'Inactiva'}</span></div>
            </div>`;
          case 'team':
            return `<div class="grid cols-2">` + (mod.data.people||[]).map(p=>`
              <div class="kv">
                <img src="${p.photo||avatar('U')}" alt="" style="width:70px;height:70px;border-radius:50%;background:#f1f5f9;object-fit:cover" />
                <div><strong>${escapeHtml(p.name||'Nombre')}</strong><div class="muted" style="font-size:12px">${escapeHtml(p.role||'Rol')}</div><div style="font-size:12px">${escapeHtml(p.bio||'Bio')}</div></div>
              </div>`).join('') + `</div>`;
          case 'paragraph':
            return `<div>${(mod.data.html||'').slice(0,300)}${(mod.data.html||'').length>300?'…':''}</div>`;
          case 'gallery':
            return `<div class="grid cols-3">` + (mod.data.images||[]).slice(0,6).map(img=>`
              <div class="slide">
                <div class="thumb">${img.image?`<img src="${img.image}" alt="">`:`<i class="fa-regular fa-image"></i>`}</div>
                <div class="muted" style="font-size:12px">${escapeHtml(img.caption||'Caption')}</div>
              </div>`).join('') + `</div>`;
          case 'badges':
            return `<div class="preview-badges">` + (mod.data.items||[]).map(b=>{
              const warn = !hasGoodContrast(b.bg||'#eee', b.color||'#333');
              return `<span class="tag" style="background:${b.bg||'#eee'}; color:${b.color||'#333'}; ${warn?'outline:2px solid #ef4444':''}">
                <i class="fa-solid ${b.icon||'fa-tag'}"></i>${escapeHtml(b.text||'Badge')}
              </span>`;
            }).join('') + `</div>` + `<div class="help">${contrastLegend()}</div>`;
          case 'news':
            return `<div class="grid cols-3">` + (mod.data.posts||[]).map(n=>`
              <div class="slide">
                <div class="thumb">${n.cover?`<img src="${n.cover}" alt="">`:`<i class="fa-regular fa-newspaper"></i>`}</div>
                <div><strong>${escapeHtml(n.title||'Noticia')}</strong><div class="muted" style="font-size:12px">${escapeHtml(n.excerpt||'…')}</div></div>
              </div>`).join('') + `</div>`;
          case 'events':
            return `<div class="grid cols-2">`+ (mod.data.items||[]).map(ev=>`
              <div class="kv">
                <div style="font-weight:800; color:var(--accent2)">${escapeHtml(ev.date||'Fecha')}</div>
                <div><strong>${escapeHtml(ev.title||'Evento')}</strong><div class="muted" style="font-size:12px">${escapeHtml(ev.place||'Lugar')}</div></div>
              </div>`).join('') + `</div>`;
          case 'facts':
            return `<div class="grid cols-4">`+(mod.data.items||[]).map(f=>`
              <div class="kv">
                <div style="font-size:26px; font-weight:800; color:var(--primary)">${escapeHtml(f.value||'0')}</div>
                <div class="muted">${escapeHtml(f.label||'Dato')}</div>
              </div>`).join('')+`</div>`;
          case 'map':
            return `<div class="muted">Mapa embebido: ${escapeHtml(mod.data.url||'https://...')}</div>`;
          case 'social':
            return `<div class="grid cols-2">
              <div><i class="fa-brands fa-facebook"></i> ${escapeHtml(mod.data.facebook||'#')}</div>
              <div><i class="fa-brands fa-instagram"></i> ${escapeHtml(mod.data.instagram||'#')}</div>
            </div>`;
          default:
            return `<div class="muted">Vista previa no disponible para este tipo. Usa “Editar” → JSON.</div>`;
        }
      }catch(e){
        return `<div class="muted">Error en la vista previa.</div>`;
      }
    }

    // ----------- Arrastrar/soltar -----------
    function initDnD(gridSel){
      const grid = qs(gridSel);
      let dragIndex = null;
      qsa('.module-card', grid).forEach(card=>{
        card.addEventListener('dragstart', (e)=>{
          dragIndex = +card.getAttribute('data-index');
          card.classList.add('dragging');
          e.dataTransfer.effectAllowed = 'move';
        });
        card.addEventListener('dragend', ()=>{
          card.classList.remove('dragging');
          qsa('.module-card', grid).forEach(c=>c.classList.remove('drop-target'));
        });
        card.addEventListener('dragover', (e)=>{
          e.preventDefault();
          const over = e.currentTarget;
          if(!over.classList.contains('dragging')){
            over.classList.add('drop-target');
          }
        });
        card.addEventListener('dragleave', (e)=>{
          e.currentTarget.classList.remove('drop-target');
        });
        card.addEventListener('drop', (e)=>{
          e.preventDefault();
          const over = e.currentTarget;
          over.classList.remove('drop-target');
          const targetIndex = +over.getAttribute('data-index');
          const pid = over.getAttribute('data-pid');
          if(dragIndex===null || targetIndex===dragIndex) return;
          reorderModules(pid, dragIndex, targetIndex);
          dragIndex = null;
        });
      });
    }
    function reorderModules(pid, fromIdx, toIdx){
      const list = STATE.pages[pid].modules;
      const [m] = list.splice(fromIdx,1);
      list.splice(toIdx,0,m);
      saveState(); renderPaginas();
      toast('Módulos reordenados','success');
    }

    // ----------- Acciones de módulos -----------
    function moveModule(pid, idx, dir){
      const list = STATE.pages[pid].modules;
      const ni = idx + dir;
      if(ni<0 || ni>=list.length) return;
      const x = list[idx]; list[idx]=list[ni]; list[ni]=x;
      saveState(); renderPaginas();
    }
    function toggleVisible(pid, idx){
      STATE.pages[pid].modules[idx].visible = !STATE.pages[pid].modules[idx].visible;
      saveState(); renderPaginas();
    }
    function duplicateModule(pid, idx){
      const m = JSON.parse(JSON.stringify(STATE.pages[pid].modules[idx]));
      STATE.pages[pid].modules.splice(idx+1, 0, m);
      saveState(); renderPaginas();
    }
    function deleteModule(pid, idx){
      STATE.pages[pid].modules.splice(idx,1);
      saveState(); renderPaginas();
      toast('Módulo eliminado','warn');
    }

    // ----------- Picker de módulos -----------
    const MODULE_TYPES = [
      {type:'carousel', icon:'fa-images', label:'Carrusel'},
      {type:'gallery', icon:'fa-grid-2', label:'Galería'},
      {type:'paragraph', icon:'fa-paragraph', label:'Párrafos'},
      {type:'badges', icon:'fa-bookmark', label:'Badges / Chips'},
      {type:'blocks', icon:'fa-square', label:'Bloques / Divs'},
      {type:'social', icon:'fa-share-nodes', label:'Redes sociales'},
      {type:'events', icon:'fa-calendar-days', label:'Panel de eventos'},
      {type:'news', icon:'fa-newspaper', label:'Noticias / Blog'},
      {type:'top_features', icon:'fa-gem', label:'Top Features'},
      {type:'about_metrics', icon:'fa-chart-simple', label:'About / Métricas'},
      {type:'convocatoria', icon:'fa-user-check', label:'Convocatoria'},
      {type:'team', icon:'fa-users', label:'Equipo / Personas'},
      {type:'map', icon:'fa-map-location-dot', label:'Mapa / Iframe'},
    ];

    function openModulePicker(){
      const grid = qs('#pickerGrid'); grid.innerHTML='';
      MODULE_TYPES.forEach(mt=>{
        const card = document.createElement('div');
        card.className='module-card';
        card.innerHTML = `
          <div class="module-head"><div class="title"><i class="fa-solid ${mt.icon}"></i><strong>${mt.label}</strong></div></div>
          <div class="module-body muted">Añade un módulo de tipo <b>${mt.type}</b> a la página activa.</div>
          <div class="module-footer">
            <button class="btn secondary" onclick="addModule('${mt.type}')"><i class="fa-solid fa-plus"></i> Agregar</button>
          </div>
        `;
        grid.appendChild(card);
      });
      openModal('modalPicker');
    }

    function addModule(type){
      closeModal('modalPicker');
      const pid = STATE.selectedPage;
      const base = defaultModule(type);
      STATE.pages[pid].modules.push(base);
      saveState(); renderPaginas();
      openEditor(pid, STATE.pages[pid].modules.length-1);
    }

    function defaultModule(type){
      const M = {type, visible:true, data:{}};
      switch(type){
        case 'carousel': M.data={slides:[{image:dummyImg(), title:'Título', subtitle:'Subtítulo', alt:'Alt'}]}; break;
        case 'gallery': M.data={images:[{image:dummyImg(), caption:'Imagen'}]}; break;
        case 'paragraph': M.data={html:'Texto de ejemplo.'}; break;
        case 'badges': M.data={items:[{text:'Ejemplo', bg:'#E6F6EE', color:'#0E6C3A', icon:'fa-tag'}]}; break;
        case 'blocks': M.data={blocks:[{variant:'hero', title:'Bloque', text:'Descripción', ctaText:'Ir', ctaLink:'#', bg:'#E6F6EE'}]}; break;
        case 'social': M.data={facebook:'#', instagram:'#', youtube:'#', linkedin:'#'}; break;
        case 'events': M.data={items:[{title:'Evento', date:'2025-01-01', place:'Lugar', link:'#', featured:false}]}; break;
        case 'news': M.data={posts:[{title:'Noticia', slug:'noticia', excerpt:'Extracto', date:'2025-01-01', author:'Autor', tags:['General'], cover:dummyImg(), status:'Borrador'}]}; break;
        case 'top_features': M.data={items:[{icon:'fa-leaf', title:'Feature', text:'Texto'}]}; break;
        case 'about_metrics': M.data={about:'Descripción', metrics:[{value:'0', label:'Métrica'}]}; break;
        case 'convocatoria': M.data={activa:false, titulo:'Convocatoria', descripcion:'Descripción', campos:['Nombre','Correo']}; break;
        case 'team': M.data={people:[{name:'Persona', role:'Rol', bio:'Bio', photo:avatar('P')}]}; break;
        case 'map': M.data={url:'https://maps.google.com/...', width:'100%', height:'360'}; break;
      }
      return M;
    }

    // ----------- Editor -----------
    let EDIT_PID=null, EDIT_INDEX=null, EDIT_MOD=null, EDIT_TAB='form';
    function openEditor(pid, idx){
      EDIT_PID = pid; EDIT_INDEX = idx;
      EDIT_MOD = JSON.parse(JSON.stringify(STATE.pages[pid].modules[idx]));
      qs('#editTitle').textContent = `Editar módulo (${EDIT_MOD.type})`;
      renderFormEditor(EDIT_MOD);
      qs('#jsonArea').value = JSON.stringify(EDIT_MOD.data, null, 2);
      openModal('modalEditor');
    }
    function toggleEditTab(tab){
      EDIT_TAB = tab;
      qs('#formEditor').style.display = (tab==='form')?'grid':'none';
      qs('#jsonEditor').style.display = (tab==='json')?'block':'none';
    }
    function renderFormEditor(mod){
      const c = qs('#formEditor'); c.innerHTML='';
      // Form rápido para algunos tipos
      if(mod.type==='paragraph'){
        c.innerHTML = `
          <div class="form-grid" style="grid-column:1 / -1">
            <label>Contenido (HTML simple)</label>
            <textarea id="p_html">${escapeHtml(mod.data.html||'')}</textarea>
          </div>`;
      }else if(mod.type==='convocatoria'){
        c.innerHTML = `
          <div>
            <label>Activa</label>
            <select id="co_activa"><option value="true"${mod.data.activa?' selected':''}>Sí</option><option value="false"${!mod.data.activa?' selected':''}>No</option></select>
          </div>
          <div><label>Título</label><input type="text" id="co_titulo" value="${escapeAttr(mod.data.titulo||'')}" /></div>
          <div class="form-grid" style="grid-column:1 / -1">
            <label>Descripción</label><textarea id="co_desc">${escapeHtml(mod.data.descripcion||'')}</textarea>
            <div class="help">Campos: Nombre, Correo, etc. (usa el editor JSON para agregar más)</div>
          </div>`;
      }else if(mod.type==='top_features'){
        c.innerHTML = (mod.data.items||[]).map((it,i)=>`
          <div><label>Ícono FA #${i+1}</label><input type="text" data-idx="${i}" data-key="icon" value="${escapeAttr(it.icon||'fa-leaf')}" /></div>
          <div><label>Título #${i+1}</label><input type="text" data-idx="${i}" data-key="title" value="${escapeAttr(it.title||'')}" /></div>
          <div class="form-grid" style="grid-column:1 / -1">
            <label>Texto #${i+1}</label><input type="text" data-idx="${i}" data-key="text" value="${escapeAttr(it.text||'')}" />
          </div>
        `).join('') + `<div style="grid-column:1 / -1"><button class="btn small" onclick="addFeature()">+ Agregar feature</button></div>`;
      }else if(mod.type==='badges'){
        c.innerHTML = (mod.data.items||[]).map((b,i)=>`
          <div><label>Texto #${i+1}</label><input type="text" data-idx="${i}" data-key="text" value="${escapeAttr(b.text||'')}" /></div>
          <div><label>Icono FA</label><input type="text" data-idx="${i}" data-key="icon" value="${escapeAttr(b.icon||'fa-tag')}" /></div>
          <div><label>Fondo</label><input type="text" data-idx="${i}" data-key="bg" value="${escapeAttr(b.bg||'#E6F6EE')}" /></div>
          <div><label>Texto</label><input type="text" data-idx="${i}" data-key="color" value="${escapeAttr(b.color||'#0E6C3A')}" /></div>
        `).join('') + `<div style="grid-column:1 / -1"><button class="btn small" onclick="addBadge()">+ Agregar badge</button> <span class="help">${contrastLegend()}</span></div>`;
      }else if(mod.type==='about_metrics'){
        c.innerHTML = `
          <div class="form-grid" style="grid-column:1 / -1">
            <label>Descripción</label>
            <textarea id="am_about">${escapeHtml(mod.data.about||'')}</textarea>
          </div>` + (mod.data.metrics||[]).map((m,i)=>`
          <div><label>Valor #${i+1}</label><input type="text" data-idx="${i}" data-key="value" value="${escapeAttr(m.value||'')}" /></div>
          <div><label>Etiqueta #${i+1}</label><input type="text" data-idx="${i}" data-key="label" value="${escapeAttr(m.label||'')}" /></div>
        `).join('') + `<div style="grid-column:1 / -1"><button class="btn small" onclick="addMetric()">+ Agregar métrica</button></div>`;
      }else if(mod.type==='team'){
        c.innerHTML = (mod.data.people||[]).map((p,i)=>`
          <div><label>Nombre #${i+1}</label><input type="text" data-idx="${i}" data-key="name" value="${escapeAttr(p.name||'')}" /></div>
          <div><label>Rol</label><input type="text" data-idx="${i}" data-key="role" value="${escapeAttr(p.role||'')}" /></div>
          <div class="form-grid" style="grid-column:1 / -1">
            <label>Bio</label><textarea data-idx="${i}" data-key="bio">${escapeHtml(p.bio||'')}</textarea>
          </div>
        `).join('') + `<div style="grid-column:1 / -1"><button class="btn small" onclick="addPerson()">+ Agregar persona</button></div>`;
      }else if(mod.type==='carousel'){
        c.innerHTML = (mod.data.slides||[]).map((s,i)=>`
          <div><label>Título #${i+1}</label><input type="text" data-idx="${i}" data-key="title" value="${escapeAttr(s.title||'')}" /></div>
          <div><label>Subtítulo</label><input type="text" data-idx="${i}" data-key="subtitle" value="${escapeAttr(s.subtitle||'')}" /></div>
          <div><label>Alt</label><input type="text" data-idx="${i}" data-key="alt" value="${escapeAttr(s.alt||'')}" /></div>
          <div><label>Imagen (URL o data URI)</label><input type="url" data-idx="${i}" data-key="image" value="${escapeAttr(s.image||'')}" /></div>
        `).join('') + `<div style="grid-column:1 / -1"><button class="btn small" onclick="addSlide()">+ Agregar slide</button></div>`;
      }else{
        c.innerHTML = `<div class="help">Este tipo no tiene formulario rápido. Usa la pestaña JSON.</div>`;
      }
    }

    function addFeature(){ EDIT_MOD.data.items = EDIT_MOD.data.items||[]; EDIT_MOD.data.items.push({icon:'fa-leaf', title:'Nuevo', text:'...'}); renderFormEditor(EDIT_MOD); }
    function addBadge(){ EDIT_MOD.data.items = EDIT_MOD.data.items||[]; EDIT_MOD.data.items.push({text:'Nuevo', bg:'#E6F6EE', color:'#0E6C3A', icon:'fa-tag'}); renderFormEditor(EDIT_MOD); }
    function addMetric(){ EDIT_MOD.data.metrics = EDIT_MOD.data.metrics||[]; EDIT_MOD.data.metrics.push({value:'0', label:'Métrica'}); renderFormEditor(EDIT_MOD); }
    function addPerson(){ EDIT_MOD.data.people = EDIT_MOD.data.people||[]; EDIT_MOD.data.people.push({name:'Persona', role:'Rol', bio:'Bio', photo:avatar('P')}); renderFormEditor(EDIT_MOD); }
    function addSlide(){ EDIT_MOD.data.slides = EDIT_MOD.data.slides||[]; EDIT_MOD.data.slides.push({image:dummyImg(), title:'Slide', subtitle:'...', alt:'Alt'}); renderFormEditor(EDIT_MOD); }

    function saveEditor(){
      // Leer pestaña activa
      if(EDIT_TAB==='json'){
        try{
          const data = JSON.parse(qs('#jsonArea').value);
          EDIT_MOD.data = data;
        }catch(e){
          toast('JSON inválido','warn'); return;
        }
      }else{
        // Sync de formulario rápido -> EDIT_MOD
        if(EDIT_MOD.type==='paragraph'){
          EDIT_MOD.data.html = qs('#p_html').value;
        }else if(EDIT_MOD.type==='convocatoria'){
          EDIT_MOD.data.activa = (qs('#co_activa').value==='true');
          EDIT_MOD.data.titulo = qs('#co_titulo').value;
          EDIT_MOD.data.descripcion = qs('#co_desc').value;
        }else if(EDIT_MOD.type==='top_features'){
          (EDIT_MOD.data.items||[]).forEach((_,i)=>{
            EDIT_MOD.data.items[i].icon = qs(`[data-idx="${i}"][data-key="icon"]`).value;
            EDIT_MOD.data.items[i].title = qs(`[data-idx="${i}"][data-key="title"]`).value;
            EDIT_MOD.data.items[i].text = qs(`[data-idx="${i}"][data-key="text"]`).value;
          });
        }else if(EDIT_MOD.type==='badges'){
          (EDIT_MOD.data.items||[]).forEach((_,i)=>{
            EDIT_MOD.data.items[i].text = qs(`[data-idx="${i}"][data-key="text"]`).value;
            EDIT_MOD.data.items[i].icon = qs(`[data-idx="${i}"][data-key="icon"]`).value;
            EDIT_MOD.data.items[i].bg = qs(`[data-idx="${i}"][data-key="bg"]`).value;
            EDIT_MOD.data.items[i].color = qs(`[data-idx="${i}"][data-key="color"]`).value;
          });
        }else if(EDIT_MOD.type==='about_metrics'){
          EDIT_MOD.data.about = qs('#am_about').value;
          (EDIT_MOD.data.metrics||[]).forEach((_,i)=>{
            EDIT_MOD.data.metrics[i].value = qs(`[data-idx="${i}"][data-key="value"]`).value;
            EDIT_MOD.data.metrics[i].label = qs(`[data-idx="${i}"][data-key="label"]`).value;
          });
        }else if(EDIT_MOD.type==='team'){
          (EDIT_MOD.data.people||[]).forEach((_,i)=>{
            EDIT_MOD.data.people[i].name = qs(`[data-idx="${i}"][data-key="name"]`).value;
            EDIT_MOD.data.people[i].role = qs(`[data-idx="${i}"][data-key="role"]`).value;
            const t = qsa('textarea[data-idx="'+i+'"][data-key="bio"]')[0];
            if(t) EDIT_MOD.data.people[i].bio = t.value;
          });
        }else if(EDIT_MOD.type==='carousel'){
          (EDIT_MOD.data.slides||[]).forEach((_,i)=>{
            EDIT_MOD.data.slides[i].title = qs(`[data-idx="${i}"][data-key="title"]`).value;
            EDIT_MOD.data.slides[i].subtitle = qs(`[data-idx="${i}"][data-key="subtitle"]`).value;
            EDIT_MOD.data.slides[i].alt = qs(`[data-idx="${i}"][data-key="alt"]`).value;
            EDIT_MOD.data.slides[i].image = qs(`[data-idx="${i}"][data-key="image"]`).value;
          });
        }
      }

      // Guardar cambios en STATE
      STATE.pages[EDIT_PID].modules[EDIT_INDEX] = JSON.parse(JSON.stringify(EDIT_MOD));
      saveState(); closeModal('modalEditor'); renderPaginas();
      toast('Módulo actualizado', 'success');
    }

    // ----------- Sección Medios (simulado) -----------
    function renderMedios(){
      const media = STATE.media||[];
      main.innerHTML = `
        <div class="panel">
          <div class="panel-title">
            <div class="title"><i class="fa-solid fa-photo-film"></i><h2 style="margin:0">Medios</h2></div>
            <div><button class="btn secondary icon" onclick="pickFiles()"><i class="fa-solid fa-upload"></i> Subir archivos</button></div>
          </div>
          <div class="muted">Subida simulada (archivos se leen con FileReader y se guardan en localStorage).</div>
        </div>
        <div class="media-grid">
          ${media.map((m,i)=>`
            <div class="media-item">
              <div class="media-thumb">${m.type.startsWith('image/')?`<img src="${m.url}" alt="" />`:`<i class="fa-regular fa-file" style="font-size:24px"></i>`}</div>
              <div class="media-meta"><div><strong>${escapeHtml(m.name)}</strong></div><div class="muted">${escapeHtml(m.type)} • ${(m.size/1024).toFixed(0)} KB</div></div>
            </div>
          `).join('')}
        </div>
        <input type="file" id="fileInput" multiple style="display:none" />
      `;
      const finput = qs('#fileInput');
      finput.addEventListener('change', handleFiles);
    }

    function pickFiles(){ qs('#fileInput').click(); }
    function handleFiles(e){
      const files = Array.from(e.target.files||[]);
      if(!files.length) return;
      const allowed = ['image/jpeg','image/png','image/webp','application/pdf'];
      const max = 10*1024*1024; // 10MB
      let pending = files.length;
      files.forEach(f=>{
        if(!allowed.includes(f.type)) { toast(`Formato no permitido: ${f.name}`,'warn'); if(--pending===0) renderMedios(); return; }
        if(f.size>max){ toast(`Archivo muy grande: ${f.name}`,'warn'); if(--pending===0) renderMedios(); return; }
        const reader = new FileReader();
        reader.onload = ()=>{
          (STATE.media=STATE.media||[]).push({name:f.name, type:f.type, size:f.size, url:reader.result});
          saveState(); if(--pending===0){ renderMedios(); toast('Archivos cargados','success'); }
        };
        reader.readAsDataURL(f);
      });
      e.target.value='';
    }

    // ----------- Sección Menú (simulado) -----------
    function renderMenu(){
      const menu = STATE.menu||[];
      main.innerHTML = `
        <div class="panel">
          <div class="panel-title"><div class="title"><i class="fa-solid fa-list"></i><h2 style="margin:0">Menú del sitio</h2></div></div>
          <div class="muted">Estructura simulada. Sin BD.</div>
        </div>
        <div class="panel">
          ${menu.map(item => `
            <div style="padding:10px 0">
              <div><strong>${escapeHtml(item.text)}</strong> <span class="muted">→ ${escapeHtml(item.url)}</span></div>
              ${(item.children||[]).map(ch=>`<div style="margin-left:16px; color:#334155">• ${escapeHtml(ch.text)} <span class="muted">→ ${escapeHtml(ch.url)}</span></div>`).join('')}
            </div>
            <div class="divider"></div>
          `).join('')}
        </div>
      `;
    }

    // ----------- Sección Noticias -----------
    function renderNoticias(){
      const posts = STATE.news||[];
      main.innerHTML = `
        <div class="panel">
          <div class="panel-title">
            <div class="title"><i class="fa-solid fa-newspaper"></i><h2 style="margin:0">Noticias</h2></div>
            <div>
              <button class="btn icon" onclick="generateRSS()"><i class="fa-solid fa-rss"></i> RSS</button>
              <button class="btn secondary icon" onclick="addNews()"><i class="fa-solid fa-plus"></i> Nueva</button>
            </div>
          </div>
          <div class="grid cols-3" id="newsGrid">
            ${posts.map((n,i)=>`
              <div class="module-card">
                <div class="module-head">
                  <div class="tags"><span class="tag ${n.status==='Publicado'?'green':'gray'}">${n.status}</span></div>
                  <div class="module-actions">
                    <button class="btn small" onclick="editNews(${i})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn small warning" onclick="deleteNews(${i})"><i class="fa-solid fa-trash"></i></button>
                  </div>
                </div>
                <div class="module-body">
                  <div class="kv">
                    <div style="height:70px; background:#f1f5f9; border-radius:12px; overflow:hidden; display:flex; align-items:center; justify-content:center">
                      ${n.cover?`<img src="${n.cover}" alt="" style="width:100%; height:100%; object-fit:cover">`:`<i class="fa-regular fa-image"></i>`}
                    </div>
                    <div><strong>${escapeHtml(n.title)}</strong><div class="muted" style="font-size:12px">${escapeHtml(n.excerpt)}</div><div class="muted" style="font-size:12px"><i class="fa-regular fa-calendar"></i> ${n.date} • ${escapeHtml(n.author)}</div></div>
                  </div>
                  <div style="margin-top:8px">${(n.tags||[]).map(t=>`<span class="tag blue">${escapeHtml(t)}</span>`).join(' ')}</div>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }
    function addNews(){
      (STATE.news=STATE.news||[]).push({title:'Nueva noticia', slug:'nueva', excerpt:'…', date:new Date().toISOString().slice(0,10), author:'DIRSU', tags:['General'], cover:dummyImg('green','Noticia'), status:'Borrador'});
      saveState(); renderNoticias();
    }
    function editNews(i){
      EDIT_PID = null; EDIT_INDEX = i; EDIT_MOD = {type:'news_item', data: STATE.news[i]};
      qs('#editTitle').textContent = 'Editar noticia';
      qs('#formEditor').innerHTML = `
        <div><label>Título</label><input id="n_title" type="text" value="${escapeAttr(STATE.news[i].title)}" /></div>
        <div><label>Slug</label><input id="n_slug" type="text" value="${escapeAttr(STATE.news[i].slug)}" /></div>
        <div><label>Fecha</label><input id="n_date" type="text" value="${escapeAttr(STATE.news[i].date)}" /></div>
        <div><label>Autor</label><input id="n_author" type="text" value="${escapeAttr(STATE.news[i].author)}" /></div>
        <div class="form-grid" style="grid-column:1 / -1">
          <label>Extracto</label>
          <textarea id="n_excerpt">${escapeHtml(STATE.news[i].excerpt)}</textarea>
        </div>
        <div><label>Portada (URL o data URI)</label><input id="n_cover" type="url" value="${escapeAttr(STATE.news[i].cover||'')}" /></div>
        <div><label>Estado</label>
          <select id="n_status">
            <option${STATE.news[i].status==='Borrador'?' selected':''}>Borrador</option>
            <option${STATE.news[i].status==='Publicado'?' selected':''}>Publicado</option>
          </select>
        </div>
      `;
      qs('#jsonArea').value = JSON.stringify(STATE.news[i], null, 2);
      openModal('modalEditor');
      EDIT_TAB='form'; toggleEditTab('form');
    }
    function deleteNews(i){ STATE.news.splice(i,1); saveState(); renderNoticias(); toast('Noticia eliminada','warn'); }
    function saveEditorNews(){
      const i = EDIT_INDEX; if(i==null) return;
      // tomar del form si pestaña form
      if(EDIT_TAB==='form'){
        EDIT_MOD.data.title = qs('#n_title').value;
        EDIT_MOD.data.slug = qs('#n_slug').value;
        EDIT_MOD.data.date = qs('#n_date').value;
        EDIT_MOD.data.author = qs('#n_author').value;
        EDIT_MOD.data.excerpt = qs('#n_excerpt').value;
        EDIT_MOD.data.cover = qs('#n_cover').value;
        EDIT_MOD.data.status = qs('#n_status').value;
      }
      STATE.news[i] = JSON.parse(JSON.stringify(EDIT_MOD.data));
      saveState(); renderNoticias(); closeModal('modalEditor'); toast('Noticia actualizada','success');
    }
    // Hijack saveEditor si es noticia
    const _saveEditor = saveEditor;
    saveEditor = function(){
      if(EDIT_MOD && EDIT_MOD.type==='news_item'){
        if(EDIT_TAB==='json'){
          try{ EDIT_MOD.data = JSON.parse(qs('#jsonArea').value); }
          catch(e){ toast('JSON inválido','warn'); return; }
        }
        return saveEditorNews();
      }
      _saveEditor();
    }

    function generateRSS(){
      const posts = (STATE.news||[]).filter(n=>n.status==='Publicado');
      const items = posts.map(n=>`<item><title>${xml(n.title)}</title><link>https://rsu.unitru.edu.pe/blog/${xml(n.slug)}</link><pubDate>${xml(n.date)}</pubDate><description>${xml(n.excerpt)}</description></item>`).join('');
      const rss = `<?xml version="1.0" encoding="UTF-8"?><rss version="2.0"><channel><title>DIRSU Noticias</title><link>https://rsu.unitru.edu.pe/</link><description>Noticias RSU</description>${items}</channel></rss>`;
      const blob = new Blob([rss], {type:'application/rss+xml'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href=url; a.download='rss.xml'; a.click(); URL.revokeObjectURL(url);
      toast('RSS generado (descarga)','success');
    }

    // ----------- Sección Eventos -----------
    function renderEventos(){
      const evts = STATE.events||[];
      main.innerHTML = `
        <div class="panel">
          <div class="panel-title">
            <div class="title"><i class="fa-solid fa-calendar-days"></i><h2 style="margin:0">Eventos</h2></div>
            <div><button class="btn secondary icon" onclick="addEvent()"><i class="fa-solid fa-plus"></i> Nuevo evento</button></div>
          </div>
          <div class="grid cols-2">
            ${evts.map((e,i)=>`
              <div class="module-card">
                <div class="module-head">
                  <div class="tags">
                    <span class="tag ${e.featured?'green':'gray'}">${e.featured?'Destacado':'Normal'}</span>
                  </div>
                  <div class="module-actions">
                    <button class="btn small" onclick="editEvent(${i})"><i class="fa-solid fa-pen"></i></button>
                    <button class="btn small warning" onclick="deleteEvent(${i})"><i class="fa-solid fa-trash"></i></button>
                  </div>
                </div>
                <div class="module-body">
                  <div class="kv">
                    <div style="font-weight:800; color:var(--accent2)">${escapeHtml(e.date)}</div>
                    <div><strong>${escapeHtml(e.title)}</strong><div class="muted" style="font-size:12px">${escapeHtml(e.place)} • <a href="${escapeAttr(e.link)}" target="_blank">Enlace</a></div></div>
                  </div>
                </div>
              </div>
            `).join('')}
          </div>
        </div>
      `;
    }
    function addEvent(){ (STATE.events=STATE.events||[]).push({title:'Evento', date:new Date().toISOString().slice(0,10), place:'Lugar', link:'#', featured:false}); saveState(); renderEventos(); }
    function editEvent(i){
      EDIT_MOD={type:'event_item', data:STATE.events[i]}; EDIT_INDEX=i;
      qs('#editTitle').textContent='Editar evento';
      qs('#formEditor').innerHTML = `
        <div><label>Título</label><input id="e_title" type="text" value="${escapeAttr(STATE.events[i].title)}" /></div>
        <div><label>Fecha</label><input id="e_date" type="text" value="${escapeAttr(STATE.events[i].date)}" /></div>
        <div><label>Lugar</label><input id="e_place" type="text" value="${escapeAttr(STATE.events[i].place)}" /></div>
        <div><label>Enlace</label><input id="e_link" type="url" value="${escapeAttr(STATE.events[i].link)}" /></div>
        <div><label>Destacado</label>
          <select id="e_feat"><option value="true"${STATE.events[i].featured?' selected':''}>Sí</option><option value="false"${!STATE.events[i].featured?' selected':''}>No</option></select>
        </div>
      `;
      qs('#jsonArea').value = JSON.stringify(STATE.events[i], null, 2);
      openModal('modalEditor'); toggleEditTab('form');
    }
    function deleteEvent(i){ STATE.events.splice(i,1); saveState(); renderEventos(); toast('Evento eliminado','warn'); }
    const _saveEditor2 = saveEditor;
    saveEditor = function(){
      if(EDIT_MOD && EDIT_MOD.type==='event_item'){
        if(EDIT_TAB==='json'){ try{ EDIT_MOD.data = JSON.parse(qs('#jsonArea').value); }catch(e){ toast('JSON inválido','warn'); return; } }
        else{
          EDIT_MOD.data.title = qs('#e_title').value;
          EDIT_MOD.data.date = qs('#e_date').value;
          EDIT_MOD.data.place = qs('#e_place').value;
          EDIT_MOD.data.link = qs('#e_link').value;
          EDIT_MOD.data.featured = (qs('#e_feat').value==='true');
        }
        STATE.events[EDIT_INDEX] = JSON.parse(JSON.stringify(EDIT_MOD.data));
        saveState(); renderEventos(); closeModal('modalEditor'); toast('Evento actualizado','success'); return;
      }
      _saveEditor2();
    }

    // ----------- Sección Ajustes (demo) -----------
    function renderAjustes(){
      main.innerHTML = `
        <div class="panel">
          <div class="panel-title"><div class="title"><i class="fa-solid fa-gear"></i><h2 style="margin:0">Ajustes (maqueta)</h2></div></div>
          <div class="grid cols-2">
            <div>
              <label>Tema</label>
              <select><option>Eco (por defecto)</option></select>
              <div class="help">Paleta ECO aplicada globalmente.</div>
            </div>
            <div>
              <label>Idioma</label>
              <select><option>Español</option></select>
            </div>
            <div style="grid-column:1 / -1">
              <button class="btn warning" onclick="localStorage.removeItem(LS_KEY); localStorage.removeItem(LS_MEDIA); location.reload();"><i class="fa-solid fa-rotate-left"></i> Resetear demo</button>
            </div>
          </div>
        </div>
      `;
    }

    // ----------- Vista previa en vivo -----------
    function buildPreviewHTML(pid){
      const page = STATE.pages[pid];
      if(!page) return '<div class="preview-container">Página no encontrada.</div>';
      let h = `<div class="preview-head"><h2 style="margin:0">${escapeHtml(page.title)}</h2></div><div class="preview-container">`;
      (page.modules||[]).forEach(m=>{
        if(!m.visible) return;
        if(m.type==='carousel' && m.data.slides && m.data.slides.length){
          const s = m.data.slides[0];
          h += `<div class="hero" style="margin-bottom:14px"><img src="${s.image||dummyImg()}" alt="${escapeAttr(s.alt||'')}"><div class="hero-caption"><strong>${escapeHtml(s.title||'')}</strong><div style="font-size:12px;opacity:.85">${escapeHtml(s.subtitle||'')}</div></div></div>`;
        }else if(m.type==='paragraph'){
          h += `<div class="panel"><div>${m.data.html||''}</div></div>`;
        }else if(m.type==='about_metrics'){
          h += `<div class="panel"><div class="grid cols-2"><div>${escapeHtml(m.data.about||'')}</div><div class="grid cols-2">`+(m.data.metrics||[]).map(x=>`<div><div style="font-weight:800;color:var(--secondary);font-size:22px">${escapeHtml(x.value||'')}</div><div class="muted">${escapeHtml(x.label||'')}</div></div>`).join('')+`</div></div></div>`;
        }else if(m.type==='gallery'){
          h += `<div class="panel"><div class="grid cols-3">`+(m.data.images||[]).map(im=>`<div class="hero"><img src="${im.image||dummyImg()}" alt=""><div class="hero-caption" style="font-size:12px">${escapeHtml(im.caption||'')}</div></div>`).join('')+`</div></div>`;
        }else if(m.type==='team'){
          h += `<div class="panel"><div class="grid cols-2">`+(m.data.people||[]).map(p=>`<div class="kv"><img src="${p.photo||avatar('U')}" style="width:70px;height:70px;border-radius:50%;object-fit:cover"><div><strong>${escapeHtml(p.name||'')}</strong><div class="muted">${escapeHtml(p.role||'')}</div><div style="font-size:12px">${escapeHtml(p.bio||'')}</div></div></div>`).join('')+`</div></div>`;
        }else if(m.type==='events'){
          h += `<div class="panel"><div class="grid cols-2">`+(m.data.items||[]).map(ev=>`<div class="kv"><div style="font-weight:800;color:var(--accent2)">${escapeHtml(ev.date||'')}</div><div><strong>${escapeHtml(ev.title||'')}</strong><div class="muted" style="font-size:12px">${escapeHtml(ev.place||'')}</div></div></div>`).join('')+`</div></div>`;
        }else if(m.type==='news'){
          h += `<div class="panel"><div class="grid cols-3">`+(m.data.posts||[]).map(n=>`<div class="hero"><img src="${n.cover||dummyImg()}" alt=""><div class="hero-caption" style="font-size:12px"><strong>${escapeHtml(n.title||'')}</strong></div></div>`).join('')+`</div></div>`;
        }else if(m.type==='badges'){
          h += `<div class="panel">`+ (m.data.items||[]).map(b=>`<span class="tag" style="background:${b.bg||'#eee'};color:${b.color||'#333'};margin-right:6px"><i class="fa-solid ${b.icon||'fa-tag'}"></i>${escapeHtml(b.text||'')}</span>`).join('') + `</div>`;
        }else if(m.type==='top_features'){
          h += `<div class="panel"><div class="grid cols-3">`+(m.data.items||[]).map(f=>`<div class="kv"><div style="height:66px;display:flex;align-items:center;justify-content:center;border-radius:12px;background:#f3faf4;color:var(--primary)"><i class="fa-solid ${f.icon||'fa-leaf'}"></i></div><div><strong>${escapeHtml(f.title||'')}</strong><div class="muted" style="font-size:12px">${escapeHtml(f.text||'')}</div></div></div>`).join('')+`</div></div>`;
        }
      });
      h += `</div>`;
      return h;
    }

    function openPreview(){
      const pid = STATE.selectedPage || 'inicio';
      qs('#pvTitle').textContent = 'Previsualización — ' + (STATE.pages[pid]?.title || pid);
      qs('#previewWrap').innerHTML = buildPreviewHTML(pid);
      openModal('modalPreview');
    }

    // ----------- Exportar JSON de la página -----------
    function exportPage(){
      if(STATE.selectedSection!=='paginas'){ toast('Abre la sección Páginas para exportar.','warn'); return; }
      const pid = STATE.selectedPage || 'inicio';
      const obj = STATE.pages[pid];
      const data = JSON.stringify(obj, null, 2);
      const blob = new Blob([data], {type:'application/json'});
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a'); a.href=url; a.download=`${pid}.json`; a.click(); URL.revokeObjectURL(url);
      toast('JSON exportado','success');
    }

    // ----------- Utilidades -----------
    function escapeHtml(s){ return (s||'').replace(/[&<>"']/g, m=>({ '&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[m])); }
    function escapeAttr(s){ return escapeHtml(s); }
    function xml(s){ return escapeHtml(s); }

    // Contraste simple (luma)
    function hexToRgb(h){
      const m = (h||'').trim().match(/^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i);
      if(!m) return {r:238,g:238,b:238};
      return {r:parseInt(m[1],16), g:parseInt(m[2],16), b:parseInt(m[3],16)};
    }
    function luma(c){ const a=[c.r,c.g,c.b].map(v=>{v/=255; return v<=0.03928 ? v/12.92 : Math.pow((v+0.055)/1.055,2.4)}); return 0.2126*a[0]+0.7152*a[1]+0.0722*a[2]; }
    function hasGoodContrast(bg, fg){
      const L1 = luma(hexToRgb((bg||'#eee')))+0.05; const L2 = luma(hexToRgb((fg||'#333')))+0.05;
      const ratio = (Math.max(L1,L2)/Math.min(L1,L2));
      return ratio >= 3.5; // relajado pero evita "oscuro/oscuro" o "claro/claro"
    }
    function contrastLegend(){ return 'Evita contrastes pobres: el sistema avisa si texto y fondo son muy similares.'; }

    // Header actions
    qs('#btn-preview').addEventListener('click', openPreview);
    qs('#btn-draft').addEventListener('click', ()=>{ saveState(); toast('Borrador guardado (localStorage)','success'); });
    qs('#btn-publish').addEventListener('click', ()=>toast('Publicación simulada','success'));
    qs('#btn-export').addEventListener('click', exportPage);

    // Inicialización
    qsa('.subpage-link').forEach(a=>{
      a.classList.toggle('active', a.getAttribute('data-page')===STATE.selectedPage);
    });
    qsa('.side-link[data-section]').forEach(a=>{
      a.classList.toggle('active', a.getAttribute('data-section')===STATE.selectedSection);
    });
    bcSection.textContent = STATE.selectedSection.charAt(0).toUpperCase() + STATE.selectedSection.slice(1);
    if(STATE.selectedSection==='paginas'){ bcCurrent.textContent = STATE.pages[STATE.selectedPage].title; }

    // Render
    render();

  </script>
</body>
</html>
