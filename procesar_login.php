<?php
session_start();
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $correo = $_POST['correo'];
    $contrasena = $_POST['contrasena'];

    try {
        $config = include 'config.php';
        $conexion = new PDO(
            'mysql:host=' . $config['db']['host'] . ';dbname=' . $config['db']['dbname'],
            $config['db']['user'],
            $config['db']['pass'],
            $config['db']['options']
        );

        $query = "SELECT * FROM usuarios WHERE correo = :correo";
        $stmt = $conexion->prepare($query);
        $stmt->bindParam(':correo', $correo);
        $stmt->execute();
        $usuario = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($usuario && password_verify($contrasena, $usuario['password'])) {
            $_SESSION['usuario'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            header("Location: control_maquinas.php");
            exit();
        } else {
            echo "<script>
                alert('Credenciales incorrectas. Inténtalo de nuevo.');
                window.location.href = 'login.php';
            </script>";
        }
    } catch (PDOException $error) {
        echo "<script>
            alert('Error en la conexión: " . $error->getMessage() . "');
            window.location.href = 'login.php';
        </script>";
    }
}
?>
