-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.15.0.7171
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;


-- Volcando estructura de base de datos para hotel_db
CREATE DATABASE IF NOT EXISTS `hotel_db` /*!40100 DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci */;
USE `hotel_db`;

-- Volcando estructura para tabla hotel_db.anticipos
CREATE TABLE IF NOT EXISTS `anticipos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `moneda` enum('PEN','USD','CLP') DEFAULT 'PEN',
  `monto_pen` decimal(10,2) DEFAULT NULL,
  `tc_aplicado` decimal(10,4) DEFAULT NULL,
  `tipo_pago` varchar(50) NOT NULL,
  `recibo` varchar(50) DEFAULT NULL,
  `fecha` date NOT NULL,
  `aplicado` tinyint(1) DEFAULT 0,
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stay` (`stay_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `anticipos_ibfk_1` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anticipos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `usuario_nombre` varchar(100) DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `idx_usuario` (`usuario_id`),
  KEY `idx_modulo` (`modulo`)
) ENGINE=InnoDB AUTO_INCREMENT=137 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.caja_chica
CREATE TABLE IF NOT EXISTS `caja_chica` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `saldo_inicial` decimal(12,2) DEFAULT 0.00,
  `saldo_final` decimal(12,2) DEFAULT NULL,
  `fecha_apertura` date NOT NULL,
  `fecha_cierre` date DEFAULT NULL,
  `estado` enum('abierta','cerrada') DEFAULT 'abierta',
  `usuario_apertura` int(10) unsigned NOT NULL,
  `usuario_cierre` int(10) unsigned DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  KEY `usuario_apertura` (`usuario_apertura`),
  KEY `usuario_cierre` (`usuario_cierre`),
  CONSTRAINT `caja_chica_ibfk_1` FOREIGN KEY (`usuario_apertura`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `caja_chica_ibfk_2` FOREIGN KEY (`usuario_cierre`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.caja_chica_movimientos
CREATE TABLE IF NOT EXISTS `caja_chica_movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `caja_id` int(10) unsigned NOT NULL,
  `tipo` enum('ingreso','egreso') NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `rubro` varchar(100) NOT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `fecha` date NOT NULL,
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `flujo_movimiento_id` int(10) unsigned DEFAULT NULL,
  `anulado` tinyint(1) DEFAULT 0,
  `motivo_anulacion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_caja` (`caja_id`),
  KEY `idx_fecha` (`fecha`),
  KEY `categoria_id` (`categoria_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `caja_chica` (`id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(150) NOT NULL,
  `dni` varchar(20) DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `tipo_cliente` enum('Normal','Restaurante','Punto de Venta') NOT NULL DEFAULT 'Normal',
  `ruc` varchar(11) DEFAULT NULL,
  `razon_social` varchar(200) DEFAULT NULL,
  `departamento` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_dni` (`dni`),
  UNIQUE KEY `uk_ruc` (`ruc`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parametro` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parametro` (`parametro`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.consumos_hab
CREATE TABLE IF NOT EXISTS `consumos_hab` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned DEFAULT NULL,
  `descripcion` varchar(150) NOT NULL,
  `cantidad` tinyint(3) unsigned DEFAULT 1,
  `precio_unit` decimal(10,2) NOT NULL,
  `total` decimal(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unit`) STORED,
  `cobrado` tinyint(1) DEFAULT 0,
  `fecha` date NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stay` (`stay_id`),
  KEY `idx_cobrado` (`cobrado`),
  KEY `producto_id` (`producto_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `consumos_hab_ibfk_1` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `consumos_hab_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`) ON DELETE SET NULL,
  CONSTRAINT `consumos_hab_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.desayunos
CREATE TABLE IF NOT EXISTS `desayunos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `pax_calculado` int(11) DEFAULT 0,
  `pax_ajustado` int(11) DEFAULT 0,
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha` (`fecha`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `desayunos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.desayunos_detalle
CREATE TABLE IF NOT EXISTS `desayunos_detalle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `desayuno_id` int(10) unsigned NOT NULL,
  `habitacion_id` int(10) unsigned NOT NULL,
  `habitacion` varchar(10) NOT NULL,
  `titular` varchar(100) DEFAULT NULL,
  `pax` int(11) DEFAULT 1,
  `incluye_desayuno` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `desayuno_id` (`desayuno_id`),
  CONSTRAINT `desayunos_detalle_ibfk_1` FOREIGN KEY (`desayuno_id`) REFERENCES `desayunos` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.finanzas_categorias
CREATE TABLE IF NOT EXISTS `finanzas_categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `modulo` enum('C.Chica','Flujo') NOT NULL,
  `tipo` enum('Ingreso','Egreso') NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `orden` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_modulo_tipo` (`modulo`,`tipo`)
) ENGINE=InnoDB AUTO_INCREMENT=28 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.flujo_caja
CREATE TABLE IF NOT EXISTS `flujo_caja` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `turno` enum('MAÑANA','TARDE') NOT NULL,
  `estado` enum('borrador','cerrado','depositado') DEFAULT 'borrador',
  `nota_entrega` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_flujo_turno` (`fecha`,`turno`),
  KEY `idx_fecha` (`fecha`),
  KEY `idx_estado` (`estado`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `flujo_caja_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.flujo_caja_movimientos
CREATE TABLE IF NOT EXISTS `flujo_caja_movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `flujo_id` int(10) unsigned NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `categoria` varchar(100) NOT NULL,
  `tipo` enum('Ingreso','Egreso') NOT NULL,
  `moneda` enum('PEN','USD','CLP') DEFAULT 'PEN',
  `monto` decimal(12,2) NOT NULL,
  `medio_pago` enum('EFECTIVO','NO EFECTIVO') DEFAULT 'EFECTIVO',
  `observacion` text DEFAULT NULL,
  `vuelto` decimal(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_flujo` (`flujo_id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `flujo_caja_movimientos_ibfk_1` FOREIGN KEY (`flujo_id`) REFERENCES `flujo_caja` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flujo_caja_movimientos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=50 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.flujo_caja_responsables
CREATE TABLE IF NOT EXISTS `flujo_caja_responsables` (
  `flujo_id` int(10) unsigned NOT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `rol_turno` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`flujo_id`,`usuario_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `flujo_caja_responsables_ibfk_1` FOREIGN KEY (`flujo_id`) REFERENCES `flujo_caja` (`id`) ON DELETE CASCADE,
  CONSTRAINT `flujo_caja_responsables_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.gastos_yape
CREATE TABLE IF NOT EXISTS `gastos_yape` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `turno` enum('MAÑANA','TARDE') NOT NULL,
  `yape_recibido` decimal(10,2) DEFAULT 0.00,
  `total_gastado` decimal(10,2) DEFAULT 0.00,
  `vuelto` decimal(10,2) DEFAULT 0.00,
  `observacion` text DEFAULT NULL,
  `estado` enum('borrador','cerrado') DEFAULT 'borrador',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_yape` (`fecha`,`turno`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `gastos_yape_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.gastos_yape_detalle
CREATE TABLE IF NOT EXISTS `gastos_yape_detalle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gasto_yape_id` int(10) unsigned NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `rubro` varchar(100) NOT NULL,
  `monto` decimal(10,2) NOT NULL,
  `observacion` text DEFAULT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_yape` (`gasto_yape_id`),
  KEY `categoria_id` (`categoria_id`),
  CONSTRAINT `gastos_yape_detalle_ibfk_1` FOREIGN KEY (`gasto_yape_id`) REFERENCES `gastos_yape` (`id`) ON DELETE CASCADE,
  CONSTRAINT `gastos_yape_detalle_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.habitaciones
CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `tipo` varchar(60) NOT NULL DEFAULT 'Simple',
  `piso` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `estado` enum('libre','ocupado','reservado','limpieza','mantenimiento') NOT NULL DEFAULT 'libre',
  `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`numero`),
  KEY `idx_estado` (`estado`),
  KEY `idx_piso` (`piso`)
) ENGINE=InnoDB AUTO_INCREMENT=57 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.
-- Volcando estructura para tabla hotel_db.inventario_movimientos
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id`           INT(11)       NOT NULL AUTO_INCREMENT,
  `producto_id`  INT(11)       NOT NULL,
  `tipo`         ENUM('VENTA','RECARGA','CONSUMO_INTERNO','AJUSTE') NOT NULL,
  `cantidad`     INT(11)       NOT NULL,
  `stock_antes`  INT(11)       NOT NULL DEFAULT 0,
  `stock_despues`INT(11)       NOT NULL DEFAULT 0,
  `referencia`   VARCHAR(150)  NULL DEFAULT NULL COMMENT 'Ej: HAB 201 - Juanpa / Dueño Mendoza',
  `usuario_id`   INT(11)       NOT NULL DEFAULT 1,
  `created_at`   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_producto` (`producto_id`),
  INDEX `idx_fecha` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.inventario_productos
CREATE TABLE IF NOT EXISTS `inventario_productos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `nombre` varchar(100) NOT NULL,
  `categoria` varchar(50) DEFAULT NULL,
  `refrigeradora` tinyint(3) unsigned DEFAULT 1,
  `precio_venta` decimal(10,2) NOT NULL DEFAULT 0.00,
  `stock_actual` int(11) DEFAULT 0,
  `activo` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.limpieza_registros
CREATE TABLE IF NOT EXISTS `limpieza_registros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `habitacion_id` int(10) unsigned NOT NULL,
  `habitacion` varchar(10) NOT NULL,
  `tipo_limpieza` enum('estimacion','estadía','salida','programada') NOT NULL,
  `prioridad` enum('baja','normal','alta') DEFAULT 'normal',
  `estado` enum('pendiente','en proceso','lista') DEFAULT 'pendiente',
  `hora_inicio` datetime DEFAULT NULL,
  `hora_fin` datetime DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_hab` (`fecha`,`habitacion_id`),
  KEY `habitacion_id` (`habitacion_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `limpieza_registros_ibfk_1` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `limpieza_registros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.registros
CREATE TABLE IF NOT EXISTS `registros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `habitacion_id` int(10) unsigned NOT NULL,
  `cliente_id` int(10) unsigned NOT NULL,
  `fecha_ingreso` datetime NOT NULL,
  `fecha_salida` datetime DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL DEFAULT 0.00,
  `estado` enum('activo','finalizado') NOT NULL DEFAULT 'activo',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_reg_habitacion` (`habitacion_id`),
  KEY `fk_reg_cliente` (`cliente_id`),
  CONSTRAINT `fk_reg_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON UPDATE CASCADE,
  CONSTRAINT `fk_reg_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`) ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.reporte_alex
CREATE TABLE IF NOT EXISTS `reporte_alex` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `turno` enum('MAÑANA','TARDE') NOT NULL,
  `pesos` decimal(10,2) DEFAULT 0.00,
  `dolares` decimal(10,2) DEFAULT 0.00,
  `soles` decimal(10,2) DEFAULT 0.00,
  `observacion` text DEFAULT NULL,
  `estado` enum('borrador','cerrado') DEFAULT 'borrador',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_alex` (`fecha`,`turno`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reporte_alex_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.reporte_mendoza
CREATE TABLE IF NOT EXISTS `reporte_mendoza` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `turno` enum('MAÑANA','TARDE') NOT NULL,
  `estado` enum('abierto','cerrado') DEFAULT 'abierto',
  `nota` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `fecha_cierre` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_mendoza` (`fecha`,`turno`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reporte_mendoza_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.reporte_mendoza_habs
CREATE TABLE IF NOT EXISTS `reporte_mendoza_habs` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reporte_id` int(10) unsigned NOT NULL,
  `hab` varchar(20) NOT NULL,
  `noches` varchar(50) NOT NULL,
  `check_in` datetime DEFAULT NULL,
  `check_out` datetime DEFAULT NULL,
  `tipo_pago` enum('POS','EFECTIVO','YAPE','SIN MOVIMIENTO') DEFAULT 'EFECTIVO',
  `monto` decimal(10,2) DEFAULT 0.00,
  `moneda` enum('SOLES','DOLARES','PESOS') DEFAULT 'SOLES',
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reporte` (`reporte_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reporte_mendoza_habs_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reporte_mendoza` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reporte_mendoza_habs_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.reporte_mendoza_otros
CREATE TABLE IF NOT EXISTS `reporte_mendoza_otros` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `reporte_id` int(10) unsigned NOT NULL,
  `tipo` enum('consumo','egreso') NOT NULL,
  `descripcion` text NOT NULL,
  `tipo_pago` enum('POS','EFECTIVO','YAPE') DEFAULT 'EFECTIVO',
  `monto` decimal(10,2) DEFAULT 0.00,
  `moneda` enum('SOLES','DOLARES','PESOS') DEFAULT 'SOLES',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_reporte` (`reporte_id`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `reporte_mendoza_otros_ibfk_1` FOREIGN KEY (`reporte_id`) REFERENCES `reporte_mendoza` (`id`) ON DELETE CASCADE,
  CONSTRAINT `reporte_mendoza_otros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_pax
CREATE TABLE IF NOT EXISTS `rooming_pax` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned NOT NULL,
  `nombre_completo` varchar(255) NOT NULL,
  `documento_tipo` varchar(20) DEFAULT 'DNI',
  `documento_num` varchar(30) NOT NULL,
  `nacionalidad` varchar(50) DEFAULT 'Peruana',
  `ciudad` varchar(80) DEFAULT NULL,
  `es_titular` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_stay` (`stay_id`),
  KEY `idx_documento` (`documento_num`),
  CONSTRAINT `rooming_pax_ibfk_1` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=39 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_stays
CREATE TABLE IF NOT EXISTS `rooming_stays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `operador` varchar(50) NOT NULL,
  `fecha_registro` date NOT NULL,
  `fecha_checkout` date DEFAULT NULL,
  `hora_checkin` time DEFAULT NULL,
  `medio_reserva` varchar(50) NOT NULL,
  `habitacion_id` int(10) unsigned NOT NULL,
  `tipo_hab_declarado` varchar(60) NOT NULL,
  `noches` tinyint(3) unsigned DEFAULT 1,
  `pax_total` tinyint(3) unsigned DEFAULT 1,
  `total_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `moneda_pago` enum('PEN','USD','CLP') NOT NULL DEFAULT 'PEN',
  `monto_original` decimal(10,2) DEFAULT NULL,
  `tc_aplicado` decimal(10,4) DEFAULT NULL,
  `recargo_tarjeta` decimal(10,2) DEFAULT 0.00,
  `metodo_pago` varchar(50) NOT NULL,
  `tipo_comprobante` varchar(50) NOT NULL,
  `num_comprobante` varchar(50) DEFAULT NULL,
  `ruc_factura` varchar(20) DEFAULT NULL,
  `cobrador` varchar(50) NOT NULL,
  `procedencia` varchar(100) DEFAULT NULL,
  `carro` varchar(20) DEFAULT NULL,
  `estado` enum('activo','finalizado','reservado','late_checkout','cancelado') NOT NULL DEFAULT 'activo',
  `estado_pago` enum('pendiente','adelanto','parcial','pagado') DEFAULT 'pendiente',
  `total_cobrado` decimal(10,2) DEFAULT 0.00,
  `checkin_realizado` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_fecha` (`fecha_registro`),
  KEY `idx_estado` (`estado`),
  KEY `idx_estado_pago` (`estado_pago`),
  KEY `usuario_id` (`usuario_id`),
  CONSTRAINT `rooming_stays_ibfk_1` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `rooming_stays_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=31 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.tipos_cambio
CREATE TABLE IF NOT EXISTS `tipos_cambio` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `moneda_origen` varchar(10) NOT NULL,
  `moneda_destino` varchar(10) NOT NULL DEFAULT 'PEN',
  `factor` decimal(10,4) NOT NULL,
  `fecha` date NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tc_fecha` (`moneda_origen`,`moneda_destino`,`fecha`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.usuarios
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','supervisor','cajera','limpieza') NOT NULL DEFAULT 'cajera',
  `nombre` varchar(100) NOT NULL,
  `estado` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


CREATE TABLE IF NOT EXISTS `rooming_consumos` (
  `id`              INT(11)        NOT NULL AUTO_INCREMENT,
  `stay_id`         INT(11)        NOT NULL,
  `producto_id`     INT(11)        NOT NULL,
  `nombre_producto` VARCHAR(100)   NOT NULL,
  `cantidad`        INT(11)        NOT NULL DEFAULT 1,
  `precio_unitario` DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `total`           DECIMAL(10,2)  NOT NULL DEFAULT 0.00,
  `metodo_pago`     VARCHAR(50)    NULL DEFAULT NULL,
  `pagado`          TINYINT(1)     NOT NULL DEFAULT 0,
  `usuario_id`      INT(11)        NOT NULL DEFAULT 1,
  `created_at`      TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  INDEX `idx_stay` (`stay_id`),
  INDEX `idx_producto` (`producto_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `usuario_permisos` (
  `id`          INT(11)     NOT NULL AUTO_INCREMENT,
  `usuario_id`  INT(11)     NOT NULL,
  `modulo`      VARCHAR(50) NOT NULL,
  `activo`      TINYINT(1)  NOT NULL DEFAULT 1,
  `updated_at`  TIMESTAMP   DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario_modulo` (`usuario_id`, `modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;


-- Registros

INSERT IGNORE INTO `usuarios` (`usuario`, `password`, `rol`, `nombre`) VALUES
('admin',   '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'admin',      'Administrador'),
('kari', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'limpieza',     'Kari'),
('cajera2', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera',     'Jessica'),
('cajera3', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera',     'Roy'),
('cajera4', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'supervisor', 'Alex');

INSERT INTO `habitaciones` (`numero`, `tipo`, `piso`, `estado`, `precio_base`) VALUES
('201', 'TRIPLE FAMILIAR',      2, 'libre', 200.00),
('202', 'EJECUTIVA SUPERIOR',   2, 'libre', 150.00),
('203', 'DOBLE',                2, 'libre', 180.00),
('204', 'EJECUTIVA SUPERIOR',   2, 'libre', 250.00),
('205', 'PLATINIUM SUITE',      2, 'libre', 300.00),

('301', 'TRIPLE',               3, 'libre', 500.00),
('302', 'EJECUTIVA SUPERIOR',   3, 'libre', 0.00),
('303', 'DOBLE',                3, 'libre', 0.00),
('304', 'MATRIMONIAL SUPERIOR', 3, 'libre', 0.00),  
('305', 'PLATINIUM SUITE',      3, 'libre', 0.00),

('401', 'TRIPLE',               4, 'libre', 0.00),
('402', 'EJECUTIVA SUPERIOR',   4, 'libre', 0.00),
('403', 'DOBLE',                4, 'libre', 0.00),
('404', 'MATRIMONIAL SUPERIOR', 4, 'libre', 0.00),  
('405', 'PLATINIUM SUITE',      4, 'libre', 0.00),

('501', 'TRIPLE',               5, 'libre', 0.00),
('502', 'EJECUTIVA SUPERIOR',   5, 'libre', 0.00),
('503', 'DOBLE',                5, 'libre', 0.00),
('504', 'MATRIMONIAL SUPERIOR', 5, 'libre', 0.00),
('505', 'PLATINIUM SUITE',      5, 'libre', 0.00),

('601', 'TRIPLE',               6, 'libre', 0.00),
('602', 'EJECUTIVA SUPERIOR',   6, 'libre', 0.00),
('603', 'DOBLE',                6, 'libre', 0.00),
('604', 'MATRIMONIAL SUPERIOR', 6, 'libre', 0.00),  
('605', 'PLATINIUM SUITE',      6, 'libre', 0.00);


INSERT INTO `finanzas_categorias` (`modulo`, `tipo`, `nombre`, `orden`) VALUES
-- Ingresos del Flujo de Caja
('Flujo', 'Ingreso', 'DEPOS/TRANS.',     1),
('Flujo', 'Ingreso', 'YAPE O PLIN',      2),
('Flujo', 'Ingreso', 'POS DOLARES',      3),
('Flujo', 'Ingreso', 'POS SOLES',        4),
('Flujo', 'Ingreso', 'PESOS EFECTIVO',   5),
('Flujo', 'Ingreso', 'DOLARES EFECTIVO', 6),
('Flujo', 'Ingreso', 'SOLES EFECTIVO',   7),
-- Egresos del Flujo de Caja
('Flujo', 'Egreso',  'MERCADO',              1),
('Flujo', 'Egreso',  'MOVILIDAD',            2),
('Flujo', 'Egreso',  'CAFETERÍA VEA-GENOVESA',3),
('Flujo', 'Egreso',  'LAVANDERÍA',           4),
('Flujo', 'Egreso',  'ÚTILES DE ESCRITORIO', 5),
('Flujo', 'Egreso',  'RECEPCIÓN C.CH.',      6),
('Flujo', 'Egreso',  'SERV. REPUESTOS',      7),
('Flujo', 'Egreso',  'PAGO A PERSONAL',      8),
('Flujo', 'Egreso',  'OTROS',                9),
-- Rubros de Caja Chica
('C.Chica', 'Egreso', 'PANADERÍA',       1),
('C.Chica', 'Egreso', 'TIENDA',          2),
('C.Chica', 'Egreso', 'MOVILIDAD',       3),
('C.Chica', 'Egreso', 'MERCADO',         4),
('C.Chica', 'Egreso', 'FERRETERÍA',      5),
('C.Chica', 'Egreso', 'FARMACIA',        6),
('C.Chica', 'Egreso', 'LAVANDERÍA',      7),
('C.Chica', 'Egreso', 'PUBLICIDAD',      8),
('C.Chica', 'Egreso', 'VUELTO',          9),
('C.Chica', 'Egreso', 'OTROS',          10),
('C.Chica', 'Ingreso','REPOSICIÓN CAJA', 1);

-- =======================


INSERT INTO `inventario_productos` (`nombre`, `categoria`, `refrigeradora`, `precio_venta`, `stock_actual`) VALUES
('COCA COLA',    'BEBIDA', 1, 7.00,10),
('INCA COLA',    'BEBIDA', 1, 7.00,10),
('AGUA SAN MATEO','BEBIDA',1, 5.00,10),
('AGUA SAN LUIS','BEBIDA', 1, 5.00,10),
('CERV. CORONA', 'BEBIDA', 1, 10.00,10),
('CERV. CUZQUEÑA','BEBIDA',1, 10.00,10),
('VINO ROJO',    'VINO',   2, 35.00,10),
('VINO MORADO',  'VINO',   2, 35.00,10),
('VINO AZUL',    'VINO',   2, 35.00,10);


-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
