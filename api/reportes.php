<?php
// ============================================================
// api/reportes.php
// GET /?tipo=diario&fecha=YYYY-MM-DD
// GET /?tipo=mensual&year=YYYY&month=M
// ============================================================
require_once '../config/conexion.php';
require_once '../app/Controllers/ReporteController.php';

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') json_response(true, null);
if ($_SERVER['REQUEST_METHOD'] !== 'GET')
    json_response(false, null, 405, 'Método no permitido');

$ctrl = new ReporteController($conn);
$tipo = $_GET['tipo'] ?? 'diario';

if ($tipo === 'diario') {
    $fecha = $_GET['fecha'] ?? date('Y-m-d');
    $ctrl->cuadreDiario($fecha);
} elseif ($tipo === 'mensual') {
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));
    $ctrl->mensual($year, $month);
} elseif ($tipo === 'graficos') {
    $year  = (int)($_GET['year']  ?? date('Y'));
    $month = (int)($_GET['month'] ?? date('n'));
    $ctrl->graficos($year, $month);
} else {
    json_response(false, null, 400, 'Tipo de reporte inválido. Use: diario, mensual o graficos');
}
