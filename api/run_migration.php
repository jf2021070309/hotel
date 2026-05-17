<?php
/**
 * api/run_migration.php
 * Script temporal para actualizar la base de datos en Railway.
 */
require_once __DIR__ . '/../config/db.php';

echo "<pre>";
echo "Iniciando migración de base de datos en Railway...\n";

try {
    // Modificar la columna stay_id para que permita valores NULL
    $sql = "ALTER TABLE `rooming_pax` MODIFY `stay_id` INT(10) UNSIGNED NULL;";
    $pdo->exec($sql);
    
    echo "=============================================\n";
    echo "¡ÉXITO! La columna stay_id ahora permite NULL.\n";
    echo "Ya puedes registrar nuevos clientes sin problemas en Railway.\n";
    echo "=============================================\n";
    echo "\nIMPORTANTE: Por seguridad, elimina este archivo de tu repositorio después de ejecutarlo.";
} catch (Exception $e) {
    echo "=============================================\n";
    echo "ERROR al modificar la estructura:\n";
    echo $e->getMessage() . "\n";
    echo "=============================================\n";
}
echo "</pre>";
