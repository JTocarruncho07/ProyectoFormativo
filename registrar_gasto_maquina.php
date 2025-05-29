<?php
session_start();

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

$config = include 'config.php';


if (!isset($_GET['id'])) {
    header("Location: gastos_maquinas.php");
    exit();
}

$maquina_id = $_GET['id'];

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener el nombre de la máquina
    $stmt = $conexion->prepare("SELECT nombre FROM maquinas WHERE id = ?");
    $stmt->execute([$maquina_id]);
    $maquina = $stmt->fetch();

    if (!$maquina) {
        header("Location: gastos_maquinas.php");
        exit();
    }

} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
    <h2>Registrar Gasto - <?php echo htmlspecialchars($maquina['nombre']); ?></h2>
    <form action="procesar_gasto_maquina.php" method="POST">
        <input type="hidden" name="maquina_id" value="<?php echo $maquina_id; ?>">

        <div class="form-group">
            <label for="tipo_gasto">Tipo de Gasto:</label>
            <select name="tipo_gasto" class="form-control" required>
                <option value="repuesto">Repuesto</option>
                <option value="combustible">Combustible</option>
                <option value="grasa">Grasa</option>
            </select>
        </div>

        <div class="form-group">
            <label for="monto">Monto:</label>
            <input type="number" step="0.01" name="monto" class="form-control" required>
        </div>

        <div class="form-group">
            <label for="descripcion">Descripción (opcional):</label>
            <textarea name="descripcion" class="form-control" rows="3"></textarea>
        </div>

        <button type="submit" class="btn btn-primary">Registrar Gasto</button>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
