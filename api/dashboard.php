<?php
// ============================================================
// api/dashboard.php — GET stats + habitaciones
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/ReporteController.php';


require_once __DIR__ . '/../auth/session.php';
if (!estaAutenticado()) { json_response(false, null, 401, 'No autorizado'); }
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    json_response(true, null);
}

$ctrl = new ReporteController($conn);
$ctrl->dashboard();

