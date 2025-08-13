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
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

$config = includeConfig('config.php');

// Obtener la fecha del parámetro GET
$fecha = isset($_GET['fecha']) ? $_GET['fecha'] : date('Y-m-d');

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

// Crear Excel usando PhpSpreadsheet
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Font;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar título
$sheet->setCellValue('A1', 'Reporte Diario de Ingresos y Egresos');
$sheet->setCellValue('A2', 'Fecha: ' . formatearFechaEspanol($fecha));

// Estilo del título
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Merge cells para el título
$sheet->mergeCells('A1:D1');
$sheet->mergeCells('A2:D2');

$row = 4;

// Sección de Ingresos
$sheet->setCellValue('A' . $row, 'INGRESOS');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Fecha');
$sheet->setCellValue('B' . $row, 'Monto');
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

if (empty($ingresos)) {
    $sheet->setCellValue('A' . $row, 'No hay ingresos registrados para esta fecha');
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
} else {
    foreach ($ingresos as $ingreso) {
        $sheet->setCellValue('A' . $row, formatearFechaEspanol($ingreso['fecha']));
        $sheet->setCellValue('B' . $row, '$' . number_format($ingreso['monto'], 2));
        $row++;
    }
}

$sheet->setCellValue('A' . $row, 'TOTAL INGRESOS');
$sheet->setCellValue('B' . $row, '$' . number_format($totalIngresos, 2));
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90');
$row += 2;

// Sección de Gastos Generales
$sheet->setCellValue('A' . $row, 'GASTOS GENERALES');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Fecha');
$sheet->setCellValue('B' . $row, 'Descripción');
$sheet->setCellValue('C' . $row, 'Monto');
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

if (empty($gastosGenerales)) {
    $sheet->setCellValue('A' . $row, 'No hay gastos generales registrados para esta fecha');
    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
} else {
    foreach ($gastosGenerales as $gasto) {
        $sheet->setCellValue('A' . $row, formatearFechaEspanol($gasto['fecha']));
        $sheet->setCellValue('B' . $row, $gasto['descripcion']);
        $sheet->setCellValue('C' . $row, '$' . number_format($gasto['monto'], 2));
        $row++;
    }
}

$sheet->setCellValue('A' . $row, 'TOTAL GASTOS GENERALES');
$sheet->setCellValue('C' . $row, '$' . number_format($totalGastosGenerales, 2));
$sheet->mergeCells('A' . $row . ':B' . $row);
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1');
$row += 2;

// Sección de Gastos de Maquinaria
$sheet->setCellValue('A' . $row, 'GASTOS DE MAQUINARIA');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Fecha');
$sheet->setCellValue('B' . $row, 'Tipo de Gasto');
$sheet->setCellValue('C' . $row, 'Monto');
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

if (empty($gastosMaquina)) {
    $sheet->setCellValue('A' . $row, 'No hay gastos de maquinaria registrados para esta fecha');
    $sheet->mergeCells('A' . $row . ':C' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
} else {
    foreach ($gastosMaquina as $gasto) {
        $sheet->setCellValue('A' . $row, formatearFechaEspanol($gasto['fecha']));
        $sheet->setCellValue('B' . $row, $gasto['tipo_gasto']);
        $sheet->setCellValue('C' . $row, '$' . number_format($gasto['monto'], 2));
        $row++;
    }
}

$sheet->setCellValue('A' . $row, 'TOTAL GASTOS MAQUINARIA');
$sheet->setCellValue('C' . $row, '$' . number_format($totalGastosMaquina, 2));
$sheet->mergeCells('A' . $row . ':B' . $row);
$sheet->getStyle('A' . $row . ':C' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1');
$row += 2;

// Sección de Pagos a Empleados
$sheet->setCellValue('A' . $row, 'PAGOS A EMPLEADOS');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Empleado');
$sheet->setCellValue('B' . $row, 'Monto');
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

if (empty($pagosEmpleados)) {
    $sheet->setCellValue('A' . $row, 'No hay pagos a empleados registrados para esta fecha');
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $row++;
} else {
    foreach ($pagosEmpleados as $pago) {
        $sheet->setCellValue('A' . $row, $pago['empleado']);
        $sheet->setCellValue('B' . $row, '$' . number_format($pago['monto'], 2));
        $row++;
    }
}

$sheet->setCellValue('A' . $row, 'TOTAL PAGOS EMPLEADOS');
$sheet->setCellValue('B' . $row, '$' . number_format($totalPagoEmpleados, 2));
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1');
$row += 2;

// Sección de Resumen de Gastos
$sheet->setCellValue('A' . $row, 'RESUMEN DE GASTOS');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Tipo');
$sheet->setCellValue('B' . $row, 'Total');
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

$sheet->setCellValue('A' . $row, 'Gastos Generales');
$sheet->setCellValue('B' . $row, '$' . number_format($totalGastosGenerales, 2));
$row++;

$sheet->setCellValue('A' . $row, 'Gastos de Maquinaria');
$sheet->setCellValue('B' . $row, '$' . number_format($totalGastosMaquina, 2));
$row++;

$sheet->setCellValue('A' . $row, 'Pagos a Empleados');
$sheet->setCellValue('B' . $row, '$' . number_format($totalPagoEmpleados, 2));
$row++;

$sheet->setCellValue('A' . $row, 'TOTAL GASTOS');
$sheet->setCellValue('B' . $row, '$' . number_format($totalEgresos, 2));
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1');
$row += 2;

// Sección de Balance
$sheet->setCellValue('A' . $row, 'BALANCE FINAL');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Ingresos Totales');
$sheet->setCellValue('B' . $row, '$' . number_format($totalIngresos, 2));
$row++;

$sheet->setCellValue('A' . $row, 'Egresos Totales');
$sheet->setCellValue('B' . $row, '$' . number_format($totalEgresos, 2));
$row++;

$sheet->setCellValue('A' . $row, 'Balance Neto');
$sheet->setCellValue('B' . $row, '$' . number_format($balance, 2));
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);

// Color del balance según si es positivo o negativo
if ($balance >= 0) {
    $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90'); // Verde claro
} else {
    $sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1'); // Rosa claro
}

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);
$sheet->getColumnDimension('D')->setAutoSize(true);

// Agregar bordes a todas las celdas con datos
$highestRow = $sheet->getHighestRow();
$sheet->getStyle('A1:D' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_diario_' . $fecha . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>