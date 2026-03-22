<?php
    // Incluir configSesion.php para verificar la sesiÃ³n
    include "../componentes/configSesion.php"; 
    // Incluir la conexiÃ³n a la base de datos
    include('../componentes/db.php');
    // Incluir el archivo que carga los datos del proyecto
    include('../componentes/proyecto/cargar_proyecto.php');
?>
<!DOCTYPE html>
<html lang="en">
   <head>
      <meta charset="utf-8">
      <meta name="viewport" content="width=device-width, initial-scale=1">
      <title>Mi Perfil - Sistema DIRSU</title>
      <!-- Favicon -->
      <link href="../imagenes/dirsu_128_128.ico" rel="icon">
      <!-- Google Font: Source Sans Pro -->
      <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
      <!-- Font Awesome -->
      <link rel="stylesheet" href="../plogins/fontawesome-free/css/all.min.css">
      <!-- Select2 -->
      <link rel="stylesheet" href="../plogins/select2/css/select2.min.css">
      <link rel="stylesheet" href="../plogins/select2-bootstrap4-theme/select2-bootstrap4.min.css">
      <!-- Bootstrap4 Duallistbox -->
      <link rel="stylesheet" href="../plogins/bootstrap4-duallistbox/bootstrap-duallistbox.min.css">
      <!-- BS Stepper -->
      <link rel="stylesheet" href="../plogins/bs-stepper/css/bs-stepper.min.css">
      <!-- Theme style -->
      <link rel="stylesheet" href="../dust/css/adminlte.min.css">
   </head>
   <body class="hold-transition sidebar-mini layout-fixed">
      <div class="wrapper">
         <!-- Preloader --><!-- Icono que se muestra mientras estÃ¡ cargando el sistema -->
         <div class="preloader flex-column justify-content-center align-items-center">
            <img class="animation__shake" src="../dust/img/dirsu_logo_128_128.png" alt="AdminLTELogo" height="60" width="60">
         </div>
         <!-- Navbar -->
         <nav class="main-header navbar navbar-expand navbar-white navbar-light">
            <!-- Left navbar links -->
            <ul class="navbar-nav">
               <li class="nav-item">
                  <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
               </li>
            </ul>
            <!-- Right navbar links -->
            <ul class="navbar-nav ml-auto">
               <li class="nav-item">
                  <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                  <i class="fas fa-expand-arrows-alt"></i>
                  </a>
               </li>
               <li class="nav-item d-none d-sm-inline-block" style="background-image: url('../web1.png'); background-size: cover; background-position: center; color: white; padding: 2px; list-style-type: none; filter: brightness(100%); text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.6);">
                  <a href="https://rsu.unitru.edu.pe/" class="nav-link" target="_blank">
                     <p style="color: white;
                        size: 8px">Ir a pÃ¡gina DIRSU</p>
                  </a>
               </li>
               </li>
               <li class="nav-item d-none d-sm-inline-block">
                  <a href="../componentes/sesion/cerrarSesion.php" class="nav-link">Cerrar sesiÃ³n</a> 
               </li>
            </ul>
         </nav>
         <!-- /.navbar -->
         <!-- Main Sidebar Container -->
         <!-- Contenedor de barra lateral principal -->
                 <?php include_once "../includes/sidebar.php"; ?>
         <!-- Content Wrapper. Contains page content -->
         <div class="content-wrapper">
            <!-- Content Header (Page header) -->
            <div class="content-header">
               <div class="container-fluid">
                  <div class="row mb-0">
                     <div class="col-sm-7">
                        <h1 class="m-0">Mi perfil</h1>
                     </div>
                     <!-- /.col -->
                     <!-- /.col -->
                  </div>
                  <!-- /.row -->
               </div>
               <!-- /.container-fluid -->
            </div>
            <!-- Main content -->
            <!-- Main content -->
            <section class="content">
               <div class="container-fluid">
                  <div class="row">
                     <div class="col-md-5">
                        <!-- Profile Image -->
                        <div class="card card-primary card-outline">
                           <div class="card-body box-profile">
                              <div class="text-center">
                                 <img class="profile-user-img img-fluid img-circle"
                                    src="../dust/img/avatar.png"
                                    alt="User profile picture">
                              </div>
                              <h3 class="profile-username text-center"><?php echo htmlspecialchars($nombres) . " " . htmlspecialchars($apellidos); ?></h3>
                              <p class="text-muted text-center">Coordinador de Proyecto</p>
                              <ul class="list-group list-group-unbordered mb-3">
                                 <li class="list-group-item">
                                    <b>Departamento AcadÃ©mico</b> <a class="float-right"><?php
                                    // Array de departamentos acadÃ©micos
                                    $departamentos_academicos = [
                                        '1' => 'AgronomÃ­a y Zootecnia',
                                        '2' => 'Ciencias Agroindustriales',
                                        '3' => 'Ciencias BiolÃ³gicas',
                                        '4' => 'MicrobiologÃ­a y ParasitologÃ­a',
                                        '5' => 'PesquerÃ­a',
                                        '6' => 'QuÃ­mica BiolÃ³gica y FisiologÃ­a Animal',
                                        '7' => 'AdministraciÃ³n',
                                        '8' => 'Contabilidad y Finanzas',
                                        '9' => 'EconomÃ­a',
                                        '10' => 'Ciencias BÃ¡sicas EstomatolÃ³gicas',
                                        '11' => 'EstomatologÃ­a',
                                        '12' => 'EstadÃ­stica',
                                        '13' => 'FÃ­sica',
                                        '14' => 'InformÃ¡tica',
                                        '15' => 'MatemÃ¡ticas',
                                        '16' => 'ArqueologÃ­a y AntropologÃ­a',
                                        '17' => 'Ciencias Sociales',
                                        '18' => 'Ciencias JurÃ­dicas PÃºblicas y PolÃ­ticas',
                                        '19' => 'Ciencias JurÃ­dicas Privadas y Sociales',
                                        '20' => 'Ciencia PolÃ­tica y Gobernabilidad',
                                        '21' => 'Ciencias de la EducaciÃ³n',
                                        '22' => 'Ciencias PsicolÃ³gicas',
                                        '23' => 'ComunicaciÃ³n Social',
                                        '24' => 'FilosofÃ­a y Arte',
                                        '25' => 'Historia y GeografÃ­a',
                                        '26' => 'Idiomas y LingÃ¼Ã­stica',
                                        '27' => 'Lengua Nacional y Literatura',
                                        '28' => 'EnfermerÃ­a de la Mujer, NiÃ±o y Adolescente',
                                        '29' => 'Salud del Adulto',
                                        '30' => 'Salud Familiar y Comunitaria',
                                        '31' => 'Farmacotecnia',
                                        '32' => 'FarmacologÃ­a',
                                        '33' => 'BioquÃ­mica',
                                        '34' => 'IngenierÃ­a Civil, Arquitectura y Urbanismo',
                                        '35' => 'IngenierÃ­a Industrial',
                                        '36' => 'IngenierÃ­a de Materiales',
                                        '37' => 'MecÃ¡nica y EnergÃ­a',
                                        '38' => 'IngenierÃ­a MetalÃºrgica',
                                        '39' => 'IngenierÃ­a de Minas',
                                        '40' => 'IngenierÃ­a de Sistemas',
                                        '41' => 'IngenierÃ­a QuÃ­mica',
                                        '42' => 'IngenierÃ­a Ambiental',
                                        '43' => 'QuÃ­mica',
                                        '44' => 'Ciencias BÃ¡sicas MÃ©dicas',
                                        '45' => 'CirugÃ­a',
                                        '46' => 'FisiologÃ­a Humana',
                                        '47' => 'GinecologÃ­a-Obstetricia',
                                        '48' => 'Medicina',
                                        '49' => 'Medicina Preventiva y Salud PÃºblica',
                                        '50' => 'MorfologÃ­a Humana',
                                        '51' => 'PediatrÃ­a',
                                    ];
                                    
                                    // Verificar si el departamento acadÃ©mico existe y mostrarlo
                                    if (isset($id_depa) && isset($departamentos_academicos[$id_depa])) {
                                        echo htmlspecialchars($departamentos_academicos[$id_depa]);
                                    } else {
                                        echo '<h5>No hay departamento acadÃ©mico seleccionado</h5>';
                                    }
                                    ?></a>
                                 </li>
                                 <li class="list-group-item">
                                    <b>Facultad</b> <a class="float-right"><?php echo htmlspecialchars($facultad_de_depa); ?></a>
                                 </li>
                                 <li class="list-group-item">
                                    <b>Mi proyecto actual</b> <a class="float-right"><?php
