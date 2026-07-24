<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM flujo_caja_movimientos");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
$stmt2 = $pdo->query("SHOW COLUMNS FROM rooming_consumos");
print_r($stmt2->fetchAll(PDO::FETCH_ASSOC));
