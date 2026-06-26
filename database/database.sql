-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Versión del servidor:         10.4.32-MariaDB - mariadb.org binary distribution
-- SO del servidor:              Win64
-- HeidiSQL Versión:             12.17.0.7270
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
  KEY `anticipos_ibfk_1` (`stay_id`),
  KEY `anticipos_ibfk_2` (`usuario_id`),
  CONSTRAINT `anticipos_ibfk_1` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `anticipos_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.auditoria
CREATE TABLE IF NOT EXISTS `auditoria` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `usuario_id` int(10) unsigned DEFAULT NULL,
  `accion` varchar(100) NOT NULL,
  `modulo` varchar(50) NOT NULL,
  `detalle` text DEFAULT NULL,
  `fecha_hora` timestamp NOT NULL DEFAULT current_timestamp(),
  `ip` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha` (`fecha_hora`),
  KEY `fk_auditoria_usuario` (`usuario_id`),
  CONSTRAINT `fk_auditoria_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=44 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `caja_chica_ibfk_1` (`usuario_apertura`),
  KEY `caja_chica_ibfk_2` (`usuario_cierre`),
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
  `anulado` tinyint(1) DEFAULT 0,
  `motivo_anulacion` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `caja_chica_movimientos_ibfk_1` (`caja_id`),
  KEY `caja_chica_movimientos_ibfk_2` (`categoria_id`),
  KEY `caja_chica_movimientos_ibfk_3` (`usuario_id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_1` FOREIGN KEY (`caja_id`) REFERENCES `caja_chica` (`id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_2` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`),
  CONSTRAINT `caja_chica_movimientos_ibfk_3` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.clientes
