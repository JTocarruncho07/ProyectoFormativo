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

try {
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $error) {
    echo "<script>alert('Error de conexión: " . addslashes($error->getMessage()) . "'); window.location.href = 'nuevo_gasto.php';</script>";
    exit();
}

// Manejar solicitud POST para actualizar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validar que se proporcione un ID válido
    if (!isset($_POST['id']) || empty($_POST['id']) || !is_numeric($_POST['id'])) {
        echo "<script>alert('ID de gasto no proporcionado.'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();
    }

    // Validar campos requeridos
    if (empty($_POST['monto'])) {
        echo "<script>alert('El monto es requerido.'); window.history.back();</script>";
        exit();
    }

    $id = $_POST['id'];
    $monto = $_POST['monto'];
    $descripcion = $_POST['descripcion'];

    try {
        $stmt = $conexion->prepare("UPDATE gastos SET monto = ?, descripcion = ? WHERE id = ?");
        $stmt->execute([$monto, $descripcion, $id]);

        echo "<script>alert('Gasto actualizado con éxito.'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();

    } catch (PDOException $error) {
        echo "<script>alert('Error al actualizar el gasto: " . addslashes($error->getMessage()) . "'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();
    }
}

// Manejar solicitud GET para mostrar formulario
if (!isset($_GET['id']) || empty($_GET['id']) || !is_numeric($_GET['id'])) {
    echo "<script>alert('ID de gasto no proporcionado.'); window.location.href = 'nuevo_gasto.php';</script>";
    exit();
}

$id = $_GET['id'];

try {
    $stmt = $conexion->prepare("SELECT * FROM gastos WHERE id = ?");
    $stmt->execute([$id]);
    $gasto = $stmt->fetch();

    if (!$gasto) {
        echo "<script>alert('Gasto no encontrado.'); window.location.href = 'nuevo_gasto.php';</script>";
        exit();
    }
} catch (PDOException $error) {
    echo "<script>alert('Error al obtener el gasto: " . addslashes($error->getMessage()) . "'); window.location.href = 'nuevo_gasto.php';</script>";
    exit();
}
?>

<?php includeTemplate('header.php'); ?>

<div class="container-fluid px-3 py-4">
    <h2 class="text-center">Editar Gasto</h2>
    <form method="POST">
        <input type="hidden" name="id" value="<?php echo $gasto['id']; ?>">
        
        <div class="form-group">
            <label for="fecha">Fecha:</label>
            <input type="text" class="form-control" value="<?php echo formatearFechaEspanol($gasto['fecha']); ?>" readonly>
            <small class="form-text text-muted">La fecha no se puede modificar</small>
        </div>
        
        <div class="form-group">
            <label for="monto">Monto del Gasto:</label>
            <input type="number" step="0.01" name="monto" class="form-control" value="<?php echo $gasto['monto']; ?>" required>
        </div>
        
        <div class="form-group">
            <label for="descripcion">Descripción:</label>
            <textarea name="descripcion" class="form-control" placeholder="Ejemplo: Compra de herramientas"><?php echo $gasto['descripcion']; ?></textarea>
        </div>
        
        <button type="submit" class="btn btn-success" onclick="return confirm('¿Estás seguro de que deseas guardar los cambios?');">Guardar Cambios</button>
        <a href="nuevo_gasto.php" class="btn btn-secondary">Cancelar</a>
    </form>
</div>

<?php includeTemplate('footer.php'); ?>