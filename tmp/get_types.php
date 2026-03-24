<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT DISTINCT tipo FROM habitaciones");
$types = $stmt->fetchAll(PDO::FETCH_COLUMN);
echo "TYPES: " . implode(", ", $types);
