<?php
// 🔗 Conexión a la base de datos
include('componentes/db.php');

// ▸ Función para contar registros en cualquier tabla
function contarRegistros($conexion, $tabla) {
    $r = mysqli_query($conexion, "SELECT COUNT(*) AS total FROM $tabla");
    return $r ? mysqli_fetch_assoc($r)['total'] : 0;
}

// ▸ Lista de tablas y sus nombres para cards
$tablas = [
    'usuarios' => 'Usuarios 👤', 'proyectos' => 'Proyectos 📚',
    'proyectos_periodo' => 'Proyectos-Periodo 🗓️', 'usuarios_proyectos' => 'Usuarios-Proyectos 🔗',
    'revisiones_proyectos' => 'Revisiones-Proyectos 🛠️', 'evaluaciones' => 'Evaluaciones 🧠',
    'observaciones_cotejo' => 'Observaciones Cotejo 📝', 'rubrica_aspectos' => 'Rubrica Aspectos 🎯',
    'historial_estados' => 'Historial Estados 🕒', 'periodos' => 'Periodos 📅'
];

// ▸ Conteo inicial de registros
$conteos = [];
foreach ($tablas as $tabla => $nombre) {
    $conteos[$tabla] = contarRegistros($conexion, $tabla);
}

// ▸ Consultas específicas para botones
$q1 = "SELECT id, usuario, nombres, apellidos FROM usuarios WHERE LENGTH(usuario) = 4";
$q2 = "SELECT id, usuario, nombres, apellidos FROM usuarios WHERE LENGTH(usuario) != 4 AND LENGTH(usuario) != 5";
$q3 = "SELECT id, usuario, nombres, apellidos FROM usuarios WHERE id_py IS NOT NULL AND id_py != 0";
$q4 = "SELECT u.usuario, COUNT(up.id_proyecto) AS cantidad FROM usuarios u
        INNER JOIN usuarios_proyectos up ON u.id = up.id_usuario
        GROUP BY u.id HAVING cantidad > 1";

// ▸ Totales de registros para cada consulta
$tot1 = mysqli_num_rows(mysqli_query($conexion, $q1));
$tot2 = mysqli_num_rows(mysqli_query($conexion, $q2));
$tot3 = mysqli_num_rows(mysqli_query($conexion, $q3));
$tot4 = mysqli_num_rows(mysqli_query($conexion, $q4));
?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="utf-8">
<title>Central de Monitoreo 📡</title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

