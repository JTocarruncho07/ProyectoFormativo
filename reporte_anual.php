<?php
include 'config.php';

// Obtener el año actual si no se selecciona uno
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');

try {
    $config = include 'config.php';
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Arrays para almacenar datos mensuales
    $ingresosMensuales = [];
    $egresosMensuales = [];
    $balanceMensual = [];

    // Iterar sobre los meses del año
    for ($mes = 1; $mes <= 12; $mes++) {
        $fecha_inicio = "$year-" . str_pad($mes, 2, "0", STR_PAD_LEFT) . "-01";
        $fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

        // Obtener ingresos del mes
        $stmt = $conexion->prepare("SELECT SUM(monto) AS total FROM ingresos WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $totalIngresos = $stmt->fetchColumn() ?: 0;

        // Obtener egresos generales
        $stmt = $conexion->prepare("SELECT SUM(monto) AS total FROM gastos WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $totalGastosGenerales = $stmt->fetchColumn() ?: 0;

        // Obtener gastos por máquina
        $stmt = $conexion->prepare("SELECT SUM(monto) AS total FROM gasto_maquina WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $totalGastosMaquina = $stmt->fetchColumn() ?: 0;

        // Obtener pagos a empleados y sumarlos directamente a los egresos
        $stmt = $conexion->prepare("SELECT SUM(monto) AS total FROM pagos_empleados WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $totalPagoEmpleados = $stmt->fetchColumn() ?: 0;

        // Calcular total de egresos (sumando pagos a empleados a los egresos)
        $totalEgresos = $totalGastosGenerales + $totalGastosMaquina + $totalPagoEmpleados;
        $balance = $totalIngresos - $totalEgresos;

        // Guardar valores en arrays
        $ingresosMensuales[$mes] = $totalIngresos;
        $egresosMensuales[$mes] = $totalEgresos;
        $balanceMensual[$mes] = $balance;
    }

    // Calcular totales anuales
    $totalAnualIngresos = array_sum($ingresosMensuales);
    $totalAnualEgresos = array_sum($egresosMensuales);
    $totalAnualBalance = $totalAnualIngresos - $totalAnualEgresos;

} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}

// Meses en español
$meses = [
    1 => "Enero", 2 => "Febrero", 3 => "Marzo", 4 => "Abril",
    5 => "Mayo", 6 => "Junio", 7 => "Julio", 8 => "Agosto",
    9 => "Septiembre", 10 => "Octubre", 11 => "Noviembre", 12 => "Diciembre"
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reporte Anual</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
<?php include 'templates/header.php'; ?>
<div class="container mt-5">
    <!-- Botones para Control de Máquinas y Reporte Mensual -->
    <div class="d-flex justify-content-center mb-4">
        <a href="control_maquinas.php" class="btn btn-success me-3">Ir a Control de Máquinas</a>
        <a href="reporte.php" class="btn btn-info">Ir a Reporte Mensual</a>
    </div>

    <h2 class="text-center">Reporte Anual de Ingresos y Egresos</h2>

    <!-- Selección del año con diseño mejorado -->
    <form method="POST" class="mb-4 d-flex justify-content-center">
        <div class="w-25">
            <label for="year">Selecciona el año:</label>
            <select name="year" id="year" class="form-control">
                <?php for ($i = date('Y'); $i >= 2000; $i--): ?>
                    <option value="<?= $i ?>" <?= ($year == $i) ? 'selected' : '' ?>><?= $i ?></option>
                <?php endfor; ?>
            </select>
        </div>
        <button type="submit" class="btn btn-primary ms-2 align-self-end">Generar</button>
    </form>

    <!-- Tabla de Ingresos y Egresos Mensuales -->
    <h4>Resumen Mensual</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Mes</th>
                <th>Ingresos</th>
                <th>Egresos</th>
                <th>Balance</th>
            </tr>
        </thead>
        <tbody>
            <?php for ($mes = 1; $mes <= 12; $mes++): ?>
                <tr>
                    <td><?= $meses[$mes] ?></td>
                    <td>$<?= number_format($ingresosMensuales[$mes], 2) ?></td>
                    <td>$<?= number_format($egresosMensuales[$mes], 2) ?></td>
                    <td class="<?= ($balanceMensual[$mes] >= 0) ? 'table-success' : 'table-danger' ?>">
                        $<?= number_format($balanceMensual[$mes], 2) ?>
                    </td>
                </tr>
            <?php endfor; ?>
        </tbody>
        <tfoot>
            <tr class="table-dark">
                <th>Total Anual</th>
                <th>$<?= number_format($totalAnualIngresos, 2) ?></th>
                <th>$<?= number_format($totalAnualEgresos, 2) ?></th>
                <th class="<?= ($totalAnualBalance >= 0) ? 'table-success' : 'table-danger' ?>">
                    $<?= number_format($totalAnualBalance, 2) ?>
                </th>
            </tr>
        </tfoot>
    </table>
</div>

<?php include 'templates/footer.php'; ?>
</body>
</html>
