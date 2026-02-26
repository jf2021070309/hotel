<?php
// ============================================================
// api/dashboard.php — GET stats + habitaciones
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/ReporteController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    json_response(true, null);
}

$ctrl = new ReporteController($conn);
$ctrl->dashboard();
