<?php
/**
 * api/rooming.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/RoomingController.php';

protegerPorRol('cajera');

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
    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}

function json_response_obj($res) {
    json_response($res['ok'], $res['data'] ?? null, $res['code'] ?? 200, $res['msg'] ?? '');
}
