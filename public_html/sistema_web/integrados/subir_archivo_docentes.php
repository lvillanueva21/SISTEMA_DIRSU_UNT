<!-- subir, ver y descargar docentes-->
<div class="card-body">
    <label>1. Lista de docentes que forman parte del equipo de trabajo </label>
    <br>
    <p>La lista se debe subir en formato Excel (.xls, .xlsx) para facilitar el tratamiento y filtrado de la información. <span style="color: red;">Solo puedes subir un archivo. Subir otro, reemplazará al anterior.</span></p>
    <div id="actions" class="row justify-content-center">
        <div class="col-lg-4">
            <div class="btn-group w-100">
                <span class="btn btn-success col fileinput-button">
                    <i class="fas fa-search"></i>
                    <span>Buscar archivo en mi computadora</span>
                </span>
            </div>
        </div>
<div class="col-lg-0 d-flex align-items-center" style="visibility: hidden;">
    <div class="fileupload-process w-100">
        <div id="total-progress" class="progress progress-striped active" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
        </div>
    </div>
</div>
    </div>
    <div class="table table-striped files" id="docentes">
        <div id="template" class="row mt-2">
            <div class="col-auto">
                <span class="preview"><img src="data:," alt="" data-dz-thumbnail /></span>
            </div>
            <div class="col d-flex align-items-center">
                <p class="mb-0">
                    <span class="lead" data-dz-name></span>
                    (<span data-dz-size></span>)
                </p>
                <strong class="error text-danger" data-dz-errormessage></strong>
            </div>
            <div class="col-4 d-flex align-items-center">
                <div class="progress progress-striped active w-100" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="0">
                    <div class="progress-bar progress-bar-success" style="width:0%;" data-dz-uploadprogress></div>
                </div>
            </div>
            <div class="col-auto d-flex align-items-center">
                <div class="btn-group">
                    <button class="btn btn-primary start">
                        <i class="fas fa-upload"></i>
                        <span>Subir al sistema</span>
                    </button>
                    <button data-dz-remove class="btn btn-warning cancel">
                        <i class="fas fa-times-circle"></i>
                        <span>Cancelar</span>
                    </button>
                    <button data-dz-remove class="btn btn-danger delete">
                        <i class="fas fa-trash"></i>
                        <span>Borrar</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
    <br>
<!-- PRUEBA VER Y DESCARGAR ARCHIVO -->
<?php
   // Incluir el archivo que descargar lista docentes
   include('../componentes/archivo/ver_lista_docentes.php');
?>
<!-- .PRUEBA VER Y DESCARGAR ARCHIVO-->
</div>
<!-- .subir, ver y descargar docentes-->
      <!-- dropzonejs -->
<script src="../plogins/dropzone/min/dropzone.min.js"></script>
<script>
    // DropzoneJS Demo Code Start
    Dropzone.autoDiscover = false;

    // Get the template HTML and remove it from the document
    var previewNode = document.querySelector("#template");
    previewNode.id = "";
    var previewTemplate = previewNode.parentNode.innerHTML;
    previewNode.parentNode.removeChild(previewNode);

    var myDropzone = new Dropzone(document.body, { // Make the whole body a dropzone
        url: "../componentes/archivo/subir_lista_docentes.php", // Set the url
        thumbnailWidth: 80,
        thumbnailHeight: 80,
        parallelUploads: 1, // Limitar a 1 archivo
        previewTemplate: previewTemplate,
        autoQueue: false, // Make sure the files aren't queued until manually added
        previewsContainer: "#docentes", // Define the container to display the docentes
        clickable: ".fileinput-button", // Define the element that should be used as click trigger to select files.
        acceptedFiles: ".xls,.xlsx", // Aceptar solo archivos Excel
    });

    myDropzone.on("addedfile", function(file) {
        // Si ya hay un archivo, elimina el anterior
        if (myDropzone.files.length > 1) {
            myDropzone.removeFile(myDropzone.files[0]);
        }
        // Hookup the start button
        file.previewElement.querySelector(".start").onclick = function() { myDropzone.enqueueFile(file) }
    });

    // Update the total progress bar
    myDropzone.on("totaluploadprogress", function(progress) {
        document.querySelector("#total-progress .progress-bar").style.width = progress + "%";
    });

    myDropzone.on("sending", function(file) {
        document.querySelector("#total-progress").style.opacity = "1";
        file.previewElement.querySelector(".start").setAttribute("disabled", "disabled");
    });

    myDropzone.on("queuecomplete", function(progress) {
        document.querySelector("#total-progress").style.opacity = "0";
    });

    // Agregar evento de éxito
    myDropzone.on("success", function(file, response) {
        var fileSize = (file.size / 1024).toFixed(2); // Tamaño en KB
        alert("Archivo subido con éxito al sistema: " + file.name + " (Tamaño: " + fileSize + " KB)");
    location.reload();
        
    });

    document.querySelector("#actions .start").onclick = function() {
        myDropzone.enqueueFiles(myDropzone.getFilesWithStatus(Dropzone.ADDED));
    };

    document.querySelector("#actions .cancel").onclick = function() {
        myDropzone.removeAllFiles(true);
    };
    
    // DropzoneJS Demo Code End
</script>