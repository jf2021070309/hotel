<?php
require_once 'c:/xampp/htdocs/hotel/config/db.php';
$stmt = $pdo->query("DESCRIBE limpieza_registros");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
