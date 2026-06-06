-- ========================================================
-- BLOC 9: INSERCIÓN DE DATA MAESTRA INICIAL
-- ========================================================
use hotel_db;

INSERT IGNORE INTO usuarios (id, usuario, password, rol, nombre) VALUES
(1, 'admin', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'admin', 'Administrador'),
(2, 'kari', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'limpieza', 'Kari'),
(3, 'jessica', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera', 'Jessica'),
(4, 'roy', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'cajera', 'Roy'),
(5, 'alex', '$2y$10$Y/GanjL/y/VpLwNpr2gNgua41aXNlmDuTxeScRsvw9Vh8zIHIt0uS', 'supervisor', 'Alex');

INSERT INTO habitaciones (numero, tipo, piso, estado, precio_base) VALUES
('201', 'TRIPLE FAMILIAR', 2, 'libre', 200.00), ('202', 'EJECUTIVA SUPERIOR', 2, 'libre', 150.00),
('203', 'DOBLE', 2, 'libre', 180.00),           ('204', 'EJECUTIVA SUPERIOR', 2, 'libre', 250.00),
('205', 'PLATINIUM SUITE', 2, 'libre', 300.00),    ('301', 'TRIPLE', 3, 'libre', 500.00),
('302', 'EJECUTIVA SUPERIOR', 3, 'libre', 150.00), ('303', 'DOBLE', 3, 'libre', 180.00),
('304', 'MATRIMONIAL SUPERIOR', 3, 'libre', 220.00),('305', 'PLATINIUM SUITE', 3, 'libre', 260.00),
('401', 'TRIPLE', 4, 'libre', 200.00),             ('402', 'EJECUTIVA SUPERIOR', 4, 'libre', 150.00),
('403', 'DOBLE', 4, 'libre', 180.00),           ('404', 'MATRIMONIAL SUPERIOR', 4, 'libre', 220.00),
('405', 'PLATINIUM SUITE', 4, 'libre', 260.00),    ('501', 'TRIPLE', 5, 'libre', 200.00),
('502', 'EJECUTIVA SUPERIOR', 5, 'libre', 150.00), ('503', 'DOBLE', 5, 'libre', 180.00),
('504', 'MATRIMONIAL SUPERIOR', 5, 'libre', 220.00),('505', 'PLATINIUM SUITE', 5, 'libre', 260.00),
('601', 'TRIPLE', 6, 'libre', 200.00),             ('602', 'EJECUTIVA SUPERIOR', 6, 'libre', 150.00),
('603', 'DOBLE', 6, 'libre', 180.00),           ('604', 'MATRIMONIAL SUPERIOR', 6, 'libre', 220.00),
('605', 'PLATINIUM SUITE', 6, 'libre', 260.00);

INSERT INTO finanzas_categorias (modulo, tipo, nombre, orden) VALUES
('Flujo', 'Ingreso', 'DEPOS/TRANS.', 1),     ('Flujo', 'Ingreso', 'YAPE O PLIN', 2),
('Flujo', 'Ingreso', 'POS DOLARES', 3),       ('Flujo', 'Ingreso', 'POS SOLES', 4),
('Flujo', 'Ingreso', 'PESOS EFECTIVO', 5),    ('Flujo', 'Ingreso', 'DOLARES EFECTIVO', 6),
('Flujo', 'Ingreso', 'SOLES EFECTIVO', 7),     ('Flujo', 'Ingreso', 'REPOSICIÓN YAPE', 8),
('Flujo', 'Egreso',  'MERCADO', 1),           ('Flujo', 'Egreso',  'MOVILIDAD', 2),
('Flujo', 'Egreso',  'CAFETERÍA VEA-GENOVESA',3),('Flujo', 'Egreso',  'LAVANDERÍA', 4),
('Flujo', 'Egreso',  'ÚTILES DE ESCRITORIO', 5), ('Flujo', 'Egreso',  'RECEPCIÓN C.CH.', 6),
('Flujo', 'Egreso',  'SERV. REPUESTOS', 7),      ('Flujo', 'Egreso',  'PAGO A PERSONAL', 8),
('Flujo', 'Egreso',  'OTROS', 9),                ('C.Chica', 'Egreso', 'PANADERÍA', 1),
('C.Chica', 'Egreso', 'TIENDA', 2),            ('C.Chica', 'Egreso', 'MOVILIDAD', 3),
('C.Chica', 'Egreso', 'MERCADO', 4),           ('C.Chica', 'Ferretería', 'FERRETERÍA', 5),
('C.Chica', 'Egreso', 'FARMACIA', 6),          ('C.Chica', 'Egreso', 'LAVANDERÍA', 7),
('C.Chica', 'Egreso', 'PUBLICIDAD', 8),        ('C.Chica', 'Egreso', 'VUELTO', 9),
('C.Chica', 'Egreso', 'OTROS', 10),            ('C.Chica', 'Ingreso','REPOSICIÓN CAJA', 1);

INSERT INTO inventario_productos (nombre, categoria, refrigeradora, precio_venta, stock_actual) VALUES
('COCA COLA', 'BEBIDA', 1, 7.00, 10),      ('INCA COLA', 'BEBIDA', 1, 7.00, 10),
('AGUA SAN MATEO', 'BEBIDA', 1, 5.00, 10), ('AGUA SAN LUIS', 'BEBIDA', 1, 5.00, 10),
('CERV. CORONA', 'BEBIDA', 1, 10.00, 10),   ('CERV. CUZQUEÑA', 'BEBIDA', 1, 10.00, 10),
('VINO ROJO', 'VINO', 2, 35.00, 10),       ('VINO MORADO', 'VINO', 2, 35.00, 10),
('VINO AZUL', 'VINO', 2, 35.00, 10);

INSERT IGNORE INTO tipos_cambio (moneda_origen, moneda_destino, factor, fecha) VALUES
('USD', 'PEN', 3.7000, CURDATE()),
('CLP', 'PEN', 277.00, CURDATE());