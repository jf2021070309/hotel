<?php
require_once __DIR__ . '/../config/db.php';
try {
    $pdo->exec("ALTER TABLE caja_chica_movimientos ADD COLUMN flujo_movimiento_id INT UNSIGNED DEFAULT NULL AFTER usuario_id");
    echo "Sync column added to caja_chica_movimientos.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
