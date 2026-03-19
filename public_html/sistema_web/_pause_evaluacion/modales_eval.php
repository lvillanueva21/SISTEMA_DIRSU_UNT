<?php
// /sistema_web/evaluacion/modales_eval.php
?>
<style>
  .ev-badge{display:inline-block;padding:.25rem .5rem;border-radius:.35rem;font-weight:600;font-size:.85rem}
  .ev-badge.wait{background:#ffeeba;color:#856404}
  .ev-badge.ok{background:#d4edda;color:#155724}
  .ev-badge.obs{background:#f8d7da;color:#721c24}

  .ev-help{font-size:.85rem;color:#6c757d}
  .ev-label{font-weight:600;margin:.35rem 0}
  .ev-label.red{color:#dc3545}
  .ev-label.green{color:#28a745}
  .ev-hr{border-top:1px solid #e9ecef;margin:.5rem 0}

  .ev-aspecto{border:1px solid #e9ecef;border-radius:.35rem;padding:.5rem;margin-bottom:.5rem}
  .ev-aspecto header{display:flex;align-items:center;justify-content:space-between;margin-bottom:.35rem}
  .ev-aspecto textarea{resize:vertical}
</style>

<!-- Modal genérico para todas las acciones -->
<div class="modal fade" id="modalEval" tabindex="-1" role="dialog" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable modal-lg" role="document">
    <div class="modal-content border-primary">
      <div class="modal-header bg-primary text-white py-2">
        <h5 class="modal-title" id="evTitle">Evaluación</h5>
        <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Cerrar">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body">
        <div id="evHeader" class="mb-2"></div>
        <div class="ev-hr"></div>
        <div id="evFormContainer"></div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-dismiss="modal" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" id="evBtnGuardar" class="btn btn-primary">
          <i class="fas fa-save"></i> Guardar
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Templates -->
<template id="tpl-cotejo">
  <form id="formCotejo">
    <div class="ev-label">Estado de Cotejo</div>
    <div class="mb-2">
      <label class="mr-3"><input type="radio" name="estado" value="0"> En espera</label>
      <label class="mr-3"><input type="radio" name="estado" value="1"> Aprobado</label>
      <label class="mr-3"><input type="radio" name="estado" value="2"> Observado</label>
    </div>
    <div id="cotejoObsBox" style="display:none;">
      <div class="ev-label red">Observación (obligatoria si Observado)</div>
      <textarea name="obs_general" class="form-control" rows="5" maxlength="3000"
        placeholder="Describe claramente qué debe subsanar (máx. 3000 caracteres)"></textarea>
      <div class="ev-help mt-1">Máximo 3000 caracteres.</div>

      <div class="ev-label red mt-2">Plazo de subsanación (obligatorio si Observado)</div>
      <label class="mr-3"><input type="radio" name="due_days" value="1"> 1 día</label>
      <label class="mr-3"><input type="radio" name="due_days" value="2"> 2 días</label>
    </div>
  </form>
</template>

<template id="tpl-vb">
  <form id="formVB">
    <div class="ev-label">Visto Bueno</div>
    <div>
      <label class="mr-3"><input type="radio" name="estado" value="0"> En espera</label>
      <label class="mr-3"><input type="radio" name="estado" value="1"> Aprobado</label>
    </div>
    <div class="ev-help mt-2">No existen observaciones en Visto Bueno.</div>
  </form>
</template>

<template id="tpl-rubrica">
  <form id="formRubrica">
    <div class="ev-label">Rúbrica — 5 aspectos (0..4). Total define el estado.</div>

    <!-- Aspectos -->
    <div class="ev-aspecto" data-asp="1">
      <header><div><strong>1) Estructura</strong></div>
        <select class="custom-select custom-select-sm asp-nota" data-asp="1">
          <option value="0">0 En espera</option>
          <option value="1">1 Insuficiente</option>
          <option value="2">2 Mejorable</option>
          <option value="3">3 Satisfactorio</option>
          <option value="4">4 Excelente</option>
        </select>
      </header>
      <div class="asp-obs-box" style="display:none;">
        <div class="ev-label" data-role="label">Observación</div>
        <textarea class="form-control asp-obs" rows="3" maxlength="3000" data-asp="1"
          placeholder="Observación / Recomendación (máx. 3000 caracteres)"></textarea>
        <div class="ev-help mt-1">Obligatoria cuando la nota es 1 o 2, opcional si el total queda Aprobado.</div>
      </div>
    </div>

    <div class="ev-aspecto" data-asp="2">
      <header><div><strong>2) Contenido</strong></div>
        <select class="custom-select custom-select-sm asp-nota" data-asp="2">
          <option value="0">0 En espera</option>
          <option value="1">1 Insuficiente</option>
          <option value="2">2 Mejorable</option>
          <option value="3">3 Satisfactorio</option>
          <option value="4">4 Excelente</option>
        </select>
      </header>
      <div class="asp-obs-box" style="display:none;">
        <div class="ev-label" data-role="label">Observación</div>
        <textarea class="form-control asp-obs" rows="3" maxlength="3000" data-asp="2"></textarea>
        <div class="ev-help mt-1">Obligatoria cuando la nota es 1 o 2, opcional si el total queda Aprobado.</div>
      </div>
    </div>

    <div class="ev-aspecto" data-asp="3">
      <header><div><strong>3) Redacción</strong></div>
        <select class="custom-select custom-select-sm asp-nota" data-asp="3">
          <option value="0">0 En espera</option>
          <option value="1">1 Insuficiente</option>
          <option value="2">2 Mejorable</option>
          <option value="3">3 Satisfactorio</option>
          <option value="4">4 Excelente</option>
        </select>
      </header>
      <div class="asp-obs-box" style="display:none;">
        <div class="ev-label" data-role="label">Observación</div>
        <textarea class="form-control asp-obs" rows="3" maxlength="3000" data-asp="3"></textarea>
        <div class="ev-help mt-1">Obligatoria cuando la nota es 1 o 2, opcional si el total queda Aprobado.</div>
      </div>
    </div>

    <div class="ev-aspecto" data-asp="4">
      <header><div><strong>4) Calidad de Información</strong></div>
        <select class="custom-select custom-select-sm asp-nota" data-asp="4">
          <option value="0">0 En espera</option>
          <option value="1">1 Insuficiente</option>
          <option value="2">2 Mejorable</option>
          <option value="3">3 Satisfactorio</option>
          <option value="4">4 Excelente</option>
        </select>
      </header>
      <div class="asp-obs-box" style="display:none;">
        <div class="ev-label" data-role="label">Observación</div>
        <textarea class="form-control asp-obs" rows="3" maxlength="3000" data-asp="4"></textarea>
        <div class="ev-help mt-1">Obligatoria cuando la nota es 1 o 2, opcional si el total queda Aprobado.</div>
      </div>
    </div>

    <div class="ev-aspecto" data-asp="5">
      <header><div><strong>5) Propuesta de mejora</strong></div>
        <select class="custom-select custom-select-sm asp-nota" data-asp="5">
          <option value="0">0 En espera</option>
          <option value="1">1 Insuficiente</option>
          <option value="2">2 Mejorable</option>
          <option value="3">3 Satisfactorio</option>
          <option value="4">4 Excelente</option>
        </select>
      </header>
      <div class="asp-obs-box" style="display:none;">
        <div class="ev-label" data-role="label">Observación</div>
        <textarea class="form-control asp-obs" rows="3" maxlength="3000" data-asp="5"></textarea>
        <div class="ev-help mt-1">Obligatoria cuando la nota es 1 o 2, opcional si el total queda Aprobado.</div>
      </div>
    </div>

    <div class="ev-hr"></div>

    <div class="d-flex align-items-center justify-content-between">
      <div>
        <div>Total: <strong id="rbTotal">0</strong> / 20</div>
        <div>Estado: <span id="rbEstado" class="ev-badge wait">En espera</span></div>
      </div>

      <div id="rbDueBox" style="display:none;">
        <div class="ev-label red mb-1">Plazo de subsanación (obligatorio si Observado)</div>
        <label class="mr-3"><input type="radio" name="due_days" value="1"> 1 día</label>
        <label class="mr-3"><input type="radio" name="due_days" value="2"> 2 días</label>
      </div>
    </div>
  </form>
</template>
