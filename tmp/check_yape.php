<?php
$pdo = new PDO('mysql:host=localhost;dbname=hotel_db', 'root', '');
$stmt = $pdo->query("SELECT id, fecha, turno, rubro, monto, estado FROM gastos_yape");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC), JSON_PRETTY_PRINT);
