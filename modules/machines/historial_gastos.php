<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ID de máquina no proporcionado.");
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

    // Obtener información de la máquina
    $stmt = $conexion->prepare("SELECT nombre FROM maquinas WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $maquina = $stmt->fetch();

    if (!$maquina) {
        die("Máquina no encontrada.");
    }

    // Construir consulta SQL con filtros
    $sql_gastos = "SELECT id, tipo_gasto, descripcion, monto, fecha FROM gasto_maquina WHERE id_maquina = ?";
    $sql_total = "SELECT COALESCE(SUM(monto), 0) AS total_egreso FROM gasto_maquina WHERE id_maquina = ?";
    $params = [$_GET['id']];
    
    if ($filtro_tipo === 'dia' && !empty($fecha_especifica)) {
        $sql_gastos .= " AND DATE(fecha) = ?";
        $sql_total .= " AND DATE(fecha) = ?";
        $params[] = $fecha_especifica;
    } elseif ($filtro_tipo === 'mes' && !empty($mes_especifico) && !empty($ano_especifico)) {
        $sql_gastos .= " AND MONTH(fecha) = ? AND YEAR(fecha) = ?";
        $sql_total .= " AND MONTH(fecha) = ? AND YEAR(fecha) = ?";
        $params[] = $mes_especifico;
        $params[] = $ano_especifico;
    } elseif ($filtro_tipo === 'ano' && !empty($ano_especifico)) {
        $sql_gastos .= " AND YEAR(fecha) = ?";
        $sql_total .= " AND YEAR(fecha) = ?";
        $params[] = $ano_especifico;
    }
    
    $sql_gastos .= " ORDER BY fecha DESC";
    
    // Obtener el historial de gastos de la máquina con filtros
    $stmt = $conexion->prepare($sql_gastos);
    $stmt->execute($params);
    $gastos = $stmt->fetchAll();

    // Obtener el total de egresos de la máquina con filtros
    $stmt = $conexion->prepare($sql_total);
    $stmt->execute($params);
    $totalEgreso = $stmt->fetchColumn();

} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php includeTemplate('header.php'); ?>

<div class="container-fluid px-3 py-4">
    <!-- Botón de Navegación -->
         <div class="d-flex justify-content-center mb-4">
             <a href="gastos_maquinas.php" class="btn btn-secondary">Volver a Gastos de Máquinas</a>
         </div>
    
    <h2>Historial de Gastos - <?php echo htmlspecialchars($maquina['nombre']); ?></h2>
    
    <!-- Filtros -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Filtrar Historial de Gastos</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="historial_gastos.php">
                <input type="hidden" name="id" value="<?php echo $_GET['id']; ?>">
                <div class="row">
                    <div class="col-md-3">
                        <label for="filtro_tipo">Filtrar por:</label>
                        <select name="filtro_tipo" id="filtro_tipo" class="form-control" onchange="toggleFiltros()">
                            <option value="todos" <?php echo $filtro_tipo === 'todos' ? 'selected' : ''; ?>>Todos los registros</option>
                            <option value="dia" <?php echo $filtro_tipo === 'dia' ? 'selected' : ''; ?>>Día específico</option>
                            <option value="mes" <?php echo $filtro_tipo === 'mes' ? 'selected' : ''; ?>>Mes específico</option>
                            <option value="ano" <?php echo $filtro_tipo === 'ano' ? 'selected' : ''; ?>>Año específico</option>
                        </select>
                    </div>
                    
                    <div class="col-md-3" id="filtro_dia" style="display: <?php echo $filtro_tipo === 'dia' ? 'block' : 'none'; ?>">
                        <label for="fecha_especifica">Fecha:</label>
                        <input type="date" name="fecha_especifica" class="form-control" value="<?php echo $fecha_especifica; ?>">
                    </div>
                    
                    <div class="col-md-3" id="filtro_mes" style="display: <?php echo $filtro_tipo === 'mes' ? 'block' : 'none'; ?>">
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
                <div class="mt-3">
                    <button type="submit" class="btn btn-primary">Aplicar filtros</button>
                    <a href="historial_gastos.php?id=<?php echo $_GET['id']; ?>" class="btn btn-secondary">Limpiar filtros</a>
                    <div class="float-right">
                        <button type="button" class="btn btn-danger mr-2" onclick="exportarPDF()"><i class="fas fa-file-pdf"></i> Exportar PDF</button>
                        <button type="button" class="btn btn-success" onclick="exportarExcel()"><i class="fas fa-file-excel"></i> Exportar Excel</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-striped table-bordered">
            <thead class="table-dark">
            <tr>
                <th>Tipo de Gasto</th>
                <th>Descripción</th>
                <th>Monto</th>
                <th>Fecha</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($gastos) > 0): ?>
                <?php foreach ($gastos as $gasto): ?>
                    <tr>
                        <td><?php echo ucfirst(htmlspecialchars($gasto['tipo_gasto'])); ?></td>
                        <td><?php echo htmlspecialchars($gasto['descripcion']); ?></td>
                        <td>$<?php echo number_format($gasto['monto'], 2); ?></td>
                        <td><?php echo formatearFechaEspanol($gasto['fecha']); ?></td>
                        <td>
                            <a href="editar_gasto_maquina.php?id=<?php echo $gasto['id']; ?>" class="btn btn-sm btn-warning me-1">Editar</a>
                            <form method="POST" action="eliminar_gasto_maquina.php" style="display: inline;">
                                <input type="hidden" name="id" value="<?php echo $gasto['id']; ?>">
                                <input type="hidden" name="id_maquina" value="<?php echo $_GET['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('¿Estás seguro de que deseas eliminar este gasto?');">Eliminar</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3" class="text-end fw-bold">Total de Egresos:</td>
                    <td colspan="2" class="fw-bold">$<?php echo number_format($totalEgreso, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="5" class="text-center">No hay gastos registrados.</td>
                </tr>
            <?php endif; ?>
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
    const url = 'generar_gastos_maquinas_pdf.php?' + params.toString();
    window.open(url, '_blank');
}

function exportarExcel() {
    const params = new URLSearchParams(window.location.search);
    const url = 'generar_gastos_maquinas_excel.php?' + params.toString();
    window.open(url, '_blank');
}
</script>

<?php includeTemplate('footer.php'); ?>
