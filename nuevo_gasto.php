<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

// Cargar la configuración
$config = include 'config.php';

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Consulta de gastos generales
    $stmt = $conexion->query("SELECT * FROM gastos ORDER BY fecha DESC");
    $gastos = $stmt->fetchAll();

} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
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

<div class="container mt-5">
    <h2 class="text-center">Historial de Gastos</h2>
    <table class="table table-bordered">
        <thead class="thead-dark">
            <tr>
                <th>Fecha</th>
                <th>Monto</th>
                <th>Descripción</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gastos as $gasto): ?>
                <tr>
                    <td><?php echo date("d-m-Y", strtotime($gasto['fecha'])); ?></td>
                    <td>$<?php echo number_format($gasto['monto'], 2); ?></td>
                    <td><?php echo !empty($gasto['descripcion']) ? $gasto['descripcion'] : 'Sin descripción'; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include 'templates/footer.php'; ?>
