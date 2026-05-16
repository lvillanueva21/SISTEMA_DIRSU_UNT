document.addEventListener('DOMContentLoaded', function () {
  var block = document.getElementById('pageHeaderBlock');
  if (!block) return;

  var canManage = block.dataset.canmanage === '1';
  if (!canManage) return;

  var apiUrl = block.dataset.api || '';
  var pageKey = block.dataset.pagekey || '';
  var csrf = block.dataset.csrf || '';
  var currentImg = block.dataset.currentimg || '';
  var defaultImg = block.dataset.defaultimg || 'img/carousel-1.jpg';
  var maxBytes = parseInt(block.dataset.uploadmaxbytes || '0', 10);
  var maxLabel = block.dataset.uploadmaxlabel || '';

  var form = document.getElementById('pageHeaderForm');
  var btnSave = document.getElementById('btnSavePageHeader');
  var btnClear = document.getElementById('btnClearPageHeaderImage');
  var alertBox = document.getElementById('pageHeaderAlert');
  var descInput = document.getElementById('phDescripcion');
  var fotoInput = document.getElementById('phFoto');
  var previewImg = document.getElementById('phPreviewImg');
  var previewEmpty = document.getElementById('phPreviewEmpty');
  var progressWrap = document.getElementById('phProgressWrap');
  var progressBar = document.getElementById('phProgressBar');

  var statusModalEl = document.getElementById('pageHeaderStatusModal');
  var statusHead = document.getElementById('pageHeaderStatusHead');
  var statusTitle = document.getElementById('pageHeaderStatusTitle');
  var statusBody = document.getElementById('pageHeaderStatusBody');

  if (!form || !btnSave || !btnClear || !alertBox || !descInput || !apiUrl || !pageKey || !csrf) return;

  function showInlineAlert(type, msg) {
    if (!alertBox) return;
    alertBox.className = 'alert alert-' + type;
    alertBox.textContent = msg;
    alertBox.style.display = 'block';
  }

  function hideInlineAlert() {
    if (!alertBox) return;
    alertBox.style.display = 'none';
    alertBox.textContent = '';
  }

  function showStatus(type, title, msg) {
    hideInlineAlert();
    if (!statusModalEl || !window.bootstrap || !bootstrap.Modal) {
      window.alert((title ? title + ': ' : '') + msg);
      return;
    }

    statusHead.classList.remove('bg-success', 'bg-danger', 'bg-warning', 'text-white', 'text-dark');
    if (type === 'success') {
      statusHead.classList.add('bg-success', 'text-white');
    } else if (type === 'warning') {
      statusHead.classList.add('bg-warning', 'text-dark');
    } else {
      statusHead.classList.add('bg-danger', 'text-white');
    }

    statusTitle.textContent = title || 'Estado';
    statusBody.textContent = msg || '';

    var modal = bootstrap.Modal.getOrCreateInstance(statusModalEl);
    modal.show();
  }

  function setBusy(on) {
    btnSave.disabled = on;
    btnClear.disabled = on;
    if (fotoInput) fotoInput.disabled = on;
    if (descInput) descInput.disabled = on;
    btnSave.textContent = on ? 'Guardando...' : 'Guardar cambios';
  }

  function parseJsonSafe(text) {
    try {
      return JSON.parse(text);
    } catch (e) {
      return null;
    }
  }

  function formatBytes(bytes) {
    if (!isFinite(bytes) || bytes <= 0) return '0 B';
    var units = ['B', 'KB', 'MB', 'GB', 'TB'];
    var i = 0;
    var n = bytes;
    while (n >= 1024 && i < units.length - 1) {
      n = n / 1024;
      i++;
    }
    return (n >= 10 || i === 0 ? n.toFixed(0) : n.toFixed(1)) + ' ' + units[i];
  }

  function setPreview(src) {
    if (!previewImg || !previewEmpty) return;
    if (src && String(src).trim() !== '') {
      previewImg.src = src;
      previewImg.style.display = 'block';
      previewEmpty.style.display = 'none';
    } else {
      previewImg.removeAttribute('src');
      previewImg.style.display = 'none';
      previewEmpty.style.display = 'block';
    }
  }

  function resetProgress() {
    if (!progressWrap || !progressBar) return;
    progressWrap.style.display = 'none';
    progressBar.style.width = '0%';
    progressBar.textContent = '0%';
    progressBar.setAttribute('aria-valuenow', '0');
  }

  function setProgress(percent) {
    if (!progressWrap || !progressBar) return;
    progressWrap.style.display = 'block';
    progressBar.style.width = percent + '%';
    progressBar.textContent = percent + '%';
    progressBar.setAttribute('aria-valuenow', String(percent));
  }

  function getErrMsg(data, status) {
    if (data && typeof data === 'object') {
      if (data.error) return String(data.error);
      if (data.msg) return String(data.msg);
    }
    return 'No se pudo completar la solicitud (HTTP ' + status + ').';
  }

  function resolveItemImg(item) {
    if (!item || !item.imagen_portada) return defaultImg;
    return String(item.imagen_portada);
  }

  function resolveItemDesc(item) {
    if (!item || !item.descripcion || !String(item.descripcion).trim()) return 'Descripción pendiente.';
    return String(item.descripcion).trim();
  }

  function applyHeaderVisual(item) {
    var img = resolveItemImg(item);
    var desc = resolveItemDesc(item);

    block.dataset.currentimg = img;
    block.dataset.currentdesc = desc;

    block.style.background = "linear-gradient(rgba(15,66,41,.6), rgba(15,66,41,.6)), url('" + img.replace(/'/g, "\\'") + "') center center / cover no-repeat";

    var descNode = block.querySelector('.page-header-desc');
    if (descNode) descNode.textContent = desc;

    setPreview(img);
  }

  function validateSelectedFile() {
    if (!fotoInput || !fotoInput.files || !fotoInput.files.length) return true;

    var file = fotoInput.files[0];
    if (!file) return true;

    if (file.type && file.type.indexOf('image/') !== 0) {
      fotoInput.value = '';
      setPreview(currentImg || defaultImg);
      showStatus('error', 'Archivo no permitido', 'Selecciona un archivo de imagen válido.');
      return false;
    }

    if (maxBytes > 0 && file.size > maxBytes) {
      fotoInput.value = '';
      setPreview(currentImg || defaultImg);
      showStatus('warning', 'Archivo demasiado grande', 'La imagen seleccionada pesa ' + formatBytes(file.size) + '. El límite del servidor es ' + (maxLabel || formatBytes(maxBytes)) + '.');
      return false;
    }

    return true;
  }

  function sendRequest(formData, hasFile, done) {
    var xhr = new XMLHttpRequest();
    xhr.open('POST', apiUrl, true);
    xhr.timeout = 180000;

    xhr.onload = function () {
      var text = xhr.responseText || '';
      var data = parseJsonSafe(text);
      var ok = xhr.status >= 200 && xhr.status < 300 && data && data.ok;
      done(ok, data, xhr.status);
    };

    xhr.onerror = function () {
      done(false, { error: 'No se pudo conectar con el servidor. Verifica tu conexión.' }, xhr.status || 0);
    };

    xhr.ontimeout = function () {
      done(false, { error: 'La solicitud tardó demasiado. Intenta con una imagen más liviana o vuelve a intentar.' }, 0);
    };

    if (hasFile && xhr.upload) {
      xhr.upload.onprogress = function (e) {
        if (!e.lengthComputable) return;
        var p = Math.max(0, Math.min(100, Math.round((e.loaded / e.total) * 100)));
        setProgress(p);
      };
    }

    xhr.send(formData);
  }

  if (currentImg && currentImg.trim() !== '') {
    setPreview(currentImg);
  } else {
    setPreview(defaultImg);
  }

  resetProgress();

  if (fotoInput) {
    fotoInput.addEventListener('change', function () {
      hideInlineAlert();
      resetProgress();

      if (!validateSelectedFile()) return;

      if (!fotoInput.files || !fotoInput.files.length) {
        setPreview(currentImg || defaultImg);
        return;
      }

      var file = fotoInput.files[0];
      var src = URL.createObjectURL(file);
      setPreview(src);
    });
  }

  btnSave.addEventListener('click', function () {
    hideInlineAlert();
    resetProgress();

    if (!validateSelectedFile()) return;

    var fd = new FormData(form);
    fd.set('action', 'save');
    fd.set('page_key', pageKey);
    fd.set('csrf', csrf);

    var hasFile = !!(fotoInput && fotoInput.files && fotoInput.files.length > 0);

    setBusy(true);
    if (hasFile) setProgress(1);

    sendRequest(fd, hasFile, function (ok, data, status) {
      setBusy(false);
      if (hasFile) setProgress(100);

      if (!ok) {
        var msgErr = getErrMsg(data, status);
        showInlineAlert('danger', msgErr);
        showStatus('error', 'No se pudo guardar', msgErr);
        return;
      }

      var msg = data.msg || 'Portada actualizada correctamente.';
      applyHeaderVisual(data.item || null);
      currentImg = (data.item && data.item.imagen_portada) ? String(data.item.imagen_portada) : defaultImg;
      showInlineAlert('success', msg);
      showStatus('success', 'Cambios guardados', msg);

      if (fotoInput) fotoInput.value = '';
      resetProgress();
    });
  });

  btnClear.addEventListener('click', function () {
    hideInlineAlert();
    resetProgress();

    var confirmMsg = 'Se quitará la imagen personalizada y se usará la imagen por defecto. ¿Deseas continuar?';
    if (!window.confirm(confirmMsg)) return;

    var fd = new FormData();
    fd.set('action', 'clear_image');
    fd.set('page_key', pageKey);
    fd.set('csrf', csrf);

    setBusy(true);

    sendRequest(fd, false, function (ok, data, status) {
      setBusy(false);

      if (!ok) {
        var msgErr = getErrMsg(data, status);
        showInlineAlert('danger', msgErr);
        showStatus('error', 'No se pudo restablecer', msgErr);
        return;
      }

      var msg = data.msg || 'Se restableció la imagen por defecto.';
      applyHeaderVisual(data.item || null);
      currentImg = defaultImg;
      if (!descInput.value.trim()) {
        descInput.value = 'Descripción pendiente.';
      }
      if (fotoInput) fotoInput.value = '';
      showInlineAlert('success', msg);
      showStatus('success', 'Operacion exitosa', msg);
    });
  });
});
