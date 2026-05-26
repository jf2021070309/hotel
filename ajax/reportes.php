<?php
/**
 * api/reportes.php
 */
require_once '../config/db.php';
require_once '../app/Middleware/session.php';
require_once '../app/Middleware/auth.php';
require_once '../app/Controllers/ReportesController.php';

protegerPorRol('cajera', 'reportes');

$action = $_GET['action'] ?? '';
$controller = new ReportesController($pdo);

switch ($action) {
    case 'facturas':
        $desde  = $_GET['desde']  ?? date('Y-m-d');
        $hasta  = $_GET['hasta']  ?? date('Y-m-d');
        $estado = $_GET['estado'] ?? null;
        json_response(true, $controller->facturas($desde, $hasta, $estado));
        break;

    case 'corporativas':
        json_response(true, $controller->corporativasExtranjeras());
        break;

    case 'recurrentes':
        $min = (int)($_GET['min'] ?? 2);
        json_response(true, $controller->extranjerosRecurrentes($min));
        break;

    case 'mendoza':
        $mes  = (int)($_GET['mes']  ?? date('m'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->mendoza($mes, $anio));
        break;

    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}
