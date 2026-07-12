<?php
$pdo = new PDO('mysql:host=127.0.0.1;dbname=hotel_db', 'hotel_user', 'Surgas654321');
$stmt = $pdo->query('SELECT id, numero, estado FROM habitaciones WHERE estado != "libre"');
$rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($rooms);

$pdo->query("UPDATE habitaciones SET estado = 'libre' WHERE estado IN ('mantenimiento', 'sucio', 'limpieza')");
echo "Habitaciones actualizadas a libre.\n";
