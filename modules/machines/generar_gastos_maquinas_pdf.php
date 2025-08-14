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

    // Construir consulta SQL con filtros
    $sql = "SELECT gm.*, m.nombre as nombre_maquina FROM gasto_maquina gm INNER JOIN maquinas m ON gm.id_maquina = m.id WHERE 1=1";
    $params = [];

    // Filtro por máquina específica si se proporciona ID
    if (isset($_GET['id']) && !empty($_GET['id'])) {
        $sql .= " AND gm.id_maquina = ?";
        $params[] = $_GET['id'];
    }
    
    // Filtro por máquina específica
    if (!empty($id_maquina)) {
        $sql .= " AND gm.id_maquina = ?";
        $params[] = $id_maquina;
    }
    
    // Filtros de fecha
    if ($filtro_tipo === 'dia' && !empty($fecha_especifica)) {
        $sql .= " AND DATE(gm.fecha) = ?";
        $params[] = $fecha_especifica;
    } elseif ($filtro_tipo === 'mes' && !empty($mes_especifico)) {
        $ano_actual = !empty($ano_especifico) ? $ano_especifico : date('Y');
        $sql .= " AND YEAR(gm.fecha) = ? AND MONTH(gm.fecha) = ?";
        $params[] = $ano_actual;
        $params[] = $mes_especifico;
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

// Crear nuevo documento PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Reporte de Gastos de Máquinas');
$pdf->SetSubject('Reporte de Gastos de Máquinas Filtrados');

// Configurar márgenes
$pdf->SetMargins(15, 15, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, 25);

// Configurar fuente
$pdf->SetFont('helvetica', '', 10);

// Agregar página
$pdf->AddPage();

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

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $titulo, 0, 1, 'C');
$pdf->Ln(5);

// Información de generación
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
$pdf->Ln(5);

// Estadísticas Generales
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'ESTADÍSTICAS GENERALES', 0, 1, 'L');
$pdf->Ln(2);

$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(60, 6, 'Total de Máquinas:', 0, 0, 'L');
$pdf->Cell(30, 6, $estadisticas['total_maquinas'], 0, 1, 'L');
$pdf->Cell(60, 6, 'Total de Gastos:', 0, 0, 'L');
$pdf->Cell(30, 6, '$' . number_format($estadisticas['total_gastos'], 2), 0, 1, 'L');
$pdf->Cell(60, 6, 'Total de Registros:', 0, 0, 'L');
$pdf->Cell(30, 6, $estadisticas['total_registros_gastos'], 0, 1, 'L');
$pdf->Ln(5);

// Gastos por Tipo
if (count($gastos_por_tipo) > 0) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, 'GASTOS POR TIPO', 0, 1, 'L');
    $pdf->Ln(2);
    
    // Encabezados de tabla gastos por tipo
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 58, 64);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(60, 8, 'Tipo de Gasto', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Cantidad', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Total', 1, 1, 'C', true);
    
    // Datos de gastos por tipo
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    
    foreach ($gastos_por_tipo as $tipo) {
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
        
        $pdf->Cell(60, 6, ucfirst($tipo['tipo_gasto']), 1, 0, 'L', $fill);
        $pdf->Cell(30, 6, $tipo['cantidad'], 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, '$' . number_format($tipo['total'], 2), 1, 1, 'R', $fill);
        
        $fill = !$fill;
    }
    $pdf->Ln(5);
}

// Detalle de Gastos
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'DETALLE DE GASTOS', 0, 1, 'L');
$pdf->Ln(2);

if (count($gastos) > 0) {
    // Encabezados de tabla
    $pdf->SetFont('helvetica', 'B', 9);
    $pdf->SetFillColor(52, 58, 64);
    $pdf->SetTextColor(255, 255, 255);
    
    $pdf->Cell(25, 8, 'Fecha', 1, 0, 'C', true);
    $pdf->Cell(40, 8, 'Máquina', 1, 0, 'C', true);
    $pdf->Cell(30, 8, 'Tipo Gasto', 1, 0, 'C', true);
    $pdf->Cell(60, 8, 'Descripción', 1, 0, 'C', true);
    $pdf->Cell(25, 8, 'Monto', 1, 1, 'C', true);
    
    // Datos de gastos
    $pdf->SetFont('helvetica', '', 8);
    $pdf->SetTextColor(0, 0, 0);
    $fill = false;
    $totalGastos = 0;
    
    foreach ($gastos as $gasto) {
        $pdf->SetFillColor($fill ? 245 : 255, $fill ? 245 : 255, $fill ? 245 : 255);
        
        $pdf->Cell(25, 6, date('d/m/Y', strtotime($gasto['fecha'])), 1, 0, 'C', $fill);
        $pdf->Cell(40, 6, $gasto['nombre_maquina'] ?? 'N/A', 1, 0, 'L', $fill);
        $pdf->Cell(30, 6, ucfirst($gasto['tipo_gasto']), 1, 0, 'C', $fill);
        $pdf->Cell(60, 6, substr($gasto['descripcion'], 0, 40), 1, 0, 'L', $fill);
        $pdf->Cell(25, 6, '$' . number_format($gasto['monto'], 2), 1, 1, 'R', $fill);
        
        $totalGastos += $gasto['monto'];
        $fill = !$fill;
    }
    
    // Total
    $pdf->Ln(3);
    $pdf->SetFont('helvetica', 'B', 10);
    $pdf->Cell(155, 8, 'TOTAL GASTOS:', 1, 0, 'R', true);
    $pdf->Cell(25, 8, '$' . number_format($totalGastos, 2), 1, 1, 'R', true);
    
} else {
    $pdf->SetFont('helvetica', '', 12);
    $pdf->Cell(0, 10, 'No se encontraron gastos de máquinas para los filtros seleccionados.', 0, 1, 'C');
}

// Generar PDF
$pdf->Output('gastos_maquinas_' . date('Y-m-d') . '.pdf', 'I');
?>