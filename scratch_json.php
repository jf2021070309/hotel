<?php
require 'config/db.php';
$stmt=$pdo->query("SELECT id, pagos_json FROM rooming_stays WHERE pagos_json IS NOT NULL AND pagos_json != '[]' LIMIT 3");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
