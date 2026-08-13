<?php
require_once __DIR__ . '/config/db.php';
$stmt = $pdo->query("DESCRIBE rooming_stays");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
