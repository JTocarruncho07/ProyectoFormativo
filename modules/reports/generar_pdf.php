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

// Crear PDF usando TCPDF
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Recebera Alto Blanco');
$pdf->SetTitle('Reporte de Ingresos y Egresos - ' . formatearMesAñoEspanol($fecha_inicio));
$pdf->SetSubject('Reporte Financiero');

// Configurar márgenes
$pdf->SetMargins(PDF_MARGIN_LEFT, PDF_MARGIN_TOP, PDF_MARGIN_RIGHT);
$pdf->SetHeaderMargin(PDF_MARGIN_HEADER);
$pdf->SetFooterMargin(PDF_MARGIN_FOOTER);

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, PDF_MARGIN_BOTTOM);

// Configurar factor de escala de imagen
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Agregar página
$pdf->AddPage();

// Configurar fuente
$pdf->SetFont('helvetica', '', 12);

// Título
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, 'Reporte de Ingresos y Egresos', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Período: ' . formatearMesAñoEspanol($fecha_inicio), 0, 1, 'C');
$pdf->Ln(10);

// Tabla de Ingresos
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'INGRESOS', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Fecha', 1, 0, 'C');
$pdf->Cell(95, 8, 'Monto', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
foreach ($ingresos as $ingreso) {
    $pdf->Cell(95, 6, formatearFechaEspanol($ingreso['fecha']), 1, 0, 'L');
    $pdf->Cell(95, 6, '$' . number_format($ingreso['monto'], 2), 1, 1, 'R');
}

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'TOTAL INGRESOS', 1, 0, 'C');
$pdf->Cell(95, 8, '$' . number_format($totalIngresos, 2), 1, 1, 'R');
$pdf->Ln(10);

// Tabla de Gastos
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'GASTOS', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Tipo', 1, 0, 'C');
$pdf->Cell(95, 8, 'Total', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(95, 6, 'Gastos Generales', 1, 0, 'L');
$pdf->Cell(95, 6, '$' . number_format($totalGastosGenerales, 2), 1, 1, 'R');
$pdf->Cell(95, 6, 'Gastos de Maquinaria', 1, 0, 'L');
$pdf->Cell(95, 6, '$' . number_format($totalGastosMaquina, 2), 1, 1, 'R');
$pdf->Cell(95, 6, 'Pagos a Empleados', 1, 0, 'L');
$pdf->Cell(95, 6, '$' . number_format($totalPagoEmpleados, 2), 1, 1, 'R');

$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'TOTAL GASTOS', 1, 0, 'C');
$pdf->Cell(95, 8, '$' . number_format($totalEgresos, 2), 1, 1, 'R');
$pdf->Ln(10);

// Balance
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'BALANCE', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(95, 8, 'Ingresos Totales', 1, 0, 'L');
$pdf->Cell(95, 8, '$' . number_format($totalIngresos, 2), 1, 1, 'R');
$pdf->Cell(95, 8, 'Egresos Totales', 1, 0, 'L');
$pdf->Cell(95, 8, '$' . number_format($totalEgresos, 2), 1, 1, 'R');

// Color del balance según si es positivo o negativo
if ($balance >= 0) {
    $pdf->SetFillColor(144, 238, 144); // Verde claro
} else {
    $pdf->SetFillColor(255, 182, 193); // Rosa claro
}

$pdf->Cell(95, 8, 'Balance Neto', 1, 0, 'L', true);
$pdf->Cell(95, 8, '$' . number_format($balance, 2), 1, 1, 'R', true);

// Generar el PDF
$filename = 'reporte_' . $mes . '.pdf';
$pdf->Output($filename, 'D');
?>