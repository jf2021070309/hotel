<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("SELECT numero, tipo FROM habitaciones WHERE tipo = 'RESERVA'");
$res = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($res);

$stmt2 = $pdo->query("SELECT id, habitacion_id, tipo_hab_declarado FROM rooming_stays WHERE tipo_hab_declarado = 'RESERVA' LIMIT 5");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
