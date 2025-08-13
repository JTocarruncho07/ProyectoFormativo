<?php
session_start();

// Incluir el archivo de rutas
if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

// Incluir el header
includeTemplate('header.php');

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Error de conexión: " . $e->getMessage());
}

// Verificar si el token existe en password_resets
$token = $_GET['token'] ?? '';
if (!$token) {
    die("❌ Token no válido.");
}

$stmt = $conexion->prepare("SELECT id_usuario FROM password_resets WHERE token = ?");
$stmt->execute([$token]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    die("❌ Token inválido o expirado. No se encontró en password_resets.");
} else {
    $id_usuario = $user['id_usuario'];
}

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $error = "⚠️ Todos los campos son obligatorios.";
    } elseif ($password !== $confirm_password) {
        $error = "❌ Las contraseñas no coinciden.";
    } else {
        $new_password = password_hash($password, PASSWORD_DEFAULT);

        try {
            // Verificar si el usuario existe en la tabla usuarios
            $stmt = $conexion->prepare("SELECT id FROM usuarios WHERE id = ?");
            $stmt->execute([$id_usuario]);
            $usuario_existente = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$usuario_existente) {
                die("❌ El usuario no existe en la tabla usuarios.");
            }

            // Actualizar la contraseña en la tabla usuarios (usando id)
            $stmt = $conexion->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->execute([$new_password, $id_usuario]);

            // Eliminar el token usado en password_resets
            $stmt = $conexion->prepare("DELETE FROM password_resets WHERE token = ?");
            $stmt->execute([$token]);

            $_SESSION['message'] = "✅ Contraseña actualizada con éxito.";
            header("Location: ../login.php?success=1");
            exit();

        } catch (PDOException $e) {
            $error = "❌ Error al actualizar la contraseña: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Restablecer Contraseña</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card shadow-lg p-4">
                    <h2 class="text-center">Restablecer Contraseña</h2>
                    <?php if ($error): ?>
                        <div class="alert alert-danger text-center"> <?= $error ?> </div>
                    <?php endif; ?>
                    <form method="post">
                        <div class="mb-3">
                            <label for="password" class="form-label">Nueva Contraseña</label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="mb-3">
                            <label for="confirm_password" class="form-label">Confirmar Contraseña</label>
                            <input type="password" class="form-control" name="confirm_password" required>
                        </div>
                        <button type="submit" class="btn btn-warning w-100">Actualizar Contraseña</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php includeTemplate('footer.php'); ?>
