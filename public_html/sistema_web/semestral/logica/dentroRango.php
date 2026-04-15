<?php
// Requiere que index.php haya cargado $sm_info y $conexion
$form          = $sm_info['form_activo'] ?? null;
$semId         = isset($sm_info['semestre_objetivo_id']) ? (int)$sm_info['semestre_objetivo_id'] : 0;
$periodoNombre = $sm_info['periodo_activo']['nombre'] ?? '-';
$periodoActivoAnio = isset($sm_info['periodo_activo']['anio']) ? (int)$sm_info['periodo_activo']['anio'] : 0;
$periodoActivoCodigo = isset($sm_info['periodo_activo']['periodo']) ? strtoupper(trim((string)$sm_info['periodo_activo']['periodo'])) : '';
$usuario       = $_SESSION['usuario'] ?? '';
$aperturaTxt   = (string)($sm_info['apertura'] ?? '-');
$cierreTxt     = (string)($sm_info['cierre'] ?? '-');
$cronogramaTipo = isset($sm_info['cronograma_tipo']) ? (int)$sm_info['cronograma_tipo'] : 0;
$tipoSemestreEsperado = ($cronogramaTipo === 1) ? 'presentacion' : 'semestral';

$semestreActualLabel = (string)$periodoNombre;
$correspondeTexto = 'Informe semestral';
$esInformeFinalActual = false;

if ($cronogramaTipo === 1) {
    $correspondeTexto = 'Presentación de proyecto';
} elseif ($cronogramaTipo === 2) {
    $correspondeTexto = 'Informe semestral';
}

