<?php
session_start();

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$config = include 'config.php';

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

        // Redirigir después de la eliminación
        header("Location: control_maquinas.php?mensaje=Máquina eliminada con éxito");
        exit();
    } catch (PDOException $error) {
        echo "Error: " . $error->getMessage();
        exit();
    }
} else {
    echo "No se ha proporcionado un ID válido para la máquina.";
    exit();
}
?>
