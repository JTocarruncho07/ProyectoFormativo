<?php
session_start();
include 'config.php';

if (!isset($_GET['id'])) {
    die("ID de empleado no proporcionado.");
}

$id = $_GET['id'];

try {
    $config = include 'config.php';
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
        die("Empleado no encontrado.");
    }

    // Si el formulario de edición se ha enviado
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        $nombre = $_POST['nombre'];
        $apellido = $_POST['apellido'];
        $documento = $_POST['documento'];
        $telefono = $_POST['telefono'];
        $fecha_nacimiento = $_POST['fecha_nacimiento'];
        $sueldo = $_POST['sueldo'];
        $tipo_sangre = $_POST['tipo_sangre'];  // Aquí se recoge el tipo de sangre seleccionado
        $fecha_inicio = $_POST['fecha_inicio'];

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

        header("Location: empleados.php");
        exit();
    }

} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Editar Empleado</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container mt-5">
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
                        <option value="A+" <?= ($empleado['tipo_sangre'] == 'A+') ? 'selected' : '' ?>>A+</option>
                        <option value="A-" <?= ($empleado['tipo_sangre'] == 'A-') ? 'selected' : '' ?>>A-</option>
                        <option value="B+" <?= ($empleado['tipo_sangre'] == 'B+') ? 'selected' : '' ?>>B+</option>
                        <option value="B-" <?= ($empleado['tipo_sangre'] == 'B-') ? 'selected' : '' ?>>B-</option>
                        <option value="O+" <?= ($empleado['tipo_sangre'] == 'O+') ? 'selected' : '' ?>>O+</option>
                        <option value="O-" <?= ($empleado['tipo_sangre'] == 'O-') ? 'selected' : '' ?>>O-</option>
                        <option value="AB+" <?= ($empleado['tipo_sangre'] == 'AB+') ? 'selected' : '' ?>>AB+</option>
                        <option value="AB-" <?= ($empleado['tipo_sangre'] == 'AB-') ? 'selected' : '' ?>>AB-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" class="form-control" value="<?= htmlspecialchars($empleado['fecha_inicio']) ?>" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Guardar Cambios</button>
            <a href="empleados.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
