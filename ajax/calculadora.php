<?php
/**
 * api/calculadora.php — Router API para el módulo Calculadora
 */
require_once BASE_PATH . 'config/db.php';
require_once BASE_PATH . 'app/Middleware/session.php';
require_once BASE_PATH . 'app/Middleware/auth.php';
require_once BASE_PATH . 'app/Controllers/CalculadoraController.php';

protegerPorRol('cajera', 'calculadora');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$ctrl = new CalculadoraController($pdo);

switch ($action) {
    case 'getTipoCambio':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $ctrl->getTipoCambio(); // responde directamente con json_response
        break;

    case 'guardarTC':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $ctrl->guardarTC($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'guardarParams':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $ctrl->guardarParams($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
