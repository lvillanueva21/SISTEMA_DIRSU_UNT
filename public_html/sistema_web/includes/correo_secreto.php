<?php
/**
 * Secreto maestro para cifrar/descifrar keys SMTP guardadas en BD.
 * Archivo separado de config.php por seguridad operativa.
 *
 * IMPORTANTE:
 * - Cambiar RSU_CORREO_MASTER_KEY por una cadena larga y aleatoria.
 * - No usar la clave de ejemplo en produccion.
 */

if (!defined('RSU_CORREO_MASTER_KEY')) {
    define('RSU_CORREO_MASTER_KEY', '7f2aB9vQ!m3Lx#8Rz@4Nw$1Tj%6Kp^2Yh&5Ud*0Ce(9Gi)_sA+qD=Vb~7Mn');
}

if (!defined('RSU_CORREO_CIPHER')) {
    define('RSU_CORREO_CIPHER', 'aes-256-gcm');
}

