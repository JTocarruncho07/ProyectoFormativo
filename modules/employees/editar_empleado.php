<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/paths.php';

if (!isset($_GET['id'])) {
    echo "<script>alert('ID de empleado no proporcionado.'); window.location.href='empleados.php';</script>";
    exit();
}

$id = $_GET['id'];

try {
    $config = includeConfig('config.php');
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener datos del empleado
    $stmt = $conexion->prepare("SELECT * FROM empleados WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();
    $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$empleado) {
        echo "<script>alert('Empleado no encontrado.'); window.location.href='empleados.php';</script>";
        exit();
    }

    // Si el formulario de edición se ha enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = trim($_POST['nombre']);
        $apellido = trim($_POST['apellido']);
        $documento = trim($_POST['documento']);
        $telefono = trim($_POST['telefono']);
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $sueldo = $_POST['sueldo'];
        $tipo_sangre = $_POST['tipo_sangre'];
        $fecha_inicio = $_POST['fecha_inicio'];

        if (empty($nombre) || empty($apellido) || empty($documento) || empty($telefono) || empty($fecha_nacimiento) || empty($sueldo) || empty($tipo_sangre) || empty($fecha_inicio)) {
            echo "<script>alert('Todos los campos son obligatorios.'); window.location.href='editar_empleado.php?id=" . $id . "';</script>";
            exit();
        }

        $stmt = $conexion->prepare("UPDATE empleados SET nombre = :nombre, apellido = :apellido, documento = :documento, telefono = :telefono, fecha_nacimiento = :fecha_nacimiento, sueldo = :sueldo, tipo_sangre = :tipo_sangre, fecha_inicio = :fecha_inicio WHERE id = :id");
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':documento', $documento);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
        $stmt->bindParam(':sueldo', $sueldo);
        $stmt->bindParam(':tipo_sangre', $tipo_sangre);  // Aquí se pasa el tipo de sangre seleccionado
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->bindParam(':id', $id);
        $stmt->execute();

        echo "<script>alert('Empleado actualizado con éxito.'); window.location.href='empleados.php';</script>";
        exit();
    }

} catch (PDOException $error) {
    echo "<script>alert('Error al actualizar el empleado: " . addslashes($error->getMessage()) . "'); window.location.href='empleados.php';</script>";
    exit();
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php includeTemplate('header.php'); ?>

    <div class="container-fluid px-3 py-4">
        <h2 class="text-center">Editar Empleado</h2>

        <form method="POST">
            <div class="row">
                <div class="col-md-4">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" class="form-control" value="<?= htmlspecialchars($empleado['nombre']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Apellido:</label>
                    <input type="text" name="apellido" class="form-control" value="<?= htmlspecialchars($empleado['apellido']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Documento:</label>
                    <input type="text" name="documento" class="form-control" value="<?= htmlspecialchars($empleado['documento']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" class="form-control" value="<?= htmlspecialchars($empleado['telefono']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Nacimiento:</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" value="<?= htmlspecialchars($empleado['fecha_nacimiento']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Sueldo:</label>
                    <input type="number" step="0.01" name="sueldo" class="form-control" value="<?= htmlspecialchars($empleado['sueldo']) ?>" required>
                </div>
                <div class="col-md-4">
                    <label>Tipo de Sangre:</label>
                    <select name="tipo_sangre" class="form-control" required>
                        <option value="">Seleccione un tipo de sangre</option>
                        <option value="O+" <?= ($empleado['tipo_sangre'] == 'O+') ? 'selected' : '' ?>>O+</option>
                        <option value="O-" <?= ($empleado['tipo_sangre'] == 'O-') ? 'selected' : '' ?>>O-</option>
                        <option value="A+" <?= ($empleado['tipo_sangre'] == 'A+') ? 'selected' : '' ?>>A+</option>
                        <option value="A-" <?= ($empleado['tipo_sangre'] == 'A-') ? 'selected' : '' ?>>A-</option>
                        <option value="B+" <?= ($empleado['tipo_sangre'] == 'B+') ? 'selected' : '' ?>>B+</option>
                        <option value="B-" <?= ($empleado['tipo_sangre'] == 'B-') ? 'selected' : '' ?>>B-</option>
                        <option value="AB+" <?= ($empleado['tipo_sangre'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
                        <option value="AB-" <?= ($empleado['tipo_sangre'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($empleado['fecha_inicio']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3" onclick="return confirm('¿Estás seguro de que deseas guardar los cambios?');">Guardar Cambios</button>
            <a href="empleados.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div>

    <?php includeTemplate('footer.php'); ?>
</body>
</html>
