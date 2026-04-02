<?php
/**
 * api/flujo.php — Thin router for Flujo de Caja
 */
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../auth/session.php';
require_once __DIR__ . '/../auth/middleware.php';
require_once __DIR__ . '/../app/Controllers/FlujoController.php';

protegerPorRol('cajera', 'flujo');
 // Cajeras, Supervisores, Admin

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];
$input  = json_decode(file_get_contents('php://input'), true) ?? [];

$controller = new FlujoController($pdo);

switch ($action) {
    case 'listar':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->listar($_GET));
        break;

    case 'detalle':
        try {
            if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
            $id = (int)($_GET['id'] ?? 0);
            $res = $controller->detalle($id);
            json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 404, $res['msg'] ?? '');
        } catch (Exception $e) {
            json_response(false, null, 500, 'Error interno: ' . $e->getMessage());
        }
        break;

    case 'guardar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        $res = $controller->guardar($input);
        json_response($res['ok'], $res['data'] ?? null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'cerrar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        protegerPorRol('admin'); // Only supervisors usually, but maybe admin config. Let's let the controller handle it or check here.
        // Assuming cajeras can close their own shift. If only admin can close, uncomment the line below:
        // protegerPorRol('admin'); 
        $id = (int)($input['id'] ?? 0);
        $res = $controller->cerrar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'depositar':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        protegerPorRol('admin'); // Only admin/supervisor can deposit
        $id = (int)($input['id'] ?? 0);
        $res = $controller->depositar($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'reabrir':
        if ($method !== 'POST') json_response(false, null, 405, 'Método no permitido');
        protegerPorRol('admin'); 
        $id = (int)($input['id'] ?? 0);
        $res = $controller->reabrir($id);
        json_response($res['ok'], null, $res['ok'] ? 200 : 422, $res['msg']);
        break;

    case 'resumen_dia':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        $fecha = $_GET['fecha'] ?? date('Y-m-d');
        json_response(true, $controller->resumenDia($fecha));
        break;

    case 'categorias':
        if ($method !== 'GET') json_response(false, null, 405, 'Método no permitido');
        json_response(true, $controller->categorias());
        break;

    default:
        json_response(false, null, 400, 'Acción no válida');
        break;
}
