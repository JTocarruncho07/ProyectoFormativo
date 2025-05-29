<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Recebera Alto Blanco</title>
    <!-- Incluir Bootstrap CSS -->
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="/css/style.css">
    <!-- Agregar FontAwesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.1/css/all.min.css">
    <style>
        .navbar-brand {
            font-weight: bold;
        }
        .nav-link {
            margin-right: 10px;
        }
        .dropdown-menu {
            border-radius: 0;
        }
    </style>
</head>
<body>
<?php if(isset($_SESSION['usuario'])): ?>
    <nav class="navbar navbar-expand-lg navbar-light bg-warning">
        <div class="container">
        <a class="navbar-brand" href="https://maps.app.goo.gl/6ks1t5D11q24sQsu5" target="_blank">Recebera Alto Blanco</a>
            <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mr-auto">
                    
                    <!-- Máquinas Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="maquinasDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-truck"></i> Máquinas
                        </a>
                        <div class="dropdown-menu" aria-labelledby="maquinasDropdown">
                            <a class="dropdown-item" href="control_maquinas.php">Control de Máquinas</a>
                            <a class="dropdown-item" href="gastos_maquinas.php">Gastos de Máquinas</a>
                        </div>
                    </li>
                    
                    <!-- Finanzas Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="finanzasDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-dollar-sign"></i> Finanzas
                        </a>
                        <div class="dropdown-menu" aria-labelledby="finanzasDropdown">
                            <a class="dropdown-item" href="nueva_venta.php">Nueva Venta</a>
                            <a class="dropdown-item" href="nuevo_gasto.php">Nuevo Gasto</a>
                        </div>
                    </li>
                    
                    <!-- Reportes Dropdown -->
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="reportesDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-chart-bar"></i> Reportes
                        </a>
                        <div class="dropdown-menu" aria-labelledby="reportesDropdown">
                            <a class="dropdown-item" href="reporte.php">Reporte Mensual</a>
                            <a class="dropdown-item" href="reporte_anual.php">Reporte Anual</a>
                        </div>
                    </li>

                    <!-- Empleados -->
                    <li class="nav-item">
                        <a class="nav-link" href="empleados.php">
                            <i class="fas fa-users"></i> Empleados
                        </a>
                    </li>

                    <!-- PQRSF -->
                    
                </ul>
                
                <!-- User Menu -->
                <ul class="navbar-nav">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-user"></i> <?php echo $_SESSION['nombre']; ?>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item" href="logout.php">Cerrar Sesión</a>
                        </div>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
<?php endif; ?>
</body>
</html>
