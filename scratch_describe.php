<?php
// scratch_describe.php
$db = new mysqli('127.0.0.1', 'root', '', 'hotel_db');
$res = $db->query("DESCRIBE rooming_consumos");
while($row = $res->fetch_assoc()) {
    echo "Field: {$row['Field']} | Type: {$row['Type']}\n";
}
