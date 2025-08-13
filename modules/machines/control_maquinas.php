<body>

<?php
session_start();
require_once '../../config/paths.php';
require_once '../../config/config.php';

try {
    $config = includeConfig('config.php');
    $pdo = new PDO(
        "mysql:host={$config['db']['host']};dbname={$config['db']['dbname']}",
        $config['db']['user'],
        $config['db']['pass'],
        $config['db']['options']
    );
} catch (PDOException $error) {
    echo "Error: " . $error->getMessage();
}

$sql = "SELECT m.id, m.nombre, m.descripcion, 
               IFNULL(r.horas_diarias, '00:00:00') AS horas_diarias
        FROM maquinas m
        LEFT JOIN registro_horas_diarias r 
        ON m.id = r.id_maquina AND DATE(r.fecha) = CURDATE()";
$stmt = $pdo->prepare($sql);
$stmt->execute();
$maquinas = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php includeTemplate('header.php'); ?>

    <div class="container-fluid px-3 py-4">
        <h2 class="text-center">Control de Máquinas</h2>

        <!-- Mensaje de éxito si existe -->
        <?php if (isset($_SESSION['mensaje'])): ?>
            <div class="alert alert-<?php echo $_SESSION['tipo_mensaje']; ?> alert-dismissible fade show" role="alert">
                <?php echo $_SESSION['mensaje']; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            <?php unset($_SESSION['mensaje'], $_SESSION['tipo_mensaje']); ?>
        <?php endif; ?>

        <div class="d-flex justify-content-between mb-3">
    <button class="btn btn-success" onclick="window.location.href='registrar_maquina.php'">Registrar Nueva Máquina</button>
    <div class="d-flex">
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#editarModal" style="margin-right: 8px;">Editar Máquina</button>
        <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#eliminarModal">Eliminar Máquina</button>
    </div>
</div>


        <div class="table-responsive">
            <table class="table table-striped table-bordered">
                <thead class="table-dark">
                    <tr>
                        <th>Nombre</th>
                        <th>Descripción</th>
                        <th>Horas Trabajadas</th>
                        <th>Acciones</th>
                        <th>Historial</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($maquinas as $maquina): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($maquina['nombre']); ?></td>
                        <td><?php echo htmlspecialchars($maquina['descripcion']); ?></td>
                        <td id="horas-<?php echo $maquina['id']; ?>"><?php echo $maquina['horas_diarias']; ?></td>
                        <td>
                            <div class="d-flex justify-content-center">
                                <button class="btn btn-success" id="btn-iniciar-<?php echo $maquina['id']; ?>" onclick="iniciarPausar(<?php echo $maquina['id']; ?>)" style="margin-right: 8px;">Iniciar</button>
                                <button class="btn btn-warning" id="reiniciar-<?php echo $maquina['id']; ?>" onclick="reiniciarTiempo(<?php echo $maquina['id']; ?>)">Reiniciar</button>
                            </div>
                        </td>
                        <td>
                            <button class="btn btn-primary" onclick="mostrarHistorial(<?php echo $maquina['id']; ?>)">Ver Historial</button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal Editar Máquina -->
    <div class="modal fade" id="editarModal" tabindex="-1" aria-labelledby="editarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editarModalLabel">Editar Máquina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selecciona una máquina para editar:</p>
                    <ul class="list-group">
                        <?php foreach ($maquinas as $maquina): ?>
                            <li class="list-group-item">
                                <a href="editar_maquina.php?id=<?= $maquina['id'] ?>">
                                    <?= htmlspecialchars($maquina['nombre']) ?>
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

    <!-- Modal Eliminar Máquina -->
    <div class="modal fade" id="eliminarModal" tabindex="-1" aria-labelledby="eliminarModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="eliminarModalLabel">Eliminar Máquina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Selecciona una máquina para eliminar:</p>
                    <ul class="list-group">
                        <?php foreach ($maquinas as $maquina): ?>
                            <li class="list-group-item">
                                <form action="eliminar_maquina.php" method="post" onsubmit="return confirm('¿Estás seguro de eliminar esta máquina?');">
                                    <input type="hidden" name="id" value="<?= $maquina['id'] ?>">
                                    <?= htmlspecialchars($maquina['nombre']) ?>
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

    <?php includeTemplate('footer.php'); ?>

    <script>
