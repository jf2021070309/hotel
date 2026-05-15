<?php
// scratch_check_data.php
$db = new mysqli('127.0.0.1', 'root', '', 'hotel_db');

if ($db->connect_error) {
    die("Connection failed: " . $db->connect_error);
}

echo "--- ANTICIPOS (Hab 205) --- \n";
$res = $db->query("SELECT id, stay_id, monto, observacion, fecha FROM anticipos WHERE stay_id IN (SELECT id FROM rooming_stays WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE numero='205')) ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | StayID: {$row['stay_id']} | Monto: {$row['monto']} | Obs: {$row['observacion']} | Fecha: {$row['fecha']}\n";
}

echo "\n--- CONSUMOS (Hab 205) --- \n";
$res = $db->query("SELECT id, habitacion_id, producto, total, metodo_pago, fecha FROM rooming_consumos WHERE habitacion_id IN (SELECT id FROM habitaciones WHERE numero='205') ORDER BY id DESC LIMIT 5");
while($row = $res->fetch_assoc()) {
    echo "ID: {$row['id']} | Prod: {$row['producto']} | Total: {$row['total']} | Metodo: {$row['metodo_pago']} | Fecha: {$row['fecha']}\n";
}
