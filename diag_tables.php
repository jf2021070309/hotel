<?php
require_once 'config/db.php';
$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "TABLES:\n";
print_r($tables);

foreach(['rooming_stays', 'rooming', 'rooming_consumos'] as $t) {
    if (in_array($t, $tables)) {
        echo "\nCOLUMNS FOR $t:\n";
        print_r($pdo->query("DESCRIBE $t")->fetchAll(PDO::FETCH_ASSOC));
    } else {
        echo "\n$t DOES NOT EXIST\n";
    }
}
