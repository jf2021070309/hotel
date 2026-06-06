<?php
require 'c:\xampp\htdocs\hotel\config\db.php';
require 'c:\xampp\htdocs\hotel\app\Models\ReservasModel.php';
$m = new ReservasModel($pdo);
try {
    // Attempt to activate stay #5 (or the last inserted one)
    $stmt = $pdo->query("SELECT id FROM rooming_stays WHERE estado = 'reservado' ORDER BY id DESC LIMIT 1");
    $id = $stmt->fetchColumn();
    if ($id) {
        $m->activarStay($id);
        echo "OK: Stay $id activated.";
    } else {
        echo "No reserved stays found.";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
