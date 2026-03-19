<?php
return [
  'db' => [
    'host'    => 'localhost',
    'name'    => 'rsudb',
    'user'    => 'au_rsu',
    'pass'    => '_BrHJMGO3U3(9v.c',
    'port'    => 3306,
    'charset' => 'utf8mb4',
  ],
  'app' => [
    'env'      => 'prod',
    'timezone' => 'America/Lima',
    // base_url: NO es necesario con tu router index.php?p=...
    // Úsalo solo si luego necesitas generar enlaces ABSOLUTOS en correos, etc.
    'base_url' => '',
  ],
];