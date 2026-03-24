<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DESCRIBE gastos_yape");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo implode("\n", $cols);
$stmt = $pdo->query("DESCRIBE gastos_yape_detalle");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "\n-- DETALLE --\n";
echo implode("\n", $cols);
