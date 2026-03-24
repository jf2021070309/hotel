<?php
require_once __DIR__ . '/../config/db.php';
$stmt = $pdo->query("SELECT * FROM finanzas_categorias");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
