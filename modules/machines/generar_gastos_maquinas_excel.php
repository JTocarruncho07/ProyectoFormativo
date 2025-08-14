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
$id_maquina = isset($_GET['id_maquina']) ? $_GET['id_maquina'] : '';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Construir consulta según el filtro
    $sql = "SELECT gm.*, m.nombre as nombre_maquina FROM gasto_maquina gm 
            LEFT JOIN maquinas m ON gm.id_maquina = m.id";
    $params = [];
    $whereConditions = [];
    
    // Filtro por máquina específica
    if (!empty($id_maquina)) {
        $whereConditions[] = "gm.id_maquina = ?";
        $params[] = $id_maquina;
    }
    
    // Filtro por máquina específica si se proporciona ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $sql .= " AND gm.id_maquina = ?";
        $params[] = $_GET['id'];
    }

    if ($filtro_tipo === 'dia' && !empty($fecha_especifica)) {
        $sql .= " AND DATE(gm.fecha) = ?";
        $params[] = $fecha_especifica;
    } elseif ($filtro_tipo === 'mes' && !empty($mes_especifico) && !empty($ano_especifico)) {
        $sql .= " AND MONTH(gm.fecha) = ? AND YEAR(gm.fecha) = ?";
        $params[] = $mes_especifico;
        $params[] = $ano_especifico;
    } elseif ($filtro_tipo === 'ano' && !empty($ano_especifico)) {
        $sql .= " AND YEAR(gm.fecha) = ?";
        $params[] = $ano_especifico;
    }

    $sql .= " ORDER BY gm.fecha DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $gastos = $stmt->fetchAll();

    // Obtener estadísticas generales
    $sql_estadisticas = "SELECT 
        COUNT(DISTINCT m.id) as total_maquinas,
        COALESCE(SUM(gm.monto), 0) as total_gastos,
        COUNT(gm.id) as total_registros_gastos
        FROM maquinas m 
        LEFT JOIN gasto_maquina gm ON m.id = gm.id_maquina";
    
    $params_estadisticas = [];
    
    // Aplicar los mismos filtros a las estadísticas
    if (!empty($id_maquina)) {
        $sql_estadisticas .= " WHERE m.id = ?";
        $params_estadisticas[] = $id_maquina;
    }
    
    if ($filtro_tipo === 'dia' && !empty($fecha_especifica)) {
        $where_clause = !empty($id_maquina) ? " AND" : " WHERE";
        $sql_estadisticas .= $where_clause . " DATE(gm.fecha) = ?";
        $params_estadisticas[] = $fecha_especifica;
    } elseif ($filtro_tipo === 'mes' && !empty($mes_especifico)) {
        $ano_actual = !empty($ano_especifico) ? $ano_especifico : date('Y');
        $where_clause = !empty($id_maquina) ? " AND" : " WHERE";
        $sql_estadisticas .= $where_clause . " YEAR(gm.fecha) = ? AND MONTH(gm.fecha) = ?";
        $params_estadisticas[] = $ano_actual;
        $params_estadisticas[] = $mes_especifico;
    } elseif ($filtro_tipo === 'ano' && !empty($ano_especifico)) {
        $where_clause = !empty($id_maquina) ? " AND" : " WHERE";
        $sql_estadisticas .= $where_clause . " YEAR(gm.fecha) = ?";
        $params_estadisticas[] = $ano_especifico;
    }
    
    $stmt_estadisticas = $conexion->prepare($sql_estadisticas);
    $stmt_estadisticas->execute($params_estadisticas);
    $estadisticas = $stmt_estadisticas->fetch();

    // Obtener gastos por tipo
    $sql_tipos = "SELECT tipo_gasto, COUNT(*) as cantidad, SUM(monto) as total 
                  FROM gasto_maquina gm 
                  INNER JOIN maquinas m ON gm.id_maquina = m.id 
                  WHERE 1=1";
    
    $params_tipos = [];
    
    if (!empty($id_maquina)) {
        $sql_tipos .= " AND gm.id_maquina = ?";
        $params_tipos[] = $id_maquina;
    }
    
    if ($filtro_tipo === 'dia' && !empty($fecha_especifica)) {
        $sql_tipos .= " AND DATE(gm.fecha) = ?";
        $params_tipos[] = $fecha_especifica;
    } elseif ($filtro_tipo === 'mes' && !empty($mes_especifico)) {
        $ano_actual = !empty($ano_especifico) ? $ano_especifico : date('Y');
        $sql_tipos .= " AND YEAR(gm.fecha) = ? AND MONTH(gm.fecha) = ?";
        $params_tipos[] = $ano_actual;
        $params_tipos[] = $mes_especifico;
    } elseif ($filtro_tipo === 'ano' && !empty($ano_especifico)) {
        $sql_tipos .= " AND YEAR(gm.fecha) = ?";
        $params_tipos[] = $ano_especifico;
    }
    
    $sql_tipos .= " GROUP BY tipo_gasto ORDER BY total DESC";
    
    $stmt_tipos = $conexion->prepare($sql_tipos);
    $stmt_tipos->execute($params_tipos);
    $gastos_por_tipo = $stmt_tipos->fetchAll();

} catch (PDOException $error) {
    die("Error: " . $error->getMessage());
}

