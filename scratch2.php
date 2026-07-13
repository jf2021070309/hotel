<?php
require 'config/db.php';
require 'app/Models/RoomingV2Model.php';
$m = new RoomingV2Model($pdo);
try {
    $res = $m->getReporte(7, 2026);
    echo "OK: " . count($res) . " registros\n";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
