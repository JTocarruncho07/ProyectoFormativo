<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../modules/auth/login.php");
    exit();
}

if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $error) {
    echo "<script>alert('Error de conexión: " . addslashes($error->getMessage()) . "'); window.location.href = 'nuevo_gasto.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que se proporcione un ID válido
    if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
        echo "<script>alert('No se ha proporcionado un ID válido para el gasto.'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();
    }

    $id = $_POST['id'];

    try {
        // Verificar que el gasto existe
        $stmt = $conexion->prepare("SELECT id FROM gastos WHERE id = ?");
        $stmt->execute([$id]);
        
        if ($stmt->rowCount() === 0) {
            echo "<script>alert('Gasto no encontrado.'); window.location.href = 'nuevo_gasto.php';</script>";
            exit();
        }

        // Eliminar el gasto
        $stmt = $conexion->prepare("DELETE FROM gastos WHERE id = ?");
        $stmt->execute([$id]);

        echo "<script>alert('Gasto eliminado con éxito.'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();

    } catch (PDOException $error) {
        echo "<script>alert('Error al eliminar el gasto: " . addslashes($error->getMessage()) . "'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Método de solicitud no válido.'); window.location.href = 'nuevo_gasto.php';</script>";
    exit();
}
?>