<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'recursos/src/PHPMailer.php';
require 'recursos/src/SMTP.php';
require 'recursos/src/Exception.php';

$mensajeEnviado = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Valores por defecto (puedes cambiarlos en el form)
    $destino = $_POST['destino'] ?? 'luigi13.livp@gmail.com';
    $asunto  = $_POST['asunto']  ?? 'Sin asunto';
    $mensaje = $_POST['mensaje'] ?? '';

    // Saneado básico recomendado
    $destino = filter_var($destino, FILTER_VALIDATE_EMAIL) ? $destino : '';
    $asunto  = str_replace(["\r", "\n"], '', $asunto);
    $mensaje = trim($mensaje);

    $mail = new PHPMailer(true);

    try {
        // --- Configuración SMTP (Gmail) ---
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'proyectosdirsu@unitru.edu.pe'; // cuenta remitente
        $mail->Password   = 'owmjcvzzurfnocgq'; // <-- sin espacios
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;   // STARTTLS
        $mail->Port       = 587;
        $mail->CharSet    = 'UTF-8';

        // --- Cabeceras / Remitente ---
        $mail->setFrom('proyectosdirsu@unitru.edu.pe', 'DIRSU Proyectos');
        $mail->addReplyTo('proyectosdirsu@unitru.edu.pe', 'DIRSU Proyectos');

        // --- Destinatario ---
        if (!$destino) {
            throw new Exception('Email de destino inválido');
        }
        $mail->addAddress($destino);

        // --- Contenido ---
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        // Escapa el cuerpo por seguridad; conserva saltos de línea como <br>
        $mail->Body    = nl2br(htmlspecialchars($mensaje, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'));
        $mail->AltBody = $mensaje; // versión texto plano

        // --- Enviar ---
        $mail->send();
        $mensajeEnviado = '✅ Mensaje enviado correctamente.';
    } catch (Exception $e) {
        $mensajeEnviado = "❌ Error al enviar el mensaje: {$mail->ErrorInfo}";
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Enviar correo con PHPMailer</title>
</head>
<body>
<h2>Enviar correo</h2>
<?php if($mensajeEnviado): ?>
    <p><?php echo $mensajeEnviado; ?></p>
<?php endif; ?>

<form method="post">
    <label>Para:</label><br>
    <input type="email" name="destino" value="luigi13.livp@gmail.com" required><br><br>

    <label>Asunto:</label><br>
    <input type="text" name="asunto" value="Hola desde PHPMailer"><br><br>

    <label>Mensaje:</label><br>
    <textarea name="mensaje" rows="6" cols="40">Este es un mensaje de prueba</textarea><br><br>

    <button type="submit">Enviar</button>
</form>
</body>
</html>
