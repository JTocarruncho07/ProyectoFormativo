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

    // Obtener el historial de gastos de la máquina con el tipo de gasto
    $stmt = $conexion->prepare("
        SELECT id, tipo_gasto, descripcion, monto, fecha 
        FROM gasto_maquina 
        WHERE id_maquina = ? 
        ORDER BY fecha DESC
    ");
    $stmt->execute([$_GET['id']]);
    $gastos = $stmt->fetchAll();

    // Obtener el total de egresos de la máquina
    $stmt = $conexion->prepare("
        SELECT COALESCE(SUM(monto), 0) AS total_egreso 
        FROM gasto_maquina 
        WHERE id_maquina = ?
    ");
    $stmt->execute([$_GET['id']]);
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

<?php includeTemplate('footer.php'); ?>
