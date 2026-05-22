<?php
require_once dirname(__DIR__) . '/config/db.php';
try {
    // Check if column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM rooming_stays LIKE 'fecha_checkout_original'");
    $exists = $stmt->fetch();
    
    if (!$exists) {
        $pdo->exec("ALTER TABLE rooming_stays ADD COLUMN fecha_checkout_original DATE DEFAULT NULL AFTER fecha_checkout");
        echo "Columna fecha_checkout_original agregada con éxito.\n";
    } else {
        echo "La columna fecha_checkout_original ya existe.\n";
    }
    
    // Initialize existing records
    $affected = $pdo->exec("UPDATE rooming_stays SET fecha_checkout_original = fecha_checkout WHERE fecha_checkout_original IS NULL");
    echo "Inicializados $affected registros antiguos con su fecha_checkout inicial.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

