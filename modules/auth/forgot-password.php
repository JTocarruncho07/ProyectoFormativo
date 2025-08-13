<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/paths.php';

$config = includeConfig('config.php');

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $e) {
    die("Error de conexión a la base de datos: " . $e->getMessage());
}

require_once __DIR__ . '/../vendor/autoload.php'; // Cargar PHPMailer

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (empty($_POST['correo'])) {
        $_SESSION['error'] = "Por favor, ingresa un correo electrónico.";
        header("Location: olvidecontraseña.php");
        exit();
    }

    $correo = trim($_POST['correo']);

    // Verificar si el correo existe en la base de datos
    $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE correo = ?");
    $stmt->execute([$correo]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        // Generar un token único
        $token = bin2hex(random_bytes(50));

        // Insertar el token en la base de datos
        $stmt = $conexion->prepare("INSERT INTO password_resets (id_usuario, token, created_at) VALUES (?, ?, NOW())");
        $stmt->execute([$user['id'], $token]);

        // Configurar PHPMailer
        $mail = new PHPMailer(true);

        try {
            // Configuración del servidor SMTP
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com'; 
            $mail->SMTPAuth = true;
            $mail->Username = 'jtocarruncho07@gmail.com'; // Tu correo
            $mail->Password = 'myvs ajan otqv acnh'; // Tu contraseña de aplicación de Gmail
            $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $mail->Port = 587;
            $mail->addAddress($correo);

            // Contenido del correo
            $mail->isHTML(true);
            $mail->Subject = 'Restablecer tu contraseña';
            $resetLink = "http://localhost/ProyectoFormativo/RESTABLECERCONTRASEÑA/reset-password.php?token=" . $token;
            $mail->Body = "Para restablecer tu contraseña, haz clic en el siguiente enlace: <a href='$resetLink'>Restablecer Contraseña</a>";

            // Enviar el correo
            if ($mail->send()) {
                $_SESSION['message'] = "Se ha enviado un enlace de restablecimiento a tu correo electrónico.";
            } else {
                $_SESSION['error'] = "Error al enviar el correo.";
            }
        } catch (Exception $e) {
            $_SESSION['error'] = "Error al enviar el correo: " . $mail->ErrorInfo;
        }
    } else {
        $_SESSION['error'] = "No se encontró una cuenta con ese correo electrónico.";
    }

    header("Location: olvidecontraseña.php");
    exit();
}
?>
