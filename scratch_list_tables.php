<?php
// scratch_list_tables.php
$db = new mysqli('127.0.0.1', 'root', '', 'hotel_db');
$res = $db->query("SHOW TABLES");
while($row = $res->fetch_row()) {
    echo $row[0] . "\n";
}
