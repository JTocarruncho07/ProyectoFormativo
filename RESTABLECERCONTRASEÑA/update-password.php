<?php
include(__DIR__ . '/../config.php');

$conexion = new PDO(
    'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
    $config['db']['user'],
    $config['db']['pass'],
    $config['db']['options']
);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);

    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($password !== $confirm_password) {
        $_SESSION['error'] = "Las contraseñas no coinciden.";
        header("Location: reset-password.php?token=" . urlencode($token));
        exit();
    }

    // Verificar si el token es válido y no ha expirado
    $stmt = $conexion->prepare("SELECT id FROM password_resets WHERE token = ? AND created_at >= NOW() - INTERVAL 1 HOUR");
    $stmt->execute([$token]);
    $resetRequest = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$resetRequest) {
        $_SESSION['error'] = "El enlace de restablecimiento es inválido o ha expirado.";
        header("Location: forgot-password.php");
        exit();
    }

    $hashedPassword = password_hash($password, PASSWORD_BCRYPT);

    // Actualizar la contraseña en la tabla `usuarios`
    $stmt = $conexion->prepare("UPDATE usuarios SET contraseña = ? WHERE id = ?");
    $stmt->execute([$hashedPassword, $resetRequest['id']]);

    if ($stmt->rowCount() > 0) {
        // Eliminar el token después de actualizar la contraseña
        $stmt = $conexion->prepare("DELETE FROM password_resets WHERE token = ?");
        $stmt->execute([$token]);

        $_SESSION['message'] = "Tu contraseña ha sido restablecida con éxito.";
        header("Location: ../login.php");
    } else {
        $_SESSION['error'] = "No se pudo actualizar la contraseña.";
        header("Location: reset-password.php?token=" . urlencode($token));
    }
    exit();
}
?>
