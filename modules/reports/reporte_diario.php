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

// Obtener la fecha actual si no se selecciona una
$fecha = isset($_POST['fecha']) ? $_POST['fecha'] : date('Y-m-d');

// Incluir el header
includeTemplate('header.php');

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener ingresos del día
    $stmt = $conexion->prepare("SELECT fecha, monto FROM ingresos WHERE fecha = :fecha ORDER BY fecha");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    $ingresos = $stmt->fetchAll();
    $totalIngresos = array_sum(array_column($ingresos, 'monto'));

    // Obtener gastos generales del día
    $stmt = $conexion->prepare("SELECT fecha, monto, descripcion FROM gastos WHERE fecha = :fecha ORDER BY fecha");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    $gastosGenerales = $stmt->fetchAll();
    $totalGastosGenerales = array_sum(array_column($gastosGenerales, 'monto'));

    // Obtener gastos por máquina del día
    $stmt = $conexion->prepare("SELECT fecha, monto, tipo_gasto FROM gasto_maquina WHERE fecha = :fecha ORDER BY fecha");
    $stmt->bindParam(':fecha', $fecha);
    $stmt->execute();
    $gastosMaquina = $stmt->fetchAll();
    $totalGastosMaquina = array_sum(array_column($gastosMaquina, 'monto'));

    // Obtener pagos a empleados del día
    $stmt = $conexion->prepare("SELECT pe.fecha, pe.monto, CONCAT(e.nombre, ' ', e.apellido) as empleado FROM pagos_empleados pe INNER JOIN empleados e ON pe.empleado_id = e.id WHERE pe.fecha = :fecha ORDER BY e.nombre, e.apellido");
    $stmt->bindParam(':fecha', $fecha);
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
    <h2 class="text-center mt-5">Reporte Diario de Ingresos y Egresos</h2>
    <!-- Selección de la fecha con diseño mejorado -->
    <form method="POST" class="mb-4 d-flex justify-content-center">
        <div class="w-25">
            <label for="fecha">Selecciona la fecha:</label>
            <input type="date" name="fecha" id="fecha" class="form-control" value="<?= $fecha ?>" required>
        </div>
        <button type="submit" class="btn btn-primary ms-2 align-self-end">Generar</button>
    </form>
    
    <!-- Botones de exportación -->
    <div class="d-flex justify-content-center mb-4">
        <a href="generar_pdf_diario.php?fecha=<?= $fecha ?>" class="btn btn-danger" style="margin-right: 8px;" target="_blank">
            <i class="fas fa-file-pdf"></i> Exportar PDF
        </a>
        <a href="generar_excel_diario.php?fecha=<?= $fecha ?>" class="btn btn-success">
            <i class="fas fa-file-excel"></i> Exportar Excel
        </a>
    </div>
    
    <h4>Ingresos del <?= formatearFechaEspanol($fecha) ?></h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Monto</th>
                </tr>
            </thead>
        <tbody>
            <?php if (empty($ingresos)): ?>
                <tr>
                    <td colspan="2" class="text-center">No hay ingresos registrados para esta fecha</td>
                </tr>
            <?php else: ?>
                <?php foreach ($ingresos as $ingreso): ?>
                    <tr>
                        <td><?= formatearFechaEspanol($ingreso['fecha']) ?></td>
                        <td>$<?= number_format($ingreso['monto'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>$<?= number_format($totalIngresos, 2) ?></th>
            </tr>
        </tfoot>
        </table>
    </div>
    
    <h4>Gastos Generales del <?= formatearFechaEspanol($fecha) ?></h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Descripción</th>
                    <th>Monto</th>
                </tr>
            </thead>
        <tbody>
            <?php if (empty($gastosGenerales)): ?>
                <tr>
                    <td colspan="3" class="text-center">No hay gastos generales registrados para esta fecha</td>
                </tr>
            <?php else: ?>
                <?php foreach ($gastosGenerales as $gasto): ?>
                    <tr>
                        <td><?= formatearFechaEspanol($gasto['fecha']) ?></td>
                        <td><?= htmlspecialchars($gasto['descripcion']) ?></td>
                        <td>$<?= number_format($gasto['monto'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th>$<?= number_format($totalGastosGenerales, 2) ?></th>
            </tr>
        </tfoot>
        </table>
    </div>
    
    <h4>Gastos de Maquinaria del <?= formatearFechaEspanol($fecha) ?></h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Tipo de Gasto</th>
                    <th>Monto</th>
                </tr>
            </thead>
        <tbody>
            <?php if (empty($gastosMaquina)): ?>
                <tr>
                    <td colspan="3" class="text-center">No hay gastos de maquinaria registrados para esta fecha</td>
                </tr>
            <?php else: ?>
                <?php foreach ($gastosMaquina as $gasto): ?>
                    <tr>
                        <td><?= formatearFechaEspanol($gasto['fecha']) ?></td>
                        <td><?= htmlspecialchars($gasto['tipo_gasto']) ?></td>
                        <td>$<?= number_format($gasto['monto'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th colspan="2">Total</th>
                <th>$<?= number_format($totalGastosMaquina, 2) ?></th>
            </tr>
        </tfoot>
        </table>
    </div>
    
    <h4>Pagos a Empleados del <?= formatearFechaEspanol($fecha) ?></h4>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
                <tr>
                    <th>Empleado</th>
                    <th>Monto</th>
                </tr>
            </thead>
        <tbody>
            <?php if (empty($pagosEmpleados)): ?>
                <tr>
                    <td colspan="2" class="text-center">No hay pagos a empleados registrados para esta fecha</td>
                </tr>
            <?php else: ?>
                <?php foreach ($pagosEmpleados as $pago): ?>
                    <tr>
                        <td><?= htmlspecialchars($pago['empleado']) ?></td>
                        <td>$<?= number_format($pago['monto'], 2) ?></td>
                    </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
        <tfoot>
            <tr>
                <th>Total</th>
                <th>$<?= number_format($totalPagoEmpleados, 2) ?></th>
            </tr>
        </tfoot>
        </table>
    </div>
    
    <h4>Resumen del Día</h4>
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
    
    <h4>Balance Final</h4>
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