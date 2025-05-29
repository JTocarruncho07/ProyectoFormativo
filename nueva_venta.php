<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Incluir y obtener configuración
$config = include 'config.php';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Consulta de ingresos diarios
    $stmt = $conexion->query("SELECT * FROM ingresos ORDER BY fecha DESC");
    $ingresos = $stmt->fetchAll();

} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
    <h2 class="text-center">Nueva Venta</h2>
    <form action="procesar_venta.php" method="POST">
        <div class="form-group">
            <label for="monto">Precio de la Venta:</label>
            <input type="number" step="0.01" name="monto" class="form-control" placeholder="Ejemplo: 150000" required>
        </div>
        <button type="submit" class="btn btn-success btn-block">Registrar Venta</button>
    </form>
</div>

<div class="container mt-5">
    <h2 class="text-center">Historial de Ventas</h2>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Monto</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ingresos as $ingreso): ?>
                <tr>
                    <td><?php echo date("d-m-Y", strtotime($ingreso['fecha'])); ?></td>
                    <td>$<?php echo number_format($ingreso['monto'], 2); ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>

