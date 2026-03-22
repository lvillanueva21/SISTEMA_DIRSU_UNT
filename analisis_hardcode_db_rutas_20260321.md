# Analisis de Hardcode DB y Rutas para Migracion a /rsu

Fecha: 2026-03-21

## 1) Archivos con credenciales DB hardcodeadas (confirmado)

- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\gestor\db.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\db.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\configSesion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\rutas.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\web\includes\config.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\validarTexto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\verTextos.php

### Lineas detectadas (credenciales)
```text
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/web/includes/config.php:4:    'host'    => 'localhost',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/web/includes/config.php:5:    'name'    => 'rsudb',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/web/includes/config.php:6:    'user'    => 'au_rsu',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/web/includes/config.php:7:    'pass'    => '_BrHJMGO3U3(9v.c',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/gestor/db.php:5:$direccionservidor = "localhost";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/gestor/db.php:6:$baseDatos = "rsudb";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/gestor/db.php:7:$usuarioBD = "au_rsu";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/gestor/db.php:8:$contraseniaBD = '_BrHJMGO3U3(9v.c';
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/validarTexto.php:3:$servername = "localhost"; // Cambia esto por tu servidor
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/validarTexto.php:4:$username = "gla_livp_2024_dirsu"; // Cambia esto por tu usuario de base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/validarTexto.php:5:$password = "passLIVP24@"; // Cambia esto por tu contraseña de base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/validarTexto.php:6:$dbname = "gla_dirsu_bd_2024"; // Cambia esto por el nombre de tu base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/verTextos.php:3:$servername = "localhost"; // Cambia esto por tu servidor
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/verTextos.php:4:$username = "gla_livp_2024_dirsu"; // Cambia esto por tu usuario de base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/verTextos.php:5:$password = "passLIVP24@"; // Cambia esto por tu contraseña de base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/verTextos.php:6:$dbname = "gla_dirsu_bd_2024"; // Cambia esto por el nombre de tu base de datos
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/rutas.php:31:$direccionservidor = "localhost";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/rutas.php:32:$baseDatos = "rsudb";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/rutas.php:33:$usuarioBD = "au_rsu";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/rutas.php:34:$contraseniaBD = "_BrHJMGO3U3(9v.c";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/configSesion.php:31:$direccionservidor = "localhost";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/configSesion.php:32:$baseDatos = "rsudb";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/configSesion.php:33:$usuarioBD = "au_rsu";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/configSesion.php:34:$contraseniaBD = "_BrHJMGO3U3(9v.c";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/db.php:3:    $direccionservidor="localhost";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/db.php:4:    $baseDatos="rsudb";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/db.php:5:    $usuarioBD="au_rsu";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/db.php:6:    $contraseniaBD="_BrHJMGO3U3(9v.c";
```

### Lineas detectadas (apertura de conexion)
```text
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/gestor/db.php:11:$conexion = new mysqli($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/web/includes/conexion.php:19:  $mysqli = new mysqli(
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/rutas.php:45:        $conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/verTextos.php:9:$conn = new mysqli($servername, $username, $password, $dbname);
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/configSesion.php:36:$conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/db.php:9:$conexion = mysqli_connect($direccionservidor, $usuarioBD, $contraseniaBD, $baseDatos);
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/validarTexto.php:9:$conn = new mysqli($servername, $username, $password, $dbname);
```

## 2) Dependencia real del sistema sobre db.php

- Archivos PHP que incluyen/requieren `db.php` o `componentes/db.php`: 226
- Conclusión: cambiar `public_html/sistema_web/componentes/db.php` impacta la mayor parte de `sistema_web`

## 3) Archivos con dominio hardcodeado https://rsu.unitru.edu.pe

- Total de archivos: 71

- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\inc\navbar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\index_pp.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\navbar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\panel_prueba.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\comite_facultad\cotejo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\comite_facultad\perfil.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\comite_facultad\rubrica.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\configSesion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\perfil\actualizar_dacademicos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\perfil\actualizar_dpersonales.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_beneficiados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_cronograma.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_diagnostico.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_facultad.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_fases.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_financiamiento.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_impacto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_interesados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_lugar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_metodologia.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_responsables.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_servicios.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_titulo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\cargar_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\ver_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_carga.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_conclusiones.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_fuentes.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_integrantes.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_lugar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_resultados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_resumen.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_titulo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\cargar_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\evaluacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\inicio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\inicio_antiguo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\perfil.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\progreso_proyectos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\decanato_facultad\visto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\codigos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\control_eventos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\inicio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\panel.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\evaluacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\inicio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\inicio_antiguo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\perfil.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\progreso_proyectos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\director_departamento\visto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\notificaciones_observacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\notificaciones_ruta.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\modulos\semestral\index.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\presentacion\index.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\index.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\notificaciones_subsanacion_autoridades.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\anexos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\cronograma.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\datos_principales.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\desarrollo_informe.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\formato.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\guia.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\informe_final.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\perfil.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\progreso.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\proyecto_final.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\revision_cronograma.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\vistas\revision_informe_final.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\web\includes\navbar.php

## 4) Archivos con rutas absolutas desde raiz /sistema_web/

- Total de archivos (php/html): 77

- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\cw_config.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\inc\navbar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\index_pp.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\navbar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\_pause_evaluacion\api_eval.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\_pause_evaluacion\modales_eval.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\_pause_evaluacion\principal.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\_pause_evaluacion\ver_informe.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\configSesion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\cw_config.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\operador\cargar_proyectos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\perfil\actualizar_dacademicos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\perfil\actualizar_dpersonales.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_beneficiados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_bienes.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_cronograma.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_diagnostico.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_facultad.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_fases.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_financiamiento.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_impacto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_interesados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_lugar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_metodologia.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_objetivos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_responsables.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_servicios.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\actualizar_titulo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\cargar_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\crear_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto\ver_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_carga.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_conclusiones.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_fuentes.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_integrantes.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_lugar.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_resultados.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_resumen.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\actualizar_titulo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\componentes\proyecto_final\cargar_proyecto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\calificacion\proyectos_cotejo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\codigos.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\data.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\evaluacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\funciones\card_items_srv.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\panel.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\direccion_rsu\red.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\api\observaciones_estado.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\api\save_evaluacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\control_oficinas.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\modales\detalle_observacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\modales\evaluacion_msg.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\modales\observaciones_resumen.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\notificaciones_observacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\notificaciones_ruta.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\principal.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\social.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\ver_informe.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\evaluacion\ver_informe_antiguo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\card_1_ruta_evaluacion.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\card_2_comunicado_vencimiento.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\card_4_control_directorio.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\card_fecha_limite.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\guardar_contacto.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\guardar_fecha_limite.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\inicio\index.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\integrados\mensaje_registrar_py.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\paginas\formularios\registro_proyecto_sleep.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\presentacion\logica\formulario.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\registrop.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\borrar_archivo.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\formulario.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\guardar_item.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\notificaciones_subsanacion_autoridades.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\sistema_web\semestral\logica\solicitar_revision.php
- e:\GITHUB CLONES\LIVP_RSU_UNT\public_html\web\includes\navbar.php

## 5) Paths absolutos de filesystem del servidor antiguo

```text
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:1:[12-Sep-2025 16:43:31 America/Lima] PHP Fatal error:  Uncaught Error: mysqli_stmt::bind_param(): Argument #2 cannot be passed by reference in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php:111
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:4:  thrown in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php on line 111
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:6:[12-Sep-2025 16:52:27 America/Lima] PHP Fatal error:  Uncaught Error: mysqli_stmt::bind_param(): Argument #2 cannot be passed by reference in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php:111
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:9:  thrown in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php on line 111
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:11:[12-Sep-2025 16:53:57 America/Lima] PHP Fatal error:  Uncaught Error: mysqli_stmt::bind_param(): Argument #2 cannot be passed by reference in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php:110
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/subsanacion_errors.log:14:  thrown in /var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/semestral/logica/enviar_subsanacion.php on line 110
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/notificaciones_subsanacion_autoridades.php:144:    '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/evaluacion/recursos/src',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/notificaciones_subsanacion_autoridades.php:145:    '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web/recursos/src',
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/guardar_item.php:10:$FS_BASE   = '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web';
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/semestral/logica/borrar_archivo.php:13:$FS_BASE          = '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web';
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/presentacion/logica/guardar_item.php:10:$FS_BASE   = '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web';
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/presentacion/logica/borrar_archivo.php:24:$FS_BASE = '/var/www/html/otros/rsu.unitru.edu.pe/htdocs/sistema_web';
```

## 6) Otros dominios hardcodeados (adicional a revisar)

```text
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/revision_proyectos.php:46:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/registro_proyecto.php:52:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/exregistro.php:52:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/equipo.php:46:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/editor.php:50:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/vistas/404.php:46:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/comite_facultad/progreso_proyectos.php:117:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/comite_facultad/inicio_antiguo.php:76:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/comite_facultad/inicio.php:76:            <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/comite_facultad/evaluacion.php:44:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/proyecto/crear_proyecto.php:11:    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/proyecto/actualizar_objetivos.php:11:    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/proyecto/actualizar_bienes.php:11:    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/componentes/operador/cargar_proyectos.php:5:    echo "<script>location.assign('https://gla.pe/sistema_web/login.php');</script>";
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/operador/proyectos.php:54:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/usuarios.php:41:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/rubrica.php:52:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/red.php:44:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/prueba4.php:46:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/prueba3.php:46:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/prueba2.php:46:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/prueba.php:46:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/progreso_proyectos.php:102:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/inicio_antiguo.php:59:                  <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/guia.php:72:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/general2.php:41:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/general.php:41:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/evaluacion.php:43:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/estadistica.php:41:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/data.php:44:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/cotejo.php:90:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/control_proyectos.php:43:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/direccion_rsu/console.php:44:                    <a href="https://gla.pe/b_demo/" class="nav-link" target="_blank">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/paginas/formularios/registro_proyecto_sleep.php:129:        <a href="https://gla.pe/demo/" class="nav-link" target="_blank"><p style="color: white;
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/paginas/formularios/registro_proyecto_sleep.php:224:                <a href="https://gla.pe/sistema_web/paginas/formularios/registro_proyecto.php" class="nav-link">
e:/GITHUB CLONES/LIVP_RSU_UNT/public_html/sistema_web/paginas/equipo.php:165:        <a href="https://gla.pe/demo/" class="nav-link" target="_blank"><p style="color: white;
```

## 7) Prioridad sugerida para migrar sin romper

1. Actualizar primero credenciales en:
   - public_html/sistema_web/componentes/db.php
   - public_html/web/includes/config.php
   - public_html/gestor/db.php
2. Actualizar redireccion de sesion/login:
   - public_html/sistema_web/componentes/configSesion.php
   - public_html/sistema_web/componentes/proyecto/*.php
   - public_html/sistema_web/componentes/proyecto_final/*.php
3. Reemplazar dominio absoluto `https://rsu.unitru.edu.pe` por base configurable para pruebas.
4. Revisar rutas absolutas `/sistema_web/...` si el sistema se desplegara bajo `/rsu/` (p.ej. usar `/rsu/sistema_web/...` o helper de base URL).
5. Ajustar paths de filesystem duro `/var/www/html/otros/rsu.unitru.edu.pe/htdocs/...` por rutas derivadas de `$_SERVER["DOCUMENT_ROOT"]` o `__DIR__`.
