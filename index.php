<?php
session_start();
if (isset($_SESSION['usuario'])) {
    header("Location: control_maquinas.php");
} else {
    header("Location: login.php");
}
exit();
?>
