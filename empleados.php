<?php
session_start();
$config = include 'config.php';  // Asegúrate de que la ruta es correcta

try {
    // Establecer conexión a la base de datos
    $conexion = new PDO(
        'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );

    // Obtener todos los empleados
    $stmt = $conexion->prepare("SELECT * FROM empleados");
    $stmt->execute();
    $empleados = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $error) {
    die("Error de conexión: " . $error->getMessage());
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestión de Empleados</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body>
    <?php include 'templates/header.php'; ?>

    <div class="container mt-5">
        <h2 class="text-center">Gestión de Empleados</h2>

        <div class="d-flex justify-content-between mb-3 align-items-center">
            <!-- Botón Agregar Empleado -->
            <a href="crear_empleado.php" class="btn btn-success">Registrar Empleado</a>

            <!-- Botones Editar y Eliminar -->
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editarModal">Editar Empleado</button>
                <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarModal">Eliminar Empleado</button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Nombre</th>
                        <th>Apellido</th>
                        <th>Documento</th>
                        <th>Teléfono</th>
                        <th>Sueldo</th>
                        <th>Pago Quincena</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($empleados as $empleado): ?>
                        <tr>
                            <td><?= htmlspecialchars($empleado['nombre']) ?></td>
                            <td><?= htmlspecialchars($empleado['apellido']) ?></td>
                            <td><?= htmlspecialchars($empleado['documento']) ?></td>
                            <td><?= htmlspecialchars($empleado['telefono']) ?></td>
                            <td>$<?= number_format($empleado['sueldo'], 2) ?></td>
                            <td>
                                <span id="estado-<?= $empleado['id'] ?>">
                                    <?= isset($empleado['pago_quincena']) && $empleado['pago_quincena'] == 1 ? 'Realizado' : 'Pendiente' ?>
                                </span>
                            </td>
                            <td>
                                <?php if (isset($empleado['pago_quincena']) && $empleado['pago_quincena'] == 0): ?>
                                    <!-- Formulario para procesar el pago -->
                                    <form action="procesar_pago.php" method="POST">
                                        <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
                                        <button type="submit" class="btn btn-primary btn-sm">Pagar</button>
                                    </form>
                                <?php else: ?>
                                    <button class="btn btn-secondary btn-sm" disabled>Pagado</button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php include 'templates/footer.php'; ?>

    <!-- Modal Editar Empleado -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selecciona un empleado para editar:</p>
                    <ul class="list-group">
                        <?php foreach ($empleados as $empleado): ?>
                            <li class="list-group-item">
                                <a href="editar_empleado.php?id=<?= $empleado['id'] ?>">
                                    <?= htmlspecialchars($empleado['nombre']) ?> <?= htmlspecialchars($empleado['apellido']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal Eliminar Empleado -->
    <div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarModalLabel">Eliminar Empleado</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selecciona un empleado para eliminar:</p>
                    <ul class="list-group">
                        <?php foreach ($empleados as $empleado): ?>
                            <li class="list-group-item">
                                <form action="eliminar_empleado.php" method="post" onsubmit="return confirm('¿Estás seguro de eliminar este empleado?');">
                                    <input type="hidden" name="id" value="<?= $empleado['id'] ?>">
                                    <?= htmlspecialchars($empleado['nombre']) ?> <?= htmlspecialchars($empleado['apellido']) ?>
                                    <button type="submit" class="btn btn-danger btn-sm float-end">Eliminar</button>
                                </form>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
