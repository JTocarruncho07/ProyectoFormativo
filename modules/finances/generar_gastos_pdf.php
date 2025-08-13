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

// Crear nuevo documento PDF
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Configurar información del documento
$pdf->SetCreator(PDF_CREATOR);
$pdf->SetAuthor('Sistema de Gestión');
$pdf->SetTitle('Reporte de Gastos');
$pdf->SetSubject('Reporte de Gastos Filtrados');

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

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, $titulo, 0, 1, 'C');
$pdf->Ln(5);

// Fecha de generación
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 8, 'Fecha de generación: ' . date('d/m/Y H:i:s'), 0, 1, 'R');
$pdf->Ln(5);

// Encabezados de tabla con anchos más amplios
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(52, 58, 64); // Color de fondo gris oscuro
$pdf->SetTextColor(255, 255, 255); // Texto blanco

// Anchos de columna más amplios para ocupar toda la página
$w = array(60, 50, 70); // Fecha, Monto, Descripción

$pdf->Cell($w[0], 8, 'Fecha', 1, 0, 'C', 1);
$pdf->Cell($w[1], 8, 'Monto', 1, 0, 'C', 1);
$pdf->Cell($w[2], 8, 'Descripción', 1, 1, 'C', 1);

// Datos de gastos
$pdf->SetFont('helvetica', '', 9);
$pdf->SetTextColor(0, 0, 0); // Texto negro
$pdf->SetFillColor(240, 240, 240); // Color de fondo alternado

$total = 0;
$fill = false;

foreach ($gastos as $gasto) {
    $pdf->Cell($w[0], 6, formatearFechaEspanol($gasto['fecha']), 1, 0, 'C', $fill);
    $pdf->Cell($w[1], 6, '$' . number_format($gasto['monto'], 2), 1, 0, 'R', $fill);
    $descripcion = !empty($gasto['descripcion']) ? $gasto['descripcion'] : 'Sin descripción';
    $pdf->Cell($w[2], 6, $descripcion, 1, 1, 'L', $fill);
    
    $total += $gasto['monto'];
    $fill = !$fill;
}

// Fila de total
$pdf->SetFont('helvetica', 'B', 10);
$pdf->SetFillColor(220, 220, 220);
$pdf->Cell($w[0], 8, 'TOTAL', 1, 0, 'C', 1);
$pdf->Cell($w[1], 8, '$' . number_format($total, 2), 1, 0, 'R', 1);
$pdf->Cell($w[2], 8, '', 1, 1, 'C', 1);

$pdf->Ln(10);

// Resumen
$pdf->SetFont('helvetica', 'B', 12);
$pdf->Cell(0, 8, 'RESUMEN:', 0, 1, 'L');
$pdf->SetFont('helvetica', '', 10);
$pdf->Cell(0, 6, 'Total de gastos: ' . count($gastos), 0, 1, 'L');
$pdf->Cell(0, 6, 'Monto total: $' . number_format($total, 2), 0, 1, 'L');

if (count($gastos) > 0) {
    $promedio = $total / count($gastos);
    $pdf->Cell(0, 6, 'Promedio por gasto: $' . number_format($promedio, 2), 0, 1, 'L');
}

// Generar nombre del archivo
$nombre_archivo = 'reporte_gastos_' . date('Y-m-d') . '.pdf';

// Salida del PDF
$pdf->Output($nombre_archivo, 'D');
?>