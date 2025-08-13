<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/paths.php';
require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
$config = includeConfig('config.php');

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Font;

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

// Crear nueva hoja de cálculo
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator('Sistema de Gestión')
    ->setLastModifiedBy('Sistema de Gestión')
    ->setTitle('Reporte de Empleados')
    ->setSubject('Lista completa de empleados')
    ->setDescription('Reporte generado automáticamente con todos los empleados registrados');

// Título principal
$sheet->setCellValue('A1', 'REPORTE DE EMPLEADOS');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Fecha de generación
$sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Encabezados de columnas
$headers = ['Nombre', 'Apellido', 'Documento', 'Teléfono', 'Tipo Sangre', 'Fecha Inicio', 'Fecha Nacimiento', 'Sueldo', 'Estado Pago'];
$columns = ['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I'];

for ($i = 0; $i < count($headers); $i++) {
    $sheet->setCellValue($columns[$i] . '4', $headers[$i]);
}

// Estilo para encabezados
$headerStyle = [
    'font' => [
        'bold' => true,
        'color' => ['rgb' => 'FFFFFF']
    ],
    'fill' => [
        'fillType' => Fill::FILL_SOLID,
        'startColor' => ['rgb' => '343A40']
    ],
    'alignment' => [
        'horizontal' => Alignment::HORIZONTAL_CENTER,
        'vertical' => Alignment::VERTICAL_CENTER
    ],
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ]
];

$sheet->getStyle('A4:I4')->applyFromArray($headerStyle);

// Datos de empleados
$row = 5;
foreach ($empleados as $empleado) {
    $sheet->setCellValue('A' . $row, $empleado['nombre']);
    $sheet->setCellValue('B' . $row, $empleado['apellido']);
    $sheet->setCellValue('C' . $row, $empleado['documento']);
    $sheet->setCellValue('D' . $row, $empleado['telefono']);
    $sheet->setCellValue('E' . $row, $empleado['tipo_sangre'] ?? 'N/A');
    $sheet->setCellValue('F' . $row, isset($empleado['fecha_inicio']) ? date('d/m/Y', strtotime($empleado['fecha_inicio'])) : 'N/A');
    $sheet->setCellValue('G' . $row, isset($empleado['fecha_nacimiento']) ? date('d/m/Y', strtotime($empleado['fecha_nacimiento'])) : 'N/A');
    $sheet->setCellValue('H' . $row, '$' . number_format($empleado['sueldo'], 2));
    $sheet->setCellValue('I' . $row, (isset($empleado['pago_quincena']) && $empleado['pago_quincena'] == 1) ? 'Realizado' : 'Pendiente');
    
    $row++;
}

// Estilo para datos
$dataStyle = [
    'borders' => [
        'allBorders' => [
            'borderStyle' => Border::BORDER_THIN
        ]
    ],
    'alignment' => [
        'vertical' => Alignment::VERTICAL_CENTER
    ]
];

$lastRow = $row - 1;
$sheet->getStyle('A4:I' . $lastRow)->applyFromArray($dataStyle);

// Alternar colores de filas
for ($i = 5; $i <= $lastRow; $i++) {
    if ($i % 2 == 0) {
        $sheet->getStyle('A' . $i . ':I' . $i)->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setRGB('F8F9FA');
    }
}

// Alineación específica para columnas
$sheet->getStyle('C4:C' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Documento
$sheet->getStyle('D4:D' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Teléfono
$sheet->getStyle('E4:E' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Tipo Sangre
$sheet->getStyle('F4:F' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha Inicio
$sheet->getStyle('G4:G' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Fecha Nacimiento
$sheet->getStyle('H4:H' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT); // Sueldo
$sheet->getStyle('I4:I' . $lastRow)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER); // Estado Pago

// Resumen
$summaryRow = $lastRow + 3;
$sheet->setCellValue('A' . $summaryRow, 'RESUMEN:');
$sheet->getStyle('A' . $summaryRow)->getFont()->setBold(true);

$summaryRow++;
$sheet->setCellValue('A' . $summaryRow, 'Total de empleados: ' . count($empleados));

$summaryRow++;
$totalSueldos = array_sum(array_column($empleados, 'sueldo'));
$sheet->setCellValue('A' . $summaryRow, 'Total en sueldos: $' . number_format($totalSueldos, 2));

$summaryRow++;
$empleadosPagados = array_filter($empleados, function($emp) { return isset($emp['pago_quincena']) && $emp['pago_quincena'] == 1; });
$empleadosPendientes = count($empleados) - count($empleadosPagados);
$sheet->setCellValue('A' . $summaryRow, 'Empleados con pago realizado: ' . count($empleadosPagados));

$summaryRow++;
$sheet->setCellValue('A' . $summaryRow, 'Empleados con pago pendiente: ' . $empleadosPendientes);

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(15); // Nombre
$sheet->getColumnDimension('B')->setWidth(15); // Apellido
$sheet->getColumnDimension('C')->setWidth(15); // Documento
$sheet->getColumnDimension('D')->setWidth(12); // Teléfono
$sheet->getColumnDimension('E')->setWidth(12); // Tipo Sangre
$sheet->getColumnDimension('F')->setWidth(15); // Fecha Inicio
$sheet->getColumnDimension('G')->setWidth(18); // Fecha Nacimiento
$sheet->getColumnDimension('H')->setWidth(12); // Sueldo
$sheet->getColumnDimension('I')->setWidth(15); // Estado Pago

// Configurar encabezados para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="reporte_empleados_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Generar archivo Excel
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;
?>