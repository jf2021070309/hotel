<?php
require_once __DIR__ . '/../config/db.php';

try {
    $pdo->exec("ALTER TABLE limpieza_registros MODIFY COLUMN tipo_limpieza ENUM('estimacion', 'estadía', 'reposo', 'salida', 'programada') NOT NULL");
    echo "ENUM actualizado correctamente.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
