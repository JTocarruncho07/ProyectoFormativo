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
    $conexion->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = trim($_POST['nombre'] ?? '');
    $descripcion = trim($_POST['descripcion'] ?? '');

    if (!empty($nombre) && !empty($descripcion)) {
        try {
            // Insertar máquina
            $sql = "INSERT INTO maquinas (nombre, descripcion) VALUES (:nombre, :descripcion)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':nombre', $nombre, PDO::PARAM_STR);
            $stmt->bindParam(':descripcion', $descripcion, PDO::PARAM_STR);
            $stmt->execute();

            // Obtener el ID de la máquina recién insertada
            $id_maquina = $conexion->lastInsertId();

            // Insertar en registro_horas_diarias para que el cronómetro funcione correctamente
            $sql = "INSERT INTO registro_horas_diarias (id_maquina, fecha, horas_diarias, inicio_tiempo) 
                    VALUES (:id_maquina, CURDATE(), '00:00:00', NULL)";
            $stmt = $conexion->prepare($sql);
            $stmt->bindParam(':id_maquina', $id_maquina, PDO::PARAM_INT);
            $stmt->execute();

            $_SESSION['mensaje'] = "Máquina registrada con éxito.";
            $_SESSION['tipo_mensaje'] = "success";
            header("Location: control_maquinas.php");
            exit();
        } catch (PDOException $error) {
            $_SESSION['mensaje'] = "Error al registrar la máquina: " . $error->getMessage();
            $_SESSION['tipo_mensaje'] = "danger";
        }
    } else {
        $_SESSION['mensaje'] = "Todos los campos son obligatorios.";
        $_SESSION['tipo_mensaje'] = "warning";
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Registrar Máquina</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center">Registrar Nueva Máquina</h2>

        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <form method="POST" action="registrar_maquina.php" class="mt-4">
            <div class="mb-3">
                <label for="nombre" class="form-label">Nombre de la Máquina</label>
                <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="mb-3">
                <label for="descripcion" class="form-label">Descripción</label>
                <textarea class="form-control" id="descripcion" name="descripcion" rows="3" required></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Registrar</button>
            <a href="control_maquinas.php" class="btn btn-secondary">Volver</a>
        </form>
    </div>

    <?php include 'templates/footer.php'; ?>
</body>
</html>
