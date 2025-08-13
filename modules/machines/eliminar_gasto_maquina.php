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
    echo "<script>alert('Error de conexión: " . addslashes($error->getMessage()) . "'); window.location.href = 'gastos_maquinas.php';</script>";
    exit();
}

// Verificar que se recibió una solicitud POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "<script>alert('Solicitud no válida.'); window.location.href = 'gastos_maquinas.php';</script>";
    exit();
}

// Validar que se proporcione un ID válido
if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
    echo "<script>alert('ID de gasto no proporcionado.'); window.location.href = 'gastos_maquinas.php';</script>";
    exit();
}

// Validar que se proporcione el ID de la máquina para redirección
if (!isset($_POST['id_maquina']) || empty($_POST['id_maquina']) || !is_numeric($_POST['id_maquina'])) {
    echo "<script>alert('ID de máquina no proporcionado.'); window.location.href = 'gastos_maquinas.php';</script>";
    exit();
}

$id = $_POST['id'];
$id_maquina = $_POST['id_maquina'];

try {
    // Verificar que el gasto existe
    $stmt = $conexion->prepare("SELECT id FROM gasto_maquina WHERE id = ?");
    $stmt->execute([$id]);
    
    if (!$stmt->fetch()) {
        echo "<script>alert('El gasto no existe.'); window.location.href = 'historial_gastos.php?id=" . $id_maquina . "';</script>";
        exit();
    }
    
    // Eliminar el gasto
    $stmt = $conexion->prepare("DELETE FROM gasto_maquina WHERE id = ?");
    $stmt->execute([$id]);
    
    echo "<script>alert('Gasto eliminado con éxito.'); window.location.href = 'historial_gastos.php?id=" . $id_maquina . "';</script>";
    exit();
    
} catch (PDOException $error) {
    echo "<script>alert('Error al eliminar el gasto: " . addslashes($error->getMessage()) . "'); window.location.href = 'historial_gastos.php?id=" . $id_maquina . "';</script>";
    exit();
}
?>