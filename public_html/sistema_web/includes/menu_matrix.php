<?php
/**
 * Matriz de menus por rol.
 * Fases habilitadas:
 * - rol 1: Direccion RSU
 * - rol 2: Coordinador de Proyecto
 * - rol 3: Decanato de Facultad
 * - rol 4: Director de Departamento
 * - rol 5: Comite de Facultad
 */

if (!function_exists('rsu_get_menu_matrix')) {
    function rsu_get_menu_matrix()
    {
        return array(
            1 => array(
                'brand' => array(
                    'href' => 'inicio.php',
                    'logo' => '../dust/img/dirsu_logo_128_128.png',
                    'name' => 'Sistema DIRSU'
                ),
                'user_home' => 'inicio.php',
                'avatar' => '../dust/img/avatar.png',
                'items' => array(
                    array(
                        'type' => 'item',
                        'label' => 'INICIO',
                        'icon' => 'fas fa-home',
                        'href' => 'inicio.php',
                        'active_on' => array('inicio.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Evaluacion de Proyectos'
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Informe Semestral 2025-I',
                        'icon' => 'fa fa-calendar',
                        'href' => 'evaluacion.php',
                        'active_on' => array('evaluacion.php')
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Codigos de proyectos',
                        'icon' => 'fa fa-at',
                        'href' => 'codigos.php',
                        'active_on' => array('codigos.php')
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Evaluaciones anteriores',
                        'icon' => 'fas fa-user-cog',
                        'children' => array(
                            array(
                                'label' => 'Lista de Cotejo (2024)',
                                'icon' => 'fa fa-clipboard-list',
                                'href' => 'cotejo.php',
                                'active_on' => array('cotejo.php')
                            ),
                            array(
                                'label' => 'Rubrica (2024)',
                                'icon' => 'fa fa-table',
                                'href' => 'rubrica.php',
                                'active_on' => array('rubrica.php')
                            )
                        )
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Solo administradores'
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Funciones',
                        'icon' => 'fas fa-user-cog',
                        'children' => array(
                            array(
                                'label' => 'Consulta Usuario',
                                'icon' => 'fas fa-users',
                                'href' => 'usuarios.php',
                                'active_on' => array('usuarios.php')
                            ),
                            array(
                                'label' => 'Reporte Proyectos',
                                'icon' => 'fas fa-file-alt',
                                'href' => 'general.php',
                                'active_on' => array('general.php')
                            ),
                            array(
                                'label' => 'Control Proyectos',
                                'icon' => 'fas fa-project-diagram',
                                'href' => 'control_proyectos.php',
                                'active_on' => array('control_proyectos.php')
                            ),
                            array(
                                'label' => 'Panel de Control',
                                'icon' => 'fas fa-users-cog',
                                'href' => 'panel.php',
                                'active_on' => array('panel.php')
                            ),
                            array(
                                'label' => 'Control Eventos',
                                'icon' => 'fas fa-calendar-check',
                                'href' => 'control_eventos.php',
                                'active_on' => array('control_eventos.php')
                            ),
                            array(
                                'label' => 'Analitics',
                                'icon' => 'fas fa-chart-line',
                                'href' => 'estadistica.php',
                                'active_on' => array('estadistica.php')
                            )
                        )
                    )
                )
            ),
            2 => array(
                'brand' => array(
                    'href' => '../inicio.php',
                    'href_by_page' => array(
                        'inicio.php' => 'inicio.php',
                        'index.php' => '../inicio.php'
                    ),
                    'logo' => '../dust/img/dirsu_logo_128_128.png',
                    'logo_by_page' => array(
                        'inicio.php' => 'dust/img/dirsu_logo_128_128.png',
                        'index.php' => '../dust/img/dirsu_logo_128_128.png'
                    ),
                    'name' => 'Sistema DIRSU'
                ),
                'user_home' => 'perfil.php',
                'user_home_by_page' => array(
                    'inicio.php' => 'vistas/perfil.php',
                    'index.php' => '../vistas/perfil.php'
                ),
                'user_link_style_by_page' => array(
                    'perfil.php' => 'color: white; text-shadow: 0 0 10px rgba(255, 255, 255, 0.8);'
                ),
                'avatar' => '../dust/img/avatar.png',
                'avatar_by_page' => array(
                    'inicio.php' => 'dust/img/avatar.png',
                    'index.php' => '../dust/img/avatar.png'
                ),
                'items' => array(
                    array(
                        'type' => 'item',
                        'label' => 'INICIO',
                        'icon' => 'fas fa-home',
                        'href' => '../inicio.php',
                        'href_by_page' => array(
                            'inicio.php' => 'inicio.php',
                            'index.php' => '../inicio.php'
                        ),
                        'active_on' => array('inicio.php')
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Guia de trabajo',
                        'icon' => 'fa fa-road',
                        'href' => 'guia.php',
                        'href_by_page' => array(
                            'inicio.php' => 'vistas/guia.php',
                            'index.php' => '../vistas/guia.php'
                        ),
                        'active_on' => array('guia.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Informacion de proyecto'
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Mi proyecto',
                        'icon' => 'fa fa-users',
                        'href' => 'proyecto.php',
                        'href_by_page' => array(
                            'inicio.php' => 'vistas/proyecto.php',
                            'index.php' => '../vistas/proyecto.php'
                        ),
                        'active_on' => array('proyecto.php')
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Mi progreso',
                        'icon' => 'fa fa-chart-line',
                        'href' => 'progreso.php',
                        'href_by_page' => array(
                            'inicio.php' => 'vistas/progreso.php',
                            'index.php' => '../vistas/progreso.php'
                        ),
                        'active_on' => array('progreso.php')
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Formatos',
                        'icon' => 'fa fa-file-word',
                        'href' => 'formato.php',
                        'href_by_page' => array(
                            'inicio.php' => 'vistas/formato.php',
                            'index.php' => '../vistas/formato.php'
                        ),
                        'active_on' => array('formato.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Fases de proyecto'
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Formulacion y presentacion',
                        'icon_badge' => '1',
                        'children' => array(
                            array(
                                'label' => '1.1. Generalidades',
                                'href' => 'datos_principales.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/datos_principales.php',
                                    'index.php' => '../vistas/datos_principales.php'
                                ),
                                'active_on' => array('datos_principales.php', 'registro_proyecto.php', 'exregistro.php', 'proyecto_final.php')
                            ),
                            array(
                                'label' => '1.2. Plan de proyecto',
                                'href' => 'desarrollo_informe.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/desarrollo_informe.php',
                                    'index.php' => '../vistas/desarrollo_informe.php'
                                ),
                                'active_on' => array('desarrollo_informe.php')
                            ),
                            array(
                                'label' => '1.3. Anexos',
                                'href' => 'anexos.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/anexos.php',
                                    'index.php' => '../vistas/anexos.php'
                                ),
                                'active_on' => array('anexos.php')
                            )
                        )
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Ejecucion y monitoreo',
                        'icon_badge' => '2',
                        'children' => array(
                            array(
                                'label' => '2.1. Cronograma de ejecucion',
                                'href' => 'cronograma.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/cronograma.php',
                                    'index.php' => '../vistas/cronograma.php'
                                ),
                                'active_on' => array('cronograma.php')
                            ),
                            array(
                                'label' => '2.2. Revision de cronograma',
                                'href' => 'revision_cronograma.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/revision_cronograma.php',
                                    'index.php' => '../vistas/revision_cronograma.php'
                                ),
                                'active_on' => array('revision_cronograma.php')
                            )
                        )
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Evaluacion e informe',
                        'icon_badge' => '3',
                        'children' => array(
                            array(
                                'label' => '3.1. Informe semestral',
                                'href' => '../semestral/index.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'semestral/index.php',
                                    'index.php' => 'index.php'
                                ),
                                'active_on' => array('index.php', 'informe_final.php')
                            ),
                            array(
                                'label' => '3.2. Revision de informe',
                                'href' => 'revision_informe_final.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/revision_informe_final.php',
                                    'index.php' => '../vistas/revision_informe_final.php'
                                ),
                                'active_on' => array('revision_informe_final.php')
                            )
                        )
                    )
                )
            ),
            3 => array(
                'brand' => array(
                    'href' => 'inicio.php',
                    'logo' => '../dust/img/dirsu_logo_128_128.png',
                    'name' => 'Sistema DIRSU'
                ),
                'user_home' => 'inicio.php',
                'avatar' => '../dust/img/avatar.png',
                'items' => array(
                    array(
                        'type' => 'item',
                        'label' => 'INICIO',
                        'icon' => 'fas fa-home',
                        'href' => 'inicio.php',
                        'active_on' => array('inicio.php', 'inicio_antiguo.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Evaluacion de Proyectos'
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Informe Semestral 2025-I',
                        'icon' => 'fa fa-calendar',
                        'href' => 'evaluacion.php',
                        'active_on' => array('evaluacion.php')
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Evaluaciones anteriores',
                        'icon' => 'fas fa-user-cog',
                        'children' => array(
                            array(
                                'label' => 'Visto Bueno (2024)',
                                'icon' => 'fa fa-clipboard-list',
                                'href' => 'visto.php',
                                'active_on' => array('visto.php')
                            )
                        )
                    )
                )
            ),
            4 => array(
                'brand' => array(
                    'href' => 'inicio.php',
                    'logo' => '../dust/img/dirsu_logo_128_128.png',
                    'name' => 'Sistema DIRSU'
                ),
                'user_home' => 'inicio.php',
                'avatar' => '../dust/img/avatar.png',
                'items' => array(
                    array(
                        'type' => 'item',
                        'label' => 'INICIO',
                        'icon' => 'fas fa-home',
                        'href' => 'inicio.php',
                        'active_on' => array('inicio.php', 'inicio_antiguo.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Evaluacion de Proyectos'
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Informe Semestral 2025-I',
                        'icon' => 'fa fa-calendar',
                        'href' => 'evaluacion.php',
                        'active_on' => array('evaluacion.php')
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Evaluaciones anteriores',
                        'icon' => 'fas fa-user-cog',
                        'children' => array(
                            array(
                                'label' => 'Visto Bueno (2024)',
                                'icon' => 'fa fa-clipboard-list',
                                'href' => 'visto.php',
                                'active_on' => array('visto.php')
                            )
                        )
                    )
                )
            ),
            5 => array(
                'brand' => array(
                    'href' => 'inicio.php',
                    'logo' => '../dust/img/dirsu_logo_128_128.png',
                    'name' => 'Sistema DIRSU'
                ),
                'user_home' => 'inicio.php',
                'avatar' => '../dust/img/avatar.png',
                'items' => array(
                    array(
                        'type' => 'item',
                        'label' => 'INICIO',
                        'icon' => 'fas fa-home',
                        'href' => 'inicio.php',
                        'active_on' => array('inicio.php', 'inicio_antiguo.php')
                    ),
                    array(
                        'type' => 'header',
                        'label' => 'Evaluacion de Proyectos'
                    ),
                    array(
                        'type' => 'item',
                        'label' => 'Informe Semestral 2025-I',
                        'icon' => 'fa fa-calendar',
                        'href' => 'evaluacion.php',
                        'active_on' => array('evaluacion.php')
                    ),
                    array(
                        'type' => 'tree',
                        'label' => 'Evaluaciones anteriores',
                        'icon' => 'fas fa-user-cog',
                        'children' => array(
                            array(
                                'label' => 'Lista de Cotejo (2024)',
                                'icon' => 'fa fa-clipboard-list',
                                'href' => 'cotejo.php',
                                'active_on' => array('cotejo.php')
                            ),
                            array(
                                'label' => 'Rubrica (2024)',
                                'icon' => 'fa fa-table',
                                'href' => 'rubrica.php',
                                'active_on' => array('rubrica.php')
                            )
                        )
                    )
                )
            )
        );
    }
}

if (!function_exists('rsu_get_menu_by_role')) {
    function rsu_get_menu_by_role($role_id)
    {
        $matrix = rsu_get_menu_matrix();
        return isset($matrix[(int)$role_id]) ? $matrix[(int)$role_id] : null;
    }
}

