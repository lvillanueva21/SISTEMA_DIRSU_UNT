<?php
/**
 * Matriz de menus por rol.
 * Fases habilitadas:
 * - rol 1: Direccion RSU
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

