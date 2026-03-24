<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DESCRIBE reporte_mendoza");
echo "REPORTE MENDOZA:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("DESCRIBE reporte_alex");
echo "\nREPORTE ALEX:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
