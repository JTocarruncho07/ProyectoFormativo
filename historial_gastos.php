<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

if (!isset($_GET['id'])) {
    die("ID de máquina no proporcionado.");
}

$config = include 'config.php';

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
        SELECT tipo_gasto, descripcion, monto, fecha 
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

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
    <h2>Historial de Gastos - <?php echo htmlspecialchars($maquina['nombre']); ?></h2>
    <a href="gastos_maquinas.php" class="btn btn-secondary mb-3">Volver</a>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Tipo de Gasto</th>
                <th>Descripción</th>
                <th>Monto</th>
                <th>Fecha</th>
            </tr>
        </thead>
        <tbody>
            <?php if (count($gastos) > 0): ?>
                <?php foreach ($gastos as $gasto): ?>
                    <tr>
                        <td><?php echo ucfirst(htmlspecialchars($gasto['tipo_gasto'])); ?></td>
                        <td><?php echo htmlspecialchars($gasto['descripcion']); ?></td>
                        <td>$<?php echo number_format($gasto['monto'], 2); ?></td>
                        <td><?php echo htmlspecialchars($gasto['fecha']); ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="2" class="text-end fw-bold">Total de Egresos:</td>
                    <td colspan="2" class="fw-bold">$<?php echo number_format($totalEgreso, 2); ?></td>
                </tr>
            <?php else: ?>
                <tr>
                    <td colspan="4" class="text-center">No hay gastos registrados.</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>
