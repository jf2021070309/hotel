<?php
$pdo = new PDO('mysql:host=localhost;dbname=hotel_db', 'root', '');
print_r($pdo->query('DESCRIBE rooming_consumos')->fetchAll(PDO::FETCH_ASSOC));
