<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$config = include 'config.php';

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
        WHERE tipo_gasto IN ('combustible', 'grasa', 'repuesto') 
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

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
    <h2>Gastos de Máquinas</h2>

    <?php if (!empty($mensajeError)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($mensajeError); ?></div>
    <?php endif; ?>

    <table class="table table-bordered">
        <thead class="thead-dark">
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
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="registrar_gasto_maquina.php?id=<?php echo $maquina['id']; ?>" class="btn btn-warning">Registrar Gasto</a>
                            <a href="historial_gastos.php?id=<?php echo $maquina['id']; ?>" class="btn btn-info">Ver Historial</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h3>Totales de Gastos por Tipo</h3>
    <table class="table table-bordered">
        <thead class="thead-dark">
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

<?php include 'templates/footer.php'; ?>
