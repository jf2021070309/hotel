<?php
require 'config/db.php';
$stmt = $pdo->query("SHOW COLUMNS FROM clientes");
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