// Crear nuevo documento Excel
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();
$sheet->setTitle('Detalle de Gastos');

// Crear hoja de estadísticas
$statsSheet = $spreadsheet->createSheet();
$statsSheet->setTitle('Estadísticas');

// Crear hoja de gastos por tipo
$typesSheet = $spreadsheet->createSheet();
$typesSheet->setTitle('Gastos por Tipo');

// Configurar hoja de estadísticas
$statsSheet->setCellValue('A1', 'ESTADÍSTICAS GENERALES');
$statsSheet->mergeCells('A1:B1');
$statsSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
$statsSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$statsSheet->setCellValue('A3', 'Total de Máquinas:');
$statsSheet->setCellValue('B3', $estadisticas['total_maquinas']);
$statsSheet->setCellValue('A4', 'Total de Gastos:');
$statsSheet->setCellValue('B4', $estadisticas['total_gastos']);
$statsSheet->setCellValue('A5', 'Total de Registros:');
$statsSheet->setCellValue('B5', $estadisticas['total_registros_gastos']);

// Formato de moneda para total de gastos
$statsSheet->getStyle('B4')->getNumberFormat()->setFormatCode('"$"#,##0.00');

// Estilo para estadísticas
$statsSheet->getStyle('A3:A5')->getFont()->setBold(true);
$statsSheet->getColumnDimension('A')->setWidth(20);
$statsSheet->getColumnDimension('B')->setWidth(15);

