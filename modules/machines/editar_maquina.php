<?php
session_start();
if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}

require_once dirname(dirname(__DIR__)) . '/config/paths.php';
$config = includeConfig('config.php');

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
            echo "<script>alert('Todos los campos son obligatorios.'); window.location.href='editar_maquina.php?id=" . $id . "';</script>";
            exit();
        }

        // Actualizar la información de la máquina
        $stmt = $conexion->prepare("UPDATE maquinas SET nombre = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$nombre, $descripcion, $id]);

        echo "<script>alert('Máquina actualizada con éxito.'); window.location.href='control_maquinas.php';</script>";
        exit();
    }
} catch (PDOException $error) {
    echo "<script>alert('Error al actualizar la máquina: " . addslashes($error->getMessage()) . "'); window.location.href='control_maquinas.php';</script>";
    exit();
}
?>

<?php includeTemplate('header.php'); ?>

<div class="container-fluid px-3 py-4">
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

        <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de que deseas guardar los cambios?');">Guardar Cambios</button>
        <a href="control_maquinas.php" class="btn btn-secondary">Cancelar</a>

    </form>
</div>

<?php includeTemplate('footer.php'); ?>
