<?php
/**
 * Endpoints del Módulo de Rooming (FrontDesk).
 * 
 * Gestiona el ciclo de vida del huésped: listar ocupación, detalles de estadía,
 * procesos de check-in, check-out, registro de pagos y late checkouts.
 * 
 * @package API\Rooming
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/RoomingController.php';

protegerPorRol('cajera', 'rooming');

$action = $_GET['action'] ?? 'listar';
$input = json_decode(file_get_contents('php://input'), true);
$controller = new RoomingController($pdo);

switch ($action) {
    case 'listar':
        json_response(true, $controller->listarActivos());
        break;
    case 'detalle':
        json_response(true, $controller->detalle((int)$_GET['id']));
        break;
    case 'checkin':
        json_response_obj($controller->checkin($input));
        break;
    case 'checkout':
        json_response_obj($controller->checkout((int)$input['id']));
        break;
    case 'pago':
        json_response_obj($controller->registrarPago($input));
        break;
    case 'late_checkout':
        json_response_obj($controller->lateCheckout($input));
        break;
    case 'reporte_pax':
        $mes  = (int)($_GET['mes']  ?? date('n'));
        $anio = (int)($_GET['anio'] ?? date('Y'));
        json_response(true, $controller->reportePax($mes, $anio));
        break;
    case 'guardar_reporte_pax':
        json_response(true, $controller->guardarReportePax($input['rows'] ?? []));
        break;
    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}

function json_response_obj($res) {
    json_response($res['ok'], $res['data'] ?? null, $res['code'] ?? 200, $res['msg'] ?? '');
}
