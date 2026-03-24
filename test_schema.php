<?php
require 'config/db.php';
$stmt = $pdo->query("DESCRIBE rooming_stays");
foreach($stmt as $r) echo $r['Field'] . " - " . $r['Type'] . "\n";
