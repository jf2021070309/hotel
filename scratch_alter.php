<?php
require 'config/db.php';
$pdo->exec('ALTER TABLE rooming_consumos MODIFY producto_id INT NULL');
echo "ALTERED";
