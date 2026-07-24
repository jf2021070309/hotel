<?php
$pdo = new PDO("mysql:host=localhost;dbname=hotel;charset=utf8", "root", "");
$stmt = $pdo->query("SHOW COLUMNS FROM rooming_consumos");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
