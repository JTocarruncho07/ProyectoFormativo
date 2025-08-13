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
    echo "Error: " . $error->getMessage();
}
?>

<?php includeTemplate('header.php'); ?>

<div class="container-fluid px-3 py-4">
    <h2 class="text-center">Nuevo Gasto</h2>
    <form action="procesar_gasto.php" method="POST">
        <div class="form-group">
            <label for="monto">Monto del Gasto:</label>
            <input type="number" step="0.01" name="monto" class="form-control" placeholder="Ejemplo: 50000" required>
        </div>
        <div class="form-group">
            <label for="descripcion">Descripción (Opcional):</label>
            <textarea name="descripcion" class="form-control" placeholder="Ejemplo: Compra de herramientas"></textarea>
        </div>
        <button type="submit" class="btn btn-danger btn-block">Registrar Gasto</button>
    </form>
</div>

<div class="px-3">
    <h2 class="text-center mt-5">Historial de Gastos</h2>
    
    <!-- Formulario de filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtrar Gastos</h5>
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
                        <a href="nuevo_gasto.php" class="btn btn-secondary">Limpiar filtros</a>
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
                <th>Fecha</th>
                <th>Monto</th>
                <th>Descripción</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gastos as $gasto): ?>
                <tr>
                    <td><?php echo formatearFechaEspanol($gasto['fecha']); ?></td>
                    <td>$<?php echo number_format($gasto['monto'], 2); ?></td>
                    <td><?php echo !empty($gasto['descripcion']) ? $gasto['descripcion'] : 'Sin descripción'; ?></td>
                    <td>
                        <a href="editar_gasto.php?id=<?php echo $gasto['id']; ?>" class="btn btn-primary btn-sm">Editar</a>
                        <form action="eliminar_gasto.php" method="POST" style="display: inline;">
                            <input type="hidden" name="id" value="<?php echo $gasto['id']; ?>">
                            <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">
                                Eliminar
                            </button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
        </table>
    </div>
</div>

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
    const url = 'generar_gastos_pdf.php?' + params.toString();
    window.open(url, '_blank');
}

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    const url = 'generar_gastos_excel.php?' + params.toString();
    window.open(url, '_blank');
}
</script>

<?php includeTemplate('footer.php'); ?>
