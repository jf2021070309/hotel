<?php
require_once __DIR__ . '/config/db.php';

try {
    $pdo->exec("ALTER TABLE flujo_caja_movimientos 
                ADD COLUMN sobre_fecha DATE DEFAULT NULL,
                ADD COLUMN sobre_turno ENUM('MAÑANA', 'TARDE') DEFAULT NULL");
    echo "Base de datos actualizada con éxito.";
} catch (PDOException $e) {
    if ($e->getCode() == '42S21') {
        echo "Las columnas ya existen.";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
