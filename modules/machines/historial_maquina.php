<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
require_once dirname(dirname(__DIR__)) . '/includes/date_utils.php';
$config = includeConfig('config.php');

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

// Obtener ID de la máquina
$id_maquina = isset($_GET['id']) ? $_GET['id'] : null;
if (!$id_maquina) {
    echo "ID de máquina no proporcionado";
    exit();
}

// Obtener el nombre de la máquina
$sql_maquina = "SELECT nombre FROM maquinas WHERE id = :id";
$stmt_maquina = $conexion->prepare($sql_maquina);
$stmt_maquina->execute(['id' => $id_maquina]);
$maquina = $stmt_maquina->fetch(PDO::FETCH_ASSOC);

if (!$maquina) {
    echo "Máquina no encontrada";
    exit();
}

// Obtener filtros de fecha
$filtro = isset($_GET['filtro']) ? $_GET['filtro'] : 'todo';
$parametros = ['id' => $id_maquina];

if ($filtro === 'dia') {
    $sql_historial = "SELECT fecha, horas_diarias FROM registro_horas_diarias WHERE id_maquina = :id AND fecha = CURDATE() ORDER BY fecha DESC";
} elseif ($filtro === 'mes') {
    $sql_historial = "SELECT fecha, horas_diarias FROM registro_horas_diarias WHERE id_maquina = :id AND MONTH(fecha) = MONTH(CURDATE()) AND YEAR(fecha) = YEAR(CURDATE()) ORDER BY fecha DESC";
} elseif ($filtro === 'anio') {
    $sql_historial = "SELECT fecha, horas_diarias FROM registro_horas_diarias WHERE id_maquina = :id AND YEAR(fecha) = YEAR(CURDATE()) ORDER BY fecha DESC";
} else {
    $sql_historial = "SELECT fecha, horas_diarias FROM registro_horas_diarias WHERE id_maquina = :id ORDER BY fecha DESC";
}

$stmt_historial = $conexion->prepare($sql_historial);
$stmt_historial->execute($parametros);
$historial = $stmt_historial->fetchAll(PDO::FETCH_ASSOC);

// Obtener las horas totales trabajadas
$sql_total = "SELECT SEC_TO_TIME(SUM(TIME_TO_SEC(horas_diarias))) AS horas_totales FROM registro_horas_diarias WHERE id_maquina = :id";
$stmt_total = $conexion->prepare($sql_total);
$stmt_total->execute(['id' => $id_maquina]);
$horas_totales = $stmt_total->fetch(PDO::FETCH_ASSOC)['horas_totales'] ?? '00:00:00';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Historial de <?php echo htmlspecialchars($maquina['nombre']); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php includeTemplate('header.php'); ?>
    <div class="container-fluid px-3 py-4">
        <!-- Botón de Navegación -->
        <div class="d-flex justify-content-center mb-4">
            <a href="control_maquinas.php" class="btn btn-secondary">Volver a Control de Máquinas</a>
        </div>
        
        <h2 class="text-center">Historial de <?php echo htmlspecialchars($maquina['nombre']); ?></h2>
        <h4 class="text-center">Horas Totales: <?php echo $horas_totales; ?></h4>
        

        <div class="table-responsive">
            <table class="table table-striped table-bordered mt-4">
                <thead class="table-dark">
                    <tr>
                        <th>Fecha</th>
                        <th>Horas Diarias</th>
                    </tr>
                </thead>
            <tbody>
                <?php foreach ($historial as $registro): ?>
                <tr>
                    <td><?php echo formatearFechaEspanol($registro['fecha']); ?></td>
                    <td><?php echo htmlspecialchars($registro['horas_diarias']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
            </table>
        </div>

    </div>
    <?php includeTemplate('footer.php'); ?>
</body>
</html>
