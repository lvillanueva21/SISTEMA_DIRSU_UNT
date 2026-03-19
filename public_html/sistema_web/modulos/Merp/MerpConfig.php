<?php
namespace Merp;

/**
 * Configuración estática (en la Parte 3 pasará a BD).
 */
class MerpConfig
{
    /* Paleta de colores y clases Bootstrap */
    public const PALETA = [
        'gris'     => ['bg' => '#647687', 'text' => 'text-white'],
        'azul'     => ['bg' => '#0275D8', 'text' => 'text-white'],
        'naranja'  => ['bg' => '#F0AD4E', 'text' => 'text-dark'],
        'cyan'     => ['bg' => '#5BC0DE', 'text' => 'text-dark'],
        'verde'    => ['bg' => '#5CB85C', 'text' => 'text-dark'],
        'rosa'     => ['bg' => '#F19C99', 'text' => 'text-dark'],
        'amarillo' => ['bg' => '#FFD966', 'text' => 'text-dark'],
        'blanco'   => ['bg' => '#FFFFFF', 'text' => 'text-dark'],
    ];

    /* Orden de columnas visible en la tabla */
    public const COLUMNAS = [
        '#'                               => ['tipo' => 'contador'],
        'Proyecto'                        => ['tipo' => 'proyecto'],
        'Coordinador'                     => ['tipo' => 'coordinador'],
        'Estado proyecto'                 => ['tipo' => 'estado'],
        '¿Qué necesita<br>mi proyecto?'      => ['tipo' => 'pendiente'],
        '¿Quién es<br>responsable?'          => ['tipo' => 'responsable'],
        'Observación'                     => ['tipo' => 'observacion'],
    ];
}
