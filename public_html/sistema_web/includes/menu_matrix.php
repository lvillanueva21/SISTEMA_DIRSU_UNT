<?php
/**
 * Matriz de menús por rol.
 * Fases habilitadas:
 * - rol 1: Dirección RSU
 * - rol 2: Coordinador de Proyecto
 * - rol 3: Decanato de Facultad
 * - rol 4: Director de Departamento
 * - rol 5: Comité de Facultad
 */

include_once __DIR__ . '/config.php';

if (!function_exists('rsu_menu_is_development_mode')) {
    function rsu_menu_is_development_mode()
    {
        global $RSU_CONFIG;

        $mode = isset($RSU_CONFIG['session_mode']) ? (string)$RSU_CONFIG['session_mode'] : 'production';
        return strtolower(trim($mode)) === 'development';
    }
}

if (!function_exists('rsu_menu_get_api_dirsu_item_by_role')) {
    function rsu_menu_get_api_dirsu_item_by_role($role_id)
    {
        $role_id = (int)$role_id;

        if (in_array($role_id, array(1, 2, 3, 4, 5), true)) {
            return array(
                'type' => 'item',
                'label' => 'API DIRSU',
                'icon' => 'fas fa-code',
                'href_dynamic' => 'includes/api_dirsu/index.php',
                'active_on_dynamic' => array('includes/api_dirsu/index.php'),
                'dev_only' => true
            );
        }

        return null;
    }
}

if (!function_exists('rsu_menu_append_development_items')) {
    function rsu_menu_append_development_items($matrix)
    {
        if (!is_array($matrix) || !rsu_menu_is_development_mode()) {
            return $matrix;
        }

        foreach ($matrix as $role_id => $role_config) {
            if (!isset($role_config['items']) || !is_array($role_config['items'])) {
                continue;
            }

            $has_api_item = false;
            foreach ($role_config['items'] as $item) {
                if (isset($item['label']) && strtolower(trim((string)$item['label'])) === 'api dirsu') {
                    $has_api_item = true;
                    break;
                }
            }

            if ($has_api_item) {
                continue;
            }

            $api_item = rsu_menu_get_api_dirsu_item_by_role($role_id);
            if (!$api_item) {
                continue;
            }

            $role_config['items'][] = array(
                'type' => 'header',
                'label' => 'Laboratorio',
                'dev_only' => true
            );
            $role_config['items'][] = $api_item;

            $matrix[$role_id] = $role_config;
        }

        return $matrix;
    }
}

if (!function_exists('rsu_menu_reorder_coordinator_sections')) {
    function rsu_menu_reorder_coordinator_sections($matrix)
    {
        if (!is_array($matrix) || !isset($matrix[2]['items']) || !is_array($matrix[2]['items'])) {
            return $matrix;
        }

        $items = $matrix[2]['items'];
        $info_start = -1;
        $fases_start = -1;

        $i = 0;
        for ($i = 0; $i < count($items); $i++) {
            $item = is_array($items[$i]) ? $items[$i] : array();
            if (!isset($item['type']) || (string)$item['type'] !== 'header') {
                continue;
            }

            $next = isset($items[$i + 1]) && is_array($items[$i + 1]) ? $items[$i + 1] : array();

            if (
                $info_start < 0
                && isset($next['label'])
                && trim((string)$next['label']) === 'Mi proyecto'
            ) {
                $info_start = $i;
            }

            if (
                $fases_start < 0
                && isset($next['type'])
                && (string)$next['type'] === 'tree'
                && isset($next['icon_badge'])
                && (string)$next['icon_badge'] === '1'
            ) {
                $fases_start = $i;
            }
        }

        if ($info_start < 0 || $fases_start < 0 || $info_start > $fases_start) {
            return $matrix;
        }

        $info_len = 4;
        $fases_len = 4;

        $before = array_slice($items, 0, $info_start);
        $info_block = array_slice($items, $info_start, $info_len);
        $between = array_slice($items, $info_start + $info_len, $fases_start - ($info_start + $info_len));
        $fases_block = array_slice($items, $fases_start, $fases_len);
        $after = array_slice($items, $fases_start + $fases_len);

        if (count($info_block) !== $info_len || count($fases_block) !== $fases_len) {
            return $matrix;
        }

        $matrix[2]['items'] = array_merge($before, $fases_block, $between, $info_block, $after);

        return $matrix;
    }
}

if (!function_exists('rsu_get_menu_matrix')) {
    function rsu_get_menu_matrix()
    {
        $matrix = array(
            1 => array(
                'menu_context_dir' => 'direccion_rsu',
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
                        'label' => 'Evaluación de Proyectos'
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
                        'label' => 'Códigos de proyectos',
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
                                'label' => 'Rúbrica (2024)',
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
                                'label' => 'Consulta de usuario',
                                'icon' => 'fas fa-users',
                                'href' => 'usuarios.php',
                                'active_on' => array('usuarios.php')
                            ),
                            array(
                                'label' => 'Reporte de proyectos',
                                'icon' => 'fas fa-file-alt',
                                'href' => 'general.php',
                                'active_on' => array('general.php')
                            ),
                            array(
                                'label' => 'Control de proyectos',
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
                                'label' => 'Control de eventos',
                                'icon' => 'fas fa-calendar-check',
                                'href' => 'control_eventos.php',
                                'active_on' => array('control_eventos.php')
                            ),
                            array(
                                'label' => 'Analítica',
                                'icon' => 'fas fa-chart-line',
                                'href' => 'estadistica.php',
                                'active_on' => array('estadistica.php')
                            )
                        )
                    )
                )
            ),
            2 => array(
                'menu_context_dir' => 'vistas',
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
                        'label' => 'Guía de trabajo',
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
                        'label' => 'Información de proyecto'
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
                        'label' => 'Formulación y presentación',
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
                        'label' => 'Ejecución y monitoreo',
                        'icon_badge' => '2',
                        'children' => array(
                            array(
                                'label' => '2.1. Cronograma de ejecución',
                                'href' => 'cronograma.php',
                                'href_by_page' => array(
                                    'inicio.php' => 'vistas/cronograma.php',
                                    'index.php' => '../vistas/cronograma.php'
                                ),
                                'active_on' => array('cronograma.php')
                            ),
                            array(
                                'label' => '2.2. Revisión de cronograma',
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
                        'label' => 'Evaluación e informe',
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
                                'label' => '3.2. Revisión de informe',
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
                'menu_context_dir' => 'decanato_facultad',
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
                        'label' => 'Evaluación de Proyectos'
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
                'menu_context_dir' => 'director_departamento',
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
                        'label' => 'Evaluación de Proyectos'
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
                'menu_context_dir' => 'comite_facultad',
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
                        'label' => 'Evaluación de Proyectos'
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
                                'label' => 'Rúbrica (2024)',
                                'icon' => 'fa fa-table',
                                'href' => 'rubrica.php',
                                'active_on' => array('rubrica.php')
                            )
                        )
                    )
                )
            )
        );

        $matrix = rsu_menu_reorder_coordinator_sections($matrix);
        return rsu_menu_append_development_items($matrix);
    }
}

if (!function_exists('rsu_get_menu_by_role')) {
    function rsu_get_menu_by_role($role_id)
    {
        $matrix = rsu_get_menu_matrix();
        return isset($matrix[(int)$role_id]) ? $matrix[(int)$role_id] : null;
    }
}
