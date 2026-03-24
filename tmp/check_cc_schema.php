<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("DESCRIBE caja_chica_movimientos");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
