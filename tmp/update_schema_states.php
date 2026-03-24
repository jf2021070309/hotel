<?php
require_once __DIR__ . '/../config/db.php';
try {
    // 1. Update rooming_stays estado
    $pdo->exec("ALTER TABLE rooming_stays MODIFY COLUMN estado ENUM('activo', 'finalizado', 'reservado', 'late_checkout', 'cancelado') NOT NULL DEFAULT 'activo'");
    
    // 2. Update habitaciones estado
    $pdo->exec("ALTER TABLE habitaciones MODIFY COLUMN estado ENUM('libre', 'ocupado', 'reservado', 'limpieza', 'mantenimiento') NOT NULL DEFAULT 'libre'");
    
    echo "Schema updated successfully with new states: reservado, mantenimiento, cancelado.\n";
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage();
}
