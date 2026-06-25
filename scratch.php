<?php
$pdo = new PDO('mysql:host=localhost;dbname=hotel', 'root', '');
$stmt = $pdo->query("SELECT f.id, f.fecha, f.turno, f.estado, m.monto, c.nombre FROM flujo_caja_movimientos m JOIN flujo_caja f ON m.flujo_id = f.id JOIN finanzas_categorias c ON m.categoria_id = c.id WHERE c.nombre LIKE '%RECEPCI%C.CH%' ORDER BY m.id DESC LIMIT 10");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
