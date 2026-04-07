<?php
require_once 'config/db.php';
try {
    $pdo->exec("ALTER TABLE flujo_caja_movimientos ADD COLUMN IF NOT EXISTS sobre_fecha DATE NULL");
    $pdo->exec("ALTER TABLE flujo_caja_movimientos ADD COLUMN IF NOT EXISTS sobre_turno VARCHAR(20) NULL");
    echo "Base de datos actualizada con éxito.";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
