<?php
/* social.php â€” Vista â€œRed Socialâ€ (demo con catÃ¡logo de imÃ¡genes)
   Requiere: Bootstrap + FontAwesome (ya los tienes en el layout padre).
*/
include_once __DIR__ . '/funciones.php';

/* ===================== USUARIO Y CATÃLOGOS ===================== */
$usr = testeo(); // rol, usuario, ids
$facultades    = obtenerFacultades();
$fac_base      = (in_array((int)$usr['id_rol'], [3,5], true) && !empty($usr['id_escuela'])) ? (int)$usr['id_escuela'] : 0;
$departamentos = obtenerDepartamentos($fac_base ?: 0);
$periodos      = obtenerPeriodos();

/* ===================== FILTROS (GET) ===================== */
$facultad     = isset($_GET['facultad']) ? (int)$_GET['facultad'] : 0;
$departamento = isset($_GET['departamento']) ? (int)$_GET['departamento'] : 0;
$periodo      = isset($_GET['periodo']) ? (int)$_GET['periodo'] : 0;
$oficina      = isset($_GET['oficina']) ? (string)$_GET['oficina'] : ''; // '', 'PCF','DD','DF','RSU','APROB','SIN'
$orden        = isset($_GET['orden']) ? (string)$_GET['orden'] : 'recientes'; // recientes|comentados|aprobados|observados
$q            = isset($_GET['q']) ? trim((string)$_GET['q']) : '';
$tag          = isset($_GET['tag']) ? trim((string)$_GET['tag']) : '';
$pagina       = isset($_GET['pagina']) ? max(1, (int)$_GET['pagina']) : 1;
$por_pagina   = 10;

/* Helper de links con filtros */
function link_con_filtros($p, $f){
  $qs = [
    'pagina'      => (int)$p,
    'facultad'    => (int)$f['facultad'],
    'departamento'=> (int)$f['departamento'],
    'periodo'     => (int)$f['periodo'],
    'oficina'     => (string)$f['oficina'],
    'orden'       => (string)$f['orden'],
    'q'           => (string)$f['q'],
    'tag'         => (string)$f['tag'],
  ];
  return '?' . http_build_query($qs);
}

/* ============================================================
   CATÃLOGO CENTRAL DE IMÃGENES â€” EDITA AQUÃ TUS URLs
   - Clave => URL pÃºblica directa a la imagen (JPEG/PNG/WebP).
   - Debajo de cada clave hay una guÃ­a de quÃ© buscar.
   - Si dejas una URL vacÃ­a, usaremos un fallback de Unsplash.
   ============================================================ */
$IMG = [
  // Comunicados RSU (cierre de propuestas)
  'comunicado_deadline_1'  => 'https://img.freepik.com/vector-premium/icono-calendario-reloj-calendario-pared-importante-horario-fecha-cita-ilustracion-stock-vectorial_100456-2345.jpg',    // Calendario con fecha marcada / reloj
  'comunicado_university'  => 'https://encrypted-tbn0.gstatic.com/images?q=tbn:ANd9GcSzE69u8__tTDyCJBYp_wxyVJN1MWyvw9tiwg&s', // Fachada/universidad con anuncio

  // Huertos escolares (ENF-014-2025)
  'huertos_escolares_bancales' => 'https://jardinessinfronteras.com/wp-content/uploads/2018/06/huertos-escolares.jpg?w=616&h=462',      // Camas de cultivo en escuela
  'huertos_capacitacion'       => 'https://estag.fimagenes.com/imagenesred/8548857.jpg', // Docente con alumnos en huerto

  // Salud preventiva (MED-032-2025)
  'salud_tamizaje'          => 'https://muniporvenir.gob.pe/wp-content/uploads/2021/04/CAMPANA-DE-PREVENCION-19-04-2021-1140x641.jpg',        // Toma de presiÃ³n/glucosa en campaÃ±a
  'salud_enfermera'         => 'https://files.adventistas.org/noticias/es/2023/10/02193041/0E8A7765.jpg',          // Enfermera atendiendo paciente

  // Lectura inicial (EDU-011-2025)
  'lectura_infantil'        => 'https://imagenes.elpais.com/resizer/v2/5ZPQNY4ROI52YCYFUCZLOHKD7I.jpg?auth=afd6552157db8fa528452042ccb60b5dc3a9f3c3872788dfa55dca3c10a092cb&width=414',      // NiÃ±os leyendo en aula
  'aula_primaria'           => 'https://disenoevaluma.wordpress.com/wp-content/uploads/2017/04/1367996797_1.jpg',   // Aula primaria con materiales

  // Limpieza de playa (AMB-027-2025)
  'playa_limpieza'          => 'https://source.unsplash.com/1200x675/?beach,cleanup',         // Voluntarios limpiando playa
  'voluntarios_bolsas'      => 'https://source.unsplash.com/1200x675/?volunteers,environment',// Personas con bolsas de basura

  // AlfabetizaciÃ³n digital (INF-020-2025)
  'capacitacion_computo'    => 'https://source.unsplash.com/1200x675/?computer,training',     // Adultos en sala de cÃ³mputo
  'alfabetizacion_tic'      => 'https://source.unsplash.com/1200x675/?digital,literacy',      // Taller TIC para adultos

  // CampaÃ±a de abrigo (SOC-044-2025)
  'donacion_ropa'           => 'https://source.unsplash.com/1200x675/?donation,clothes',      // Clasificando ropa/entrega
  'comunidad_ayuda'         => 'https://source.unsplash.com/1200x675/?community,help',        // Vecinos recibiendo ayuda

  // Ãreas verdes (AMB-029-2025)
  'parque_mantenimiento'    => 'https://source.unsplash.com/1200x675/?urban,park',            // Vecinos arreglando parque
  'siembra_plantones'       => 'https://source.unsplash.com/1200x675/?tree,planting',         // Siembra de Ã¡rboles

  // Reciclaje creativo (EDU-021-2025)
  'reciclaje_manualidades'  => 'https://source.unsplash.com/1200x675/?recycling,craft',       // Manualidades con reciclaje
  'upcycling_estudiantes'   => 'https://source.unsplash.com/1200x675/?upcycling,students',    // Estudiantes mostrando trabajos

  // Huertos familiares (AGR-008-2025)
  'huerto_familiar'         => 'https://source.unsplash.com/1200x675/?family,garden',         // Huertos en casa/familia
  'riego_goteo'             => 'https://source.unsplash.com/1200x675/?drip,irrigation',       // Detalle de riego por goteo

  // Recordatorio RSU (reporte quincenal)
  'recordatorio_megafono'   => 'https://source.unsplash.com/1200x675/?reminder,megaphone',    // MegÃ¡fono/recordatorio
  'recordatorio_aviso'      => 'https://source.unsplash.com/1200x675/?university,notice',     // Cartel/aviso en campus

  // Orquesta infantil (ART-002-2025)
  'ninos_orquesta'          => 'https://source.unsplash.com/1200x675/?kids,orchestra',        // NiÃ±os tocando instrumentos
  'musica_comunidad'        => 'https://source.unsplash.com/1200x675/?music,community',       // Ensayo/presentaciÃ³n comunitaria

  // Defaults del compositor
  'default_1'               => 'https://source.unsplash.com/1200x675/?community,project',
  'default_2'               => 'https://source.unsplash.com/1200x675/?education,volunteer',
];

