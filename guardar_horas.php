<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$config = include 'config.php';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
    exit();
}

$id_maquina = $_POST['id'];
$horas_diarias = $_POST['horas_diarias'];
$estado = $_POST['estado'];
$fecha = date('Y-m-d');
$inicio_tiempo = date('Y-m-d H:i:s'); // Puedes ajustar esta parte según tu necesidad

// Verificar si ya existe un registro para hoy
$sql = "SELECT * FROM registro_horas_diarias WHERE id_maquina = :id_maquina AND fecha = :fecha";
$stmt = $conexion->prepare($sql);
$stmt->execute(['id_maquina' => $id_maquina, 'fecha' => $fecha]);
$registro = $stmt->fetch(PDO::FETCH_ASSOC);

if ($registro) {
    // Si ya existe, actualizar las horas y el estado
    $sql = "UPDATE registro_horas_diarias SET horas_diarias = :horas_diarias, estado = :estado, inicio_tiempo = :inicio_tiempo WHERE id = :id";
    $stmt = $conexion->prepare($sql);
    $stmt->execute(['horas_diarias' => $horas_diarias, 'estado' => $estado, 'inicio_tiempo' => $inicio_tiempo, 'id' => $registro['id']]);
} else {
    // Si no existe, insertar un nuevo registro
    $sql = "INSERT INTO registro_horas_diarias (id_maquina, fecha, horas_diarias, inicio_tiempo, estado) VALUES (:id_maquina, :fecha, :horas_diarias, :inicio_tiempo, :estado)";
    $stmt = $conexion->prepare($sql);
    $stmt->execute([
        'id_maquina' => $id_maquina,
        'fecha' => $fecha,
        'horas_diarias' => $horas_diarias,
        'inicio_tiempo' => $inicio_tiempo,
        'estado' => $estado
    ]);
}

// Actualizar la columna horas_trabajadas de la tabla maquinas
$sql = "UPDATE maquinas SET horas_trabajadas = horas_trabajadas + :horas_diarias WHERE id = :id_maquina";
$stmt = $conexion->prepare($sql);
$stmt->execute(['horas_diarias' => $horas_diarias, 'id_maquina' => $id_maquina]);

echo "Horas guardadas con éxito";
?>
