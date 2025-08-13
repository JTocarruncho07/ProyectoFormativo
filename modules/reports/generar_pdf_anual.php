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

// Crear PDF usando TCPDF
$pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Recebera Alto Blanco');
$pdf->SetTitle('Reporte Anual de Ingresos y Egresos - ' . $year);
$pdf->SetSubject('Reporte Financiero Anual');

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
$pdf->Cell(0, 10, 'Reporte Anual de Ingresos y Egresos', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 12);
$pdf->Cell(0, 10, 'Año: ' . $year, 0, 1, 'C');
$pdf->Ln(10);

// Tabla de Resumen Mensual
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 10, 'RESUMEN MENSUAL', 0, 1, 'L');
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(47.5, 8, 'Mes', 1, 0, 'C');
$pdf->Cell(47.5, 8, 'Ingresos', 1, 0, 'C');
$pdf->Cell(47.5, 8, 'Egresos', 1, 0, 'C');
$pdf->Cell(47.5, 8, 'Balance', 1, 1, 'C');

$pdf->SetFont('helvetica', '', 8);
for ($mes = 1; $mes <= 12; $mes++) {
    $pdf->Cell(47.5, 6, $meses[$mes], 1, 0, 'L');
    $pdf->Cell(47.5, 6, '$' . number_format($ingresosMensuales[$mes], 2), 1, 0, 'R');
    $pdf->Cell(47.5, 6, '$' . number_format($egresosMensuales[$mes], 2), 1, 0, 'R');
    
    // Color del balance según si es positivo o negativo
    if ($balanceMensual[$mes] >= 0) {
        $pdf->SetFillColor(144, 238, 144); // Verde claro
    } else {
        $pdf->SetFillColor(255, 182, 193); // Rosa claro
    }
    
    $pdf->Cell(47.5, 6, '$' . number_format($balanceMensual[$mes], 2), 1, 1, 'R', true);
    $pdf->SetFillColor(255, 255, 255); // Resetear color
}

// Totales anuales
$pdf->SetFont('helvetica', 'B', 9);
$pdf->Cell(47.5, 8, 'TOTAL ANUAL', 1, 0, 'C');
$pdf->Cell(47.5, 8, '$' . number_format($totalAnualIngresos, 2), 1, 0, 'R');
$pdf->Cell(47.5, 8, '$' . number_format($totalAnualEgresos, 2), 1, 0, 'R');

// Color del balance anual
if ($totalAnualBalance >= 0) {
    $pdf->SetFillColor(144, 238, 144); // Verde claro
} else {
    $pdf->SetFillColor(255, 182, 193); // Rosa claro
}

$pdf->Cell(47.5, 8, '$' . number_format($totalAnualBalance, 2), 1, 1, 'R', true);

// Generar el PDF
$filename = 'reporte_anual_' . $year . '.pdf';
$pdf->Output($filename, 'D');
?>