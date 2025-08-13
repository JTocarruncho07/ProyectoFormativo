<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Cargar configuración
require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $monto = $_POST['monto'];
    $fecha = date("Y-m-d");

    try {
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );

        $sql = "INSERT INTO ingresos (fecha, monto) VALUES (:fecha, :monto)";
        $stmt = $conexion->prepare($sql);
        $stmt->execute(['fecha' => $fecha, 'monto' => $monto]);

        header("Location: nueva_venta.php");
        exit();

    } catch (PDOException $error) {
        echo "Error: " . $error->getMessage();
    }
}
?>