CREATE TABLE IF NOT EXISTS `clientes` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `tipo_cliente` enum('NATURAL','JURIDICO') NOT NULL DEFAULT 'NATURAL',
  `documento_tipo` enum('DNI','CE','PASAPORTE','RUC') NOT NULL DEFAULT 'DNI',
  `documento_num` varchar(30) NOT NULL,
  `ruc` varchar(20) DEFAULT NULL,
  `empresa` varchar(255) DEFAULT NULL,
  `nombre_razon_social` varchar(255) NOT NULL COMMENT 'Nombre completo o Razón Social de la Empresa',
  `celular` varchar(20) DEFAULT NULL,
  `email` varchar(150) DEFAULT NULL,
  `nacionalidad` varchar(50) DEFAULT 'Peruana',
  `pais_origen` varchar(60) DEFAULT NULL,
  `ciudad` varchar(80) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_documento` (`documento_tipo`,`documento_num`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.configuracion
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `parametro` varchar(50) NOT NULL,
  `valor` varchar(255) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `creado_en` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parametro` (`parametro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `desayunos_ibfk_1` (`usuario_id`),
  CONSTRAINT `desayunos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.desayunos_detalle
CREATE TABLE IF NOT EXISTS `desayunos_detalle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `desayuno_id` int(10) unsigned NOT NULL,
  `stay_id` int(10) unsigned NOT NULL COMMENT 'Relación directa con la estadía activa',
  `pax` int(11) DEFAULT 1,
  `incluye_desayuno` tinyint(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `desayunos_detalle_ibfk_1` (`desayuno_id`),
  KEY `fk_desayuno_stay` (`stay_id`),
  CONSTRAINT `desayunos_detalle_ibfk_1` FOREIGN KEY (`desayuno_id`) REFERENCES `desayunos` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_desayuno_stay` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  KEY `flujo_caja_ibfk_1` (`usuario_id`),
  CONSTRAINT `flujo_caja_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.flujo_caja_movimientos
CREATE TABLE IF NOT EXISTS `flujo_caja_movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `flujo_id` int(10) unsigned NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `stay_id` int(10) unsigned DEFAULT NULL COMMENT 'Relación directa opcional con la habitación',
  `tipo` enum('Ingreso','Egreso') NOT NULL,
  `monto` decimal(12,2) NOT NULL,
  `moneda` enum('PEN','USD','CLP') DEFAULT 'PEN',
  `medio_pago` enum('EFECTIVO','YAPE_CAJA','PLIN','POS_SOLES','POS_DOLARES','TRANSFERENCIA') NOT NULL DEFAULT 'EFECTIVO',
  `documento` varchar(100) DEFAULT NULL COMMENT 'Número de operación o comprobante de gasto',
  `observacion` text DEFAULT NULL,
  `vuelto` decimal(12,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_movimientos_flujo` (`flujo_id`),
  KEY `fk_movimientos_categoria` (`categoria_id`),
  KEY `fk_movimientos_stay` (`stay_id`),
  CONSTRAINT `fk_movimientos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`),
  CONSTRAINT `fk_movimientos_flujo` FOREIGN KEY (`flujo_id`) REFERENCES `flujo_caja` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_movimientos_stay` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.gastos_yape
CREATE TABLE IF NOT EXISTS `gastos_yape` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `fecha` date NOT NULL,
  `turno` enum('MAÑANA','TARDE') NOT NULL,
  `yape_recibido` decimal(12,2) NOT NULL DEFAULT 0.00,
  `total_gastado` decimal(12,2) NOT NULL DEFAULT 0.00,
  `vuelto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacion` text DEFAULT NULL,
  `estado` enum('borrador','cerrado') NOT NULL DEFAULT 'borrador',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_gastos_yape_usuario` (`usuario_id`),
  CONSTRAINT `fk_gastos_yape_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.gastos_yape_detalle
CREATE TABLE IF NOT EXISTS `gastos_yape_detalle` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `gasto_yape_id` int(10) unsigned NOT NULL,
  `categoria_id` int(11) DEFAULT NULL,
  `rubro` varchar(100) NOT NULL,
  `monto` decimal(12,2) NOT NULL DEFAULT 0.00,
  `observacion` text DEFAULT NULL,
  `documento` varchar(100) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_yape_detalle_cabecera` (`gasto_yape_id`),
  KEY `fk_yape_detalle_categoria` (`categoria_id`),
  CONSTRAINT `fk_yape_detalle_cabecera` FOREIGN KEY (`gasto_yape_id`) REFERENCES `gastos_yape` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_yape_detalle_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.habitaciones
CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `numero` varchar(10) NOT NULL,
  `tipo` varchar(60) NOT NULL DEFAULT 'Simple',
  `piso` tinyint(3) unsigned NOT NULL DEFAULT 1,
  `estado` enum('libre','ocupado','reservado','limpieza','sucio','mantenimiento') NOT NULL DEFAULT 'libre',
  `precio_base` decimal(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` text DEFAULT NULL,
  `activa` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`numero`),
  KEY `idx_estado` (`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=26 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.inventario_movimientos
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `producto_id` int(10) unsigned NOT NULL,
  `tipo` enum('VENTA','RECARGA','CONSUMO_INTERNO','AJUSTE') NOT NULL,
  `cantidad` int(11) NOT NULL,
  `stock_antes` int(11) NOT NULL DEFAULT 0,
  `stock_despues` int(11) NOT NULL DEFAULT 0,
  `stay_id` int(10) unsigned DEFAULT NULL COMMENT 'Si es venta a cuarto, enlaza al Rooming',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_inv_producto` (`producto_id`),
  KEY `fk_inv_stay` (`stay_id`),
  KEY `fk_inv_usuario` (`usuario_id`),
  CONSTRAINT `fk_inv_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`),
  CONSTRAINT `fk_inv_stay` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inv_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  `tipo_limpieza` enum('estimacion','reposo','salida','programada') NOT NULL,
  `prioridad` enum('baja','normal','alta') DEFAULT 'normal',
  `estado` enum('pendiente','en proceso','lista') DEFAULT 'pendiente',
  `hora_inicio` datetime DEFAULT NULL,
  `hora_fin` datetime DEFAULT NULL,
  `observacion` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_hab` (`fecha`,`habitacion_id`),
  KEY `limpieza_registros_ibfk_1` (`habitacion_id`),
  KEY `limpieza_registros_ibfk_2` (`usuario_id`),
  CONSTRAINT `limpieza_registros_ibfk_1` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `limpieza_registros_ibfk_2` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_consumos
CREATE TABLE IF NOT EXISTS `rooming_consumos` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned NOT NULL,
  `producto_id` int(10) unsigned NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL DEFAULT 0.00,
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `metodo_pago` varchar(50) DEFAULT NULL,
  `pagado` tinyint(1) NOT NULL DEFAULT 0 COMMENT '0: Cargo a la Hab, 1: Cancelado al instante',
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_consumos_producto` (`producto_id`),
  KEY `fk_consumos_stay` (`stay_id`),
  KEY `fk_consumos_usuario` (`usuario_id`),
  CONSTRAINT `fk_consumos_producto` FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos` (`id`),
  CONSTRAINT `fk_consumos_stay` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_consumos_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_pax
CREATE TABLE IF NOT EXISTS `rooming_pax` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned DEFAULT NULL,
  `cliente_id` int(10) unsigned NOT NULL COMMENT 'ID del huésped (Persona Natural)',
  `es_titular_acompanante` tinyint(1) DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `vip` tinyint(1) DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_stay_cliente` (`stay_id`,`cliente_id`),
  KEY `rooming_pax_ibfk_2` (`cliente_id`),
  CONSTRAINT `rooming_pax_ibfk_1` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `rooming_pax_ibfk_2` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_stays
CREATE TABLE IF NOT EXISTS `rooming_stays` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `operador` varchar(50) NOT NULL,
  `fecha_registro` date NOT NULL COMMENT 'Fecha en que se crea la reserva o ingreso',
  `fecha_checkin_real` datetime DEFAULT NULL COMMENT 'Fecha y hora exacta del Check-In en vivo',
  `fecha_checkout` date DEFAULT NULL COMMENT 'Almacena la fecha de salida real y actual',
  `hora_checkin` varchar(10) DEFAULT '' COMMENT 'Hora de Check-in manual',
  `medio_reserva` varchar(50) NOT NULL,
  `habitacion_id` int(10) unsigned NOT NULL,
  `tipo_hab_declarado` varchar(60) NOT NULL,
  `pax_total` tinyint(3) unsigned DEFAULT 1 COMMENT 'Cantidad de personas previstas',
  `total_pago` decimal(10,2) NOT NULL DEFAULT 0.00,
  `moneda_pago` enum('PEN','USD','CLP') NOT NULL DEFAULT 'PEN',
  `monto_original` decimal(10,2) DEFAULT NULL,
  `tc_aplicado` decimal(10,4) DEFAULT NULL,
  `recargo_tarjeta` decimal(10,2) DEFAULT 0.00,
  `metodo_pago` varchar(50) NOT NULL,
  `tipo_comprobante` enum('BOLETA','FACTURA','TICKET') NOT NULL DEFAULT 'BOLETA',
  `num_comprobante` varchar(50) DEFAULT NULL,
  `cobrador` varchar(50) NOT NULL,
  `procedencia` varchar(100) DEFAULT NULL,
  `carro` varchar(20) DEFAULT NULL,
  `estado` enum('reservado','activo','finalizado','late_checkout','cancelado') NOT NULL DEFAULT 'reservado',
  `estado_pago` enum('pendiente','adelanto','parcial','pagado') DEFAULT 'pendiente',
  `total_cobrado` decimal(10,2) DEFAULT 0.00,
  `total_cobrado_orig` decimal(12,2) NOT NULL DEFAULT 0.00,
  `checkin_realizado` tinyint(1) DEFAULT 0,
  `observaciones` text DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `cliente_titular_id` int(10) unsigned NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_stays_habitacion` (`habitacion_id`),
  KEY `fk_stays_usuario` (`usuario_id`),
  KEY `fk_stays_cliente_titular` (`cliente_titular_id`),
  CONSTRAINT `fk_stays_cliente_titular` FOREIGN KEY (`cliente_titular_id`) REFERENCES `clientes` (`id`),
  CONSTRAINT `fk_stays_habitacion` FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones` (`id`),
  CONSTRAINT `fk_stays_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- La exportación de datos fue deseleccionada.

-- Volcando estructura para tabla hotel_db.rooming_stays_historial_fechas
CREATE TABLE IF NOT EXISTS `rooming_stays_historial_fechas` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `stay_id` int(10) unsigned NOT NULL,
  `fecha_checkout_pasada` date NOT NULL COMMENT 'La fecha que tenía la estadía antes de ser cambiada',
  `motivo` varchar(255) DEFAULT NULL,
  `usuario_id` int(10) unsigned NOT NULL COMMENT 'Usuario que procesó el cambio',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `fk_historial_fechas_stay` (`stay_id`),
  KEY `fk_historial_fechas_user` (`usuario_id`),
  CONSTRAINT `fk_historial_fechas_stay` FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_historial_fechas_user` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
  UNIQUE KEY `uk_tc_fecha` (`moneda_origen`,`moneda_destino`,`fecha`)
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

-- La exportación de datos fue deseleccionada.

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
