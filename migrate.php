<?php
require 'config/db.php';
try {
    $pdo->query("ALTER TABLE rooming_stays ADD COLUMN no_registrado TINYINT(1) DEFAULT 0");
    echo "OK";
} catch (Exception $e) {
    echo $e->getMessage();
}
