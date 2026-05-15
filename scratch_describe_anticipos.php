<?php
// scratch_describe_anticipos.php
$db = new mysqli('127.0.0.1', 'root', '', 'hotel_db');
$res = $db->query("DESCRIBE anticipos");
while($row = $res->fetch_assoc()) {
    echo "Field: {$row['Field']} | Type: {$row['Type']}\n";
}
