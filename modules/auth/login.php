<?php
session_start();

// Incluir el archivo de rutas
if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

if (isset($_SESSION['usuario'])) {
    header("Location: " . moduleUrl('machines/control_maquinas.php'));
    exit();
}

$mensaje = "";
$tipoMensaje = "";

if (isset($_GET['success']) && $_GET['success'] == 1) {
    $mensaje = "✅ Contraseña actualizada con éxito. Inicia sesión con tu nueva contraseña.";
    $tipoMensaje = "success";
} elseif (isset($_GET['error'])) {
    $mensaje = "❌ " . htmlspecialchars($_GET['error']);
    $tipoMensaje = "danger";
}

// Incluir el header usando la función includeTemplate
includeTemplate('header.php');
?>

<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 350px; background: #fff; border-radius: 10px;">
        <div class="text-center">
            <img src="<?= ROOT_URL ?>/assets/img/logo.png" alt="Icon" style="width: 80px;">
            <h3 class="mt-2">Iniciar Sesión</h3>
        </div>

        <?php if ($mensaje): ?>
            <div class="alert alert-<?= $tipoMensaje ?> text-center"><?= $mensaje ?></div>
        <?php endif; ?>

        <form action="procesar_login.php" method="POST">
            <div class="form-group mt-3">
                <label for="correo"><i class="fas fa-envelope"></i> Correo:</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <div class="form-group mt-3">
                <label for="contrasena"><i class="fas fa-lock"></i> Contraseña:</label>
                <input type="password" name="contrasena" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-warning btn-block mt-3">Ingresar</button>
        </form>

        <div class="text-center mt-2">
            <a href="RESTABLECERCONTRASEÑA/olvidecontraseña.php" class="btn btn-link">¿Olvidaste tu contraseña?</a>
        </div>
    </div>
</div>

<?php includeTemplate('footer.php'); ?>

