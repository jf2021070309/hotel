<?php
require 'config/db.php';
global $pdo;
try {
    $pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN reservas_mark VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN salidas_mark VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN repasos_mark VARCHAR(255) DEFAULT NULL");
    $pdo->exec("ALTER TABLE limpieza_registros ADD COLUMN pendientes_mark VARCHAR(255) DEFAULT NULL");
    echo "Columns added successfully.\n";
} catch (Exception $e) {
    echo "Error or columns already exist: " . $e->getMessage() . "\n";
}