let cronometros = {};  // Almacenará las instancias de los cronómetros
let tiempos = JSON.parse(localStorage.getItem('tiempos')) || {};  // Cargar los tiempos previos desde localStorage

// Al cargar la página, verificamos si hay cronómetros activos
window.onload = function () {
    for (let id in tiempos) {
        let btnIniciar = document.getElementById(`btn-iniciar-${id}`);
        let btnReiniciar = document.getElementById(`reiniciar-${id}`);
        
        // Si el cronómetro estaba activo, reiniciarlo
        if (tiempos[id].activo) {
            cronometros[id] = setInterval(() => {
                actualizarHoras(id);
            }, 1000);

            // Cambiar el texto y el estilo del botón de iniciar/pausar
            btnIniciar.textContent = 'Pausar';  
            btnIniciar.classList.replace('btn-success', 'btn-danger');  
            btnReiniciar.disabled = false;  
            btnReiniciar.classList.replace('btn-warning', 'btn-info');  
        } else {
            // Si el cronómetro estaba pausado, solo actualizamos el tiempo
            let tiempoFormateado = formatTime(tiempos[id].tiempo);
            document.getElementById(`horas-${id}`).textContent = tiempoFormateado;
        }
    }
};

// Función para iniciar o pausar el cronómetro
function iniciarPausar(id) {
    let btnIniciar = document.getElementById(`btn-iniciar-${id}`);
    let btnReiniciar = document.getElementById(`reiniciar-${id}`);

    if (!cronometros[id]) {
        // Si no hay cronómetro en ejecución, iniciar
        cronometros[id] = setInterval(() => {
            actualizarHoras(id);
        }, 1000);

        // Guardar el estado y tiempo en localStorage
        tiempos[id] = { activo: true, tiempo: tiempos[id]?.tiempo || 0 };
        localStorage.setItem('tiempos', JSON.stringify(tiempos));

        btnIniciar.textContent = 'Pausar';  
        btnIniciar.classList.replace('btn-success', 'btn-danger');  
        btnReiniciar.disabled = false;  
        btnReiniciar.classList.replace('btn-warning', 'btn-info');
    } else {
        // Si ya está en ejecución, pausar
        clearInterval(cronometros[id]);
        cronometros[id] = null;

        // Guardar el estado de pausa
        tiempos[id].activo = false;
        localStorage.setItem('tiempos', JSON.stringify(tiempos));

        btnIniciar.textContent = 'Iniciar';  
        btnIniciar.classList.replace('btn-danger', 'btn-success');  
    }
}

// Actualizar las horas del cronómetro
function actualizarHoras(id) {
    // Aumentar el tiempo acumulado por segundo
    tiempos[id].tiempo += 1;
    localStorage.setItem('tiempos', JSON.stringify(tiempos));

    // Mostrar el tiempo actualizado en la interfaz
    let tiempoFormateado = formatTime(tiempos[id].tiempo);
    document.getElementById(`horas-${id}`).textContent = tiempoFormateado;
}

// Función para formatear el tiempo en formato HH:MM:SS
function formatTime(segundos) {
    let horas = Math.floor(segundos / 3600);
    let minutos = Math.floor((segundos % 3600) / 60);
    let segundosRestantes = segundos % 60;

    return `${String(horas).padStart(2, '0')}:${String(minutos).padStart(2, '0')}:${String(segundosRestantes).padStart(2, '0')}`;
}

// Reiniciar el tiempo
function reiniciarTiempo(id) {
    tiempos[id].tiempo = 0;
    localStorage.setItem('tiempos', JSON.stringify(tiempos));

    document.getElementById(`horas-${id}`).textContent = '00:00:00';
}

// Mostrar historial de la máquina
function mostrarHistorial(id) {
    window.location.href = `historial_maquina.php?id=${id}`;
}



    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>