<div class="card card-solid">
    <div class="card-body">
        <div class="row">
            <div class="col-12 col-sm-6">
                <h3 class="d-inline-block d-sm-none">LOWA Men’s Renegade GTX Mid Hiking Boots Review</h3>
                <div class="col-12">
                    <img src="../imagenes/registrar_proyecto.jpg" class="product-image" alt="Product Image">
                </div>
            </div>
            <div class="col-12 col-sm-6">
                <div class="text-center text-sm-left my-4">
                    <h3 class="mb-3">¡Es hora de comenzar tu primer proyecto!</h3>
                    <p class="mb-4">No hemos encontrado proyectos registrados en tu cuenta. ¡No te preocupes! Solo haz clic en "Registrar proyecto" y empieza a darle forma a tu idea.</p>
                </div>
                
                <div class="d-flex justify-content-center justify-content-sm-start mt-4">
                    <div class="mr-2">
                        <button id="crearProyectoBtn" class="btn btn-primary btn-lg btn-flat">
                            <i class="fas fa-folder-plus fa-lg mr-2"></i>
                            Registrar nuevo proyecto
                        </button>
                    </div>

                    <div>
                        <button id="infoBtn" class="btn btn-default btn-lg btn-flat">
                            <i class="fas fa-info-circle fa-lg mr-2"></i>
                            Más información
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- /.card-body -->
</div>
<!-- /.card -->

<!-- Modal -->
<div class="modal fade" id="infoModal" tabindex="-1" aria-labelledby="infoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-secondary">
                <h5 class="modal-title" id="infoModalLabel">Importante</h5>
                <button type="button" class="close" data-bs-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
            </div>
            <div class="modal-body">
                <p><div align="justify" style="padding-right: 40px;">
  <ul>
    <li>
      Al registrar un nuevo proyecto, podrás ingresar tu información en las pestañas: 
      <b>Generalidades</b>, <b>Plan de proyecto</b> y <b>Anexos</b>. 📝
    </li>
    <li>
      El sistema ofrece la facilidad de actualizar cualquier información ingresada del proyecto.
    </li>
    <li>
      Podrás consultar toda la información que subiste en la pestaña <b>Mi proyecto</b>. ✅
    </li>
    <li>
      Al terminar de subir la información, deberás solicitar una revisión en la pestaña <b>Mi progreso</b>. 🔍
    </li>
    <li>
      Para subir tu proyecto, hay una fecha límite que <b>DIRSU</b> comunicará a los coordinadores de proyecto. 📅
    </li>
  </ul>
</div>

</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<!-- JQuery y Bootstrap JS -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function() {
    $('#crearProyectoBtn').on('click', function() {
        $.ajax({
            url: '../componentes/proyecto/crear_proyecto.php',
            type: 'POST',
            data: { crear_proyecto: true },
            success: function(response) {
                alert('Nuevo proyecto creado y asignado exitosamente.');
                window.location.href = '../../sistema_web/vistas/datos_principales.php'; // Redirigir a la página de inicio
            },
            error: function(xhr, status, error) {
                alert('Error: ' + error);
            }
        });
    });

    // Mostrar el modal cuando se haga clic en el botón "Más información"
    $('#infoBtn').on('click', function() {
        $('#infoModal').modal('show');
    });
});
</script>
