<?php
// scratch_list_mendoza.php
$db = new mysqli('127.0.0.1', 'root', '', 'hotel_db');
echo "--- REPORTE MENDOZA HABS --- \n";
$res = $db->query("SELECT * FROM reporte_mendoza_habs LIMIT 3");
while($row = $res->fetch_assoc()) print_r($row);

echo "\n--- REPORTE MENDOZA OTROS --- \n";
$res = $db->query("SELECT * FROM reporte_mendoza_otros LIMIT 3");
while($row = $res->fetch_assoc()) print_r($row);
