<?php
/**
 * Bootstrap del motor Evaluacion V1.
 * Uso:
 *   require_once __DIR__ . '/bootstrap.php';
 *   $engine = rsu_eval_v1_engine($conexion);
 */

require_once __DIR__ . '/../db_connection.php';
require_once __DIR__ . '/ContextResolver.php';
require_once __DIR__ . '/PermissionService.php';
require_once __DIR__ . '/WorkflowService.php';
require_once __DIR__ . '/EventLoggerService.php';
require_once __DIR__ . '/MailOutboxService.php';
require_once __DIR__ . '/NotificationService.php';
require_once __DIR__ . '/MessagingPolicyService.php';
require_once __DIR__ . '/NotificationWarnings.php';
require_once __DIR__ . '/LegacyCompatibilityService.php';
require_once __DIR__ . '/LegacyDispatcher.php';
require_once __DIR__ . '/EvaluationEngine.php';

if (!function_exists('rsu_eval_v1_engine')) {
    function rsu_eval_v1_engine($conexion = null)
    {
        if (!($conexion instanceof mysqli)) {
            $conexion = rsu_db_connect();
        }
        if (!($conexion instanceof mysqli)) {
            return null;
        }
        return new RSUEvaluacionV1EvaluationEngine($conexion);
    }
}
