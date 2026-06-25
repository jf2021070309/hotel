<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel', 'root', '');
$stmt = $pdo->query("SELECT f.fecha, f.turno, m.monto, c.nombre, m.fecha as m_fecha, m.id FROM flujo_caja_movimientos m JOIN flujo_caja f ON m.flujo_id = f.id LEFT JOIN finanzas_categorias c ON m.categoria_id = c.id ORDER BY m.id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
