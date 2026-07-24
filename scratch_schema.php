<?php
$pdo = new PDO("mysql:host=localhost;dbname=hotel;charset=utf8", "root", "");
$stmt = $pdo->query("SHOW CREATE TABLE rooming_consumos");
file_put_contents('scratch_consumos.txt', $stmt->fetchColumn(1));
$stmt2 = $pdo->query("SHOW CREATE TABLE flujo_caja_movimientos");
file_put_contents('scratch_flujo.txt', $stmt2->fetchColumn(1));
