<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
try {
    require 'config/db.php';
    $pdo->exec('ALTER TABLE rooming_consumos DROP FOREIGN KEY fk_consumos_producto');
    $pdo->exec('ALTER TABLE rooming_consumos MODIFY producto_id INT NULL');
    echo "ALTERED";
} catch (Throwable $e) {
    echo "ERROR: " . $e->getMessage();
}
