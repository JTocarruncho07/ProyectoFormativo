<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: control_maquinas.php");
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
?>

<?php include 'templates/header.php'; ?>

<div class="d-flex justify-content-center align-items-center vh-100">
    <div class="card shadow p-4" style="width: 350px; background: #fff; border-radius: 10px;">
        <div class="text-center">
            <img src="img/user-icon.png" alt="User Icon" style="width: 80px;">
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
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