<style>
/* ─────────── ESTILOS PRINCIPALES ─────────── */
body, html { height: 100%; margin: 0; }
.sidebar-left, .sidebar-right, .sidebar-top, .sidebar-bottom {
    position: fixed; padding: 10px; color: #fff; z-index: 1000;
}
.sidebar-left {
    top: 0; bottom: 0; left: 0; width: 200px; background: #343a40; overflow-y: auto;
}
.sidebar-right {
    top: 0; bottom: 0; right: 0; width: 200px; background: #343a40;
}
.sidebar-top {
    top: 0; left: 200px; right: 200px; height: 60px; background: #007bff;
    display: flex; align-items: center; justify-content: center;
}
.sidebar-bottom {
    bottom: 0; left: 200px; right: 200px; height: 150px; background: #f8f9fa;
    white-space: nowrap; overflow-x: auto; overflow-y: hidden; padding-bottom: 20px;
}
.sidebar-bottom::-webkit-scrollbar { height: 8px; }
.sidebar-bottom::-webkit-scrollbar-thumb { background: #adb5bd; border-radius: 4px; }
.content {
    position: absolute; top: 60px; bottom: 150px; left: 200px; right: 200px;
    background: #f8f9fa; display: flex; justify-content: center; align-items: center;
    padding: 20px; overflow-y: auto;
}
.card-table {
    background: #ffffff; border-radius: 10px; box-shadow: 0 0 6px rgba(0,0,0,0.15);
    margin: 4px; min-width: 180px; height: 110px; flex: 0 0 auto; text-align: center;
    display: flex; flex-direction: column; justify-content: space-between;
}
.card-table .card-header {
    background-color: #212529; color: #f8f9fa; font-weight: bold;
    padding: 6px; font-size: 0.8rem; border-top-left-radius: 10px; border-top-right-radius: 10px;
}
.card-table .card-body {
    background: #f8f9fa; color: #212529; font-size: 0.95rem;
    padding: 5px; display: flex; flex-direction: column; justify-content: center;
}
</style>

</head>

<body>

<!-- ■■■ PANEL IZQUIERDO ■■■ -->
<div class="sidebar-left">
    <h5 class="text-center text-primary mb-3">📋 Usuarios - Relación</h5>
    <table class="table table-bordered table-sm text-center">
        <thead class="table-dark">
            <tr><th>Relación</th></tr>
        </thead>
        <tbody>
            <?php
            function fila($titulo, $total, $query, $tipo) {
                $qEsc = htmlspecialchars($query, ENT_QUOTES);
                echo "<tr><td>
                        <div><strong>$titulo</strong></div>
                        <div class='text-muted small mb-2'>$total usuarios</div>
                        <div>
                            <button class='btn btn-primary btn-sm me-1 btn-consulta' data-query=\"$qEsc\">🔍</button>
                            <button class='btn btn-success btn-sm btn-registros' data-tipo='$tipo'>📄</button>
                        </div>
                      </td></tr>";
            }
            fila('Usuarios con cod. Doc.', $tot1, $q1, 'cod_doc');
            fila('Usuarios sin cod.',     $tot2, $q2, 'sin_cod');
            fila('Con proyecto activo',   $tot3, $q3, 'con_proyecto');
            fila('Con múltiples proyectos', $tot4, $q4, 'multiproyectos');
            ?>
        </tbody>
    </table>
</div>

<!-- ■■■ PANEL DERECHO / TOP ■■■ -->
<div class="sidebar-right">
    <h6>⚙️ Opciones</h6>
    <ul class="nav flex-column">
        <li class="nav-item"><a class="nav-link text-white" href="#">Configuración</a></li>
        <li class="nav-item"><a class="nav-link text-white" href="#">Ayuda</a></li>
    </ul>
</div>

<div class="sidebar-top">
    <h5 class="text-white m-0">🚀 Central de Monitoreo de Proyectos DIRSU</h5>
</div>

<!-- ■■■ PANEL INFERIOR - CARDS ■■■ -->
<div class="sidebar-bottom">
    <div id="tablasContainer" class="cards-container" style="display:flex;align-items:center;height:100%;padding:5px;">
        <?php foreach ($tablas as $tabla => $nombre): ?>
        <div class="card-table">
            <div class="card-header"><?= $nombre ?></div>
            <div class="card-body">
                <p class="mb-1">Registros:</p>
                <h6 class="mb-0"><?= $conteos[$tabla] ?></h6>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- ■■■ ÁREA CENTRAL ■■■ -->
<div class="content">
    <div id="lienzo" class="card w-100 shadow p-4" style="min-height:300px;">
        <h5 class="card-title text-center">🎨 Área de Lienzo</h5>
        <p class="card-text text-center text-muted">Aquí se mostrará la consulta o los registros.</p>
    </div>
</div>
<?php
// ---- DATOS PARA JAVASCRIPT ----
$cod_doc_array = mysqli_fetch_all(mysqli_query($conexion, $q1), MYSQLI_ASSOC);
$sin_cod_array = mysqli_fetch_all(mysqli_query($conexion, $q2), MYSQLI_ASSOC);
$con_proyecto_array = mysqli_fetch_all(mysqli_query($conexion, $q3), MYSQLI_ASSOC);
$multiproyectos_array = mysqli_fetch_all(mysqli_query($conexion, "
    SELECT u.usuario,u.id_rol,u.nombres,u.apellidos,u.id_py,
           p.id id_proyecto,p.p2 titulo,per.nombre periodo
    FROM usuarios u
    INNER JOIN usuarios_proyectos up ON u.id=up.id_usuario
    INNER JOIN proyectos p ON p.id=up.id_proyecto
    INNER JOIN proyectos_periodo pp ON pp.id_py=p.id
    INNER JOIN periodos per ON per.id=pp.id_periodo
"), MYSQLI_ASSOC);
?>

<!-- CARGA Bootstrap Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- SCRIPT PRINCIPAL -->
<script>
/* ========== DATOS PHP → JS ========== */
const datos = {
  cod_doc: <?= json_encode($cod_doc_array) ?>,
  sin_cod: <?= json_encode($sin_cod_array) ?>,
  con_proyecto: <?= json_encode($con_proyecto_array) ?>,
  multiproyectos: <?= json_encode($multiproyectos_array) ?>
};

/* ========== UTILIDADES ========== */
function mostrarConsulta(q) {
  document.getElementById('lienzo').innerHTML = `
    <h5>🔍 Consulta SQL:</h5>
    <pre class="bg-light p-3 border rounded">${q}</pre>`;
}

function mostrarRegistros(tipo) {
  const data = datos[tipo] || [];
  if (!data.length) {
    mostrarConsulta('Sin resultados.');
    return;
  }

  const cols = ['#', ...Object.keys(data[0])];
  const lienzo = document.getElementById('lienzo');
  lienzo.innerHTML = `
    <div class="sticky-top bg-white py-2" style="z-index:10">
      <div class="d-flex justify-content-between flex-wrap mb-2">
        <input id="searchInput" class="form-control form-control-sm w-50" placeholder="🔍 Buscar...">
        <select id="rowsPerPage" class="form-control form-control-sm w-auto">
          <option value="10">10 filas</option>
          <option value="20">20 filas</option>
          <option value="9999">Todos</option>
        </select>
      </div>
    </div>
    <div class="table-responsive" style="overflow-x:auto;max-height:500px;overflow-y:auto;margin-top:50px;">
      <table class="table table-sm table-bordered table-hover" id="tablaResultados">
        <thead class="table-dark"><tr>${cols.map(c => `<th>${c}</th>`).join('')}</tr></thead>
        <tbody></tbody>
      </table>
    </div>
    <nav class="mt-2">
      <ul class="pagination justify-content-center" id="pagination"></ul>
    </nav>`;

  let currentPage = 1;
  let rowsPerPage = parseInt(document.getElementById('rowsPerPage').value);

  function renderTable() {
    const tbody = document.querySelector('#tablaResultados tbody');
    const paginacion = document.getElementById('pagination');
    const term = document.getElementById('searchInput').value.toLowerCase();

    const filtered = data.filter(r => Object.values(r).some(v => v && v.toString().toLowerCase().includes(term)));

    const start = (currentPage - 1) * rowsPerPage;
    const end = rowsPerPage === 9999 ? filtered.length : start + rowsPerPage;
    const slice = filtered.slice(start, end);

    tbody.innerHTML = slice.map((r, i) =>
      `<tr><td>${start + i + 1}</td>${cols.slice(1).map(c => `<td>${r[c] ?? ''}</td>`).join('')}</tr>`
    ).join('');

    const totalPages = Math.ceil(filtered.length / rowsPerPage);
    paginacion.innerHTML = [...Array(totalPages).keys()].map(n =>
      `<li class="page-item ${n + 1 === currentPage ? 'active' : ''}">
        <button class="page-link btn-sm">${n + 1}</button>
      </li>`).join('');

    paginacion.querySelectorAll('button').forEach(btn => {
      btn.onclick = () => {
        currentPage = +btn.textContent;
        renderTable();
      };
    });
  }

  // Eventos
  document.getElementById('searchInput').addEventListener('input', () => {
    currentPage = 1;
    renderTable();
  });
  document.getElementById('rowsPerPage').addEventListener('change', (e) => {
    rowsPerPage = parseInt(e.target.value);
    currentPage = 1;
    renderTable();
  });

  renderTable();
}

/* ========== ASIGNAR EVENTOS AL CARGAR EL DOM ========== */
document.addEventListener('DOMContentLoaded', () => {
  // Scroll horizontal en el footer (cards)
  const barra = document.querySelector('.sidebar-bottom');
  barra.addEventListener('wheel', (e) => {
    if (e.deltaY !== 0) {
      e.preventDefault();
      barra.scrollLeft += e.deltaY;
    }
  });

  // Botones lupa y registros
  document.querySelectorAll('.btn-consulta').forEach(boton => {
    boton.addEventListener('click', () => mostrarConsulta(boton.dataset.query));
  });
  document.querySelectorAll('.btn-registros').forEach(boton => {
    boton.addEventListener('click', () => mostrarRegistros(boton.dataset.tipo));
  });
});
</script>
</body>
</html>
