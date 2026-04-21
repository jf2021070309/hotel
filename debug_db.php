<?php
require_once 'config/db.php';
$stmt = $pdo->query("SELECT * FROM desayunos ORDER BY id DESC LIMIT 5");
echo "DESAYUNOS:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));

$stmt = $pdo->query("SELECT * FROM desayunos_detalle ORDER BY id DESC LIMIT 5");
echo "\nDETALLES:\n";
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
