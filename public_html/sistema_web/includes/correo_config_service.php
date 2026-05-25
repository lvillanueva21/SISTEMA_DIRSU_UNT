<?php
/**
 * Servicio central para configuracion SMTP Gmail.
 * - Lee/escribe configuracion en tablas cor_*
 * - Cifra/descifra la key de aplicacion
 * - Prueba de envio con trazabilidad
 */

if (!defined('RSU_CORREO_CONFIG_SERVICE_LOADED')) {
    define('RSU_CORREO_CONFIG_SERVICE_LOADED', 1);

    require_once __DIR__ . '/db_connection.php';
    require_once __DIR__ . '/correo_secreto.php';

    function cor_mail_now()
    {
        return date('Y-m-d H:i:s');
    }

    function cor_mail_normalize_email($value)
    {
        return strtolower(trim((string)$value));
    }

    function cor_mail_normalize_app_key($value)
    {
        $raw = trim((string)$value);
        if ($raw === '') {
            return '';
        }
        return preg_replace('/\s+/', '', $raw);
    }

    function cor_mail_master_key_valid(&$error = '')
    {
        $error = '';
        $raw = defined('RSU_CORREO_MASTER_KEY') ? (string)RSU_CORREO_MASTER_KEY : '';
        if (trim($raw) === '') {
            $error = 'No existe clave maestra de cifrado.';
            return false;
        }
        if (stripos($raw, 'CAMBIAR_ESTA_CLAVE_MAESTRA') !== false) {
            $error = 'Debes cambiar RSU_CORREO_MASTER_KEY en includes/correo_secreto.php.';
            return false;
        }
        return true;
    }

    function cor_mail_cipher_key_raw()
    {
        $raw = defined('RSU_CORREO_MASTER_KEY') ? (string)RSU_CORREO_MASTER_KEY : '';
        return hash('sha256', $raw, true);
    }

    function cor_mail_encrypt_key($plainText, &$error = '')
    {
        $error = '';
        $plainText = trim((string)$plainText);
        if ($plainText === '') {
            $error = 'Key vacia.';
            return false;
        }
        if (!function_exists('openssl_encrypt')) {
            $error = 'OpenSSL no disponible en PHP.';
            return false;
        }
        if (!cor_mail_master_key_valid($error)) {
            return false;
        }

        $cipher = defined('RSU_CORREO_CIPHER') ? (string)RSU_CORREO_CIPHER : 'aes-256-gcm';
        $iv = random_bytes(12);
        $tag = '';
        $key = cor_mail_cipher_key_raw();
        $ct = openssl_encrypt($plainText, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
        if ($ct === false) {
            $error = 'No se pudo cifrar la key.';
            return false;
        }

        $payload = array(
            'v' => 1,
            'alg' => $cipher,
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($ct),
        );
        return base64_encode(json_encode($payload));
    }

    function cor_mail_decrypt_key($cipherText, &$error = '')
    {
        $error = '';
        $cipherText = trim((string)$cipherText);
        if ($cipherText === '') {
            $error = 'Key cifrada vacia.';
            return false;
        }
        if (!function_exists('openssl_decrypt')) {
            $error = 'OpenSSL no disponible en PHP.';
            return false;
        }
        if (!cor_mail_master_key_valid($error)) {
            return false;
        }

        $raw = base64_decode($cipherText, true);
        if ($raw === false || $raw === '') {
            $error = 'Formato de key cifrada invalido.';
            return false;
        }
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $error = 'Payload cifrado invalido.';
            return false;
        }

        $cipher = isset($payload['alg']) ? (string)$payload['alg'] : 'aes-256-gcm';
        $iv = isset($payload['iv']) ? base64_decode((string)$payload['iv'], true) : false;
        $tag = isset($payload['tag']) ? base64_decode((string)$payload['tag'], true) : false;
        $ct = isset($payload['ct']) ? base64_decode((string)$payload['ct'], true) : false;
        if ($iv === false || $tag === false || $ct === false) {
            $error = 'Payload cifrado incompleto.';
            return false;
        }

        $key = cor_mail_cipher_key_raw();
        $plain = openssl_decrypt($ct, $cipher, $key, OPENSSL_RAW_DATA, $iv, $tag, '');
        if ($plain === false || trim((string)$plain) === '') {
            $error = 'No se pudo descifrar la key.';
            return false;
        }
        return (string)$plain;
    }

    function cor_mail_fetch_row(mysqli $db)
    {
        $sql = "SELECT id, proveedor, remitente_email, remitente_nombre, smtp_usuario, smtp_host, smtp_puerto, smtp_secure,
                       app_key_cifrada, app_key_last4, correo_verificador, estado,
                       key_creada_en, key_actualizada_en, ultima_prueba_estado, ultima_prueba_detalle, ultima_prueba_en,
                       created_by, updated_by, created_at, updated_at
                  FROM cor_config_smtp
                 WHERE id = 1
                 LIMIT 1";
        $rs = @mysqli_query($db, $sql);
        if (!($rs instanceof mysqli_result)) {
            return null;
        }
        $row = mysqli_fetch_assoc($rs);
        mysqli_free_result($rs);
        return is_array($row) ? $row : null;
    }

    function cor_mail_mask_last4($last4)
    {
        $last4 = trim((string)$last4);
        if ($last4 === '') {
            return '';
        }
        return '************' . $last4;
    }

    function cor_mail_get_ui_state(mysqli $db)
    {
        $state = array(
            'exists' => false,
            'ready' => false,
            'estado' => 0,
            'blocked_reason' => 'sin_configuracion',
            'blocked_message' => 'Primero configura tu Key en Configuracion de correo.',
            'config' => array(
                'proveedor' => 'gmail',
                'remitente_email' => '',
                'remitente_nombre' => 'Sistema DIRSU',
                'smtp_usuario' => '',
                'smtp_host' => 'smtp.gmail.com',
                'smtp_puerto' => 587,
                'smtp_secure' => 'tls',
                'correo_verificador' => '',
                'app_key_mask' => '',
                'key_creada_en' => null,
                'key_actualizada_en' => null,
                'ultima_prueba_estado' => null,
                'ultima_prueba_detalle' => null,
                'ultima_prueba_en' => null,
                'created_by' => null,
                'updated_by' => null,
                'created_at' => null,
                'updated_at' => null,
            ),
        );

        $row = cor_mail_fetch_row($db);
        if (!is_array($row)) {
            return $state;
        }

        $state['exists'] = true;
        $state['estado'] = ((int)$row['estado'] === 1) ? 1 : 0;
        $state['config']['proveedor'] = (string)($row['proveedor'] ?? 'gmail');
        $state['config']['remitente_email'] = (string)($row['remitente_email'] ?? '');
        $state['config']['remitente_nombre'] = (string)($row['remitente_nombre'] ?? 'Sistema DIRSU');
        $state['config']['smtp_usuario'] = (string)($row['smtp_usuario'] ?? '');
        $state['config']['smtp_host'] = (string)($row['smtp_host'] ?? 'smtp.gmail.com');
        $state['config']['smtp_puerto'] = isset($row['smtp_puerto']) ? (int)$row['smtp_puerto'] : 587;
        $state['config']['smtp_secure'] = (string)($row['smtp_secure'] ?? 'tls');
        $state['config']['correo_verificador'] = (string)($row['correo_verificador'] ?? '');
        $state['config']['app_key_mask'] = cor_mail_mask_last4((string)($row['app_key_last4'] ?? ''));
        $state['config']['key_creada_en'] = $row['key_creada_en'] ?? null;
        $state['config']['key_actualizada_en'] = $row['key_actualizada_en'] ?? null;
        $state['config']['ultima_prueba_estado'] = $row['ultima_prueba_estado'] ?? null;
        $state['config']['ultima_prueba_detalle'] = $row['ultima_prueba_detalle'] ?? null;
        $state['config']['ultima_prueba_en'] = $row['ultima_prueba_en'] ?? null;
        $state['config']['created_by'] = $row['created_by'] ?? null;
        $state['config']['updated_by'] = $row['updated_by'] ?? null;
        $state['config']['created_at'] = $row['created_at'] ?? null;
        $state['config']['updated_at'] = $row['updated_at'] ?? null;

        if ($state['estado'] !== 1) {
            $state['blocked_reason'] = 'config_inactiva';
            $state['blocked_message'] = 'La Configuracion de correo esta inactiva.';
            return $state;
        }

        if (!cor_mail_master_key_valid($mkError)) {
            $state['blocked_reason'] = 'master_key_invalida';
            $state['blocked_message'] = $mkError;
            return $state;
        }

        $enc = isset($row['app_key_cifrada']) ? trim((string)$row['app_key_cifrada']) : '';
        if ($enc === '') {
            $state['blocked_reason'] = 'key_vacia';
            $state['blocked_message'] = 'No existe key SMTP configurada.';
            return $state;
        }

        $dec = cor_mail_decrypt_key($enc, $decError);
        if ($dec === false) {
            $state['blocked_reason'] = 'key_no_descifrable';
            $state['blocked_message'] = $decError;
            return $state;
        }

        $state['ready'] = true;
        $state['blocked_reason'] = '';
        $state['blocked_message'] = '';
        return $state;
    }

    function cor_mail_can_send_notifications(mysqli $db, &$reason = '', &$message = '')
    {
        $state = cor_mail_get_ui_state($db);
        if (!empty($state['ready'])) {
            $reason = '';
            $message = '';
            return true;
        }
        $reason = isset($state['blocked_reason']) ? (string)$state['blocked_reason'] : 'configuracion_correo_invalida';
        $message = isset($state['blocked_message']) ? (string)$state['blocked_message'] : 'Configuracion de correo invalida.';
        return false;
    }

    function cor_mail_get_active_config(mysqli $db, $withDecryptedKey = true)
    {
        $row = cor_mail_fetch_row($db);
        if (!is_array($row)) {
            return array(
                'ok' => false,
                'reason' => 'sin_configuracion',
                'message' => 'No existe configuracion de correo.',
            );
        }
        if ((int)$row['estado'] !== 1) {
            return array(
                'ok' => false,
                'reason' => 'config_inactiva',
                'message' => 'La configuracion de correo esta inactiva.',
            );
        }
        if (!cor_mail_master_key_valid($mkError)) {
            return array(
                'ok' => false,
                'reason' => 'master_key_invalida',
                'message' => $mkError,
            );
        }

        $cfg = array(
            'id' => (int)$row['id'],
            'proveedor' => (string)($row['proveedor'] ?? 'gmail'),
            'remitente_email' => (string)($row['remitente_email'] ?? ''),
            'remitente_nombre' => (string)($row['remitente_nombre'] ?? 'Sistema DIRSU'),
            'smtp_usuario' => (string)($row['smtp_usuario'] ?? ''),
            'smtp_host' => (string)($row['smtp_host'] ?? 'smtp.gmail.com'),
            'smtp_puerto' => isset($row['smtp_puerto']) ? (int)$row['smtp_puerto'] : 587,
            'smtp_secure' => (string)($row['smtp_secure'] ?? 'tls'),
            'correo_verificador' => (string)($row['correo_verificador'] ?? ''),
            'app_key_last4' => (string)($row['app_key_last4'] ?? ''),
            'app_key_cifrada' => (string)($row['app_key_cifrada'] ?? ''),
        );

        if ($withDecryptedKey) {
            $dec = cor_mail_decrypt_key((string)$cfg['app_key_cifrada'], $decError);
            if ($dec === false) {
                return array(
                    'ok' => false,
                    'reason' => 'key_no_descifrable',
                    'message' => $decError,
                );
            }
            $cfg['app_key'] = $dec;
        }

        return array('ok' => true, 'config' => $cfg);
    }

    function cor_mail_update_last_test(mysqli $db, $estado, $detalle)
    {
        $estado = strtolower(trim((string)$estado)) === 'enviado' ? 'enviado' : 'error';
        $detalle = trim((string)$detalle);
        $sql = "UPDATE cor_config_smtp
                   SET ultima_prueba_estado = ?,
                       ultima_prueba_detalle = ?,
                       ultima_prueba_en = NOW(),
                       updated_at = NOW()
                 WHERE id = 1
                 LIMIT 1";
        $st = @mysqli_prepare($db, $sql);
        if (!$st) {
            return false;
        }
        mysqli_stmt_bind_param($st, 'ss', $estado, $detalle);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        return $ok;
    }

    function cor_mail_log_test(mysqli $db, array $data)
    {
        $sql = "INSERT INTO cor_test_envios
                    (config_id, remitente_email, destino_email, asunto, estado, detalle, created_by, ip, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        $st = @mysqli_prepare($db, $sql);
        if (!$st) {
            return false;
        }
        $configId = isset($data['config_id']) ? (int)$data['config_id'] : 0;
        $rem = isset($data['remitente_email']) ? (string)$data['remitente_email'] : '';
        $dest = isset($data['destino_email']) ? (string)$data['destino_email'] : '';
        $asunto = isset($data['asunto']) ? (string)$data['asunto'] : '';
        $estado = isset($data['estado']) ? (string)$data['estado'] : 'error';
        $detalle = isset($data['detalle']) ? (string)$data['detalle'] : '';
        $createdBy = isset($data['created_by']) ? (string)$data['created_by'] : null;
        $ip = isset($data['ip']) ? (string)$data['ip'] : null;
        mysqli_stmt_bind_param($st, 'isssssss', $configId, $rem, $dest, $asunto, $estado, $detalle, $createdBy, $ip);
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        return $ok;
    }

    function cor_mail_save_config(mysqli $db, array $input, $actor = null, &$error = '')
    {
        $error = '';
        $estado = isset($input['estado']) && (int)$input['estado'] === 0 ? 0 : 1;
        $remitenteEmail = cor_mail_normalize_email(isset($input['remitente_email']) ? $input['remitente_email'] : '');
        $remitenteNombre = trim((string)(isset($input['remitente_nombre']) ? $input['remitente_nombre'] : 'Sistema DIRSU'));
        $smtpUsuario = cor_mail_normalize_email(isset($input['smtp_usuario']) ? $input['smtp_usuario'] : $remitenteEmail);
        $correoVerificador = cor_mail_normalize_email(isset($input['correo_verificador']) ? $input['correo_verificador'] : '');
        $appKeyInput = isset($input['app_key']) ? (string)$input['app_key'] : '';
        $appKey = cor_mail_normalize_app_key($appKeyInput);
        $actor = $actor !== null ? trim((string)$actor) : null;

        if ($remitenteEmail === '' || !filter_var($remitenteEmail, FILTER_VALIDATE_EMAIL)) {
            $error = 'Debes ingresar un correo remitente valido.';
            return false;
        }
        if ($smtpUsuario === '' || !filter_var($smtpUsuario, FILTER_VALIDATE_EMAIL)) {
            $error = 'Debes ingresar un usuario SMTP valido.';
            return false;
        }
        if ($correoVerificador === '' || !filter_var($correoVerificador, FILTER_VALIDATE_EMAIL)) {
            $error = 'Debes ingresar un correo verificador valido.';
            return false;
        }
        if ($remitenteNombre === '') {
            $remitenteNombre = 'Sistema DIRSU';
        }

        $current = cor_mail_fetch_row($db);
        $enc = '';
        $last4 = '';
        $keyCreadaEn = null;
        $keyActualizadaEn = null;
        if (is_array($current)) {
            $enc = isset($current['app_key_cifrada']) ? (string)$current['app_key_cifrada'] : '';
            $last4 = isset($current['app_key_last4']) ? (string)$current['app_key_last4'] : '';
            $keyCreadaEn = isset($current['key_creada_en']) ? $current['key_creada_en'] : null;
            $keyActualizadaEn = isset($current['key_actualizada_en']) ? $current['key_actualizada_en'] : null;
        }

        if ($appKey !== '') {
            $encNew = cor_mail_encrypt_key($appKey, $encError);
            if ($encNew === false) {
                $error = $encError;
                return false;
            }
            $enc = $encNew;
            $last4 = substr($appKey, -4);
            $now = cor_mail_now();
            if ($keyCreadaEn === null || trim((string)$keyCreadaEn) === '') {
                $keyCreadaEn = $now;
            }
            $keyActualizadaEn = $now;
        } elseif ($enc === '') {
            $error = 'Debes ingresar la key SMTP de Gmail.';
            return false;
        }

        $sql = "INSERT INTO cor_config_smtp
                    (id, proveedor, remitente_email, remitente_nombre, smtp_usuario, smtp_host, smtp_puerto, smtp_secure,
                     app_key_cifrada, app_key_last4, correo_verificador, estado, key_creada_en, key_actualizada_en,
                     created_by, updated_by, created_at, updated_at)
                VALUES
                    (1, 'gmail', ?, ?, ?, 'smtp.gmail.com', 587, 'tls',
                     ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE
                    proveedor = VALUES(proveedor),
                    remitente_email = VALUES(remitente_email),
                    remitente_nombre = VALUES(remitente_nombre),
                    smtp_usuario = VALUES(smtp_usuario),
                    smtp_host = VALUES(smtp_host),
                    smtp_puerto = VALUES(smtp_puerto),
                    smtp_secure = VALUES(smtp_secure),
                    app_key_cifrada = VALUES(app_key_cifrada),
                    app_key_last4 = VALUES(app_key_last4),
                    correo_verificador = VALUES(correo_verificador),
                    estado = VALUES(estado),
                    key_creada_en = VALUES(key_creada_en),
                    key_actualizada_en = VALUES(key_actualizada_en),
                    updated_by = VALUES(updated_by),
                    updated_at = NOW()";
        $st = @mysqli_prepare($db, $sql);
        if (!$st) {
            $error = 'No se pudo preparar el guardado de configuracion de correo.';
            return false;
        }
        $createdBy = $actor;
        $updatedBy = $actor;
        mysqli_stmt_bind_param(
            $st,
            'ssssssissss',
            $remitenteEmail,
            $remitenteNombre,
            $smtpUsuario,
            $enc,
            $last4,
            $correoVerificador,
            $estado,
            $keyCreadaEn,
            $keyActualizadaEn,
            $createdBy,
            $updatedBy
        );
        $ok = mysqli_stmt_execute($st);
        mysqli_stmt_close($st);
        if (!$ok) {
            $error = 'No se pudo guardar la configuracion de correo en BD.';
            return false;
        }

        return true;
    }

    function cor_mail_send_using_active_config(mysqli $db, array $to, $subject, $html, $text, &$errorDetail = '')
    {
        $errorDetail = '';
        $to = array_values(array_filter(array_map('trim', $to), function ($v) { return $v !== ''; }));
        if (empty($to)) {
            $errorDetail = 'Sin destinatarios.';
            return false;
        }

        $cfgResult = cor_mail_get_active_config($db, true);
        if (empty($cfgResult['ok'])) {
            $errorDetail = isset($cfgResult['message']) ? (string)$cfgResult['message'] : 'No hay configuracion activa.';
            return false;
        }
        $cfg = $cfgResult['config'];

        $base = realpath(__DIR__ . '/../recursos/src') ?: (__DIR__ . '/../recursos/src');
        foreach (array($base . '/PHPMailer.php', $base . '/SMTP.php', $base . '/Exception.php') as $p) {
            if (!file_exists($p)) {
                $errorDetail = 'PHPMailer no encontrado: ' . $p;
                return false;
            }
        }
        require_once $base . '/Exception.php';
        require_once $base . '/PHPMailer.php';
        require_once $base . '/SMTP.php';

        $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = (string)$cfg['smtp_host'];
            $mail->SMTPAuth = true;
            $mail->Username = (string)$cfg['smtp_usuario'];
            $mail->Password = (string)$cfg['app_key'];
            $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = (int)$cfg['smtp_puerto'];
            $mail->CharSet = 'UTF-8';

            $mail->setFrom((string)$cfg['remitente_email'], (string)$cfg['remitente_nombre']);
            $mail->addReplyTo((string)$cfg['remitente_email'], (string)$cfg['remitente_nombre']);
            foreach ($to as $addr) {
                $mail->addAddress($addr);
            }

            $mail->isHTML(true);
            $mail->Subject = (string)$subject;
            $mail->Body = (string)$html;
            $mail->AltBody = (string)$text;

            return $mail->send();
        } catch (\Throwable $e) {
            $errorDetail = $e->getMessage();
            return false;
        }
    }

    function cor_mail_run_test(mysqli $db, $createdBy = null, $ip = null)
    {
        $cfgResult = cor_mail_get_active_config($db, true);
        if (empty($cfgResult['ok'])) {
            $detail = isset($cfgResult['message']) ? (string)$cfgResult['message'] : 'No hay configuracion activa.';
            cor_mail_update_last_test($db, 'error', $detail);
            cor_mail_log_test($db, array(
                'config_id' => 1,
                'remitente_email' => '',
                'destino_email' => '',
                'asunto' => 'Prueba SMTP',
                'estado' => 'error',
                'detalle' => $detail,
                'created_by' => $createdBy,
                'ip' => $ip,
            ));
            return array('ok' => false, 'msg' => $detail, 'estado' => 'error', 'detalle' => $detail);
        }

        $cfg = $cfgResult['config'];
        $destino = (string)$cfg['correo_verificador'];
        if ($destino === '' || !filter_var($destino, FILTER_VALIDATE_EMAIL)) {
            $detail = 'El correo verificador no es valido.';
            cor_mail_update_last_test($db, 'error', $detail);
            cor_mail_log_test($db, array(
                'config_id' => (int)$cfg['id'],
                'remitente_email' => (string)$cfg['remitente_email'],
                'destino_email' => $destino,
                'asunto' => 'Prueba SMTP',
                'estado' => 'error',
                'detalle' => $detail,
                'created_by' => $createdBy,
                'ip' => $ip,
            ));
            return array('ok' => false, 'msg' => $detail, 'estado' => 'error', 'detalle' => $detail);
        }

        $fecha = date('d/m/Y H:i:s');
        $subject = 'Prueba de correo Gmail - Sistema DIRSU';
        $html = '<p>Prueba de envio SMTP Gmail.</p>'
              . '<p><strong>Fecha:</strong> ' . htmlspecialchars($fecha, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
              . '<p><strong>Remitente:</strong> ' . htmlspecialchars((string)$cfg['remitente_email'], ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') . '</p>'
              . '<p>Si recibes este correo, la configuracion se encuentra operativa.</p>';
        $text = "Prueba de envio SMTP Gmail.\nFecha: {$fecha}\nRemitente: " . (string)$cfg['remitente_email'] . "\nSi recibes este correo, la configuracion se encuentra operativa.";

        $errorDetail = '';
        $ok = cor_mail_send_using_active_config($db, array($destino), $subject, $html, $text, $errorDetail);
        if ($ok) {
            $detail = 'Correo de prueba enviado correctamente a ' . $destino . '.';
            cor_mail_update_last_test($db, 'enviado', $detail);
            cor_mail_log_test($db, array(
                'config_id' => (int)$cfg['id'],
                'remitente_email' => (string)$cfg['remitente_email'],
                'destino_email' => $destino,
                'asunto' => $subject,
                'estado' => 'enviado',
                'detalle' => $detail,
                'created_by' => $createdBy,
                'ip' => $ip,
            ));
            return array(
                'ok' => true,
                'msg' => 'Correo de prueba enviado correctamente.',
                'estado' => 'enviado',
                'detalle' => $detail,
                'destino' => $destino,
                'asunto' => $subject,
            );
        }

        $detail = $errorDetail !== '' ? $errorDetail : 'Fallo desconocido de SMTP.';
        cor_mail_update_last_test($db, 'error', $detail);
        cor_mail_log_test($db, array(
            'config_id' => (int)$cfg['id'],
            'remitente_email' => (string)$cfg['remitente_email'],
            'destino_email' => $destino,
            'asunto' => $subject,
            'estado' => 'error',
            'detalle' => $detail,
            'created_by' => $createdBy,
            'ip' => $ip,
        ));
        return array(
            'ok' => false,
            'msg' => 'No se pudo enviar el correo de prueba.',
            'estado' => 'error',
            'detalle' => $detail,
            'destino' => $destino,
            'asunto' => $subject,
        );
    }
}
