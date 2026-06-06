<?php
/**
 * api/reservas.php - Thin router for Reservas
 */
require_once __DIR__ . '/../ajax/bootstrap.php';
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/ReservasController.php';

protegerPorRol('cajera', 'reservas');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new ReservasController($pdo);

switch ($action) {
    case 'datos':
        if ($method !== 'GET') json_response(false, null, 405, 'Metodo no permitido');
        json_response(true, $controller->datos());
        break;

    case 'pago_rapido':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->pagoRapido($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'late_checkout':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->lateCheckout($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    
    case 'quick_reserva':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->quickReserva($input);
        json_response($res['ok'], $res['id'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'editar_quick_reserva':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->editarQuickReserva($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'checkin':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->checkin($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'rechazar':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->rechazar($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'estado_hab':
        if ($method !== 'POST') json_response(false, null, 405, 'Metodo no permitido');
        $res = $controller->cambiarEstadoHab($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    default:
        json_response(false, null, 400, 'Accion no valida');
        break;
}
