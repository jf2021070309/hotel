<?php
require_once 'config/db.php';
$stmt = $pdo->query("DESCRIBE rooming_pax");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
