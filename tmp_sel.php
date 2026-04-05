<?php
require_once 'c:/xampp/htdocs/hotel/config/db.php';
$stmt = $pdo->query("SELECT * FROM limpieza_registros LIMIT 1");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
if ($row) {
    print_r(array_keys($row));
} else {
    echo "No rows.";
    $stmt = $pdo->query("SHOW COLUMNS FROM limpieza_registros");
    print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
}
