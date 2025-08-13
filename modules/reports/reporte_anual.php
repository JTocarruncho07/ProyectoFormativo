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

// Incluir el header
includeTemplate('header.php');

// Obtener el año actual si no se selecciona uno
$year = isset($_POST['year']) ? $_POST['year'] : date('Y');

try {
    // Configuración ya cargada arriba
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
<div class="px-3">

    <h2 class="text-center mt-5">Reporte Anual de Ingresos y Egresos</h2>

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
    
    <!-- Botones de exportación -->
    <div class="d-flex justify-content-center mb-4">
        <a href="generar_pdf_anual.php?year=<?= $year ?>" class="btn btn-danger" style="margin-right: 8px;" target="_blank">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
        <a href="generar_excel_anual.php?year=<?= $year ?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </a>
    </div>

    <!-- Tabla de Ingresos y Egresos Mensuales -->
    <h4>Resumen Mensual</h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
        <thead class="table-dark">
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
</div>

<?php includeTemplate('footer.php'); ?>
