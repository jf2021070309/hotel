<?php
/**
 * api/medios_pago.php
 */
require_once '../config/db.php';
require_once '../auth/session.php';
require_once '../auth/middleware.php';
require_once '../app/Controllers/MedioPagoController.php';

protegerPorRol('cajera');

$action = $_GET['action'] ?? 'listar';

// Acciones que requieren rol ADMIN
if (in_array($action, ['guardar', 'toggle', 'eliminar'])) {
    protegerPorRol('cajera', 'medios_pago');
}
$input = json_decode(file_get_contents('php://input'), true);
$controller = new MedioPagoController($pdo);

switch ($action) {
    case 'listar':
        json_response_obj($controller->listar());
        break;
    case 'guardar':
        json_response_obj($controller->guardar($input));
        break;
    case 'toggle':
        json_response_obj($controller->toggle((int)$_GET['id']));
        break;
    case 'eliminar':
        json_response_obj($controller->eliminar((int)$_GET['id']));
        break;
    default:
        json_response(false, null, 400, "Acción no válida");
        break;
}

function json_response_obj($res) {
    json_response($res['ok'], $res['data'] ?? null, $res['code'] ?? 200, $res['msg'] ?? '');
}
