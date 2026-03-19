<?php /* Diagrama estático de la ruta evaluadora (fiel a la imagen) */ ?>
<div class="card shadow-sm">
  <div class="card-body p-3">
    <div class="rsu-diagrama" style="width:100%;">
      <!--
        El SVG es responsivo via viewBox. Puedes ajustar textos o posiciones
        buscando los grupos <g id="...">. Todo el estilo está inline para evitar
        conflictos con AdminLTE.
      -->
      <svg viewBox="0 0 1440 620" width="100%" role="img" aria-label="Ruta de evaluación del informe semestral">
        <defs>
          <!-- Flechas -->
          <marker id="flecha" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#0f172a"></path>
          </marker>
          <marker id="flechaVerde" viewBox="0 0 10 10" refX="8" refY="5" markerWidth="8" markerHeight="8" orient="auto-start-reverse">
            <path d="M 0 0 L 10 5 L 0 10 z" fill="#16a34a"></path>
          </marker>
          <!-- Sombras ligeras -->
          <filter id="sombra" x="-20%" y="-20%" width="140%" height="140%">
            <feDropShadow dx="0" dy="2" stdDeviation="3" flood-color="rgba(2,6,23,.25)"/>
          </filter>
          <!-- Estilos de texto -->
          <style>
            .t{font-family: system-ui,-apple-system,Segoe UI,Roboto,Helvetica,Arial; fill:#0f172a}
            .t.small{font-size:12px}
            .t.base{font-size:14px}
            .t.med{font-size:15px;font-weight:700}
            .t.big{font-size:20px;font-weight:800}
            .t.white{fill:#fff}
            .nota{fill:#fde68a;stroke:#eab308;stroke-width:1}
            .ok{fill:#16a34a}
            .okText{fill:#fff;font-size:13px;font-weight:800}
            .pill{rx:14; ry:14}
            .card{rx:14; ry:14; fill:#fff; stroke:#e2e8f0}
            .header{font-weight:800;fill:#fff}
            .b-gray{fill:#64748b}
            .b-blue{fill:#2563eb}
            .b-amber{fill:#d97706}
            .b-cyan{fill:#06b6d4}
            .b-green{fill:#16a34a}
            .dashed{stroke:#94a3b8; stroke-width:2; stroke-dasharray:10 8; fill:none}
          </style>
        </defs>

        <!-- ===== Título superior ===== -->
        <g id="titulo">
          <rect x="12" y="14" width="1416" height="56" rx="10" fill="#111827"/>
          <text class="t white big" x="40" y="50">RUTA DE EVALUACIÓN DE INFORME SEMESTRAL 2025‑I</text>
        </g>

        <!-- ===== Banda superior Observado / días de subsanación ===== -->
        <g id="observado" transform="translate(80,98)">
          <path class="dashed" d="M0 30 H1260"></path>
          <rect x="210"  y="0" width="180" height="44" rx="8" fill="#fca5a5" filter="url(#sombra)"/>
          <text class="t base" x="300" y="19" text-anchor="middle">Observado</text>
          <text class="t small" x="300" y="35" text-anchor="middle">Días de subsanación</text>

          <rect x="690"  y="0" width="200" height="44" rx="8" fill="#fca5a5" filter="url(#sombra)"/>
          <text class="t base" x="790" y="19" text-anchor="middle">Observado</text>
          <text class="t small" x="790" y="35" text-anchor="middle">Días de subsanación</text>
        </g>

        <!-- ===== Columnas (x de cada estación) ===== -->
        <!-- Coordenadas base -->
        <!-- c0=120, c1=400, c2=680, c3=960, c4=1240 -->
        <!-- Línea de base para guiar flechas -->
        <!-- (Solo decoración, no visible) -->

        <!-- ===== Coordinador de proyecto ===== -->
        <g id="coordinador" transform="translate(80,190)">
          <rect class="pill b-gray" x="0" y="0" width="250" height="56" filter="url(#sombra)"/>
          <text class="t white med" x="125" y="34" text-anchor="middle">Coordinador de proyecto</text>

          <rect class="card" x="22" y="82" width="206" height="120" filter="url(#sombra)"/>
          <text class="t med" x="125" y="122" text-anchor="middle">Período 2025‑I:</text>
          <text class="t med" x="125" y="146" text-anchor="middle">Informe Semestral</text>

          <!-- Nota: Solicita revisión -->
          <g transform="translate(30,230)">
            <rect class="nota" x="0" y="0" width="150" height="56" rx="8" filter="url(#sombra)"/>
            <text class="t base" x="75" y="23" text-anchor="middle">Solicita</text>
            <text class="t base" x="75" y="40" text-anchor="middle">Revisión</text>
          </g>
        </g>

        <!-- ===== Comité de Facultad ===== -->
        <g id="comite" transform="translate(360,190)">
          <rect class="pill b-blue" x="0" y="0" width="280" height="56" filter="url(#sombra)"/>
          <text class="t header" x="140" y="26" text-anchor="middle">Oficina: Comité de</text>
          <text class="t header" x="140" y="44" text-anchor="middle">Facultad</text>

          <rect class="card" x="10" y="82" width="260" height="140" filter="url(#sombra)"/>
          <text class="t base" x="24" y="118">1. Revisión por <tspan font-weight="800">Lista</tspan></text>
          <text class="t base" x="24" y="138"><tspan font-weight="800">de Cotejo</tspan></text>
          <text class="t base" x="24" y="168">2. Revisión por</text>
          <text class="t base" x="24" y="188"><tspan font-weight="800">Rúbricas</tspan></text>
        </g>

        <!-- ===== Dirección Departamento ===== -->
        <g id="departamento" transform="translate(640,190)">
          <rect class="pill b-amber" x="0" y="0" width="280" height="56" filter="url(#sombra)"/>
          <text class="t header" x="140" y="26" text-anchor="middle">Oficina: Dirección</text>
          <text class="t header" x="140" y="44" text-anchor="middle">Departamento</text>

          <rect class="card" x="10" y="82" width="260" height="120" filter="url(#sombra)"/>
          <text class="t base" x="24" y="118">1. Revisión por <tspan font-weight="800">Visto</tspan></text>
          <text class="t base" x="24" y="138"><tspan font-weight="800">Bueno</tspan></text>
        </g>

        <!-- ===== Decanato de Facultad ===== -->
        <g id="decanato" transform="translate(920,190)">
          <rect class="pill b-cyan" x="0" y="0" width="280" height="56" filter="url(#sombra)"/>
          <text class="t header" x="140" y="26" text-anchor="middle">Oficina: Decanato</text>
          <text class="t header" x="140" y="44" text-anchor="middle">de Facultad</text>

          <rect class="card" x="10" y="82" width="260" height="120" filter="url(#sombra)"/>
          <text class="t base" x="24" y="118">1. Revisión por <tspan font-weight="800">Visto</tspan></text>
          <text class="t base" x="24" y="138"><tspan font-weight="800">Bueno</tspan></text>
        </g>

        <!-- ===== Dirección RSU ===== -->
        <g id="rsu" transform="translate(1200,190)">
          <rect class="pill b-green" x="0" y="0" width="220" height="56" filter="url(#sombra)"/>
          <text class="t header" x="110" y="26" text-anchor="middle">Oficina: Dirección</text>
          <text class="t header" x="110" y="44" text-anchor="middle">RSU</text>

          <rect class="card" x="-10" y="82" width="240" height="140" filter="url(#sombra)"/>
          <text class="t base" x="6" y="118">1. Revisión por <tspan font-weight="800">Lista</tspan> de</text>
          <text class="t base" x="6" y="138"><tspan font-weight="800">Cotejo</tspan></text>
          <text class="t base" x="6" y="168">2. Revisión por <tspan font-weight="800">Rúbricas</tspan></text>

          <!-- Aprobación 2025-I -->
          <g transform="translate(170,110)">
            <rect x="0" y="0" width="120" height="60" rx="12" fill="#fde68a" stroke="#f59e0b"/>
            <text class="t base" x="60" y="25" text-anchor="middle">Aprobación</text>
            <text class="t base" x="60" y="45" text-anchor="middle">2025‑I</text>
          </g>
          <line x1="130" y1="150" x2="170" y2="140" stroke="#0f172a" stroke-width="2" marker-end="url(#flecha)"/>
        </g>

        <!-- ===== Bloques verdes de “Aprobado / Derivado para Revisión” + flechas ===== -->
        <!-- Entre Coordinador -> Comité -->
        <g transform="translate(300,410)">
          <rect class="ok" x="-60" y="0" width="120" height="90" rx="8" filter="url(#sombra)"/>
          <text class="okText" x="0" y="28" text-anchor="middle">Aprobado</text>
          <text class="okText" x="0" y="48" text-anchor="middle">Derivado</text>
          <text class="okText" x="0" y="68" text-anchor="middle">para</text>
          <text class="okText" x="0" y="88" text-anchor="middle">Revisión</text>
          <line x1="-160" y1="-150" x2="-60" y2="0" stroke="#16a34a" stroke-width="3" marker-end="url(#flechaVerde)"/>
          <line x1="60" y1="0" x2="120" y2="-150" stroke="#0f172a" stroke-width="2" marker-end="url(#flecha)"/>
        </g>

        <!-- Comité -> Dirección Departamento -->
        <g transform="translate(580,410)">
          <rect class="ok" x="-60" y="0" width="120" height="90" rx="8" filter="url(#sombra)"/>
          <text class="okText" x="0" y="28" text-anchor="middle">Aprobado</text>
          <text class="okText" x="0" y="48" text-anchor="middle">Derivado</text>
          <text class="okText" x="0" y="68" text-anchor="middle">para</text>
          <text class="okText" x="0" y="88" text-anchor="middle">Revisión</text>
          <line x1="-160" y1="-150" x2="-60" y2="0" stroke="#16a34a" stroke-width="3" marker-end="url(#flechaVerde)"/>
          <line x1="60" y1="0" x2="120" y2="-150" stroke="#0f172a" stroke-width="2" marker-end="url(#flecha)"/>
        </g>

        <!-- Dirección Departamento -> Decanato -->
        <g transform="translate(860,410)">
          <rect class="ok" x="-60" y="0" width="120" height="90" rx="8" filter="url(#sombra)"/>
          <text class="okText" x="0" y="28" text-anchor="middle">Aprobado</text>
          <text class="okText" x="0" y="48" text-anchor="middle">Derivado</text>
          <text class="okText" x="0" y="68" text-anchor="middle">para</text>
          <text class="okText" x="0" y="88" text-anchor="middle">Revisión</text>
          <line x1="-160" y1="-150" x2="-60" y2="0" stroke="#16a34a" stroke-width="3" marker-end="url(#flechaVerde)"/>
          <line x1="60" y1="0" x2="120" y2="-150" stroke="#0f172a" stroke-width="2" marker-end="url(#flecha)"/>
        </g>

        <!-- Decanato -> RSU -->
        <g transform="translate(1140,410)">
          <rect class="ok" x="-60" y="0" width="120" height="90" rx="8" filter="url(#sombra)"/>
          <text class="okText" x="0" y="28" text-anchor="middle">Aprobado</text>
          <text class="okText" x="0" y="48" text-anchor="middle">Derivado</text>
          <text class="okText" x="0" y="68" text-anchor="middle">para</text>
          <text class="okText" x="0" y="88" text-anchor="middle">Revisión</text>
          <line x1="-160" y1="-150" x2="-60" y2="0" stroke="#16a34a" stroke-width="3" marker-end="url(#flechaVerde)"/>
          <line x1="60" y1="0" x2="120" y2="-150" stroke="#0f172a" stroke-width="2" marker-end="url(#flecha)"/>
        </g>

        <!-- Notas amarillas inferiores (después de subsanar) -->
        <g transform="translate(520,510)">
          <rect class="nota" x="-90" y="0" width="180" height="56" rx="8" filter="url(#sombra)"/>
          <text class="t base" x="0" y="22" text-anchor="middle">Solicita Revisión</text>
          <text class="t base" x="0" y="40" text-anchor="middle">después de subsanar</text>
        </g>

        <g transform="translate(1040,510)">
          <rect class="nota" x="-90" y="0" width="180" height="56" rx="8" filter="url(#sombra)"/>
          <text class="t base" x="0" y="22" text-anchor="middle">Solicita Revisión</text>
          <text class="t base" x="0" y="40" text-anchor="middle">después de subsanar</text>
        </g>
      </svg>
    </div>
  </div>
</div>