// Configurar hoja de gastos por tipo
if (count($gastos_por_tipo) > 0) {
    $typesSheet->setCellValue('A1', 'GASTOS POR TIPO');
    $typesSheet->mergeCells('A1:C1');
    $typesSheet->getStyle('A1')->getFont()->setBold(true)->setSize(14);
    $typesSheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    
    // Encabezados
    $typesSheet->setCellValue('A3', 'Tipo de Gasto');
    $typesSheet->setCellValue('B3', 'Cantidad');
    $typesSheet->setCellValue('C3', 'Total');
    
    // Estilo de encabezados
    $typesSheet->getStyle('A3:C3')->getFont()->setBold(true);
    $typesSheet->getStyle('A3:C3')->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('343A40');
    $typesSheet->getStyle('A3:C3')->getFont()->getColor()->setRGB('FFFFFF');
    $typesSheet->getStyle('A3:C3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $typesSheet->getStyle('A3:C3')->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Datos
    $row = 4;
    foreach ($gastos_por_tipo as $tipo) {
        $typesSheet->setCellValue('A' . $row, ucfirst($tipo['tipo_gasto']));
        $typesSheet->setCellValue('B' . $row, $tipo['cantidad']);
        $typesSheet->setCellValue('C' . $row, $tipo['total']);
        
        // Formato de moneda
        $typesSheet->getStyle('C' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Bordes
        $typesSheet->getStyle('A' . $row . ':C' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $row++;
    }
    
    // Ajustar ancho de columnas
    $typesSheet->getColumnDimension('A')->setWidth(20);
    $typesSheet->getColumnDimension('B')->setWidth(15);
    $typesSheet->getColumnDimension('C')->setWidth(15);
}

// Volver a la hoja principal
$spreadsheet->setActiveSheetIndex(0);

// Título del reporte
$titulo = 'REPORTE DE GASTOS DE MÁQUINAS';
switch ($filtro_tipo) {
    case 'dia':
        if (!empty($fecha_especifica)) {
            $titulo .= ' - ' . formatearFechaEspanol($fecha_especifica);
        }
        break;
    case 'mes':
        if (!empty($mes_especifico)) {
            $ano_actual = !empty($ano_especifico) ? $ano_especifico : date('Y');
            $titulo .= ' - ' . formatearMesAñoEspanol($ano_actual . '-' . str_pad($mes_especifico, 2, '0', STR_PAD_LEFT) . '-01');
        }
        break;
    case 'ano':
        if (!empty($ano_especifico)) {
            $titulo .= ' - Año ' . $ano_especifico;
        }
        break;
    default:
        $titulo .= ' - Todos los registros';
        break;
}

// Configurar título
$sheet->setCellValue('A1', $titulo);
$sheet->mergeCells('A1:E1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Fecha de generación
$sheet->setCellValue('A2', 'Fecha de generación: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A2:E2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

if (count($gastos) > 0) {
    // Encabezados
    $row = 4;
    $headers = ['Fecha', 'Máquina', 'Tipo Gasto', 'Descripción', 'Monto'];
    $columns = ['A', 'B', 'C', 'D', 'E'];
    
    foreach ($headers as $index => $header) {
        $sheet->setCellValue($columns[$index] . $row, $header);
    }
    
    // Estilo de encabezados
    $headerRange = 'A' . $row . ':E' . $row;
    $sheet->getStyle($headerRange)->getFont()->setBold(true);
    $sheet->getStyle($headerRange)->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB('343A40');
    $sheet->getStyle($headerRange)->getFont()->getColor()->setRGB('FFFFFF');
    $sheet->getStyle($headerRange)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
    $sheet->getStyle($headerRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
    // Datos
    $row = 5;
    $totalGastos = 0;
    
    foreach ($gastos as $gasto) {
        $sheet->setCellValue('A' . $row, date('d/m/Y', strtotime($gasto['fecha'])));
        $sheet->setCellValue('B' . $row, $gasto['nombre_maquina'] ?? 'N/A');
        $sheet->setCellValue('C' . $row, ucfirst($gasto['tipo_gasto']));
        $sheet->setCellValue('D' . $row, $gasto['descripcion']);
        $sheet->setCellValue('E' . $row, $gasto['monto']);
        
        // Formato de moneda
        $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
        
        // Bordes
        $dataRange = 'A' . $row . ':E' . $row;
        $sheet->getStyle($dataRange)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
        
        $totalGastos += $gasto['monto'];
        $row++;
    }
    
    // Total
    $row++;
    $sheet->setCellValue('D' . $row, 'TOTAL GASTOS:');
    $sheet->setCellValue('E' . $row, $totalGastos);
    
    // Estilo del total
    $sheet->getStyle('D' . $row . ':E' . $row)->getFont()->setBold(true);
    $sheet->getStyle('D' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);
    $sheet->getStyle('E' . $row)->getNumberFormat()->setFormatCode('"$"#,##0.00');
    $sheet->getStyle('D' . $row . ':E' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
    
} else {
    $sheet->setCellValue('A4', 'No se encontraron gastos de máquinas para los filtros seleccionados.');
    $sheet->mergeCells('A4:E4');
    $sheet->getStyle('A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
}

// Ajustar ancho de columnas
$sheet->getColumnDimension('A')->setWidth(15);
$sheet->getColumnDimension('B')->setWidth(25);
$sheet->getColumnDimension('C')->setWidth(20);
$sheet->getColumnDimension('D')->setWidth(40);
$sheet->getColumnDimension('E')->setWidth(15);

// Configurar encabezados para descarga
header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="gastos_maquinas_' . date('Y-m-d') . '.xlsx"');
header('Cache-Control: max-age=0');

// Generar y enviar archivo
$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit();
?>