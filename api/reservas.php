<?php
/**
 * api/reservas.php — Thin router for Reservas
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../app/Controllers/ReservasController.php';

protegerPorRol('cajera', 'reservas');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new ReservasController($pdo);

switch ($action) {
    case 'datos':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->datos());
        break;

    case 'pago_rapido':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->pagoRapido($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'late_checkout':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->lateCheckout($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;
    
    case 'quick_reserva':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->quickReserva($input);
        json_response($res['ok'], $res['id'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
