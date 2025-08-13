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

// Obtener el año del parámetro GET
$year = isset($_GET['year']) ? $_GET['year'] : date('Y');

try {
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

        // Obtener pagos a empleados
        $stmt = $conexion->prepare("SELECT SUM(monto) AS total FROM pagos_empleados WHERE fecha BETWEEN :fecha_inicio AND :fecha_fin");
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':fecha_fin', $fecha_fin);
        $stmt->execute();
        $totalPagoEmpleados = $stmt->fetchColumn() ?: 0;

        // Calcular total de egresos
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
$sheet->setCellValue('A1', 'Reporte Anual de Ingresos y Egresos');
$sheet->setCellValue('A2', 'Año: ' . $year);

// Estilo del título
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12);
$sheet->getStyle('A1:A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Merge cells para el título
$sheet->mergeCells('A1:D1');
$sheet->mergeCells('A2:D2');

$row = 4;

// Sección de Resumen Mensual
$sheet->setCellValue('A' . $row, 'RESUMEN MENSUAL');
$sheet->getStyle('A' . $row)->getFont()->setBold(true)->setSize(14);
$row++;

$sheet->setCellValue('A' . $row, 'Mes');
$sheet->setCellValue('B' . $row, 'Ingresos');
$sheet->setCellValue('C' . $row, 'Egresos');
$sheet->setCellValue('D' . $row, 'Balance');
$sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('E6E6FA');
$row++;

for ($mes = 1; $mes <= 12; $mes++) {
    $sheet->setCellValue('A' . $row, $meses[$mes]);
    $sheet->setCellValue('B' . $row, '$' . number_format($ingresosMensuales[$mes], 2));
    $sheet->setCellValue('C' . $row, '$' . number_format($egresosMensuales[$mes], 2));
    $sheet->setCellValue('D' . $row, '$' . number_format($balanceMensual[$mes], 2));
    
    // Color del balance según si es positivo o negativo
    if ($balanceMensual[$mes] >= 0) {
        $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90'); // Verde claro
    } else {
        $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1'); // Rosa claro
    }
    
    $row++;
}

// Totales anuales
$sheet->setCellValue('A' . $row, 'TOTAL ANUAL');
$sheet->setCellValue('B' . $row, '$' . number_format($totalAnualIngresos, 2));
$sheet->setCellValue('C' . $row, '$' . number_format($totalAnualEgresos, 2));
$sheet->setCellValue('D' . $row, '$' . number_format($totalAnualBalance, 2));
$sheet->getStyle('A' . $row . ':D' . $row)->getFont()->setBold(true);
$sheet->getStyle('A' . $row . ':C' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('D3D3D3'); // Gris claro

// Color del balance anual
if ($totalAnualBalance >= 0) {
    $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('90EE90'); // Verde claro
} else {
    $sheet->getStyle('D' . $row)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('FFB6C1'); // Rosa claro
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
header('Content-Disposition: attachment;filename="reporte_anual_' . $year . '.xlsx"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>