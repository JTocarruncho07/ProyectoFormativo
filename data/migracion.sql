CREATE DATABASE IF NOT EXISTS mina_recebo;
USE mina_recebo;

CREATE TABLE `empleados` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `apellido` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `documento` varchar(20) NOT NULL,
  `tipo_sangre` enum('O+','O-','A+','A-','B+','B-','AB+','AB-') NOT NULL,
  `fecha_inicio` date NOT NULL,
  `fecha_nacimiento` date NOT NULL,
  `sueldo` decimal(10,2) NOT NULL,
  `pago_quincena` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gastos` (
  `id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `gasto_maquina` (
  `id` int(11) NOT NULL,
  `id_maquina` int(11) NOT NULL,
  `tipo_gasto` enum('Combustible','Grasa','Repuestos') NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `ingresos` (
  `id` int(11) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `fecha` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `maquinas` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text NOT NULL,
  `horas_trabajadas` time DEFAULT '00:00:00'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `pagos_empleados` (
  `id` int(11) NOT NULL,
  `empleado_id` int(11) NOT NULL,
  `fecha` date NOT NULL,
  `monto` decimal(10,2) NOT NULL DEFAULT 0.00
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `id_usuario` int(11) NOT NULL,
  `token` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `registro_horas_diarias` (
  `id` int(11) NOT NULL,
  `id_maquina` int(11) NOT NULL,
  `fecha` date NOT NULL DEFAULT curdate(),
  `horas_diarias` time NOT NULL DEFAULT '00:00:00',
  `inicio_tiempo` datetime DEFAULT NULL,
  `estado` enum('activo','pausado') DEFAULT 'pausado'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

CREATE TABLE `usuarios` (
  `id` int(11) NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `correo` varchar(100) NOT NULL,
  `telefono` varchar(15) NOT NULL,
  `password` varchar(255) NOT NULL,
  `es_admin` tinyint(1) DEFAULT 0,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Índices para tablas
ALTER TABLE `empleados`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `documento` (`documento`);

ALTER TABLE `gastos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `gasto_maquina`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_maquina` (`id_maquina`);

ALTER TABLE `ingresos`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `maquinas`
  ADD PRIMARY KEY (`id`);

ALTER TABLE `pagos_empleados`
  ADD PRIMARY KEY (`id`),
  ADD KEY `empleado_id` (`empleado_id`);

ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_id_usuario` (`id_usuario`);

ALTER TABLE `registro_horas_diarias`
  ADD PRIMARY KEY (`id`),
  ADD KEY `id_maquina` (`id_maquina`);

ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `correo` (`correo`);

-- AUTO_INCREMENT de las tablas
ALTER TABLE `empleados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `gastos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `gasto_maquina`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

ALTER TABLE `ingresos`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

ALTER TABLE `maquinas`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `pagos_empleados`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

ALTER TABLE `registro_horas_diarias`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

ALTER TABLE `usuarios`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

-- Restricciones para tablas
ALTER TABLE `gasto_maquina`
  ADD CONSTRAINT `gasto_maquina_ibfk_1` FOREIGN KEY (`id_maquina`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE;

ALTER TABLE `pagos_empleados`
  ADD CONSTRAINT `pagos_empleados_ibfk_1` FOREIGN KEY (`empleado_id`) REFERENCES `empleados` (`id`) ON DELETE CASCADE;

ALTER TABLE `password_resets`
  ADD CONSTRAINT `fk_password_resets_usuario` FOREIGN KEY (`id_usuario`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

ALTER TABLE `registro_horas_diarias`
  ADD CONSTRAINT `registro_horas_diarias_ibfk_1` FOREIGN KEY (`id_maquina`) REFERENCES `maquinas` (`id`) ON DELETE CASCADE;
COMMIT;

INSERT INTO usuarios (nombre, correo, telefono, password, es_admin) 
VALUES ('Admin', 'jtocarruncho07@gmail.com', '3134954563', 
        '$2y$10$1AOlvwuE.67mchYFJgq7.OxQC4jLHmES8kb.R4DPYfsYsR/gYF.h.', 1);

-- Inserción de 10 ventas en la tabla `ingresos`
INSERT INTO `ingresos` (`id`, `monto`, `fecha`) VALUES
(1, 150000.00, '2025-03-25 10:00:00'),
(2, 220000.00, '2025-03-25 11:15:00'),
(3, 180000.00, '2025-03-25 12:30:00'),
(4, 250000.00, '2025-03-25 13:45:00'),
(5, 300000.00, '2025-03-25 14:00:00'),
(6, 210000.00, '2025-03-25 14:30:00'),
(7, 350000.00, '2025-03-25 15:00:00'),
(8, 400000.00, '2025-03-25 16:00:00'),
(9, 500000.00, '2025-03-25 17:00:00'),
(10, 450000.00, '2025-03-25 18:00:00');

-- Inserción de 10 registros de gastos (excluyendo los gastos de maquinaria) en la tabla `gastos`
INSERT INTO `gastos` (`id`, `monto`, `descripcion`, `fecha`) VALUES
(1, 50000.00, 'Compra de material de oficina', '2025-03-25 10:00:00'),
(2, 75000.00, 'Pago de servicios públicos', '2025-03-25 11:00:00'),
(3, 20000.00, 'Compra de mercado', '2025-03-25 12:00:00'),
(4, 120000.00, 'Alquiler de oficina', '2025-03-25 13:00:00'),
(5, 25000.00, 'Gastos en transporte', '2025-03-25 14:00:00'),
(6, 30000.00, 'Gastos en combustible', '2025-03-25 15:00:00'),
(7, 25000.00, 'Gastos en seguro', '2025-03-25 16:00:00'),
(8, 50000.00, 'Compra de herramientas', '2025-03-25 17:00:00'),
(9, 70000.00, 'Gastos en publicidad', '2025-03-25 18:00:00'),
(10, 130000.00, 'Gastos en recursos humanos', '2025-03-25 19:00:00');

-- Inserción de empleados con nuevos id comenzando desde 1
INSERT INTO `empleados` (`nombre`, `apellido`, `telefono`, `documento`, `tipo_sangre`, `fecha_inicio`, `fecha_nacimiento`, `sueldo`, `pago_quincena`) VALUES
('Carlos', 'Perez', '3123456789', '1023456789', 'O+', '2025-03-25', '1985-04-15', 2500000.00, 0),
('Ana', 'Martinez', '3122345678', '2034567890', 'A+', '2025-03-25', '1992-08-30', 2800000.00, 0),
('Luis', 'Gomez', '3134567890', '3045678901', 'B-', '2025-03-25', '1990-02-05', 3000000.00, 0),
('Maria', 'Lopez', '3145678901', '4056789012', 'AB+', '2025-03-25', '1999-11-22', 2200000.00, 0),
('Pedro', 'Sanchez', '3156789012', '5067890123', 'O-', '2025-03-25', '1980-12-10', 3500000.00, 0);

-- Inserción de registros de pago para los empleados con los nuevos ID
INSERT INTO `pagos_empleados` (`empleado_id`, `fecha`, `monto`) VALUES
(1, '2025-03-10', 2500000.00),
(1, '2025-03-25', 2500000.00),
(2, '2025-03-10', 2800000.00),
(2, '2025-03-25', 2800000.00),
(3, '2025-03-10', 3000000.00),
(3, '2025-03-25', 3000000.00),
(4, '2025-03-10', 2200000.00),
(4, '2025-03-25', 2200000.00),
(5, '2025-03-10', 3500000.00),
(5, '2025-03-25', 3500000.00);

INSERT INTO `maquinas` (`id`, `nombre`, `descripcion`, `horas_trabajadas`) VALUES
(1, 'Excavadora', 'Máquina de gran capacidad para excavar grandes cantidades de material.', '02:30:00'),
(2, 'Bulldozer', 'Máquina utilizada para mover grandes cantidades de tierra o rocas.', '04:15:00'),
(3, 'Camión Volquete', 'Camión usado para transportar material pesado, como tierra, rocas, o minerales.', '06:00:00'),
(4, 'Perforadora', 'Máquina especializada en perforar el terreno para la extracción de minerales.', '03:45:00'),
(5, 'Trituradora', 'Máquina encargada de triturar grandes bloques de material extraído de la mina.', '05:10:00');

INSERT INTO `gasto_maquina` (`id`, `id_maquina`, `tipo_gasto`, `monto`, `descripcion`, `fecha`) VALUES
(1, 1, 'Combustible', 5000.00, 'Gasto en combustible para la excavadora', '2025-03-20 08:00:00'),
(2, 1, 'Grasa', 1500.00, 'Gasto en grasa para mantenimiento de la excavadora', '2025-03-20 08:00:00'),
(3, 1, 'Repuestos', 2500.00, 'Repuesto de piezas para excavadora', '2025-03-20 08:00:00'),
(4, 2, 'Combustible', 4000.00, 'Gasto en combustible para el bulldozer', '2025-03-21 08:00:00'),
(5, 2, 'Grasa', 1300.00, 'Gasto en grasa para mantenimiento del bulldozer', '2025-03-21 08:00:00'),
(6, 2, 'Repuestos', 2000.00, 'Repuesto de piezas para bulldozer', '2025-03-21 08:00:00'),
(7, 3, 'Combustible', 7000.00, 'Gasto en combustible para camión volquete', '2025-03-22 08:00:00'),
(8, 3, 'Grasa', 1700.00, 'Gasto en grasa para mantenimiento de camión volquete', '2025-03-22 08:00:00'),
(9, 3, 'Repuestos', 2800.00, 'Repuesto de piezas para camión volquete', '2025-03-22 08:00:00'),
(10, 4, 'Combustible', 3000.00, 'Gasto en combustible para perforadora', '2025-03-23 08:00:00'),
(11, 4, 'Grasa', 1000.00, 'Gasto en grasa para mantenimiento de perforadora', '2025-03-23 08:00:00'),
(12, 4, 'Repuestos', 1500.00, 'Repuesto de piezas para perforadora', '2025-03-23 08:00:00'),
(13, 5, 'Combustible', 6000.00, 'Gasto en combustible para trituradora', '2025-03-24 08:00:00'),
(14, 5, 'Grasa', 1400.00, 'Gasto en grasa para mantenimiento de trituradora', '2025-03-24 08:00:00'),
(15, 5, 'Repuestos', 2200.00, 'Repuesto de piezas para trituradora', '2025-03-24 08:00:00');

INSERT INTO `registro_horas_diarias` (`id`, `id_maquina`, `fecha`, `horas_diarias`, `inicio_tiempo`, `estado`) VALUES
(1, 1, '2025-03-20', '02:30:00', '2025-03-20 08:00:00', 'activo'),
(2, 2, '2025-03-21', '04:15:00', '2025-03-21 08:00:00', 'activo'),
(3, 3, '2025-03-22', '06:00:00', '2025-03-22 08:00:00', 'activo'),
(4, 4, '2025-03-23', '03:45:00', '2025-03-23 08:00:00', 'activo'),
(5, 5, '2025-03-24', '05:10:00', '2025-03-24 08:00:00', 'activo'),
(6, 1, '2025-03-25', '02:45:00', '2025-03-25 08:00:00', 'activo'),
(7, 2, '2025-03-25', '04:00:00', '2025-03-25 08:00:00', 'activo'),
(8, 3, '2025-03-25', '06:15:00', '2025-03-25 08:00:00', 'activo'),
(9, 4, '2025-03-25', '03:50:00', '2025-03-25 08:00:00', 'activo'),
(10, 5, '2025-03-25', '05:20:00', '2025-03-25 08:00:00', 'activo');