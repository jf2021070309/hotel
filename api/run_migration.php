<?php
/**
 * api/run_migration.php
 * Script temporal para actualizar la base de datos en Railway (agregar columna ruc a rooming_pax).
 */
require_once __DIR__ . '/../config/db.php';

echo "<pre>";
echo "Iniciando migración de base de datos en Railway...\n";

try {
    // 1. Modificar stay_id para permitir NULL
    $sql1 = "ALTER TABLE `rooming_pax` MODIFY `stay_id` INT(10) UNSIGNED NULL;";
    $pdo->exec($sql1);
    echo "✓ Columna stay_id modificada para permitir NULL.\n";

    // 2. Agregar columna ruc si no existe
    $sql2 = "ALTER TABLE `rooming_pax` ADD COLUMN `ruc` VARCHAR(20) DEFAULT NULL AFTER `documento_num`;";
    $pdo->exec($sql2);
    echo "✓ Columna ruc agregada a la tabla rooming_pax.\n";

    echo "=============================================\n";
    echo "¡ÉXITO! Base de datos de Railway completamente actualizada.\n";
    echo "=============================================\n";
} catch (Exception $e) {
    echo "=============================================\n";
    echo "INFORMACIÓN/ERROR al modificar la estructura:\n";
    echo $e->getMessage() . "\n";
    echo "=============================================\n";
}
echo "</pre>";
