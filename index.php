<?php
require_once 'config/paths.php';
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: " . moduleUrl('machines/control_maquinas.php'));
} else {
    header("Location: " . moduleUrl('auth/login.php'));
}
exit();
?>
