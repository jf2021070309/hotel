<?php
require_once __DIR__ . '/../config/db.php';
echo "--- GASTOS YAPE (CABECERA) ---\n";
$stmt = $pdo->query("SELECT * FROM gastos_yape");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
echo "\n--- DETALLE ---\n";
$stmt = $pdo->query("SELECT * FROM gastos_yape_detalle");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
