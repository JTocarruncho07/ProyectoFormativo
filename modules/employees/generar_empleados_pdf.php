<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/paths.php';
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$config = includeConfig('config.php');

try {
    // Establecer conexión a la base de datos
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener todos los empleados
    $stmt = $conexion->prepare("SELECT * FROM empleados ORDER BY nombre, apellido");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}

// Crear instancia de TCPDF
$pdf = new \TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);

// Configurar documento
$pdf->SetCreator('Sistema de Gestión');
$pdf->SetAuthor('Empresa');
$pdf->SetTitle('Reporte de Empleados');
$pdf->SetSubject('Lista completa de empleados');

// Configurar márgenes
$pdf->SetMargins(10, 15, 10);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Agregar página
$pdf->AddPage();

// Configurar fuente
$pdf->SetFont('helvetica', 'B', 16);

// Título
$pdf->Cell(0, 10, 'REPORTE DE EMPLEADOS', 0, 1, 'C');
$pdf->Ln(5);

// Fecha de generación
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
$pdf->Ln(5);

// Encabezados de tabla
$pdf->SetFont('helvetica', 'B', 9);
$pdf->SetFillColor(52, 58, 64);
$pdf->SetTextColor(255, 255, 255);

// Definir anchos de columnas para formato horizontal (ocupando todo el ancho)
// Ancho total disponible: 277mm (A4 horizontal - márgenes)
$w = array(35, 35, 32, 28, 20, 30, 30, 26, 30);

$pdf->Cell($w[0], 9, 'Nombre', 1, 0, 'C', true);
$pdf->Cell($w[1], 9, 'Apellido', 1, 0, 'C', true);
$pdf->Cell($w[2], 9, 'Documento', 1, 0, 'C', true);
$pdf->Cell($w[3], 9, 'Teléfono', 1, 0, 'C', true);
$pdf->Cell($w[4], 9, 'Tipo Sangre', 1, 0, 'C', true);
$pdf->Cell($w[5], 9, 'Fecha Inicio', 1, 0, 'C', true);
$pdf->Cell($w[6], 9, 'Fecha Nacimiento', 1, 0, 'C', true);
$pdf->Cell($w[7], 9, 'Sueldo', 1, 0, 'C', true);
$pdf->Cell($w[8], 9, 'Estado Pago', 1, 1, 'C', true);

// Datos de empleados
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0);
$fill = false;

foreach ($empleados as $empleado) {
    $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
    
    $pdf->Cell($w[0], 7, $empleado['nombre'], 1, 0, 'L', $fill);
    $pdf->Cell($w[1], 7, $empleado['apellido'], 1, 0, 'L', $fill);
    $pdf->Cell($w[2], 7, $empleado['documento'], 1, 0, 'C', $fill);
    $pdf->Cell($w[3], 7, $empleado['telefono'], 1, 0, 'C', $fill);
    $pdf->Cell($w[4], 7, $empleado['tipo_sangre'] ?? 'N/A', 1, 0, 'C', $fill);
    $pdf->Cell($w[5], 7, isset($empleado['fecha_inicio']) ? date('d/m/Y', strtotime($empleado['fecha_inicio'])) : 'N/A', 1, 0, 'C', $fill);
    $pdf->Cell($w[6], 7, isset($empleado['fecha_nacimiento']) ? date('d/m/Y', strtotime($empleado['fecha_nacimiento'])) : 'N/A', 1, 0, 'C', $fill);
    $pdf->Cell($w[7], 7, '$' . number_format($empleado['sueldo'], 2), 1, 0, 'R', $fill);
    $pdf->Cell($w[8], 7, (isset($empleado['pago_quincena']) && $empleado['pago_quincena'] == 1) ? 'Realizado' : 'Pendiente', 1, 1, 'C', $fill);
    
    $fill = !$fill;
}

// Resumen
$pdf->Ln(5);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 6, 'RESUMEN:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 9);
$pdf->Cell(0, 5, 'Total de empleados: ' . count($empleados), 0, 1, 'L');

$totalSueldos = array_sum(array_column($empleados, 'sueldo'));
$pdf->Cell(0, 5, 'Total en sueldos: $' . number_format($totalSueldos, 2), 0, 1, 'L');

$empleadosPagados = array_filter($empleados, function($emp) { return isset($emp['pago_quincena']) && $emp['pago_quincena'] == 1; });
$empleadosPendientes = count($empleados) - count($empleadosPagados);
$pdf->Cell(0, 5, 'Empleados con pago realizado: ' . count($empleadosPagados), 0, 1, 'L');
$pdf->Cell(0, 5, 'Empleados con pago pendiente: ' . $empleadosPendientes, 0, 1, 'L');

// Generar PDF
$pdf->Output('reporte_empleados_' . date('Y-m-d') . '.pdf', 'I');
?>