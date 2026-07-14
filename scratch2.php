<?php
require 'config/db.php';
try {
    $pdo->exec("ALTER TABLE flujo_caja ADD COLUMN declarado_pen DECIMAL(10,2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE flujo_caja ADD COLUMN declarado_usd DECIMAL(10,2) DEFAULT NULL");
    $pdo->exec("ALTER TABLE flujo_caja ADD COLUMN declarado_clp DECIMAL(10,2) DEFAULT NULL");
    echo "OK";
} catch (Exception $e) {
    echo $e->getMessage();
}
