<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: ../../modules/auth/login.php");
    exit();
}

if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
require_once dirname(dirname(__DIR__)) . '/includes/date_utils.php';
$config = includeConfig('config.php');

// Obtener el mes actual si no se selecciona uno
$mes = isset($_POST['mes']) ? $_POST['mes'] : date('Y-m');
$fecha_inicio = $mes . "-01";
$fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

// Incluir el header
includeTemplate('header.php');

try {
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

<div class="px-3">
    <h2 class="text-center mt-5">Reporte de Ingresos y Egresos</h2>
    <!-- Selección del mes con diseño mejorado -->
    <form method="POST" class="mb-4 d-flex justify-content-center">
        <div class="w-25">
            <label for="mes">Selecciona el mes:</label>
            <input type="month" name="mes" id="mes" class="form-control" value="<?= $mes ?>" required>
        </div>
        <button type="submit" class="btn btn-primary ms-2 align-self-end">Generar</button>
    </form>
    
    <!-- Botones de exportación -->
    <div class="d-flex justify-content-center mb-4">
        <a href="generar_pdf.php?mes=<?= $mes ?>" class="btn btn-danger" style="margin-right: 8px;" target="_blank">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
        <a href="generar_excel.php?mes=<?= $mes ?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </a>
    </div>
    
    <h4>Ingresos</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>
        <tbody>
            <?php foreach ($ingresos as $ingreso): ?>
                <tr>
                    <td><?= formatearFechaEspanol($ingreso['fecha']) ?></td>
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
    </div>
    
    <h4>Gastos</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
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
    </div>
    
    <h4>Balance</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
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
</div>

<?php includeTemplate('footer.php'); ?>
