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

    // Verificar si se recibe un ID válido
    if ($_SERVER["REQUEST_METHOD"] === "GET" && isset($_GET['id'])) {
        $id = $_GET['id'];

        // Obtener datos de la máquina
        $stmt = $conexion->prepare("SELECT * FROM maquinas WHERE id = ?");
        $stmt->execute([$id]);
        $maquina = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$maquina) {
            die("Máquina no encontrada.");
        }
    }

    // Procesar el formulario cuando se envía
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['id'])) {
        $id = $_POST['id'];
        $nombre = trim($_POST['nombre']);
        $descripcion = trim($_POST['descripcion']);

        if (empty($nombre) || empty($descripcion)) {
            die("Todos los campos son obligatorios.");
        }

        // Actualizar la información de la máquina
        $stmt = $conexion->prepare("UPDATE maquinas SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $id]);

        $_SESSION['mensaje'] = "Máquina actualizada con éxito.";
        header("Location: index.php");
        exit();
    }
} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}
?>

<?php include 'templates/header.php'; ?>

<div class="container mt-5">
    <h2>Editar Máquina</h2>
    
    <form method="POST" action="editar_maquina.php">
        <input type="hidden" name="id" value="<?php echo htmlspecialchars($maquina['id']); ?>">
        
        <div class="form-group">
            <label>Nombre</label>
            <input type="text" name="nombre" class="form-control" value="<?php echo htmlspecialchars($maquina['nombre']); ?>" required>
        </div>

        <div class="form-group">
            <label>Descripción</label>
            <textarea name="descripcion" class="form-control" required><?php echo htmlspecialchars($maquina['descripcion']); ?></textarea>
        </div>

        <button type="submit" class="btn btn-success">Guardar Cambios</button>
        <a href="index.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php include 'templates/footer.php'; ?>
