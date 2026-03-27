<?php
require_once 'config/db.php';
$stmt = $pdo->query('SHOW TABLES');
var_dump($stmt->fetchAll(PDO::FETCH_COLUMN));
