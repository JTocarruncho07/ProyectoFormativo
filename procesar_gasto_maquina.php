<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$config = include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $maquina_id = $_POST['maquina_id'] ?? null;
    $tipo_gasto = $_POST['tipo_gasto'] ?? null;
    $monto = $_POST['monto'] ?? null;
    $descripcion = $_POST['descripcion'] ?? '';
    
    if (!$maquina_id || !$tipo_gasto || !$monto) {
        header("Location: registrar_gasto_maquina.php?id=" . $maquina_id . "&error=Faltan datos obligatorios");
        exit();
    }
    
    try {
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );

        $stmt = $conexion->prepare("INSERT INTO gasto_maquina (id_maquina, tipo_gasto, monto, descripcion, fecha) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute([$maquina_id, $tipo_gasto, $monto, $descripcion]);
        
        header("Location: gastos_maquinas.php?success=Gasto registrado correctamente");
        exit();
    } catch (PDOException $error) {
        header("Location: registrar_gasto_maquina.php?id=" . $maquina_id . "&error=" . urlencode($error->getMessage()));
        exit();
    }
} else {
    header("Location: gastos_maquinas.php");
    exit();
}
