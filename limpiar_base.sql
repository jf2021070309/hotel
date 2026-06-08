-- Desactivar temporalmente la verificación de claves foráneas
SET FOREIGN_KEY_CHECKS = 0;

-- 1. Vaciar las tablas de reservas, checkins y huéspedes
TRUNCATE TABLE rooming_stays;
TRUNCATE TABLE rooming_pax;
TRUNCATE TABLE rooming_stays_historial_fechas;
TRUNCATE TABLE clientes;

-- 2. Vaciar las tablas financieras y consumos vinculados a las estadías
TRUNCATE TABLE rooming_consumos;
TRUNCATE TABLE anticipos;
TRUNCATE TABLE flujo_caja_movimientos;
TRUNCATE TABLE flujo_caja;

-- 3. Vaciar tablas operativas que dependen de los huéspedes
TRUNCATE TABLE limpieza_registros;
TRUNCATE TABLE desayunos;
TRUNCATE TABLE desayunos_detalle;

-- 4. Liberar todas las habitaciones
UPDATE habitaciones SET estado = 'libre';

-- Volver a activar las claves foráneas
SET FOREIGN_KEY_CHECKS = 1;
