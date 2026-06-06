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

-- Volcando datos para la tabla hotel_db.anticipos: ~2 rows (aproximadamente)
INSERT IGNORE INTO `anticipos` (`id`, `stay_id`, `monto`, `moneda`, `monto_pen`, `tc_aplicado`, `tipo_pago`, `recibo`, `fecha`, `aplicado`, `observacion`, `usuario_id`, `created_at`) VALUES
	(1, 1, 300.00, 'PEN', 300.00, 1.0000, 'SOLES EFECTIVO', '', '2026-05-31', 0, NULL, 4, '2026-05-31 17:20:25'),
	(2, 2, 500.00, 'PEN', 500.00, 1.0000, 'SOLES EFECTIVO', '', '2026-05-31', 0, NULL, 4, '2026-05-31 18:55:39');

-- Volcando datos para la tabla hotel_db.auditoria: ~43 rows (aproximadamente)
INSERT IGNORE INTO `auditoria` (`id`, `usuario_id`, `accion`, `modulo`, `detalle`, `fecha_hora`, `ip`) VALUES
	(1, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 17:20:25', '::1'),
	(2, 4, 'REGISTRAR_DESAYUNO', 'COMIDA', 'Consolidó el padrón de desayunos para la fecha: 2026-05-31', '2026-05-31 17:57:57', '::1'),
	(3, 4, 'LIMPIEZA_ESTADO', 'LIMPIEZA', 'Marcó como LIMPIA la Habitación #202', '2026-05-31 18:31:14', '::1'),
	(4, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 18:36:48', '::1'),
	(5, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 18:38:22', '::1'),
	(6, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 18:46:40', '::1'),
	(7, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 18:55:39', '::1'),
	(8, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 18:55:48', '::1'),
	(9, 4, 'REGISTRAR_DESAYUNO', 'COMIDA', 'Consolidó el padrón de desayunos para la fecha: 2026-06-01', '2026-05-31 19:19:59', '::1'),
	(10, 4, 'REGISTRAR_DESAYUNO', 'COMIDA', 'Consolidó el padrón de desayunos para la fecha: 2026-06-01', '2026-05-31 19:20:09', '::1'),
	(11, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 20:11:28', '::1'),
	(12, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-05-31 20:12:17', '::1'),
	(13, 4, 'YAPE_CREADO_DIA', 'FINANZAS', 'Se inicializó el día 2026-05-31 con un monto inicial de 0.', '2026-05-31 20:28:45', '::1'),
	(14, 4, 'CAJA_CHICA_ABIERTA', 'FINANZAS', 'Ciclo de C.Chica abierto: DDD con S/100.', '2026-05-31 20:56:43', '::1'),
	(15, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-01 15:28:30', '::1'),
	(16, 4, 'ACTUALIZAR_USUARIO', 'USUARIOS', '{"mensaje":"Actualizó datos del usuario: admin","cambios":null}', '2026-06-01 16:58:12', '::1'),
	(17, 4, 'ACTUALIZAR_USUARIO', 'USUARIOS', '{"mensaje":"Actualizó datos del usuario: jessica","cambios":{"Estado":{"antes":"Activo","despues":"Inactivo"}}}', '2026-06-01 16:58:17', '::1'),
	(18, 4, 'ACTUALIZAR_USUARIO', 'USUARIOS', '{"mensaje":"Actualizó datos del usuario: jessica","cambios":{"Estado":{"antes":"Inactivo","despues":"Activo"}}}', '2026-06-01 16:58:20', '::1'),
	(19, 4, 'RECARGA_STOCK', 'INVENTARIO', '{"mensaje":"Recargó stock de: AGUA SAN LUIS","cambios":{"Stock":{"antes":10,"despues":20}}}', '2026-06-01 18:01:11', '::1'),
	(20, 4, 'ACTUALIZAR_PRODUCTO', 'INVENTARIO', '{"mensaje":"Actualizó producto: AGUA SAN LUIS (1 campos modificados)","cambios":{"Stock":{"antes":20,"despues":30}}}', '2026-06-01 18:08:07', '::1'),
	(21, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-02 17:16:29', '::1'),
	(22, 4, 'YAPE_CREADO_DIA', 'FINANZAS', 'Se inicializó el día 2026-06-02 con un monto inicial de 0.', '2026-06-02 17:21:47', '::1'),
	(23, 4, 'LIMPIEZA_ESTADO', 'LIMPIEZA', 'Marcó como LIMPIA la Habitación #202', '2026-06-02 17:33:38', '::1'),
	(24, 4, 'LIMPIEZA_ESTADO', 'LIMPIEZA', 'Marcó como LIMPIA la Habitación #205', '2026-06-02 17:33:38', '::1'),
	(25, 4, 'GUARDAR_ROOMING_V2', 'ROOMING', 'Guardó/actualizó registros en la grilla plana Rooming V2', '2026-06-02 17:52:39', '::1'),
	(26, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-03 17:26:38', '::1'),
	(27, 4, 'GASTO_CAJA_CHICA', 'FINANZAS', '{"mensaje":"Registro un GASTO en Caja Chica","cambios":{"Documento":{"antes":"-","despues":"DD"},"Monto":{"antes":"S\\/ 0.00","despues":"S\\/ 50.00"},"Obs":{"antes":"-","despues":"PAN"}}}', '2026-06-03 17:34:22', '::1'),
	(28, 4, 'CAJA_CHICA_CERRADA', 'FINANZAS', 'Caja Chica 1 cerrada. Saldo Final: 50', '2026-06-03 17:34:39', '::1'),
	(29, 4, 'CIERRE_SESION', 'SEGURIDAD', 'El usuario cerró su sesión', '2026-06-03 19:39:20', '::1'),
	(30, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-03 19:39:28', '::1'),
	(31, 4, 'CIERRE_SESION', 'SEGURIDAD', 'El usuario cerró su sesión', '2026-06-03 19:39:44', '::1'),
	(32, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-03 19:39:50', '::1'),
	(33, 4, 'CIERRE_SESION', 'SEGURIDAD', 'El usuario cerró su sesión', '2026-06-03 19:44:13', '::1'),
	(34, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-03 19:44:18', '::1'),
	(35, 4, 'CIERRE_SESION', 'SEGURIDAD', 'El usuario cerró su sesión', '2026-06-03 19:46:58', '::1'),
	(36, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-03 19:47:04', '::1'),
	(37, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-05 19:06:15', '::1'),
	(38, 4, 'NUEVA_RESERVA', 'RESERVAS', 'Creó RESERVA RÁPIDA para: juan (Hab #1)', '2026-06-05 19:38:50', '::1'),
	(39, 4, 'CHECKIN_RESERVA', 'RESERVAS', 'Activó llegada de Huésped (Check-in desde Reservas)', '2026-06-05 19:39:00', '::1'),
	(40, 4, 'NUEVA_RESERVA', 'RESERVAS', 'Creó RESERVA RÁPIDA para: ja (Hab #4)', '2026-06-05 20:45:14', '::1'),
	(41, 4, 'NUEVA_RESERVA', 'RESERVAS', 'Creó RESERVA RÁPIDA para: calor ejm (Hab #7)', '2026-06-05 21:25:20', '::1'),
	(42, 4, 'CHECKIN_RESERVA', 'RESERVAS', 'Activó llegada de Huésped (Check-in desde Reservas)', '2026-06-05 21:25:39', '::1'),
	(43, 4, 'INICIO_SESION', 'SEGURIDAD', 'Inicio de sesión exitoso (Trabajador)', '2026-06-06 17:20:44', '::1');

-- Volcando datos para la tabla hotel_db.caja_chica: ~2 rows (aproximadamente)
INSERT IGNORE INTO `caja_chica` (`id`, `nombre`, `saldo_inicial`, `saldo_final`, `fecha_apertura`, `fecha_cierre`, `estado`, `usuario_apertura`, `usuario_cierre`, `created_at`) VALUES
	(1, 'DDD', 100.00, 50.00, '2026-05-31', '2026-06-03', 'cerrada', 4, 4, '2026-05-31 20:56:43'),
	(2, 'FONDO FIJO S/ 100 - 03/06/2026', 100.00, NULL, '2026-06-03', NULL, 'abierta', 4, NULL, '2026-06-03 17:34:39');

-- Volcando datos para la tabla hotel_db.caja_chica_movimientos: ~1 rows (aproximadamente)
INSERT IGNORE INTO `caja_chica_movimientos` (`id`, `caja_id`, `tipo`, `monto`, `categoria_id`, `rubro`, `documento`, `fecha`, `observacion`, `usuario_id`, `anulado`, `motivo_anulacion`, `created_at`) VALUES
	(1, 1, 'egreso', 50.00, NULL, 'DD', 'DD', '2026-06-03', 'PAN', 4, 0, NULL, '2026-06-03 17:34:22');

-- Volcando datos para la tabla hotel_db.clientes: ~6 rows (aproximadamente)
INSERT IGNORE INTO `clientes` (`id`, `tipo_cliente`, `documento_tipo`, `documento_num`, `ruc`, `empresa`, `nombre_razon_social`, `celular`, `email`, `nacionalidad`, `pais_origen`, `ciudad`, `created_at`) VALUES
	(1, 'NATURAL', 'DNI', '76032957', NULL, NULL, 'Andree Flores Melendez', NULL, NULL, 'Peruana', NULL, 'Tacna', '2026-05-31 17:20:25'),
	(2, 'NATURAL', 'DNI', '76329578', '', '', 'Jaime Flores', '957084266', 'jf2017057494@virtual.upt.pe', 'Peruana', NULL, 'Lima', '2026-05-31 18:55:39'),
	(3, 'NATURAL', 'DNI', '', NULL, NULL, 'juan', NULL, NULL, 'Peruana', NULL, NULL, '2026-06-05 19:38:50'),
	(7, 'NATURAL', 'DNI', 'R_6a2334f311fca', NULL, NULL, 'Test', NULL, NULL, 'Peruana', NULL, NULL, '2026-06-05 20:43:31'),
	(8, 'NATURAL', 'DNI', 'R_6a23355a62bc4', NULL, NULL, 'ja', NULL, NULL, 'Peruana', NULL, NULL, '2026-06-05 20:45:14'),
	(9, 'NATURAL', 'DNI', 'R_6a233ec0b6a33', NULL, NULL, 'calor ejm', NULL, NULL, 'Peruana', NULL, NULL, '2026-06-05 21:25:20');

-- Volcando datos para la tabla hotel_db.configuracion: ~0 rows (aproximadamente)

-- Volcando datos para la tabla hotel_db.desayunos: ~2 rows (aproximadamente)
INSERT IGNORE INTO `desayunos` (`id`, `fecha`, `pax_calculado`, `pax_ajustado`, `observacion`, `usuario_id`, `created_at`) VALUES
	(1, '2026-05-31', 1, 1, '', 4, '2026-05-31 17:57:57'),
	(2, '2026-06-01', 2, 2, '', 4, '2026-05-31 19:19:59');

-- Volcando datos para la tabla hotel_db.desayunos_detalle: ~3 rows (aproximadamente)
INSERT IGNORE INTO `desayunos_detalle` (`id`, `desayuno_id`, `stay_id`, `pax`, `incluye_desayuno`) VALUES
	(1, 1, 1, 1, 1),
	(4, 2, 1, 1, 1),
	(5, 2, 2, 1, 1);

-- Volcando datos para la tabla hotel_db.finanzas_categorias: ~28 rows (aproximadamente)
INSERT IGNORE INTO `finanzas_categorias` (`id`, `modulo`, `tipo`, `nombre`, `orden`, `activo`) VALUES
	(1, 'Flujo', 'Ingreso', 'DEPOS/TRANS.', 1, 1),
	(2, 'Flujo', 'Ingreso', 'YAPE O PLIN', 2, 1),
	(3, 'Flujo', 'Ingreso', 'POS DOLARES', 3, 1),
	(4, 'Flujo', 'Ingreso', 'POS SOLES', 4, 1),
	(5, 'Flujo', 'Ingreso', 'PESOS EFECTIVO', 5, 1),
	(6, 'Flujo', 'Ingreso', 'DOLARES EFECTIVO', 6, 1),
	(7, 'Flujo', 'Ingreso', 'SOLES EFECTIVO', 7, 1),
	(8, 'Flujo', 'Ingreso', 'REPOSICIÓN YAPE', 8, 1),
	(9, 'Flujo', 'Egreso', 'MERCADO', 1, 1),
	(10, 'Flujo', 'Egreso', 'MOVILIDAD', 2, 1),
	(11, 'Flujo', 'Egreso', 'CAFETERÍA VEA-GENOVESA', 3, 1),
	(12, 'Flujo', 'Egreso', 'LAVANDERÍA', 4, 1),
	(13, 'Flujo', 'Egreso', 'ÚTILES DE ESCRITORIO', 5, 1),
	(14, 'Flujo', 'Egreso', 'RECEPCIÓN C.CH.', 6, 1),
	(15, 'Flujo', 'Egreso', 'SERV. REPUESTOS', 7, 1),
	(16, 'Flujo', 'Egreso', 'PAGO A PERSONAL', 8, 1),
	(17, 'Flujo', 'Egreso', 'OTROS', 9, 1),
	(18, 'C.Chica', 'Egreso', 'PANADERÍA', 1, 1),
	(19, 'C.Chica', 'Egreso', 'TIENDA', 2, 1),
	(20, 'C.Chica', 'Egreso', 'MOVILIDAD', 3, 1),
	(21, 'C.Chica', 'Egreso', 'MERCADO', 4, 1),
	(22, 'C.Chica', '', 'FERRETERÍA', 5, 1),
	(23, 'C.Chica', 'Egreso', 'FARMACIA', 6, 1),
	(24, 'C.Chica', 'Egreso', 'LAVANDERÍA', 7, 1),
	(25, 'C.Chica', 'Egreso', 'PUBLICIDAD', 8, 1),
	(26, 'C.Chica', 'Egreso', 'VUELTO', 9, 1),
	(27, 'C.Chica', 'Egreso', 'OTROS', 10, 1),
	(28, 'C.Chica', 'Ingreso', 'REPOSICIÓN CAJA', 1, 1);

-- Volcando datos para la tabla hotel_db.flujo_caja: ~1 rows (aproximadamente)
INSERT IGNORE INTO `flujo_caja` (`id`, `fecha`, `turno`, `estado`, `nota_entrega`, `usuario_id`, `created_at`, `updated_at`) VALUES
	(1, '2026-05-31', 'MAÑANA', 'borrador', 'Apertura automática desde Rooming V2', 4, '2026-05-31 17:20:25', '2026-05-31 17:20:25');

-- Volcando datos para la tabla hotel_db.flujo_caja_movimientos: ~4 rows (aproximadamente)
INSERT IGNORE INTO `flujo_caja_movimientos` (`id`, `flujo_id`, `categoria_id`, `stay_id`, `tipo`, `monto`, `moneda`, `medio_pago`, `documento`, `observacion`, `vuelto`, `created_at`) VALUES
	(1, 1, 7, 1, 'Ingreso', 300.00, 'PEN', 'EFECTIVO', NULL, 'HOSPEDAJE: Andree Flores Melendez - Registro #1 (Hab #202)', 0.00, '2026-05-31 17:20:25'),
	(2, 1, 7, 2, 'Ingreso', 500.00, 'PEN', 'EFECTIVO', NULL, 'HOSPEDAJE: Jaime Flores - Registro #2 (Hab #205)', 0.00, '2026-05-31 18:55:39'),
	(3, 1, 14, NULL, 'Egreso', 100.00, 'PEN', 'EFECTIVO', NULL, 'Apertura Ciclo #1: DDD', 0.00, '2026-05-31 20:56:43'),
	(4, 1, 14, NULL, 'Egreso', 100.00, 'PEN', 'EFECTIVO', NULL, 'Apertura Ciclo #2: FONDO FIJO S/ 100 - 03/06/2026', 0.00, '2026-06-03 17:34:39');

-- Volcando datos para la tabla hotel_db.gastos_yape: ~4 rows (aproximadamente)
INSERT IGNORE INTO `gastos_yape` (`id`, `fecha`, `turno`, `yape_recibido`, `total_gastado`, `vuelto`, `observacion`, `estado`, `usuario_id`, `created_at`) VALUES
	(1, '2026-05-31', 'MAÑANA', 500.00, 175.00, 325.00, '', 'borrador', 4, '2026-05-31 20:28:45'),
	(2, '2026-05-31', 'TARDE', 0.00, 0.00, 0.00, '', 'borrador', 4, '2026-05-31 20:28:45'),
	(3, '2026-06-02', 'MAÑANA', 0.00, 0.00, 0.00, '', 'borrador', 4, '2026-06-02 17:21:47'),
	(4, '2026-06-02', 'TARDE', 0.00, 0.00, 0.00, '', 'borrador', 4, '2026-06-02 17:21:47');

-- Volcando datos para la tabla hotel_db.gastos_yape_detalle: ~6 rows (aproximadamente)
INSERT IGNORE INTO `gastos_yape_detalle` (`id`, `gasto_yape_id`, `categoria_id`, `rubro`, `monto`, `observacion`, `documento`, `created_at`) VALUES
	(1, 1, NULL, 'MERCADO', 100.00, '', '', '2026-05-31 20:32:08'),
	(2, 1, NULL, 'MOVILIDAD', 5.00, '', '', '2026-05-31 20:32:08'),
	(3, 1, NULL, 'CAFETERÍA/VEA', 10.00, '', '', '2026-05-31 20:32:08'),
	(4, 1, NULL, 'LAVANDERÍA', 40.00, '', '', '2026-05-31 20:32:08'),
	(5, 1, NULL, 'SERV. REPUESTOS', 10.00, '', '', '2026-05-31 20:32:08'),
	(6, 1, NULL, 'OTROS', 10.00, '', '', '2026-05-31 20:32:08');

-- Volcando datos para la tabla hotel_db.habitaciones: ~25 rows (aproximadamente)
INSERT IGNORE INTO `habitaciones` (`id`, `numero`, `tipo`, `piso`, `estado`, `precio_base`, `descripcion`, `activa`) VALUES
	(1, '201', 'TRIPLE FAMILIAR', 2, 'ocupado', 200.00, NULL, 1),
	(2, '202', 'EJECUTIVA SUPERIOR', 2, 'libre', 150.00, NULL, 1),
	(3, '203', 'DOBLE', 2, 'libre', 180.00, NULL, 1),
	(4, '204', 'EJECUTIVA SUPERIOR', 2, 'ocupado', 250.00, NULL, 1),
	(5, '205', 'PLATINIUM SUITE', 2, 'ocupado', 300.00, NULL, 1),
	(6, '301', 'TRIPLE', 3, 'libre', 500.00, NULL, 1),
	(7, '302', 'EJECUTIVA SUPERIOR', 3, 'ocupado', 150.00, NULL, 1),
	(8, '303', 'DOBLE', 3, 'libre', 180.00, NULL, 1),
	(9, '304', 'MATRIMONIAL SUPERIOR', 3, 'libre', 220.00, NULL, 1),
	(10, '305', 'PLATINIUM SUITE', 3, 'libre', 260.00, NULL, 1),
	(11, '401', 'TRIPLE', 4, 'libre', 200.00, NULL, 1),
	(12, '402', 'EJECUTIVA SUPERIOR', 4, 'libre', 150.00, NULL, 1),
	(13, '403', 'DOBLE', 4, 'libre', 180.00, NULL, 1),
	(14, '404', 'MATRIMONIAL SUPERIOR', 4, 'libre', 220.00, NULL, 1),
	(15, '405', 'PLATINIUM SUITE', 4, 'libre', 260.00, NULL, 1),
	(16, '501', 'TRIPLE', 5, 'libre', 200.00, NULL, 1),
	(17, '502', 'EJECUTIVA SUPERIOR', 5, 'libre', 150.00, NULL, 1),
	(18, '503', 'DOBLE', 5, 'libre', 180.00, NULL, 1),
	(19, '504', 'MATRIMONIAL SUPERIOR', 5, 'libre', 220.00, NULL, 1),
	(20, '505', 'PLATINIUM SUITE', 5, 'libre', 260.00, NULL, 1),
	(21, '601', 'TRIPLE', 6, 'libre', 200.00, NULL, 1),
	(22, '602', 'EJECUTIVA SUPERIOR', 6, 'libre', 150.00, NULL, 1),
	(23, '603', 'DOBLE', 6, 'libre', 180.00, NULL, 1),
	(24, '604', 'MATRIMONIAL SUPERIOR', 6, 'libre', 220.00, NULL, 1),
	(25, '605', 'PLATINIUM SUITE', 6, 'libre', 260.00, NULL, 1);

-- Volcando datos para la tabla hotel_db.inventario_movimientos: ~2 rows (aproximadamente)
INSERT IGNORE INTO `inventario_movimientos` (`id`, `producto_id`, `tipo`, `cantidad`, `stock_antes`, `stock_despues`, `stay_id`, `usuario_id`, `created_at`) VALUES
	(1, 4, 'RECARGA', 10, 10, 20, NULL, 1, '2026-06-01 18:01:11'),
	(2, 4, 'RECARGA', 10, 20, 30, NULL, 4, '2026-06-01 18:08:07');

-- Volcando datos para la tabla hotel_db.inventario_productos: ~9 rows (aproximadamente)
INSERT IGNORE INTO `inventario_productos` (`id`, `nombre`, `categoria`, `refrigeradora`, `precio_venta`, `stock_actual`, `activo`) VALUES
	(1, 'COCA COLA', 'BEBIDA', 1, 7.00, 10, 1),
	(2, 'INCA COLA', 'BEBIDA', 1, 7.00, 10, 1),
	(3, 'AGUA SAN MATEO', 'BEBIDA', 1, 5.00, 10, 1),
	(4, 'AGUA SAN LUIS', 'BEBIDA', 1, 5.00, 30, 1),
	(5, 'CERV. CORONA', 'BEBIDA', 1, 10.00, 10, 1),
	(6, 'CERV. CUZQUEÑA', 'BEBIDA', 1, 10.00, 10, 1),
	(7, 'VINO ROJO', 'VINO', 2, 35.00, 10, 1),
	(8, 'VINO MORADO', 'VINO', 2, 35.00, 10, 1),
	(9, 'VINO AZUL', 'VINO', 2, 35.00, 10, 1);

-- Volcando datos para la tabla hotel_db.limpieza_registros: ~5 rows (aproximadamente)
INSERT IGNORE INTO `limpieza_registros` (`id`, `fecha`, `habitacion_id`, `tipo_limpieza`, `prioridad`, `estado`, `hora_inicio`, `hora_fin`, `observacion`, `usuario_id`, `created_at`) VALUES
	(1, '2026-05-31', 2, 'salida', 'alta', 'lista', NULL, '2026-05-31 13:31:14', NULL, 4, '2026-05-31 17:20:57'),
	(2, '2026-06-01', 2, 'salida', 'normal', 'pendiente', NULL, NULL, NULL, 4, '2026-06-01 15:54:46'),
	(3, '2026-06-01', 5, 'salida', 'normal', 'pendiente', NULL, NULL, NULL, 4, '2026-06-01 15:54:46'),
	(4, '2026-06-02', 2, 'salida', 'normal', 'lista', NULL, '2026-06-02 12:33:38', NULL, 4, '2026-06-02 17:23:05'),
	(5, '2026-06-02', 5, 'salida', 'normal', 'lista', NULL, '2026-06-02 12:33:38', NULL, 4, '2026-06-02 17:23:05');

-- Volcando datos para la tabla hotel_db.rooming_consumos: ~0 rows (aproximadamente)

-- Volcando datos para la tabla hotel_db.rooming_pax: ~6 rows (aproximadamente)
INSERT IGNORE INTO `rooming_pax` (`id`, `stay_id`, `cliente_id`, `es_titular_acompanante`, `created_at`, `vip`) VALUES
	(1, 1, 1, 1, '2026-05-31 17:20:25', 0),
	(2, 2, 2, 1, '2026-05-31 18:55:39', 0),
	(3, 3, 3, 1, '2026-06-05 19:38:50', 0),
	(4, 4, 7, 1, '2026-06-05 20:43:31', 0),
	(5, 5, 8, 1, '2026-06-05 20:45:14', 0),
	(6, 6, 9, 1, '2026-06-05 21:25:20', 0);

-- Volcando datos para la tabla hotel_db.rooming_stays: ~6 rows (aproximadamente)
INSERT IGNORE INTO `rooming_stays` (`id`, `operador`, `fecha_registro`, `fecha_checkin_real`, `fecha_checkout`, `medio_reserva`, `habitacion_id`, `tipo_hab_declarado`, `pax_total`, `total_pago`, `moneda_pago`, `monto_original`, `tc_aplicado`, `recargo_tarjeta`, `metodo_pago`, `tipo_comprobante`, `num_comprobante`, `cobrador`, `procedencia`, `carro`, `estado`, `estado_pago`, `total_cobrado`, `total_cobrado_orig`, `checkin_realizado`, `observaciones`, `usuario_id`, `created_at`, `updated_at`, `cliente_titular_id`) VALUES
	(1, 'Roy', '2026-05-31', '2026-05-31 12:20:00', '2026-06-04', 'DIRECTO', 2, 'EJECUTIVA SUPERIOR', 1, 300.00, 'PEN', 300.00, NULL, 0.00, 'SOLES EFECTIVO', '', '', 'Roy', NULL, 'NO', 'activo', 'pagado', 300.00, 300.00, 1, 'sin tv', 4, '2026-05-31 17:20:25', '2026-05-31 18:38:21', 1),
	(2, 'Roy', '2026-05-31', '2026-05-31 13:55:00', '2026-06-02', 'DIRECTO', 5, 'PLATINIUM SUITE', 1, 500.00, 'PEN', 500.00, NULL, 0.00, 'SOLES EFECTIVO', '', '', 'Roy', NULL, 'NO', 'activo', 'pagado', 500.00, 500.00, 1, '', 4, '2026-05-31 18:55:39', '2026-06-02 17:52:39', 2),
	(3, 'Roy', '2026-06-05', NULL, '2026-06-08', 'DIRECTO', 1, 'RESERVA', 1, 0.00, 'PEN', NULL, NULL, 0.00, 'EFECTIVO', '', NULL, 'Roy', NULL, NULL, 'activo', 'pendiente', 0.00, 0.00, 0, 'sin television', 4, '2026-06-05 19:38:50', '2026-06-05 19:39:00', 3),
	(4, 'Admin', '2026-06-05', NULL, '2026-06-06', 'DIRECTO', 1, 'RESERVA', 1, 0.00, 'PEN', NULL, NULL, 0.00, 'EFECTIVO', '', NULL, 'Admin', NULL, NULL, 'reservado', 'pendiente', 0.00, 0.00, 0, '', 1, '2026-06-05 20:43:31', '2026-06-05 20:43:31', 7),
	(5, 'Roy', '2026-06-06', NULL, '2026-06-07', 'LLAMADA', 4, 'RESERVA', 1, 0.00, 'PEN', NULL, NULL, 0.00, 'EFECTIVO', '', NULL, 'Roy', NULL, NULL, 'activo', 'pendiente', 0.00, 0.00, 1, 'f', 4, '2026-06-05 20:45:14', '2026-06-05 20:53:01', 8),
	(6, 'Roy', '2026-06-05', NULL, '2026-06-06', 'DIRECTO', 7, 'RESERVA', 1, 0.00, 'PEN', NULL, NULL, 0.00, 'EFECTIVO', '', NULL, 'Roy', NULL, NULL, 'activo', 'pendiente', 0.00, 0.00, 1, 'con piscina', 4, '2026-06-05 21:25:20', '2026-06-05 21:25:39', 9);

-- Volcando datos para la tabla hotel_db.rooming_stays_historial_fechas: ~3 rows (aproximadamente)
INSERT IGNORE INTO `rooming_stays_historial_fechas` (`id`, `stay_id`, `fecha_checkout_pasada`, `motivo`, `usuario_id`, `created_at`) VALUES
	(1, 1, '2026-05-31', 'Ampliación de estadía desde grilla V2', 4, '2026-05-31 18:36:48'),
	(2, 1, '2026-06-03', 'Ampliación de estadía desde grilla V2', 4, '2026-05-31 18:38:21'),
	(3, 2, '2026-05-31', 'Ampliación de estadía desde grilla V2', 4, '2026-05-31 18:55:48');

-- Volcando datos para la tabla hotel_db.tipos_cambio: ~2 rows (aproximadamente)
INSERT IGNORE INTO `tipos_cambio` (`id`, `moneda_origen`, `moneda_destino`, `factor`, `fecha`, `created_at`) VALUES
	(1, 'USD', 'PEN', 3.7000, '2026-05-31', '2026-05-31 17:10:46'),
	(2, 'CLP', 'PEN', 277.0000, '2026-05-31', '2026-05-31 17:10:46');

-- Volcando datos para la tabla hotel_db.usuarios: ~5 rows (aproximadamente)
INSERT IGNORE INTO `usuarios` (`id`, `usuario`, `password`, `rol`, `nombre`, `estado`, `created_at`) VALUES
	(1, 'admin', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'admin', 'Administrador', 1, '2026-05-31 17:10:46'),
	(2, 'kari', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'limpieza', 'Kari', 1, '2026-05-31 17:10:46'),
	(3, 'jessica', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera', 'Jessica', 1, '2026-05-31 17:10:46'),
	(4, 'roy', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera', 'Roy', 1, '2026-05-31 17:10:46'),
	(5, 'alex', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'supervisor', 'Alex', 1, '2026-05-31 17:10:46');

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
