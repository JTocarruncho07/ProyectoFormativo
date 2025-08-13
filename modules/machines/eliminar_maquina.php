<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

// Verificar si se ha recibido un ID de máquina
if (isset($_POST['id'])) {
    try {
        // Establecer conexión a la base de datos
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );
        
        // Preparar la consulta para eliminar la máquina
        $sql = "DELETE FROM maquinas WHERE id = :id";
        $stmt = $conexion->prepare($sql);
        $stmt->bindParam(':id', $_POST['id'], PDO::PARAM_INT);
        $stmt->execute();

        // Mostrar alert y redirigir después de la eliminación
        echo "<script>alert('Máquina eliminada con éxito.'); window.location.href='control_maquinas.php';</script>";
        exit();
    } catch (PDOException $error) {
        echo "<script>alert('Error al eliminar la máquina: " . addslashes($error->getMessage()) . "'); window.location.href='control_maquinas.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('No se ha proporcionado un ID válido para la máquina.'); window.location.href='control_maquinas.php';</script>";
    exit();
}
?>
