<?php
include 'config.php';
include 'templates/header.php';

// Obtener el mes actual si no se selecciona uno
$mes = isset($_POST['mes']) ? $_POST['mes'] : date('Y-m');
$fecha_inicio = $mes . "-01";
$fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

try {
    $config = include 'config.php';
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener ingresos
    $stmt = $conexion->prepare("SELECT fecha, monto FROM ingresos WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $ingresos = $stmt->fetchAll();
    $totalIngresos = array_sum(array_column($ingresos, 'monto'));

    // Obtener gastos generales
    $stmt = $conexion->prepare("SELECT fecha, monto, descripcion FROM gastos WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $gastosGenerales = $stmt->fetchAll();
    $totalGastosGenerales = array_sum(array_column($gastosGenerales, 'monto'));

    // Obtener gastos por máquina
    $stmt = $conexion->prepare("SELECT fecha, monto, tipo_gasto FROM gasto_maquina WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $gastosMaquina = $stmt->fetchAll();
    $totalGastosMaquina = array_sum(array_column($gastosMaquina, 'monto'));

    // Obtener pagos a empleados
    $stmt = $conexion->prepare("SELECT fecha, monto FROM pagos_empleados WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin ORDER BY fecha");
    $stmt->bindParam(':fecha_inicio', $fecha_inicio);
    $stmt->bindParam(':fecha_fin', $fecha_fin);
    $stmt->execute();
    $pagosEmpleados = $stmt->fetchAll();
    $totalPagoEmpleados = array_sum(array_column($pagosEmpleados, 'monto'));

    // Calcular total de egresos y balance final
    $totalEgresos = $totalGastosGenerales + $totalGastosMaquina + $totalPagoEmpleados;
    $balance = $totalIngresos - $totalEgresos;

} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}
?>

<div class="container mt-5">
    <!-- Botones de Navegación -->
    <div class="d-flex justify-content-center mb-4">
        <a href="control_maquinas.php" class="btn btn-success me-3">Ir a Control de Máquinas</a>
        <a href="reporte_anual.php" class="btn btn-info">Ir a Reporte Anual</a>
    </div>

    <h2 class="text-center">Reporte de Ingresos y Egresos</h2>
    <form method="POST" class="mb-4 d-flex justify-content-center" style="width: 33%; margin: 0 auto;">
        <label for="mes" class="me-2">Mes:</label>
        <input type="month" name="mes" class="form-control form-control-sm" value="<?= $mes ?>" required>
        <button type="submit" class="btn btn-primary btn-sm ms-2">Generar</button>
    </form>
    
    <h4>Ingresos</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ingresos as $ingreso): ?>
                <tr>
                    <td><?= $ingreso['fecha'] ?></td>
                    <td>$<?= number_format($ingreso['monto'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>$<?= number_format($totalIngresos, 2) ?></th>
            </tr>
        </tfoot>
    </table>
    
    <h4>Gastos</h4>
    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Tipo</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Gastos Generales</td>
                <td>$<?= number_format($totalGastosGenerales, 2) ?></td>
            </tr>
            <tr>
                <td>Gastos de Maquinaria</td>
                <td>$<?= number_format($totalGastosMaquina, 2) ?></td>
            </tr>
            <tr>
                <td>Pagos a Empleados</td>
                <td>$<?= number_format($totalPagoEmpleados, 2) ?></td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <th>Total Gastos</th>
                <th>$<?= number_format($totalGastosGenerales + $totalGastosMaquina + $totalPagoEmpleados, 2) ?></th>
            </tr>
        </tfoot>
    </table>
    
    <h4>Balance</h4>
    <table class="table table-bordered">
        <tr>
            <th>Ingresos Totales</th>
            <td>$<?= number_format($totalIngresos, 2) ?></td>
        </tr>
        <tr>
            <th>Egresos Totales</th>
            <td>$<?= number_format($totalEgresos, 2) ?></td>
        </tr>
        <tr class="<?= ($balance >= 0) ? 'table-success' : 'table-danger' ?>">
            <th>Balance Neto</th>
            <td>$<?= number_format($balance, 2) ?></td>
        </tr>
    </table>
</div>

<?php include 'templates/footer.php'; ?>
