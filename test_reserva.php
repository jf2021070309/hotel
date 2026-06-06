<?php
require 'c:\xampp\htdocs\hotel\config\db.php';
require 'c:\xampp\htdocs\hotel\app\Models\ReservasModel.php';
$m = new ReservasModel($pdo);
try {
    $id = $m->registrarReservaRapida([
        'hab_id' => 1,
        'fecha_inicio' => '2026-06-05',
        'noches' => 1,
        'titular' => 'Test',
        'observaciones' => '',
        'usuario_id' => 1,
        'canal' => 'DIRECTO'
    ]);
    echo "OK: ID $id";
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage();
}
