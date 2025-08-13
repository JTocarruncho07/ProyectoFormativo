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
$filtro_tipo = isset($_GET['filtro_tipo']) ? $_GET['filtro_tipo'] : 'todas';
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
    $sql = "SELECT * FROM ingresos";
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
            // 'todas' - no agregar WHERE
            break;
    }
    
    $sql .= " ORDER BY fecha DESC";
    
    $stmt = $conexion->prepare($sql);
    $stmt->execute($params);
    $ventas = $stmt->fetchAll();

} catch (PDOException $error) {
    die("Error: " . $error->getMessage());
}

// Crear PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configuración del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Reporte de Ventas');
$pdf->SetSubject('Reporte de Ventas Filtradas');

// Configurar márgenes
$pdf->SetMargins(15, 27, 15);
$pdf->SetHeaderMargin(5);
$pdf->SetFooterMargin(10);

// Configurar salto de página automático
$pdf->SetAutoPageBreak(TRUE, 25);

// Configurar fuente
$pdf->SetFont('helvetica', '', 10);

// Agregar página
$pdf->AddPage();

// Título del reporte
$titulo = 'REPORTE DE VENTAS';
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
        $titulo .= ' - TODAS LAS VENTAS';
        break;
}

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $titulo, 0, 1, 'C');
$pdf->Ln(5);

// Información adicional
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
$pdf->Ln(5);

// Encabezados de tabla con anchos más amplios
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(52, 58, 64);
$pdf->SetTextColor(255, 255, 255);

// Anchos de columna más amplios para ocupar toda la página
$w = array(90, 90); // Fecha, Monto

$pdf->Cell($w[0], 10, 'Fecha', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, 'Monto', 1, 1, 'C', true);

// Datos de ventas
$pdf->SetFont('helvetica', '', 10);
$pdf->SetTextColor(0, 0, 0);
$total = 0;

foreach ($ventas as $venta) {
    $pdf->Cell($w[0], 8, formatearFechaEspanol($venta['fecha']), 1, 0, 'C');
    $pdf->Cell($w[1], 8, '$' . number_format($venta['monto'], 2), 1, 1, 'R');
    $total += $venta['monto'];
}

// Total
$pdf->SetFont('helvetica', 'B', 12);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w[0], 10, 'TOTAL', 1, 0, 'C', true);
$pdf->Cell($w[1], 10, '$' . number_format($total, 2), 1, 1, 'R', true);

// Resumen
$pdf->Ln(10);
$pdf->SetFont('helvetica', 'B', 10);
$pdf->Cell(0, 5, 'RESUMEN:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 5, 'Total de ventas: ' . count($ventas), 0, 1, 'L');
$pdf->Cell(0, 5, 'Monto total: $' . number_format($total, 2), 0, 1, 'L');

if (count($ventas) > 0) {
    $promedio = $total / count($ventas);
    $pdf->Cell(0, 5, 'Promedio por venta: $' . number_format($promedio, 2), 0, 1, 'L');
}

// Generar nombre del archivo
$nombre_archivo = 'reporte_ventas_' . date('Y-m-d') . '.pdf';

// Salida del PDF
$pdf->Output($nombre_archivo, 'I');
?>