<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SHOW CREATE TABLE desayunos_detalle");
print_r($stmt->fetch(PDO::FETCH_ASSOC));
