<?php
session_start();
require_once dirname(dirname(__DIR__)) . '/config/paths.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $apellido = $_POST['apellido'];
    $documento = $_POST['documento'];
    $telefono = $_POST['telefono'];
    $fecha_nacimiento = $_POST['fecha_nacimiento'];
    $sueldo = $_POST['sueldo'];
    $tipo_sangre = $_POST['tipo_sangre'];
    $fecha_inicio = $_POST['fecha_inicio'];

    try {
        $config = includeConfig('config.php');
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );

        $query = "INSERT INTO empleados (nombre, apellido, documento, telefono, fecha_nacimiento, sueldo, tipo_sangre, fecha_inicio) VALUES (:nombre, :apellido, :documento, :telefono, :fecha_nacimiento, :sueldo, :tipo_sangre, :fecha_inicio)";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':nombre', $nombre);
        $stmt->bindParam(':apellido', $apellido);
        $stmt->bindParam(':documento', $documento);
        $stmt->bindParam(':telefono', $telefono);
        $stmt->bindParam(':fecha_nacimiento', $fecha_nacimiento);
        $stmt->bindParam(':sueldo', $sueldo);
        $stmt->bindParam(':tipo_sangre', $tipo_sangre);
        $stmt->bindParam(':fecha_inicio', $fecha_inicio);
        $stmt->execute();

        header("Location: empleados.php");
        exit();
    } catch (PDOException $error) {
        if ($error->getCode() == 23000 && strpos($error->getMessage(), '1062') !== false && strpos($error->getMessage(), 'documento') !== false) {
            echo "<script>alert('Error: Ya existe un empleado con este número de documento. Por favor, verifique el documento ingresado.'); window.history.back();</script>";
            exit();
        } else {
            die("Error en la conexión: " . $error->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Crear Empleado</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
</head>
<body>
    <?php includeTemplate('header.php'); ?>

    <div class="container-fluid px-3 py-4">
        <h2 class="text-center">Nuevo Empleado</h2>

        <form action="crear_empleado.php" method="POST">
            <div class="row">
                <div class="col-md-4">
                    <label>Nombre:</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Apellido:</label>
                    <input type="text" name="apellido" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Documento:</label>
                    <input type="text" name="documento" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Teléfono:</label>
                    <input type="text" name="telefono" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Nacimiento:</label>
                    <input type="date" name="fecha_nacimiento" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Sueldo:</label>
                    <input type="number" step="0.01" name="sueldo" class="form-control" required>
                </div>
                <div class="col-md-4">
                    <label>Tipo de Sangre:</label>
                    <select name="tipo_sangre" class="form-control" required>
                        <option value="">Seleccione un tipo de sangre</option>
                        <option value="A+">A+</option>
                        <option value="A-">A-</option>
                        <option value="B+">B+</option>
                        <option value="B-">B-</option>
                        <option value="O+">O+</option>
                        <option value="O-">O-</option>
                        <option value="AB+">AB+</option>
                        <option value="AB-">AB-</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label>Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" class="form-control" required>
                </div>
            </div>
            <button type="submit" class="btn btn-primary mt-3">Guardar Empleado</button>
            <a href="empleados.php" class="btn btn-secondary mt-3">Cancelar</a>
        </form>
    </div>

    <?php includeTemplate('footer.php'); ?>
</body>
</html>
