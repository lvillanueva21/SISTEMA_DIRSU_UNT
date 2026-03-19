<?php
require 'componentes/db.php';
require 'recursos/PHPMailer-master/src/PHPMailer.php';
require 'recursos/PHPMailer-master/src/SMTP.php';
require 'recursos/PHPMailer-master/src/Exception.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

$cliente_id = $_POST['cliente_id'];
$mensaje_id = $_POST['mensaje_id'] ?? null;
$mensaje_personalizado = trim($_POST['mensaje_personalizado'] ?? '');

// Obtener datos del cliente
$cliente = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT * FROM clientes WHERE id = $cliente_id"));
$email = $cliente['email'];
$nombre = $cliente['nombre'];

// Obtener cuerpo del mensaje predeterminado si no hay personalizado
if (empty($mensaje_personalizado) && $mensaje_id) {
    $mensaje_data = mysqli_fetch_assoc(mysqli_query($conexion, "SELECT cuerpo FROM mensajes WHERE id = $mensaje_id"));
    $mensaje_final = $mensaje_data['cuerpo'];
} else {
    $mensaje_final = $mensaje_personalizado;
}

// Configuración PHPMailer
$mail = new PHPMailer(true);
try {
    $mail->isSMTP();
    $mail->Host = 'smtp.office365.com';  // Usar este si unitru usa Outlook/Exchange
    $mail->SMTPAuth = true;
    $mail->Username = 'lvillanueva@unitru.edu.pe';  // Tu correo real
    $mail->Password = 'AQUÍ_TU_CONTRASEÑA';         // O contraseña de aplicación si es necesario
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    $mail->setFrom('lvillanueva@unitru.edu.pe', 'Notificador RSU');
    $mail->addAddress($email, $nombre);
    $mail->Subject = 'Observación sobre su Proyecto RSU';
    $mail->Body    = $mensaje_final;

    $mail->send();
    echo "✅ Correo enviado a $nombre ($email)";
} catch (Exception $e) {
    echo "❌ Error al enviar el correo: {$mail->ErrorInfo}";
}
