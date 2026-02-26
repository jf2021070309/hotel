-- ============================================================
-- SISTEMA DE GESTIÓN HOTEL
-- Base de datos completa
-- ============================================================

CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;

-- --------------------------------------------------------
-- Tabla: habitaciones
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `numero` VARCHAR(10) NOT NULL,
  `tipo` ENUM('Simple','Doble','Suite') NOT NULL DEFAULT 'Simple',
  `piso` TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `estado` ENUM('libre','ocupado') NOT NULL DEFAULT 'libre',
  `precio_base` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`numero`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: clientes
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `nombre` VARCHAR(150) NOT NULL,
  `dni` VARCHAR(20) NOT NULL,
  `telefono` VARCHAR(20) DEFAULT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dni` (`dni`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: registros
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `registros` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `habitacion_id` INT UNSIGNED NOT NULL,
  `cliente_id` INT UNSIGNED NOT NULL,
  `fecha_ingreso` DATE NOT NULL,
  `fecha_salida` DATE DEFAULT NULL,
  `precio` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `estado` ENUM('activo','finalizado') NOT NULL DEFAULT 'activo',
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_reg_habitacion` (`habitacion_id`),
  KEY `fk_reg_cliente` (`cliente_id`),
  CONSTRAINT `fk_reg_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: pagos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `pagos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `registro_id` INT UNSIGNED NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `metodo` ENUM('efectivo','tarjeta') NOT NULL DEFAULT 'efectivo',
  `fecha` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `fk_pago_registro` (`registro_id`),
  CONSTRAINT `fk_pago_registro` FOREIGN KEY (`registro_id`) REFERENCES `registros` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- --------------------------------------------------------
-- Tabla: gastos
-- --------------------------------------------------------
CREATE TABLE IF NOT EXISTS `gastos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `descripcion` VARCHAR(255) NOT NULL,
  `monto` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `fecha` DATE NOT NULL,
  `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DATOS DE EJEMPLO: 25 habitaciones
-- ============================================================
INSERT INTO `habitaciones` (`numero`, `tipo`, `piso`, `estado`, `precio_base`) VALUES
('101','Simple',1,'libre',80.00),
('102','Simple',1,'libre',80.00),
('103','Doble',1,'libre',120.00),
('104','Doble',1,'libre',120.00),
('105','Suite',1,'libre',200.00),
('201','Simple',2,'libre',80.00),
('202','Simple',2,'libre',80.00),
('203','Doble',2,'libre',120.00),
('204','Doble',2,'libre',120.00),
('205','Suite',2,'libre',200.00),
('206','Simple',2,'libre',80.00),
('207','Doble',2,'libre',120.00),
('208','Suite',2,'libre',200.00),
('209','Simple',2,'libre',80.00),
('210','Doble',2,'libre',120.00),
('301','Simple',3,'libre',80.00),
('302','Simple',3,'libre',80.00),
('303','Doble',3,'libre',120.00),
('304','Doble',3,'libre',120.00),
('305','Suite',3,'libre',200.00),
('306','Simple',3,'libre',80.00),
('307','Doble',3,'libre',120.00),
('308','Suite',3,'libre',200.00),
('309','Simple',3,'libre',80.00),
('310','Doble',3,'libre',120.00);
