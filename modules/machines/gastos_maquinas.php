<?php
session_start();

// Incluir el archivo de rutas
if (!defined('ROOT_PATH')) {
    require_once dirname(dirname(__DIR__)) . '/config/paths.php';
}

if (!isset($_SESSION['usuario'])) {
    header("Location: " . moduleUrl('auth/login.php'));
    exit();
}

$config = includeConfig('config.php');

// Inicializar variables de filtro
$filtro_tipo = $_GET['filtro_tipo'] ?? 'todos';
$fecha_especifica = $_GET['fecha_especifica'] ?? '';
$mes_especifico = $_GET['mes_especifico'] ?? '';
$ano_especifico = $_GET['ano_especifico'] ?? '';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Consulta de las máquinas con sus gastos totales
    $stmt = $conexion->query(" 
        SELECT m.id, m.nombre, m.descripcion, COALESCE(SUM(g.monto), 0) AS total_egreso 
        FROM maquinas m 
        LEFT JOIN gasto_maquina g ON m.id = g.id_maquina 
        GROUP BY m.id, m.nombre, m.descripcion 
    ");
    
    $maquinas = $stmt->fetchAll();

    // Consulta de los gastos por tipo en todas las máquinas
    $stmt = $conexion->query(" 
        SELECT tipo_gasto, COALESCE(SUM(monto), 0) AS total 
        FROM gasto_maquina 
        WHERE tipo_gasto IN ('combustible', 'grasa', 'repuestos') 
        GROUP BY tipo_gasto 
    ");
    
    $gastosPorTipo = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Mensaje de error si existe
    $mensajeError = $_SESSION['error'] ?? '';
    unset($_SESSION['error']);

} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php includeTemplate('header.php'); ?>

<div class="container-fluid px-3 py-4">
    <h2>Gastos de Máquinas</h2>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
    <?php endif; ?>

    <!-- Formulario de filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtrar Gastos de Máquinas</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="">
                <div class="row">
                    <div class="col-md-3">
                        <label for="filtro_tipo">Filtrar por:</label>
                        <select name="filtro_tipo" id="filtro_tipo" class="form-control" onchange="toggleFiltros()">
                            <option value="todos" <?php echo ($filtro_tipo == 'todos') ? 'selected' : ''; ?>>Todos los gastos</option>
                            <option value="dia" <?php echo ($filtro_tipo == 'dia') ? 'selected' : ''; ?>>Por día</option>
                            <option value="mes" <?php echo ($filtro_tipo == 'mes') ? 'selected' : ''; ?>>Por mes</option>
                            <option value="ano" <?php echo ($filtro_tipo == 'ano') ? 'selected' : ''; ?>>Por año</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="filtro_dia" style="display: <?php echo ($filtro_tipo == 'dia') ? 'block' : 'none'; ?>">
                        <label for="fecha_especifica">Fecha:</label>
                        <input type="date" name="fecha_especifica" class="form-control" value="<?php echo $fecha_especifica; ?>">
                    </div>
                    
                    <div class="col-md-3" id="filtro_mes" style="display: <?php echo ($filtro_tipo == 'mes') ? 'block' : 'none'; ?>">
                        <label for="mes_especifico">Mes:</label>
                        <select name="mes_especifico" class="form-control">
                            <option value="">Seleccionar mes</option>
                            <?php
                            $meses_espanol = [
                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
                            ];
                            for ($i = 1; $i <= 12; $i++) {
                                $selected = ($mes_especifico == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>{$meses_espanol[$i]}</option>";
                            }
                            ?>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="filtro_ano" style="display: <?php echo ($filtro_tipo == 'ano' || $filtro_tipo == 'mes') ? 'block' : 'none'; ?>">
                        <label for="ano_especifico">Año:</label>
                        <select name="ano_especifico" class="form-control">
                            <option value="">Año actual</option>
                            <?php
                            $ano_actual = date('Y');
                            for ($i = $ano_actual; $i >= $ano_actual - 5; $i--) {
                                $selected = ($ano_especifico == $i) ? 'selected' : '';
                                echo "<option value='$i' $selected>$i</option>";
                            }
                            ?>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <button type="submit" class="btn btn-primary">Filtrar</button>
                        <a href="gastos_maquinas.php" class="btn btn-secondary">Limpiar filtros</a>
                        <div class="float-right">
                            <button type="button" class="btn btn-danger mr-2" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
                            <button type="button" class="btn btn-success" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Exportar Excel</button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
            <tr>
                <th>Nombre</th>
                <th>Descripción</th>
                <th>Total de Egreso</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($maquinas as $maquina): ?>
                <tr>
                    <td><?php echo htmlspecialchars($maquina['nombre']); ?></td>
                    <td><?php echo htmlspecialchars($maquina['descripcion']); ?></td>
                    <td>$<?php echo number_format($maquina['total_egreso'], 2); ?></td>
                    <td>
                        <div class="d-flex justify-content-center">
                            <button type="button" class="btn btn-warning" style="margin-right: 8px;" onclick="window.location.href='registrar_gasto_maquina.php?id=<?php echo $maquina['id']; ?>'">Registrar Gasto</button>
                            <button type="button" class="btn btn-info" onclick="window.location.href='historial_gastos.php?id=<?php echo $maquina['id']; ?>'">Ver Historial</button>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>

    <h3>Totales de Gastos por Tipo</h3>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
            <tr>
                <th>Tipo de Gasto</th>
                <th>Total</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gastosPorTipo as $gasto): ?>
                <tr>
                    <td><?php echo htmlspecialchars($gasto['tipo_gasto']); ?></td>
                    <td>$<?php echo number_format($gasto['total'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>

<?php includeTemplate('footer.php'); ?>

<script>
function toggleFiltros() {
    const filtroTipo = document.getElementById('filtro_tipo').value;
    const filtroDia = document.getElementById('filtro_dia');
    const filtroMes = document.getElementById('filtro_mes');
    const filtroAno = document.getElementById('filtro_ano');
    
    // Ocultar todos los filtros
    filtroDia.style.display = 'none';
    filtroMes.style.display = 'none';
    filtroAno.style.display = 'none';
    
    // Mostrar el filtro correspondiente
    switch(filtroTipo) {
        case 'dia':
            filtroDia.style.display = 'block';
            break;
        case 'mes':
            filtroMes.style.display = 'block';
            filtroAno.style.display = 'block';
            break;
        case 'ano':
            filtroAno.style.display = 'block';
            break;
    }
}

function exportarPDF() {
    const params = new URLSearchParams(window.location.search);
    const url = 'generar_gastos_maquinas_pdf.php?' + params.toString();
    window.open(url, '_blank');
}

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    const url = 'generar_gastos_maquinas_excel.php?' + params.toString();
    window.open(url, '_blank');
}
</script>