echo (!empty($p2) ? $p2 : "</b> aÃºn no has registrado el tÃ­tulo de tu proyecto");
?></a>
                                 </li>
                              </ul>
                           </div>
                           <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                     </div>
                     <!-- /.col -->
                     <div class="col-md-7">
                        <div class="card">
                           <div class="card-header p-2">
                              <ul class="nav nav-pills">
                                 <li class="nav-item"><a class="nav-link" href="#dpersonales" data-toggle="tab">Datos Personales</a></li>
                                 <li class="nav-item"><a class="nav-link active" href="#dacademicos" data-toggle="tab">Datos AcadÃ©micos</a></li>
                              </ul>
                           </div>
                           <div class="card-body">
                              <div class="tab-content">
                                 <!-- Tab Datos Personales -->
                                 <div class="tab-pane" id="dpersonales">
                                    <form class="form-horizontal" method="POST" action="../componentes/perfil/actualizar_dpersonales.php">
                                        <div class="form-group row">
                                           <div class="col-sm-12">
                                           <div class="alert alert-info alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                  <h5><i class="icon fas fa-info"></i> A tener en cuenta</h5>
                  Puedes editar uno o varios datos a la vez. Las casillas en gris son automÃ¡ticas, no se pueden editar manualmente.
                </div>
                                           </div>
                                           </div>
                                       <div class="form-group row">
                                          <label for="inputNombresPerfil" class="col-sm-3 col-form-label">Nombres</label>
                                          <div class="col-sm-9">
                                             <input type="text" class="form-control" id="NombresPerfil" name="NombresPerfil" placeholder="Ingresar nombre(s)" value="<?php echo htmlspecialchars($nombres); ?>" required>
                                          </div>
                                       </div>
                                       <div class="form-group row">
                                          <label for="inputApellidosPerfil" class="col-sm-3 col-form-label">Apellidos</label>
                                          <div class="col-sm-9">
                                             <input type="text" class="form-control" id="ApellidosPerfil" name="ApellidosPerfil" placeholder="Ingresar Apellido(s)" value="<?php echo htmlspecialchars($apellidos); ?>" required>
                                          </div>
                                       </div>
                                       <div class="form-group row">
                                          <label for="inputUsuarioPerfil" class="col-sm-3 col-form-label">Usuario <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="Para actualizar tu usuario, escrÃ­benos a dirsu@unitru.edu.pe">
                                          <i class="fa fa-info-circle"></i>
                                          </button> <br>(CÃ³digo docente)</label>
                                          <div class="col-sm-9">
                                             <input type="text" class="form-control" id="UsuarioPerfil" name="UsuarioPerfil" placeholder="Ingresar Usuario" value="<?php echo htmlspecialchars($usuario); ?>" required readonly>
                                          </div>
                                       </div>
                                       <div class="form-group row">
                                          <label for="inputClavePerfil" class="col-sm-3 col-form-label">Nueva Clave</label>
                                          <div class="col-sm-9">
                                             <div class="input-group">
                                                <input type="password" class="form-control" id="ClavePerfil" name="ClavePerfil" placeholder="Ingresa una nueva Clave" oninput="showPasswordMessage()">
                                                <div class="input-group-append">
                                                   <button class="btn btn-outline-secondary" type="button" onclick="togglePassword('ClavePerfil')">
                                                   <i class="fa fa-eye" id="toggleClaveIcon"></i>
                                                   </button>
                                                </div>
                                             </div>
                                             <!-- Mensaje que se mostrarÃ¡ cuando el usuario escriba en la clave -->
                                             <small id="passwordMessage" class="form-text text-danger" style="display:none;">
                                             EstÃ¡s escribiendo una nueva clave, si presionas <b>Actualizar</b>, tu clave cambiarÃ¡.
                                             </small>
                                          </div>
                                       </div>
                                       <div class="form-group row">
                                          <div class="offset-sm-2 col-sm-10">
                                             <button class="btn btn-primary">Actualizar</button>
                                          </div>
                                       </div>
                                    </form>
                                 </div>
                                 <!-- .Tab Datos Personales -->
                                 <!-- Tab Datos AcadÃ©micos -->
                                 <div class="active tab-pane" id="dacademicos">
                                    <form class="form-horizontal" method="POST" action="../componentes/perfil/actualizar_dacademicos.php">
                                       <div class="form-group row">
                                           <div class="col-sm-12">
                                           <div class="alert alert-warning alert-dismissible">
                  <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
                  <h5><i class="icon fas fa-exclamation-triangle"></i> !Actualiza tu sede y departamento!</h5>
                  Puede que tu Sede y Departamento no sean los correctos. Por favor, revisa y actualiza estos datos. Si son correctos, ignora este mensaje.
                </div>
                                           </div>
                                           </div>
                                       <div class="form-group row">
                                          <label for="inputSedePerfil" class="col-sm-3 col-form-label">Sede</label>
                                          <div class="col-sm-9">
                                             <select class="form-control" id="id_sede" name="id_sede" required>
                                                <option value="">Seleccione una opciÃ³n</option>
                                                <option value="1" <?php echo (isset($id_sede) && $id_sede == '1') ? 'selected' : ''; ?>>Trujillo</option>
                                                <option value="2" <?php echo (isset($id_sede) && $id_sede == '2') ? 'selected' : ''; ?>>Jequetepeque</option>
                                                <option value="3" <?php echo (isset($id_sede) && $id_sede == '3') ? 'selected' : ''; ?>>Huamachuco</option>
                                                <option value="4" <?php echo (isset($id_sede) && $id_sede == '4') ? 'selected' : ''; ?>>Santiago de Chuco</option>
                                             </select>
                                          </div>
                                       </div>
                                       <div class="form-group row">
                                          <label for="inputDepaPerfil" class="col-sm-3 col-form-label">Departamento AcadÃ©mico</label>
                                          <div class="col-sm-9">
                                             <select class="form-control" id="id_depa" name="id_depa" required>
                                                <option value="">Seleccione una opciÃ³n</option>
                                                <option value="1" <?php echo (isset($id_depa) && $id_depa == '1') ? 'selected' : ''; ?>>AgronomÃ­a y Zootecnia</option>
                                                <option value="2" <?php echo (isset($id_depa) && $id_depa == '2') ? 'selected' : ''; ?>>Ciencias Agroindustriales</option>
                                                <option value="3" <?php echo (isset($id_depa) && $id_depa == '3') ? 'selected' : ''; ?>>Ciencias BiolÃ³gicas</option>
                                                <option value="4" <?php echo (isset($id_depa) && $id_depa == '4') ? 'selected' : ''; ?>>MicrobiologÃ­a y ParasitologÃ­a</option>
                                                <option value="5" <?php echo (isset($id_depa) && $id_depa == '5') ? 'selected' : ''; ?>>PesquerÃ­a</option>
                                                <option value="6" <?php echo (isset($id_depa) && $id_depa == '6') ? 'selected' : ''; ?>>QuÃ­mica BiolÃ³gica y FisiologÃ­a Animal</option>
                                                <option value="7" <?php echo (isset($id_depa) && $id_depa == '7') ? 'selected' : ''; ?>>AdministraciÃ³n</option>
                                                <option value="8" <?php echo (isset($id_depa) && $id_depa == '8') ? 'selected' : ''; ?>>Contabilidad y Finanzas</option>
                                                <option value="9" <?php echo (isset($id_depa) && $id_depa == '9') ? 'selected' : ''; ?>>EconomÃ­a</option>
                                                <option value="10" <?php echo (isset($id_depa) && $id_depa == '10') ? 'selected' : ''; ?>>Ciencias BÃ¡sicas EstomatolÃ³gicas</option>
                                                <option value="11" <?php echo (isset($id_depa) && $id_depa == '11') ? 'selected' : ''; ?>>EstomatologÃ­a</option>
                                                <option value="12" <?php echo (isset($id_depa) && $id_depa == '12') ? 'selected' : ''; ?>>EstadÃ­stica</option>
                                                <option value="13" <?php echo (isset($id_depa) && $id_depa == '13') ? 'selected' : ''; ?>>FÃ­sica</option>
                                                <option value="14" <?php echo (isset($id_depa) && $id_depa == '14') ? 'selected' : ''; ?>>InformÃ¡tica</option>
                                                <option value="15" <?php echo (isset($id_depa) && $id_depa == '15') ? 'selected' : ''; ?>>MatemÃ¡ticas</option>
                                                <option value="16" <?php echo (isset($id_depa) && $id_depa == '16') ? 'selected' : ''; ?>>ArqueologÃ­a y AntropologÃ­a</option>
                                                <option value="17" <?php echo (isset($id_depa) && $id_depa == '17') ? 'selected' : ''; ?>>Ciencias Sociales</option>
                                                <option value="18" <?php echo (isset($id_depa) && $id_depa == '18') ? 'selected' : ''; ?>>Ciencias JurÃ­dicas PÃºblicas y PolÃ­ticas</option>
                                                <option value="19" <?php echo (isset($id_depa) && $id_depa == '19') ? 'selected' : ''; ?>>Ciencias JurÃ­dicas Privadas y Sociales</option>
                                                <option value="20" <?php echo (isset($id_depa) && $id_depa == '20') ? 'selected' : ''; ?>>Ciencia PolÃ­tica y Gobernabilidad</option>
                                                <option value="21" <?php echo (isset($id_depa) && $id_depa == '21') ? 'selected' : ''; ?>>Ciencias de la EducaciÃ³n</option>
                                                <option value="22" <?php echo (isset($id_depa) && $id_depa == '22') ? 'selected' : ''; ?>>Ciencias PsicolÃ³gicas</option>
                                                <option value="23" <?php echo (isset($id_depa) && $id_depa == '23') ? 'selected' : ''; ?>>ComunicaciÃ³n Social</option>
                                                <option value="24" <?php echo (isset($id_depa) && $id_depa == '24') ? 'selected' : ''; ?>>FilosofÃ­a y Arte</option>
                                                <option value="25" <?php echo (isset($id_depa) && $id_depa == '25') ? 'selected' : ''; ?>>Historia y GeografÃ­a</option>
                                                <option value="26" <?php echo (isset($id_depa) && $id_depa == '26') ? 'selected' : ''; ?>>Idiomas y LingÃ¼Ã­stica</option>
                                                <option value="27" <?php echo (isset($id_depa) && $id_depa == '27') ? 'selected' : ''; ?>>Lengua Nacional y Literatura</option>
                                                <option value="28" <?php echo (isset($id_depa) && $id_depa == '28') ? 'selected' : ''; ?>>EnfermerÃ­a de la Mujer, NiÃ±o y Adolescente</option>
                                                <option value="29" <?php echo (isset($id_depa) && $id_depa == '29') ? 'selected' : ''; ?>>Salud del Adulto</option>
                                                <option value="30" <?php echo (isset($id_depa) && $id_depa == '30') ? 'selected' : ''; ?>>Salud Familiar y Comunitaria</option>
                                                <option value="31" <?php echo (isset($id_depa) && $id_depa == '31') ? 'selected' : ''; ?>>Farmacotecnia</option>
                                                <option value="32" <?php echo (isset($id_depa) && $id_depa == '32') ? 'selected' : ''; ?>>FarmacologÃ­a</option>
                                                <option value="33" <?php echo (isset($id_depa) && $id_depa == '33') ? 'selected' : ''; ?>>BioquÃ­mica</option>
                                                <option value="34" <?php echo (isset($id_depa) && $id_depa == '34') ? 'selected' : ''; ?>>IngenierÃ­a Civil, Arquitectura y Urbanismo</option>
                                                <option value="35" <?php echo (isset($id_depa) && $id_depa == '35') ? 'selected' : ''; ?>>IngenierÃ­a Industrial</option>
                                                <option value="36" <?php echo (isset($id_depa) && $id_depa == '36') ? 'selected' : ''; ?>>IngenierÃ­a de Materiales</option>
                                                <option value="37" <?php echo (isset($id_depa) && $id_depa == '37') ? 'selected' : ''; ?>>MecÃ¡nica y EnergÃ­a</option>
                                                <option value="38" <?php echo (isset($id_depa) && $id_depa == '38') ? 'selected' : ''; ?>>IngenierÃ­a MetalÃºrgica</option>
                                                <option value="39" <?php echo (isset($id_depa) && $id_depa == '39') ? 'selected' : ''; ?>>IngenierÃ­a de Minas</option>
                                                <option value="40" <?php echo (isset($id_depa) && $id_depa == '40') ? 'selected' : ''; ?>>IngenierÃ­a de Sistemas</option>
                                                <option value="41" <?php echo (isset($id_depa) && $id_depa == '41') ? 'selected' : ''; ?>>IngenierÃ­a QuÃ­mica</option>
                                                <option value="42" <?php echo (isset($id_depa) && $id_depa == '42') ? 'selected' : ''; ?>>IngenierÃ­a Ambiental</option>
                                                <option value="43" <?php echo (isset($id_depa) && $id_depa == '43') ? 'selected' : ''; ?>>QuÃ­mica</option>
                                                <option value="44" <?php echo (isset($id_depa) && $id_depa == '44') ? 'selected' : ''; ?>>Ciencias BÃ¡sicas MÃ©dicas</option>
                                                <option value="45" <?php echo (isset($id_depa) && $id_depa == '45') ? 'selected' : ''; ?>>CirugÃ­a</option>
                                                <option value="46" <?php echo (isset($id_depa) && $id_depa == '46') ? 'selected' : ''; ?>>FisiologÃ­a Humana</option>
                                                <option value="47" <?php echo (isset($id_depa) && $id_depa == '47') ? 'selected' : ''; ?>>GinecologÃ­a-Obstetricia</option>
                                                <option value="48" <?php echo (isset($id_depa) && $id_depa == '48') ? 'selected' : ''; ?>>Medicina</option>
                                                <option value="49" <?php echo (isset($id_depa) && $id_depa == '49') ? 'selected' : ''; ?>>Medicina Preventiva y Salud PÃºblica</option>
                                                <option value="50" <?php echo (isset($id_depa) && $id_depa == '50') ? 'selected' : ''; ?>>MorfologÃ­a Humana</option>
                                                <option value="51" <?php echo (isset($id_depa) && $id_depa == '51') ? 'selected' : ''; ?>>PediatrÃ­a</option>
                                             </select>
                                          </div>
                                       </div>
                                       <div class="form-group row">
        <label for="inputEscuelaPerfil" class="col-sm-3 col-form-label">Facultad
            <button type="button" class="btn btn-link p-0" data-toggle="tooltip" data-placement="right" title="La facultad dependerÃ¡ del departamento seleccionado.">
                <i class="fa fa-info-circle"></i>
            </button>
        </label>
        <div class="col-sm-9">
            <!-- Campo para mostrar el nombre de la facultad -->
            <input type="text" class="form-control" id="FacultadPerfil" name="FacultadPerfil" value="<?php echo htmlspecialchars($facultad_de_depa); ?>" readonly>
        </div>
    </div>
                                       <div class="form-group row">
                                          <div class="offset-sm-2 col-sm-10">
                                             <button type="submit" class="btn btn-primary">Actualizar</button>
                                          </div>
                                       </div>
                                    </form>
                                 </div>
                                 <!-- .Tab Datos AcadÃ©micos -->
                              </div>
                              <!-- /.tab-content -->
                           </div>
                           <!-- /.card-body -->
                        </div>
                        <!-- /.card -->
                     </div>
                     <!-- /.col -->
                  </div>
                  <!-- /.row -->
               </div>
               <!-- /.container-fluid -->
            </section>
            <!-- /.content -->
            <!-- /.content -->
         </div>
         <!-- /.content-wrapper -->
         <footer class="main-footer">
            <strong>Â© 2024 Universidad Nacional de Trujillo. Todos los derechos reservados.</strong>
            <div class="float-right d-none d-sm-inline-block">
               <p>Desarrollado por el <a href="#"> Ãrea  informÃ¡tica - DIRSU</a></p>
            </div>
         </footer>
         <!-- Control Sidebar -->
         <aside class="control-sidebar control-sidebar-dark">
            <!-- Control sidebar content goes here -->
         </aside>
         <!-- /.control-sidebar -->
      </div>
      <!-- ./wrapper -->
      <!-- jQuery -->
      <script src="../plogins/jquery/jquery.min.js"></script>
      <!-- Bootstrap 4 -->
      <script src="../plogins/bootstrap/js/bootstrap.bundle.min.js"></script>
      <!-- Select2 -->
      <script src="../plogins/select2/js/select2.full.min.js"></script>
      <!-- BS-Stepper -->
      <script src="../plogins/bs-stepper/js/bs-stepper.min.js"></script>
      <!-- AdminLTE App -->
      <script src="../dust/js/adminlte.min.js"></script>
      <!-- AdminLTE for demo purposes -->
      <script src="../dust/js/demo.js"></script>
      <!-- Page specific script -->
      <script>
         $(function () {
             //Initialize Select2 Elements
             $('.select2').select2()
         
             //Initialize Select2 Elements
             $('.select2bs4').select2({
               theme: 'bootstrap4'
             })
         
             //Datemask dd/mm/yyyy
             $('#datemask').inputmask('dd/mm/yyyy', { 'placeholder': 'dd/mm/yyyy' })
             //Datemask2 mm/dd/yyyy
             $('#datemask2').inputmask('mm/dd/yyyy', { 'placeholder': 'mm/dd/yyyy' })
             //Money Euro
             $('[data-mask]').inputmask()
         
             //Date picker
             $('#reservationdate').datetimepicker({
                 format: 'L'
             });
         
             //Date and time picker
             $('#reservationdatetime').datetimepicker({ icons: { time: 'far fa-clock' } });
         
             //Date range picker
             $('#reservation').daterangepicker()
             //Date range picker with time picker
             $('#reservationtime').daterangepicker({
               timePicker: true,
               timePickerIncrement: 30,
               locale: {
                 format: 'MM/DD/YYYY hh:mm A'
               }
             })
             //Date range as a button
             $('#daterange-btn').daterangepicker(
               {
                 ranges   : {
                   'Today'       : [moment(), moment()],
                   'Yesterday'   : [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                   'Last 7 Days' : [moment().subtract(6, 'days'), moment()],
                   'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                   'This Month'  : [moment().startOf('month'), moment().endOf('month')],
                   'Last Month'  : [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')]
                 },
                 startDate: moment().subtract(29, 'days'),
                 endDate  : moment()
               },
               function (start, end) {
                 $('#reportrange span').html(start.format('MMMM D, YYYY') + ' - ' + end.format('MMMM D, YYYY'))
               }
             )
         
             //Timepicker
             $('#timepicker').datetimepicker({
               format: 'LT'
             })
         
             //Bootstrap Duallistbox
             $('.duallistbox').bootstrapDualListbox()
         
             //Colorpicker
             $('.my-colorpicker1').colorpicker()
             //color picker with addon
             $('.my-colorpicker2').colorpicker()
         
             $('.my-colorpicker2').on('colorpickerChange', function(event) {
               $('.my-colorpicker2 .fa-square').css('color', event.color.toString());
             })
         
             $("input[data-bootstrap-switch]").each(function(){
               $(this).bootstrapSwitch('state', $(this).prop('checked'));
             })
         
           })
           // BS-Stepper Init
           document.addEventListener('DOMContentLoaded', function () {
             window.stepper = new Stepper(document.querySelector('.bs-stepper'))
           })
         
      </script>
      <script>
         // FunciÃ³n para alternar la visibilidad de la contraseÃ±a
         function togglePassword(inputId) {
             var input = document.getElementById(inputId);
             var icon = document.getElementById('toggleClaveIcon');
             
             // Cambiar tipo de campo entre 'password' y 'text'
             if (input.type === "password") {
                 input.type = "text";
                 icon.classList.remove('fa-eye');
                 icon.classList.add('fa-eye-slash');
             } else {
                 input.type = "password";
                 icon.classList.remove('fa-eye-slash');
                 icon.classList.add('fa-eye');
             }
         }
      </script>
      <script>
         function showPasswordMessage() {
             var passwordField = document.getElementById('ClavePerfil');
             var message = document.getElementById('passwordMessage');
             
             if (passwordField.value) {
                 message.style.display = 'block';  // Muestra el mensaje si hay texto
             } else {
                 message.style.display = 'none';  // Oculta el mensaje si el campo estÃ¡ vacÃ­o
             }
         }
      </script>
      <script>
         $(document).ready(function(){
                    $('[data-toggle="tooltip"]').tooltip(); 
                  });
      </script>
   </body>
</html>