/* Fallback: si una clave no tiene URL vÃ¡lida, usa una de Unsplash por tema */
function img($key, $fallbackQuery = 'social,project'){
  global $IMG;
  $u = isset($IMG[$key]) ? trim((string)$IMG[$key]) : '';
  if ($u !== '') return $u;
  return 'https://source.unsplash.com/1200x675/?' . rawurlencode($fallbackQuery);
}

/* ===================== DATOS DUMMY (consumen $IMG[...]) ===================== */
$posts = [
  [
    'id'=>1001, 'tipo'=>'rsu', 'id_py'=>null,
    'autor'=>'DirecciÃ³n RSU','rol'=>'Comunicado',
    'avatar'=>'https://ui-avatars.com/api/?name=RSU&background=0d6efd&color=fff&size=64',
    'titulo'=>'Cierre de presentaciÃ³n de proyectos',
    'codigo'=>null,
    'contenido'=>'ðŸ“¢ Recordatorio: cierre de presentaciÃ³n de proyectos el 25/04/2026. Verifica requisitos y cronograma antes de enviar.',
    'imagenes'=>[ img('comunicado_deadline_1','deadline,calendar'), img('comunicado_university','university,announcement') ],
    'tags'=>['#comunicado','#cronograma'],
    'facultad_id'=>0, 'departamento_id'=>0, 'periodo_id'=>1, 'oficina'=>'RSU',
    'likes'=>24,'coments'=>9,'ts'=>strtotime('2025-04-10 10:10:00'),'estado'=>null
  ],
  [
    'id'=>1002, 'tipo'=>'proyecto', 'id_py'=>101,
    'autor'=>'Eliana P. Sandoval','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=12',
    'titulo'=>'Cultivando huertos escolares â€“ Progreso ENF-014-2025',
    'codigo'=>'ENF-014-2025',
    'contenido'=>'Instalamos 6 bancales y capacitamos a 40 estudiantes en tÃ©cnicas bÃ¡sicas de riego y compost.',
    'imagenes'=>[ img('huertos_escolares_bancales','school,garden'), img('huertos_capacitacion','gardening,community') ],
    'tags'=>['#educaciÃ³n','#ambiente','#comunidad'],
    'facultad_id'=>8,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'PCF',
    'likes'=>37,'coments'=>12,'ts'=>strtotime('2025-04-09 18:20:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1003,'tipo'=>'proyecto','id_py'=>102,
    'autor'=>'Luis R. Medina','rol'=>'Coordinador',
    'avatar'=>'https://i.pravatar.cc/64?img=33',
    'titulo'=>'CampaÃ±a de salud preventiva â€“ Barrio Los Laureles',
    'codigo'=>'MED-032-2025',
    'contenido'=>'Tamizaje de presiÃ³n arterial y glucosa a 120 vecinos, derivaciones coordinadas con el centro de salud.',
    'imagenes'=>[ img('salud_tamizaje','health,checkup'), img('salud_enfermera','nurse,clinic') ],
    'tags'=>['#salud','#comunidad'],
    'facultad_id'=>3,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'DD',
    'likes'=>52,'coments'=>18,'ts'=>strtotime('2025-04-08 16:00:00'),'estado'=>'aprobado'
  ],
  [
    'id'=>1004,'tipo'=>'proyecto','id_py'=>103,
    'autor'=>'Karla V. Torres','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=22',
    'titulo'=>'Taller de lectura inicial â€“ I.E. Santa Rosa',
    'codigo'=>'EDU-011-2025',
    'contenido'=>'Se trabajÃ³ conciencia fonolÃ³gica con 2Âº grado. 5 docentes replicarÃ¡n la metodologÃ­a en sus aulas.',
    'imagenes'=>[ img('lectura_infantil','reading,children'), img('aula_primaria','classroom,education') ],
    'tags'=>['#educaciÃ³n','#comunidad'],
    'facultad_id'=>6,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'PCF',
    'likes'=>20,'coments'=>7,'ts'=>strtotime('2025-04-07 11:10:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1005,'tipo'=>'proyecto','id_py'=>104,
    'autor'=>'Jorge E. Salazar','rol'=>'Coordinador',
    'avatar'=>'https://i.pravatar.cc/64?img=41',
    'titulo'=>'Jornada de limpieza de playa â€“ Huanchaco',
    'codigo'=>'AMB-027-2025',
    'contenido'=>'Recolectamos 450 kg de residuos con voluntarios y pescadores. Se entregÃ³ reporte a la municipalidad.',
    'imagenes'=>[ img('playa_limpieza','beach,cleanup'), img('voluntarios_bolsas','volunteers,environment') ],
    'tags'=>['#ambiente','#comunidad'],
    'facultad_id'=>5,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'DF',
    'likes'=>73,'coments'=>21,'ts'=>strtotime('2025-04-06 09:00:00'),'estado'=>'observado'
  ],
  [
    'id'=>1006,'tipo'=>'proyecto','id_py'=>105,
    'autor'=>'MarÃ­a L. Campos','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=47',
    'titulo'=>'AlfabetizaciÃ³n digital para adultos â€“ Centro Poblado El Milagro',
    'codigo'=>'INF-020-2025',
    'contenido'=>'20 participantes crearon su correo y aprendieron videollamadas. PrÃ³ximo mÃ³dulo: seguridad digital.',
    'imagenes'=>[ img('capacitacion_computo','computer,training'), img('alfabetizacion_tic','digital,literacy') ],
    'tags'=>['#educaciÃ³n','#tecnologÃ­a','#comunidad'],
    'facultad_id'=>7,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'RSU',
    'likes'=>29,'coments'=>11,'ts'=>strtotime('2025-04-05 19:30:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1007,'tipo'=>'proyecto','id_py'=>106,
    'autor'=>'Sonia P. Herrera','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=10',
    'titulo'=>'CampaÃ±a de abrigo â€“ Barrio Manco CÃ¡pac',
    'codigo'=>'SOC-044-2025',
    'contenido'=>'Se entregaron 180 kits de abrigo y se levantÃ³ padrÃ³n para seguimiento de casos vulnerables.',
    'imagenes'=>[ img('donacion_ropa','donation,clothes'), img('comunidad_ayuda','community,help') ],
    'tags'=>['#comunidad','#inclusiÃ³n'],
    'facultad_id'=>2,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'PCF',
    'likes'=>34,'coments'=>8,'ts'=>strtotime('2025-04-04 17:45:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1008,'tipo'=>'proyecto','id_py'=>107,
    'autor'=>'Carlos A. NÃºÃ±ez','rol'=>'Coordinador',
    'avatar'=>'https://i.pravatar.cc/64?img=55',
    'titulo'=>'RecuperaciÃ³n de Ã¡reas verdes â€“ Urb. Palermo',
    'codigo'=>'AMB-029-2025',
    'contenido'=>'Se podÃ³, pintÃ³ y sembraron 80 plantones. Junta vecinal se comprometiÃ³ con el mantenimiento.',
    'imagenes'=>[ img('parque_mantenimiento','urban,park'), img('siembra_plantones','tree,planting') ],
    'tags'=>['#ambiente','#comunidad'],
    'facultad_id'=>8,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'DD',
    'likes'=>18,'coments'=>5,'ts'=>strtotime('2025-04-03 15:00:00'),'estado'=>'aprobado'
  ],
  [
    'id'=>1009,'tipo'=>'proyecto','id_py'=>108,
    'autor'=>'Gabriela C. Rojas','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=5',
    'titulo'=>'Taller de reciclaje creativo â€“ I.E. VÃ­ctor RaÃºl',
    'codigo'=>'EDU-021-2025',
    'contenido'=>'Estudiantes elaboraron macetas y Ãºtiles con materiales reutilizados. ExposiciÃ³n programada.',
    'imagenes'=>[ img('reciclaje_manualidades','recycling,craft'), img('upcycling_estudiantes','upcycling,students') ],
    'tags'=>['#educaciÃ³n','#ambiente'],
    'facultad_id'=>6,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'PCF',
    'likes'=>25,'coments'=>9,'ts'=>strtotime('2025-04-02 10:00:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1010,'tipo'=>'proyecto','id_py'=>109,
    'autor'=>'Pedro M. Yupanqui','rol'=>'Coordinador',
    'avatar'=>'https://i.pravatar.cc/64?img=67',
    'titulo'=>'Huertos familiares â€“ Centro Poblado El TrÃ³pico',
    'codigo'=>'AGR-008-2025',
    'contenido'=>'Se instalaron 15 huertos con riego por goteo artesanal y capacitaciÃ³n bÃ¡sica.',
    'imagenes'=>[ img('huerto_familiar','family,garden'), img('riego_goteo','drip,irrigation') ],
    'tags'=>['#ambiente','#comunidad'],
    'facultad_id'=>4,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'DF',
    'likes'=>44,'coments'=>13,'ts'=>strtotime('2025-04-01 08:20:00'),'estado'=>'en_espera'
  ],
  [
    'id'=>1011,'tipo'=>'rsu','id_py'=>null,
    'autor'=>'DirecciÃ³n RSU','rol'=>'Comunicado',
    'avatar'=>'https://ui-avatars.com/api/?name=RSU&background=0d6efd&color=fff&size=64',
    'titulo'=>'Recordatorio de reporte quincenal',
    'codigo'=>null,
    'contenido'=>'Recuerden actualizar avances y evidencias (fotos, enlaces) cada 15 dÃ­as segÃºn la guÃ­a RSU.',
    'imagenes'=>[ img('recordatorio_megafono','reminder,megaphone'), img('recordatorio_aviso','university,notice') ],
    'tags'=>['#comunicado'],
    'facultad_id'=>0,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'RSU',
    'likes'=>11,'coments'=>4,'ts'=>strtotime('2025-03-31 09:30:00'),'estado'=>null
  ],
  [
    'id'=>1012,'tipo'=>'proyecto','id_py'=>110,
    'autor'=>'Elena D. Paredes','rol'=>'Coordinadora',
    'avatar'=>'https://i.pravatar.cc/64?img=3',
    'titulo'=>'Orquesta infantil â€“ Barrio Chicago',
    'codigo'=>'ART-002-2025',
    'contenido'=>'Se formÃ³ el ensamble inicial con 25 niÃ±as y niÃ±os. PresentaciÃ³n piloto en 4 semanas.',
    'imagenes'=>[ img('ninos_orquesta','kids,orchestra'), img('musica_comunidad','music,community') ],
    'tags'=>['#cultura','#comunidad'],
    'facultad_id'=>9,'departamento_id'=>0,'periodo_id'=>1,'oficina'=>'PCF',
    'likes'=>41,'coments'=>16,'ts'=>strtotime('2025-03-30 18:00:00'),'estado'=>'en_espera'
  ],
];

/* ===================== FILTRADO / ORDEN / PAGINACIÃ“N ===================== */
$filtrados = array_values(array_filter($posts, function($p) use ($facultad,$departamento,$periodo,$oficina,$q,$tag){
  if ($facultad>0 && (int)$p['facultad_id'] !== $facultad) return false;
  if ($departamento>0 && (int)$p['departamento_id'] !== $departamento) return false;
  if ($periodo>0 && (int)$p['periodo_id'] !== $periodo) return false;

  if ($oficina !== '') {
    if ($oficina === 'APROB' && ($p['estado']??'') !== 'aprobado') return false;
    elseif ($oficina === 'SIN' && !empty($p['oficina'])) return false;
    elseif (in_array($oficina, ['PCF','DD','DF','RSU'], true) && strtoupper($p['oficina']) !== $oficina) return false;
  }

  if ($q !== '') {
    $needle = mb_strtolower($q,'UTF-8');
    $hay = mb_strtolower(($p['titulo'].' '.$p['contenido'].' '.($p['codigo']?:'').' '.implode(' ',$p['tags'])), 'UTF-8');
    if (mb_strpos($hay,$needle) === false) return false;
  }

  if ($tag !== '') {
    if (!in_array($tag, $p['tags'], true)) return false;
  }
  return true;
}));

if ($orden === 'comentados')       usort($filtrados, fn($a,$b)=>$b['coments']<=>$a['coments']);
elseif ($orden === 'aprobados')    usort($filtrados, fn($a,$b)=>strcmp(($b['estado']??''),'aprobado')<=>strcmp(($a['estado']??''),'aprobado'));
elseif ($orden === 'observados')   usort($filtrados, fn($a,$b)=>strcmp(($b['estado']??''),'observado')<=>strcmp(($a['estado']??''),'observado'));
else                               usort($filtrados, fn($a,$b)=>$b['ts']<=>$a['ts']);

$total_items = count($filtrados);
$total_pages = max(1, (int)ceil($total_items / $por_pagina));
$offset      = ($pagina-1)*$por_pagina;
$items       = array_slice($filtrados, $offset, $por_pagina);
$desde       = ($total_items>0)? ($offset+1) : 0;
$hasta       = ($total_items>0)? ($offset+count($items)) : 0;

/* KPIs demo */
$kpi_proyectos_actuales   = 126;
$kpi_con_publicaciones    = 11;
$kpi_actualizados_semana  = count(array_filter($posts, fn($p)=> $p['ts'] >= (time()-7*86400)));
$kpi_aprob_total_recientes= 2;

/* ========= Datos para barra lateral derecha ========= */

/* Tendencias: top etiquetas en todos los posts (no solo filtrados) */
$__tagCount = [];
foreach ($posts as $pp) {
  foreach ($pp['tags'] as $tg) {
    $__tagCount[$tg] = ($__tagCount[$tg] ?? 0) + 1;
  }
}
arsort($__tagCount);
$trend_tags = array_slice(array_keys($__tagCount), 0, 8);

/* Proyectos sugeridos: proyectos recientes (toma 4) */
$__sugg = array_values(array_filter($posts, fn($p)=>$p['tipo']==='proyecto'));
usort($__sugg, fn($a,$b)=>$b['ts']<=>$a['ts']);
$suggested_projects = array_slice($__sugg, 0, 4);

/* Colaboradores destacados: por #posts y likes totales */
$__auth = [];
foreach ($posts as $pp) {
  $a = $pp['autor'];
  if (!isset($__auth[$a])) {
    $__auth[$a] = ['posts'=>0,'likes'=>0,'avatar'=>$pp['avatar'],'last_ts'=>$pp['ts']];
  }
  $__auth[$a]['posts']++;
  $__auth[$a]['likes'] += (int)$pp['likes'];
  if ($pp['ts'] > $__auth[$a]['last_ts']) { $__auth[$a]['last_ts'] = $pp['ts']; $__auth[$a]['avatar']=$pp['avatar']; }
}
uasort($__auth, function($x,$y){
  if ($x['posts'] === $y['posts']) return $y['likes'] <=> $x['likes'];
  return $y['posts'] <=> $x['posts'];
});
$top_authors = array_slice($__auth, 0, 5, true);

/* Resumen por oficina (sobre el conjunto filtrado que estÃ¡s viendo) */
$office_dist = ['PCF'=>0,'DD'=>0,'DF'=>0,'RSU'=>0,'APROB'=>0,'SIN'=>0];
foreach ($filtrados as $pf) {
  if (($pf['estado'] ?? '') === 'aprobado') { $office_dist['APROB']++; continue; }
  $of = strtoupper((string)($pf['oficina'] ?? ''));
  if (isset($office_dist[$of])) $office_dist[$of]++; else $office_dist['SIN']++;
}

?>
<style>
:root{
  --night-900:#0a1633;
  --night-800:#0f244d;
  --night-700:#173568;
  --accent:#0d6efd;
  --soft:#eff3fa;
}
.rsu-social .kpi-card{
  background: linear-gradient(135deg,var(--night-800),var(--night-700));
  color:#fff; border:none; border-radius:16px; box-shadow:0 6px 22px rgba(10,22,51,.25);
}
.rsu-social .kpi-card .num{ font-weight:800; font-size:1.8rem; line-height:1; }
.rsu-social .kpi-card .lbl{ opacity:.9; font-size:.92rem; }

.rsu-social .composer-card, .rsu-social .filters-card{
  background:#fff; border:1px solid #e7ecf5; border-radius:14px; box-shadow:0 6px 20px rgba(15,36,77,.06);
}
.rsu-social .composer-title{
  font-weight:700; color:var(--night-800); font-size:1.02rem; display:flex; align-items:center; gap:.5rem;
}
.rsu-social .chip-tag{
  display:inline-block; padding:.25rem .55rem; border-radius:999px; background:#e8eefb; color:#1c3f7a; font-size:.78rem;
  margin-right:.35rem; cursor:pointer; user-select:none;
}
.rsu-social .chip-tag:hover{ background:#dce6fb; }

.rsu-social .feed-card{
  background:#fff; border:1px solid #e7ecf5; border-radius:14px; margin-bottom:12px; overflow:hidden;
  box-shadow:0 6px 20px rgba(15,36,77,.06);
}
.rsu-social .feed-hd{ display:flex; align-items:center; gap:.75rem; padding:12px 14px; background:linear-gradient(180deg,#fff,#f7f9fe); }
.rsu-social .avatar{ width:40px; height:40px; border-radius:50%; object-fit:cover; border:2px solid var(--soft); }
.rsu-social .feed-meta{ color:#6b7a99; font-size:.82rem; }
.rsu-social .feed-title{ margin:0; font-weight:700; color:#0f244d; font-size:1rem; }
.rsu-social .feed-body{ padding:0 14px 12px; }
/* ImÃ¡genes del post: 500px de alto + recorte moderno */
.rsu-social .feed-body img{
  width:100%;
  height:200px;
  object-fit:cover;
  border-radius:12px;
  display:block;
}

/* Layout a 3 columnas: izquierda (compositor/filtros), centro (feed), derecha (sidebar) */
.rsu-social .layout{
  display:grid;
  grid-template-columns: 360px 1fr 320px;
  gap:14px;
  align-items:start;
}
@media (max-width: 1200px){
  .rsu-social .layout{ grid-template-columns: 320px 1fr; }
}
@media (max-width: 992px){
  .rsu-social .layout{ grid-template-columns: 1fr; }
}

/* Nueva columna central (antes .right) para alojar el feed scrolleable */
.rsu-social .center{
  display:flex;
  flex-direction:column;
  min-height:0; /* clave para que el overflow interno funcione */
}

/* Tarjetas de la barra lateral con diseÃ±o compacto y colores */
.rsu-social .aside-card{
  border:0;
  border-radius:14px;
  overflow:hidden;
  box-shadow:0 10px 24px rgba(15,36,77,.10);
  margin-bottom:12px;
  background:#ffffff;
}
.rsu-social .aside-hd{
  display:flex; align-items:center; gap:.5rem;
  padding:10px 12px; color:#fff; font-weight:700; font-size:.95rem;
}
.rsu-social .aside-bd{ padding:10px 12px; }

/* Gradientes de cabecera (coloridas) */
.rsu-social .grad-blue    { background:linear-gradient(135deg,#1b3b8b,#0d6efd); }
.rsu-social .grad-purple  { background:linear-gradient(135deg,#4b2c82,#8a5cf6); }
.rsu-social .grad-green   { background:linear-gradient(135deg,#136a3a,#20c997); }
.rsu-social .grad-orange  { background:linear-gradient(135deg,#a24b1a,#ff7f32); }
.rsu-social .grad-pink    { background:linear-gradient(135deg,#8d225a,#ff4fa6); }
.rsu-social .grad-indigo  { background:linear-gradient(135deg,#0f244d,#173568); }

/* Lista compacta (sugeridos y autores) */
.rsu-social .mini-list{ list-style:none; margin:0; padding:0; }
.rsu-social .mini-list li{
  display:flex; align-items:center; gap:10px;
  padding:8px 0; border-bottom:1px dashed #e6ebf7;
}
.rsu-social .mini-list li:last-child{ border-bottom:none; }
.rsu-social .mini-thumb{ width:44px; height:44px; border-radius:10px; object-fit:cover; border:1px solid #eef2fb; }
.rsu-social .mini-meta{ font-size:.78rem; color:#6b7a99; }

/* Chips reutilizables (tendencias) en aside */
.rsu-social .aside-tags .chip-tag{ margin:0 6px 6px 0; }

/* Pills de resumen oficina (compactas y legibles) */
.rsu-social .pill{
  display:inline-flex; align-items:center; gap:6px;
  background:linear-gradient(180deg,#f1f5ff,#e8eefb);
  color:#1f2e4d; border-radius:999px; padding:.2rem .55rem; font-size:.76rem; margin:0 6px 6px 0;
}
.rsu-social .pill i{ opacity:.8; }

/* Scroll de feed (ya existente) â€“ lo mantenemos */
.rsu-social .feed-scroll{
  overflow-y:auto;
  padding-right:6px;
}

/* Scrollbar visual */
.rsu-social .feed-scroll::-webkit-scrollbar{ width:10px; }
.rsu-social .feed-scroll::-webkit-scrollbar-thumb{ background:#d6deef; border-radius:8px; }
.rsu-social .feed-scroll::-webkit-scrollbar-track{ background:#f3f6fd; }

/* Aside sticky para aprovechar la altura y ver muchos mÃ³dulos */
.rsu-social .aside{
  position:sticky; top:8px; align-self:start;
  max-height: calc(100vh - 100px);
  overflow:auto;
  padding-right:4px;
}
.rsu-social .aside::-webkit-scrollbar{ width:8px; }
.rsu-social .aside::-webkit-scrollbar-thumb{ background:#d6deef; border-radius:8px; }
.rsu-social .aside::-webkit-scrollbar-track{ background:transparent; }

/* Contenedor scrolleable del feed (altura se ajusta por JS) */
.rsu-social .feed-scroll{
  overflow-y:auto;
  padding-right:6px; /* respiraciÃ³n al borde del scroll */
}

/* Opcional: suaviza la barra en navegadores con scrollbar visible */
.rsu-social .feed-scroll::-webkit-scrollbar{ width:10px; }
.rsu-social .feed-scroll::-webkit-scrollbar-thumb{ background:#d6deef; border-radius:8px; }
.rsu-social .feed-scroll::-webkit-scrollbar-track{ background:#f3f6fd; }

.rsu-social .img-row{ display:grid; grid-template-columns:1fr 1fr; gap:10px; margin:8px 0 6px; }
.rsu-social .img-row .mb-2{ margin:0 !important; }
.rsu-social .feed-actions{ display:flex; align-items:center; gap:8px; border-top:1px solid #eef2f9; padding:10px 12px; }
.rsu-social .feed-actions .btn{ border-radius:10px; }
.rsu-social .code-badge{ background:#0d6efd; color:#fff; border-radius:8px; padding:.1rem .4rem; font-size:.72rem; }

.rsu-social .btn-navy{ background:var(--night-700); color:#fff; border:none; }
.rsu-social .btn-navy:hover{ background:#1a4487; color:#fff; }
.rsu-social .btn-outline-navy{ border:1px solid var(--night-700); color:var(--night-700); }
.rsu-social .btn-outline-navy:hover{ background:var(--night-700); color:#fff; }

.rsu-social .section-title{ color:#0f244d; font-weight:800; font-size:1.05rem; }
.rsu-social .subtle{ color:#6b7a99; }


.rsu-social .small-muted{ font-size:.82rem; color:#6b7a99; }

/* Modal Ver Informe */
#modalInforme .modal-dialog { max-width: 1140px; }
#modalInforme .modal-dialog.modal-dialog-scrollable { height: 90vh; }
#modalInforme .modal-content { height: 100%; }
#modalInforme .modal-body { padding:0; overflow:hidden !important; }
#contenidoInforme { height: 78vh; overflow:hidden; }
</style>
<div class="rsu-social">
  <div class="d-flex justify-content-between align-items-center mb-2">
    <div class="section-title">
      <i class="fas fa-hashtag"></i> Red Social de Responsabilidad Social
      <span class="subtle">| Rol: <?= htmlspecialchars($usr['rol']) ?> &nbsp; Usuario: <?= htmlspecialchars($usr['usuario']) ?></span>
    </div>
  </div>

  <!-- KPIs -->
  <div class="row g-2 mb-2">
    <div class="col-12 col-md-3">
      <div class="p-3 kpi-card h-100 d-flex align-items-center justify-content-between">
        <div><div class="num"><?= (int)$kpi_proyectos_actuales ?></div><div class="lbl">Proyectos actuales</div></div>
        <i class="fas fa-diagram-project fa-lg"></i>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="p-3 kpi-card h-100 d-flex align-items-center justify-content-between">
        <div><div class="num"><?= (int)$kpi_con_publicaciones ?></div><div class="lbl">Proyectos con publicaciones</div></div>
        <i class="fas fa-bullhorn fa-lg"></i>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="p-3 kpi-card h-100 d-flex align-items-center justify-content-between">
        <div><div class="num"><?= (int)$kpi_actualizados_semana ?></div><div class="lbl">Actualizados esta semana</div></div>
        <i class="fas fa-calendar-week fa-lg"></i>
      </div>
    </div>
    <div class="col-12 col-md-3">
      <div class="p-3 kpi-card h-100 d-flex align-items-center justify-content-between">
        <div><div class="num"><?= (int)$kpi_aprob_total_recientes ?></div><div class="lbl">AprobaciÃ³n Total recientes</div></div>
        <i class="fas fa-check-circle fa-lg"></i>
      </div>
    </div>
  </div>

  <div class="layout">
    <!-- Izquierda: compositor + filtros -->
    <div class="left">
      <div class="composer-card p-3 mb-2">
        <div class="composer-title mb-2"><i class="fas fa-pen-to-square text-primary"></i> Â¿QuÃ© avance quieres compartir? <span class="small-muted">(Demo, no guarda en BD)</span></div>
        <div class="row g-2">
          <div class="col-12 col-sm-5">
            <label class="small-muted mb-1">Tipo</label>
            <select id="cmp_tipo" class="form-control form-control-sm">
              <option value="proyecto" selected>Proyecto</option>
              <option value="rsu">Comunicado RSU</option>
            </select>
          </div>
          <div class="col-12 col-sm-7">
            <label class="small-muted mb-1">CÃ³digo proyecto</label>
            <input type="text" id="cmp_codigo" class="form-control form-control-sm" placeholder="ENF-014-2025">
          </div>
          <div class="col-12">
            <label class="small-muted mb-1">TÃ­tulo</label>
            <input type="text" id="cmp_titulo" class="form-control form-control-sm" placeholder="TÃ­tulo del proyecto o anuncio">
          </div>
          <div class="col-12">
            <label class="small-muted mb-1">Contenido</label>
            <textarea id="cmp_cont" class="form-control form-control-sm" rows="3" placeholder="Describe el avanceâ€¦"></textarea>
          </div>
          <div class="col-12 col-sm-6">
            <label class="small-muted mb-1">Imagen 1 (URL)</label>
            <input type="text" id="cmp_img1" class="form-control form-control-sm" value="<?= htmlspecialchars($IMG['default_1']) ?>">
          </div>
          <div class="col-12 col-sm-6">
            <label class="small-muted mb-1">Imagen 2 (URL)</label>
            <input type="text" id="cmp_img2" class="form-control form-control-sm" value="<?= htmlspecialchars($IMG['default_2']) ?>">
          </div>
          <div class="col-12 d-flex justify-content-end">
            <button id="btnPublicar" class="btn btn-sm btn-navy mt-2"><i class="fas fa-paper-plane"></i> Publicar</button>
          </div>
        </div>
      </div>

      <div class="filters-card p-3 mb-2">
        <div class="composer-title mb-2"><i class="fas fa-sliders"></i> Filtros</div>
        <form id="frmFiltros" method="get" class="mb-0">
          <input type="hidden" name="pagina" value="1">
          <div class="row g-2">
            <div class="col-12">
              <label class="small-muted mb-1">Facultad</label>
              <select name="facultad" class="form-control form-control-sm">
                <option value="0" <?= $facultad===0?'selected':'' ?>>Todas</option>
                <?php foreach ($facultades as $id=>$nom): if ((int)$id===0) continue; ?>
                  <option value="<?= (int)$id ?>" <?= $facultad===(int)$id?'selected':'' ?>><?= htmlspecialchars($nom) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="small-muted mb-1">Departamento</label>
              <select name="departamento" class="form-control form-control-sm" <?= ($fac_base<=0 && $facultad===0)?'disabled':''; ?>>
                <?php if ($fac_base<=0 && $facultad===0): ?>
                  <option value="0" selected>Sin Departamento AcadÃ©mico</option>
                <?php else: ?>
                  <option value="0" <?= $departamento===0?'selected':'' ?>>Todos</option>
                  <?php foreach (obtenerDepartamentos($facultad?:$fac_base?:0) as $id=>$nom): ?>
                    <option value="<?= (int)$id ?>" <?= $departamento===(int)$id?'selected':'' ?>><?= htmlspecialchars($nom) ?></option>
                  <?php endforeach; ?>
                <?php endif; ?>
              </select>
            </div>
            <div class="col-6">
              <label class="small-muted mb-1">Estado / Oficina</label>
              <select name="oficina" class="form-control form-control-sm">
                <option value=""    <?= ($oficina==='')?'selected':''; ?>>Todos</option>
                <option value="PCF" <?= ($oficina==='PCF')?'selected':''; ?>>ComitÃ© de Facultad</option>
                <option value="DD"  <?= ($oficina==='DD')?'selected':''; ?>>DirecciÃ³n de Departamento</option>
                <option value="DF"  <?= ($oficina==='DF')?'selected':''; ?>>Decanato de Facultad</option>
                <option value="RSU" <?= ($oficina==='RSU')?'selected':''; ?>>DirecciÃ³n RSU</option>
                <option value="APROB" <?= ($oficina==='APROB')?'selected':''; ?>>AprobaciÃ³n Total</option>
                <option value="SIN" <?= ($oficina==='SIN')?'selected':''; ?>>sin Estado / Oficina</option>
              </select>
            </div>
            <div class="col-6">
              <label class="small-muted mb-1">PerÃ­odo</label>
              <select name="periodo" class="form-control form-control-sm">
                <option value="0" <?= $periodo===0?'selected':''; ?>>Todos</option>
                <?php foreach ($periodos as $id=>$nom): ?>
                  <option value="<?= (int)$id ?>" <?= $periodo===(int)$id?'selected':''; ?>><?= htmlspecialchars($nom) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-12">
              <label class="small-muted mb-1">Buscar</label>
              <div class="d-flex">
                <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" class="form-control form-control-sm" placeholder="Proyecto, coordinador, cÃ³digo, texto">
                <button class="btn btn-sm btn-outline-navy ml-2" type="submit"><i class="fas fa-search"></i></button>
                <a class="btn btn-sm btn-outline-secondary ml-2" title="Limpiar"
                   href="<?= htmlspecialchars(link_con_filtros(1, ['facultad'=>0,'departamento'=>0,'periodo'=>0,'oficina'=>'','orden'=>'recientes','q'=>'','tag'=>''])) ?>">
                  <i class="fas fa-broom"></i>
                </a>
              </div>
            </div>
            <div class="col-12">
              <label class="small-muted mb-1">Ordenar por</label>
              <select name="orden" class="form-control form-control-sm">
                <option value="recientes"  <?= $orden==='recientes'?'selected':''; ?>>MÃ¡s recientes</option>
                <option value="comentados" <?= $orden==='comentados'?'selected':''; ?>>MÃ¡s comentados</option>
                <option value="aprobados"  <?= $orden==='aprobados'?'selected':''; ?>>Con aprobaciÃ³n total</option>
                <option value="observados" <?= $orden==='observados'?'selected':''; ?>>Observados</option>
              </select>
            </div>
          </div>
        </form>
        <div class="mt-2">
          <span class="chip-tag" data-tag="#salud">#salud</span>
          <span class="chip-tag" data-tag="#educaciÃ³n">#educaciÃ³n</span>
          <span class="chip-tag" data-tag="#ambiente">#ambiente</span>
          <span class="chip-tag" data-tag="#comunidad">#comunidad</span>
          <span class="chip-tag" data-tag="#comunicado">#comunicado</span>
        </div>
      </div>
    </div>

        <!-- Centro: feed (scrolleable) -->
    <div class="center">
      <div id="feedScroll" class="feed-scroll">
        <?php if (empty($items)): ?>
          <div class="feed-card p-4 text-center"><div class="subtle"><i class="far fa-face-meh"></i> No hay publicaciones con los filtros seleccionados.</div></div>
        <?php else: ?>
          <?php foreach ($items as $p): ?>
            <article class="feed-card">
              <header class="feed-hd">
                <img class="avatar" src="<?= htmlspecialchars($p['avatar']) ?>" alt="avatar">
                <div class="w-100">
                  <div class="d-flex justify-content-between">
                    <h5 class="feed-title"><?= htmlspecialchars($p['autor']) ?> <small class="feed-meta">Â· <?= htmlspecialchars($p['rol']) ?></small></h5>
                    <div class="feed-meta"><?= date('d/m/Y H:i', $p['ts']) ?></div>
                  </div>
                  <div class="feed-meta">
                    <?php if (!empty($p['codigo'])): ?><span class="code-badge"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($p['codigo']) ?></span><?php endif; ?>
                    <?php if (!empty($p['oficina'])): ?><span class="ml-1"><i class="fas fa-building"></i> <?= htmlspecialchars($p['oficina']) ?></span><?php endif; ?>
                  </div>
                </div>
              </header>
              <div class="feed-body">
                <div class="font-weight-bold mb-1"><?= htmlspecialchars($p['titulo']) ?></div>
                <div class="mb-2"><?= htmlspecialchars($p['contenido']) ?></div>

                <?php if (!empty($p['imagenes'])): ?>
                  <div class="img-row">
                    <?php foreach ($p['imagenes'] as $img): ?>
                      <div class="mb-2"><img src="<?= htmlspecialchars($img) ?>" alt="imagen"></div>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>

                <div class="mb-2">
                  <?php foreach ($p['tags'] as $tg): ?>
                    <span class="chip-tag" data-tag="<?= htmlspecialchars($tg) ?>"><?= htmlspecialchars($tg) ?></span>
                  <?php endforeach; ?>
                </div>
              </div>
              <div class="feed-actions">
                <button class="btn btn-sm btn-outline-navy"><i class="far fa-thumbs-up"></i> Me gusta (<?= (int)$p['likes'] ?>)</button>
                <button class="btn btn-sm btn-outline-navy"><i class="far fa-comment"></i> Comentarios (<?= (int)$p['coments'] ?>)</button>
                <?php if ($p['tipo']==='proyecto' && !empty($p['id_py'])): ?>
                  <button class="btn btn-sm btn-outline-secondary ml-auto btn-ver-informe" data-id_py="<?= (int)$p['id_py'] ?>">
                    <i class="fas fa-external-link-alt"></i> Ver informe
                  </button>
                <?php endif; ?>
              </div>
            </article>
          <?php endforeach; ?>
        <?php endif; ?>
      </div>
    </div>

    <!-- Derecha: barra lateral colorida -->
    <aside class="aside">
      <!-- Tendencias -->
      <div class="aside-card">
        <div class="aside-hd grad-blue"><i class="fas fa-hashtag"></i> Tendencias</div>
        <div class="aside-bd aside-tags">
          <?php if (!empty($trend_tags)): ?>
            <?php foreach ($trend_tags as $tg): ?>
              <span class="chip-tag" data-tag="<?= htmlspecialchars($tg) ?>"><?= htmlspecialchars($tg) ?></span>
            <?php endforeach; ?>
          <?php else: ?>
            <span class="small-muted">Sin etiquetas por ahora.</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Proyectos sugeridos -->
      <div class="aside-card">
        <div class="aside-hd grad-orange"><i class="fas fa-lightbulb"></i> Proyectos sugeridos</div>
        <div class="aside-bd">
          <?php if (!empty($suggested_projects)): ?>
            <ul class="mini-list">
              <?php foreach ($suggested_projects as $sp):
                $thumb = $sp['imagenes'][0] ?? ($IMG['default_1'] ?? 'https://source.unsplash.com/1200x675/?project');
              ?>
                <li>
                  <img class="mini-thumb" src="<?= htmlspecialchars($thumb) ?>" alt="thumb">
                  <div>
                    <div class="font-weight-bold" style="line-height:1.15"><?= htmlspecialchars($sp['titulo']) ?></div>
                    <div class="mini-meta">
                      <?php if (!empty($sp['codigo'])): ?><span class="code-badge"><i class="fas fa-hashtag"></i> <?= htmlspecialchars($sp['codigo']) ?></span><?php endif; ?>
                      <span class="ml-1"><i class="far fa-clock"></i> <?= date('d/m/Y', $sp['ts']) ?></span>
                    </div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <span class="small-muted">Sin sugerencias por ahora.</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Colaboradores destacados -->
      <div class="aside-card">
        <div class="aside-hd grad-green"><i class="fas fa-user-stars"></i> Colaboradores destacados</div>
        <div class="aside-bd">
          <?php if (!empty($top_authors)): ?>
            <ul class="mini-list">
              <?php foreach ($top_authors as $name => $st): ?>
                <li>
                  <img class="mini-thumb" src="<?= htmlspecialchars($st['avatar']) ?>" alt="avatar">
                  <div>
                    <div class="font-weight-bold" style="line-height:1.15"><?= htmlspecialchars($name) ?></div>
                    <div class="mini-meta"><i class="far fa-file-lines"></i> <?= (int)$st['posts'] ?> posts Â· <i class="far fa-thumbs-up ml-1"></i> <?= (int)$st['likes'] ?> likes</div>
                  </div>
                </li>
              <?php endforeach; ?>
            </ul>
          <?php else: ?>
            <span class="small-muted">AÃºn no hay actividad.</span>
          <?php endif; ?>
        </div>
      </div>

      <!-- Resumen por oficina -->
      <div class="aside-card">
        <div class="aside-hd grad-indigo"><i class="fas fa-building"></i> Resumen por oficina</div>
        <div class="aside-bd">
          <div class="d-flex flex-wrap">
            <span class="pill"><i class="fas fa-sitemap"></i> PCF: <?= (int)$office_dist['PCF'] ?></span>
            <span class="pill"><i class="fas fa-sitemap"></i> DD: <?= (int)$office_dist['DD'] ?></span>
            <span class="pill"><i class="fas fa-sitemap"></i> DF: <?= (int)$office_dist['DF'] ?></span>
            <span class="pill"><i class="fas fa-sitemap"></i> RSU: <?= (int)$office_dist['RSU'] ?></span>
            <span class="pill"><i class="fas fa-check-circle"></i> APROB: <?= (int)$office_dist['APROB'] ?></span>
            <span class="pill"><i class="fas fa-minus-circle"></i> SIN: <?= (int)$office_dist['SIN'] ?></span>
          </div>
          <div class="small-muted mt-1">*Sobre los resultados filtrados actuales.</div>
        </div>
      </div>

      <!-- Fechas clave -->
      <div class="aside-card">
        <div class="aside-hd grad-pink"><i class="fas fa-calendar-days"></i> Fechas clave</div>
        <div class="aside-bd">
          <ul class="mini-list">
            <li>
              <i class="far fa-calendar-check fa-lg text-danger"></i>
              <div>
                <div class="font-weight-bold" style="line-height:1.15">Cierre presentaciÃ³n de proyectos</div>
                <div class="mini-meta">25/04/2026 Â· 23:59</div>
              </div>
            </li>
            <li>
              <i class="far fa-bell fa-lg text-primary"></i>
              <div>
                <div class="font-weight-bold" style="line-height:1.15">Reporte quincenal</div>
                <div class="mini-meta">Cada 15 dÃ­as (demo)</div>
              </div>
            </li>
            <li>
              <i class="far fa-handshake fa-lg text-success"></i>
              <div>
                <div class="font-weight-bold" style="line-height:1.15">Taller de buenas prÃ¡cticas</div>
                <div class="mini-meta">20/05/2026 Â· 10:00</div>
              </div>
            </li>
          </ul>
        </div>
      </div>
    </aside>


  </div>
</div>

<!-- MODAL: VER INFORME -->
<div class="modal fade" id="modalInforme" tabindex="-1" role="dialog">
  <div class="modal-dialog modal-dialog-scrollable modal-xl" role="document">
    <div class="modal-content border-primary">
      <div class="modal-header bg-success text-white py-2">
        <h5 class="modal-title"><i class="fas fa-info-circle"></i> Informe del Proyecto</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body p-0">
        <div id="contenidoInforme" style="height:78vh; overflow:hidden;">
          <p class="text-center text-muted my-4">Cargando informe...</p>
        </div>
      </div>
    </div>
  </div>
</div>
<script>
/* ===== Altura dinÃ¡mica del contenedor scrolleable del feed =====
   Calcula la altura disponible desde la posiciÃ³n del feed hasta el final del viewport
   para que sÃ³lo scrollee el listado y no toda la pÃ¡gina. */
(function(){
  function setFeedHeight(){
    var el = document.getElementById('feedScroll');
    if (!el) return;
    var rect = el.getBoundingClientRect();
    var vh = window.innerHeight || document.documentElement.clientHeight || 0;
    var margenInferior = 16; // respiraciÃ³n
    var h = Math.max(180, Math.floor(vh - rect.top - margenInferior));
    el.style.maxHeight = h + 'px';
  }
  window.addEventListener('resize', setFeedHeight);
  window.addEventListener('orientationchange', setFeedHeight);
  document.addEventListener('DOMContentLoaded', setFeedHeight);
  // Por si el layout padre tarda en pintar: recalcular tras breve delay
  setTimeout(setFeedHeight, 200);
})();
</script>

<script>
/* ===== Chips de etiquetas -> filtran con ?tag= ===== */
document.querySelectorAll('.chip-tag[data-tag]').forEach(function(el){
  el.addEventListener('click', function(){
    const tg = this.getAttribute('data-tag') || '';
    const url = new URL(window.location.href);
    url.searchParams.set('tag', tg);
    url.searchParams.set('pagina', '1');
    window.location.href = url.toString();
  });
});

/* ===== Auto-submit al cambiar filtros ===== */
(function(){
  const form = document.getElementById('frmFiltros');
  if (!form) return;
  form.querySelectorAll('select').forEach(function(sel){
    sel.addEventListener('change', function(){ form.submit(); });
  });
})();

/* ===== Compositor (demo: inyecta tarjeta, no persiste) ===== */
(function(){
  const btn = document.getElementById('btnPublicar');
  if (!btn) return;
  btn.addEventListener('click', function(){
    const tipo   = document.getElementById('cmp_tipo').value || 'proyecto';
    const codigo = (document.getElementById('cmp_codigo').value || '').trim();
    const titulo = (document.getElementById('cmp_titulo').value || '').trim();
    const cont   = (document.getElementById('cmp_cont').value || '').trim();
    const img1   = (document.getElementById('cmp_img1').value || '').trim();
    const img2   = (document.getElementById('cmp_img2').value || '').trim();

    if (!titulo || !cont){ alert('Completa al menos TÃ­tulo y Contenido.'); return; }

    const autor = (tipo==='rsu') ? 'DirecciÃ³n RSU' : 'Coordinador/a';
    const rol   = (tipo==='rsu') ? 'Comunicado'    : 'Coordinador';
    const avatar= (tipo==='rsu')
      ? 'https://ui-avatars.com/api/?name=RSU&background=0d6efd&color=fff&size=64'
      : 'https://i.pravatar.cc/64?img=68';

    const now   = new Date();
    const dd  = String(now.getDate()).padStart(2,'0');
    const mm  = String(now.getMonth()+1).padStart(2,'0');
    const yyyy= now.getFullYear();
    const hh  = String(now.getHours()).padStart(2,'0');
    const min = String(now.getMinutes()).padStart(2,'0');
    const fecha = `${dd}/${mm}/${yyyy} ${hh}:${min}`;

    const imgs = [];
    if (img1) imgs.push(img1);
    if (img2) imgs.push(img2);

    const chips = (tipo==='rsu') ? ['#comunicado'] : ['#comunidad'];

    const html = `
      <article class="feed-card">
        <header class="feed-hd">
          <img class="avatar" src="${avatar}" alt="avatar">
          <div class="w-100">
            <div class="d-flex justify-content-between">
              <h5 class="feed-title">${autor} <small class="feed-meta">Â· ${rol}</small></h5>
              <div class="feed-meta">${fecha}</div>
            </div>
            <div class="feed-meta">
              ${codigo ? `<span class="code-badge"><i class="fas fa-hashtag"></i> ${codigo}</span>` : ``}
              ${tipo==='rsu' ? `<span class="ml-1"><i class="fas fa-building"></i> RSU</span>` : ``}
            </div>
          </div>
        </header>
        <div class="feed-body">
          <div class="font-weight-bold mb-1">${escapeHtml(titulo)}</div>
          <div class="mb-2">${escapeHtml(cont)}</div>
          ${imgs.length ? `
            <div class="img-row">
              ${imgs.map(u => `<div class="mb-2"><img src="${u}" alt="imagen"></div>`).join('')}
            </div>` : ``}
          <div class="mb-2">
            ${chips.map(c => `<span class="chip-tag" data-tag="${c}">${c}</span>`).join('')}
          </div>
        </div>
        <div class="feed-actions">
          <button class="btn btn-sm btn-outline-navy"><i class="far fa-thumbs-up"></i> Me gusta (0)</button>
          <button class="btn btn-sm btn-outline-navy"><i class="far fa-comment"></i> Comentarios (0)</button>
        </div>
      </article>
    `;
    const feed = document.getElementById('feedScroll');
if (feed) {
  feed.insertAdjacentHTML('afterbegin', html);
  // Recalcular alto del contenedor scrolleable
  window.dispatchEvent(new Event('resize'));
  // Scroll al inicio dentro del feed
  feed.scrollTo({top:0, behavior:'smooth'});
}

  });

  function escapeHtml(s){
    return s.replace(/[&<>"']/g, function(m){
      return ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'})[m];
    });
  }
})();

/* ===== Ver Informe (usa el mismo modal de siempre) ===== */
(function () {
  document.addEventListener('click', function (e) {
    const btn = e.target.closest('.btn-ver-informe');
    if (!btn) return;

    e.stopPropagation();
    e.preventDefault();

    const idpy = btn.getAttribute('data-id_py');
    if (!idpy) return;

    if (window.jQuery) {
      jQuery('#contenidoInforme').html('<p class="text-center text-muted my-4">Cargando informe...</p>');
      jQuery.get('../informe_semestral/ver_informe.php', { id: idpy }, function (html) {
        jQuery('#contenidoInforme').html(html);
      }, 'html').fail(function () {
        jQuery('#contenidoInforme').html('<div class="text-danger p-3">No se pudo cargar el informe.</div>');
      });
      jQuery('#modalInforme').modal('show');
      return;
    }

    fetch('../informe_semestral/ver_informe.php?id=' + encodeURIComponent(idpy))
      .then((r) => r.text())
      .then((html) => { document.getElementById('contenidoInforme').innerHTML = html; })
      .catch(() => { document.getElementById('contenidoInforme').innerHTML = '<div class="text-danger p-3">Error cargando informe.</div>'; });

    const modal = document.getElementById('modalInforme');
    if (window.bootstrap && window.bootstrap.Modal) new bootstrap.Modal(modal).show();
    else modal.style.display = 'block';
  });
})();
</script>

