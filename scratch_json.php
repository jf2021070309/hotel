<?php
$pdo = new PDO("mysql:host=localhost;dbname=hotel;charset=utf8", "root", "");
$stmt=$pdo->query("SELECT id, total_pago, pagos_json, observaciones FROM rooming_stays ORDER BY id DESC LIMIT 5");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
