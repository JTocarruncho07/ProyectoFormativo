<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Incluir el archivo de rutas
if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

// Incluir el header usando la función includeTemplate
includeTemplate('header.php');
?>
    <div class="d-flex justify-content-center align-items-center vh-100">
        <div class="card shadow p-4" style="width: 350px; background: #fff; border-radius: 10px;">
            <div class="text-center">
                <h3 class="mt-2">Recuperar Contraseña</h3>
            </div>
        <?php
        if (isset($_SESSION['message'])) {
            echo '<p class="info"  style="color:green;">' . $_SESSION['message'] . '</p>';
            unset($_SESSION['message']);
        }
        if (isset($_SESSION['error'])) {
            echo '<p class="info" style="color: red;">' . $_SESSION['error'] . '</p>';
            unset($_SESSION['error']);
        }
        ?>
        <form action="forgot-password.php" method="POST">
            <div class="form-group mt-3">
                <label for="correo"><i class="fas fa-envelope"></i> Correo:</label>
                <input type="email" name="correo" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-warning btn-block mt-3">Ingresar</button>
        </form>
        <div class="text-center mt-2">
            <a href="login.php" class="btn btn-link">Volver al inicio de sesión</a>
        </div>
    </div>
</div>

<?php includeTemplate('footer.php'); ?>
