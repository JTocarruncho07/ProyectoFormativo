<?php
session_start();
$config = include 'config.php';  // Asegúrate de que la ruta es correcta


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Verificar si la variable 'id' está presente en el POST
        if (!isset($_POST['id'])) {
            echo 'Error: No se ha recibido el ID del empleado.';
            exit;
        }

        $idEmpleado = $_POST['id'];

        // Establecer conexión a la base de datos
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );

        // Comprobar si el empleado existe y obtener su sueldo
        $stmt = $conexion->prepare("SELECT * FROM empleados WHERE id = :id");
        $stmt->bindParam(':id', $idEmpleado);
        $stmt->execute();
        $empleado = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$empleado) {
            echo 'Error: El empleado no existe.';
            exit;
        }

        // Obtener el sueldo del empleado
        $monto = $empleado['sueldo'];
        $fecha = date('Y-m-d');

        // Insertar el pago en la tabla pagos_empleados
        $stmtInsert = $conexion->prepare("INSERT INTO pagos_empleados (empleado_id, monto, fecha) 
                                          VALUES (:empleado_id, :monto, :fecha)");
        $stmtInsert->bindParam(':empleado_id', $idEmpleado);
        $stmtInsert->bindParam(':monto', $monto);
        $stmtInsert->bindParam(':fecha', $fecha);
        $stmtInsert->execute();

        // Actualizar el valor de 'pago_quincena' a 1
        $stmtUpdate = $conexion->prepare("UPDATE empleados SET pago_quincena = 1 WHERE id = :id");
        $stmtUpdate->bindParam(':id', $idEmpleado);
        $stmtUpdate->execute();

        // Redirigir de vuelta al listado de empleados
        header('Location: empleados.php');
        exit;
    } catch (PDOException $error) {
        echo 'Error: ' . $error->getMessage();
    }
}
?>
