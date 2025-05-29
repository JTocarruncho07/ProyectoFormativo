<?php
session_start();

// Incluir el archivo de configuración
$config = include 'config.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['id'])) {
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

            // Redirigir de vuelta a la lista de empleados
            header("Location: empleados.php");
            exit();
        } catch (PDOException $error) {
            die("Error al eliminar el empleado: " . $error->getMessage());
        }
    }
} else {
    die("Método de solicitud no válido.");
}
?>
