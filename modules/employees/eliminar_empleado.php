<?php
session_start();

// Incluir el archivo de configuración
require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id']) && !empty($_POST['id'])) {
        $id = $_POST['id'];

        try {
            // Conectar a la base de datos
            $conexion = new PDO(
                'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
                $config['db']['user'],
                $config['db']['pass'],
                $config['db']['options']
            );

            // Eliminar el empleado
            $stmt = $conexion->prepare("DELETE FROM empleados WHERE id = :id");
            $stmt->bindParam(':id', $id);
            $stmt->execute();

            // Mostrar mensaje de éxito y redirigir
            echo "<script>alert('Empleado eliminado con éxito.'); window.location.href='empleados.php';</script>";
            exit();
        } catch (PDOException $error) {
            echo "<script>alert('Error al eliminar el empleado: " . addslashes($error->getMessage()) . "'); window.location.href='empleados.php';</script>";
            exit();
        }
    } else {
        echo "<script>alert('No se ha proporcionado un ID válido para el empleado.'); window.location.href='empleados.php';</script>";
        exit();
    }
} else {
    echo "<script>alert('Método de solicitud no válido.'); window.location.href='empleados.php';</script>";
    exit();
}
?>
