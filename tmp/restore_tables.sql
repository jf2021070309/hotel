-- MÓDULO: LIMPIEZA
CREATE TABLE IF NOT EXISTS `limpieza_registros` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `habitacion_id` INT UNSIGNED NOT NULL,
  `habitacion` VARCHAR(10) NOT NULL,
  `tipo_limpieza` ENUM('estimacion', 'estadía', 'salida', 'programada') NOT NULL,
  `prioridad` ENUM('baja', 'normal', 'alta') DEFAULT 'normal',
  `estado` ENUM('pendiente', 'en proceso', 'lista') DEFAULT 'pendiente',
  `hora_inicio` DATETIME DEFAULT NULL,
  `hora_fin` DATETIME DEFAULT NULL,
  `observacion` TEXT DEFAULT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha_hab` (`fecha`, `habitacion_id`),
  FOREIGN KEY (`habitacion_id`) REFERENCES `habitaciones`(`id`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- MÓDULO: DESAYUNOS
CREATE TABLE IF NOT EXISTS `desayunos` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `fecha` DATE NOT NULL,
  `pax_calculado` INT DEFAULT 0,
  `pax_ajustado` INT DEFAULT 0,
  `observacion` TEXT DEFAULT NULL,
  `usuario_id` INT UNSIGNED NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_fecha` (`fecha`),
  FOREIGN KEY (`usuario_id`) REFERENCES `usuarios`(`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `desayunos_detalle` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `desayuno_id` INT UNSIGNED NOT NULL,
  `habitacion_id` INT UNSIGNED NOT NULL,
  `habitacion` VARCHAR(10) NOT NULL,
  `titular` VARCHAR(100) DEFAULT NULL,
  `pax` INT DEFAULT 1,
  `incluye_desayuno` TINYINT(1) DEFAULT 1,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`desayuno_id`) REFERENCES `desayunos`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
