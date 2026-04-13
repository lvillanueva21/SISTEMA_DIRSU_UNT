<?php
// Requiere que index.php haya cargado $sm_info y $conexion
$form          = $sm_info['form_activo'] ?? null;
$semId         = $sm_info['semestre_objetivo_id'] ?? null;
$periodoNombre = $sm_info['periodo_activo']['nombre'] ?? '-';
$usuario       = $_SESSION['usuario'] ?? '';

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
  <div class="sm-impact__hero">
    <h5 id="sm-hero-title" class="mb-0">Período de informes: <span class="sm-subtle"><?= htmlspecialchars($periodoNombre) ?></span></h5>
    <div class="sm-muted">Ventana activa para presentación de informes semestrales</div>
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
        <div class="sm-title">Información del período</div>
        <div class="sm-sub">Revisa las fechas antes de continuar.</div>

        <div class="sm-dates">
          <span class="sm-badge sm-badge-open">Apertura: <?= htmlspecialchars($sm_info['apertura'] ?? '-') ?></span>
          <span class="sm-badge sm-badge-close">Cierre: <?= htmlspecialchars($sm_info['cierre'] ?? '-') ?></span>
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
