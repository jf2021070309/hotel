<?php
/**
 * api/yape.php — Thin router for Gastos Yape
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../app/Controllers/YapeController.php';

protegerPorRol('cajera', 'yape');

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new YapeController($pdo);

switch ($action) {
    case 'listar':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->listar();
        json_response($res['ok'], $res['data'] ?? null, 200, $res['msg'] ?? '');
        break;

    case 'detalle':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $id = (int)($_GET['id'] ?? 0);
        $res = $controller->detalle($id);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 404, $res['msg'] ?? '');
        break;

    case 'guardar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->guardar($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'cerrar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->cerrar($input);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
