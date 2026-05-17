<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    require_once __DIR__ . '/../config/db.php';
    
    echo "Conectado con éxito!\n";
    
    // Verificar si existe la columna vip en la tabla rooming_pax
    $desc = $pdo->query("DESCRIBE rooming_pax")->fetchAll();
    echo "Columnas en rooming_pax:\n";
    $hasVip = false;
    foreach ($desc as $col) {
        echo " - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        if ($col['Field'] === 'vip') {
            $hasVip = true;
        }
    }
    
    if (!$hasVip) {
        echo "Alerta: No se encontró la columna 'vip' en la tabla rooming_pax!\n";
        echo "Intentando agregar la columna manualmente:\n";
        $pdo->exec("ALTER TABLE `rooming_pax` ADD COLUMN `vip` TINYINT(1) DEFAULT 0;");
        echo "Columna 'vip' agregada con éxito!\n";
    } else {
        echo "La columna 'vip' sí existe!\n";
    }
    
    // Intentar hacer un update de prueba
    echo "Ejecutando update de prueba...\n";
    $stmt = $pdo->prepare("UPDATE rooming_pax SET vip = 1 WHERE documento_num = ? AND es_titular = 1");
    $res = $stmt->execute(['72883481']);
    echo "Resultado del UPDATE: " . ($res ? "EXITOSO" : "FALLIDO") . "\n";
    
} catch (Throwable $e) {
    echo "ERROR DETECTADO: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
}
