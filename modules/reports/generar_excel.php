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

// Obtener el mes del parámetro GET
$mes = isset($_GET['mes']) ? $_GET['mes'] : date('Y-m');
$fecha_inicio = $mes . "-01";
$fecha_fin = date("Y-m-t", strtotime($fecha_inicio));

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
$sheet->setCellValue('A1', 'Reporte de Ingresos y Egresos');
$sheet->setCellValue('A2', 'Período: ' . formatearMesAñoEspanol($fecha_inicio));

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

foreach ($ingresos as $ingreso) {
    $sheet->setCellValue('A' . $row, formatearFechaEspanol($ingreso['fecha']));
    $sheet->setCellValue('B' . $row, '$' . number_format($ingreso['monto'], 2));
    $row++;
}

$sheet->setCellValue('A' . $row, 'TOTAL INGRESOS');
$sheet->setCellValue('B' . $row, '$' . number_format($totalIngresos, 2));
$sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':B' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90');
$row += 2;

// Sección de Gastos
$sheet->setCellValue('A' . $row, 'GASTOS');
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
$sheet->setCellValue('A' . $row, 'BALANCE');
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

// Agregar bordes a todas las celdas con datos
$highestRow = $sheet->getHighestRow();
$sheet->getStyle('A1:B' . $highestRow)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_' . $mes . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>