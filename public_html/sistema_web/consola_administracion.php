<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ruta de Proyecto</title>
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet" />
  <!-- Bootstrap Icons -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" />
  <style>
    /* Tabla 1 sin bordes internos, solo borde externo redondeado */
    .custom-table {
      border: 2px solid #000;
      border-radius: 10px;
      overflow: hidden;
    }
    .custom-table td, .custom-table th {
      border: none !important;
    }


    /* Bordes negros en todas las celdas internas de los divs */
    .inner-table td {
      border: 1px solid #000;
    }


    /* Estilo para ocultar celda de flecha */
    .arrow-cell {
      border: none !important;
    }


    .arrow-icon {
      font-size: 2.5rem;
      color: #CDEB8B;
    }
  </style>
</head>
<body>


<div class="container mt-5">
  <div class="card p-3 shadow">


    <!-- Tabla 1 sin bordes internos, con 4 celdas en fila 2 -->
    <table class="w-100 mb-4 shadow custom-table">
      <tr>
        <td colspan="2"><strong>Nombre completo Nombre completo</strong></td>
        <td colspan="2">
          <em>Proyecto título de ejemplo - Proyecto título de ejemplo - Proyecto título de ejemplo</em><br>
          Código usuario: <strong>3006</strong> &nbsp;&nbsp; Código proyecto: <strong>354</strong>
        </td>
      </tr>
      <tr>
        <td>
          <span class="badge text-white" style="background-color:#563D7C">Ciencias Sociales</span>
        </td>
        <td>
          <span class="badge text-dark" style="background-color:#D4FF00">Ciencias Sociales</span>
        </td>
        <td></td>
        <td></td>
      </tr>
    </table>


    <!-- Tabla 2 sin bordes -->
    <table class="w-100">
      <!-- Fila 1 -->
      <tr>
        <td style="width:25%">
          <button class="btn text-white w-100 shadow" style="background-color:#0275D8">
            Oficina del Comité de Facultad
          </button>
        </td>
        <td class="text-center align-middle arrow-cell" style="width:10%">
          <i class="bi bi-arrow-right-circle-fill arrow-icon"></i>
        </td>
        <td colspan="2" style="width:65%">
          <div class="shadow" style="border:1px solid #000;">
            <table class="w-100 inner-table">
              <tr>
                <td style="width:40%">Desde 15-04-2025</td>
                <td style="background-color:#0050EF; color:white; text-align:center;">
                  En Espera
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  - Usuario @nombre_completo ha solicitado REVISIÓN por LISTA DE COTEJO y RÚBRICA de su proyecto titulado @titulo.<br>
                  - Proyecto EN ESPERA de REVISIÓN por parte del Presidente del Comité de la Facultad de @facultad desde el día @15-04-2025 a las @18:09:00.
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>


      <!-- Fila 2 -->
      <tr class="mt-2">
        <td>
          <button class="btn w-100 shadow" style="background-color:#F0AD4E; color:#000;">
            Oficina de la Dirección de Departamento
          </button>
        </td>
        <td class="text-center align-middle arrow-cell">
          <i class="bi bi-arrow-right-circle-fill arrow-icon"></i>
        </td>
        <td colspan="2">
          <div class="shadow" style="border:1px solid #000;">
            <table class="w-100 inner-table">
              <tr>
                <td style="width:40%">Desde 17-04-2025</td>
                <td style="background-color:#0050EF; color:white; text-align:center;">
                  En Espera
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  - Usuario @nombre_completo necesita el VISTO BUENO de su proyecto titulado @titulo.<br>
                  - Proyecto EN ESPERA de VISTO BUENO por parte del Director de Departamento de @departamento desde el día @15-04-2025 a las @18:09:00.
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>


      <!-- Fila 3 -->
      <tr class="mt-2">
        <td>
          <button class="btn w-100 shadow" style="background-color:#5BC0DE; color:#000;">
            Oficina del Decanato de Facultad
          </button>
        </td>
        <td class="text-center align-middle arrow-cell">
          <i class="bi bi-arrow-right-circle-fill arrow-icon"></i>
        </td>
        <td colspan="2">
          <div class="shadow" style="border:1px solid #000;">
            <table class="w-100 inner-table">
              <tr>
                <td style="width:40%">Desde 19-04-2025</td>
                <td style="background-color:#0050EF; color:white; text-align:center;">
                  En Espera
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  - Usuario @nombre_completo necesita el VISTO BUENO de su proyecto titulado @titulo.<br>
                  - Proyecto EN ESPERA de VISTO BUENO por parte del Decano de la Facultad de @facultad desde el día @15-04-2025 a las @18:09:00.
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>


      <!-- Fila 4 -->
      <tr class="mt-2">
        <td>
          <button class="btn text-white w-100 shadow" style="background-color:#5CB85C;">
            Dirección de RSU - UNT
          </button>
        </td>
        <td class="text-center align-middle arrow-cell">
          <i class="bi bi-arrow-right-circle-fill arrow-icon"></i>
        </td>
        <td colspan="2">
          <div class="shadow" style="border:1px solid #000;">
            <table class="w-100 inner-table">
              <tr>
                <td style="width:40%">Desde 21-04-2025</td>
                <td style="background-color:#0050EF; color:white; text-align:center;">
                  En Espera
                </td>
              </tr>
              <tr>
                <td colspan="2">
                  - Usuario @nombre_completo necesita REVISIÓN de su proyecto titulado @titulo.<br>
                  - Proyecto EN ESPERA de REVISIÓN por parte de la Dirección de Responsabilidad Social Universitaria desde el día @15-04-2025 a las @18:09:00.
                </td>
              </tr>
            </table>
          </div>
        </td>
      </tr>


    </table>
  </div>
</div>


</body>
</html>