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

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Border;

$config = includeConfig('config.php');

// Obtener parámetros de filtro
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : 'todos';
$fecha_especifica = isset($_GET['fecha_especifica']) ? $_GET['fecha_especifica'] : '';
$mes_especifico = isset($_GET['mes_especifico']) ? $_GET['mes_especifico'] : '';
$ano_especifico = isset($_GET['ano_especifico']) ? $_GET['ano_especifico'] : '';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Construir consulta según el filtro
    $sql = "SELECT * FROM gastos";
    $params = [];
    
    switch ($filtro_tipo) {
        case 'dia':
            if (!empty($fecha_especifica)) {
                $sql .= " WHERE DATE(fecha) = ?";
                $params[] = $fecha_especifica;
            }
            break;
        case 'mes':
            if (!empty($mes_especifico)) {
                $sql .= " WHERE YEAR(fecha) = ? AND MONTH(fecha) = ?";
                $ano_actual = !empty($ano_especifico) ? $ano_especifico : date('Y');
                $params[] = $ano_actual;
                $params[] = $mes_especifico;
            }
            break;
        case 'ano':
            if (!empty($ano_especifico)) {
                $sql .= " WHERE YEAR(fecha) = ?";
                $params[] = $ano_especifico;
            }
            break;
        default:
            // 'todos' - no agregar WHERE
            break;
    }
    
    $sql .= " ORDER BY fecha DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $gastos = $stmt->fetchAll();

} catch (PDOException $error) {
    die("Error: " . $error->getMessage());
}

// Crear nuevo spreadsheet
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Configurar título del reporte
$titulo = 'REPORTE DE GASTOS';
switch ($filtro_tipo) {
    case 'dia':
        if (!empty($fecha_especifica)) {
            $titulo .= ' - ' . formatearFechaEspanol($fecha_especifica);
        }
        break;
    case 'mes':
        if (!empty($mes_especifico)) {
            $meses_espanol = [
                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
            ];
            $ano_mostrar = !empty($ano_especifico) ? $ano_especifico : date('Y');
            $titulo .= ' - ' . $meses_espanol[$mes_especifico] . ' ' . $ano_mostrar;
        }
        break;
    case 'ano':
        if (!empty($ano_especifico)) {
            $titulo .= ' - Año ' . $ano_especifico;
        }
        break;
    default:
        $titulo .= ' - TODOS LOS GASTOS';
        break;
}

// Título principal
$sheet->setCellValue('A1', $titulo);
$sheet->mergeCells('A1:C1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Fecha de generación
$sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:C2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Encabezados de tabla
$sheet->setCellValue('A4', 'Fecha');
$sheet->setCellValue('B4', 'Monto');
$sheet->setCellValue('C4', 'Descripción');

// Estilo para encabezados
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '343A40']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A4:C4')->applyFromArray($headerStyle);

// Datos de gastos
$fila = 5;
$total = 0;

foreach ($gastos as $gasto) {
    $sheet->setCellValue('A' . $fila, formatearFechaEspanol($gasto['fecha']));
    $sheet->setCellValue('B' . $fila, '$' . number_format($gasto['monto'], 2));
    $descripcion = !empty($gasto['descripcion']) ? $gasto['descripcion'] : 'Sin descripción';
    $sheet->setCellValue('C' . $fila, $descripcion);
    
    // Estilo para datos
    $sheet->getStyle('A' . $fila . ':C' . $fila)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    $sheet->getStyle('A' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('C' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
    
    $total += $gasto['monto'];
    $fila++;
}

// Fila de total
$sheet->setCellValue('A' . $fila, 'TOTAL');
$sheet->setCellValue('B' . $fila, '$' . number_format($total, 2));
$sheet->setCellValue('C' . $fila, '');

// Estilo para total
$totalStyle = [
    'font' => ['bold' => true],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => 'DDDDDD']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A' . $fila . ':C' . $fila)->applyFromArray($totalStyle);
$sheet->getStyle('B' . $fila)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

// Resumen
$fila += 2;
$sheet->setCellValue('A' . $fila, 'RESUMEN:');
$sheet->getStyle('A' . $fila)->getFont()->setBold(true);
$fila++;

$sheet->setCellValue('A' . $fila, 'Total de gastos: ' . count($gastos));
$fila++;
$sheet->setCellValue('A' . $fila, 'Monto total: $' . number_format($total, 2));
$fila++;

if (count($gastos) > 0) {
    $promedio = $total / count($gastos);
    $sheet->setCellValue('A' . $fila, 'Promedio por gasto: $' . number_format($promedio, 2));
}

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setAutoSize(true);
$sheet->getColumnDimension('B')->setAutoSize(true);
$sheet->getColumnDimension('C')->setAutoSize(true);

// Configurar propiedades del documento
$spreadsheet->getProperties()
    ->setCreator('Sistema de Gestión')
    ->setTitle('Reporte de Gastos')
    ->setSubject('Reporte de Gastos Filtrados')
    ->setDescription('Reporte generado automáticamente');

// Generar nombre del archivo
$nombre_archivo = 'reporte_gastos_' . date('Y-m-d') . '.xlsx';

// Configurar headers para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $nombre_archivo . '"');
header('Cache-Control: max-age=0');

// Crear writer y generar archivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>