if (
    $semId <= 0
    && $periodoActivoAnio > 0
    && ($periodoActivoCodigo === 'I' || $periodoActivoCodigo === 'II')
) {
    $idProyectoSesion = isset($_SESSION['id_py']) ? (int)$_SESSION['id_py'] : 0;
    if ($idProyectoSesion > 0) {
        $stSemObj = $conexion->prepare("
          SELECT id
          FROM sm_proyecto_semestres
          WHERE id_py = ? AND anio = ? AND periodo = ? AND tipo = ? AND vigente = 1
          LIMIT 1
        ");
        if ($stSemObj) {
            $stSemObj->bind_param("iiss", $idProyectoSesion, $periodoActivoAnio, $periodoActivoCodigo, $tipoSemestreEsperado);
            $stSemObj->execute();
            $stSemObj->bind_result($semIdFound);
            if ($stSemObj->fetch()) {
                $semId = (int)$semIdFound;
            }
            $stSemObj->close();
        }
    }
}

if ($semId > 0) {
    $stSemInfo = $conexion->prepare("
      SELECT anio, periodo, tipo, final
      FROM sm_proyecto_semestres
      WHERE id = ?
      LIMIT 1
    ");
    if ($stSemInfo) {
        $semIdInt = (int)$semId;
        $stSemInfo->bind_param("i", $semIdInt);
        $stSemInfo->execute();
        $semInfo = $stSemInfo->get_result()->fetch_assoc();
        $stSemInfo->close();

        if ($semInfo) {
            $anio = isset($semInfo['anio']) ? (int)$semInfo['anio'] : 0;
            $periodo = isset($semInfo['periodo']) ? strtoupper(trim((string)$semInfo['periodo'])) : '';
            if ($anio > 0 && ($periodo === 'I' || $periodo === 'II')) {
                $semestreActualLabel = $anio . '-' . $periodo;
            }

            $tipo = isset($semInfo['tipo']) ? trim((string)$semInfo['tipo']) : '';
            $esFinalSem = isset($semInfo['final']) ? (int)$semInfo['final'] : 0;
            if ($tipo === 'semestral' && $esFinalSem === 1) {
                $correspondeTexto = 'Informe semestral final';
                $esInformeFinalActual = true;
            } elseif ($tipo === 'semestral') {
                $correspondeTexto = 'Informe semestral';
            } elseif ($tipo === 'presentacion') {
                $correspondeTexto = 'Presentación de proyecto';
            }
        }
    }
}

// Prefill de contacto (si existe tabla y registro)
$prefillEmail = '';
$prefillTel   = '';
$foundContact = false;

if ($usuario !== '') {
    // Evita error si la tabla aún no existe (primera vez)
    $chk = $conexion->query("SHOW TABLES LIKE 'usuario_contactos'");
    if ($chk && $chk->num_rows > 0) {
        if ($st = $conexion->prepare("SELECT email, telefono FROM usuario_contactos WHERE usuario=? LIMIT 1")) {
            $st->bind_param("s", $usuario);
            $st->execute();
            $res = $st->get_result()->fetch_assoc();
            if ($res) {
                $prefillEmail = (string)($res['email'] ?? '');
                $prefillTel   = (string)($res['telefono'] ?? '');
                $foundContact = ($prefillEmail !== '' || $prefillTel !== '');
            }
            $st->close();
        }
    }
}
?>
<style>
  /* Layout general */
  .sm-impact{border:0;overflow:hidden;border-radius:1rem;box-shadow:0 12px 30px rgba(0,0,0,.08);display:flex;flex-direction:column;height:100%;}
  .sm-impact__hero{
    background:
      radial-gradient(1200px 400px at 10% -20%, rgba(255,255,255,.25), transparent 60%),
      linear-gradient(135deg,#28a745,#1e7e34 55%,#155724);
    color:#fff;padding:1rem 1.25rem;
  }
  .sm-impact__hero--final{
    background:
      radial-gradient(1200px 400px at 10% -20%, rgba(255,255,255,.17), transparent 60%),
      linear-gradient(135deg,#1f2937,#111827 55%,#000000);
  }
  .sm-resumen{
    border:1px solid #d1d5db;
    background:#f9fafb;
    border-radius:.75rem;
    padding:.65rem .75rem;
    margin-bottom:.85rem;
    text-align:left;
  }
  .sm-resumen__title{
    font-size:.9rem;
    font-weight:700;
    color:#111827;
    margin-bottom:.35rem;
  }
  .sm-resumen__line{
    margin:0 0 .2rem 0;
    color:#374151;
    font-size:.88rem;
    line-height:1.3;
  }
  .sm-resumen__line:last-child{ margin-bottom:0; }
  .sm-chip-final{
    display:inline-flex;
    align-items:center;
    font-size:.74rem;
    font-weight:700;
    color:#ffffff;
    background:#111827;
    border:1px solid #111827;
    border-radius:999px;
    padding:.14rem .5rem;
    margin-top:.45rem;
  }
  .sm-body{flex:1 1 auto;}
  .sm-body.row{align-items:stretch;}

  /* Izquierda (solo imagen) */
  .sm-left{display:flex;align-items:center;justify-content:center;background:#f8fafc;border-right:1px solid #eef2f7;}
  .sm-left-frame{padding:1.25rem; /* ← aire alrededor de la imagen */ }
  .sm-illust{
    display:block;margin:0 auto;
    max-width:min(520px, 100%); /* controla tamaño */
    width:100%; height:auto; object-fit:contain;
  }

  /* Derecha (todo el contenido) */
  .sm-right{display:flex;align-items:center;justify-content:center;padding:2rem 1.5rem;}
  .sm-stack{width:100%;max-width:560px;text-align:center;}
  .sm-title{font-weight:700;font-size:1.1rem;margin-bottom:.75rem;}
  .sm-sub{color:#6b7280;margin-bottom:1rem;}

  /* Fechas en fila */
  .sm-dates{display:flex;flex-wrap:wrap;justify-content:center;margin:-.25rem 0 1rem;}
  .sm-badge{display:inline-block;margin:.25rem .35rem;padding:.4rem .65rem;border-radius:.5rem;font-weight:600;font-size:.9rem;}
  .sm-badge-open{background:#e9f7ef;color:#155724;}
  .sm-badge-close{background:#fff7ed;color:#9a3412;}

  /* Mensajes y CTA */
  .sm-help{font-size:.92rem;color:#4b5563;margin-bottom:1rem;}
  .sm-divider{height:1px;background:rgba(0,0,0,.06);margin:1rem auto;width:100%;}
  .sm-alert{background:#fff1f2;color:#b91c1c;border:1px solid #fecaca;border-radius:.75rem;padding:.75rem .9rem;font-weight:600;margin-bottom:0;}
  .sm-empty{background:#fff7ed;color:#9a3412;border:1px solid #fed7aa;}

  @media (max-width: 767.98px){
    .sm-left{border-right:0;border-bottom:1px solid #eef2f7;}
    .sm-right{padding:1.25rem 1rem;}
    .sm-left-frame{padding:.75rem;}
  }
</style>

<div class="card sm-impact" role="region" aria-labelledby="sm-hero-title">
  <!-- Hero -->
  <div class="sm-impact__hero <?php echo $esInformeFinalActual ? 'sm-impact__hero--final' : ''; ?>">
    <h5 id="sm-hero-title" class="mb-0">Período de informes: <span class="sm-subtle"><?= htmlspecialchars($periodoNombre) ?></span></h5>
    <div class="sm-muted">
      <?php if ($esInformeFinalActual): ?>
        Ventana activa para presentaci&oacute;n de informe semestral final
      <?php else: ?>
        Ventana activa para presentaci&oacute;n de informes semestrales
      <?php endif; ?>
    </div>
  </div>

  <!-- Cuerpo -->
  <div class="row no-gutters sm-body">
    <!-- Izquierda: imagen con aire -->
    <div class="col-md-6 sm-left">
      <div class="sm-left-frame">
        <img class="sm-illust" src="../imagenes/apertura_informe_semestral.png" alt="Referencia" loading="lazy">
      </div>
    </div>

    <!-- Derecha: todo el contenido -->
    <div class="col-md-6 sm-right">
      <div class="sm-stack">
        <div class="sm-resumen">
          <div class="sm-resumen__title">Resumen del semestre actual</div>
          <p class="sm-resumen__line"><strong>Semestre actual del proyecto:</strong> <?= htmlspecialchars($semestreActualLabel) ?></p>
          <p class="sm-resumen__line"><strong>Te corresponde subir:</strong> <?= htmlspecialchars($correspondeTexto) ?></p>
          <p class="sm-resumen__line"><strong>Debes completar:</strong> <?= htmlspecialchars($form['nombre'] ?? 'Formulario del cronograma activo') ?></p>
          <p class="sm-resumen__line"><strong>Ventana para completar:</strong> <?= htmlspecialchars($aperturaTxt) ?> | <?= htmlspecialchars($cierreTxt) ?></p>
          <?php if ($esInformeFinalActual): ?>
            <span class="sm-chip-final">Tramo final del proyecto</span>
          <?php endif; ?>
        </div>

        <div class="sm-title">Información del período</div>
        <div class="sm-sub">Revisa las fechas antes de continuar.</div>

        <div class="sm-dates">
          <span class="sm-badge sm-badge-open">Apertura: <?= htmlspecialchars($aperturaTxt) ?></span>
          <span class="sm-badge sm-badge-close">Cierre: <?= htmlspecialchars($cierreTxt) ?></span>
        </div>

        <div class="sm-divider"></div>

        <?php if (!$form): ?>
          <div class="sm-alert">No hay formulario activo para el cronograma actual.</div>

        <?php elseif (!$semId): ?>
          <div class="sm-alert sm-empty">
            Tu proyecto no necesita un <b><?= htmlspecialchars($form['nombre']) ?></b>
            porque no contiene el semestre <b><?= htmlspecialchars($periodoNombre) ?></b>.
          </div>

        <?php else: ?>
          <div class="sm-help">
            Para crear tu <b><?= htmlspecialchars($form['nombre']) ?></b> de <b><?= htmlspecialchars($periodoNombre) ?></b>,
            primero registra tu <b>correo institucional</b> y <b>teléfono</b>.
          </div>
          <button type="button" class="btn btn-primary btn-lg px-4" data-toggle="modal" data-target="#crearModal">
            Crear <?= htmlspecialchars($form['nombre']) ?>
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>


<?php if ($form && $semId): ?>
<!-- Modal de confirmación + contacto -->
<div class="modal fade" id="crearModal" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-md modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header py-2">
        <h6 class="modal-title mb-0">Confirmación para crear <?= htmlspecialchars($form['nombre']) ?></h6>
        <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formCrear" method="post" action="logica/crear_respuesta.php" novalidate>
        <div class="modal-body">
          <p class="mb-2">
            Para crear un <b><?= htmlspecialchars($form['nombre']) ?></b> debes registrar tu
            <b>correo</b> (para observaciones) y tu <b>teléfono</b> (directorio DIRSU de coordinadores de proyectos).
          </p>

          <!-- Hidden: ids -->
          <input type="hidden" name="id_formulario" value="<?= (int)$form['id'] ?>">
          <input type="hidden" name="id_semestre"   value="<?= (int)$semId ?>">
          <input type="hidden" name="id_cronograma" value="<?= (int)$sm_info['cronograma_id'] ?>">

          <!-- Email -->
          <div class="form-group mb-2">
            <label for="contact_email" class="mb-0">Correo institucional (@unitru.edu.pe)</label>
            <input
              type="email"
              class="form-control"
              id="contact_email"
              name="email"
              value="<?= htmlspecialchars($prefillEmail) ?>"
              placeholder="tucuenta@unitru.edu.pe"
              required
              autocomplete="email"
              inputmode="email"
            >
            <?php if ($foundContact): ?>
              <small class="text-info">Se encontró contacto previo, ¿actualizar?</small>
            <?php endif; ?>
            <div class="invalid-feedback">
              Ingresa un correo válido con dominio <b>@unitru.edu.pe</b>.
            </div>
          </div>

          <!-- Teléfono -->
          <div class="form-group mb-0">
            <label for="contact_tel" class="mb-0">Teléfono (9 dígitos, inicia con 9)</label>
            <input
              type="tel"
              class="form-control"
              id="contact_tel"
              name="telefono"
              value="<?= htmlspecialchars($prefillTel) ?>"
              placeholder="9XXXXXXXX"
              required
              minlength="9"
              maxlength="9"
              pattern="^9\d{8}$"
              inputmode="numeric"
            >
            <div class="invalid-feedback">
              El teléfono debe tener 9 dígitos y empezar con 9.
            </div>
          </div>
        </div>

        <div class="modal-footer py-2">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-primary btn-sm">Crear ahora</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// ===== Validación en vivo (Bootstrap 4) =====
(function () {
  var form = document.getElementById('formCrear');
  var email = document.getElementById('contact_email');
  var tel   = document.getElementById('contact_tel');

  // Limpia tel (solo números, tope 9)
  function sanitizeTel() {
    tel.value = tel.value.replace(/\D/g, '').slice(0, 9);
  }
  tel.addEventListener('input', sanitizeTel);

  // Normaliza y valida email (dominio unitru.edu.pe)
  function validateEmailDomain() {
    var v = (email.value || '').trim().toLowerCase();
    email.value = v;
    if (!v.endsWith('@unitru.edu.pe')) {
      email.setCustomValidity('Dominio no permitido');
    } else {
      email.setCustomValidity('');
    }
  }
  email.addEventListener('input', validateEmailDomain);

  form.addEventListener('submit', function (e) {
    sanitizeTel();
    validateEmailDomain();

    // Valida teléfono contra regex exacta
    var telOk = /^9\d{8}$/.test(tel.value);
    if (!telOk) tel.setCustomValidity('Teléfono inválido'); else tel.setCustomValidity('');

    if (!form.checkValidity()) {
      e.preventDefault();
      e.stopPropagation();
    }
    form.classList.add('was-validated');
  });
})();
</script>
<?php endif; ?>
