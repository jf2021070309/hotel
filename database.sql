-- ============================================================
-- SISTEMA DE GESTIÓN HOTEL - PLATINIUM
-- Base de datos completa v2.0 (corregida y ampliada)
-- ============================================================

CREATE DATABASE IF NOT EXISTS hotel_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE hotel_db;

-- ============================================================
-- MÓDULO: CONFIGURACIÓN BASE
-- ============================================================

CREATE TABLE IF NOT EXISTS `configuracion` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parametro`   VARCHAR(50)  NOT NULL,
  `valor`       VARCHAR(255) NOT NULL,
  `descripcion` TEXT         DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_parametro` (`parametro`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `configuracion` (`parametro`, `valor`, `descripcion`) VALUES
('recargo_tarjeta',    '0.05',          'Recargo por pago con tarjeta internacional (5%)'),
('nombre_hotel',       'Hotel Platinium','Nombre del hotel'),
('moneda_base',        'PEN',           'Moneda principal del sistema'),
('version_bd',         '2.0',           'Versión del esquema de base de datos');

-- ============================================================
-- MÓDULO: TIPOS DE CAMBIO
-- ============================================================

CREATE TABLE IF NOT EXISTS `tipos_cambio` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `moneda_origen`  VARCHAR(10)   NOT NULL,
  `moneda_destino` VARCHAR(10)   NOT NULL DEFAULT 'PEN',
  `factor`         DECIMAL(10,4) NOT NULL,
  `fecha`          DATE          NOT NULL,
  `created_at`     TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_tc_fecha` (`moneda_origen`, `moneda_destino`, `fecha`),
  KEY `idx_fecha` (`fecha`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `tipos_cambio` (`moneda_origen`, `moneda_destino`, `factor`, `fecha`) VALUES
('USD', 'PEN', 3.7000, CURRENT_DATE),
('CLP', 'PEN', 0.0036, CURRENT_DATE); -- 1 CLP = 0.0036 PEN (277 PEN = 1 USD aprox)

-- ============================================================
-- MÓDULO: USUARIOS Y SEGURIDAD
-- ============================================================

CREATE TABLE IF NOT EXISTS `usuarios` (
  `id`         INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario`    VARCHAR(50)  NOT NULL,
  `password`   VARCHAR(255) NOT NULL,
  `rol`        ENUM('admin','supervisor','cajera','limpieza') NOT NULL DEFAULT 'cajera',
  `nombre`     VARCHAR(100) NOT NULL,
  `estado`     TINYINT(1)   NOT NULL DEFAULT 1,
  `created_at` TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_usuario` (`usuario`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- password: 'admin123' (bcrypt)
INSERT IGNORE INTO `usuarios` (`usuario`, `password`, `rol`, `nombre`) VALUES
('admin',   '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'admin',      'Administrador'),
('cajera1', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera',     'Kari'),
('cajera2', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera',     'Jessica'),
('cajera3', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera',     'Roy'),
('cajera4', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'supervisor', 'Alex');

CREATE TABLE IF NOT EXISTS `auditoria` (
  `id`            INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `usuario_id`    INT UNSIGNED DEFAULT NULL,
  `usuario_nombre`VARCHAR(100) DEFAULT NULL,
  `accion`        VARCHAR(100) NOT NULL,
  `modulo`        VARCHAR(50)  NOT NULL,
  `detalle`       TEXT         DEFAULT NULL,
  `fecha_hora`    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `ip`            VARCHAR(45)  DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_fecha`    (`fecha_hora`),
  KEY `idx_usuario`  (`usuario_id`),
  KEY `idx_modulo`   (`modulo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: HABITACIONES
-- CORRECCIÓN: estado ampliado + typo "MATRIONIAL" corregido
-- ============================================================

CREATE TABLE IF NOT EXISTS `habitaciones` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `numero`      VARCHAR(10)   NOT NULL,
  `tipo`        VARCHAR(60)   NOT NULL DEFAULT 'Simple',
  `piso`        TINYINT UNSIGNED NOT NULL DEFAULT 1,
  `estado`      ENUM('libre','ocupado','limpieza','mantenimiento','bloqueada') NOT NULL DEFAULT 'libre',
  `precio_base` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `descripcion` TEXT          DEFAULT NULL,
  `activa`      TINYINT(1)    NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_numero` (`numero`),
  KEY `idx_estado` (`estado`),
  KEY `idx_piso`   (`piso`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `habitaciones` (`numero`, `tipo`, `piso`, `estado`, `precio_base`) VALUES
('201', 'TRIPLE FAMILIAR',      2, 'libre', 0.00),
('202', 'EJECUTIVA SUPERIOR',   2, 'libre', 0.00),
('203', 'DOBLE',                2, 'libre', 0.00),
('204', 'EJECUTIVA SUPERIOR',   2, 'libre', 0.00),
('205', 'PLATINIUM SUITE',      2, 'libre', 0.00),

('301', 'TRIPLE',               3, 'libre', 0.00),
('302', 'EJECUTIVA SUPERIOR',   3, 'libre', 0.00),
('303', 'DOBLE',                3, 'libre', 0.00),
('304', 'MATRIMONIAL SUPERIOR', 3, 'libre', 0.00),  -- corregido
('305', 'PLATINIUM SUITE',      3, 'libre', 0.00),

('401', 'TRIPLE',               4, 'libre', 0.00),
('402', 'EJECUTIVA SUPERIOR',   4, 'libre', 0.00),
('403', 'DOBLE',                4, 'libre', 0.00),
('404', 'MATRIMONIAL SUPERIOR', 4, 'libre', 0.00),  -- corregido
('405', 'PLATINIUM SUITE',      4, 'libre', 0.00),

('501', 'TRIPLE',               5, 'libre', 0.00),
('502', 'EJECUTIVA SUPERIOR',   5, 'libre', 0.00),
('503', 'DOBLE',                5, 'libre', 0.00),
('504', 'MATRIMONIAL SUPERIOR', 5, 'libre', 0.00),
('505', 'PLATINIUM SUITE',      5, 'libre', 0.00),

('601', 'TRIPLE',               6, 'libre', 0.00),
('602', 'EJECUTIVA SUPERIOR',   6, 'libre', 0.00),
('603', 'DOBLE',                6, 'libre', 0.00),
('604', 'MATRIMONIAL SUPERIOR', 6, 'libre', 0.00),  -- corregido
('605', 'PLATINIUM SUITE',      6, 'libre', 0.00);

-- ============================================================
-- MÓDULO: CATEGORÍAS FINANCIERAS (configurable)
-- ============================================================

CREATE TABLE IF NOT EXISTS `finanzas_categorias` (
  `id`     INT          NOT NULL AUTO_INCREMENT,
  `modulo` ENUM('C.Chica','Flujo') NOT NULL,
  `tipo`   ENUM('Ingreso','Egreso') NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `orden`  INT          DEFAULT 0,
  `activo` TINYINT(1)   DEFAULT 1,
  PRIMARY KEY (`id`),
  KEY `idx_modulo_tipo` (`modulo`, `tipo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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

-- ============================================================
-- MÓDULO: CAJA CHICA
-- ============================================================

CREATE TABLE IF NOT EXISTS `caja_chica` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`           VARCHAR(100)  NOT NULL,
  `saldo_inicial`    DECIMAL(12,2) DEFAULT 0.00,
  `saldo_final`      DECIMAL(12,2) DEFAULT NULL,
  `fecha_apertura`   DATE          NOT NULL,
  `fecha_cierre`     DATE          DEFAULT NULL,
  `estado`           ENUM('abierta','cerrada') DEFAULT 'abierta',
  `usuario_apertura` INT UNSIGNED  NOT NULL,
  `usuario_cierre`   INT UNSIGNED  DEFAULT NULL,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_estado` (`estado`),
  FOREIGN KEY (`usuario_apertura`) REFERENCES `usuarios`(`id`),
  FOREIGN KEY (`usuario_cierre`)   REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `caja_chica_movimientos` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `caja_id`          INT UNSIGNED  NOT NULL,
  `tipo`             ENUM('ingreso','egreso') NOT NULL,
  `monto`            DECIMAL(12,2) NOT NULL,
  `categoria_id`     INT           DEFAULT NULL,  -- FK a finanzas_categorias
  `rubro`            VARCHAR(100)  NOT NULL,       -- texto libre como respaldo
  `documento`        VARCHAR(100)  DEFAULT NULL,
  `fecha`            DATE          NOT NULL,
  `observacion`      TEXT          DEFAULT NULL,
  `usuario_id`       INT UNSIGNED  NOT NULL,
  `anulado`          TINYINT(1)    DEFAULT 0,
  `motivo_anulacion` TEXT          DEFAULT NULL,
  `created_at`       TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_caja`   (`caja_id`),
  KEY `idx_fecha`  (`fecha`),
  FOREIGN KEY (`caja_id`)      REFERENCES `caja_chica`(`id`),
  FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias`(`id`),
  FOREIGN KEY (`usuario_id`)   REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: FLUJO DE CAJA POR TURNO
-- CORRECCIÓN: eliminados mendoza_id/alex_id → tabla responsables
-- ============================================================

CREATE TABLE IF NOT EXISTS `flujo_caja` (
  `id`          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`       DATE         NOT NULL,
  `turno`       ENUM('MAÑANA','TARDE') NOT NULL,
  `estado`      ENUM('borrador','cerrado','depositado') DEFAULT 'borrador',
  `nota_entrega`TEXT         DEFAULT NULL,
  `usuario_id`  INT UNSIGNED NOT NULL,
  `created_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  `updated_at`  TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_flujo_turno` (`fecha`, `turno`),
  KEY `idx_fecha`  (`fecha`),
  KEY `idx_estado` (`estado`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- NUEVO: Responsables del turno (reemplaza mendoza_id / alex_id hardcodeados)
CREATE TABLE IF NOT EXISTS `flujo_caja_responsables` (
  `flujo_id`   INT UNSIGNED NOT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `rol_turno`  VARCHAR(50)  DEFAULT NULL, -- 'supervisor', 'cajera', 'entrega'
  PRIMARY KEY (`flujo_id`, `usuario_id`),
  FOREIGN KEY (`flujo_id`)   REFERENCES `flujo_caja`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `flujo_caja_movimientos` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `flujo_id`    INT UNSIGNED  NOT NULL,
  `categoria_id`INT           DEFAULT NULL,
  `categoria`   VARCHAR(100)  NOT NULL,
  `tipo`        ENUM('Ingreso','Egreso') NOT NULL,
  `moneda`      ENUM('PEN','USD','CLP')  DEFAULT 'PEN',
  `monto`       DECIMAL(12,2) NOT NULL,
  `medio_pago`  ENUM('EFECTIVO','NO EFECTIVO') DEFAULT 'EFECTIVO',
  `observacion` TEXT          DEFAULT NULL,
  `vuelto`      DECIMAL(12,2) DEFAULT 0.00,
  PRIMARY KEY (`id`),
  KEY `idx_flujo` (`flujo_id`),
  FOREIGN KEY (`flujo_id`)     REFERENCES `flujo_caja`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`) REFERENCES `finanzas_categorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: GASTOS CON YAPE (BELINDA/ALEX)
-- CORRECCIÓN: columnas fijas → tabla detalle flexible
-- ============================================================

CREATE TABLE IF NOT EXISTS `gastos_yape` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `fecha`          DATE          NOT NULL,
  `turno`          ENUM('MAÑANA','TARDE') NOT NULL,
  `yape_recibido`  DECIMAL(10,2) DEFAULT 0.00,
  `total_gastado`  DECIMAL(10,2) DEFAULT 0.00,  -- calculado desde detalle
  `vuelto`         DECIMAL(10,2) DEFAULT 0.00,  -- yape_recibido - total_gastado
  `observacion`    TEXT          DEFAULT NULL,
  `estado`         ENUM('borrador','cerrado') DEFAULT 'borrador',
  `usuario_id`     INT UNSIGNED  NOT NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_yape` (`fecha`, `turno`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `gastos_yape_detalle` (
  `id`             INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `gasto_yape_id`  INT UNSIGNED  NOT NULL,
  `categoria_id`   INT           DEFAULT NULL,
  `rubro`          VARCHAR(100)  NOT NULL,
  `monto`          DECIMAL(10,2) NOT NULL,
  `observacion`    TEXT          DEFAULT NULL,
  `documento`      VARCHAR(100)  DEFAULT NULL,
  `created_at`     TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_yape` (`gasto_yape_id`),
  FOREIGN KEY (`gasto_yape_id`) REFERENCES `gastos_yape`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`categoria_id`)  REFERENCES `finanzas_categorias`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: ROOMING (REGISTRO PAX)
-- CORRECCIÓN: moneda_pago + monto_original + tc_aplicado
-- ============================================================

CREATE TABLE IF NOT EXISTS `rooming_stays` (
  `id`                 INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `operador`           VARCHAR(50)   NOT NULL,
  `fecha_registro`     DATE          NOT NULL,
  `fecha_checkout`     DATE          DEFAULT NULL,
  `hora_checkin`       TIME          DEFAULT NULL,
  `medio_reserva`      VARCHAR(50)   NOT NULL,  -- DIRECTO, WHATSAPP, BOOKING, EXPEDIA, CORPORATIVO
  `habitacion_id`      INT UNSIGNED  NOT NULL,
  `tipo_hab_declarado` VARCHAR(60)   NOT NULL,
  `noches`             TINYINT UNSIGNED DEFAULT 1,
  `pax_total`          TINYINT UNSIGNED DEFAULT 1,
  -- Campos de pago corregidos
  `total_pago`         DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `moneda_pago`        ENUM('PEN','USD','CLP') NOT NULL DEFAULT 'PEN',
  `monto_original`     DECIMAL(10,2) DEFAULT NULL, -- monto en moneda origen antes de conversión
  `tc_aplicado`        DECIMAL(10,4) DEFAULT NULL, -- tipo de cambio usado en el cobro
  `recargo_tarjeta`    DECIMAL(10,2) DEFAULT 0.00, -- monto del 5% si aplica
  -- Comprobante
  `metodo_pago`        VARCHAR(50)   NOT NULL,
  `tipo_comprobante`   VARCHAR(50)   NOT NULL,      -- BOLETA, FACTURA, F.X.
  `num_comprobante`    VARCHAR(50)   DEFAULT NULL,
  `ruc_factura`        VARCHAR(20)   DEFAULT NULL,  -- RUC si es factura corporativa
  `cobrador`           VARCHAR(50)   NOT NULL,
  -- Origen del huésped
  `procedencia`        VARCHAR(100)  DEFAULT NULL,
  `carro_placa`        VARCHAR(20)   DEFAULT NULL,
  -- Estado
  `estado`             ENUM('activo','finalizado','anulado','late_checkout') DEFAULT 'activo',
  -- Estado de pago (para visualización en tiempo real del jefe)
  `estado_pago`        ENUM('pendiente','adelanto','parcial','pagado') DEFAULT 'pendiente',
  `total_cobrado`      DECIMAL(10,2) DEFAULT 0.00, -- suma de pagos registrados
  -- Otros
  `checkin_realizado`  TINYINT(1)    DEFAULT 0,
  `observaciones`      TEXT          DEFAULT NULL,
  `usuario_id`         INT UNSIGNED  NOT NULL,
  `created_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  `updated_at`         TIMESTAMP     DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_habitacion` (`habitacion_id`),
  KEY `idx_fecha`      (`fecha_registro`),
  KEY `idx_estado`     (`estado`),
  KEY `idx_estado_pago`(`estado_pago`),
  FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones`(`id`),
  FOREIGN KEY (`usuario_id`)    REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `rooming_pax` (
  `id`              INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `stay_id`         INT UNSIGNED NOT NULL,
  `nombre_completo` VARCHAR(255) NOT NULL,
  `documento_tipo`  VARCHAR(20)  DEFAULT 'DNI',   -- DNI, CEDULA, PASAPORTE
  `documento_num`   VARCHAR(30)  NOT NULL,
  `nacionalidad`    VARCHAR(50)  DEFAULT 'Peruana',
  `ciudad`          VARCHAR(80)  DEFAULT NULL,
  `es_titular`      TINYINT(1)   DEFAULT 0,
  `created_at`      TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stay`      (`stay_id`),
  KEY `idx_documento` (`documento_num`),
  FOREIGN KEY (`stay_id`) REFERENCES `rooming_stays`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: ANTICIPOS Y PAGOS PARCIALES (NUEVO)
-- Cubre casos: "500.00 adelanto x alojamiento", pagos en 2 partes
-- ============================================================

CREATE TABLE IF NOT EXISTS `anticipos` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stay_id`     INT UNSIGNED  NOT NULL,
  `monto`       DECIMAL(10,2) NOT NULL,
  `moneda`      ENUM('PEN','USD','CLP') DEFAULT 'PEN',
  `monto_pen`   DECIMAL(10,2) DEFAULT NULL, -- equivalente en soles
  `tc_aplicado` DECIMAL(10,4) DEFAULT NULL,
  `tipo_pago`   VARCHAR(50)   NOT NULL,     -- EFECTIVO, POS, YAPE, etc.
  `recibo`      VARCHAR(50)   DEFAULT NULL, -- número de recibo entregado
  `fecha`       DATE          NOT NULL,
  `aplicado`    TINYINT(1)    DEFAULT 0,    -- 1 = ya se descontó del total
  `observacion` TEXT          DEFAULT NULL,
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stay` (`stay_id`),
  FOREIGN KEY (`stay_id`)    REFERENCES `rooming_stays`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: INVENTARIO DE BEBIDAS (NUEVO)
-- Cubre: Refri 1 (bebidas) y Refri 2 (vinos) del Rooming-Enero
-- ============================================================

CREATE TABLE IF NOT EXISTS `inventario_productos` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `nombre`       VARCHAR(100)  NOT NULL,
  `categoria`    VARCHAR(50)   DEFAULT NULL,   -- BEBIDA, VINO, SNACK, etc.
  `refrigeradora`TINYINT UNSIGNED DEFAULT 1,   -- 1 = Refri 1, 2 = Refri 2
  `precio_venta` DECIMAL(10,2) NOT NULL DEFAULT 0.00,
  `stock_actual` INT           DEFAULT 0,
  `activo`       TINYINT(1)    DEFAULT 1,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO `inventario_productos` (`nombre`, `categoria`, `refrigeradora`, `precio_venta`) VALUES
('COCA COLA',    'BEBIDA', 1, 7.00),
('INCA COLA',    'BEBIDA', 1, 7.00),
('AGUA SAN MATEO','BEBIDA',1, 5.00),
('AGUA SAN LUIS','BEBIDA', 1, 5.00),
('CERV. CORONA', 'BEBIDA', 1, 10.00),
('CERV. CUZQUEÑA','BEBIDA',1, 10.00),
('VINO ROJO',    'VINO',   2, 35.00),
('VINO MORADO',  'VINO',   2, 35.00),
('VINO AZUL',    'VINO',   2, 35.00);

CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `producto_id`  INT UNSIGNED NOT NULL,
  `fecha`        DATE         NOT NULL,
  `tipo`         ENUM('ingreso','venta','ajuste') NOT NULL,
  `cantidad`     INT          NOT NULL,
  `stock_queda`  INT          NOT NULL,           -- stock después del movimiento
  `stay_id`      INT UNSIGNED DEFAULT NULL,        -- si es venta vinculada a hab.
  `habitacion`   VARCHAR(10)  DEFAULT NULL,        -- referencia rápida
  `observacion`  TEXT         DEFAULT NULL,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_producto` (`producto_id`),
  KEY `idx_fecha`    (`fecha`),
  KEY `idx_stay`     (`stay_id`),
  FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos`(`id`),
  FOREIGN KEY (`stay_id`)     REFERENCES `rooming_stays`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`)  REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: CONSUMOS POR HABITACIÓN (NUEVO)
-- Vincula ventas de minibar/extras a la estadía para cobro al checkout
-- ============================================================

CREATE TABLE IF NOT EXISTS `consumos_hab` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `stay_id`     INT UNSIGNED  NOT NULL,
  `producto_id` INT UNSIGNED  DEFAULT NULL,
  `descripcion` VARCHAR(150)  NOT NULL,
  `cantidad`    TINYINT UNSIGNED DEFAULT 1,
  `precio_unit` DECIMAL(10,2) NOT NULL,
  `total`       DECIMAL(10,2) GENERATED ALWAYS AS (`cantidad` * `precio_unit`) STORED,
  `cobrado`     TINYINT(1)    DEFAULT 0,       -- 1 = incluido en pago final
  `fecha`       DATE          NOT NULL,
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_stay`    (`stay_id`),
  KEY `idx_cobrado` (`cobrado`),
  FOREIGN KEY (`stay_id`)     REFERENCES `rooming_stays`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`producto_id`) REFERENCES `inventario_productos`(`id`) ON DELETE SET NULL,
  FOREIGN KEY (`usuario_id`)  REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: REPORTE MENDOZA (cierre por turno para el jefe)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporte_mendoza` (
  `id`           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha`        DATE         NOT NULL,
  `turno`        ENUM('MAÑANA','TARDE') NOT NULL,
  `estado`       ENUM('abierto','cerrado') DEFAULT 'abierto',
  `nota`         TEXT         DEFAULT NULL,
  `usuario_id`   INT UNSIGNED NOT NULL,
  `fecha_cierre` DATETIME     DEFAULT NULL,
  `created_at`   TIMESTAMP    DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_mendoza` (`fecha`, `turno`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reporte_mendoza_habs` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `reporte_id`  INT UNSIGNED  NOT NULL,
  `hab`         VARCHAR(20)   NOT NULL,
  `noches`      VARCHAR(50)   NOT NULL,
  `check_in`    DATETIME      DEFAULT NULL,
  `check_out`   DATETIME      DEFAULT NULL,
  `tipo_pago`   ENUM('POS','EFECTIVO','YAPE','SIN MOVIMIENTO') DEFAULT 'EFECTIVO',
  `monto`       DECIMAL(10,2) DEFAULT 0.00,
  `moneda`      ENUM('SOLES','DOLARES','PESOS') DEFAULT 'SOLES',
  `observacion` TEXT          DEFAULT NULL,
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reporte` (`reporte_id`),
  FOREIGN KEY (`reporte_id`) REFERENCES `reporte_mendoza`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `reporte_mendoza_otros` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `reporte_id`  INT UNSIGNED  NOT NULL,
  `tipo`        ENUM('consumo','egreso') NOT NULL,
  `descripcion` TEXT          NOT NULL,
  `tipo_pago`   ENUM('POS','EFECTIVO','YAPE') DEFAULT 'EFECTIVO',
  `monto`       DECIMAL(10,2) DEFAULT 0.00,
  `moneda`      ENUM('SOLES','DOLARES','PESOS') DEFAULT 'SOLES',
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_reporte` (`reporte_id`),
  FOREIGN KEY (`reporte_id`) REFERENCES `reporte_mendoza`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- MÓDULO: REPORTE ALEX (control físico de dinero)
-- ============================================================

CREATE TABLE IF NOT EXISTS `reporte_alex` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `fecha`       DATE          NOT NULL,
  `turno`       ENUM('MAÑANA','TARDE') NOT NULL,
  `pesos`       DECIMAL(10,2) DEFAULT 0.00,
  `dolares`     DECIMAL(10,2) DEFAULT 0.00,
  `soles`       DECIMAL(10,2) DEFAULT 0.00,
  `observacion` TEXT          DEFAULT NULL,
  `estado`      ENUM('borrador','cerrado') DEFAULT 'borrador',
  `usuario_id`  INT UNSIGNED  NOT NULL,
  `created_at`  TIMESTAMP     DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_turno_alex` (`fecha`, `turno`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- FIN DEL SCRIPT - hotel_db v2.0
-- Total de tablas: 20
-- ============================================================
